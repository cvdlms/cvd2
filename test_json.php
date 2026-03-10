<?php
$json = file_get_contents(__DIR__ . '/data/html_templates_metadata.json');
$data = json_decode($json, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ JSON VALID<br><br>";
    echo "Categories: " . count($data['categories']) . "<br>";
    echo "Templates: " . count($data['templates']) . "<br><br>";
    
    echo "<h3>Categories:</h3>";
    foreach ($data['categories'] as $cat) {
        echo "- {$cat['name']} ({$cat['id']})<br>";
    }
    
    echo "<br><h3>Templates by Category:</h3>";
    $byCategory = [];
    foreach ($data['templates'] as $t) {
        $byCategory[$t['category']][] = $t['name'];
    }
    
    foreach ($byCategory as $cat => $templates) {
        echo "<strong>$cat:</strong> " . implode(', ', $templates) . "<br>";
    }
} else {
    echo "❌ JSON ERROR: " . json_last_error_msg();
}
