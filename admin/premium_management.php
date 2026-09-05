<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

require_once '../includes/premium_helper.php';

// Get user data
$users = json_decode(file_get_contents('user.json'), true) ?: [];
$fullname = $_SESSION['fullname'] ?? ($users['admin']['fullname'] ?? 'Admin');
$current_page = 'premium_management.php';

$stats = getPremiumStats();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Premium - CVD Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700;1,9..144,500&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/admin-ui.css?v=20260806" rel="stylesheet">
    <link href="assets/admin-navbar.css?v=20260806" rel="stylesheet">
    <link href="assets/premium_management.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
    <?php include 'navbar.php'; ?>

    <main class="cvd-page">
        <header class="cvd-page-header">
            <div>
                <div class="cvd-eyebrow"><i class="bi bi-patch-check-fill"></i> Quản trị dịch vụ</div>
                <h1>Quản lý Premium GV</h1>
                <p class="cvd-sub">Theo dõi gói tài khoản giáo viên, quản lý mã kích hoạt và xử lý đơn đăng ký gia hạn.</p>
            </div>
            <div class="cvd-page-actions">
                <a href="premium_pricing.php" class="btn btn-outline-secondary">
                    <i class="bi bi-cash-coin me-1"></i> Bảng giá gói
                </a>
                <a href="premium_config.php" class="btn btn-outline-secondary">
                    <i class="bi bi-sliders me-1"></i> Cấu hình dịch vụ
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKeyModal">
                    <i class="bi bi-plus-lg me-1"></i> Tạo key mới
                </button>
            </div>
        </header>

        <section class="cvd-stats" aria-label="Thống kê Premium">
            <div class="cvd-stat cvd-reveal">
                <span class="cvd-stat-icon"><i class="bi bi-person-badge-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo number_format($stats['total_active']); ?></div>
                    <div class="cvd-stat-label">GV Premium đang dùng</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d1">
                <span class="cvd-stat-icon is-gold"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo number_format($stats['expiring_soon']); ?></div>
                    <div class="cvd-stat-label">Sắp hết hạn (7 ngày tới)</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d2">
                <span class="cvd-stat-icon"><i class="bi bi-key-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo number_format($stats['unused_keys']); ?></div>
                    <div class="cvd-stat-label">Key kích hoạt khả dụng</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d3">
                <span class="cvd-stat-icon is-accent"><i class="bi bi-receipt-cutoff"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="cvd-stat-label">Đơn đăng ký chờ duyệt</div>
                </div>
            </div>
        </section>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs premium-tabs cvd-reveal cvd-reveal-d1" id="premiumTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="subscriptions-tab" data-bs-toggle="tab" data-bs-target="#subscriptions" type="button" role="tab" aria-controls="subscriptions" aria-selected="true">
                    <i class="bi bi-people-fill"></i>
                    <span>Tài khoản Premium</span>
                    <?php if ($stats['total_active'] > 0): ?>
                        <span class="badge bg-success-subtle text-success-emphasis ms-1"><?php echo $stats['total_active']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys" type="button" role="tab" aria-controls="keys" aria-selected="false">
                    <i class="bi bi-key-fill"></i>
                    <span>Quản lý Key</span>
                    <?php if ($stats['unused_keys'] > 0): ?>
                        <span class="badge bg-primary-subtle text-primary-emphasis ms-1"><?php echo $stats['unused_keys']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                    <i class="bi bi-card-checklist"></i>
                    <span>Đơn đăng ký</span>
                    <?php if ($stats['pending_orders'] > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis ms-1"><?php echo $stats['pending_orders']; ?> chờ duyệt</span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="premiumTabContent">
            <!-- Tài khoản Premium Tab -->
            <div class="tab-pane fade show active" id="subscriptions" role="tabpanel" aria-labelledby="subscriptions-tab">
                <?php include 'premium_subscriptions_tab.php'; ?>
            </div>

            <!-- Quản lý Key Tab -->
            <div class="tab-pane fade" id="keys" role="tabpanel" aria-labelledby="keys-tab">
                <?php include 'premium_keys_tab.php'; ?>
            </div>

            <!-- Đơn đăng ký Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                <?php include 'premium_orders_tab.php'; ?>
            </div>
        </div>

        <div class="cvd-footer-credit">
            Được phát triển & vận hành bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/toast-notifications.js"></script>
    <script>
    // Tab persistence across page reloads
    document.addEventListener('DOMContentLoaded', function() {
        const tabKey = 'cvd_premium_active_tab';
        const savedTab = sessionStorage.getItem(tabKey) || window.location.hash;
        if (savedTab) {
            const triggerEl = document.querySelector(`button[data-bs-target="${savedTab}"]`);
            if (triggerEl) {
                bootstrap.Tab.getOrCreateInstance(triggerEl).show();
            }
        }

        document.querySelectorAll('#premiumTabs button[data-bs-toggle="tab"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', function(e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target) {
                    sessionStorage.setItem(tabKey, target);
                    history.replaceState(null, null, target);
                }
            });
        });
    });
    </script>
</body>
</html>
