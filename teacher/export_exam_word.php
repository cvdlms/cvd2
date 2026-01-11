<?php
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/premium_helper.php';

// Check Premium status
$username = $_SESSION['username'];
if (!isPremiumUser($username)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho tài khoản Premium']);
    exit;
}

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['file']) || !isset($_POST['grade']) || !isset($_POST['subject_id'])) {
            throw new Exception("Thiếu thông tin đề thi");
        }

        $file = basename($_POST['file']);
        $grade = $_POST['grade'];
        $subjectId = (int)$_POST['subject_id'];
        
        // Load exam data
        $examFile = __DIR__ . "/exams/{$grade}/subject_{$subjectId}/" . $file;
        if (!file_exists($examFile)) {
            throw new Exception("Không tìm thấy đề thi");
        }

        $examData = json_decode(file_get_contents($examFile), true);
        if (!$examData) {
            throw new Exception("Không thể đọc dữ liệu đề thi");
        }

        // Create new Word document
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(13);
        
        // Add section
        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        // Title style
        $titleStyle = ['name' => 'Times New Roman', 'size' => 16, 'bold' => true];
        $headingStyle = ['name' => 'Times New Roman', 'size' => 14, 'bold' => true];
        $normalStyle = ['name' => 'Times New Roman', 'size' => 13];
        $boldStyle = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];
        
        // Header
        $section->addText('TRƯỜNG THCS _______________', $normalStyle, ['alignment' => 'left']);
        $section->addText(strtoupper($examData['test_name']), $titleStyle, ['alignment' => 'center', 'spaceAfter' => 100]);
        $section->addText('Thời gian: ' . $examData['time_limit'] . ' phút', $normalStyle, ['alignment' => 'center', 'spaceAfter' => 200]);
        
        // Exam info
        $section->addText('Họ và tên học sinh: .......................................', $normalStyle);
        $section->addText('Lớp: ............     Ngày thi: ' . date('d/m/Y'), $normalStyle, ['spaceAfter' => 200]);
        
        // Questions section
        $section->addText('PHẦN I: CÂU HỎI', $headingStyle, ['spaceAfter' => 100]);
        
        foreach ($examData['questions'] as $idx => $question) {
            $questionNum = $idx + 1;
            
            // Question text
            $questionText = strip_tags($question['question']);
            $section->addText(
                "Câu {$questionNum}: {$questionText}",
                $boldStyle,
                ['spaceAfter' => 50]
            );
            
            // Options
            if (isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $optIdx => $option) {
                    $optionLetter = chr(65 + $optIdx);
                    $section->addText(
                        "     {$optionLetter}. " . strip_tags($option),
                        $normalStyle,
                        ['spaceAfter' => 30]
                    );
                }
            }
            
            $section->addText('', $normalStyle, ['spaceAfter' => 100]);
        }
        
        // Page break for answer key
        $section->addPageBreak();
        
        // Answer key section
        $section->addText('PHẦN II: ĐÁP ÁN', $headingStyle, ['spaceAfter' => 100]);
        
        // Create answer table
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => 100 * 50,
            'unit' => 'pct'
        ]);
        
        // Table header
        $table->addRow(400);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $boldStyle, ['alignment' => 'center']);
        $table->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $boldStyle, ['alignment' => 'center']);
        $table->addCell(5000, ['bgColor' => 'E0E0E0'])->addText('Ghi chú', $boldStyle, ['alignment' => 'center']);
        
        // Answer rows
        foreach ($examData['questions'] as $idx => $question) {
            $questionNum = $idx + 1;
            
            // Get correct answer
            $correctAnswer = '';
            if (isset($question['correct'])) {
                if (is_numeric($question['correct'])) {
                    $correctAnswer = chr(65 + (int)$question['correct']);
                } else {
                    $correctAnswer = strtoupper($question['correct']);
                }
            }
            
            $level = $question['level'] ?? 'NB';
            
            $table->addRow();
            $table->addCell(1500)->addText($questionNum, $normalStyle, ['alignment' => 'center']);
            $table->addCell(1500)->addText($correctAnswer, $boldStyle, ['alignment' => 'center']);
            $table->addCell(1500)->addText($level, $normalStyle, ['alignment' => 'center']);
            $table->addCell(5000)->addText('', $normalStyle);
        }
        
        // Summary at the end
        $section->addText('', $normalStyle, ['spaceAfter' => 200]);
        $section->addText('Tổng số câu: ' . $examData['total_questions'], $normalStyle);
        $section->addText('Tổng điểm: ' . $examData['total_points'], $normalStyle);
        $section->addText('Điểm mỗi câu: ' . $examData['points_per_question'], $normalStyle);

        // Generate filename
        $outputFilename = create_slug($examData['test_name']) . '_' . date('Ymd') . '.docx';
        $outputPath = sys_get_temp_dir() . '/' . $outputFilename;

        // Save document
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        // Send file to browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $outputFilename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($outputPath));
        
        ob_clean();
        flush();
        readfile($outputPath);
        
        // Clean up temp file
        unlink($outputPath);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// Function to create URL-friendly slug
function create_slug($string) {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    $string = strtolower($string);
    return $string;
}
