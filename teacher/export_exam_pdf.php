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
            <p>Họ và tên học sinh: ................................................................</p>
            <p>Lớp: .................&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ngày thi: <?php echo date('d/m/Y'); ?></p>
        </div>
        
        <!-- Questions Section -->
        <div class="section-title">Phần I: Câu Hỏi Trắc Nghiệm</div>
        
        <?php foreach ($examData['questions'] as $idx => $question): ?>
            <div class="question">
                <div class="question-header">
                    <span>Câu <?php echo $idx + 1; ?>:</span>
                    <span class="question-level"><?php echo htmlspecialchars($question['level'] ?? 'NB'); ?></span>
                </div>
                <div class="question-text">
                    <?php echo $question['question']; ?>
                </div>
                <?php if (isset($question['options']) && is_array($question['options'])): ?>
                    <div class="options">
                        <?php foreach ($question['options'] as $optIdx => $option): ?>
                            <div class="option">
                                <?php echo chr(65 + $optIdx); ?>. <?php echo $option; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Answer Key Section -->
        <div class="answer-key">
            <div class="section-title">Phần II: Đáp Án và Hướng Dẫn Chấm</div>
            
            <table class="answer-table">
                <thead>
                    <tr>
                        <th style="width: 10%">Câu</th>
                        <th style="width: 15%">Đáp án</th>
                        <th style="width: 15%">Mức độ</th>
                        <th style="width: 60%">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examData['questions'] as $idx => $question): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td class="correct-answer">
                                <?php 
                                    $correct = $question['correct'] ?? '';
                                    if (is_numeric($correct)) {
                                        echo chr(65 + (int)$correct);
                                    } else {
                                        echo strtoupper($correct);
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($question['level'] ?? 'NB'); ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <p><strong>Tổng số câu:</strong> <?php echo $examData['total_questions']; ?> câu</p>
                <p><strong>Tổng điểm:</strong> <?php echo $examData['total_points']; ?> điểm</p>
                <p><strong>Điểm mỗi câu:</strong> <?php echo $examData['points_per_question']; ?> điểm</p>
            </div>
        </div>
    </div>
</body>
</html>
