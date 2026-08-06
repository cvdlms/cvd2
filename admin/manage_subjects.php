<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

$message = '';
$messageType = 'info';

$users = json_decode(file_get_contents('user.json'), true) ?: [];
$fullname = $users['admin']['fullname'] ?? 'Admin';

// Load subjects
$subjectsFile = 'subjects.json';
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];

// Handle add subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);

    $maxId = 0;
    foreach ($subjects as $subj) {
        if ($subj['id'] > $maxId) $maxId = $subj['id'];
    }
    $newId = $maxId + 1;

    $subjects[] = ['id' => $newId, 'name' => $name, 'code' => $code];
    file_put_contents($subjectsFile, json_encode($subjects, JSON_PRETTY_PRINT));
    $message = 'Môn học đã được thêm thành công.';
    $messageType = 'success';
}

// Handle edit subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $id = (int)$_POST['edit_subject_id'];
    $name = trim($_POST['edit_name']);
    $code = trim($_POST['edit_code']);

    foreach ($subjects as &$subj) {
        if ($subj['id'] === $id) {
            $subj['name'] = $name;
            $subj['code'] = $code;
            break;
        }
    }
    file_put_contents($subjectsFile, json_encode($subjects, JSON_PRETTY_PRINT));
    $message = 'Môn học đã được cập nhật thành công.';
    $messageType = 'success';
}

// Handle delete subject
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $subjects = array_filter($subjects, function($subj) use ($id) {
        return $subj['id'] !== $id;
    });
    file_put_contents($subjectsFile, json_encode(array_values($subjects), JSON_PRETTY_PRINT));
    $message = 'Môn học đã được xóa.';
    $messageType = 'success';
}

// Stats
$teacher_subjects = json_decode(file_get_contents('teacher_subjects.json'), true) ?: [];
$assigned_ids = [];
foreach ($teacher_subjects as $teacherId => $ids) {
    foreach ((array) $ids as $sid) {
        $assigned_ids[(int) $sid] = true;
    }
}
$total_subjects = count($subjects);
$assigned_subject_count = count($assigned_ids);
$unused_subject_count = $total_subjects - $assigned_subject_count;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Môn Học - CVD Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/manage_subjects.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_subjects.php'; include 'navbar.php'; ?>

    <main class="cvd-page">
        <header class="cvd-page-header">
            <div>
                <div class="cvd-eyebrow"><i class="bi bi-journal-bookmark"></i> Danh mục trường học</div>
                <h1>Môn học</h1>
                <p class="cvd-sub">Quản lý danh sách môn học sử dụng trong hệ thống kiểm tra, đánh giá và phân công giảng dạy.</p>
            </div>
            <div class="cvd-page-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="bi bi-plus-lg me-1"></i> Thêm môn học
                </button>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        <?php endif; ?>

        <section class="cvd-stats" aria-label="Thống kê môn học">
            <div class="cvd-stat cvd-reveal">
                <span class="cvd-stat-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $total_subjects; ?></div>
                    <div class="cvd-stat-label">Tổng số môn học</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d1">
                <span class="cvd-stat-icon"><i class="bi bi-person-video3"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $assigned_subject_count; ?></div>
                    <div class="cvd-stat-label">Môn đang phân công</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d2">
                <span class="cvd-stat-icon is-accent"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $unused_subject_count; ?></div>
                    <div class="cvd-stat-label">Môn chưa sử dụng</div>
                </div>
            </div>
        </section>

        <section class="cvd-panel cvd-reveal cvd-reveal-d2">
            <div class="cvd-panel-header">
                <div>
                    <h2>Danh sách môn học</h2>
                    <p>Tên và mã viết tắt của từng môn học trong hệ thống</p>
                </div>
                <span class="badge bg-success-subtle"><?php echo $total_subjects; ?> môn</span>
            </div>
            <div class="cvd-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:80px;">STT</th>
                            <th>Tên môn học</th>
                            <th style="width:180px;">Mã môn học</th>
                            <th class="text-end" style="width:180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Chưa có môn học nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $index => $subject): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="subject-swatch"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($subject['name'], 0, 1))); ?></span>
                                            <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><span class="subject-code"><?php echo htmlspecialchars($subject['code']); ?></span></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                            onclick="editSubject(<?php echo (int) $subject['id']; ?>, '<?php echo addslashes($subject['name']); ?>', '<?php echo addslashes($subject['code']); ?>')">
                                            <i class="bi bi-pencil me-1"></i>Sửa
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDeleteSubject(<?php echo (int) $subject['id']; ?>, '<?php echo addslashes($subject['name']); ?>')">
                                            <i class="bi bi-trash me-1"></i>Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="cvd-footer-credit">
            Được phát triển & vận hành bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a>
        </div>
    </main>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Thêm môn học mới</h5>
                            <small class="text-muted">Tạo danh mục môn học sử dụng trong hệ thống</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên môn học *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ví dụ: Ngữ Văn">
                        </div>
                        <div class="mb-0">
                            <label for="code" class="form-label">Mã môn học *</label>
                            <input type="text" class="form-control" id="code" name="code" required placeholder="Ví dụ: NV">
                            <div class="form-text">Mã viết tắt ngắn gọn, dùng cho xuất dữ liệu và nhập danh sách.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="add_subject" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm môn học</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" id="edit_subject_id" name="edit_subject_id">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Chỉnh sửa môn học</h5>
                            <small class="text-muted">Cập nhật thông tin môn học đã chọn</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Tên môn học *</label>
                            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                        </div>
                        <div class="mb-0">
                            <label for="edit_code" class="form-label">Mã môn học *</label>
                            <input type="text" class="form-control" id="edit_code" name="edit_code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="edit_subject" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSubject(id, name, code) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
        }

        function confirmDeleteSubject(id, name) {
            if (confirm('Bạn có chắc muốn xóa môn học "' + name + '" không?\n\nHệ thống sẽ xóa môn học khỏi danh mục.')) {
                window.location.href = '?delete=' + id;
            }
        }
    </script>
</body>
</html>
