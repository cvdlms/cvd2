<?php
ob_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/export_latex_errors.log');

include '../includes/session_check.php';
include '../includes/premium_helper.php';

$username = $_SESSION['username'];
if (!isPremiumUser($username)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho tài khoản Premium']);
    exit;
}

// Check if vendor/autoload.php exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lỗi</title></head><body>';
    echo '<h2>Lỗi: Chưa cài đặt thư viện PhpWord</h2>';
    echo '<p>Vui lòng chạy lệnh sau trong thư mục gốc của dự án:</p>';
    echo '<pre>composer require phpoffice/phpword</pre>';
    echo '<p>Hoặc liên hệ admin để cài đặt.</p>';
    echo '</body></html>';
    exit;
}

require_once $autoloadPath;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

function preserveLatex($text) {
    if (empty($text)) return '';
    
    // Remove HTML tags but preserve LaTeX
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    $text = str_replace(['&nbsp;', '&lt;', '&gt;', '&amp;'], [' ', '<', '>', '&'], $text);
    
    // Keep LaTeX formulas intact
    // Just clean up excessive whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}

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
    $string = strtolower($string);
    
    if (strlen($string) > 50) {
        $string = substr($string, 0, 50);
        $string = rtrim($string, '-');
    }
    
    return $string ?: 'de-thi-latex-' . date('Ymd');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['file']) || !isset($_POST['grade']) || !isset($_POST['subject_id'])) {
            throw new Exception("Thiếu thông tin đề thi");
        }

        $file = basename($_POST['file']);
        $grade = $_POST['grade'];
        $subjectId = (int)$_POST['subject_id'];
        
        $examFile = __DIR__ . "/exams/{$grade}/subject_{$subjectId}/" . $file;
        if (!file_exists($examFile)) {
            throw new Exception("Không tìm thấy đề thi");
        }

        $examData = json_decode(file_get_contents($examFile), true);
        if (!$examData || !isset($examData['questions'])) {
            throw new Exception("Không thể đọc dữ liệu đề thi");
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(13);
        
        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $titleStyle = ['name' => 'Times New Roman', 'size' => 16, 'bold' => true];
        $headingStyle = ['name' => 'Times New Roman', 'size' => 14, 'bold' => true];
        $normalStyle = ['name' => 'Times New Roman', 'size' => 13];
        $boldStyle = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];
        $noteStyle = ['name' => 'Times New Roman', 'size' => 11, 'italic' => true, 'color' => '0000FF'];
        
        $section->addText('TRƯỜNG THCS _______________', $normalStyle, ['alignment' => 'left']);
        $section->addText(strtoupper($examData['test_name']), $titleStyle, ['alignment' => 'center', 'spaceAfter' => 100]);
        $section->addText('Thời gian: ' . $examData['time_limit'] . ' phút', $normalStyle, ['alignment' => 'center', 'spaceAfter' => 200]);
        
        $section->addText('Họ và tên học sinh: .......................................', $normalStyle);
        $section->addText('Lớp: ............     Ngày thi: ' . date('d/m/Y'), $normalStyle, ['spaceAfter' => 100]);
        
        // Add important note about LaTeX formulas
        $section->addText('LƯU Ý: File này chứa công thức LaTeX (trong dấu $ hoặc $$).', $noteStyle, ['spaceAfter' => 0]);
        $section->addText('Để chuyển thành công thức toán học:', $noteStyle, ['spaceAfter' => 0]);
        $section->addText('1. Cài MathType cho Word (hoặc dùng Insert > Equation)', $noteStyle, ['spaceAfter' => 0]);
        $section->addText('2. Trong MathType: Chọn "Convert Equations" > "LaTeX and TeX"', $noteStyle, ['spaceAfter' => 0]);
        $section->addText('3. Tất cả công thức sẽ được convert tự động thành Equation.', $noteStyle, ['spaceAfter' => 200]);
        
        $section->addText('PHẦN I: CÂU HỎI', $headingStyle, ['spaceAfter' => 100]);
        
        foreach ($examData['questions'] as $idx => $question) {
            $questionNum = $idx + 1;
            
            // Keep LaTeX formulas intact
            $questionText = preserveLatex($question['question']);
            $section->addText("Câu {$questionNum}: {$questionText}", $boldStyle, ['spaceAfter' => 50]);
            
            if (isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $optIdx => $option) {
                    $optionLetter = chr(65 + $optIdx);
                    $optionText = preserveLatex($option);
                    $section->addText("     {$optionLetter}. {$optionText}", $normalStyle, ['spaceAfter' => 30]);
                }
            }
            
            $section->addText('', $normalStyle, ['spaceAfter' => 100]);
        }
        
        $section->addPageBreak();
        $section->addText('PHẦN II: ĐÁP ÁN', $headingStyle, ['spaceAfter' => 100]);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => 100 * 50,
            'unit' => 'pct'
        ]);
        
        $table->addRow(400);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $boldStyle, ['alignment' => 'center']);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $boldStyle, ['alignment' => 'center']);
        $table->addCell(5000, ['bgColor' => 'E0E0E0'])->addText('Ghi chú', $boldStyle, ['alignment' => 'center']);
        
        foreach ($examData['questions'] as $idx => $question) {
            $questionNum = $idx + 1;
            
            $correctAnswer = '';
            if (isset($question['correct'])) {
                // Handle array of correct answers (multiple choice with multiple correct answers)
                if (is_array($question['correct'])) {
                    $answers = array_map(function($ans) {
                        if (is_numeric($ans)) {
                            return chr(65 + (int)$ans);
                        }
                        return strtoupper($ans);
                    }, $question['correct']);
                    $correctAnswer = implode(', ', $answers);
                } elseif (is_numeric($question['correct'])) {
                    $correctAnswer = chr(65 + (int)$question['correct']);
                } else {
                    $correctAnswer = strtoupper((string)$question['correct']);
                }
            }
            
            $level = $question['level'] ?? 'NB';
            
            $table->addRow();
            $table->addCell(1500)->addText($questionNum, $normalStyle, ['alignment' => 'center']);
            $table->addCell(1500)->addText($correctAnswer, $boldStyle, ['alignment' => 'center']);
            $table->addCell(1500)->addText($level, $normalStyle, ['alignment' => 'center']);
            $table->addCell(5000)->addText('', $normalStyle);
        }
        
        $section->addText('', $normalStyle, ['spaceAfter' => 200]);
        $section->addText('Tổng số câu: ' . $examData['total_questions'], $normalStyle);
        $section->addText('Tổng điểm: ' . $examData['total_points'], $normalStyle);
        $section->addText('Điểm mỗi câu: ' . $examData['points_per_question'], $normalStyle);

        $outputFilename = create_slug($examData['test_name']) . '_latex_' . date('Ymd') . '.docx';
        $tempPath = sys_get_temp_dir() . '/' . uniqid('cvd_exam_latex_', true) . '.docx';

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);
        
        if (!file_exists($tempPath)) {
            throw new Exception("Không thể tạo file Word");
        }
        
        $fileSize = filesize($tempPath);
        if ($fileSize === 0 || $fileSize === false) {
            @unlink($tempPath);
            throw new Exception("File Word rỗng");
        }

        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', 'Off');
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $outputFilename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        
        $handle = fopen($tempPath, 'rb');
        if ($handle === false) {
            @unlink($tempPath);
            throw new Exception("Không thể đọc file Word");
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        
        fclose($handle);
        @unlink($tempPath);
        exit;

    } catch (Exception $e) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        error_log("Export Word LaTeX Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Return user-friendly HTML error page
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lỗi Export</title>';
        echo '<style>body{font-family:Arial,sans-serif;padding:40px;background:#f5f5f5;}';
        echo '.error-box{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:600px;margin:0 auto;}';
        echo 'h2{color:#dc3545;}pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto;}</style>';
        echo '</head><body><div class="error-box">';
        echo '<h2>❌ Lỗi khi xuất file Word</h2>';
        echo '<p><strong>Chi tiết lỗi:</strong></p>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<p><strong>Các bước khắc phục:</strong></p>';
        echo '<ol>';
        echo '<li>Kiểm tra file đề thi có tồn tại không</li>';
        echo '<li>Đảm bảo đã cài đặt thư viện PhpWord (composer require phpoffice/phpword)</li>';
        echo '<li>Kiểm tra quyền ghi file trong thư mục temp</li>';
        echo '<li>Liên hệ admin nếu vấn đề vẫn tiếp diễn</li>';
        echo '</ol>';
        echo '<p><a href="my_exams.php" style="display:inline-block;margin-top:20px;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;">← Quay lại danh sách đề thi</a></p>';
        echo '</div></body></html>';
        exit;
    }
}
?>
