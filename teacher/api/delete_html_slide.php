<?php
/**
 * Delete HTML Slide API
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];

$data = json_decode(file_get_contents('php://input'), true);
$slideId = $data['slide_id'] ?? '';

if (empty($slideId)) {
    echo json_encode(['success' => false, 'message' => 'No slide ID']);
    exit;
}

// Load metadata
$metadataFile = __DIR__ . '/../../data/html_slides_metadata.json';
$htmlSlides = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Verify ownership and delete
if (isset($htmlSlides[$slideId]) && $htmlSlides[$slideId]['teacher_username'] === $username) {
    // Delete HTML file
    $filePath = __DIR__ . '/../../' . $htmlSlides[$slideId]['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Remove from metadata
    unset($htmlSlides[$slideId]);
    file_put_contents($metadataFile, json_encode($htmlSlides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode(['success' => true, 'message' => 'Slide deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Slide not found or permission denied']);
}
