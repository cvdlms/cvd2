<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

$studentsFile = __DIR__ . '/../students.json';
$classesFile = __DIR__ . '/../classes.json';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['code']) || !isset($input['name']) ||
    !isset($input['gender']) || !isset($input['birth_date']) || !isset($input['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate class exists
$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

$classExists = false;
foreach ($classes as $class) {
    if ($class['id'] === $input['class_id']) {
        $classExists = true;
        break;
    }
}

if (!$classExists) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

$students = [];
if (file_exists($studentsFile)) {
    $students = json_decode(file_get_contents($studentsFile), true) ?: [];
}

$found = false;
foreach ($students as &$student) {
    if ($student['id'] === $input['id']) {
        // Check if new code conflicts with other students
        foreach ($students as $otherStudent) {
            if ($otherStudent['id'] !== $input['id'] && $otherStudent['code'] === $input['code']) {
                echo json_encode(['success' => false, 'message' => 'Student code already exists']);
                exit;
            }
        }

        $student['code'] = trim($input['code']);
        $student['name'] = trim($input['name']);
        $student['gender'] = trim($input['gender']);
        $student['birth_date'] = trim($input['birth_date']);
        $student['class_id'] = trim($input['class_id']);
        $student['email'] = isset($input['email']) ? trim($input['email']) : '';
        $student['notes'] = isset($input['notes']) ? trim($input['notes']) : '';
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update student']);
}
?>
