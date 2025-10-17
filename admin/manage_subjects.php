<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

$users = json_decode(file_get_contents('user.json'), true) ?: [];
$fullname = $users['admin']['fullname'] ?? 'Admin';

// Load subjects
$subjectsFile = 'subjects.json';
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];

// Handle add subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $name = $_POST['name'];
    $code = $_POST['code'];

    // Generate new id
    $maxId = 0;
    foreach ($subjects as $subj) {
        if ($subj['id'] > $maxId) $maxId = $subj['id'];
    }
    $newId = $maxId + 1;

    $subjects[] = ['id' => $newId, 'name' => $name, 'code' => $code];
    file_put_contents($subjectsFile, json_encode($subjects, JSON_PRETTY_PRINT));
    $message = 'Môn học đã được thêm thành công.';
}

// Handle edit subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $id = (int)$_POST['edit_subject_id'];
    $name = $_POST['edit_name'];
    $code = $_POST['edit_code'];

    foreach ($subjects as &$subj) {
        if ($subj['id'] === $id) {
            $subj['name'] = $name;
            $subj['code'] = $code;
            break;
        }
    }
    file_put_contents($subjectsFile, json_encode($subjects, JSON_PRETTY_PRINT));
    $message = 'Môn học đã được cập nhật thành công.';
}

// Handle delete subject
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $subjects = array_filter($subjects, function($subj) use ($id) {
        return $subj['id'] !== $id;
    });
    file_put_contents($subjectsFile, json_encode(array_values($subjects), JSON_PRETTY_PRINT));
    $message = 'Môn học đã được xóa.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Môn Học - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_subjects.php'; include 'navbar.php'; ?>

    <div class="main-content">
        <h1>Quản Lý Môn Học</h1>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Thêm Môn Học Mới</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Tên môn học</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">Mã môn học</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-primary mt-3">Thêm Môn Học</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Danh Sách Môn Học</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên môn học</th>
                            <th>Mã môn học</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo $subject['id']; ?></td>
                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editSubject(<?php echo $subject['id']; ?>, '<?php echo addslashes($subject['name']); ?>', '<?php echo addslashes($subject['code']); ?>')">Chỉnh Sửa</button>
                                    <a href="?delete=<?php echo $subject['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa môn học này?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Subject Modal -->
        <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSubjectModalLabel">Chỉnh Sửa Môn Học</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <input type="hidden" id="edit_subject_id" name="edit_subject_id">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Tên môn học</label>
                                <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_code" class="form-label">Mã môn học</label>
                                <input type="text" class="form-control" id="edit_code" name="edit_code" required>
                            </div>
                            <button type="submit" name="edit_subject" class="btn btn-primary">Cập Nhật</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSubject(id, name, code) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            var modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
            modal.show();
        }
    </script>
</body>
</html>
