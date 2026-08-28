<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';
require_once __DIR__ . '/../../includes/school_year.php';

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

// Lưu trữ bản ghi trước khi xóa (soft-delete) — yêu cầu lưu trữ 5 năm:
// dữ liệu bị xóa trên giao diện vẫn còn nguyên trong shared/scores_deleted/.
$removedEntries = [];

// Remove from consolidated file (khóa đọc-ghi để không đè dữ liệu khi học sinh đang nộp bài)
$removedFromConsolidated = 0;
$consolidatedResult = update_json_data($consolidatedFile, function($consolidatedData) use ($idsToDelete, &$removedFromConsolidated, &$removedEntries) {
    if (!is_array($consolidatedData)) { $consolidatedData = []; }
    $originalCount = count($consolidatedData);
    $kept = [];
    foreach ($consolidatedData as $entry) {
        $entryId = $entry['result_id'] ?? $entry['id'] ?? '';
        if (in_array($entryId, $idsToDelete)) {
            $removedEntries[] = ['source' => 'student_score.json', 'record' => $entry];
        } else {
            $kept[] = $entry;
        }
    }
    $consolidatedData = array_values($kept);
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
        $ok = update_json_data($studentFile, function($studentData) use ($idsToDelete, &$removedFromStudent, &$removedEntries) {
            if (!is_array($studentData)) { $studentData = []; }
            $originalStudentCount = count($studentData);
            $kept = [];
            foreach ($studentData as $entry) {
                if (in_array($entry['id'] ?? '', $idsToDelete)) {
                    $removedEntries[] = ['source' => basename($studentFile), 'record' => $entry];
                } else {
                    $kept[] = $entry;
                }
            }
            $studentData = array_values($kept);
            $removedFromStudent = $originalStudentCount - count($studentData);
            return $studentData;
        }, []);
        
        if ($ok === false && $removedFromStudent > 0) {
            $errors[] = "Failed to update file for student: " . $studentId;
        }
    }
}

// Ghi bản ghi đã xóa vào kho lưu trữ thùng rác (không xóa vĩnh viễn)
$archivedCount = 0;
if (!empty($removedEntries)) {
    $trashDir = __DIR__ . '/../../shared/scores_deleted/';
    if (!is_dir($trashDir)) {
        @mkdir($trashDir, 0755, true);
    }
    $trashFile = $trashDir . 'deleted_' . date('Ymd_His') . '_' . uniqid() . '.json';
    $trashPayload = [
        'deleted_at' => date('Y-m-d H:i:s'),
        'deleted_by' => $_SESSION['username'] ?? 'admin',
        'school_year' => get_current_school_year(),
        'count' => count($removedEntries),
        'records' => $removedEntries
    ];
    if (save_json_data($trashFile, $trashPayload)) {
        $archivedCount = count($removedEntries);
    } else {
        $errors[] = "Không lưu được bản lưu trữ soft-delete vào shared/scores_deleted/";
    }
}

if ($deleted > 0) {
    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'archived' => $archivedCount,
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
