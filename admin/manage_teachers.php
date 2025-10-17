<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Load data
$usersFile = 'user.json';
$users = json_decode(file_get_contents($usersFile), true) ?: [];
$subjectsFile = 'subjects.json';
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$teacherSubjectsFile = 'teacher_subjects.json';
$teacher_subjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];

// Filter teachers (all users except admin)
$teachers = [];
foreach ($users as $username => $user) {
    if ($username !== 'admin') {
        $teachers[$username] = $user;
    }
}

// Handle add teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];

    if (isset($users[$username])) {
        $message = 'Tên đăng nhập đã tồn tại.';
    } else {
        $users[$username] = ['fullname' => $fullname, 'username' => $username, 'password' => $password, 'email' => $email, 'dob' => $dob];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $message = 'Giáo viên đã được thêm thành công.';
        $teachers[$username] = $users[$username];
    }
}

// Handle edit teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher'])) {
    $old_username = $_POST['edit_old_username'];
    $username = $_POST['edit_username'];
    $fullname = $_POST['edit_fullname'];
    $email = $_POST['edit_email'];
    $dob = $_POST['edit_dob'];

    if ($username !== $old_username && isset($users[$username])) {
        $message = 'Tên đăng nhập mới đã tồn tại.';
    } else {
        // Update user
        $users[$username] = ['fullname' => $fullname, 'username' => $username, 'password' => $users[$old_username]['password'], 'email' => $email, 'dob' => $dob];
        if ($username !== $old_username) {
            unset($users[$old_username]);
            // Update teacher_subjects
            if (isset($teacher_subjects[$old_username])) {
                $teacher_subjects[$username] = $teacher_subjects[$old_username];
                unset($teacher_subjects[$old_username]);
                file_put_contents($teacherSubjectsFile, json_encode($teacher_subjects, JSON_PRETTY_PRINT));
            }
        }
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $message = 'Giáo viên đã được cập nhật thành công.';
        $teachers = [];
        foreach ($users as $u => $d) {
            if ($u !== 'admin') $teachers[$u] = $d;
        }
    }
}

// Handle assign subjects to teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subjects'])) {
    $teacher_username = $_POST['assign_teacher_username'];
    $assigned_subjects = $_POST['assigned_subjects'] ?? [];

    $teacher_subjects[$teacher_username] = array_map('intval', $assigned_subjects);
    file_put_contents($teacherSubjectsFile, json_encode($teacher_subjects, JSON_PRETTY_PRINT));
    $message = 'Môn học đã được gán cho giáo viên.';
}

// Handle assign classes to teacher
$classesFile = 'classes.json';
$classes = json_decode(file_get_contents($classesFile), true) ?: [];
$teacher_classesFile = 'teacher_classes.json';
$teacher_classes = json_decode(file_get_contents($teacher_classesFile), true) ?: [];

// Group classes by grade
$grouped_classes = [];
foreach ($classes as $class) {
    $grade = substr($class['name'], 0, 1);
    if (in_array($grade, ['6','7','8','9'])) {
        $grouped_classes[$grade][] = $class;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_classes'])) {
    $teacher_username = $_POST['assign_teacher_username'];
    $assigned_classes = $_POST['assigned_classes'] ?? [];

    $teacher_classes[$teacher_username] = $assigned_classes;
    file_put_contents($teacher_classesFile, json_encode($teacher_classes, JSON_PRETTY_PRINT));
    $message = 'Lớp học đã được gán cho giáo viên.';
}

// Handle reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = $_POST['reset_username'];
    $new_password = $_POST['new_password'];
    if (!empty($new_password) && isset($users[$username])) {
        $users[$username]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $message = 'Mật khẩu của giáo viên ' . htmlspecialchars($username) . ' đã được reset thành công.';
        // Reload teachers
        $teachers = [];
        foreach ($users as $u => $d) {
            if ($u !== 'admin') $teachers[$u] = $d;
        }
    } else {
        $message = 'Lỗi: Mật khẩu không hợp lệ hoặc giáo viên không tồn tại.';
    }
}

// Handle delete teacher
if (isset($_GET['delete'])) {
    $username = $_GET['delete'];
    unset($users[$username]);
    unset($teacher_subjects[$username]);
    unset($teacher_classes[$username]);
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    file_put_contents($teacherSubjectsFile, json_encode($teacher_subjects, JSON_PRETTY_PRINT));
    file_put_contents($teacher_classesFile, json_encode($teacher_classes, JSON_PRETTY_PRINT));
    $message = 'Giáo viên đã được xóa.';
    unset($teachers[$username]);
    // Redirect to avoid URL with delete parameter
    header('Location: manage_teachers.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Giáo Viên - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_teachers.php'; include 'navbar.php'; ?>

    <div class="main-content">
        <h1>Quản Lý Giáo Viên</h1>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Thêm Giáo Viên Mới</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fullname" class="form-label">Họ tên</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" id="dob" name="dob">
                        </div>
                    </div>
                    <button type="submit" name="add_teacher" class="btn btn-primary mt-3">Thêm Giáo Viên</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Danh Sách Giáo Viên</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $username => $teacher): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($username); ?></td>
                                <td><?php echo htmlspecialchars($teacher['fullname']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editTeacher('<?php echo addslashes($username); ?>', '<?php echo addslashes($teacher['fullname']); ?>')">Chỉnh Sửa</button>
                                    <button class="btn btn-sm btn-info" onclick="assignSubjects('<?php echo addslashes($username); ?>', '<?php echo addslashes($teacher['fullname']); ?>')">Gán Môn Học</button>
                                    <button class="btn btn-sm btn-success" onclick="assignClasses('<?php echo addslashes($username); ?>', '<?php echo addslashes($teacher['fullname']); ?>')">Gán Lớp Học</button>
                                    <button class="btn btn-sm btn-secondary" onclick="resetPassword('<?php echo addslashes($username); ?>', '<?php echo addslashes($teacher['fullname']); ?>')">Reset Password</button>
                                    <a href="?delete=<?php echo urlencode($username); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa giáo viên này?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">Chỉnh Sửa Giáo Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" id="edit_old_username" name="edit_old_username">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="edit_username" name="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_fullname" class="form-label">Họ tên</label>
                        <input type="text" class="form-control" id="edit_fullname" name="edit_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="edit_email">
                    </div>
                    <div class="mb-3">
                        <label for="edit_dob" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" id="edit_dob" name="edit_dob">
                    </div>
                    <button type="submit" name="edit_teacher" class="btn btn-primary">Cập Nhật</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assign Subjects Modal -->
<div class="modal fade" id="assignSubjectsModal" tabindex="-1" aria-labelledby="assignSubjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignSubjectsModalLabel">Gán Môn Học Cho Giáo Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" id="assign_teacher_username" name="assign_teacher_username">
                    <div class="mb-3">
                        <label class="form-label">Chọn Môn Học:</label>
                        <div id="subjects_checkboxes">
                            <?php foreach ($subjects as $subject): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="assigned_subjects[]" value="<?php echo $subject['id']; ?>" id="subject_<?php echo $subject['id']; ?>">
                                    <label class="form-check-label" for="subject_<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="assign_subjects" class="btn btn-primary">Gán Môn Học</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assign Classes Modal -->
<div class="modal fade" id="assignClassesModal" tabindex="-1" aria-labelledby="assignClassesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignClassesModalLabel">Gán Lớp Học Cho Giáo Viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" id="assign_teacher_username_classes" name="assign_teacher_username">
                    <div class="mb-3">
                        <label class="form-label">Chọn Lớp Học:</label>
                        <div class="row">
                            <?php foreach (['6','7','8','9'] as $grade): ?>
                            <div class="col-md-3">
                                <h6>Khối <?php echo $grade; ?></h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select_all_<?php echo $grade; ?>">
                                    <label class="form-check-label" for="select_all_<?php echo $grade; ?>">
                                        Tất cả
                                    </label>
                                </div>
                                <?php if (isset($grouped_classes[$grade])): ?>
                                    <?php foreach ($grouped_classes[$grade] as $class): ?>
                                        <div class="form-check">
                                            <input class="form-check-input class-checkbox" type="checkbox" name="assigned_classes[]" value="<?php echo $class['id']; ?>" id="class_<?php echo $class['id']; ?>" data-grade="<?php echo $grade; ?>">
                                            <label class="form-check-label" for="class_<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="assign_classes" class="btn btn-primary">Gán Lớp Học</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password for Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" id="reset_username" name="reset_username">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTeacher(username, fullname) {
            document.getElementById('edit_old_username').value = username;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = fullname;
            // Fetch teacher data
            fetch('get_teacher_data.php?username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_email').value = data.email || '';
                    document.getElementById('edit_dob').value = data.dob || '';
                });
            var modal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
            modal.show();
        }

        function assignSubjects(username, fullname) {
            document.getElementById('assign_teacher_username').value = username;
            document.getElementById('assignSubjectsModalLabel').innerText = 'Gán Môn Học Cho ' + fullname;

            // Uncheck all checkboxes first
            const checkboxes = document.querySelectorAll('#subjects_checkboxes input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);

            // Fetch assigned subjects
            fetch('get_assigned_subjects.php?teacher_username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(result => {
                    result.data.forEach(subject_id => {
                        const cb = document.getElementById('subject_' + subject_id);
                        if (cb) cb.checked = true;
                    });
                });

            var modal = new bootstrap.Modal(document.getElementById('assignSubjectsModal'));
            modal.show();
        }

        function assignClasses(username, fullname) {
            document.getElementById('assign_teacher_username_classes').value = username;
            document.getElementById('assignClassesModalLabel').innerText = 'Gán Lớp Học Cho ' + fullname;

            // Uncheck all checkboxes first
            const checkboxes = document.querySelectorAll('.class-checkbox');
            checkboxes.forEach(cb => cb.checked = false);

            // Uncheck master checkboxes
            ['6','7','8','9'].forEach(grade => {
                document.getElementById('select_all_' + grade).checked = false;
            });

            // Fetch assigned classes
            fetch('get_assigned_classes.php?teacher_username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(result => {
                    result.data.forEach(class_id => {
                        const cb = document.getElementById('class_' + class_id);
                        if (cb) cb.checked = true;
                    });
                });

            var modal = new bootstrap.Modal(document.getElementById('assignClassesModal'));
            modal.show();
        }

        function resetPassword(username, fullname) {
            document.getElementById('reset_username').value = username;
            document.getElementById('resetPasswordModalLabel').innerText = 'Reset Password for ' + fullname;
            var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }

        // Handle master checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            ['6','7','8','9'].forEach(grade => {
                const masterCb = document.getElementById('select_all_' + grade);
                if (masterCb) {
                    masterCb.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.class-checkbox[data-grade="' + grade + '"]');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                }
            });
        });
    </script>
</body>
</html>
