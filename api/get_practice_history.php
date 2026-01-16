<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'history' => []]);
    exit;
}

$studentCode = $_GET['student_code'] ?? '';
if ($studentCode !== $_SESSION['student_code']) {
    echo json_encode(['success' => false, 'history' => []]);
    exit;
}

$historyFile = __DIR__ . '/../admin/student_practice_history.json';
if (!file_exists($historyFile)) {
    echo json_encode(['success' => true, 'history' => []]);
    exit;
}

$allHistory = json_decode(file_get_contents($historyFile), true) ?: [];
$studentHistory = array_filter($allHistory, function($record) use ($studentCode) {
    return $record['student_code'] === $studentCode;
});

echo json_encode([
    'success' => true,
    'history' => array_values($studentHistory)
]);
?>