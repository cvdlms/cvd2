<?php
/**
 * list_banks.php — Liệt kê ngân hàng câu hỏi có sẵn theo khối + học kỳ
 * Trả về các file questions/khoiX/hkY/subject_Z.json (kèm số câu) để chọn khi tạo đề.
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$grade    = preg_replace('/[^0-9]/', '', $_GET['grade'] ?? '');
$semester = ($_GET['semester'] ?? 'hk2') === 'hk1' ? 'hk1' : 'hk2';

if ($grade === '' || (int)$grade < 1 || (int)$grade > 12) {
    echo json_encode(['ok' => false, 'error' => 'Khối lớp không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tên môn: lấy từ admin/subjects.json (id => name)
$subjectNames = [];
$subjectsFile = dirname(__DIR__, 3) . '/admin/subjects.json';
if (is_file($subjectsFile)) {
    $list = json_decode(file_get_contents($subjectsFile), true);
    if (is_array($list)) {
        foreach ($list as $s) {
            if (isset($s['id'])) $subjectNames[(string)$s['id']] = $s['name'] ?? ('Môn #' . $s['id']);
        }
    }
}

$bankDir = dirname(__DIR__, 2) . '/questions/khoi' . $grade . '/' . $semester;
$banks   = [];

if (is_dir($bankDir)) {
    foreach (glob($bankDir . '/subject_*.json') ?: [] as $file) {
        $name = basename($file);
        if (preg_match('/^subject_(\d+)\.json$/', $name, $m)) {
            $subjectId = (int)$m[1];
        } elseif (preg_match('/^subject_(\d+)_history\.json$/', $name, $m)) {
            continue; // bỏ file lịch sử
        } else {
            continue;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) $data = [];

        $count = 0;
        foreach ($data as $topic) {
            if (isset($topic['questions']) && is_array($topic['questions'])) {
                $count += count($topic['questions']);
            }
        }

        if ($count <= 0) continue; // bỏ ngân hàng rỗng

        $label = $subjectNames[(string)$subjectId] ?? ('Môn #' . $subjectId);
        $banks[] = [
            'subject_id' => $subjectId,
            'label'      => $label . ' (subject_' . $subjectId . '.json)',
            'name'       => $label,
            'count'      => $count,
        ];
    }
}

usort($banks, fn($a, $b) => strcmp($a['name'], $b['name']));

echo json_encode(['ok' => true, 'banks' => $banks], JSON_UNESCAPED_UNICODE);
