<?php
$orders = json_decode(file_get_contents(PREMIUM_ORDERS_FILE), true) ?: [];
$packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];

// Sắp xếp: pending trước, sau đó theo thời gian tạo mới nhất
usort($orders, function($a, $b) {
    $statusOrder = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
    $scoreA = $statusOrder[$a['status'] ?? 'pending'] ?? 3;
    $scoreB = $statusOrder[$b['status'] ?? 'pending'] ?? 3;
    if ($scoreA !== $scoreB) return $scoreA - $scoreB;
    return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
});

if (!function_exists('orderTeacherInitials')) {
    function orderTeacherInitials(string $name): string {
        $parts = preg_split('/\s+/u', trim($name));
        if (!$parts) return 'GV';
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
        return mb_strtoupper($first . $last, 'UTF-8');
    }
}

$totalOrders = count($orders);
$pendingCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));
$approvedCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'approved'));
$rejectedCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'rejected'));
?>

<section class="cvd-panel cvd-reveal cvd-reveal-d2">
    <div class="cvd-panel-header">
        <div>
            <h2>Đơn đăng ký mua gói Premium</h2>
            <p>Xử lý và phê duyệt các yêu cầu đăng ký mua gói hoặc gia hạn từ giáo viên</p>
        </div>
        <?php if ($pendingCount > 0): ?>
            <span class="badge bg-warning-subtle text-warning-emphasis">
                <i class="bi bi-hourglass-split me-1"></i><?php echo $pendingCount; ?> đơn chờ duyệt
            </span>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success-emphasis">
                <i class="bi bi-check-all me-1"></i>Đã xử lý hết đơn
            </span>
        <?php endif; ?>
    </div>

    <!-- Filter & Search Bar -->
    <div class="cvd-filter-bar">
        <div class="cvd-filter-group" id="orderFilterGroup">
            <button type="button" class="cvd-filter-btn active" data-order-filter="all">
                Tất cả <span class="badge bg-secondary-subtle"><?php echo $totalOrders; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-order-filter="pending">
                Chờ duyệt <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo $pendingCount; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-order-filter="approved">
                Đã duyệt <span class="badge bg-success-subtle text-success-emphasis"><?php echo $approvedCount; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-order-filter="rejected">
                Từ chối <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo $rejectedCount; ?></span>
            </button>
        </div>
        <div class="cvd-search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="orderSearchInput" placeholder="Tìm theo tên, email, mã đơn...">
        </div>
    </div>

    <div class="cvd-table-wrap">
        <?php if (empty($orders)): ?>
            <div class="cvd-empty">
                <i class="bi bi-inbox"></i>
                <p class="mb-0">Chưa có đơn đăng ký Premium nào trong hệ thống.</p>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0 w-100" id="ordersTable">
                <thead>
                    <tr>
                        <th>Mã Đơn & Thời Gian</th>
                        <th>Giáo Viên</th>
                        <th>Gói Dịch Vụ & Giá</th>
                        <th>Ghi Chú Của GV</th>
                        <th>Trạng Thái</th>
                        <th class="text-end">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $orderId = $order['order_id'] ?? '';
                        $username = $order['username'] ?? '';
                        $fullname = $order['fullname'] ?? $username;
                        $email = $order['email'] ?? '';
                        $packageName = $order['package_name'] ?? 'Gói Premium';
                        $price = (int)($order['price'] ?? 0);
                        $status = $order['status'] ?? 'pending';
                        $initials = orderTeacherInitials($fullname);
                        $isYearly = (isset($order['package_id']) && $order['package_id'] == 3) || (stripos($packageName, 'năm') !== false);
                    ?>
                    <tr class="order-row"
                        data-status="<?php echo htmlspecialchars($status); ?>"
                        data-search="<?php echo htmlspecialchars(mb_strtolower($fullname . ' ' . $username . ' ' . $email . ' ' . $orderId . ' ' . $packageName)); ?>">
                        <td>
                            <div class="cvd-order-id"><?php echo htmlspecialchars($orderId); ?></div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo !empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '—'; ?>
                            </small>
                        </td>
                        <td>
                            <div class="cvd-teacher-cell">
                                <span class="cvd-teacher-avatar"><?php echo htmlspecialchars($initials); ?></span>
                                <div>
                                    <div class="cvd-teacher-name"><?php echo htmlspecialchars($fullname); ?></div>
                                    <div class="cvd-teacher-username">@<?php echo htmlspecialchars($username); ?><?php if ($email) echo ' · ' . htmlspecialchars($email); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cvd-pkg-badge <?php echo $isYearly ? 'is-yearly' : ''; ?>">
                                <i class="bi <?php echo $isYearly ? 'bi-stars' : 'bi-patch-check'; ?>"></i>
                                <?php echo htmlspecialchars($packageName); ?>
                            </span>
                            <div class="cvd-price-tag mt-1">
                                <?php echo number_format($price); ?> <small class="text-muted fw-normal">VNĐ</small>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($order['notes'])): ?>
                                <div class="cvd-note-box">
                                    <i class="bi bi-chat-left-text me-1 text-muted"></i><?php echo htmlspecialchars($order['notes']); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'pending'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis">
                                    <i class="bi bi-hourglass-split me-1"></i>Chờ duyệt
                                </span>
                            <?php elseif ($status === 'approved'): ?>
                                <div>
                                    <span class="badge bg-success-subtle text-success-emphasis">
                                        <i class="bi bi-check-circle-fill me-1"></i>Đã duyệt
                                    </span>
                                    <?php if (!empty($order['admin_note'])): ?>
                                        <div class="cvd-note-box mt-1 text-muted">
                                            <i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($order['admin_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($status === 'rejected'): ?>
                                <div>
                                    <span class="badge bg-danger-subtle text-danger-emphasis">
                                        <i class="bi bi-x-circle-fill me-1"></i>Từ chối
                                    </span>
                                    <?php if (!empty($order['admin_note'])): ?>
                                        <div class="cvd-note-box mt-1 text-danger">
                                            <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($order['admin_note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle"><?php echo htmlspecialchars($status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($status === 'pending'): ?>
                                <div class="cvd-table-actions">
                                    <button type="button" class="btn btn-sm btn-success approve-order-btn" 
                                            data-order-id="<?php echo htmlspecialchars($orderId); ?>"
                                            data-username="<?php echo htmlspecialchars($username); ?>"
                                            data-fullname="<?php echo htmlspecialchars($fullname); ?>"
                                            data-package="<?php echo htmlspecialchars($packageName); ?>"
                                            data-price="<?php echo number_format($price); ?>"
                                            title="Duyệt đơn này">
                                        <i class="bi bi-check-lg me-1"></i> Duyệt
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger reject-order-btn" 
                                            data-order-id="<?php echo htmlspecialchars($orderId); ?>"
                                            data-fullname="<?php echo htmlspecialchars($fullname); ?>"
                                            title="Từ chối đơn">
                                        <i class="bi bi-x-lg me-1"></i> Từ chối
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="small text-muted">
                                    <?php echo !empty($order['processed_at']) ? date('d/m/Y H:i', strtotime($order['processed_at'])) : 'Đã xử lý'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="orderNoResult" class="cvd-empty d-none">
                <i class="bi bi-search"></i>
                <p class="mb-0">Không tìm thấy đơn đăng ký nào phù hợp.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Duyệt Đơn -->
<div class="modal fade" id="approveOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-success"><i class="bi bi-check-circle-fill me-2"></i>Duyệt Đơn Đăng Ký</h5>
                    <small class="text-muted">Kích hoạt gói Premium cho giáo viên</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="approveOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="approve_order_id">
                    <input type="hidden" name="username" id="approve_username">

                    <div class="card bg-light border-0 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Giáo viên:</span>
                            <strong id="approve_fullname"></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Gói đăng ký:</span>
                            <span class="fw-semibold" id="approve_package_name"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Số tiền:</span>
                            <strong class="text-success" id="approve_price"></strong>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Ghi Chú Phản Hồi (Tùy chọn)</label>
                        <textarea class="form-control" name="admin_note" rows="2" placeholder="VD: Đã xác nhận thanh toán chuyển khoản ngân hàng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Xác Nhận Duyệt Đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Từ Chối Đơn -->
<div class="modal fade" id="rejectOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle-fill me-2"></i>Từ Chối Đơn Đăng Ký</h5>
                    <small class="text-muted">Không chấp thuận yêu cầu mua gói</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="rejectOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="reject_order_id">

                    <div class="alert alert-danger py-2 mb-3">
                        <div>Từ chối đơn đăng ký của giáo viên: <strong id="reject_fullname"></strong></div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Lý Do Từ Chối *</label>
                        <textarea class="form-control" name="admin_note" rows="3" placeholder="VD: Chưa nhận được thanh toán chuyển khoản, thông tin không khớp..." required></textarea>
                        <small class="text-muted">Lý do này sẽ hiển thị để giáo viên nắm được thông tin.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i> Xác Nhận Từ Chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter for Orders table
    const orderSearchInput = document.getElementById('orderSearchInput');
    const orderFilterBtns = document.querySelectorAll('#orderFilterGroup [data-order-filter]');
    const orderRows = document.querySelectorAll('#ordersTable .order-row');
    const orderNoResult = document.getElementById('orderNoResult');
    let currentOrderFilter = 'all';

    function applyOrderFilters() {
        const query = (orderSearchInput ? orderSearchInput.value : '').toLowerCase().trim();
        let visibleCount = 0;

        orderRows.forEach(row => {
            const searchData = row.dataset.search || '';
            const status = row.dataset.status || '';
            
            const matchSearch = query === '' || searchData.includes(query);
            let matchFilter = (currentOrderFilter === 'all') || (status === currentOrderFilter);

            if (matchSearch && matchFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (orderNoResult) {
            if (visibleCount === 0 && orderRows.length > 0) {
                orderNoResult.classList.remove('d-none');
            } else {
                orderNoResult.classList.add('d-none');
            }
        }
    }

    if (orderSearchInput) {
        orderSearchInput.addEventListener('input', applyOrderFilters);
    }

    orderFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            orderFilterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentOrderFilter = this.dataset.orderFilter;
            applyOrderFilters();
        });
    });

    // Approve button trigger
    document.querySelectorAll('.approve-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('approve_order_id').value = this.dataset.orderId;
            document.getElementById('approve_username').value = this.dataset.username;
            document.getElementById('approve_fullname').textContent = this.dataset.fullname;
            document.getElementById('approve_package_name').textContent = this.dataset.package;
            document.getElementById('approve_price').textContent = this.dataset.price + ' VNĐ';
            new bootstrap.Modal(document.getElementById('approveOrderModal')).show();
        });
    });
    
    // Reject button trigger
    document.querySelectorAll('.reject-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject_order_id').value = this.dataset.orderId;
            document.getElementById('reject_fullname').textContent = this.dataset.fullname;
            new bootstrap.Modal(document.getElementById('rejectOrderModal')).show();
        });
    });
    
    // Approve form submit
    const approveOrderForm = document.getElementById('approveOrderForm');
    if (approveOrderForm) {
        approveOrderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const formData = new FormData(this);
            formData.append('action', 'approve_order');
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message || 'Đã duyệt đơn hàng thành công');
                    bootstrap.Modal.getInstance(document.getElementById('approveOrderModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast(data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                showErrorToast('Lỗi kết nối: ' + err.message);
            });
        });
    }
    
    // Reject form submit
    const rejectOrderForm = document.getElementById('rejectOrderForm');
    if (rejectOrderForm) {
        rejectOrderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const formData = new FormData(this);
            formData.append('action', 'reject_order');
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message || 'Đã từ chối đơn hàng');
                    bootstrap.Modal.getInstance(document.getElementById('rejectOrderModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast(data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                showErrorToast('Lỗi kết nối: ' + err.message);
            });
        });
    }
});
</script>
