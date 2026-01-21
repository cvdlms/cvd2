<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

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

// Load notifications
$notificationsFile = __DIR__ . '/../../data/teacher_notifications.json';
$notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
if (!is_array($notifications)) $notifications = [];

// Mark notification as read
if (isset($notifications[$notificationId]) && $notifications[$notificationId]['teacher_username'] === $username) {
    $notifications[$notificationId]['is_read'] = true;
    file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Notification not found']);
}
?>
