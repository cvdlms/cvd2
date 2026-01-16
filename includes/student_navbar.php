<?php
$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Check premium status
require_once __DIR__ . '/student_premium_helper.php';
$navbarPremiumStatus = getStudentPremiumStatus($studentCode);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">🏫 CVD - Học Sinh</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'practice.php' ? 'active' : ''; ?>" href="practice.php">📚 Luyện Tập</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>" href="results.php">📈 Kết Quả</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['assignments.php', 'submit_assignment.php', 'my_submissions.php']) ? 'active' : ''; ?>" href="assignments.php">
                        <i class="bi bi-journal-check"></i> Bài Tập
                        <?php if (!$navbarPremiumStatus['is_premium']): ?>
                            <i class="bi bi-star-fill" style="color: #ffd700; font-size: 0.8em; margin-left: 4px;"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($navbarPremiumStatus['is_premium']): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'advanced_statistics.php' ? 'active' : ''; ?>" href="advanced_statistics.php">
                        <i class="bi bi-graph-up-arrow"></i> Thống Kê Nâng Cao
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="premium.php">
                        <i class="bi bi-star"></i> Premium
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                        👤 <?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>)
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">👤 Thông tin cá nhân</a></li>
                        <?php if (!$navbarPremiumStatus['is_premium']): ?>
                        <li><a class="dropdown-item" href="premium.php"><i class="bi bi-star"></i> Nâng cấp Premium</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">🚪 Đăng xuất</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
