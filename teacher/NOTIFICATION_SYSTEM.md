# Hệ Thống Thông Báo Cho Giáo Viên

## Tổng Quan
Hệ thống thông báo tự động gửi thông báo cho giáo viên khi có các sự kiện quan trọng xảy ra, ví dụ như học sinh nộp bài tập.

## Các Tính Năng

### 1. Badge Thông Báo Trên Navbar
- Biểu tượng chuông (🔔) hiển thị trên thanh navigation
- Badge đỏ hiển thị số lượng thông báo chưa đọc
- Cập nhật tự động mỗi 30 giây
- Dropdown hiển thị 5 thông báo mới nhất khi click vào chuông

### 2. Trang Thông Báo (`notifications.php`)
- Hiển thị tất cả thông báo của giáo viên
- Phân biệt rõ ràng giữa thông báo đã đọc và chưa đọc
- Hiển thị thời gian tương đối (vừa xong, 5 phút trước, 2 giờ trước, v.v.)
- Nút "Đánh dấu đã đọc" cho từng thông báo
- Nút "Đánh dấu tất cả đã đọc"
- Link trực tiếp đến trang liên quan (xem bài nộp)

### 3. Trang Chủ Giáo Viên
- Hiển thị 5 thông báo chưa đọc mới nhất ngay trên dashboard
- Giúp giáo viên nhanh chóng nắm bắt các hoạt động mới

## Cách Hoạt Động

### Khi Học Sinh Nộp Bài Tập
1. Học sinh nộp bài qua `student/api/submit_assignment.php`
2. Hệ thống tự động tạo thông báo cho giáo viên phụ trách
3. Thông báo được lưu vào `data/teacher_notifications.json`
4. Giáo viên sẽ thấy badge cập nhật trên navbar
5. Giáo viên có thể click vào chuông để xem hoặc vào trang Thông Báo

### Cấu Trúc Thông Báo
```json
{
    "id": "notif_xxxxx",
    "teacher_username": "visal",
    "type": "assignment_submission",
    "title": "Học sinh nộp bài tập mới",
    "message": "Châu Thế Hào (7A1) đã nộp bài tập: Bài thực hành excel",
    "link": "view_submissions.php?id=assign_xxxxx",
    "assignment_id": "assign_xxxxx",
    "student_code": "2405548512",
    "student_name": "Châu Thế Hào",
    "created_at": "2026-01-21 10:30:00",
    "is_read": false
}
```

## API Endpoints

### 1. `teacher/api/get_notifications_count.php`
- **Method**: GET
- **Mô tả**: Lấy số lượng thông báo chưa đọc
- **Response**: `{"success": true, "unread_count": 5}`

### 2. `teacher/api/get_notifications.php`
- **Method**: GET
- **Mô tả**: Lấy tất cả thông báo của giáo viên
- **Response**: `{"success": true, "notifications": [...]}`

### 3. `teacher/api/mark_notification_read.php`
- **Method**: POST
- **Body**: `{"notification_id": "notif_xxxxx"}`
- **Mô tả**: Đánh dấu một thông báo đã đọc

### 4. `teacher/api/mark_all_notifications_read.php`
- **Method**: POST
- **Mô tả**: Đánh dấu tất cả thông báo đã đọc

## Helper Functions

File `includes/notification_helper.php` cung cấp các hàm tiện ích:

### `createTeacherNotification()`
```php
createTeacherNotification(
    $teacherUsername,
    'assignment_submission',
    'Tiêu đề thông báo',
    'Nội dung chi tiết',
    'link/to/page.php',
    ['metadata_key' => 'value']
);
```

### `getUnreadNotificationCount()`
```php
$count = getUnreadNotificationCount($teacherUsername);
```

### `markNotificationAsRead()`
```php
markNotificationAsRead($notificationId, $teacherUsername);
```

### `markAllNotificationsAsRead()`
```php
markAllNotificationsAsRead($teacherUsername);
```

## Mở Rộng Trong Tương Lai

Hệ thống có thể được mở rộng để hỗ trợ các loại thông báo khác:

1. **Thông báo khi học sinh hoàn thành bài kiểm tra**
2. **Thông báo khi có học sinh mới đăng ký vào lớp**
3. **Nhắc nhở về deadline sắp đến**
4. **Thông báo khi có học sinh yêu cầu xem lại bài kiểm tra**
5. **Thông báo hệ thống (bảo trì, cập nhật, v.v.)**

## Cách Thêm Loại Thông Báo Mới

1. Xác định loại thông báo (ví dụ: `exam_completed`)
2. Tại điểm cần tạo thông báo, gọi:
```php
require_once __DIR__ . '/../includes/notification_helper.php';
createTeacherNotification(
    $teacherUsername,
    'exam_completed',
    'Học sinh hoàn thành bài kiểm tra',
    $studentName . ' đã hoàn thành: ' . $examTitle,
    'manage_result.php?exam_id=' . $examId,
    [
        'exam_id' => $examId,
        'student_code' => $studentCode
    ]
);
```

## Lưu Ý Kỹ Thuật

- Thông báo được lưu trong file JSON: `data/teacher_notifications.json`
- Không có giới hạn số lượng thông báo (có thể thêm chức năng xóa thông báo cũ nếu cần)
- Cập nhật tự động mỗi 30 giây bằng JavaScript
- Tương thích với các trình duyệt hiện đại
- Responsive design, hoạt động tốt trên mobile

## Bảo Mật

- Mỗi thông báo được gắn với `teacher_username`
- API kiểm tra quyền truy cập dựa trên session
- Chỉ giáo viên có thể xem và đánh dấu thông báo của mình

---

**Ngày tạo**: 21/01/2026  
**Phiên bản**: 1.0  
**Tác giả**: CVD Team
