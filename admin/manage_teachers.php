<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

$message = '';
$messageType = 'info';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function validateCsrfToken(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
    }
}

function saveTeacherStores(array $stores): bool {
    $originalContents = [];
    foreach ($stores as $path => $data) {
        $originalContents[$path] = file_exists($path) ? file_get_contents($path) : null;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($path, $json, LOCK_EX) === false) {
            foreach ($originalContents as $restorePath => $contents) {
                if ($contents !== null) {
                    file_put_contents($restorePath, $contents, LOCK_EX);
                }
            }
            return false;
        }
    }
    return true;
}

function createTeacherDataBackup(array $paths, string $operation): ?string {
    $backupDirectory = dirname(__DIR__) . '/backups/teacher_operations';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true) && !is_dir($backupDirectory)) {
        return null;
    }

    $backupName = preg_replace('/[^a-z0-9_-]+/i', '-', $operation) . '_' . date('Y-m-d_His');
    $targetDirectory = $backupDirectory . '/' . $backupName;
    if (!mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
        return null;
    }

    foreach ($paths as $path) {
        if (is_file($path) && !copy($path, $targetDirectory . '/' . basename($path))) {
            return null;
        }
    }

    return $backupName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
}

// Load data
$usersFile = 'user.json';
$users = json_decode(file_get_contents($usersFile), true) ?: [];
$subjectsFile = 'subjects.json';
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$teacherSubjectsFile = 'teacher_subjects.json';
$teacher_subjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
$classesFile = 'classes.json';
$classes = json_decode(file_get_contents($classesFile), true) ?: [];
$teacher_classesFile = 'teacher_classes.json';
$teacher_classes = json_decode(file_get_contents($teacher_classesFile), true) ?: [];

// Filter teachers (all users except admin)
$teachers = [];
foreach ($users as $username => $user) {
    if ($username !== 'admin') {
        $teachers[$username] = $user;
    }
}

// Handle add teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $username = mb_strtolower(trim($_POST['username']), 'UTF-8');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dob = trim($_POST['dob']);

    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
        $message = 'Tên đăng nhập chỉ gồm chữ thường, số, dấu chấm, gạch dưới hoặc gạch ngang.';
        $messageType = 'danger';
    } elseif (isset($users[$username])) {
        $message = 'Tên đăng nhập đã tồn tại.';
        $messageType = 'warning';
    } else {
        $users[$username] = ['fullname' => $fullname, 'username' => $username, 'password' => $password, 'email' => $email, 'dob' => $dob];
        $teacher_subjects[$username] = array_map('intval', $_POST['initial_subjects'] ?? []);
        $teacher_classes[$username] = array_values($_POST['initial_classes'] ?? []);
        if (saveTeacherStores([
            $usersFile => $users,
            $teacherSubjectsFile => $teacher_subjects,
            $teacher_classesFile => $teacher_classes
        ])) {
            $message = 'Giáo viên đã được thêm thành công.';
            $messageType = 'success';
            $teachers[$username] = $users[$username];
        } else {
            $message = 'Không thể lưu dữ liệu giáo viên.';
            $messageType = 'danger';
        }
    }
}

// Handle edit teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher'])) {
    $old_username = $_POST['edit_old_username'];
    $username = mb_strtolower(trim($_POST['username']), 'UTF-8');
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dob = trim($_POST['dob']);

    if (!isset($users[$old_username]) || $old_username === 'admin') {
        $message = 'Không tìm thấy giáo viên cần cập nhật.';
        $messageType = 'danger';
    } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
        $message = 'Tên đăng nhập mới không hợp lệ.';
        $messageType = 'danger';
    } elseif ($username !== $old_username && isset($users[$username])) {
        $message = 'Tên đăng nhập mới đã tồn tại.';
        $messageType = 'warning';
    } else {
        // Update user
        $users[$username] = ['fullname' => $fullname, 'username' => $username, 'password' => $users[$old_username]['password'], 'email' => $email, 'dob' => $dob];
        if ($username !== $old_username) {
            unset($users[$old_username]);
            // Update teacher_subjects
            if (isset($teacher_subjects[$old_username])) {
                $teacher_subjects[$username] = $teacher_subjects[$old_username];
                unset($teacher_subjects[$old_username]);
            }
            if (isset($teacher_classes[$old_username])) {
                $teacher_classes[$username] = $teacher_classes[$old_username];
                unset($teacher_classes[$old_username]);
            }
        }
        if (saveTeacherStores([
            $usersFile => $users,
            $teacherSubjectsFile => $teacher_subjects,
            $teacher_classesFile => $teacher_classes
        ])) {
            $message = 'Giáo viên đã được cập nhật thành công.';
            $messageType = 'success';
            $teachers = [];
            foreach ($users as $u => $d) {
                if ($u !== 'admin') $teachers[$u] = $d;
            }
        } else {
            $message = 'Không thể cập nhật đồng bộ dữ liệu giáo viên.';
            $messageType = 'danger';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_workload'])) {
    $teacher_username = trim($_POST['assign_teacher_username']);
    if (!isset($users[$teacher_username]) || $teacher_username === 'admin') {
        $message = 'Không tìm thấy giáo viên cần phân công.';
        $messageType = 'danger';
    } else {
        $teacher_subjects[$teacher_username] = array_values(array_map('intval', $_POST['assigned_subjects'] ?? []));
        $teacher_classes[$teacher_username] = array_values($_POST['assigned_classes'] ?? []);
        if (saveTeacherStores([
            $teacherSubjectsFile => $teacher_subjects,
            $teacher_classesFile => $teacher_classes
        ])) {
            $message = 'Đã cập nhật phân công chuyên môn.';
            $messageType = 'success';
        } else {
            $message = 'Không thể lưu phân công chuyên môn.';
            $messageType = 'danger';
        }
    }
}

// Handle reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = $_POST['reset_username'];
    $new_password = $_POST['new_password'];
    if (!empty($new_password) && isset($users[$username])) {
        $users[$username]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        if (saveTeacherStores([$usersFile => $users])) {
            $message = 'Mật khẩu của giáo viên ' . $username . ' đã được đặt lại.';
            $messageType = 'success';
            $teachers = [];
            foreach ($users as $u => $d) {
                if ($u !== 'admin') $teachers[$u] = $d;
            }
        } else {
            $message = 'Không thể cập nhật mật khẩu.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Lỗi: Mật khẩu không hợp lệ hoặc giáo viên không tồn tại.';
        $messageType = 'danger';
    }
}

// Handle delete teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher'])) {
    $username = trim($_POST['delete_username'] ?? '');
    if ($username === '' || $username === 'admin' || !isset($users[$username])) {
        $message = 'Không tìm thấy giáo viên cần xóa.';
        $messageType = 'danger';
    } else {
        $backupName = createTeacherDataBackup([$usersFile, $teacherSubjectsFile, $teacher_classesFile], 'delete-teacher');
        if ($backupName === null) {
            $message = 'Không thể tạo backup trước khi xóa giáo viên.';
            $messageType = 'danger';
        } else {
        unset($users[$username], $teacher_subjects[$username], $teacher_classes[$username]);
        if (saveTeacherStores([
            $usersFile => $users,
            $teacherSubjectsFile => $teacher_subjects,
            $teacher_classesFile => $teacher_classes
        ])) {
            $message = 'Giáo viên đã được xóa.';
            $messageType = 'success';
            unset($teachers[$username]);
        } else {
            $message = 'Không thể xóa đồng bộ dữ liệu giáo viên.';
            $messageType = 'danger';
        }
        }
    }
}

// Handle bulk add teachers from Excel/CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $file_name = $_FILES['excel_file']['name'];
    if (!file_exists($file)) {
        $message = 'File not uploaded.';
    } else {
        $rows = [];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_extension === 'csv') {
            // Handle CSV file
            if (($handle = fopen($file, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            } else {
                $message = 'Failed to open CSV file.';
            }
        } elseif ($file_extension === 'xlsx') {
            // Handle Excel file
            require_once 'SimpleXLSX.php';
            $xlsx = new SimpleXLSX($file);
            if ($xlsx) {
                $rows = $xlsx->rows();
            } else {
                $message = 'Failed to parse Excel file.';
            }
        } else {
            $message = 'Unsupported file type. Please upload a CSV or XLSX file.';
        }
        if (is_array($rows) && !empty($rows)) {
            // Skip header
            array_shift($rows);
            $success_count = 0;
            $errors = [];
            foreach ($rows as $row) {
                $username = mb_strtolower(trim($row[0] ?? ''), 'UTF-8');
                $password = trim($row[1] ?? '');
                $fullname = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $dob = trim($row[4] ?? '');
                $subject_ids = trim($row[5] ?? '');
                $class_codes = trim($row[6] ?? '');
                if (empty($username) || empty($password) || empty($fullname)) {
                    $errors[] = "Có dòng thiếu tài khoản, mật khẩu hoặc họ tên.";
                    continue;
                }
                if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
                    $errors[] = "Tài khoản $username không hợp lệ.";
                    continue;
                }
                if (isset($users[$username])) {
                    $errors[] = "Tài khoản $username đã tồn tại, đã bỏ qua.";
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
                // Assign subjects (comma-separated)
                if ($subject_ids) {
                    $subject_id_array = array_map('intval', array_filter(array_map('trim', explode(',', $subject_ids))));
                    $teacher_subjects[$username] = $subject_id_array;
                }
                // Assign classes (comma-separated)
                if ($class_codes) {
                    $class_id_array = [];
                    $class_code_array = array_filter(array_map('trim', explode(',', $class_codes)));
                    foreach ($class_code_array as $class_code) {
                        $class_id = null;
                        foreach ($classes as $class) {
                            if ($class['code'] === $class_code) {
                                $class_id = $class['id'];
                                break;
                            }
                        }
                        if ($class_id) {
                            $class_id_array[] = $class_id;
                        }
                    }
                    if (!empty($class_id_array)) {
                        $teacher_classes[$username] = $class_id_array;
                    }
                }
                $success_count++;
            }
            $backupName = createTeacherDataBackup([$usersFile, $teacherSubjectsFile, $teacher_classesFile], 'bulk-import-teachers');
            if ($backupName === null) {
                $message = 'Không thể tạo backup trước khi nhập dữ liệu.';
                $messageType = 'danger';
            } elseif (saveTeacherStores([
                $usersFile => $users,
                $teacherSubjectsFile => $teacher_subjects,
                $teacher_classesFile => $teacher_classes
            ])) {
                $message = "Đã thêm $success_count giáo viên.";
                $messageType = 'success';
                if ($errors) {
                    $message .= " Bỏ qua/lỗi: " . implode(' ', $errors);
                    $messageType = 'warning';
                }
            } else {
                $message = 'Không thể lưu đồng bộ dữ liệu nhập.';
                $messageType = 'danger';
            }
            // Reload teachers
            $teachers = [];
            foreach ($users as $u => $d) {
                if ($u !== 'admin') $teachers[$u] = $d;
            }
        } else {
            $message = 'Failed to parse Excel file.';
        }
    }
}
?>

<?php
$subjectLookup = [];
foreach ($subjects as $subject) {
    $subjectLookup[(string) $subject['id']] = $subject;
}
$classLookup = [];
foreach ($classes as $class) {
    $classLookup[(string) $class['id']] = $class;
}
$totalTeachers = count($teachers);
$assignedSubjectCount = 0;
$assignedClassCount = 0;
$completeAssignmentCount = 0;
foreach ($teachers as $teacherUsername => $teacherData) {
    $hasSubjects = !empty($teacher_subjects[$teacherUsername]);
    $hasClasses = !empty($teacher_classes[$teacherUsername]);
    if ($hasSubjects) $assignedSubjectCount++;
    if ($hasClasses) $assignedClassCount++;
    if ($hasSubjects && $hasClasses) $completeAssignmentCount++;
}
function teacherInitials(string $name): string {
    $parts = preg_split('/\s+/u', trim($name));
    if (!$parts) return 'GV';
    $first = mb_substr($parts[0], 0, 1, 'UTF-8');
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
    return mb_strtoupper($first . $last, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Giáo Viên - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/manage_teachers.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
<?php $current_page = 'manage_teachers.php'; include 'navbar.php'; ?>

<main class="teacher-workspace">
    <header class="teacher-page-header">
        <div>
            <h1>Quản lý giáo viên</h1>
            <p>Quản lý tài khoản và phân công chuyên môn phục vụ hệ thống kiểm tra đánh giá.</p>
        </div>
        <div class="teacher-header-actions">
            <button type="button" class="btn btn-outline-primary" id="openBulkImportBtn"><i class="bi bi-file-earmark-arrow-up"></i> Nhập Excel/CSV</button>
            <button type="button" class="btn btn-primary" id="openAddTeacherBtn"><i class="bi bi-person-plus"></i> Thêm giáo viên</button>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <section class="teacher-stats" aria-label="Thống kê giáo viên">
        <div class="teacher-stat"><span class="teacher-stat-icon"><i class="bi bi-people"></i></span><div><div class="teacher-stat-value"><?php echo $totalTeachers; ?></div><div class="teacher-stat-label">Tổng giáo viên</div></div></div>
        <div class="teacher-stat"><span class="teacher-stat-icon"><i class="bi bi-journal-bookmark"></i></span><div><div class="teacher-stat-value"><?php echo $assignedSubjectCount; ?></div><div class="teacher-stat-label">Đã phân công môn</div></div></div>
        <div class="teacher-stat"><span class="teacher-stat-icon"><i class="bi bi-building"></i></span><div><div class="teacher-stat-value"><?php echo $assignedClassCount; ?></div><div class="teacher-stat-label">Đã phân công lớp</div></div></div>
        <div class="teacher-stat"><span class="teacher-stat-icon"><i class="bi bi-exclamation-circle"></i></span><div><div class="teacher-stat-value"><?php echo $totalTeachers - $completeAssignmentCount; ?></div><div class="teacher-stat-label">Chưa đủ phân công</div></div></div>
    </section>

    <section class="teacher-panel">
        <div class="teacher-panel-header">
            <div><h2>Danh sách giáo viên</h2><p><span id="teacherVisibleCount"><?php echo $totalTeachers; ?></span> giáo viên đang hiển thị</p></div>
        </div>
        <div class="teacher-filter-bar">
            <div class="teacher-search"><i class="bi bi-search"></i><input type="search" class="form-control" id="teacherSearch" placeholder="Tên, tài khoản hoặc email..."></div>
            <select class="form-select" id="teacherSubjectFilter"><option value="">Tất cả môn học</option><?php foreach ($subjects as $subject): ?><option value="<?php echo (int) $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option><?php endforeach; ?></select>
            <select class="form-select" id="teacherGradeFilter"><option value="">Tất cả khối</option><option value="6">Khối 6</option><option value="7">Khối 7</option><option value="8">Khối 8</option><option value="9">Khối 9</option></select>
            <select class="form-select" id="teacherStatusFilter"><option value="">Tất cả trạng thái</option><option value="complete">Đã phân công đủ</option><option value="incomplete">Chưa đủ phân công</option></select>
        </div>
        <div class="teacher-table-wrap">
            <table class="table teacher-table">
                <thead><tr><th>Giáo viên</th><th>Liên hệ</th><th>Môn phụ trách</th><th>Lớp phụ trách</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody id="teacherTableBody">
                <?php foreach ($teachers as $username => $teacher): ?>
                    <?php
                    $assignedSubjectIds = array_map('strval', $teacher_subjects[$username] ?? []);
                    $assignedClassIds = array_map('strval', $teacher_classes[$username] ?? []);
                    $assignedGrades = [];
                    foreach ($assignedClassIds as $classId) {
                        if (isset($classLookup[$classId])) $assignedGrades[] = substr((string) $classLookup[$classId]['code'], 0, 1);
                    }
                    $assignedGrades = array_values(array_unique($assignedGrades));
                    $isComplete = !empty($assignedSubjectIds) && !empty($assignedClassIds);
                    $searchText = mb_strtolower(($teacher['fullname'] ?? '') . ' ' . $username . ' ' . ($teacher['email'] ?? ''), 'UTF-8');
                    ?>
                    <tr class="teacher-row" data-search="<?php echo htmlspecialchars($searchText); ?>" data-subjects="<?php echo htmlspecialchars(implode(',', $assignedSubjectIds)); ?>" data-grades="<?php echo htmlspecialchars(implode(',', $assignedGrades)); ?>" data-status="<?php echo $isComplete ? 'complete' : 'incomplete'; ?>">
                        <td><div class="teacher-identity"><span class="teacher-avatar"><?php echo htmlspecialchars(teacherInitials($teacher['fullname'] ?? $username)); ?></span><div><div class="teacher-name"><?php echo htmlspecialchars($teacher['fullname'] ?? $username); ?></div><div class="teacher-username">@<?php echo htmlspecialchars($username); ?></div></div></div></td>
                        <td><div><?php echo htmlspecialchars($teacher['email'] ?: 'Chưa cập nhật email'); ?></div><?php if (!empty($teacher['dob'])): ?><div class="teacher-username">Ngày sinh: <?php echo htmlspecialchars($teacher['dob']); ?></div><?php endif; ?></td>
                        <td><div class="teacher-badges"><?php if (!$assignedSubjectIds): ?><span class="text-muted">Chưa phân công</span><?php else: ?><?php foreach ($assignedSubjectIds as $subjectId): ?><?php if (isset($subjectLookup[$subjectId])): ?><span class="teacher-badge is-subject"><?php echo htmlspecialchars($subjectLookup[$subjectId]['name']); ?></span><?php endif; ?><?php endforeach; ?><?php endif; ?></div></td>
                        <td><div class="teacher-badges"><?php if (!$assignedClassIds): ?><span class="text-muted">Chưa phân công</span><?php else: ?><?php foreach (array_slice($assignedClassIds, 0, 5) as $classId): ?><?php if (isset($classLookup[$classId])): ?><span class="teacher-badge"><?php echo htmlspecialchars($classLookup[$classId]['name']); ?></span><?php endif; ?><?php endforeach; ?><?php if (count($assignedClassIds) > 5): ?><span class="teacher-badge">+<?php echo count($assignedClassIds) - 5; ?></span><?php endif; ?><?php endif; ?></div></td>
                        <td><span class="teacher-status <?php echo $isComplete ? 'is-complete' : 'is-incomplete'; ?>"><i class="bi <?php echo $isComplete ? 'bi-check-circle-fill' : 'bi-clock-fill'; ?>"></i><?php echo $isComplete ? 'Đã phân công' : 'Chưa hoàn thiện'; ?></span></td>
                        <td class="text-end teacher-actions"><div class="dropdown"><button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-label="Mở thao tác"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item edit-teacher-btn" data-username="<?php echo htmlspecialchars($username); ?>"><i class="bi bi-pencil me-2"></i>Chỉnh sửa thông tin</button></li>
                            <li><button class="dropdown-item assign-teacher-btn" data-username="<?php echo htmlspecialchars($username); ?>" data-fullname="<?php echo htmlspecialchars($teacher['fullname'] ?? $username); ?>"><i class="bi bi-diagram-3 me-2"></i>Phân công chuyên môn</button></li>
                            <li><button class="dropdown-item reset-teacher-btn" data-username="<?php echo htmlspecialchars($username); ?>" data-fullname="<?php echo htmlspecialchars($teacher['fullname'] ?? $username); ?>"><i class="bi bi-key me-2"></i>Đặt lại mật khẩu</button></li>
                            <li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger delete-teacher-btn" data-username="<?php echo htmlspecialchars($username); ?>" data-fullname="<?php echo htmlspecialchars($teacher['fullname'] ?? $username); ?>"><i class="bi bi-trash me-2"></i>Xóa giáo viên</button></li>
                        </ul></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="teacher-empty d-none" id="teacherFilterEmpty"><i class="bi bi-person-x"></i>Không tìm thấy giáo viên phù hợp với bộ lọc.</div>
        </div>
    </section>
</main>

<div class="modal fade teacher-management-modal" id="teacherFormModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" id="teacherForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="edit_old_username" id="teacherOldUsername">
    <div class="modal-header"><div class="teacher-modal-heading"><span class="teacher-modal-icon"><i class="bi bi-person-plus" id="teacherFormIcon"></i></span><div><h5 class="modal-title" id="teacherFormTitle">Thêm giáo viên</h5><span class="modal-subtitle" id="teacherFormSubtitle">Thông tin tài khoản, hồ sơ và phân công ban đầu</span></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="teacherUsername">Tên đăng nhập</label><input type="text" class="form-control" id="teacherUsername" name="username" required pattern="[a-z0-9._-]{3,50}"><div class="form-text">Chữ thường, số, dấu chấm, gạch dưới hoặc gạch ngang.</div></div>
        <div class="col-md-6" id="teacherPasswordGroup"><label class="form-label" for="teacherPassword">Mật khẩu ban đầu</label><div class="input-group"><input type="password" class="form-control" id="teacherPassword" name="password"><button class="btn btn-outline-secondary password-toggle" type="button" data-target="teacherPassword"><i class="bi bi-eye"></i></button></div></div>
        <div class="col-md-6"><label class="form-label" for="teacherFullname">Họ và tên</label><input type="text" class="form-control" id="teacherFullname" name="fullname" required></div>
        <div class="col-md-6"><label class="form-label" for="teacherEmail">Email</label><input type="email" class="form-control" id="teacherEmail" name="email"></div>
        <div class="col-md-6"><label class="form-label" for="teacherDob">Ngày sinh</label><input type="date" class="form-control" id="teacherDob" name="dob"></div>
    </div>
    <div id="initialAssignmentGroup" class="mt-4 pt-3 border-top">
        <h6 class="mb-3">Phân công ban đầu</h6>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="initialSubjects">Môn giảng dạy</label><select class="form-select" id="initialSubjects" name="initial_subjects[]" multiple size="5"><?php foreach ($subjects as $subject): ?><option value="<?php echo (int) $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option><?php endforeach; ?></select><div class="form-text">Giữ Ctrl để chọn nhiều môn.</div></div>
            <div class="col-md-6"><label class="form-label" for="initialClasses">Lớp phụ trách</label><select class="form-select" id="initialClasses" name="initial_classes[]" multiple size="5"><?php foreach ($classes as $class): ?><option value="<?php echo htmlspecialchars($class['id']); ?>"><?php echo htmlspecialchars($class['name']); ?></option><?php endforeach; ?></select><div class="form-text">Có thể chỉnh lại sau trong Phân công chuyên môn.</div></div>
        </div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary" id="teacherFormSubmit" name="add_teacher">Thêm giáo viên</button></div>
</form></div></div></div>

<div class="modal fade teacher-management-modal" id="assignmentModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="post" id="assignmentForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="assign_workload" value="1"><input type="hidden" name="assign_teacher_username" id="assignmentUsername">
    <div class="modal-header"><div class="teacher-modal-heading"><span class="teacher-modal-icon"><i class="bi bi-diagram-3"></i></span><div><h5 class="modal-title">Phân công chuyên môn</h5><span class="modal-subtitle" id="assignmentTeacherName"></span></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body"><div class="assignment-grid"><aside class="assignment-summary"><h3>Tóm tắt phân công</h3><div class="assignment-summary-row"><span>Môn đã chọn</span><strong id="selectedSubjectCount">0</strong></div><div class="assignment-summary-row"><span>Lớp đã chọn</span><strong id="selectedClassCount">0</strong></div><div class="mt-3 small text-muted">Giáo viên chỉ nhìn thấy dữ liệu của các môn và lớp được phân công.</div></aside><div class="assignment-content">
        <ul class="nav nav-tabs mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#assignmentSubjects" type="button">Môn giảng dạy</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#assignmentClasses" type="button">Lớp phụ trách</button></li></ul>
        <div class="tab-content"><div class="tab-pane fade show active" id="assignmentSubjects"><div class="subject-option-grid"><?php foreach ($subjects as $subject): ?><label class="assignment-option"><input class="form-check-input assignment-subject" type="checkbox" name="assigned_subjects[]" value="<?php echo (int) $subject['id']; ?>"><span><?php echo htmlspecialchars($subject['name']); ?></span></label><?php endforeach; ?></div></div>
        <div class="tab-pane fade" id="assignmentClasses"><?php foreach (['6','7','8','9'] as $grade): ?><section class="class-grade-block"><div class="class-grade-header"><strong>Khối <?php echo $grade; ?></strong><label class="small"><input type="checkbox" class="form-check-input select-grade" data-grade="<?php echo $grade; ?>"> Chọn tất cả</label></div><div class="class-option-grid"><?php foreach ($grouped_classes[$grade] ?? [] as $class): ?><label class="assignment-option"><input class="form-check-input assignment-class" type="checkbox" name="assigned_classes[]" value="<?php echo htmlspecialchars($class['id']); ?>" data-grade="<?php echo $grade; ?>"><span><?php echo htmlspecialchars($class['name']); ?></span></label><?php endforeach; ?></div></section><?php endforeach; ?></div></div>
    </div></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Lưu phân công</button></div>
</form></div></div></div>

<div class="modal fade teacher-management-modal" id="resetPasswordModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="reset_username" id="resetUsername"><div class="modal-header"><div class="teacher-modal-heading"><span class="teacher-modal-icon"><i class="bi bi-key"></i></span><div><h5 class="modal-title">Đặt lại mật khẩu</h5><span class="modal-subtitle">Thiết lập mật khẩu đăng nhập mới cho giáo viên</span></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><p id="resetTeacherName" class="fw-semibold mb-3"></p><label class="form-label" for="newPassword">Mật khẩu mới</label><div class="input-group"><input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6"><button class="btn btn-outline-secondary password-toggle" type="button" data-target="newPassword"><i class="bi bi-eye"></i></button></div><div class="form-text mt-2">Mật khẩu cần có tối thiểu 6 ký tự.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" name="reset_password" class="btn btn-primary">Cập nhật mật khẩu</button></div></form></div></div></div>

<div class="modal fade teacher-management-modal" id="deleteTeacherModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="delete_teacher" value="1"><input type="hidden" name="delete_username" id="deleteUsername"><div class="modal-header"><div class="teacher-modal-heading"><span class="teacher-modal-icon is-danger"><i class="bi bi-trash"></i></span><div><h5 class="modal-title">Xóa giáo viên</h5><span class="modal-subtitle">Thao tác này sẽ xóa tài khoản và phân công hiện tại</span></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><p class="mb-3">Bạn có chắc muốn xóa <strong id="deleteTeacherName"></strong>?</p><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Dữ liệu đề và kết quả đã tạo không bị xóa. Hệ thống sẽ tạo backup tài khoản trước khi thực hiện.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Xóa giáo viên</button></div></form></div></div></div>

<div class="modal fade teacher-management-modal" id="bulkImportModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="post" enctype="multipart/form-data" id="bulkImportForm"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><div class="modal-header"><div class="teacher-modal-heading"><span class="teacher-modal-icon is-success"><i class="bi bi-file-earmark-arrow-up"></i></span><div><h5 class="modal-title">Nhập giáo viên từ Excel/CSV</h5><span class="modal-subtitle">Kiểm tra dữ liệu trước khi thêm; tài khoản trùng sẽ được bỏ qua</span></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Cột: username, password, fullname, email, dob, subject_ids, class_codes. <a href="download_sample_teachers.php" class="alert-link">Tải file mẫu</a>.</div><label class="form-label" for="teacherImportFile">Tệp dữ liệu</label><input type="file" class="form-control" id="teacherImportFile" name="excel_file" accept=".xlsx,.csv" required><div id="teacherImportPreview" class="mt-4 d-none"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Kết quả xem trước</h6><span id="teacherImportStats"></span></div><div class="import-preview-table"><table class="table table-sm mb-0"><thead><tr><th>Dòng</th><th>Tài khoản</th><th>Họ tên</th><th>Môn</th><th>Lớp</th><th>Kết quả</th></tr></thead><tbody id="teacherImportPreviewBody"></tbody></table></div><label class="form-check mt-3"><input type="checkbox" class="form-check-input" id="confirmTeacherImport"> Tôi đã kiểm tra dữ liệu xem trước</label></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="button" class="btn btn-outline-primary" id="previewTeacherImportBtn"><i class="bi bi-eye me-1"></i>Xem trước</button><button type="submit" class="btn btn-primary d-none" id="submitTeacherImportBtn" disabled>Nhập giáo viên mới</button></div></form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const teacherUsers = <?php echo json_encode(array_keys($users), JSON_UNESCAPED_UNICODE); ?>;
const teacherAssignments = <?php echo json_encode(['subjects' => $teacher_subjects, 'classes' => $teacher_classes], JSON_UNESCAPED_UNICODE); ?>;
const knownSubjectIds = <?php echo json_encode(array_map(static fn($subject) => (string) $subject['id'], $subjects)); ?>;
const knownClassCodes = <?php echo json_encode(array_map(static fn($class) => (string) $class['code'], $classes), JSON_UNESCAPED_UNICODE); ?>;

const teacherModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('teacherFormModal'));
const assignmentModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('assignmentModal'));
const resetModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('resetPasswordModal'));
const deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTeacherModal'));
const bulkModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkImportModal'));

function filterTeachers() {
    const keyword = document.getElementById('teacherSearch').value.trim().toLocaleLowerCase('vi');
    const subject = document.getElementById('teacherSubjectFilter').value;
    const grade = document.getElementById('teacherGradeFilter').value;
    const status = document.getElementById('teacherStatusFilter').value;
    let visible = 0;
    document.querySelectorAll('.teacher-row').forEach(row => {
        const subjects = (row.dataset.subjects || '').split(',');
        const grades = (row.dataset.grades || '').split(',');
        const matches = (!keyword || row.dataset.search.includes(keyword)) && (!subject || subjects.includes(subject)) && (!grade || grades.includes(grade)) && (!status || row.dataset.status === status);
        row.classList.toggle('d-none', !matches);
        if (matches) visible++;
    });
    document.getElementById('teacherVisibleCount').textContent = visible;
    document.getElementById('teacherFilterEmpty').classList.toggle('d-none', visible !== 0);
}
['teacherSearch','teacherSubjectFilter','teacherGradeFilter','teacherStatusFilter'].forEach(id => document.getElementById(id).addEventListener(id === 'teacherSearch' ? 'input' : 'change', filterTeachers));

document.getElementById('openAddTeacherBtn').addEventListener('click', () => {
    document.getElementById('teacherForm').reset(); document.getElementById('teacherOldUsername').value = '';
    document.getElementById('teacherFormTitle').textContent = 'Thêm giáo viên'; document.getElementById('teacherPasswordGroup').classList.remove('d-none'); document.getElementById('teacherPassword').required = true;
    document.getElementById('teacherFormIcon').className = 'bi bi-person-plus'; document.getElementById('teacherFormSubtitle').textContent = 'Thông tin tài khoản, hồ sơ và phân công ban đầu';
    document.getElementById('initialAssignmentGroup').classList.remove('d-none');
    const submit = document.getElementById('teacherFormSubmit'); submit.name = 'add_teacher'; submit.textContent = 'Thêm giáo viên'; teacherModal.show();
});

document.querySelectorAll('.edit-teacher-btn').forEach(button => button.addEventListener('click', async () => {
    const username = button.dataset.username; const response = await fetch('get_teacher_data.php?username=' + encodeURIComponent(username)); const data = await response.json();
    document.getElementById('teacherOldUsername').value = username; document.getElementById('teacherUsername').value = username; document.getElementById('teacherFullname').value = data.fullname || ''; document.getElementById('teacherEmail').value = data.email || ''; document.getElementById('teacherDob').value = data.dob || '';
    document.getElementById('teacherFormTitle').textContent = 'Chỉnh sửa giáo viên'; document.getElementById('teacherPasswordGroup').classList.add('d-none'); document.getElementById('teacherPassword').required = false;
    document.getElementById('teacherFormIcon').className = 'bi bi-pencil-square'; document.getElementById('teacherFormSubtitle').textContent = 'Cập nhật thông tin tài khoản và hồ sơ giáo viên';
    document.getElementById('initialAssignmentGroup').classList.add('d-none');
    const submit = document.getElementById('teacherFormSubmit'); submit.name = 'edit_teacher'; submit.textContent = 'Lưu thay đổi'; teacherModal.show();
}));

function updateAssignmentCount() { document.getElementById('selectedSubjectCount').textContent = document.querySelectorAll('.assignment-subject:checked').length; document.getElementById('selectedClassCount').textContent = document.querySelectorAll('.assignment-class:checked').length; }
document.querySelectorAll('.assignment-subject,.assignment-class').forEach(cb => cb.addEventListener('change', updateAssignmentCount));
document.querySelectorAll('.select-grade').forEach(master => master.addEventListener('change', () => { document.querySelectorAll('.assignment-class[data-grade="' + master.dataset.grade + '"]').forEach(cb => cb.checked = master.checked); updateAssignmentCount(); }));
document.querySelectorAll('.assign-teacher-btn').forEach(button => button.addEventListener('click', () => {
    const username = button.dataset.username; document.getElementById('assignmentUsername').value = username; document.getElementById('assignmentTeacherName').textContent = button.dataset.fullname + ' (@' + username + ')';
    const assignedSubjects = (teacherAssignments.subjects[username] || []).map(String); const assignedClasses = (teacherAssignments.classes[username] || []).map(String);
    document.querySelectorAll('.assignment-subject').forEach(cb => cb.checked = assignedSubjects.includes(cb.value)); document.querySelectorAll('.assignment-class').forEach(cb => cb.checked = assignedClasses.includes(cb.value));
    document.querySelectorAll('.select-grade').forEach(master => { const items=[...document.querySelectorAll('.assignment-class[data-grade="'+master.dataset.grade+'"]')]; master.checked=items.length>0&&items.every(cb=>cb.checked); }); updateAssignmentCount(); assignmentModal.show();
}));

document.querySelectorAll('.reset-teacher-btn').forEach(button => button.addEventListener('click', () => { document.getElementById('resetUsername').value=button.dataset.username; document.getElementById('resetTeacherName').textContent='Giáo viên: '+button.dataset.fullname; document.getElementById('newPassword').value=''; resetModal.show(); }));
document.querySelectorAll('.delete-teacher-btn').forEach(button => button.addEventListener('click', () => { document.getElementById('deleteUsername').value=button.dataset.username; document.getElementById('deleteTeacherName').textContent=button.dataset.fullname+' (@'+button.dataset.username+')'; deleteModal.show(); }));
document.querySelectorAll('.password-toggle').forEach(button => button.addEventListener('click', () => { const input=document.getElementById(button.dataset.target); input.type=input.type==='password'?'text':'password'; button.querySelector('i').className=input.type==='password'?'bi bi-eye':'bi bi-eye-slash'; }));

document.getElementById('openBulkImportBtn').addEventListener('click', () => { document.getElementById('bulkImportForm').reset(); document.getElementById('teacherImportPreview').classList.add('d-none'); document.getElementById('submitTeacherImportBtn').classList.add('d-none'); document.getElementById('previewTeacherImportBtn').classList.remove('d-none'); bulkModal.show(); });
document.getElementById('confirmTeacherImport').addEventListener('change', function(){ document.getElementById('submitTeacherImportBtn').disabled=!this.checked; });
document.getElementById('previewTeacherImportBtn').addEventListener('click', () => {
    const file=document.getElementById('teacherImportFile').files[0]; if(!file){alert('Vui lòng chọn file.');return;} const reader=new FileReader(); reader.onload=e=>{ const workbook=XLSX.read(new Uint8Array(e.target.result),{type:'array'}); const rows=XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]],{header:1,raw:false,defval:''}).slice(1); let valid=0,duplicates=0,invalid=0; const seen=new Set();
        document.getElementById('teacherImportPreviewBody').innerHTML=rows.filter(r=>r.some(v=>String(v).trim()!=='')).map((row,index)=>{ const username=String(row[0]).trim().toLowerCase(); const fullname=String(row[2]).trim(); const subjects=String(row[5]).split(',').map(v=>v.trim()).filter(Boolean); const classes=String(row[6]).split(',').map(v=>v.trim()).filter(Boolean); let status='Sẵn sàng'; let cls='text-success'; if(!username||!String(row[1]).trim()||!fullname){status='Thiếu dữ liệu';cls='text-danger';invalid++;} else if(teacherUsers.includes(username)||seen.has(username)){status='Trùng, bỏ qua';cls='text-warning';duplicates++;} else if(subjects.some(id=>!knownSubjectIds.includes(id))||classes.some(code=>!knownClassCodes.includes(code))){status='Môn/lớp không tồn tại';cls='text-danger';invalid++;} else {valid++;} seen.add(username); return '<tr><td>'+(index+2)+'</td><td>'+username+'</td><td>'+fullname+'</td><td>'+subjects.join(', ')+'</td><td>'+classes.join(', ')+'</td><td class="'+cls+' fw-semibold">'+status+'</td></tr>'; }).join('');
        document.getElementById('teacherImportStats').innerHTML='<span class="badge bg-success me-1">'+valid+' hợp lệ</span><span class="badge bg-warning text-dark me-1">'+duplicates+' trùng</span><span class="badge bg-danger">'+invalid+' lỗi</span>'; document.getElementById('teacherImportPreview').classList.remove('d-none'); document.getElementById('previewTeacherImportBtn').classList.add('d-none'); document.getElementById('submitTeacherImportBtn').classList.remove('d-none'); document.getElementById('submitTeacherImportBtn').disabled=true; document.getElementById('confirmTeacherImport').checked=false;
    }; reader.readAsArrayBuffer(file);
});
</script>
</body>
</html>
