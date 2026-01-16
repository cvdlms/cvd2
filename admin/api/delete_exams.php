<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

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

foreach ($paths as $path) {
    // Security check: ensure path is within exams directory
    $realPath = realpath($path);
    $examsBase = realpath(__DIR__ . '/../../teacher/exams/');
    
    if ($realPath && strpos($realPath, $examsBase) === 0) {
        // Additional check: ensure it's a JSON file
        if (pathinfo($realPath, PATHINFO_EXTENSION) === 'json') {
            if (file_exists($realPath)) {
                if (unlink($realPath)) {
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
