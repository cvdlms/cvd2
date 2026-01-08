<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Scan all exam directories
$examsBaseDir = __DIR__ . '/../../teacher/exams/';
$allExams = [];

// Load subjects for mapping
$subjectsFile = __DIR__ . '/../subjects.json';
$subjects = [];
if (file_exists($subjectsFile)) {
    $subjectsArray = json_decode(file_get_contents($subjectsFile), true) ?: [];
    foreach ($subjectsArray as $subject) {
        $subjects[$subject['id']] = $subject['name'];
    }
}

// Scan grade directories (khoi7, khoi9, etc.)
$gradeDirs = glob($examsBaseDir . 'khoi*', GLOB_ONLYDIR);

foreach ($gradeDirs as $gradeDir) {
    $grade = basename($gradeDir);
    
    // Scan subject directories
    $subjectDirs = glob($gradeDir . '/subject_*', GLOB_ONLYDIR);
    
    foreach ($subjectDirs as $subjectDir) {
        // Extract subject_id from directory name
        preg_match('/subject_(\d+)/', basename($subjectDir), $matches);
        $subjectId = isset($matches[1]) ? (int)$matches[1] : 0;
        
        // Scan JSON exam files
        $examFiles = glob($subjectDir . '/*.json');
        
        foreach ($examFiles as $examFile) {
            $examData = json_decode(file_get_contents($examFile), true);
            
            if ($examData) {
                $allExams[] = [
                    'file_path' => $examFile,
                    'test_id' => $examData['test_id'] ?? basename($examFile, '.json'),
                    'test_name' => $examData['test_name'] ?? 'N/A',
                    'subject_id' => $subjectId,
                    'subject_name' => $subjects[$subjectId] ?? 'Unknown',
                    'grade' => $grade,
                    'question_count' => count($examData['questions'] ?? []),
                    'time_limit' => $examData['time_limit'] ?? 0,
                    'created_date' => $examData['created_at'] ?? null,
                    'file_size' => filesize($examFile)
                ];
            }
        }
    }
}

// Sort by created date (newest first)
usort($allExams, function($a, $b) {
    $dateA = $a['created_date'] ?? '1970-01-01';
    $dateB = $b['created_date'] ?? '1970-01-01';
    return strcmp($dateB, $dateA);
});

echo json_encode($allExams);
