<?php
include 'api/scores.php';

$scores = getAllScores();
$studentScores = [];

// Group scores by student_code and test_name to calculate averages
$groupedScores = [];
foreach ($scores as $score) {
    $key = $score['student_code'] . '_' . $score['test_name'];
    if (!isset($groupedScores[$key])) {
        $groupedScores[$key] = [
            'student_code' => $score['student_code'],
            'student_name' => $score['student_name'],
            'class_code' => $score['class_code'],
            'test_name' => $score['test_name'],
            'scores' => [],
            'timestamps' => []
        ];
    }
    $groupedScores[$key]['scores'][] = $score['score'];
    $groupedScores[$key]['timestamps'][] = $score['timestamp'];
}

// Create student_score.json with individual attempts (score_1, score_2, attempts)
foreach ($groupedScores as $group) {
    $entry = [
        'student_code' => $group['student_code'],
        'student_name' => $group['student_name'],
        'class_code' => $group['class_code'],
        'test_name' => $group['test_name'],
        'attempts' => count($group['scores']),
        'timestamp' => max($group['timestamps']) // Use the latest timestamp
    ];

    // Add score_1 and score_2
    $entry['score_1'] = count($group['scores']) >= 1 ? $group['scores'][0] : null;
    $entry['score_2'] = count($group['scores']) >= 2 ? $group['scores'][1] : null;

    // Calculate average score
    $validScores = array_filter([$entry['score_1'], $entry['score_2']], function($s) { return $s !== null; });
    $entry['score'] = count($validScores) > 0 ? array_sum($validScores) / count($validScores) : null;

    // Convert timestamp to readable format
    $entry['timestamp'] = date('d/m/Y H:i', strtotime($entry['timestamp']));

    $studentScores[] = $entry;
}

file_put_contents('student_score.json', json_encode($studentScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'student_score.json updated with individual scores';
?>
