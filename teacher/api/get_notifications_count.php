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

// Count unread notifications for this teacher
$unreadCount = 0;
foreach ($notifications as $notif) {
    if ($notif['teacher_username'] === $username && !($notif['is_read'] ?? false)) {
        $unreadCount++;
    }
}

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount
]);
?>
