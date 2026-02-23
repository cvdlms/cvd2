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

// Sanitize student code
$student_code = trim($student_code);

// Auto-detect base path (works for any project folder name)
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('#^(/[^/]+)/teacher/#', $requestUri, $matches)) {
    $basePath = $matches[1];
} else {
    // Fallback: try to detect from SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (preg_match('#^(/[^/]+)/#', $scriptName, $matches)) {
        $basePath = $matches[1];
    } else {
        $basePath = '';
    }
}

// Path to the student's score file using dynamic base path
// Check multiple possible paths for better hosting compatibility
$possiblePaths = [
    $_SERVER['DOCUMENT_ROOT'] . $basePath . '/shared/scores/' . $student_code . '.json',
    __DIR__ . '/../../shared/scores/' . $student_code . '.json',
    dirname(dirname(__DIR__)) . '/shared/scores/' . $student_code . '.json'
];

$foundPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $foundPath = $path;
        break;
    }
}

if (!$foundPath) {
    echo json_encode(['success' => false, 'message' => 'Student score file not found']);
    exit;
}

$scoreFile = $foundPath;

// Read and decode the JSON file
$exams = json_decode(file_get_contents($scoreFile), true);

if ($exams === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Load student_score.json to get subject_id for each exam
$studentScorePaths = [
    $_SERVER['DOCUMENT_ROOT'] . $basePath . '/shared/scores/student_score.json',
    __DIR__ . '/../../shared/scores/student_score.json',
    dirname(dirname(__DIR__)) . '/shared/scores/student_score.json'
];

$studentScoreFile = null;
foreach ($studentScorePaths as $path) {
    if (file_exists($path)) {
        $studentScoreFile = $path;
        break;
    }
}

$studentScores = [];
if ($studentScoreFile) {
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
