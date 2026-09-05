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
        
        // Phân loại câu hỏi theo các phần
        $part1 = []; // Trắc nghiệm nhiều lựa chọn
        $part2 = []; // Đúng sai
        $part3 = []; // Tự luận
        $part4 = []; // Thực hành

        foreach ($examData['questions'] as $q) {
            $t = strtolower($q['type'] ?? '');
            if ($t === 'true_false_multiple' || $t === 'true_false' || $t === 'dungsai') {
                $part2[] = $q;
            } elseif ($t === 'essay' || $t === 'tuluan' || $t === 'short_answer') {
                $part3[] = $q;
            } elseif ($t === 'practice' || $t === 'thuchanh') {
                $part4[] = $q;
            } else {
                $part1[] = $q;
            }
        }

        $calcPts = function(array $list) {
            $pts = 0;
            $hasPts = false;
            foreach ($list as $q) {
                if (isset($q['points']) && is_numeric($q['points'])) {
                    $pts += (float)$q['points'];
                    $hasPts = true;
                }
            }
            return $hasPts ? round($pts, 2) : null;
        };

        $pts1 = $calcPts($part1);
        $pts2 = $calcPts($part2);
        $pts3 = $calcPts($part3);
        $pts4 = $calcPts($part4);

        $italicStyle = ['name' => 'Times New Roman', 'size' => 12, 'italic' => true];
        $partHeadingStyle = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];

        $romanNumerals = ['I', 'II', 'III', 'IV', 'V'];
        $partIdx = 0;

        // --- PHẦN I: TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN ---
        if (!empty($part1)) {
            $roman = $romanNumerals[$partIdx++];
            $ptsLabel = $pts1 !== null ? " ({$pts1} điểm)" : "";
            $section->addText("PHẦN {$roman}. TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN{$ptsLabel}", $headingStyle, ['spaceBefore' => 150, 'spaceAfter' => 50]);
            $section->addText("Học sinh trả lời từ câu 1 đến câu " . count($part1) . ", mỗi câu hỏi học sinh chỉ chọn một phương án.", $italicStyle, ['spaceAfter' => 100]);

            foreach ($part1 as $idx => $question) {
                $questionNum = $idx + 1;
                $questionText = preserveLatex($question['question']);
                $ptsSuffix = !empty($question['points']) ? " (" . $question['points'] . "đ)" : "";
                $section->addText("Câu {$questionNum}{$ptsSuffix}: {$questionText}", $boldStyle, ['spaceAfter' => 40]);

                if (isset($question['options']) && is_array($question['options'])) {
                    foreach ($question['options'] as $optIdx => $option) {
                        $optionLetter = chr(65 + $optIdx);
                        $optionText = preserveLatex($option);
                        $section->addText("     {$optionLetter}. {$optionText}", $normalStyle, ['spaceAfter' => 25]);
                    }
                }
                $section->addText('', $normalStyle, ['spaceAfter' => 60]);
            }
        }

        // --- PHẦN II: TRẮC NGHIỆM ĐÚNG SAI ---
        if (!empty($part2)) {
            $roman = $romanNumerals[$partIdx++];
            $ptsLabel = $pts2 !== null ? " ({$pts2} điểm)" : "";
            $section->addText("PHẦN {$roman}. TRẮC NGHIỆM ĐÚNG SAI{$ptsLabel}", $headingStyle, ['spaceBefore' => 150, 'spaceAfter' => 50]);
            $section->addText("Học sinh trả lời từ câu 1 đến câu " . count($part2) . ". Trong mỗi ý a), b), c), d) ở mỗi câu, học sinh ghi đúng hoặc sai.", $italicStyle, ['spaceAfter' => 100]);

            foreach ($part2 as $idx => $question) {
                $questionNum = $idx + 1;
                $questionText = preserveLatex($question['question']);
                $ptsSuffix = !empty($question['points']) ? " (" . $question['points'] . "đ)" : "";
                $section->addText("Câu {$questionNum}{$ptsSuffix}: {$questionText}", $boldStyle, ['spaceAfter' => 40]);

                if (!empty($question['items']) && is_array($question['items'])) {
                    foreach ($question['items'] as $itemIdx => $item) {
                        $lbl = $item['label'] ?? chr(97 + $itemIdx);
                        $stmt = preserveLatex($item['statement'] ?? $item['text'] ?? '');
                        $section->addText("     {$lbl}) {$stmt}", $normalStyle, ['spaceAfter' => 25]);
                    }
                }
                $section->addText('', $normalStyle, ['spaceAfter' => 60]);
            }
        }

        // --- PHẦN III: TỰ LUẬN ---
        if (!empty($part3)) {
            $roman = $romanNumerals[$partIdx++];
            $ptsLabel = $pts3 !== null ? " ({$pts3} điểm)" : "";
            $section->addText("PHẦN {$roman}. TỰ LUẬN{$ptsLabel}", $headingStyle, ['spaceBefore' => 150, 'spaceAfter' => 100]);

            foreach ($part3 as $idx => $question) {
                $questionNum = $idx + 1;
                $questionText = preserveLatex($question['question']);
                $ptsSuffix = !empty($question['points']) ? " (" . $question['points'] . " điểm)" : "";
                $section->addText("Câu {$questionNum}{$ptsSuffix}: {$questionText}", $boldStyle, ['spaceAfter' => 60]);
                $section->addText('', $normalStyle, ['spaceAfter' => 80]);
            }
        }

        // --- PHẦN IV: THỰC HÀNH (nếu có) ---
        if (!empty($part4)) {
            $roman = $romanNumerals[$partIdx++];
            $ptsLabel = $pts4 !== null ? " ({$pts4} điểm)" : "";
            $section->addText("PHẦN {$roman}. THỰC HÀNH{$ptsLabel}", $headingStyle, ['spaceBefore' => 150, 'spaceAfter' => 100]);

            foreach ($part4 as $idx => $question) {
                $questionNum = $idx + 1;
                $questionText = preserveLatex($question['question']);
                $ptsSuffix = !empty($question['points']) ? " (" . $question['points'] . " điểm)" : "";
                $section->addText("Câu {$questionNum}{$ptsSuffix}: {$questionText}", $boldStyle, ['spaceAfter' => 60]);
                $section->addText('', $normalStyle, ['spaceAfter' => 80]);
            }
        }

        // --- TRANG ĐÁP ÁN & HƯỚNG DẪN CHẤM ---
        $section->addPageBreak();
        $section->addText('ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM', $titleStyle, ['alignment' => 'center', 'spaceAfter' => 150]);

        if (!empty($part1)) {
            $section->addText('I. ĐÁP ÁN PHẦN TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN', $headingStyle, ['spaceBefore' => 100, 'spaceAfter' => 50]);
            $table1 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct'
            ]);
            $table1->addRow(350);
            $table1->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
            $table1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $boldStyle, ['alignment' => 'center']);
            $table1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $boldStyle, ['alignment' => 'center']);
            $table1->addCell(4500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $boldStyle, ['alignment' => 'center']);

            foreach ($part1 as $idx => $question) {
                $qNum = $idx + 1;
                $ans = '';
                if (isset($question['correct'])) {
                    if (is_numeric($question['correct'])) {
                        $ans = chr(65 + (int)$question['correct']);
                    } elseif (is_array($question['correct'])) {
                        $ans = implode(', ', array_map(fn($c) => is_numeric($c) ? chr(65 + (int)$c) : $c, $question['correct']));
                    } else {
                        $ans = strtoupper((string)$question['correct']);
                    }
                }
                $table1->addRow();
                $table1->addCell(1500)->addText($qNum, $normalStyle, ['alignment' => 'center']);
                $table1->addCell(2000)->addText($ans, $boldStyle, ['alignment' => 'center']);
                $table1->addCell(2000)->addText($question['level'] ?? 'NB', $normalStyle, ['alignment' => 'center']);
                $table1->addCell(4500)->addText($question['points'] ?? '', $normalStyle, ['alignment' => 'center']);
            }
            $section->addText('', $normalStyle, ['spaceAfter' => 100]);
        }

        if (!empty($part2)) {
            $section->addText('II. ĐÁP ÁN PHẦN TRẮC NGHIỆM ĐÚNG SAI', $headingStyle, ['spaceBefore' => 100, 'spaceAfter' => 50]);
            $table2 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'width' => 100 * 50,
                'unit' => 'pct'
            ]);
            $table2->addRow(350);
            $table2->addCell(1200, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
            $table2->addCell(1800, ['bgColor' => 'E0E0E0'])->addText('Lệnh hỏi', $boldStyle, ['alignment' => 'center']);
            $table2->addCell(2500, ['bgColor' => 'E0E0E0'])->addText('Đáp án (Đ/S)', $boldStyle, ['alignment' => 'center']);
            $table2->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $boldStyle, ['alignment' => 'center']);
            $table2->addCell(2500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $boldStyle, ['alignment' => 'center']);

            foreach ($part2 as $idx => $question) {
                $qNum = $idx + 1;
                $items = $question['items'] ?? [];
                foreach ($items as $itemIdx => $item) {
                    $lbl = $item['label'] ?? chr(97 + $itemIdx);
                    $isT = ($item['correct'] === true || $item['correct'] === 1 || $item['correct'] === 'true' || $item['correct'] === 'dung' || $item['correct'] === 'Đúng');
                    $table2->addRow();
                    if ($itemIdx === 0) {
                        $table2->addCell(1200)->addText($qNum, $boldStyle, ['alignment' => 'center']);
                    } else {
                        $table2->addCell(1200)->addText('', $normalStyle, ['alignment' => 'center']);
                    }
                    $table2->addCell(1800)->addText("Ý {$lbl}", $normalStyle, ['alignment' => 'center']);
                    $table2->addCell(2500)->addText($isT ? 'Đúng' : 'Sai', $boldStyle, ['alignment' => 'center']);
                    $table2->addCell(2000)->addText($question['level'] ?? 'TH', $normalStyle, ['alignment' => 'center']);
                    $table2->addCell(2500)->addText($itemIdx === 0 ? ($question['points'] ?? '') : '', $normalStyle, ['alignment' => 'center']);
                }
            }
            $section->addText('', $normalStyle, ['spaceAfter' => 100]);
        }

        if (!empty($part3)) {
            $section->addText('III. HƯỚNG DẪN CHẤM PHẦN TỰ LUẬN', $headingStyle, ['spaceBefore' => 100, 'spaceAfter' => 50]);
            foreach ($part3 as $idx => $question) {
                $qNum = $idx + 1;
                $ptsSuffix = !empty($question['points']) ? " ({$question['points']} điểm)" : "";
                $section->addText("Câu {$qNum}{$ptsSuffix}:", $boldStyle, ['spaceAfter' => 30]);
                $guide = preserveLatex($question['suggested_answer'] ?? $question['answer'] ?? 'Theo biểu điểm và đáp án chi tiết của tổ chuyên môn.');
                $section->addText("   {$guide}", $normalStyle, ['spaceAfter' => 60]);
            }
        }

        $section->addText('', $normalStyle, ['spaceAfter' => 150]);
        $section->addText('Tổng số câu: ' . $examData['total_questions'], $normalStyle);
        $section->addText('Tổng điểm: ' . $examData['total_points'], $normalStyle);

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
