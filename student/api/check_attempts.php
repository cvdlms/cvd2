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

$examId = $_GET['exam_id'] ?? $_GET['exam_type'] ?? '';
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

// Get attempts for this student and exam type
$attempts = getStudentAttempts($studentCode, $examId);

$maxAttempts = 2;
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
