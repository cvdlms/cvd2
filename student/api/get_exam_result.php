<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_name('CVD_STUDENT_SESSION');
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

// Search in official scores
foreach ($allScores as $score) {
    if (isset($score['id']) && $score['id'] === $examId && $score['student_code'] === $studentCode) {
        $examResult = $score;
        $examResult['test_name'] = $examResult['test_name'] ?? 'Bài kiểm tra trắc nghiệm';
        break;
    }
}

// If not found in official scores, search in practice results
if (!$examResult) {
    $practiceFile = __DIR__ . '/../../data/practice_results/practice_results.json';
    if (file_exists($practiceFile)) {
        $practiceResults = json_decode(file_get_contents($practiceFile), true) ?? [];
        foreach ($practiceResults as $result) {
            if (isset($result['id']) && $result['id'] === $examId && $result['student_code'] === $studentCode) {
                $examResult = $result;
                $examResult['test_name'] = $examResult['test_name'] ?? 'Bài luyện tập';
                break;
            }
        }
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
