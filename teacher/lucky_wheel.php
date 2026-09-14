<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Load teacher's assigned classes
$teacherClasses = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_classes.json'), true);
$assignedClasses = $teacherClasses[$username] ?? [];

$title = 'Vòng Quay May Mắn - CVD';
include '../includes/teacher_header.php';
?>

    <div class="lucky-wheel-container" id="luckyWheelFullscreen">
        <!-- Animated Background -->
        <div class="animated-bg">
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
            <div class="star"></div>
        </div>

        <!-- Header Section -->
        <div class="wheel-header text-center">
            <div class="header-content">
                <div class="icon-wrapper mb-3">
                    <i class="fas fa-star fa-spin"></i>
                    <i class="fas fa-dharmachakra main-icon"></i>
                    <i class="fas fa-star fa-spin"></i>
                </div>
                <h1 class="display-4 fw-bold text-gradient mb-3">Vòng Quay May Mắn</h1>
                <p class="lead text-muted mb-0">Chọn ngẫu nhiên học sinh một cách công bằng và thú vị!</p>
            </div>
        </div>

        <div class="main-content">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <!-- Class Selection Card -->
                    <div class="selection-card mb-4">
                        <div class="card-glow"></div>
                        <div class="selection-content">
                            <label for="classSelect" class="selection-label">
                                <i class="fas fa-users-class me-2"></i>
                                Chọn Lớp Học
                            </label>
                            <select class="form-select form-select-lg" id="classSelect">
                                <option value="">🎯 Chọn lớp để bắt đầu...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Wheel Card -->
                    <div class="wheel-card">
                        <div class="card-shine"></div>
                        
                        <!-- Wheel Container -->
                        <div id="wheelContainer" class="wheel-main" style="display: none;">
                            <div class="wheel-columns">
                                <div class="wheel-column-main">
                                <!-- Wheel Wrapper -->
                                <div class="wheel-wrapper">
                                <!-- Decorative Elements -->
                                <div class="wheel-glow"></div>
                                
                                <!-- Wheel Canvas -->
                                <div class="canvas-container">
                                    <canvas id="wheelCanvas" width="500" height="500"></canvas>
                                    
                                    <!-- Center Button -->
                                    <div class="center-button" onclick="spinWheel()">
                                        <div class="button-inner">
                                            <i class="fas fa-play"></i>
                                            <span>QUAY</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Pointer Arrow (real wheel style) -->
                                    <div class="wheel-pointer">
                                        <div class="pointer-hub"></div>
                                        <div class="pointer-tip"></div>
                                        <div class="pointer-glow"></div>
                                    </div>

                                    <!-- Fixed selection zone under the pointer -->
                                    <div class="pointer-zone"></div>
                                </div>
                            </div>
                            </div>

                            <!-- Wheel Column Side -->
                            <div class="wheel-column-side">

                            <!-- Result Display -->
                            <div class="result-display mt-4">
                                <div class="result-header">
                                    <i class="fas fa-trophy me-2"></i>
                                    <span>Kết Quả</span>
                                </div>
                                <div id="nameScroller" class="name-scroller">
                                    <div id="nameList" class="name-text">Nhấn QUAY để bắt đầu!</div>
                                </div>
                                <div class="result-decoration">
                                    <div class="sparkle"></div>
                                    <div class="sparkle"></div>
                                    <div class="sparkle"></div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="instructions mt-4">
                                <div class="instruction-item">
                                    <div class="step-number">1</div>
                                    <div class="step-text">Chọn lớp học</div>
                                </div>
                                <div class="instruction-arrow">→</div>
                                <div class="instruction-item">
                                    <div class="step-number">2</div>
                                    <div class="step-text">Nhấn nút QUAY</div>
                                </div>
                                <div class="instruction-arrow">→</div>
                                <div class="instruction-item">
                                    <div class="step-number">3</div>
                                    <div class="step-text">Xem kết quả</div>
                                </div>
                            </div>

                            <!-- History & Stats Panel -->
                            <div class="history-panel" id="historyPanel">
                                <div class="history-header">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Thống Kê Phiên Quay
                                </div>
                                <div class="history-body">
                                    <div class="history-stats">
                                        <div class="stat-item">
                                            <div class="stat-value" id="totalSpins">0</div>
                                            <div class="stat-label">Lượt quay</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value" id="uniqueStudents">0</div>
                                            <div class="stat-label">Đã gọi</div>
                                        </div>
                                    </div>
                                    <div class="history-section-title">
                                        <i class="fas fa-clock-rotate-left me-1"></i> Danh sách đã gọi
                                    </div>
                                    <div id="historyList" class="history-list">
                                        <div class="history-empty">Chưa có lượt quay nào</div>
                                    </div>
                                </div>
                            </div>
                            </div>

                        </div>

                        <!-- Loading Indicator -->
                        <div id="loading" class="loading-state" style="display: none;">
                            <div class="loading-spinner">
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                            </div>
                            <p class="loading-text">Đang tải danh sách học sinh...</p>
                        </div>

                        <!-- No Students Message -->
                        <div id="noStudents" class="empty-state" style="display: none;">
                            <div class="empty-icon">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <h6 class="fw-700">Không có học sinh</h6>
                            <p>Lớp học này chưa có học sinh nào. Vui lòng chọn lớp khác.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Fullscreen Toggle -->
        <button type="button" id="fullscreenBtn" class="fullscreen-btn" onclick="toggleFullscreen()" title="Toàn màn hình" style="display: none;">
            <i class="fas fa-expand"></i>
            <span class="fs-tooltip">Toàn màn hình</span>
        </button>

        <!-- Footer -->
        <div class="wheel-footer text-center">
            <p class="mb-0">Powered by <a href="https://psmcvn.com/" target="_blank" class="footer-link">PSMCVN</a></p>
        </div>
    </div>

    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        let students = [];
        let canvas, ctx;
        let isSpinning = false;
        let nameScrollTimer;
        let currentSpinProgress = 0;
        let audioCtx = null;
        let totalSpinCount = 0;
        let selectionCounts = {};
        let spinHistory = [];

        // Custom confetti layer inside the fullscreenable container so it stays
        // visible even when the wheel is in fullscreen (top layer).
        const confettiLayer = document.createElement('canvas');
        confettiLayer.className = 'confetti-layer';
        document.getElementById('luckyWheelFullscreen').appendChild(confettiLayer);
        const confettiInstance = confetti.create(confettiLayer, { resize: true, useWorker: true });

        // Colors for wheel segments - vibrant gradient colors
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
            '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', 
            '#BB8FCE', '#85C1E9', '#F8B739', '#FF8B94',
            '#A8E6CF', '#FFD3B6', '#FFAAA5', '#B4A7D6'
        ];

        // Short, distinctive label for a student on the wheel
        function getStudentLabel(index) {
            const s = students[index];
            if (!s) return '#' + (index + 1);
            return s.name || s.code || '#' + (index + 1);
        }

        // Shared audio context (created on first user gesture, then reused)
        function getAudioContext() {
            if (!audioCtx) {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return null;
                audioCtx = new Ctx();
            }
            if (audioCtx.state === 'suspended') audioCtx.resume();
            return audioCtx;
        }

        // Sharp per-segment "click" while the pointer passes each segment edge
        function playTick(intensity) {
            const ac = getAudioContext();
            if (!ac) return;
            const t = ac.currentTime;
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(2300 - (1 - intensity) * 1000, t);
            gain.gain.setValueAtTime(0.02 + 0.15 * intensity, t);
            gain.gain.exponentialRampToValueAtTime(0.0008, t + 0.035);
            osc.connect(gain);
            gain.connect(ac.destination);
            osc.start(t);
            osc.stop(t + 0.05);
        }

        // Dull "clack" when the wheel settles
        function playClack() {
            const ac = getAudioContext();
            if (!ac) return;
            const t = ac.currentTime;
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(420, t);
            osc.frequency.exponentialRampToValueAtTime(170, t + 0.1);
            gain.gain.setValueAtTime(0.4, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.12);
            osc.connect(gain);
            gain.connect(ac.destination);
            osc.start(t);
            osc.stop(t + 0.14);
        }

        // Initialize canvas
        function initCanvas() {
            canvas = document.getElementById('wheelCanvas');
            ctx = canvas.getContext('2d');
        }

        // Load assigned classes
        async function loadClasses() {
            try {
                const response = await fetch('api/get_classes.php');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    const classSelect = document.getElementById('classSelect');
                    
                    // API đã filter classes theo teacher rồi, nên không cần filter lại
                    result.data.forEach(classItem => {
                        classSelect.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                    });
                } else if (result.success && result.data.length === 0) {
                    Swal.fire('Thông báo', 'Bạn chưa được phân công lớp nào. Vui lòng liên hệ admin.', 'info');
                }
            } catch (error) {
                console.error('Error loading classes:', error);
                Swal.fire('Lỗi', 'Không thể tải danh sách lớp học.', 'error');
            }
        }

        // Load students for selected class
        async function loadStudents(classId) {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('wheelContainer').style.display = 'none';
            document.getElementById('noStudents').style.display = 'none';
            resetStats();

            try {
                const response = await fetch(`api/get_students.php?class_id=${classId}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    students = result.data;
                    drawWheel();
                    document.getElementById('wheelContainer').style.display = 'block';
                    document.getElementById('fullscreenBtn').style.display = 'flex';
                } else {
                    document.getElementById('noStudents').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading students:', error);
                Swal.fire('Lỗi', 'Không thể tải danh sách học sinh.', 'error');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Draw the wheel with enhanced graphics + labels that match the student list
        function drawWheel() {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 30;
            const anglePerSegment = (2 * Math.PI) / students.length;
            const degreesPerSegment = 360 / students.length;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw outer glow
            ctx.shadowBlur = 20;
            ctx.shadowColor = 'rgba(255, 255, 255, 0.3)';

            students.forEach((student, index) => {
                const startAngle = index * anglePerSegment - Math.PI / 2;
                const endAngle = (index + 1) * anglePerSegment - Math.PI / 2;

                // Draw segment with gradient
                const gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
                gradient.addColorStop(0, colors[index % colors.length]);
                gradient.addColorStop(1, shadeColor(colors[index % colors.length], -20));

                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();

                // Segment border + subtle inner separation line
                ctx.shadowBlur = 0;
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.85)';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.stroke();
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.62, startAngle, endAngle);
                ctx.arc(centerX, centerY, radius * 0.62, endAngle, startAngle, true);
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.35)';
                ctx.lineWidth = 1;
                ctx.stroke();

                // Draw the student label on the segment
                drawLabel(index, startAngle + anglePerSegment / 2, radius, anglePerSegment);
            });

            // Outer rim ring
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 3;
            ctx.stroke();

            // Draw center circle
            ctx.shadowBlur = 0;
            const centerRadius = radius * 0.18;
            const centerGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, centerRadius);
            centerGradient.addColorStop(0, '#ffffff');
            centerGradient.addColorStop(1, '#f0f0f0');

            ctx.beginPath();
            ctx.arc(centerX, centerY, centerRadius, 0, 2 * Math.PI);
            ctx.fillStyle = centerGradient;
            ctx.fill();
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 2;
            ctx.stroke();
        }

        // Draw the full name stacked word-by-word along the segment radius (like a
// real prize wheel). Font size shrinks so every word stays inside its wedge.
function drawLabel(index, midAngle, radius, anglePerSegment) {
    const fullName = getStudentLabel(index);
    const words = (fullName || '').trim().split(/\s+/).filter(Boolean);
    if (words.length === 0) return;

    // Wrap every word on its own radar line; cap at 4 so the wheel stays readable
    let lines = words;
    if (lines.length > 4) {
        lines = [words[0], words[words.length - 1]];
    }

    const rInner = radius * 0.4;
    const rOuter = radius * 0.92;
    const lineGap = (rOuter - rInner) / lines.length;

    const fits = function(size, rLine) {
        ctx.font = '700 ' + size + 'px "Segoe UI", Arial, sans-serif';
        return lines.every((word, j) =>
            ctx.measureText(word).width <= Math.max(12, rLine[j] * anglePerSegment)
        );
    };

    // Compute a valid font size (also bound by line spacing so lines don't overlap)
    let fontSize = Math.min(24, lineGap * 0.95);
    const lineRadii = lines.map((_, j) => rInner + (j + 0.5) * lineGap);
    while (fontSize > 8 && !fits(fontSize, lineRadii)) fontSize -= 0.5;

    ctx.save();
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate(midAngle);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.font = '700 ' + fontSize.toFixed(1) + 'px "Segoe UI", Arial, sans-serif';
    ctx.shadowColor = 'rgba(0,0,0,0.45)';
    ctx.shadowBlur = 3;
    ctx.shadowOffsetX = 1;
    ctx.shadowOffsetY = 1;
    ctx.fillStyle = '#ffffff';
    lines.forEach((word, j) => {
        ctx.fillText(word, lineRadii[j], 0);
    });
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;
    ctx.restore();
}

        // Helper function to shade colors
        function shadeColor(color, percent) {
            const num = parseInt(color.replace("#",""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 +
                (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255))
                .toString(16).slice(1);
        }

        // Start scrolling names (speed slows down together with the wheel)
        function startNameScroll() {
            const nameList = document.getElementById('nameList');
            let currentIndex = 0;

            function step() {
                nameList.textContent = students[currentIndex].name;
                currentIndex = (currentIndex + 1) % students.length;
                gsap.fromTo(nameList,
                    { y: 14, opacity: 0.3 },
                    { y: 0, opacity: 1, duration: 0.12, ease: "power1.out" }
                );

                const p = currentSpinProgress || 0;
                const interval = 85 + (380 - 85) * Math.pow(p, 1.6);
                nameScrollTimer = setTimeout(step, interval);
            }
            step();
        }

        // Stop scrolling names
        function stopNameScroll() {
            if (nameScrollTimer) {
                clearTimeout(nameScrollTimer);
                nameScrollTimer = null;
            }
        }

        // Spin the wheel like a real one: the RED ARROW is the reference. A random
        // final angle is generated first; whoever's segment is under the arrow
        // when the wheel stops becomes the winner (arrow decides, wheel obeys).
        function spinWheel() {
            if (isSpinning || students.length === 0) return;

            isSpinning = true;

            // Disable center button during spin
            document.querySelector('.center-button').classList.add('spinning');

            drawWheel(); // clear any previous highlight first

            // Start name scrolling
            startNameScroll();

            // Play anticipation sound
            playAnticipationSound();

            // Physics: only the stop angle is random. The pointer can land
            // anywhere inside a segment, just like a real wheel.
            const degreesPerSegment = 360 / students.length;
            const spins = Math.random() * 3 + 9;      // 9-12 full rotations
            const drift = Math.random() * 360;        // random resting angle (0..360)
            const startRot = Number(gsap.getProperty(canvas, 'rotation')) || 0;
            const endRot = startRot + spins * 360 + drift;
            let prevRot = startRot;
            let lastStride = Math.floor(startRot / degreesPerSegment);

            gsap.to(canvas, {
                rotation: endRot,
                duration: 6.5,
                ease: "power4.out",
                onStart: function() {
                    currentSpinProgress = 0;
                },
                onUpdate: function() {
                    currentSpinProgress = this.progress();
                    const rot = Number(gsap.getProperty(canvas, 'rotation')) || 0;
                    const delta = rot - prevRot;

                    // Tick once every time a segment edge crosses the pointer
                    const stride = Math.floor(rot / degreesPerSegment);
                    if (stride > lastStride) {
                        lastStride = stride;
                        playTick(Math.min(1, Math.max(0.12, delta / 10)));
                    }

                    // Tiny motion blur proportional to speed (zero when slow)
                    const v = Math.abs(delta);
                    canvas.style.filter = v > 6 ? 'blur(' + Math.min(1.6, v / 16) + 'px)' : 'none';

                    prevRot = rot;
                },
                onComplete: function() {
                    // Natural settle: short back-and-forth wobble like a real wheel
                    canvas.style.filter = 'none';
                    const settleStart = Number(gsap.getProperty(canvas, 'rotation')) || 0;
                    getAudioContext();
                    playClack();

                    const settle = gsap.timeline();
                    settle.to(canvas, { rotation: settleStart - 5, duration: 0.13, ease: "power2.inOut" });
                    settle.to(canvas, { rotation: settleStart + 2.4, duration: 0.16, ease: "power2.inOut" });
                    settle.to(canvas, { rotation: settleStart - 1.1, duration: 0.18, ease: "power2.inOut" });
                    settle.to(canvas, { rotation: settleStart, duration: 0.28, ease: "power2.out" });
                    settle.eventCallback('onComplete', function() {
                        // The arrow decides: derive the winner from the rotation
                        // the wheel actually stopped at (after the wobble).
                        const finalRot = Number(gsap.getProperty(canvas, 'rotation')) || endRot;
                        const winner = segmentUnderPointer(finalRot, degreesPerSegment);
                        finishSpin(winner);
                    });
                }
            });
        }

        // "The arrow decides" - map the pointer's resting angle back to a segment.
        // The pointer is fixed at the top; the wheel is rotated clockwise (CSS
        // positive rotate) by rotationDeg. A segment starts at local angle k*ds
        // (clockwise from top), so the segment under the arrow is the one whose
        // span contains (360 - rotationDeg) mod 360.
        function segmentUnderPointer(rotationDeg, degreesPerSegment) {
            const n = students.length;
            if (n <= 0) return 0;
            const rem = ((rotationDeg % 360) + 360) % 360;
            const local = (360 - rem) % 360;
            return Math.floor(local / degreesPerSegment) % n;
        }

        // Finalise the spin: reveal winner, highlight the segment, celebrate
        function finishSpin(randomSegment) {
            stopNameScroll();

            const selectedStudent = students[randomSegment];

            // Record spin for live stats & history
            recordSpin(selectedStudent);

            // Highlight the matching segment on the wheel (it sits under the pointer)
            highlightSegment(randomSegment);

            // Show selected name with animation
            const nameList = document.getElementById('nameList');
            nameList.textContent = selectedStudent.name;
            gsap.fromTo(nameList,
                { scale: 0.5, opacity: 0 },
                { scale: 1, opacity: 1, duration: 0.5, ease: "back.out(2)" }
            );

            isSpinning = false;
            document.querySelector('.center-button').classList.remove('spinning');

            // Enhanced confetti effect
            celebrateWinner();

            // Play success sound
            playSuccessSound();

            // Show winner modal
            setTimeout(() => {
                Swal.fire({
                    title: '<div class="winner-title">🎉 Chúc Mừng! 🎉</div>',
                    html: `
                        <div class="winner-content">
                            <div class="winner-badge">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="winner-name">${selectedStudent.name}</div>
                            <div class="winner-subtitle">đã được chọn!</div>
                            <div class="winner-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    `,
                    target: document.getElementById('luckyWheelFullscreen'),
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: '<i class="fas fa-redo me-2"></i>Quay lại',
                    confirmButtonColor: '#4F46E5',
                    background: 'linear-gradient(135deg, #3730A3 0%, #4F46E5 100%)',
                    color: '#fff',
                    customClass: {
                        popup: 'winner-popup',
                        confirmButton: 'winner-button'
                    }
                });
            }, 500);
        }

        // Re-paint the winning segment with a golden highlight so the wheel
        // clearly matches the result shown in the side list.
        function highlightSegment(segmentIndex) {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 30;
            const anglePerSegment = (2 * Math.PI) / students.length;
            const startAngle = segmentIndex * anglePerSegment - Math.PI / 2;
            const endAngle = (segmentIndex + 1) * anglePerSegment - Math.PI / 2;
            const midAngle = startAngle + anglePerSegment / 2;

            ctx.save();
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, startAngle, endAngle);
            ctx.closePath();
            ctx.fillStyle = 'rgba(255, 255, 255, 0.22)';
            ctx.fill();
            ctx.strokeStyle = '#FFD700';
            ctx.lineWidth = 5;
            ctx.shadowColor = 'rgba(255, 215, 0, 0.9)';
            ctx.shadowBlur = 18;
            ctx.stroke();

            // Small sparkle marker at the outer tip of the winning segment
            const tipX = centerX + Math.cos(midAngle) * radius;
            const tipY = centerY + Math.sin(midAngle) * radius;
            ctx.fillStyle = '#FFD700';
            ctx.beginPath();
            ctx.arc(tipX, tipY, 7, 0, 2 * Math.PI);
            ctx.fill();
            ctx.shadowBlur = 0;
            ctx.restore();
        }

        // Enhanced confetti celebration
        function celebrateWinner() {
            const duration = 5 * 1000;
            const end = Date.now() + duration;
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

            (function frame() {
                confettiInstance({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0, y: 0.8 },
                    colors: colors
                });
                confettiInstance({
                    particleCount: 5,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1, y: 0.8 },
                    colors: colors
                });

                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());

            // Center burst
            setTimeout(() => {
                confettiInstance({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }, 500);
        }

        // Play anticipation sound
        function playAnticipationSound() {
            const audioContext = getAudioContext();
            if (!audioContext) return;
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(200, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.5);

            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }

        // Play success sound
        function playSuccessSound() {
            const audioContext = getAudioContext();
            if (!audioContext) return;
            const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6

            notes.forEach((freq, index) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(freq, audioContext.currentTime + index * 0.1);
                gainNode.gain.setValueAtTime(0.2, audioContext.currentTime + index * 0.1);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + index * 0.1 + 0.3);

                oscillator.start(audioContext.currentTime + index * 0.1);
                oscillator.stop(audioContext.currentTime + index * 0.1 + 0.3);
            });
        }

        // Reset statistics when a class is selected
        function resetStats() {
            selectionCounts = {};
            spinHistory = [];
            totalSpinCount = 0;
            document.getElementById('totalSpins').textContent = '0';
            document.getElementById('uniqueStudents').textContent = '0';
            document.getElementById('historyList').innerHTML = '<div class="history-empty">Chưa có lượt quay nào</div>';
        }

        // Record each spin and update stats/history
        function recordSpin(student) {
            totalSpinCount++;
            selectionCounts[student.id] = (selectionCounts[student.id] || 0) + 1;

            spinHistory.unshift({
                id: student.id,
                name: student.name,
                time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
            });
            if (spinHistory.length > 30) spinHistory.pop();

            updateStats();
        }

        // Render stats & called-students list (fairness tracking)
        function updateStats() {
            document.getElementById('totalSpins').textContent = totalSpinCount;
            document.getElementById('uniqueStudents').textContent = Object.keys(selectionCounts).length;

            const listEl = document.getElementById('historyList');
            if (spinHistory.length === 0) {
                listEl.innerHTML = '<div class="history-empty">Chưa có lượt quay nào</div>';
                return;
            }

            // Sort most-called students first for fairness visibility
            const sorted = Object.entries(selectionCounts).sort((a, b) => b[1] - a[1]);
            listEl.innerHTML = sorted.map(([id, count]) => {
                const student = students.find(s => String(s.id) === String(id));
                const name = student ? student.name : id;
                return `
                    <div class="history-item">
                        <span class="history-name" title="${name}">${name}</span>
                        <span class="history-count">${count} lần</span>
                    </div>
                `;
            }).join('');
        }

        // --- Fullscreen mode ---
        function isWheelFullscreen() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
        }

        function toggleFullscreen() {
            const element = document.getElementById('luckyWheelFullscreen');
            if (!isWheelFullscreen()) {
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        function handleFullscreenChange() {
            const active = isWheelFullscreen();
            const btn = document.getElementById('fullscreenBtn');
            const icon = btn.querySelector('i');
            if (active) {
                btn.classList.add('active');
                icon.className = 'fas fa-compress';
                btn.querySelector('.fs-tooltip').textContent = 'Thoát toàn màn hình';
            } else {
                btn.classList.remove('active');
                icon.className = 'fas fa-expand';
                btn.querySelector('.fs-tooltip').textContent = 'Toàn màn hình';
            }
            resizeWheelCanvas();
        }

        // Re-render wheel at higher resolution in fullscreen for crisp edges
        function resizeWheelCanvas() {
            if (!canvas) return;
            const size = isWheelFullscreen()
                ? Math.min(window.innerWidth * 0.62, window.innerHeight * 0.72, 1100)
                : 500;
            if (canvas.width !== size || canvas.height !== size) {
                canvas.width = size;
                canvas.height = size;
                if (students.length > 0) drawWheel();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initCanvas();
            loadClasses();

            // Fullscreen listeners
            document.addEventListener('fullscreenchange', handleFullscreenChange);
            document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
            document.addEventListener('msfullscreenchange', handleFullscreenChange);
            window.addEventListener('resize', function() {
                if (isWheelFullscreen()) resizeWheelCanvas();
            });

            // Keyboard shortcut: Space / Enter to spin (presentation-friendly)
            document.addEventListener('keydown', function(e) {
                if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')) return;
                if ((e.code === 'Space' || e.code === 'Enter') && students.length > 0) {
                    e.preventDefault();
                    spinWheel();
                }
            });

            // Event listeners
            document.getElementById('classSelect').addEventListener('change', function() {
                const classId = this.value;
                if (classId) {
                    loadStudents(classId);
                } else {
                    document.getElementById('wheelContainer').style.display = 'none';
                    document.getElementById('noStudents').style.display = 'none';
                    document.getElementById('fullscreenBtn').style.display = 'none';
                    stopNameScroll();
                    if (isWheelFullscreen()) toggleFullscreen();
                }
            });

            // Click on wheel to spin
            canvas.addEventListener('click', spinWheel);
        });
    </script>

    <style>
        /* Modern Professional Styling */
        body {
            background: var(--page-bg);
            min-height: 100vh;
        }

        .lucky-wheel-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .star {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--accent);
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        .star:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .star:nth-child(2) { top: 40%; left: 80%; animation-delay: 1s; }
        .star:nth-child(3) { top: 60%; left: 30%; animation-delay: 2s; }
        .star:nth-child(4) { top: 80%; left: 70%; animation-delay: 0.5s; }
        .star:nth-child(5) { top: 30%; left: 50%; animation-delay: 1.5s; }

        @keyframes twinkle {
            0%, 100% { opacity: 0; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.5); }
        }

        /* Header Section */
        .wheel-header {
            position: relative;
            z-index: 1;
            padding: 3rem 0 2rem;
        }

        .header-content {
            animation: fadeInDown 0.8s ease-out;
        }

        .icon-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }

        .icon-wrapper i {
            color: var(--gold);
            font-size: 2rem;
        }

        .icon-wrapper .main-icon {
            font-size: 3rem;
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--accent-dark), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Selection Card */
        .selection-card {
            position: relative;
            background: var(--surface);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: glow 3s linear infinite;
        }

        @keyframes glow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .selection-content {
            position: relative;
            z-index: 1;
        }

        .selection-label {
            display: block;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 1rem;
        }

        /* Wheel Card */
        .wheel-card {
            position: relative;
            background: var(--surface);
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            min-height: 400px;
            animation: fadeInUp 0.8s ease-out 0.4s backwards;
        }

        .card-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Wheel Styling */
        .wheel-main {
            position: relative;
            z-index: 1;
        }

        .wheel-wrapper {
            text-align: center;
            position: relative;
        }

        .wheel-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 650px;
            height: 650px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.3), transparent 70%);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.05); opacity: 0.8; }
        }

        .canvas-container {
            position: relative;
            display: inline-block;
            margin: 0 auto;
        }

        #wheelCanvas {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #wheelCanvas:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 50px rgba(0,0,0,0.4);
        }

        /* Center Button */
        .center-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .center-button:hover {
            transform: translate(-50%, -50%) scale(1.1);
        }

        .center-button.spinning {
            pointer-events: none;
            opacity: 0.5;
        }

        .button-inner {
            width: 100%;
            height: 100%;
            background: var(--grad-accent);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            border: 4px solid white;
            animation: buttonPulse 1.5s ease-in-out infinite;
        }

        @keyframes buttonPulse {
            0%, 100% { box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
            50% { box-shadow: 0 5px 30px rgba(79, 70, 229, 0.6); }
        }

        .button-inner i {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 0.2rem;
        }

        .button-inner span {
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
        }

        /* Pointer */
        .wheel-pointer {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 6;
            pointer-events: none;
            animation: pointerBounce 1.6s ease-in-out infinite;
        }

        @keyframes pointerBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-3px); }
        }

        /* Gold bearing / stud at the top of the pointer */
        .pointer-hub {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 28%, #ffe28a, #f59e0b 55%, #b45309);
            border: 3px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.35);
            z-index: 2;
            margin-bottom: -16px;
        }

        /* Red arrow pointing straight at the selected segment */
        .pointer-tip {
            position: relative;
            width: 54px;
            height: 64px;
            background: linear-gradient(180deg, #ef4444 0%, #dc2626 55%, #b91c1c 100%);
            clip-path: polygon(50% 100%, 3% 0, 97% 0);
            box-shadow: inset 0 5px 0 rgba(255,255,255,0.4);
            filter: drop-shadow(0 0 2px #ffffff) drop-shadow(0 3px 5px rgba(0,0,0,0.35));
        }

        .pointer-tip::after {
            content: '';
            position: absolute;
            top: 14px;
            left: 50%;
            transform: translateX(-50%);
            width: 46px;
            height: 9px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            filter: blur(1px);
        }

        .pointer-glow {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 0, 0, 0.4), transparent 70%);
            border-radius: 50%;
            z-index: -1;
        }

        /* Fixed arc glow at the rim showing where the selected student is taken */
        .pointer-zone {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 170px;
            height: 96px;
            background: radial-gradient(120% 100% at 50% 100%, rgba(255, 235, 148, 0.4), rgba(255, 215, 0, 0.14) 45%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 4;
            animation: zonePulse 1.8s ease-in-out infinite;
        }

        @keyframes zonePulse {
            0%, 100% { opacity: 0.75; transform: translateX(-50%) scale(1); }
            50% { opacity: 1; transform: translateX(-50%) scale(1.06); }
        }

        /* Result Display */
        .result-display {
            position: relative;
        }

        .result-header {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .name-scroller {
            position: relative;
            height: 80px;
            background: var(--grad-accent);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .name-text {
            font-size: 2rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            padding: 0 2rem;
            text-align: center;
        }

        .result-decoration {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .sparkle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--gold);
            border-radius: 50%;
            animation: sparkleAnim 2s infinite;
        }

        .sparkle:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .sparkle:nth-child(2) { top: 10%; right: 10%; animation-delay: 0.5s; }
        .sparkle:nth-child(3) { bottom: 10%; left: 50%; animation-delay: 1s; }

        @keyframes sparkleAnim {
            0%, 100% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1); }
        }

        /* Instructions */
        .instructions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .instruction-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: var(--grad-accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        .step-text {
            font-size: 0.9rem;
            color: var(--muted-strong);
            font-weight: 500;
        }

        .instruction-arrow {
            font-size: 1.5rem;
            color: var(--accent);
            font-weight: bold;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .loading-spinner {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
        }

        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }

        .spinner-ring:nth-child(2) {
            border-top-color: var(--accent-dark);
            animation-delay: -0.5s;
        }

        .spinner-ring:nth-child(3) {
            border-top-color: var(--gold);
            animation-delay: -1s;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 1.1rem;
            color: var(--muted-strong);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        /* Footer */
        .wheel-footer {
            position: relative;
            z-index: 1;
            padding: 2rem 0;
            color: var(--muted-strong);
        }

        .footer-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .footer-link:hover {
            color: var(--accent-dark);
        }

        /* Fullscreen Toggle Button */
        .fullscreen-btn {
            position: fixed;
            right: 1.5rem;
            bottom: 5.5rem;
            z-index: 1000;
            width: 56px;
            height: 56px;
            border: none;
            border-radius: 50%;
            background: var(--grad-accent);
            color: #fff;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
        }

        .fullscreen-btn:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 8px 26px rgba(79, 70, 229, 0.55);
        }

        .fullscreen-btn .fs-tooltip {
            position: absolute;
            right: 130%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(30, 41, 59, 0.92);
            color: #fff;
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .fullscreen-btn:hover .fs-tooltip {
            opacity: 1;
        }

        /* Confetti layer: covers the viewport and stays visible inside the
           fullscreen container (position:fixed is relative to viewport). */
        .confetti-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 9999;
        }

        /* History & Stats Panel */
        .wheel-columns {
            display: flex;
            flex-direction: column;
        }

        .history-panel {
            margin-top: 1.5rem;
            background: rgba(99, 102, 241, 0.06);
            border: 1px solid rgba(99, 102, 241, 0.18);
            border-radius: 20px;
            overflow: hidden;
        }

        .history-header {
            padding: 0.9rem 1.25rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--accent);
            border-bottom: 1px solid rgba(99, 102, 241, 0.15);
            display: flex;
            align-items: center;
        }

        .history-body {
            padding: 1rem 1.25rem 1.25rem;
        }

        .history-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            flex: 1;
            background: var(--grad-accent);
            border-radius: 14px;
            padding: 0.85rem;
            text-align: center;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 0.15rem;
        }

        .history-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--muted-strong);
            margin-bottom: 0.6rem;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            max-height: 220px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        .history-list::-webkit-scrollbar {
            width: 6px;
        }

        .history-list::-webkit-scrollbar-thumb {
            background: rgba(79, 70, 229, 0.3);
            border-radius: 10px;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            background: var(--surface);
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 10px;
            padding: 0.45rem 0.85rem;
        }

        .history-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .history-count {
            flex: none;
            font-size: 0.78rem;
            font-weight: 700;
            color: #1f2937;
            background: var(--gold);
            border-radius: 50px;
            padding: 0.15rem 0.6rem;
        }

        .history-empty {
            text-align: center;
            color: var(--muted-strong);
            padding: 1rem;
            font-size: 0.9rem;
        }

        /* Fullscreen Layout */
        .lucky-wheel-container:fullscreen,
        .lucky-wheel-container:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
            padding: 1.5rem 2rem;
            background: var(--page-bg);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
        }

        .lucky-wheel-container:fullscreen .wheel-header,
        .lucky-wheel-container:-webkit-full-screen .wheel-header {
            padding: 0.5rem 0;
        }

        .lucky-wheel-container:fullscreen .wheel-header h1,
        .lucky-wheel-container:-webkit-full-screen .wheel-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.25rem !important;
        }

        .lucky-wheel-container:fullscreen .selection-card,
        .lucky-wheel-container:-webkit-full-screen .selection-card {
            display: none;
        }

        .lucky-wheel-container:fullscreen .wheel-footer,
        .lucky-wheel-container:-webkit-full-screen .wheel-footer {
            display: none;
        }

        .lucky-wheel-container:fullscreen .main-content,
        .lucky-wheel-container:-webkit-full-screen .main-content {
            flex: 1;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }

        .lucky-wheel-container:fullscreen .container,
        .lucky-wheel-container:-webkit-full-screen .container {
            max-width: 100%;
            padding: 0;
        }

        .lucky-wheel-container:fullscreen .col-xl-10,
        .lucky-wheel-container:-webkit-full-screen .col-xl-10 {
            max-width: 100%;
        }

        .lucky-wheel-container:fullscreen .wheel-card,
        .lucky-wheel-container:-webkit-full-screen .wheel-card {
            display: flex;
            flex-direction: column;
            min-height: 0;
            padding: 1.5rem 2rem;
        }

        .lucky-wheel-container:fullscreen .wheel-main,
        .lucky-wheel-container:-webkit-full-screen .wheel-main {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .lucky-wheel-container:fullscreen .wheel-columns,
        .lucky-wheel-container:-webkit-full-screen .wheel-columns {
            width: 100%;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            align-items: center;
        }

        .lucky-wheel-container:fullscreen .wheel-column-main,
        .lucky-wheel-container:-webkit-full-screen .wheel-column-main {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .lucky-wheel-container:fullscreen .wheel-column-side,
        .lucky-wheel-container:-webkit-full-screen .wheel-column-side {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            max-height: 100%;
            overflow-y: auto;
        }

        .lucky-wheel-container:fullscreen .instructions,
        .lucky-wheel-container:-webkit-full-screen .instructions {
            display: none;
        }

        .lucky-wheel-container:fullscreen #wheelCanvas,
        .lucky-wheel-container:-webkit-full-screen #wheelCanvas {
            max-width: 100%;
            max-height: 100%;
        }

        .lucky-wheel-container:fullscreen .center-button,
        .lucky-wheel-container:-webkit-full-screen .center-button {
            width: clamp(110px, 13vh, 170px);
            height: clamp(110px, 13vh, 170px);
        }

        .lucky-wheel-container:fullscreen .button-inner i,
        .lucky-wheel-container:-webkit-full-screen .button-inner i {
            font-size: clamp(1.6rem, 4vh, 2.4rem);
        }

        .lucky-wheel-container:fullscreen .button-inner span,
        .lucky-wheel-container:-webkit-full-screen .button-inner span {
            font-size: clamp(1rem, 2.4vh, 1.4rem);
        }

        .lucky-wheel-container:fullscreen .wheel-pointer,
        .lucky-wheel-container:-webkit-full-screen .wheel-pointer {
            top: -58px;
        }

        .lucky-wheel-container:fullscreen .pointer-tip,
        .lucky-wheel-container:-webkit-full-screen .pointer-tip {
            width: 78px;
            height: 92px;
        }

        .lucky-wheel-container:fullscreen .pointer-hub,
        .lucky-wheel-container:-webkit-full-screen .pointer-hub {
            width: 50px;
            height: 50px;
            margin-bottom: -22px;
        }

        .lucky-wheel-container:fullscreen .pointer-zone,
        .lucky-wheel-container:-webkit-full-screen .pointer-zone {
            width: 230px;
            height: 130px;
        }

        .lucky-wheel-container:fullscreen .name-text,
        .lucky-wheel-container:-webkit-full-screen .name-text {
            font-size: clamp(1.2rem, 1.7vw, 1.55rem);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lucky-wheel-container:fullscreen .wheel-glow,
        .lucky-wheel-container:-webkit-full-screen .wheel-glow {
            width: 90%;
            height: 90%;
        }

        .lucky-wheel-container:fullscreen .history-panel,
        .lucky-wheel-container:-webkit-full-screen .history-panel {
            margin-top: 0;
        }

        @media (max-width: 992px) {
            .lucky-wheel-container:fullscreen .wheel-columns,
            .lucky-wheel-container:-webkit-full-screen .wheel-columns {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .lucky-wheel-container:fullscreen .history-panel,
            .lucky-wheel-container:-webkit-full-screen .history-panel {
                max-height: 200px;
                overflow-y: auto;
            }
        }

        /* Winner Modal Styling */
        .winner-popup {
            border-radius: 20px !important;
            border: 3px solid var(--gold) !important;
        }
        .teacher-page {
            background: var(--page-bg);
        }
        .winner-title {
            font-size: 2rem;
            font-weight: bold;
            animation: tada 1s;
        }

        .winner-content {
            padding: 2rem 0;
        }

        .winner-badge {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounceIn 1s;
        }

        .winner-badge i {
            color: var(--gold);
            filter: drop-shadow(0 0 10px rgba(245,158,11,0.5));
        }

        .winner-name {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
            animation: bounceIn 1s;
        }

        .winner-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .winner-stars {
            margin-top: 1.5rem;
            font-size: 1.5rem;
        }

        .winner-stars i {
            color: var(--gold);
            margin: 0 0.3rem;
            animation: twinkle 1s infinite;
        }

        .winner-stars i:nth-child(2) {
            animation-delay: 0.2s;
        }

        .winner-stars i:nth-child(3) {
            animation-delay: 0.4s;
        }

        .winner-button {
            border-radius: 50px !important;
            padding: 0.8rem 2rem !important;
            font-weight: 600 !important;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes tada {
            0% { transform: scale(1); }
            10%, 20% { transform: scale(0.9) rotate(-3deg); }
            30%, 50%, 70%, 90% { transform: scale(1.1) rotate(3deg); }
            40%, 60%, 80% { transform: scale(1.1) rotate(-3deg); }
            100% { transform: scale(1) rotate(0); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .wheel-header h1 {
                font-size: 2rem;
            }

            #wheelCanvas {
                width: 350px !important;
                height: 350px !important;
            }

            .wheel-pointer {
                top: -30px;
            }

            .pointer-tip {
                width: 42px;
                height: 50px;
            }

            .pointer-hub {
                width: 26px;
                height: 26px;
                margin-bottom: -11px;
            }

            .pointer-zone {
                width: 128px;
                height: 72px;
            }

            .center-button {
                width: 80px;
                height: 80px;
            }

            .button-inner i {
                font-size: 1.2rem;
            }

            .button-inner span {
                font-size: 0.8rem;
            }

            .name-text {
                font-size: 1.5rem;
            }

            .instructions {
                flex-direction: column;
            }

            .instruction-arrow {
                transform: rotate(90deg);
            }
        }

        @media (max-width: 480px) {
            #wheelCanvas {
                width: 300px !important;
                height: 300px !important;
            }

            .wheel-pointer {
                top: -26px;
            }

            .pointer-tip {
                width: 36px;
                height: 44px;
            }

            .pointer-hub {
                width: 22px;
                height: 22px;
                margin-bottom: -10px;
            }

            .wheel-card {
                padding: 1.5rem;
            }

            .selection-card {
                padding: 1.5rem;
            }
        }
    </style>

<?php include '../includes/footer.php'; ?>
