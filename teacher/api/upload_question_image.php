<?php
session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];

$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ ảnh JPG, PNG, GIF, WebP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Ảnh quá lớn. Tối đa 5MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/questions/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'q_' . uniqid() . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode([
        'success' => true,
        'message' => 'Upload ảnh thành công',
        'url' => '../uploads/questions/' . $filename,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu ảnh.']);
}
?>
