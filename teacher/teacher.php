<?php
// Set unique session name for Teacher/Admin (must match login.php)
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php'; // Ensure logged in
include '../includes/premium_helper.php'; // Check Premium status

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Check Premium status
$isPremium = isPremiumUser($username);

// Load system config for security settings
$configFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$disableViewSource = $systemConfig['system']['disable_view_source'] ?? true;

$title = 'Trang Chủ Giáo Viên - CVD';
include '../includes/teacher_header.php';

// Load recent notifications
$notificationsFile = __DIR__ . '/../data/teacher_notifications.json';
$notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
if (!is_array($notifications)) $notifications = [];

// Filter notifications for this teacher
$recentNotifications = [];
foreach ($notifications as $notif) {
    if ($notif['teacher_username'] === $username && !($notif['is_read'] ?? false)) {
        $recentNotifications[] = $notif;
    }
}

// Sort by created_at (newest first) and get top 5
usort($recentNotifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentNotifications = array_slice($recentNotifications, 0, 5);
?>
    <div class="main-content">
        <div class="container mb-5">
            <!-- Welcome Banner -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="bg-gradient text-white shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body p-4 text-center">
                                                           
                            <h1 class="display-5 fw-bold mb-4">Xin chào, <?php echo htmlspecialchars($fullname); ?>! 👋</h1>
                            <?php if ($isPremium): ?>
                                <span class="badge bg-warning text-dark mt-2 px-3 py-2">
                                    <i class="bi bi-star-fill"></i> Tài khoản Premium
                                </span>
                            <?php else: ?>
                                <a href="premium_activation.php" class="btn btn-warning btn-sm mt-2">
                                    <i class="bi bi-lightning-charge"></i> Nâng cấp Premium
                                </a>
                            <?php endif; ?>
                             
                               
                           
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Features Section -->
            <div class="mb-5">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary rounded-3 p-2 me-3">
                        <i class="bi bi-grid-3x3-gap text-white fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">Chức Năng Chính</h3>
                        <p class="text-muted mb-0 small">Các công cụ thiết yếu cho giảng dạy và quản lý</p>
                    </div>
                </div>
                
                <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3">
                    <!-- Manage Students -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-people-fill text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Quản Lý Học Sinh</h5>
                                <p class="card-text text-muted text-center small">Theo dõi, quản lý thông tin và kết quả học tập của học sinh trong lớp</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Thêm/Sửa/Xóa học sinh</li>
                                    <li><i class="bi bi-check2 text-success"></i> Import từ Excel</li>
                                    <li><i class="bi bi-check2 text-success"></i> Quản lý lớp học</li>
                                </ul>
                                <a href="manage_students.php" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Create Exams -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-file-earmark-text-fill text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Tạo Bài Kiểm Tra</h5>
                                <p class="card-text text-muted text-center small">Tạo đề thi nhanh chóng với nhiều hình thức và cấu trúc linh hoạt</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Tạo thủ công hoặc tự động</li>
                                    <li><i class="bi bi-check2 text-success"></i> Đa dạng loại câu hỏi</li>
                                    <li><i class="bi bi-check2 text-success"></i> Ma trận đề thi chuẩn</li>
                                </ul>
                                <a href="exam_creation.php" class="btn btn-success w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question Bank -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-bank2 text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Ngân Hàng Câu Hỏi</h5>
                                <p class="card-text text-muted text-center small">Quản lý kho câu hỏi phong phú theo môn học và chương trình</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Phân loại theo môn/chương</li>
                                    <li><i class="bi bi-check2 text-success"></i> Nhập từ Excel/Word</li>
                                    <li><i class="bi bi-check2 text-success"></i> Tìm kiếm nhanh</li>
                                </ul>
                                <a href="question_bank.php" class="btn btn-warning w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-graph-up-arrow text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Kết Quả Học Tập</h5>
                                <p class="card-text text-muted text-center small">Theo dõi và phân tích kết quả bài kiểm tra của học sinh</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Thống kê chi tiết</li>
                                    <li><i class="bi bi-check2 text-success"></i> Biểu đồ trực quan</li>
                                    <li><i class="bi bi-check2 text-success"></i> Xuất Excel/PDF</li>
                                </ul>
                                <a href="manage_result.php" class="btn btn-success w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson Plans - PREMIUM -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0 <?php echo !$isPremium ? 'opacity-75' : ''; ?> position-relative" 
                             style="<?php echo $isPremium ? 'border: 2px solid #ffc107 !important;' : ''; ?>">
                            <?php if (!$isPremium): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Premium
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: <?php echo $isPremium ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'linear-gradient(135deg, #adb5bd 0%, #6c757d 100%)'; ?>; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-journal-bookmark text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Kế Hoạch Bài Dạy</h5>
                                <p class="card-text text-muted text-center small">Tạo và quản lý kế hoạch bài dạy theo chuẩn 4 hoạt động</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Form chuẩn theo quy định</li>
                                    <li><i class="bi bi-check2 text-success"></i> Chia sẻ với GV khác</li>
                                    <li><i class="bi bi-check2 text-success"></i> Xuất Word/PDF</li>
                                </ul>
                                <?php if ($isPremium): ?>
                                    <a href="lesson_plans.php" class="btn btn-primary w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                    </a>
                                <?php else: ?>
                                    <a href="premium_activation.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-lock-fill"></i> Nâng Cấp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Slide Bài Giảng -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-easel text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Slide Bài Giảng</h5>
                                <p class="card-text text-muted text-center small">Tạo và quản lý slide trình chiếu chuyên nghiệp cho bài giảng</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Editor trực quan</li>
                                    <li><i class="bi bi-check2 text-success"></i> Templates đẹp mắt</li>
                                    <li><i class="bi bi-check2 text-success"></i> Trình chiếu fullscreen</li>
                                </ul>
                                <a href="slides.php" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Manage Assignments - PREMIUM -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0 <?php echo !$isPremium ? 'opacity-75' : ''; ?> position-relative" 
                             style="<?php echo $isPremium ? 'border: 2px solid #ffc107 !important;' : ''; ?>">
                            <?php if (!$isPremium): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Premium
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: <?php echo $isPremium ? 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' : 'linear-gradient(135deg, #adb5bd 0%, #6c757d 100%)'; ?>; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-journal-text text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Quản Lý Bài Tập</h5>
                                <p class="card-text text-muted text-center small">Giao bài tập cho nhiều lớp, theo dõi tiến độ nộp bài của học sinh</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Giao cho nhiều lớp</li>
                                    <li><i class="bi bi-check2 text-success"></i> Deadline tự động</li>
                                    <li><i class="bi bi-check2 text-success"></i> Xem bài nộp</li>
                                </ul>
                                <?php if ($isPremium): ?>
                                    <a href="manage_assignments.php" class="btn btn-danger w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                    </a>
                                <?php else: ?>
                                    <a href="premium_activation.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-lock-fill"></i> Nâng Cấp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Matrix Builder - PREMIUM -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0 <?php echo !$isPremium ? 'opacity-75' : ''; ?> position-relative" 
                             style="<?php echo $isPremium ? 'border: 2px solid #ffc107 !important;' : ''; ?>">
                            <?php if (!$isPremium): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Premium
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: <?php echo $isPremium ? 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' : 'linear-gradient(135deg, #adb5bd 0%, #6c757d 100%)'; ?>; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-diagram-3-fill text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Xây Dựng Ma Trận</h5>
                                <p class="card-text text-muted text-center small">Công cụ tạo ma trận đề kiểm tra tùy chỉnh theo yêu cầu riêng</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Tự động phân bổ câu hỏi</li>
                                    <li><i class="bi bi-check2 text-success"></i> Đáp ứng chuẩn quy định</li>
                                    <li><i class="bi bi-check2 text-success"></i> Xuất file Word/PDF</li>
                                </ul>
                                <?php if ($isPremium): ?>
                                    <a href="matrix_builder.php" class="btn btn-warning w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                    </a>
                                <?php else: ?>
                                    <a href="premium_activation.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-lock-fill"></i> Nâng Cấp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Word - PREMIUM -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0 <?php echo !$isPremium ? 'opacity-75' : ''; ?> position-relative" 
                             style="<?php echo $isPremium ? 'border: 2px solid #ffc107 !important;' : ''; ?>">
                            <?php if (!$isPremium): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Premium
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="icon-box mx-auto" style="width: 80px; height: 80px; background: <?php echo $isPremium ? 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' : 'linear-gradient(135deg, #adb5bd 0%, #6c757d 100%)'; ?>; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-file-word-fill text-white" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center mb-3">Xuất Đề File Word</h5>
                                <p class="card-text text-muted text-center small">Xuất đề thi và đáp án ra file Word để in ấn chuyên nghiệp</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="bi bi-check2 text-success"></i> Format chuẩn đẹp</li>
                                    <li><i class="bi bi-check2 text-success"></i> Hỗ trợ công thức toán</li>
                                    <li><i class="bi bi-check2 text-success"></i> Xuất đáp án riêng</li>
                                </ul>
                                <?php if ($isPremium): ?>
                                    <a href="my_exams.php" class="btn btn-info w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Truy Cập
                                    </a>
                                <?php else: ?>
                                    <a href="premium_activation.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-lock-fill"></i> Nâng Cấp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tools Section -->
            <div class="mb-5">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-secondary rounded-3 p-2 me-3">
                        <i class="bi bi-tools text-white fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">Công Cụ Hỗ Trợ</h3>
                        <p class="text-muted mb-0 small">Các công cụ nâng cao giúp tiết kiệm thời gian</p>
                    </div>
                </div>
                
                <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-4">
                    <!-- Excel Comments - PREMIUM -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0 <?php echo !$isPremium ? 'opacity-75' : ''; ?> position-relative" 
                             style="<?php echo $isPremium ? 'border: 2px solid #ffc107 !important;' : ''; ?>">
                            <?php if (!$isPremium): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Premium
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-box mx-auto" style="width: 70px; height: 70px; background: <?php echo $isPremium ? 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' : 'linear-gradient(135deg, #6c757d 0%, #495057 100%)'; ?>; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-file-earmark-excel-fill text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h6 class="card-title fw-bold">Nhận Xét Vnedu</h6>
                                <p class="card-text small text-muted">Tự động tạo nhận xét học sinh từ file Excel cho hệ thống Vnedu</p>
                                <?php if ($isPremium): ?>
                                    <a href="excel_comments.php" class="btn btn-warning btn-sm w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Sử Dụng
                                    </a>
                                <?php else: ?>
                                    <a href="premium_activation.php" class="btn btn-outline-warning btn-sm w-100">
                                        <i class="bi bi-lock-fill"></i> Nâng Cấp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lucky Wheel -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-box mx-auto" style="width: 70px; height: 70px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-disc-fill text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h6 class="card-title fw-bold">Vòng Quay May Mắn</h6>
                                <p class="card-text small text-muted">Chọn ngẫu nhiên học sinh trong lớp một cách công bằng và vui nhộn</p>
                                <a href="lucky_wheel.php" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Sử Dụng
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Remote Control -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-box mx-auto" style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-broadcast text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h6 class="card-title fw-bold">Điều Khiển Từ Xa</h6>
                                <p class="card-text small text-muted">Điều khiển máy tính giảng dạy từ điện thoại hoặc thiết bị khác</p>
                                <a href="remote_control.php" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Sử Dụng
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Guide -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-box mx-auto" style="width: 70px; height: 70px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-question-circle-fill text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h6 class="card-title fw-bold">Hướng Dẫn Sử Dụng</h6>
                                <p class="card-text small text-muted">Tài liệu hướng dẫn chi tiết các chức năng và cách sử dụng hệ thống</p>
                                <a href="user_guide.php" class="btn btn-info btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Xem Hướng Dẫn
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Knowledge Assessment -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-lift border-0">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="icon-box mx-auto" style="width: 70px; height: 70px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-clipboard-data text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h6 class="card-title fw-bold">Mức Độ Đánh Giá</h6>
                                <p class="card-text small text-muted">Bản mô tả mức độ đánh giá nội dung kiến thức theo môn học</p>
                                <a href="knowledge_assessment.php" class="btn btn-warning btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle"></i> Quản Lý
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
        
        <!-- Recent Notifications Section -->
        <?php if (!empty($recentNotifications)): ?>
        <div class="container mb-5">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-bell-fill me-2"></i>Thông Báo Mới</h5>
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentNotifications as $notif): ?>
                                    <?php
                                    // Format time
                                    $createdDate = new DateTime($notif['created_at']);
                                    $now = new DateTime();
                                    $diff = $now->getTimestamp() - $createdDate->getTimestamp();
                                    
                                    if ($diff < 60) {
                                        $timeAgo = 'Vừa xong';
                                    } elseif ($diff < 3600) {
                                        $minutes = floor($diff / 60);
                                        $timeAgo = $minutes . ' phút trước';
                                    } elseif ($diff < 86400) {
                                        $hours = floor($diff / 3600);
                                        $timeAgo = $hours . ' giờ trước';
                                    } else {
                                        $days = floor($diff / 86400);
                                        $timeAgo = $days . ' ngày trước';
                                    }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-journal-check text-white"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                                    <small class="text-muted"><i class="bi bi-clock"></i> <?php echo $timeAgo; ?></small>
                                                </div>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        .card {
            border-radius: 12px;
            overflow: hidden;
        }
        .bg-gradient {
            border-radius: 15px;
        }
    </style>

    <?php if ($disableViewSource): ?>
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
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
