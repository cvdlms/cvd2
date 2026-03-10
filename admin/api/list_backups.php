<?php
/**
 * List Backups API
 * Liệt kê tất cả các file backup hiện có
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $backupDir = __DIR__ . '/../../backups/';
    
    // Get all backup files
    $backupFiles = glob($backupDir . 'cvd2_backup_*.zip');
    
    // Sort by modification time (newest first)
    usort($backupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $backups = [];
    foreach ($backupFiles as $file) {
        $filename = basename($file);
        $fileSize = filesize($file);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        $fileTime = filemtime($file);
        
        $backups[] = [
            'filename' => $filename,
            'size' => $fileSizeMB,
            'size_formatted' => $fileSizeMB . ' MB',
            'created_at' => date('Y-m-d H:i:s', $fileTime),
            'created_at_formatted' => date('d/m/Y H:i', $fileTime),
            'timestamp' => $fileTime
        ];
    }
    
    echo json_encode([
        'success' => true,
        'backups' => $backups,
        'count' => count($backups)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
