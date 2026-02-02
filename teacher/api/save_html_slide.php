<?php
/**
 * Save HTML Slide API
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];

// Get POST data
$action = $_POST['action'] ?? '';
$slideId = $_POST['slide_id'] ?? '';
$title = trim($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';

if ($action !== 'save_html_slide') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and content required']);
    exit;
}

// Ensure directories exist
$uploadDir = __DIR__ . '/../../uploads/html_slides';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Load metadata
$metadataFile = __DIR__ . '/../../data/html_slides_metadata.json';
$htmlSlides = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Generate or use existing slide ID
if (empty($slideId)) {
    $slideId = 'slide_' . uniqid() . '_' . time();
    $isNew = true;
} else {
    $isNew = false;
    // Verify ownership
    if (!isset($htmlSlides[$slideId]) || $htmlSlides[$slideId]['teacher_username'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
}

// Save HTML file
$filename = $slideId . '.html';
$filePath = $uploadDir . '/' . $filename;

if (file_put_contents($filePath, $content) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update metadata
if ($isNew) {
    $htmlSlides[$slideId] = [
        'id' => $slideId,
        'title' => $title,
        'filename' => $filename,
        'file_path' => 'uploads/html_slides/' . $filename,
        'teacher_username' => $username,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'views' => 0
    ];
} else {
    $htmlSlides[$slideId]['title'] = $title;
    $htmlSlides[$slideId]['updated_at'] = date('Y-m-d H:i:s');
}

// Save metadata
if (file_put_contents($metadataFile, json_encode($htmlSlides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save metadata']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => $isNew ? 'Slide created successfully' : 'Slide updated successfully',
    'slide_id' => $slideId
]);
