<?php
session_name('CVD_STUDENT_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentCode = $_SESSION['student_code'];

$submissionsFile = __DIR__ . '/../../data/student_submissions.json';
$assignmentsFile = __DIR__ . '/../../data/assignments.json';

// Load submissions
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

// Load assignments
$assignments = file_exists($assignmentsFile) ? json_decode(file_get_contents($assignmentsFile), true) : [];
if (!is_array($assignments)) $assignments = [];

// Create assignment lookup
$assignmentMap = [];
foreach ($assignments as $assignment) {
    $assignmentMap[$assignment['id']] = $assignment;
}

// Filter submissions for this student
$mySubmissions = array_filter($submissions, function($sub) use ($studentCode) {
    return $sub['student_code'] === $studentCode;
});

// Enrich submissions with assignment info
foreach ($mySubmissions as &$submission) {
    $assignmentId = $submission['assignment_id'];
    if (isset($assignmentMap[$assignmentId])) {
        $submission['title'] = $assignmentMap[$assignmentId]['title'];
        $submission['subject_id'] = $assignmentMap[$assignmentId]['subject_id'];
        $submission['description'] = $assignmentMap[$assignmentId]['description'];
        $submission['max_score'] = $assignmentMap[$assignmentId]['max_score'];
    }
}

if (isset($_GET['id'])) {
    // Get specific submission
    $id = $_GET['id'];
    $submission = null;
    foreach ($mySubmissions as $sub) {
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
} else {
    // Return all submissions
    echo json_encode(['success' => true, 'submissions' => array_values($mySubmissions)]);
}
?>
