<?php
/**
 * Migration Script: presentations.json → Individual files
 * Run once to migrate from old storage to new system
 */

session_name('CVD_TEACHER_SESSION');
session_start();

// Only admin can run migration
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    die('Only admin can run migration');
}

require_once __DIR__ . '/../includes/PresentationStorage.php';

$storage = new PresentationStorage();

// Check if already migrated
if (!$storage->isLegacySystem()) {
    echo "<h2>✅ Migration already completed!</h2>";
    echo "<p>presentations_index.json already exists.</p>";
    echo '<p><a href="slides.php">Go to Slides</a></p>';
    exit;
}

// Get old presentations
$oldPresentations = $storage->getLegacyPresentations();

if (empty($oldPresentations)) {
    echo "<h2>⚠️ No presentations to migrate</h2>";
    echo '<p><a href="slides.php">Go to Slides</a></p>';
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Migration - CVD</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border-left: 3px solid #667eea; }
    </style>
</head>
<body>
    <h1>🔄 Presentation Storage Migration</h1>
    <p>Migrating from single presentations.json to individual files...</p>
    <hr>
";

$successCount = 0;
$errorCount = 0;

foreach ($oldPresentations as $presentation) {
    $id = $presentation['id'] ?? 'unknown';
    
    try {
        // Add metadata if missing
        if (!isset($presentation['created_at'])) {
            $presentation['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($presentation['source'])) {
            $presentation['source'] = 'template';
        }
        
        // Save using new storage system
        $storage->save($presentation);
        
        echo "<div class='log success'>✅ Migrated: {$presentation['title']} (ID: $id)</div>";
        $successCount++;
        
    } catch (Exception $e) {
        echo "<div class='log error'>❌ Error migrating ID: $id - {$e->getMessage()}</div>";
        $errorCount++;
    }
}

echo "<hr>
<h2>Migration Complete!</h2>
<ul>
    <li class='success'>✅ Successfully migrated: $successCount presentations</li>
    " . ($errorCount > 0 ? "<li class='error'>❌ Errors: $errorCount</li>" : "") . "
</ul>

<h3>Next Steps:</h3>
<ol>
    <li><strong>Verify:</strong> Check that presentations_index.json exists in data/</li>
    <li><strong>Verify:</strong> Check that individual .json files exist in uploads/presentations/</li>
    <li><strong>Backup:</strong> The old presentations.json will be renamed to presentations_backup.json</li>
    <li><strong>Test:</strong> Visit <a href='slides.php'>Slides Library</a> to verify all presentations appear</li>
</ol>
";

// Backup old file
$oldFile = __DIR__ . '/../data/presentations.json';
$backupFile = __DIR__ . '/../data/presentations_backup.json';

if (file_exists($oldFile)) {
    if (rename($oldFile, $backupFile)) {
        echo "<div class='log success'>✅ Backed up presentations.json → presentations_backup.json</div>";
    } else {
        echo "<div class='log error'>❌ Could not backup presentations.json (please backup manually)</div>";
    }
}

echo "
<p><a href='slides.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Go to Slides Library</a></p>
</body>
</html>
";
