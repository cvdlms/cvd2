<?php
require_once __DIR__ . '/../includes/student_session.php';
require_once __DIR__ . '/../includes/json_db_helper.php';
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

// Ghi dưới khóa vì đây là file dùng chung của mọi học sinh
$entry = [
    'student_code' => $studentCode,
    'date' => date('Y-m-d'),
    'time' => date('H:i:s'),
    'subject_id' => $subjectId,
    'question_count' => (int)$questionCount,
    'timestamp' => time()
];

$result = update_json_data($historyFile, function($history) use ($entry) {
    if (!is_array($history)) { $history = []; }
    $history[] = $entry;
    return $history;
}, []);

if ($result !== false) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu lịch sử luyện tập']);
}
?>