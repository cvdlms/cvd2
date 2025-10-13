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

$testName = $_GET['test_name'] ?? '';
$studentCode = $_SESSION['student_code'];

if (!$testName) {
    echo json_encode(['success' => false, 'message' => 'Test name required']);
    exit;
}

// Load scores data
$scoresFile = __DIR__ . '/../../shared/api/scores.php';
if (!file_exists($scoresFile)) {
    echo json_encode(['success' => false, 'message' => 'Scores file not found']);
    exit;
}

require_once $scoresFile;

// Get attempts for this student and test name
$attempts = getStudentAttempts($studentCode, $testName);

$maxAttempts = 1;
$currentAttempts = count($attempts);

if ($currentAttempts >= $maxAttempts) {
    echo json_encode([
        'success' => true,
        'can_take' => false,
        'attempts' => $currentAttempts,
        'message' => 'Bạn đã đạt giới hạn số lần thi. Vui lòng liên hệ giáo viên để reset.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'can_take' => true,
        'attempts' => $currentAttempts,
        'remaining' => $maxAttempts - $currentAttempts
    ]);
}
?>
