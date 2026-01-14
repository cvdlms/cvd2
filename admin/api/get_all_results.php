<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Load students data to get student names
$studentsFile = __DIR__ . '/../students.json';
$students = [];
if (file_exists($studentsFile)) {
    $studentData = json_decode(file_get_contents($studentsFile), true) ?: [];
    foreach ($studentData as $student) {
        $students[$student['code']] = $student;
    }
}

// Load all results from consolidated file
$consolidatedFile = __DIR__ . '/../../shared/scores/student_score.json';
$allResults = [];

if (file_exists($consolidatedFile)) {
    $data = json_decode(file_get_contents($consolidatedFile), true) ?: [];
    
    foreach ($data as $entry) {
        $studentId = $entry['student_id'] ?? 'N/A';
        $studentInfo = $students[$studentId] ?? null;
        
        // Try to get exam grade from exam file
        $grade = 'N/A';
        if (isset($entry['exam_id']) && isset($entry['subject_id'])) {
            // Try different possible locations with khoi prefix
            $possiblePaths = [];
            
            // Check in teacher/exams with khoi structure
            for ($g = 6; $g <= 12; $g++) {
                $possiblePaths[] = __DIR__ . "/../../teacher/exams/khoi{$g}/subject_{$entry['subject_id']}/{$entry['exam_id']}.json";
            }
            
            foreach ($possiblePaths as $examFile) {
                if (file_exists($examFile)) {
                    $examData = json_decode(file_get_contents($examFile), true);
                    if ($examData) {
                        // Extract grade from file path
                        if (preg_match('/khoi(\d+)\//', $examFile, $matches)) {
                            $grade = $matches[1];
                        }
                        break;
                    }
                }
            }
        }
        
        $allResults[] = [
            'id' => $entry['result_id'] ?? $entry['id'] ?? uniqid(),
            'student_id' => $studentId,
            'student_name' => $studentInfo['name'] ?? $entry['student_name'] ?? 'N/A',
            'exam_id' => $entry['exam_id'] ?? 'N/A',
            'exam_name' => $entry['test_name'] ?? $entry['exam_name'] ?? 'N/A',
            'subject_id' => $entry['subject_id'] ?? null,
            'grade' => $grade,
            'score' => $entry['score'] ?? null,
            'correct_answers' => $entry['correct_answers'] ?? 0,
            'total_questions' => $entry['total_questions'] ?? 0,
            'submitted_at' => $entry['timestamp'] ?? $entry['submitted_at'] ?? null,
            'attempts' => $entry['attempts'] ?? 1
        ];
    }
}

// Sort by submission date (newest first)
usort($allResults, function($a, $b) {
    $dateA = $a['submitted_at'] ?? '1970-01-01';
    $dateB = $b['submitted_at'] ?? '1970-01-01';
    return strcmp($dateB, $dateA);
});

echo json_encode($allResults);

