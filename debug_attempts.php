<?php
/**
 * Debug getStudentAttempts
 */

$studentCode = '2203858901';
$testId = 'BAI_20260112152634_32dd7b';
$studentFile = __DIR__ . '/shared/scores/' . $studentCode . '.json';

echo "=== DEBUG getStudentAttempts ===\n\n";
echo "Student Code: $studentCode\n";
echo "Test ID: $testId\n";
echo "File: $studentFile\n";
echo "File exists: " . (file_exists($studentFile) ? 'YES' : 'NO') . "\n\n";

if (file_exists($studentFile)) {
    $raw = file_get_contents($studentFile);
    echo "Raw Content:\n$raw\n\n";
    
    $data = json_decode($raw, true);
    echo "Decoded Data:\n";
    var_dump($data);
    echo "\n";
    
    if (is_array($data)) {
        echo "Is Array: YES\n";
        echo "Count: " . count($data) . "\n\n";
        
        foreach ($data as $idx => $entry) {
            echo "Entry #$idx:\n";
            echo "  - source_exam_id: " . ($entry['source_exam_id'] ?? 'NOT SET') . "\n";
            echo "  - exam_id: " . ($entry['exam_id'] ?? 'NOT SET') . "\n";
            echo "  - Match test_id? ";
            
            $match = false;
            if ((isset($entry['source_exam_id']) && $entry['source_exam_id'] === $testId)) {
                echo "YES (via source_exam_id)\n";
                $match = true;
            } elseif ((isset($entry['exam_id']) && $entry['exam_id'] === $testId)) {
                echo "YES (via exam_id)\n";
                $match = true;
            } else {
                echo "NO\n";
                echo "     source_exam_id='". ($entry['source_exam_id'] ?? '') . "' !== '$testId'\n";
                echo "     exam_id='". ($entry['exam_id'] ?? '') . "' !== '$testId'\n";
            }
            echo "\n";
        }
    } else {
        echo "ERROR: Not an array!\n";
    }
}

// Test với hàm thật
require_once __DIR__ . '/shared/api/scores.php';
$attempts = getStudentAttempts($studentCode, $testId);
echo "\n=== RESULT FROM getStudentAttempts() ===\n";
echo "Count: " . count($attempts) . "\n";
var_dump($attempts);
