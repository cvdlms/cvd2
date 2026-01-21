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

// Filter notifications for this teacher
$teacherNotifications = [];
foreach ($notifications as $notif) {
    if ($notif['teacher_username'] === $username) {
        $teacherNotifications[] = $notif;
    }
}

// Sort by created_at (newest first)
usort($teacherNotifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode([
    'success' => true,
    'notifications' => $teacherNotifications
]);
?>
