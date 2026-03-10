<?php
/**
 * Create Backup API
 * Tạo file ZIP chứa tất cả dữ liệu quan trọng
 */

// Disable error output to prevent breaking JSON
error_reporting(0);
ini_set('display_errors', '0');

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
    
    // Create backups folder if not exists
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Generate backup filename
    $timestamp = date('Y-m-d_His');
    $backupFile = $backupDir . 'cvd2_backup_' . $timestamp . '.zip';
    
    // Initialize ZIP
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Không thể tạo file ZIP');
    }
    
    // === 1. BACKUP HỌC SINH ===
    $studentFiles = [
        __DIR__ . '/../students.json',
        __DIR__ . '/../classes.json',
        __DIR__ . '/../student_premium.json'
    ];
    
    foreach ($studentFiles as $file) {
        if (file_exists($file)) {
            $zip->addFile($file, 'admin/' . basename($file));
        }
    }
    
    // === 2. BACKUP BÀI KIỂM TRA ===
    $examsDir = __DIR__ . '/../../teacher/exams/';
    if (is_dir($examsDir)) {
        addDirectoryToZip($zip, $examsDir, 'teacher/exams/');
    }
    
    // === 3. BACKUP NGÂN HÀNG CÂU HỎI ===
    $questionsDir = __DIR__ . '/../../questions/';
    if (is_dir($questionsDir)) {
        addDirectoryToZip($zip, $questionsDir, 'questions/');
    }
    
    // === 4. BACKUP KẾT QUẢ KIỂM TRA ===
    $scoresDir = __DIR__ . '/../../shared/scores/';
    if (is_dir($scoresDir)) {
        addDirectoryToZip($zip, $scoresDir, 'shared/scores/');
    }
    
    // Add backup info file
    $backupInfo = [
        'created_at' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'php_version' => PHP_VERSION,
        'contents' => [
            'students' => 'admin/students.json, classes.json, student_premium.json',
            'exams' => 'teacher/exams/ (khoi6-9)',
            'questions' => 'questions/ folder',
            'results' => 'shared/scores/student_score.json'
        ]
    ];
    $zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Close ZIP
    $zip->close();
    
    // === CLEAN OLD BACKUPS (Keep only 3 most recent) ===
    $backupFiles = glob($backupDir . 'cvd2_backup_*.zip');
    if (count($backupFiles) > 3) {
        // Sort by modification time
        usort($backupFiles, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $toDelete = array_slice($backupFiles, 0, count($backupFiles) - 3);
        foreach ($toDelete as $oldFile) {
            @unlink($oldFile);
        }
    }
    
    // Get file size
    $fileSize = filesize($backupFile);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup đã được tạo thành công',
        'filename' => basename($backupFile),
        'size' => $fileSizeMB . ' MB',
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

/**
 * Helper function: Add directory to ZIP recursively
 */
function addDirectoryToZip($zip, $dir, $zipPath) {
    if (!is_dir($dir)) return;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            
            // Skip .git folder
            if (strpos($filePath, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }
            
            $relativePath = $zipPath . substr($filePath, strlen($dir));
            $zip->addFile($filePath, $relativePath);
        }
    }
}
