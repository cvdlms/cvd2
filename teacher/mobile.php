<?php
/**
 * Mobile Remote Control - Public Version (No Session Check)
 * Minimal dependencies to work on any hosting
 */

// Get session ID from URL parameter
$session_id = $_GET['session'] ?? '';
if (empty($session_id)) {
    http_response_code(400);
    die('<h3>Error: Invalid or missing session parameter</h3>');
}

// Sanitize session ID
$session_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $session_id);

$title = 'Điều Khiển PowerPoint';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
        }
        .container-fluid {
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            color: #333;
        }
        .control-btn {
            height: 90px;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .control-btn:active {
            transform: scale(0.95);
        }
        .status-box {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
        }
        .status-text {
            margin: 0;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <div class="text-center mb-4">
                    <h1 class="mb-2" style="font-size: 2rem;">🎬 Điều Khiển PowerPoint</h1>
                    <p style="font-size: 0.9rem; opacity: 0.9;">Session: <code><?php echo htmlspecialchars($session_id); ?></code></p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-success w-100 control-btn" onclick="sendCommand('start_slideshow')">
                                    <i class="bi bi-play-fill"></i><br><small>Trình chiếu</small>
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-danger w-100 control-btn" onclick="sendCommand('stop_slideshow')">
                                    <i class="bi bi-stop-fill"></i><br><small>Dừng chiếu</small>
                                </button>
                            </div>

                            <div class="col-6">
                                <button class="btn btn-primary w-100 control-btn" onclick="sendCommand('prev_slide')">
                                    <i class="bi bi-chevron-left"></i><br><small>Slide trước</small>
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-primary w-100 control-btn" onclick="sendCommand('next_slide')">
                                    <i class="bi bi-chevron-right"></i><br><small>Slide sau</small>
                                </button>
                            </div>
                        </div>

                        <div class="status-box">
                            <div id="status-message" class="status-text text-center">✅ Sẵn sàng</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const sessionId = <?php echo json_encode($session_id); ?>;
        const apiUrl = '/cvd2/teacher/api/remote_commands.php';

        async function sendCommand(command) {
            const statusMsg = document.getElementById('status-message');
            
            try {
                statusMsg.textContent = '⏳ Đang gửi...';
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session: sessionId,
                        command: command
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    statusMsg.textContent = '✅ Lệnh đã gửi!';
                    setTimeout(() => {
                        statusMsg.textContent = '✅ Sẵn sàng';
                    }, 1500);
                } else {
                    statusMsg.textContent = '❌ Lỗi: ' + (data.message || 'Gửi không được');
                }
            } catch (error) {
                console.error('Error:', error);
                statusMsg.textContent = '❌ Lỗi kết nối: ' + error.message;
            }
        }

        // Optional: Poll status from server
        function pollStatus() {
            fetch('/cvd2/teacher/api/remote_status.php?session=' + encodeURIComponent(sessionId))
                .then(r => r.json())
                .then(data => {
                    // Status polling (optional)
                })
                .catch(() => {});
        }

        setInterval(pollStatus, 2000);
    </script>
</body>
</html>
