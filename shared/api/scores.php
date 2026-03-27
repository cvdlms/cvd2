<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getAllScores() {
    $scoresDir = __DIR__ . '/../scores/';
    if (!is_dir($scoresDir)) {
        return [];
    }
    $allScores = [];
    $files = glob($scoresDir . '*.json');
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $allScores = array_merge($allScores, $data);
        }
    }
    return $allScores;
}

function getStudentAttempts($studentCode, $testId) {
    // Return array of previous attempts for this student+test (match by source exam id)
    $studentFile = __DIR__ . '/../scores/' . $studentCode . '.json';
    if (!file_exists($studentFile)) {
        return [];
    }
    $content = file_get_contents($studentFile);
    $data = json_decode($content, true);
    
    // Handle JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in getStudentAttempts for $studentCode: " . json_last_error_msg());
        error_log("File content: " . substr($content, 0, 200));
        return [];
    }
    
    if (!is_array($data)) {
        error_log("getStudentAttempts: Data is not array for $studentCode");
        return [];
    }
    
    $attempts = [];
    foreach ($data as $entry) {
        if ((isset($entry['source_exam_id']) && $entry['source_exam_id'] === $testId) || (isset($entry['exam_id']) && $entry['exam_id'] === $testId)) {
            $attempts[] = $entry;
        }
    }
    return $attempts;
}

function saveExamResult($result) {
    $studentCode = $result['student_code'];
    $studentFile = __DIR__ . '/../scores/' . $studentCode . '.json';
    
    // Ensure directory exists
    $scoresDir = __DIR__ . '/../scores/';
    if (!is_dir($scoresDir)) {
        error_log("saveExamResult: Creating scores directory");
        mkdir($scoresDir, 0755, true);
    }
    
    $studentScores = [];
    if (file_exists($studentFile)) {
        $studentScores = json_decode(file_get_contents($studentFile), true) ?: [];
    }
    $studentScores[] = $result;
    
    $writeResult = file_put_contents($studentFile, json_encode($studentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($writeResult === false) {
        error_log("saveExamResult: Failed to write student file for $studentCode");
    }

    // Also save to student_score.json for manage_result display
    $studentScoreFile = __DIR__ . '/../scores/student_score.json';
    $allStudentScores = [];
    if (file_exists($studentScoreFile)) {
        $content = file_get_contents($studentScoreFile);
        $allStudentScores = json_decode($content, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("saveExamResult: JSON decode error in student_score.json: " . json_last_error_msg());
            error_log("saveExamResult: File content (first 500 chars): " . substr($content, 0, 500));
            // Try to recover by creating empty array
            $allStudentScores = [];
        }
        
        if (!is_array($allStudentScores)) {
            error_log("saveExamResult: student_score.json data is not array, resetting");
            $allStudentScores = [];
        }
    }

    // Primary key: use source_exam_id (which should be the canonical test_id)
    // Secondary matcher: also use subject_id to ensure no cross-subject false matches
    $sourceId = $result['source_exam_id'] ?? '';
    $subjectId = $result['subject_id'] ?? '';
    $notes = $result['notes'] ?? '';  // Get notes from result

    // Find existing entry for this student and exam (match by source_exam_id + subject_id)
    $found = false;
    foreach ($allStudentScores as &$entry) {
        if ($entry['student_id'] === $studentCode && isset($entry['exam_id']) && $entry['exam_id'] === $sourceId) {
            // Additional check: if subject_id is set, ensure it matches to prevent cross-subject false positives
            if (!isset($entry['subject_id']) || $entry['subject_id'] == $subjectId) {
                $entry['attempts'] = ($entry['attempts'] ?? 0) + 1;
                $entry['timestamp'] = $result['timestamp'];
                $entry['score'] = $result['score'];
                $entry['result_id'] = $result['id'];
                $entry['subject_id'] = $subjectId;  // Ensure subject_id is always set
                
                // Update notes: append new notes if different
                if (!empty($notes)) {
                    $existingNotes = $entry['notes'] ?? '';
                    if (empty($existingNotes)) {
                        $entry['notes'] = $notes;
                    } else if (strpos($existingNotes, $notes) === false) {
                        // Append new note with separator
                        $entry['notes'] = $existingNotes . ' | ' . $notes;
                    }
                } else if (!isset($entry['notes'])) {
                    $entry['notes'] = '';
                }
                
                $found = true;
                break;
            }
        }
    }

    if (!$found) {
        $allStudentScores[] = [
            'student_id' => $studentCode,
            'exam_id' => $sourceId,  // CRITICAL: use source_exam_id (canonical test_id)
            'result_id' => $result['id'],
            'subject_id' => $subjectId,  // CRITICAL: always include subject_id to prevent cross-subject matches
            'test_name' => $result['test_name'],
            'attempts' => 1,
            'timestamp' => $result['timestamp'],
            'score' => $result['score'],
            'notes' => $notes  // Save notes
        ];
    }

    $finalResult = file_put_contents($studentScoreFile, json_encode($allStudentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($finalResult === false) {
        error_log("saveExamResult: CRITICAL - Failed to write student_score.json for student $studentCode");
        error_log("saveExamResult: File path: $studentScoreFile");
        error_log("saveExamResult: Is writable: " . (is_writable($studentScoreFile) ? 'yes' : 'no'));
        error_log("saveExamResult: Directory writable: " . (is_writable(dirname($studentScoreFile)) ? 'yes' : 'no'));
    } else {
        error_log("saveExamResult: Successfully saved score for student $studentCode (wrote $finalResult bytes)");
    }
    
    return $finalResult;
}

// Output the scores as JSON only when accessed directly
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
    echo json_encode(getAllScores());
}
?>
