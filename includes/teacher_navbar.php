<?php
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';
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
          <a class="nav-link <?php echo basename(__FILE__) == 'teacher.php' ? 'active' : ''; ?>" href="teacher.php">📊 Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">👨‍🎓 Xem Học Sinh</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'exam_creation.php' ? 'active' : ''; ?>" href="exam_creation.php">📝 Tạo Bài Kiểm Tra</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'question_bank.php' ? 'active' : ''; ?>" href="question_bank.php">📚 Ngân Hàng Câu Hỏi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename(__FILE__) == 'manage_result.php' ? 'active' : ''; ?>" href="manage_result.php">📈 Quản Lý Kết Quả</a>
        </li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            👤 <?php echo htmlspecialchars($fullname ?? 'Giáo Viên'); ?>
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
