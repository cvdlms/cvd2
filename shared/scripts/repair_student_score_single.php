<?php
$studentCode = $argv[1] ?? '';
if (!$studentCode) { echo "Usage: php repair_student_score_single.php <student_code>\n"; exit(1); }
$scoreFile = __DIR__ . '/../scores/student_score.json';
if (!file_exists($scoreFile)) { echo "student_score.json not found\n"; exit(1); }
$backup = $scoreFile . '.' . date('YmdHis') . '.bak';
copy($scoreFile, $backup);
echo "Backup created: $backup\n";
$data = json_decode(file_get_contents($scoreFile), true) ?: [];

// Build exam map
$examMap = [];
$baseExams = __DIR__ . '/../../teacher/exams/';
$gradeDirs = glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
function simple_slug($s) {
    $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-zA-Z0-9\-]/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = trim($s, '-');
    return strtolower($s);
}
foreach ($gradeDirs as $gradeDir) {
    $subjectDirs = glob($gradeDir . '/subject_*', GLOB_ONLYDIR) ?: [];
    foreach ($subjectDirs as $subjDir) {
        $files = glob($subjDir . '/*.json') ?: [];
        foreach ($files as $f) {
            $content = @json_decode(@file_get_contents($f), true);
            if (!$content) continue;
            $fname = pathinfo($f, PATHINFO_FILENAME);
            $testId = $content['test_id'] ?? null;
            $testName = $content['test_name'] ?? null;
            if ($testId) $examMap[$testId] = $testId;
            if ($testName) $examMap[simple_slug($testName)] = $testId ?: $fname;
            if ($fname) $examMap[$fname] = $testId ?: $fname;
            // subject_slug key
            $m = []; if (preg_match('/subject_(\d+)/', $subjDir, $m)) {
                $subjectId = $m[1];
                $slug = simple_slug($testName ?: $fname);
                if ($slug) $examMap[$subjectId . '_' . $slug] = $testId ?: ($subjectId . '_' . $fname);
            }
        }
    }
}

$changed = 0; $total = 0;
foreach ($data as &$entry) {
    if (($entry['student_id'] ?? '') !== $studentCode) continue;
    $total++;
    $examId = $entry['exam_id'] ?? '';
    $testName = $entry['test_name'] ?? '';
    $orig = $examId;
    $fixed = null;
    if (preg_match('/^exam_/', $examId)) continue; // already ok
    // detect malformed patterns like '1_' or '2_' or numeric+underscore
    if ($examId === '' || preg_match('/^[0-9]+_?$/', $examId)) {
        $slug = simple_slug($testName);
        if ($slug && isset($examMap[$slug])) $fixed = $examMap[$slug];
        if (!$fixed && isset($examMap[$examId])) $fixed = $examMap[$examId];
        if (!$fixed && preg_match('/^([0-9]+)_?$/', $examId, $mm)) {
            $key = $mm[1] . '_' . $slug;
            if ($slug && isset($examMap[$key])) $fixed = $examMap[$key];
        }
        // last resort: find examMap value where key contains slug
        if (!$fixed && $slug) {
            foreach ($examMap as $k => $v) {
                if (strpos($k, $slug) !== false) { $fixed = $v; break; }
            }
        }
    }
    if ($fixed && $fixed !== $examId) {
        $entry['exam_id'] = $fixed;
        $changed++;
        echo "Updated student {$studentCode} entry: '$orig' => '$fixed' (test_name='$testName')\n";
    }
}
if ($changed > 0) {
    file_put_contents($scoreFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
echo "Processed $total entries for student $studentCode, changed $changed.\n";
if ($changed>0) echo "Backup: $backup\n";
?>