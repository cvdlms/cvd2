<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

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

// Load submissions
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

$found = false;
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

if ($found) {
    file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'message' => 'Grade saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
}
?>
