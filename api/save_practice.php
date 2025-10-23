<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$requiredFields = ['student_code', 'student_name', 'class_code', 'subject', 'topic', 'lesson', 'total_questions', 'correct_answers', 'incorrect_answers', 'score_percentage', 'timestamp', 'question_results'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Define directories
$practicesDir = __DIR__ . '/../shared/practices/';
$summaryFile = $practicesDir . 'student_practice.json';
$studentFile = $practicesDir . $data['student_code'] . '_practice.json';

// Ensure directory exists
if (!is_dir($practicesDir)) {
    mkdir($practicesDir, 0755, true);
}

// Load existing summary data
$summaryData = [];
if (file_exists($summaryFile)) {
    $summaryData = json_decode(file_get_contents($summaryFile), true) ?: [];
}

// Create practice entry for summary
$practiceEntry = [
    'student_id' => $data['student_code'],
    'student_name' => $data['student_name'],
    'class_code' => $data['class_code'],
    'subject' => $data['subject'],
    'topic' => $data['topic'],
    'lesson' => $data['lesson'],
    'total_questions' => $data['total_questions'],
    'correct_answers' => $data['correct_answers'],
    'incorrect_answers' => $data['incorrect_answers'],
    'score_percentage' => $data['score_percentage'],
    'timestamp' => $data['timestamp']
];

// Add to summary
$summaryData[] = $practiceEntry;

// Save summary
if (file_put_contents($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save summary data']);
    exit;
}

// Load existing student practice data
$studentData = [];
if (file_exists($studentFile)) {
    $studentData = json_decode(file_get_contents($studentFile), true) ?: [];
}

// Add detailed practice session to student file
$studentData[] = $data;

// Save student data
if (file_put_contents($studentFile, json_encode($studentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save student data']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Practice results saved successfully']);
?>
