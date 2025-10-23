<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$usersFile = __DIR__ . '/../user.json';
$subjectsFile = __DIR__ . '/../subjects.json';
$classesFile = __DIR__ . '/../classes.json';
$teacherSubjectsFile = __DIR__ . '/../teacher_subjects.json';
$teacherClassesFile = __DIR__ . '/../teacher_classes.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['fullname']) || !isset($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: username, fullname, password']);
    exit;
}

$users = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
}

$subjects = [];
if (file_exists($subjectsFile)) {
    $subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
}

$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

$teacher_subjects = [];
if (file_exists($teacherSubjectsFile)) {
    $teacher_subjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
}

$teacher_classes = [];
if (file_exists($teacherClassesFile)) {
    $teacher_classes = json_decode(file_get_contents($teacherClassesFile), true) ?: [];
}

// Check if username already exists
if (isset($users[$input['username']])) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}

// Prepare teacher data
$teacherData = [
    'fullname' => trim($input['fullname']),
    'username' => trim($input['username']),
    'password' => password_hash(trim($input['password']), PASSWORD_DEFAULT),
    'email' => isset($input['email']) ? trim($input['email']) : '',
    'dob' => isset($input['dob']) ? trim($input['dob']) : ''
];

$users[$input['username']] = $teacherData;

// Assign subjects if provided
if (isset($input['subject_ids']) && is_array($input['subject_ids'])) {
    $teacher_subjects[$input['username']] = array_map('intval', $input['subject_ids']);
}

// Assign classes if provided
if (isset($input['class_ids']) && is_array($input['class_ids'])) {
    $teacher_classes[$input['username']] = $input['class_ids'];
}

// Save files
$success = true;
$success &= file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$success &= file_put_contents($teacherSubjectsFile, json_encode($teacher_subjects, JSON_PRETTY_PRINT));
$success &= file_put_contents($teacherClassesFile, json_encode($teacher_classes, JSON_PRETTY_PRINT));

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Teacher added successfully', 'data' => $teacherData]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save teacher data']);
}
?>
