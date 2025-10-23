<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    if (!file_exists($file)) {
        $message = 'File not uploaded.';
    } else {
        require_once 'SimpleXLSX.php';
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            // Skip header
            array_shift($rows);
            $usersFile = 'user.json';
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
            $teacherSubjectsFile = 'teacher_subjects.json';
            $teacher_subjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
            $teacherClassesFile = 'teacher_classes.json';
            $teacher_classes = json_decode(file_get_contents($teacherClassesFile), true) ?: [];
            $classes = json_decode(file_get_contents('classes.json'), true) ?: [];
            $success_count = 0;
            $errors = [];
            foreach ($rows as $row) {
                $username = trim($row[0] ?? '');
                $password = trim($row[1] ?? '');
                $fullname = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $dob = trim($row[4] ?? '');
                $subject_id = trim($row[5] ?? '');
                $class_code = trim($row[6] ?? '');
                if (empty($username) || empty($password) || empty($fullname)) {
                    $errors[] = "Missing required fields for row.";
                    continue;
                }
                if (isset($users[$username])) {
                    $errors[] = "Username $username already exists.";
                    continue;
                }
                $teacherData = [
                    'fullname' => $fullname,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'dob' => $dob
                ];
                $users[$username] = $teacherData;
                // Assign subject
                if ($subject_id) {
                    $teacher_subjects[$username] = [intval($subject_id)];
                }
                // Assign class
                if ($class_code) {
                    $class_id = null;
                    foreach ($classes as $class) {
                        if ($class['code'] === $class_code) {
                            $class_id = $class['id'];
                            break;
                        }
                    }
                    if ($class_id) {
                        $teacher_classes[$username] = [$class_id];
                    }
                }
                $success_count++;
            }
            // Save
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            file_put_contents($teacherSubjectsFile, json_encode($teacher_subjects, JSON_PRETTY_PRINT));
            file_put_contents($teacherClassesFile, json_encode($teacher_classes, JSON_PRETTY_PRINT));
            $message = "Added $success_count teachers.";
            if ($errors) {
                $message .= " Errors: " . implode(', ', $errors);
            }
        } else {
            $message = 'Failed to parse Excel file.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bulk Add Teachers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Bulk Add Teachers from Excel</h1>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excel_file" class="form-label">Upload Excel File (.xlsx)</label>
                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload and Add Teachers</button>
        </form>
    </div>
</body>
</html>
