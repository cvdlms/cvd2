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

// Load teacher's assigned subjects
$teacherSubjects = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_subjects.json'), true);
$assignedSubjects = $teacherSubjects[$username] ?? [];

// Load subjects for display names
$subjects = json_decode(file_get_contents(__DIR__ . '/../admin/subjects.json'), true) ?? [];
$subjectNames = [];
foreach ($subjects as $subject) {
    $subjectNames[$subject['id']] = $subject['name'];
}

$title = 'Xem Học Sinh - CVD';
include '../includes/teacher_header.php';
?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">👨‍🎓 Kết quả kiểm tra</h2>
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
                                    <th>STT</th>
                                    <th>Mã học sinh</th>
                                    <th>Họ và tên</th>
                                    <th>Lớp</th>
                                    <th>Điểm</th>
                                    <th>Kiểm tra</th>
                                    <th>Ngày</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Modal -->
        <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historyModalLabel">📊 Lịch sử điểm kiểm tra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input to store student code -->
                        <input type="hidden" id="modalStudentCodeInput">



                        <!-- Table Section -->
                        <div id="modalTableSection" style="display: none;">
                            <table id="modalHistoryTable" class="table table-striped table-bordered" width="100%">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên bài kiểm tra</th>
                                        <th>Lần làm</th>
                                        <th>Điểm</th>
                                        <th>Thời gian nộp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <!-- No Data Message -->
                        <div id="modalNoDataMessage" class="alert alert-info" style="display: none;">
                            Không tìm thấy dữ liệu lịch sử cho học sinh này.
                        </div>

                        <!-- Loading Spinner -->
                        <div id="modalLoadingSpinner" class="text-center" style="display: none;">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p>Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>




    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        const subjectNames = <?php echo json_encode($subjectNames); ?>;
    </script>

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
                    // Load student scores with cache busting
                    let scoresResult = [];
                    try {
                        const scoresResponse = await fetch('../shared/scores/student_score.json?v=' + Date.now());
                        if (scoresResponse.ok) {
                            scoresResult = await scoresResponse.json();
                        } else {
                            console.warn('Student scores file not found, using empty array');
                        }
                    } catch (scoreError) {
                        console.warn('Error loading student scores:', scoreError);
                        scoresResult = [];
                    }

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

                    // Transform data to include scores (filtered by teacher's subjects)
                    const tableData = result.data.map((student, index) => {
                        // Find student's latest score for teacher's subjects
                        const studentScores = scoresResult.filter(score =>
                            score.student_id === student.code &&
                            (<?php echo json_encode($assignedSubjects); ?>.includes(parseInt(score.subject_id)) || score.subject_id === null || score.subject_id === "")
                        );
                        const latestScore = studentScores.length > 0 ? studentScores.reduce((latest, current) =>
                            new Date(current.timestamp) > new Date(latest.timestamp) ? current : latest
                        ) : null;

                        return {
                            stt: index + 1,
                            code: student.code,
                            name: student.name,
                            class_name: student.class_name,
                            score: latestScore ? latestScore.score : '-',
                            test_name: latestScore ? latestScore.test_name : '-',
                            timestamp: latestScore ? new Date(latestScore.timestamp).toLocaleDateString('vi-VN') : '-'
                        };
                    });

                    studentsTable = $('#studentsTable').DataTable({
                        data: tableData,
                        columns: [
                            { data: 'stt' },
                            { data: 'code' },
                            { data: 'name', type: 'vietnamese-name' },
                            { data: 'class_name' },
                            { data: 'score' },
                            { data: 'test_name' },
                            { data: 'timestamp' },
                            {
                                data: null,
                                render: function(data, type, row) {
                                    if (row.score !== '-') {
                                        return `<button type="button" class="btn btn-sm btn-info detail-btn" data-student-code="${row.code}">📊 Chi tiết</button>`;
                                    } else {
                                        return '';
                                    }
                                },
                                orderable: false
                            }
                        ],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 50,
                        order: [[3, 'asc'], [2, 'asc']]  // Sort by class_name (column 3), then by name (column 2)
                    });
                } else {
                    alert('Không thể tải danh sách học sinh: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading students:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // History Modal Functions
        let modalHistoryTable;
        let modalAllData = [];

        // Open History Modal - Make it globally accessible
        window.openHistoryModal = function(studentCode) {
            document.getElementById('modalStudentCodeInput').value = studentCode;
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
            loadModalHistory(studentCode);
        };

        // Load History in Modal
        async function loadModalHistory(studentCode) {
            document.getElementById('modalLoadingSpinner').style.display = 'block';
            document.getElementById('modalTableSection').style.display = 'none';
            document.getElementById('modalNoDataMessage').style.display = 'none';

            try {
                const response = await fetch(`api/get_student_history.php?student_code=${encodeURIComponent(studentCode)}`);
                const result = await response.json();

                if (result.success) {
                    modalAllData = result.data;
                    displayModalData(modalAllData);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading modal history:', error);
                alert('Lỗi kết nối: ' + error.message);
            } finally {
                document.getElementById('modalLoadingSpinner').style.display = 'none';
            }
        }

        // Display Modal Data
        function displayModalData(data) {
            // Filter data to only include teacher's subjects
            const teacherSubjects = <?php echo json_encode($assignedSubjects); ?>;
            const filteredData = data.filter(item => {
                return teacherSubjects.includes(parseInt(item.subject_id));
            });

            if (filteredData.length === 0) {
                document.getElementById('modalNoDataMessage').style.display = 'block';
                document.getElementById('modalTableSection').style.display = 'none';
                return;
            }

            document.getElementById('modalNoDataMessage').style.display = 'none';
            document.getElementById('modalTableSection').style.display = 'block';

            // Initialize or update table
            if (modalHistoryTable) {
                modalHistoryTable.destroy();
            }

            const tableData = filteredData.map((exam, index) => ({
                stt: index + 1,
                test_name: exam.test_name,
                attempt: exam.attempt,
                score: exam.score,
                timestamp: new Date(exam.timestamp).toLocaleString('vi-VN')
            }));

            modalHistoryTable = $('#modalHistoryTable').DataTable({
                data: tableData,
                columns: [
                    { data: 'stt' },
                    { data: 'test_name' },
                    { data: 'attempt' },
                    { data: 'score' },
                    { data: 'timestamp' }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                pageLength: 10,
                order: [[4, 'desc']]  // Sort by timestamp descending (column 4)
            });
        }





        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Event delegation for detail buttons (works for dynamically created buttons)
            $(document).on('click', '.detail-btn', function(e) {
                e.preventDefault();
                const studentCode = $(this).data('student-code');
                if (studentCode) {
                    window.openHistoryModal(studentCode);
                }
            });

            // Class filter
            document.getElementById('classFilter').addEventListener('change', function() {
                loadStudents(this.value);
            });

            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                if (studentsTable) {
                    studentsTable.search(this.value).draw();
                }
            });

            // Export to Excel with sheets by class
            document.getElementById('exportBtn').addEventListener('click', async function() {
                try {
                    const response = await fetch('api/get_students.php');
                    const result = await response.json();

                    if (result.success && result.data.length > 0) {
                        // Load student scores with cache busting
                        let scoresResult = [];
                        try {
                            const scoresResponse = await fetch('../shared/scores/student_score.json?v=' + Date.now());
                            if (scoresResponse.ok) {
                                scoresResult = await scoresResponse.json();
                            } else {
                                console.warn('Student scores file not found, using empty array');
                            }
                        } catch (scoreError) {
                            console.warn('Error loading student scores:', scoreError);
                            scoresResult = [];
                        }

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
                            const wsData = [['STT', 'Mã học sinh', 'Họ và tên', 'Lớp', 'Điểm', 'Kiểm tra', 'Ngày']];

                            students.forEach((student, index) => {
                                // Find student's latest score for teacher's subjects
                                const studentScores = scoresResult.filter(score =>
                                    score.student_id === student.code &&
                                    (<?php echo json_encode($assignedSubjects); ?>.includes(parseInt(score.subject_id)) || score.subject_id === null || score.subject_id === "")
                                );
                                const latestScore = studentScores.length > 0 ? studentScores.reduce((latest, current) =>
                                    new Date(current.timestamp) > new Date(latest.timestamp) ? current : latest
                                ) : null;

                                wsData.push([
                                    index + 1,
                                    student.code || '',
                                    student.name || '',
                                    student.class_name || '',
                                    latestScore ? latestScore.score : '-', // Score
                                    latestScore ? latestScore.test_name : '-', // Test name
                                    latestScore ? new Date(latestScore.timestamp).toLocaleDateString('vi-VN') : '-'  // Date
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

            // Load initial data
            loadClasses().then(() => {
                loadStudents();
            });
        });
    </script>
<?php include '../includes/footer.php'; ?>
