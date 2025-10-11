<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$examType = $_GET['type'] ?? '';
if (!in_array($examType, ['TX1', 'TX2'])) {
    header('Location: dashboard.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'];
$studentClassCode = $_SESSION['student_class_code'];

// Determine grade level from class code (e.g., TH6A1 -> 6)
preg_match('/TH(\d+)/', $studentClassCode, $matches);
$gradeLevel = $matches[1] ?? '6';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Thi <?php echo $examType; ?> - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .question-card {
            margin-bottom: 2rem;
        }
        .option-label {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .option-label:hover {
            background-color: #f8f9fa;
        }
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        .question-nav {
            max-height: 400px;
            overflow-y: auto;
        }
        .question-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
            cursor: pointer;
            border: 2px solid #dee2e6;
        }
        .question-number.answered {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        .question-number.current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .exam-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1000;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="exam-header p-3 bg-light">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0">Bài Thi <?php echo $examType; ?> - Tin Học</h5>
                    <small class="text-muted"><?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>)</small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="timer" id="timer">45:00</div>
                    <small class="text-muted">Thời gian còn lại</small>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-warning me-2" onclick="pauseExam()">⏸️ Tạm Dừng</button>
                    <button class="btn btn-success" onclick="submitExam()">✅ Nộp Bài</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Questions Navigation -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Danh Sách Câu Hỏi</h6>
                    </div>
                    <div class="card-body question-nav">
                        <div id="questionNav" class="text-center">
                            <!-- Question numbers will be generated here -->
                        </div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h6>Tiến Độ</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                        </div>
                        <small id="progressText">0/40 câu</small>
                    </div>
                </div>
            </div>

            <!-- Questions Display -->
            <div class="col-md-9">
                <div id="questionsContainer">
                    <!-- Questions will be loaded here -->
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-outline-primary" id="prevBtn" onclick="previousQuestion()" disabled>
                        ← Câu Trước
                    </button>
                    <button class="btn btn-outline-primary" id="nextBtn" onclick="nextQuestion()">
                        Câu Tiếp →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pause Modal -->
    <div class="modal fade" id="pauseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạm Dừng Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-pause-circle" style="font-size: 3rem; color: #ffc107;"></i>
                    </div>
                    <p>Bài thi đã được tạm dừng. Thời gian vẫn tiếp tục chạy.</p>
                    <p class="text-muted">Nhấn "Tiếp Tục" để quay lại bài thi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tiếp Tục</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác Nhận Nộp Bài</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Kiểm tra lại trước khi nộp:</strong>
                        <ul id="submitSummary" class="mb-0 mt-2">
                            <!-- Summary will be populated here -->
                        </ul>
                    </div>
                    <p class="mb-0">Sau khi nộp bài, bạn sẽ không thể thay đổi câu trả lời. Bạn có chắc muốn nộp bài?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kiểm Tra Lại</button>
                    <button type="button" class="btn btn-success" id="confirmSubmitBtn">Nộp Bài</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let examData = {
            type: '<?php echo $examType; ?>',
            studentCode: '<?php echo $studentCode; ?>',
            studentName: '<?php echo $studentName; ?>',
            classCode: '<?php echo $studentClassCode; ?>',
            gradeLevel: '<?php echo $gradeLevel; ?>',
            questions: [],
            answers: {},
            currentQuestion: 0,
            timeRemaining: 45 * 60, // 45 minutes in seconds
            timer: null,
            paused: false
        };

        // Load questions
        async function loadQuestions() {
            try {
                const response = await fetch(`api/generate_exam.php?type=${examData.type}&grade=${examData.gradeLevel}`);
                const data = await response.json();

                if (data.success) {
                    examData.questions = data.questions;
                    renderQuestions();
                    renderQuestionNav();
                    startTimer();
                } else {
                    alert('Không thể tải câu hỏi: ' + data.message);
                    window.location.href = 'dashboard.php';
                }
            } catch (error) {
                console.error('Error loading questions:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
                window.location.href = 'dashboard.php';
            }
        }

        // Render questions
        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';

            examData.questions.forEach((question, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question-card card';
                questionDiv.id = `question-${index}`;
                questionDiv.style.display = index === 0 ? 'block' : 'none';

                let optionsHtml = '';
                if (question.type === 'single') {
                    optionsHtml = question.options.map((option, optIndex) => `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q${index}" value="${optIndex}"
                                   id="q${index}o${optIndex}" ${examData.answers[index] === optIndex ? 'checked' : ''}>
                            <label class="form-check-label option-label w-100" for="q${index}o${optIndex}">
                                ${String.fromCharCode(65 + optIndex)}. ${option}
                            </label>
                        </div>
                    `).join('');
                } else if (question.type === 'multiple') {
                    optionsHtml = question.options.map((option, optIndex) => `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="q${index}" value="${optIndex}"
                                   id="q${index}o${optIndex}" ${examData.answers[index] && examData.answers[index].includes(optIndex) ? 'checked' : ''}>
                            <label class="form-check-label option-label w-100" for="q${index}o${optIndex}">
                                ${String.fromCharCode(65 + optIndex)}. ${option}
                            </label>
                        </div>
                    `).join('');
                }

                questionDiv.innerHTML = `
                    <div class="card-body">
                        <h5 class="card-title">Câu ${index + 1}:</h5>
                        <p class="card-text">${question.question}</p>
                        <div class="options">
                            ${optionsHtml}
                        </div>
                    </div>
                `;

                // Add event listeners for answer changes
                const inputs = questionDiv.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('change', () => saveAnswer(index));
                });

                container.appendChild(questionDiv);
            });
        }

        // Render question navigation
        function renderQuestionNav() {
            const nav = document.getElementById('questionNav');
            nav.innerHTML = '';

            examData.questions.forEach((_, index) => {
                const numDiv = document.createElement('div');
                numDiv.className = `question-number ${index === examData.currentQuestion ? 'current' : ''} ${examData.answers[index] !== undefined ? 'answered' : ''}`;
                numDiv.textContent = index + 1;
                numDiv.onclick = () => goToQuestion(index);
                nav.appendChild(numDiv);
            });

            updateProgress();
        }

        // Save answer
        function saveAnswer(questionIndex) {
            const question = examData.questions[questionIndex];
            const inputs = document.querySelectorAll(`input[name="q${questionIndex}"]:checked`);

            if (question.type === 'single') {
                examData.answers[questionIndex] = inputs.length > 0 ? parseInt(inputs[0].value) : undefined;
            } else if (question.type === 'multiple') {
                examData.answers[questionIndex] = Array.from(inputs).map(input => parseInt(input.value));
            }

            renderQuestionNav();
        }

        // Navigation functions
        function goToQuestion(index) {
            document.getElementById(`question-${examData.currentQuestion}`).style.display = 'none';
            examData.currentQuestion = index;
            document.getElementById(`question-${examData.currentQuestion}`).style.display = 'block';
            renderQuestionNav();

            // Update navigation buttons
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === examData.questions.length - 1;
        }

        function nextQuestion() {
            if (examData.currentQuestion < examData.questions.length - 1) {
                goToQuestion(examData.currentQuestion + 1);
            }
        }

        function previousQuestion() {
            if (examData.currentQuestion > 0) {
                goToQuestion(examData.currentQuestion - 1);
            }
        }

        // Timer functions
        function startTimer() {
            examData.timer = setInterval(() => {
                if (!examData.paused) {
                    examData.timeRemaining--;

                    if (examData.timeRemaining <= 0) {
                        clearInterval(examData.timer);
                        autoSubmitExam();
                    }

                    updateTimerDisplay();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(examData.timeRemaining / 60);
            const seconds = examData.timeRemaining % 60;
            document.getElementById('timer').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Change color when time is running low
            if (examData.timeRemaining < 300) { // 5 minutes
                document.getElementById('timer').style.color = '#dc3545';
            }
        }

        function pauseExam() {
            examData.paused = true;
            new bootstrap.Modal(document.getElementById('pauseModal')).show();
        }

        // Update progress
        function updateProgress() {
            const answered = Object.keys(examData.answers).length;
            const total = examData.questions.length;
            const percentage = (answered / total) * 100;

            document.getElementById('progressBar').style.width = `${percentage}%`;
            document.getElementById('progressText').textContent = `${answered}/${total} câu`;
        }

        // Submit functions
        function submitExam() {
            const answered = Object.keys(examData.answers).length;
            const total = examData.questions.length;

            const summary = document.getElementById('submitSummary');
            summary.innerHTML = `
                <li>Tổng số câu hỏi: ${total}</li>
                <li>Đã trả lời: ${answered}</li>
                <li>Chưa trả lời: ${total - answered}</li>
                <li>Thời gian còn lại: ${Math.floor(examData.timeRemaining / 60)}:${(examData.timeRemaining % 60).toString().padStart(2, '0')}</li>
            `;

            new bootstrap.Modal(document.getElementById('submitModal')).show();
        }

        function autoSubmitExam() {
            alert('Hết thời gian! Bài thi sẽ được nộp tự động.');
            doSubmitExam();
        }

        document.getElementById('confirmSubmitBtn').addEventListener('click', doSubmitExam);

        async function doSubmitExam() {
            clearInterval(examData.timer);

            try {
                const response = await fetch('api/submit_exam.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(examData)
                });

                const result = await response.json();
                if (result.success) {
                    window.location.href = `result.php?exam_id=${result.exam_id}`;
                } else {
                    alert('Lỗi nộp bài: ' + result.message);
                }
            } catch (error) {
                console.error('Error submitting exam:', error);
                alert('Lỗi kết nối khi nộp bài. Vui lòng liên hệ giáo viên.');
            }
        }

        // Prevent context menu and keyboard shortcuts
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.ctrlKey && (e.key === 'u' || e.key === 's' || e.key === 'a' || e.key === 'c' || e.key === 'v')) {
                e.preventDefault();
            }
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                e.preventDefault();
            }
        });

        // Warn before leaving page
        window.addEventListener('beforeunload', e => {
            e.preventDefault();
            e.returnValue = 'Bài thi đang diễn ra. Bạn có chắc muốn rời khỏi trang?';
        });

        // Load questions on page load
        document.addEventListener('DOMContentLoaded', loadQuestions);
    </script>
</body>
</html>
