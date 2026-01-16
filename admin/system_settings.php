<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'system_settings.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt Hệ Thống - CVD Admin</title>
    <link href="../styles/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .config-category {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .config-category:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #0d6efd;
        }
        .category-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .semester-icon { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
        .premium-icon { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .security-icon { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .system-icon { background: linear-gradient(135deg, #198754 0%, #157347 100%); }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="header-section bg-primary">
        <div class="container">
            <h2><i class="bi bi-gear-fill"></i> Cài Đặt Hệ Thống</h2>
            <p class="mb-0">Quản lý tất cả cấu hình hệ thống CVD</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Semester Configuration -->
            <div class="col-md-6 col-lg-3">
                <div class="config-category" onclick="location.href='semester_config.php'">
                    <div class="category-icon semester-icon text-white mx-auto">
                        📅
                    </div>
                    <h5 class="text-center mb-3">Học Kì</h5>
                    <div class="info-item">
                        <span class="text-muted">Học kì hiện tại:</span>
                        <strong><?php echo $config['semester']['labels'][$config['semester']['current']] ?? 'N/A'; ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Năm học:</span>
                        <strong><?php echo $config['system']['school_year'] ?? 'N/A'; ?></strong>
                    </div>
                    <div class="text-center mt-3">
                        <a href="semester_config.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-gear"></i> Cấu hình
                        </a>
                    </div>
                </div>
            </div>

            <!-- Premium Configuration -->
            <div class="col-md-6 col-lg-3">
                <div class="config-category" onclick="location.href='premium_config.php'">
                    <div class="category-icon premium-icon text-white mx-auto">
                        ⭐
                    </div>
                    <h5 class="text-center mb-3">Premium</h5>
                    <div class="info-item">
                        <span class="text-muted">Trạng thái:</span>
                        <span class="status-badge <?php echo ($config['premium']['enabled'] ?? false) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($config['premium']['enabled'] ?? false) ? 'Đang bật' : 'Đã tắt'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Dùng thử:</span>
                        <strong><?php echo $config['premium']['trial_days'] ?? 0; ?> ngày</strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Tính năng:</span>
                        <strong><?php echo count(array_filter($config['premium']['features'] ?? [])); ?>/6</strong>
                    </div>
                    <div class="text-center mt-3">
                        <a href="premium_config.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-gear"></i> Cấu hình
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Configuration -->
            <div class="col-md-6 col-lg-3">
                <div class="config-category" onclick="location.href='security_config.php'">
                    <div class="category-icon security-icon text-white mx-auto">
                        🔒
                    </div>
                    <h5 class="text-center mb-3">Bảo Mật</h5>
                    <div class="info-item">
                        <span class="text-muted">Độ dài mật khẩu:</span>
                        <strong>≥ <?php echo $config['security']['password_min_length'] ?? 6; ?> ký tự</strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Thời gian phiên:</span>
                        <strong><?php echo round(($config['security']['session_timeout'] ?? 3600) / 60); ?> phút</strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Whitelist IP:</span>
                        <span class="status-badge <?php echo ($config['security']['ip_whitelist_enabled'] ?? false) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($config['security']['ip_whitelist_enabled'] ?? false) ? 'Bật' : 'Tắt'; ?>
                        </span>
                    </div>
                    <div class="text-center mt-3">
                        <a href="security_config.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-gear"></i> Cấu hình
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-md-6 col-lg-3">
                <div class="config-category">
                    <div class="category-icon system-icon text-white mx-auto">
                        ℹ️
                    </div>
                    <h5 class="text-center mb-3">Thông Tin</h5>
                    <div class="info-item">
                        <span class="text-muted">Phiên bản:</span>
                        <strong><?php echo $config['system']['version'] ?? '2.0'; ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Cập nhật:</span>
                        <strong><?php echo $config['system']['last_updated'] ?? date('Y-m-d'); ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Trường:</span>
                        <strong class="text-truncate" style="max-width: 150px;" title="<?php echo $config['system']['school_name'] ?? 'CVD'; ?>">
                            <?php echo $config['system']['school_name'] ?? 'CVD'; ?>
                        </strong>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="bi bi-info-circle"></i> Chi tiết
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Thao Tác Nhanh</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="semester_config.php" class="btn btn-outline-primary">
                                        <i class="bi bi-calendar-event"></i> Chuyển Học Kì
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="premium_management.php" class="btn btn-outline-warning">
                                        <i class="bi bi-star"></i> Quản Lý Premium
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-grid">
                                    <a href="security_config.php" class="btn btn-outline-danger">
                                        <i class="bi bi-shield-lock"></i> Cài Đặt Bảo Mật
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration History -->
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Lịch Sử Cấu Hình</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> 
                            Lần cập nhật gần nhất: <strong><?php echo $config['system']['last_updated'] ?? date('Y-m-d'); ?></strong>
                            <br>
                            <small class="text-muted">Tính năng lịch sử chi tiết sẽ được bổ sung trong phiên bản tiếp theo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
