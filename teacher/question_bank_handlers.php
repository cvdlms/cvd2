<?php
// question_bank_handlers.php - POST and GET request handlers for question_bank.php

if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="questions_' . $selectedGrade . '_' . $selectedSemester . '_subject_' . $selectedSubjectId . '.json"');
    echo json_encode($questionsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle download Excel template
if (isset($_GET['action']) && $_GET['action'] === 'download_excel_template') {
    require_once '../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();

    // Set headers
    $worksheet->setCellValue('A1', 'Chủ đề');
    $worksheet->setCellValue('B1', 'Bài học');
    $worksheet->setCellValue('C1', 'Câu hỏi');
    $worksheet->setCellValue('D1', 'Đáp án A');
    $worksheet->setCellValue('E1', 'Đáp án B');
    $worksheet->setCellValue('F1', 'Đáp án C');
    $worksheet->setCellValue('G1', 'Đáp án D');
    $worksheet->setCellValue('H1', 'Đáp án đúng (1=A, 2=B, 3=C, 4=D hoặc 1,3 cho nhiều đáp án)');
    $worksheet->setCellValue('I1', 'Loại câu hỏi (single/multiple)');
    $worksheet->setCellValue('J1', 'Mức độ (NB/TH/VD/VDC)');

    // Sample data
    $worksheet->setCellValue('A2', 'Chủ đề 1: Máy tính và cộng đồng');
    $worksheet->setCellValue('B2', 'Bài 1: Thiết bị vào và thiết bị ra');
    $worksheet->setCellValue('C2', 'Thiết bị nào sau đây là thiết bị vào?');
    $worksheet->setCellValue('D2', 'Bàn phím');
    $worksheet->setCellValue('E2', 'Màn hình');
    $worksheet->setCellValue('F2', 'Loa');
    $worksheet->setCellValue('G2', 'Máy in');
    $worksheet->setCellValue('H2', '1');
    $worksheet->setCellValue('I2', 'single');
    $worksheet->setCellValue('J2', 'NB');

    $worksheet->setCellValue('A3', 'Chủ đề 1: Máy tính và cộng đồng');
    $worksheet->setCellValue('B3', 'Bài 1: Thiết bị vào và thiết bị ra');
    $worksheet->setCellValue('C3', 'Thiết bị nào sau đây là thiết bị ra?');
    $worksheet->setCellValue('D3', 'Bàn phím');
    $worksheet->setCellValue('E3', 'Màn hình');
    $worksheet->setCellValue('F3', 'Loa');
    $worksheet->setCellValue('G3', 'Máy in');
    $worksheet->setCellValue('H3', '2,3,4');
    $worksheet->setCellValue('I3', 'multiple');
    $worksheet->setCellValue('J3', 'TH');

    // Set column widths
    $worksheet->getColumnDimension('A')->setWidth(30);
    $worksheet->getColumnDimension('B')->setWidth(30);
    $worksheet->getColumnDimension('C')->setWidth(50);
    $worksheet->getColumnDimension('D')->setWidth(20);
    $worksheet->getColumnDimension('E')->setWidth(20);
    $worksheet->getColumnDimension('F')->setWidth(20);
    $worksheet->getColumnDimension('G')->setWidth(20);
    $worksheet->getColumnDimension('H')->setWidth(60);
    $worksheet->getColumnDimension('I')->setWidth(25);
    $worksheet->getColumnDimension('J')->setWidth(25);

    // Output Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="mau_excel_cau_hoi.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

// Handle download Word template
// NOTE: This handler has been moved to the top of question_bank.php 
// to avoid output buffer issues. Keeping this comment for reference.

// Handle import from Word
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_from_word') {
    require_once '../vendor/autoload.php';

    $grade = $_POST['grade'] ?? '';
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $semester = $_POST['semester'] ?? '';
    $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] == '1';

    // Validate inputs
    if (!in_array($grade, $availableGrades)) {
        $_SESSION['import_error'] = 'Khối không hợp lệ.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
        exit;
    }

    if (!in_array($subjectId, $assignedSubjectIds)) {
        $_SESSION['import_error'] = 'Môn học không hợp lệ hoặc không được phép.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
        exit;
    }

    if (!isset($_FILES['word_file']) || $_FILES['word_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['import_error'] = 'Vui lòng chọn file Word (.docx) hợp lệ.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
        exit;
    }

    try {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($_FILES['word_file']['tmp_name']);
        
        // Extract all text from Word document
        $fullText = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $fullText .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $fullText .= $childElement->getText() . "\n";
                        }
                    }
                }
            }
        }

        // Parse questions from text
        $parsedQuestions = parseQuestionsFromText($fullText);

        if (empty($parsedQuestions)) {
            $_SESSION['import_error'] = 'Không tìm thấy câu hỏi nào trong file. Vui lòng kiểm tra lại format.';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
            exit;
        }

        // Load existing questions
        $questionsFile = __DIR__ . "/questions/{$grade}/{$semester}/subject_{$subjectId}.json";
        $questionsDir = dirname($questionsFile);
        if (!is_dir($questionsDir)) {
            mkdir($questionsDir, 0755, true);
        }

        $existingData = [];
        if (file_exists($questionsFile)) {
            $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        }

        // Merge or append questions
        if ($overwrite) {
            // Overwrite mode: replace existing topics/lessons
            $existingData = $parsedQuestions;
        } else {
            // Append mode: add to existing data
            foreach ($parsedQuestions as $newTopic) {
                $found = false;
                foreach ($existingData as &$existingTopic) {
                    if ($existingTopic['topic'] === $newTopic['topic'] && 
                        $existingTopic['lesson'] === $newTopic['lesson']) {
                        // Merge questions
                        $existingTopic['questions'] = array_merge(
                            $existingTopic['questions'], 
                            $newTopic['questions']
                        );
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $existingData[] = $newTopic;
                }
            }
        }

        // Save to file
        file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $totalImported = 0;
        foreach ($parsedQuestions as $topic) {
            $totalImported += count($topic['questions']);
        }

        $_SESSION['import_message'] = "Đã import thành công {$totalImported} câu hỏi từ file Word!";
        header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
        exit;

    } catch (Exception $e) {
        $_SESSION['import_error'] = 'Lỗi khi đọc file Word: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?grade=' . $grade . '&subject_id=' . $subjectId . '&semester=' . $semester);
        exit;
    }
}

/**
 * Parse questions from Word text content
 * CHỈ HỖ TRỢ TRẮC NGHIỆM (TNKQ):
 * - single: Một đáp án đúng
 * - multiple: Nhiều đáp án đúng
 */
function parseQuestionsFromText($text) {
    $lines = explode("\n", $text);
    $questions = [];
    $currentTopic = '';
    $currentLesson = '';
    $currentOptions = [];
    $currentCorrectAnswers = [];
    $currentLevel = 'NB';
    $currentType = 'single';
    $questionText = '';
    $inQuestion = false;

    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and separators
        if (empty($line) || $line === '---' || strpos($line, '===') === 0) {
            // If we have a complete question, save it
            if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
                $questions[] = [
                    'topic' => $currentTopic,
                    'lesson' => $currentLesson,
                    'question' => [
                        'question' => trim($questionText),
                        'options' => $currentOptions,
                        'correct_answers' => $currentCorrectAnswers,
                        'level' => $currentLevel,
                        'type' => $currentType
                    ]
                ];
            }
            
            // Reset for next question
            $questionText = '';
            $currentOptions = [];
            $currentCorrectAnswers = [];
            $currentLevel = 'NB';
            $currentType = 'single';
            $inQuestion = false;
            continue;
        }

        // Parse metadata
        if (preg_match('/^Chủ đề:\s*(.+)$/ui', $line, $matches)) {
            $currentTopic = trim($matches[1]);
            continue;
        }
        
        if (preg_match('/^Bài học:\s*(.+)$/ui', $line, $matches)) {
            $currentLesson = trim($matches[1]);
            continue;
        }

        // Parse question start - flexible format: "Câu 1:" or "Câu 1." or "Câu 1 "
        if (preg_match('/^Câu\s+(\d+)[\s.:]*(.*)$/ui', $line, $matches)) {
            // Save previous question if exists
            if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
                $questions[] = [
                    'topic' => $currentTopic,
                    'lesson' => $currentLesson,
                    'question' => [
                        'question' => trim($questionText),
                        'options' => $currentOptions,
                        'correct_answers' => $currentCorrectAnswers,
                        'level' => $currentLevel,
                        'type' => $currentType
                    ]
                ];
            }

            // Reset for new question
            $questionText = '';
            $currentOptions = [];
            $currentCorrectAnswers = [];
            $currentLevel = 'NB';
            $currentType = 'single';
            $inQuestion = true;

            // Parse metadata from question line
            $questionNumber = $matches[1];
            $metaLine = $matches[2];
            
            // Extract level - accept both "Mức độ" and "Muc do"
            if (preg_match('/\[(Mức độ|Muc do|Mức\s*độ|Muc\s*do)[:\s]*(NB|TH|VD|VDC)\]/ui', $metaLine, $levelMatch)) {
                $currentLevel = strtoupper($levelMatch[2]);
                $metaLine = preg_replace('/\[(Mức độ|Muc do|Mức\s*độ|Muc\s*do)[:\s]*(NB|TH|VD|VDC)\]\s*/ui', '', $metaLine);
            }
            
            // Extract type - CHỈ CHẤP NHẬN single hoặc multiple
            if (preg_match('/\[(Loại|Loai)[:\s]*(single|multiple)\]/ui', $metaLine, $typeMatch)) {
                $currentType = strtolower($typeMatch[2]);
                $metaLine = preg_replace('/\[(Loại|Loai)[:\s]*(single|multiple)\]\s*/ui', '', $metaLine);
            }
            
            // Save remaining text as question text (trim để xử lý khoảng trắng dư)
            $questionText = trim($metaLine);
            
            continue;
        }

        // Parse regular options (A), B), C), D) or A., B., C., D.)
        if (preg_match('/^([A-D])[.)]\s*(.+)$/ui', $line, $matches)) {
            $optionLetter = strtoupper($matches[1]);
            $optionText = trim($matches[2]);
            
            // Check if this option is marked as correct with * (có thể có khoảng trắng)
            $isCorrect = false;
            $optionText = rtrim($optionText); // Xóa khoảng trắng bên phải
            if (substr($optionText, -1) === '*') {
                $optionText = trim(substr($optionText, 0, -1)); // Xóa * và trim
                $isCorrect = true;
            }
            
            $optionIndex = ord($optionLetter) - ord('A');
            $currentOptions[$optionIndex] = $optionText;
            
            if ($isCorrect) {
                $currentCorrectAnswers[] = $optionIndex;
            }
            
            continue;
        }

        // Parse correct answer line: "Đáp án đúng: A, B" or "Đáp án đúng: 1, 2"
        if (preg_match('/^Đáp án đúng:\s*(.+)$/ui', $line, $matches)) {
            $answerStr = trim($matches[1]);
            $currentCorrectAnswers = []; // Reset
            
            // Parse A, B, C or 1, 2, 3 format
            $answers = preg_split('/[,\s]+/', $answerStr);
            foreach ($answers as $ans) {
                $ans = trim($ans);
                if (preg_match('/^[A-D]$/i', $ans)) {
                    $currentCorrectAnswers[] = ord(strtoupper($ans)) - ord('A');
                } elseif (is_numeric($ans)) {
                    $currentCorrectAnswers[] = (int)$ans - 1;
                }
            }
            continue;
        }

        // Skip description lines starting with "Loại:", "LƯU Ý:", "Học sinh", "Hãy xác định"
        if (preg_match('/^(Loại|LƯU Ý|Học sinh|Hãy xác định|Hãy chọn|Trong):\s*/ui', $line)) {
            continue;
        }
        
        // Skip section headers like "=== PHẦN 1: TRẮC NGHIỆM ==="
        if (preg_match('/^(PHẦN|Phần)\s+\d+:/ui', $line)) {
            continue;
        }

        // If in question mode and not an option, add to question text
        if ($inQuestion && empty($currentOptions)) {
            $questionText .= ($questionText ? ' ' : '') . $line;
        }
    }

    // Save last question if exists
    if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
        $questions[] = [
            'topic' => $currentTopic,
            'lesson' => $currentLesson,
            'question' => [
                'question' => trim($questionText),
                'options' => $currentOptions,
                'correct_answers' => $currentCorrectAnswers,
                'level' => $currentLevel,
                'type' => $currentType
            ]
        ];
    }

    // Group questions by topic and lesson
    $groupedQuestions = [];
    foreach ($questions as $q) {
        $found = false;
        foreach ($groupedQuestions as &$group) {
            if ($group['topic'] === $q['topic'] && $group['lesson'] === $q['lesson']) {
                $group['questions'][] = $q['question'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $groupedQuestions[] = [
                'topic' => $q['topic'],
                'lesson' => $q['lesson'],
                'questions' => [$q['question']]
            ];
        }
    }

    return $groupedQuestions;
}

// Handle POST request for adding questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    header('Content-Type: application/json');

    try {
        // Validate required fields
        $requiredFields = ['topic', 'lesson', 'question_text', 'question_type', 'question_level', 'options'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Thiếu thông tin bắt buộc: $field");
            }
        }

        // Validate correct answers
        if (!isset($_POST['correct']) || empty($_POST['correct'])) {
            throw new Exception("Vui lòng chọn ít nhất một đáp án đúng");
        }

        $topic = $_POST['topic'];
        if ($topic === 'new_topic') {
            if (!isset($_POST['new_topic_name']) || empty(trim($_POST['new_topic_name']))) {
                throw new Exception("Vui lòng nhập tên chủ đề mới");
            }
            $topic = trim($_POST['new_topic_name']);
        }

        $lesson = $_POST['lesson'];
        if ($lesson === 'new_lesson') {
            if (!isset($_POST['new_lesson_name']) || empty(trim($_POST['new_lesson_name']))) {
                throw new Exception("Vui lòng nhập tên bài học mới");
            }
            $lesson = trim($_POST['new_lesson_name']);
        }

        $questionType = $_POST['question_type'];
        $correctAnswers = $_POST['correct'];

        // Validate question type and correct answers
        if ($questionType === 'single' && count($correctAnswers) > 1) {
            throw new Exception("Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng");
        }
        if ($questionType === 'multiple' && count($correctAnswers) < 2) {
            throw new Exception("Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng");
        }

        // Prepare question data
        $newQuestion = [
            'question' => trim($_POST['question_text']),
            'options' => array_map('trim', $_POST['options']),
            'correct' => $questionType === 'single' ? (int)$correctAnswers[0] : array_map('intval', $correctAnswers),
            'type' => $questionType,
            'level' => $_POST['question_level']
        ];

        // Load existing questions
        $questionsDir = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}";
        if (!is_dir($questionsDir)) {
            mkdir($questionsDir, 0755, true);
        }

        $questionsFile = $questionsDir . "/subject_{$selectedSubjectId}.json";
        $existingData = [];
        if (file_exists($questionsFile)) {
            $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        }

        // Find or add topic/lesson
        $topicIndex = null;
        foreach ($existingData as $idx => $item) {
            if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                $topicIndex = $idx;
                break;
            }
        }
        if ($topicIndex === null) {
            $existingData[] = [
                'topic' => $topic,
                'lesson' => $lesson,
                'questions' => []
            ];
            $topicIndex = count($existingData) - 1;
        }

        // Add new question
        $existingData[$topicIndex]['questions'][] = $newQuestion;

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được thêm thành công']);
        } else {
            throw new Exception("Không thể lưu câu hỏi vào file");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for deleting questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    header('Content-Type: application/json');

    try {
        $topicIndex = isset($_POST['topic_index']) ? (int)$_POST['topic_index'] : -1;
        $questionIndex = isset($_POST['index']) ? (int)$_POST['index'] : -1;

        if ($topicIndex < 0 || $questionIndex < 0) {
            throw new Exception("Thiếu thông tin chỉ số chủ đề hoặc câu hỏi");
        }

        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
        if (!file_exists($questionsFile)) {
            throw new Exception("File câu hỏi không tồn tại");
        }

        $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (!isset($existingData[$topicIndex]) || !isset($existingData[$topicIndex]['questions'][$questionIndex])) {
            throw new Exception("Câu hỏi không tồn tại");
        }

        // Remove the question
        array_splice($existingData[$topicIndex]['questions'], $questionIndex, 1);

        // If questions array is empty, remove the topic
        if (empty($existingData[$topicIndex]['questions'])) {
            unset($existingData[$topicIndex]);
            // Reindex the array
            $existingData = array_values($existingData);
        }

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được xóa thành công']);
        } else {
            throw new Exception("Không thể lưu file sau khi xóa câu hỏi");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for deleting all questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all_questions') {
    header('Content-Type: application/json');

    try {
        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";

        if (file_put_contents($questionsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Tất cả câu hỏi đã được xóa thành công']);
        } else {
            throw new Exception("Không thể xóa tất cả câu hỏi");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for editing questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_question') {
    header('Content-Type: application/json');

    try {
        // Validate required fields
        $requiredFields = ['edit_topic', 'edit_lesson', 'edit_question_text', 'edit_question_type', 'edit_question_level', 'edit_topic_index', 'edit_index'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                throw new Exception("Thiếu thông tin bắt buộc: $field");
            }
        }

        // Validate correct answers
        if (!isset($_POST['edit_correct']) || empty($_POST['edit_correct'])) {
            throw new Exception("Vui lòng chọn ít nhất một đáp án đúng");
        }

        $topicIndex = (int)$_POST['edit_topic_index'];
        $questionIndex = (int)$_POST['edit_index'];

        $topic = $_POST['edit_topic'];
        if ($topic === 'new_topic') {
            if (!isset($_POST['edit_new_topic_name']) || empty(trim($_POST['edit_new_topic_name']))) {
                throw new Exception("Vui lòng nhập tên chủ đề mới");
            }
            $topic = trim($_POST['edit_new_topic_name']);
        }

        $lesson = $_POST['edit_lesson'];
        if ($lesson === 'new_lesson') {
            if (!isset($_POST['edit_new_lesson_name']) || empty(trim($_POST['edit_new_lesson_name']))) {
                throw new Exception("Vui lòng nhập tên bài học mới");
            }
            $lesson = trim($_POST['edit_new_lesson_name']);
        }

        $questionType = $_POST['edit_question_type'];
        $correctAnswers = $_POST['edit_correct'];

        // Validate question type and correct answers
        if ($questionType === 'single' && count($correctAnswers) > 1) {
            throw new Exception("Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng");
        }
        if ($questionType === 'multiple' && count($correctAnswers) < 2) {
            throw new Exception("Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng");
        }

        // Load existing questions
        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
        if (!file_exists($questionsFile)) {
            throw new Exception("File câu hỏi không tồn tại");
        }

        $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (!isset($existingData[$topicIndex]) || !isset($existingData[$topicIndex]['questions'][$questionIndex])) {
            throw new Exception("Câu hỏi không tồn tại");
        }

        // Prepare updated question data
        $updatedQuestion = [
            'question' => trim($_POST['edit_question_text']),
            'options' => array_map('trim', $_POST['edit_options']),
            'correct' => $questionType === 'single' ? (int)$correctAnswers[0] : array_map('intval', $correctAnswers),
            'type' => $questionType,
            'level' => $_POST['edit_question_level']
        ];

        // If topic or lesson changed, handle moving
        $currentTopic = $existingData[$topicIndex]['topic'];
        $currentLesson = $existingData[$topicIndex]['lesson'];
        if ($topic !== $currentTopic || $lesson !== $currentLesson) {
            // Remove from current topic/lesson
            array_splice($existingData[$topicIndex]['questions'], $questionIndex, 1);
            // If questions empty, remove topic
            if (empty($existingData[$topicIndex]['questions'])) {
                unset($existingData[$topicIndex]);
                $existingData = array_values($existingData);
            }
            // Find or add new topic/lesson
            $newTopicIndex = null;
            foreach ($existingData as $idx => $item) {
                if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                    $newTopicIndex = $idx;
                    break;
                }
            }
            if ($newTopicIndex === null) {
                $existingData[] = [
                    'topic' => $topic,
                    'lesson' => $lesson,
                    'questions' => []
                ];
                $newTopicIndex = count($existingData) - 1;
            }
            // Add to new topic/lesson
            $existingData[$newTopicIndex]['questions'][] = $updatedQuestion;
        } else {
            // Update in place
            $existingData[$topicIndex]['questions'][$questionIndex] = $updatedQuestion;
        }

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được cập nhật thành công']);
        } else {
            throw new Exception("Không thể lưu câu hỏi đã cập nhật");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for importing from JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_questions') {
    $importMessage = '';
    $importError = '';

    $grade = $_POST['import_grade'] ?? '';
    $subjectId = (int)($_POST['import_subject_id'] ?? 0);

    if (!in_array($grade, $availableGrades)) {
        $importError = 'Khối không hợp lệ.';
    } elseif (!in_array($subjectId, $assignedSubjectIds)) {
        $importError = 'Môn học không hợp lệ hoặc không được phép.';
    } elseif (!isset($_FILES['questions_file']) || $_FILES['questions_file']['error'] !== UPLOAD_ERR_OK) {
        $importError = 'Vui lòng chọn file JSON hợp lệ để tải lên.';
    } else {
        $semester = $_POST['import_semester'] ?? '';
        if (!in_array($semester, ['hk1', 'hk2'])) {
            $importError = 'Học kì không hợp lệ.';
        } else {
            $questionsDir = __DIR__ . '/questions/' . $grade . '/' . $semester . '/';
            if (!is_dir($questionsDir)) {
                mkdir($questionsDir, 0755, true);
            }
            $fileContent = file_get_contents($_FILES['questions_file']['tmp_name']);
            $data = json_decode($fileContent, true);
            if ($data === null) {
                $importError = 'File JSON không hợp lệ.';
            } else {
                if (!is_array($data)) {
                    $importError = 'File JSON phải là mảng các chủ đề/bài học.';
                } else {
                $allValid = true;
                $normalizedData = [];
                foreach ($data as $topicItem) {
                    if (!isset($topicItem['topic'], $topicItem['lesson'], $topicItem['questions']) || !is_array($topicItem['questions'])) {
                        $allValid = false;
                        break;
                    }
                    $valid = true;
                    foreach ($topicItem['questions'] as &$q) {
                        if (!isset($q['question'], $q['options'], $q['correct'], $q['type'], $q['level'])) {
                            $valid = false;
                            break;
                        }
                        if ($q['type'] === 'single') {
                            if (is_array($q['correct']) && count($q['correct']) === 1) {
                                $q['correct'] = $q['correct'][0];
                            } elseif (!is_int($q['correct'])) {
                                $valid = false;
                                break;
                            }
                        } elseif ($q['type'] === 'multiple' && !is_array($q['correct'])) {
                            $valid = false;
                            break;
                        }
                    }
                    unset($q);
                    if (!$valid) {
                        $allValid = false;
                        break;
                    }
                    $normalizedData[] = $topicItem;
                }
                if (!$allValid) {
                    $importError = 'Định dạng câu hỏi không hợp lệ.';
                } else {
                    $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                    $existing = [];
                    if (file_exists($subjectQuestionsFile)) {
                        $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                    }
                    // Merge imported data into existing, avoiding duplicates
                    foreach ($normalizedData as $newTopicItem) {
                        $topic = $newTopicItem['topic'];
                        $lesson = $newTopicItem['lesson'];
                        $newQuestions = $newTopicItem['questions'];
                        $merged = false;
                        foreach ($existing as &$existingTopic) {
                            if ($existingTopic['topic'] === $topic && $existingTopic['lesson'] === $lesson) {
                                // Merge questions, avoiding duplicates based on question text
                                foreach ($newQuestions as $newQ) {
                                    $duplicate = false;
                                    foreach ($existingTopic['questions'] as $existingQ) {
                                        if ($existingQ['question'] === $newQ['question']) {
                                            $duplicate = true;
                                            break;
                                        }
                                    }
                                    if (!$duplicate) {
                                        $existingTopic['questions'][] = $newQ;
                                    }
                                }
                                $merged = true;
                                break;
                            }
                        }
                        unset($existingTopic);
                        if (!$merged) {
                            $existing[] = $newTopicItem;
                        }
                    }
                    if (file_put_contents($subjectQuestionsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                        $importMessage = 'Câu hỏi đã được nhập thành công cho môn học.';
                    } else {
                        $importError = 'Lỗi khi lưu câu hỏi.';
                    }
                }
            }
        }
        }
    }
}

// Handle POST request for importing from Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    $importMessage = '';
    $importError = '';

    $grade = $_POST['excel_import_grade'] ?? '';
    $subjectId = (int)($_POST['excel_import_subject_id'] ?? 0);

    if (!in_array($grade, $availableGrades)) {
        $importError = 'Khối không hợp lệ.';
    } elseif (!in_array($subjectId, $assignedSubjectIds)) {
        $importError = 'Môn học không hợp lệ hoặc không được phép.';
    } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $importError = 'Vui lòng chọn file Excel hợp lệ để tải lên.';
    } else {
        // Require PhpSpreadsheet (assuming it's installed via Composer)
        require_once '../vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            $semester = $_POST['excel_import_semester'] ?? '';
            if (!in_array($semester, ['hk1', 'hk2'])) {
                $importError = 'Học kì không hợp lệ.';
            } else {
                $questionsDir = __DIR__ . '/questions/' . $grade . '/' . $semester . '/';
                if (!is_dir($questionsDir)) {
                    mkdir($questionsDir, 0755, true);
                }

                $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                $existing = [];
                if (file_exists($subjectQuestionsFile)) {
                    $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                }

                foreach ($rows as $row) {
                // Expected columns: Topic, Lesson, Question, Option A, Option B, Option C, Option D, Correct (e.g., 1 or 1,3), Type (single/multiple), Level (NB/TH/VD/VDC)
                if (count($row) < 10) continue; // Skip invalid rows

                $topic = trim($row[0]);
                $lesson = trim($row[1]);
                $question = trim($row[2]);
                $options = [trim($row[3]), trim($row[4]), trim($row[5]), trim($row[6])];
                $correctStr = trim($row[7]);
                $type = trim($row[8]);
                $level = trim($row[9]);

                // Validate type and level
                if (!in_array($type, ['single', 'multiple']) || !in_array($level, ['NB', 'TH', 'VD', 'VDC'])) {
                    continue; // Skip invalid questions
                }

                // Parse correct answers
                if ($type === 'single') {
                    $correct = is_numeric($correctStr) ? (int)$correctStr - 1 : 0; // 1-based to 0-based
                } else {
                    $correctParts = array_map('intval', explode(',', $correctStr));
                    $correct = array_map(function($c) { return $c - 1; }, $correctParts);
                }

                // Find or create topic/lesson
                $topicIndex = null;
                foreach ($existing as $idx => $item) {
                    if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                        $topicIndex = $idx;
                        break;
                    }
                }
                if ($topicIndex === null) {
                    $existing[] = [
                        'topic' => $topic,
                        'lesson' => $lesson,
                        'questions' => []
                    ];
                    $topicIndex = count($existing) - 1;
                }

                // Add question, avoiding duplicates
                $duplicate = false;
                foreach ($existing[$topicIndex]['questions'] as $existingQ) {
                    if ($existingQ['question'] === $question) {
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    $existing[$topicIndex]['questions'][] = [
                        'question' => $question,
                        'options' => $options,
                        'correct' => $correct,
                        'type' => $type,
                        'level' => $level
                    ];
                }
            }

                if (file_put_contents($subjectQuestionsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $importMessage = 'Câu hỏi đã được nhập từ Excel thành công.';
                } else {
                    $importError = 'Lỗi khi lưu câu hỏi.';
                }
            }
        } catch (Exception $e) {
            $importError = 'Lỗi xử lý file Excel: ' . $e->getMessage();
        }
    }
}
?>
