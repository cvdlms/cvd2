<?php
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

// Check Premium status
if (file_exists(__DIR__ . '/premium_helper.php')) {
    include_once __DIR__ . '/premium_helper.php';
    $isPremiumUser = isPremiumUser($_SESSION['username']);
    $premiumDaysRemaining = $isPremiumUser ? getPremiumDaysRemaining($_SESSION['username']) : 0;
} else {
    $isPremiumUser = false;
    $premiumDaysRemaining = 0;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="teacher.php">🏫 CVD Teacher</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teacher.php' ? 'active' : ''; ?>" href="teacher.php">📊 Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">👨‍🎓 Học Sinh</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exam_creation.php' ? 'active' : ''; ?>" href="exam_creation.php">📝 Bài Kiểm Tra</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'question_bank.php' ? 'active' : ''; ?>" href="question_bank.php">📚 Ngân Hàng Câu Hỏi</a>
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'matrix_builder.php' ? 'active' : ''; ?>" href="matrix_builder.php">🔧 Xây Dựng Ma Trận</a>
        </li> -->
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_result.php' ? 'active' : ''; ?>" href="manage_result.php">📈 Kết Quả</a>
        </li>
      </ul>
      <ul class="navbar-nav">
        <!-- Notifications -->
        <li class="nav-item dropdown">
          <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" id="notificationDropdown">
            <i class="bi bi-bell-fill fs-5"></i>
            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill" id="notificationBadge" style="display: none; font-size: 0.7rem;">
              0
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
            <li class="dropdown-header d-flex justify-content-between align-items-center">
              <strong>Thông báo</strong>
              <a href="notifications.php" class="text-primary small">Xem tất cả</a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li id="notificationList">
              <div class="text-center text-muted py-3">
                <i class="bi bi-inbox"></i> Không có thông báo mới
              </div>
            </li>
          </ul>
        </li>
        
        <?php if ($isPremiumUser): ?>
          <li class="nav-item">
            <a class="nav-link" href="premium_activation.php">
              <span class="badge bg-warning text-dark">⭐ Premium 
                <?php if ($premiumDaysRemaining <= 7): ?>
                  <span class="badge bg-danger"><?php echo $premiumDaysRemaining; ?>d</span>
                <?php endif; ?>
              </span>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="premium_activation.php">
              <span class="badge bg-secondary">🔒 Nâng cấp Premium</span>
            </a>
          </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            👤 <?php echo htmlspecialchars($fullname ?? 'Giáo Viên'); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="premium_activation.php">⭐ Premium</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../change_password.php">🔐 Đổi mật khẩu</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">🚪 Đăng xuất</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Auto Keep-Alive Script to prevent session timeout -->
<script>
(function() {
    // Keep session alive every 5 minutes (300000ms)
    setInterval(function() {
        fetch('../api/keep_alive.php')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.warn('Session may have expired');
                }
            })
            .catch(error => {
                console.error('Keep-alive failed:', error);
            });
    }, 300000); // 5 minutes
    
    // Load notification count
    function loadNotificationCount() {
        fetch('api/get_notifications_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unread_count > 0) {
                    document.getElementById('notificationBadge').textContent = data.unread_count;
                    document.getElementById('notificationBadge').style.display = 'inline-block';
                } else {
                    document.getElementById('notificationBadge').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }
    
    // Load notifications on page load
    loadNotificationCount();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotificationCount, 30000);
    
    // Load notification list when dropdown is opened
    document.getElementById('notificationDropdown').addEventListener('click', function() {
        loadNotificationList();
    });
    
    function loadNotificationList() {
        fetch('api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    const listHtml = data.notifications.slice(0, 5).map(notif => {
                        const isRead = notif.is_read ? '' : 'bg-light';
                        const time = formatTime(notif.created_at);
                        return `
                            <li>
                                <a class="dropdown-item ${isRead}" href="notifications.php">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-journal-check text-success me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="small fw-bold">${notif.title}</div>
                                            <div class="small text-muted">${notif.message}</div>
                                            <div class="small text-muted"><i class="bi bi-clock"></i> ${time}</div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `;
                    }).join('');
                    document.getElementById('notificationList').innerHTML = listHtml;
                } else {
                    document.getElementById('notificationList').innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-inbox"></i> Không có thông báo mới
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading notification list:', error);
            });
    }
    
    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Vừa xong';
        if (diffMins < 60) return diffMins + ' phút trước';
        if (diffHours < 24) return diffHours + ' giờ trước';
        if (diffDays < 7) return diffDays + ' ngày trước';
        return date.toLocaleDateString('vi-VN');
    }
})();
</script>

<?php if ($isPremiumUser): ?>
<!-- Floating Zalo Contact Button -->
<a href="https://zalo.me/0973384354" target="_blank" class="zalo-float-button" title="Hỗ trợ Premium qua Zalo">
  <span class="zalo-icon">💬</span>
</a>
<?php endif; ?>
