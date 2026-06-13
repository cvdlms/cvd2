<?php
// Admin and teacher accounts share this named session.
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$systemConfigFile = __DIR__ . '/admin/system_config.json';
$systemConfig = file_exists($systemConfigFile)
    ? json_decode(file_get_contents($systemConfigFile), true)
    : [];
$minimumPasswordLength = max(6, (int)($systemConfig['security']['password_min_length'] ?? 6));

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Vui lòng điền đầy đủ các trường.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
    } elseif (strlen($newPassword) < $minimumPasswordLength) {
        $error = "Mật khẩu mới phải có ít nhất {$minimumPasswordLength} ký tự.";
    } elseif (hash_equals($currentPassword, $newPassword)) {
        $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    } else {
        $usersFile = __DIR__ . '/admin/user.json';
        $users = json_decode(file_get_contents($usersFile), true);
        $username = $_SESSION['username'];

        if (!is_array($users)) {
            $error = 'Không thể đọc dữ liệu tài khoản.';
        } elseif (!isset($users[$username])) {
            $error = 'Người dùng không tồn tại.';
        } else {
            if (!password_verify($currentPassword, $users[$username]['password'])) {
                $error = 'Mật khẩu hiện tại không đúng.';
            } else {
                $users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $encodedUsers = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                if ($encodedUsers !== false && file_put_contents($usersFile, $encodedUsers, LOCK_EX) !== false) {
                    $_SESSION = [];

                    if (ini_get('session.use_cookies')) {
                        $cookie = session_get_cookie_params();
                        setcookie(
                            session_name(),
                            '',
                            time() - 42000,
                            $cookie['path'],
                            $cookie['domain'],
                            $cookie['secure'],
                            $cookie['httponly']
                        );
                    }

                    session_destroy();
                    header('Location: login.php?message=password_changed');
                    exit;
                } else {
                    $error = 'Lỗi khi lưu mật khẩu mới.';
                }
            }
        }
    }
}

// Determine back link
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || $_SESSION['username'] === 'admin';
$backLink = $isAdmin ? 'admin/dashboard.php' : 'teacher/teacher.php';
$title = $isAdmin ? 'Đổi Mật Khẩu Admin' : 'Đổi Mật Khẩu Giáo Viên';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="styles/main.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex justify-content-center align-items-center">
         <div class="change-password-container">
        <div class="change-password-header">
            <h2>🔐 Đổi Mật Khẩu</h2>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-4">
                <label for="current_password" class="form-label">🔒 Mật khẩu hiện tại</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Nhập mật khẩu hiện tại" />
                    <button type="button" class="btn btn-outline-secondary" id="toggleCurrentPassword"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-4">
                <label for="new_password" class="form-label">🔑 Mật khẩu mới</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="<?php echo $minimumPasswordLength; ?>" placeholder="Nhập mật khẩu mới" />
                    <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-text">Tối thiểu <?php echo $minimumPasswordLength; ?> ký tự.</div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">✅ Xác nhận mật khẩu mới</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-check-circle-fill"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="<?php echo $minimumPasswordLength; ?>" placeholder="Xác nhận mật khẩu mới" />
                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">🚀 Đổi Mật Khẩu</button>
            <a href="<?php echo $backLink; ?>" class="btn btn-outline-secondary btn-lg ms-auto d-block mt-3">⬅️ Quay Lại</a>
        </form>
    </div>
    </div>
   

    <script>
        function togglePassword(inputId, buttonId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(buttonId).querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
            togglePassword('current_password', 'toggleCurrentPassword');
        });

        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            togglePassword('new_password', 'toggleNewPassword');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            togglePassword('confirm_password', 'toggleConfirmPassword');
        });
    </script>
</body>
</html>
