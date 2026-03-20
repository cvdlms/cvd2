<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentsFile = __DIR__ . '/../students.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'Students file not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['student_id']) || !isset($input['direction'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$studentId = $input['student_id'];
$direction = $input['direction']; // 'up' or 'down'

$students = json_decode(file_get_contents($studentsFile), true);

// Initialize and normalize order_index by class first
$studentsByClass = [];
foreach ($students as $index => &$student) {
    $studentClassId = $student['class_id'];  // Use different variable name
    if (!isset($studentsByClass[$studentClassId])) {
        $studentsByClass[$studentClassId] = [];
    }
    $studentsByClass[$studentClassId][] = ['index' => $index, 'student' => &$student];
}

// Initialize order_index for each class separately
foreach ($studentsByClass as $studentClassId => &$classStudentList) {  // Use different variable name
    usort($classStudentList, function($a, $b) {
        $orderA = isset($a['student']['order_index']) ? $a['student']['order_index'] : 999999;
        $orderB = isset($b['student']['order_index']) ? $b['student']['order_index'] : 999999;
        if ($orderA == $orderB) {
            return $a['index'] - $b['index'];
        }
        return $orderA - $orderB;
    });
    
    foreach ($classStudentList as $pos => &$item) {
        $item['student']['order_index'] = $pos;
    }
}

// Find the student
$currentIndex = null;
$currentStudent = null;
foreach ($students as $index => $student) {
    if ($student['id'] === $studentId) {
        $currentIndex = $index;
        $currentStudent = $student;
        break;
    }
}

if ($currentIndex === null) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Get all students in the same class
$classId = $currentStudent['class_id'];
$classStudents = [];
$classIndices = [];
foreach ($students as $index => $student) {
    if ($student['class_id'] === $classId) {
        $classStudents[] = $student;
        $classIndices[] = $index;
    }
}

// Sort by order_index
usort($classStudents, function($a, $b) {
    $orderA = isset($a['order_index']) ? $a['order_index'] : 999999;
    $orderB = isset($b['order_index']) ? $b['order_index'] : 999999;
    return $orderA - $orderB;
});

// Find position in sorted array
$position = null;
foreach ($classStudents as $i => $student) {
    if ($student['id'] === $studentId) {
        $position = $i;
        break;
    }
}

// Can't move up if already at top, or down if already at bottom
if ($direction === 'up' && $position === 0) {
    echo json_encode(['success' => false, 'message' => 'Học sinh đã ở vị trí đầu tiên trong lớp']);
    exit;
}

if ($direction === 'down' && $position === count($classStudents) - 1) {
    echo json_encode(['success' => false, 'message' => 'Học sinh đã ở vị trí cuối cùng trong lớp']);
    exit;
}

// Swap order_index
if ($direction === 'up') {
    $swapStudent = $classStudents[$position - 1];
} else {
    $swapStudent = $classStudents[$position + 1];
}

// Update order_index in the main array
foreach ($students as &$student) {
    if ($student['id'] === $studentId) {
        $tempOrder = $student['order_index'];
        $student['order_index'] = $swapStudent['order_index'];
        
        // Also update the swap student
        foreach ($students as &$s2) {
            if ($s2['id'] === $swapStudent['id']) {
                $s2['order_index'] = $tempOrder;
                break;
            }
        }
        break;
    }
}

// Save back to file
if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật thứ tự thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu file']);
}
?>
