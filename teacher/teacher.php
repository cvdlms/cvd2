<?php
session_start();
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

$title = 'Trang Chủ Giáo Viên - CVD';
include '../includes/teacher_header.php';
?>
    <div class="main-content">
        <div class="container my-5">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="mb-4">Chào mừng, <?php echo htmlspecialchars($fullname); ?>!</h1>
                    <p class="lead mb-5">Trang quản lý dành cho giáo viên. Chọn chức năng bạn muốn sử dụng.</p>
                </div>
            </div>
            <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-md-3">
                <!-- Manage Students -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-people display-4 text-primary mb-3"></i>
                            <h5 class="card-title">Quản Lý Học Sinh</h5>
                            <p class="card-text">Xem thông tin chi tiết của học sinh trong các lớp được giao.</p>
                            <a href="manage_students.php" class="btn btn-primary">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Create Exams -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-clipboard-check display-4 text-success mb-3"></i>
                            <h5 class="card-title">Tạo Bài Kiểm Tra</h5>
                            <p class="card-text">Tạo đề thi thủ công hoặc tự động từ ngân hàng câu hỏi.</p>
                            <a href="exam_creation.php" class="btn btn-success">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Manage Questions -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-question-circle display-4 text-warning mb-3"></i>
                            <h5 class="card-title">Ngân Hàng Câu Hỏi</h5>
                            <p class="card-text">Thêm, sửa, xóa và nhập câu hỏi cho các môn học.</p>
                            <a href="question_bank.php" class="btn btn-warning">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Matrix -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-table display-4 text-info mb-3"></i>
                            <h5 class="card-title">Ma Trận Đề Kiểm Tra</h5>
                            <p class="card-text">Xem ma trận đề kiểm tra với tỉ trọng điểm và số câu hỏi.</p>
                            <a href="matrix.php" class="btn btn-info">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Matrix Builder -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-tools display-4 text-secondary mb-3"></i>
                            <h5 class="card-title">Xây Dựng Ma Trận</h5>
                            <p class="card-text">Tạo ma trận đề kiểm tra tùy chỉnh dựa trên chủ đề và đơn vị kiến thức.</p>
                            <a href="matrix_builder.php" class="btn btn-secondary">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- View Results -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-bar-chart display-4 text-success mb-3"></i>
                            <h5 class="card-title">Xem Kết Quả</h5>
                            <p class="card-text">Xem và xuất kết quả kiểm tra của học sinh.</p>
                            <a href="manage_result.php" class="btn btn-success">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Practice -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-book display-4 text-secondary mb-3"></i>
                            <h5 class="card-title">Luyện Tập</h5>
                            <p class="card-text">Truy cập chế độ luyện tập để ôn tập kiến thức.</p>
                            <a href="practice.php" class="btn btn-secondary">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Excel Comments -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-spreadsheet display-4 text-info mb-3"></i>
                            <h5 class="card-title">Nhận Xét Excel</h5>
                            <p class="card-text">Upload file Excel để tự động tạo nhận xét học sinh dựa trên điểm số.</p>
                            <a href="excel_comments.php" class="btn btn-info">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Lucky Wheel -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-repeat display-4 text-danger mb-3"></i>
                            <h5 class="card-title">Vòng Quay May Mắn</h5>
                            <p class="card-text">Quay vòng quay để chọn ngẫu nhiên học sinh trong lớp.</p>
                            <a href="lucky_wheel.php" class="btn btn-danger">Truy Cập</a>
                        </div>
                    </div>
                </div>
                <!-- Remote Control -->
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-phone display-4 text-primary mb-3"></i>
                            <h5 class="card-title">Điều Khiển Từ Xa</h5>
                            <p class="card-text">Điều khiển máy tính từ điện thoại để quản lý bài giảng và kiểm tra.</p>
                            <a href="remote_control.php" class="btn btn-primary">Truy Cập</a>
                        </div>
                    </div>
                </div>


        </div>
    </div>

    <script>
        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Disable F12, Ctrl+Shift+I (Dev Tools), Ctrl+U (View Source)
        document.addEventListener('keydown', function(e) {
            if (e.keyCode === 123) { // F12
                e.preventDefault();
            }
            if (e.ctrlKey && e.shiftKey && e.keyCode === 73) { // Ctrl+Shift+I
                e.preventDefault();
            }
            if (e.ctrlKey && e.keyCode === 85) { // Ctrl+U
                e.preventDefault();
            }
        });
    </script>

    <?php include '../includes/footer.php'; ?>
