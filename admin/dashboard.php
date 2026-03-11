<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
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

$fullname = $users['admin']['fullname'] ?? 'Admin';

// Calculate basic stats
$total_teachers = count($users) - 1; // Exclude admin
$total_students = count($students);
$total_subjects = count($subjects);
$total_classes = count($classes);

// Count exams from teacher folders
$total_exams = 0;
$exam_dir = '../teacher/exams/generated';
if (file_exists($exam_dir)) {
    $exam_files = glob($exam_dir . '/*.json');
    $total_exams = count($exam_files);
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
    <title>Admin Dashboard - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .stat-card.primary { border-color: #0d6efd; }
        .stat-card.success { border-color: #198754; }
        .stat-card.info { border-color: #0dcaf0; }
        .stat-card.warning { border-color: #ffc107; }
        .stat-card.danger { border-color: #dc3545; }
        .stat-card.purple { border-color: #6f42c1; }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .trend-up { color: #198754; }
        .trend-down { color: #dc3545; }
        
        .quick-action-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .quick-action-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .activity-item {
            border-left: 3px solid #e9ecef;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'dashboard.php'; include 'navbar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-speedometer2"></i> Tổng Quan Hệ Thống</h1>
        <div class="text-muted">
            <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <!-- Main Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card primary">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Giáo Viên</h6>
                <h2 class="mb-0"><?php echo $total_teachers; ?></h2>
                <small class="text-muted">Đang hoạt động</small>
              </div>
              <div class="stat-icon text-primary">
                <i class="bi bi-person-badge"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card success">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Học Sinh</h6>
                <h2 class="mb-0"><?php echo $total_students; ?></h2>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> <?php echo $total_active_students; ?> hoạt động
                </small>
              </div>
              <div class="stat-icon text-success">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card info">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Lớp Học</h6>
                <h2 class="mb-0"><?php echo $total_classes; ?></h2>
                <small class="text-muted"><?php echo $total_subjects; ?> môn học</small>
              </div>
              <div class="stat-icon text-info">
                <i class="bi bi-house-door-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card warning">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Đề Thi</h6>
                <h2 class="mb-0"><?php echo $total_exams; ?></h2>
                <small class="text-muted">Đã tạo</small>
              </div>
              <div class="stat-icon text-warning">
                <i class="bi bi-file-earmark-text"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Secondary Statistics -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card purple">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Lượt Ôn Tập</h6>
                <h2 class="mb-0"><?php echo number_format($total_practices); ?></h2>
                <small class="<?php echo $practice_growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <i class="bi bi-arrow-<?php echo $practice_growth >= 0 ? 'up' : 'down'; ?>"></i> 
                    <?php echo abs($practice_growth); ?>% so với tháng trước
                </small>
              </div>
              <div class="stat-icon text-purple">
                <i class="bi bi-graph-up"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card success">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Premium</h6>
                <h2 class="mb-0"><?php echo $total_premium_students; ?></h2>
                <small class="text-success">
                    <?php echo number_format($premium_revenue); ?> VNĐ doanh thu
                </small>
              </div>
              <div class="stat-icon text-success">
                <i class="bi bi-star-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card warning">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Chờ Duyệt</h6>
                <h2 class="mb-0"><?php echo $pending_premium_requests; ?></h2>
                <small class="text-warning">Yêu cầu premium</small>
              </div>
              <div class="stat-icon text-warning">
                <i class="bi bi-hourglass-split"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card stat-card danger">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="text-muted mb-2">Cảnh Báo</h6>
                <h2 class="mb-0"><?php echo $security_alerts; ?></h2>
                <small class="text-danger">Đăng nhập thất bại</small>
              </div>
              <div class="stat-icon text-danger">
                <i class="bi bi-shield-exclamation"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts and Activity Row -->
    <div class="row g-3 mb-4">
      <!-- Subject Usage Chart -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-bar-chart-line"></i> Thống Kê Môn Học</h5>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="subjectChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-clock-history"></i> Hoạt Động Gần Đây</h5>
          </div>
          <div class="card-body" style="max-height: 300px; overflow-y: auto;">
            <?php if (empty($recent_activity)): ?>
              <p class="text-muted text-center">Chưa có hoạt động nào</p>
            <?php else: ?>
              <?php foreach ($recent_activity as $activity): ?>
                <?php 
                $subject_name = $subject_names[$activity['subject_id']] ?? 'Không rõ';
                $time_ago = time() - ($activity['timestamp'] ?? 0);
                $time_text = '';
                if ($time_ago < 3600) {
                    $time_text = floor($time_ago / 60) . ' phút trước';
                } elseif ($time_ago < 86400) {
                    $time_text = floor($time_ago / 3600) . ' giờ trước';
                } else {
                    $time_text = floor($time_ago / 86400) . ' ngày trước';
                }
                ?>
                <div class="activity-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?php echo htmlspecialchars($activity['student_code']); ?></strong> 
                      ôn tập <span class="badge bg-info"><?php echo htmlspecialchars($subject_name); ?></span>
                    </div>
                    <small class="text-muted"><?php echo $time_text; ?></small>
                  </div>
                  <small class="text-muted"><?php echo $activity['question_count']; ?> câu hỏi</small>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-lightning-charge"></i> Thao Tác Nhanh</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <a href="manage_teachers.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-person-badge fs-1 text-primary"></i>
                      <h6 class="mt-2">Quản Lý Giáo Viên</h6>
                      <small class="text-muted"><?php echo $total_teachers; ?> giáo viên</small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="manage_students.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-people fs-1 text-success"></i>
                      <h6 class="mt-2">Quản Lý Học Sinh</h6>
                      <small class="text-muted"><?php echo $total_students; ?> học sinh</small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="manage_classes.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-house-door fs-1 text-info"></i>
                      <h6 class="mt-2">Quản Lý Lớp</h6>
                      <small class="text-muted"><?php echo $total_classes; ?> lớp học</small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="manage_subjects.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-journal-bookmark fs-1 text-warning"></i>
                      <h6 class="mt-2">Quản Lý Môn Học</h6>
                      <small class="text-muted"><?php echo $total_subjects; ?> môn học</small>
                    </div>
                  </div>
                </a>
              </div>
            </div>
            
            <div class="row g-3 mt-2">
              <div class="col-md-3">
                <a href="premium_management.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-star fs-1 text-warning"></i>
                      <h6 class="mt-2">Quản Lý Premium</h6>
                      <small class="text-muted">
                        <?php echo $pending_premium_requests > 0 ? 
                          '<span class="badge bg-danger">' . $pending_premium_requests . ' chờ duyệt</span>' : 
                          'Không có yêu cầu mới'; ?>
                      </small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="system_settings.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-gear fs-1 text-secondary"></i>
                      <h6 class="mt-2">Cài Đặt Hệ Thống</h6>
                      <small class="text-muted">Cấu hình chung</small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="practice_statistics.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-graph-up-arrow fs-1 text-primary"></i>
                      <h6 class="mt-2">Thống Kê Ôn Tập</h6>
                      <small class="text-muted"><?php echo number_format($total_practices); ?> lượt</small>
                    </div>
                  </div>
                </a>
              </div>
              
              <div class="col-md-3">
                <a href="exam_statistics.php" class="text-decoration-none">
                  <div class="card quick-action-card text-center h-100">
                    <div class="card-body">
                      <i class="bi bi-file-earmark-bar-graph fs-1 text-success"></i>
                      <h6 class="mt-2">Thống Kê Đề Thi</h6>
                      <small class="text-muted"><?php echo $total_exams; ?> đề thi</small>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Teachers & Top Subjects -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-trophy"></i> Giáo Viên</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $count = 0;
                  foreach ($teacher_names as $username => $fullname): 
                    if ($count >= 5) break;
                    $email = $users[$username]['email'] ?? '';
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($fullname); ?></td>
                      <td><?php echo htmlspecialchars($email); ?></td>
                      <td><span class="badge bg-success">Hoạt động</span></td>
                    </tr>
                  <?php 
                    $count++;
                  endforeach; 
                  ?>
                </tbody>
              </table>
            </div>
            <div class="text-center mt-2">
              <a href="manage_teachers.php" class="btn btn-sm btn-outline-primary">Xem Tất Cả</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-bookmark-star"></i> Môn Học Phổ Biến</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Môn Học</th>
                    <th>Lượt Ôn Tập</th>
                    <th>Tỷ Lệ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $count = 0;
                  $total_subject_practices = array_sum($subject_usage);
                  foreach ($subject_usage as $subject_id => $usage): 
                    if ($count >= 5) break;
                    $name = $subject_names[$subject_id] ?? 'Không rõ';
                    $percentage = $total_subject_practices > 0 ? round(($usage / $total_subject_practices) * 100, 1) : 0;
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($name); ?></td>
                      <td><?php echo number_format($usage); ?></td>
                      <td>
                        <div class="progress" style="height: 20px;">
                          <div class="progress-bar" role="progressbar" 
                               style="width: <?php echo $percentage; ?>%" 
                               aria-valuenow="<?php echo $percentage; ?>" 
                               aria-valuemin="0" aria-valuemax="100">
                            <?php echo $percentage; ?>%
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php 
                    $count++;
                  endforeach; 
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Subject Usage Chart
    const subjectData = <?php echo json_encode(array_slice($subject_usage, 0, 6, true)); ?>;
    const subjectNames = <?php echo json_encode($subject_names); ?>;
    
    const labels = Object.keys(subjectData).map(id => subjectNames[id] || 'Không rõ');
    const data = Object.values(subjectData);
    
    const ctx = document.getElementById('subjectChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Lượt ôn tập',
          data: data,
          backgroundColor: [
            'rgba(13, 110, 253, 0.8)',
            'rgba(25, 135, 84, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(220, 53, 69, 0.8)',
            'rgba(13, 202, 240, 0.8)',
            'rgba(111, 66, 193, 0.8)'
          ],
          borderColor: [
            'rgba(13, 110, 253, 1)',
            'rgba(25, 135, 84, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(220, 53, 69, 1)',
            'rgba(13, 202, 240, 1)',
            'rgba(111, 66, 193, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  </script>
</body>
</html>
