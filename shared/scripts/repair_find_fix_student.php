<?php
$studentCode = $argv[1] ?? '';
if (!$studentCode) { echo "Usage: php repair_find_fix_student.php <student_code>\n"; exit(1); }
$cwd = realpath(__DIR__ . '/../../');
if (!$cwd) $cwd = getcwd();
echo "Workspace root: $cwd\n";
// gather potential score files under workspace
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cwd));
$files = [];
foreach ($iter as $f) {
    if ($f->isFile() && strtolower($f->getFilename()) === strtolower($studentCode . '.json')) {
        $path = $f->getPathname();
        if (strpos($path, DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'scores' . DIRECTORY_SEPARATOR) !== false) {
            $files[] = $path;
        }
    }
}
if (empty($files)) { echo "No per-student score files found for $studentCode\n"; exit(0); }

// build exam map from teacher exams (same logic as earlier)
$examMap = [];
$baseExams = $cwd . DIRECTORY_SEPARATOR . 'teacher' . DIRECTORY_SEPARATOR . 'exams' . DIRECTORY_SEPARATOR;
function simple_slug($s) {
    $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-zA-Z0-9\-]/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = trim($s, '-');
    return strtolower($s);
}
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

$totalFiles = count($files);
$modifiedFiles = 0;
foreach ($files as $path) {
    echo "Processing: $path\n";
    $json = @json_decode(@file_get_contents($path), true);
    if (!is_array($json)) { echo "  failed to decode JSON\n"; continue; }
    $backup = $path . '.' . date('YmdHis') . '.bak';
    copy($path, $backup);
    echo "  backup: $backup\n";
    $changed = 0;
    foreach ($json as &$entry) {
        $code = $entry['student_code'] ?? ($entry['student_id'] ?? '');
        if ($code !== $studentCode) continue;
        $examId = $entry['exam_id'] ?? ($entry['exam_type'] ?? '');
        $testName = $entry['test_name'] ?? ($entry['test_name'] ?? '');
        // look for malformed examId
        if ($examId === '' || preg_match('/^[0-9]+_?$/', $examId) || preg_match('/^\d+$/', $examId)) {
            $slug = simple_slug($testName);
            $fixed = null;
            if ($slug && isset($examMap[$slug])) $fixed = $examMap[$slug];
            if (!$fixed && isset($examMap[$examId])) $fixed = $examMap[$examId] ?? null;
            if (!$fixed) {
                foreach ($examMap as $k=>$v) { if (strpos($k, $slug)!==false) { $fixed = $v; break; } }
            }
            if ($fixed) {
                $entry['exam_id'] = $fixed;
                $changed++;
                echo "  fixed entry: $examId => $fixed (test_name='$testName')\n";
            }
        }
    }
    if ($changed>0) {
        file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $modifiedFiles++;
    }
}

echo "Done. Files scanned: $totalFiles, modified: $modifiedFiles\n";
?>