<?php
session_name('CVD_TEACHER_SESSION');
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg): void { echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Phương thức không hợp lệ.');
$file = $_FILES['subject_json'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) fail('Lỗi upload file.');
if ($file['size'] > MAX_JSON_MB * 1024 * 1024) fail('File quá lớn (tối đa ' . MAX_JSON_MB . 'MB).');
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') fail('Chỉ hỗ trợ file .json');

$content = file_get_contents($file['tmp_name']);
if ($content === false) fail('Không đọc được file.');
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) fail('File JSON không hợp lệ: ' . json_last_error_msg());
if (empty($data['các_khối'])) fail('File thiếu trường "các_khối".');

$rawName  = $data['tên_tài_liệu'] ?? $file['name'];
$subjName = preg_replace('/Bảng mô tả mức độ đánh giá môn |Bảng mô tả |đặc tả /ui', '', $rawName);
$subjName = preg_replace('/THCS/ui', '', $subjName);
$subjName = trim($subjName) ?: pathinfo($file['name'], PATHINFO_FILENAME);
$slug     = preg_replace('/[^a-zA-Z0-9_\-]/u', '_', $subjName);
$savePath = UPLOAD_DIR . $slug . '.json';

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (file_put_contents($savePath, $content) === false) fail('Không thể lưu file. Kiểm tra quyền ghi.');

$grades = array_map('strval', array_keys($data['các_khối']));
sort($grades);
echo json_encode(['ok' => true, 'subjName' => $subjName, 'slug' => $slug, 'grades' => $grades, 'msg' => "Đã thêm môn: {$subjName} (Khối: " . implode(', ', $grades) . ')'], JSON_UNESCAPED_UNICODE);
