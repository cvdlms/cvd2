<?php
/**
 * Download Backup API
 * Tải file backup về máy
 */

session_name('CVD_TEACHER_SESSION');
session_start();

// Check admin authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

// Get filename from GET
$filename = $_GET['filename'] ?? '';

if (empty($filename)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Filename is required');
}

// Validate filename (security check)
if (!preg_match('/^cvd2_backup_\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $filename)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid filename format');
}

$backupDir = __DIR__ . '/../../backups/';
$filePath = $backupDir . $filename;

// Check if file exists
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

// Send file for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');

readfile($filePath);
exit;
