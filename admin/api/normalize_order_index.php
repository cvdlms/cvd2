<?php
/**
 * API to normalize all order_index values
 * This will fix any duplicate or missing order_index issues
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentsFile = __DIR__ . '/../students.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'Students file not found']);
    exit;
}

$students = json_decode(file_get_contents($studentsFile), true);

if (!$students) {
    echo json_encode(['success' => false, 'message' => 'Invalid students data']);
    exit;
}

// Group students by class
$studentsByClass = [];
foreach ($students as $index => &$student) {
    $studentClassId = $student['class_id'];  // Use different variable name
    if (!isset($studentsByClass[$studentClassId])) {
        $studentsByClass[$studentClassId] = [];
    }
    $studentsByClass[$studentClassId][] = ['index' => $index, 'student' => &$student];
}

$normalizedCount = 0;

// Normalize order_index for each class
foreach ($studentsByClass as $studentClassId => &$classStudentList) {  // Use different variable name
    // Sort by existing order_index (if available), then by original index
    usort($classStudentList, function($a, $b) {
        $orderA = isset($a['student']['order_index']) ? $a['student']['order_index'] : 999999;
        $orderB = isset($b['student']['order_index']) ? $b['student']['order_index'] : 999999;
        if ($orderA == $orderB) {
            return $a['index'] - $b['index'];
        }
        return $orderA - $orderB;
    });
    
    // Assign sequential order_index starting from 0
    foreach ($classStudentList as $pos => &$item) {
        $oldIndex = isset($item['student']['order_index']) ? $item['student']['order_index'] : 'none';
        $item['student']['order_index'] = $pos;
        
        if ($oldIndex !== $pos) {
            $normalizedCount++;
        }
    }
}

// Save normalized data back to file
if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true, 
        'message' => 'Đã chuẩn hóa order_index thành công',
        'normalized_count' => $normalizedCount,
        'total_students' => count($students),
        'total_classes' => count($studentsByClass)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu file']);
}
?>
