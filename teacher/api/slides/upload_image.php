<?php
session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB.']);
    exit;
}

// Create upload directory if not exists
$uploadDir = __DIR__ . '/../../../uploads/slides/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'slide_' . uniqid() . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Return relative URL
    $imageUrl = '../uploads/slides/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'url' => $imageUrl,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
}
?>
