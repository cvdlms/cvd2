<?php
require '../vendor/autoload.php';

// Copy parseQuestionsFromText function with fix
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
            }
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
        if (preg_match('/^Câu\s+\d+:\s*(.*)$/ui', $line, $matches)) {
            // Save previous question if exists
            if ($inQuestion && !empty($questionText) && !empty($currentOptions) && !empty($currentCorrectAnswers)) {
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

            // Parse metadata and question text from question line
            $metaLine = $matches[1];
            
            // Extract level if present
            if (preg_match('/\[Mức độ:\s*(NB|TH|VD|VDC)\]/ui', $metaLine, $levelMatch)) {
                $currentLevel = strtoupper($levelMatch[1]);
                $metaLine = preg_replace('/\[Mức độ:\s*(NB|TH|VD|VDC)\]/ui', '', $metaLine);
            }
            
            // Extract type if present
            if (preg_match('/\[Loại:\s*(single|multiple)\]/ui', $metaLine, $typeMatch)) {
                $currentType = strtolower($typeMatch[1]);
                $metaLine = preg_replace('/\[Loại:\s*(single|multiple)\]/ui', '', $metaLine);
            }
            
            // Save remaining text as question text
            $questionText = trim($metaLine);
            
            continue;
        }

        // Parse options (A), B), C), D) or A., B., C., D.)
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
            continue;
        }

        // Skip "Loại:" description lines
        if (preg_match('/^Loại:\s*/ui', $line)) {
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

// Test
$phpWord = \PhpOffice\PhpWord\IOFactory::load('generated_templates/mau_cau_hoi_word.docx');
$text = '';
foreach ($phpWord->getSections() as $s) {
    foreach ($s->getElements() as $e) {
        if (method_exists($e, 'getText')) {
            $text .= $e->getText() . "\n";
        }
    }
}

$parsed = parseQuestionsFromText($text);
echo "Found " . count($parsed) . " topic(s)\n\n";

foreach ($parsed as $t) {
    echo "Topic: {$t['topic']}\n";
    echo "Lesson: {$t['lesson']}\n";
    echo "Questions: " . count($t['questions']) . "\n\n";
    
    foreach ($t['questions'] as $i => $q) {
        echo "  Câu " . ($i + 1) . ": {$q['question']}\n";
        echo "  Type: {$q['type']}, Level: {$q['level']}\n";
        echo "  Correct: " . (is_array($q['correct']) ? implode(', ', $q['correct']) : $q['correct']) . "\n\n";
    }
}
