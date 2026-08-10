<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$submissionsFile = __DIR__ . '/../../data/student_submissions.json';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$submissionId = $input['submission_id'] ?? '';
$score = $input['score'] ?? null;
$feedback = $input['feedback'] ?? '';

if (empty($submissionId)) {
    echo json_encode(['success' => false, 'message' => 'Missing submission ID']);
    exit;
}

// Sửa điểm/feedback dưới khóa để không đè mất bài nộp của học sinh đang nộp cùng lúc
$found = false;
$result = update_json_data($submissionsFile, function($submissions) use ($submissionId, $score, $feedback, &$found) {
    if (!is_array($submissions)) { return []; }
    foreach ($submissions as &$submission) {
        if ($submission['id'] === $submissionId) {
            $submission['score'] = floatval($score);
            $submission['feedback'] = $feedback;
            $submission['graded_at'] = date('Y-m-d H:i:s');
            $submission['graded_by'] = $_SESSION['username'];
            $found = true;
            break;
        }
    }
    return $submissions;
}, []);

if ($found && $result !== false) {
    echo json_encode(['success' => true, 'message' => 'Grade saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
}
?>
