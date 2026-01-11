<?php
$subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
$users = json_decode(file_get_contents(__DIR__ . '/user.json'), true) ?: [];

// Sắp xếp theo ngày hết hạn
usort($subscriptions, function($a, $b) {
    return strtotime($a['end_date']) - strtotime($b['end_date']);
});
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Danh Sách Tài Khoản Premium</h5>
    </div>
    <div class="card-body">
        <?php if (empty($subscriptions)): ?>
            <div class="alert alert-info">Chưa có tài khoản Premium nào.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Username</th>
                            <th>Họ Tên</th>
                            <th>Gói Premium</th>
                            <th>Ngày Bắt Đầu</th>
                            <th>Ngày Hết Hạn</th>
                            <th>Còn Lại</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): 
                            $user = $users[$sub['username']] ?? null;
                            $fullname = $user ? $user['fullname'] : 'N/A';
                            $daysRemaining = getPremiumDaysRemaining($sub['username']);
                            $statusClass = $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'expired' ? 'danger' : 'secondary');
                            $statusLabel = ['active' => 'Đang hoạt động', 'expired' => 'Hết hạn', 'revoked' => 'Đã thu hồi'][$sub['status']] ?? $sub['status'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['username']); ?></td>
                            <td><?php echo htmlspecialchars($fullname); ?></td>
                            <td><?php echo htmlspecialchars($sub['package_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($sub['start_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($sub['end_date'])); ?></td>
                            <td>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <span class="badge bg-<?php echo $daysRemaining <= 7 ? 'warning' : 'info'; ?>">
                                        <?php echo $daysRemaining; ?> ngày
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                            <td>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-primary extend-btn" data-username="<?php echo $sub['username']; ?>" data-subscription-id="<?php echo $sub['subscription_id']; ?>">
                                        ➕ Gia hạn
                                    </button>
                                    <button class="btn btn-sm btn-danger revoke-btn" data-username="<?php echo $sub['username']; ?>" data-subscription-id="<?php echo $sub['subscription_id']; ?>">
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

<!-- Modal Gia hạn -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gia Hạn Premium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="extendForm">
                <div class="modal-body">
                    <input type="hidden" name="username" id="extend_username">
                    <div class="mb-3">
                        <label class="form-label">Số Ngày Gia Hạn</label>
                        <input type="number" class="form-control" name="days" min="1" value="30" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Gia Hạn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thu hồi -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thu Hồi Premium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revokeForm">
                <div class="modal-body">
                    <input type="hidden" name="username" id="revoke_username">
                    <div class="mb-3">
                        <label class="form-label">Lý Do Thu Hồi</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Cảnh báo:</strong> Hành động này sẽ tức thì thu hồi quyền Premium của giáo viên.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác Nhận Thu Hồi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../includes/toast-notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Extend button
    document.querySelectorAll('.extend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.dataset.username;
            document.getElementById('extend_username').value = username;
            new bootstrap.Modal(document.getElementById('extendModal')).show();
        });
    });
    
    // Revoke button
    document.querySelectorAll('.revoke-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.dataset.username;
            document.getElementById('revoke_username').value = username;
            new bootstrap.Modal(document.getElementById('revokeModal')).show();
        });
    });
    
    // Extend form submit
    document.getElementById('extendForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'extend');
        
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
        });
    });
    
    // Revoke form submit
    document.getElementById('revokeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'revoke');
        
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
        });
    });
});
</script>
