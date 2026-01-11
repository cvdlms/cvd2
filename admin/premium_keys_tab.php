<?php
$packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];
$keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];

// Thống kê
$totalKeys = count($keys);
$unusedKeys = count(array_filter($keys, fn($k) => $k['status'] === 'unused'));
$usedKeys = count(array_filter($keys, fn($k) => $k['status'] === 'used'));
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Quản Lý Key Kích Hoạt</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKeyModal">
            ➕ Tạo Key Mới
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3><?php echo $totalKeys; ?></h3>
                        <p class="mb-0">Tổng số key</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?php echo $unusedKeys; ?></h3>
                        <p class="mb-0">Chưa sử dụng</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h3><?php echo $usedKeys; ?></h3>
                        <p class="mb-0">Đã sử dụng</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($keys)): ?>
            <div class="alert alert-info">Chưa có key nào được tạo.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Mã Key</th>
                            <th>Gói Premium</th>
                            <th>Trạng Thái</th>
                            <th>Ngày Tạo</th>
                            <th>Được Dùng Bởi</th>
                            <th>Ngày Sử Dụng</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keys as $key): 
                            $package = array_filter($packages, fn($p) => $p['package_id'] == $key['package_id']);
                            $packageName = !empty($package) ? reset($package)['name'] : 'N/A';
                            $statusClass = [
                                'unused' => 'success',
                                'used' => 'secondary',
                                'revoked' => 'danger'
                            ][$key['status']] ?? 'secondary';
                            $statusLabel = [
                                'unused' => 'Chưa dùng',
                                'used' => 'Đã dùng',
                                'revoked' => 'Đã thu hồi'
                            ][$key['status']] ?? $key['status'];
                        ?>
                        <tr>
                            <td>
                                <code><?php echo htmlspecialchars($key['key_code']); ?></code>
                                <button class="btn btn-sm btn-outline-secondary copy-key" data-key="<?php echo $key['key_code']; ?>" title="Copy">
                                    📋
                                </button>
                            </td>
                            <td><?php echo htmlspecialchars($packageName); ?></td>
                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($key['created_at'])); ?></td>
                            <td><?php echo $key['used_by'] ? htmlspecialchars($key['used_by']) : '-'; ?></td>
                            <td><?php echo $key['used_at'] ? date('d/m/Y H:i', strtotime($key['used_at'])) : '-'; ?></td>
                            <td>
                                <?php if ($key['status'] === 'unused'): ?>
                                    <button class="btn btn-sm btn-danger revoke-key-btn" 
                                            data-key-id="<?php echo $key['key_id']; ?>"
                                            data-key-code="<?php echo htmlspecialchars($key['key_code']); ?>">
                                        🚫 Thu hồi
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tạo Key -->
<div class="modal fade" id="createKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo Key Kích Hoạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createKeyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn Gói Premium</label>
                        <select class="form-select" name="package_id" required>
                            <option value="">-- Chọn gói --</option>
                            <?php 
                            // Chỉ hiển thị gói 6 tháng và 1 năm
                            foreach ($packages as $pkg): 
                                if ($pkg['package_id'] == 2 || $pkg['package_id'] == 3):
                            ?>
                                <option value="<?php echo $pkg['package_id']; ?>">
                                    <?php 
                                    echo htmlspecialchars($pkg['name']) . ' - ' . number_format($pkg['price']) . ' VND';
                                    if ($pkg['package_id'] == 3) {
                                        echo ' ⭐ PHỔ BIẾN';
                                    }
                                    ?>
                                </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Key</label>
                        <input type="number" class="form-control" name="quantity" min="1" max="100" value="1" required>
                        <small class="text-muted">Tối đa 100 key/lần</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thu hồi Key -->
<div class="modal fade" id="revokeKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">⚠️ Xác Nhận Thu Hồi Key</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc chắn muốn thu hồi key này?</p>
                <p class="text-muted small mt-2">Key: <strong id="revoke_key_code"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmRevokeBtn">Xác Nhận Thu Hồi</button>
            </div>
        </div>
    </div>
</div>

<script src="../includes/toast-notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy key
    document.querySelectorAll('.copy-key').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            copyToClipboard(key, 'Đã copy key: ' + key);
        });
    });
    
    // Create key form
    document.getElementById('createKeyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create_keys');
        
        fetch('premium_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccessToast(`Đã tạo ${data.keys.length} key thành công!`);
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast('Lỗi: ' + data.message);
            }
        });
    });
    
    // Revoke key - Hiển thị modal xác nhận
    let selectedKeyId = null;
    document.querySelectorAll('.revoke-key-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedKeyId = this.dataset.keyId;
            const keyCode = this.dataset.keyCode || 'N/A';
            document.getElementById('revoke_key_code').textContent = keyCode;
            new bootstrap.Modal(document.getElementById('revokeKeyModal')).show();
        });
    });
    
    // Xác nhận thu hồi key
    document.getElementById('confirmRevokeBtn').addEventListener('click', function() {
        if (!selectedKeyId) return;
        
        const formData = new FormData();
        formData.append('action', 'revoke_key');
        formData.append('key_id', selectedKeyId);
        
        fetch('premium_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccessToast(data.message);
                bootstrap.Modal.getInstance(document.getElementById('revokeKeyModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(data.message);
            }
        });
    });
});
</script>
