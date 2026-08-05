<?php
session_name('CVD_TEACHER_SESSION');
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$slug  = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['slug']  ?? '');
$grade = preg_replace('/[^0-9]/',           '', $_GET['grade'] ?? '');
if (!$slug || !$grade) { echo json_encode(['ok' => false, 'error' => 'Thiếu tham số']); exit; }
$path = UPLOAD_DIR . $slug . '.json';
if (!file_exists($path)) { echo json_encode(['ok' => false, 'error' => "Không tìm thấy file: {$slug}.json"]); exit; }
$data  = json_decode(file_get_contents($path), true);
$units = $data['các_khối'][$grade] ?? [];
echo json_encode(['ok' => true, 'units' => $units, 'grade' => $grade], JSON_UNESCAPED_UNICODE);
