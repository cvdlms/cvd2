<?php
// Test parser với text đã extract
require_once 'question_bank_handlers.php';

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
Câu 3: [Mức độ: NB] [Loại: single] Trong PowerPoint, hiệu ứng chuyển giữa các trang chiếu được gọi là:
A. Animation
B. Transition *
C. Slide Show
D. Design
---
EOD;

$parsed = parseQuestionsFromText($testText);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Parse Result</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4caf50; color: white; }
    </style>
</head>
<body>
    <h1>🧪 Test Kết quả Parse</h1>
    
    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h3>📊 Kết quả:</h3>
        <?php if (empty($parsed)): ?>
            <p class="error">❌ KHÔNG PARSE ĐƯỢC CÂU HỎI NÀO!</p>
            <p>Text đúng format nhưng parser không hoạt động. Có thể có lỗi trong code parser.</p>
        <?php else: ?>
            <p class="success">✅ Parse thành công <?php echo count($parsed); ?> topic(s)</p>
            <?php 
            $totalQuestions = 0;
            foreach ($parsed as $topic) {
                $totalQuestions += count($topic['questions']);
            }
            ?>
            <p class="success">✅ Tổng số câu hỏi: <?php echo $totalQuestions; ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($parsed)): ?>
        <h3>📋 Chi tiết câu hỏi:</h3>
        <?php foreach ($parsed as $topicData): ?>
            <div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #4caf50;">
                <h4>📚 <?php echo htmlspecialchars($topicData['topic']); ?></h4>
                <p><strong>Bài học:</strong> <?php echo htmlspecialchars($topicData['lesson']); ?></p>
                
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Câu hỏi</th>
                            <th>Đáp án đúng</th>
                            <th>Mức độ</th>
                            <th>Loại</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topicData['questions'] as $idx => $q): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($q['question']); ?></strong>
                                    <ul style="margin: 5px 0;">
                                        <?php foreach ($q['options'] as $optIdx => $opt): ?>
                                            <li style="<?php echo in_array($optIdx, $q['correct_answers']) ? 'color: green; font-weight: bold;' : ''; ?>">
                                                <?php echo chr(65 + $optIdx) . '. ' . htmlspecialchars($opt); ?>
                                                <?php if (in_array($optIdx, $q['correct_answers'])): ?>
                                                    ✓
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <?php 
                                    $correctLetters = array_map(function($idx) {
                                        return chr(65 + $idx);
                                    }, $q['correct_answers']);
                                    echo implode(', ', $correctLetters);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($q['level']); ?></td>
                                <td><?php echo htmlspecialchars($q['type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        
        <h3>📝 JSON Output (để import):</h3>
        <pre><?php echo json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
    <?php endif; ?>

    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4>🔍 Debug Info:</h4>
        <p><strong>Kiểm tra các pattern:</strong></p>
        <ul>
            <li>Chủ đề: <?php echo preg_match_all('/^Chủ đề:/umi', $testText); ?> lần</li>
            <li>Bài học: <?php echo preg_match_all('/^Bài học:/umi', $testText); ?> lần</li>
            <li>Câu X: <?php echo preg_match_all('/^Câu\s+\d+/umi', $testText); ?> lần</li>
            <li>[Mức độ: <?php echo preg_match_all('/\[Mức độ:/ui', $testText); ?> lần</li>
            <li>[Loại: <?php echo preg_match_all('/\[Loại:/ui', $testText); ?> lần</li>
            <li>A): <?php echo preg_match_all('/^A[.)]/umi', $testText); ?> lần</li>
            <li>Dấu *: <?php echo substr_count($testText, '*'); ?> lần</li>
        </ul>
    </div>

</body>
</html>
