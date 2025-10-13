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

function getStudentAttempts($studentCode, $testName) {
    $studentFile = __DIR__ . '/../scores/' . $studentCode . '.json';
    if (!file_exists($studentFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($studentFile), true);
    if (!$data) {
        return [];
    }
    return array_filter($data, function($score) use ($testName) {
        return $score['test_name'] === $testName;
    });
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
