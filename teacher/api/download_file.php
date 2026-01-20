<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    http_response_code(403);
    die('Unauthorized');
}

$filePath = $_GET['file'] ?? '';
if (empty($filePath)) {
    http_response_code(400);
    die('Missing file parameter');
}

// Security: validate file path
$allowedPath = realpath(__DIR__ . '/../../uploads/assignments/');
$requestedFile = realpath(__DIR__ . '/../../' . $filePath);

// Check if file exists and is within allowed directory
if (!$requestedFile || !file_exists($requestedFile)) {
    http_response_code(404);
    die('File not found');
}

if (strpos($requestedFile, $allowedPath) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Get file info
$fileName = basename($requestedFile);
$fileSize = filesize($requestedFile);
$extension = pathinfo($fileName, PATHINFO_EXTENSION);

// Set appropriate content type
$contentTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$contentType = $contentTypes[strtolower($extension)] ?? 'application/octet-stream';

// Set headers for download
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($requestedFile);
exit;
?>
