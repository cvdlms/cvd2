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

$examId = $_GET['exam_id'] ?? '';
$studentCode = $_SESSION['student_code'];

if (!$examId) {
    echo json_encode(['success' => false, 'message' => 'Exam ID required']);
    exit;
}

// Load scores data
$scoresFile = __DIR__ . '/../../shared/api/scores.php';
if (!file_exists($scoresFile)) {
    echo json_encode(['success' => false, 'message' => 'Scores file not found']);
    exit;
}

require_once $scoresFile;

// Get all scores and find the specific exam
$allScores = getAllScores();
$examResult = null;

foreach ($allScores as $score) {
    if ($score['id'] === $examId && $score['student_code'] === $studentCode) {
        $examResult = $score;
        $examResult['test_name'] = $examResult['test_name'] ?? 'Bài kiểm tra trắc nghiệm'; // Ensure test_name exists
        break;
    }
}

if ($examResult) {
    echo json_encode([
        'success' => true,
        'result' => $examResult
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Exam result not found']);
}
?>
