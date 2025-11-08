<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['file'])) {
    die('Invalid request');
}

$fileName = basename($_GET['file']);
$tempDir = sys_get_temp_dir() . '/cvd_excel_processing/';
$filePath = $tempDir . $fileName;

// Security check - ensure file exists and is in temp directory
if (!file_exists($filePath) || strpos($filePath, $tempDir) !== 0) {
    die('File not found');
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0');

// Output file
readfile($filePath);

// Clean up temp file after download
unlink($filePath);

// Clean up old temp files (older than 1 hour)
$files = glob($tempDir . '*');
foreach ($files as $file) {
    if (filemtime($file) < time() - 3600) {
        unlink($file);
    }
}
?>
