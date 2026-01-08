<?php
$grade = $argv[1] ?? 'khoi7';
$base = __DIR__ . '/exams/' . $grade;
if (!is_dir($base)) {
    echo "Grade dir not found: $base\n";
    exit(1);
}
$subjectDirs = scandir($base);
$found = 0;
foreach ($subjectDirs as $subj) {
    if ($subj === '.' || $subj === '..') continue;
    $m = [];
    if (!preg_match('/subject_(\d+)/', $subj, $m)) {
        echo "Skipping non-subject entry: $subj\n";
        continue;
    }
    $subjectId = intval($m[1]);
    $path = $base . '/' . $subj;
    if (!is_dir($path)) {
        echo "Not a dir: $path\n";
        continue;
    }
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (!preg_match('/\.json$/', $file)) {
            echo "Skipping non-json file: $file in $subj\n";
            continue;
        }
        $full = $path . '/' . $file;
        $content = @file_get_contents($full);
        if ($content === false) {
            echo "Cannot read file: $full\n";
            continue;
        }
        $data = json_decode($content, true);
        if ($data === null) {
            echo "Invalid JSON in $full\n";
            continue;
        }
        $approved = $data['approved'] ?? false;
        echo "File: $file | subject_id={$data['subject_id']} | test_name={$data['test_name']} | approved=" . ($approved ? 'true' : 'false') . "\n";
        if ($approved) $found++;
    }
}
echo "\nTotal approved exams found for $grade: $found\n";
?>