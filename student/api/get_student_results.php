<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$studentCode = $_SESSION['student_code'];

// Load scores data
$scoresFile = __DIR__ . '/../../shared/api/scores.php';
if (!file_exists($scoresFile)) {
    echo json_encode(['success' => false, 'message' => 'Scores file not found']);
    exit;
}

require_once $scoresFile;

// Get all results for this student
$allScores = getAllScores();
$studentResults = [];

// Get student grade
$studentClassCode = $_SESSION['student_class_code'] ?? '';
$prefix = substr($studentClassCode, 0, 1);
$studentGrade = 'khoi' . $prefix;

foreach ($allScores as $score) {
    if ($score['student_code'] === $studentCode) {
        $score['test_name'] = $score['test_name'] ?? 'Bài kiểm tra trắc nghiệm'; // Ensure test_name exists
        $studentResults[] = $score;
    }
}

// Sort by timestamp descending (most recent first)
usort($studentResults, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

echo json_encode([
    'success' => true,
    'results' => array_slice($studentResults, 0, 10) // Return last 10 results
]);
?>
