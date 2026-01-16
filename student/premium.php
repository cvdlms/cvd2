<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$premiumStatus = getStudentPremiumStatus($studentCode);
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_premium') {
        $packageType = $_POST['package_type'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        // Save request
        $requestsFile = __DIR__ . '/../admin/student_premium_requests.json';
        $requests = [];
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
        }
        
        // Price mapping
        $prices = [
            'month' => 29000,
            'semester' => 119000,
            'year' => 199000
        ];
        
        $requests[] = [
            'id' => uniqid('req_'),
            'student_code' => $studentCode,
            'student_name' => $studentName,
            'class' => $studentClass,
            'package_type' => $packageType,
            'price' => $prices[$packageType] ?? 0,
            'notes' => $notes,
            'status' => 'pending',
            'requested_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $message = 'Yêu cầu của bạn đã được gửi! Giáo viên sẽ xem xét và phê duyệt sớm nhất.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../styles/main.css" rel="stylesheet">
    <style>
        .premium-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .premium-hero::before {
            content: "⭐";
            position: absolute;
            font-size: 200px;
            opacity: 0.1;
            top: -50px;
            right: -50px;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .package-card {
            border: 3px solid transparent;
            transition: all 0.3s;
            position: relative;
        }
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .package-card.recommended {
            border-color: #667eea;
            background: linear-gradient(135deg, #f7f9fc 0%, #eef2ff 100%);
        }
        .package-card.recommended::before {
            content: "🏆 Khuyến nghị";
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .price {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/student_navbar.php'; ?>

    <?php if ($premiumStatus['is_premium']): ?>
        <!-- Already Premium -->
        <div class="premium-hero">
            <div class="container">
                <h1 class="display-4">✨ Bạn là thành viên Premium!</h1>
                <p class="lead">Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi</p>
            </div>
        </div>

        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body p-5 text-center">
                            <h3>🎉 Tài khoản Premium của bạn</h3>
                            <div class="my-4">
                                <p class="mb-2"><strong>Gói:</strong> <?php 
                                    $types = ['month' => 'Tháng', 'semester' => 'Học Kỳ', 'year' => 'Năm Học'];
                                    echo $types[$premiumStatus['type']] ?? 'Premium';
                                ?></p>
                                <p class="mb-2"><strong>Hết hạn:</strong> <?php echo date('d/m/Y', strtotime($premiumStatus['end_date'])); ?></p>
                                <p class="mb-2"><strong>Còn lại:</strong> <span class="text-success"><?php echo $premiumStatus['days_remaining']; ?> ngày</span></p>
                            </div>
                            
                            <h5 class="mt-4 mb-3">Tính năng đã mở khóa:</h5>
                            <div class="row text-start">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">✅ Luyện tập không giới hạn</li>
                                        <li class="mb-2">✅ Thi lại không giới hạn</li>
                                        <li class="mb-2">✅ Thống kê chi tiết</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">✅ Gợi ý AI thông minh</li>
                                        <li class="mb-2">✅ Tải đề về PDF</li>
                                        <li class="mb-2">✅ Không quảng cáo</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <a href="dashboard.php" class="btn btn-lg mt-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                                Bắt đầu học ngay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Not Premium - Show Packages -->
        <div class="premium-hero">
            <div class="container">
                <h1 class="display-3">⭐ Nâng cấp Premium</h1>
                <p class="lead">Mở khóa toàn bộ tính năng học tập thông minh</p>
            </div>
        </div>

        <div class="container my-5">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Features Section -->
            <div class="row mb-5">
                <div class="col-12 text-center mb-4">
                    <h2>💎 Premium mang lại gì cho bạn?</h2>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">📚</div>
                        <h5>Học không giới hạn</h5>
                        <p class="text-muted">Luyện tập & làm lại bài thi bao nhiêu cũng được, không lo giới hạn</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">📊</div>
                        <h5>Thống kê thông minh</h5>
                        <p class="text-muted">Biểu đồ chi tiết, phát hiện điểm yếu, theo dõi tiến bộ theo thời gian</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">🤖</div>
                        <h5>AI gợi ý</h5>
                        <p class="text-muted">Hệ thống AI phân tích và gợi ý bài tập phù hợp với trình độ</p>
                    </div>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="row mb-4">
                <div class="col-12 text-center mb-4">
                    <h2>🎯 Chọn gói phù hợp với bạn</h2>
                </div>

                <!-- Month Package -->
                <div class="col-md-4 mb-4">
                    <div class="card package-card h-100">
                        <div class="card-body text-center p-4">
                            <h3 class="mb-3">🎒 Gói Tháng</h3>
                            <div class="price mb-2">29.000đ</div>
                            <p class="text-muted mb-4">Phù hợp thử nghiệm</p>
                            <ul class="list-unstyled text-start mb-4">
                                <li class="mb-2">✅ Tất cả tính năng Premium</li>
                                <li class="mb-2">✅ Sử dụng 30 ngày</li>
                                <li class="mb-2">✅ Hỗ trợ ưu tiên</li>
                            </ul>
                            <button class="btn btn-outline-primary w-100" onclick="selectPackage('month')">
                                Chọn gói này
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Semester Package -->
                <div class="col-md-4 mb-4">
                    <div class="card package-card recommended h-100">
                        <div class="card-body text-center p-4" style="margin-top: 15px;">
                            <h3 class="mb-3">📚 Gói Học Kỳ</h3>
                            <div class="price mb-2">119.000đ</div>
                            <div class="old-price mb-2">145.000đ</div>
                            <p class="text-muted mb-4">Tiết kiệm 26.000đ</p>
                            <ul class="list-unstyled text-start mb-4">
                                <li class="mb-2">✅ Tất cả tính năng Premium</li>
                                <li class="mb-2">✅ Sử dụng 5 tháng (1 học kỳ)</li>
                                <li class="mb-2">✅ Hỗ trợ ưu tiên</li>
                                <li class="mb-2">🎁 Tặng tài liệu ôn thi</li>
                            </ul>
                            <button class="btn w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;" onclick="selectPackage('semester')">
                                Chọn gói này
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Year Package -->
                <div class="col-md-4 mb-4">
                    <div class="card package-card h-100">
                        <div class="card-body text-center p-4">
                            <h3 class="mb-3">🎓 Gói Năm Học</h3>
                            <div class="price mb-2">199.000đ</div>
                            <div class="old-price mb-2">290.000đ</div>
                            <p class="text-muted mb-4">Tiết kiệm 91.000đ</p>
                            <ul class="list-unstyled text-start mb-4">
                                <li class="mb-2">✅ Tất cả tính năng Premium</li>
                                <li class="mb-2">✅ Sử dụng 10 tháng (cả năm)</li>
                                <li class="mb-2">✅ Hỗ trợ ưu tiên</li>
                                <li class="mb-2">🎁 Tặng 1 tháng miễn phí</li>
                                <li class="mb-2">🎁 Tài liệu VIP</li>
                            </ul>
                            <button class="btn btn-outline-primary w-100" onclick="selectPackage('year')">
                                Chọn gói này
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Form -->
            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <h4 class="mb-4">📝 Đăng ký Premium</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="request_premium">
                                <div class="mb-3">
                                    <label class="form-label">Chọn gói Premium *</label>
                                    <select class="form-select" name="package_type" id="packageSelect" required>
                                        <option value="">-- Chọn gói --</option>
                                        <option value="month">🎒 Gói Tháng - 29.000đ</option>
                                        <option value="semester">📚 Gói Học Kỳ - 119.000đ (Khuyến nghị)</option>
                                        <option value="year">🎓 Gói Năm Học - 199.000đ (Tiết kiệm nhất)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ghi chú (nếu có)</label>
                                    <textarea class="form-control" name="notes" rows="3" placeholder="Ví dụ: Phụ huynh đã đồng ý, muốn thanh toán qua chuyển khoản..."></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <small>
                                        ℹ️ Sau khi gửi yêu cầu, giáo viên sẽ liên hệ với phụ huynh để xác nhận thanh toán.
                                        Tài khoản Premium sẽ được kích hoạt ngay sau khi thanh toán thành công.
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-lg w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                                    Gửi yêu cầu đăng ký
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPackage(type) {
            document.getElementById('packageSelect').value = type;
            document.getElementById('packageSelect').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
