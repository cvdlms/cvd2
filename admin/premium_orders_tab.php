<?php
$orders = json_decode(file_get_contents(PREMIUM_ORDERS_FILE), true) ?: [];
$packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];

// Sắp xếp: pending trước
usort($orders, function($a, $b) {
    $statusOrder = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
    return ($statusOrder[$a['status']] ?? 3) - ($statusOrder[$b['status']] ?? 3);
});
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Đơn Đăng Ký Premium</h5>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">Chưa có đơn đăng ký nào.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Họ Tên</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Gói Premium</th>
                            <th>Giá</th>
                            <th>Ngày Tạo</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $statusClass = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ][$order['status']] ?? 'secondary';
                            $statusLabel = [
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Từ chối'
                            ][$order['status']] ?? $order['status'];
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($order['order_id']); ?></code></td>
                            <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo htmlspecialchars($order['package_name']); ?></td>
                            <td><?php echo number_format($order['price']); ?> VND</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                            <td>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success approve-order-btn" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-username="<?php echo $order['username']; ?>"
                                            data-fullname="<?php echo $order['fullname']; ?>">
                                        ✅ Duyệt
                                    </button>
                                    <button class="btn btn-sm btn-danger reject-order-btn" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-fullname="<?php echo $order['fullname']; ?>">
                                        ❌
                                    </button>
                                <?php else: ?>
                                    <?php if (isset($order['admin_note'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['admin_note']); ?></small>
                                    <?php endif; ?>
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

<!-- Modal Duyệt Đơn -->
<div class="modal fade" id="approveOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Duyệt Đơn Đăng Ký</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="approve_order_id">
                    <input type="hidden" name="username" id="approve_username">
                    <div class="alert alert-success">
                        <strong>Xác nhận duyệt đơn cho:</strong> <span id="approve_fullname"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú (Tùy chọn)</label>
                        <textarea class="form-control" name="admin_note" rows="2" placeholder="VD: Đã thanh toán chuyển khoản"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Xác Nhận Duyệt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Từ Chối Đơn -->
<div class="modal fade" id="rejectOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Từ Chối Đơn Đăng Ký</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="reject_order_id">
                    <div class="alert alert-danger">
                        <strong>Xác nhận từ chối đơn cho:</strong> <span id="reject_fullname"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý Do Từ Chối (Bắt buộc)</label>
                        <textarea class="form-control" name="admin_note" rows="2" placeholder="VD: Chưa thanh toán" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác Nhận Từ Chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../includes/toast-notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve button
    document.querySelectorAll('.approve-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('approve_order_id').value = this.dataset.orderId;
            document.getElementById('approve_username').value = this.dataset.username;
            document.getElementById('approve_fullname').textContent = this.dataset.fullname;
            new bootstrap.Modal(document.getElementById('approveOrderModal')).show();
        });
    });
    
    // Reject button
    document.querySelectorAll('.reject-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject_order_id').value = this.dataset.orderId;
            document.getElementById('reject_fullname').textContent = this.dataset.fullname;
            new bootstrap.Modal(document.getElementById('rejectOrderModal')).show();
        });
    });
    
    // Approve form submit
    document.getElementById('approveOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'approve_order');
        
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
    
    // Reject form submit
    document.getElementById('rejectOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'reject_order');
        
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
