<?php
// Migration script: rename exam files to use test_id.json when possible
$base = __DIR__ . '/exams';
$dirs = glob($base . '/khoi*', GLOB_ONLYDIR) ?: [];
$renamed = 0;
$skipped = 0;
$errors = [];
foreach ($dirs as $gradeDir) {
    $subjectDirs = glob($gradeDir . '/subject_*', GLOB_ONLYDIR) ?: [];
    foreach ($subjectDirs as $subjDir) {
        $files = glob($subjDir . '/*.json') ?: [];
        foreach ($files as $file) {
            $content = @json_decode(@file_get_contents($file), true);
            if (!$content || !isset($content['test_id'])) {
                $skipped++;
                continue;
            }
            $desired = $subjDir . '/' . $content['test_id'] . '.json';
            if (basename($file) === basename($desired)) {
                // already correct
                continue;
            }
            if (file_exists($desired)) {
                // avoid overwrite: skip or create unique name
                $errors[] = "Destination exists: $desired (source: $file)";
                $skipped++;
                continue;
            }
            if (@rename($file, $desired)) {
                $renamed++;
            } else {
                $errors[] = "Failed to rename $file to $desired";
            }
        }
    }
}
echo "Renamed: $renamed\nSkipped: $skipped\n";
if (!empty($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
}
?>