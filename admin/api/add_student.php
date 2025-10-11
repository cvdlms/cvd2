<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$studentsFile = __DIR__ . '/../students.json';
$classesFile = __DIR__ . '/../classes.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['code']) || !isset($input['name']) || !isset($input['gender']) ||
    !isset($input['birth_date']) || !isset($input['class_id'])) {
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

// Check if student code already exists
foreach ($students as $student) {
    if ($student['code'] === $input['code']) {
        echo json_encode(['success' => false, 'message' => 'Student code already exists']);
        exit;
    }
}

// Generate new ID
$maxId = 0;
foreach ($students as $student) {
    if ($student['id'] > $maxId) {
        $maxId = $student['id'];
    }
}
$newId = $maxId + 1;

$newStudent = [
    'id' => (string)$newId,
    'code' => trim($input['code']),
    'name' => trim($input['name']),
    'gender' => trim($input['gender']),
    'birth_date' => trim($input['birth_date']),
    'class_id' => trim($input['class_id']),
    'email' => isset($input['email']) ? trim($input['email']) : '',
    'notes' => isset($input['notes']) ? trim($input['notes']) : ''
];

$students[] = $newStudent;

if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Student added successfully', 'data' => $newStudent]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save student']);
}
?>
