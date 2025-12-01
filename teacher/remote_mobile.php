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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
            <!-- Touchpad area -->
            <div class="col-12 mb-3">
                <div id="touchpad" class="border rounded" style="height:300px; background:#fff; touch-action:none; -webkit-user-select:none; user-select:none;">
                    <div id="touch-instructions" class="text-center text-muted mt-3">Kéo để di chuyển con trỏ, chạm để click</div>
                </div>
            </div>
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
        const sessionId = <?php echo json_encode($session_id); ?>;

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

        // Touchpad handling: send normalized coordinates (0..1) relative to the touchpad rect
        (function() {
            const pad = document.getElementById('touchpad');
            if (!pad) return;

            let lastSent = 0;
            const throttleMs = 50; // ~20 updates/sec

            function getNormalized(e) {
                const rect = pad.getBoundingClientRect();
                let clientX, clientY;
                if (e.touches && e.touches[0]) {
                    clientX = e.touches[0].clientX; clientY = e.touches[0].clientY;
                } else {
                    clientX = e.clientX; clientY = e.clientY;
                }
                const x = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
                const y = Math.max(0, Math.min(1, (clientY - rect.top) / rect.height));
                return {x,y};
            }

            function sendMove(e) {
                const now = Date.now();
                if (now - lastSent < throttleMs) return;
                lastSent = now;
                const p = getNormalized(e);
                fetch('api/remote_commands.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ session: sessionId, command: 'mouse_move', payload: p })
                }).catch(()=>{});
            }

            pad.addEventListener('touchmove', function(ev){ ev.preventDefault(); sendMove(ev); }, {passive:false});
            pad.addEventListener('mousemove', function(ev){ if (ev.buttons) sendMove(ev); });

            // Tap to click
            pad.addEventListener('click', function(ev){
                const p = getNormalized(ev);
                fetch('api/remote_commands.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ session: sessionId, command: 'mouse_click', payload: p })
                }).catch(()=>{});
            });
        })();
    </script>
</body>
</html>
