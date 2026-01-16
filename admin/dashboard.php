<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load data
$users = json_decode(file_get_contents('user.json'), true) ?: [];
$subjects = json_decode(file_get_contents('subjects.json'), true) ?: [];
$teacher_subjects = json_decode(file_get_contents('teacher_subjects.json'), true) ?: [];

$fullname = $users['admin']['fullname'] ?? 'Admin';

// Calculate stats
$total_users = count($users);
$total_teachers = count(array_filter($users, function($user) { return isset($user['role']) && $user['role'] === 'teacher'; }));
$total_students = count(array_filter($users, function($user) { return isset($user['role']) && $user['role'] === 'student'; }));
$total_subjects = count($subjects);
// For exams, perhaps count from somewhere, but for now 0
$total_exams = 0;

// Fix: Since user.json does not have 'role', count all except admin as teachers for now
$total_teachers = 0;
$total_students = 0;
foreach ($users as $username => $user) {
    if ($username !== 'admin') {
        $total_teachers++;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'dashboard.php'; include 'navbar.php'; ?>

  <div class="main-content">
    <h1>Trang Quản Trị</h1>
    <div class="row">
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-person-badge"></i> Quản Lý Giáo Viên</h5>
            <a href="manage_teachers.php" class="btn btn-primary">Xem</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-house-door"></i> Quản Lý Lớp</h5>
            <a href="manage_classes.php" class="btn btn-primary">Xem</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-people"></i> Quản Lý Học Sinh</h5>
            <a href="manage_students.php" class="btn btn-primary">Xem</a>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-journal-bookmark"></i> Quản Lý Môn Học</h5>
            <a href="manage_subjects.php" class="btn btn-primary">Xem</a>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-3 d-none">
      <div class="col-md-12">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-bar-chart-line"></i> Thống Kê</h5>
            <a href="#" class="btn btn-primary">Xem</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5>Thống Kê Tổng Quan</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <div class="text-center">
                  <h3><?php echo $total_users; ?></h3>
                  <p>Tổng Người Dùng</p>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h3><?php echo $total_teachers; ?></h3>
                  <p>Giáo Viên</p>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h3><?php echo $total_students; ?></h3>
                  <p>Học Sinh</p>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h3><?php echo $total_subjects; ?></h3>
                  <p>Môn Học</p>
                </div>
              </div>
              <div class="col-md-2">
                <div class="text-center">
                  <h3><?php echo $total_exams; ?></h3>
                  <p>Đề Thi</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
