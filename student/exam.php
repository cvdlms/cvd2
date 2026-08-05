<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/exam_helper.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';

$examId = $_GET['exam_id'] ?? $_GET['type'] ?? '';
if (!$examId) {
    header('Location: dashboard.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

$premiumStatus = getStudentPremiumStatus($studentCode);

// Determine grade level from class code
$prefix = substr($studentClassCode, 0, 1);
$grade = 'khoi' . $prefix;
$gradeLevel = $prefix;

// Resolve the exam file safely (guards against path traversal, supports
// both legacy "subject_slug" and canonical test_id formats).
$resolved = exam_resolve_file($examId, $grade);
if (!$resolved) {
    header('Location: dashboard.php');
    exit;
}

$examFile = $resolved['file'];
$subjectId = $resolved['subject_id'];

$examData = json_decode(file_get_contents($examFile), true);
if (!is_array($examData)) {
    header('Location: dashboard.php');
    exit;
}

$canonicalTestId = $examData['test_id'] ?? null;
$examType = $examData['exam_type'] ?? 'practice';
$questions = $examData['questions'] ?? [];
$timeLimit = (int)($examData['time_limit'] ?? 45);
$testName = $examData['test_name'] ?? $examId;

// Retake rules:
// 1. Official exams: 1 attempt for everyone (fair rankings)
// 2. Practice exams: non-premium = 1 attempt, premium = unlimited
$submittedResultId = exam_find_result_id($studentCode, $canonicalTestId, $subjectId);
if ($submittedResultId) {
    if ($examType === 'official') {
        $_SESSION['exam_limit_msg'] = "Đây là bài thi chính thức, chỉ được thi 1 lần duy nhất để đảm bảo công bằng.";
        header('Location: result.php?exam_id=' . urlencode($submittedResultId));
        exit;
    }
    if (!$premiumStatus['is_premium']) {
        $_SESSION['premium_limit_msg'] = "Bạn đã hoàn thành bài luyện tập này. Nâng cấp Premium để thi lại không giới hạn!";
        header('Location: result.php?exam_id=' . urlencode($submittedResultId));
        exit;
    }
}

// Deterministic shuffle so each student gets the same order on every reload
// and the server can re-grade the same order.
if (!empty($questions)) {
    $questions = exam_shuffle_questions($questions, $studentCode, $canonicalTestId);
}

// Load subjects for the subject name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}
$subjectName = $subjects[$subjectId] ?? 'Unknown';

// Safe payloads for JS injection
$jsExam = json_encode([
    'type' => $canonicalTestId ?: $examId,
    'testName' => $testName,
    'studentCode' => $studentCode,
    'studentName' => $studentName,
    'classCode' => $studentClassCode,
    'gradeLevel' => $gradeLevel,
    'timeLimit' => $timeLimit,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
// NOTE: correct answers are STRIPPED before sending to the client (anti-cheat).
$jsQuestions = json_encode(exam_strip_answers($questions), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Thi <?php echo htmlspecialchars($testName); ?> - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/theme-eduvn-student.css">
    <link rel="stylesheet" href="../styles/main.css">
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)'],],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                packages: {'[+]': ['mhchem']}
            },
            loader: {
                load: ['[tex]/mhchem']
            }
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>
    <style>
        .exam-shell { background: var(--page-bg); }
        .exam-topbar { gap: 12px; }
        .exam-topbar .et-title small { display: block; font-family: var(--body); font-weight: 500; color: var(--ink-soft); font-size: .72rem; }
        .exam-timer { min-width: 118px; text-align: center; }
        .exam-timer.low { background: var(--coral); color: #fff; }
        .exam-topbar .btn { font-family: var(--display); font-weight: 700; }
        .btn-outline-violet { border: 2px solid var(--violet); color: var(--violet); background: #fff; font-weight: 700; }
        .btn-outline-violet:hover { background: var(--violet); color: #fff; }
        .exam-body { max-width: 860px; }
        .exam-question .q-text { font-size: 1rem; line-height: 1.7; }
        .exam-question .q-type { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-faint); }
        .exam-navbar .btn { font-family: var(--display); font-weight: 700; }
        .en-progress .progress { height: 10px; border-radius: 99px; background: var(--border); }
        .en-progress .progress-bar { background: var(--grad-violet); border-radius: 99px; }
        .en-progress small { font-weight: 600; color: var(--ink-soft); }
        .q-palette { display: flex; flex-wrap: wrap; gap: 8px; }
        .q-palette .q-num-btn {
            width: 42px; height: 42px; border-radius: 13px; border: 2px solid var(--border);
            background: #fff; font-family: var(--display); font-weight: 700; color: var(--ink);
            display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
            transition: all .15s ease; font-size: .9rem;
        }
        .q-palette .q-num-btn:hover { border-color: var(--violet); transform: translateY(-2px); }
        .q-palette .q-num-btn.answered { background: var(--grad-violet); color: #fff; border-color: transparent; }
        .q-palette .q-num-btn.current { box-shadow: 0 0 0 3px var(--violet-light); border-color: var(--violet); }
        #violationCount { font-weight: 700; font-size: .74rem; }
        .modal-content { border-radius: var(--radius-lg); border: none; box-shadow: 0 24px 60px -12px rgba(32,34,58,.3); }
        .modal-header { border-bottom: 1px solid var(--border); padding: 1.25rem 1.5rem; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { border-top: 1px solid var(--border); padding: 1rem 1.5rem; }
        .btn-warning { color: #3d2e00; }
        .toast-container { position: fixed; top: 18px; right: 18px; z-index: 2000; display: flex; flex-direction: column; gap: 8px; }
        .toast-notification { border-radius: 14px; box-shadow: 0 10px 30px -8px rgba(32,34,58,.35); }
    </style>
</head>
<body class="student-page">
    <div class="exam-shell">

        <!-- Top bar -->
        <div class="exam-topbar">
            <div class="et-logo">✏️</div>
            <div class="et-title">
                <?php echo htmlspecialchars($testName); ?>
                <small><?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>) · <?php echo htmlspecialchars($subjectName); ?></small>
            </div>
            <div class="et-countdown">
                <div class="exam-timer" id="timer"><?php echo str_pad($timeLimit, 2, '0', STR_PAD_LEFT); ?>:00</div>
                <div class="text-center mt-1" id="violationCount"></div>
            </div>
            <button class="btn btn-warning" id="pauseBtn" onclick="pauseExam()">⏸️ Tạm Dừng</button>
            <button class="btn btn-success" onclick="submitExam()">✅ Nộp Bài</button>
        </div>

        <!-- Questions body -->
        <div class="exam-body">
            <div class="d-flex gap-3" style="align-items:flex-start;">
                <!-- Question palette -->
                <div class="card shadow-sm flex-shrink-0 d-none d-lg-block" style="width:280px; border-radius:20px; border:none;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Danh sách câu hỏi</h6>
                        <div class="q-palette" id="questionNav"></div>
                        <div class="en-progress mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small>Tiến độ</small>
                                <small id="progressText">0/0 câu</small>
                            </div>
                            <div class="progress"><div class="progress-bar" id="progressBar" style="width:0%"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div style="flex:1; min-width:0;">
                    <div id="questionsContainer"></div>
                    <div class="d-flex justify-content-center my-4 gap-3">
                        <button class="btn btn-outline-violet px-4" id="prevBtn" onclick="previousQuestion()" disabled>← Câu Trước</button>
                        <button class="btn btn-outline-violet px-4" id="nextBtn" onclick="nextQuestion()">Câu Tiếp →</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Exam Modal -->
    <div class="modal fade" id="startExamModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🔒 Bắt Đầu Bài Thi</h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ Lưu ý quan trọng:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Bài thi sẽ chạy ở chế độ <strong>toàn màn hình</strong></li>
                            <li>Không được thoát màn hình hoặc chuyển tab trong khi thi</li>
                            <li>Vi phạm quá <strong>3 lần</strong> sẽ tự động nộp bài</li>
                            <li>Thời gian thi: <strong><?php echo $timeLimit; ?> phút</strong></li>
                        </ul>
                    </div>
                    <p class="mb-0">Nhấn nút bên dưới để bắt đầu làm bài thi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="startExamFullscreen()">
                        🚀 Bắt Đầu Thi Ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pause Modal -->
    <div class="modal fade" id="pauseModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">⏸️ Tạm Dừng Bài Thi</h5></div>
                <div class="modal-body text-center">
                    <p>Bài thi đã được tạm dừng. Thời gian vẫn được tính chính xác.</p>
                    <p class="text-muted">Nhấn "Tiếp Tục" để quay lại bài thi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="resumeExam()">Tiếp Tục</button>
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
                        <ul id="submitSummary" class="mb-0 mt-2"></ul>
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

    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toastContainer');
            const colors = { success: '#198754', warning: '#f39c12', danger: '#dc3545', info: '#6D5EF0' };
            const toast = document.createElement('div');
            toast.className = 'toast-notification text-white align-items-center border-0';
            toast.style.background = colors[type] || colors.info;
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast-notification').remove()"></button>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => { toast.remove(); }, duration);
        }

        const examData = Object.assign({
            answers: {},
            currentQuestion: 0,
            totalTime: 0,
            startTime: null,
            timeRemaining: 0,
            pauseTime: 0,
            timer: null,
            paused: false,
            pause_used: false,
            violations: 0,
            maxViolations: 3,
            started: false
        }, <?php echo $jsExam; ?>);
        examData.totalTime = examData.timeLimit * 60;
        examData.timeRemaining = examData.totalTime;
        examData.questions = <?php echo $jsQuestions; ?>;

        const examKey = 'exam_' + (examData.type || 'unknown');
        const savedData = localStorage.getItem(examKey);
        const isNavigatingBack = sessionStorage.getItem('examStarted') === 'true';

        if (savedData && !isNavigatingBack) {
            try {
                const parsed = JSON.parse(savedData);
                if (parsed.startTime && !parsed.completed) {
                    showToast('⚠️ Bạn đang có bài thi đang làm dở! Vui lòng hoàn thành bài thi trước.', 'warning', 5000);
                    sessionStorage.setItem('examStarted', 'true');
                    setTimeout(() => window.location.reload(), 1500);
                    throw new Error('Preventing exam reset');
                }
            } catch (e) { /* swallow rethrow */ }
        }

        // Restore progress from a previous session of this exam
        if (savedData && isNavigatingBack) {
            try {
                const parsed = JSON.parse(savedData);
                examData.answers = parsed.answers || {};
                examData.currentQuestion = parsed.currentQuestion || 0;
                examData.startTime = parsed.startTime || null;
                examData.pauseTime = parsed.pauseTime || 0;
                examData.paused = parsed.paused || false;
                examData.pause_used = parsed.pause_used || false;
                examData.violations = parsed.violations || 0;
                if (examData.startTime) {
                    const elapsed = Math.floor((Date.now() - examData.startTime) / 1000) - examData.pauseTime;
                    examData.timeRemaining = Math.max(0, examData.totalTime - elapsed);
                }
            } catch (e) {
                localStorage.removeItem(examKey);
            }
        }

        if (!examData.startTime) {
            examData.startTime = Date.now();
        }

        function saveExamData() {
            localStorage.setItem(examKey, JSON.stringify({
                answers: examData.answers,
                currentQuestion: examData.currentQuestion,
                startTime: examData.startTime,
                pauseTime: examData.pauseTime,
                paused: examData.paused,
                pause_used: examData.pause_used,
                violations: examData.violations,
                completed: false
            }));
        }

        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';
            examData.questions.forEach((question, index) => {
                const qDiv = document.createElement('div');
                qDiv.className = 'exam-question';
                qDiv.id = 'question-' + index;
                qDiv.style.display = index === 0 ? 'block' : 'none';

                let optionsHtml = '';
                if (question.type === 'single') {
                    optionsHtml = question.options.map((option, oi) => `
                        <label class="exam-option ${examData.answers[index] === oi ? 'selected' : ''}" data-q="${index}" data-o="${oi}" data-type="single">
                            <span class="o-key">${String.fromCharCode(65 + oi)}</span>
                            <span>${option}</span>
                        </label>
                    `).join('');
                } else {
                    optionsHtml = question.options.map((option, oi) => {
                        const checked = (examData.answers[index] || []).includes(oi);
                        return `
                            <label class="exam-option ${checked ? 'selected' : ''}" data-q="${index}" data-o="${oi}" data-type="multiple">
                                <span class="o-key">${String.fromCharCode(65 + oi)}</span>
                                <span>${option}</span>
                            </label>
                        `;
                    }).join('');
                }

                qDiv.innerHTML = `
                    <span class="q-type">${question.type === 'single' ? 'Trắc nghiệm 1 đáp án' : 'Trắc nghiệm nhiều đáp án'}</span>
                    <div class="q-num">Câu ${index + 1}${question.level ? ' · ' + question.level : ''}</div>
                    <div class="q-text">${question.question}</div>
                    <div>${optionsHtml}</div>
                `;
                container.appendChild(qDiv);
            });

            // Answer handling
            container.addEventListener('click', (e) => {
                const opt = e.target.closest('.exam-option');
                if (!opt) return;
                const qi = parseInt(opt.dataset.q, 10);
                const oi = parseInt(opt.dataset.o, 10);
                const q = examData.questions[qi];
                if (q.type === 'single') {
                    examData.answers[qi] = oi;
                    opt.parentElement.querySelectorAll('.exam-option').forEach(el => el.classList.remove('selected'));
                    opt.classList.add('selected');
                } else {
                    if (!Array.isArray(examData.answers[qi])) examData.answers[qi] = [];
                    const arr = examData.answers[qi];
                    const idx = arr.indexOf(oi);
                    if (idx >= 0) { arr.splice(idx, 1); opt.classList.remove('selected'); }
                    else { arr.push(oi); opt.classList.add('selected'); }
                    if (arr.length === 0) delete examData.answers[qi];
                }
                renderQuestionNav();
                saveExamData();
            });

            setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(() => {});
                }
            }, 100);
        }

        function renderQuestionNav() {
            const nav = document.getElementById('questionNav');
            nav.innerHTML = '';
            examData.questions.forEach((_, index) => {
                const numDiv = document.createElement('div');
                numDiv.className = 'q-num-btn' +
                    (index === examData.currentQuestion ? ' current' : '') +
                    (examData.answers[index] !== undefined ? ' answered' : '');
                numDiv.textContent = index + 1;
                numDiv.onclick = () => goToQuestion(index);
                nav.appendChild(numDiv);
            });
            updateProgress();
        }

        function goToQuestion(index) {
            examData.questions.forEach((_, i) => {
                const d = document.getElementById('question-' + i);
                if (d) d.style.display = i === index ? 'block' : 'none';
            });
            examData.currentQuestion = index;
            renderQuestionNav();
            saveExamData();
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === examData.questions.length - 1;
        }

        function nextQuestion() {
            if (examData.currentQuestion < examData.questions.length - 1) goToQuestion(examData.currentQuestion + 1);
        }
        function previousQuestion() {
            if (examData.currentQuestion > 0) goToQuestion(examData.currentQuestion - 1);
        }

        function updateProgress() {
            const answered = Object.keys(examData.answers).length;
            const total = examData.questions.length;
            const pct = total ? (answered / total) * 100 : 0;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressText').textContent = answered + '/' + total + ' câu';
            document.getElementById('violationCount').textContent =
                examData.violations > 0 ? ('⚠️ Vi phạm: ' + examData.violations + '/' + examData.maxViolations) : '';
        }

        function startTimer() {
            let lastPauseStart = null;
            examData.timer = setInterval(() => {
                if (!examData.paused) {
                    if (lastPauseStart) {
                        examData.pauseTime += Math.floor((Date.now() - lastPauseStart) / 1000);
                        lastPauseStart = null;
                        saveExamData();
                    }
                    const elapsed = Math.floor((Date.now() - examData.startTime) / 1000) - examData.pauseTime;
                    examData.timeRemaining = Math.max(0, examData.totalTime - elapsed);
                    if (examData.timeRemaining <= 0) {
                        clearInterval(examData.timer);
                        autoSubmitExam();
                    }
                } else {
                    if (!lastPauseStart) lastPauseStart = Date.now();
                }
                updateTimerDisplay();
            }, 1000);
        }

        function updateTimerDisplay() {
            const m = Math.floor(examData.timeRemaining / 60);
            const s = examData.timeRemaining % 60;
            const timerEl = document.getElementById('timer');
            timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            timerEl.classList.toggle('low', examData.timeRemaining < 300);
            saveExamData();
        }

        function pauseExam() {
            if (examData.pause_used) {
                showToast('Bạn chỉ được phép tạm dừng 1 lần. Nút tạm dừng đã bị tắt.', 'warning');
                return;
            }
            examData.paused = true;
            examData.pause_used = true;
            saveExamData();
            const btn = document.getElementById('pauseBtn');
            btn.disabled = true;
            btn.style.opacity = '.5';
            new bootstrap.Modal(document.getElementById('pauseModal')).show();
        }

        function resumeExam() {
            examData.paused = false;
            saveExamData();
        }

        function submitExam() {
            const answered = Object.keys(examData.answers).length;
            const total = examData.questions.length;
            const m = Math.floor(examData.timeRemaining / 60);
            const s = examData.timeRemaining % 60;
            document.getElementById('submitSummary').innerHTML = `
                <li>Tổng số câu hỏi: ${total}</li>
                <li>Đã trả lời: ${answered}</li>
                <li>Chưa trả lời: ${total - answered}</li>
                <li>Thời gian còn lại: ${m}:${String(s).padStart(2, '0')}</li>
            `;
            new bootstrap.Modal(document.getElementById('submitModal')).show();
        }

        function autoSubmitExam() {
            showToast('⏰ Hết thời gian! Bài thi sẽ được nộp tự động.', 'warning', 3000);
            setTimeout(doSubmitExam, 2000);
        }

        document.getElementById('confirmSubmitBtn').addEventListener('click', doSubmitExam);

        async function doSubmitExam() {
            clearInterval(examData.timer);
            sessionStorage.removeItem('examStarted');
            try { localStorage.removeItem(examKey); } catch (e) { /* ignore */ }

            try {
                const response = await fetch('api/submit_exam.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        exam_id: examData.type,
                        answers: examData.answers,
                        violations: examData.violations || 0
                    })
                });

                if (!response.ok) {
                    showToast('Lỗi khi nộp bài — HTTP ' + response.status, 'danger', 6000);
                    saveExamData();
                    return;
                }

                let result = null;
                try {
                    result = await response.json();
                } catch (e) {
                    showToast('Lỗi khi phân tích phản hồi từ server.', 'danger', 6000);
                    saveExamData();
                    return;
                }

                if (result && result.success) {
                    window.location.href = 'result.php?exam_id=' + result.exam_id;
                } else {
                    showToast('Lỗi nộp bài: ' + (result && result.message ? result.message : 'Không rõ'), 'danger', 6000);
                    saveExamData();
                }
            } catch (error) {
                console.error('Error submitting exam:', error);
                showToast('Lỗi kết nối khi nộp bài. Vui lòng liên hệ giáo viên.', 'danger', 6000);
                saveExamData();
            }
        }

        // ---- Anti-cheat ----
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.ctrlKey && ['u', 's', 'a', 'c', 'v', 'p'].includes(e.key)) e.preventDefault();
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) e.preventDefault();
            if (e.key === 'Escape') { e.preventDefault(); return false; }
        });

        function enterFullscreen() {
            const el = document.documentElement;
            return (el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen || function() {
                return Promise.reject('Fullscreen not supported');
            }).call(el);
        }

        // Always start the exam even if fullscreen is denied — never a dead-end.
        function beginExam() {
            renderQuestions();
            renderQuestionNav();
            startTimer();
            examData.started = true;
            sessionStorage.setItem('examStarted', 'true');
            if (examData.pause_used) {
                const btn = document.getElementById('pauseBtn');
                btn.disabled = true;
                btn.style.opacity = '.5';
            }
        }

        window.startExamFullscreen = function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('startExamModal'));
            if (modal) modal.hide();
            enterFullscreen().then(() => {
                beginExam();
                setTimeout(() => showToast('✅ Đã vào chế độ toàn màn hình. Chúc bạn làm bài tốt!', 'success', 4000), 500);
            }).catch(() => {
                beginExam();
                showToast('⚠️ Không vào được toàn màn hình, bạn vẫn có thể làm bài. Không chuyển tab nhé!', 'warning', 5000);
            });
        };

        let fullscreenInitialized = false;
        function handleFullscreenChange() {
            if (!examData.started) return;
            if (!fullscreenInitialized) {
                if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
                    fullscreenInitialized = true;
                }
                return;
            }
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                examData.violations++;
                saveExamData();
                updateProgress();
                if (examData.violations >= examData.maxViolations) {
                    showToast('⚠️ Vi phạm ' + examData.violations + ' lần! Bài thi sẽ được nộp tự động.', 'danger', 3000);
                    setTimeout(() => doSubmitExam(), 1000);
                } else {
                    showToast('⚠️ Cảnh báo ' + examData.violations + '/' + examData.maxViolations + ': Không được thoát chế độ toàn màn hình!', 'warning', 3000);
                    setTimeout(() => {
                        enterFullscreen().catch(() => {});
                    }, 100);
                }
            }
        }
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);

        let tabSwitchWarningShown = false;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && examData.started) {
                examData.violations++;
                saveExamData();
                if (examData.violations >= examData.maxViolations) {
                    showToast('⚠️ Vi phạm ' + examData.violations + ' lần (chuyển tab/cửa sổ)! Bài thi sẽ được nộp tự động.', 'danger', 3000);
                    setTimeout(() => doSubmitExam(), 1000);
                } else if (!tabSwitchWarningShown) {
                    tabSwitchWarningShown = true;
                    showToast('⚠️ Cảnh báo ' + examData.violations + '/' + examData.maxViolations + ': Không được chuyển tab hoặc cửa sổ khác trong khi thi!', 'warning', 4000);
                    setTimeout(() => tabSwitchWarningShown = false, 4500);
                }
            }
        });

        // Prevent browser back button (only counts after the exam starts)
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.pushState(null, null, location.href);
            if (!examData.started) return;
            examData.violations++;
            saveExamData();
            showToast('⚠️ Không được sử dụng nút Back trong khi thi!', 'warning', 3000);
            if (examData.violations >= examData.maxViolations) {
                showToast('⚠️ Vi phạm ' + examData.violations + ' lần! Bài thi sẽ được nộp tự động.', 'danger', 3000);
                setTimeout(() => doSubmitExam(), 1000);
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const isResuming = savedData && isNavigatingBack && examData.startTime;
            if (isResuming) {
                beginExam();
                setTimeout(() => {
                    enterFullscreen().catch(() => {
                        showToast('⚠️ Vui lòng cho phép toàn màn hình để tiếp tục thi.', 'warning', 4000);
                    });
                }, 500);
            } else {
                new bootstrap.Modal(document.getElementById('startExamModal')).show();
            }
        });

        window.addEventListener('load', function() {
            setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) MathJax.typesetPromise().catch(() => {});
            }, 100);
        });
    </script>
</body>
</html>
