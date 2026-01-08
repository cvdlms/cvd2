<?php
$studentCode = $argv[1] ?? '';
if (!$studentCode) { echo "Usage: php get_scores_for_student.php <student_code>\n"; exit(1); }
$file = __DIR__ . '/../shared/scores/student_score.json';
if (!file_exists($file)) { echo "scores file not found\n"; exit(1); }
$data = json_decode(file_get_contents($file), true) ?: [];
$found = [];
foreach ($data as $entry) {
    if (($entry['student_id'] ?? '') === $studentCode) $found[] = $entry;
}
if (empty($found)) { echo "No scores found for $studentCode\n"; exit(0); }
foreach ($found as $f) {
    echo "exam_id:" . ($f['exam_id'] ?? '') . " | test_name:" . ($f['test_name'] ?? '') . " | attempts:" . ($f['attempts'] ?? '') . " | score:" . ($f['score'] ?? '') . " | timestamp:" . ($f['timestamp'] ?? '') . "\n";
}
?>