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

if (!isset($input['student_id']) || !isset($input['new_stt']) || !isset($input['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$studentId = $input['student_id'];
$newSTT = intval($input['new_stt']);
$classId = $input['class_id'];

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

// Find the student to move
$currentStudent = null;
$currentIndex = null;
foreach ($students as $index => $student) {
    if ($student['id'] === $studentId) {
        $currentStudent = $student;
        $currentIndex = $index;
        break;
    }
}

if (!$currentStudent) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Verify student belongs to the class
if ($currentStudent['class_id'] !== $classId) {
    echo json_encode(['success' => false, 'message' => 'Student does not belong to this class']);
    exit;
}

// Get all students in the same class
$classStudents = [];
foreach ($students as $index => $student) {
    if ($student['class_id'] === $classId) {
        $classStudents[] = [
            'data' => $student,
            'original_index' => $index
        ];
    }
}

// Sort by current order_index
usort($classStudents, function($a, $b) {
    $orderA = isset($a['data']['order_index']) ? $a['data']['order_index'] : 999999;
    $orderB = isset($b['data']['order_index']) ? $b['data']['order_index'] : 999999;
    return $orderA - $orderB;
});

// Find current position in sorted array
$currentPosition = null;
foreach ($classStudents as $pos => $item) {
    if ($item['data']['id'] === $studentId) {
        $currentPosition = $pos;
        break;
    }
}

if ($currentPosition === null) {
    echo json_encode(['success' => false, 'message' => 'Could not find student position']);
    exit;
}

// Validate new STT
if ($newSTT < 1 || $newSTT > count($classStudents)) {
    echo json_encode(['success' => false, 'message' => 'Invalid STT value']);
    exit;
}

// Remove student from current position
$studentToMove = array_splice($classStudents, $currentPosition, 1)[0];

// Insert at new position (STT is 1-based, array is 0-based)
array_splice($classStudents, $newSTT - 1, 0, [$studentToMove]);

// Update order_index for all students in this class
foreach ($classStudents as $pos => $item) {
    $students[$item['original_index']]['order_index'] = $pos;
}

// Save back to file
if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật STT thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu file']);
}
?>
