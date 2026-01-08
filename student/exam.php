<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$examId = $_GET['exam_id'] ?? $_GET['type'] ?? '';
if (!$examId) {
    header('Location: dashboard.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Function to create URL-friendly slug
function create_slug($string) {
    // Ensure string is valid UTF-8
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    // Remove accents
    $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    // Replace non-alphanumeric with dashes
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    // Remove multiple dashes
    $string = preg_replace('/-+/', '-', $string);
    // Trim dashes from start and end
    $string = trim($string, '-');
    // Lowercase
    $string = strtolower($string);
    return $string;
}

// Determine grade level from class code
$prefix = substr($studentClassCode, 0, 1);
$grade = 'khoi' . $prefix;
$gradeLevel = $prefix;

// Parse exam ID - handle both legacy format (subject_id_slug) and new format (test_id)
// Legacy: 1_kttx-1 (parsed as subject_id=1, slug=kttx-1)
// New: SUB_20251229110817_b70bfc (this is test_id, need to search for it)

$subjectId = null;
$slug = null;
$examFile = null;

// Try to detect format: if starts with digit(s)_, it's legacy format
if (preg_match('/^(\d+)_(.+)$/', $examId, $matches)) {
    // Legacy format: subject_id_slug
    $subjectId = (int)$matches[1];
    $slug = $matches[2];
    $examDir = __DIR__ . '/../teacher/exams/' . $grade . '/subject_' . $subjectId . '/';
    $examFile = $examDir . $slug . '.json';
    
    if (!file_exists($examFile)) {
        // Find by slug matching test_name
        $files = @glob($examDir . '*.json') ?: [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && create_slug($data['test_name']) === $slug) {
                $examFile = $file;
                break;
            }
        }
    }
} else {
    // New format: test_id - need to search all grades/subjects for matching test_id
    $baseExams = __DIR__ . '/../teacher/exams/';
    $gradeDirs = @glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
    foreach ($gradeDirs as $gradeDir) {
        $subjectDirs = @glob($gradeDir . '/subject_*', GLOB_ONLYDIR) ?: [];
        foreach ($subjectDirs as $subjectDir) {
            preg_match('/subject_(\d+)/', $subjectDir, $matches);
            $sid = (int)$matches[1];
            $files = @glob($subjectDir . '/*.json') ?: [];
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && ($data['test_id'] ?? '') === $examId) {
                    // Found matching test_id
                    $examFile = $file;
                    $subjectId = $sid;
                    break 3;  // Break all loops
                }
            }
        }
    }
}

if (!file_exists($examFile)) {
    header('Location: dashboard.php');
    exit;
}

// Load exam data first to get test_id for exact matching
$examData = json_decode(file_get_contents($examFile), true);
$canonicalTestId = $examData['test_id'] ?? null;

// If the student already submitted this exam, redirect to the result page
// Check against both consolidated and per-student score files
$consolidatedScoreFile = __DIR__ . '/../shared/scores/student_score.json';
$studentScoresFile = __DIR__ . '/../shared/scores/' . $studentCode . '.json';

function hasStudentSubmittedExam($studentCode, $examId, $canonicalTestId, $consolidatedFile, $perStudentFile) {
    // Check consolidated file first
    if (file_exists($consolidatedFile)) {
        $data = json_decode(file_get_contents($consolidatedFile), true) ?: [];
        foreach ($data as $entry) {
            if (($entry['student_id'] ?? '') !== $studentCode) continue;
            $storedId = $entry['exam_id'] ?? '';
            // Match by canonical test_id (primary) or by passed exam_id (fallback)
            if ($canonicalTestId && $storedId === $canonicalTestId) {
                return $entry['result_id'] ?? $entry['id'] ?? null;
            }
            if ($storedId === $examId) {
                return $entry['result_id'] ?? $entry['id'] ?? null;
            }
        }
    }
    // Check per-student file as fallback
    if (file_exists($perStudentFile)) {
        $data = json_decode(file_get_contents($perStudentFile), true) ?: [];
        foreach ($data as $entry) {
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            // Match by canonical test_id (primary) or by passed exam_id (fallback)
            if ($canonicalTestId && $storedId === $canonicalTestId) {
                return $entry['id'] ?? null;
            }
            if ($storedId === $examId) {
                return $entry['id'] ?? null;
            }
        }
    }
    return null;
}

$submittedResultId = hasStudentSubmittedExam($studentCode, $examId, $canonicalTestId, $consolidatedScoreFile, $studentScoresFile);
if ($submittedResultId) {
    header('Location: result.php?exam_id=' . urlencode($submittedResultId));
    exit;
}

$examData = json_decode(file_get_contents($examFile), true);
$questions = $examData['questions'] ?? [];
$timeLimit = $examData['time_limit'] ?? 45;
$testName = $examData['test_name'] ?? $examId;

// Load subjects to get subject name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}
$subjectName = $subjects[$subjectId] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Thi <?php echo htmlspecialchars($testName); ?> - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <h5 class="mb-0">Bài Thi <?php echo htmlspecialchars($testName); ?> - <?php echo htmlspecialchars($subjectName); ?></h5>
                    <small class="text-muted"><?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>)</small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="timer" id="timer"><?php echo str_pad($timeLimit, 2, '0', STR_PAD_LEFT); ?>:00</div>
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
                <div class="d-flex justify-content-center my-4">
                    <button class="btn btn-outline-primary me-4" id="prevBtn" onclick="previousQuestion()" disabled>
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
    <div class="modal fade" id="pauseModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạm Dừng Bài Thi</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-pause-circle" style="font-size: 3rem; color: #ffc107;"></i>
                    </div>
                    <p>Bài thi đã được tạm dừng.</p>
                    <p class="text-muted">Nhấn "Tiếp Tục" để quay lại bài thi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="examData.paused = false;">Tiếp Tục</button>
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
        // Use canonical test_id when available to identify the exam uniquely
        const canonicalTestId = '<?php echo $canonicalTestId ?? ''; ?>';
        const examKey = 'exam_' + (canonicalTestId || '<?php echo $examId; ?>');

        let examData = {
            type: canonicalTestId || '<?php echo $examId; ?>',
            testName: '<?php echo htmlspecialchars($testName); ?>',
            studentCode: '<?php echo $studentCode; ?>',
            studentName: '<?php echo $studentName; ?>',
            classCode: '<?php echo $studentClassCode; ?>',
            gradeLevel: '<?php echo $gradeLevel; ?>',
            questions: <?php echo json_encode($questions); ?>,
            answers: {},
            currentQuestion: 0,
            timeRemaining: <?php echo $timeLimit; ?> * 60, // minutes in seconds
            timer: null,
            paused: false,
            pause_used: false  // ANTI-CHEAT: Track if pause button has been used
        };

        // CRITICAL: Load from localStorage ONLY if it's a fresh session (not coming back from browser history)
        // If browser back button was used, sessionStorage won't have 'examStarted', which means we should NOT restore from localStorage
        const savedData = localStorage.getItem(examKey);
        const isResumeSession = sessionStorage.getItem('examStarted') === 'true';
        
        if (savedData && isResumeSession) {
            // Only restore if this is genuinely a resume (same tab, not back button)
            const parsed = JSON.parse(savedData);
            examData.answers = parsed.answers || {};
            examData.currentQuestion = parsed.currentQuestion || 0;
            examData.timeRemaining = parsed.timeRemaining || examData.timeRemaining;
            examData.paused = parsed.paused || false;
            examData.pause_used = parsed.pause_used || false;
        } else if (savedData) {
            // Browser back was pressed or new session — CLEAR the old exam state
            try { localStorage.removeItem(examKey); } catch (e) { /* ignore */ }
        }

        // Save to localStorage
        function saveExamData() {
            localStorage.setItem(examKey, JSON.stringify({
                answers: examData.answers,
                currentQuestion: examData.currentQuestion,
                timeRemaining: examData.timeRemaining,
                paused: examData.paused,
                pause_used: examData.pause_used
            }));
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

            // Render math formulas in the newly added questions
             setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(function(err) { 
                        console.log('MathJax error:', err); 
                    });
                }
            }, 100);
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
            saveExamData();
        }

        // Navigation functions
        function goToQuestion(index) {
            // Hide all questions first
            examData.questions.forEach((_, i) => {
                const questionDiv = document.getElementById(`question-${i}`);
                if (questionDiv) {
                    questionDiv.style.display = 'none';
                }
            });
            examData.currentQuestion = index;
            const targetQuestion = document.getElementById(`question-${examData.currentQuestion}`);
            if (targetQuestion) {
                targetQuestion.style.display = 'block';
            }
            renderQuestionNav();
            saveExamData();

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

            saveExamData();
        }

        function pauseExam() {
            // ANTI-CHEAT: Only allow pause once
            if (examData.pause_used) {
                alert('Bạn chỉ được phép tạm dừng 1 lần. Nút tạm dừng đã bị tắt.');
                return;
            }
            
            examData.paused = true;
            examData.pause_used = true;  // Mark pause as used
            saveExamData();
            
            // Disable pause button
            const pauseBtn = document.querySelector('button[onclick="pauseExam()"]');
            if (pauseBtn) {
                pauseBtn.disabled = true;
                pauseBtn.style.opacity = '0.5';
                pauseBtn.style.cursor = 'not-allowed';
            }
            
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
            sessionStorage.removeItem('examStarted');
            
            // Immediately remove localStorage to prevent accidental restore on browser back
            try { localStorage.removeItem(examKey); } catch (e) { /* ignore */ }

            try {
                const response = await fetch('api/submit_exam.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...examData, exam_id: examData.type, test_name: examData.testName })
                });

                if (!response.ok) {
                    const text = await response.text();
                    alert('Lỗi khi nộp bài — HTTP ' + response.status + '\n' + text.substring(0, 200));
                    console.error('Submit failed:', response.status, text);
                    // If submit failed, restore localStorage so user can try again
                    saveExamData();
                    return;
                }

                // Try to parse JSON but provide fallback debug on failure
                let result = null;
                try {
                    result = await response.json();
                } catch (parseErr) {
                    const txt = await response.text();
                    alert('Lỗi khi phân tích phản hồi từ server. Xem console để biết chi tiết.');
                    console.error('Failed to parse JSON response:', parseErr, 'raw response:', txt);
                    // If parse failed, restore localStorage so user can try again
                    saveExamData();
                    return;
                }

                if (result && result.success) {
                    // Submit succeeded — localStorage already cleared above, just redirect
                    window.location.href = `result.php?exam_id=${result.exam_id}`;
                } else {
                    alert('Lỗi nộp bài: ' + (result && result.message ? result.message : 'Không rõ'));
                    // If backend returned error, restore localStorage so user can try again
                    saveExamData();
                }
            } catch (error) {
                console.error('Error submitting exam:', error);
                alert('Lỗi kết nối khi nộp bài. Vui lòng liên hệ giáo viên.');
                // If network error, restore localStorage so user can try again
                saveExamData();
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
        document.addEventListener('DOMContentLoaded', () => {
            // If this is a new page load (sessionStorage has no examStarted flag), clear old exam data
            // This prevents browser back button from restoring old exam state
            if (sessionStorage.getItem('examStarted') !== 'true') {
                try { localStorage.removeItem(examKey); } catch (e) { /* ignore */ }
            }
            
            renderQuestions();
            renderQuestionNav();
            startTimer();
            sessionStorage.setItem('examStarted', 'true');
            
            // ANTI-CHEAT: Disable pause button if it has been used
            if (examData.pause_used) {
                const pauseBtn = document.querySelector('button[onclick="pauseExam()"]');
                if (pauseBtn) {
                    pauseBtn.disabled = true;
                    pauseBtn.style.opacity = '0.5';
                    pauseBtn.style.cursor = 'not-allowed';
                    pauseBtn.title = 'Nút tạm dừng đã bị tắt (chỉ được sử dụng 1 lần)';
                }
            }

            // Render math formulas with KaTeX
            setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(function(err) {
                        console.log('MathJax error:', err);
                    });
                }
            }, 100);
        });

        // Additional MathJax rendering on window load for better formula display
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(function(err) {
                        console.log('MathJax error:', err);
                    });
                }
            }, 100);
        });
    </script>
</body>
</html>
