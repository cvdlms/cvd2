<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

$studentCode = $_GET['student_code'] ?? '';
if ($studentCode !== $_SESSION['student_code']) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

// Load student scores
$scoreFile = __DIR__ . '/../shared/scores/student_score.json';
if (!file_exists($scoreFile)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$allScores = json_decode(file_get_contents($scoreFile), true) ?: [];

// Filter scores for this student
$studentResults = [];
foreach ($allScores as $score) {
    // Check both student_code and student_id for compatibility
    $scoreStudentId = $score['student_code'] ?? $score['student_id'] ?? '';
    
    if ($scoreStudentId === $studentCode) {
        // Get exam info to enrich the data
        $examId = $score['exam_id'] ?? '';
        $subjectId = $score['subject_id'] ?? null;  // Already in score data
        $grade = null;
        
        // Try to extract grade from exam file if not in score
        if ($examId && !$subjectId) {
            // Search in khoi* directories
            $examDirs = glob(__DIR__ . '/../teacher/exams/khoi*');
            foreach ($examDirs as $dir) {
                $gradeFolder = basename($dir);
                $examFiles = glob($dir . '/subject_*/exam_*.json');
                foreach ($examFiles as $examFile) {
                    $examData = json_decode(file_get_contents($examFile), true);
                    if ($examData && ($examData['exam_id'] ?? '') === $examId) {
                        $subjectId = $examData['subject_id'] ?? null;
                        $grade = $gradeFolder;
                        break 2;
                    }
                }
            }
        }
        
        $studentResults[] = [
            'exam_id' => $score['exam_id'] ?? '',
            'score' => floatval($score['score'] ?? 0),
            'timestamp' => strtotime($score['timestamp'] ?? 'now'),
            'subject_id' => $subjectId,
            'grade' => $grade,
            'exam_type' => $score['exam_type'] ?? 'unknown',
            'test_name' => $score['test_name'] ?? '',
            'attempts' => $score['attempts'] ?? 1
        ];
    }
}

// Sort by timestamp (newest first)
usort($studentResults, function($a, $b) {
    return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
});

echo json_encode([
    'success' => true,
    'results' => $studentResults
]);
?>