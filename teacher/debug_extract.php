<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Extract Word Text</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        h1 { color: #333; }
        .upload-box { 
            background: #f5f5f5; 
            padding: 20px; 
            border-radius: 8px;
            margin: 20px 0;
        }
        .result-box { 
            background: #e8f5e9; 
            padding: 20px; 
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4caf50;
        }
        .error-box { 
            background: #ffebee; 
            padding: 20px; 
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f44336;
        }
        pre { 
            background: white; 
            padding: 15px; 
            overflow-x: auto;
            max-height: 400px;
            border: 1px solid #ddd;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        button { 
            background: #2196f3; 
            color: white; 
            padding: 10px 20px; 
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #1976d2; }
        .info { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <h1>🔍 Debug: Kiểm tra Text Extract từ Word</h1>
    
    <div class="upload-box">
        <h3>Bước 1: Upload file Word</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="word_file" accept=".docx" required>
            <button type="submit">📤 Upload và Kiểm tra</button>
        </form>
        <p class="info">Upload file .docx để xem PHPWord extract được text như thế nào</p>
    </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['word_file'])) {
    require_once '../vendor/autoload.php';
    
    if ($_FILES['word_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error-box"><h3>❌ Lỗi Upload</h3><p>Không thể upload file.</p></div>';
    } else {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($_FILES['word_file']['tmp_name']);
            
            echo '<div class="result-box">';
            echo '<h3>✅ Upload thành công: ' . htmlspecialchars($_FILES['word_file']['name']) . '</h3>';
            echo '</div>';
            
            // Extract text
            $fullText = '';
            $sectionCount = 0;
            $elementCount = 0;
            
            foreach ($phpWord->getSections() as $section) {
                $sectionCount++;
                foreach ($section->getElements() as $element) {
                    $elementCount++;
                    
                    if (method_exists($element, 'getText')) {
                        $fullText .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $fullText .= $childElement->getText() . "\n";
                            } elseif (method_exists($childElement, 'getElements')) {
                                // Handle nested elements (tables, etc.)
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
            
            echo '<div class="result-box">';
            echo '<h3>📊 Thống kê:</h3>';
            echo '<p><strong>Số Sections:</strong> ' . $sectionCount . '</p>';
            echo '<p><strong>Số Elements:</strong> ' . $elementCount . '</p>';
            echo '<p><strong>Độ dài text:</strong> ' . strlen($fullText) . ' ký tự</p>';
            echo '<p><strong>Số dòng:</strong> ' . substr_count($fullText, "\n") . '</p>';
            echo '</div>';
            
            if (empty(trim($fullText))) {
                echo '<div class="error-box">';
                echo '<h3>⚠️ CẢNH BÁO</h3>';
                echo '<p><strong>PHPWord KHÔNG EXTRACT ĐƯỢC TEXT!</strong></p>';
                echo '<p>Nguyên nhân có thể:</p>';
                echo '<ul>';
                echo '<li>File Word được tạo từ phần mềm khác (không phải Microsoft Word)</li>';
                echo '<li>File có cấu trúc đặc biệt (TextBox, Shape, Image chứa text)</li>';
                echo '<li>File bị lỗi hoặc corrupt</li>';
                echo '</ul>';
                echo '<p><strong>Giải pháp:</strong></p>';
                echo '<ol>';
                echo '<li>Mở file bằng Microsoft Word</li>';
                echo '<li>Ctrl+A (chọn tất cả) → Ctrl+C (copy)</li>';
                echo '<li>Tạo file Word MỚI → Ctrl+V (paste)</li>';
                echo '<li>Save As → format .docx</li>';
                echo '<li>Upload lại file mới</li>';
                echo '</ol>';
                echo '</div>';
            } else {
                echo '<div class="result-box">';
                echo '<h3>📄 Text được extract:</h3>';
                echo '<pre>' . htmlspecialchars($fullText) . '</pre>';
                echo '</div>';
                
                // Test parsing
                echo '<div class="result-box">';
                echo '<h3>🧪 Test Parsing:</h3>';
                
                // Pattern checks
                $patterns = [
                    'Chủ đề:' => preg_match_all('/^Chủ đề:/umi', $fullText),
                    'Bài học:' => preg_match_all('/^Bài học:/umi', $fullText),
                    'Câu \d+:' => preg_match_all('/^Câu\s+\d+/umi', $fullText),
                    'A\)' => preg_match_all('/^A[.)]/umi', $fullText),
                    '\[Mức độ:' => preg_match_all('/\[Mức độ:/ui', $fullText),
                    '\[Loại:' => preg_match_all('/\[Loại:/ui', $fullText),
                    'Dấu *' => substr_count($fullText, '*'),
                ];
                
                echo '<table border="1" cellpadding="5" style="background:white;">';
                echo '<tr><th>Pattern</th><th>Số lần xuất hiện</th></tr>';
                foreach ($patterns as $pattern => $count) {
                    $color = $count > 0 ? 'green' : 'red';
                    echo "<tr><td><code>$pattern</code></td><td style='color:$color;'><strong>$count</strong></td></tr>";
                }
                echo '</table>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error-box">';
            echo '<h3>❌ Lỗi xử lý file</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
    }
}
?>

    <div class="info" style="margin-top: 40px; padding: 20px; background: #f5f5f5; border-radius: 8px;">
        <h3>💡 Hướng dẫn sử dụng:</h3>
        <ol>
            <li>Upload file Word (.docx) của bạn</li>
            <li>Xem phần "Text được extract" → Kiểm tra xem có đúng nội dung không</li>
            <li>Xem bảng Pattern → Kiểm tra các pattern có được phát hiện không</li>
            <li>Nếu "Text được extract" trống hoặc sai → File Word có vấn đề về cấu trúc</li>
        </ol>
        
        <h4>⚠️ Nếu không extract được text:</h4>
        <p>Hãy tạo file Word mới theo cách sau:</p>
        <ol>
            <li>Mở Microsoft Word (không dùng Google Docs, WPS Office)</li>
            <li>Paste nội dung trực tiếp vào (không dùng TextBox, Table)</li>
            <li>Save as .docx</li>
            <li>Upload lại</li>
        </ol>
    </div>

</body>
</html>
