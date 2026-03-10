<?php
session_name('CVD_TEACHER_SESSION');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$users = json_decode(file_get_contents('user.json'), true) ?: [];
$fullname = $users['admin']['fullname'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - CVD Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <style>
        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .backup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .backup-table {
            margin-top: 20px;
        }
        .backup-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .backup-info {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .backup-info ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .backup-info li {
            margin-bottom: 5px;
        }
        .btn-action {
            padding: 6px 12px;
            font-size: 14px;
            margin-right: 5px;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
        .badge-size {
            background: #e9ecef;
            color: #495057;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'backup.php'; include 'navbar.php'; ?>

  <div class="main-content">
    <h1><i class="bi bi-cloud-download"></i> Backup & Restore</h1>
    
    <!-- Info Card -->
    <div class="backup-info">
        <h5><i class="bi bi-info-circle"></i> Dữ liệu được sao lưu:</h5>
        <ul>
            <li><strong>Học sinh:</strong> Danh sách học sinh, lớp học, premium</li>
            <li><strong>Bài kiểm tra:</strong> Tất cả đề thi (Khối 6-9)</li>
            <li><strong>Ngân hàng câu hỏi:</strong> Questions bank</li>
            <li><strong>Kết quả kiểm tra:</strong> Điểm số học sinh</li>
        </ul>
        <p class="mb-0 mt-2"><i class="bi bi-clock-history"></i> <strong>Lưu ý:</strong> Hệ thống tự động giữ 3 bản backup gần nhất.</p>
    </div>

    <!-- Main Card -->
    <div class="backup-card">
        <div class="backup-header">
            <h4><i class="bi bi-archive"></i> Quản Lý Backup</h4>
            <button class="btn btn-primary" onclick="createBackup()">
                <i class="bi bi-plus-circle"></i> Tạo Backup Mới
            </button>
        </div>

        <!-- Backup List Table -->
        <div class="backup-table">
            <table class="table table-hover" id="backupTable">
                <thead>
                    <tr>
                        <th width="45%">Tên File</th>
                        <th width="20%">Ngày Tạo</th>
                        <th width="15%">Kích Thước</th>
                        <th width="20%" class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="backupList">
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            <i class="bi bi-hourglass-split"></i> Đang tải...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
      <div class="loading-spinner">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3 mb-0" id="loadingText">Đang xử lý...</p>
      </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Load backups on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadBackups();
    });

    // Load backup list
    function loadBackups() {
        fetch('api/list_backups.php')
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('backupList');
                
                if (data.success && data.backups.length > 0) {
                    tbody.innerHTML = data.backups.map(backup => `
                        <tr>
                            <td>
                                <i class="bi bi-file-earmark-zip text-primary"></i>
                                ${backup.filename}
                            </td>
                            <td>${backup.created_at_formatted}</td>
                            <td><span class="badge-size">${backup.size_formatted}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-success btn-action" onclick="downloadBackup('${backup.filename}')" title="Tải về">
                                    <i class="bi bi-download"></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-action" onclick="restoreBackup('${backup.filename}')" title="Khôi phục">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-action" onclick="deleteBackup('${backup.filename}')" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="bi bi-inbox"></i> Chưa có backup nào. Nhấn "Tạo Backup Mới" để tạo.
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(err => {
                console.error('Error loading backups:', err);
                document.getElementById('backupList').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            <i class="bi bi-exclamation-triangle"></i> Lỗi tải danh sách backup
                        </td>
                    </tr>
                `;
            });
    }

    // Create new backup
    function createBackup() {
        if (!confirm('Tạo backup mới? Quá trình này có thể mất vài giây.')) return;
        
        showLoading('Đang tạo backup...');
        
        fetch('api/create_backup.php', {
            method: 'POST'
        })
        .then(r => {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Response:', text);
                throw new Error('Phản hồi không hợp lệ từ server');
            }
        })
        .then(data => {
            hideLoading();
            if (data.success) {
                alert('✅ ' + data.message + '\nKích thước: ' + data.size);
                loadBackups();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            hideLoading();
            alert('❌ Lỗi: ' + err.message);
        });
    }

    // Download backup
    function downloadBackup(filename) {
        window.location.href = 'api/download_backup.php?filename=' + encodeURIComponent(filename);
    }

    // Restore backup
    function restoreBackup(filename) {
        if (!confirm('⚠️ CẢNH BÁO: Khôi phục backup sẽ GHI ĐÈ tất cả dữ liệu hiện tại!\n\nBạn có chắc chắn muốn tiếp tục?')) {
            return;
        }
        
        if (!confirm('Xác nhận lần 2: Bạn thực sự muốn khôi phục backup "' + filename + '"?')) {
            return;
        }
        
        showLoading('Đang khôi phục backup...');
        
        const formData = new FormData();
        formData.append('filename', filename);
        
        fetch('api/restore_backup.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                alert('✅ ' + data.message + '\n\nĐã khôi phục:\n' + data.restored_items.join('\n'));
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            hideLoading();
            alert('❌ Lỗi: ' + err.message);
        });
    }

    // Delete backup
    function deleteBackup(filename) {
        if (!confirm('Xóa backup "' + filename + '"?')) return;
        
        showLoading('Đang xóa...');
        
        const formData = new FormData();
        formData.append('filename', filename);
        
        fetch('api/delete_backup.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                alert('✅ ' + data.message);
                loadBackups();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            hideLoading();
            alert('❌ Lỗi: ' + err.message);
        });
    }

    // Show loading overlay
    function showLoading(text) {
        document.getElementById('loadingText').textContent = text;
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    // Hide loading overlay
    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
  </script>
</body>
</html>
