<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Simple Test - Slides Loading</title>
    <style>
        iframe { width: 100%; height: 400px; border: 1px solid #ccc; margin: 10px 0; }
        .info { background: #f0f0f0; padding: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>Test Slides Loading - Presentation: pres_1770276085_698444f521f44</h1>
    
    <?php
    $presentationId = 'pres_1770276085_698444f521f44';
    $metadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
    $presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];
    
    if (isset($presentations[$presentationId])) {
        $presentation = $presentations[$presentationId];
        
        // Load slide contents
        foreach ($presentation['slides'] as $index => &$slide) {
            if (isset($slide['file_path'])) {
                $filePath = __DIR__ . '/../' . $slide['file_path'];
                echo "<div class='info'>";
                echo "<strong>Slide " . ($index + 1) . ":</strong> " . htmlspecialchars($slide['title']) . "<br>";
                echo "Template: " . htmlspecialchars($slide['template'] ?? 'N/A') . "<br>";
                echo "File: " . htmlspecialchars($slide['file_path']) . "<br>";
                echo "Exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "<br>";
                
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    $slide['content'] = $content;
                    echo "Length: " . strlen($content) . " bytes<br>";
                    echo "Title in HTML: " . htmlspecialchars(substr($content, 0, 100)) . "...<br>";
                }
                echo "</div>";
                
                // Display in iframe
                if (isset($slide['content'])) {
                    echo "<iframe id='iframe-$index'></iframe>";
                    echo "<script>
                        (function() {
                            const iframe = document.getElementById('iframe-$index');
                            const content = " . json_encode($slide['content'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";
                            const doc = iframe.contentDocument || iframe.contentWindow.document;
                            doc.open();
                            doc.write(content);
                            doc.close();
                        })();
                    </script>";
                }
            }
        }
    } else {
        echo "Presentation not found!";
    }
    ?>
</body>
</html>
