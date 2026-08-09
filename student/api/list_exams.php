<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireStudentSession();

header('Content-Type: application/json; charset=utf-8');

$studentCode = $_SESSION['student_code'];
$studentClassCode = $_SESSION['student_class_code'] ?? $_SESSION['student_class'] ?? '';

$prefix = substr(trim((string)$studentClassCode), 0, 1);
$grade = ctype_digit($prefix) ? 'khoi' . $prefix : '';

$subjects = [];
$subjectsFile = __DIR__ . '/../../admin/subjects.json';
if (file_exists($subjectsFile)) {
    foreach (json_decode(file_get_contents($subjectsFile), true) ?: [] as $s) {
        if (isset($s['id'])) $subjects[(string)$s['id']] = $s;
    }
}

$scoresByExam = [];
if ($studentCode) {
    $scoreFile = __DIR__ . '/../../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '.json';
    if (file_exists($scoreFile)) {
        foreach (json_decode(file_get_contents($scoreFile), true) ?: [] as $entry) {
            if (empty($entry['completed'])) continue;
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            if ($storedId === '') continue;
            $scoresByExam[$storedId] = $entry;
        }
    }
}

$exams = [];
if ($grade !== '') {
    $baseExams = realpath(__DIR__ . '/../../teacher/exams/');
    if ($baseExams) {
        $gradeDir = $baseExams . DIRECTORY_SEPARATOR . $grade;
        foreach (@glob($gradeDir . DIRECTORY_SEPARATOR . 'subject_*', GLOB_ONLYDIR) ?: [] as $subjectDir) {
            if (!preg_match('/subject_(\d+)/', $subjectDir, $m)) continue;
            $subjectId = (string)$m[1];
            foreach (@glob($subjectDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (!is_array($data)) continue;
                $testId = $data['test_id'] ?? '';
                if ($testId === '') continue;
                if (empty($data['approved'])) continue;

                $subject = $subjects[$subjectId] ?? null;
                $done = isset($scoresByExam[$testId]);
                $scoreEntry = $done ? $scoresByExam[$testId] : null;

                $exams[] = [
                    'test_id'          => $testId,
                    'test_name'        => $data['test_name'] ?? 'Bài kiểm tra',
                    'subject_id'       => (int)$subjectId,
                    'subject_name'     => $subject['name'] ?? 'Môn học',
                    'exam_type'        => $data['exam_type'] ?? 'practice',
                    'time_limit'       => (int)($data['time_limit'] ?? 45),
                    'total_questions'  => (int)($data['total_questions'] ?? count($data['questions'] ?? [])),
                    'points_per_question' => (float)($data['points_per_question'] ?? 0),
                    'total_points'     => (float)($data['total_points'] ?? 10),
                    'status'           => $done ? 'done' : 'open',
                    'score'            => $done && isset($scoreEntry['score']) ? round((float)$scoreEntry['score'], 1) : null,
                    'result_id'        => $done ? ($scoreEntry['id'] ?? null) : null,
                    'created_at'       => $data['created_at'] ?? ''
                ];
            }
        }
    }
}

usort($exams, function($a, $b) {
    $pa = $a['status'] === 'open' ? 0 : 1;
    $pb = $b['status'] === 'open' ? 0 : 1;
    if ($pa !== $pb) return $pa - $pb;
    return strcmp((string)$a['created_at'], (string)$b['created_at']);
});

$openCount = 0;
$doneCount = 0;
$scoreSum = 0;
foreach ($exams as $e) {
    if ($e['status'] === 'open') $openCount++;
    if ($e['status'] === 'done') {
        $doneCount++;
        if ($e['score'] !== null) $scoreSum += $e['score'];
    }
}

echo json_encode([
    'success' => true,
    'grade'   => $grade,
    'exams'   => $exams,
    'stats'   => [
        'done'   => $doneCount,
        'avg'    => $doneCount > 0 ? round($scoreSum / $doneCount, 1) : 0,
        'open'   => $openCount
    ]
]);
