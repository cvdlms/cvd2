<?php
/**
 * Delete Backup API
 * Xóa một file backup
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get filename from POST
$filename = $_POST['filename'] ?? '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Filename is required']);
    exit;
}

// Validate filename (security check)
if (!preg_match('/^cvd2_backup_\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename format']);
    exit;
}

try {
    $backupDir = __DIR__ . '/../../backups/';
    $filePath = $backupDir . $filename;
    
    // Check if file exists
    if (!file_exists($filePath)) {
        throw new Exception('File không tồn tại');
    }
    
    // Delete file
    if (!unlink($filePath)) {
        throw new Exception('Không thể xóa file');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa backup: ' . $filename
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
