<?php
/**
 * MIGRATION SCRIPT: Convert old question format to new format
 * 
 * Old format:
 * {
 *   "topic": "Chủ đề 1. ...",
 *   "lesson": "Bài 1: ...",
 *   "questions": [...]
 * }
 * 
 * New format:
 * {
 *   "topic_name": "Chủ đề A. ...",
 *   "unit_name": "Đơn vị kiến thức ...",
 *   "questions": [...]
 * }
 * 
 * Usage:
 * php migrate_questions.php <input_file> <output_file>
 * 
 * Example:
 * php migrate_questions.php khoi8/hk1/subject_1.json khoi8/hk1/subject_1_new.json
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

if ($argc < 3) {
    echo "Usage: php migrate_questions.php <input_file> <output_file>\n";
    echo "Example: php migrate_questions.php khoi8/hk1/subject_1.json khoi8/hk1/subject_1_new.json\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

// Check if input file exists
if (!file_exists($inputFile)) {
    echo "Error: Input file not found: $inputFile\n";
    exit(1);
}

// Read input file
echo "Reading: $inputFile\n";
$json = file_get_contents($inputFile);
$oldData = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in input file: " . json_last_error_msg() . "\n";
    exit(1);
}

if (!is_array($oldData)) {
    echo "Error: Input file must contain a JSON array\n";
    exit(1);
}

// Convert to new format
$newData = [];
$warnings = [];

foreach ($oldData as $index => $item) {
    $newItem = [];
    
    // Map old fields to new fields
    if (isset($item['topic_name'])) {
        // Already new format
        $newItem = $item;
        echo "  [" . ($index + 1) . "] Already new format (topic_name found)\n";
    } elseif (isset($item['topic'])) {
        // Old format - migrate
        $newItem['topic_name'] = $item['topic'];
        
        if (isset($item['unit_name'])) {
            $newItem['unit_name'] = $item['unit_name'];
        } elseif (isset($item['lesson'])) {
            $newItem['unit_name'] = $item['lesson'];
            echo "  [" . ($index + 1) . "] Migrated: lesson → unit_name\n";
        } elseif (isset($item['unit'])) {
            $newItem['unit_name'] = $item['unit'];
            echo "  [" . ($index + 1) . "] Migrated: unit → unit_name\n";
        } else {
            $newItem['unit_name'] = '';
            $warnings[] = "Item " . ($index + 1) . ": No unit/lesson field found";
            echo "  [" . ($index + 1) . "] WARNING: No unit field found\n";
        }
        
        $newItem['questions'] = $item['questions'] ?? [];
        
        if (empty($newItem['questions'])) {
            $warnings[] = "Item " . ($index + 1) . ": No questions found";
            echo "  [" . ($index + 1) . "] WARNING: No questions\n";
        } else {
            echo "  [" . ($index + 1) . "] Migrated: {$newItem['topic_name']} / {$newItem['unit_name']} (" . count($newItem['questions']) . " questions)\n";
        }
    } else {
        $warnings[] = "Item " . ($index + 1) . ": No topic field found, skipping";
        echo "  [" . ($index + 1) . "] ERROR: No topic field, skipping\n";
        continue;
    }
    
    $newData[] = $newItem;
}

// Create output directory if needed
$outputDir = dirname($outputFile);
if (!is_dir($outputDir) && !empty($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created directory: $outputDir\n";
}

// Write output file
$outputJson = json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($outputFile, $outputJson);

echo "\n✅ Migration complete!\n";
echo "   Input:  $inputFile\n";
echo "   Output: $outputFile\n";
echo "   Items:  " . count($newData) . "\n";

if (!empty($warnings)) {
    echo "\n⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
}

echo "\nNext steps:\n";
echo "1. Review the output file: cat '$outputFile'\n";
echo "2. Test with a small subset first\n";
echo "3. Backup original file before replacing\n";
echo "4. Replace: mv '$outputFile' '$inputFile'\n";
