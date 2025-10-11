<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$classesFile = __DIR__ . '/../classes.json';
$studentsFile = __DIR__ . '/../students.json';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing class ID']);
    exit;
}

$classId = $input['id'];

// Load classes
$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

// Find and remove the class
$classFound = false;
$updatedClasses = [];
foreach ($classes as $class) {
    if ($class['id'] === $classId) {
        $classFound = true;
    } else {
        $updatedClasses[] = $class;
    }
}

if (!$classFound) {
    echo json_encode(['success' => false, 'message' => 'Class not found']);
    exit;
}

// Load students and remove students in this class
$students = [];
if (file_exists($studentsFile)) {
    $students = json_decode(file_get_contents($studentsFile), true) ?: [];
}

$updatedStudents = [];
foreach ($students as $student) {
    if ($student['class_id'] !== $classId) {
        $updatedStudents[] = $student;
    }
}

// Save updated data
$classesSaved = file_put_contents($classesFile, json_encode($updatedClasses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$studentsSaved = file_put_contents($studentsFile, json_encode($updatedStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($classesSaved && $studentsSaved) {
    echo json_encode(['success' => true, 'message' => 'Class and associated students deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete class']);
}
?>
