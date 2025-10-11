<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'Vui lòng nhập đầy đủ thông tin!';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Mật khẩu mới và xác nhận không khớp!';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        $messageType = 'danger';
    } else {
        // Load students data
        $studentsFile = __DIR__ . '/../admin/students.json';
        $students = [];

        if (file_exists($studentsFile)) {
            $students = json_decode(file_get_contents($studentsFile), true) ?: [];
        }

        // Find and update student
        $updated = false;
        foreach ($students as &$s) {
            if ($s['id'] === $studentId) {
                $storedPassword = $s['password'] ?? '123456';
                if ($currentPassword === $storedPassword) {
                    $s['password'] = $newPassword;
                    $updated = true;
                } else {
                    $message = 'Mật khẩu hiện tại không đúng!';
                    $messageType = 'danger';
                }
                break;
            }
        }

        if ($updated) {
            // Save back to file
            if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $message = 'Đổi mật khẩu thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi khi lưu dữ liệu. Vui lòng thử lại!';
                $messageType = 'danger';
            }
        }
    }
}

// Load student data
$studentsFile = __DIR__ . '/../admin/students.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$student = null;
$class = null;

if (file_exists($studentsFile)) {
    $students = json_decode(file_get_contents($studentsFile), true) ?: [];
    foreach ($students as $s) {
        if ($s['id'] === $studentId) {
            $student = $s;
            break;
        }
    }
}

if ($student && file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
    foreach ($classes as $c) {
        if ($c['id'] === $student['class_id']) {
            $class = $c;
            break;
        }
    }
}

if (!$student) {
    die('Student data not found.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Cá Nhân - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .profile-card {
            max-width: 600px;
            margin: 2rem auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }
        .info-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .info-value {
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🏫 CVD - Học Sinh</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">📈 Kết Quả</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">👤 Thông Tin Cá Nhân</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            👤 <?php echo htmlspecialchars($_SESSION['student_name']); ?> (<?php echo htmlspecialchars($_SESSION['student_code']); ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">👤 Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">🚪 Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Profile Information -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    👤
                </div>
                <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                <p class="mb-0">Mã học sinh: <?php echo htmlspecialchars($student['code']); ?></p>
            </div>

            <div class="card-body p-0">
                <div class="info-item">
                    <div class="info-label">Họ và tên</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Mã học sinh</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['code']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Lớp</div>
                    <div class="info-value"><?php echo htmlspecialchars($class ? $class['name'] : 'N/A'); ?> (<?php echo htmlspecialchars($class ? $class['code'] : 'N/A'); ?>)</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Giới tính</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['gender'] === 'M' ? 'Nam' : ($student['gender'] === 'F' ? 'Nữ' : 'Khác')); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Ngày sinh</div>
                    <div class="info-value"><?php echo htmlspecialchars(date('d/m/Y', strtotime($student['birth_date']))); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['email'] ?: 'Chưa cập nhật'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Ghi chú</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['notes'] ?: 'Không có ghi chú'); ?></div>
                </div>
            </div>
        </div>

        <!-- Password Change -->
        <div class="card mt-4" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h5 class="mb-0">🔒 Đổi Mật Khẩu</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật Khẩu Hiện Tại *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật Khẩu Mới *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác Nhận Mật Khẩu Mới *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">🔄 Đổi Mật Khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
