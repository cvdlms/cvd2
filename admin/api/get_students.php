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

// Add class name to each student
foreach ($students as &$student) {
    $classInfo = $classLookup[$student['class_id']] ?? null;
    $student['class_name'] = $classInfo ? $classInfo['name'] : 'Unknown';
    $student['class_code'] = $classInfo ? $classInfo['code'] : 'Unknown';
}

$students = array_values($students); // Reset array keys

if ($students === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid students data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $students]);
?>
