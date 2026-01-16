<?php
session_name('CVD_STUDENT_SESSION');
session_start();
header('Content-Type: application/json');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentCode = $_SESSION['student_code'];
// Try both student_class and student_class_code
$studentClass = $_SESSION['student_class_code'] ?? $_SESSION['student_class'] ?? '';

$assignmentsFile = __DIR__ . '/../../data/assignments.json';
$submissionsFile = __DIR__ . '/../../data/student_submissions.json';

// Load assignments
$assignments = file_exists($assignmentsFile) ? json_decode(file_get_contents($assignmentsFile), true) : [];
if (!is_array($assignments)) $assignments = [];

// Load submissions
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

// Filter assignments for student's class
$studentAssignments = array_filter($assignments, function($assignment) use ($studentClass) {
    // Case-insensitive comparison and trim whitespace
    $assignmentClass = trim(strtolower($assignment['class_name'] ?? ''));
    $myClass = trim(strtolower($studentClass));
    
    return $assignmentClass === $myClass;
});

// Re-index array after filtering
$studentAssignments = array_values($studentAssignments);

// Add my submission info to each assignment
$updatedAssignments = [];
foreach ($studentAssignments as $assignment) {
    $mySubmission = null;
    foreach ($submissions as $sub) {
        if ($sub['assignment_id'] === $assignment['id'] && $sub['student_code'] === $studentCode) {
            $mySubmission = $sub;
            break;
        }
    }
    $assignment['my_submission'] = $mySubmission;
    $updatedAssignments[] = $assignment;
}
$studentAssignments = $updatedAssignments;

// If requesting specific assignment
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $assignment = null;
    
    foreach ($studentAssignments as $a) {
        if ($a['id'] === $id) {
            $assignment = $a;
            break;
        }
    }
    
    if ($assignment) {
        echo json_encode(['success' => true, 'assignment' => $assignment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    }
} else {
    // Return all assignments
    echo json_encode(['success' => true, 'assignments' => array_values($studentAssignments)]);
}
?>
