<?php
session_name('CVD_STUDENT_SESSION');
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
        unset($s);

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

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    $usernameInput = trim($_POST['student_username'] ?? '');
    $normalizedUsername = strtolower($usernameInput);
    $studentsFile = __DIR__ . '/../admin/students.json';
    $students = [];

    if (file_exists($studentsFile)) {
        $students = json_decode(file_get_contents($studentsFile), true) ?: [];
    }

    if ($usernameInput !== '' && !preg_match('/^[a-zA-Z0-9._-]{4,30}$/', $usernameInput)) {
        $message = 'Tên đăng nhập phải có 4-30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
        $messageType = 'danger';
    } else {
        $duplicate = false;
        foreach ($students as $s) {
            $existingCode = strtolower(trim((string)($s['code'] ?? '')));
            $existingUsername = strtolower(trim((string)($s['username'] ?? '')));
            $isCurrentStudent = ($s['id'] ?? null) === $studentId;

            if ($usernameInput !== '' && $normalizedUsername === $existingCode) {
                $duplicate = true;
                break;
            }

            if (!$isCurrentStudent && $usernameInput !== '' && $existingUsername !== '' && $normalizedUsername === $existingUsername) {
                $duplicate = true;
                break;
            }
        }

        if ($duplicate) {
            $message = 'Tên đăng nhập này đã được sử dụng hoặc trùng với mã học sinh. Vui lòng chọn tên khác.';
            $messageType = 'danger';
        } else {
            $updated = false;
            foreach ($students as &$s) {
                if (($s['id'] ?? null) === $studentId) {
                    if ($usernameInput === '') {
                        unset($s['username']);
                    } else {
                        $s['username'] = $normalizedUsername;
                    }
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated && file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $message = $usernameInput === '' ? 'Đã xoá tên đăng nhập.' : 'Cập nhật tên đăng nhập thành công!';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mt-4" style="max-width: 600px; margin: 0 auto;" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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
                    <div class="info-label">Tên đăng nhập</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['username'] ?? 'Chưa thiết lập'); ?></div>
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

        <!-- Username Update -->
        <div class="card mt-4" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h5 class="mb-0">👤 Tên Đăng Nhập</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="update_username" value="1">

                    <div class="mb-3">
                        <label for="student_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="student_username" name="student_username"
                               value="<?php echo htmlspecialchars($student['username'] ?? ''); ?>"
                               pattern="[A-Za-z0-9._-]{4,30}" maxlength="30"
                               placeholder="Ví dụ: an.nguyen">
                        <div class="form-text">
                            Có thể dùng mã học sinh hoặc tên đăng nhập này để đăng nhập. Để trống và lưu nếu muốn xoá tên đăng nhập.
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Lưu Tên Đăng Nhập</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Change -->
        <div class="card mt-4" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h5 class="mb-0">🔒 Đổi Mật Khẩu</h5>
            </div>
            <div class="card-body">
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
