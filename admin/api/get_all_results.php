<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Load all results from consolidated file
$consolidatedFile = __DIR__ . '/../../shared/scores/student_score.json';
$allResults = [];

if (file_exists($consolidatedFile)) {
    $data = json_decode(file_get_contents($consolidatedFile), true) ?: [];
    
    foreach ($data as $entry) {
        $allResults[] = [
            'id' => $entry['result_id'] ?? $entry['id'] ?? uniqid(),
            'student_id' => $entry['student_id'] ?? 'N/A',
            'student_name' => $entry['student_name'] ?? 'N/A',
            'exam_id' => $entry['exam_id'] ?? 'N/A',
            'exam_name' => $entry['exam_name'] ?? 'N/A',
            'score' => $entry['score'] ?? null,
            'correct_answers' => $entry['correct_answers'] ?? 0,
            'total_questions' => $entry['total_questions'] ?? 0,
            'submitted_at' => $entry['submitted_at'] ?? null
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
