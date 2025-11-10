<?php
session_start();
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

    <div class="container my-5">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="mb-4">🎡 Vòng Quay May Mắn</h1>
                <p class="lead mb-5">Chọn lớp và click vào vòng quay để chọn ngẫu nhiên học sinh! 🎉</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <!-- Class Selection -->
                        <div class="mb-4">
                            <label for="classSelect" class="form-label fs-5">Chọn Lớp Học:</label>
                            <select class="form-select form-select-lg" id="classSelect">
                                <option value="">-- Chọn lớp --</option>
                            </select>
                        </div>

                        <!-- Wheel Container -->
                        <div id="wheelContainer" class="mb-4" style="display: none;">
                            <div style="position: relative; display: inline-block;">
                                <canvas id="wheelCanvas" width="550" height="550" style="max-width: 100%; border-radius: 50%; border:4px solid rgba(0,0,0,0.1); cursor: pointer;"></canvas>
                                <!-- Stationary Pointer -->
                                <div id="pointer" style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 30px solid transparent; border-right: 30px solid transparent; border-bottom: 60px solid #FF0000; filter: drop-shadow(3px 3px 6px rgba(0,0,0,0.4)); z-index: 10;"></div>
                            </div>

                            <!-- Scrolling Names Display -->
                            <div id="nameScroller" class="mt-4" style="height: 60px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; border: 2px solid #fff; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div id="nameList" style="position: absolute; width: 100%; text-align: center; font-size: 1.5em; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"></div>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loading" class="text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Đang tải...</span>
                            </div>
                            <p class="mt-2">Đang tải danh sách học sinh...</p>
                        </div>

                        <!-- No Students Message -->
                        <div id="noStudents" class="alert alert-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Không có học sinh trong lớp này.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
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

        // Colors for wheel segments
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'];

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

                if (result.success) {
                    const classSelect = document.getElementById('classSelect');
                    const assignedClassIds = <?php echo json_encode($assignedClasses); ?>;

                    result.data.forEach(classItem => {
                        if (assignedClassIds.includes(classItem.id)) {
                            classSelect.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                        }
                    });
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

        // Draw the wheel
        function drawWheel() {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 20;
            const anglePerSegment = (2 * Math.PI) / students.length;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            students.forEach((student, index) => {
                const startAngle = index * anglePerSegment;
                const endAngle = (index + 1) * anglePerSegment;

                // Draw segment
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = colors[index % colors.length];
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Draw text
                const textAngle = startAngle + anglePerSegment / 2;
                const textX = centerX + Math.cos(textAngle) * (radius * 0.7);
                const textY = centerY + Math.sin(textAngle) * (radius * 0.7);

                ctx.save();
                ctx.translate(textX, textY);
                ctx.rotate(textAngle + Math.PI / 2);
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 16px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.shadowColor = 'rgba(0,0,0,0.7)';
                ctx.shadowBlur = 3;
                ctx.shadowOffsetX = 1;
                ctx.shadowOffsetY = 1;

                // Truncate long names
                let name = student.name;
                if (name.length > 15) {
                    name = name.substring(0, 12) + '...';
                }

                ctx.fillText(name, 0, 0);
                ctx.restore();
            });

            // Pointer is now drawn as HTML element, not on canvas
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

        // Spin the wheel
        function spinWheel() {
            if (isSpinning || students.length === 0) return;

            isSpinning = true;

            // Start name scrolling
            startNameScroll();

            // Random number of full rotations + random segment
            const spins = Math.random() * 5 + 5; // 5-10 full rotations
            const randomSegment = Math.floor(Math.random() * students.length);
            const targetAngle = (randomSegment * (360 / students.length)) + (360 / students.length / 2);

            const totalRotation = spins * 360 + (360 - targetAngle);

            gsap.to(canvas, {
                rotation: '+=' + totalRotation,
                duration: 7,
                ease: "power2.out",
                onComplete: () => {
                    isSpinning = false;

                    // Stop name scrolling
                    stopNameScroll();

                    const selectedStudent = students[randomSegment];

                    // Show selected name in scroller
                    const nameList = document.getElementById('nameList');
                    nameList.textContent = selectedStudent.name;
                    gsap.fromTo(nameList, { scale: 0.8 }, { scale: 1, duration: 0.3, ease: "back.out(1.7)" });

                    // Confetti effect
                    var end = Date.now() + (5 * 1000);

                    // go Buckeyes!
                    var colors = ['#bb0000', '#ffffff'];

                    (function frame() {
                    confetti({
                        particleCount: 2,
                        angle: 60,
                        spread: 55,
                        origin: { x: 0 },
                        colors: colors
                    });
                    confetti({
                        particleCount: 2,
                        angle: 120,
                        spread: 55,
                        origin: { x: 1 },
                        colors: colors
                    });

                    if (Date.now() < end) {
                        requestAnimationFrame(frame);
                    }
                    }());
                    // Play sound effect (if supported)
                    playSound();

                    // Congratulatory message
                    Swal.fire({
                        title: '🎉 Chúc mừng!',
                        html: `<div style="font-size: 1.2em;"><strong>${selectedStudent.name}</strong> được chọn!</div><div class="mt-3"><i class="fas fa-trophy fa-3x text-warning"></i></div>`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745'
                    });
                }
            });
        }

        // Play sound effect
        function playSound() {
            // Create audio context
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();

            // Create oscillator for celebratory sound
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
            oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
            oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2); // G5

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }

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
        document.getElementById('wheelCanvas').addEventListener('click', spinWheel);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initCanvas();
            loadClasses();
        });
    </script>

    <style>
        #wheelCanvas {
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        #wheelCanvas:hover {
            transform: scale(1.02);
        }
        .swal2-popup {
            font-family: 'Arial', sans-serif;
        }
        #nameScroller {
            font-family: 'Arial', sans-serif;
        }
        #pointer {
            transition: transform 0.1s ease;
        }
        #pointer:hover {
            transform: translateX(-50%) scale(1.1);
        }
    </style>

<?php include '../includes/footer.php'; ?>
