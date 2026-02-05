<?php
/**
 * API: Delete HTML Presentation
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);
$presentationId = $input['presentation_id'] ?? '';

if (!$presentationId) {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
    exit;
}

// Load presentations
$metadataFile = __DIR__ . '/../../data/html_presentations_metadata.json';
$presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Check if presentation exists and belongs to user
if (!isset($presentations[$presentationId]) || $presentations[$presentationId]['teacher_username'] !== $username) {
    echo json_encode(['success' => false, 'message' => 'Presentation not found or access denied']);
    exit;
}

// Delete presentation files
$presentationDir = __DIR__ . '/../../data/html_presentations/' . $presentationId;
if (is_dir($presentationDir)) {
    // Delete all files in directory
    $files = glob($presentationDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    // Remove directory
    rmdir($presentationDir);
}

// Remove from metadata
unset($presentations[$presentationId]);

// Save updated metadata
if (file_put_contents($metadataFile, json_encode($presentations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Presentation deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update metadata']);
}
