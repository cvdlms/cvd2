<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/premium_helper.php';

$username = $_SESSION['username'];
if (!isPremiumUser($username)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho tài khoản Premium']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

function create_slug($string) {
    $vietnamese = [
        'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y', 'đ' => 'd',
        'Á' => 'A', 'À' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
        'Ă' => 'A', 'Ắ' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
        'Â' => 'A', 'Ấ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
        'É' => 'E', 'È' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
        'Ê' => 'E', 'Ế' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
        'Ô' => 'O', 'Ố' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
        'Ơ' => 'O', 'Ớ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
        'Ư' => 'U', 'Ứ' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
        'Ý' => 'Y', 'Ỳ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y', 'Đ' => 'D',
    ];
    
    $string = str_replace(array_keys($vietnamese), array_values($vietnamese), $string);
    $string = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $string);
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return strtolower($string);
}

// Get lesson plan ID
$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo "Missing lesson plan ID";
    exit;
}

// Load lesson plan
$dataFile = __DIR__ . '/../data/lesson_plans.json';
$lessonPlans = json_decode(file_get_contents($dataFile), true) ?: [];

if (!isset($lessonPlans[$id])) {
    echo "Lesson plan not found";
    exit;
}

$plan = $lessonPlans[$id];

// Check permission
$subjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$teacherSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$mySubjects = $teacherSubjects[$username] ?? [];

$canView = ($plan['teacher_username'] === $username) || 
           ($plan['share_with_others'] && in_array($plan['subject_id'], $mySubjects));

if (!$canView) {
    echo "Access denied";
    exit;
}

// Load subject name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$allSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjectName = '';
foreach ($allSubjects as $subj) {
    if ($subj['id'] === $plan['subject_id']) {
        $subjectName = $subj['name'];
        break;
    }
}

// Create Word document
$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Times New Roman');
$phpWord->setDefaultFontSize(13);

// Create section
$section = $phpWord->addSection([
    'marginLeft' => 1134,
    'marginRight' => 1134,
    'marginTop' => 1134,
    'marginBottom' => 1134,
]);

// Title
$section->addText(
    mb_strtoupper($plan['basic_info']['ten_bai_day']),
    ['bold' => true, 'size' => 16, 'name' => 'Times New Roman'],
    ['alignment' => 'center', 'spaceAfter' => 200]
);

// Basic info
$textRun = $section->addTextRun(['alignment' => 'center']);
$textRun->addText('Số tiết: ', ['bold' => true]);
$textRun->addText($plan['basic_info']['so_tiet'] . ' | ');
$textRun->addText('Tiết PPCT: ', ['bold' => true]);
$textRun->addText(($plan['basic_info']['tiet_ppct'] ?: 'N/A') . ' | ');
$textRun->addText('Ngày dạy: ', ['bold' => true]);
$textRun->addText($plan['basic_info']['ngay_day']);
$section->addTextBreak();

$section->addText(
    'Môn học: ' . $subjectName,
    ['italic' => true],
    ['alignment' => 'center', 'spaceAfter' => 300]
);

// 1. Objectives
$section->addText('1. MỤC TIÊU', ['bold' => true, 'size' => 14], ['spaceAfter' => 100]);

$section->addText('   • Kiến thức:', ['bold' => true], ['spaceAfter' => 50]);
$section->addText('      ' . ($plan['muc_tieu']['kien_thuc'] ?: 'N/A'), [], ['spaceAfter' => 100]);

$section->addText('   • Năng lực:', ['bold' => true], ['spaceAfter' => 50]);
$section->addText('      ' . ($plan['muc_tieu']['nang_luc'] ?: 'N/A'), [], ['spaceAfter' => 100]);

$section->addText('   • Năng lực số:', ['bold' => true], ['spaceAfter' => 50]);
$section->addText('      ' . ($plan['muc_tieu']['nang_luc_so'] ?: 'N/A'), [], ['spaceAfter' => 100]);

$section->addText('   • Phẩm chất:', ['bold' => true], ['spaceAfter' => 50]);
$section->addText('      ' . ($plan['muc_tieu']['pham_chat'] ?: 'N/A'), [], ['spaceAfter' => 200]);

// 2. Equipment
$section->addText('2. THIẾT BỊ DẠY HỌC VÀ HỌC LIỆU', ['bold' => true, 'size' => 14], ['spaceAfter' => 100]);
$section->addText($plan['thiet_bi'] ?: 'N/A', [], ['spaceAfter' => 200]);

// 3. Activities
$section->addText('3. TIẾN TRÌNH DẠY HỌC', ['bold' => true, 'size' => 14], ['spaceAfter' => 100]);

foreach ($plan['hoat_dong'] as $idx => $activity) {
    $section->addText(
        'Hoạt động ' . ($idx + 1) . ': ' . $activity['ten'],
        ['bold' => true, 'color' => '0000FF'],
        ['spaceAfter' => 100]
    );
    
    $section->addText('   a) Mục tiêu:', ['bold' => true, 'size' => 12], ['spaceAfter' => 50]);
    $section->addText('      ' . ($activity['muc_tieu'] ?: 'N/A'), [], ['spaceAfter' => 100]);
    
    $section->addText('   b) Nội dung:', ['bold' => true, 'size' => 12], ['spaceAfter' => 50]);
    $section->addText('      ' . ($activity['noi_dung'] ?: 'N/A'), [], ['spaceAfter' => 100]);
    
    $section->addText('   c) Sản phẩm:', ['bold' => true, 'size' => 12], ['spaceAfter' => 50]);
    $section->addText('      ' . ($activity['san_pham'] ?: 'N/A'), [], ['spaceAfter' => 100]);
    
    $section->addText('   d) Tổ chức thực hiện:', ['bold' => true, 'size' => 12], ['spaceAfter' => 50]);
    
    $section->addText('      → Giao nhiệm vụ học tập:', ['italic' => true], ['spaceAfter' => 50]);
    $section->addText('         ' . ($activity['to_chuc']['giao_nhiem_vu'] ?: 'N/A'), [], ['spaceAfter' => 80]);
    
    $section->addText('      → Thực hiện nhiệm vụ:', ['italic' => true], ['spaceAfter' => 50]);
    $section->addText('         ' . ($activity['to_chuc']['thuc_hien'] ?: 'N/A'), [], ['spaceAfter' => 80]);
    
    $section->addText('      → Báo cáo, thảo luận:', ['italic' => true], ['spaceAfter' => 50]);
    $section->addText('         ' . ($activity['to_chuc']['bao_cao'] ?: 'N/A'), [], ['spaceAfter' => 80]);
    
    $section->addText('      → Kết luận, nhận định:', ['italic' => true], ['spaceAfter' => 50]);
    $section->addText('         ' . ($activity['to_chuc']['ket_luan'] ?: 'N/A'), [], ['spaceAfter' => 200]);
}

// 4. Homework
$section->addText('4. HƯỚNG DẪN VỀ NHÀ', ['bold' => true, 'size' => 14], ['spaceAfter' => 100]);
$section->addText($plan['huong_dan_ve_nha'] ?: 'N/A', [], ['spaceAfter' => 200]);

// Footer
$section->addTextBreak(2);
$textRun = $section->addTextRun(['alignment' => 'right']);
$textRun->addText('Ngày xuất: ' . date('d/m/Y'), ['italic' => true, 'size' => 11]);

// Generate filename
$filename = create_slug($plan['basic_info']['ten_bai_day']) . '_' . date('Ymd') . '.docx';

// Clear output buffer
ob_end_clean();

// Set headers
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Save to output
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
