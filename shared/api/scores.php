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
    $studentScoreFile = __DIR__ . '/../scores/student_score.json';
    if (!file_exists($studentScoreFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($studentScoreFile), true);
    if (!$data) {
        return [];
    }
    $attempts = 0;
    foreach ($data as $entry) {
        if ($entry['student_id'] === $studentCode && $entry['exam_id'] === $testId) {
            $attempts = $entry['attempts'];
            break;
        }
    }
    return array_fill(0, $attempts, ['dummy' => true]);
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

    // Find existing entry for this student and exam
    $found = false;
    foreach ($allStudentScores as &$entry) {
        if ($entry['student_id'] === $studentCode && $entry['exam_id'] === $result['id']) {
            $entry['attempts'] = $entry['attempts'] + 1;
            $entry['timestamp'] = $result['timestamp'];
            $entry['score'] = $result['score'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $allStudentScores[] = [
            'student_id' => $studentCode,
            'exam_id' => $result['id'],
            'subject_id' => $result['subject_id'] ?? '',
            'test_name' => $result['test_name'],
            'attempts' => 0,  // Set to 0 after completion
            'timestamp' => $result['timestamp'],
            'score' => $result['score']
        ];
    } else {
        $entry['attempts'] = 0;  // Set to 0 after completion
    }

    return file_put_contents($studentScoreFile, json_encode($allStudentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Output the scores as JSON only when accessed directly
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
    echo json_encode(getAllScores());
}
?>
