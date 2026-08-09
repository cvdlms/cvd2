<?php
$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Compute XP / level / streak from the student's score history
$navbarXp = 0;
$navbarStreak = 0;
$navbarScoreFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '.json';
if (file_exists($navbarScoreFile)) {
    $navbarHistory = json_decode(file_get_contents($navbarScoreFile), true);
    if (is_array($navbarHistory)) {
        $navbarDays = [];
        foreach ($navbarHistory as $navbarRec) {
            $navbarXp += round(($navbarRec['score'] ?? 0) * 10, 0);
            if (!empty($navbarRec['timestamp'])) {
                $navbarDays[date('Y-m-d', strtotime($navbarRec['timestamp']))] = true;
            }
        }
        // Consecutive-day streak ending today or yesterday
        $cursor = new DateTime();
        if (!isset($navbarDays[$cursor->format('Y-m-d')])) {
            $cursor->modify('-1 day');
        }
        while (isset($navbarDays[$cursor->format('Y-m-d')])) {
            $navbarStreak++;
            $cursor->modify('-1 day');
        }
    }
}
$navbarLevel = intdiv($navbarXp, 100) + 1;
$navbarLevelProgress = ($navbarXp % 100);

$stdPage = basename($_SERVER['PHP_SELF']);
$stdAvatarInitial = !empty($studentName) ? mb_substr(trim($studentName), 0, 1) : 'HS';
?>
<aside class="std-sidebar" id="stdSidebar">
    <div class="std-brand">
        <svg class="std-brand-icon" viewBox="0 0 100 100" aria-hidden="true">
            <path d="M50 15c22 0 36 15 34 35-1.5 17-15 28-34 28S17.5 67 16 51C14 31 28 15 50 15z" fill="url(#stdnavg)"/>
            <defs><linearGradient id="stdnavg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9C84FF"/><stop offset="1" stop-color="#7B5CFA"/></linearGradient></defs>
            <circle cx="38" cy="52" r="4.5" fill="#fff"/><circle cx="63" cy="52" r="4.5" fill="#fff"/>
            <path d="M40 62q10 8 20 0" stroke="#fff" stroke-width="4" fill="none" stroke-linecap="round"/>
        </svg>
        <div>
            <div class="logo-text">EduVN</div>
            <div class="logo-sub">Học sinh</div>
        </div>
    </div>

    <div class="std-profile">
        <div class="pf-top">
            <div class="std-avatar"><?php echo htmlspecialchars($stdAvatarInitial); ?></div>
            <div>
                <div class="pf-name"><?php echo htmlspecialchars($studentName); ?></div>
                <div class="pf-class"><?php echo htmlspecialchars($studentClass ?: ($studentClassCode ?: 'Học sinh')); ?></div>
            </div>
        </div>
        <div class="std-xp-top">
            <span class="xp-val">Cấp <?php echo $navbarLevel; ?></span>
            <span class="xp-val"><?php echo number_format($navbarXp); ?> XP</span>
        </div>
        <div class="std-xp-track">
            <div class="std-xp-fill" style="width: <?php echo max(4, min(100, $navbarLevelProgress)); ?>%"></div>
        </div>
    </div>

    <div class="std-nav-group">Học tập</div>
    <a class="std-nav-item <?php echo $stdPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
        <span class="ic"><i class="bi bi-grid-fill"></i></span>
        <span>Trang chủ</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'exam.php' ? 'active' : ''; ?>" href="dashboard.php#kiem-tra">
        <span class="ic"><i class="bi bi-file-earmark-text-fill"></i></span>
        <span>Làm bài kiểm tra</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'practice.php' ? 'active' : ''; ?>" href="practice.php">
        <span class="ic"><i class="bi bi-bullseye"></i></span>
        <span>Luyện tập</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'results.php' ? 'active' : ''; ?>" href="results.php">
        <span class="ic"><i class="bi bi-bar-chart-fill"></i></span>
        <span>Kết quả</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'advanced_statistics.php' ? 'active' : ''; ?>" href="advanced_statistics.php">
        <span class="ic"><i class="bi bi-graph-up-arrow"></i></span>
        <span>Thống kê</span>
    </a>

    <div class="std-nav-group">Bài tập</div>
    <a class="std-nav-item <?php echo in_array($stdPage, ['assignments.php', 'my_submissions.php', 'submit_assignment.php']) ? 'active' : ''; ?>" href="assignments.php">
        <span class="ic"><i class="bi bi-clipboard-check-fill"></i></span>
        <span>Bài tập của tôi</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'my_submissions.php' ? 'active' : ''; ?>" href="my_submissions.php">
        <span class="ic"><i class="bi bi-clock-history"></i></span>
        <span>Lịch sử nộp bài</span>
    </a>
    <a class="std-nav-item <?php echo $stdPage == 'user_guide.php' ? 'active' : ''; ?>" href="user_guide.php">
        <span class="ic"><i class="bi bi-question-circle-fill"></i></span>
        <span>Hướng dẫn</span>
    </a>

    <div class="std-nav-group">Tài khoản</div>
    <a class="std-nav-item <?php echo $stdPage == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
        <span class="ic"><i class="bi bi-person-gear"></i></span>
        <span>Tài khoản của tôi</span>
    </a>

    <div class="std-nav-footer">
        <a class="std-logout" href="logout.php">
            <i class="bi bi-box-arrow-right"></i>
            Đăng xuất
        </a>
    </div>
</aside>

<button class="std-burger std-icon-btn" id="stdBurger" type="button" aria-label="Mở menu">
    <i class="bi bi-list"></i>
</button>
<div class="std-backdrop" id="stdBackdrop"></div>

<main class="std-main">

    <nav class="std-tabbar" aria-label="Điều hướng nhanh">
        <a class="mtab <?php echo $stdPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="bi bi-grid-fill icon"></i>Trang chủ
        </a>
        <a class="mtab <?php echo $stdPage == 'exam.php' ? 'active' : ''; ?>" href="dashboard.php#kiem-tra">
            <i class="bi bi-file-earmark-text-fill icon"></i>Làm bài
        </a>
        <a class="mtab <?php echo $stdPage == 'practice.php' ? 'active' : ''; ?>" href="practice.php">
            <i class="bi bi-bullseye icon"></i>Luyện tập
        </a>
        <a class="mtab <?php echo in_array($stdPage, ['assignments.php', 'my_submissions.php', 'submit_assignment.php']) ? 'active' : ''; ?>" href="assignments.php">
            <i class="bi bi-clipboard-check-fill icon"></i>Bài tập
        </a>
        <a class="mtab <?php echo in_array($stdPage, ['results.php', 'advanced_statistics.php']) ? 'active' : ''; ?>" href="results.php">
            <i class="bi bi-clock-history icon"></i>Lịch sử
        </a>
    </nav>
