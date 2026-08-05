<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$premiumStatus = getStudentPremiumStatus($studentCode);
$message = '';
$messageType = '';

// Load pricing from admin configuration
$pricingFile = __DIR__ . '/../admin/premium_pricing.json';
$packages = [];

if (file_exists($pricingFile)) {
    $pricingData = json_decode(file_get_contents($pricingFile), true);
    $studentPackages = $pricingData['student'] ?? [];

    // Get all active packages from admin config
    foreach ($studentPackages as $pkg) {
        if (!($pkg['is_active'] ?? true)) continue;

        $originalPrice = 0;
        $save = 0;
        if (isset($pkg['discount']) && $pkg['discount'] > 0) {
            $originalPrice = round($pkg['price'] / (1 - $pkg['discount'] / 100));
            $save = $originalPrice - $pkg['price'];
        }

        $packages[$pkg['id']] = [
            'id' => $pkg['id'],
            'name' => $pkg['name'],
            'price' => $pkg['price'],
            'duration' => $pkg['duration_days'],
            'save' => $save,
            'original_price' => $originalPrice,
            'features' => $pkg['features'] ?? [],
            'discount' => $pkg['discount'] ?? 0
        ];
    }

    // Sort by duration (shortest to longest)
    uasort($packages, function($a, $b) {
        return $a['duration'] - $b['duration'];
    });
}

// Fallback if no packages configured
if (empty($packages)) {
    $packages = [
        'student_1month' => ['id' => 'student_1month', 'name' => 'Gói Tháng', 'price' => 29000, 'duration' => 30, 'save' => 0, 'original_price' => 0, 'features' => [], 'discount' => 0]
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_premium') {
        $packageType = $_POST['package_type'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        if (!isset($packages[$packageType])) {
            $message = 'Vui lòng chọn một gói Premium hợp lệ.';
            $messageType = 'danger';
        } else {
            // Save request
            $requestsFile = __DIR__ . '/../admin/student_premium_requests.json';
            $requests = [];
            if (file_exists($requestsFile)) {
                $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
            }

            $price = $packages[$packageType]['price'];

            $requests[] = [
                'id' => uniqid('req_'),
                'student_code' => $studentCode,
                'student_name' => $studentName,
                'class' => $studentClass,
                'package_type' => $packageType,
                'price' => $price,
                'notes' => $notes,
                'status' => 'pending',
                'requested_at' => date('Y-m-d H:i:s')
            ];

            file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $message = 'Yêu cầu của bạn đã được gửi! Giáo viên sẽ xem xét và phê duyệt sớm nhất.';
            $messageType = 'success';
        }
    }
}

$title = 'Premium - EduVN';
include '../includes/student_header.php';
?>

    <div class="std-content">
        <header class="std-masthead">
            <div class="std-page-head" style="margin-bottom:0;">
                <div class="ph-title">
                    <div class="ph-ic" style="background:var(--grad-amber);"><i class="bi bi-star-fill"></i></div>
                    <div>
                        <h1>Premium</h1>
                        <div class="ph-sub">Mở khóa toàn bộ tính năng học tập thông minh</div>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert" style="margin-top:18px;">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($premiumStatus['is_premium']): ?>
            <!-- Already Premium -->
            <section class="std-hero" style="margin-top:18px;">
                <div class="std-hero-inner">
                    <h2>✨ Bạn là thành viên Premium!</h2>
                    <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi</p>
                    <div class="premium-badge-box">
                        <span class="badge badge-gold">Gói:
                            <?php
                            $types = ['month' => 'Tháng', 'semester' => 'Học Kỳ', 'year' => 'Năm Học'];
                            echo $types[$premiumStatus['type']] ?? 'Premium';
                            ?>
                        </span>
                        <span class="badge badge-gold">Hết hạn: <?php echo date('d/m/Y', strtotime($premiumStatus['end_date'])); ?></span>
                        <span class="badge badge-gold">Còn lại: <?php echo $premiumStatus['days_remaining']; ?> ngày</span>
                    </div>
                </div>
            </section>

            <div class="std-card" style="margin-top:18px;">
                <h3>🎉 Tài khoản Premium của bạn</h3>
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
                <a href="dashboard.php" class="std-btn std-violet mt-3">Bắt đầu học ngay</a>
            </div>

        <?php else: ?>
            <!-- Not Premium - Show Packages -->

            <!-- Features Section -->
            <section style="margin-top:24px;">
                <h3 class="std-section-head">💎 Premium mang lại gì cho bạn?</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="std-card text-center h-100 p-4">
                            <div class="feature-icon">📚</div>
                            <h5>Học không giới hạn</h5>
                            <p class="text-muted">Luyện tập & làm lại bài thi bao nhiêu cũng được, không lo giới hạn</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="std-card text-center h-100 p-4">
                            <div class="feature-icon">📊</div>
                            <h5>Thống kê thông minh</h5>
                            <p class="text-muted">Biểu đồ chi tiết, phát hiện điểm yếu, theo dõi tiến bộ theo thời gian</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="std-card text-center h-100 p-4">
                            <div class="feature-icon">🤖</div>
                            <h5>AI gợi ý</h5>
                            <p class="text-muted">Hệ thống AI phân tích và gợi ý bài tập phù hợp với trình độ</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section>
                <h3 class="std-section-head">🎯 Chọn gói phù hợp với bạn</h3>
                <div class="row g-3">
                    <?php
                    $icons = ['🎒', '📚', '⭐', '🎓', '💎'];
                    $iconIndex = 0;
                    $middleIndex = floor(count($packages) / 2);
                    $currentIndex = 0;
                    foreach ($packages as $pkg):
                        $isRecommended = ($currentIndex === $middleIndex && count($packages) > 1);
                        $icon = $icons[$iconIndex % count($icons)];
                        $iconIndex++;
                    ?>
                    <div class="col-md-<?php echo count($packages) <= 3 ? '4' : '3'; ?> mb-3">
                        <div class="std-card package-card <?php echo $isRecommended ? 'recommended' : ''; ?> h-100">
                            <div class="card-body text-center p-4" <?php echo $isRecommended ? 'style="margin-top: 15px;"' : ''; ?>>
                                <h3 class="mb-3"><?php echo $icon; ?> <?php echo htmlspecialchars($pkg['name']); ?></h3>
                                <div class="price mb-2"><?php echo number_format($pkg['price']); ?>đ</div>
                                <?php if ($pkg['save'] > 0): ?>
                                <div class="old-price mb-2"><?php echo number_format($pkg['original_price']); ?>đ</div>
                                <p class="text-muted mb-4">Tiết kiệm <?php echo number_format($pkg['save']); ?>đ (<?php echo $pkg['discount']; ?>%)</p>
                                <?php else: ?>
                                <p class="text-muted mb-4"><?php echo $pkg['duration']; ?> ngày</p>
                                <?php endif; ?>
                                <ul class="list-unstyled text-start mb-4">
                                    <?php if (!empty($pkg['features'])): ?>
                                        <?php foreach ($pkg['features'] as $feature): ?>
                                        <li class="mb-2">✅ <?php echo htmlspecialchars($feature); ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="mb-2">✅ Tất cả tính năng Premium</li>
                                        <li class="mb-2">✅ Sử dụng <?php echo $pkg['duration']; ?> ngày</li>
                                        <li class="mb-2">✅ Hỗ trợ ưu tiên</li>
                                    <?php endif; ?>
                                </ul>
                                <button class="btn w-100 <?php echo $isRecommended ? 'std-btn std-violet' : 'btn-outline-violet'; ?>"
                                        onclick="selectPackage('<?php echo htmlspecialchars($pkg['id'], ENT_QUOTES); ?>')">
                                    Chọn gói này
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                        $currentIndex++;
                    endforeach; ?>
                </div>
            </section>

            <!-- Request Form -->
            <section class="row justify-content-center mt-4">
                <div class="col-md-8">
                    <div class="std-card p-4">
                        <h4 class="mb-4">📝 Đăng ký Premium</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="request_premium">
                            <div class="mb-3">
                                <label class="form-label">Chọn gói Premium *</label>
                                <select class="form-select" name="package_type" id="packageSelect" required>
                                    <option value="">-- Chọn gói --</option>
                                    <?php foreach ($packages as $pkg): ?>
                                    <option value="<?php echo htmlspecialchars($pkg['id'], ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($pkg['name']); ?> - <?php echo number_format($pkg['price']); ?>đ<?php echo $pkg['save'] > 0 ? ' (Tiết kiệm ' . number_format($pkg['save']) . 'đ)' : ''; ?>
                                    </option>
                                    <?php endforeach; ?>
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
                            <button type="submit" class="std-btn std-amber btn-lg w-100">Gửi yêu cầu đăng ký</button>
                        </form>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <style>
        .premium-badge-box { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .badge-gold {
            background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.35);
            border-radius: 99px; padding: 7px 16px; font-weight: 700; font-size: .82rem; backdrop-filter: blur(4px);
        }
        .package-card { transition: all .25s ease; position: relative; border: 2px solid var(--border); }
        .package-card:hover { transform: translateY(-8px); box-shadow: 0 18px 40px -18px rgba(32,34,58,.35); }
        .package-card.recommended { border-color: var(--violet); background: linear-gradient(160deg, #F6F4FF 0%, #fff 100%); }
        .package-card.recommended::before {
            content: "🏆 Khuyến nghị"; position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
            background: var(--grad-violet); color: #fff; padding: 5px 20px; border-radius: 99px;
            font-size: 13px; font-weight: 700; white-space: nowrap;
        }
        .feature-icon { font-size: 30px; margin-bottom: 10px; }
        .price { font-size: 34px; font-weight: 800; font-family: var(--display); color: var(--violet); }
        .old-price { text-decoration: line-through; color: #999; font-size: 19px; }
        .btn-outline-violet { border: 2px solid var(--violet); color: var(--violet); background: #fff; font-weight: 700; }
        .btn-outline-violet:hover { background: var(--violet); color: #fff; }
        .std-btn.btn-lg { padding: 13px 24px; font-size: 1rem; }
    </style>

    <script>
        function selectPackage(id) {
            const sel = document.getElementById('packageSelect');
            sel.value = id;
            sel.scrollIntoView({ behavior: 'smooth' });
        }
    </script>

<?php include '../includes/student_footer.php'; ?>
