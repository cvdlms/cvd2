<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$current_page = 'system_info_config.php';

// Load config
$config = [];
if (file_exists('system_config.json')) {
    $config = json_decode(file_get_contents('system_config.json'), true) ?: [];
}

$systemConfig = array_merge([
    'school_name' => 'Trường THCS CVD',
    'school_short_name' => '',
    'school_level' => '',
    'school_address' => '',
    'school_phone' => '',
    'school_email' => '',
    'school_website' => '',
    'school_year' => '2025-2026',
    'version' => '2.0',
    'last_updated' => date('Y-m-d')
], $config['system'] ?? []);

function info_value(array $systemConfig, string $key): string
{
    return htmlspecialchars(trim((string)($systemConfig[$key] ?? '')), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Chung Hệ Thống - CVD Admin</title>
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
            overflow: hidden;
        }
        .config-header {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            padding: 20px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #198754;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #198754;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .preview-card {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .preview-mark {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 12px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #e9ecef;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="config-card">
                    <div class="config-header">
                        <h3 class="mb-0"><i class="bi bi-info-circle-fill"></i> Thông Tin Chung Hệ Thống</h3>
                        <p class="mb-0 mt-2">Thông tin trường học hiển thị trên toàn bộ hệ thống CVD</p>
                    </div>

                    <div class="card-body p-4">
                        <div class="info-box">
                            <h6><i class="bi bi-lightbulb-fill"></i> Thông tin này dùng ở đâu?</h6>
                            <ul class="mb-0">
                                <li>Tên trường hiển thị trên trang đăng nhập, đầu bài kiểm tra và trang kết quả</li>
                                <li>Thông tin liên hệ dùng ở chân trang và trang trợ giúp</li>
                                <li>Năm học dùng để lọc dữ liệu học sinh, giáo viên và kỳ thi</li>
                            </ul>
                        </div>

                        <div id="systemInfoAlert" class="alert d-none" role="alert"></div>

                        <form id="systemInfoForm" novalidate>
                            <input type="hidden" name="action" value="update_system_info">

                            <div class="section-title"><i class="bi bi-building"></i> Thông tin trường học</div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label" for="school_name">Tên trường <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="school_name" name="school_name"
                                        value="<?php echo info_value($systemConfig, 'school_name'); ?>"
                                        maxlength="150" required placeholder="Ví dụ: Trường THPT Nguyễn Du">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="school_short_name">Tên viết tắt</label>
                                    <input type="text" class="form-control" id="school_short_name" name="school_short_name"
                                        value="<?php echo info_value($systemConfig, 'school_short_name'); ?>"
                                        maxlength="30" placeholder="Ví dụ: THPT Nguyễn Du">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="school_level">Cấp học</label>
                                    <input type="text" class="form-control" id="school_level" name="school_level"
                                        value="<?php echo info_value($systemConfig, 'school_level'); ?>"
                                        maxlength="50" placeholder="Ví dụ: Trung học phổ thông">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="school_address">Địa chỉ</label>
                                    <input type="text" class="form-control" id="school_address" name="school_address"
                                        value="<?php echo info_value($systemConfig, 'school_address'); ?>"
                                        maxlength="255" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố">
                                </div>
                            </div>

                            <div class="section-title"><i class="bi bi-telephone-fill"></i> Thông tin liên hệ</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" for="school_phone">Số điện thoại</label>
                                    <input type="tel" class="form-control" id="school_phone" name="school_phone"
                                        value="<?php echo info_value($systemConfig, 'school_phone'); ?>"
                                        maxlength="30" placeholder="Ví dụ: 0123 456 789">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="school_email">Email</label>
                                    <input type="email" class="form-control" id="school_email" name="school_email"
                                        value="<?php echo info_value($systemConfig, 'school_email'); ?>"
                                        placeholder="email@truong.edu.vn">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="school_website">Website</label>
                                    <input type="url" class="form-control" id="school_website" name="school_website"
                                        value="<?php echo info_value($systemConfig, 'school_website'); ?>"
                                        placeholder="https://truong.edu.vn">
                                </div>
                            </div>

                            <div class="section-title"><i class="bi bi-calendar3"></i> Năm học và phiên bản</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" for="school_year">Năm học <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="school_year" name="school_year"
                                        value="<?php echo info_value($systemConfig, 'school_year'); ?>"
                                        pattern="\d{4}\s*-\s*\d{4}" required placeholder="2025-2026">
                                    <small class="text-muted">Định dạng: YYYY-YYYY</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="version">Phiên bản hệ thống</label>
                                    <input type="text" class="form-control" id="version" name="version"
                                        value="<?php echo info_value($systemConfig, 'version'); ?>"
                                        pattern="\d+(\.\d+)*" placeholder="2.0">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="systemInfoSubmit">
                                    <i class="bi bi-save"></i> Lưu thông tin
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="preview-card mb-3" id="previewCard">
                    <div class="preview-mark"><i class="bi bi-mortarboard-fill"></i></div>
                    <h5 class="mb-1" id="previewName"><?php echo info_value($systemConfig, 'school_name'); ?></h5>
                    <p class="mb-0 small" id="previewLevel"><?php echo info_value($systemConfig, 'school_level'); ?></p>
                    <p class="mb-0 small" id="previewYear">Năm học: <?php echo info_value($systemConfig, 'school_year'); ?></p>
                </div>

                <div class="config-card">
                    <div class="card-body p-4">
                        <h6 class="mb-3"><i class="bi bi-clipboard-data"></i> Thông tin đang lưu</h6>
                        <div class="summary-item">
                            <span class="text-muted">Cập nhật lần cuối</span>
                            <strong><?php echo info_value($systemConfig, 'last_updated'); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Địa chỉ</span>
                            <strong class="text-end" id="summaryAddress"><?php echo info_value($systemConfig, 'school_address') ?: 'Chưa cập nhật'; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Điện thoại</span>
                            <strong id="summaryPhone"><?php echo info_value($systemConfig, 'school_phone') ?: 'Chưa cập nhật'; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Email</span>
                            <strong class="text-end text-break" id="summaryEmail"><?php echo info_value($systemConfig, 'school_email') ?: 'Chưa cập nhật'; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span class="text-muted">Website</span>
                            <strong class="text-end text-break" id="summaryWebsite"><?php echo info_value($systemConfig, 'school_website') ?: 'Chưa cập nhật'; ?></strong>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="card-body p-4">
                        <h6 class="mb-3"><i class="bi bi-tools"></i> Liên kết nhanh</h6>
                        <div class="d-grid gap-2">
                            <a href="semester_config.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-calendar-event"></i> Cấu hình học kì
                            </a>
                            <a href="system_settings.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-sliders"></i> Tổng quan cấu hình
                            </a>
                            <a href="security_config.php" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-shield-lock"></i> Cài đặt bảo mật
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const form = document.getElementById('systemInfoForm');
            const alertBox = document.getElementById('systemInfoAlert');
            const submitBtn = document.getElementById('systemInfoSubmit');

            function showAlert(message, type) {
                alertBox.className = 'alert alert-' + type;
                alertBox.innerHTML = '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + message;
            }

            function updatePreview() {
                const value = id => (document.getElementById(id).value || '').trim();

                document.getElementById('previewName').textContent = value('school_name') || 'Chưa đặt tên trường';
                document.getElementById('previewLevel').textContent = value('school_level');
                document.getElementById('previewYear').textContent = 'Năm học: ' + (value('school_year') || '—');

                document.getElementById('summaryAddress').textContent = value('school_address') || 'Chưa cập nhật';
                document.getElementById('summaryPhone').textContent = value('school_phone') || 'Chưa cập nhật';
                document.getElementById('summaryEmail').textContent = value('school_email') || 'Chưa cập nhật';
                document.getElementById('summaryWebsite').textContent = value('school_website') || 'Chưa cập nhật';
            }

            form.addEventListener('input', updatePreview);

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                alertBox.classList.add('d-none');

                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    showAlert('Vui lòng nhập đầy đủ và đúng định dạng các trường bắt buộc.', 'danger');
                    return;
                }
                form.classList.add('was-validated');

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';

                try {
                    const response = await fetch('api/system_config_actions.php', {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    const result = await response.json();

                    if (result.success) {
                        showAlert(result.message, 'success');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showAlert('Lỗi: ' + result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('Có lỗi xảy ra: ' + error.message, 'danger');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-save"></i> Lưu thông tin';
                }
            });
        })();
    </script>
</body>
</html>
