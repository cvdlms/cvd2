<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Update the student_score.json file
$studentScoreFile = __DIR__ . '/../../shared/scores/student_score.json';

if (!file_exists($studentScoreFile)) {
    echo json_encode(['success' => false, 'message' => 'Score file not found']);
    exit;
}

$allStudentScores = json_decode(file_get_contents($studentScoreFile), true) ?? [];

// Find and update the matching record
$found = false;
foreach ($allStudentScores as &$entry) {
    if ($entry['student_id'] === $studentCode && $entry['exam_id'] === $examId) {
        $entry['notes'] = $notes;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Score record not found']);
    exit;
}

// Save the updated data
$result = file_put_contents($studentScoreFile, json_encode($allStudentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save note']);
}
?>
