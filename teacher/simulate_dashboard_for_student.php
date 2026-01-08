<?php
$studentCode = $argv[1] ?? '';
$students = json_decode(file_get_contents(__DIR__ . '/../admin/students.json'), true);
$classes = json_decode(file_get_contents(__DIR__ . '/../admin/classes.json'), true);
$classesById = [];
foreach ($classes as $c) $classesById[$c['id']] = $c;
if ($studentCode === '') {
    // pick first student in class_id 12
    foreach ($students as $s) {
        if (($s['class_id'] ?? '') === '12') {
            $studentCode = $s['code'];
            break;
        }
    }
}
if ($studentCode === '') {
    echo "No student found for simulation\n";
    exit(1);
}
$found = null;
foreach ($students as $s) {
    if ($s['code'] === $studentCode) { $found = $s; break; }
}
if (!$found) { echo "Student $studentCode not found\n"; exit(1); }
$classId = $found['class_id'] ?? '';
$classCode = isset($classesById[$classId]) ? $classesById[$classId]['code'] : '';
$prefix = substr($classCode,0,1);
$studentGrade = 'khoi' . $prefix;
echo "Simulating for student: code={$found['code']}, name={$found['name']}, class_id={$classId}, class_code={$classCode}\n";
echo "Computed studentGrade: $studentGrade\n";
// list exams found for this grade (reuse dashboard logic)
$subjectsData = json_decode(file_get_contents(__DIR__ . '/../admin/subjects.json'), true) ?: [];
$subjects = [];
foreach ($subjectsData as $sub) $subjects[$sub['id']] = $sub['name'];
$examsDir = __DIR__ . '/exams/' . $studentGrade;
if (!is_dir($examsDir)) { echo "Exams dir not found: $examsDir\n"; exit(0); }
$subjectDirs = scandir($examsDir);
$approvedExams = [];
$studentScores = [];
$studentScoresFile = __DIR__ . '/../shared/scores/student_score.json';
if (file_exists($studentScoresFile)) $studentScores = json_decode(file_get_contents($studentScoresFile), true) ?: [];
foreach ($subjectDirs as $sd) {
    if ($sd === '.' || $sd === '..') continue;
    if (!preg_match('/subject_(\d+)/', $sd, $m)) continue;
    $subjectId = (int)$m[1];
    $files = scandir($examsDir . '/' . $sd);
    foreach ($files as $file) {
        if (!preg_match('/\.json$/', $file)) continue;
        $path = $examsDir . '/' . $sd . '/' . $file;
        $data = json_decode(file_get_contents($path), true);
        if (!$data) continue;
        if (!($data['approved'] ?? false)) continue;
        $examCode = $data['exam_code'] ?? preg_replace('/[^a-z0-9\-]/i','-',strtolower($data['test_name'] ?? $file));
        $examId = $subjectId . '_' . $examCode;
        // check completed
        $hasCompleted = false;
        foreach ($studentScores as $score) {
            if ($score['student_id'] !== $found['code']) continue;
            if ((isset($score['exam_id']) && $score['exam_id'] === $examId) || (isset($score['test_name']) && $score['test_name'] === ($data['test_name'] ?? ''))) {
                $hasCompleted = true; break;
            }
        }
        if (!$hasCompleted) {
            $approvedExams[] = ['file'=>$file,'test_name'=>$data['test_name'] ?? '', 'subject_id'=>$subjectId];
        }
    }
}
echo "\nApproved exams visible to student (count=".count($approvedExams)."):\n";
foreach ($approvedExams as $e) echo "  file={$e['file']} | test_name={$e['test_name']} | subject_id={$e['subject_id']}\n";
?>