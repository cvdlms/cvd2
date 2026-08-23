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
    
    // === 1. BACKUP TÀI KHOẢN & DỮ LIỆU QUẢN TRỊ ===
    // Bao gồm tài khoản đăng nhập (user.json) để sau khi restore hệ thống
    // vẫn đăng nhập được — bắt buộc cho khôi phục thảm họa.
    $adminFiles = [
        'students.json',
        'classes.json',
        'user.json',
        'subjects.json',
        'teacher_classes.json',
        'teacher_subjects.json',
        'system_config.json'
    ];

    foreach ($adminFiles as $file) {
        $file = __DIR__ . '/../' . $file;
        if (file_exists($file)) {
            if (!$zip->addFile($file, 'admin/' . basename($file))) {
                throw new Exception('Không thể nén file: admin/' . basename($file));
            }
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

    // === 3b. BACKUP KHO CÂU HỎI GIÁO VIÊN (theo khối/học kì) ===
    $teacherQuestionsDir = __DIR__ . '/../../teacher/questions/';
    if (is_dir($teacherQuestionsDir)) {
        addDirectoryToZip($zip, $teacherQuestionsDir, 'teacher/questions/');
    }

    // === 4. BACKUP KẾT QUẢ KIỂM TRA ===
    $scoresDir = __DIR__ . '/../../shared/scores/';
    if (is_dir($scoresDir)) {
        addDirectoryToZip($zip, $scoresDir, 'shared/scores/');
    }

    // === 5. BACKUP KẾT QUẢ LUYỆN TẬP ===
    $practiceResultsDir = __DIR__ . '/../../data/practice_results/';
    if (is_dir($practiceResultsDir)) {
        addDirectoryToZip($zip, $practiceResultsDir, 'data/practice_results/');
    }
    $practicesDir = __DIR__ . '/../../shared/practices/';
    if (is_dir($practicesDir)) {
        addDirectoryToZip($zip, $practicesDir, 'shared/practices/');
    }

    // Add backup info file
    $backupInfo = [
        'created_at' => date('Y-m-d H:i:s'),
        'version' => '1.2',
        'php_version' => PHP_VERSION,
        'contents' => [
            'accounts' => 'admin/user.json, students.json, classes.json, subjects.json, teacher_classes.json, teacher_subjects.json, system_config.json',
            'exams' => 'teacher/exams/ (khoi6-9)',
            'questions' => 'questions/ folder + teacher/questions/ (kho câu hỏi theo khối/học kì)',
            'results' => 'shared/scores/ (file cá nhân + student_score.json)',
            'practice' => 'data/practice_results/ + shared/practices/'
        ]
    ];
    $zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Close ZIP — addFile chỉ xếp hàng, dữ liệu thực sự được ghi lúc close.
    // Nếu close thất bại (đĩa đầy / file bị khóa), ZIP sẽ hỏng ngầm như lỗi cũ.
    if ($zip->close() !== TRUE) {
        @unlink($backupFile);
        throw new Exception('Không thể ghi file ZIP (đĩa đầy hoặc file bị khóa?)');
    }
    
    // === CLEAN OLD BACKUPS ===
    // Yêu cầu lưu trữ 5 năm: giữ số lượng backup lớn hơn (mặc định 30) thay vì 3.
    // Backup định kỳ là lớp bảo vệ vận hành; lưu trữ dài hạn theo năm học nằm ở
    // thư mục archives/ (xem shared/scripts/archive_school_year.php).
    if (!defined('CVD_BACKUP_RETENTION')) {
        define('CVD_BACKUP_RETENTION', 30);
    }
    $backupFiles = glob($backupDir . 'cvd2_backup_*.zip');
    if (count($backupFiles) > CVD_BACKUP_RETENTION) {
        // Sort by modification time
        usort($backupFiles, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $toDelete = array_slice($backupFiles, 0, count($backupFiles) - CVD_BACKUP_RETENTION);
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
        if ($file->isDir()) {
            continue;
        }

        // getSubPathName() trả về đường dẫn TƯƠNG ĐỐI so với $dir —
        // tránh tuyệt đối lỗi substr lệch offset khi $dir chứa '/../..' chưa resolve.
        $subPath = str_replace('\\', '/', $files->getSubPathName());

        // Skip .git folder
        if (strpos('/' . $subPath, '/.git/') !== false) {
            continue;
        }

        $filePath = $file->getPathname();
        if (!$zip->addFile($filePath, $zipPath . $subPath)) {
            throw new Exception('Không thể nén file: ' . $zipPath . $subPath);
        }
    }
}
