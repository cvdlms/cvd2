<?php
function getAllScores() {
    $scoresFilePath = __DIR__ . '/../scores.json';
    if (!file_exists($scoresFilePath)) {
        return [];
    }
    $data = json_decode(file_get_contents($scoresFilePath), true);
    return $data ?: [];
}

function getStudentAttempts($studentCode, $examType) {
    $allScores = getAllScores();
    return array_filter($allScores, function($score) use ($studentCode, $examType) {
        return $score['student_code'] === $studentCode && $score['exam_type'] === $examType;
    });
}

function saveExamResult($result) {
    $scoresFilePath = __DIR__ . '/../scores.json';
    $allScores = getAllScores();
    $allScores[] = $result;
    return file_put_contents($scoresFilePath, json_encode($allScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
