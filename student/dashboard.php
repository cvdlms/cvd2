<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'];
$studentClassCode = $_SESSION['student_class_code'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Học Sinh - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .exam-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .exam-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link active" href="dashboard.php">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">📈 Kết Quả</a>
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title">Chào mừng <?php echo htmlspecialchars($studentName); ?>!</h3>
                        <p class="card-text mb-0">Lớp: <?php echo htmlspecialchars($studentClass); ?> | Mã HS: <?php echo htmlspecialchars($studentCode); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Types -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">📝 Chọn Bài Kiểm Tra</h4>
            </div>
        </div>

        <div class="row">
            <!-- TX1 Exam -->
            <div class="col-md-6 mb-4">
                <div class="card exam-card h-100" onclick="startExam('TX1')">
                    <div class="card-body text-center">
                        <div class="exam-icon">📊</div>
                        <h5 class="card-title">Bài Kiểm Tra TX1</h5>
                        <p class="card-text">Bài kiểm tra học kỳ 1 - Tin học</p>
                        <div class="mt-3">
                            <span class="badge bg-primary">45 phút</span>
                            <span class="badge bg-info ms-2">40 câu</span>
                        </div>
                        <button class="btn btn-primary mt-3" onclick="startExam('TX1')">
                            🚀 Bắt Đầu Thi TX1
                        </button>
                    </div>
                </div>
            </div>

            <!-- TX2 Exam -->
            <div class="col-md-6 mb-4">
                <div class="card exam-card h-100" onclick="startExam('TX2')">
                    <div class="card-body text-center">
                        <div class="exam-icon">📈</div>
                        <h5 class="card-title">Bài Kiểm Tra TX2</h5>
                        <p class="card-text">Bài kiểm tra học kỳ 2 - Tin học</p>
                        <div class="mt-3">
                            <span class="badge bg-success">45 phút</span>
                            <span class="badge bg-warning ms-2">40 câu</span>
                        </div>
                        <button class="btn btn-success mt-3" onclick="startExam('TX2')">
                            🚀 Bắt Đầu Thi TX2
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Results -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">📊 Kết Quả Gần Đây</h4>
                <div class="card">
                    <div class="card-body">
                        <div id="recentResults">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải kết quả...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Start Confirmation Modal -->
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác Nhận Bắt Đầu Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ Lưu ý quan trọng:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Bài thi sẽ bắt đầu ngay khi bạn nhấn "Bắt Đầu"</li>
                            <li>Thời gian làm bài là 45 phút</li>
                            <li>Không được phép rời khỏi trang trong khi thi</li>
                            <li>Kết quả sẽ được lưu tự động khi hết thời gian</li>
                        </ul>
                    </div>
                    <p class="mb-0">Bạn có chắc muốn bắt đầu bài thi <strong id="examTypeText"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="confirmStartBtn">Bắt Đầu</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedExamType = '';

        function startExam(examType) {
            selectedExamType = examType;
            document.getElementById('examTypeText').textContent = examType;
            new bootstrap.Modal(document.getElementById('examModal')).show();
        }

        document.getElementById('confirmStartBtn').addEventListener('click', function() {
            // Check if student has already taken this exam
            fetch(`api/check_attempts.php?exam_type=${selectedExamType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.can_take) {
                        window.location.href = `exam.php?type=${selectedExamType}`;
                    } else {
                        alert(`Bạn đã thi ${selectedExamType} ${data.attempts}/3 lần. ${data.message}`);
                        bootstrap.Modal.getInstance(document.getElementById('examModal')).hide();
                    }
                })
                .catch(error => {
                    console.error('Error checking attempts:', error);
                    alert('Lỗi kiểm tra số lần thi. Vui lòng thử lại.');
                });
        });

        // Load recent results
        async function loadRecentResults() {
            try {
                const response = await fetch('api/get_student_results.php');
                const data = await response.json();

                const resultsDiv = document.getElementById('recentResults');

                if (data.success && data.results.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>Loại Thi</th><th>Điểm</th><th>Lần Thi</th><th>Thời Gian</th><th>Trạng Thái</th></tr></thead>';
                    html += '<tbody>';

                    data.results.slice(0, 5).forEach(result => {
                        const date = new Date(result.timestamp).toLocaleString('vi-VN');
                        const status = result.completed ? 'Hoàn thành' : 'Chưa hoàn thành';
                        const statusClass = result.completed ? 'success' : 'warning';

                        html += `<tr>
                            <td>${result.exam_type}</td>
                            <td><strong>${result.score !== null ? result.score : '-'}</strong></td>
                            <td>${result.attempt}</td>
                            <td>${date}</td>
                            <td><span class="badge bg-${statusClass}">${status}</span></td>
                        </tr>`;
                    });

                    html += '</tbody></table></div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<div class="text-center text-muted py-4">Chưa có kết quả thi nào.</div>';
                }
            } catch (error) {
                console.error('Error loading results:', error);
                document.getElementById('recentResults').innerHTML =
                    '<div class="text-center text-muted py-4">Không thể tải kết quả. Vui lòng thử lại sau.</div>';
            }
        }

        // Load results on page load
        document.addEventListener('DOMContentLoaded', loadRecentResults);
    </script>
</body>
</html>
