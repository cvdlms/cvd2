<?php
include '../includes/session_check.php';

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load data
$premiumFile = __DIR__ . '/student_premium.json';
$requestsFile = __DIR__ . '/student_premium_requests.json';
$studentsFile = __DIR__ . '/students.json';
$classesFile = __DIR__ . '/classes.json';

$premiumData = file_exists($premiumFile) ? json_decode(file_get_contents($premiumFile), true) : [];
$requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) : [];
$students = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
$classes = file_exists($classesFile) ? json_decode(file_get_contents($classesFile), true) : [];

// Create class lookup
$classLookup = [];
foreach ($classes as $class) {
    $classLookup[$class['id']] = $class;
}

// Create student lookup with proper field names
$studentLookup = [];
foreach ($students as $student) {
    $code = $student['code'] ?? $student['student_code'] ?? $student['student_id'] ?? null;
    if ($code) {
        $studentLookup[$code] = $student;
    }
}

// Count statistics
$totalPremium = count($premiumData);
$activePremium = 0;
$expiredPremium = 0;
foreach ($premiumData as $record) {
    if ($record['premium_status'] === 'active' && strtotime($record['end_date']) >= time()) {
        $activePremium++;
    } else {
        $expiredPremium++;
    }
}

$pendingRequests = 0;
foreach ($requests as $req) {
    if ($req['status'] === 'pending') {
        $pendingRequests++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Premium Học Sinh - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .premium-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .success-gradient {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .warning-gradient {
            background: linear-gradient(135deg, #f79d00 0%, #f5af19 100%);
            color: white;
        }
        .danger-gradient {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }
        .badge-premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card premium-gradient">
                    <div class="card-body text-center py-4">
                        <h2><i class="bi bi-star-fill me-2"></i>Quản Lý Premium Học Sinh</h2>
                        <p class="mb-0">Quản lý yêu cầu và danh sách học sinh Premium</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card premium-gradient">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill display-4"></i>
                        <h3 class="mt-2"><?php echo $totalPremium; ?></h3>
                        <p class="mb-0">Tổng Premium</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card success-gradient">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle-fill display-4"></i>
                        <h3 class="mt-2"><?php echo $activePremium; ?></h3>
                        <p class="mb-0">Đang hoạt động</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card warning-gradient">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split display-4"></i>
                        <h3 class="mt-2"><?php echo $pendingRequests; ?></h3>
                        <p class="mb-0">Yêu cầu chờ duyệt</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card danger-gradient">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle-fill display-4"></i>
                        <h3 class="mt-2"><?php echo $expiredPremium; ?></h3>
                        <p class="mb-0">Đã hết hạn</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#requests">
                    <i class="bi bi-inbox me-2"></i>Yêu Cầu Premium (<?php echo $pendingRequests; ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#premiumList">
                    <i class="bi bi-people me-2"></i>Danh Sách Premium (<?php echo $activePremium; ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addPremium">
                    <i class="bi bi-plus-circle me-2"></i>Thêm Premium Thủ Công
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#classPremium">
                    <i class="bi bi-grid-3x3 me-2"></i>Cấu Hình Theo Lớp
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab 1: Requests -->
            <div class="tab-pane fade show active" id="requests">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-inbox me-2"></i>Yêu Cầu Premium Đang Chờ
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Mã HS</th>
                                        <th>Tên học sinh</th>
                                        <th>Lớp</th>
                                        <th>Gói</th>
                                        <th>Giá</th>
                                        <th>Ngày yêu cầu</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $index => $req): 
                                        $student = $studentLookup[$req['student_code']] ?? null;
                                        $studentName = $req['student_name'] ?? ($student ? ($student['name'] ?? $student['student_name'] ?? 'N/A') : 'N/A');
                                        $classId = $student['class_id'] ?? null;
                                        $className = $req['class'] ?? ($classId && isset($classLookup[$classId]) ? $classLookup[$classId]['name'] : ($student['student_class'] ?? 'N/A'));
                                        $statusClass = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ][$req['status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($studentName); ?></td>
                                        <td><?php echo htmlspecialchars($className); ?></td>
                                        <td>
                                            <?php 
                                            $typeLabels = [
                                                'month' => 'Tháng',
                                                'semester' => 'Học Kỳ',
                                                'year' => 'Năm Học'
                                            ];
                                            $packageType = $req['package_type'] ?? $req['premium_type'] ?? 'N/A';
                                            echo $typeLabels[$packageType] ?? $packageType;
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            // Use price from request (accurate), fallback to defaults
                                            $defaultPrices = [
                                                'month' => 29000,
                                                'semester' => 119000,
                                                'year' => 199000
                                            ];
                                            $price = $req['price'] ?? $defaultPrices[$packageType] ?? 0;
                                            echo number_format($price);
                                            ?>đ
                                        </td>
                                        <td>
                                            <?php 
                                            $requestDate = $req['request_date'] ?? $req['requested_at'] ?? '';
                                            echo $requestDate ? date('d/m/Y H:i', strtotime($requestDate)) : 'N/A';
                                            ?>
                                        </td>
                                        <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $index; ?>)">
                                                    <i class="bi bi-check"></i> Duyệt
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $index; ?>)">
                                                    <i class="bi bi-x"></i> Từ chối
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Đã xử lý</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Premium List -->
            <div class="tab-pane fade" id="premiumList">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-people me-2"></i>Danh Sách Học Sinh Premium
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="premiumTable">
                                <thead>
                                    <tr>
                                        <th>Mã HS</th>
                                        <th>Tên học sinh</th>
                                        <th>Lớp</th>
                                        <th>Loại</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Ngày kết thúc</th>
                                        <th>Còn lại</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($premiumData as $index => $premium): 
                                        $student = $studentLookup[$premium['student_code']] ?? null;
                                        $studentName = $student['name'] ?? $student['student_name'] ?? 'N/A';
                                        $classId = $student['class_id'] ?? null;
                                        $className = $classId && isset($classLookup[$classId]) ? $classLookup[$classId]['name'] : ($student['student_class'] ?? 'N/A');
                                        $endTime = strtotime($premium['end_date']);
                                        $daysLeft = ceil(($endTime - time()) / 86400);
                                        $isActive = $premium['premium_status'] === 'active' && $endTime >= time();
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($premium['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($studentName); ?></td>
                                        <td><?php echo htmlspecialchars($className); ?></td>
                                        <td>
                                            <?php 
                                            $typeLabels = [
                                                'month' => 'Tháng',
                                                'semester' => 'Học Kỳ',
                                                'year' => 'Năm Học'
                                            ];
                                            echo $typeLabels[$premium['premium_type']] ?? $premium['premium_type'];
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($premium['start_date'])); ?></td>
                                        <td><?php echo date('d/m/Y', $endTime); ?></td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-<?php echo $daysLeft <= 7 ? 'danger' : 'success'; ?>">
                                                    <?php echo $daysLeft; ?> ngày
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Hết hạn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Hết hạn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <button class="btn btn-sm btn-warning" onclick="extendPremium(<?php echo $index; ?>)">
                                                    <i class="bi bi-arrow-clockwise"></i> Gia hạn
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="cancelPremium(<?php echo $index; ?>)">
                                                    <i class="bi bi-x"></i> Hủy
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="renewPremium(<?php echo $index; ?>)">
                                                    <i class="bi bi-arrow-repeat"></i> Gia hạn
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Add Premium -->
            <div class="tab-pane fade" id="addPremium">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-plus-circle me-2"></i>Thêm Premium Thủ Công
                    </div>
                    <div class="card-body">
                        <form id="addPremiumForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mã học sinh</label>
                                    <input type="text" class="form-control" id="studentCode" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Loại gói</label>
                                    <select class="form-select" id="premiumType" required>
                                        <option value="month">Tháng (30 ngày)</option>
                                        <option value="semester">Học Kỳ (120 ngày)</option>
                                        <option value="year">Năm Học (270 ngày)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="startDate" required>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Thêm Premium
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Class Premium Configuration -->
            <div class="tab-pane fade" id="classPremium">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-grid-3x3 me-2"></i>Cấu Hình Premium Theo Lớp
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Khi cấp Premium cho cả lớp, tất cả học sinh trong lớp sẽ được cấp quyền Premium với cùng thời hạn.
                        </div>

                        <form id="classPremiumForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-building me-2"></i>Chọn lớp</label>
                                    <select class="form-select" id="classSelect" required>
                                        <option value="">-- Chọn lớp --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo $class['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Số học sinh trong lớp: <span id="studentCount">0</span></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-calendar-check me-2"></i>Loại gói Premium</label>
                                    <select class="form-select" id="classPremiumType" required>
                                        <option value="month">Tháng (30 ngày)</option>
                                        <option value="semester">Học Kỳ (120 ngày)</option>
                                        <option value="year">Năm Học (270 ngày)</option>
                                        <option value="permanent">Vĩnh viễn</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-calendar3 me-2"></i>Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="classStartDate" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><i class="bi bi-chat-text me-2"></i>Ghi chú</label>
                                    <input type="text" class="form-control" id="classNote" placeholder="Lý do cấp Premium...">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-lightning-charge me-2"></i>Cấp Premium Cho Cả Lớp
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="previewClassPremium()">
                                        <i class="bi bi-eye me-2"></i>Xem trước danh sách
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Preview list -->
                        <div id="previewContainer" class="mt-4" style="display: none;">
                            <h6>Danh sách học sinh sẽ được cấp Premium:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã HS</th>
                                            <th>Họ tên</th>
                                            <th>Trạng thái hiện tại</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="notificationToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="bi bi-bell-fill me-2"></i>
                <strong class="me-auto" id="toastTitle">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#requestsTable').DataTable({
                order: [[5, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                }
            });
            
            $('#premiumTable').DataTable({
                order: [[5, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                }
            });

            // Set default start date to today
            document.getElementById('startDate').valueAsDate = new Date();
        });

        function showToast(message, type = 'success') {
            const toastEl = document.getElementById('notificationToast');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            const toastHeader = toastEl.querySelector('.toast-header');
            
            // Set icon and color based on type
            const icons = {
                'success': { icon: 'bi-check-circle-fill', color: 'text-success' },
                'error': { icon: 'bi-exclamation-triangle-fill', color: 'text-danger' },
                'info': { icon: 'bi-info-circle-fill', color: 'text-info' }
            };
            
            const config = icons[type] || icons['info'];
            toastTitle.innerHTML = `<i class="bi ${config.icon} ${config.color} me-2"></i>Thông báo`;
            toastMessage.textContent = message;
            
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        function approveRequest(index) {
            if (!confirm('Xác nhận duyệt yêu cầu Premium này?')) return;
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approve', index: index })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã duyệt yêu cầu thành công!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể duyệt yêu cầu'), 'error');
                }
            });
        }

        function rejectRequest(index) {
            if (!confirm('Xác nhận từ chối yêu cầu Premium này?')) return;
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', index: index })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã từ chối yêu cầu!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể từ chối'), 'error');
                }
            });
        }

        function extendPremium(index) {
            const days = prompt('Gia hạn thêm bao nhiêu ngày?', '30');
            if (!days || days <= 0) return;
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'extend', index: index, days: parseInt(days) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã gia hạn thành công!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể gia hạn'), 'error');
                }
            });
        }

        function cancelPremium(index) {
            if (!confirm('Xác nhận HỦY Premium của học sinh này?')) return;
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'cancel', index: index })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã hủy Premium!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể hủy'), 'error');
                }
            });
        }

        function renewPremium(index) {
            const type = prompt('Chọn gói (month/semester/year):', 'month');
            if (!type) return;
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'renew', index: index, type: type })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã gia hạn Premium!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể gia hạn'), 'error');
                }
            });
        }

        document.getElementById('addPremiumForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const data = {
                action: 'add',
                student_code: document.getElementById('studentCode').value,
                premium_type: document.getElementById('premiumType').value,
                start_date: document.getElementById('startDate').value
            };
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã thêm Premium thành công!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể thêm Premium'), 'error');
                }
            });
        });

        // Class Premium functionality
        const students = <?php echo json_encode($students); ?>;
        const premiumData = <?php echo json_encode($premiumData); ?>;

        document.getElementById('classSelect').addEventListener('change', function() {
            const classId = this.value;
            if (!classId) {
                document.getElementById('studentCount').textContent = '0';
                return;
            }
            
            const classStudents = students.filter(s => s.class_id === classId);
            document.getElementById('studentCount').textContent = classStudents.length;
        });

        function previewClassPremium() {
            const classId = document.getElementById('classSelect').value;
            if (!classId) {
                alert('Vui lòng chọn lớp!');
                return;
            }

            const classStudents = students.filter(s => s.class_id === classId);
            const previewList = document.getElementById('previewList');
            previewList.innerHTML = '';

            classStudents.forEach(student => {
                const code = student.code || student.student_code || student.student_id;
                const hasPremium = premiumData.some(p => p.student_code === code && p.premium_status === 'active');
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${code}</td>
                    <td>${student.name}</td>
                    <td>${hasPremium ? '<span class="badge bg-success">Đang có Premium</span>' : '<span class="text-muted">Chưa có</span>'}</td>
                `;
                previewList.appendChild(row);
            });

            document.getElementById('previewContainer').style.display = 'block';
        }

        document.getElementById('classPremiumForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const classId = document.getElementById('classSelect').value;
            if (!classId) {
                alert('Vui lòng chọn lớp!');
                return;
            }

            const classStudents = students.filter(s => s.class_id === classId);
            if (classStudents.length === 0) {
                alert('Lớp này không có học sinh nào!');
                return;
            }

            if (!confirm(`Bạn có chắc muốn cấp Premium cho ${classStudents.length} học sinh trong lớp này?`)) {
                return;
            }

            const data = {
                action: 'add_class',
                class_id: classId,
                premium_type: document.getElementById('classPremiumType').value,
                start_date: document.getElementById('classStartDate').value,
                note: document.getElementById('classNote').value
            };
            
            fetch('api/manage_premium_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`✅ Đã cấp Premium cho ${data.count || classStudents.length} học sinh!`, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast('Lỗi: ' + (data.message || 'Không thể cấp Premium'), 'error');
                }
            });
        });
    </script>
</body>
</html>