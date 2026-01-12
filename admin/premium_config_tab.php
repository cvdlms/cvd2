<?php
$config = getSystemConfig();
$currentSemester = $config['semester']['current'] ?? 'hk2';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">⚙️ Cấu Hình Học Kì</h5>
            </div>
            <div class="card-body">
                <form id="semesterConfigForm">
                    <div class="mb-3">
                        <label class="form-label">Học Kì Hiện Tại</label>
                        <select class="form-select" name="current_semester" required>
                            <option value="hk1" <?php echo $currentSemester === 'hk1' ? 'selected' : ''; ?>>Học kì 1</option>
                            <option value="hk2" <?php echo $currentSemester === 'hk2' ? 'selected' : ''; ?>>Học kì 2</option>
                        </select>
                        <small class="text-muted">Dữ liệu học sinh và giáo viên sẽ lọc theo học kì này</small>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Lưu Cấu Hình</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">🎖️ Cấu Hình Premium</h5>
            </div>
            <div class="card-body">
                <form id="premiumConfigForm">
                    <div class="mb-3">
                        <label class="form-label">Trạng Thái Premium</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="premiumEnabled" name="premium_enabled"
                                   <?php echo ($config['premium']['enabled'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="premiumEnabled">
                                Kích hoạt hệ thống Premium
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tính Năng Premium</label>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" checked disabled>
                                Tạo đề không giới hạn
                            </li>
                            <li class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" checked disabled>
                                Xuất đề + đáp án
                            </li>
                            <li class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" checked disabled>
                                Ma trận đề tự động
                            </li>
                            <li class="list-group-item">
                                <input class="form-check-input me-2" type="checkbox" checked disabled>
                                Thống kê nâng cao
                            </li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Lưu Cấu Hình</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">🔒 Cấu Hình Bảo Mật</h5>
            </div>
            <div class="card-body">
                <form id="securityConfigForm">
                    <div class="mb-3">
                        <label class="form-label">Chống Xem Mã Nguồn (View Source)</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="disableViewSource" name="disable_view_source"
                                   <?php echo ($config['system']['disable_view_source'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="disableViewSource">
                                Chặn chuột phải, F12, Ctrl+U (khuyến nghị: BẬT khi production)
                            </label>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            TẮT khi đang phát triển/kiểm tra, BẬT khi triển khai thực tế
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Lưu Cấu Hình</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📊 Thống Kê Hệ Thống</h5>
            </div>
            <div class="card-body">
                <?php
                $stats = getPremiumStats();
                $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
                $totalUsers = count(json_decode(file_get_contents(__DIR__ . '/user.json'), true) ?: []);
                $premiumRate = $totalUsers > 0 ? round(($stats['total_active'] / $totalUsers) * 100, 1) : 0;
                ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-light text-center">
                            <div class="card-body">
                                <h3><?php echo $totalUsers; ?></h3>
                                <p class="mb-0">Tổng Giáo Viên</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white text-center">
                            <div class="card-body">
                                <h3><?php echo $stats['total_active']; ?></h3>
                                <p class="mb-0">Giáo Viên Premium</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white text-center">
                            <div class="card-body">
                                <h3><?php echo $premiumRate; ?>%</h3>
                                <p class="mb-0">Tỷ Lệ Premium</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white text-center">
                            <div class="card-body">
                                <h3><?php echo number_format($stats['total_revenue']); ?></h3>
                                <p class="mb-0">Doanh Thu (VND)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../includes/toast-notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Semester config form
    document.getElementById('semesterConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_semester');
        
        fetch('premium_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccessToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(data.message);
            }
        })
        .catch(err => {
            showErrorToast('Lỗi: ' + err.message);
        });
    });
    
    // Premium config form
    document.getElementById('premiumConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_premium_config');
        
        fetch('premium_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccessToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(data.message);
            }
        })
        .catch(err => {
            showErrorToast('Lỗi: ' + err.message);
        });
    });
    
    // Security config form
    document.getElementById('securityConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_security');
        
        fetch('premium_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccessToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(data.message);
            }
        })
        .catch(err => {
            showErrorToast('Lỗi: ' + err.message);
        });
    });
});
</script>
