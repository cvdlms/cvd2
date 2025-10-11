<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$className = $data['className'] ?? '';
$studentName = $data['studentName'] ?? '';
$part = $data['part'] ?? '';
$score = $data['score'] ?? null;

if (!$className || !$studentName || !$part || $score === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Parse student name to extract code and full name
// Format: "9. 2405684063 Nguyễn Lâm Bảo"
$studentCode = '';
$fullName = $studentName;
$hoTen = $studentName; // Default to full name

// Try to extract student code from format "STT. CODE FULL_NAME"
if (preg_match('/^(\d+)\.\s*([^\s]+)\s*(.+)$/', $studentName, $matches)) {
    $stt = $matches[1]; // STT number like "9"
    $studentCode = $matches[2]; // Student code like "2405684063"
    $hoTen = trim($matches[3]); // Full name like "Nguyễn Lâm Bảo"

    // Log successful parsing
    error_log("Student name parsed successfully: STT={$stt}, Code={$studentCode}, Name={$hoTen}");
} else {
    // Log warning for unparseable names
    error_log("Warning: Could not parse student name: {$studentName}");
}

// Load existing scores
$scoresFile = '../data/scores.json';
$scores = [];
if (file_exists($scoresFile)) {
    $scores = json_decode(file_get_contents($scoresFile), true) ?: [];
}

// Find student entry using student code as key
$key = $className . '_' . $studentCode;
if (!isset($scores[$key])) {
    http_response_code(400);
    echo json_encode(['error' => 'Học sinh không tồn tại trong danh sách. Vui lòng kiểm tra lại thông tin.']);
    exit;
}

// Check attempt limit before saving
$attemptField = $part === 'TX1' ? 'tx1_attempts' : 'tx2_attempts';
$scoreField = $part === 'TX1' ? 'tx1_score' : 'tx2_score';

$currentAttempts = $scores[$key][$attemptField] ?? 0;
if ($currentAttempts >= 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Đã đạt giới hạn 3 lần thi cho phần này. Vui lòng liên hệ giáo viên để được reset.']);
    exit;
}

$scores[$key][$attemptField]++;
$scores[$key][$scoreField] = $score;

// Save back
if (file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT))) {
    echo json_encode(['message' => 'Điểm số đã được lưu thành công!']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save score']);
}
?>
