<?php
/**
 * Debug Word Import - Test parsing logic
 * 
 * Để test: Upload file Word và xem kết quả extract
 */

require_once '../vendor/autoload.php';

/**
 * Parse questions from Word text content (simplified - TNKQ only)
 * CHỈ HỖ TRỢ: single và multiple choice
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

        // Parse question start
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
            
            // Extract level
            if (preg_match('/\[(Mức độ|Muc do|Mức\s*độ|Muc\s*do)[:\s]*(NB|TH|VD|VDC)\]/ui', $metaLine, $levelMatch)) {
                $currentLevel = strtoupper($levelMatch[2]);
                $metaLine = preg_replace('/\[(Mức độ|Muc do|Mức\s*độ|Muc\s*do)[:\s]*(NB|TH|VD|VDC)\]/ui', '', $metaLine);
            }
            
            // Extract type - CHỈ single hoặc multiple
            if (preg_match('/\[(Loại|Loai)[:\s]*(single|multiple)\]/ui', $metaLine, $typeMatch)) {
                $currentType = strtolower($typeMatch[2]);
                $metaLine = preg_replace('/\[(Loại|Loai)[:\s]*(single|multiple)\]/ui', '', $metaLine);
            }
            
            // Save remaining text as question text
            $questionText = trim($metaLine);
            
            continue;
        }

        // Parse regular options (A-D only)
        if (preg_match('/^([A-D])[.)]\s*(.+)$/i', $line, $matches)) {
            $optionLetter = strtoupper($matches[1]);
            $optionText = trim($matches[2]);
            
            // Check if this option is marked as correct with *
            $isCorrect = false;
            if (substr($optionText, -1) === '*') {
                $optionText = trim(substr($optionText, 0, -1));
                $isCorrect = true;
            }
            
            $optionIndex = ord($optionLetter) - ord('A');
            $currentOptions[$optionIndex] = $optionText;
            
            if ($isCorrect) {
                $currentCorrectAnswers[] = $optionIndex;
            }
            
            continue;
        }

        // Parse correct answer line
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

        // Skip description lines
        if (preg_match('/^(Loại|LƯU Ý|Học sinh|Hãy xác định|Hãy chọn|Trong|PHẦN|Phần):\s*/ui', $line)) {
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

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Word Import</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .section-body {
            padding: 20px;
        }
        form {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        .form-group {
            flex: 1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 500;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #3498db;
            border-radius: 4px;
            background: #f8f9fa;
        }
        button {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
            border: 1px solid #e0e0e0;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 12px;
            border-radius: 4px;
            color: #155724;
            margin-bottom: 15px;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 12px;
            border-radius: 4px;
            color: #721c24;
            margin-bottom: 15px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug Word Import Parser</h1>
        
        <div class="section">
            <div class="section-header">📤 Upload File Word</div>
            <div class="section-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Chọn file .docx:</label>
                        <input type="file" name="word_file" accept=".docx" required>
                    </div>
                    <button type="submit" name="action" value="debug">🔍 Debug</button>
                </form>
            </div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'debug') {
            if (!isset($_FILES['word_file']) || $_FILES['word_file']['error'] !== UPLOAD_ERR_OK) {
                echo '<div class="error">❌ Lỗi upload file</div>';
            } else {
                try {
                    echo '<div class="success">✅ File uploaded: ' . htmlspecialchars($_FILES['word_file']['name']) . '</div>';
                    
                    // Load Word file
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($_FILES['word_file']['tmp_name']);
                    
                    // Extract text
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
                    
                    echo '<div class="grid">';
                    
                    // Show extracted text
                    echo '<div class="section">';
                    echo '<div class="section-header">📄 Extracted Text (' . strlen($fullText) . ' chars)</div>';
                    echo '<div class="section-body">';
                    echo '<pre>' . htmlspecialchars($fullText) . '</pre>';
                    echo '</div></div>';
                    
                    // Parse questions
                    $parsedQuestions = parseQuestionsFromText($fullText);
                    
                    echo '<div class="section">';
                    echo '<div class="section-header">📊 Parsed Result</div>';
                    echo '<div class="section-body">';
                    
                    if (empty($parsedQuestions)) {
                        echo '<div class="error">❌ Không tìm thấy câu hỏi nào!</div>';
                        
                        // Debug: show what patterns we're looking for
                        echo '<div style="margin-top: 20px;">';
                        echo '<strong>🔍 Checking patterns:</strong><br>';
                        echo 'Looking for "Chủ đề:": ' . (preg_match('/Chủ đề:/ui', $fullText) ? '✅ Found' : '❌ Not found') . '<br>';
                        echo 'Looking for "Bài học:": ' . (preg_match('/Bài học:/ui', $fullText) ? '✅ Found' : '❌ Not found') . '<br>';
                        echo 'Looking for "Câu (number)": ' . (preg_match('/Câu\s+\d+[\s.:]/ui', $fullText) ? '✅ Found' : '❌ Not found') . '<br>';
                        echo 'Looking for "[Mức độ]" or "[Muc do]": ' . (preg_match('/\[(Mức độ|Muc do):/ui', $fullText) ? '✅ Found' : '❌ Not found') . '<br>';
                        echo 'Looking for "[Loại]" or "[Loai]": ' . (preg_match('/\[(Loại|Loai):/ui', $fullText) ? '✅ Found' : '❌ Not found') . '<br>';
                        
                        // Show first few lines
                        echo '<br><strong>First 10 lines:</strong><pre>';
                        $lines = explode("\n", $fullText);
                        foreach (array_slice($lines, 0, 10) as $i => $line) {
                            echo ($i + 1) . ': ' . htmlspecialchars($line) . "\n";
                        }
                        echo '</pre>';
                        echo '</div>';
                    } else {
                        $totalQuestions = 0;
                        foreach ($parsedQuestions as $topic) {
                            $totalQuestions += count($topic['questions']);
                        }
                        
                        echo '<div class="stats">';
                        echo '<div class="stat-card"><div class="stat-number">' . count($parsedQuestions) . '</div><div class="stat-label">Topics/Lessons</div></div>';
                        echo '<div class="stat-card"><div class="stat-number">' . $totalQuestions . '</div><div class="stat-label">Total Questions</div></div>';
                        echo '</div>';
                        
                        echo '<pre>' . json_encode($parsedQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                    }
                    
                    echo '</div></div>';
                    echo '</div>'; // end grid
                    
                } catch (Exception $e) {
                    echo '<div class="error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
        ?>
        
        <div class="section" style="margin-top: 30px;">
            <div class="section-header">📋 Format được hỗ trợ</div>
            <div class="section-body">
                <p style="margin-bottom: 15px;"><strong>✅ CHỈ hỗ trợ câu hỏi TRẮC NGHIỆM (TNKQ):</strong></p>
                <ul style="margin-bottom: 15px; padding-left: 20px;">
                    <li><strong>single:</strong> Một đáp án đúng (A, B, C hoặc D)</li>
                    <li><strong>multiple:</strong> Nhiều đáp án đúng (A+B, A+C+D, v.v.)</li>
                </ul>
                
                <p style="color: #e74c3c; margin-bottom: 15px;"><strong>❌ KHÔNG hỗ trợ:</strong> Câu hỏi Đúng/Sai, Tự luận</p>
                
                <pre>Chủ đề: Chương 1: Phương trình

Bài học: Bài 1: Giải phương trình bậc hai

<strong>Câu hỏi MỘT đáp án đúng:</strong>
Câu 1: [Mức độ: NB] [Loại: single] Nghiệm của $x^2 = 4$ là:
A) $x = 2$
B) $x = -2$
C) $x = \pm 2$ *
D) $x = 4$
---

<strong>Câu hỏi NHIỀU đáp án đúng:</strong>
Câu 2: [Mức độ: TH] [Loại: multiple] Chọn đáp án đúng:
A) Đáp án 1 *
B) Đáp án 2
C) Đáp án 3 *
D) Đáp án 4 *
---

<strong>Hoặc viết không dấu (cũng được):</strong>
Câu 3 [Muc do: VD] [Loai: single] Câu hỏi khác
A. Đáp án A
B. Đáp án B *
C. Đáp án C
D. Đáp án D
</pre>
                <p style="color: #e74c3c; font-weight: bold; margin-top: 15px;">
                    ⚠️ LƯU Ý QUAN TRỌNG:<br>
                    - Dấu <strong>*</strong> đánh dấu đáp án đúng (đặt sau đáp án) <br>
                    - Chỉ có A, B, C, D (4 đáp án)<br>
                    - Mỗi câu hỏi phải có ít nhất 1 đáp án đúng<br>
                    - Dùng <strong>---</strong> để ngăn cách các câu hỏi
                </p>
            </div>
        </div>
    </div>
</body>
</html>
