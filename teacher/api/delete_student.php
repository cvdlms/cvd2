<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$studentsFile = __DIR__ . '/../students.json';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing student ID']);
    exit;
}

$studentId = $input['id'];

// Load students
$students = [];
if (file_exists($studentsFile)) {
    $students = json_decode(file_get_contents($studentsFile), true) ?: [];
}

// Find and remove the student
$studentFound = false;
$updatedStudents = [];
foreach ($students as $student) {
    if ($student['id'] === $studentId) {
        $studentFound = true;
    } else {
        $updatedStudents[] = $student;
    }
}

if (!$studentFound) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Save updated data
if (file_put_contents($studentsFile, json_encode($updatedStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
}
?>
