<?php
/**
 * Helper functions for creating teacher notifications
 */
require_once __DIR__ . '/json_db_helper.php';

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

    // Ghi dưới khóa để nhiều học sinh nộp bài cùng lúc không đè mất thông báo
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

    return update_json_data($notificationsFile, function($notifications) use ($notification) {
        if (!is_array($notifications)) { $notifications = []; }
        $notifications[] = $notification;
        return $notifications;
    }, []);
}

/**
 * Get unread notification count for a teacher
 * 
 * @param string $teacherUsername The username of the teacher
 * @return int Count of unread notifications
 */
function getUnreadNotificationCount($teacherUsername) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
    $notifications = get_json_data($notificationsFile, []);
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
    $found = false;

    $result = update_json_data($notificationsFile, function($notifications) use ($notificationId, $teacherUsername, &$found) {
        if (!is_array($notifications)) { return []; }
        foreach ($notifications as &$notif) {
            if ($notif['id'] === $notificationId && $notif['teacher_username'] === $teacherUsername) {
                $notif['is_read'] = true;
                $found = true;
                break;
            }
        }
        return $notifications;
    }, []);

    return $found ? $result : false;
}

/**
 * Mark all notifications as read for a teacher
 * 
 * @param string $teacherUsername The username of the teacher
 * @return bool Success status
 */
function markAllNotificationsAsRead($teacherUsername) {
    $notificationsFile = __DIR__ . '/../data/teacher_notifications.json';

    return update_json_data($notificationsFile, function($notifications) use ($teacherUsername) {
        if (!is_array($notifications)) { return []; }
        foreach ($notifications as &$notif) {
            if ($notif['teacher_username'] === $teacherUsername) {
                $notif['is_read'] = true;
            }
        }
        return $notifications;
    }, []);
}
?>
