<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';

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

// Load consolidated score file path
$consolidatedFile = __DIR__ . '/../../shared/scores/student_score.json';

// Create a set of result IDs to delete
$idsToDelete = [];
$studentIdsAffected = [];
foreach ($results as $result) {
    $idsToDelete[] = $result['id'];
    if (!in_array($result['student_id'], $studentIdsAffected)) {
        $studentIdsAffected[] = $result['student_id'];
    }
}

// Remove from consolidated file (khóa đọc-ghi để không đè dữ liệu khi học sinh đang nộp bài)
$removedFromConsolidated = 0;
$consolidatedResult = update_json_data($consolidatedFile, function($consolidatedData) use ($idsToDelete, &$removedFromConsolidated) {
    if (!is_array($consolidatedData)) { $consolidatedData = []; }
    $originalCount = count($consolidatedData);
    $consolidatedData = array_values(array_filter($consolidatedData, function($entry) use ($idsToDelete) {
        $entryId = $entry['result_id'] ?? $entry['id'] ?? '';
        return !in_array($entryId, $idsToDelete);
    }));
    $removedFromConsolidated = $originalCount - count($consolidatedData);
    return $consolidatedData;
}, []);

if ($consolidatedResult === false) {
    $errors[] = "Failed to update consolidated score file";
} else {
    $deleted = $removedFromConsolidated;
}

// Remove from individual student files
foreach ($studentIdsAffected as $studentId) {
    $studentFile = __DIR__ . '/../../shared/scores/' . $studentId . '.json';
    
    if (file_exists($studentFile)) {
        $removedFromStudent = 0;
        $ok = update_json_data($studentFile, function($studentData) use ($idsToDelete, &$removedFromStudent) {
            if (!is_array($studentData)) { $studentData = []; }
            $originalStudentCount = count($studentData);
            $studentData = array_values(array_filter($studentData, function($entry) use ($idsToDelete) {
                return !in_array($entry['id'] ?? '', $idsToDelete);
            }));
            $removedFromStudent = $originalStudentCount - count($studentData);
            return $studentData;
        }, []);
        
        if ($ok === false && $removedFromStudent > 0) {
            $errors[] = "Failed to update file for student: " . $studentId;
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
