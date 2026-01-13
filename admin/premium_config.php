<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'premium_config.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true);
}

$premiumConfig = $config['premium'] ?? [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Premium - CVD Admin</title>
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
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .feature-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .feature-item:hover {
            border-color: #ffc107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
        }
        .feature-item.active {
            background: #fff3cd;
            border-color: #ffc107;
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
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
                        <h3 class="mb-0">⭐ Cấu Hình Premium</h3>
                        <p class="mb-0 mt-2">Quản lý tính năng và cài đặt hệ thống Premium</p>
                    </div>

                    <div class="card-body p-4">
                        <form id="premiumConfigForm">
                            <!-- Premium System Status -->
                            <div class="mb-4">
                                <h5>Trạng thái hệ thống Premium</h5>
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <div>
                                        <h6 class="mb-0">Kích hoạt hệ thống Premium</h6>
                                        <small class="text-muted">Cho phép người dùng đăng ký và sử dụng các tính năng Premium</small>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="premium_enabled" id="premiumEnabled" 
                                            <?php echo ($premiumConfig['enabled'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Trial Period -->
                            <div class="mb-4">
                                <h5>Thời gian dùng thử</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Số ngày dùng thử miễn phí</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="trial_days" 
                                                value="<?php echo $premiumConfig['trial_days'] ?? 7; ?>" min="0" max="30">
                                            <span class="input-group-text">ngày</span>
                                        </div>
                                        <small class="text-muted">Người dùng mới sẽ được dùng thử Premium miễn phí</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Premium Features -->
                            <div class="mb-4">
                                <h5>Tính năng Premium</h5>
                                <p class="text-muted">Chọn các tính năng sẽ có trong gói Premium</p>
                                
                                <?php
                                $features = [
                                    'unlimited_exams' => ['icon' => 'bi-infinity', 'name' => 'Tạo đề thi không giới hạn', 'desc' => 'Không giới hạn số lượng đề thi có thể tạo'],
                                    'export_with_answers' => ['icon' => 'bi-file-earmark-pdf', 'name' => 'Xuất đề có đáp án', 'desc' => 'Cho phép xuất file PDF kèm đáp án chi tiết'],
                                    'auto_matrix' => ['icon' => 'bi-grid-3x3', 'name' => 'Tạo ma trận tự động', 'desc' => 'Tự động tạo ma trận đề thi theo chuẩn'],
                                    'advanced_stats' => ['icon' => 'bi-graph-up', 'name' => 'Thống kê nâng cao', 'desc' => 'Xem báo cáo và phân tích chi tiết'],
                                    'import_excel' => ['icon' => 'bi-file-excel', 'name' => 'Import câu hỏi Excel', 'desc' => 'Nhập câu hỏi hàng loạt từ file Excel'],
                                    'question_bank_unlimited' => ['icon' => 'bi-database', 'name' => 'Ngân hàng câu hỏi không giới hạn', 'desc' => 'Lưu trữ không giới hạn câu hỏi']
                                ];
                                
                                foreach ($features as $key => $feature):
                                    $checked = $premiumConfig['features'][$key] ?? true;
                                ?>
                                <div class="feature-item <?php echo $checked ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <i class="<?php echo $feature['icon']; ?> fs-4 me-3 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0"><?php echo $feature['name']; ?></h6>
                                                <small class="text-muted"><?php echo $feature['desc']; ?></small>
                                            </div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="feature_<?php echo $key; ?>" 
                                                class="feature-toggle" data-key="<?php echo $key; ?>"
                                                <?php echo $checked ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Package Pricing -->
                            <div class="mb-4">
                                <h5>Gói Premium (Thông tin)</h5>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    Để cập nhật giá gói và chi tiết, vui lòng truy cập trang 
                                    <a href="premium_management.php" class="alert-link">Quản lý Premium</a>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Lưu cấu hình
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
        // Toggle feature item styling
        document.querySelectorAll('.feature-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const item = this.closest('.feature-item');
                if (this.checked) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        });

        // Form submission
        document.getElementById('premiumConfigForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_premium_config');
            
            try {
                const response = await fetch('api/system_config_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Cập nhật cấu hình Premium thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        });
    </script>
</body>
</html>
