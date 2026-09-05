<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'premium_pricing.php';

// Load pricing configuration
$pricingFile = __DIR__ . '/premium_pricing.json';
if (!file_exists($pricingFile)) {
    $defaultPricing = [
        'teacher' => [
            [
                'id' => 'teacher_1month',
                'name' => 'Premium 1 tháng',
                'duration_days' => 30,
                'price' => 50000,
                'currency' => 'VND',
                'features' => [
                    'Tạo đề không giới hạn',
                    'Xuất đề + đáp án',
                    'Ma trận đề tự động',
                    'Thống kê nâng cao',
                    'Import từ Excel'
                ],
                'is_active' => true
            ],
            [
                'id' => 'teacher_6months',
                'name' => 'Premium 6 tháng',
                'duration_days' => 180,
                'price' => 250000,
                'currency' => 'VND',
                'features' => [
                    'Tất cả tính năng gói 1 tháng',
                    'Hỗ trợ ưu tiên',
                    'Ngân hàng câu hỏi không giới hạn'
                ],
                'is_active' => true,
                'discount' => 17
            ],
            [
                'id' => 'teacher_1year',
                'name' => 'Premium 1 năm',
                'duration_days' => 365,
                'price' => 400000,
                'currency' => 'VND',
                'features' => [
                    'Tất cả tính năng gói 6 tháng',
                    'Backup dữ liệu tự động',
                    'API truy cập'
                ],
                'is_active' => true,
                'discount' => 33
            ]
        ]
    ];
    file_put_contents($pricingFile, json_encode($defaultPricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$pricing = json_decode(file_get_contents($pricingFile), true) ?: [];
$teacherPackages = $pricing['teacher'] ?? [];
$totalPackages = count($teacherPackages);
$activePackages = count(array_filter($teacherPackages, fn($p) => ($p['is_active'] ?? true)));
$inactivePackages = $totalPackages - $activePackages;

$minPrice = 0;
if (!empty($teacherPackages)) {
    $prices = array_map(fn($p) => (int)($p['price'] ?? 0), $teacherPackages);
    $minPrice = min($prices);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Giá Gói Premium - CVD Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700;1,9..144,500&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <link href="assets/admin-ui.css?v=20260806" rel="stylesheet">
    <link href="assets/admin-navbar.css?v=20260806" rel="stylesheet">
    <link href="assets/premium_pricing.css?v=20260806" rel="stylesheet">
</head>
<body class="admin-page">
    <?php include 'navbar.php'; ?>

    <main class="cvd-page">
        <header class="cvd-page-header">
            <div>
                <div class="cvd-eyebrow"><i class="bi bi-cash-coin"></i> Bảng giá & Dịch vụ</div>
                <h1>Quản lý Giá Premium</h1>
                <p class="cvd-sub">Thiết lập các gói dịch vụ, giá bán, thời hạn và chính sách ưu đãi dành cho giáo viên.</p>
            </div>
            <div class="cvd-page-actions">
                <a href="premium_management.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quản lý Premium
                </a>
                <a href="premium_config.php" class="btn btn-outline-secondary">
                    <i class="bi bi-sliders me-1"></i> Cấu hình dịch vụ
                </a>
                <button type="button" class="btn btn-primary" onclick="addPackage('teacher')">
                    <i class="bi bi-plus-lg me-1"></i> Thêm gói mới
                </button>
            </div>
        </header>

        <!-- Stats -->
        <section class="cvd-stats" aria-label="Thống kê gói dịch vụ">
            <div class="cvd-stat cvd-reveal">
                <span class="cvd-stat-icon"><i class="bi bi-tags-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $totalPackages; ?></div>
                    <div class="cvd-stat-label">Tổng số gói dịch vụ</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d1">
                <span class="cvd-stat-icon"><i class="bi bi-check-circle-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $activePackages; ?></div>
                    <div class="cvd-stat-label">Gói đang mở bán</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d2">
                <span class="cvd-stat-icon is-gold"><i class="bi bi-pause-circle-fill"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo $inactivePackages; ?></div>
                    <div class="cvd-stat-label">Gói tạm dừng mở bán</div>
                </div>
            </div>
            <div class="cvd-stat cvd-reveal cvd-reveal-d3">
                <span class="cvd-stat-icon is-accent"><i class="bi bi-currency-exchange"></i></span>
                <div>
                    <div class="cvd-stat-value"><?php echo number_format($minPrice); ?> <small style="font-size: 1rem; font-weight: normal;">đ</small></div>
                    <div class="cvd-stat-label">Mức giá khởi điểm</div>
                </div>
            </div>
        </section>

        <!-- Pricing Panel -->
        <section class="cvd-panel cvd-reveal cvd-reveal-d2">
            <div class="cvd-panel-header">
                <div>
                    <h2>Danh sách gói Premium dành cho Giáo viên</h2>
                    <p>Các gói được kích hoạt sẽ xuất hiện trong biểu phí đăng ký và nâng cấp tài khoản</p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addPackage('teacher')">
                    <i class="bi bi-plus-lg me-1"></i> Thêm gói mới
                </button>
            </div>

            <?php if (empty($teacherPackages)): ?>
                <div class="cvd-empty">
                    <i class="bi bi-tags"></i>
                    <p class="mb-0">Chưa có gói Premium nào. Hãy nhấn <strong>Thêm gói mới</strong> để tạo gói dịch vụ.</p>
                </div>
            <?php else: ?>
                <div class="pricing-grid">
                    <?php foreach ($teacherPackages as $package): 
                        $isActive = $package['is_active'] ?? true;
                        $discount = (int)($package['discount'] ?? 0);
                        $features = is_array($package['features'] ?? null) ? $package['features'] : [];
                    ?>
                    <div class="pricing-card <?php echo !$isActive ? 'is-inactive' : ''; ?>">
                        <?php if ($discount > 0): ?>
                            <div class="pricing-discount-badge">
                                <i class="bi bi-lightning-fill"></i> Giảm <?php echo $discount; ?>%
                            </div>
                        <?php endif; ?>

                        <div class="pricing-card-header">
                            <h3 class="pricing-card-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                            <div class="pricing-card-duration">
                                <i class="bi bi-calendar3"></i> Thời hạn: <strong><?php echo (int)$package['duration_days']; ?> ngày</strong>
                                <?php if (!$isActive): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Tạm dừng</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success-emphasis ms-1">Đang mở bán</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pricing-card-price-box">
                            <span class="pricing-card-price"><?php echo number_format($package['price']); ?></span>
                            <span class="pricing-card-currency">VNĐ</span>
                        </div>

                        <div class="pricing-features-title">Tính năng bao gồm:</div>
                        <ul class="pricing-features-list">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="bi bi-check2-circle"></i>
                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="pricing-card-actions">
                            <button type="button" class="btn btn-outline-primary flex-grow-1" 
                                    onclick="editPackage('teacher', '<?php echo htmlspecialchars($package['id']); ?>')">
                                <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
                            </button>
                            <button type="button" class="btn btn-outline-<?php echo $isActive ? 'warning' : 'success'; ?>" 
                                    onclick="togglePackage('teacher', '<?php echo htmlspecialchars($package['id']); ?>')"
                                    title="<?php echo $isActive ? 'Tạm dừng gói' : 'Kích hoạt lại gói'; ?>">
                                <i class="bi bi-<?php echo $isActive ? 'pause-fill' : 'play-fill'; ?>"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="deletePackage('teacher', '<?php echo htmlspecialchars($package['id']); ?>', '<?php echo htmlspecialchars(addslashes($package['name'])); ?>')"
                                    title="Xóa gói dịch vụ">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="cvd-footer-credit">
            Được phát triển & vận hành bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a>
        </div>
    </main>

    <!-- Edit/Add Package Modal -->
    <div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modalTitle">Chỉnh sửa gói Premium</h5>
                        <small class="text-muted">Cập nhật thông tin chi tiết gói dịch vụ</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form id="packageForm">
                        <input type="hidden" id="packageType" name="type" value="teacher">
                        <input type="hidden" id="packageId" name="id">
                        <input type="hidden" id="formAction" name="action" value="add">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label" for="packageName">Tên gói dịch vụ *</label>
                                <input type="text" class="form-control" id="packageName" name="name" placeholder="VD: Premium 6 tháng" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="packageDuration">Thời hạn (ngày) *</label>
                                <input type="number" class="form-control" id="packageDuration" name="duration_days" placeholder="VD: 180" required min="1">
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="packagePrice">Giá gói (VNĐ) *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="packagePrice" name="price" placeholder="VD: 250000" required min="0" step="1000">
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="packageDiscount">Ưu đãi giảm giá (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="packageDiscount" name="discount" min="0" max="100" value="0">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Tự động tính dựa trên gói 30 ngày (nếu có).</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="packageFeatures">Danh sách tính năng (Mỗi tính năng trên 1 dòng)</label>
                            <textarea class="form-control" id="packageFeatures" name="features" rows="5" 
                                placeholder="Tạo đề không giới hạn&#10;Xuất đề + đáp án Word/PDF&#10;Ma trận đề tự động&#10;Thống kê nâng cao"></textarea>
                        </div>
                        
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="packageActive" name="is_active" checked>
                            <label class="form-check-label fw-semibold" for="packageActive">
                                Mở bán gói này cho giáo viên
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="button" class="btn btn-primary" id="savePackageBtn" onclick="savePackage()">
                        <i class="bi bi-floppy me-1"></i> Lưu Thông Tin
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePackageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Xóa Gói Dịch Vụ</h5>
                        <small class="text-muted">Xác nhận xóa gói khỏi hệ thống</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa gói dịch vụ <strong id="deletePackageNameDisplay"></strong>?</p>
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i> Các tài khoản giáo viên đang sử dụng gói này trước đó vẫn được bảo lưu quyền sử dụng bình thường.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePackageBtn">
                        <i class="bi bi-trash3 me-1"></i> Xác Nhận Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/toast-notifications.js"></script>
    <script>
        let packageModal;
        let deleteModal;
        let pendingDeleteId = null;
        let pendingDeleteType = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            packageModal = new bootstrap.Modal(document.getElementById('packageModal'));
            deleteModal = new bootstrap.Modal(document.getElementById('deletePackageModal'));
            
            const priceInput = document.getElementById('packagePrice');
            const durationInput = document.getElementById('packageDuration');
            
            if (priceInput && durationInput) {
                priceInput.addEventListener('input', autoCalculateDiscount);
                durationInput.addEventListener('input', autoCalculateDiscount);
            }

            document.getElementById('confirmDeletePackageBtn').addEventListener('click', executeDeletePackage);
        });
        
        const baseMonthlyPrices = {
            'teacher': <?php 
                $teacherMonthly = 0;
                foreach ($teacherPackages as $pkg) {
                    if (($pkg['duration_days'] ?? 0) == 30) {
                        $teacherMonthly = (int)$pkg['price'];
                        break;
                    }
                }
                echo $teacherMonthly ?: 50000;
            ?>
        };
        
        function autoCalculateDiscount() {
            const type = document.getElementById('packageType').value;
            const price = parseFloat(document.getElementById('packagePrice').value) || 0;
            const durationDays = parseInt(document.getElementById('packageDuration').value) || 0;
            const monthlyPrice = baseMonthlyPrices[type] || 0;
            
            if (durationDays > 30 && monthlyPrice > 0 && price > 0) {
                const months = durationDays / 30;
                const originalPrice = monthlyPrice * months;
                if (originalPrice > price) {
                    const discount = ((originalPrice - price) / originalPrice * 100);
                    document.getElementById('packageDiscount').value = Math.max(0, Math.round(discount));
                }
            }
        }

        function addPackage(type) {
            document.getElementById('modalTitle').textContent = 'Thêm Gói Premium Mới';
            document.getElementById('packageForm').reset();
            document.getElementById('packageType').value = type;
            document.getElementById('packageId').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('packageActive').checked = true;
            packageModal.show();
        }

        async function editPackage(type, id) {
            try {
                const response = await fetch(`api/premium_pricing_api.php?action=get&type=${type}&id=${encodeURIComponent(id)}`);
                const result = await response.json();
                
                if (result.success) {
                    const pkg = result.data;
                    document.getElementById('modalTitle').textContent = 'Chỉnh Sửa Gói Premium';
                    document.getElementById('packageType').value = type;
                    document.getElementById('packageId').value = id;
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('packageName').value = pkg.name;
                    document.getElementById('packageDuration').value = pkg.duration_days;
                    document.getElementById('packagePrice').value = pkg.price;
                    document.getElementById('packageDiscount').value = pkg.discount || 0;
                    document.getElementById('packageFeatures').value = (pkg.features || []).join('\n');
                    document.getElementById('packageActive').checked = pkg.is_active;
                    packageModal.show();
                } else {
                    showErrorToast('Lỗi: ' + result.message);
                }
            } catch (error) {
                showErrorToast('Lỗi: ' + error.message);
            }
        }

        async function savePackage() {
            const form = document.getElementById('packageForm');
            const name = document.getElementById('packageName').value.trim();
            const duration = document.getElementById('packageDuration').value;
            const price = document.getElementById('packagePrice').value;

            if (!name || !duration || !price) {
                showErrorToast('Vui lòng điền đầy đủ các thông tin bắt buộc (*)');
                return;
            }

            const saveBtn = document.getElementById('savePackageBtn');
            saveBtn.disabled = true;

            const formData = new FormData(form);
            const features = document.getElementById('packageFeatures').value
                .split('\n')
                .map(f => f.trim())
                .filter(f => f.length > 0);
            
            formData.delete('features');
            formData.append('features', JSON.stringify(features));
            
            try {
                const response = await fetch('api/premium_pricing_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                saveBtn.disabled = false;
                
                if (result.success) {
                    showSuccessToast(result.message || 'Lưu gói Premium thành công!');
                    packageModal.hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast('Lỗi: ' + result.message);
                }
            } catch (error) {
                saveBtn.disabled = false;
                showErrorToast('Có lỗi xảy ra: ' + error.message);
            }
        }

        async function togglePackage(type, id) {
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('type', type);
            formData.append('id', id);
            
            try {
                const response = await fetch('api/premium_pricing_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast('Đã cập nhật trạng thái gói');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showErrorToast('Lỗi: ' + result.message);
                }
            } catch (error) {
                showErrorToast('Có lỗi xảy ra: ' + error.message);
            }
        }

        function deletePackage(type, id, name) {
            pendingDeleteType = type;
            pendingDeleteId = id;
            document.getElementById('deletePackageNameDisplay').textContent = name;
            deleteModal.show();
        }

        async function executeDeletePackage() {
            if (!pendingDeleteId || !pendingDeleteType) return;
            const btn = document.getElementById('confirmDeletePackageBtn');
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('type', pendingDeleteType);
            formData.append('id', pendingDeleteId);
            
            try {
                const response = await fetch('api/premium_pricing_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                btn.disabled = false;
                
                if (result.success) {
                    showSuccessToast(result.message || 'Đã xóa gói dịch vụ');
                    deleteModal.hide();
                    setTimeout(() => location.reload(), 600);
                } else {
                    showErrorToast('Lỗi: ' + result.message);
                }
            } catch (error) {
                btn.disabled = false;
                showErrorToast('Có lỗi xảy ra: ' + error.message);
            }
        }
    </script>
</body>
</html>
