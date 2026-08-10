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
$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['notification_id'] ?? null;

if ($notificationId === null) {
    echo json_encode(['success' => false, 'message' => 'Missing notification ID']);
    exit;
}

// Đọc–sửa–ghi dưới khóa để không đè mất thông báo đang được tạo cùng lúc
$notificationsFile = __DIR__ . '/../../data/teacher_notifications.json';
$found = false;
$result = update_json_data($notificationsFile, function($notifications) use ($notificationId, $username, &$found) {
    if (!is_array($notifications)) { return []; }
    if (isset($notifications[$notificationId]) && $notifications[$notificationId]['teacher_username'] === $username) {
        $notifications[$notificationId]['is_read'] = true;
        $found = true;
    }
    return $notifications;
}, []);

if ($found && $result !== false) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Notification not found']);
}
?>
