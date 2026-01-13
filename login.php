<?php
session_start();

$users = json_decode(file_get_contents(__DIR__ . '/admin/user.json'), true);

// Load security config
$securityConfig = [];
if (file_exists(__DIR__ . '/admin/system_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/admin/system_config.json'), true);
    $securityConfig = $config['security'] ?? [];
}

$maxAttempts = $securityConfig['max_login_attempts'] ?? 5;
$lockoutDuration = $securityConfig['lockout_duration'] ?? 900; // seconds

// Login attempts tracking file
$attemptsFile = __DIR__ . '/admin/login_attempts.json';
if (!file_exists($attemptsFile)) {
    file_put_contents($attemptsFile, json_encode([]));
}
$loginAttempts = json_decode(file_get_contents($attemptsFile), true) ?: [];

$error = '';
$success = '';

if (isset($_GET['message']) && $_GET['message'] === 'password_changed') {
    $success = 'Mật khẩu đã được đổi thành công.';
}

if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    $error = '⏰ Phiên làm việc đã hết hạn do không hoạt động. Vui lòng đăng nhập lại.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $currentTime = time();

    // Check if account is locked
    if (isset($loginAttempts[$username])) {
        $attemptData = $loginAttempts[$username];
        $attempts = $attemptData['attempts'] ?? 0;
        $lockTime = $attemptData['lock_time'] ?? 0;

        // Check if still locked
        if ($attempts >= $maxAttempts && ($currentTime - $lockTime) < $lockoutDuration) {
            $remainingTime = $lockoutDuration - ($currentTime - $lockTime);
            $remainingMinutes = ceil($remainingTime / 60);
            $error = "🔒 Tài khoản đã bị khóa do đăng nhập sai quá nhiều lần. Vui lòng thử lại sau $remainingMinutes phút.";
        } else if ($attempts >= $maxAttempts && ($currentTime - $lockTime) >= $lockoutDuration) {
            // Reset attempts after lockout period
            unset($loginAttempts[$username]);
            file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
        }
    }

    // Only proceed if not locked
    if (!$error) {
        if (isset($users[$username])) {
            if (password_verify($password, $users[$username]['password'])) {
                // Successful login - reset attempts
                if (isset($loginAttempts[$username])) {
                    unset($loginAttempts[$username]);
                    file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
                }

                $_SESSION['username'] = $username;
                $_SESSION['role'] = ($username === 'admin') ? 'admin' : 'teacher';
                $_SESSION['LAST_ACTIVITY'] = time(); // Session timeout tracking
                if ($username === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: teacher/teacher.php');
                }
                exit;
            } else {
                // Failed login - increment attempts
                if (!isset($loginAttempts[$username])) {
                    $loginAttempts[$username] = ['attempts' => 0, 'lock_time' => 0];
                }
                $loginAttempts[$username]['attempts']++;
                
                if ($loginAttempts[$username]['attempts'] >= $maxAttempts) {
                    $loginAttempts[$username]['lock_time'] = $currentTime;
                    $error = "🔒 Bạn đã đăng nhập sai $maxAttempts lần. Tài khoản đã bị khóa trong " . ($lockoutDuration / 60) . " phút.";
                } else {
                    $remainingAttempts = $maxAttempts - $loginAttempts[$username]['attempts'];
                    $error = "❌ Sai mật khẩu. Còn $remainingAttempts lần thử.";
                }
                
                file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
            }
        } else {
            $error = '❌ Tên đăng nhập không tồn tại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Đăng nhập Giáo Viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 50px;
            max-width: 450px;
            width: 100%;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header h2 {
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .login-header p {
            color: #666;
            font-size: 16px;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e1e5e9;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        .input-group-text {
            background: #f8f9fa;
            border-radius: 12px 0 0 12px;
            border: 2px solid #e1e5e9;
            border-right: none;
            color: #667eea;
            font-size: 18px;
        }
        .form-control:focus + .input-group-text,
        .input-group-text:focus {
            border-color: #667eea;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert {
            border-radius: 12px;
            border: none;
            font-weight: 500;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>🔐 Đăng nhập Giáo Viên</h2>
            <p>Vui lòng nhập thông tin để truy cập trang quản lý</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-4">
                <label for="username" class="form-label">👤 Tên đăng nhập</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="Nhập tên đăng nhập" />
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">🔒 Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu" />
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-login">🚀 Đăng nhập</button>
        </form>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
