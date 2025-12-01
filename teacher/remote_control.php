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

// Use provided session param if present (allows teacher to resume existing session), otherwise generate a new one
$provided = $_GET['session'] ?? '';
if (!empty($provided)) {
    // sanitize to allowed characters
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $provided);
    $session_id = $safe;
} else {
    // Generate a unique session ID for this remote control session
    $session_id = session_id() . '_' . time();
}

// Get the proper host for mobile URL (prefer domain name over IP)
// If accessed via IP address, try to use domain name if available
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$server_name = $_SERVER['SERVER_NAME'] ?? $http_host;

// Prioritize SERVER_NAME (domain) over HTTP_HOST (which might be IP)
$preferred_host = $server_name;
if (strpos($preferred_host, '.') === false) {
    // If SERVER_NAME is not a domain (localhost or similar), use HTTP_HOST
    $preferred_host = $http_host;
}

// If still using IP, try to get domain from environment or hardcoded fallback
if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $preferred_host)) {
    // This is an IP address, try alternative methods
    
    // Method 1: Check if there's a domain in Apache config (VirtualHost)
    $possible_domains = array('psmcvn.com', 'www.psmcvn.com');
    foreach ($possible_domains as $domain) {
        // If domain matches server setup, prefer it
        if (!empty($domain) && $domain !== 'localhost') {
            $preferred_host = $domain;
            break;
        }
    }
}

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
                <!-- Socket.IO Version (Recommended) -->
                <div class="card shadow mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">✨ Socket.IO Remote Control (Khuyến Nghị)</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            <strong>Phiên bản mới - Đơn giản, Nhanh, Ổn Định</strong>
                        </p>
                        <ul class="small mb-3">
                            <li>✅ Điều khiển chuột thực thời (touchpad)</li>
                            <li>✅ 4 nút PowerPoint (Trình chiếu, Dừng, Slide trước, Slide sau)</li>
                            <li>✅ Tốc độ cực nhanh (WebSocket real-time)</li>
                            <li>✅ Không cần file locking hay polling</li>
                        </ul>
                        <div class="text-center">
                            <?php
                                $socketio_url = $scheme . '://' . $preferred_host . ':5000/?token=socketio123';
                                $socketio_qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($socketio_url);
                            ?>
                            <p class="small text-muted mb-2">Mã QR hoặc link:</p>
                            <img src="<?php echo htmlspecialchars($socketio_qr_api); ?>" alt="Socket.IO QR Code" width="150" height="150" class="mb-2" />
                            <p class="small mb-2">
                                <a href="<?php echo htmlspecialchars($socketio_url); ?>" target="_blank" class="link-success">
                                    <?php echo htmlspecialchars($socketio_url); ?>
                                </a>
                            </p>
                            <p class="small text-muted">
                                <strong>Lưu ý:</strong> Socket.IO server phải đang chạy (bấn <code>start_socketio_server.bat</code> trên máy tính)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Màn Hình Máy Tính (Phiên Bản Cũ)</h5>
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
                            <!-- Download Controller Section -->
                            <div class="alert alert-info mb-4">
                                <p class="mb-2"><strong>Bước 1: Tải File Cài Đặt (Trên Máy Tính Giáo Viên)</strong></p>
                                <p class="small mb-3">Tải file <code>ppt_controller.exe</code> về máy tính để có khả năng điều khiển PowerPoint từ điện thoại.</p>
                                <a href="api/download_exe.php" class="btn btn-success btn-sm" download>
                                    <i class="bi bi-download"></i> Tải ppt_controller.exe
                                </a>
                                <small class="d-block mt-2">Sau khi tải, double-click file để chạy. File này cho phép điều khiển bàn phím/chuột trên PowerPoint.</small>
                            </div>

                            <hr>

                            <p class="mb-3"><strong>Bước 2: Quét Mã QR Từ Điện Thoại</strong></p>
                            <p class="small mb-3">Mở ứng dụng Camera hoặc QR Scanner trên điện thoại và quét mã dưới đây:</p>
                            <div id="qr-code" class="mb-3"></div>
                            <?php
                                // Build a fully qualified mobile URL so QR code and displayed link are correct
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                // Use preferred host (domain name > IP address)
                                $remote_mobile_url = $scheme . '://' . $preferred_host . '/cvd2/teacher/mobile.php?session=' . $session_id;
                            ?>
                            <p class="text-muted small">URL: <a id="mobile-link" href="<?php echo htmlspecialchars($remote_mobile_url); ?>" target="_blank"><?php echo htmlspecialchars($remote_mobile_url); ?></a></p>
                            
                            <div class="input-group input-group-sm mb-3" style="max-width:100%;">
                                <span class="input-group-text">Domain/Host</span>
                                <input id="qr-host-manual" class="form-control" value="<?php echo htmlspecialchars($preferred_host); ?>" placeholder="psmcvn.com" />
                                <button id="update-host-btn" class="btn btn-outline-secondary" onclick="updateMobileUrl()">Cập nhật</button>
                            </div>

                            <?php
                                // Warn if host is localhost since phone won't reach it
                                if (strpos($preferred_host, 'localhost') !== false || strpos($preferred_host, '127.0.0.1') !== false) {
                                    echo '<div class="alert alert-warning small mt-2">Lưu ý: URL đang dùng <strong>localhost</strong>. Nếu bạn quét mã từ điện thoại, hãy thay <code>localhost</code> bằng địa chỉ IP LAN của máy tính (ví dụ: 192.168.x.x:8080) hoặc mở trang bằng địa chỉ IP của máy tính.</div>';
                                }

                                // Try to detect a candidate LAN IP for convenience
                                $detected_host = '';
                                $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
                                if (!empty($serverAddr) && $serverAddr !== '127.0.0.1' && $serverAddr !== '::1') {
                                    $detected_host = $serverAddr . (strpos($_SERVER['HTTP_HOST'], ':') !== false ? strstr($_SERVER['HTTP_HOST'], ':') : '');
                                } else {
                                    $g = gethostbyname(gethostname());
                                    if ($g && $g !== '127.0.0.1') {
                                        $detected_host = $g . (strpos($_SERVER['HTTP_HOST'], ':') !== false ? strstr($_SERVER['HTTP_HOST'], ':') : '');
                                    } else {
                                        $detected_host = $_SERVER['HTTP_HOST'];
                                    }
                                }
                            ?>

                            <div class="input-group input-group-sm mb-2" style="max-width:520px; margin:0 auto;">
                                <span class="input-group-text">Host for mobile</span>
                                <input id="qr-host" class="form-control" value="<?php echo htmlspecialchars($detected_host); ?>" />
                                <button id="regenerate-qr" class="btn btn-outline-primary">Regenerate QR</button>
                            </div>
                            <?php
                                // Warn if host is localhost since phone won't reach it
                                if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                                    echo '<div class="alert alert-warning small mt-2">Lưu ý: URL đang dùng <strong>localhost</strong>. Nếu bạn quét mã từ điện thoại, hãy thay <code>localhost</code> bằng địa chỉ IP LAN của máy tính (ví dụ: 192.168.x.x:8080) hoặc mở trang bằng địa chỉ IP của máy tính.</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const sessionId = <?php echo json_encode($session_id); ?>;
const remoteMobileUrl = <?php echo json_encode($remote_mobile_url ?? ''); ?>;
const detectedHost = <?php echo json_encode($detected_host ?? ''); ?>;
let pollingInterval;
let qrCodeReady = false;

// Load QRCode library with proper callback and fallback
function loadQRCodeLibrary(callback) {
    if (typeof QRCode !== 'undefined') {
        callback();
        return;
    }
    
    // Try multiple CDN sources
    const cdnUrls = [
        'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
        'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js'
    ];
    
    function tryLoadFromUrl(index) {
        if (index >= cdnUrls.length) {
            console.warn('All CDN sources failed, using API-based QR generation');
            qrCodeReady = true;
            callback();
            return;
        }
        
        const script = document.createElement('script');
        script.src = cdnUrls[index];
        script.timeout = 5000;
        
        script.onload = function() {
            console.log('QRCode library loaded from:', cdnUrls[index]);
            qrCodeReady = true;
            callback();
        };
        
        script.onerror = function() {
            console.warn('Failed to load from', cdnUrls[index], 'trying next...');
            tryLoadFromUrl(index + 1);
        };
        
        document.head.appendChild(script);
    }
    
    tryLoadFromUrl(0);
}

function generateQRCode() {
    // Priority: qr-host-manual (new input) > qr-host (old input) > remoteMobileUrl > window.location.host
    let host = window.location.host;
    
    const hostInputManual = document.getElementById('qr-host-manual');
    if (hostInputManual && hostInputManual.value) {
        host = hostInputManual.value;
    } else {
        const hostInputOld = document.getElementById('qr-host');
        if (hostInputOld && hostInputOld.value) {
            host = hostInputOld.value;
        } else if (remoteMobileUrl) {
            try {
                host = new URL(remoteMobileUrl).host;
            } catch(e) {
                console.warn('Could not parse remoteMobileUrl:', e);
            }
        }
    }
    
    const url = buildMobileUrl(host);
    
    // update the visible link too
    try { const link = document.getElementById('mobile-link'); if (link) { link.href = url; link.textContent = url; } } catch(e){}
    
    // Try using QRCode library if available (cdnjs version uses canvas, not toDataURL)
    if (typeof QRCode !== 'undefined') {
        try {
            const container = document.getElementById('qr-code');
            container.innerHTML = ''; // Clear previous QR
            
            // cdnjs QRCode uses canvas rendering, not toDataURL
            // Create a div for the canvas
            const qrDiv = document.createElement('div');
            container.appendChild(qrDiv);
            
            // Render QR code using the library
            new QRCode(qrDiv, {
                text: url,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            return;
        } catch (e) {
            console.warn('QRCode library error:', e);
        }
    }
    
    // Fallback to API-based generation
    generateQRCodeViaAPI(url);
}

function generateQRCodeViaAPI(url) {
    // Use a public QR code API as fallback
    const apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
    const img = document.createElement('img');
    img.src = apiUrl;
    img.alt = 'QR Code';
    img.width = 200;
    img.height = 200;
    img.onerror = function() {
        // Last resort: show plain text link
        const container = document.getElementById('qr-code');
        container.innerHTML = '<p class="text-muted">QR code unavailable. Manually enter URL:</p>';
    };
    const container = document.getElementById('qr-code');
    container.innerHTML = '';
    container.appendChild(img);
}

function buildMobileUrl(host) {
    const scheme = (window.location.protocol && window.location.protocol.indexOf('http') === 0) ? window.location.protocol.replace(':','') : 'http';
    // allow host to include port
    // Updated to use mobile.php instead of remote_mobile.php for better hosting compatibility
    return scheme + '://' + host + '/cvd2/teacher/mobile.php?session=' + encodeURIComponent(sessionId);
}

function updateMobileUrl() {
    const hostInput = document.getElementById('qr-host-manual');
    if (!hostInput || !hostInput.value) return;
    
    const newHost = hostInput.value.trim();
    const url = buildMobileUrl(newHost);
    
    // Update the link
    const link = document.getElementById('mobile-link');
    if (link) {
        link.href = url;
        link.textContent = url;
    }
    
    // Regenerate QR code
    generateQRCode();
    
    // Show success message
    alert('URL cập nhật thành công! QR code đã được tạo lại.\n\nURL mới: ' + url);
}

// Function to send status ACK back to mobile
function postStatus(session, status, message) {
    fetch('api/remote_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session: session,
            status: status,
            message: message || ''
        })
    })
    .catch(error => console.warn('postStatus error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Load QRCode library and then initialize
    loadQRCodeLibrary(function() {
        // wire regenerate button
        const regen = document.getElementById('regenerate-qr');
        if (regen) regen.addEventListener('click', function(e){ e.preventDefault(); generateQRCode(); });
        // if detectedHost is set and qr-host is empty, set it
        try { const h = detectedHost; const inp = document.getElementById('qr-host'); if (h && inp && (!inp.value || inp.value.indexOf('localhost') !== -1)) inp.value = h; } catch(e){}
        // Generate QR once library is ready
        generateQRCode();
        // Start polling for commands
        pollingInterval = setInterval(pollForCommands, 2000);
    });
});

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
            case 'start_slideshow':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Bắt đầu slideshow / Start slideshow';
                try {
                    if (typeof Reveal !== 'undefined') {
                        if (typeof Reveal.toggleFullscreen === 'function') Reveal.toggleFullscreen();
                        else if (typeof Reveal.toggleFullScreen === 'function') Reveal.toggleFullScreen();
                    }
                } catch (e) { console.warn('Reveal start failed', e); }
                try { document.dispatchEvent(new KeyboardEvent('keydown',{key:'F5',keyCode:116,which:116,bubbles:true})); } catch(e){}
                // Inform mobile that slideshow was requested
                postStatus(sessionId, 'start_slideshow_requested', 'Requested to start slideshow');
                break;
            case 'stop_slideshow':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Dừng slideshow / Stop slideshow';
                try { document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',keyCode:27,which:27,bubbles:true})); } catch(e){}
                // exitFullscreen returns a Promise, need to handle it properly
                try {
                    if (document.fullscreenElement) {
                        document.exitFullscreen().catch(() => {
                            // Silently ignore if not in fullscreen
                        });
                    }
                } catch(e) {
                    // Silently ignore if not supported or already not fullscreen
                }
                postStatus(sessionId, 'stop_slideshow_requested', 'Requested to stop slideshow');
                break;
            case 'fullscreen':
                msgEl.innerHTML = '<strong>Lệnh:</strong> Yêu cầu toàn màn hình — yêu cầu xác nhận từ giáo viên';
                // Because most browsers require a real user gesture to enter fullscreen,
                // show a visible button the teacher can click to grant fullscreen.
                try {
                    showFullscreenPrompt(display);
                } catch (e) {
                    console.warn('Failed to show fullscreen prompt', e);
                }
                break;
            default:
                msgEl.innerHTML = '<strong>Lệnh:</strong> ' + command.type;
        }
        console.log('Executed command', command.type);
    });
}

// Create and show a fullscreen prompt button overlayed on the display element.
function showFullscreenPrompt(targetEl) {
    // If a prompt already exists, don't duplicate
    if (document.getElementById('fullscreen-prompt')) return;

    const prompt = document.createElement('div');
    prompt.id = 'fullscreen-prompt';
    prompt.style.position = 'absolute';
    prompt.style.left = '50%';
    prompt.style.top = '12px';
    prompt.style.transform = 'translateX(-50%)';
    prompt.style.zIndex = 3000;
    prompt.innerHTML = `
        <button id="enter-fullscreen" class="btn btn-lg btn-warning">Bấm để vào Toàn Màn Hình</button>
        <button id="dismiss-fullscreen" class="btn btn-sm btn-outline-secondary ms-2">Đóng</button>
    `;

    // position relative to document body but visually over the display area
    document.body.appendChild(prompt);

    // Hook up button handlers
    // notify mobile that prompt shown
    try { fetch('api/remote_status.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session: sessionId, status:'prompt_shown', message:'Teacher prompt shown' }) }).catch(()=>{}); } catch(e){}

    document.getElementById('enter-fullscreen').addEventListener('click', async function() {
        try {
            // Try Reveal.js API first
            if (typeof Reveal !== 'undefined') {
                if (typeof Reveal.toggleFullscreen === 'function') {
                    Reveal.toggleFullscreen();
                } else if (typeof Reveal.toggleFullScreen === 'function') {
                    Reveal.toggleFullScreen();
                }
            }

            // Then request fullscreen on the provided element
            if (targetEl.requestFullscreen) {
                await targetEl.requestFullscreen();
            } else if (targetEl.webkitRequestFullscreen) {
                targetEl.webkitRequestFullscreen();
            } else if (targetEl.mozRequestFullScreen) {
                targetEl.mozRequestFullScreen();
            } else if (targetEl.msRequestFullscreen) {
                targetEl.msRequestFullscreen();
            }
        } catch (err) {
            console.warn('Fullscreen request failed:', err);
            alert('Thao tác toàn màn hình bị chặn bởi trình duyệt. Hãy thử bấm F11 hoặc kiểm tra cài đặt trình duyệt.');
        }
        // notify mobile that teacher accepted fullscreen
        try { await fetch('api/remote_status.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ session: sessionId, status:'fullscreen_entered', message:'Teacher entered fullscreen' }) }); } catch(e){}
        removeFullscreenPrompt();
    });

    document.getElementById('dismiss-fullscreen').addEventListener('click', function() {
        removeFullscreenPrompt();
    });
}

function removeFullscreenPrompt() {
    const p = document.getElementById('fullscreen-prompt');
    if (p) p.remove();
}

window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
