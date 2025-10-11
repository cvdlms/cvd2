<?php
session_start();

// Check if already logged in
if (isset($_SESSION['student_code'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentCode = trim($_POST['student_code'] ?? '');
    $classCode = trim($_POST['class_code'] ?? '');

    if (empty($studentCode) || empty($classCode)) {
        $message = 'Vui lòng nhập đầy đủ mã học sinh và mã lớp!';
    } else {
        // Load students data
        $studentsFile = __DIR__ . '/../teacher/students.json';
        $classesFile = __DIR__ . '/../teacher/classes.json';

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
        $studentClass = null;

        foreach ($students as $student) {
            if ($student['code'] === $studentCode) {
                $foundStudent = $student;

                // Find class info
                foreach ($classes as $class) {
                    if ($class['id'] === $student['class_id']) {
                        $studentClass = $class;
                        break;
                    }
                }
                break;
            }
        }

        if ($foundStudent && $studentClass && $studentClass['code'] === $classCode) {
            // Login successful
            $_SESSION['student_code'] = $studentCode;
            $_SESSION['student_name'] = $foundStudent['name'];
            $_SESSION['student_class'] = $studentClass['name'];
            $_SESSION['student_class_code'] = $studentClass['code'];
            $_SESSION['student_id'] = $foundStudent['id'];

            header('Location: dashboard.php');
            exit;
        } else {
            $message = 'Mã học sinh hoặc mã lớp không đúng!';
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
                           value="<?php echo htmlspecialchars($_POST['student_code'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="class_code" class="form-label">Mã Lớp *</label>
                    <input type="text" class="form-control" id="class_code" name="class_code"
                           value="<?php echo htmlspecialchars($_POST['class_code'] ?? ''); ?>" required>
                    <div class="form-text">Ví dụ: TH6A1, TH7B2, TH8C3...</div>
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
