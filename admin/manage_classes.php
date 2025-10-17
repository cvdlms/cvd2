<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Lớp Học - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- <link rel="stylesheet" href="style.css"> -->
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_classes.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">📚 Quản Lý Lớp Học</h2>
                        <div>
                            <button type="button" class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addClassModal">
                                ➕ Thêm Lớp Mới
                            </button>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">
                                📥 Nhập Từ Excel/CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="classesTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã Lớp</th>
                                    <th>Tên Lớp</th>
                                    <th>Năm Học</th>
                                    <th>Giáo Viên Chủ Nhiệm</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Lớp Học Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassForm">
                        <div class="mb-3">
                            <label for="classCode" class="form-label">Mã Lớp *</label>
                            <input type="text" class="form-control" id="classCode" required>
                        </div>
                        <div class="mb-3">
                            <label for="className" class="form-label">Tên Lớp *</label>
                            <input type="text" class="form-control" id="className" required>
                        </div>
                        <div class="mb-3">
                            <label for="classYear" class="form-label">Năm Học *</label>
                            <input type="text" class="form-control" id="classYear" placeholder="2024-2025" required>
                        </div>
                        <div class="mb-3">
                            <label for="classTeacher" class="form-label">Giáo Viên Chủ Nhiệm *</label>
                            <input type="text" class="form-control" id="classTeacher" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="saveClassBtn">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Lớp Học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editClassForm">
                        <input type="hidden" id="editClassId">
                        <div class="mb-3">
                            <label for="editClassCode" class="form-label">Mã Lớp *</label>
                            <input type="text" class="form-control" id="editClassCode" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Tên Lớp *</label>
                            <input type="text" class="form-control" id="editClassName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassYear" class="form-label">Năm Học *</label>
                            <input type="text" class="form-control" id="editClassYear" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassTeacher" class="form-label">Giáo Viên Chủ Nhiệm *</label>
                            <input type="text" class="form-control" id="editClassTeacher" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="updateClassBtn">Cập Nhật</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nhập Danh Sách Lớp Từ Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Định dạng file:</strong><br>
                        Cột 1: Mã lớp<br>
                        Cột 2: Tên lớp<br>
                        Cột 3: Năm học<br>
                        Cột 4: Giáo viên chủ nhiệm
                    </div>
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Chọn File Excel/CSV</label>
                            <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </form>
                    <div id="previewSection" style="display: none;">
                        <h6>Xem Trước Dữ Liệu:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="previewTable">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-info" id="previewBtn">Xem Trước</button>
                    <button type="button" class="btn btn-success" id="importBtn" style="display: none;">Nhập Dữ Liệu</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        let classesTable;
        let importData = [];

        // Load classes table
        async function loadClasses() {
            try {
                const response = await fetch('api/get_classes.php');
                const result = await response.json();

                if (result.success) {
                    if (classesTable) {
                        classesTable.destroy();
                    }

                    classesTable = $('#classesTable').DataTable({
                        data: result.data,
                        columns: [
                            { data: 'code' },
                            { data: 'name' },
                            { data: 'year' },
                            { data: 'teacher' },
                            {
                                data: null,
                                render: function(data) {
                                    return `
                                        <button class="btn btn-sm btn-warning me-1" onclick="editClass('${data.id}')">✏️</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteClass('${data.id}', '${data.name}')">🗑️</button>
                                    `;
                                },
                                orderable: false
                            }
                        ],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 25
                    });
                } else {
                    alert('Không thể tải danh sách lớp: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading classes:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // Add new class
        document.getElementById('saveClassBtn').addEventListener('click', async function() {
            const code = document.getElementById('classCode').value.trim();
            const name = document.getElementById('className').value.trim();
            const year = document.getElementById('classYear').value.trim();
            const teacher = document.getElementById('classTeacher').value.trim();

            if (!code || !name || !year || !teacher) {
                alert('Vui lòng điền đầy đủ thông tin!');
                return;
            }

            try {
                const response = await fetch('api/add_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code, name, year, teacher })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Thêm lớp thành công!');
                    document.getElementById('addClassForm').reset();
                    bootstrap.Modal.getInstance(document.getElementById('addClassModal')).hide();
                    loadClasses();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error adding class:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        });

        // Edit class
        function editClass(id) {
            // Find class data
            const classData = classesTable.rows().data().toArray().find(c => c.id === id);
            if (classData) {
                document.getElementById('editClassId').value = classData.id;
                document.getElementById('editClassCode').value = classData.code;
                document.getElementById('editClassName').value = classData.name;
                document.getElementById('editClassYear').value = classData.year;
                document.getElementById('editClassTeacher').value = classData.teacher;

                new bootstrap.Modal(document.getElementById('editClassModal')).show();
            }
        }

        // Update class
        document.getElementById('updateClassBtn').addEventListener('click', async function() {
            const id = document.getElementById('editClassId').value;
            const code = document.getElementById('editClassCode').value.trim();
            const name = document.getElementById('editClassName').value.trim();
            const year = document.getElementById('editClassYear').value.trim();
            const teacher = document.getElementById('editClassTeacher').value.trim();

            if (!code || !name || !year || !teacher) {
                alert('Vui lòng điền đầy đủ thông tin!');
                return;
            }

            try {
                const response = await fetch('api/update_class.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, code, name, year, teacher })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Cập nhật lớp thành công!');
                    bootstrap.Modal.getInstance(document.getElementById('editClassModal')).hide();
                    loadClasses();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating class:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        });

        // Delete class
        async function deleteClass(id, name) {
            if (!confirm(`Bạn có chắc muốn xóa lớp "${name}"?\n\nLưu ý: Tất cả học sinh trong lớp này sẽ bị xóa!`)) {
                return;
            }

            try {
                const response = await fetch('api/delete_class.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Xóa lớp thành công!');
                    loadClasses();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting class:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // Preview import data
        document.getElementById('previewBtn').addEventListener('click', function() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];

            if (!file) {
                alert('Vui lòng chọn file!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                    // Skip header row and process data
                    importData = jsonData.slice(1).map(row => ({
                        code: row[0] || '',
                        name: row[1] || '',
                        year: row[2] || '',
                        teacher: row[3] || ''
                    })).filter(row => row.code && row.name);

                    // Show preview
                    const previewTable = document.getElementById('previewTable');
                    previewTable.querySelector('thead').innerHTML = `
                        <tr>
                            <th>Mã Lớp</th>
                            <th>Tên Lớp</th>
                            <th>Năm Học</th>
                            <th>Giáo Viên</th>
                        </tr>
                    `;

                    const tbody = previewTable.querySelector('tbody');
                    tbody.innerHTML = importData.map(row => `
                        <tr>
                            <td>${row.code}</td>
                            <td>${row.name}</td>
                            <td>${row.year}</td>
                            <td>${row.teacher}</td>
                        </tr>
                    `).join('');

                    document.getElementById('previewSection').style.display = 'block';
                    document.getElementById('importBtn').style.display = 'inline-block';
                    document.getElementById('previewBtn').style.display = 'none';

                } catch (error) {
                    console.error('Error reading file:', error);
                    alert('Lỗi đọc file: ' + error.message);
                }
            };

            reader.readAsArrayBuffer(file);
        });

        // Import data
        document.getElementById('importBtn').addEventListener('click', async function() {
            if (importData.length === 0) {
                alert('Không có dữ liệu để nhập!');
                return;
            }

            const importBtn = document.getElementById('importBtn');
            importBtn.disabled = true;
            importBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang nhập dữ liệu...';

            try {
                let successCount = 0;
                let errorCount = 0;
                let errors = [];

                for (const classData of importData) {
                    try {
                        const response = await fetch('api/add_class.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(classData)
                        });

                        const result = await response.json();
                        if (result.success) {
                            successCount++;
                        } else {
                            errorCount++;
                            errors.push(`${classData.code}: ${result.message}`);
                        }
                    } catch (error) {
                        errorCount++;
                        errors.push(`${classData.code}: Lỗi kết nối`);
                    }
                }

                let message = `Nhập thành công ${successCount} lớp.`;
                if (errorCount > 0) {
                    message += `\nLỗi ${errorCount} lớp:\n${errors.join('\n')}`;
                }

                alert(message);
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                loadClasses();

                // Reset modal
                document.getElementById('importForm').reset();
                document.getElementById('previewSection').style.display = 'none';
                document.getElementById('importBtn').style.display = 'none';
                document.getElementById('previewBtn').style.display = 'inline-block';
                importData = [];

            } catch (error) {
                console.error('Error importing data:', error);
                alert('Lỗi nhập dữ liệu: ' + error.message);
            } finally {
                importBtn.disabled = false;
                importBtn.innerHTML = 'Nhập Dữ Liệu';
            }
        });

        // Load table on page load
        document.addEventListener('DOMContentLoaded', loadClasses);
    </script>
</body>
</html>
