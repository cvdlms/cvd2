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
    $data = json_decode(file_get_contents($studentFile), true) ?: [];
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
    $studentScores = [];
    if (file_exists($studentFile)) {
        $studentScores = json_decode(file_get_contents($studentFile), true) ?: [];
    }
    $studentScores[] = $result;
    file_put_contents($studentFile, json_encode($studentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Also save to student_score.json for manage_result display
    $studentScoreFile = __DIR__ . '/../scores/student_score.json';
    $allStudentScores = [];
    if (file_exists($studentScoreFile)) {
        $allStudentScores = json_decode(file_get_contents($studentScoreFile), true) ?: [];
    }

    // Primary key: use source_exam_id (which should be the canonical test_id)
    // Secondary matcher: also use subject_id to ensure no cross-subject false matches
    $sourceId = $result['source_exam_id'] ?? '';
    $subjectId = $result['subject_id'] ?? '';

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
            'score' => $result['score']
        ];
    }

    return file_put_contents($studentScoreFile, json_encode($allStudentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Output the scores as JSON only when accessed directly
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
    echo json_encode(getAllScores());
}
?>
