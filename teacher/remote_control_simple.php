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

// Generate a simple unique session ID
$session_id = 'remote_' . time();

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
                            <p class="mb-3">Truy cập URL từ điện thoại:</p>
                            <p class="text-muted small">URL: <code><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/cvd2/teacher/remote_mobile.php?session=' . $session_id); ?></code></p>
                            <p class="mb-3">Session ID: <strong><?php echo $session_id; ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let sessionId = '<?php echo $session_id; ?>';
let pollingInterval;

function pollForCommands() {
    console.log('Polling for commands with session:', sessionId);
    fetch('api/remote_commands.php?session=' + sessionId)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.commands && data.commands.length > 0) {
                console.log('Executing commands:', data.commands);
                executeCommands(data.commands);
            }
        })
        .catch(error => {
            console.error('Error polling:', error);
            document.getElementById('status-message').innerHTML = '<strong>Lỗi:</strong> ' + error.message;
        });
}

function executeCommands(commands) {
    commands.forEach(command => {
        console.log('Executing:', command.type);
        const statusMessage = document.getElementById('status-message');
        statusMessage.className = 'alert alert-success';
        statusMessage.innerHTML = '<strong>Lệnh nhận được:</strong> ' + command.type.toUpperCase() + ' <i class="bi bi-check-circle"></i>';

        // Visual feedback
        const display = document.getElementById('remote-display');
        display.innerHTML = `
            <div class="text-center">
                <i class="bi bi-check-circle-fill display-4 text-success mb-3"></i>
                <h4 class="text-success">Lệnh đã thực hiện!</h4>
                <p class="text-muted">${command.type.replace('_', ' ').toUpperCase()}</p>
            </div>
        `;

        setTimeout(() => {
            display.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-display display-4 text-secondary mb-3"></i>
                    <p class="text-muted">Đang chờ lệnh từ điện thoại...</p>
                    <div id="status-message" class="alert alert-info">
                        <strong>Trạng Thái:</strong> Sẵn sàng nhận lệnh
                    </div>
                </div>
            `;
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Starting polling...');
    pollingInterval = setInterval(pollForCommands, 1000); // Poll every 1 second
});

window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
