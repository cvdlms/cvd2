<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get student_code from GET or POST
$student_code = $_GET['student_code'] ?? $_POST['student_code'] ?? '';

if (empty($student_code)) {
    echo json_encode(['success' => false, 'message' => 'Student code is required']);
    exit;
}

// Path to the student's score file
$scoreFile = __DIR__ . '/../../shared/scores/' . $student_code . '.json';

if (!file_exists($scoreFile)) {
    echo json_encode(['success' => false, 'message' => 'Student score file not found']);
    exit;
}

// Read and decode the JSON file
$exams = json_decode(file_get_contents($scoreFile), true);

if ($exams === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Load student_score.json to get subject_id for each exam
$studentScoreFile = __DIR__ . '/../../shared/scores/student_score.json';
$studentScores = [];
if (file_exists($studentScoreFile)) {
    $studentScores = json_decode(file_get_contents($studentScoreFile), true) ?? [];
}

// Create a map of exam_id to subject_id
$examSubjectMap = [];
foreach ($studentScores as $score) {
    if ($score['student_id'] === $student_code) {
        $examSubjectMap[$score['exam_id']] = $score['subject_id'];
    }
}

// Add subject_id to each exam
foreach ($exams as &$exam) {
    // Use source_exam_id instead of id to match with exam_id in student_score.json
    $exam['subject_id'] = $examSubjectMap[$exam['source_exam_id']] ?? null;
}

// Sort exams by timestamp descending (newest first)
usort($exams, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Return the sorted data
echo json_encode(['success' => true, 'data' => $exams]);
?>
