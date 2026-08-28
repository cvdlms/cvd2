<?php
require_once __DIR__ . '/../includes/student_session.php';
require_once __DIR__ . '/../includes/json_db_helper.php';
require_once __DIR__ . '/../includes/school_year.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['student_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Only allow saving practice results for the logged-in student
$safeCode = preg_replace('/[^A-Za-z0-9_\-]/', '', $data['student_code'] ?? '');
if ($safeCode !== $_SESSION['student_code']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}
$data['student_code'] = $safeCode;

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

// Gắn nhãn năm học phục vụ lưu trữ tối thiểu 5 năm theo chuẩn kiểm tra đánh giá
school_year_stamp_record($data);

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
    'timestamp' => $data['timestamp'],
    'school_year' => $data['school_year']
];

// Save summary (ghi dưới khóa vì đây là file dùng chung của nhiều học sinh)
$summarySaved = update_json_data($summaryFile, function($summaryData) use ($practiceEntry) {
    if (!is_array($summaryData)) { $summaryData = []; }
    $summaryData[] = $practiceEntry;
    return $summaryData;
}, []);

if ($summarySaved === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save summary data']);
    exit;
}

// Save student data
$studentSaved = update_json_data($studentFile, function($studentData) use ($data) {
    if (!is_array($studentData)) { $studentData = []; }
    $studentData[] = $data;
    return $studentData;
}, []);

if ($studentSaved === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save student data']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Practice results saved successfully']);
?>
