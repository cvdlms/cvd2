<?php
// Set unique session name for Student to avoid conflict with Teacher
session_name('CVD_STUDENT_SESSION');
session_start();

// Check if timeout occurred - clear session completely
if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    session_unset();
    session_destroy();
    session_name('CVD_STUDENT_SESSION');
    session_start();
    session_regenerate_id(true);
    // Redirect to clean URL to avoid re-processing timeout on form submit
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: login.php?msg=timeout');
        exit;
    }
}

// Check if already logged in
if (isset($_SESSION['student_code'])) {
    header('Location: dashboard.php');
    exit;
}

// Load security config
$securityConfig = [];
if (file_exists(__DIR__ . '/../admin/system_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
    $securityConfig = $config['security'] ?? [];
}

$maxAttempts = $securityConfig['max_login_attempts'] ?? 5;
$lockoutDuration = $securityConfig['lockout_duration'] ?? 900; // seconds

// Login attempts tracking file
$attemptsFile = __DIR__ . '/../admin/student_login_attempts.json';
if (!file_exists($attemptsFile)) {
    file_put_contents($attemptsFile, json_encode([]));
}
$loginAttempts = json_decode(file_get_contents($attemptsFile), true) ?: [];

$message = '';
$isTimeout = isset($_GET['msg']) && $_GET['msg'] === 'timeout';

if ($isTimeout) {
    $message = '⏰ Phiên làm việc đã hết hạn do không hoạt động. Vui lòng đăng nhập lại.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentCode = trim($_POST['student_code'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $currentTime = time();

    if (empty($studentCode) || empty($password)) {
        $message = 'Vui lòng nhập đầy đủ mã học sinh và mật khẩu!';
    } else {
        // Check if account is locked
        if (isset($loginAttempts[$studentCode])) {
            $attemptData = $loginAttempts[$studentCode];
            $attempts = $attemptData['attempts'] ?? 0;
            $lockTime = $attemptData['lock_time'] ?? 0;

            // Check if still locked
            if ($attempts >= $maxAttempts && ($currentTime - $lockTime) < $lockoutDuration) {
                $remainingTime = $lockoutDuration - ($currentTime - $lockTime);
                $remainingMinutes = ceil($remainingTime / 60);
                $message = "🔒 Tài khoản đã bị khóa do đăng nhập sai quá nhiều lần. Vui lòng thử lại sau $remainingMinutes phút.";
            } else if ($attempts >= $maxAttempts && ($currentTime - $lockTime) >= $lockoutDuration) {
                // Reset attempts after lockout period
                unset($loginAttempts[$studentCode]);
                file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
            }
        }

        // Only proceed if not locked
        if (!$message) {
            // Load students data
            $studentsFile = __DIR__ . '/../admin/students.json';
            $classesFile = __DIR__ . '/../admin/classes.json';

            $students = [];
            $classes = [];

            if (file_exists($studentsFile)) {
                $students = json_decode(file_get_contents($studentsFile), true) ?: [];
            }

            if (file_exists($classesFile)) {
                $classes = json_decode(file_get_contents($classesFile), true) ?: [];
            }

            // Find student
            $foundStudent = null;

            foreach ($students as $student) {
                if ($student['code'] === $studentCode) {
                    $foundStudent = $student;
                    break;
                }
            }

            // Check password (default to '123456' if not set)
            $storedPassword = $foundStudent['password'] ?? '123456';
            if ($foundStudent && $password === $storedPassword) {
                // Successful login - reset attempts
                if (isset($loginAttempts[$studentCode])) {
                    unset($loginAttempts[$studentCode]);
                    file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
                }

                // Find class
                $foundClass = null;
                foreach ($classes as $class) {
                    if ($class['id'] === $foundStudent['class_id'] || $class['code'] === $foundStudent['class_id']) {
                        $foundClass = $class;
                        break;
                    }
                }

                // Login successful - regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['student_code'] = $studentCode;
                $_SESSION['student_name'] = $foundStudent['name'];
                $_SESSION['student_id'] = $foundStudent['id'];
                $_SESSION['student_class'] = $foundClass ? $foundClass['name'] : '';
                $_SESSION['student_class_code'] = $foundClass ? $foundClass['code'] : '';
                $_SESSION['LAST_ACTIVITY'] = time(); // Session timeout tracking

                header('Location: dashboard.php');
                exit;
            } else {
                // Failed login - increment attempts
                if (!isset($loginAttempts[$studentCode])) {
                    $loginAttempts[$studentCode] = ['attempts' => 0, 'lock_time' => 0];
                }
                $loginAttempts[$studentCode]['attempts']++;
                
                if ($loginAttempts[$studentCode]['attempts'] >= $maxAttempts) {
                    $loginAttempts[$studentCode]['lock_time'] = $currentTime;
                    $message = "🔒 Bạn đã đăng nhập sai $maxAttempts lần. Tài khoản đã bị khóa trong " . ($lockoutDuration / 60) . " phút.";
                } else {
                    $remainingAttempts = $maxAttempts - $loginAttempts[$studentCode]['attempts'];
                    $message = "❌ Mã học sinh hoặc mật khẩu không đúng! Còn $remainingAttempts lần thử.";
                }
                
                file_put_contents($attemptsFile, json_encode($loginAttempts, JSON_PRETTY_PRINT));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Học Sinh - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            background: white;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="text-center mb-4">
                <h2 class="text-primary">🏫 CVD - Học Sinh</h2>
                <p class="text-muted">Đăng nhập để làm bài kiểm tra</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="student_code" class="form-label">Mã Học Sinh *</label>
                    <input type="text" class="form-control" id="student_code" name="student_code"
                           value="<?php echo $isTimeout ? '' : htmlspecialchars($_POST['student_code'] ?? ''); ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật Khẩu *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Đăng Nhập</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="../index.html" class="text-decoration-none">← Quay lại trang chủ</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
