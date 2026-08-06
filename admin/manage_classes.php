<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

// Stats from classes.json
$classes = json_decode(file_get_contents(__DIR__ . '/../admin/classes.json'), true) ?: [];
$total_classes = count($classes);
$grades = [];
$homeroom_teachers = [];
$years = [];
foreach ($classes as $class) {
    $grade = substr((string) ($class['code'] ?? $class['name'] ?? ''), 0, 1);
    if (in_array($grade, ['6', '7', '8', '9'], true)) $grades[$grade] = true;
    if (!empty($class['teacher'])) $homeroom_teachers[$class['teacher']] = true;
    if (!empty($class['year'])) $years[$class['year']] = true;
}
$total_grades = count($grades);
$total_homeroom = count($homeroom_teachers);
$total_years = count($years);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Lớp Học - CVD Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/manage_classes.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_classes.php'; include 'navbar.php'; ?>

    <main class="cvd-page">
        <header class="cvd-page-header">
            <div>
                <div class="cvd-eyebrow"><i class="bi bi-building"></i> Danh mục trường học</div>
                <h1>Lớp học</h1>
                <p class="cvd-sub">Quản lý danh sách lớp học theo khối, năm học và giáo viên chủ nhiệm.</p>
            </div>
            <div class="cvd-page-actions">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Nhập Excel/CSV
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class="bi bi-plus-lg me-1"></i> Thêm lớp mới
                </button>
            </div>
        </header>

        <section class="cvd-stats" aria-label="Thống kê lớp học">
            <div class="cvd-stat cvd-reveal">
                <span class="cvd-stat-icon"><i class="bi bi-building-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $total_classes; ?></div>
                    <div class="cvd-stat-label">Tổng số lớp học</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d1">
                <span class="cvd-stat-icon"><i class="bi bi-grid-3x3-gap"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $total_grades; ?></div>
                    <div class="cvd-stat-label">Khối học (6 – 9)</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d2">
                <span class="cvd-stat-icon is-accent"><i class="bi bi-person-badge"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $total_homeroom; ?></div>
                    <div class="cvd-stat-label">Giáo viên chủ nhiệm</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d3">
                <span class="cvd-stat-icon is-gold"><i class="bi bi-calendar3"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $total_years; ?></div>
                    <div class="cvd-stat-label">Năm học đang quản lý</div>
                </div>
            </div>
        </section>

        <section class="cvd-panel cvd-reveal cvd-reveal-d2">
            <div class="cvd-panel-header">
                <div>
                    <h2>Danh sách lớp học</h2>
                    <p>Thông tin lớp, năm học và giáo viên chủ nhiệm</p>
                </div>
                <span class="badge bg-success-subtle"><?php echo $total_classes; ?> lớp</span>
            </div>
            <div class="cvd-table-wrap">
                <table id="classesTable" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Mã Lớp</th>
                            <th>Tên Lớp</th>
                            <th>Năm Học</th>
                            <th>Giáo Viên Chủ Nhiệm</th>
                            <th class="text-end">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <div class="cvd-footer-credit">
            Được phát triển & vận hành bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a>
        </div>
    </main>

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Thêm lớp học mới</h5>
                        <small class="text-muted">Tạo lớp học mới trong hệ thống</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassForm">
                        <div class="mb-3">
                            <label for="classCode" class="form-label">Mã Lớp *</label>
                            <input type="text" class="form-control" id="classCode" required placeholder="Ví dụ: 6A1">
                        </div>
                        <div class="mb-3">
                            <label for="className" class="form-label">Tên Lớp *</label>
                            <input type="text" class="form-control" id="className" required placeholder="Ví dụ: 6A1">
                        </div>
                        <div class="mb-3">
                            <label for="classYear" class="form-label">Năm Học *</label>
                            <input type="text" class="form-control" id="classYear" placeholder="2025-2026" required>
                        </div>
                        <div class="mb-0">
                            <label for="classTeacher" class="form-label">Giáo Viên Chủ Nhiệm *</label>
                            <input type="text" class="form-control" id="classTeacher" required placeholder="Họ và tên giáo viên chủ nhiệm">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="saveClassBtn"><i class="bi bi-plus-lg me-1"></i>Lưu lớp</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chỉnh sửa lớp học</h5>
                        <small class="text-muted">Cập nhật thông tin lớp học đã chọn</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
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
                        <div class="mb-0">
                            <label for="editClassTeacher" class="form-label">Giáo Viên Chủ Nhiệm *</label>
                            <input type="text" class="form-control" id="editClassTeacher" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="updateClassBtn"><i class="bi bi-check-lg me-1"></i>Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Nhập danh sách lớp từ Excel/CSV</h5>
                        <small class="text-muted">Xem trước dữ liệu trước khi thêm vào hệ thống</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Định dạng file:</strong><br>
                        Cột 1: Mã lớp · Cột 2: Tên lớp · Cột 3: Năm học · Cột 4: Giáo viên chủ nhiệm
                    </div>
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Chọn File Excel/CSV</label>
                            <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </form>
                    <div id="previewSection" style="display: none;">
                        <h6 class="mb-2">Xem Trước Dữ Liệu:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="previewTable">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-outline-primary" id="previewBtn"><i class="bi bi-eye me-1"></i>Xem Trước</button>
                    <button type="button" class="btn btn-success" id="importBtn" style="display: none;"><i class="bi bi-file-earmark-arrow-down me-1"></i>Nhập Dữ Liệu</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
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
                            {
                                data: 'code',
                                render: function(data) {
                                    const grade = String(data).charAt(0);
                                    return `<span class="class-grade-badge class-grade-${grade}">${data}</span>`;
                                }
                            },
                            { data: 'name' },
                            { data: 'year' },
                            { data: 'teacher' },
                            {
                                data: null,
                                render: function(data) {
                                    return `
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-secondary" title="Chỉnh sửa" onclick="editClass('${data.id}')"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" title="Xóa lớp" onclick="deleteClass('${data.id}', '${data.name}')"><i class="bi bi-trash"></i></button>
                                        </div>
                                    `;
                                },
                                orderable: false,
                                className: 'text-end'
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
