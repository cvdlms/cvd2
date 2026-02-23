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

$id = $_GET['id'] ?? '';

if (empty($id)) {
    die('Missing lesson plan ID');
}

// Load lesson plan
$dataFile = __DIR__ . '/../data/lesson_plans.json';
$lessonPlans = json_decode(file_get_contents($dataFile), true) ?: [];

if (!isset($lessonPlans[$id])) {
    die('Lesson plan not found');
}

$plan = $lessonPlans[$id];

// Check permission
$subjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$teacherSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$mySubjects = $teacherSubjects[$username] ?? [];

$canView = ($plan['teacher_username'] === $username) || 
           ($plan['share_with_others'] && in_array($plan['subject_id'], $mySubjects));

if (!$canView) {
    die('Access denied');
}

// Load subject name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$allSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjectName = '';
foreach ($allSubjects as $subj) {
    if ($subj['id'] === $plan['subject_id']) {
        $subjectName = $subj['name'];
        break;
    }
}

// Load class names
$classesFile = __DIR__ . '/../admin/classes.json';
$allClasses = json_decode(file_get_contents($classesFile), true) ?: [];
$classNames = [];
foreach ($allClasses as $class) {
    if (in_array($class['id'], $plan['class_ids'])) {
        $classNames[] = $class['name'];
    }
}
$classNamesStr = implode(', ', $classNames);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plan['basic_info']['ten_bai_day']); ?></title>
    <style>
        @media print {
            .no-print { 
                display: none !important; 
                visibility: hidden !important;
            }
            @page { 
                margin: 2cm; 
                size: A4;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #0d6efd;
        }
        
        .btn-close {
            background: #6c757d;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        
        .header .info-line {
            font-size: 13pt;
            margin: 5px 0;
        }
        
        .header .info-line strong {
            font-weight: bold;
        }
        
        .section {
            margin: 25px 0;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            color: #000;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
        }
        
        .section-content {
            padding-left: 20px;
        }
        
        .objective-item {
            margin: 10px 0;
        }
        
        .objective-item strong {
            font-weight: bold;
        }
        
        .activity {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            page-break-inside: avoid;
        }
        
        .activity-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 12px;
            color: #667eea;
        }
        
        .activity-section {
            margin: 12px 0;
        }
        
        .activity-section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .activity-section-content {
            padding-left: 15px;
            text-align: justify;
        }
        
        .sub-item {
            margin: 8px 0;
            padding-left: 20px;
        }
        
        .sub-item-title {
            font-style: italic;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: right;
            font-style: italic;
            font-size: 12pt;
            color: #6c757d;
        }
        
        ul {
            list-style-type: disc;
            padding-left: 40px;
        }
        
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button class="btn btn-print" onclick="window.print()">
            📄 In/Xuất PDF
        </button>
        <button class="btn btn-close" onclick="window.close()">
            ✖ Đóng
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo htmlspecialchars($plan['basic_info']['ten_bai_day']); ?></h1>
            <p class="info-line">
                <strong>Số tiết:</strong> <?php echo htmlspecialchars($plan['basic_info']['so_tiet']); ?> | 
                <strong>Tiết PPCT:</strong> <?php echo htmlspecialchars($plan['basic_info']['tiet_ppct'] ?: 'N/A'); ?> | 
                <strong>Ngày dạy:</strong> <?php echo htmlspecialchars($plan['basic_info']['ngay_day']); ?>
            </p>
            <p class="info-line">
                <strong>Môn học:</strong> <?php echo htmlspecialchars($subjectName); ?>
            </p>
            <p class="info-line">
                <strong>Lớp:</strong> <?php echo htmlspecialchars($classNamesStr); ?>
            </p>
        </div>

        <!-- 1. Objectives -->
        <div class="section">
            <h2 class="section-title">1. MỤC TIÊU</h2>
            <div class="section-content">
                <div class="objective-item">
                    <strong>• Kiến thức:</strong><br>
                    <?php echo nl2br(htmlspecialchars($plan['muc_tieu']['kien_thuc'] ?: 'N/A')); ?>
                </div>
                <div class="objective-item">
                    <strong>• Năng lực:</strong><br>
                    <?php echo nl2br(htmlspecialchars($plan['muc_tieu']['nang_luc'] ?: 'N/A')); ?>
                </div>
                <div class="objective-item">
                    <strong>• Năng lực số:</strong><br>
                    <?php echo nl2br(htmlspecialchars($plan['muc_tieu']['nang_luc_so'] ?: 'N/A')); ?>
                </div>
                <div class="objective-item">
                    <strong>• Phẩm chất:</strong><br>
                    <?php echo nl2br(htmlspecialchars($plan['muc_tieu']['pham_chat'] ?: 'N/A')); ?>
                </div>
            </div>
        </div>

        <!-- 2. Equipment -->
        <div class="section">
            <h2 class="section-title">2. THIẾT BỊ DẠY HỌC VÀ HỌC LIỆU</h2>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($plan['thiet_bi'] ?: 'N/A')); ?>
            </div>
        </div>

        <!-- 3. Activities -->
        <div class="section">
            <h2 class="section-title">3. TIẾN TRÌNH DẠY HỌC</h2>
            <div class="section-content">
                <?php foreach ($plan['hoat_dong'] as $idx => $activity): ?>
                <div class="activity">
                    <div class="activity-title">
                        Hoạt động <?php echo ($idx + 1); ?>: <?php echo htmlspecialchars($activity['ten']); ?>
                    </div>
                    
                    <div class="activity-section">
                        <div class="activity-section-title">a) Mục tiêu:</div>
                        <div class="activity-section-content">
                            <?php echo nl2br(htmlspecialchars($activity['muc_tieu'] ?: 'N/A')); ?>
                        </div>
                    </div>
                    
                    <div class="activity-section">
                        <div class="activity-section-title">b) Nội dung:</div>
                        <div class="activity-section-content">
                            <?php echo nl2br(htmlspecialchars($activity['noi_dung'] ?: 'N/A')); ?>
                        </div>
                    </div>
                    
                    <div class="activity-section">
                        <div class="activity-section-title">c) Sản phẩm:</div>
                        <div class="activity-section-content">
                            <?php echo nl2br(htmlspecialchars($activity['san_pham'] ?: 'N/A')); ?>
                        </div>
                    </div>
                    
                    <div class="activity-section">
                        <div class="activity-section-title">d) Tổ chức thực hiện:</div>
                        <div class="activity-section-content">
                            <div class="sub-item">
                                <div class="sub-item-title">→ Giao nhiệm vụ học tập:</div>
                                <?php echo nl2br(htmlspecialchars($activity['to_chuc']['giao_nhiem_vu'] ?: 'N/A')); ?>
                            </div>
                            <div class="sub-item">
                                <div class="sub-item-title">→ Thực hiện nhiệm vụ (HS thực hiện; GV theo dõi, hỗ trợ):</div>
                                <?php echo nl2br(htmlspecialchars($activity['to_chuc']['thuc_hien'] ?: 'N/A')); ?>
                            </div>
                            <div class="sub-item">
                                <div class="sub-item-title">→ Báo cáo, thảo luận (GV tổ chức, điều hành; HS báo cáo, thảo luận):</div>
                                <?php echo nl2br(htmlspecialchars($activity['to_chuc']['bao_cao'] ?: 'N/A')); ?>
                            </div>
                            <div class="sub-item">
                                <div class="sub-item-title">→ Kết luận, nhận định:</div>
                                <?php echo nl2br(htmlspecialchars($activity['to_chuc']['ket_luan'] ?: 'N/A')); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 4. Homework -->
        <div class="section">
            <h2 class="section-title">4. HƯỚNG DẪN VỀ NHÀ</h2>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($plan['huong_dan_ve_nha'] ?: 'N/A')); ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Ngày xuất: <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <script>
        // Auto print dialog if requested
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_print') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>
