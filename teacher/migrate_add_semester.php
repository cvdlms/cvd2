<?php
/**
 * Migration Script: Add Semester Structure
 * 
 * This script migrates existing question files from:
 *   questions/{khoi}/subject_{id}.json
 * to:
 *   questions/{khoi}/hk1/subject_{id}.json
 * 
 * All existing questions will be moved to Học kì 1 (hk1) by default.
 */

echo "<h2>Migration: Add Semester Structure</h2>\n";
echo "<pre>\n";

$questionsBaseDir = __DIR__ . '/questions/';
$grades = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
$migrationLog = [];

foreach ($grades as $grade) {
    $gradeDir = $questionsBaseDir . $grade;
    
    if (!is_dir($gradeDir)) {
        echo "✗ Skipping {$grade}: Directory does not exist\n";
        continue;
    }
    
    echo "\n📁 Processing {$grade}...\n";
    
    // Get all subject JSON files in the grade directory
    $files = glob($gradeDir . '/subject_*.json');
    
    if (empty($files)) {
        echo "  ✗ No subject files found in {$grade}\n";
        continue;
    }
    
    // Create hk1 and hk2 directories
    $hk1Dir = $gradeDir . '/hk1';
    $hk2Dir = $gradeDir . '/hk2';
    
    if (!is_dir($hk1Dir)) {
        mkdir($hk1Dir, 0755, true);
        echo "  ✓ Created {$grade}/hk1/\n";
    }
    
    if (!is_dir($hk2Dir)) {
        mkdir($hk2Dir, 0755, true);
        echo "  ✓ Created {$grade}/hk2/\n";
    }
    
    // Move each subject file to hk1
    foreach ($files as $file) {
        $filename = basename($file);
        $newPath = $hk1Dir . '/' . $filename;
        
        // Copy file to hk1
        if (copy($file, $newPath)) {
            echo "  ✓ Migrated {$filename} to {$grade}/hk1/\n";
            
            // Create empty file in hk2 for consistency
            $hk2Path = $hk2Dir . '/' . $filename;
            if (!file_exists($hk2Path)) {
                file_put_contents($hk2Path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo "  ✓ Created empty {$filename} in {$grade}/hk2/\n";
            }
            
            // Backup original file before deleting
            $backupPath = $file . '.backup';
            if (rename($file, $backupPath)) {
                echo "  ✓ Backed up original {$filename} to {$filename}.backup\n";
                $migrationLog[] = [
                    'grade' => $grade,
                    'file' => $filename,
                    'original' => $file,
                    'backup' => $backupPath,
                    'new_hk1' => $newPath,
                    'new_hk2' => $hk2Path,
                    'status' => 'success'
                ];
            } else {
                echo "  ⚠ Warning: Could not backup {$filename}\n";
            }
        } else {
            echo "  ✗ Failed to migrate {$filename}\n";
            $migrationLog[] = [
                'grade' => $grade,
                'file' => $filename,
                'status' => 'failed'
            ];
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Migration Summary:\n";
echo str_repeat("=", 60) . "\n";

$successCount = 0;
$failedCount = 0;

foreach ($migrationLog as $log) {
    if ($log['status'] === 'success') {
        $successCount++;
    } else {
        $failedCount++;
    }
}

echo "✓ Successfully migrated: {$successCount} files\n";
echo "✗ Failed: {$failedCount} files\n";

echo "\n📝 Migration Log:\n";
echo json_encode($migrationLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n";
echo str_repeat("=", 60) . "\n";
echo "Migration complete!\n";
echo str_repeat("=", 60) . "\n";
echo "\nNOTE: Original files have been backed up with .backup extension.\n";
echo "You can safely delete them after verifying the migration.\n";
echo "</pre>\n";
?>
