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
$studentCode = $data['studentCode'] ?? '';
$part = $data['part'] ?? ''; // 'TX1', 'TX2', or 'both'

if (!$className || !$studentCode) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: className and studentCode']);
    exit;
}

// Load existing scores
$scoresFile = '../data/scores.json';
$scores = [];
if (file_exists($scoresFile)) {
    $scores = json_decode(file_get_contents($scoresFile), true) ?: [];
}

// Find student entry
$key = $className . '_' . $studentCode;
if (!isset($scores[$key])) {
    http_response_code(404);
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Reset attempts based on part
if ($part === 'both') {
    $scores[$key]['tx1_attempts'] = 0;
    $scores[$key]['tx2_attempts'] = 0;
    $message = 'Đã reset số lần thi cho cả TX1 và TX2';
} elseif ($part === 'TX1') {
    $scores[$key]['tx1_attempts'] = 0;
    $message = 'Đã reset số lần thi TX1';
} elseif ($part === 'TX2') {
    $scores[$key]['tx2_attempts'] = 0;
    $message = 'Đã reset số lần thi TX2';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid part. Must be TX1, TX2, or both']);
    exit;
}

// Save back
if (file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT))) {
    echo json_encode(['message' => $message]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reset attempts']);
}
?>
