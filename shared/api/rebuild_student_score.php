<?php
/**
 * Script to rebuild student_score.json from individual student score files
 * Run this once to recover lost data after the bug fix
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$scoresDir = __DIR__ . '/../scores/';
$outputFile = $scoresDir . 'student_score.json';

if (!is_dir($scoresDir)) {
    die("Error: Scores directory not found at $scoresDir\n");
}

echo "Starting rebuild of student_score.json...\n\n";

// Backup existing file if it exists
if (file_exists($outputFile)) {
    $backupFile = $scoresDir . 'student_score_backup_' . date('Ymd_His') . '.json';
    copy($outputFile, $backupFile);
    echo "Backed up existing file to: $backupFile\n\n";
}

// Scan all individual student files
$studentFiles = glob($scoresDir . '*.json');
$consolidatedScores = [];
$processedCount = 0;
$errors = [];

foreach ($studentFiles as $file) {
    $filename = basename($file);
    
    // Skip the consolidated file, backup files, and old files
    if ($filename === 'student_score.json' || 
        strpos($filename, 'student_score_backup') !== false ||
        strpos($filename, '.old') !== false ||
        strpos($filename, '_backup') !== false) {
        continue;
    }
    
    // Extract student code from filename (e.g., HS001.json -> HS001)
    $studentCode = pathinfo($filename, PATHINFO_FILENAME);
    
    echo "Processing student: $studentCode... ";
    
    $content = file_get_contents($file);
    $studentData = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMsg = "JSON decode error for $studentCode: " . json_last_error_msg();
        echo "ERROR\n  $errorMsg\n";
        $errors[] = $errorMsg;
        continue;
    }
    
    if (!is_array($studentData)) {
        $errorMsg = "Invalid data format for $studentCode (not array)";
        echo "ERROR\n  $errorMsg\n";
        $errors[] = $errorMsg;
        continue;
    }
    
    // Process each exam result for this student
    foreach ($studentData as $examResult) {
        $sourceId = $examResult['source_exam_id'] ?? ($examResult['exam_id'] ?? '');
        $subjectId = $examResult['subject_id'] ?? 0;
        $testName = $examResult['test_name'] ?? '';
        $timestamp = $examResult['timestamp'] ?? '';
        $score = $examResult['score'] ?? 0;
        $resultId = $examResult['id'] ?? '';
        $notes = $examResult['notes'] ?? '';
        
        if (empty($sourceId)) {
            continue; // Skip entries without exam ID
        }
        
        // Check if already exists in consolidated array
        $found = false;
        foreach ($consolidatedScores as &$entry) {
            if ($entry['student_id'] === $studentCode && 
                $entry['exam_id'] === $sourceId && 
                (!isset($entry['subject_id']) || $entry['subject_id'] == $subjectId)) {
                
                // Update if this is a newer attempt
                if ($timestamp > $entry['timestamp']) {
                    $entry['attempts'] = ($entry['attempts'] ?? 0) + 1;
                    $entry['timestamp'] = $timestamp;
                    $entry['score'] = $score;
                    $entry['result_id'] = $resultId;
                    $entry['subject_id'] = $subjectId;
                    
                    // Merge notes
                    if (!empty($notes)) {
                        $existingNotes = $entry['notes'] ?? '';
                        if (empty($existingNotes)) {
                            $entry['notes'] = $notes;
                        } else if (strpos($existingNotes, $notes) === false) {
                            $entry['notes'] = $existingNotes . ' | ' . $notes;
                        }
                    }
                }
                
                $found = true;
                break;
            }
        }
        
        // Add new entry if not found
        if (!$found) {
            $consolidatedScores[] = [
                'student_id' => $studentCode,
                'exam_id' => $sourceId,
                'result_id' => $resultId,
                'subject_id' => $subjectId,
                'test_name' => $testName,
                'attempts' => 1,
                'timestamp' => $timestamp,
                'score' => $score,
                'notes' => $notes
            ];
        }
    }
    
    $processedCount++;
    echo "OK (" . count($studentData) . " exams)\n";
}

echo "\n--- Summary ---\n";
echo "Total student files processed: $processedCount\n";
echo "Total consolidated entries: " . count($consolidatedScores) . "\n";
echo "Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Write consolidated file
$writeResult = file_put_contents($outputFile, json_encode($consolidatedScores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($writeResult === false) {
    die("\nFATAL ERROR: Could not write to $outputFile\n");
}

echo "\n✓ Successfully rebuilt student_score.json\n";
echo "  File size: " . number_format($writeResult) . " bytes\n";
echo "  Location: $outputFile\n";

echo "\nRebuild complete!\n";
?>
