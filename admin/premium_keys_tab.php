<?php
$packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];
$keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];

// Sắp xếp: Key mới tạo lên đầu
usort($keys, function($a, $b) {
    return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
});

// Thống kê số lượng
$totalKeys = count($keys);
$unusedKeys = count(array_filter($keys, fn($k) => ($k['status'] ?? '') === 'unused'));
$usedKeys = count(array_filter($keys, fn($k) => ($k['status'] ?? '') === 'used'));
$revokedKeys = count(array_filter($keys, fn($k) => ($k['status'] ?? '') === 'revoked'));
?>

<section class="cvd-panel cvd-reveal cvd-reveal-d2">
    <div class="cvd-panel-header">
        <div>
            <h2>Kho mã kích hoạt (Activation Keys)</h2>
            <p>Quản lý và cấp phát mã kích hoạt gói dịch vụ Premium cho giáo viên</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKeyModal">
            <i class="bi bi-plus-lg me-1"></i> Tạo Key Mới
        </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="cvd-filter-bar">
        <div class="cvd-filter-group" id="keyFilterGroup">
            <button type="button" class="cvd-filter-btn active" data-key-filter="all">
                Tất cả <span class="badge bg-secondary-subtle"><?php echo $totalKeys; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-key-filter="unused">
                Chưa dùng <span class="badge bg-success-subtle text-success-emphasis"><?php echo $unusedKeys; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-key-filter="used">
                Đã dùng <span class="badge bg-secondary-subtle"><?php echo $usedKeys; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-key-filter="revoked">
                Đã thu hồi <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo $revokedKeys; ?></span>
            </button>
        </div>
        <div class="cvd-search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="keySearchInput" placeholder="Tìm theo mã key, người dùng...">
        </div>
    </div>

    <div class="cvd-table-wrap">
        <?php if (empty($keys)): ?>
            <div class="cvd-empty">
                <i class="bi bi-key"></i>
                <p class="mb-0">Chưa có mã kích hoạt nào được tạo.</p>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0 w-100" id="keysTable">
                <thead>
                    <tr>
                        <th>Mã Kích Hoạt</th>
                        <th>Gói Áp Dụng</th>
                        <th>Trạng Thái</th>
                        <th>Ngày Tạo</th>
                        <th>Người Sử Dụng</th>
                        <th class="text-end">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys as $key): 
                        $pkgFound = array_filter($packages, fn($p) => ($p['package_id'] ?? 0) == ($key['package_id'] ?? 0));
                        $pkg = !empty($pkgFound) ? reset($pkgFound) : null;
                        $packageName = $pkg ? $pkg['name'] : 'Gói Premium';
                        $isYearly = (isset($key['package_id']) && $key['package_id'] == 3) || (stripos($packageName, 'năm') !== false);
                        $status = $key['status'] ?? 'unused';
                    ?>
                    <tr class="key-row" 
                        data-status="<?php echo htmlspecialchars($status); ?>"
                        data-search="<?php echo htmlspecialchars(mb_strtolower(($key['key_code'] ?? '') . ' ' . ($key['used_by'] ?? '') . ' ' . $packageName)); ?>">
                        <td>
                            <div class="cvd-key-box">
                                <span class="cvd-key-code user-select-all"><?php echo htmlspecialchars($key['key_code']); ?></span>
                                <button type="button" class="cvd-copy-btn copy-key-btn" 
                                        data-key="<?php echo htmlspecialchars($key['key_code']); ?>" 
                                        title="Sao chép mã key">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="cvd-pkg-badge <?php echo $isYearly ? 'is-yearly' : ''; ?>">
                                <i class="bi <?php echo $isYearly ? 'bi-stars' : 'bi-patch-check'; ?>"></i>
                                <?php echo htmlspecialchars($packageName); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($status === 'unused'): ?>
                                <span class="badge bg-success-subtle text-success-emphasis">
                                    <i class="bi bi-check-circle-fill me-1"></i>Chưa sử dụng
                                </span>
                            <?php elseif ($status === 'used'): ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    <i class="bi bi-person-check-fill me-1"></i>Đã kích hoạt
                                </span>
                            <?php elseif ($status === 'revoked'): ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis">
                                    <i class="bi bi-slash-circle-fill me-1"></i>Đã thu hồi
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle"><?php echo htmlspecialchars($status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted">
                                <?php echo !empty($key['created_at']) ? date('d/m/Y H:i', strtotime($key['created_at'])) : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($key['used_by'])): ?>
                                <div>
                                    <strong class="text-dark">@<?php echo htmlspecialchars($key['used_by']); ?></strong>
                                    <small class="text-muted d-block"><?php echo !empty($key['used_at']) ? date('d/m/Y H:i', strtotime($key['used_at'])) : ''; ?></small>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($status === 'unused'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger revoke-key-btn" 
                                        data-key-id="<?php echo htmlspecialchars($key['key_id']); ?>"
                                        data-key-code="<?php echo htmlspecialchars($key['key_code']); ?>"
                                        title="Thu hồi key này">
                                    <i class="bi bi-trash3 me-1"></i> Thu hồi
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="keyNoResult" class="cvd-empty d-none">
                <i class="bi bi-search"></i>
                <p class="mb-0">Không tìm thấy mã kích hoạt nào phù hợp.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Tạo Key -->
<div class="modal fade" id="createKeyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="bi bi-key-fill text-primary me-2"></i>Tạo Key Kích Hoạt Mới</h5>
                    <small class="text-muted">Sinh mã kích hoạt cho các gói Premium</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="createKeyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn Gói Dịch Vụ *</label>
                        <select class="form-select" name="package_id" required>
                            <option value="">-- Chọn gói Premium --</option>
                            <?php foreach ($packages as $pkg): ?>
                                <option value="<?php echo $pkg['package_id']; ?>">
                                    <?php 
                                    echo htmlspecialchars($pkg['name']) . ' · ' . number_format($pkg['price']) . ' VNĐ';
                                    if (!empty($pkg['discount'])) {
                                        echo ' (' . htmlspecialchars($pkg['discount']) . ')';
                                    }
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Key Cần Tạo *</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-key-qty" data-qty="1">1 key</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-key-qty" data-qty="5">5 key</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-key-qty" data-qty="10">10 key</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-key-qty" data-qty="20">20 key</button>
                        </div>
                        <input type="number" class="form-control" name="quantity" id="keyQuantityInput" min="1" max="100" value="1" required>
                        <small class="text-muted">Tối đa tạo 100 key trong một lần.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Sinh Mã Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thu hồi Key -->
<div class="modal fade" id="revokeKeyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Thu Hồi Mã Key</h5>
                    <small class="text-muted">Hủy kích hoạt mã key khả dụng</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn thu hồi mã kích hoạt này?</p>
                <div class="p-3 bg-light rounded text-center my-3 border">
                    <span class="cvd-key-code text-danger fs-5" id="revoke_key_code_display"></span>
                </div>
                <div class="alert alert-warning py-2 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i> Sau khi thu hồi, mã này sẽ không thể sử dụng để kích hoạt Premium được nữa.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger" id="confirmRevokeKeyBtn">
                    <i class="bi bi-trash3 me-1"></i> Xác Nhận Thu Hồi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter for Keys table
    const keySearchInput = document.getElementById('keySearchInput');
    const keyFilterBtns = document.querySelectorAll('#keyFilterGroup [data-key-filter]');
    const keyRows = document.querySelectorAll('#keysTable .key-row');
    const keyNoResult = document.getElementById('keyNoResult');
    let currentKeyFilter = 'all';

    function applyKeyFilters() {
        const query = (keySearchInput ? keySearchInput.value : '').toLowerCase().trim();
        let visibleCount = 0;

        keyRows.forEach(row => {
            const searchData = row.dataset.search || '';
            const status = row.dataset.status || '';
            
            const matchSearch = query === '' || searchData.includes(query);
            let matchFilter = (currentKeyFilter === 'all') || (status === currentKeyFilter);

            if (matchSearch && matchFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (keyNoResult) {
            if (visibleCount === 0 && keyRows.length > 0) {
                keyNoResult.classList.remove('d-none');
            } else {
                keyNoResult.classList.add('d-none');
            }
        }
    }

    if (keySearchInput) {
        keySearchInput.addEventListener('input', applyKeyFilters);
    }

    keyFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            keyFilterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentKeyFilter = this.dataset.keyFilter;
            applyKeyFilters();
        });
    });

    // Copy key button
    document.querySelectorAll('.copy-key-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            const originalHTML = this.innerHTML;
            copyToClipboard(key, 'Đã sao chép mã: ' + key);
            this.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(() => {
                this.innerHTML = originalHTML;
            }, 1500);
        });
    });

    // Preset quantity buttons
    document.querySelectorAll('.preset-key-qty').forEach(btn => {
        btn.addEventListener('click', function() {
            const qty = this.dataset.qty;
            const input = document.getElementById('keyQuantityInput');
            if (input) input.value = qty;
        });
    });

    // Create key form
    const createKeyForm = document.getElementById('createKeyForm');
    if (createKeyForm) {
        createKeyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const formData = new FormData(this);
            formData.append('action', 'create_keys');
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message || 'Đã tạo key thành công!');
                    bootstrap.Modal.getInstance(document.getElementById('createKeyModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast('Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                showErrorToast('Lỗi kết nối: ' + err.message);
            });
        });
    }

    // Revoke key modal trigger
    let selectedKeyId = null;
    document.querySelectorAll('.revoke-key-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedKeyId = this.dataset.keyId;
            const keyCode = this.dataset.keyCode || 'N/A';
            document.getElementById('revoke_key_code_display').textContent = keyCode;
            new bootstrap.Modal(document.getElementById('revokeKeyModal')).show();
        });
    });

    // Confirm revoke key
    const confirmRevokeKeyBtn = document.getElementById('confirmRevokeKeyBtn');
    if (confirmRevokeKeyBtn) {
        confirmRevokeKeyBtn.addEventListener('click', function() {
            if (!selectedKeyId) return;
            this.disabled = true;

            const formData = new FormData();
            formData.append('action', 'revoke_key');
            formData.append('key_id', selectedKeyId);
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                confirmRevokeKeyBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message || 'Đã thu hồi key thành công');
                    bootstrap.Modal.getInstance(document.getElementById('revokeKeyModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast(data.message);
                }
            })
            .catch(err => {
                confirmRevokeKeyBtn.disabled = false;
                showErrorToast('Lỗi kết nối: ' + err.message);
            });
        });
    }
});
</script>
