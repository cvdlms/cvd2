<?php
session_name('CVD_TEACHER_SESSION');
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['slug'] ?? '');
if (!$slug) { echo json_encode(['ok' => false, 'error' => 'Thiếu tham số slug']); exit; }
$path = UPLOAD_DIR . $slug . '.json';
if (!file_exists($path)) { echo json_encode(['ok' => false, 'error' => 'File không tồn tại']); exit; }
if (!unlink($path)) { echo json_encode(['ok' => false, 'error' => 'Không thể xóa file.']); exit; }
echo json_encode(['ok' => true, 'msg' => 'Đã xóa môn thành công.'], JSON_UNESCAPED_UNICODE);
