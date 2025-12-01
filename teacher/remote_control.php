<?php
session_start();
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Generate a unique session ID for this remote control session
$session_id = session_id() . '_' . time();

$title = 'Điều Khiển Từ Xa - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
    <div class="container my-5">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="mb-4">Điều Khiển Từ Xa</h1>
                <p class="lead mb-5">Sử dụng điện thoại để điều khiển máy tính này.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Màn Hình Máy Tính</h5>
                    </div>
                    <div class="card-body">
                        <div id="remote-display" class="border p-4 mb-4" style="min-height: 300px; background-color: #f8f9fa;">
                            <div class="text-center">
                                <i class="bi bi-display display-4 text-secondary mb-3"></i>
                                <p class="text-muted">Đang chờ lệnh từ điện thoại...</p>
                                <div id="status-message" class="alert alert-info">
                                    <strong>Trạng Thái:</strong> Sẵn sàng nhận lệnh
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <p class="mb-3">Quét mã QR hoặc truy cập URL từ điện thoại:</p>
                            <div id="qr-code" class="mb-3"></div>
                            <p class="text-muted small">URL: <code><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/cvd2/teacher/remote_mobile.php?session=' . $session_id); ?></code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
let sessionId = '<?php echo $session_id; ?>';
let pollingInterval;

function generateQRCode() {
    const url = window.location.protocol + '//' + window.location.host + '/cvd2/teacher/remote_mobile.php?session=' + sessionId;
    QRCode.toCanvas(document.getElementById('qr-code'), url, {
        width: 200,
        height: 200
    }, function (error) {
        if (error) console.error(error);
    });
}

function pollForCommands() {
    fetch('api/remote_commands.php?session=' + sessionId)
        .then(response => response.json())
        .then(data => {
            if (data.commands && data.commands.length > 0) {
                executeCommands(data.commands);
            }
        })
        .catch(error => console.error('Error polling for commands:', error));
}

function executeCommands(commands) {
    commands.forEach(command => {
        switch(command.type) {
            case 'next_slide':
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> Chuyển slide tiếp theo';
                // Add slide navigation logic here
                break;
            case 'prev_slide':
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> Chuyển slide trước';
                // Add slide navigation logic here
                break;
            case 'start_quiz':
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> Bắt đầu bài kiểm tra';
                // Add quiz start logic here
                break;
            case 'stop_quiz':
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> Dừng bài kiểm tra';
                // Add quiz stop logic here
                break;
            case 'show_results':
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> Hiển thị kết quả';
                // Add results display logic here
                break;
            default:
                document.getElementById('status-message').innerHTML = '<strong>Lệnh:</strong> ' + command.type;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    generateQRCode();
    pollingInterval = setInterval(pollForCommands, 2000); // Poll every 2 seconds
});

window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
