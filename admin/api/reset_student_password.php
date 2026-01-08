<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$studentId = $input['student_id'] ?? '';
$newPassword = $input['new_password'] ?? '123456'; // Default password

if (empty($studentId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID học sinh']);
    exit;
}

// Load students data
$studentsFile = __DIR__ . '/../students.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'File dữ liệu không tồn tại']);
    exit;
}

$students = json_decode(file_get_contents($studentsFile), true);

if (!is_array($students)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Find and update student password
$found = false;
foreach ($students as &$student) {
    if ($student['id'] === $studentId) {
        $student['password'] = $newPassword;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy học sinh']);
    exit;
}

// Save updated data
if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Reset mật khẩu thành công',
        'new_password' => $newPassword
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu']);
}
