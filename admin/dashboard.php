<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

// Load all data files
$users = json_decode(file_get_contents('user.json'), true) ?: [];
$subjects = json_decode(file_get_contents('subjects.json'), true) ?: [];
$students = json_decode(file_get_contents('students.json'), true) ?: [];
$classes = json_decode(file_get_contents('classes.json'), true) ?: [];
$practice_history = json_decode(file_get_contents('student_practice_history.json'), true) ?: [];
$premium_orders = json_decode(file_get_contents('premium_orders.json'), true) ?: [];
$premium_subscriptions = json_decode(file_get_contents('premium_subscriptions.json'), true) ?: [];
$login_attempts = json_decode(file_get_contents('login_attempts.json'), true) ?: [];
$teacher_subjects = json_decode(file_get_contents('teacher_subjects.json'), true) ?: [];
$teacher_classes = json_decode(file_get_contents('teacher_classes.json'), true) ?: [];
$system_config = json_decode(file_get_contents('system_config.json'), true) ?: [];

$fullname = $users['admin']['fullname'] ?? 'Admin';
$school_name = $system_config['system']['school_name'] ?? 'Hệ thống CVD';
$school_year = $system_config['system']['school_year'] ?? '';
$current_semester = $system_config['semester']['current'] ?? 'hk1';
$semester_label = $system_config['semester']['labels'][$current_semester] ?? strtoupper($current_semester);

// Calculate basic stats
$total_teachers = count($users) - 1; // Exclude admin
$total_students = count($students);
$total_subjects = count($subjects);
$total_classes = count($classes);

// Count exams from teacher folders
$total_exams = 0;
$exam_dir = '../teacher/exams';
if (is_dir($exam_dir)) {
    $exam_iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($exam_dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($exam_iterator as $exam_file) {
        if ($exam_file->isFile() && strtolower($exam_file->getExtension()) === 'json') {
            $total_exams++;
        }
    }
}

// Premium statistics
$total_premium_students = count($premium_subscriptions);
$pending_premium_requests = count(array_filter($premium_orders, function($order) {
    return $order['status'] === 'pending';
}));
$premium_revenue = array_sum(array_map(function($order) {
    return ($order['status'] === 'approved') ? $order['price'] : 0;
}, $premium_orders));

// Practice statistics (last 30 days)
$thirty_days_ago = strtotime('-30 days');
$recent_practices = array_filter($practice_history, function($practice) use ($thirty_days_ago) {
    return isset($practice['timestamp']) && $practice['timestamp'] >= $thirty_days_ago;
});
$total_practices = count($recent_practices);

// Active students (practiced in last 7 days)
$seven_days_ago = strtotime('-7 days');
$active_students = [];
foreach ($practice_history as $practice) {
    if (isset($practice['timestamp']) && $practice['timestamp'] >= $seven_days_ago) {
        $active_students[$practice['student_code']] = true;
    }
}
$total_active_students = count($active_students);

// Subject usage statistics
$subject_usage = [];
foreach ($practice_history as $practice) {
    $subject_id = $practice['subject_id'] ?? 'unknown';
    if (!isset($subject_usage[$subject_id])) {
        $subject_usage[$subject_id] = 0;
    }
    $subject_usage[$subject_id]++;
}
arsort($subject_usage);

// Get subject names
$subject_names = [];
foreach ($subjects as $subject) {
    $subject_names[$subject['id']] = $subject['name'];
}

// Recent activity (last 10 practices)
$recent_activity = array_slice(array_reverse($practice_history), 0, 10);

// Teacher activity
$teacher_names = [];
foreach ($users as $username => $user) {
    if ($username !== 'admin') {
        $teacher_names[$username] = $user['fullname'];
    }
}

// Security alerts
$security_alerts = count($login_attempts);

$fully_assigned_teachers = 0;
foreach ($teacher_names as $teacher_username => $_teacher_name) {
    if (!empty($teacher_subjects[$teacher_username]) && !empty($teacher_classes[$teacher_username])) {
        $fully_assigned_teachers++;
    }
}
$incomplete_teacher_assignments = max(0, $total_teachers - $fully_assigned_teachers);
$active_student_rate = $total_students > 0 ? round(($total_active_students / $total_students) * 100, 1) : 0;

// Calculate growth (compare with previous periods)
$now = time();
$this_month_start = strtotime('first day of this month 00:00:00');
$last_month_start = strtotime('first day of last month 00:00:00');

$this_month_practices = count(array_filter($practice_history, function($p) use ($this_month_start) {
    return isset($p['timestamp']) && $p['timestamp'] >= $this_month_start;
}));

$last_month_practices = count(array_filter($practice_history, function($p) use ($last_month_start, $this_month_start) {
    return isset($p['timestamp']) && $p['timestamp'] >= $last_month_start && $p['timestamp'] < $this_month_start;
}));

$practice_growth = $last_month_practices > 0 ? round((($this_month_practices - $last_month_practices) / $last_month_practices) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan hệ thống - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/dashboard.css?v=20260806" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-page">
<?php $current_page = 'dashboard.php'; include 'navbar.php'; ?>

<main class="admin-dashboard">
    <header class="dashboard-header">
        <div>
            <div class="dashboard-eyebrow"><i class="bi bi-speedometer2"></i> Trung tâm điều hành</div>
            <h1>Tổng quan hệ thống</h1>
            <p><?php echo htmlspecialchars($school_name); ?> · Theo dõi dữ liệu và hoạt động kiểm tra đánh giá.</p>
        </div>
        <div class="dashboard-period">
            <span><i class="bi bi-calendar3 me-2"></i>Năm học <?php echo htmlspecialchars($school_year); ?></span>
            <small><?php echo htmlspecialchars($semester_label); ?> · Cập nhật <?php echo date('H:i, d/m/Y'); ?></small>
        </div>
    </header>

    <section class="dashboard-stats" aria-label="Chỉ số chính">
        <article class="dashboard-stat"><div class="dashboard-stat-head"><span class="dashboard-stat-label">Giáo viên</span><span class="dashboard-stat-icon"><i class="bi bi-person-badge"></i></span></div><div class="dashboard-stat-value"><?php echo number_format($total_teachers); ?></div><div class="dashboard-stat-note <?php echo $incomplete_teacher_assignments === 0 ? 'is-good' : 'is-warning'; ?>"><?php echo $incomplete_teacher_assignments === 0 ? 'Đã hoàn tất phân công' : $incomplete_teacher_assignments . ' giáo viên chưa đủ phân công'; ?></div></article>
        <article class="dashboard-stat"><div class="dashboard-stat-head"><span class="dashboard-stat-label">Học sinh</span><span class="dashboard-stat-icon"><i class="bi bi-people"></i></span></div><div class="dashboard-stat-value"><?php echo number_format($total_students); ?></div><div class="dashboard-stat-note is-good"><?php echo number_format($total_active_students); ?> hoạt động trong 7 ngày · <?php echo $active_student_rate; ?>%</div></article>
        <article class="dashboard-stat"><div class="dashboard-stat-head"><span class="dashboard-stat-label">Lớp và môn học</span><span class="dashboard-stat-icon"><i class="bi bi-building"></i></span></div><div class="dashboard-stat-value"><?php echo number_format($total_classes); ?></div><div class="dashboard-stat-note"><?php echo number_format($total_subjects); ?> môn học đang quản lý</div></article>
        <article class="dashboard-stat"><div class="dashboard-stat-head"><span class="dashboard-stat-label">Đề kiểm tra</span><span class="dashboard-stat-icon"><i class="bi bi-file-earmark-check"></i></span></div><div class="dashboard-stat-value"><?php echo number_format($total_exams); ?></div><div class="dashboard-stat-note">Tổng số file đề trong hệ thống</div></article>
    </section>

    <section class="dashboard-alerts" aria-label="Thông tin cần chú ý">
        <a class="dashboard-alert" href="practice_statistics.php"><span class="dashboard-alert-icon"><i class="bi bi-graph-up-arrow"></i></span><span><strong><?php echo number_format($total_practices); ?> lượt ôn tập trong 30 ngày</strong><small><?php echo $practice_growth >= 0 ? 'Tăng ' : 'Giảm '; ?><?php echo abs($practice_growth); ?>% so với tháng trước</small></span></a>
        <a class="dashboard-alert" href="premium_management.php"><span class="dashboard-alert-icon"><i class="bi bi-hourglass-split"></i></span><span><strong><?php echo $pending_premium_requests; ?> yêu cầu Premium chờ duyệt</strong><small><?php echo number_format($premium_revenue); ?> VNĐ doanh thu đã duyệt</small></span></a>
        <a class="dashboard-alert" href="security_config.php"><span class="dashboard-alert-icon"><i class="bi bi-shield-exclamation"></i></span><span><strong><?php echo $security_alerts; ?> cảnh báo đăng nhập</strong><small>Kiểm tra cấu hình và lịch sử đăng nhập thất bại</small></span></a>
    </section>

    <section class="dashboard-main-grid">
        <article class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2>Hoạt động theo môn học</h2><p>Phân bổ lượt luyện tập tích lũy theo các môn được sử dụng nhiều nhất</p></div><a class="dashboard-panel-link" href="practice_statistics.php">Xem thống kê <i class="bi bi-arrow-right"></i></a></div>
            <div class="dashboard-panel-body"><div class="dashboard-chart"><canvas id="subjectChart"></canvas></div></div>
        </article>

        <article class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2>Hoạt động gần đây</h2><p>10 lượt ôn tập mới nhất của học sinh</p></div></div>
            <div class="dashboard-panel-body dashboard-activity-list">
                <?php if (empty($recent_activity)): ?>
                    <div class="dashboard-empty"><i class="bi bi-clock-history d-block fs-3 mb-2"></i>Chưa có hoạt động luyện tập.</div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <?php
                        $subject_name = $subject_names[$activity['subject_id']] ?? 'Không rõ';
                        $time_ago = max(0, time() - ($activity['timestamp'] ?? 0));
                        if ($time_ago < 3600) $time_text = max(1, floor($time_ago / 60)) . ' phút trước';
                        elseif ($time_ago < 86400) $time_text = floor($time_ago / 3600) . ' giờ trước';
                        else $time_text = floor($time_ago / 86400) . ' ngày trước';
                        ?>
                        <div class="dashboard-activity"><span class="dashboard-activity-icon"><i class="bi bi-lightning-charge"></i></span><div><strong><?php echo htmlspecialchars($activity['student_code'] ?? 'Học sinh'); ?></strong><p>Ôn tập <?php echo htmlspecialchars($subject_name); ?> · <?php echo (int) ($activity['question_count'] ?? 0); ?> câu hỏi</p></div><time><?php echo htmlspecialchars($time_text); ?></time></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section class="dashboard-panel mb-3">
        <div class="dashboard-panel-header"><div><h2>Thao tác nhanh</h2><p>Truy cập các công việc quản trị thường dùng</p></div></div>
        <div class="dashboard-panel-body"><div class="dashboard-quick-grid">
            <a class="dashboard-quick" href="manage_teachers.php"><span class="dashboard-quick-icon"><i class="bi bi-person-badge"></i></span><span><strong>Giáo viên</strong><small><?php echo $total_teachers; ?> tài khoản</small></span></a>
            <a class="dashboard-quick" href="manage_students.php"><span class="dashboard-quick-icon"><i class="bi bi-people"></i></span><span><strong>Học sinh</strong><small><?php echo number_format($total_students); ?> học sinh</small></span></a>
            <a class="dashboard-quick" href="manage_classes.php"><span class="dashboard-quick-icon"><i class="bi bi-building"></i></span><span><strong>Lớp học</strong><small><?php echo $total_classes; ?> lớp</small></span></a>
            <a class="dashboard-quick" href="manage_subjects.php"><span class="dashboard-quick-icon"><i class="bi bi-journal-bookmark"></i></span><span><strong>Môn học</strong><small><?php echo $total_subjects; ?> môn</small></span></a>
            <a class="dashboard-quick" href="manage_cleanup.php"><span class="dashboard-quick-icon"><i class="bi bi-trash3"></i></span><span><strong>Làm sạch dữ liệu</strong><small>Giải phóng dung lượng</small></span></a>
            <a class="dashboard-quick" href="backup.php"><span class="dashboard-quick-icon"><i class="bi bi-cloud-arrow-down"></i></span><span><strong>Sao lưu</strong><small>Bảo vệ dữ liệu</small></span></a>
            <a class="dashboard-quick" href="exam_statistics.php"><span class="dashboard-quick-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span><strong>Thống kê kiểm tra</strong><small><?php echo $total_exams; ?> đề</small></span></a>
            <a class="dashboard-quick" href="system_settings.php"><span class="dashboard-quick-icon"><i class="bi bi-gear"></i></span><span><strong>Cấu hình hệ thống</strong><small>Năm học và bảo mật</small></span></a>
        </div></div>
    </section>

    <section class="dashboard-lower-grid">
        <article class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2>Đội ngũ giáo viên</h2><p>Một số tài khoản giáo viên trong hệ thống</p></div><a class="dashboard-panel-link" href="manage_teachers.php">Quản lý giáo viên</a></div>
            <div class="table-responsive"><table class="table dashboard-table"><thead><tr><th>Giáo viên</th><th>Email</th><th>Phân công</th></tr></thead><tbody>
                <?php $teacher_count = 0; foreach ($teacher_names as $username => $teacher_fullname): if ($teacher_count >= 5) break; $has_assignment = !empty($teacher_subjects[$username]) && !empty($teacher_classes[$username]); ?>
                    <?php $name_parts = preg_split('/\s+/u', trim($teacher_fullname)); $initial = mb_substr($name_parts[0] ?? 'G', 0, 1, 'UTF-8') . mb_substr($name_parts[count($name_parts)-1] ?? 'V', 0, 1, 'UTF-8'); ?>
                    <tr><td><div class="dashboard-teacher"><span class="dashboard-avatar"><?php echo htmlspecialchars(mb_strtoupper($initial, 'UTF-8')); ?></span><div><strong><?php echo htmlspecialchars($teacher_fullname); ?></strong><div class="text-muted small">@<?php echo htmlspecialchars($username); ?></div></div></div></td><td><?php echo htmlspecialchars($users[$username]['email'] ?? 'Chưa cập nhật'); ?></td><td><span class="badge <?php echo $has_assignment ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>"><?php echo $has_assignment ? 'Đã hoàn tất' : 'Chưa đầy đủ'; ?></span></td></tr>
                <?php $teacher_count++; endforeach; ?>
            </tbody></table></div>
        </article>

        <article class="dashboard-panel">
            <div class="dashboard-panel-header"><div><h2>Môn học được sử dụng nhiều</h2><p>Tỷ trọng lượt luyện tập theo môn học</p></div><a class="dashboard-panel-link" href="practice_statistics.php">Xem chi tiết</a></div>
            <div class="table-responsive"><table class="table dashboard-table"><thead><tr><th>Môn học</th><th>Lượt</th><th>Tỷ trọng</th></tr></thead><tbody>
                <?php $usage_count = 0; $total_subject_practices = array_sum($subject_usage); foreach ($subject_usage as $subject_id => $usage): if ($usage_count >= 5) break; $subject_name = $subject_names[$subject_id] ?? 'Không rõ'; $percentage = $total_subject_practices > 0 ? round(($usage / $total_subject_practices) * 100, 1) : 0; ?>
                    <tr><td><strong><?php echo htmlspecialchars($subject_name); ?></strong></td><td><?php echo number_format($usage); ?></td><td style="min-width:150px"><div class="d-flex justify-content-between small mb-1"><span></span><span><?php echo $percentage; ?>%</span></div><div class="progress dashboard-progress"><div class="progress-bar" style="width:<?php echo $percentage; ?>%"></div></div></td></tr>
                <?php $usage_count++; endforeach; ?>
                <?php if ($usage_count === 0): ?><tr><td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu luyện tập.</td></tr><?php endif; ?>
            </tbody></table></div>
        </article>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const subjectData = <?php echo json_encode(array_slice($subject_usage, 0, 6, true)); ?>;
const subjectNames = <?php echo json_encode($subject_names, JSON_UNESCAPED_UNICODE); ?>;
const chartCanvas = document.getElementById('subjectChart');
if (chartCanvas) {
    const labels = Object.keys(subjectData).map(id => subjectNames[id] || 'Không rõ');
    const values = Object.values(subjectData);
    new Chart(chartCanvas, {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: ['#2e6b45', '#c64d2f', '#a97f1f', '#3a6286', '#6f6a5d', '#21513a'], borderRadius: 6, borderSkipped: false, maxBarThickness: 44 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { displayColors: false, callbacks: { label: context => context.parsed.y + ' lượt luyện tập' } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#6f6a5d', font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#ece5d4' }, ticks: { precision: 0, color: '#6f6a5d', font: { size: 11 } } }
            }
        }
    });
}
</script>
</body>
</html>
