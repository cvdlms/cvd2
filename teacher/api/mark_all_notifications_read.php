<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];

// Load notifications
$notificationsFile = __DIR__ . '/../../data/teacher_notifications.json';
$notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
if (!is_array($notifications)) $notifications = [];

// Mark all notifications for this teacher as read
foreach ($notifications as $key => $notif) {
    if ($notif['teacher_username'] === $username) {
        $notifications[$key]['is_read'] = true;
    }
}

file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
?>
