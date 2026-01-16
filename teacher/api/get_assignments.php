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

$username = $_SESSION['username'];
$assignmentsFile = __DIR__ . '/../../data/assignments.json';
$submissionsFile = __DIR__ . '/../../data/student_submissions.json';

// Load assignments
$assignments = file_exists($assignmentsFile) ? json_decode(file_get_contents($assignmentsFile), true) : [];
if (!is_array($assignments)) $assignments = [];

// Load submissions to count
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

// Filter assignments by teacher
$teacherAssignments = array_filter($assignments, function($assignment) use ($username) {
    return $assignment['teacher_username'] === $username;
});

// Re-index array after filtering
$teacherAssignments = array_values($teacherAssignments);

// Add submission count to each assignment
$updatedAssignments = [];
foreach ($teacherAssignments as $assignment) {
    $assignment['submission_count'] = count(array_filter($submissions, function($sub) use ($assignment) {
        return $sub['assignment_id'] === $assignment['id'];
    }));
    $updatedAssignments[] = $assignment;
}
$teacherAssignments = $updatedAssignments;

// If requesting specific assignment
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $assignment = null;
    foreach ($teacherAssignments as $a) {
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
    echo json_encode(['success' => true, 'assignments' => array_values($teacherAssignments)]);
}
?>
