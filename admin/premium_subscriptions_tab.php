<?php
$subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
$users = json_decode(file_get_contents(__DIR__ . '/user.json'), true) ?: [];

// Sắp xếp: Đang hoạt động lên đầu, sau đó sắp xếp theo ngày hết hạn
usort($subscriptions, function($a, $b) {
    $statusScore = ['active' => 0, 'expired' => 1, 'revoked' => 2];
    $scoreA = $statusScore[$a['status'] ?? 'expired'] ?? 3;
    $scoreB = $statusScore[$b['status'] ?? 'expired'] ?? 3;
    if ($scoreA !== $scoreB) return $scoreA - $scoreB;
    return strtotime($a['end_date'] ?? 'now') - strtotime($b['end_date'] ?? 'now');
});

if (!function_exists('subTeacherInitials')) {
    function subTeacherInitials(string $name): string {
        $parts = preg_split('/\s+/u', trim($name));
        if (!$parts) return 'GV';
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
        return mb_strtoupper($first . $last, 'UTF-8');
    }
}

$activeSubs = 0;
$expiringSubs = 0;
$inactiveSubs = 0;
$nowTime = time();

foreach ($subscriptions as $s) {
    if (($s['status'] ?? '') === 'active') {
        $activeSubs++;
        $rem = ceil((strtotime($s['end_date']) - $nowTime) / 86400);
        if ($rem <= 7) {
            $expiringSubs++;
        }
    } else {
        $inactiveSubs++;
    }
}
?>

<section class="cvd-panel cvd-reveal cvd-reveal-d2">
    <div class="cvd-panel-header">
        <div>
            <h2>Danh sách tài khoản Premium</h2>
            <p>Theo dõi các tài khoản giáo viên đã kích hoạt hoặc từng sử dụng gói Premium</p>
        </div>
        <span class="badge bg-success-subtle text-success-emphasis">
            <?php echo count($subscriptions); ?> tài khoản
        </span>
    </div>

    <!-- Filter & Search Bar -->
    <div class="cvd-filter-bar">
        <div class="cvd-filter-group" id="subFilterGroup">
            <button type="button" class="cvd-filter-btn active" data-sub-filter="all">
                Tất cả <span class="badge bg-secondary-subtle"><?php echo count($subscriptions); ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-sub-filter="active">
                Đang dùng <span class="badge bg-success-subtle text-success-emphasis"><?php echo $activeSubs; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-sub-filter="expiring">
                Sắp hết hạn <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo $expiringSubs; ?></span>
            </button>
            <button type="button" class="cvd-filter-btn" data-sub-filter="inactive">
                Đã hết hạn / Thu hồi <span class="badge bg-secondary-subtle"><?php echo $inactiveSubs; ?></span>
            </button>
        </div>
        <div class="cvd-search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" id="subSearchInput" placeholder="Tìm theo tên, tài khoản...">
        </div>
    </div>

    <div class="cvd-table-wrap">
        <?php if (empty($subscriptions)): ?>
            <div class="cvd-empty">
                <i class="bi bi-person-x"></i>
                <p class="mb-0">Chưa có tài khoản Premium nào trong hệ thống.</p>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0 w-100" id="subsTable">
                <thead>
                    <tr>
                        <th>Giáo Viên</th>
                        <th>Gói Dịch Vụ</th>
                        <th>Thời Gian Áp Dụng</th>
                        <th>Thời Hạn Còn Lại</th>
                        <th>Trạng Thái</th>
                        <th class="text-end">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): 
                        $username = $sub['username'] ?? '';
                        $user = $users[$username] ?? null;
                        $userFullname = $user ? $user['fullname'] : $username;
                        $initials = subTeacherInitials($userFullname);
                        $daysRemaining = getPremiumDaysRemaining($username);
                        $isExpiringSoon = ($sub['status'] === 'active' && $daysRemaining <= 7);
                        $isYearly = (isset($sub['package_id']) && $sub['package_id'] == 3) || (stripos($sub['package_name'] ?? '', 'năm') !== false);
                        
                        $rowFilterType = 'inactive';
                        if ($sub['status'] === 'active') {
                            $rowFilterType = $isExpiringSoon ? 'expiring active' : 'active';
                        }
                    ?>
                    <tr class="sub-row" 
                        data-filter-type="<?php echo $rowFilterType; ?>"
                        data-search="<?php echo htmlspecialchars(mb_strtolower($userFullname . ' ' . $username . ' ' . ($sub['package_name'] ?? ''))); ?>">
                        <td>
                            <div class="cvd-teacher-cell">
                                <span class="cvd-teacher-avatar"><?php echo htmlspecialchars($initials); ?></span>
                                <div>
                                    <div class="cvd-teacher-name"><?php echo htmlspecialchars($userFullname); ?></div>
                                    <div class="cvd-teacher-username">@<?php echo htmlspecialchars($username); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cvd-pkg-badge <?php echo $isYearly ? 'is-yearly' : ''; ?>">
                                <i class="bi <?php echo $isYearly ? 'bi-stars' : 'bi-patch-check'; ?>"></i>
                                <?php echo htmlspecialchars($sub['package_name'] ?? 'Gói Premium'); ?>
                            </span>
                            <div class="small text-muted mt-1">
                                <?php 
                                if (!empty($sub['activated_by']) && $sub['activated_by'] === 'key') {
                                    echo '<i class="bi bi-key me-1"></i>Kích hoạt qua Key';
                                } elseif (!empty($sub['activated_by']) && $sub['activated_by'] === 'admin_approval') {
                                    echo '<i class="bi bi-shield-check me-1"></i>Admin phê duyệt';
                                }
                                ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold small text-nowrap">
                                <?php echo !empty($sub['start_date']) ? date('d/m/Y', strtotime($sub['start_date'])) : '—'; ?>
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                <?php echo !empty($sub['end_date']) ? date('d/m/Y', strtotime($sub['end_date'])) : '—'; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($sub['status'] === 'active'): ?>
                                <span class="badge <?php echo $daysRemaining <= 7 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis'; ?>">
                                    <i class="bi <?php echo $daysRemaining <= 7 ? 'bi-hourglass-split' : 'bi-clock'; ?> me-1"></i>
                                    <?php echo $daysRemaining; ?> ngày
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sub['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success-emphasis">
                                    <i class="bi bi-check-circle-fill me-1"></i>Đang hoạt động
                                </span>
                            <?php elseif ($sub['status'] === 'expired'): ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    <i class="bi bi-clock-history me-1"></i>Hết hạn
                                </span>
                            <?php elseif ($sub['status'] === 'revoked'): ?>
                                <div>
                                    <span class="badge bg-danger-subtle text-danger-emphasis">
                                        <i class="bi bi-slash-circle-fill me-1"></i>Đã thu hồi
                                    </span>
                                    <?php if (!empty($sub['revoked_reason'])): ?>
                                        <div class="cvd-note-box mt-1">Lý do: <?php echo htmlspecialchars($sub['revoked_reason']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle"><?php echo htmlspecialchars($sub['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="cvd-table-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary extend-btn" 
                                        data-username="<?php echo htmlspecialchars($username); ?>"
                                        data-fullname="<?php echo htmlspecialchars($userFullname); ?>"
                                        data-subscription-id="<?php echo htmlspecialchars($sub['subscription_id'] ?? ''); ?>"
                                        title="Gia hạn thời gian sử dụng">
                                    <i class="bi bi-calendar-plus me-1"></i> Gia hạn
                                </button>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger revoke-btn" 
                                            data-username="<?php echo htmlspecialchars($username); ?>"
                                            data-fullname="<?php echo htmlspecialchars($userFullname); ?>"
                                            data-subscription-id="<?php echo htmlspecialchars($sub['subscription_id'] ?? ''); ?>"
                                            title="Thu hồi quyền Premium">
                                        <i class="bi bi-slash-circle me-1"></i> Thu hồi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="subNoResult" class="cvd-empty d-none">
                <i class="bi bi-search"></i>
                <p class="mb-0">Không tìm thấy tài khoản Premium nào phù hợp với bộ lọc.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Gia hạn -->
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="bi bi-calendar-plus text-primary me-2"></i>Gia Hạn Premium</h5>
                    <small class="text-muted">Cộng thêm thời gian sử dụng gói Premium cho giáo viên</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="extendForm">
                <div class="modal-body">
                    <input type="hidden" name="username" id="extend_username">
                    <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-person-circle fs-5"></i>
                        <div>
                            <div>Giáo viên: <strong id="extend_fullname_display"></strong></div>
                            <small class="text-muted" id="extend_username_display"></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chọn nhanh thời gian gia hạn</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-days-btn" data-days="30">+30 ngày (1 tháng)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-days-btn" data-days="90">+90 ngày (3 tháng)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-days-btn" data-days="180">+180 ngày (6 tháng)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-days-btn" data-days="365">+365 ngày (1 năm)</button>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Số Ngày Gia Hạn *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="days" id="extend_days_input" min="1" max="3650" value="30" required>
                            <span class="input-group-text">ngày</span>
                        </div>
                        <small class="text-muted">Thời hạn gói sẽ được cộng dồn tiếp tục từ ngày hết hạn hiện tại.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Xác Nhận Gia Hạn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thu hồi -->
<div class="modal fade" id="revokeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-danger"><i class="bi bi-slash-circle me-2"></i>Thu Hồi Premium</h5>
                    <small class="text-muted">Hủy kích hoạt quyền sử dụng gói Premium của giáo viên</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="revokeForm">
                <div class="modal-body">
                    <input type="hidden" name="username" id="revoke_username">
                    <div class="alert alert-warning py-2 mb-3">
                        <div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Cảnh báo thao tác:</div>
                        <div>Tài khoản của <strong id="revoke_fullname_display"></strong> sẽ bị dừng quyền Premium ngay lập tức.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Lý Do Thu Hồi *</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Nhập lý do thu hồi (VD: Hoàn tiền, hết thời gian thử nghiệm, yêu cầu hủy...)" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-slash-circle me-1"></i> Xác Nhận Thu Hồi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter for Subscriptions table
    const subSearchInput = document.getElementById('subSearchInput');
    const subFilterBtns = document.querySelectorAll('#subFilterGroup [data-sub-filter]');
    const subRows = document.querySelectorAll('#subsTable .sub-row');
    const subNoResult = document.getElementById('subNoResult');
    let currentSubFilter = 'all';

    function applySubFilters() {
        const query = (subSearchInput ? subSearchInput.value : '').toLowerCase().trim();
        let visibleCount = 0;

        subRows.forEach(row => {
            const searchData = row.dataset.search || '';
            const filterType = row.dataset.filterType || '';
            
            const matchSearch = query === '' || searchData.includes(query);
            let matchFilter = false;

            if (currentSubFilter === 'all') {
                matchFilter = true;
            } else if (currentSubFilter === 'active') {
                matchFilter = filterType.includes('active');
            } else if (currentSubFilter === 'expiring') {
                matchFilter = filterType.includes('expiring');
            } else if (currentSubFilter === 'inactive') {
                matchFilter = filterType.includes('inactive');
            }

            if (matchSearch && matchFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (subNoResult) {
            if (visibleCount === 0 && subRows.length > 0) {
                subNoResult.classList.remove('d-none');
            } else {
                subNoResult.classList.add('d-none');
            }
        }
    }

    if (subSearchInput) {
        subSearchInput.addEventListener('input', applySubFilters);
    }

    subFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            subFilterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentSubFilter = this.dataset.subFilter;
            applySubFilters();
        });
    });

    // Preset days button in extend modal
    document.querySelectorAll('.preset-days-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const days = this.dataset.days;
            const input = document.getElementById('extend_days_input');
            if (input) input.value = days;
        });
    });

    // Extend modal trigger
    document.querySelectorAll('.extend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.dataset.username;
            const fullname = this.dataset.fullname || username;
            document.getElementById('extend_username').value = username;
            document.getElementById('extend_fullname_display').textContent = fullname;
            document.getElementById('extend_username_display').textContent = '@' + username;
            new bootstrap.Modal(document.getElementById('extendModal')).show();
        });
    });
    
    // Revoke modal trigger
    document.querySelectorAll('.revoke-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.dataset.username;
            const fullname = this.dataset.fullname || username;
            document.getElementById('revoke_username').value = username;
            document.getElementById('revoke_fullname_display').textContent = fullname;
            new bootstrap.Modal(document.getElementById('revokeModal')).show();
        });
    });
    
    // Extend form submit
    const extendForm = document.getElementById('extendForm');
    if (extendForm) {
        extendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const formData = new FormData(this);
            formData.append('action', 'extend');
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('extendModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast(data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                showErrorToast('Lỗi kết nối máy chủ: ' + err.message);
            });
        });
    }
    
    // Revoke form submit
    const revokeForm = document.getElementById('revokeForm');
    if (revokeForm) {
        revokeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const formData = new FormData(this);
            formData.append('action', 'revoke');
            
            fetch('premium_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    showSuccessToast(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('revokeModal')).hide();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showErrorToast(data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                showErrorToast('Lỗi kết nối máy chủ: ' + err.message);
            });
        });
    }
});
</script>
