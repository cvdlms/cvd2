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
                        <h2 class="card-title mb-0">👨‍🎓 Kết quả kiểm tra</h2>
                        <div>
                            <button type="button" class="btn btn-success me-2" id="initBtn">
                                🔄 Khởi tạo dữ liệu
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

                        <!-- Students Table -->
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped table-hover" style="width:100%">
                                <thead class="table-primary">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã học sinh</th>
                                        <th>Họ và tên</th>
                                        <th>Lớp</th>
                                        <th>Điểm</th>
                                        <th>Kiểm tra</th>
                                        <th>Ngày</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>




    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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

        // Load students table from student_score.json
        async function loadStudents(classFilter = '') {
            try {
                const response = await fetch('../shared/student_score.json');
                const scores = await response.json();

                if (scores) {
                    if (studentsTable) {
                        studentsTable.destroy();
                    }

                    // Filter by class if selected
                    let filteredScores = scores;
                    if (classFilter !== '') {
                        filteredScores = scores.filter(score => score.class_code === classFilter);
                    }

                    // Transform data for display
                    const tableData = filteredScores.map((score, index) => ({
                        stt: index + 1,
                        student_code: score.student_code,
                        student_name: score.student_name,
                        class_code: score.class_code,
                        score: score.score || '-',
                        test_name: score.test_name,
                        timestamp: score.timestamp
                    }));

                    studentsTable = $('#studentsTable').DataTable({
                        data: tableData,
                        columns: [
                            { data: 'stt' },
                            { data: 'student_code' },
                            { data: 'student_name' },
                            { data: 'class_code' },
                            { data: 'score' },
                            { data: 'test_name' },
                            { data: 'timestamp' }
                        ],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 25,
                        order: [[2, 'asc'], [3, 'asc']]  // Sort by class, then name
                    });
                } else {
                    console.error('Failed to load scores');
                    $('#studentsTable tbody').empty();
                }
            } catch (error) {
                console.error('Error loading scores:', error);
                $('#studentsTable tbody').empty();
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

        // Initialize data from scores.json
        document.getElementById('initBtn').addEventListener('click', async function() {
            if (confirm('Bạn có chắc muốn khởi tạo dữ liệu từ scores.json?')) {
                try {
                    const response = await fetch('../shared/api/scores.php');
                    const scores = await response.json();

                    if (scores) {
                        // Group scores by student_code and test_name
                        const groupedScores = {};
                        scores.forEach(score => {
                            const key = score.student_code + '_' + score.test_name;
                            if (!groupedScores[key]) {
                                groupedScores[key] = {
                                    student_code: score.student_code,
                                    student_name: score.student_name,
                                    class_code: score.class_code,
                                    test_name: score.test_name,
                                    scores: [],
                                    timestamps: []
                                };
                            }
                            groupedScores[key].scores.push(score.score);
                            groupedScores[key].timestamps.push(score.timestamp);
                        });

                        // Create student_score.json with individual attempts
                        const studentScores = [];
                        Object.values(groupedScores).forEach(group => {
                            const entry = {
                                student_code: group.student_code,
                                student_name: group.student_name,
                                class_code: group.class_code,
                                test_name: group.test_name,
                                attempts: group.scores.length,
                                timestamp: Math.max(...group.timestamps.map(t => new Date(t).getTime()))
                            };

                            // Add score_1 and score_2
                            entry.score_1 = group.scores.length >= 1 ? group.scores[0] : null;
                            entry.score_2 = group.scores.length >= 2 ? group.scores[1] : null;

                            // Calculate average score
                            const validScores = [entry.score_1, entry.score_2].filter(s => s !== null);
                            entry.score = validScores.length > 0 ? validScores.reduce((a, b) => a + b, 0) / validScores.length : null;

                            // Convert timestamp to readable format
                            entry.timestamp = new Date(entry.timestamp).toISOString().slice(0, 19).replace('T', ' ');

                            studentScores.push(entry);
                        });

                        // Save to student_score.json
                        const saveResponse = await fetch('api/save_student_scores.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(studentScores)
                        });

                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            alert('Khởi tạo dữ liệu thành công!');
                            loadStudents(); // Reload the table
                        } else {
                            alert('Lỗi khi lưu dữ liệu: ' + saveResult.message);
                        }
                    } else {
                        alert('Không thể tải dữ liệu từ scores.json');
                    }
                } catch (error) {
                    console.error('Error initializing data:', error);
                    alert('Lỗi khi khởi tạo dữ liệu: ' + error.message);
                }
            }
        });

        // Export to Excel
        document.getElementById('exportBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('../shared/student_score.json');
                const scores = await response.json();

                if (scores) {
                    const wsData = [['STT', 'Mã học sinh', 'Lớp', 'Họ và tên', 'Điểm lần 1', 'Điểm lần 2', 'Số lần', 'Kiểm tra', 'Ngày']];
                    scores.forEach((score, index) => {
                        wsData.push([
                            index + 1,
                            score.student_code || '',
                            score.class_code || '',
                            score.student_name || '',
                            score.score_1 || '',
                            score.score_2 || '',
                            score.attempts || 0,
                            score.test_name || '',
                            score.timestamp || ''
                        ]);
                    });

                    const ws = XLSX.utils.aoa_to_sheet(wsData);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'KetQua');
                    XLSX.writeFile(wb, 'KetQuaKiemTra.xlsx');
                } else {
                    alert('Không có dữ liệu để xuất.');
                }
            } catch (error) {
                console.error('Error exporting:', error);
                alert('Lỗi khi xuất dữ liệu.');
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
