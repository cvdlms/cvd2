<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'security_config.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true);
}

// Default security settings
$securityConfig = $config['security'] ?? [
    'password_min_length' => 6,
    'password_require_uppercase' => false,
    'password_require_numbers' => false,
    'password_require_special' => false,
    'session_timeout' => 3600,
    'max_login_attempts' => 5,
    'lockout_duration' => 900,
    'enable_2fa' => false,
    'ip_whitelist_enabled' => false,
    'ip_whitelist' => []
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Bảo Mật - CVD Admin</title>
    <link href="../styles/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .config-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .config-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .security-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .danger-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="config-card">
                    <div class="config-header">
                        <h3 class="mb-0">🔒 Cấu Hình Bảo Mật</h3>
                        <p class="mb-0 mt-2">Quản lý các thiết lập bảo mật hệ thống</p>
                    </div>

                    <div class="card-body p-4">
                        <div class="warning-box">
                            <i class="bi bi-exclamation-triangle-fill"></i> 
                            <strong>Lưu ý:</strong> Thay đổi cấu hình bảo mật có thể ảnh hưởng đến trải nghiệm người dùng. 
                            Hãy cân nhắc kỹ trước khi thay đổi.
                        </div>

                        <form id="securityConfigForm">
                            <!-- Password Policy -->
                            <div class="security-section">
                                <h5><i class="bi bi-key"></i> Chính sách mật khẩu</h5>
                                <p class="text-muted">Yêu cầu độ phức tạp cho mật khẩu người dùng</p>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Độ dài tối thiểu</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="password_min_length" 
                                                value="<?php echo $securityConfig['password_min_length']; ?>" 
                                                min="4" max="20">
                                            <span class="input-group-text">ký tự</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded">
                                        <div>
                                            <strong>Yêu cầu chữ hoa</strong>
                                            <small class="d-block text-muted">Mật khẩu phải có ít nhất 1 chữ hoa</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="password_require_uppercase" 
                                                <?php echo $securityConfig['password_require_uppercase'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded">
                                        <div>
                                            <strong>Yêu cầu số</strong>
                                            <small class="d-block text-muted">Mật khẩu phải có ít nhất 1 chữ số</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="password_require_numbers" 
                                                <?php echo $securityConfig['password_require_numbers'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded">
                                        <div>
                                            <strong>Yêu cầu ký tự đặc biệt</strong>
                                            <small class="d-block text-muted">Mật khẩu phải có ký tự như @, #, $, %, etc.</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="password_require_special" 
                                                <?php echo $securityConfig['password_require_special'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Session Management -->
                            <div class="security-section">
                                <h5><i class="bi bi-clock-history"></i> Quản lý phiên đăng nhập</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Thời gian hết hạn phiên</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="session_timeout" 
                                                value="<?php echo $securityConfig['session_timeout']; ?>" 
                                                min="300" max="86400">
                                            <span class="input-group-text">giây</span>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            $minutes = round($securityConfig['session_timeout'] / 60);
                                            echo "Hiện tại: $minutes phút";
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Login Attempts -->
                            <div class="security-section">
                                <h5><i class="bi bi-shield-lock"></i> Bảo vệ chống brute force</h5>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Số lần đăng nhập sai tối đa</label>
                                        <input type="number" class="form-control" name="max_login_attempts" 
                                            value="<?php echo $securityConfig['max_login_attempts']; ?>" 
                                            min="3" max="10">
                                        <small class="text-muted">Khóa tài khoản sau số lần đăng nhập sai</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Thời gian khóa</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="lockout_duration" 
                                                value="<?php echo $securityConfig['lockout_duration']; ?>" 
                                                min="60" max="3600">
                                            <span class="input-group-text">giây</span>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            $minutes = round($securityConfig['lockout_duration'] / 60);
                                            echo "Hiện tại: $minutes phút";
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Two-Factor Authentication -->
                            <div class="security-section">
                                <h5><i class="bi bi-phone"></i> Xác thực hai yếu tố (2FA)</h5>
                                
                                <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded">
                                    <div>
                                        <strong>Bật xác thực 2 yếu tố</strong>
                                        <small class="d-block text-muted">Yêu cầu mã xác thực bổ sung khi đăng nhập</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="enable_2fa" 
                                            <?php echo $securityConfig['enable_2fa'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bi bi-info-circle"></i> Tính năng đang trong giai đoạn phát triển
                                </div>
                            </div>

                            <!-- IP Whitelist -->
                            <div class="security-section">
                                <h5><i class="bi bi-hdd-network"></i> Danh sách IP cho phép</h5>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded">
                                        <div>
                                            <strong>Kích hoạt whitelist IP</strong>
                                            <small class="d-block text-muted">Chỉ cho phép truy cập từ các IP được chỉ định</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="ip_whitelist_enabled" 
                                                <?php echo $securityConfig['ip_whitelist_enabled'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="danger-box">
                                    <i class="bi bi-exclamation-octagon-fill"></i> 
                                    <strong>Cảnh báo:</strong> Bật tính năng này có thể khóa bạn ra khỏi hệ thống nếu IP của bạn không nằm trong danh sách.
                                </div>

                                <label class="form-label">Danh sách IP (mỗi IP một dòng)</label>
                                <textarea class="form-control" name="ip_whitelist" rows="5" 
                                    placeholder="192.168.1.1&#10;192.168.1.100&#10;10.0.0.0/24"><?php 
                                    echo implode("\n", $securityConfig['ip_whitelist']); 
                                ?></textarea>
                                <small class="text-muted">Hỗ trợ định dạng IP đơn hoặc CIDR (VD: 192.168.1.0/24)</small>
                            </div>

                            <!-- System Protection -->
                            <div class="security-section">
                                <h5><i class="bi bi-code-slash"></i> Bảo vệ hệ thống</h5>
                                
                                <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded">
                                    <div>
                                        <strong>Vô hiệu hóa xem mã nguồn</strong>
                                        <small class="d-block text-muted">Ngăn người dùng xem mã nguồn trang web (Ctrl+U)</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="disable_view_source" 
                                            <?php echo ($config['system']['disable_view_source'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-shield-check"></i> Lưu cấu hình bảo mật
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('securityConfigForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Confirm if IP whitelist is enabled
            const ipWhitelistEnabled = document.querySelector('input[name="ip_whitelist_enabled"]').checked;
            if (ipWhitelistEnabled) {
                const confirm = window.confirm(
                    '⚠️ CẢNH BÁO: Bạn đang bật whitelist IP.\n\n' +
                    'Điều này có thể khóa bạn ra khỏi hệ thống nếu IP hiện tại của bạn không nằm trong danh sách.\n\n' +
                    'Bạn có chắc chắn muốn tiếp tục?'
                );
                if (!confirm) return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'update_security_config');
            
            try {
                const response = await fetch('api/system_config_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Cập nhật cấu hình bảo mật thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        });

        // Update time display when values change
        document.querySelector('input[name="session_timeout"]').addEventListener('input', function() {
            const minutes = Math.round(this.value / 60);
            this.nextElementSibling.nextElementSibling.textContent = `Hiện tại: ${minutes} phút`;
        });

        document.querySelector('input[name="lockout_duration"]').addEventListener('input', function() {
            const minutes = Math.round(this.value / 60);
            this.nextElementSibling.nextElementSibling.textContent = `Hiện tại: ${minutes} phút`;
        });
    </script>
</body>
</html>
