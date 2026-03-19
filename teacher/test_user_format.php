<?php
// Test parser với format của user

$testText = <<<'EOD'
Chủ đề: Chủ đề E. Ứng dụng tin học
Bài học: Soạn thảo văn bản và phần mềm trình chiếu nâng cao

Câu 1: [Mức độ: NB] [Loại: single] Trong phần mềm soạn thảo văn bản, chức năng Styles dùng để làm gì?
A. Thay đổi màu nền trang
B. Áp dụng nhanh định dạng cho tiêu đề và đoạn văn *
C. Chèn hình ảnh vào văn bản
D. Kiểm tra lỗi chính tả
---
Câu 2: [Mức độ: NB] [Loại: single] Trong Microsoft Word, để tạo mục lục tự động, người dùng cần làm gì trước?
A. Chèn hình ảnh
B. Áp dụng các kiểu tiêu đề (Heading) *
C. Chèn bảng
D. Định dạng lề trang
---
Câu 12: Trong Word, phần Header và Footer thường dùng để:
A. Chèn hình ảnh
B. Tạo bảng
C. Chèn thông tin đầu và cuối trang *
D. Thay đổi font chữ
---
EOD;

// Include parser function
require_once 'question_bank_handlers.php';

// Parse
$parsed = parseQuestionsFromText($testText);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<h2>Kết quả parse:</h2>";
echo "<p>Số câu hỏi tìm thấy: <strong>" . count($parsed) . "</strong></p>";

if (empty($parsed)) {
    echo "<p style='color:red;'>❌ KHÔNG TÌM THẤY CÂU HỎI NÀO!</p>";
    echo "<h3>Debug - Text lines:</h3>";
    $lines = explode("\n", $testText);
    foreach ($lines as $idx => $line) {
        echo "<div>" . ($idx+1) . ": <code>" . htmlspecialchars($line) . "</code></div>";
    }
} else {
    echo "<pre>";
    print_r($parsed);
    echo "</pre>";
}

echo "</body></html>";
?>
