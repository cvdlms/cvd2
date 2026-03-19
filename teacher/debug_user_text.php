<?php
// Test với CHÍNH XÁC text của user
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
Câu 4: [Mức độ: NB] [Loại: single] Trong PowerPoint, Animation dùng để:
A. Tạo hiệu ứng cho đối tượng trên trang chiếu *
B. Chuyển trang chiếu
C. Tạo bảng
D. Thay đổi màu nền
---
Câu 5: [Mức độ: NB] [Loại: single] Khi trình chiếu, phím nào thường dùng để bắt đầu trình chiếu từ trang đầu tiên?
A. F1
B. F5 *
C. F7
D. F9
---
Câu 6: [Mức độ: NB] [Loại: single] Trong Word, chức năng Find and Replace dùng để:
A. Chèn ảnh
B. Tìm kiếm và thay thế nội dung *
C. Kiểm tra chính tả
D. Chèn bảng
---
Câu 7: [Mức độ: TH] [Loại: single] Trong PowerPoint, muốn chèn video vào bài trình chiếu ta chọn:
A. Insert → Video *
B. Home → Video
C. Design → Video
D. Review → Video
---
Câu 8: [Mức độ: TH] [Loại: single] Trong Word, việc chia văn bản thành nhiều cột giống báo chí sử dụng chức năng:
A. Columns *
B. Layout
C. Header
D. Styles
---
Câu 9: [Mức độ: TH] [Loại: single] Trong PowerPoint, Theme là gì?
A. Bộ hiệu ứng chuyển trang
B. Bộ mẫu định dạng gồm màu sắc, phông chữ và bố cục *
C. Một hình ảnh nền
D. Một trang chiếu
---
Câu 10: [Mức độ: TH] [Loại: single] Trong Word, phần Header và Footer thường dùng để:
A. Chèn hình ảnh
B. Tạo bảng
C. Chèn thông tin đầu và cuối trang *
D. Thay đổi font chữ
---
Câu 11: [Mức độ: TH] [Loại: single] Trong PowerPoint, muốn sao chép định dạng của một đối tượng sang đối tượng khác ta dùng:
A. Format Painter *
B. Slide Master
C. Layout
D. Animation
---
Câu 12: [Mức độ: NB] [Loại: single]Trong Word, công cụ nào giúp kiểm tra lỗi chính tả?
A. Spell Check *
B. Insert
C. Layout
D. Design
---
Câu 13: [Mức độ: TH] [Loại: single] Trong PowerPoint, Layout của slide dùng để:
A. Tạo hiệu ứng
B. Chọn bố cục nội dung của trang chiếu *
C. Chèn video
D. Chèn bảng
---
Câu 14: [Mức độ: NB] [Loại: single] Trong Word, chức năng Page Number dùng để:
A. Đánh số trang *
B. Tạo mục lục
C. Chèn hình ảnh
D. Kiểm tra lỗi chính tả
---
Câu 15: [Mức độ: NB] [Loại: single] Trong PowerPoint, để sao chép một trang chiếu, ta sử dụng:
A. Duplicate Slide *
B. Insert Slide
C. New Slide
D. Slide Layout
---
Câu 16: [Mức độ: NB] [Loại: single]Trong Word, khi muốn chèn bảng, ta chọn:
A. Insert → Table *
B. Home → Table
C. Design → Table
D. Review → Table
---
Câu 17: [Mức độ: TH] [Loại: single] Trong PowerPoint, để trình chiếu từ trang hiện tại, ta dùng phím:
A. F5
B. Shift + F5 *
C. Ctrl + F5
D. Alt + F5
---
Câu 18: [Mức độ: TH] [Loại: single] Trong bài trình chiếu, việc sử dụng quá nhiều hiệu ứng sẽ:
A. Làm bài trình chiếu chuyên nghiệp hơn
B. Giúp người xem dễ hiểu hơn
C. Làm bài trình chiếu rối mắt và khó theo dõi *
D. Không ảnh hưởng gì
EOD;

$parsed = parseQuestionsFromText($testText);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug User's Text</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 1400px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #4caf50; color: white; }
        .problem { background: #ffebee; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Debug Text của User</h1>
    
    <?php if (empty($parsed)): ?>
        <div class="error">
            <h3>❌ KHÔNG PARSE ĐƯỢC - ĐÂY LÀ VẤN ĐỀ!</h3>
            <p>Text này chính là text trong file Word của bạn, nhưng parser không hoạt động.</p>
        </div>
        
        <div class="warning">
            <h3>🔍 Phân tích từng dòng:</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Nội dung dòng</th>
                        <th style="width:150px;">Pattern Match</th>
                        <th style="width:200px;">Vấn đề</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $lines = explode("\n", $testText);
                    $questionCount = 0;
                    $optionCount = 0;
                    $inQuestion = false;
                    
                    foreach ($lines as $idx => $line): 
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $pattern = '';
                        $problem = '';
                        $rowClass = '';
                        
                        if (preg_match('/^Chủ đề:/ui', $line)) {
                            $pattern = '<span style="color:blue;">✓ Chủ đề</span>';
                        } elseif (preg_match('/^Bài học:/ui', $line)) {
                            $pattern = '<span style="color:blue;">✓ Bài học</span>';
                        } elseif (preg_match('/^Câu\s+(\d+)[\s.:]*(.*)$/ui', $line, $m)) {
                            $pattern = '<span style="color:green;">✓ Câu hỏi</span>';
                            $questionCount++;
                            $inQuestion = true;
                            $optionCount = 0;
                            
                            // Check metadata
                            $metaLine = $m[2];
                            $hasLevel = preg_match('/\[Mức độ:/ui', $metaLine);
                            $hasType = preg_match('/\[Loại:/ui', $metaLine);
                            
                            // Check spacing issue
                            if (preg_match('/\]\s*[A-ZĐ]/u', $metaLine, $spaceCheck)) {
                                if (!preg_match('/\]\s+[A-ZĐ]/u', $metaLine)) {
                                    $problem = '⚠️ Thiếu khoảng trắng sau ]';
                                    $rowClass = 'problem';
                                }
                            }
                            
                            if (!$hasLevel) {
                                $problem .= ' ⚠️ Thiếu [Mức độ:]';
                                $rowClass = 'problem';
                            }
                            if (!$hasType) {
                                $problem .= ' ⚠️ Thiếu [Loại:]';
                                $rowClass = 'problem';
                            }
                        } elseif (preg_match('/^([A-D])[.)]/ui', $line)) {
                            $pattern = '<span style="color:orange;">✓ Option</span>';
                            $optionCount++;
                            
                            // Check for *
                            if (strpos($line, '*') === false && $inQuestion) {
                                // This is OK, not every option needs *
                            }
                        } elseif ($line === '---') {
                            $pattern = '<span style="color:gray;">✓ Separator</span>';
                            
                            // Check if previous question had 4 options and at least 1 correct
                            if ($optionCount < 4) {
                                $problem = "⚠️ Câu trước chỉ có $optionCount options";
                                $rowClass = 'problem';
                            }
                            $inQuestion = false;
                        } else {
                            $pattern = '<span style="color:red;">✗ Unknown</span>';
                            $rowClass = 'problem';
                        }
                        
                        echo '<tr class="' . $rowClass . '">';
                        echo '<td>' . ($idx + 1) . '</td>';
                        echo '<td><code>' . htmlspecialchars(substr($line, 0, 100)) . (strlen($line) > 100 ? '...' : '') . '</code></td>';
                        echo '<td>' . $pattern . '</td>';
                        echo '<td>' . $problem . '</td>';
                        echo '</tr>';
                    endforeach; 
                    ?>
                </tbody>
            </table>
            
            <h4>📊 Tóm tắt:</h4>
            <ul>
                <li><strong>Số câu hỏi phát hiện:</strong> <?php echo $questionCount; ?></li>
                <li><strong>Pattern "Chủ đề:":</strong> <?php echo preg_match_all('/^Chủ đề:/umi', $testText); ?></li>
                <li><strong>Pattern "Bài học:":</strong> <?php echo preg_match_all('/^Bài học:/umi', $testText); ?></li>
                <li><strong>Pattern "Câu X:":</strong> <?php echo preg_match_all('/^Câu\s+\d+/umi', $testText); ?></li>
                <li><strong>Pattern "A)":</strong> <?php echo preg_match_all('/^A[.)]/umi', $testText); ?></li>
                <li><strong>Dấu *:</strong> <?php echo substr_count($testText, '*'); ?></li>
            </ul>
        </div>
        
    <?php else: ?>
        <div class="success">
            <h3>✅ Parse thành công!</h3>
            <?php 
            $totalQuestions = 0;
            foreach ($parsed as $topic) {
                $totalQuestions += count($topic['questions']);
            }
            ?>
            <p><strong>Số câu hỏi:</strong> <?php echo $totalQuestions; ?></p>
        </div>
        
        <div class="warning">
            <h3>📋 Chi tiết câu hỏi:</h3>
            <?php foreach ($parsed as $topicData): ?>
                <h4><?php echo htmlspecialchars($topicData['topic']); ?></h4>
                <p><em><?php echo htmlspecialchars($topicData['lesson']); ?></em></p>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Câu hỏi</th>
                            <th>Đáp án đúng</th>
                            <th>Level</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topicData['questions'] as $idx => $q): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($q['question']); ?></strong>
                                    <ul style="margin: 5px 0; font-size: 12px;">
                                        <?php foreach ($q['options'] as $optIdx => $opt): ?>
                                            <li style="<?php echo in_array($optIdx, $q['correct_answers']) ? 'color:green; font-weight:bold;' : ''; ?>">
                                                <?php echo chr(65 + $optIdx) . '. ' . htmlspecialchars($opt); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td><?php echo implode(', ', array_map(fn($i) => chr(65+$i), $q['correct_answers'])); ?></td>
                                <td><?php echo htmlspecialchars($q['level']); ?></td>
                                <td><?php echo htmlspecialchars($q['type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>
