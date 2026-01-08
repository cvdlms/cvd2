<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$results = $input['results'] ?? [];

if (empty($results)) {
    echo json_encode(['success' => false, 'message' => 'No results provided']);
    exit;
}

$deleted = 0;
$errors = [];

// Load consolidated score file
$consolidatedFile = __DIR__ . '/../../shared/scores/student_score.json';
$consolidatedData = [];
if (file_exists($consolidatedFile)) {
    $consolidatedData = json_decode(file_get_contents($consolidatedFile), true) ?: [];
}

// Create a set of result IDs to delete
$idsToDelete = [];
$studentIdsAffected = [];
foreach ($results as $result) {
    $idsToDelete[] = $result['id'];
    if (!in_array($result['student_id'], $studentIdsAffected)) {
        $studentIdsAffected[] = $result['student_id'];
    }
}

// Remove from consolidated file
$originalCount = count($consolidatedData);
$consolidatedData = array_filter($consolidatedData, function($entry) use ($idsToDelete) {
    $entryId = $entry['result_id'] ?? $entry['id'] ?? '';
    return !in_array($entryId, $idsToDelete);
});
$consolidatedData = array_values($consolidatedData); // Re-index array

// Save consolidated file
if (file_put_contents($consolidatedFile, json_encode($consolidatedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    $deleted = $originalCount - count($consolidatedData);
} else {
    $errors[] = "Failed to update consolidated score file";
}

// Remove from individual student files
foreach ($studentIdsAffected as $studentId) {
    $studentFile = __DIR__ . '/../../shared/scores/' . $studentId . '.json';
    
    if (file_exists($studentFile)) {
        $studentData = json_decode(file_get_contents($studentFile), true) ?: [];
        $originalStudentCount = count($studentData);
        
        $studentData = array_filter($studentData, function($entry) use ($idsToDelete) {
            $entryId = $entry['id'] ?? '';
            return !in_array($entryId, $idsToDelete);
        });
        $studentData = array_values($studentData);
        
        if ($originalStudentCount !== count($studentData)) {
            if (!file_put_contents($studentFile, json_encode($studentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $errors[] = "Failed to update file for student: " . $studentId;
            }
        }
    }
}

if ($deleted > 0) {
    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'students_affected' => count($studentIdsAffected),
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No results were deleted',
        'errors' => $errors
    ]);
}
