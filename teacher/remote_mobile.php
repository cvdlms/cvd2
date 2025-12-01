<?php
session_start();

// Get session ID from URL parameter
$session_id = $_GET['session'] ?? '';
if (empty($session_id)) {
    die('Invalid session');
}

$title = 'Điều Khiển Từ Xa - Mobile';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .control-btn {
            height: 80px;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .status-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2 class="mb-2">🎛️ Điều Khiển Từ Xa</h2>
                <p class="text-muted">Nhấn nút để điều khiển máy tính</p>
            </div>
        </div>

        <div class="row g-3">
            <!-- Navigation Controls -->
            <div class="col-6">
                <button class="btn btn-primary w-100 control-btn" onclick="sendCommand('prev_slide')">
                    <i class="bi bi-chevron-left"></i><br>Trước
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-primary w-100 control-btn" onclick="sendCommand('next_slide')">
                    <i class="bi bi-chevron-right"></i><br>Sau
                </button>
            </div>

            <!-- Quiz Controls -->
            <div class="col-6">
                <button class="btn btn-success w-100 control-btn" onclick="sendCommand('start_quiz')">
                    <i class="bi bi-play-circle"></i><br>Bắt Đầu Kiểm Tra
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-danger w-100 control-btn" onclick="sendCommand('stop_quiz')">
                    <i class="bi bi-stop-circle"></i><br>Dừng Kiểm Tra
                </button>
            </div>

            <!-- Results -->
            <div class="col-12">
                <button class="btn btn-info w-100 control-btn" onclick="sendCommand('show_results')">
                    <i class="bi bi-bar-chart"></i><br>Hiển Thị Kết Quả
                </button>
            </div>

            <!-- Additional Controls -->
            <div class="col-6">
                <button class="btn btn-warning w-100 control-btn" onclick="sendCommand('lucky_wheel')">
                    <i class="bi bi-arrow-repeat"></i><br>Vòng Quay
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-secondary w-100 control-btn" onclick="sendCommand('fullscreen')">
                    <i class="bi bi-arrows-fullscreen"></i><br>Toàn Màn Hình
                </button>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div id="status-message" class="alert alert-success text-center">
                    Sẵn sàng gửi lệnh
                </div>
            </div>
        </div>
    </div>

    <div class="status-indicator" id="status-indicator"></div>

    <script>
        let sessionId = '<?php echo htmlspecialchars($session_id); ?>';

        function sendCommand(command) {
            const statusMessage = document.getElementById('status-message');
            const statusIndicator = document.getElementById('status-indicator');

            // Show sending status
            statusMessage.className = 'alert alert-warning text-center';
            statusMessage.textContent = 'Đang gửi lệnh...';
            statusIndicator.style.backgroundColor = '#ffc107';

            fetch('api/remote_commands.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session: sessionId,
                    command: command
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusMessage.className = 'alert alert-success text-center';
                    statusMessage.textContent = 'Lệnh đã gửi thành công!';
                    statusIndicator.style.backgroundColor = '#28a745';

                    // Reset status after 2 seconds
                    setTimeout(() => {
                        statusMessage.className = 'alert alert-success text-center';
                        statusMessage.textContent = 'Sẵn sàng gửi lệnh';
                    }, 2000);
                } else {
                    statusMessage.className = 'alert alert-danger text-center';
                    statusMessage.textContent = 'Lỗi: ' + (data.message || 'Không thể gửi lệnh');
                    statusIndicator.style.backgroundColor = '#dc3545';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusMessage.className = 'alert alert-danger text-center';
                statusMessage.textContent = 'Lỗi kết nối';
                statusIndicator.style.backgroundColor = '#dc3545';
            });
        }

        // Prevent accidental navigation
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });

        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>
