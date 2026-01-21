<?php
/**
 * File demo để tạo thông báo mẫu cho giáo viên
 * Chạy file này để tạo một số thông báo test
 */

require_once __DIR__ . '/../includes/notification_helper.php';

// Tạo 5 thông báo mẫu
$notifications = [
    [
        'teacher' => 'visal',
        'type' => 'assignment_submission',
        'title' => 'Học sinh nộp bài tập mới',
        'message' => 'Nguyễn Văn A (7A1) đã nộp bài tập: Bài thực hành Excel',
        'link' => 'view_submissions.php?id=assign_test1',
        'metadata' => [
            'assignment_id' => 'assign_test1',
            'student_code' => '2405548501',
            'student_name' => 'Nguyễn Văn A'
        ]
    ],
    [
        'teacher' => 'visal',
        'type' => 'assignment_submission',
        'title' => 'Học sinh nộp bài tập mới',
        'message' => 'Trần Thị B (7A1) đã nộp bài tập: Bài thực hành Word',
        'link' => 'view_submissions.php?id=assign_test2',
        'metadata' => [
            'assignment_id' => 'assign_test2',
            'student_code' => '2405548502',
            'student_name' => 'Trần Thị B'
        ]
    ],
    [
        'teacher' => 'visal',
        'type' => 'assignment_submission',
        'title' => 'Học sinh nộp bài tập mới',
        'message' => 'Lê Văn C (7A2) đã nộp bài tập: Bài tập về nhà tuần 3',
        'link' => 'view_submissions.php?id=assign_test3',
        'metadata' => [
            'assignment_id' => 'assign_test3',
            'student_code' => '2405548503',
            'student_name' => 'Lê Văn C'
        ]
    ],
    [
        'teacher' => 'visal',
        'type' => 'assignment_submission',
        'title' => 'Học sinh nộp bài tập mới',
        'message' => 'Phạm Thị D (7A1) đã nộp bài tập: Bài thực hành PowerPoint',
        'link' => 'view_submissions.php?id=assign_test4',
        'metadata' => [
            'assignment_id' => 'assign_test4',
            'student_code' => '2405548504',
            'student_name' => 'Phạm Thị D'
        ]
    ],
    [
        'teacher' => 'visal',
        'type' => 'assignment_submission',
        'title' => 'Học sinh nộp bài tập mới',
        'message' => 'Hoàng Văn E (7A2) đã nộp bài tập: Bài tập tổng hợp',
        'link' => 'view_submissions.php?id=assign_test5',
        'metadata' => [
            'assignment_id' => 'assign_test5',
            'student_code' => '2405548505',
            'student_name' => 'Hoàng Văn E'
        ]
    ]
];

$successCount = 0;
foreach ($notifications as $notif) {
    if (createTeacherNotification(
        $notif['teacher'],
        $notif['type'],
        $notif['title'],
        $notif['message'],
        $notif['link'],
        $notif['metadata']
    )) {
        $successCount++;
    }
}

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Demo Thông Báo</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container my-5'>
        <div class='card shadow'>
            <div class='card-header bg-success text-white'>
                <h3 class='mb-0'><i class='bi bi-check-circle'></i> Demo Thông Báo Thành Công</h3>
            </div>
            <div class='card-body'>
                <div class='alert alert-success'>
                    <h4 class='alert-heading'><i class='bi bi-check2-all'></i> Đã tạo {$successCount} thông báo mẫu!</h4>
                    <hr>
                    <p class='mb-0'>Các thông báo đã được tạo cho giáo viên <strong>visal</strong></p>
                </div>
                
                <h5 class='mt-4 mb-3'>Các tính năng đã triển khai:</h5>
                <ul class='list-group mb-4'>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Badge thông báo trên navbar với số lượng chưa đọc
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Dropdown hiển thị 5 thông báo mới nhất
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Trang xem tất cả thông báo (notifications.php)
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Hiển thị 5 thông báo mới nhất trên trang chủ
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Đánh dấu đã đọc/chưa đọc
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Tự động tạo thông báo khi học sinh nộp bài
                    </li>
                    <li class='list-group-item'>
                        <i class='bi bi-check-circle-fill text-success'></i> 
                        Cập nhật tự động mỗi 30 giây
                    </li>
                </ul>
                
                <div class='d-grid gap-2'>
                    <a href='teacher.php' class='btn btn-primary btn-lg'>
                        <i class='bi bi-house-door'></i> Đi đến Trang Chủ Giáo Viên
                    </a>
                    <a href='notifications.php' class='btn btn-success btn-lg'>
                        <i class='bi bi-bell'></i> Xem Tất Cả Thông Báo
                    </a>
                    <a href='../login.php' class='btn btn-secondary'>
                        <i class='bi bi-box-arrow-in-right'></i> Đăng Nhập
                    </a>
                </div>
                
                <div class='alert alert-info mt-4'>
                    <h6><i class='bi bi-info-circle'></i> Hướng dẫn test:</h6>
                    <ol class='mb-0'>
                        <li>Đăng nhập với tài khoản giáo viên <strong>visal</strong></li>
                        <li>Kiểm tra biểu tượng chuông trên navbar (sẽ có badge màu đỏ hiển thị số thông báo)</li>
                        <li>Click vào chuông để xem dropdown thông báo</li>
                        <li>Hoặc vào trang Thông Báo để xem tất cả</li>
                        <li>Thử đánh dấu đã đọc để xem badge cập nhật</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
?>
