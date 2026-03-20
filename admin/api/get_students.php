<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$studentsFile = __DIR__ . '/../students.json';
$classesFile = __DIR__ . '/../classes.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'Students file not found']);
    exit;
}

$students = json_decode(file_get_contents($studentsFile), true);
$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

// Create class lookup
$classLookup = [];
foreach ($classes as $class) {
    $classLookup[$class['id']] = $class;
}

// Filter by class if provided
$classFilter = isset($_GET['class_id']) ? $_GET['class_id'] : null;
if ($classFilter) {
    $students = array_filter($students, function($student) use ($classFilter) {
        return $student['class_id'] === $classFilter;
    });
}

// Initialize and normalize order_index by class
// Group students by class
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
    // Sort by existing order_index if available
    usort($classStudentList, function($a, $b) {
        $orderA = isset($a['student']['order_index']) ? $a['student']['order_index'] : 999999;
        $orderB = isset($b['student']['order_index']) ? $b['student']['order_index'] : 999999;
        if ($orderA == $orderB) {
            return $a['index'] - $b['index']; // Use original index as tiebreaker
        }
        return $orderA - $orderB;
    });
    
    // Assign sequential order_index
    foreach ($classStudentList as $pos => &$item) {
        $item['student']['order_index'] = $pos;
    }
}

// Add class name to each student
foreach ($students as &$student) {
    $classInfo = $classLookup[$student['class_id']] ?? null;
    $student['class_name'] = $classInfo ? $classInfo['name'] : 'Unknown';
    $student['class_code'] = $classInfo ? $classInfo['code'] : 'Unknown';
}

// Sort by class_id first, then by order_index
usort($students, function($a, $b) {
    if ($a['class_id'] === $b['class_id']) {
        $orderA = isset($a['order_index']) ? $a['order_index'] : 999999;
        $orderB = isset($b['order_index']) ? $b['order_index'] : 999999;
        return $orderA - $orderB;
    }
    return strcmp($a['class_id'], $b['class_id']);
});

// Add STT (sequential number) based on order within class
$sttCounter = [];
foreach ($students as &$student) {
    $classId = $student['class_id'];
    if (!isset($sttCounter[$classId])) {
        $sttCounter[$classId] = 1;
    }
    $student['stt'] = $sttCounter[$classId];
    $sttCounter[$classId]++;
}

$students = array_values($students); // Reset array keys

if ($students === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid students data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $students]);
?>
