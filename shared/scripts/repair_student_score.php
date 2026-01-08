<?php
// Repair malformed entries in shared/scores/student_score.json
$root = __DIR__ . '/../../';
$scoreFile = __DIR__ . '/../scores/student_score.json';
if (!file_exists($scoreFile)) {
    echo "student_score.json not found: $scoreFile\n";
    exit(1);
}
$backup = $scoreFile . '.' . date('YmdHis') . '.bak';
copy($scoreFile, $backup);
echo "Backup created: $backup\n";
$data = json_decode(file_get_contents($scoreFile), true) ?: [];

// Build mapping from teacher exams
$examMap = []; // keys: test_id, slug(test_name), filename -> test_id
$baseExams = __DIR__ . '/../../teacher/exams/';
$gradeDirs = glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
function simple_slug($s) {
    $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
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
            if ($testId) {
                $examMap[$testId] = $testId;
            }
            if ($testName) {
                $examMap[simple_slug($testName)] = $testId ?: $fname;
            }
            if ($fname) {
                $examMap[$fname] = $testId ?: $fname;
            }
            // also map subjectid_slug -> testId
            $m = []; if (preg_match('/subject_(\d+)/', $subjDir, $m)) {
                $subjectId = $m[1];
                $slugName = simple_slug($testName ?: $fname);
                if ($slugName) {
                    $examMap[$subjectId . '_' . $slugName] = $testId ?: ($subjectId . '_' . $fname);
                }
            }
        }
    }
}

$changed = 0; $total = count($data);
foreach ($data as $i => &$entry) {
    $examId = $entry['exam_id'] ?? '';
    $testName = $entry['test_name'] ?? '';
    $orig = $examId;
    $fixed = null;
    // If exam_id already looks like canonical test_id (contains non-numeric, not just like '1_') prefer it
    if (preg_match('/^exam_/', $examId)) {
        // assume OK
        continue;
    }
    // If empty or malformed (like '1_' or ends with underscore)
    if ($examId === '' || preg_match('/^[0-9]+_?$/', $examId)) {
        // try by test_name slug
        $slug = simple_slug($testName);
        if ($slug && isset($examMap[$slug])) {
            $fixed = $examMap[$slug];
        }
        // try subjectid_ + slug
        if (!$fixed && preg_match('/^([0-9]+)_?$/', $examId, $mm)) {
            $subj = $mm[1];
            $key = $subj . '_' . $slug;
            if ($slug && isset($examMap[$key])) $fixed = $examMap[$key];
        }
        // try filename keys
        if (!$fixed && isset($examMap[$examId])) $fixed = $examMap[$examId];
        // fallback: try find any mapping by test_name containing words
        if (!$fixed && $testName) {
            $s = simple_slug($testName);
            foreach ($examMap as $k => $v) {
                if ($k === $s) { $fixed = $v; break; }
            }
        }
    }
    // If fixed, update
    if ($fixed && $fixed !== $examId) {
        $entry['exam_id'] = $fixed;
        $changed++;
        echo "Updated entry index $i: '$orig' => '$fixed' (test_name='$testName')\n";
    }
}

if ($changed > 0) {
    file_put_contents($scoreFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo "Processed $total entries, changed $changed entries.\n";
if ($changed>0) echo "Original backed up at: $backup\n";
?>