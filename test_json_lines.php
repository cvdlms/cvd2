<?php
$file = __DIR__ . '/data/html_templates_metadata.json';
$content = file_get_contents($file);

// Check for invalid characters or encoding issues
$lines = explode("\n", $content);

echo "Checking each line for issues...<br><br>";

for ($i = 0; $i < min(50, count($lines)); $i++) {
    $line = $lines[$i];
    $trimmed = trim($line);
    
    if (empty($trimmed)) continue;
    
    // Check for invalid quotes with hex codes
    if (strpos($line, "\xe2\x80\x9c") !== false || strpos($line, "\xe2\x80\x9d") !== false) {
        echo "⚠️ Line " . ($i+1) . ": Has curly quotes<br>";
        echo htmlspecialchars($line) . "<br><br>";
    }
    
    // Try to decode just this line if it looks like JSON
    if (preg_match('/^\s*"[^"]+"\s*:/', $line)) {
        $testJson = '{' . $line . '}';
        json_decode($testJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ Line " . ($i+1) . ": " . json_last_error_msg() . "<br>";
            echo htmlspecialchars($line) . "<br><br>";
        }
    }
}

// Try to parse in chunks
echo "<hr><h3>Testing JSON structure:</h3>";
$testChunks = [
    'First 500 chars' => substr($content, 0, 500),
    'Categories section' => substr($content, 0, strpos($content, '"templates"')),
];

foreach ($testChunks as $name => $chunk) {
    if ($chunk) {
        // Complete the JSON for testing
        $test = rtrim($chunk, ',') . (strpos($chunk, '[') !== false? ']' : '') . '}';
        json_decode($test);
        echo "$name: " . (json_last_error() === JSON_ERROR_NONE ? '✅' : '❌ ' . json_last_error_msg()) . "<br>";
    }
}
