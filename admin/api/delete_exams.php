<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/school_year.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$paths = $input['paths'] ?? [];

if (empty($paths)) {
    echo json_encode(['success' => false, 'message' => 'No paths provided']);
    exit;
}

$deleted = 0;
$errors = [];

// Thùng rác theo năm học — đề bị xóa vẫn còn để truy vết (lưu trữ 5 năm)
$trashBase = __DIR__ . '/../../archives/trash/exams/' . str_replace('-', '_', get_current_school_year()) . '/';

foreach ($paths as $path) {
    // Security check: ensure path is within exams directory
    $realPath = realpath($path);
    $examsBase = realpath(__DIR__ . '/../../teacher/exams/');
    
    if ($realPath && strpos($realPath, $examsBase) === 0) {
        // Additional check: ensure it's a JSON file
        if (pathinfo($realPath, PATHINFO_EXTENSION) === 'json') {
            if (file_exists($realPath)) {
                if (!is_dir($trashBase)) {
                    @mkdir($trashBase, 0755, true);
                }
                // Đổi tên file khi chuyển vào thùng rác để không ghi đè lần xóa trước
                $trashFile = $trashBase . date('Ymd_His') . '_' . basename($realPath);
                if (@rename($realPath, $trashFile)) {
                    $deleted++;
                } else {
                    $errors[] = "Failed to delete: " . basename($realPath);
                }
            } else {
                $errors[] = "File not found: " . basename($realPath);
            }
        } else {
            $errors[] = "Invalid file type: " . basename($path);
        }
    } else {
        $errors[] = "Invalid path: " . basename($path);
    }
}

if ($deleted > 0) {
    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No files were deleted',
        'errors' => $errors
    ]);
}
