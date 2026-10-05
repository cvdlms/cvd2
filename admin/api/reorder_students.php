<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireAdminSession();

/**
 * API lưu thứ tự học sinh của một lớp.
 * Nhận danh sách id theo đúng thứ tự mong muốn, gán lại order_index = 0..n-1.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentsFile = __DIR__ . '/../students.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'Students file not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['class_id']) || !isset($input['ordered_ids']) || !is_array($input['ordered_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$classId = (string)$input['class_id'];
$orderedIds = array_values(array_unique(array_map('strval', $input['ordered_ids'])));

if ($orderedIds === []) {
    echo json_encode(['success' => false, 'message' => 'Danh sách học sinh rỗng']);
    exit;
}

$students = json_decode(file_get_contents($studentsFile), true);

if (!$students) {
    echo json_encode(['success' => false, 'message' => 'Invalid students data']);
    exit;
}

// Gom id học sinh của lớp theo thứ tự order_index hiện tại
$classStudents = [];
foreach ($students as $index => $student) {
    if ((string)$student['class_id'] === $classId) {
        $classStudents[] = [
            'id' => (string)$student['id'],
            'index' => $index,
            'order' => isset($student['order_index']) ? (int)$student['order_index'] : PHP_INT_MAX
        ];
    }
}

if ($classStudents === []) {
    echo json_encode(['success' => false, 'message' => 'Lớp không có học sinh nào']);
    exit;
}

usort($classStudents, function($a, $b) {
    return $a['order'] <=> $b['order'];
});

$existingIds = array_column($classStudents, 'id');
$positionById = array_flip($existingIds);

// Id gửi lên không thuộc lớp -> bỏ qua, tránh ghi sai order_index
$validIds = array_values(array_filter($orderedIds, function($id) use ($positionById) {
    return isset($positionById[$id]);
}));

// Bổ sung các học sinh còn lại của lớp vào cuối để không bị mất dữ liệu
$missingIds = array_values(array_diff($existingIds, $validIds));
$finalIds = array_values(array_unique(array_merge($validIds, $missingIds)));

// Một id có thể tồn tại nhiều bản ghi trong dữ liệu.
// Mở rộng lại danh sách theo số bản ghi thực tế để order_index luôn liên tục 0..n-1.
$countById = array_count_values($existingIds);
$expandedIds = [];
foreach ($finalIds as $id) {
    for ($i = 0; $i < $countById[$id]; $i++) {
        $expandedIds[] = $id;
    }
}

$indicesById = [];
foreach ($classStudents as $item) {
    $indicesById[$item['id']][] = $item['index'];
}

foreach ($expandedIds as $position => $id) {
    $index = array_shift($indicesById[$id]);
    $students[$index]['order_index'] = $position;
}

if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật thứ tự học sinh',
        'total' => count($expandedIds)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu file']);
}
