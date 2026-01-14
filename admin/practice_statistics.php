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
    <title>Thống Kê Luyện Tập - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="../styles/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Giới hạn kích thước biểu đồ tròn */
        #classChart {
            max-height: 300px;
        }
        
        /* Tăng chiều cao biểu đồ cột */
        #subjectChart {
            min-height: 300px;
        }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'practice_statistics.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0">📊 Thống Kê Luyện Tập</h2>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-4" id="summaryCards">
                            <!-- Cards will be populated by JavaScript -->
                        </div>

                        <!-- Charts Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Điểm Trung Bình Theo Môn Học</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="subjectChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Số Lần Luyện Tập Theo Lớp</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="classChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="classFilter" class="form-label">Lọc theo lớp:</label>
                                <select class="form-select" id="classFilter">
                                    <option value="">Tất cả lớp</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="subjectFilter" class="form-label">Lọc theo môn:</label>
                                <select class="form-select" id="subjectFilter">
                                    <option value="">Tất cả môn</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="dateFrom" class="form-label">Từ ngày:</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <label for="dateTo" class="form-label">Đến ngày:</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                        </div>

                        <!-- Practice Sessions Table -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Chi Tiết Các Buổi Luyện Tập</h6>
                            </div>
                            <div class="card-body">
                                <table id="practiceTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Thời Gian</th>
                                            <th>Mã HS</th>
                                            <th>Tên Học Sinh</th>
                                            <th>Lớp</th>
                                            <th>Môn Học</th>
                                            <th>Chủ Đề</th>
                                            <th>Bài Học</th>
                                            <th>Tổng Câu</th>
                                            <th>Đúng</th>
                                            <th>Sai</th>
                                            <th>Điểm (%)</th>
                                            <th>Chi Tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>

    <!-- Practice Details Modal -->
    <div class="modal fade" id="practiceDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Buổi Luyện Tập</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="practiceDetailsContent">
                        <!-- Details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let practiceTable;
        let practiceData = [];
        let classesData = [];
        let subjectsData = [];
        let subjectChart, classChart;

        // Load initial data
        async function loadData() {
            try {
                // Load classes
                const classesResponse = await fetch('api/get_classes.php');
                const classesResult = await classesResponse.json();
                if (classesResult.success) {
                    classesData = classesResult.data;
                    populateClassFilter();
                }

                // Load subjects
                const subjectsResponse = await fetch('api/get_subjects.php');
                const subjectsResult = await subjectsResponse.json();
                if (subjectsResult.success) {
                    subjectsData = subjectsResult.data;
                    populateSubjectFilter();
                }

                // Load practice data
                await loadPracticeData();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        function populateClassFilter() {
            const classFilter = document.getElementById('classFilter');
            classFilter.innerHTML = '<option value="">Tất cả lớp</option>';
            classesData.forEach(classItem => {
                classFilter.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
            });
        }

        function populateSubjectFilter() {
            const subjectFilter = document.getElementById('subjectFilter');
            subjectFilter.innerHTML = '<option value="">Tất cả môn</option>';
            subjectsData.forEach(subject => {
                subjectFilter.innerHTML += `<option value="subject_${subject.id}">${subject.name}</option>`;
            });
        }

        async function loadPracticeData() {
            try {
                const response = await fetch('../shared/practices/student_practice.json');
                if (response.ok) {
                    practiceData = await response.json();
                    updateSummaryCards();
                    updateCharts();
                    populateTable();
                } else {
                    console.log('No practice data found');
                    practiceData = [];
                }
            } catch (error) {
                console.error('Error loading practice data:', error);
                practiceData = [];
            }
        }

        function updateSummaryCards() {
            const summaryCards = document.getElementById('summaryCards');
            const totalSessions = practiceData.length;
            const uniqueStudents = new Set(practiceData.map(p => p.student_id)).size;
            const avgScore = practiceData.length > 0 ?
                Math.round(practiceData.reduce((sum, p) => sum + p.score_percentage, 0) / practiceData.length) : 0;

            summaryCards.innerHTML = `
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">${totalSessions}</h5>
                            <p class="card-text">Tổng buổi luyện tập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">${uniqueStudents}</h5>
                            <p class="card-text">Học sinh tham gia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">${avgScore}%</h5>
                            <p class="card-text">Điểm trung bình</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">${Math.round(totalSessions / Math.max(uniqueStudents, 1))}</h5>
                            <p class="card-text">Buổi luyện tập/học sinh</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateCharts() {
            // Subject performance chart
            const subjectStats = {};
            practiceData.forEach(session => {
                const subject = session.subject;
                if (!subjectStats[subject]) {
                    subjectStats[subject] = { total: 0, count: 0 };
                }
                subjectStats[subject].total += session.score_percentage;
                subjectStats[subject].count++;
            });

            const subjectLabels = Object.keys(subjectStats).map(subjectId => {
                const subject = subjectsData.find(s => `subject_${s.id}` === subjectId);
                return subject ? subject.name : subjectId;
            });
            const subjectScores = Object.values(subjectStats).map(stat => Math.round(stat.total / stat.count));

            if (subjectChart) subjectChart.destroy();
            subjectChart = new Chart(document.getElementById('subjectChart'), {
                type: 'bar',
                data: {
                    labels: subjectLabels,
                    datasets: [{
                        label: 'Điểm Trung Bình (%)',
                        data: subjectScores,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Class participation chart
            const classStats = {};
            practiceData.forEach(session => {
                const classId = session.class_code;
                if (!classStats[classId]) {
                    classStats[classId] = 0;
                }
                classStats[classId]++;
            });

            const classLabels = Object.keys(classStats).map(classCode => {
                const classInfo = classesData.find(c => c.code === classCode);
                return classInfo ? classInfo.name : classCode;
            });
            const classCounts = Object.values(classStats);

            if (classChart) classChart.destroy();
            classChart = new Chart(document.getElementById('classChart'), {
                type: 'pie',
                data: {
                    labels: classLabels,
                    datasets: [{
                        data: classCounts,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 205, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }

        function populateTable() {
            if (practiceTable) {
                practiceTable.destroy();
            }

            practiceTable = $('#practiceTable').DataTable({
                data: practiceData,
                columns: [
                    {
                        data: 'timestamp',
                        render: function(data) {
                            const date = new Date(data);
                            return date.toLocaleString('vi-VN');
                        }
                    },
                    { data: 'student_id' },
                    { data: 'student_name' },
                    {
                        data: 'class_code',
                        render: function(data) {
                            const classInfo = classesData.find(c => c.code === data);
                            return classInfo ? classInfo.name : data;
                        }
                    },
                    {
                        data: 'subject',
                        render: function(data) {
                            const subject = subjectsData.find(s => `subject_${s.id}` === data);
                            return subject ? subject.name : data;
                        }
                    },
                    { data: 'topic' },
                    { data: 'lesson' },
                    { data: 'total_questions' },
                    { data: 'correct_answers' },
                    { data: 'incorrect_answers' },
                    {
                        data: 'score_percentage',
                        render: function(data) {
                            return `${data}%`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-info" onclick="showPracticeDetails('${row.student_id}', '${row.timestamp}')">👁️ Xem</button>`;
                        },
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']] // Sort by timestamp descending
            });
        }

        function showPracticeDetails(studentId, timestamp) {
            // Find the detailed practice data
            fetch(`../shared/practices/${studentId}_practice.json`)
                .then(response => response.json())
                .then(data => {
                    const session = data.find(s => s.timestamp === timestamp);
                    if (session) {
                        let detailsHtml = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Học sinh:</strong> ${session.student_name} (${session.student_id})<br>
                                    <strong>Lớp:</strong> ${session.class_code}<br>
                                    <strong>Môn học:</strong> ${getSubjectName(session.subject)}<br>
                                    <strong>Chủ đề:</strong> ${session.topic || 'Tất cả'}<br>
                                    <strong>Bài học:</strong> ${session.lesson || 'Tất cả'}
                                </div>
                                <div class="col-md-6">
                                    <strong>Thời gian:</strong> ${new Date(session.timestamp).toLocaleString('vi-VN')}<br>
                                    <strong>Tổng câu hỏi:</strong> ${session.total_questions}<br>
                                    <strong>Đúng:</strong> ${session.correct_answers}<br>
                                    <strong>Sai:</strong> ${session.incorrect_answers}<br>
                                    <strong>Điểm:</strong> ${session.score_percentage}%
                                </div>
                            </div>
                            <h6>Chi tiết từng câu:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Câu</th>
                                            <th>Nội dung</th>
                                            <th>Đáp án HS</th>
                                            <th>Đáp án đúng</th>
                                            <th>Kết quả</th>
                                            <th>Chủ đề</th>
                                            <th>Bài học</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        session.question_results.forEach((result, index) => {
                            const status = result.is_correct ? '<span class="badge bg-success">Đúng</span>' : '<span class="badge bg-danger">Sai</span>';
                            detailsHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${result.question.substring(0, 50)}...</td>
                                    <td>${formatAnswer(result.user_answer, result.type)}</td>
                                    <td>${formatAnswer(result.correct_answer, result.type)}</td>
                                    <td>${status}</td>
                                    <td>${result.topic}</td>
                                    <td>${result.lesson}</td>
                                </tr>
                            `;
                        });

                        detailsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        document.getElementById('practiceDetailsContent').innerHTML = detailsHtml;
                        new bootstrap.Modal(document.getElementById('practiceDetailsModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error loading practice details:', error);
                    alert('Không thể tải chi tiết buổi luyện tập');
                });
        }

        function getSubjectName(subjectId) {
            const subject = subjectsData.find(s => `subject_${s.id}` === subjectId);
            return subject ? subject.name : subjectId;
        }

        function formatAnswer(answer, type) {
            if (type === 'multiple') {
                if (Array.isArray(answer)) {
                    return answer.map(i => String.fromCharCode(65 + i)).join(', ');
                }
                return 'Chưa trả lời';
            } else {
                return answer !== null ? String.fromCharCode(65 + answer) : 'Chưa trả lời';
            }
        }

        // Filter functionality
        function applyFilters() {
            const classFilter = document.getElementById('classFilter').value;
            const subjectFilter = document.getElementById('subjectFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            let filteredData = practiceData;

            if (classFilter) {
                filteredData = filteredData.filter(p => {
                    const classInfo = classesData.find(c => c.id === classFilter);
                    return classInfo && p.class_code === classInfo.code;
                });
            }

            if (subjectFilter) {
                filteredData = filteredData.filter(p => p.subject === subjectFilter);
            }

            if (dateFrom) {
                const fromDate = new Date(dateFrom);
                filteredData = filteredData.filter(p => new Date(p.timestamp) >= fromDate);
            }

            if (dateTo) {
                const toDate = new Date(dateTo);
                toDate.setHours(23, 59, 59, 999);
                filteredData = filteredData.filter(p => new Date(p.timestamp) <= toDate);
            }

            // Update table with filtered data
            if (practiceTable) {
                practiceTable.clear();
                practiceTable.rows.add(filteredData);
                practiceTable.draw();
            }

            // Update summary cards with filtered data
            updateFilteredSummary(filteredData);
        }

        function updateFilteredSummary(filteredData) {
            const summaryCards = document.getElementById('summaryCards');
            const totalSessions = filteredData.length;
            const uniqueStudents = new Set(filteredData.map(p => p.student_id)).size;
            const avgScore = filteredData.length > 0 ?
                Math.round(filteredData.reduce((sum, p) => sum + p.score_percentage, 0) / filteredData.length) : 0;

            summaryCards.innerHTML = `
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">${totalSessions}</h5>
                            <p class="card-text">Buổi luyện tập (đã lọc)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">${uniqueStudents}</h5>
                            <p class="card-text">Học sinh (đã lọc)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">${avgScore}%</h5>
                            <p class="card-text">Điểm TB (đã lọc)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">${Math.round(totalSessions / Math.max(uniqueStudents, 1))}</h5>
                            <p class="card-text">Buổi/HS (đã lọc)</p>
                        </div>
                    </div>
                </div>
            `;
        }

        // Event listeners
        document.getElementById('classFilter').addEventListener('change', applyFilters);
        document.getElementById('subjectFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFrom').addEventListener('change', applyFilters);
        document.getElementById('dateTo').addEventListener('change', applyFilters);

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadData);
    </script>
</body>
</html>
