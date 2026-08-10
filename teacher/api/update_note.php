<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';

session_name('CVD_SESSION');
session_start();

// Check if logged in and is teacher
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['student_code']) || !isset($input['exam_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$studentCode = $input['student_code'];
$examId = $input['exam_id'];
$notes = $input['notes'] ?? '';

// Update the student_score.json file (khóa đọc-ghi để không đè mất dữ liệu
// khi giáo viên chỉnh sửa đúng lúc học sinh đang nộp bài)
$studentScoreFile = __DIR__ . '/../../shared/scores/student_score.json';

$found = false;
$result = update_json_data($studentScoreFile, function($allStudentScores) use ($studentCode, $examId, $notes, &$found) {
    if (!is_array($allStudentScores)) { $allStudentScores = []; }

    // Find and update the matching record
    foreach ($allStudentScores as &$entry) {
        if (($entry['student_id'] ?? '') === $studentCode && ($entry['exam_id'] ?? '') === $examId) {
            $entry['notes'] = $notes;
            $found = true;
            break;
        }
    }

    return $allStudentScores;
}, []);

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Score record not found']);
    exit;
}

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save note']);
}
?>
