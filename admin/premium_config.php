<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'premium_config.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true);
}

$premiumConfig = $config['premium'] ?? [];
$isPremiumEnabled = (bool)($premiumConfig['enabled'] ?? true);
$trialDays = (int)($premiumConfig['trial_days'] ?? 7);

$features = [
    'unlimited_exams' => [
        'icon' => 'bi-infinity',
        'name' => 'Tạo đề thi không giới hạn',
        'desc' => 'Không giới hạn số lượng đề thi và lượt tạo của giáo viên'
    ],
    'export_with_answers' => [
        'icon' => 'bi-file-earmark-pdf',
        'name' => 'Xuất đề thi có đáp án',
        'desc' => 'Cho phép tải và in file đề kèm lời giải/đáp án chi tiết'
    ],
    'auto_matrix' => [
        'icon' => 'bi-grid-3x3-gap',
        'name' => 'Tạo ma trận đề tự động',
        'desc' => 'Tự động sinh ma trận phân bổ câu hỏi theo ma trận đề chuẩn'
    ],
    'advanced_stats' => [
        'icon' => 'bi-graph-up-arrow',
        'name' => 'Thống kê nâng cao',
        'desc' => 'Báo cáo chi tiết biểu đồ và phân tích kết quả chuyên sâu'
    ],
    'import_excel' => [
        'icon' => 'bi-file-earmark-spreadsheet',
        'name' => 'Import câu hỏi từ Excel',
        'desc' => 'Tải lên danh sách câu hỏi hàng loạt qua file bảng tính'
    ],
    'question_bank_unlimited' => [
        'icon' => 'bi-database',
        'name' => 'Ngân hàng câu hỏi không giới hạn',
        'desc' => 'Lưu trữ không giới hạn kho câu hỏi cá nhân của giáo viên'
    ]
];

$activeFeaturesCount = 0;
foreach ($features as $key => $feature) {
    if (!empty($premiumConfig['features'][$key])) {
        $activeFeaturesCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Premium - CVD Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700;1,9..144,500&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/admin-ui.css?v=20260806" rel="stylesheet">
    <link href="assets/admin-navbar.css?v=20260806" rel="stylesheet">
    <link href="assets/premium_config.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
    <?php include 'navbar.php'; ?>

    <main class="cvd-page">
        <header class="cvd-page-header">
            <div>
                <div class="cvd-eyebrow"><i class="bi bi-sliders"></i> Cấu hình hệ thống</div>
                <h1>Cấu hình Premium</h1>
                <p class="cvd-sub">Thiết lập trạng thái hoạt động, thời gian dùng thử và danh sách tính năng thuộc gói Premium.</p>
            </div>
            <div class="cvd-page-actions">
                <a href="premium_management.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quản lý Premium
                </a>
                <a href="premium_pricing.php" class="btn btn-outline-secondary">
                    <i class="bi bi-cash-coin me-1"></i> Bảng giá gói
                </a>
            </div>
        </header>

        <!-- Stats row -->
        <section class="cvd-stats" aria-label="Thống kê cấu hình Premium">
            <div class="cvd-stat cvd-reveal">
                <span class="cvd-stat-icon <?php echo $isPremiumEnabled ? '' : 'is-accent'; ?>">
                    <i class="bi <?php echo $isPremiumEnabled ? 'bi-check-circle-fill' : 'bi-slash-circle-fill'; ?>"></i>
                </span>
                <div>
                    <div class="cvd-stat-value"><?php echo $isPremiumEnabled ? 'Đang bật' : 'Tắt'; ?></div>
                    <div class="cvd-stat-label">Hệ thống Premium</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d1">
                <span class="cvd-stat-icon is-gold"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php
                        if ($trialDays >= 365) {
                            echo '1 năm';
                        } elseif ($trialDays >= 180) {
                            echo '6 tháng';
                        } elseif ($trialDays >= 30) {
                            echo round($trialDays / 30) . ' tháng';
                        } else {
                            echo $trialDays . ' <small style="font-size: 1rem; font-weight: normal;">ngày</small>';
                        }
                    ?></div>
                    <div class="cvd-stat-label">Thời gian dùng thử</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d2">
                <span class="cvd-stat-icon"><i class="bi bi-stars"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $activeFeaturesCount; ?> / <?php echo count($features); ?></div>
                    <div class="cvd-stat-label">Tính năng Premium bật</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d3">
                <span class="cvd-stat-icon is-accent"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="cvd-stat-value"><a href="premium_pricing.php" style="text-decoration: none; color: inherit;">Biểu phí</a></div>
                    <div class="cvd-stat-label">Quản lý giá gói dịch vụ</div>
                </div>
            </div>
        </section>

        <form id="premiumConfigForm">
            <!-- Section 1: General Status & Trial -->
            <section class="cvd-panel cvd-reveal cvd-reveal-d1 mb-4">
                <div class="cvd-panel-header">
                    <div>
                        <h2>Trạng thái & Thời gian dùng thử</h2>
                        <p>Bật/tắt chế độ Premium và thiết lập số ngày trải nghiệm cho giáo viên mới</p>
                    </div>
                </div>
                <div class="cvd-panel-body">
                    <div class="config-toggle-box is-highlight mb-3">
                        <div>
                            <h4 class="mb-1" style="font-size: .95rem; font-weight: 700;">Kích hoạt hệ thống Premium</h4>
                            <p class="mb-0 text-muted small">
                                Khi BẬT: Hệ thống sẽ giới hạn tính năng nâng cao cho giáo viên thường và mở cho tài khoản Premium.<br>
                                Khi TẮT: Toàn bộ tính năng sẽ được mở miễn phí cho tất cả giáo viên.
                            </p>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input" type="checkbox" name="premium_enabled" id="premiumEnabled" 
                                <?php echo $isPremiumEnabled ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label">Thời gian dùng thử miễn phí</label>
                            <input type="hidden" name="trial_days" id="trialDaysInput" value="<?php echo $trialDays; ?>">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-trial-btn" data-days="0">Tắt</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-trial-btn" data-days="180">6 tháng</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-trial-btn" data-days="365">1 năm</button>
                            </div>
                            <small class="text-muted">Giáo viên mới tạo tài khoản sẽ tự động nhận được thời gian trải nghiệm Premium này.</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 2: Features included in Premium -->
            <section class="cvd-panel cvd-reveal cvd-reveal-d2 mb-4">
                <div class="cvd-panel-header">
                    <div>
                        <h2>Danh sách tính năng gói Premium</h2>
                        <p>Chọn các tính năng chuyên biệt chỉ dành riêng cho tài khoản đã nâng cấp Premium</p>
                    </div>
                    <span class="badge bg-success-subtle text-success-emphasis">
                        <span id="activeFeatureCounter"><?php echo $activeFeaturesCount; ?></span> / <?php echo count($features); ?> tính năng
                    </span>
                </div>
                <div class="cvd-panel-body">
                    <div class="feature-grid">
                        <?php foreach ($features as $key => $feature): 
                            $isChecked = !empty($premiumConfig['features'][$key]);
                        ?>
                        <div class="feature-setting-card <?php echo $isChecked ? 'is-active' : ''; ?>" id="card_<?php echo $key; ?>">
                            <div class="feature-setting-info">
                                <div class="feature-setting-icon">
                                    <i class="bi <?php echo $feature['icon']; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="feature-setting-name"><?php echo htmlspecialchars($feature['name']); ?></h4>
                                    <p class="feature-setting-desc"><?php echo htmlspecialchars($feature['desc']); ?></p>
                                </div>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input feature-toggle" type="checkbox" 
                                    name="feature_<?php echo $key; ?>" 
                                    data-key="<?php echo $key; ?>"
                                    <?php echo $isChecked ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Section 3: Quick Navigation -->
            <section class="cvd-panel cvd-reveal cvd-reveal-d3 mb-4">
                <div class="cvd-panel-header">
                    <div>
                        <h2>Liên kết quản trị nhanh</h2>
                        <p>Truy cập các khu vực quản lý biểu phí và danh sách tài khoản Premium</p>
                    </div>
                </div>
                <div class="cvd-panel-body">
                    <div class="config-quick-links">
                        <a href="premium_management.php" class="config-quick-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="config-quick-card-icon">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="config-quick-card-info">
                                    <h4>Tài khoản & Mã kích hoạt</h4>
                                    <p>Xem danh sách giáo viên, cấp phát key và duyệt đơn hàng</p>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>

                        <a href="premium_pricing.php" class="config-quick-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="config-quick-card-icon" style="background: var(--cvd-gold-soft); color: var(--cvd-gold);">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="config-quick-card-info">
                                    <h4>Quản lý Giá & Gói dịch vụ</h4>
                                    <p>Thiết lập các gói dịch vụ (1 tháng, 6 tháng, 1 năm) và mức giá</p>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Submit Action Bar -->
            <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded shadow-sm">
                <button type="submit" class="btn btn-primary btn-lg" id="submitConfigBtn">
                    <i class="bi bi-floppy me-1"></i> Lưu Cấu Hình Premium
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Tải lại trang
                </button>
            </div>
        </form>

        <div class="cvd-footer-credit">
            Được phát triển & vận hành bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/toast-notifications.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Preset trial buttons
        const presetBtns = document.querySelectorAll('.preset-trial-btn');
        const trialInput = document.getElementById('trialDaysInput');

        function setActiveTrialBtn(val) {
            presetBtns.forEach(b => {
                if (b.dataset.days == val) {
                    b.classList.add('is-selected');
                } else {
                    b.classList.remove('is-selected');
                }
            });
        }

        setActiveTrialBtn(trialInput.value);

        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const days = this.dataset.days;
                trialInput.value = days;
                setActiveTrialBtn(days);
            });
        });

        // Toggle card visual state & update counter
        const featureToggles = document.querySelectorAll('.feature-toggle');
        const counterEl = document.getElementById('activeFeatureCounter');

        function updateFeatureCount() {
            let activeCount = 0;
            featureToggles.forEach(toggle => {
                const key = toggle.dataset.key;
                const card = document.getElementById('card_' + key);
                if (toggle.checked) {
                    activeCount++;
                    if (card) card.classList.add('is-active');
                } else {
                    if (card) card.classList.remove('is-active');
                }
            });
            if (counterEl) counterEl.textContent = activeCount;
        }

        featureToggles.forEach(toggle => {
            toggle.addEventListener('change', updateFeatureCount);
        });

        // Form submission
        const form = document.getElementById('premiumConfigForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitConfigBtn');
                submitBtn.disabled = true;

                const formData = new FormData(this);
                formData.append('action', 'update_premium_config');
                
                try {
                    const response = await fetch('api/system_config_actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    submitBtn.disabled = false;
                    
                    if (result.success) {
                        showSuccessToast(result.message || 'Đã cập nhật cấu hình Premium thành công!');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showErrorToast('Lỗi: ' + result.message);
                    }
                } catch (error) {
                    submitBtn.disabled = false;
                    showErrorToast('Có lỗi xảy ra: ' + error.message);
                }
            });
        }
    });
    </script>
</body>
</html>
