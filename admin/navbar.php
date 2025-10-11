<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">🏫 CVD Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">📊 Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_teachers.php' ? 'active' : ''; ?>" href="manage_teachers.php">👨‍🏫 Quản Lý Giáo Viên</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">👨‍🎓 Quản Lý Học Sinh</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_classes.php' ? 'active' : ''; ?>" href="manage_classes.php">🏫 Quản Lý Lớp Học</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_subjects.php' ? 'active' : ''; ?>" href="manage_subjects.php">📚 Quản Lý Môn Học</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">📈 Thống Kê</a>
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
