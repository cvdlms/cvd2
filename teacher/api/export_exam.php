<?php
/**
 * API XUẤT ĐỀ THI RA WORD/PDF
 * 
 * Endpoints:
 *   GET ?action=export_word&exam_id=xxx&variant=A
 */

// Suppress all output except JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_name('CVD_TEACHER_SESSION');
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    die('Unauthorized. Teacher login required.');
}

require_once __DIR__ . '/../../vendor/autoload.php'; // For PHPWord if available

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;

class ExamExporter {
    private $examsDir;
    
    public function __construct() {
        $this->examsDir = __DIR__ . '/../exams/generated';
    }
    
    /**
     * Export đề thi ra Word
     */
    public function exportToWord($examId, $variantLabel = 'A', $includeAnswers = true) {
        // Load exam
        $exam = $this->loadExam($examId);
        if (!$exam) {
            throw new Exception("Exam not found: $examId");
        }
        
        // Find variant
        $variant = null;
        foreach ($exam['variants'] as $v) {
            if ($v['variant'] === $variantLabel) {
                $variant = $v;
                break;
            }
        }
        
        if (!$variant) {
            throw new Exception("Variant not found: $variantLabel");
        }
        
        // Create PHPWord document
        $phpWord = new PhpWord();
        
        // Set default font
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(13);
        
        // Add section
        $section = $phpWord->addSection([
            'marginLeft' => 1134,
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);
        
        // HEADER
        $this->addHeader($section, $exam);
        
        // EXAM TITLE
        $this->addExamTitle($section, $exam, $variantLabel);
        
        // QUESTIONS
        $this->addQuestions($section, $variant);
        
        // FOOTER
        $this->addFooter($section);
        
        // CREATE ANSWER KEY (separate page)
        if ($includeAnswers) {
            $section->addPageBreak();
            $this->addAnswerKey($section, $exam, $variant, $variantLabel);
        }
        
        // Save to temp file
        $tempFile = sys_get_temp_dir() . '/exam_' . $examId . '_' . $variantLabel . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return $tempFile;
    }
    
    /**
     * Thêm header (tên trường, tổ...)
     */
    private function addHeader($section, $exam) {
        $table = $section->addTable([
            'borderSize' => 0,
            'width' => 100 * 50,
            'unit' => 'pct'
        ]);
        
        $table->addRow();
        
        // Left cell
        $cell1 = $table->addCell(4500);
        $cell1->addText('TRƯỜNG THCS ___________', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $cell1->addText('TỔ: TIN HỌC', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        
        // Right cell
        $cell2 = $table->addCell(4500);
        $cell2->addText('ĐỀ CHÍNH THỨC', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $cell2->addText('(Đề gồm 2 trang)', ['italic' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        
        $section->addText('', [], ['spaceAfter' => 100]);
    }
    
    /**
     * Thêm tiêu đề đề thi
     */
    private function addExamTitle($section, $exam, $variantLabel) {
        $section->addText(
            mb_strtoupper($exam['title']),
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        
        $gradeText = str_replace('khoi', 'LỚP ', $exam['grade']);
        $section->addText(
            "MÔN: TIN HỌC - $gradeText",
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        
        $section->addText(
            'Thời gian: 45 phút (không kể thời gian giao đề)',
            ['italic' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
        
        $section->addText(
            "ĐỀ $variantLabel",
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
        );
        
        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0]);
    }
    
    /**
     * Thêm danh sách câu hỏi
     */
    private function addQuestions($section, $variant) {
        $questions = $variant['questions'];
        
        // Group by question_type
        $tnkqQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'TNKQ');
        $dsQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'DS');
        $tlQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'TL');
        
        // A. TRẮC NGHIỆM
        if (!empty($tnkqQuestions)) {
            $this->addQuestionSection($section, 'A. PHẦN TRẮC NGHIỆM KHÁCH QUAN', $tnkqQuestions, 'tnkq');
        }
        
        // B. ĐÚNG/SAI
        if (!empty($dsQuestions)) {
            $this->addQuestionSection($section, 'B. PHẦN ĐÚNG/SAI', $dsQuestions, 'ds');
        }
        
        // C. TỰ LUẬN
        if (!empty($tlQuestions)) {
            $this->addQuestionSection($section, 'C. PHẦN TỰ LUẬN', $tlQuestions, 'tl');
        }
    }
    
    /**
     * Thêm 1 section câu hỏi
     */
    private function addQuestionSection($section, $title, $questions, $type) {
        // Section title
        $section->addText(
            $title,
            ['bold' => true, 'size' => 13],
            ['spaceAfter' => 100]
        );
        
        if ($type === 'tnkq') {
            $section->addText(
                'Khoanh tròn chữ cái trước câu trả lời đúng:',
                ['italic' => true, 'size' => 12],
                ['spaceAfter' => 100]
            );
        } elseif ($type === 'ds') {
            $section->addText(
                'Chọn Đ (đúng) hoặc S (sai) cho mỗi ý:',
                ['italic' => true, 'size' => 12],
                ['spaceAfter' => 100]
            );
        } else {
            $section->addText(
                'Trả lời ngắn gọn các câu hỏi sau:',
                ['italic' => true, 'size' => 12],
                ['spaceAfter' => 100]
            );
        }
        
        foreach ($questions as $q) {
            // Question number
            $questionText = "Câu {$q['number']}. ";
            if (isset($q['required_points'])) {
                $questionText .= "(" . number_format($q['required_points'], 2) . " điểm) ";
            }
            $questionText .= $q['question'];
            
            $section->addText($questionText, ['bold' => true, 'size' => 13]);
            
            // Options
            if ($type === 'tnkq' && isset($q['options'])) {
                foreach ($q['options'] as $idx => $option) {
                    $label = chr(65 + $idx); // A, B, C, D
                    $section->addText("    $label. $option", ['size' => 13]);
                }
                $section->addText('', [], ['spaceAfter' => 100]);
            } elseif ($type === 'ds' && isset($q['options'])) {
                foreach ($q['options'] as $idx => $option) {
                    $label = chr(97 + $idx); // a, b, c, d
                    $section->addText("    $label) [ ] $option", ['size' => 13]);
                }
                $section->addText('', [], ['spaceAfter' => 100]);
            } else {
                // Tự luận - không có options
                $section->addText('', [], ['spaceAfter' => 300]);
            }
        }
    }
    
    /**
     * Thêm footer
     */
    private function addFooter($section) {
        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0]);
        $section->addText(
            '--- HẾT ---',
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
        $section->addText(
            'Học sinh không được sử dụng tài liệu. Giám thị coi thi không giải thích gì thêm.',
            ['italic' => true, 'size' => 11],
            ['alignment' => Jc::CENTER]
        );
    }
    
    /**
     * Thêm đáp án + hướng dẫn chấm
     */
    private function addAnswerKey($section, $exam, $variant, $variantLabel) {
        $section->addText(
            "ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM - ĐỀ $variantLabel",
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
        );
        
        $questions = $variant['questions'];
        
        // Group by type
        $tnkqQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'TNKQ');
        $dsQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'DS');
        $tlQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'TL');
        
        // A. TRẮC NGHIỆM
        if (!empty($tnkqQuestions)) {
            $section->addText('A. TRẮC NGHIỆM:', ['bold' => true, 'size' => 13]);
            
            $answerLine = '';
            foreach ($tnkqQuestions as $q) {
                $correctLetter = isset($q['correct_text']) ? $q['correct_text'] : chr(65 + $q['correct']);
                $answerLine .= "{$q['number']}-{$correctLetter}  ";
            }
            $section->addText($answerLine, ['size' => 12], ['spaceAfter' => 200]);
        }
        
        // B. ĐÚNG/SAI
        if (!empty($dsQuestions)) {
            $section->addText('B. ĐÚNG/SAI:', ['bold' => true, 'size' => 13]);
            
            foreach ($dsQuestions as $q) {
                $section->addText("Câu {$q['number']}:", ['bold' => true, 'size' => 12]);
                
                if (isset($q['options']) && isset($q['correct'])) {
                    // Parse correct answer (might be array for DS)
                    $correctAnswers = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
                    
                    foreach ($q['options'] as $idx => $option) {
                        $label = chr(97 + $idx);
                        $isCorrect = in_array($idx, $correctAnswers);
                        $symbol = $isCorrect ? 'Đ' : 'S';
                        $section->addText("  $label) $symbol", ['size' => 12]);
                    }
                }
                $section->addText('', [], ['spaceAfter' => 100]);
            }
        }
        
        // C. TỰ LUẬN - HƯỚNG DẪN CHẤM
        if (!empty($tlQuestions)) {
            $section->addText('C. TỰ LUẬN - HƯỚNG DẪN CHẤM:', ['bold' => true, 'size' => 13]);
            
            foreach ($tlQuestions as $q) {
                $points = number_format($q['required_points'], 2);
                $section->addText("Câu {$q['number']}: ($points điểm)", ['bold' => true, 'size' => 12]);
                
                if (!empty($q['explanation'])) {
                    $section->addText("  " . $q['explanation'], ['size' => 12]);
                } else {
                    $section->addText("  (Giáo viên chấm dựa trên nội dung chính xác và mạch lạc)", ['italic' => true, 'size' => 11]);
                }
                $section->addText('', [], ['spaceAfter' => 100]);
            }
        }
    }
    
    /**
     * Load exam từ file
     */
    private function loadExam($examId) {
        $filePath = "{$this->examsDir}/{$examId}.json";
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }
}

// ===== MAIN HANDLER =====

$action = $_GET['action'] ?? null;

if (!$action) {
    die('Missing action parameter');
}

try {
    $exporter = new ExamExporter();
    
    switch ($action) {
        case 'export_word':
            $examId = $_GET['exam_id'] ?? null;
            $variant = $_GET['variant'] ?? 'A';
            $includeAnswers = isset($_GET['include_answers']) ? (bool)$_GET['include_answers'] : true;
            
            if (!$examId) {
                die('Missing exam_id parameter');
            }
            
            $tempFile = $exporter->exportToWord($examId, $variant, $includeAnswers);
            
            // Download file
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="De_Thi_' . $variant . '.docx"');
            header('Content-Length: ' . filesize($tempFile));
            readfile($tempFile);
            unlink($tempFile);
            break;
            
        default:
            die('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
