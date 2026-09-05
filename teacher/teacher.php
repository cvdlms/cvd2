<?php
// Set unique session name for Teacher/Admin (must match index.php)
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php'; // Ensure logged in
include '../includes/premium_helper.php'; // Check Premium status

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Check Premium status
$isPremium = isPremiumUser($username);
$isEduVnSso = !empty($_SESSION['eduvn_sso']);

// Load system config for security settings
$configFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$disableViewSource = $systemConfig['system']['disable_view_source'] ?? true;

$title = 'Bảng Điều Khiển Giáo Viên - EDUVN EXAMS';
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
    <div class="container py-4 mb-5">

        <!-- Welcome Hero Banner -->
        <div class="hero-banner p-4 p-md-5 mb-5 text-white eduvn-reveal">
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="hero-avatar">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge-glass">
                                    <i class="bi bi-person-badge-fill me-1"></i> Giáo Viên Portal
                                </span>

                                <?php if ($isEduVnSso): ?>
                                    <span class="badge-glass d-inline-flex align-items-center gap-2">
                                        <span class="pulse-dot"></span>
                                        <i class="bi bi-shield-check"></i> Đã kết nối EduVN
                                    </span>
                                <?php endif; ?>

                                <?php if ($isPremium): ?>
                                    <span class="badge-gold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-star-fill"></i> Premium Account
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h1 class="display-6 fw-800 mb-0 text-white">Xin chào, <?php echo htmlspecialchars($fullname); ?>! 👋</h1>
                        </div>
                    </div>
                    <p class="text-white-50 lead mb-0 fs-6">Chào mừng bạn quay trở lại với hệ thống quản lý học tập EDUVN EXAMS. Chúc bạn một ngày làm việc hiệu quả!</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!$isPremium): ?>
                        <a href="premium_activation.php" class="btn btn-light btn-lg px-4 py-3 rounded-4 fw-bold shadow-lg text-primary d-inline-flex align-items-center gap-2">
                            <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                            <span>Nâng Cấp Premium</span>
                        </a>
                    <?php else: ?>
                        <div class="p-3 rounded-4 d-inline-block text-start" style="background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.25);">
                            <div class="small text-white-50">Trạng thái tài khoản</div>
                            <div class="fw-bold text-white fs-6"><i class="bi bi-patch-check-fill text-warning"></i> Toàn quyền Premium</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Features Section -->
        <div class="mb-5 eduvn-reveal">
            <div class="section-header">
                <div class="sh-icon">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div>
                    <h3>Chức Năng Chính</h3>
                    <p>Các công cụ thiết yếu cho giảng dạy, quản lý học sinh và đề thi</p>
                </div>
            </div>

            <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-xl-3">

                <!-- Manage Students -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon primary mx-auto">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Quản Lý Học Sinh</h5>
                        <p class="feature-desc text-center">Theo dõi, quản lý thông tin và kết quả học tập của học sinh trong từng lớp</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Thêm / Sửa / Xóa học sinh</li>
                            <li><i class="bi bi-check-circle-fill"></i> Import dữ liệu từ Excel</li>
                            <li><i class="bi bi-check-circle-fill"></i> Phân loại danh sách lớp học</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="manage_students.php" class="btn btn-primary btn-action-custom w-100">
                                <span>Truy Cập Quản Lý</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Create Exams -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon success mx-auto">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Tạo Bài Kiểm Tra</h5>
                        <p class="feature-desc text-center">Tạo đề thi nhanh chóng với nhiều định dạng và cấu trúc đề thi linh hoạt</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Tạo thủ công hoặc tự động</li>
                            <li><i class="bi bi-check-circle-fill"></i> Đa dạng hình thức câu hỏi</li>
                            <li><i class="bi bi-check-circle-fill"></i> Ma trận đề thi đạt chuẩn</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="exam_creation.php" class="btn btn-success btn-action-custom w-100">
                                <span>Truy Cập Tạo Đề</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Question Bank -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon warning mx-auto">
                                <i class="bi bi-bank2"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Ngân Hàng Câu Hỏi</h5>
                        <p class="feature-desc text-center">Quản lý kho câu hỏi phong phú phân loại theo môn học và chương trình</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Phân loại môn & chương</li>
                            <li><i class="bi bi-check-circle-fill"></i> Nhập từ Excel/Word thông minh</li>
                            <li><i class="bi bi-check-circle-fill"></i> Tìm kiếm & lọc nhanh chóng</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="question_bank.php" class="btn btn-soft-warning btn-action-custom w-100">
                                <span>Truy Cập Ngân Hàng</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Manage Results -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon info mx-auto">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Kết Quả Học Tập</h5>
                        <p class="feature-desc text-center">Theo dõi và phân tích chi tiết kết quả làm bài kiểm tra của học sinh</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Thống kê bài thi chi tiết</li>
                            <li><i class="bi bi-check-circle-fill"></i> Biểu đồ kết quả trực quan</li>
                            <li><i class="bi bi-check-circle-fill"></i> Xuất kết quả ra Excel/PDF</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="manage_result.php" class="btn btn-soft-info btn-action-custom w-100">
                                <span>Truy Cập Kết Quả</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Lesson Plans - PREMIUM -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column <?php echo $isPremium ? 'premium-active-border' : 'opacity-85'; ?>">
                        <?php if (!$isPremium): ?>
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-warning text-dark font-weight-bold px-2 py-1"><i class="bi bi-star-fill"></i> Premium</span>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mb-3">
                            <div class="feature-icon <?php echo $isPremium ? 'primary' : 'slate'; ?> mx-auto">
                                <i class="bi bi-journal-bookmark"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Kế Hoạch Bài Dạy</h5>
                        <p class="feature-desc text-center">Tạo và quản lý kế hoạch bài dạy chuẩn quy định 4 hoạt động</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Form chuẩn Bộ GD&ĐT</li>
                            <li><i class="bi bi-check-circle-fill"></i> Chia sẻ cùng đồng nghiệp</li>
                            <li><i class="bi bi-check-circle-fill"></i> Xuất file Word/PDF tiện lợi</li>
                        </ul>
                        <div class="mt-auto">
                            <?php if ($isPremium): ?>
                                <a href="lesson_plans.php" class="btn btn-primary btn-action-custom w-100">
                                    <span>Truy Cập Giáo Án</span>
                                    <i class="bi bi-arrow-right-short fs-5"></i>
                                </a>
                            <?php else: ?>
                                <a href="premium_activation.php" class="btn btn-soft-warning btn-action-custom w-100">
                                    <i class="bi bi-lock-fill"></i>
                                    <span>Nâng Cấp Để Mở Khóa</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bài Học Chiếu TV -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon primary mx-auto" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="bi bi-tv-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Bài Học (Chiếu TV)</h5>
                        <p class="feature-desc text-center">Chọn lọc câu hỏi từ Ngân hàng câu hỏi và trình chiếu slide chữ to rõ trên TV phòng học</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Chọn câu hỏi từ Ngân hàng</li>
                            <li><i class="bi bi-check-circle-fill"></i> Slide font chữ to, rõ nét trên TV</li>
                            <li><i class="bi bi-check-circle-fill"></i> Hiện/ẩn đáp án, đếm giờ, gọi tên</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="lessons.php" class="btn btn-success btn-action-custom w-100" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
                                <span>Vào Bài Học</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Slide Bài Giảng -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon violet mx-auto">
                                <i class="bi bi-easel-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Slide Bài Giảng</h5>
                        <p class="feature-desc text-center">Tạo và trình chiếu slide bài giảng sinh động, chuyên nghiệp</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Trình biên soạn trực quan</li>
                            <li><i class="bi bi-check-circle-fill"></i> Giao diện Template hiện đại</li>
                            <li><i class="bi bi-check-circle-fill"></i> Chế độ trình chiếu Fullscreen</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="slides.php" class="btn btn-soft-violet btn-action-custom w-100">
                                <span>Soạn Slide</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Manage Assignments -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="feature-icon danger mx-auto">
                                <i class="bi bi-journal-text"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Quản Lý Bài Tập</h5>
                        <p class="feature-desc text-center">Giao bài tập về nhà cho lớp, theo dõi tiến độ nộp bài của học sinh</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Giao bài cho nhiều lớp</li>
                            <li><i class="bi bi-check-circle-fill"></i> Tự động kiểm soát deadline</li>
                            <li><i class="bi bi-check-circle-fill"></i> Chấm & xem bài nộp</li>
                        </ul>
                        <div class="mt-auto">
                            <a href="manage_assignments.php" class="btn btn-soft-danger btn-action-custom w-100">
                                <span>Quản Lý Bài Tập</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Matrix Builder - PREMIUM -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column <?php echo $isPremium ? 'premium-active-border' : 'opacity-85'; ?>">
                        <?php if (!$isPremium): ?>
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-warning text-dark font-weight-bold px-2 py-1"><i class="bi bi-star-fill"></i> Premium</span>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mb-3">
                            <div class="feature-icon <?php echo $isPremium ? 'violet' : 'slate'; ?> mx-auto">
                                <i class="bi bi-diagram-3-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Xây Dựng Ma Trận</h5>
                        <p class="feature-desc text-center">Tạo ma trận đề kiểm tra chi tiết theo tỷ lệ nhận biết, thông hiểu, vận dụng</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Phân bổ câu hỏi chuẩn</li>
                            <li><i class="bi bi-check-circle-fill"></i> Đúng khung quy định</li>
                            <li><i class="bi bi-check-circle-fill"></i> Xuất file ma trận Word</li>
                        </ul>
                        <div class="mt-auto">
                            <?php if ($isPremium): ?>
                                <a href="matrix_builder.php" class="btn btn-soft-warning btn-action-custom w-100">
                                    <span>Tạo Ma Trận</span>
                                    <i class="bi bi-arrow-right-short fs-5"></i>
                                </a>
                            <?php else: ?>
                                <a href="premium_activation.php" class="btn btn-soft-warning btn-action-custom w-100">
                                    <i class="bi bi-lock-fill"></i>
                                    <span>Nâng Cấp Mở Khóa</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Export Word - PREMIUM -->
                <div class="col">
                    <div class="feature-card h-100 p-4 d-flex flex-column <?php echo $isPremium ? 'premium-active-border' : 'opacity-85'; ?>">
                        <?php if (!$isPremium): ?>
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-warning text-dark font-weight-bold px-2 py-1"><i class="bi bi-star-fill"></i> Premium</span>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mb-3">
                            <div class="feature-icon <?php echo $isPremium ? 'gold' : 'slate'; ?> mx-auto">
                                <i class="bi bi-file-word-fill"></i>
                            </div>
                        </div>
                        <h5 class="feature-title text-center mb-2">Xuất Đề File Word</h5>
                        <p class="feature-desc text-center">Xuất đề thi và bảng đáp án chi tiết ra định dạng Word để in ấn</p>
                        <ul class="feature-checklist mb-4">
                            <li><i class="bi bi-check-circle-fill"></i> Format trình bày chuẩn đẹp</li>
                            <li><i class="bi bi-check-circle-fill"></i> Hỗ trợ công thức MathJax/LaTeX</li>
                            <li><i class="bi bi-check-circle-fill"></i> Tách riêng trang đáp án</li>
                        </ul>
                        <div class="mt-auto">
                            <?php if ($isPremium): ?>
                                <a href="my_exams.php" class="btn btn-soft-info btn-action-custom w-100">
                                    <span>Xuất File Word</span>
                                    <i class="bi bi-arrow-right-short fs-5"></i>
                                </a>
                            <?php else: ?>
                                <a href="premium_activation.php" class="btn btn-soft-warning btn-action-custom w-100">
                                    <i class="bi bi-lock-fill"></i>
                                    <span>Nâng Cấp Mở Khóa</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tools Section -->
        <div class="mb-5 eduvn-reveal">
            <div class="section-header">
                <div class="sh-icon alt">
                    <i class="bi bi-tools"></i>
                </div>
                <div>
                    <h3>Công Cụ Hỗ Trợ</h3>
                    <p>Các công cụ tiện ích nâng cao giúp tiết kiệm thời gian giảng dạy</p>
                </div>
            </div>

            <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-4">

                <!-- Excel Comments - PREMIUM -->
                <div class="col">
                    <div class="feature-card h-100 p-4 text-center d-flex flex-column <?php echo $isPremium ? 'premium-active-border' : ''; ?>">
                        <?php if (!$isPremium): ?>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-warning text-dark font-weight-bold px-2 py-1"><i class="bi bi-star-fill"></i> Premium</span>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <div class="feature-icon <?php echo $isPremium ? 'success' : 'slate'; ?> mx-auto" style="width: 56px; height: 56px; border-radius: 16px; font-size: 1.4rem;">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Nhận Xét Vnedu</h6>
                        <p class="text-muted small mb-3">Tự động tạo nhận xét học sinh từ file Excel đưa lên hệ thống Vnedu</p>
                        <div class="mt-auto">
                            <?php if ($isPremium): ?>
                                <a href="excel_comments.php" class="btn btn-soft-success btn-action-custom w-100 py-2">
                                    <span>Sử Dụng</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
                            <?php else: ?>
                                <a href="premium_activation.php" class="btn btn-soft-warning btn-action-custom w-100 py-2">
                                    <i class="bi bi-lock-fill"></i> <span>Mở Khóa</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Lucky Wheel -->
                <div class="col">
                    <div class="feature-card h-100 p-4 text-center d-flex flex-column">
                        <div class="mb-3">
                            <div class="feature-icon danger mx-auto" style="width: 56px; height: 56px; border-radius: 16px; font-size: 1.4rem;">
                                <i class="bi bi-disc-fill"></i>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Vòng Quay May Mắn</h6>
                        <p class="text-muted small mb-3">Chọn ngẫu nhiên học sinh trả lời câu hỏi công bằng và không khí vui nhộn</p>
                        <div class="mt-auto">
                            <a href="lucky_wheel.php" class="btn btn-soft-danger btn-action-custom w-100 py-2">
                                <span>Sử Dụng</span>
                                <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Remote Control -->
                <div class="col">
                    <div class="feature-card h-100 p-4 text-center d-flex flex-column">
                        <div class="mb-3">
                            <div class="feature-icon primary mx-auto" style="width: 56px; height: 56px; border-radius: 16px; font-size: 1.4rem;">
                                <i class="bi bi-broadcast"></i>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Điều Khiển Từ Xa</h6>
                        <p class="text-muted small mb-3">Điều khiển trình chiếu trên máy tính từ điện thoại thông minh</p>
                        <div class="mt-auto">
                            <a href="remote_control.php" class="btn btn-primary btn-action-custom w-100 py-2">
                                <span>Sử Dụng</span>
                                <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Guide -->
                <div class="col">
                    <div class="feature-card h-100 p-4 text-center d-flex flex-column">
                        <div class="mb-3">
                            <div class="feature-icon info mx-auto" style="width: 56px; height: 56px; border-radius: 16px; font-size: 1.4rem;">
                                <i class="bi bi-question-circle-fill"></i>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Hướng Dẫn Sử Dụng</h6>
                        <p class="text-muted small mb-3">Tài liệu hướng dẫn chi tiết các thao tác quản lý trên hệ thống</p>
                        <div class="mt-auto">
                            <a href="user_guide.php" class="btn btn-soft-info btn-action-custom w-100 py-2">
                                <span>Xem Hướng Dẫn</span>
                                <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recent Notifications Section -->
        <?php if (!empty($recentNotifications)): ?>
            <div class="eduvn-card overflow-hidden mb-5 eduvn-reveal">
                <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-bell-fill text-primary fs-5"></i>
                        <span>Thông Báo Mới</span>
                    </h5>
                    <a href="notifications.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Xem tất cả</a>
                </div>
                <div>
                    <?php foreach ($recentNotifications as $notif): ?>
                        <?php
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
                        <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="notification-item unread">
                            <div class="notif-icon">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                    <h6><?php echo htmlspecialchars($notif['title']); ?></h6>
                                    <span class="notif-time"><i class="bi bi-clock me-1"></i><?php echo $timeAgo; ?></span>
                                </div>
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clean up modal backdrops if any
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    });
</script>

<?php include '../includes/footer.php'; ?>
