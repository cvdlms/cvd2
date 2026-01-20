<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: \'../login.php\'');
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

    <div class="lucky-wheel-container">
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
                <p class="lead text-white-50 mb-0">Chọn ngẫu nhiên học sinh một cách công bằng và thú vị!</p>
            </div>
        </div>

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
                            <select class="modern-select" id="classSelect">
                                <option value="">🎯 Chọn lớp để bắt đầu...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Wheel Card -->
                    <div class="wheel-card">
                        <div class="card-shine"></div>
                        
                        <!-- Wheel Container -->
                        <div id="wheelContainer" class="wheel-main" style="display: none;">
                            <div class="wheel-wrapper">
                                <!-- Decorative Elements -->
                                <div class="wheel-glow"></div>
                                
                                <!-- Wheel Canvas -->
                                <div class="canvas-container">
                                    <canvas id="wheelCanvas" width="600" height="600"></canvas>
                                    
                                    <!-- Center Button -->
                                    <div class="center-button" onclick="spinWheel()">
                                        <div class="button-inner">
                                            <i class="fas fa-play"></i>
                                            <span>QUAY</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Pointer Arrow -->
                                    <div class="wheel-pointer">
                                        <div class="pointer-glow"></div>
                                    </div>
                                </div>
                            </div>

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
                            <h4>Không có học sinh</h4>
                            <p>Lớp học này chưa có học sinh nào. Vui lòng chọn lớp khác.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
        let nameScrollInterval;

        // Colors for wheel segments - vibrant gradient colors
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
            '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', 
            '#BB8FCE', '#85C1E9', '#F8B739', '#FF8B94',
            '#A8E6CF', '#FFD3B6', '#FFAAA5', '#B4A7D6'
        ];

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

            try {
                const response = await fetch(`api/get_students.php?class_id=${classId}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    students = result.data;
                    drawWheel();
                    document.getElementById('wheelContainer').style.display = 'block';
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

        // Draw the wheel with enhanced graphics
        function drawWheel() {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 30;
            const anglePerSegment = (2 * Math.PI) / students.length;

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
                
                // Segment border
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 3;
                ctx.shadowBlur = 0;
                ctx.stroke();

                // Draw text
                const textAngle = startAngle + anglePerSegment / 2;
                const textRadius = radius * 0.75;
                const textX = centerX + Math.cos(textAngle) * textRadius;
                const textY = centerY + Math.sin(textAngle) * textRadius;

                ctx.save();
                ctx.translate(textX, textY);
                ctx.rotate(textAngle + Math.PI / 2);
                
                // Text styling
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 18px "Segoe UI", Arial, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.shadowColor = 'rgba(0,0,0,0.8)';
                ctx.shadowBlur = 4;
                ctx.shadowOffsetX = 2;
                ctx.shadowOffsetY = 2;

                // Truncate long names
                let name = student.name;
                if (name.length > 12) {
                    name = name.substring(0, 10) + '...';
                }

                ctx.fillText(name, 0, 0);
                ctx.restore();
            });

            // Draw center circle
            ctx.shadowBlur = 0;
            const centerGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 50);
            centerGradient.addColorStop(0, '#ffffff');
            centerGradient.addColorStop(1, '#f0f0f0');
            
            ctx.beginPath();
            ctx.arc(centerX, centerY, 50, 0, 2 * Math.PI);
            ctx.fillStyle = centerGradient;
            ctx.fill();
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 2;
            ctx.stroke();
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

        // Start scrolling names
        function startNameScroll() {
            const nameList = document.getElementById('nameList');
            let currentIndex = 0;

            nameScrollInterval = setInterval(() => {
                nameList.textContent = students[currentIndex].name;
                currentIndex = (currentIndex + 1) % students.length;

                // Animate the name scrolling up
                gsap.fromTo(nameList, { y: 60 }, { y: 0, duration: 0.5, ease: "power2.out" });
            }, 200); // Change name every 200ms
        }

        // Stop scrolling names
        function stopNameScroll() {
            if (nameScrollInterval) {
                clearInterval(nameScrollInterval);
                nameScrollInterval = null;
            }
        }

        // Spin the wheel with enhanced animations
        function spinWheel() {
            if (isSpinning || students.length === 0) return;

            isSpinning = true;

            // Disable center button during spin
            document.querySelector('.center-button').classList.add('spinning');

            // Start name scrolling
            startNameScroll();

            // Play anticipation sound
            playAnticipationSound();

            // Random selection
            const spins = Math.random() * 3 + 8; // 8-11 full rotations
            const randomSegment = Math.floor(Math.random() * students.length);
            const degreesPerSegment = 360 / students.length;
            const targetAngle = (randomSegment * degreesPerSegment) + (degreesPerSegment / 2);
            const totalRotation = spins * 360 + (360 - targetAngle);

            // Animate canvas rotation
            gsap.to(canvas, {
                rotation: '+=' + totalRotation,
                duration: 8,
                ease: "power3.out",
                onUpdate: function() {
                    // Add wobble effect during spin
                    const progress = this.progress();
                    if (progress > 0.7) {
                        canvas.style.filter = `blur(${(1 - progress) * 2}px)`;
                    }
                },
                onComplete: () => {
                    canvas.style.filter = 'none';
                    isSpinning = false;
                    document.querySelector('.center-button').classList.remove('spinning');

                    // Stop name scrolling
                    stopNameScroll();

                    const selectedStudent = students[randomSegment];

                    // Show selected name with animation
                    const nameList = document.getElementById('nameList');
                    nameList.textContent = selectedStudent.name;
                    gsap.fromTo(nameList, 
                        { scale: 0.5, opacity: 0 }, 
                        { scale: 1, opacity: 1, duration: 0.5, ease: "back.out(2)" }
                    );

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
                            showCloseButton: true,
                            showConfirmButton: true,
                            confirmButtonText: '<i class="fas fa-redo me-2"></i>Quay lại',
                            confirmButtonColor: '#667eea',
                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            color: '#fff',
                            customClass: {
                                popup: 'winner-popup',
                                confirmButton: 'winner-button'
                            }
                        });
                    }, 500);
                }
            });
        }

        // Enhanced confetti celebration
        function celebrateWinner() {
            const duration = 5 * 1000;
            const end = Date.now() + duration;
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

            (function frame() {
                confetti({
                    particleCount: 5,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0, y: 0.8 },
                    colors: colors
                });
                confetti({
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
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }, 500);
        }

        // Play anticipation sound
        function playAnticipationSound() {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
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
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
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

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initCanvas();
            loadClasses();

            // Event listeners
            document.getElementById('classSelect').addEventListener('change', function() {
                const classId = this.value;
                if (classId) {
                    loadStudents(classId);
                } else {
                    document.getElementById('wheelContainer').style.display = 'none';
                    document.getElementById('noStudents').style.display = 'none';
                    stopNameScroll();
                }
            });

            // Click on wheel to spin
            canvas.addEventListener('click', spinWheel);
        });
    </script>

    <style>
        /* Modern Professional Styling */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            background: white;
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
            color: #FFD700;
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
            background: linear-gradient(45deg, #fff, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Selection Card */
        .selection-card {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
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
            color: #333;
            margin-bottom: 1rem;
        }

        .modern-select {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            border: 3px solid #667eea;
            border-radius: 15px;
            background: white;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
        }

        .modern-select:hover {
            border-color: #764ba2;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .modern-select:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
        }

        /* Wheel Card */
        .wheel-card {
            position: relative;
            background: rgba(255, 255, 255, 0.98);
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
            background: radial-gradient(circle, rgba(102, 126, 234, 0.3), transparent 70%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            50% { box-shadow: 0 5px 30px rgba(102, 126, 234, 0.6); }
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
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-top: 50px solid #FF0000;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
            z-index: 5;
            animation: pointerBounce 1s ease-in-out infinite;
        }

        @keyframes pointerBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-5px); }
        }

        .pointer-glow {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(255,0,0,0.5), transparent);
            border-radius: 50%;
        }

        /* Result Display */
        .result-display {
            position: relative;
        }

        .result-header {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .name-scroller {
            position: relative;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: gold;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #666;
            font-weight: 500;
        }

        .instruction-arrow {
            font-size: 1.5rem;
            color: #667eea;
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
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }

        .spinner-ring:nth-child(2) {
            border-top-color: #764ba2;
            animation-delay: -0.5s;
        }

        .spinner-ring:nth-child(3) {
            border-top-color: #FFD700;
            animation-delay: -1s;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 1.1rem;
            color: #666;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #999;
        }

        /* Footer */
        .wheel-footer {
            position: relative;
            z-index: 1;
            padding: 2rem 0;
            color: rgba(255,255,255,0.8);
        }

        .footer-link {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .footer-link:hover {
            color: #FFF;
            text-shadow: 0 0 10px rgba(255,215,0,0.5);
        }

        /* Winner Modal Styling */
        .winner-popup {
            border-radius: 20px !important;
            border: 3px solid #FFD700 !important;
        }
        .teacher-page{
            background: #41658a;
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
            color: #FFD700;
            filter: drop-shadow(0 0 10px rgba(255,215,0,0.5));
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
            color: #FFD700;
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
                width: 400px !important;
                height: 400px !important;
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

            .wheel-card {
                padding: 1.5rem;
            }

            .selection-card {
                padding: 1.5rem;
            }
        }
    </style>

<?php include '../includes/footer.php'; ?>
