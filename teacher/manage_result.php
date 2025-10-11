<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';
$username = $_SESSION['username'];

$title = 'Kết quả kiểm tra - CVD';
include '../includes/teacher_header.php';
?>
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">👨‍🎓 Kết quả kiểm tra</h2>
                        <div>
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
                                        <option value="">Tất cả lớp được giao</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Students Table -->
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped table-hover" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã học sinh</th>
                                        <th>Lớp</th>
                                        <th>Họ và tên</th>
                                        <th>Điểm TX1</th>
                                        <th>Điểm TX2</th>
                                        <th>Điểm Bài tập</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        let dataTable;
        let assignedClasses = [];

        // Load assigned classes for teacher
        async function loadAssignedClasses() {
            try {
                const response = await fetch(`../admin/get_assigned_classes.php?teacher_username=<?php echo $username; ?>`);
                const result = await response.json();
                if (result.success) {
                    assignedClasses = result.data;
                    populateClassFilter();
                } else {
                    console.error('Failed to load classes:', result.message);
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        function populateClassFilter() {
            const classFilter = document.getElementById('classFilter');
            // Load all classes for names, but filter to assigned
            fetch('../admin/api/get_classes.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const allClasses = result.data;
                        const assignedClassMap = {};
                        const assignedClassNames = [];
                        assignedClasses.forEach(classId => {
                            const cls = allClasses.find(c => c.id === classId);
                            if (cls) {
                                assignedClassMap[classId] = cls;
                                assignedClassNames.push(cls.name);
                            }
                        });

                        // Add options for assigned classes
                        Object.keys(assignedClassMap).forEach(classId => {
                            const cls = assignedClassMap[classId];
                            const option = document.createElement('option');
                            option.value = cls.name;
                            option.textContent = `${cls.code} - ${cls.name}`;
                            classFilter.appendChild(option);
                        });

                        // Store assigned class names globally
                        window.assignedClassNames = assignedClassNames;
                    }
                })
                .catch(error => console.error('Error loading all classes:', error));
        }

        // Load scores based on class filter (client-side filter since API loads all)
        async function loadStudents(classFilterValue) {
            if (dataTable) {
                dataTable.destroy();
            }

            let url = '../teacher/api/get_scores.php';

            try {
                const response = await fetch(url);
                const result = await response.json();
                if (result) {
                    let scores = result.data || [];
                    // Filter to assigned classes first
                    scores = scores.filter(score => window.assignedClassNames.includes(score.class));
                    // Then filter by selected class if not all
                    if (classFilterValue !== '') {
                        scores = scores.filter(score => score.class === classFilterValue);
                    }
                    displayStudents(scores);
                } else {
                    console.error('Failed to load scores');
                    $('#studentsTable tbody').empty();
                }
            } catch (error) {
                console.error('Error loading scores:', error);
                $('#studentsTable tbody').empty();
            }
        }

        function displayStudents(students) {
            const tbody = $('#studentsTable tbody');
            tbody.empty();

            students.forEach((student, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${student.student_code || ''}</td>
                        <td>${student.class || ''}</td>
                        <td>${student.name || ''}</td>
                        <td>${student.tx1 || 'N/A'}</td>
                        <td>${student.tx2 || 'N/A'}</td>
                        <td>N/A</td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Initialize DataTable with Vietnamese locale
            dataTable = $('#studentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                pageLength: 25,
                order: [[2, 'asc'], [3, 'asc']], // Sort by class, then name
                columnDefs: [
                    { orderable: false, targets: 0 } // STT not sortable
                ]
            });
        }

        // Export to Excel/CSV
        document.getElementById('exportBtn').addEventListener('click', function() {
            const classFilterValue = document.getElementById('classFilter').value;
            loadStudentsForExport(classFilterValue);
        });

        async function loadStudentsForExport(classFilterValue) {
            let url = '../teacher/api/get_scores.php';

            try {
                const response = await fetch(url);
                const result = await response.json();
                if (result) {
                    let scores = result.data || [];
                    // Filter to assigned classes first
                    scores = scores.filter(score => window.assignedClassNames.includes(score.class));
                    // Then filter by selected class if not all
                    if (classFilterValue !== '') {
                        scores = scores.filter(score => score.class === classFilterValue);
                    }
                    exportToExcel(scores);
                } else {
                    alert('Lỗi khi tải dữ liệu để xuất.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Lỗi khi xuất dữ liệu.');
            }
        }

        function exportToExcel(students) {
            const wsData = [['STT', 'Mã học sinh', 'Lớp', 'Họ và tên', 'Điểm TX1', 'Điểm TX2', 'Điểm Bài tập']];
            students.forEach((student, index) => {
                wsData.push([
                    index + 1,
                    student.student_code || '',
                    student.class || '',
                    student.name || '',
                    student.tx1 || 'N/A',
                    student.tx2 || 'N/A',
                    'N/A'
                ]);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'KetQua');
            XLSX.writeFile(wb, 'KetQuaKiemTra.xlsx');
        }

        // Event listeners
        document.getElementById('classFilter').addEventListener('change', function() {
            loadStudents(this.value);
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadAssignedClasses().then(() => {
                loadStudents(''); // Load all initially
            });
        });
    </script>
