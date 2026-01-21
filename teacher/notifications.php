<?php
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];

// Load user data for fullname
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

// Load notifications
$notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
$notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
if (!is_array($notifications)) $notifications = [];

// Filter notifications for this teacher
$teacherNotifications = [];
foreach ($notifications as $key => $notif) {
    if ($notif['teacher_username'] === $username) {
        $teacherNotifications[$key] = $notif;
    }
}

// Sort by created_at (newest first)
uasort($teacherNotifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$title = 'Thông Báo - CVD';
include '../includes/teacher_header.php';
?>

<style>
.notification-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.notification-card.unread {
    background: linear-gradient(to right, rgba(0, 123, 255, 0.05), transparent);
    border-left-color: #007bff;
}

.notification-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.notification-icon.submission {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.mark-read-btn {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-card:hover .mark-read-btn {
    opacity: 1;
}
</style>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-bell-fill me-2"></i>Thông Báo</h2>
                    <p class="text-muted mb-0">Theo dõi các hoạt động mới nhất từ học sinh</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary" onclick="markAllAsRead()">
                        <i class="bi bi-check2-all"></i> Đánh dấu tất cả đã đọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if (empty($teacherNotifications)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">Chưa có thông báo nào</h4>
                        <p class="text-muted">Các thông báo về bài nộp của học sinh sẽ hiển thị tại đây</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($teacherNotifications as $key => $notif): ?>
                    <?php
                    $isRead = $notif['is_read'] ?? false;
                    $cardClass = $isRead ? '' : 'unread';
                    
                    // Format time
                    $createdDate = new DateTime($notif['created_at']);
                    $now = new DateTime();
                    $diff = $now->getTimestamp() - $createdDate->getTimestamp();
                    
                    if ($diff < 60) {
                        $timeAgo = 'Vừa xong';
                    } elseif ($diff < 3600) {
                        $minutes = floor($diff / 60);
                        $timeAgo = $minutes . ' phút trước';
                    } elseif ($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $timeAgo = $hours . ' giờ trước';
                    } elseif ($diff < 604800) {
                        $days = floor($diff / 86400);
                        $timeAgo = $days . ' ngày trước';
                    } else {
                        $timeAgo = $createdDate->format('d/m/Y H:i');
                    }
                    ?>
                    <div class="card notification-card <?php echo $cardClass; ?> mb-3 shadow-sm" data-notification-id="<?php echo $key; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon submission me-3">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                            <?php if (!$isRead): ?>
                                                <span class="badge bg-primary ms-2">Mới</span>
                                            <?php endif; ?>
                                        </h5>
                                        <?php if (!$isRead): ?>
                                            <button class="btn btn-sm btn-outline-success mark-read-btn" onclick="markAsRead(<?php echo $key; ?>)">
                                                <i class="bi bi-check2"></i> Đánh dấu đã đọc
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo $timeAgo; ?>
                                        </small>
                                        <?php if (!empty($notif['link'])): ?>
                                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Xem chi tiết
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread styling
            const card = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (card) {
                card.classList.remove('unread');
                // Remove "Mới" badge
                const badge = card.querySelector('.badge.bg-primary');
                if (badge) badge.remove();
                // Remove mark-read button
                const btn = card.querySelector('.mark-read-btn');
                if (btn) btn.remove();
            }
            // Update navbar badge
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function markAllAsRead() {
    fetch('api/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateNotificationBadge() {
    fetch('api/get_notifications_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.unread_count > 0) {
                document.getElementById('notificationBadge').textContent = data.unread_count;
                document.getElementById('notificationBadge').style.display = 'inline-block';
            } else {
                document.getElementById('notificationBadge').style.display = 'none';
            }
        });
}
</script>

<?php include '../includes/teacher_footer.php'; ?>
