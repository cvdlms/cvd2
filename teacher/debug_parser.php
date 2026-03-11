<?php
// Debug script để test parseQuestionsFromText function

require_once '../vendor/autoload.php';

function parseQuestionsFromText($text) {
    $lines = explode("\n", $text);
    $questions = [];
    $currentTopic = '';
    $currentLesson = '';
    $currentQuestion = null;
    $currentOptions = [];
    $currentCorrectAnswers = [];
    $currentLevel = 'NB';
    $currentType = 'single';
    $questionText = '';
    $inQuestion = false;

    echo "=== PARSING LINE BY LINE ===\n";
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        echo "Line $lineNum: [$line]\n";
        
        // Skip empty lines and separators
        if (empty($line) || $line === '---' || strpos($line, '===') === 0) {
            echo "  -> Skip (empty/separator)\n";
            // If we have a complete question, save it
            if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
                echo "  -> Saving question on separator\n";
                $questions[] = [
                    'topic' => $currentTopic,
                    'lesson' => $currentLesson,
                    'question' => [
                        'question' => trim($questionText),
                        'options' => $currentOptions,
                        'correct' => count($currentCorrectAnswers) === 1 ? $currentCorrectAnswers[0] : $currentCorrectAnswers,
                        'type' => $currentType,
                        'level' => $currentLevel
                    ]
                ];
                
                // Reset for next question
                $questionText = '';
                $currentOptions = [];
                $currentCorrectAnswers = [];
                $currentLevel = 'NB';
                $currentType = 'single';
                $inQuestion = false;
            } else if ($inQuestion) {
                echo "  -> Question incomplete: text=" . (!empty($questionText) ? 'yes' : 'no') 
                     . ", options=" . count($currentOptions) 
                     . ", correct=" . count($currentCorrectAnswers) . "\n";
            }
            continue;
        }

        // Parse metadata
        if (preg_match('/^Chủ đề:\s*(.+)$/ui', $line, $matches)) {
            $currentTopic = trim($matches[1]);
            echo "  -> Topic: $currentTopic\n";
            continue;
        }
        
        if (preg_match('/^Bài học:\s*(.+)$/ui', $line, $matches)) {
            $currentLesson = trim($matches[1]);
            echo "  -> Lesson: $currentLesson\n";
            continue;
        }

        // Parse question start
        if (preg_match('/^Câu\s+\d+:\s*(.*)$/ui', $line, $matches)) {
            // Save previous question if exists
            if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
                echo "  -> Saving previous question\n";
                $questions[] = [
                    'topic' => $currentTopic,
                    'lesson' => $currentLesson,
                    'question' => [
                        'question' => trim($questionText),
                        'options' => $currentOptions,
                        'correct' => count($currentCorrectAnswers) === 1 ? $currentCorrectAnswers[0] : $currentCorrectAnswers,
                        'type' => $currentType,
                        'level' => $currentLevel
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
            $metaLine = $matches[1];
            if (preg_match('/\[Mức độ:\s*(NB|TH|VD|VDC)\]/ui', $metaLine, $levelMatch)) {
                $currentLevel = strtoupper($levelMatch[1]);
            }
            if (preg_match('/\[Loại:\s*(single|multiple)\]/ui', $metaLine, $typeMatch)) {
                $currentType = strtolower($typeMatch[1]);
            }
            
            echo "  -> New question (level=$currentLevel, type=$currentType)\n";
            continue;
        }

        // Parse options (A), B), C), D) or A., B), C., D.)
        if (preg_match('/^([A-Z])[.)]\s*(.+)$/i', $line, $matches)) {
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
            
            echo "  -> Option $optionLetter ($optionIndex): $optionText" . ($isCorrect ? ' [CORRECT]' : '') . "\n";
            continue;
        }

        // Parse correct answer line
        if (preg_match('/^Đáp án đúng:\s*(.+)$/ui', $line, $matches)) {
            $answerStr = trim($matches[1]);
            // Parse A, B, C or 1, 2, 3 format
            $answers = preg_split('/[,\s]+/', $answerStr);
            foreach ($answers as $ans) {
                $ans = trim($ans);
                if (preg_match('/^[A-Z]$/i', $ans)) {
                    $currentCorrectAnswers[] = ord(strtoupper($ans)) - ord('A');
                } elseif (is_numeric($ans)) {
                    $currentCorrectAnswers[] = (int)$ans - 1;
                }
            }
            echo "  -> Correct answers: " . implode(', ', $currentCorrectAnswers) . "\n";
            continue;
        }

        // Skip "Loại:" lines
        if (preg_match('/^Loại:\s*/ui', $line)) {
            echo "  -> Skip (type description)\n";
            continue;
        }

        // If in question mode and not an option, add to question text
        if ($inQuestion && empty($currentOptions)) {
            $questionText .= ($questionText ? ' ' : '') . $line;
            echo "  -> Add to question text\n";
        }
    }

    // Save last question if exists
    if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
        echo "Saving last question\n";
        $questions[] = [
            'topic' => $currentTopic,
            'lesson' => $currentLesson,
            'question' => [
                'question' => trim($questionText),
                'options' => $currentOptions,
                'correct' => count($currentCorrectAnswers) === 1 ? $currentCorrectAnswers[0] : $currentCorrectAnswers,
                'type' => $currentType,
                'level' => $currentLevel
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

$wordFile = __DIR__ . '/generated_templates/mau_cau_hoi_word.docx';

if (!file_exists($wordFile)) {
    die("File không tồn tại: $wordFile\n");
}

try {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($wordFile);
    
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

    echo "=== EXTRACTED TEXT ===\n";
    echo $fullText;
    echo "\n\n";

    // Parse questions
    $parsedQuestions = parseQuestionsFromText($fullText);

    echo "=== PARSED QUESTIONS ===\n";
    echo "Total topics: " . count($parsedQuestions) . "\n\n";

    foreach ($parsedQuestions as $index => $topicData) {
        echo "Topic $index:\n";
        echo "  Chủ đề: {$topicData['topic']}\n";
        echo "  Bài học: {$topicData['lesson']}\n";
        echo "  Số câu hỏi: " . count($topicData['questions']) . "\n";
        
        foreach ($topicData['questions'] as $qIndex => $question) {
            echo "\n  Câu hỏi " . ($qIndex + 1) . ":\n";
            echo "    Question: {$question['question']}\n";
            echo "    Type: {$question['type']}\n";
            echo "    Level: {$question['level']}\n";
            echo "    Correct: " . print_r($question['correct'], true);
            echo "    Options:\n";
            foreach ($question['options'] as $optIndex => $option) {
                $marker = in_array($optIndex, (array)$question['correct']) ? ' [✓]' : '';
                echo "      [$optIndex] $option$marker\n";
            }
        }
        echo "\n";
    }

    if (empty($parsedQuestions)) {
        echo "⚠️ KHÔNG TÌM THẤY CÂU HỎI NÀO!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
