<?php
/**
 * VALIDATION SCRIPT: Validate question JSON format
 * 
 * Checks:
 * - JSON syntax
 * - Required fields
 * - Field types
 * - Value constraints
 * - Question type consistency
 * 
 * Usage:
 * php validate_questions.php <json_file>
 * 
 * Example:
 * php validate_questions.php khoi8/hk1/subject_1.json
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

if ($argc < 2) {
    echo "Usage: php validate_questions.php <json_file>\n";
    echo "Example: php validate_questions.php khoi8/hk1/subject_1.json\n";
    exit(1);
}

$jsonFile = $argv[1];

if (!file_exists($jsonFile)) {
    echo "❌ Error: File not found: $jsonFile\n";
    exit(1);
}

echo "🔍 Validating: $jsonFile\n\n";

// Read and parse JSON
$json = file_get_contents($jsonFile);
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON Syntax Error: " . json_last_error_msg() . "\n";
    exit(1);
}

if (!is_array($data)) {
    echo "❌ Error: Root must be an array\n";
    exit(1);
}

$errors = [];
$warnings = [];
$stats = [
    'topics' => 0,
    'questions' => 0,
    'by_type' => ['single' => 0, 'true_false' => 0, 'essay' => 0],
    'by_level' => ['NB' => 0, 'TH' => 0, 'VD' => 0, 'VDC' => 0]
];

// Validate each topic
foreach ($data as $topicIndex => $topic) {
    $topicNum = $topicIndex + 1;
    $stats['topics']++;
    
    // Check required fields
    if (empty($topic['topic_name'])) {
        if (empty($topic['topic'])) {
            $errors[] = "Topic #$topicNum: Missing 'topic_name' field";
        } else {
            $warnings[] = "Topic #$topicNum: Using old format 'topic' instead of 'topic_name'";
        }
    }
    
    if (empty($topic['unit_name'])) {
        if (empty($topic['lesson']) && empty($topic['unit'])) {
            $errors[] = "Topic #$topicNum: Missing 'unit_name' field";
        } else {
            $warnings[] = "Topic #$topicNum: Using old format 'lesson'/'unit' instead of 'unit_name'";
        }
    }
    
    if (!isset($topic['questions'])) {
        $errors[] = "Topic #$topicNum: Missing 'questions' field";
        continue;
    }
    
    if (!is_array($topic['questions'])) {
        $errors[] = "Topic #$topicNum: 'questions' must be an array";
        continue;
    }
    
    if (empty($topic['questions'])) {
        $warnings[] = "Topic #$topicNum: No questions found";
        continue;
    }
    
    $topicName = $topic['topic_name'] ?? $topic['topic'] ?? "Topic #$topicNum";
    $unitName = $topic['unit_name'] ?? $topic['lesson'] ?? $topic['unit'] ?? "Unknown";
    
    // Validate each question
    foreach ($topic['questions'] as $qIndex => $question) {
        $qNum = $qIndex + 1;
        $qPath = "$topicName / $unitName / Q$qNum";
        $stats['questions']++;
        
        // Required: question text
        if (empty($question['question'])) {
            $errors[] = "$qPath: Missing 'question' text";
        }
        
        // Required: type
        if (empty($question['type'])) {
            $errors[] = "$qPath: Missing 'type' field";
        } else {
            $type = $question['type'];
            $validTypes = ['single', 'true_false', 'essay'];
            
            if (!in_array($type, $validTypes)) {
                $errors[] = "$qPath: Invalid type '$type' (must be: " . implode(', ', $validTypes) . ")";
            } else {
                $stats['by_type'][$type]++;
                
                // Type-specific validation
                if ($type === 'single' || $type === 'true_false') {
                    // TNKQ/DS must have options and correct
                    if (!isset($question['options'])) {
                        $errors[] = "$qPath: Missing 'options' for type '$type'";
                    } elseif (!is_array($question['options'])) {
                        $errors[] = "$qPath: 'options' must be an array";
                    } else {
                        $optCount = count($question['options']);
                        
                        if ($type === 'true_false' && $optCount !== 2) {
                            $errors[] = "$qPath: True/False must have exactly 2 options";
                        } elseif ($type === 'single' && ($optCount < 2 || $optCount > 6)) {
                            $warnings[] = "$qPath: Single choice should have 2-6 options (found $optCount)";
                        }
                    }
                    
                    if (!isset($question['correct'])) {
                        $errors[] = "$qPath: Missing 'correct' answer for type '$type'";
                    } elseif (!is_numeric($question['correct'])) {
                        $errors[] = "$qPath: 'correct' must be a number";
                    } elseif (isset($question['options'])) {
                        $correct = (int)$question['correct'];
                        $optCount = count($question['options']);
                        if ($correct < 0 || $correct >= $optCount) {
                            $errors[] = "$qPath: 'correct' index $correct out of range (0-" . ($optCount - 1) . ")";
                        }
                    }
                } elseif ($type === 'essay') {
                    // Essay should have points, shouldn't have options/correct
                    if (isset($question['options'])) {
                        $warnings[] = "$qPath: Essay type shouldn't have 'options'";
                    }
                    if (isset($question['correct'])) {
                        $warnings[] = "$qPath: Essay type shouldn't have 'correct'";
                    }
                    if (!isset($question['points'])) {
                        $warnings[] = "$qPath: Essay should have 'points' field";
                    }
                }
            }
        }
        
        // Required: level
        if (empty($question['level'])) {
            $errors[] = "$qPath: Missing 'level' field";
        } else {
            $level = $question['level'];
            $validLevels = ['NB', 'TH', 'VD', 'VDC'];
            
            if (!in_array($level, $validLevels)) {
                $errors[] = "$qPath: Invalid level '$level' (must be: " . implode(', ', $validLevels) . ")";
            } else {
                $stats['by_level'][$level]++;
            }
        }
    }
}

// Print results
echo "📊 Statistics:\n";
echo "   Topics:    {$stats['topics']}\n";
echo "   Questions: {$stats['questions']}\n";
echo "\n";
echo "   By Type:\n";
echo "     - Single choice:  {$stats['by_type']['single']}\n";
echo "     - True/False:     {$stats['by_type']['true_false']}\n";
echo "     - Essay:          {$stats['by_type']['essay']}\n";
echo "\n";
echo "   By Level:\n";
echo "     - NB (Nhận biết):  {$stats['by_level']['NB']}\n";
echo "     - TH (Thông hiểu): {$stats['by_level']['TH']}\n";
echo "     - VD (Vận dụng):   {$stats['by_level']['VD']}\n";
echo "     - VDC (VD cao):    {$stats['by_level']['VDC']}\n";
echo "\n";

if (!empty($warnings)) {
    echo "⚠️  Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ Errors (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
    echo "❌ Validation FAILED\n";
    exit(1);
} else {
    echo "✅ Validation PASSED\n";
    
    if (!empty($warnings)) {
        echo "\nNote: File is valid but has warnings. Consider fixing them for better compatibility.\n";
        exit(0);
    }
    
    exit(0);
}
