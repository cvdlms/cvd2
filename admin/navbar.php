<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">🏫 CVD Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page ?? '') == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">📊 Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="schoolDataMenu" role="button" data-bs-toggle="dropdown">
            🏢 Dữ Liệu Trường
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'manage_teachers.php' ? 'active' : ''; ?>" href="manage_teachers.php">👨‍🏫 Giáo Viên</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">👨‍🎓 Học Sinh</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'manage_classes.php' ? 'active' : ''; ?>" href="manage_classes.php">🏫 Lớp Học</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'manage_subjects.php' ? 'active' : ''; ?>" href="manage_subjects.php">📚 Môn Học</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'manage_cleanup.php' ? 'active' : ''; ?>" href="manage_cleanup.php">🧹 Dọn Dẹp Dữ Liệu</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page ?? '') == 'premium_management.php' ? 'active' : ''; ?>" href="premium_management.php">⭐ Premium</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="statsMenu" role="button" data-bs-toggle="dropdown">
            📈 Thống Kê
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'exam_statistics.php' ? 'active' : ''; ?>" href="exam_statistics.php">📝 Thống Kê Kỳ Thi</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'practice_statistics.php' ? 'active' : ''; ?>" href="practice_statistics.php">📚 Thống Kê Luyện Tập</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="systemMenu" role="button" data-bs-toggle="dropdown">
            ⚙️ Hệ Thống
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'system_settings.php' ? 'active' : ''; ?>" href="system_settings.php">🏠 Tổng Quan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'semester_config.php' ? 'active' : ''; ?>" href="semester_config.php">📅 Cấu Hình Học Kì</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'premium_config.php' ? 'active' : ''; ?>" href="premium_config.php">⭐ Cấu Hình Premium</a></li>
            <li><a class="dropdown-item <?php echo ($current_page ?? '') == 'security_config.php' ? 'active' : ''; ?>" href="security_config.php">🔒 Cấu Hình Bảo Mật</a></li>
          </ul>
        </li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
            👤 <?php echo htmlspecialchars($fullname ?? 'Admin'); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="../change_password.php">🔐 Đổi mật khẩu</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">🚪 Đăng xuất</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
