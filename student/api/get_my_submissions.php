<?php
// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_name('CVD_STUDENT_SESSION');
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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
$mySubmissions = [];
foreach ($submissions as $sub) {
    if ($sub['student_code'] === $studentCode) {
        $mySubmissions[] = $sub;
    }
}

// Enrich submissions with assignment info
foreach ($mySubmissions as $key => $submission) {
    $assignmentId = $submission['assignment_id'];
    if (isset($assignmentMap[$assignmentId])) {
        $mySubmissions[$key]['title'] = $assignmentMap[$assignmentId]['title'];
        $mySubmissions[$key]['subject_id'] = $assignmentMap[$assignmentId]['subject_id'];
        $mySubmissions[$key]['description'] = $assignmentMap[$assignmentId]['description'];
        $mySubmissions[$key]['max_score'] = $assignmentMap[$assignmentId]['max_score'];
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
        echo json_encode(['success' => true, 'submission' => $submission], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission not found', 'debug' => ['requested_id' => $id, 'student_code' => $studentCode, 'found_submissions' => count($mySubmissions)]], JSON_UNESCAPED_UNICODE);
    }
} else {
    // Return all submissions
    echo json_encode(['success' => true, 'submissions' => $mySubmissions], JSON_UNESCAPED_UNICODE);
}
?>
