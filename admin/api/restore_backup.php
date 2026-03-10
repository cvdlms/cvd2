<?php
/**
 * Restore Backup API
 * Khôi phục dữ liệu từ file backup
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
        throw new Exception('File backup không tồn tại');
    }
    
    // Initialize ZIP
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        throw new Exception('Không thể mở file ZIP');
    }
    
    $tempDir = $backupDir . 'temp_restore_' . time() . '/';
    
    // Extract to temp directory
    $zip->extractTo($tempDir);
    $zip->close();
    
    $restoredItems = [];
    
    // === 1. RESTORE HỌC SINH ===
    $studentFiles = ['students.json', 'classes.json', 'student_premium.json'];
    foreach ($studentFiles as $file) {
        $source = $tempDir . 'admin/' . $file;
        $dest = __DIR__ . '/../' . $file;
        if (file_exists($source)) {
            copy($source, $dest);
            $restoredItems[] = 'Học sinh: ' . $file;
        }
    }
    
    // === 2. RESTORE BÀI KIỂM TRA ===
    $examsSource = $tempDir . 'teacher/exams/';
    $examsDest = __DIR__ . '/../../teacher/exams/';
    if (is_dir($examsSource)) {
        // Backup current exams (just in case)
        if (is_dir($examsDest)) {
            deleteDirectory($examsDest);
        }
        copyDirectory($examsSource, $examsDest);
        $restoredItems[] = 'Bài kiểm tra: teacher/exams/';
    }
    
    // === 3. RESTORE NGÂN HÀNG CÂU HỎI ===
    $questionsSource = $tempDir . 'questions/';
    $questionsDest = __DIR__ . '/../../questions/';
    if (is_dir($questionsSource)) {
        if (is_dir($questionsDest)) {
            deleteDirectory($questionsDest);
        }
        copyDirectory($questionsSource, $questionsDest);
        $restoredItems[] = 'Ngân hàng câu hỏi: questions/';
    }
    
    // === 4. RESTORE KẾT QUẢ KIỂM TRA ===
    $scoresSource = $tempDir . 'shared/scores/';
    $scoresDest = __DIR__ . '/../../shared/scores/';
    if (is_dir($scoresSource)) {
        if (is_dir($scoresDest)) {
            deleteDirectory($scoresDest);
        }
        copyDirectory($scoresSource, $scoresDest);
        $restoredItems[] = 'Kết quả kiểm tra: shared/scores/';
    }
    
    // Clean up temp directory
    deleteDirectory($tempDir);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã khôi phục backup thành công',
        'restored_items' => $restoredItems
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}

/**
 * Helper: Copy directory recursively
 */
function copyDirectory($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcPath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            
            if (is_dir($srcPath)) {
                copyDirectory($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }
    closedir($dir);
}

/**
 * Helper: Delete directory recursively
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        // Skip .git folder to avoid permission issues
        if ($file === '.git') continue;
        
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : @unlink($path);
    }
    @rmdir($dir);
}
