<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireStudentSession();

header('Content-Type: application/json; charset=utf-8');

$studentCode = $_SESSION['student_code'];
$studentClassCode = $_SESSION['student_class_code'] ?? $_SESSION['student_class'] ?? '';

$prefix = substr(trim((string)$studentClassCode), 0, 1);
$grade = ctype_digit($prefix) ? 'khoi' . $prefix : '';

$configFile = __DIR__ . '/../../admin/system_config.json';
$config = json_decode(file_get_contents($configFile), true);
$semester = $config['semester']['current'] ?? 'hk1';

$subjects = [];
$subjectsFile = __DIR__ . '/../../admin/subjects.json';
if (file_exists($subjectsFile)) {
    foreach (json_decode(file_get_contents($subjectsFile), true) ?: [] as $s) {
        if (isset($s['id'])) $subjects[(string)$s['id']] = $s;
    }
}

$doneByTopic = [];
if ($studentCode) {
    $practiceFile = __DIR__ . '/../../shared/practices/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '_practice.json';
    if (file_exists($practiceFile)) {
        foreach (json_decode(file_get_contents($practiceFile), true) ?: [] as $session) {
            $key = ($session['subject'] ?? '') . '||' . ($session['topic'] ?? '');
            if ($key === '||') continue;
            $doneByTopic[$key] = ($doneByTopic[$key] ?? 0) + (int)($session['total_questions'] ?? 0);
        }
    }
}

$result = [];
if ($grade !== '') {
    $bankDir = realpath(__DIR__ . '/../../teacher/questions/' . $grade . '/' . $semester);
    if ($bankDir) {
        foreach (@glob($bankDir . DIRECTORY_SEPARATOR . 'subject_*.json') ?: [] as $file) {
            if (!preg_match('/subject_(\d+)/', $file, $m)) continue;
            $subjectId = (string)$m[1];
            $subjectKey = 'subject_' . $subjectId;

            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) continue;

            $topics = [];
            $topicMap = [];
            $total = 0;
            $done = 0;
            foreach ($data as $entry) {
                $topicName = (string)($entry['topic'] ?? ($entry['topic_name'] ?? ''));
                $topicCount = count($entry['questions'] ?? []);
                if ($topicName === '') continue;

                if (!isset($topicMap[$topicName])) {
                    $topicMap[$topicName] = ['total' => 0, 'done' => 0];
                }
                $topicMap[$topicName]['total'] += $topicCount;
            }

            foreach (array_keys($topicMap) as $topicName) {
                $topicCount = $topicMap[$topicName]['total'];
                $topicDone = (int)($doneByTopic[$subjectKey . '||' . $topicName] ?? 0);
                if ($topicDone > $topicCount) $topicDone = $topicCount;
                $topicMap[$topicName]['done'] = $topicDone;

                $topics[] = [
                    'name'  => $topicName,
                    'total' => $topicCount,
                    'done'  => $topicDone
                ];
                $total += $topicCount;
                $done += $topicDone;
            }

            $result[] = [
                'subject_id' => (int)$subjectId,
                'subject'    => $subjectKey,
                'name'       => $subjects[$subjectId]['name'] ?? 'Môn học',
                'total'      => $total,
                'done'       => $done,
                'topics'     => $topics
            ];
        }
    }
}

usort($result, function($a, $b) { return $a['subject_id'] - $b['subject_id']; });

echo json_encode([
    'success'  => true,
    'grade'    => $grade,
    'semester' => $semester,
    'subjects' => $result
]);
