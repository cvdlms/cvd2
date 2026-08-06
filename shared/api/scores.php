<?php
// Comprehensive scores endpoint that handles both file structures
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';

function getScoresForStudent($studentId) {
    // Strategy 1: Check consolidated student_score.json
    $consolidatedFile = __DIR__ . '/../scores/student_score.json';
    if (file_exists($consolidatedFile)) {
        $scores = get_json_data($consolidatedFile, []);
        $studentScores = array_filter($scores, function($score) use ($studentId) {
            return isset($score['student_id']) && $score['student_id'] === $studentId;
        });
        if (!empty($studentScores)) {
            return array_values($studentScores);
        }
    }

    // Strategy 2: Check individual student file
    $individualFile = __DIR__ . '/../scores/' . $studentId . '.json';
    if (file_exists($individualFile)) {
        $scores = get_json_data($individualFile, []);
        if (!empty($scores)) {
            return $scores;
        }
    }

    // Strategy 3: Check student history file
    $historyFile = __DIR__ . '/../data/student_history_' . $studentId . '.json';
    if (file_exists($historyFile)) {
        $scores = get_json_data($historyFile, []);
        if (!empty($scores)) {
            return $scores;
        }
    }

    return [];
}

function getAllScores() {
    $scoresDir = __DIR__ . '/../scores/';
    $allScores = [];

    $consolidatedFile = $scoresDir . 'student_score.json';
    if (file_exists($consolidatedFile)) {
        $data = get_json_data($consolidatedFile, []);
        if (is_array($data)) {
            $allScores = array_merge($allScores, $data);
        }
    }

    if (is_dir($scoresDir)) {
        $files = glob($scoresDir . '*.json');
        foreach ($files as $file) {
            if (basename($file) === 'student_score.json') continue;
            $data = get_json_data($file, []);
            if (is_array($data)) {
                $allScores = array_merge($allScores, $data);
            }
        }
    }

    return $allScores;
}

function getStudentAttempts($studentCode, $testId) {
    if (empty($studentCode) || empty($testId)) {
        return [];
    }

    $matchingAttempts = [];

    // 1. Check individual student score file
    $individualFile = __DIR__ . '/../scores/' . $studentCode . '.json';
    if (file_exists($individualFile)) {
        $scores = get_json_data($individualFile, []);
        if (is_array($scores)) {
            foreach ($scores as $score) {
                $examId = $score['source_exam_id'] ?? $score['exam_id'] ?? '';
                if ($examId === $testId) {
                    $matchingAttempts[] = $score;
                }
            }
        }
        if (!empty($matchingAttempts)) {
            return $matchingAttempts;
        }
    }

    // 2. Fallback to consolidated student_score.json
    $consolidatedFile = __DIR__ . '/../scores/student_score.json';
    if (file_exists($consolidatedFile)) {
        $scores = get_json_data($consolidatedFile, []);
        if (is_array($scores)) {
            foreach ($scores as $score) {
                $sid = $score['student_id'] ?? $score['student_code'] ?? '';
                $eid = $score['exam_id'] ?? $score['source_exam_id'] ?? '';
                if ($sid === $studentCode && $eid === $testId) {
                    $count = (int)($score['attempts'] ?? 1);
                    for ($i = 0; $i < $count; $i++) {
                        $matchingAttempts[] = $score;
                    }
                    return $matchingAttempts;
                }
            }
        }
    }

    return [];
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
    
    $studentScores = get_json_data($studentFile, []);
    $studentScores[] = $result;
    
    $writeResult = save_json_data($studentFile, $studentScores);
    if ($writeResult === false) {
        error_log("saveExamResult: Failed to write student file for $studentCode");
    }

    // Also save to student_score.json for manage_result display
    $studentScoreFile = __DIR__ . '/../scores/student_score.json';
    $allStudentScores = get_json_data($studentScoreFile, []);

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

    $finalResult = save_json_data($studentScoreFile, $allStudentScores);
    
    if ($finalResult === false) {
        error_log("saveExamResult: CRITICAL - Failed to write student_score.json for student $studentCode");
    } else {
        error_log("saveExamResult: Successfully saved score for student $studentCode");
    }
    
    return $finalResult;
}

// Output the scores as JSON only when accessed directly
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
    $studentId = $_GET['student_id'] ?? '';
    if (empty($studentId)) {
        echo json_encode(['error' => 'Student ID required']);
        exit;
    }
    $scores = getScoresForStudent($studentId);
    echo json_encode($scores);
}
?>
