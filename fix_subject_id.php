<?php
// Script to fix subject_id in existing student_score.json
// Run this once to update all existing records

$studentScoreFile = __DIR__ . '/shared/scores/student_score.json';

if (!file_exists($studentScoreFile)) {
    echo "student_score.json not found!\n";
    exit(1);
}

$scores = json_decode(file_get_contents($studentScoreFile), true);
if (!$scores) {
    echo "Failed to decode JSON\n";
    exit(1);
}

// For now, let's manually set subject_id based on test_name patterns
// This is a temporary fix since the exam files may not exist anymore
$subjectMappings = [
    'Tinhoc8-test' => 1, // Tin học
    'Bai kiem tra 1 test' => 1, // Tin học
    'TX1' => 1, // Assuming Tin học for TX1
    'TX2' => 1, // Assuming Tin học for TX2
    'Bai tap 2' => 1, // Assuming Tin học
    'Bai tap o nha' => 1, // Assuming Tin học
    'Bài kiểm tra lần 1' => 1, // Assuming Tin học
    'KTTX 2' => 1, // Assuming Tin học
    'ÔN TẬP GIỮA KÌ' => 1, // Assuming Tin học
    'Thien-kiemtra' => 1, // Assuming Tin học
];

$fixed = 0;
foreach ($scores as &$score) {
    if (empty($score['subject_id']) && isset($score['test_name'])) {
        $testName = $score['test_name'];
        if (isset($subjectMappings[$testName])) {
            $score['subject_id'] = $subjectMappings[$testName];
            $fixed++;
            echo "Fixed subject_id for exam {$score['exam_id']} ({$testName}): {$subjectMappings[$testName]}\n";
        } else {
            echo "Could not find mapping for test_name: {$testName}\n";
        }
    }
}

if ($fixed > 0) {
    file_put_contents($studentScoreFile, json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nFixed {$fixed} records with subject_id\n";
} else {
    echo "\nNo records needed fixing\n";
}
?>
