<?php
session_name('CVD_TEACHER_SESSION');
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
    <title>Thống Kê Kỳ Thi - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="../styles/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.primary { border-color: #0d6efd; }
        .stats-card.success { border-color: #198754; }
        .stats-card.info { border-color: #0dcaf0; }
        .stats-card.warning { border-color: #ffc107; }
        
        /* Giới hạn kích thước biểu đồ tròn */
        #scoreDistributionChart {
            max-height: 300px;
        }
        
        /* Tăng chiều cao biểu đồ cột */
        #subjectChart {
            min-height: 300px;
        }
        
        #examTable th:nth-child(1),
        #examTable td:nth-child(1) { width: 12%; }
        #examTable th:nth-child(2),
        #examTable td:nth-child(2) { width: 15%; }
        #examTable th:nth-child(3),
        #examTable td:nth-child(3) { width: 10%; }
        #examTable th:nth-child(4),
        #examTable td:nth-child(4) { width: 20%; }
        #examTable th:nth-child(5),
        #examTable td:nth-child(5) { width: 12%; }
        #examTable th:nth-child(6),
        #examTable td:nth-child(6) { width: 10%; }
        #examTable th:nth-child(7),
        #examTable td:nth-child(7) { width: 15%; }
        #examTable th:nth-child(8),
        #examTable td:nth-child(8) { width: 6%; }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'exam_statistics.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0">📝 Thống Kê Kỳ Thi</h2>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-4" id="summaryCards">
                            <div class="col-md-3">
                                <div class="card stats-card primary text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary" id="totalExams">0</h5>
                                        <p class="card-text mb-0">Tổng kỳ thi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card success text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-success" id="totalSubmissions">0</h5>
                                        <p class="card-text mb-0">Lượt thi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card info text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-info" id="avgScore">0%</h5>
                                        <p class="card-text mb-0">Điểm trung bình</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card warning text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning" id="uniqueStudents">0</h5>
                                        <p class="card-text mb-0">Học sinh tham gia</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">📊 Điểm Trung Bình Theo Môn Học</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="subjectChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">🎓 Phân Bố Điểm Số</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="scoreDistributionChart"></canvas>
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

                        <!-- Exam Results Table -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">📋 Chi Tiết Kết Quả Thi</h6>
                            </div>
                            <div class="card-body">
                                <table id="examTable" class="table table-striped table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Mã HS</th>
                                            <th>Tên Học Sinh</th>
                                            <th>Lớp</th>
                                            <th>Bài Thi</th>
                                            <th>Môn Học</th>
                                            <th>Khối</th>
                                            <th>Ngày Thi</th>
                                            <th>Điểm</th>
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let examTable;
        let resultsData = [];
        let classesData = [];
        let subjectsData = [];
        let studentsData = [];
        let subjectChart, scoreDistributionChart;

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

                // Load students
                const studentsResponse = await fetch('api/get_students.php');
                const studentsResult = await studentsResponse.json();
                if (studentsResult.success) {
                    studentsData = studentsResult.data;
                }

                // Load exam results
                await loadExamResults();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        function populateClassFilter() {
            const classFilter = document.getElementById('classFilter');
            classFilter.innerHTML = '<option value="">Tất cả lớp</option>';
            classesData.forEach(classItem => {
                classFilter.innerHTML += `<option value="${classItem.code}">${classItem.name}</option>`;
            });
        }

        function populateSubjectFilter() {
            const subjectFilter = document.getElementById('subjectFilter');
            subjectFilter.innerHTML = '<option value="">Tất cả môn</option>';
            subjectsData.forEach(subject => {
                subjectFilter.innerHTML += `<option value="${subject.id}">${subject.name}</option>`;
            });
        }

        async function loadExamResults() {
            try {
                const response = await fetch('api/get_all_results.php');
                if (response.ok) {
                    const data = await response.json();
                    resultsData = data || [];
                    updateSummaryCards();
                    updateCharts();
                    populateTable();
                } else {
                    console.log('No exam results found');
                    resultsData = [];
                }
            } catch (error) {
                console.error('Error loading exam results:', error);
                resultsData = [];
            }
        }

        function updateSummaryCards() {
            const uniqueExams = new Set(resultsData.map(r => r.exam_name)).size;
            const totalSubmissions = resultsData.length;
            const uniqueStudents = new Set(resultsData.map(r => r.student_id)).size;
            const avgScore = resultsData.length > 0 ?
                Math.round((resultsData.reduce((sum, r) => sum + (r.score || 0), 0) / resultsData.length) * 10) / 10 : 0;

            document.getElementById('totalExams').textContent = uniqueExams;
            document.getElementById('totalSubmissions').textContent = totalSubmissions;
            document.getElementById('avgScore').textContent = avgScore.toFixed(2);
            document.getElementById('uniqueStudents').textContent = uniqueStudents;
        }

        function updateCharts() {
            // Subject performance chart
            const subjectStats = {};
            resultsData.forEach(result => {
                const subjectId = result.subject_id || 'unknown';
                if (!subjectStats[subjectId]) {
                    subjectStats[subjectId] = { total: 0, count: 0 };
                }
                subjectStats[subjectId].total += result.score || 0;
                subjectStats[subjectId].count++;
            });

            const subjectLabels = Object.keys(subjectStats).map(subjectId => {
                const subject = subjectsData.find(s => s.id == subjectId);
                return subject ? subject.name : `Môn ${subjectId}`;
            });
            const subjectScores = Object.values(subjectStats).map(stat => 
                Math.round((stat.total / stat.count) * 10) / 10
            );

            if (subjectChart) subjectChart.destroy();
            const ctx1 = document.getElementById('subjectChart');
            subjectChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: subjectLabels,
                    datasets: [{
                        label: 'Điểm Trung Bình',
                        data: subjectScores,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Score distribution chart
            const scoreRanges = {
                '0-2': 0,
                '2-4': 0,
                '4-6': 0,
                '6-8': 0,
                '8-10': 0
            };

            resultsData.forEach(result => {
                const score = result.score || 0;
                if (score < 2) scoreRanges['0-2']++;
                else if (score < 4) scoreRanges['2-4']++;
                else if (score < 6) scoreRanges['4-6']++;
                else if (score < 8) scoreRanges['6-8']++;
                else scoreRanges['8-10']++;
            });

            if (scoreDistributionChart) scoreDistributionChart.destroy();
            const ctx2 = document.getElementById('scoreDistributionChart');
            scoreDistributionChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(scoreRanges),
                    datasets: [{
                        data: Object.values(scoreRanges),
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.6)',
                            'rgba(255, 193, 7, 0.6)',
                            'rgba(13, 202, 240, 0.6)',
                            'rgba(25, 135, 84, 0.6)',
                            'rgba(32, 201, 151, 0.6)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function populateTable() {
            if (examTable) {
                examTable.destroy();
            }

            const tableData = resultsData.map(result => {
                const student = studentsData.find(s => s.code === result.student_id);
                const classInfo = classesData.find(c => c.code === (student?.class_code || ''));
                const subject = subjectsData.find(s => s.id == result.subject_id);
                
                return {
                    student_id: result.student_id,
                    student_name: result.student_name || student?.name || 'N/A',
                    class_name: classInfo?.name || student?.class_code || 'N/A',
                    class_code: student?.class_code || '',
                    exam_name: result.exam_name,
                    subject_name: subject?.name || 'N/A',
                    subject_id: result.subject_id,
                    grade: result.grade || 'N/A',
                    submitted_at: result.submitted_at,
                    score: result.score || 0
                };
            });

            examTable = $('#examTable').DataTable({
                data: tableData,
                columns: [
                    { data: 'student_id' },
                    { data: 'student_name' },
                    { data: 'class_name' },
                    { data: 'exam_name' },
                    { data: 'subject_name' },
                    { data: 'grade' },
                    {
                        data: 'submitted_at',
                        render: function(data) {
                            if (!data) return 'N/A';
                            const date = new Date(data);
                            return date.toLocaleString('vi-VN');
                        }
                    },
                    {
                        data: 'score',
                        render: function(data) {
                            const score = parseFloat(data) || 0;
                            let colorClass = 'text-danger';
                            if (score >= 8) colorClass = 'text-success';
                            else if (score >= 6.5) colorClass = 'text-info';
                            else if (score >= 5) colorClass = 'text-warning';
                            
                            return `<strong class="${colorClass}">${score.toFixed(2)}</strong>`;
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[6, 'desc']], // Sort by date descending
                autoWidth: false
            });
        }

        // Filter functionality
        function applyFilters() {
            const classFilter = document.getElementById('classFilter').value;
            const subjectFilter = document.getElementById('subjectFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            let filteredData = resultsData;

            if (classFilter) {
                filteredData = filteredData.filter(r => {
                    const student = studentsData.find(s => s.code === r.student_id);
                    return student && student.class_code === classFilter;
                });
            }

            if (subjectFilter) {
                filteredData = filteredData.filter(r => r.subject_id == subjectFilter);
            }

            if (dateFrom) {
                const fromDate = new Date(dateFrom);
                filteredData = filteredData.filter(r => {
                    if (!r.submitted_at) return false;
                    return new Date(r.submitted_at) >= fromDate;
                });
            }

            if (dateTo) {
                const toDate = new Date(dateTo);
                toDate.setHours(23, 59, 59, 999);
                filteredData = filteredData.filter(r => {
                    if (!r.submitted_at) return false;
                    return new Date(r.submitted_at) <= toDate;
                });
            }

            // Update table with filtered data
            if (examTable) {
                examTable.clear();
                const tableData = filteredData.map(result => {
                    const student = studentsData.find(s => s.code === result.student_id);
                    const classInfo = classesData.find(c => c.code === (student?.class_code || ''));
                    const subject = subjectsData.find(s => s.id == result.subject_id);
                    
                    return {
                        student_id: result.student_id,
                        student_name: result.student_name || student?.name || 'N/A',
                        class_name: classInfo?.name || student?.class_code || 'N/A',
                        class_code: student?.class_code || '',
                        exam_name: result.exam_name,
                        subject_name: subject?.name || 'N/A',
                        subject_id: result.subject_id,
                        grade: result.grade || 'N/A',
                        submitted_at: result.submitted_at,
                        score: result.score || 0
                    };
                });
                examTable.rows.add(tableData);
                examTable.draw();
            }

            // Update summary cards with filtered data
            updateFilteredSummary(filteredData);
        }

        function updateFilteredSummary(filteredData) {
            const uniqueExams = new Set(filteredData.map(r => r.exam_name)).size;
            const totalSubmissions = filteredData.length;
            const uniqueStudents = new Set(filteredData.map(r => r.student_id)).size;
            const avgScore = filteredData.length > 0 ?
                Math.round((filteredData.reduce((sum, r) => sum + (r.score || 0), 0) / filteredData.length) * 10) / 10 : 0;

            document.getElementById('totalExams').textContent = uniqueExams;
            document.getElementById('totalSubmissions').textContent = totalSubmissions;
            document.getElementById('avgScore').textContent = avgScore.toFixed(2);
            document.getElementById('uniqueStudents').textContent = uniqueStudents;
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
