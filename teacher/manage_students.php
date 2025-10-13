<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Load teacher's assigned classes
$teacherClasses = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_classes.json'), true);
$assignedClasses = $teacherClasses[$username] ?? [];

$title = 'Xem Học Sinh - CVD';
include '../includes/teacher_header.php';
?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">👨‍🎓 Danh Sách Học Sinh</h2>
                        <button type="button" class="btn btn-success" id="exportBtn">
                            📤 Xuất Excel
                        </button>
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




    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

    <script>
        let studentsTable;
        let classesData = [];

        // Load classes for dropdowns
        async function loadClasses() {
            try {
                const response = await fetch('api/get_classes.php');
                const result = await response.json();

                if (result.success) {
                    classesData = result.data;

                    // Populate class filters
                    const classFilter = document.getElementById('classFilter');

                    classFilter.innerHTML = '<option value="">Tất cả lớp</option>';

                    classesData.forEach(classItem => {
                        classFilter.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
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
                                    const date = new Date(data);
                                    return date.toLocaleDateString('vi-VN');
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

        // Class filter
        document.getElementById('classFilter').addEventListener('change', function() {
            loadStudents(this.value);
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            studentsTable.search(this.value).draw();
        });

        // Export to Excel with sheets by class
        document.getElementById('exportBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('api/get_students.php');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    // Group students by class
                    const groupedByClass = result.data.reduce((acc, student) => {
                        const className = student.class_name;
                        if (!acc[className]) {
                            acc[className] = [];
                        }
                        acc[className].push(student);
                        return acc;
                    }, {});

                    const wb = XLSX.utils.book_new();

                    // Create a sheet for each class
                    Object.keys(groupedByClass).forEach(className => {
                        const students = groupedByClass[className];
                        const wsData = [['STT', 'Mã HS', 'Họ và Tên', 'Giới Tính', 'Ngày Sinh', 'Lớp', 'Email', 'Ghi Chú']];

                        students.forEach((student, index) => {
                            const birthDate = student.birth_date ? new Date(student.birth_date).toLocaleDateString('vi-VN') : '-';
                            wsData.push([
                                index + 1,
                                student.code || '',
                                student.name || '',
                                student.gender || '',
                                birthDate,
                                student.class_name || '',
                                student.email || '',
                                student.notes || ''
                            ]);
                        });

                        const ws = XLSX.utils.aoa_to_sheet(wsData);
                        XLSX.utils.book_append_sheet(wb, ws, className);
                    });

                    XLSX.writeFile(wb, 'DanhSachHocSinh.xlsx');
                } else {
                    alert('Không có dữ liệu để xuất.');
                }
            } catch (error) {
                console.error('Error exporting:', error);
                alert('Lỗi khi xuất dữ liệu: ' + error.message);
            }
        });

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses().then(() => {
                loadStudents();
            });
        });
    </script>
<?php include '../includes/footer.php'; ?>
