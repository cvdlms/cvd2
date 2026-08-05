<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF'] ?? '');
$admin_name = $fullname ?? ($_SESSION['fullname'] ?? 'Quản trị viên');

$school_data_pages = [
    'manage_teachers.php',
    'manage_students.php',
    'manage_classes.php',
    'manage_subjects.php',
    'manage_cleanup.php',
];
$statistics_pages = ['exam_statistics.php', 'practice_statistics.php'];
$system_pages = [
    'system_settings.php',
    'semester_config.php',
    'premium_config.php',
    'premium_pricing.php',
    'security_config.php',
    'backup.php',
];

function admin_nav_group_active(array $pages, string $current_page): string
{
    return in_array($current_page, $pages, true) ? ' active' : '';
}

function admin_nav_item_active(string $page, string $current_page): string
{
    return $page === $current_page ? ' active' : '';
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/admin-navbar.css?v=20260614" rel="stylesheet">

<nav class="navbar navbar-expand-xl admin-navbar" aria-label="Điều hướng quản trị">
  <div class="container-fluid admin-navbar__container">
    <a class="navbar-brand admin-navbar__brand" href="dashboard.php" aria-label="CVD Admin - Trang tổng quan">
      <span class="admin-navbar__brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
      <span>
        <strong>CVD Admin</strong>
        <small>Quản lý kiểm tra đánh giá</small>
      </span>
    </a>

    <button class="navbar-toggler admin-navbar__toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
      aria-controls="adminNavbar" aria-expanded="false" aria-label="Mở menu quản trị">
      <i class="bi bi-list"></i>
    </button>

    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav admin-navbar__nav me-auto">
        <li class="nav-item">
          <a class="nav-link<?php echo admin_nav_item_active('dashboard.php', $current_page); ?>" href="dashboard.php">
            <i class="bi bi-grid-1x2"></i>
            <span>Tổng quan</span>
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?php echo admin_nav_group_active($school_data_pages, $current_page); ?>"
            href="#" id="schoolDataMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-database"></i>
            <span>Dữ liệu</span>
          </a>
          <ul class="dropdown-menu admin-submenu" aria-labelledby="schoolDataMenu">
            <li class="admin-submenu__heading">
              <span>Dữ liệu trường học</span>
              <small>Quản lý các danh mục sử dụng trong hệ thống</small>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('manage_teachers.php', $current_page); ?>" href="manage_teachers.php">
                <span class="admin-submenu__icon"><i class="bi bi-person-video3"></i></span>
                <span><strong>Giáo viên</strong><small>Tài khoản và phân công chuyên môn</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('manage_students.php', $current_page); ?>" href="manage_students.php">
                <span class="admin-submenu__icon"><i class="bi bi-people"></i></span>
                <span><strong>Học sinh</strong><small>Hồ sơ, lớp học và nhập danh sách</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('manage_classes.php', $current_page); ?>" href="manage_classes.php">
                <span class="admin-submenu__icon"><i class="bi bi-building"></i></span>
                <span><strong>Lớp học</strong><small>Danh mục lớp và khối học</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('manage_subjects.php', $current_page); ?>" href="manage_subjects.php">
                <span class="admin-submenu__icon"><i class="bi bi-journal-bookmark"></i></span>
                <span><strong>Môn học</strong><small>Danh mục môn và cấu hình liên quan</small></span>
              </a>
            </li>
            <li class="admin-submenu__separator" aria-hidden="true"></li>
            <li>
              <a class="dropdown-item admin-submenu__utility<?php echo admin_nav_item_active('manage_cleanup.php', $current_page); ?>" href="manage_cleanup.php">
                <span class="admin-submenu__icon"><i class="bi bi-trash3"></i></span>
                <span><strong>Làm sạch dữ liệu</strong><small>Kiểm tra và xóa dữ liệu không còn sử dụng</small></span>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link<?php echo admin_nav_item_active('premium_management.php', $current_page); ?>" href="premium_management.php">
            <i class="bi bi-patch-check"></i>
            <span>Premium GV</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?php echo admin_nav_item_active('manage_student_premium.php', $current_page); ?>" href="manage_student_premium.php">
            <i class="bi bi-person-check"></i>
            <span>Premium HS</span>
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?php echo admin_nav_group_active($statistics_pages, $current_page); ?>"
            href="#" id="statsMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bar-chart-line"></i>
            <span>Thống kê</span>
          </a>
          <ul class="dropdown-menu admin-submenu admin-submenu--compact" aria-labelledby="statsMenu">
            <li class="admin-submenu__heading">
              <span>Báo cáo hệ thống</span>
              <small>Theo dõi hoạt động kiểm tra và luyện tập</small>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('exam_statistics.php', $current_page); ?>" href="exam_statistics.php">
                <span class="admin-submenu__icon"><i class="bi bi-clipboard-data"></i></span>
                <span><strong>Thống kê kỳ thi</strong><small>Kết quả và mức độ tham gia kiểm tra</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('practice_statistics.php', $current_page); ?>" href="practice_statistics.php">
                <span class="admin-submenu__icon"><i class="bi bi-graph-up-arrow"></i></span>
                <span><strong>Thống kê luyện tập</strong><small>Tần suất và kết quả luyện tập</small></span>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?php echo admin_nav_group_active($system_pages, $current_page); ?>"
            href="#" id="systemMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear"></i>
            <span>Hệ thống</span>
          </a>
          <ul class="dropdown-menu admin-submenu" aria-labelledby="systemMenu">
            <li class="admin-submenu__heading">
              <span>Thiết lập hệ thống</span>
              <small>Cấu hình vận hành, bảo mật và dữ liệu</small>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('system_settings.php', $current_page); ?>" href="system_settings.php">
                <span class="admin-submenu__icon"><i class="bi bi-sliders"></i></span>
                <span><strong>Cấu hình chung</strong><small>Thông tin và thiết lập mặc định</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('semester_config.php', $current_page); ?>" href="semester_config.php">
                <span class="admin-submenu__icon"><i class="bi bi-calendar3"></i></span>
                <span><strong>Học kỳ</strong><small>Thời gian và học kỳ đang hoạt động</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('premium_config.php', $current_page); ?>" href="premium_config.php">
                <span class="admin-submenu__icon"><i class="bi bi-stars"></i></span>
                <span><strong>Cấu hình Premium</strong><small>Tính năng và chính sách sử dụng</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('premium_pricing.php', $current_page); ?>" href="premium_pricing.php">
                <span class="admin-submenu__icon"><i class="bi bi-cash-coin"></i></span>
                <span><strong>Giá Premium</strong><small>Gói dịch vụ và mức giá áp dụng</small></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item<?php echo admin_nav_item_active('security_config.php', $current_page); ?>" href="security_config.php">
                <span class="admin-submenu__icon"><i class="bi bi-shield-lock"></i></span>
                <span><strong>Bảo mật</strong><small>Đăng nhập và chính sách an toàn</small></span>
              </a>
            </li>
            <li class="admin-submenu__separator" aria-hidden="true"></li>
            <li>
              <a class="dropdown-item admin-submenu__utility<?php echo admin_nav_item_active('backup.php', $current_page); ?>" href="backup.php">
                <span class="admin-submenu__icon"><i class="bi bi-cloud-arrow-down"></i></span>
                <span><strong>Sao lưu dữ liệu</strong><small>Tạo và khôi phục bản sao hệ thống</small></span>
              </a>
            </li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav admin-navbar__account">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle admin-navbar__account-toggle" href="#" id="userMenu" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <span class="admin-navbar__avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($admin_name, 0, 1))); ?></span>
            <span class="admin-navbar__account-name">
              <small>Quản trị viên</small>
              <strong><?php echo htmlspecialchars($admin_name); ?></strong>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end admin-submenu admin-submenu--account" aria-labelledby="userMenu">
            <li class="admin-submenu__heading">
              <span>Tài khoản quản trị</span>
              <small>Quản lý thông tin đăng nhập của bạn</small>
            </li>
            <li>
              <a class="dropdown-item" href="../change_password.php">
                <span class="admin-submenu__icon"><i class="bi bi-key"></i></span>
                <span><strong>Đổi mật khẩu</strong><small>Cập nhật mật khẩu đăng nhập</small></span>
              </a>
            </li>
            <li class="admin-submenu__separator" aria-hidden="true"></li>
            <li>
              <a class="dropdown-item admin-submenu__danger" href="../logout.php">
                <span class="admin-submenu__icon"><i class="bi bi-box-arrow-right"></i></span>
                <span><strong>Đăng xuất</strong><small>Kết thúc phiên làm việc hiện tại</small></span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script>
(function () {
    window.setInterval(function () {
        fetch('api/keep_alive.php', { credentials: 'same-origin' }).catch(function () {});
    }, 300000);
})();
</script>
