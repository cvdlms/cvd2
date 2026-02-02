<?php
/**
 * Download Original PowerPoint File
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$presentationId = $_GET['id'] ?? '';
$username = $_SESSION['username'];

if (empty($presentationId)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing presentation ID');
}

// Load presentation metadata
require_once __DIR__ . '/../../includes/PresentationStorage.php';
$storage = new PresentationStorage();
$presentation = $storage->getById($presentationId);

if (!$presentation) {
    header('HTTP/1.0 404 Not Found');
    exit('Presentation not found');
}

// Security check: Only owner can download
if ($presentation['teacher_username'] !== $username) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Check if this is from PPTX import
if (!isset($presentation['source']) || $presentation['source'] !== 'pptx') {
    header('HTTP/1.0 400 Bad Request');
    exit('This presentation was not imported from PowerPoint');
}

// Get PPTX file path
$pptxPath = __DIR__ . '/../../' . $presentation['pptx_metadata']['pptx_path'];

if (!file_exists($pptxPath)) {
    header('HTTP/1.0 404 Not Found');
    exit('Original PowerPoint file not found');
}

// Get original filename or create one
$originalFilename = $presentation['pptx_metadata']['original_filename'] ?? 
                    ($presentation['title'] . '.pptx');

// Clean filename
$originalFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename);

// Send file
header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
header('Content-Disposition: attachment; filename="' . $originalFilename . '"');
header('Content-Length: ' . filesize($pptxPath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

// Output file
readfile($pptxPath);
exit;
