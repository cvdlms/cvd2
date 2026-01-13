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

// Shuffle questions based on student code and exam ID to ensure each student gets different order
// but the same order every time they reload (deterministic shuffle)
if (!empty($questions)) {
    $seed = crc32($studentCode . '_' . $canonicalTestId);
    mt_srand($seed);
    
    // Fisher-Yates shuffle with seeded random
    $count = count($questions);
    for ($i = $count - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $temp = $questions[$i];
        $questions[$i] = $questions[$j];
        $questions[$j] = $temp;
    }
    
    // Reset random seed to avoid affecting other random operations
    mt_srand();
}

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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .question-card {
            margin-bottom: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border: none;
            background: white;
        }
        .question-card .card-body {
            padding: 1.5rem;
        }
        .question-card .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0;
            display: inline;
        }
        .question-card .card-text {
            font-size: 1.25rem;
            line-height: 1.8;
            color: #1a202c;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        .form-check {
            margin-bottom: 0.75rem;
            padding: 0;
        }
        .option-label {
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            display: block;
            font-size: 1.3rem;
            line-height: 1.6;
            background: #f7fafc;
            font-weight: 500;
            color: #2d3748;
        }
        .option-label:hover {
            background: #edf2f7;
            border-color: #667eea;
            transform: translateX(4px);
        }
        .form-check-input:checked + .option-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            font-weight: 600;
        }
        .form-check-input {
            display: none;
        }
        .timer {
            font-size: 2rem;
            font-weight: 800;
            color: #e53e3e;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            line-height: 1;
            margin: 0.25rem 0;
        }
        .question-nav {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
        }
        .question-number {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 4px;
            cursor: pointer;
            border: 2px solid #cbd5e0;
            background: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .question-number:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .question-number.answered {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border-color: #38a169;
        }
        .question-number.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: scale(1.15);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        .exam-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1000;
            border-bottom: 3px solid #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 0 !important;
        }
        .exam-header h5 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.1rem;
            line-height: 1.2;
        }
        .exam-header small {
            font-size: 0.8rem;
            line-height: 1.2;
        }
        .exam-header .row {
            margin: 0;
        }
        .exam-header .col-md-4 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .btn {
            padding: 0.5rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border: none;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            border: none;
            color: white;
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(237, 137, 54, 0.4);
        }
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        .progress {
            height: 10px;
            border-radius: 10px;
            background: #e2e8f0;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 350px;
        }
        .toast {
            font-size: 1.1rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal-header {
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }
        .modal-body {
            padding: 2rem;
            font-size: 1.1rem;
        }
        .alert {
            border-radius: 12px;
            font-size: 1.05rem;
            padding: 1.25rem;
        }
        #violationCount {
            font-size: 1.1rem;
        }
        .container-fluid {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 1rem;
            margin-bottom: 2rem;
        }
        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
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
                    <div class="mt-1">
                        <small class="text-danger" id="violationCount" style="font-weight: bold;"></small>
                    </div>
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

    <!-- Start Exam Modal - Required for fullscreen -->
    <div class="modal fade" id="startExamModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
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
                    <button type="button" class="btn btn-primary btn-lg w-100" id="startExamBtn" onclick="startExamFullscreen()">
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
        // Toast notification function (doesn't break fullscreen like alert())
        function showToast(message, type = 'info', duration = 3000) {
            const toastContainer = document.getElementById('toastContainer') || (() => {
                const container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-notification';
                document.body.appendChild(container);
                return container;
            })();
            
            const colors = {
                'success': 'bg-success',
                'warning': 'bg-warning',
                'danger': 'bg-danger',
                'info': 'bg-info'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white ${colors[type] || colors['info']} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: duration });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
        
        // Use canonical test_id when available to identify the exam uniquely
        const canonicalTestId = '<?php echo $canonicalTestId ?? ''; ?>';
        const examKey = 'exam_' + (canonicalTestId || '<?php echo $examId; ?>');
        
        // ANTI-CHEAT: Check if student is trying to restart an ongoing exam
        // This prevents time reset by blocking fresh start if exam is already in progress
        const savedData = localStorage.getItem(examKey);
        const isNavigatingBack = sessionStorage.getItem('examStarted') === 'true';
        
        if (savedData && !isNavigatingBack) {
            // Student is trying to start a NEW exam session while one is already in progress
            // This means they clicked "Bắt đầu thi" from dashboard while exam is ongoing
            // We should NOT allow this - redirect them back to continue the exam
            const parsed = JSON.parse(savedData);
            if (parsed.startTime) {
                // Exam is in progress - prevent reset by showing warning
                showToast('⚠️ Bạn đang có bài thi đang làm dở! Vui lòng hoàn thành bài thi trước.', 'warning', 5000);
                // Set session flag and reload to resume exam
                sessionStorage.setItem('examStarted', 'true');
                setTimeout(() => window.location.reload(), 1500);
                throw new Error('Preventing exam reset');
            }
        }

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
            totalTime: <?php echo $timeLimit; ?> * 60, // Total exam time in seconds
            startTime: null, // Timestamp when exam started
            timeRemaining: <?php echo $timeLimit; ?> * 60, // Calculated time remaining
            pauseTime: 0, // Total paused time in seconds
            timer: null,
            paused: false,
            pause_used: false,  // ANTI-CHEAT: Track if pause button has been used
            violations: 0,  // Count tab switches / fullscreen exits
            maxViolations: 3  // Auto-submit after 3 violations
        };

        // CRITICAL: Restore from localStorage if available and valid
        // Use timestamp-based calculation to prevent time reset exploit
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
                
                // Calculate time remaining based on elapsed time since start
                if (examData.startTime) {
                    const now = Date.now();
                    const elapsed = Math.floor((now - examData.startTime) / 1000) - examData.pauseTime;
                    examData.timeRemaining = Math.max(0, examData.totalTime - elapsed);
                }
            } catch (e) {
                console.error('Error parsing saved exam data:', e);
                // If corrupted data, remove it
                localStorage.removeItem(examKey);
            }
        }
        
        // Initialize start time if this is the first time
        if (!examData.startTime) {
            examData.startTime = Date.now();
        }

        // Save to localStorage
        function saveExamData() {
            localStorage.setItem(examKey, JSON.stringify({
                answers: examData.answers,
                currentQuestion: examData.currentQuestion,
                startTime: examData.startTime,
                pauseTime: examData.pauseTime,
                paused: examData.paused,
                pause_used: examData.pause_used,
                violations: examData.violations
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
                        <p class="card-text"><strong style="font-size: 1.5rem; color: #667eea;">Câu ${index + 1}:</strong> ${question.question}</p>
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
            let lastPauseStart = null;
            
            examData.timer = setInterval(() => {
                if (!examData.paused) {
                    // Calculate time based on elapsed time since start
                    const now = Date.now();
                    const elapsed = Math.floor((now - examData.startTime) / 1000) - examData.pauseTime;
                    examData.timeRemaining = Math.max(0, examData.totalTime - elapsed);

                    if (examData.timeRemaining <= 0) {
                        clearInterval(examData.timer);
                        autoSubmitExam();
                    }

                    updateTimerDisplay();
                } else {
                    // Track pause time
                    if (!lastPauseStart) {
                        lastPauseStart = Date.now();
                    }
                }
                
                // If resumed from pause, add the paused duration
                if (lastPauseStart && !examData.paused) {
                    examData.pauseTime += Math.floor((Date.now() - lastPauseStart) / 1000);
                    lastPauseStart = null;
                    saveExamData();
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
            
            // Update violation count display
            const violationElement = document.getElementById('violationCount');
            if (examData.violations > 0) {
                violationElement.textContent = `⚠️ Vi phạm: ${examData.violations}/${examData.maxViolations}`;
            } else {
                violationElement.textContent = '';
            }
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
            // Prevent Escape key to exit fullscreen
            if (e.key === 'Escape') {
                e.preventDefault();
                return false;
            }
        });

        // ANTI-CHEAT: Fullscreen enforcement
        function enterFullscreen() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                return elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                return elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                return elem.msRequestFullscreen();
            }
            return Promise.reject('Fullscreen not supported');
        }

        // Start exam in fullscreen (called from user click)
        window.startExamFullscreen = function() {
            // Hide modal first before entering fullscreen
            const modal = bootstrap.Modal.getInstance(document.getElementById('startExamModal'));
            modal.hide();
            
            // Wait for modal to completely hide, then enter fullscreen
            setTimeout(() => {
                enterFullscreen().then(() => {
                    // Successfully entered fullscreen
                    renderQuestions();
                    renderQuestionNav();
                    startTimer();
                    sessionStorage.setItem('examStarted', 'true');
                    
                    // Use toast instead of alert to avoid breaking fullscreen
                    setTimeout(() => {
                        if (examData.violations === 0) {
                            showToast('✅ Đã vào chế độ toàn màn hình. Chúc bạn làm bài tốt!', 'success', 4000);
                        }
                    }, 500);
                }).catch(err => {
                    showToast('❌ Không thể vào chế độ toàn màn hình. Vui lòng cho phép và thử lại.', 'danger', 5000);
                    console.error('Fullscreen error:', err);
                });
            }, 300);
        };

        // Detect fullscreen exit
        let fullscreenInitialized = false;
        
        function handleFullscreenChange() {
            // Only process if exam has started (prevent false trigger during initialization)
            if (!fullscreenInitialized) {
                // First fullscreen entry - don't count as violation
                if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
                    fullscreenInitialized = true;
                }
                return;
            }
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                // User exited fullscreen - count violation
                examData.violations++;
                saveExamData();
                updateProgress(); // Update violation display
                
                if (examData.violations >= examData.maxViolations) {
                    // Max violations reached - submit exam
                    showToast(`⚠️ Vi phạm ${examData.violations} lần! Bài thi sẽ được nộp tự động.`, 'danger', 3000);
                    setTimeout(() => doSubmitExam(), 1000);
                } else {
                    // Show warning and immediately re-enter fullscreen
                    showToast(`⚠️ Cảnh báo ${examData.violations}/${examData.maxViolations}: Không được thoát chế độ toàn màn hình!`, 'warning', 3000);
                    
                    // Immediately try to re-enter fullscreen (continuously)
                    setTimeout(() => {
                        enterFullscreen().catch(err => {
                            // If failed, keep trying every 500ms until success
                            const retryInterval = setInterval(() => {
                                if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
                                    clearInterval(retryInterval);
                                    return;
                                }
                                enterFullscreen().then(() => {
                                    clearInterval(retryInterval);
                                }).catch(() => {
                                    // Keep retrying
                                });
                            }, 500);
                        });
                    }, 100);
                }
            }
        }

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);

        // ANTI-CHEAT: Detect tab switch / window blur
        let tabSwitchWarningShown = false;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Student switched tab or minimized window
                examData.violations++;
                saveExamData();
                
                if (examData.violations >= examData.maxViolations) {
                    showToast(`⚠️ Vi phạm ${examData.violations} lần (chuyển tab/cửa sổ)! Bài thi sẽ được nộp tự động.`, 'danger', 3000);
                    setTimeout(() => doSubmitExam(), 1000);
                } else if (!tabSwitchWarningShown) {
                    tabSwitchWarningShown = true;
                    showToast(`⚠️ Cảnh báo ${examData.violations}/${examData.maxViolations}: Không được chuyển tab hoặc cửa sổ khác trong khi thi!`, 'warning', 4000);
                    setTimeout(() => tabSwitchWarningShown = false, 4500);
                }
            }
        });

        // Prevent browser back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.pushState(null, null, location.href);
            examData.violations++;
            saveExamData();
            
            showToast('⚠️ Không được sử dụng nút Back trong khi thi!', 'warning', 3000);
            
            if (examData.violations >= examData.maxViolations) {
                showToast(`⚠️ Vi phạm ${examData.violations} lần! Bài thi sẽ được nộp tự động.`, 'danger', 3000);
                setTimeout(() => doSubmitExam(), 1000);
            }
        };

        // Load questions on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Check if resuming exam (already started before)
            const isResuming = savedData && isNavigatingBack && examData.startTime;
            
            if (isResuming) {
                // Resume exam - skip modal and enter fullscreen directly
                renderQuestions();
                renderQuestionNav();
                startTimer();
                sessionStorage.setItem('examStarted', 'true');
                
                // Try to enter fullscreen again
                setTimeout(() => {
                    enterFullscreen().catch(() => {
                        showToast('⚠️ Vui lòng cho phép toàn màn hình để tiếp tục thi.', 'warning', 4000);
                    });
                }, 500);
            } else {
                // New exam - show modal to start
                const startModal = new bootstrap.Modal(document.getElementById('startExamModal'));
                startModal.show();
            }
            
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
