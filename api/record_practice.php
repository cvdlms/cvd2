<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$studentCode = $input['student_code'] ?? '';
$subjectId = $input['subject_id'] ?? '';
$questionCount = $input['question_count'] ?? 0;

if ($studentCode !== $_SESSION['student_code']) {
    echo json_encode(['success' => false, 'message' => 'Không hợp lệ']);
    exit;
}

require_once __DIR__ . '/../includes/student_premium_helper.php';
recordPracticeSession($studentCode, $subjectId, $questionCount);

echo json_encode(['success' => true]);
?>