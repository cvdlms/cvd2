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

    // === GUARD: từ chối backup phiên bản cũ ===
    // Backup tạo trước v1.2 chứa entry tên file bị cắt cụt (bug substr) và thiếu
    // tài khoản/kho câu hỏi. Restore chúng sẽ XÓA dữ liệu hiện tại rồi thay bằng
    // dữ liệu rác — nguy hiểm hơn cả không restore.
    $infoRaw = $zip->getFromName('backup_info.json');
    if ($infoRaw === false) {
        $zip->close();
        throw new Exception('File không phải backup hợp lệ của hệ thống');
    }
    $info = json_decode($infoRaw, true);
    if (!is_array($info) || !isset($info['version']) || version_compare($info['version'], '1.2', '<')) {
        $zip->close();
        throw new Exception('Backup này được tạo từ phiên bản cũ đang có lỗi nén file, không đủ dữ liệu tin cậy để khôi phục. Vui lòng tạo backup mới rồi thử lại.');
    }
    
    $tempDir = $backupDir . 'temp_restore_' . time() . '/';
    
    // Extract to temp directory
    $zip->extractTo($tempDir);
    $zip->close();
    
    $restoredItems = [];
    
    // === 1. RESTORE TÀI KHOẢN & DỮ LIỆU QUẢN TRỊ ===
    $adminFiles = [
        'students.json'         => 'Học sinh',
        'classes.json'          => 'Lớp học',
        'user.json'             => 'Tài khoản đăng nhập',
        'subjects.json'         => 'Danh mục môn học',
        'teacher_classes.json'  => 'Phân công giảng dạy',
        'teacher_subjects.json' => 'Môn học theo giáo viên',
        'system_config.json'    => 'Cấu hình hệ thống'
    ];
    foreach ($adminFiles as $file => $label) {
        $source = $tempDir . 'admin/' . $file;
        $dest = __DIR__ . '/../' . $file;
        if (file_exists($source)) {
            copy($source, $dest);
            $restoredItems[] = $label . ': admin/' . $file;
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

    // === 3b. RESTORE KHO CÂU HỎI GIÁO VIÊN (trước đây bị bỏ sót) ===
    $tfqSource = $tempDir . 'teacher/questions/';
    $tfqDest = __DIR__ . '/../../teacher/questions/';
    if (is_dir($tfqSource)) {
        if (is_dir($tfqDest)) {
            deleteDirectory($tfqDest);
        }
        copyDirectory($tfqSource, $tfqDest);
        $restoredItems[] = 'Kho câu hỏi giáo viên: teacher/questions/';
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

    // === 5. RESTORE KẾT QUẢ LUYỆN TẬP (trước đây bị bỏ sót) ===
    $practiceResultsSource = $tempDir . 'data/practice_results/';
    $practiceResultsDest = __DIR__ . '/../../data/practice_results/';
    if (is_dir($practiceResultsSource)) {
        if (is_dir($practiceResultsDest)) {
            deleteDirectory($practiceResultsDest);
        }
        copyDirectory($practiceResultsSource, $practiceResultsDest);
        $restoredItems[] = 'Kết quả luyện tập: data/practice_results/';
    }

    $practicesSource = $tempDir . 'shared/practices/';
    $practicesDest = __DIR__ . '/../../shared/practices/';
    if (is_dir($practicesSource)) {
        if (is_dir($practicesDest)) {
            deleteDirectory($practicesDest);
        }
        copyDirectory($practicesSource, $practicesDest);
        $restoredItems[] = 'Kết quả luyện tập: shared/practices/';
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
