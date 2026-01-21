<?php
/**
 * Helper functions for creating teacher notifications
 */

/**
 * Create a notification for a teacher
 * 
 * @param string $teacherUsername The username of the teacher
 * @param string $type Type of notification (assignment_submission, exam_completed, etc.)
 * @param string $title Short title for the notification
 * @param string $message Detailed message
 * @param string $link URL to relevant page (relative to teacher folder)
 * @param array $metadata Additional data (assignment_id, student_code, etc.)
 * @return bool Success status
 */
function createTeacherNotification($teacherUsername, $type, $title, $message, $link = '', $metadata = []) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
    $notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
    if (!is_array($notifications)) $notifications = [];
    
    $notification = [
        'id' => uniqid('notif_'),
        'teacher_username' => $teacherUsername,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link,
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => false
    ];
    
    // Add metadata
    foreach ($metadata as $key => $value) {
        $notification[$key] = $value;
    }
    
    $notifications[] = $notification;
    return file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Get unread notification count for a teacher
 * 
 * @param string $teacherUsername The username of the teacher
 * @return int Count of unread notifications
 */
function getUnreadNotificationCount($teacherUsername) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
    $notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
    if (!is_array($notifications)) return 0;
    
    $count = 0;
    foreach ($notifications as $notif) {
        if ($notif['teacher_username'] === $teacherUsername && !($notif['is_read'] ?? false)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Mark a notification as read
 * 
 * @param string $notificationId The ID of the notification
 * @param string $teacherUsername The username of the teacher (for security)
 * @return bool Success status
 */
function markNotificationAsRead($notificationId, $teacherUsername) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
    $notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
    if (!is_array($notifications)) return false;
    
    $found = false;
    foreach ($notifications as &$notif) {
        if ($notif['id'] === $notificationId && $notif['teacher_username'] === $teacherUsername) {
            $notif['is_read'] = true;
            $found = true;
            break;
        }
    }
    
    if ($found) {
        return file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    return false;
}

/**
 * Mark all notifications as read for a teacher
 * 
 * @param string $teacherUsername The username of the teacher
 * @return bool Success status
 */
function markAllNotificationsAsRead($teacherUsername) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
    $notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
    if (!is_array($notifications)) return false;
    
    foreach ($notifications as &$notif) {
        if ($notif['teacher_username'] === $teacherUsername) {
            $notif['is_read'] = true;
        }
    }
    
    return file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}
?>
