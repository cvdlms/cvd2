<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$submissionsFile = __DIR__ . '/../../data/student_submissions.json';
$studentsFile = __DIR__ . '/../../admin/students.json';

// Load submissions
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

// Load students for names
$students = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
if (!is_array($students)) $students = [];

// Load classes for class names
$classesFile = __DIR__ . '/../../admin/classes.json';
$classes = file_exists($classesFile) ? json_decode(file_get_contents($classesFile), true) : [];
if (!is_array($classes)) $classes = [];

// Create class lookup map
$classMap = [];
foreach ($classes as $class) {
    $classMap[$class['id']] = $class['code'];
}

// Create student lookup map (using 'code' field, not 'student_code')
$studentMap = [];
foreach ($students as $student) {
    $studentMap[$student['code']] = $student;
}

// Enrich submissions with student info
$enrichedSubmissions = [];
foreach ($submissions as $submission) {
    $studentCode = $submission['student_code'];
    if (isset($studentMap[$studentCode])) {
        $submission['student_name'] = $studentMap[$studentCode]['name'];
        $classId = $studentMap[$studentCode]['class_id'] ?? null;
        $submission['student_class'] = isset($classMap[$classId]) ? $classMap[$classId] : 'Unknown';
    } else {
        $submission['student_name'] = 'Unknown';
        $submission['student_class'] = 'Unknown';
    }
    $enrichedSubmissions[] = $submission;
}
$submissions = $enrichedSubmissions;

if (isset($_GET['id'])) {
    // Get specific submission
    $id = $_GET['id'];
    $submission = null;
    foreach ($submissions as $sub) {
        if ($sub['id'] === $id) {
            $submission = $sub;
            break;
        }
    }
    
    if ($submission) {
        echo json_encode(['success' => true, 'submission' => $submission]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
    }
} elseif (isset($_GET['assignment_id'])) {
    // Get all submissions for an assignment
    $assignmentId = $_GET['assignment_id'];
    $filtered = array_filter($submissions, function($sub) use ($assignmentId) {
        return $sub['assignment_id'] === $assignmentId;
    });
    
    echo json_encode(['success' => true, 'submissions' => array_values($filtered)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameter']);
}
?>
