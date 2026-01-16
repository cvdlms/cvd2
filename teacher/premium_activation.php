<?php
include '../includes/session_check.php';
include '../includes/premium_helper.php';

$username = $_SESSION['username'];
$isPremium = isPremiumUser($username);
$subscription = getActiveSubscription($username);
$daysRemaining = $isPremium ? getPremiumDaysRemaining($username) : 0;

// Load pricing from admin configuration
$pricingFile = __DIR__ . '/../admin/premium_pricing.json';
$packages = [];

if (file_exists($pricingFile)) {
    $pricingData = json_decode(file_get_contents($pricingFile), true);
    $teacherPackages = $pricingData['teacher'] ?? [];
    
    // Get all active packages
    $packageId = 1;
    foreach ($teacherPackages as $pkg) {
        if (!($pkg['is_active'] ?? true)) continue;
        
        $packages[] = [
            'package_id' => $packageId++,
            'name' => $pkg['name'],
            'duration_days' => $pkg['duration_days'],
            'price' => $pkg['price'],
            'currency' => 'VND',
            'features' => $pkg['features'] ?? [],
            'is_active' => true,
            'discount' => isset($pkg['discount']) && $pkg['discount'] > 0 ? 'Giảm ' . $pkg['discount'] . '%' : null
        ];
    }
}

// Fallback if no packages configured
if (empty($packages)) {
    $packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];
}

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true) ?: [];
$fullname = $users[$username]['fullname'] ?? 'Giáo Viên';

$message = '';
$messageType = '';

// Xử lý kích hoạt key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'activate_key') {
        $keyCode = trim($_POST['key_code'] ?? '');
        $result = activatePremiumByKey($username, $keyCode);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
        
        if ($result['success']) {
            header('Location: premium_activation.php?success=1');
            exit;
        }
    } elseif ($_POST['action'] === 'create_order') {
        $packageId = (int)($_POST['package_id'] ?? 0);
        $package = null;
        foreach ($packages as $p) {
            if ($p['package_id'] == $packageId) {
                $package = $p;
                break;
            }
        }
        
        if ($package) {
            $orderData = [
                'username' => $username,
                'fullname' => $fullname,
                'email' => trim($_POST['email'] ?? ''),
                'package_id' => $packageId,
                'package_name' => $package['name'],
                'price' => $package['price'],
                'notes' => trim($_POST['notes'] ?? '')
            ];
            
            $result = createPremiumOrder($orderData);
            
            // Redirect để tránh POST resubmission
            if ($result['success']) {
                header('Location: premium_activation.php?order_success=1');
                exit;
            } else {
                header('Location: premium_activation.php?order_error=' . urlencode($result['message']));
                exit;
            }
        }
    }
}

// Xử lý thông báo từ redirect
if (isset($_GET['order_success'])) {
    $message = 'Đơn đăng ký Premium đã được gửi thành công! Admin sẽ xem xét và phê duyệt.';
    $messageType = 'success';
} elseif (isset($_GET['order_error'])) {
    $message = urldecode($_GET['order_error']);
    $messageType = 'danger';
}

$title = 'Kích Hoạt Premium - CVD';
include '../includes/teacher_header.php';
?>

<div class="container my-4">
    <h2 class="mb-4">⭐ Kích Hoạt Tài Khoản Premium</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>🎉 Chúc mừng!</strong> Bạn đã kích hoạt Premium thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Trạng thái Premium -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card <?php echo $isPremium ? 'border-warning' : ''; ?>">
                <div class="card-header <?php echo $isPremium ? 'bg-warning text-dark' : 'bg-primary'; ?>">
                    <h5 class="mb-0">
                        <?php if ($isPremium): ?>
                            ⭐ Trạng Thái: PREMIUM
                        <?php else: ?>
                            Trạng Thái: Tài Khoản Thường
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($isPremium && $subscription): ?>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Gói Premium:</strong><br>
                                <span class="badge bg-success"><?php echo htmlspecialchars($subscription['package_name']); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Ngày Bắt Đầu:</strong><br>
                                <?php echo date('d/m/Y', strtotime($subscription['start_date'])); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Ngày Hết Hạn:</strong><br>
                                <?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Còn Lại:</strong><br>
                                <span class="badge bg-<?php echo $daysRemaining <= 7 ? 'danger' : 'info'; ?>">
                                    <?php echo $daysRemaining; ?> ngày
                                </span>
                            </div>
                        </div>
                        <?php if ($daysRemaining <= 7): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>⚠️ Thông báo:</strong> Premium của bạn sắp hết hạn. Vui lòng gia hạn!
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mb-0">Nâng cấp lên Premium để sử dụng đầy đủ tính năng của hệ thống!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isPremium): ?>
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="activationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="key-tab" data-bs-toggle="tab" data-bs-target="#keyTab" type="button">
                    🔑 Kích Hoạt Bằng Key
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="order-tab" data-bs-toggle="tab" data-bs-target="#orderTab" type="button">
                    📋 Đăng Ký Premium
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="packages-tab" data-bs-toggle="tab" data-bs-target="#packagesTab" type="button">
                    💎 Bảng Giá
                </button>
            </li>
        </ul>

        <div class="tab-content" id="activationTabContent">
            <!-- Tab Kích hoạt Key -->
            <div class="tab-pane fade show active" id="keyTab" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Kích Hoạt Bằng Key</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="activate_key">
                                    <div class="mb-3">
                                        <label class="form-label">Nhập Mã Key</label>
                                        <input type="text" class="form-control form-control-lg text-center" 
                                               name="key_code" 
                                               placeholder="XXXX-XXXX-XXXX-XXXX" 
                                               pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                               required>
                                        <small class="text-muted">Nhập key 16 ký tự (vd: A1B2-C3D4-E5F6-G7H8)</small>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-lg w-100">
                                        ⚡ Kích Hoạt Ngay
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Đăng ký -->
            <div class="tab-pane fade" id="orderTab" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Đăng Ký Premium (Chờ Admin Duyệt)</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="create_order">
                                    <div class="mb-3">
                                        <label class="form-label">Họ và Tên</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Chọn Gói Premium</label>
                                        <select class="form-select form-select-lg" name="package_id" required>
                                            <option value="">-- Chọn gói --</option>
                                            <?php foreach ($packages as $pkg): ?>
                                                <option value="<?php echo $pkg['package_id']; ?>">
                                                    <?php 
                                                    echo htmlspecialchars($pkg['name']) . ' - ' . number_format($pkg['price']) . ' VND';
                                                    if (isset($pkg['discount'])) {
                                                        echo ' (' . $pkg['discount'] . ')';
                                                    }
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Chọn gói phù hợp với nhu cầu của bạn</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ghi Chú (Tùy chọn)</label>
                                        <textarea class="form-control" name="notes" rows="2" 
                                                  placeholder="VD: Đã chuyển khoản vào tài khoản..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        📤 Gửi Đơn Đăng Ký
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Bảng giá -->
            <div class="tab-pane fade" id="packagesTab" role="tabpanel">
                <h4 class="text-center mb-4">So Sánh Gói Dịch Vụ</h4>
                
                <div class="row justify-content-center">
                    <!-- Gói FREE -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white text-center">
                                <h5 class="mb-0">FREE</h5>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-secondary mb-3">0 VND</h2>
                                <p class="text-muted">Dùng thử cơ bản</p>
                                <hr>
                                <ul class="list-unstyled text-start">
                                    <li class="mb-2">✅ Tạo đề thi (giới hạn)</li>
                                    <li class="mb-2">❌ Xuất đề + đáp án</li>
                                    <li class="mb-2">❌ Ma trận đề tự động</li>
                                    <li class="mb-2">❌ Thống kê nâng cao</li>
                                    <li class="mb-2">❌ Import từ Excel</li>
                                    <li class="mb-2">❌ Hỗ trợ ưu tiên</li>
                                    <li class="mb-2">❌ Công cụ: Xây Dựng Ma Trận</li>
                                    <li class="mb-2">❌ Nhận xét vnedu</li>
                                    <li class="mb-2">❌ Tạo đề không giới hạn</li>
                                </ul>
                                <div class="mt-3">
                                    <button class="btn btn-outline-secondary w-100" disabled>Gói Hiện Tại</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php 
                    // Hiển thị tất cả các gói Premium
                    $middleIndex = floor(count($packages) / 2);
                    $currentIndex = 0;
                    foreach ($packages as $pkg): 
                        $isPopular = ($currentIndex === $middleIndex && count($packages) > 1);
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 <?php echo $isPopular ? 'border-warning shadow' : 'border-primary'; ?>">
                                <?php if ($isPopular): ?>
                                    <div class="card-header bg-warning text-dark text-center">
                                        <strong>🌟 PHỔ BIẾN NHẤT</strong>
                                    </div>
                                <?php else: ?>
                                    <div class="card-header bg-primary text-white text-center">
                                        <h5 class="mb-0">PREMIUM</h5>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body text-center">
                                    <h4 class="card-title"><?php echo htmlspecialchars($pkg['name']); ?></h4>
                                    <h2 class="text-primary"><?php echo number_format($pkg['price']); ?> VND</h2>
                                    <?php if (isset($pkg['discount'])): ?>
                                        <p class="text-success"><strong><?php echo $pkg['discount']; ?></strong></p>
                                    <?php endif; ?>
                                    <p class="text-muted"><?php echo $pkg['duration_days']; ?> ngày</p>
                                    <hr>
                                    <ul class="list-unstyled text-start">
                                        <?php if (!empty($pkg['features'])): ?>
                                            <?php foreach ($pkg['features'] as $feature): ?>
                                            <li class="mb-2">✅ <?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="mb-2">✅ Tạo đề không giới hạn</li>
                                            <li class="mb-2">✅ Xuất đề + đáp án</li>
                                            <li class="mb-2">✅ Ma trận đề tự động</li>
                                            <li class="mb-2">✅ Thống kê nâng cao</li>
                                            <li class="mb-2">✅ Import từ Excel</li>
                                            <li class="mb-2">✅ Hỗ trợ ưu tiên</li>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="mt-3">
                                        <button class="btn btn-<?php echo $isPopular ? 'warning' : 'primary'; ?> w-100" 
                                                onclick="selectPackage(<?php echo $pkg['package_id']; ?>)">
                                            Chọn Gói Này
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $currentIndex++;
                    endforeach; ?>
                </div>

                <div class="alert alert-info mt-4">
                    <strong>💡 Lưu ý:</strong> Sau khi chọn gói, vui lòng chuyển sang tab "Đăng Ký Premium" để hoàn tất đăng ký.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tính năng Premium -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">🎁 Tính Năng Premium Của Bạn</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">✅ Tạo đề không giới hạn</li>
                            <li class="list-group-item">✅ Xuất đề + đáp án</li>
                            <li class="list-group-item">✅ Ma trận đề tự động</li>
                            <li class="list-group-item">✅ Công cụ: Xây Dựng Ma Trận</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">✅ Thống kê nâng cao</li>
                            <li class="list-group-item">✅ Import từ Excel</li>
                            <li class="list-group-item">✅ Hỗ trợ ưu tiên</li>
                            <li class="list-group-item">✅ Nhận xét vnedu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="../includes/toast-notifications.js"></script>
<script>
// Format key input with dashes
document.addEventListener('DOMContentLoaded', function() {
    const keyInput = document.querySelector('input[name="key_code"]');
    if (keyInput) {
        keyInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/g, '');
            let formatted = '';
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) formatted += '-';
                formatted += value[i];
            }
            e.target.value = formatted.toUpperCase();
        });
    }
});

// Function to select package and switch to order tab
function selectPackage(packageId) {
    // Switch to order tab
    const orderTab = document.getElementById('order-tab');
    const orderTabPane = new bootstrap.Tab(orderTab);
    orderTabPane.show();
    
    // Select the package in dropdown
    const packageSelect = document.querySelector('select[name="package_id"]');
    if (packageSelect) {
        packageSelect.value = packageId;
        packageSelect.focus();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
