<?php
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/premium_helper.php';

// Check Premium status
$username = $_SESSION['username'];
if (!isPremiumUser($username)) {
    die('Chức năng này chỉ dành cho tài khoản Premium');
}

if (!isset($_GET['file']) || !isset($_GET['grade']) || !isset($_GET['subject_id'])) {
    die('Thiếu thông tin đề thi');
}

$file = basename($_GET['file']);
$grade = $_GET['grade'];
$subjectId = (int)$_GET['subject_id'];

// Load exam data
$examFile = __DIR__ . "/exams/{$grade}/subject_{$subjectId}/" . $file;
if (!file_exists($examFile)) {
    die('Không tìm thấy đề thi');
}

$examData = json_decode(file_get_contents($examFile), true);
if (!$examData) {
    die('Không thể đọc dữ liệu đề thi');
}

// Load subjects for name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjectName = 'Chưa xác định';
foreach ($subjects as $subj) {
    if ($subj['id'] == $subjectId) {
        $subjectName = $subj['name'];
        break;
    }
}

$gradeLabels = [
    'khoi6' => 'Khối 6',
    'khoi7' => 'Khối 7',
    'khoi8' => 'Khối 8',
    'khoi9' => 'Khối 9',
];
$gradeLabel = $gradeLabels[$grade] ?? $grade;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($examData['test_name']); ?></title>
    <style>
        @media print {
            .no-print { 
                display: none !important; 
                visibility: hidden !important;
            }
            .action-buttons {
                display: none !important;
            }
            @page { 
                margin: 2cm; 
                size: A4;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 13pt;
            line-height: 1.6;
            color: #000;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 21cm;
            margin: 20px auto;
            background: white;
            padding: 2cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        @media print {
            body { background: white; }
            .container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
        
        .header {
            text-align: left;
            margin-bottom: 20px;
        }
        
        .header-line {
            font-size: 12pt;
            margin-bottom: 5px;
        }
        
        .title {
            text-align: center;
            margin: 20px 0;
        }
        
        .title h1 {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .title .subtitle {
            font-size: 13pt;
            margin-bottom: 5px;
        }
        
        .student-info {
            margin: 20px 0;
            font-size: 13pt;
        }
        
        .student-info p {
            margin: 5px 0;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 10px 0;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        
        .question {
            margin: 15px 0;
            page-break-inside: avoid;
        }
        
        .question-header {
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .question-level {
            font-size: 10pt;
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: normal;
        }
        
        .question-text {
            margin-bottom: 8px;
        }
        
        .options {
            margin-left: 30px;
        }
        
        .option {
            margin: 5px 0;
        }
        
        .answer-key {
            page-break-before: always;
        }
        
        .answer-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .answer-table th,
        .answer-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        
        .answer-table th {
            background: #e0e0e0;
            font-weight: bold;
        }
        
        .correct-answer {
            font-weight: bold;
            color: #198754;
        }
        
        .summary {
            margin-top: 20px;
            font-size: 13pt;
        }
        
        .summary p {
            margin: 5px 0;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0d6efd;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14pt;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #0b5ed7;
        }
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        
        .action-buttons button {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14pt;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .action-buttons button:hover {
            background: #0b5ed7;
        }
        
        .action-buttons .back-btn {
            background: #6c757d;
        }
        
        .action-buttons .back-btn:hover {
            background: #5c636a;
        }
    </style>
    
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                packages: {'[+]': ['mhchem']}
            },
            loader: {
                load: ['[tex]/mhchem']
            },
            startup: {
                pageReady: () => {
                    return MathJax.startup.defaultPageReady().then(() => {
                        console.log('MathJax loaded and ready');
                    });
                }
            }
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>
</head>
<body>
    <div class="action-buttons no-print">
        <button class="back-btn" onclick="window.close()">← Quay lại</button>
        <button onclick="window.print()">🖨️ In / Lưu PDF</button>
    </div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-line">TRƯỜNG THCS ___________________</div>
            <div class="header-line">Tổ: ___________________</div>
        </div>
        
        <!-- Title -->
        <div class="title">
            <h1><?php echo htmlspecialchars($examData['test_name']); ?></h1>
            <div class="subtitle">Môn: <?php echo htmlspecialchars($subjectName); ?> - <?php echo htmlspecialchars($gradeLabel); ?></div>
            <div class="subtitle">Thời gian: <?php echo $examData['time_limit']; ?> phút (không kể thời gian phát đề)</div>
        </div>
        
        <!-- Student Info -->
        <div class="student-info">
            <p>Họ và tên học sinh: ................................................................ Lớp: .................</p>
            <p>Ngày thi: <?php echo date('d/m/Y'); ?></p>
        </div>
        
        <?php
        $part1 = []; // Trắc nghiệm nhiều lựa chọn
        $part2 = []; // Đúng sai
        $part3 = []; // Tự luận
        $part4 = []; // Thực hành

        foreach ($examData['questions'] as $q) {
            $t = strtolower($q['type'] ?? '');
            if ($t === 'true_false_multiple' || $t === 'true_false' || $t === 'dungsai') {
                $part2[] = $q;
            } elseif ($t === 'essay' || $t === 'tuluan' || $t === 'short_answer') {
                $part3[] = $q;
            } elseif ($t === 'practice' || $t === 'thuchanh') {
                $part4[] = $q;
            } else {
                $part1[] = $q;
            }
        }

        $calcPts = function(array $list) {
            $pts = 0;
            $hasPts = false;
            foreach ($list as $q) {
                if (isset($q['points']) && is_numeric($q['points'])) {
                    $pts += (float)$q['points'];
                    $hasPts = true;
                }
            }
            return $hasPts ? round($pts, 2) : null;
        };

        $pts1 = $calcPts($part1);
        $pts2 = $calcPts($part2);
        $pts3 = $calcPts($part3);
        $pts4 = $calcPts($part4);

        $romanNumerals = ['I', 'II', 'III', 'IV', 'V'];
        $partIdx = 0;
        ?>

        <!-- PHẦN I: TRẮC NGHIỆM NHIỀU LỰA CHỌN -->
        <?php if (!empty($part1)): ?>
            <?php 
                $roman = $romanNumerals[$partIdx++]; 
                $ptsLabel = $pts1 !== null ? " ({$pts1} điểm)" : "";
            ?>
            <div class="section-title">PHẦN <?php echo $roman; ?>. TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN<?php echo $ptsLabel; ?></div>
            <p style="font-style: italic; margin-bottom: 12px; font-size: 12pt;">
                Học sinh trả lời từ câu 1 đến câu <?php echo count($part1); ?>, mỗi câu hỏi học sinh chỉ chọn một phương án.
            </p>
            
            <?php foreach ($part1 as $idx => $question): ?>
                <div class="question">
                    <div class="question-header">
                        <span>Câu <?php echo $idx + 1; ?><?php echo !empty($question['points']) ? " (" . $question['points'] . "đ)" : ""; ?>:</span>
                        <span class="question-level"><?php echo htmlspecialchars($question['level'] ?? 'NB'); ?></span>
                    </div>
                    <div class="question-text">
                        <?php echo $question['question']; ?>
                    </div>
                    <?php if (isset($question['options']) && is_array($question['options'])): ?>
                        <div class="options">
                            <?php foreach ($question['options'] as $optIdx => $option): ?>
                                <div class="option">
                                    <strong><?php echo chr(65 + $optIdx); ?>.</strong> <?php echo $option; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PHẦN II: TRẮC NGHIỆM ĐÚNG SAI -->
        <?php if (!empty($part2)): ?>
            <?php 
                $roman = $romanNumerals[$partIdx++]; 
                $ptsLabel = $pts2 !== null ? " ({$pts2} điểm)" : "";
            ?>
            <div class="section-title">PHẦN <?php echo $roman; ?>. TRẮC NGHIỆM ĐÚNG SAI<?php echo $ptsLabel; ?></div>
            <p style="font-style: italic; margin-bottom: 12px; font-size: 12pt;">
                Học sinh trả lời từ câu 1 đến câu <?php echo count($part2); ?>. Trong mỗi ý a), b), c), d) ở mỗi câu, học sinh ghi đúng hoặc sai.
            </p>
            
            <?php foreach ($part2 as $idx => $question): ?>
                <div class="question">
                    <div class="question-header">
                        <span>Câu <?php echo $idx + 1; ?><?php echo !empty($question['points']) ? " (" . $question['points'] . "đ)" : ""; ?>:</span>
                        <span class="question-level"><?php echo htmlspecialchars($question['level'] ?? 'TH'); ?></span>
                    </div>
                    <div class="question-text">
                        <?php echo $question['question']; ?>
                    </div>
                    <?php if (!empty($question['items']) && is_array($question['items'])): ?>
                        <div class="options">
                            <?php foreach ($question['items'] as $itemIdx => $item): ?>
                                <div class="option">
                                    <strong><?php echo $item['label'] ?? chr(97 + $itemIdx); ?>)</strong> <?php echo $item['statement'] ?? $item['text'] ?? ''; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PHẦN III: TỰ LUẬN -->
        <?php if (!empty($part3)): ?>
            <?php 
                $roman = $romanNumerals[$partIdx++]; 
                $ptsLabel = $pts3 !== null ? " ({$pts3} điểm)" : "";
            ?>
            <div class="section-title">PHẦN <?php echo $roman; ?>. TỰ LUẬN<?php echo $ptsLabel; ?></div>
            
            <?php foreach ($part3 as $idx => $question): ?>
                <div class="question">
                    <div class="question-header">
                        <span>Câu <?php echo $idx + 1; ?><?php echo !empty($question['points']) ? " (" . $question['points'] . " điểm)" : ""; ?>:</span>
                        <span class="question-level"><?php echo htmlspecialchars($question['level'] ?? 'VD'); ?></span>
                    </div>
                    <div class="question-text">
                        <?php echo $question['question']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PHẦN IV: THỰC HÀNH -->
        <?php if (!empty($part4)): ?>
            <?php 
                $roman = $romanNumerals[$partIdx++]; 
                $ptsLabel = $pts4 !== null ? " ({$pts4} điểm)" : "";
            ?>
            <div class="section-title">PHẦN <?php echo $roman; ?>. THỰC HÀNH<?php echo $ptsLabel; ?></div>
            
            <?php foreach ($part4 as $idx => $question): ?>
                <div class="question">
                    <div class="question-header">
                        <span>Câu <?php echo $idx + 1; ?><?php echo !empty($question['points']) ? " (" . $question['points'] . " điểm)" : ""; ?>:</span>
                    </div>
                    <div class="question-text">
                        <?php echo $question['question']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Answer Key Section -->
        <div class="answer-key">
            <div class="section-title" style="text-align: center; margin-top: 30px;">ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM</div>
            
            <?php if (!empty($part1)): ?>
                <h3 style="font-size: 13pt; font-weight: bold; margin: 15px 0 8px;">I. ĐÁP ÁN PHẦN TRẮC NGHIỆM LỰA CHỌN</h3>
                <table class="answer-table">
                    <thead>
                        <tr>
                            <th style="width: 15%">Câu</th>
                            <th style="width: 25%">Đáp án</th>
                            <th style="width: 25%">Mức độ</th>
                            <th style="width: 35%">Điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($part1 as $idx => $question): ?>
                            <tr>
                                <td><strong><?php echo $idx + 1; ?></strong></td>
                                <td class="correct-answer">
                                    <?php 
                                        $correct = $question['correct'] ?? '';
                                        if (is_numeric($correct)) {
                                            echo chr(65 + (int)$correct);
                                        } elseif (is_array($correct)) {
                                            echo implode(', ', array_map(fn($c) => is_numeric($c) ? chr(65 + (int)$c) : $c, $correct));
                                        } else {
                                            echo strtoupper((string)$correct);
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($question['level'] ?? 'NB'); ?></td>
                                <td><?php echo htmlspecialchars($question['points'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($part2)): ?>
                <h3 style="font-size: 13pt; font-weight: bold; margin: 20px 0 8px;">II. ĐÁP ÁN PHẦN TRẮC NGHIỆM ĐÚNG SAI</h3>
                <table class="answer-table">
                    <thead>
                        <tr>
                            <th style="width: 15%">Câu</th>
                            <th style="width: 20%">Lệnh hỏi</th>
                            <th style="width: 25%">Đáp án (Đ/S)</th>
                            <th style="width: 20%">Mức độ</th>
                            <th style="width: 20%">Điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($part2 as $idx => $question): ?>
                            <?php 
                            $items = $question['items'] ?? []; 
                            foreach ($items as $itemIdx => $item):
                                $lbl = $item['label'] ?? chr(97 + $itemIdx);
                                $isT = ($item['correct'] === true || $item['correct'] === 1 || $item['correct'] === 'true' || $item['correct'] === 'dung' || $item['correct'] === 'Đúng');
                            ?>
                                <tr>
                                    <?php if ($itemIdx === 0): ?>
                                        <td rowspan="<?php echo count($items); ?>" style="vertical-align: middle; font-weight: bold;"><?php echo $idx + 1; ?></td>
                                    <?php endif; ?>
                                    <td>Ý <?php echo $lbl; ?></td>
                                    <td style="font-weight: bold; color: <?php echo $isT ? '#198754' : '#dc3545'; ?>;"><?php echo $isT ? 'Đúng' : 'Sai'; ?></td>
                                    <td><?php echo htmlspecialchars($question['level'] ?? 'TH'); ?></td>
                                    <?php if ($itemIdx === 0): ?>
                                        <td rowspan="<?php echo count($items); ?>" style="vertical-align: middle;"><?php echo htmlspecialchars($question['points'] ?? ''); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($part3)): ?>
                <h3 style="font-size: 13pt; font-weight: bold; margin: 20px 0 8px;">III. HƯỚNG DẪN CHẤM PHẦN TỰ LUẬN</h3>
                <div style="margin-left: 10px; margin-bottom: 20px;">
                    <?php foreach ($part3 as $idx => $question): ?>
                        <div style="margin-bottom: 12px;">
                            <strong>Câu <?php echo $idx + 1; ?><?php echo !empty($question['points']) ? " (" . $question['points'] . " điểm)" : ""; ?>:</strong>
                            <div style="margin-left: 15px; margin-top: 4px; color: #212529;">
                                <?php echo nl2br(htmlspecialchars($question['suggested_answer'] ?? $question['answer'] ?? 'Theo biểu điểm và đáp án chi tiết của tổ chuyên môn.')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="summary" style="border-top: 1px dashed #999; padding-top: 10px;">
                <p><strong>Tổng số câu:</strong> <?php echo $examData['total_questions']; ?> câu</p>
                <p><strong>Tổng điểm:</strong> <?php echo $examData['total_points']; ?> điểm</p>
            </div>
        </div>
    </div>
</body>
</html>
