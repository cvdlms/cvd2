<?php
session_start();

// Check if logged in
if (!isset($_SESSION['username'])) {
    echo "⚠️ Chưa đăng nhập. Hãy đăng nhập vào hệ thống trước.";
    exit;
}

$username = $_SESSION['username'];
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';

$teacherSubjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];

$assignedSubjectIds = $teacherSubjects[$username] ?? [];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Check Permission</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { background: #f5f5f5; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4caf50; color: white; }
    </style>
</head>
<body>
    <h1>🔐 Kiểm tra Permission</h1>
    
    <div class="box success">
        <h3>👤 User hiện tại</h3>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
    </div>
    
    <div class="box <?php echo empty($assignedSubjectIds) ? 'error' : 'success'; ?>">
        <h3>📚 Môn học được phân công</h3>
        <?php if (empty($assignedSubjectIds)): ?>
            <p style="color: red; font-weight: bold;">❌ KHÔNG CÓ MÔN HỌC NÀO!</p>
            <p>Đây là lý do tại sao import bị lỗi. Teacher chưa được admin phân công môn học.</p>
            <p><strong>Giải pháp:</strong></p>
            <ol>
                <li>Đăng nhập bằng tài khoản admin</li>
                <li>Vào <strong>Admin → Quản lý giáo viên</strong></li>
                <li>Phân công môn học cho user <code><?php echo htmlspecialchars($username); ?></code></li>
            </ol>
        <?php else: ?>
            <p style="color: green; font-weight: bold;">✅ Có <?php echo count($assignedSubjectIds); ?> môn học</p>
            <table>
                <thead>
                    <tr>
                        <th>Subject ID</th>
                        <th>Tên môn học</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignedSubjectIds as $subjectId): ?>
                        <?php 
                        $subject = array_filter($subjects, fn($s) => $s['id'] == $subjectId);
                        $subject = reset($subject);
                        ?>
                        <tr>
                            <td><?php echo $subjectId; ?></td>
                            <td><?php echo $subject ? htmlspecialchars($subject['name']) : '<em style="color:red;">Không tìm thấy</em>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="box warning">
        <h3>💡 Lưu ý khi Import</h3>
        <ul>
            <li>Khi import, phải chọn <strong>Môn học</strong> nằm trong danh sách trên</li>
            <li>Nếu chọn môn khác → Lỗi "Môn học không hợp lệ hoặc không được phép"</li>
            <li>File Word đúng format nhưng không có permission → Vẫn báo lỗi</li>
        </ul>
    </div>
    
    <?php if (!empty($assignedSubjectIds)): ?>
    <div class="box success">
        <h3>✅ Hướng dẫn Import</h3>
        <ol>
            <li>Vào <a href="question_bank.php" target="_blank">question_bank.php</a></li>
            <li>Chọn <strong>Khối</strong> (khoi6, khoi7, khoi8, khoi9)</li>
            <li>Chọn <strong>Môn học</strong> = Một trong các môn trên</li>
            <li>Chọn <strong>Học kỳ</strong> (hk1 hoặc hk2)</li>
            <li>Click <strong>"THÊM TỪ WORD"</strong></li>
            <li>Upload file Word đã sửa (Câu 12, 14 có metadata)</li>
            <li>Click <strong>"Import"</strong></li>
        </ol>
    </div>
    <?php endif; ?>

</body>
</html>
