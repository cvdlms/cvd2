<?php
/**
 * Update PPT view count
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['file_id'] ?? '';

if (empty($fileId)) {
    echo json_encode(['success' => false, 'message' => 'No file ID']);
    exit;
}

// Load metadata
$metadataFile = __DIR__ . '/../../data/ppt_metadata.json';
$pptFiles = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

if (isset($pptFiles[$fileId])) {
    $pptFiles[$fileId]['views'] = ($pptFiles[$fileId]['views'] ?? 0) + 1;
    file_put_contents($metadataFile, json_encode($pptFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'File not found']);
}
