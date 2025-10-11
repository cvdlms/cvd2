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
                    <p class="lead">Trang quản lý dành cho giáo viên.</p>
                    <a href="manage_students.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-people me-2"></i>Xem Thông Tin Học Sinh
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
