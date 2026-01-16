<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'semester_config.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true);
}

$currentSemester = $config['semester']['current'] ?? 'hk2';
$semesterLabels = $config['semester']['labels'] ?? ['hk1' => 'Học kì 1', 'hk2' => 'Học kì 2'];
$availableSemesters = $config['semester']['available'] ?? ['hk1', 'hk2'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình Học Kì - CVD Admin</title>
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
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .semester-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .semester-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .semester-card.active {
            border-color: #198754;
            background: #f0fff4;
        }
        .badge-current {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
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
                        <h3 class="mb-0">📅 Cấu Hình Học Kì</h3>
                        <p class="mb-0 mt-2">Quản lý học kì và năm học của hệ thống</p>
                    </div>

                    <div class="card-body p-4">
                        <!-- Information Box -->
                        <div class="info-box">
                            <h6><i class="bi bi-info-circle"></i> Thông tin quan trọng:</h6>
                            <ul class="mb-0">
                                <li>Học kì hiện tại sẽ được sử dụng để lọc dữ liệu học sinh, giáo viên và các kỳ thi</li>
                                <li>Khi chuyển học kì, hệ thống sẽ tự động cập nhật dữ liệu hiển thị</li>
                                <li>Dữ liệu của các học kì trước vẫn được lưu trữ và có thể truy cập</li>
                            </ul>
                        </div>

                        <!-- Current Semester -->
                        <div class="mb-4">
                            <h5>Học kì hiện tại</h5>
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="bi bi-calendar-check fs-3 me-3"></i>
                                <div>
                                    <h5 class="mb-0"><?php echo $semesterLabels[$currentSemester] ?? $currentSemester; ?></h5>
                                    <small class="text-muted">Năm học: <?php echo $config['system']['school_year'] ?? '2025-2026'; ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Change Semester Form -->
                        <form id="semesterForm">
                            <div class="mb-4">
                                <h5>Thay đổi học kì</h5>
                                <div class="row">
                                    <?php foreach ($availableSemesters as $sem): ?>
                                    <div class="col-md-6">
                                        <div class="semester-card <?php echo $sem === $currentSemester ? 'active' : ''; ?>">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="semester" 
                                                    id="sem_<?php echo $sem; ?>" value="<?php echo $sem; ?>"
                                                    <?php echo $sem === $currentSemester ? 'checked' : ''; ?>>
                                                <label class="form-check-label w-100" for="sem_<?php echo $sem; ?>">
                                                    <h5>
                                                        <?php echo $semesterLabels[$sem] ?? $sem; ?>
                                                        <?php if ($sem === $currentSemester): ?>
                                                        <span class="badge-current float-end">Đang sử dụng</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <p class="text-muted mb-0">
                                                        <?php echo $sem === 'hk1' ? 'Từ tháng 8 đến tháng 12' : 'Từ tháng 1 đến tháng 5'; ?>
                                                    </p>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- School Year -->
                            <div class="mb-4">
                                <h5>Năm học</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Năm học hiện tại</label>
                                        <input type="text" class="form-control" name="school_year" 
                                            value="<?php echo $config['system']['school_year'] ?? '2025-2026'; ?>"
                                            placeholder="Ví dụ: 2025-2026">
                                        <small class="text-muted">Định dạng: YYYY-YYYY</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tên trường</label>
                                        <input type="text" class="form-control" name="school_name" 
                                            value="<?php echo $config['system']['school_name'] ?? 'Trường THCS CVD'; ?>">
                                    </div>
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
        document.getElementById('semesterForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/system_config_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Cập nhật cấu hình thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        });

        // Highlight selected semester card
        document.querySelectorAll('input[name="semester"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.semester-card').forEach(card => {
                    card.classList.remove('active');
                });
                this.closest('.semester-card').classList.add('active');
            });
        });
    </script>
</body>
</html>
