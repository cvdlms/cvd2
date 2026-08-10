<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];

// Đọc–sửa–ghi dưới khóa để không đè mất thông báo đang được tạo cùng lúc
$notificationsFile = __DIR__ . '/../../data/teacher_notifications.json';
$result = update_json_data($notificationsFile, function($notifications) use ($username) {
    if (!is_array($notifications)) { return []; }
    foreach ($notifications as $key => $notif) {
        if ($notif['teacher_username'] === $username) {
            $notifications[$key]['is_read'] = true;
        }
    }
    return $notifications;
}, []);

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}
?>
