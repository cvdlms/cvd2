<?php
$file = __DIR__ . '/data/html_templates_metadata.json';
$content = file_get_contents($file);

echo "File size: " . strlen($content) . " bytes<br><br>";

// Try to find the error
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON ERROR: " . json_last_error_msg() . "<br>";
    echo "Error at position: " . json_last_error() . "<br><br>";
    
    // Try to find approximate line
    $lines = explode("\n", $content);
    echo "Total lines: " . count($lines) . "<br><br>";
    
    // Look for common errors
    echo "<h3>Checking for common issues:</h3>";
    
    $brackets = ['open' => 0, 'close' => 0];
    $braces = ['open' => 0, 'close' => 0];
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $brackets['open'] += substr_count($line, '[');
        $brackets['close'] += substr_count($line, ']');
        $braces['open'] += substr_count($line, '{');
        $braces['close'] += substr_count($line, '}');
        
        // Check for lines ending with ] but not followed by comma or }
        if (preg_match('/^\s*\]\s*$/', $line) && isset($lines[$i+1])) {
            $nextLine = trim($lines[$i+1]);
            if ($nextLine !== '' && !preg_match('/^[,}\]]/', $nextLine)) {
                echo "⚠️ Line " . ($i+1) . ": ] not followed by comma, might need comma<br>";
                echo "Current: " . htmlspecialchars($line) . "<br>";
                echo "Next: " . htmlspecialchars($nextLine) . "<br><br>";
            }
        }
    }
    
    echo "<br>Brackets: [ = {$brackets['open']}, ] = {$brackets['close']}<br>";
    echo "Braces: { = {$braces['open']}, } = {$braces['close']}<br>";
} else {
    echo "✅ JSON VALID<br>";
    echo "Categories: " . count($data['categories']) . "<br>";
    echo "Templates: " . count($data['templates']) . "<br>";
}
