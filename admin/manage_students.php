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
    <title>Quản Lý Học Sinh - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- <link rel="stylesheet" href="style.css"> -->
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'manage_students.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">👨‍🎓 Quản Lý Học Sinh</h2>
                        <div>
                            <button type="button" class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                ➕ Thêm Học Sinh Mới
                            </button>
                            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                📥 Nhập Từ Excel/CSV
                            </button>
                           
                            <button type="button" class="btn btn-secondary" id="exportBtn">
                                📤 Xuất Danh Sách
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="classFilter" class="form-label">Lọc theo lớp:</label>
                                    <select class="form-select" id="classFilter">
                                        <option value="">Tất cả lớp</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label for="searchInput" class="form-label">Tìm kiếm:</label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm theo mã HS hoặc tên...">
                                </div>
                            </div>
                        </div>

                        <table id="studentsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã HS</th>
                                    <th>Họ và Tên</th>
                                    <th>Giới Tính</th>
                                    <th>Ngày Sinh</th>
                                    <th>Lớp</th>
                                    <th>Email</th>
                                    <th>Ghi Chú</th>
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

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Học Sinh Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentCode" class="form-label">Mã Học Sinh *</label>
                                    <input type="text" class="form-control" id="studentCode" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentName" class="form-label">Họ và Tên *</label>
                                    <input type="text" class="form-control" id="studentName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentGender" class="form-label">Giới Tính *</label>
                                    <select class="form-select" id="studentGender" required>
                                        <option value="">Chọn giới tính</option>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentBirthDate" class="form-label">Ngày Sinh *</label>
                                    <input type="date" class="form-control" id="studentBirthDate" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentClass" class="form-label">Lớp *</label>
                                    <select class="form-select" id="studentClass" required>
                                        <option value="">Chọn lớp</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="studentEmail">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="studentNotes" class="form-label">Ghi Chú</label>
                            <textarea class="form-control" id="studentNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="saveStudentBtn">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Học Sinh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" id="editStudentId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentCode" class="form-label">Mã Học Sinh *</label>
                                    <input type="text" class="form-control" id="editStudentCode" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentName" class="form-label">Họ và Tên *</label>
                                    <input type="text" class="form-control" id="editStudentName" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentGender" class="form-label">Giới Tính *</label>
                                    <select class="form-select" id="editStudentGender" required>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentBirthDate" class="form-label">Ngày Sinh *</label>
                                    <input type="date" class="form-control" id="editStudentBirthDate" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentClass" class="form-label">Lớp *</label>
                                    <select class="form-select" id="editStudentClass" required>
                                        <option value="">Chọn lớp</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStudentEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editStudentEmail">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentNotes" class="form-label">Ghi Chú</label>
                            <textarea class="form-control" id="editStudentNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="updateStudentBtn">Cập Nhật</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nhập Danh Sách Học Sinh Từ Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Định dạng file:</strong><br>
                        Cột 1: Mã học sinh<br>
                        Cột 2: Họ và tên<br>
                        Cột 3: Giới tính (Nam/Nữ)<br>
                        Cột 4: Ngày sinh (YYYY-MM-DD)<br>
                        Cột 5: Mã lớp<br>
                        Cột 6: Email (tùy chọn)<br>
                        Cột 7: Ghi chú (tùy chọn)
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
        let studentsTable;
        let classesData = [];
        let importData = [];
        let jsonImportData = [];

        // Load classes for dropdowns
        async function loadClasses() {
            try {
                const response = await fetch('api/get_classes.php');
                const result = await response.json();

                if (result.success) {
                    classesData = result.data;

                    // Populate class filters and dropdowns
                    const classFilter = document.getElementById('classFilter');
                    const studentClass = document.getElementById('studentClass');
                    const editStudentClass = document.getElementById('editStudentClass');

                    classFilter.innerHTML = '<option value="">Tất cả lớp</option>';
                    studentClass.innerHTML = '<option value="">Chọn lớp</option>';
                    editStudentClass.innerHTML = '<option value="">Chọn lớp</option>';

                    classesData.forEach(classItem => {
                        classFilter.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                        studentClass.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                        editStudentClass.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        // Load students table
        async function loadStudents(classFilter = '') {
            try {
                const url = classFilter ? `api/get_students.php?class_id=${classFilter}` : 'api/get_students.php';
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    if (studentsTable) {
                        studentsTable.destroy();
                    }

                    // Custom sort for Vietnamese names (by last name)
                    $.fn.dataTable.ext.type.order['vietnamese-name-asc'] = function (a, b) {
                        const getLastName = (name) => {
                            const words = name.trim().split(' ');
                            return words[words.length - 1].toLowerCase();
                        };
                        const aLast = getLastName(a);
                        const bLast = getLastName(b);
                        return aLast.localeCompare(bLast, 'vi');
                    };

                    studentsTable = $('#studentsTable').DataTable({
                        data: result.data,
                        columns: [
                            { data: 'code' },
                            { data: 'name', type: 'vietnamese-name' },
                            { data: 'gender' },
                            {
                                data: 'birth_date',
                                render: function(data) {
                                    if (!data) return '-';
                                    let date;
                                    if (typeof data === 'string' && data.includes('/')) {
                                        // Assume DD/MM/YYYY format
                                        const parts = data.split('/');
                                        if (parts.length === 3) {
                                            const day = parseInt(parts[0], 10);
                                            const month = parseInt(parts[1], 10) - 1; // Month is 0-based
                                            const year = parseInt(parts[2], 10);
                                            date = new Date(year, month, day);
                                        } else {
                                            date = new Date(data);
                                        }
                                    } else {
                                        date = new Date(data);
                                    }
                                    if (isNaN(date.getTime())) return '-';
                                    const day = date.getDate().toString().padStart(2, '0');
                                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                                    const year = date.getFullYear();
                                    return `${day}/${month}/${year}`;
                                }
                            },
                            { data: 'class_name' },
                            {
                                data: 'email',
                                render: function(data) { return data || '-'; }
                            },
                            {
                                data: 'notes',
                                render: function(data) { return data || '-'; }
                            },
                            {
                                data: null,
                                render: function(data) {
                                    return `
                                        <button class="btn btn-sm btn-warning me-1" onclick="editStudent('${data.id}')" title="Chỉnh sửa">✏️</button>
                                        <button class="btn btn-sm btn-info me-1" onclick="resetStudentPassword('${data.id}', '${data.name}')" title="Reset mật khẩu">🔑</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteStudent('${data.id}', '${data.name}')" title="Xóa">🗑️</button>
                                    `;
                                },
                                orderable: false
                            }
                        ],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 25,
                        order: [[4, 'asc'], [1, 'asc']]  // Sort by class_name (column 4), then by name (column 1)
                    });
                } else {
                    alert('Không thể tải danh sách học sinh: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading students:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // Add new student
        document.getElementById('saveStudentBtn').addEventListener('click', async function() {
            const code = document.getElementById('studentCode').value.trim();
            const name = document.getElementById('studentName').value.trim();
            const gender = document.getElementById('studentGender').value;
            const birthDate = document.getElementById('studentBirthDate').value;
            const classId = document.getElementById('studentClass').value;
            const email = document.getElementById('studentEmail').value.trim();
            const notes = document.getElementById('studentNotes').value.trim();

            if (!code || !name || !gender || !birthDate || !classId) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return;
            }

            try {
                const response = await fetch('api/add_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code, name, gender, birth_date: birthDate, class_id: classId, email, notes })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Thêm học sinh thành công!');
                    document.getElementById('addStudentForm').reset();
                    bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
                    loadStudents(document.getElementById('classFilter').value);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error adding student:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        });

        // Edit student
        function editStudent(id) {
            // Find student data
            const studentData = studentsTable.rows().data().toArray().find(s => s.id === id);
            if (studentData) {
                document.getElementById('editStudentId').value = studentData.id;
                document.getElementById('editStudentCode').value = studentData.code;
                document.getElementById('editStudentName').value = studentData.name;
                document.getElementById('editStudentGender').value = studentData.gender;
                document.getElementById('editStudentBirthDate').value = studentData.birth_date;
                document.getElementById('editStudentClass').value = studentData.class_id;
                document.getElementById('editStudentEmail').value = studentData.email || '';
                document.getElementById('editStudentNotes').value = studentData.notes || '';

                new bootstrap.Modal(document.getElementById('editStudentModal')).show();
            }
        }

        // Update student
        document.getElementById('updateStudentBtn').addEventListener('click', async function() {
            const id = document.getElementById('editStudentId').value;
            const code = document.getElementById('editStudentCode').value.trim();
            const name = document.getElementById('editStudentName').value.trim();
            const gender = document.getElementById('editStudentGender').value;
            const birthDate = document.getElementById('editStudentBirthDate').value;
            const classId = document.getElementById('editStudentClass').value;
            const email = document.getElementById('editStudentEmail').value.trim();
            const notes = document.getElementById('editStudentNotes').value.trim();

            if (!code || !name || !gender || !birthDate || !classId) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return;
            }

            try {
                const response = await fetch('api/update_student.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, code, name, gender, birth_date: birthDate, class_id: classId, email, notes })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Cập nhật học sinh thành công!');
                    bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
                    loadStudents(document.getElementById('classFilter').value);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating student:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        });

        // Reset student password
        async function resetStudentPassword(id, name) {
            const confirmed = confirm(`Bạn có chắc muốn reset mật khẩu của học sinh "${name}"?\n\nMật khẩu mới sẽ là: 123456`);
            
            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch('api/reset_student_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: id, new_password: '123456' })
                });

                const result = await response.json();
                if (result.success) {
                    alert(`Reset mật khẩu thành công!\n\nTên học sinh: ${name}\nMật khẩu mới: ${result.new_password}`);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // Delete student
        async function deleteStudent(id, name) {
            if (!confirm(`Bạn có chắc muốn xóa học sinh "${name}"?`)) {
                return;
            }

            try {
                const response = await fetch('api/delete_student.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Xóa học sinh thành công!');
                    loadStudents(document.getElementById('classFilter').value);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting student:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // Class filter
        document.getElementById('classFilter').addEventListener('change', function() {
            loadStudents(this.value);
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            studentsTable.search(this.value).draw();
        });

        // Export functionality
        document.getElementById('exportBtn').addEventListener('click', async function() {
            try {
                const classFilter = document.getElementById('classFilter').value;
                const url = classFilter ? `api/get_students.php?class_id=${classFilter}` : 'api/get_students.php';
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    // Create Excel file
                    const ws = XLSX.utils.json_to_sheet(result.data.map(student => ({
                        'Mã HS': student.code,
                        'Họ và Tên': student.name,
                        'Giới Tính': student.gender,
                        'Ngày Sinh': student.birth_date,
                        'Lớp': student.class_name,
                        'Email': student.email || '',
                        'Ghi Chú': student.notes || ''
                    })));

                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'DanhSachHocSinh');

                    const wbout = XLSX.write(wb, {bookType:'xlsx', type:'binary'});
                    const blob = new Blob([s2ab(wbout)], {type:"application/octet-stream"});

                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `danh_sach_hoc_sinh_${new Date().toISOString().split('T')[0]}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    alert('File Excel đã được tải xuống!');
                } else {
                    alert('Không có dữ liệu để xuất!');
                }
            } catch (error) {
                console.error('Error exporting data:', error);
                alert('Lỗi xuất dữ liệu: ' + error.message);
            }
        });

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
                        gender: row[2] || '',
                        birth_date: row[3] || '',
                        class_code: row[4] || '',
                        email: row[5] || '',
                        notes: row[6] || ''
                    })).filter(row => row.code && row.name);

                    // Convert class_code to class_id
                    importData.forEach(student => {
                        const classInfo = classesData.find(c => c.code === student.class_code);
                        if (classInfo) {
                            student.class_id = classInfo.id;
                            student.class_name = classInfo.name;
                        } else {
                            student.class_id = '';
                            student.class_name = 'Unknown';
                        }
                    });

                    // Show preview
                    const previewTable = document.getElementById('previewTable');
                    previewTable.querySelector('thead').innerHTML = `
                        <tr>
                            <th>Mã HS</th>
                            <th>Họ và Tên</th>
                            <th>Giới Tính</th>
                            <th>Ngày Sinh</th>
                            <th>Lớp</th>
                            <th>Email</th>
                            <th>Ghi Chú</th>
                        </tr>
                    `;

                    const tbody = previewTable.querySelector('tbody');
                    tbody.innerHTML = importData.map(row => `
                        <tr>
                            <td>${row.code}</td>
                            <td>${row.name}</td>
                            <td>${row.gender}</td>
                            <td>${row.birth_date}</td>
                            <td>${row.class_name}</td>
                            <td>${row.email || ''}</td>
                            <td>${row.notes || ''}</td>
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

                for (const studentData of importData) {
                    if (!studentData.class_id) {
                        errorCount++;
                        errors.push(`${studentData.code}: Lớp không tồn tại`);
                        continue;
                    }

                    try {
                        const response = await fetch('api/add_student.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(studentData)
                        });

                        const result = await response.json();
                        if (result.success) {
                            successCount++;
                        } else {
                            errorCount++;
                            errors.push(`${studentData.code}: ${result.message}`);
                        }
                    } catch (error) {
                        errorCount++;
                        errors.push(`${studentData.code}: Lỗi kết nối`);
                    }
                }

                let message = `Nhập thành công ${successCount} học sinh.`;
                if (errorCount > 0) {
                    message += `\nLỗi ${errorCount} học sinh:\n${errors.join('\n')}`;
                }

                alert(message);
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                loadStudents(document.getElementById('classFilter').value);

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

         function s2ab(s) {
            const buf = new ArrayBuffer(s.length);
            const view = new Uint8Array(buf);
            for (let i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
            return buf;
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses().then(() => {
                loadStudents();
            });
        });
    </script>
</body>
</html>
