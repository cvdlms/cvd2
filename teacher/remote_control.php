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
                                            <div id="remote-display" class="border p-4 mb-4 position-relative" style="min-height: 300px; background-color: #f8f9fa;">
                            <div class="text-center">
                                <i class="bi bi-display display-4 text-secondary mb-3"></i>
                                <p class="text-muted">Đang chờ lệnh từ điện thoại...</p>
                                <div id="status-message" class="alert alert-info">
                                    <strong>Trạng Thái:</strong> Sẵn sàng nhận lệnh
                                </div>
                            </div>
                                                <!-- Cursor overlay for remote mouse -->
                                                <div id="remote-cursor" style="position:absolute; width:22px; height:22px; border-radius:50%; background:rgba(30,58,138,0.9); transform:translate(-50%,-50%); pointer-events:none; display:none; z-index:2000; box-shadow:0 0 6px rgba(0,0,0,0.25);"></div>
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
const sessionId = <?php echo json_encode($session_id); ?>;
let pollingInterval;

function generateQRCode() {
    const url = window.location.protocol + '//' + window.location.host + '/cvd2/teacher/remote_mobile.php?session=' + sessionId;
    // Use toDataURL to render QR as an image inside the #qr-code element
    QRCode.toDataURL(url, { width: 200, margin: 1 }, function (err, dataUrl) {
        if (err) {
            console.error('QR error', err);
            return;
        }
        const img = document.createElement('img');
        img.src = dataUrl;
        img.alt = 'QR Code';
        img.width = 200;
        img.height = 200;
        const container = document.getElementById('qr-code');
        container.innerHTML = '';
        container.appendChild(img);
    });
}

function pollForCommands() {
    fetch('api/remote_commands.php?session=' + encodeURIComponent(sessionId))
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
        const msgEl = document.getElementById('status-message');
        const cursor = document.getElementById('remote-cursor');
        const display = document.getElementById('remote-display');
        switch (command.type) {
            case 'next_slide':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Chuyển slide tiếp theo';
                // Try dispatching keyboard ArrowRight or call common presentation APIs
                try { document.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',keyCode:39,which:39})); } catch(e){}
                if (typeof Reveal !== 'undefined' && Reveal.next) Reveal.next();
                break;
            case 'prev_slide':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Chuyển slide trước';
                try { document.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowLeft',keyCode:37,which:37})); } catch(e){}
                if (typeof Reveal !== 'undefined' && Reveal.prev) Reveal.prev();
                break;
            case 'start_quiz':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Bắt đầu bài kiểm tra';
                // TODO: start quiz flow
                break;
            case 'stop_quiz':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Dừng bài kiểm tra';
                // TODO: stop quiz flow
                break;
            case 'show_results':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Hiển thị kết quả';
                // TODO: show results UI
                break;
            case 'mouse_move':
                // Expect command.payload = {x:0..1, y:0..1} relative coordinates
                try {
                    const p = command.payload || {};
                    const x = (typeof p.x === 'number') ? p.x : 0.5;
                    const y = (typeof p.y === 'number') ? p.y : 0.5;
                    const rect = display.getBoundingClientRect();
                    const px = rect.left + x * rect.width;
                    const py = rect.top + y * rect.height;
                    cursor.style.display = 'block';
                    cursor.style.left = (x * 100) + '%';
                    cursor.style.top = (y * 100) + '%';
                    // dispatch synthetic mousemove to underlying element
                    const el = document.elementFromPoint(px, py);
                    if (el) {
                        const ev = new MouseEvent('mousemove', {clientX: px, clientY: py, bubbles: true});
                        el.dispatchEvent(ev);
                    }
                } catch (e) { console.error(e); }
                break;
            case 'mouse_click':
                try {
                    const p = command.payload || {};
                    const x = (typeof p.x === 'number') ? p.x : 0.5;
                    const y = (typeof p.y === 'number') ? p.y : 0.5;
                    const rect = display.getBoundingClientRect();
                    const px = rect.left + x * rect.width;
                    const py = rect.top + y * rect.height;
                    const el = document.elementFromPoint(px, py);
                    if (el) {
                        ['mousedown','mouseup','click'].forEach(type => {
                            const evt = new MouseEvent(type, {clientX: px, clientY: py, bubbles: true});
                            el.dispatchEvent(evt);
                        });
                    }
                } catch(e) { console.error(e); }
                break;
            case 'fullscreen':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Toàn màn hình / Bắt đầu slideshow (nếu khả dụng)';
                // Try Reveal.js fullscreen API first (presentation frameworks)
                try {
                    if (typeof Reveal !== 'undefined') {
                        if (typeof Reveal.toggleFullscreen === 'function') {
                            Reveal.toggleFullscreen();
                        } else if (typeof Reveal.toggleFullScreen === 'function') {
                            Reveal.toggleFullScreen();
                        }
                    }
                } catch (e) { console.warn('Reveal fullscreen call failed', e); }

                // Try the standard Fullscreen API on the remote display element
                (async function() {
                    try {
                        if (!document.fullscreenElement) {
                            if (display.requestFullscreen) {
                                await display.requestFullscreen();
                            } else if (display.webkitRequestFullscreen) {
                                display.webkitRequestFullscreen();
                            } else if (display.mozRequestFullScreen) {
                                display.mozRequestFullScreen();
                            } else if (display.msRequestFullscreen) {
                                display.msRequestFullscreen();
                            }
                        } else {
                            if (document.exitFullscreen) {
                                await document.exitFullscreen();
                            }
                        }
                    } catch (e) {
                        // Fullscreen API frequently requires a user gesture; try keyboard fallback below
                        console.warn('Fullscreen API failed or blocked:', e);
                        try {
                            // Attempt to dispatch F5 to start a slideshow in browser-based viewers
                            document.dispatchEvent(new KeyboardEvent('keydown', {key:'F5', keyCode:116, which:116, bubbles:true}));
                            document.dispatchEvent(new KeyboardEvent('keyup', {key:'F5', keyCode:116, which:116, bubbles:true}));
                        } catch (ke) { console.warn('Dispatching F5 failed', ke); }
                    }
                })();
                break;
            default:
                msgEl.innerHTML = '<strong>Lệnh:</strong> ' + command.type;
        }
        console.log('Executed command', command.type);
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
