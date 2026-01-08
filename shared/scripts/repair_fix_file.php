<?php
$path = $argv[1] ?? '';
if (!$path) { echo "Usage: php repair_fix_file.php <path-to-student-file.json>\n"; exit(1); }
if (!file_exists($path)) { echo "File not found: $path\n"; exit(1); }
$studentCode = $argv[2] ?? null;
$json = @json_decode(@file_get_contents($path), true);
if (!is_array($json)) { echo "Failed to decode JSON: $path\n"; exit(1); }
$backup = $path . '.' . date('YmdHis') . '.bak';
copy($path, $backup);
echo "Backup created: $backup\n";
// build exam map from teacher exams under nearest cvd2 folder
$cwd = realpath(dirname(__DIR__,1));
$baseExams = dirname($cwd) . DIRECTORY_SEPARATOR . 'teacher' . DIRECTORY_SEPARATOR . 'exams' . DIRECTORY_SEPARATOR;
function simple_slug($s) {
    $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-zA-Z0-9\-]/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = trim($s, '-');
    return strtolower($s);
}
$examMap = [];
if (is_dir($baseExams)) {
    $gradeDirs = glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
    foreach ($gradeDirs as $gradeDir) {
        $subjectDirs = glob($gradeDir . '/subject_*', GLOB_ONLYDIR) ?: [];
        foreach ($subjectDirs as $subjDir) {
            $filesList = glob($subjDir . '/*.json') ?: [];
            foreach ($filesList as $f) {
                $content = @json_decode(@file_get_contents($f), true);
                if (!$content) continue;
                $fname = pathinfo($f, PATHINFO_FILENAME);
                $testId = $content['test_id'] ?? null;
                $testName = $content['test_name'] ?? null;
                if ($testId) $examMap[$testId] = $testId;
                if ($testName) $examMap[simple_slug($testName)] = $testId ?: $fname;
                if ($fname) $examMap[$fname] = $testId ?: $fname;
            }
        }
    }
}
$changed=0;
foreach ($json as &$entry) {
    $code = $entry['student_code'] ?? ($entry['student_id'] ?? null);
    if ($studentCode && $code !== $studentCode) continue;
    $examId = $entry['exam_id'] ?? ($entry['exam_type'] ?? '');
    $testName = $entry['test_name'] ?? ($entry['test_name'] ?? '');
    if ($examId === '' || preg_match('/^[0-9]+_?$/', $examId) || preg_match('/^\d+$/', $examId)) {
        $slug = simple_slug($testName);
        $fixed = null;
        if ($slug && isset($examMap[$slug])) $fixed = $examMap[$slug];
        if (!$fixed && isset($examMap[$examId])) $fixed = $examMap[$examId] ?? null;
        if (!$fixed) { foreach ($examMap as $k=>$v) { if (strpos($k, $slug)!==false) { $fixed = $v; break; } } }
        if ($fixed) { $entry['exam_id'] = $fixed; $changed++; echo "Fixed: $examId => $fixed (test_name='$testName')\n"; }
    }
}
if ($changed>0) file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Completed. Changed: $changed\n";
?>