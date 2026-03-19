<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Test Import Directly</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { background: #f5f5f5; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        pre { background: white; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Test Import Trực tiếp</h1>
    
    <div class="box">
        <h3>Upload file Word để test</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="word_file" accept=".docx" required>
            <br><br>
            <label><input type="checkbox" name="debug" value="1" checked> Debug mode (xem chi tiết)</label>
            <br><br>
            <button type="submit">📤 Test Import</button>
        </form>
    </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['word_file'])) {
    require_once '../vendor/autoload.php';
    require_once 'question_bank_handlers.php';
    
    $debug = isset($_POST['debug']);
    
    try {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($_FILES['word_file']['tmp_name']);
        
        // Extract text
        $fullText = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $fullText .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $fullText .= $childElement->getText() . "\n";
                        } elseif (method_exists($childElement, 'getElements')) {
                            foreach ($childElement->getElements() as $nestedElement) {
                                if (method_exists($nestedElement, 'getText')) {
                                    $fullText .= $nestedElement->getText() . "\n";
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if ($debug) {
            echo '<div class="box">';
            echo '<h3>📄 Text Extract:</h3>';
            echo '<pre>' . htmlspecialchars($fullText) . '</pre>';
            echo '<p>Độ dài: ' . strlen($fullText) . ' ký tự</p>';
            echo '</div>';
        }
        
        // Parse
        $parsedQuestions = parseQuestionsFromText($fullText);
        
        if (empty($parsedQuestions)) {
            echo '<div class="box error">';
            echo '<h3>❌ Không parse được câu hỏi nào!</h3>';
            echo '<p>Đây chính là vấn đề mà bạn gặp phải khi import.</p>';
            
            if ($debug) {
                echo '<h4>Debug Patterns:</h4>';
                echo '<ul>';
                echo '<li>Chủ đề: ' . preg_match_all('/^Chủ đề:/umi', $fullText) . '</li>';
                echo '<li>Bài học: ' . preg_match_all('/^Bài học:/umi', $fullText) . '</li>';
                echo '<li>Câu X: ' . preg_match_all('/^Câu\s+\d+/umi', $fullText) . '</li>';
                echo '<li>[Mức độ: ' . preg_match_all('/\[Mức độ:/ui', $fullText) . '</li>';
                echo '<li>[Loại: ' . preg_match_all('/\[Loại:/ui', $fullText) . '</li>';
                echo '<li>A): ' . preg_match_all('/^A[.)]/umi', $fullText) . '</li>';
                echo '<li>B): ' . preg_match_all('/^B[.)]/umi', $fullText) . '</li>';
                echo '<li>C): ' . preg_match_all('/^C[.)]/umi', $fullText) . '</li>';
                echo '<li>D): ' . preg_match_all('/^D[.)]/umi', $fullText) . '</li>';
                echo '<li>Dấu *: ' . substr_count($fullText, '*') . '</li>';
                echo '</ul>';
                
                // Test từng line
                echo '<h4>Line by line analysis:</h4>';
                $lines = explode("\n", $fullText);
                echo '<table border="1" cellpadding="5" style="background:white; width:100%;">';
                echo '<tr><th>#</th><th>Line</th><th>Matched Pattern</th></tr>';
                foreach ($lines as $idx => $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $pattern = 'Unknown';
                    if (preg_match('/^Chủ đề:/ui', $line)) $pattern = '<span style="color:blue;">Chủ đề</span>';
                    elseif (preg_match('/^Bài học:/ui', $line)) $pattern = '<span style="color:blue;">Bài học</span>';
                    elseif (preg_match('/^Câu\s+\d+/ui', $line)) $pattern = '<span style="color:green;">Câu hỏi</span>';
                    elseif (preg_match('/^([A-D])[.)]/ui', $line)) $pattern = '<span style="color:orange;">Option</span>';
                    elseif ($line === '---') $pattern = '<span style="color:gray;">Separator</span>';
                    
                    echo '<tr>';
                    echo '<td>' . ($idx + 1) . '</td>';
                    echo '<td style="max-width:500px; word-wrap:break-word;"><code>' . htmlspecialchars($line) . '</code></td>';
                    echo '<td>' . $pattern . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="box success">';
            echo '<h3>✅ Parse thành công!</h3>';
            
            $totalQuestions = 0;
            foreach ($parsedQuestions as $topic) {
                $totalQuestions += count($topic['questions']);
            }
            
            echo '<p><strong>Số topics:</strong> ' . count($parsedQuestions) . '</p>';
            echo '<p><strong>Tổng câu hỏi:</strong> ' . $totalQuestions . '</p>';
            echo '</div>';
            
            if ($debug) {
                echo '<div class="box">';
                echo '<h3>📋 Chi tiết câu hỏi:</h3>';
                foreach ($parsedQuestions as $topicData) {
                    echo '<h4>📚 ' . htmlspecialchars($topicData['topic']) . '</h4>';
                    echo '<p><em>' . htmlspecialchars($topicData['lesson']) . '</em></p>';
                    echo '<table border="1" cellpadding="5" style="background:white; width:100%;">';
                    echo '<tr><th>STT</th><th>Câu hỏi</th><th>Options</th><th>Đúng</th><th>Level</th><th>Type</th></tr>';
                    foreach ($topicData['questions'] as $idx => $q) {
                        echo '<tr>';
                        echo '<td>' . ($idx + 1) . '</td>';
                        echo '<td>' . htmlspecialchars($q['question']) . '</td>';
                        echo '<td><ul style="margin:0;">';
                        foreach ($q['options'] as $optIdx => $opt) {
                            $correct = in_array($optIdx, $q['correct_answers']) ? ' ✓' : '';
                            echo '<li>' . chr(65 + $optIdx) . '. ' . htmlspecialchars($opt) . $correct . '</li>';
                        }
                        echo '</ul></td>';
                        echo '<td>' . implode(', ', array_map(fn($i) => chr(65+$i), $q['correct_answers'])) . '</td>';
                        echo '<td>' . htmlspecialchars($q['level']) . '</td>';
                        echo '<td>' . htmlspecialchars($q['type']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                echo '</div>';
                
                echo '<div class="box">';
                echo '<h3>📝 JSON Output:</h3>';
                echo '<pre>' . json_encode($parsedQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                echo '</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="box error">';
        echo '<h3>❌ Lỗi: ' . htmlspecialchars($e->getMessage()) . '</h3>';
        echo '</div>';
    }
}
?>

</body>
</html>
