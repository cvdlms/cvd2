<?php
require_once __DIR__ . '/../includes/student_session.php';
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

$historyFile = __DIR__ . '/../admin/student_practice_history.json';
$history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
if (!is_array($history)) $history = [];

$history[] = [
    'student_code' => $studentCode,
    'date' => date('Y-m-d'),
    'time' => date('H:i:s'),
    'subject_id' => $subjectId,
    'question_count' => (int)$questionCount,
    'timestamp' => time()
];

file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
?>