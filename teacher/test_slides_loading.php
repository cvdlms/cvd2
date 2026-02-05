<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Slides Loading</title>
</head>
<body>
    <h1>Testing Slide Content Loading</h1>
    
    <div id="output"></div>
    
    <script>
        <?php
        session_name('CVD_TEACHER_SESSION');
        session_start();
        
        $presentationId = 'pres_1770276085_698444f521f44';
        $metadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
        $presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];
        
        if (isset($presentations[$presentationId])) {
            $presentation = $presentations[$presentationId];
            
            // Load slide contents
            foreach ($presentation['slides'] as &$slide) {
                if (isset($slide['file_path'])) {
                    $filePath = __DIR__ . '/../' . $slide['file_path'];
                    if (file_exists($filePath)) {
                        $slide['content'] = file_get_contents($filePath);
                    }
                }
            }
            
            echo "const slidesContent = " . json_encode(array_map(function($slide) {
                return $slide['content'] ?? '';
            }, $presentation['slides']), JSON_UNESCAPED_UNICODE) . ";\n";
        }
        ?>
        
        let output = '<h2>Slides Content Analysis</h2>';
        slidesContent.forEach((content, index) => {
            output += `<h3>Slide ${index + 1}</h3>`;
            output += `<p>Length: ${content.length} chars</p>`;
            output += `<pre>${content.substring(0, 200)}...</pre>`;
            output += '<hr>';
        });
        
        document.getElementById('output').innerHTML = output;
    </script>
</body>
</html>
