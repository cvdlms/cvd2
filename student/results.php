<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thi - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .score-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
        .exam-card {
            transition: transform 0.2s;
        }
        .exam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🏫 CVD - Học Sinh</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="results.php">📈 Kết Quả</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            👤 <?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">👤 Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">🚪 Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="totalExams">-</h3>
                        <p class="mb-0">Tổng bài thi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="averageScore">-</h3>
                        <p class="mb-0">Điểm trung bình</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="highestScore">-</h3>
                        <p class="mb-0">Điểm cao nhất</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="passRate">-</h3>
                        <p class="mb-0">Tỷ lệ đỗ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📊 Lịch Sử Bài Thi</h5>
            </div>
            <div class="card-body">
                <table id="resultsTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Loại Thi</th>
                            <th>Lần Thi</th>
                            <th>Điểm</th>
                            <th>Xếp Loại</th>
                            <th>Thời Gian</th>
                            <th>Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Exam Detail Modal -->
    <div class="modal fade" id="examDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="examDetailContent">
                        <!-- Exam details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn-primary" onclick="printExamDetail()">In Chi Tiết</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let resultsTable;
        let allResults = [];

        // Load student results
        async function loadResults() {
            try {
                const response = await fetch('api/get_student_results.php');
                const data = await response.json();

                if (data.success) {
                    allResults = data.results;
                    displayStatistics();
                    displayResultsTable();
                } else {
                    document.querySelector('#resultsTable tbody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-muted">Chưa có kết quả thi nào.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading results:', error);
                alert('Lỗi tải kết quả: ' + error.message);
            }
        }

        // Display statistics
        function displayStatistics() {
            const totalExams = allResults.length;

            if (totalExams === 0) {
                document.getElementById('totalExams').textContent = '0';
                document.getElementById('averageScore').textContent = '-';
                document.getElementById('highestScore').textContent = '-';
                document.getElementById('passRate').textContent = '-';
                return;
            }

            // Calculate statistics
            let totalScore = 0;
            let highestScore = 0;
            let passedExams = 0;

            allResults.forEach(result => {
                if (result.score !== null) {
                    totalScore += result.score;
                    if (result.score > highestScore) highestScore = result.score;
                    if (result.score >= 5.0) passedExams++;
                }
            });

            const averageScore = (totalScore / totalExams).toFixed(1);
            const passRate = ((passedExams / totalExams) * 100).toFixed(1) + '%';

            document.getElementById('totalExams').textContent = totalExams;
            document.getElementById('averageScore').textContent = averageScore;
            document.getElementById('highestScore').textContent = highestScore.toFixed(1);
            document.getElementById('passRate').textContent = passRate;
        }

        // Display results table
        function displayResultsTable() {
            if (resultsTable) {
                resultsTable.destroy();
            }

            resultsTable = $('#resultsTable').DataTable({
                data: allResults,
                columns: [
                    { data: 'exam_type' },
                    { data: 'attempt' },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="text-muted">Chưa hoàn thành</span>';
                            return `<strong>${data}</strong>`;
                        }
                    },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="badge bg-secondary">Chưa hoàn thành</span>';

                            let grade = 'F';
                            let badgeClass = 'bg-danger';

                            if (data >= 9.0) { grade = 'A+'; badgeClass = 'bg-success'; }
                            else if (data >= 8.5) { grade = 'A'; badgeClass = 'bg-success'; }
                            else if (data >= 8.0) { grade = 'B+'; badgeClass = 'bg-info'; }
                            else if (data >= 7.0) { grade = 'B'; badgeClass = 'bg-info'; }
                            else if (data >= 6.5) { grade = 'C+'; badgeClass = 'bg-warning'; }
                            else if (data >= 6.0) { grade = 'C'; badgeClass = 'bg-warning'; }
                            else if (data >= 5.5) { grade = 'D+'; badgeClass = 'bg-warning'; }
                            else if (data >= 5.0) { grade = 'D'; badgeClass = 'bg-warning'; }

                            return `<span class="badge ${badgeClass} score-badge">${grade}</span>`;
                        }
                    },
                    {
                        data: 'timestamp',
                        render: function(data) {
                            return new Date(data).toLocaleString('vi-VN');
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            if (!data.completed) {
                                return '<span class="text-muted">Chưa hoàn thành</span>';
                            }
                            return `<button class="btn btn-sm btn-info" onclick="viewExamDetail('${data.id}')">👁️ Xem</button>`;
                        },
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                order: [[4, 'desc']], // Sort by timestamp descending
                pageLength: 10
            });
        }

        // View exam detail
        async function viewExamDetail(examId) {
            try {
                const response = await fetch(`api/get_exam_result.php?exam_id=${examId}`);
                const data = await response.json();

                if (data.success) {
                    const result = data.result;
                    const modal = new bootstrap.Modal(document.getElementById('examDetailModal'));
                    const content = document.getElementById('examDetailContent');

                    content.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Loại thi:</strong> ${result.exam_type}
                            </div>
                            <div class="col-md-6">
                                <strong>Lần thi:</strong> ${result.attempt}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Điểm số:</strong> <span class="h4 text-primary">${result.score}/10</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Số câu đúng:</strong> ${result.correct_answers}/${result.total_questions}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Thời gian:</strong> ${new Date(result.timestamp).toLocaleString('vi-VN')}
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong> <span class="badge bg-success">Hoàn thành</span>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Chi Tiết Bài Làm</h5>
                        <div class="accordion" id="questionsAccordion">
                            ${result.question_results.map((q, index) => `
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ${q.is_correct ? '' : 'bg-danger text-white'}" type="button" data-bs-toggle="collapse" data-bs-target="#question${index}">
                                            Câu ${index + 1}: ${q.is_correct ? '✅ Đúng' : '❌ Sai'}
                                        </button>
                                    </h2>
                                    <div id="question${index}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Câu hỏi:</strong> ${q.question}</p>
                                            <p><strong>Đáp án đúng:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.correct_answer)
                                                    : q.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>
                                            ${q.user_answer !== null ? `<p><strong>Đáp án của bạn:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.user_answer)
                                                    : q.user_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>` : '<p><strong>Đáp án của bạn:</strong> <em>Chưa trả lời</em></p>'}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    modal.show();
                } else {
                    alert('Không thể tải chi tiết bài thi');
                }
            } catch (error) {
                console.error('Error loading exam detail:', error);
                alert('Lỗi tải chi tiết bài thi: ' + error.message);
            }
        }

        // Print exam detail
        function printExamDetail() {
            const content = document.getElementById('examDetailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chi Tiết Bài Thi</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .accordion-item { margin-bottom: 10px; border: 1px solid #ddd; }
                        .accordion-button { background: #f8f9fa; border: none; padding: 10px; width: 100%; text-align: left; }
                        .accordion-body { padding: 10px; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .bg-success { background: #28a745; color: white; }
                        .text-primary { color: #007bff; }
                        .h4 { font-size: 1.5rem; font-weight: bold; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Load results on page load
        document.addEventListener('DOMContentLoaded', loadResults);
    </script>
</body>
</html>
