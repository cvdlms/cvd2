<?php
/**
 * API: Save HTML Presentation (Multi-slide support)
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$action = $_POST['action'] ?? '';

if ($action !== 'save_html_presentation') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$presentationId = $_POST['presentation_id'] ?? '';
$data = json_decode($_POST['data'] ?? '{}', true);

if (!$presentationId || !$data) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// Verify ownership
if (isset($data['teacher_username']) && $data['teacher_username'] !== $username) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Set username
$data['teacher_username'] = $username;
$data['id'] = $presentationId;
$data['updated_at'] = date('Y-m-d H:i:s');

if (!isset($data['created_at'])) {
    $data['created_at'] = $data['updated_at'];
}

// Create directories if needed
$dataDir = __DIR__ . '/../../data';
$slidesDir = $dataDir . '/html_presentations';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

if (!is_dir($slidesDir)) {
    mkdir($slidesDir, 0755, true);
}

// Save each slide content to separate files
$presentationDir = $slidesDir . '/' . $presentationId;
if (!is_dir($presentationDir)) {
    mkdir($presentationDir, 0755, true);
}

foreach ($data['slides'] as $index => &$slide) {
    $slideFilename = 'slide_' . $index . '.html';
    $slideFilePath = $presentationDir . '/' . $slideFilename;
    
    file_put_contents($slideFilePath, $slide['content']);
    
    // Store relative path instead of full content in metadata
    $slide['file_path'] = 'data/html_presentations/' . $presentationId . '/' . $slideFilename;
    unset($slide['content']); // Remove content from metadata
}

// Load existing presentations
$metadataFile = $dataDir . '/html_presentations_metadata.json';
$presentations = [];

if (file_exists($metadataFile)) {
    $presentations = json_decode(file_get_contents($metadataFile), true);
    if (!is_array($presentations)) {
        $presentations = [];
    }
}

// Update or add presentation
$presentations[$presentationId] = $data;

// Save metadata
if (file_put_contents($metadataFile, json_encode($presentations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Presentation saved successfully',
        'presentation_id' => $presentationId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save metadata']);
}
