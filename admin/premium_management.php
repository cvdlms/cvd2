<?php
include '../includes/session_check.php';
include '../includes/premium_helper.php';

if ($_SESSION['role'] !== 'admin') {
    die('Chỉ admin mới có quyền truy cập trang này.');
}

// Get user data
$users = json_decode(file_get_contents('user.json'), true) ?: [];
$fullname = $users['admin']['fullname'] ?? 'Admin';

$stats = getPremiumStats();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Premium - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
</head>
<body class="admin-page">
  <?php $current_page = 'premium_management.php'; include 'navbar.php'; ?>

<div class="container-fluid my-4">
    <h2 class="mb-4">🎖️ Quản Lý Premium</h2>
    
    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Tổng GV Premium</h5>
                    <h2><?php echo $stats['total_active']; ?></h2>
                    <p class="mb-0">Đang hoạt động</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Sắp Hết Hạn</h5>
                    <h2><?php echo $stats['expiring_soon']; ?></h2>
                    <p class="mb-0">Trong 7 ngày tới</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Key Chưa Dùng</h5>
                    <h2><?php echo $stats['unused_keys']; ?></h2>
                    <p class="mb-0">Khả dụng</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Đơn Chờ Duyệt</h5>
                    <h2><?php echo $stats['pending_orders']; ?></h2>
                    <p class="mb-0">Cần xử lý</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="premiumTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="subscriptions-tab" data-bs-toggle="tab" data-bs-target="#subscriptions" type="button">
                👥 Tài Khoản Premium
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys" type="button">
                🔑 Quản Lý Key
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                📋 Đơn Đăng Ký
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button">
                ⚙️ Cấu Hình
            </button>
        </li>
    </ul>

    <div class="tab-content" id="premiumTabContent">
        <!-- Tài khoản Premium Tab -->
        <div class="tab-pane fade show active" id="subscriptions" role="tabpanel">
            <?php include 'premium_subscriptions_tab.php'; ?>
        </div>

        <!-- Quản lý Key Tab -->
        <div class="tab-pane fade" id="keys" role="tabpanel">
            <?php include 'premium_keys_tab.php'; ?>
        </div>

        <!-- Đơn đăng ký Tab -->
        <div class="tab-pane fade" id="orders" role="tabpanel">
            <?php include 'premium_orders_tab.php'; ?>
        </div>

        <!-- Cấu hình Tab -->
        <div class="tab-pane fade" id="config" role="tabpanel">
            <?php include 'premium_config_tab.php'; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã copy: ' + text);
    });
}
</script>
</body>
</html>
