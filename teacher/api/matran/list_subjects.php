<?php
session_name('CVD_TEACHER_SESSION');
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$subjects = [];

if (is_dir(UPLOAD_DIR)) {
    foreach (glob(UPLOAD_DIR . '*.json') ?: [] as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data || empty($data['các_khối'])) continue;
        $slug   = pathinfo($file, PATHINFO_FILENAME);
        $name   = $data['tên_tài_liệu'] ?? $slug;
        $name   = preg_replace('/Bảng mô tả mức độ đánh giá môn |Bảng mô tả |đặc tả /ui', '', $name);
        $name   = trim(preg_replace('/THCS/ui', '', $name)) ?: $slug;
        $grades = array_map('strval', array_keys($data['các_khối']));
        sort($grades);
        $subjects[] = ['name' => $name, 'slug' => $slug, 'grades' => $grades];
    }
}

echo json_encode(['ok' => true, 'subjects' => $subjects], JSON_UNESCAPED_UNICODE);
