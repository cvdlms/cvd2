<?php
require_once 'session_check.php';

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

// Determine student grade from class code
$prefix = substr($studentClassCode, 0, 1);
$studentGrade = 'khoi' . $prefix;

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

// Load student scores to check attempts
$studentScoreFile = __DIR__ . '/../shared/scores/student_score.json';
$studentScores = [];
if (file_exists($studentScoreFile)) {
    $studentScores = json_decode(file_get_contents($studentScoreFile), true) ?: [];
}

// Load approved exams for the student's grade
$approvedExams = [];
$examsDir = __DIR__ . '/../teacher/exams/' . $studentGrade;
if (is_dir($examsDir)) {
    $subjectDirs = scandir($examsDir);
    foreach ($subjectDirs as $subjectDir) {
        if ($subjectDir === '.' || $subjectDir === '..') continue;
        if (preg_match('/subject_(\d+)/', $subjectDir, $matches)) {
            $subjectId = (int)$matches[1];
            $subjectPath = $examsDir . '/' . $subjectDir;
            if (is_dir($subjectPath)) {
                $files = scandir($subjectPath);
                foreach ($files as $file) {
                    if (preg_match('/\.json$/', $file)) {
                        $examPath = $subjectPath . '/' . $file;
                        $examData = json_decode(file_get_contents($examPath), true);
                        if ($examData && ($examData['approved'] ?? false)) {
                            // Use canonical test_id as exam identifier; extract subject_id from directory
                            $testId = $examData['test_id'] ?? null;
                            $testName = $examData['test_name'] ?? $file;
                            
                            // Build exam ID key: test_id is the unique identifier
                            // If test_id not available (legacy), use subject_id_slug fallback
                            if ($testId) {
                                $examId = $testId;
                            } else {
                                $examCode = create_slug($testName);
                                $examId = $subjectId . '_' . $examCode;
                            }

                            // Check if student has completed this exam by matching test_id + subject_id exactly
                            // This prevents false matches when test_name is identical across subjects
                            $hasCompleted = false;
                            foreach ($studentScores as $score) {
                                if ($score['student_id'] !== $studentCode) continue;
                                
                                // Match by canonical test_id (primary)
                                $storedId = $score['exam_id'] ?? '';
                                if ($storedId === $examId) {
                                    // Additional check: verify subject_id matches to prevent false positives
                                    if (!isset($score['subject_id']) || $score['subject_id'] == $subjectId) {
                                        $hasCompleted = true;
                                        break;
                                    }
                                }
                                
                                // Fallback: also match by test_id if available
                                if ($testId && $storedId === $testId) {
                                    if (!isset($score['subject_id']) || $score['subject_id'] == $subjectId) {
                                        $hasCompleted = true;
                                        break;
                                    }
                                }
                            }

                            // Only add exam if not completed by this student
                            if (!$hasCompleted) {
                                $approvedExams[] = [
                                    'id' => $examId,
                                    'test_id' => $testId,
                                    'test_name' => $testName,
                                    'subject_id' => $subjectId,
                                    'subject_name' => $subjects[$subjectId] ?? 'Unknown',
                                    'file' => $file,
                                    'total_questions' => $examData['total_questions'] ?? 0,
                                    'time_limit' => is_numeric($examData['time_limit'] ?? null) ? (int)$examData['time_limit'] : 45
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
}
// No fallback scan: only show exams for the student's computed grade
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Học Sinh - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .exam-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .exam-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'refresh_not_allowed'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>⚠️ Cảnh báo:</strong> Bạn không được phép refresh trang trong khi thi. Bài thi đã bị hủy.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title">Chào mừng <?php echo htmlspecialchars($studentName); ?>!</h3>
                        <p class="card-text mb-0">Lớp: <?php echo htmlspecialchars($studentClass); ?> | Mã HS: <?php echo htmlspecialchars($studentCode); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Types -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">📝 Chọn Bài Kiểm Tra</h4>
            </div>
        </div>

        <div class="row">
            <?php if (count($approvedExams) === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Chưa có bài kiểm tra nào được duyệt cho khối của bạn.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($approvedExams as $exam): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card exam-card h-100">
                            <div class="card-body text-center">
                                <div class="exam-icon">📊</div>
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['test_name']); ?></h5>
                                <p class="card-text">Môn: <?php echo htmlspecialchars($exam['subject_name']); ?></p>
                                <div class="mt-3">
                                    <span class="badge bg-primary"><?php echo $exam['time_limit']; ?> phút</span>
                                    <span class="badge bg-info ms-2"><?php echo $exam['total_questions']; ?> câu</span>
                                    <span class="badge bg-secondary ms-2" id="attempts-<?php echo $exam['id']; ?>">Đang tải...</span>
                                </div>
                                <button class="btn btn-primary mt-3" onclick="startExam('<?php echo $exam['id']; ?>', '<?php echo htmlspecialchars($exam['test_name']); ?>', <?php echo $exam['time_limit']; ?>)">
                                    🚀 Bắt Đầu Thi
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="totalExams">-</h3>
                        <p class="mb-0">Tổng bài thi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="averageScore">-</h3>
                        <p class="mb-0">Điểm trung bình</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="highestScore">-</h3>
                        <p class="mb-0">Điểm cao nhất</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h3 id="passRate">-</h3>
                        <p class="mb-0">Tỷ lệ đỗ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">📊 Lịch Sử Bài Thi</h4>
                <div class="card">
                    <div class="card-body">
                        <table id="resultsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Loại Thi</th>
                                    <th>Lần Thi</th>
                                    <th>Điểm</th>
                                    <th>Xếp Loại</th>
                                    <th>Thời Gian</th>
                                    <th>Chi Tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Start Confirmation Modal -->
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác Nhận Bắt Đầu Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ Lưu ý quan trọng:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Bài thi sẽ bắt đầu ngay khi bạn nhấn "Bắt Đầu"</li>
                            <li>Thời gian làm bài là <span id="examTimeLimit">45</span> phút</li>
                            <li>Không được phép rời khỏi trang trong khi thi</li>
                            <li>Kết quả sẽ được lưu tự động khi hết thời gian</li>
                        </ul>
                    </div>
                    <p class="mb-0">Bạn có chắc muốn bắt đầu bài thi <strong id="examTypeText"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="confirmStartBtn">Bắt Đầu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Detail Modal -->
    <div class="modal fade" id="examDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="examDetailContent">
                        <!-- Exam details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-primary" onclick="printExamDetail()">In Chi Tiết</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedExamType = '';
        let selectedExamName = '';
        let resultsTable;
        let allResults = [];

        function startExam(examType, examName, timeLimit = 45) {
            selectedExamType = examType;
            selectedExamName = examName;
            document.getElementById('examTypeText').textContent = examName;
            // Ensure timeLimit is a valid number
            timeLimit = parseInt(timeLimit) || 45;
            // Update the modal with the correct time limit
            document.getElementById('examTimeLimit').textContent = timeLimit;
            new bootstrap.Modal(document.getElementById('examModal')).show();
        }

        document.getElementById('confirmStartBtn').addEventListener('click', function() {
            // Check if student has already taken this exam
            fetch(`api/check_attempts.php?test_name=${encodeURIComponent(selectedExamName)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.can_take) {
                        // Clear any existing localStorage for this exam to start fresh
                        localStorage.removeItem(`exam_${selectedExamType}`);
                        window.location.href = `exam.php?type=${selectedExamType}`;
                    } else {
                        alert(`Bạn đã thi ${selectedExamName} ${data.attempts}/2 lần. ${data.message}`);
                        bootstrap.Modal.getInstance(document.getElementById('examModal')).hide();
                    }
                })
                .catch(error => {
                    console.error('Error checking attempts:', error);
                    alert('Lỗi kiểm tra số lần thi. Vui lòng thử lại.');
                });
        });

        // Load student results
        async function loadResults() {
            try {
                const response = await fetch('api/get_student_results.php');
                const data = await response.json();

                if (data.success) {
                    allResults = data.results;
                    displayStatistics();
                    displayResultsTable();
                } else {
                    document.querySelector('#resultsTable tbody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-muted">Chưa có kết quả thi nào.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading results:', error);
                alert('Lỗi tải kết quả: ' + error.message);
            }
        }

        // Display statistics
        function displayStatistics() {
            const totalExams = allResults.length;

            if (totalExams === 0) {
                document.getElementById('totalExams').textContent = '0';
                document.getElementById('averageScore').textContent = '-';
                document.getElementById('highestScore').textContent = '-';
                document.getElementById('passRate').textContent = '-';
                return;
            }

            // Calculate statistics
            let totalScore = 0;
            let highestScore = 0;
            let passedExams = 0;

            allResults.forEach(result => {
                if (result.score !== null) {
                    totalScore += result.score;
                    if (result.score > highestScore) highestScore = result.score;
                    if (result.score >= 5.0) passedExams++;
                }
            });

            const averageScore = (totalScore / totalExams).toFixed(1);
            const passRate = ((passedExams / totalExams) * 100).toFixed(1) + '%';

            document.getElementById('totalExams').textContent = totalExams;
            document.getElementById('averageScore').textContent = averageScore;
            document.getElementById('highestScore').textContent = highestScore.toFixed(1);
            document.getElementById('passRate').textContent = passRate;
        }

        // Display results table
        function displayResultsTable() {
            if (resultsTable) {
                resultsTable.destroy();
            }

            resultsTable = $('#resultsTable').DataTable({
                data: allResults,
                columns: [
                    {
                        data: null,
                        render: function(data) {
                            return data.test_name || data.exam_type;
                        }
                    },
                    { data: 'attempt' },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="text-muted">Chưa hoàn thành</span>';
                            return `<strong>${data}</strong>`;
                        }
                    },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="badge bg-secondary">Chưa hoàn thành</span>';

                            let grade = 'F';
                            let badgeClass = 'bg-danger';

                            if (data >= 9.0) { grade = 'A+'; badgeClass = 'bg-success'; }
                            else if (data >= 8.5) { grade = 'A'; badgeClass = 'bg-success'; }
                            else if (data >= 8.0) { grade = 'B+'; badgeClass = 'bg-info'; }
                            else if (data >= 7.0) { grade = 'B'; badgeClass = 'bg-info'; }
                            else if (data >= 6.5) { grade = 'C+'; badgeClass = 'bg-warning'; }
                            else if (data >= 6.0) { grade = 'C'; badgeClass = 'bg-warning'; }
                            else if (data >= 5.5) { grade = 'D+'; badgeClass = 'bg-warning'; }
                            else if (data >= 5.0) { grade = 'D'; badgeClass = 'bg-warning'; }

                            return `<span class="badge ${badgeClass} score-badge">${grade}</span>`;
                        }
                    },
                    {
                        data: 'timestamp',
                        render: function(data) {
                            return new Date(data).toLocaleString('vi-VN');
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            if (!data.completed) {
                                return '<span class="text-muted">Chưa hoàn thành</span>';
                            }
                            return `<button class="btn btn-sm btn-info" onclick="viewExamDetail('${data.id}')">👁️ Xem</button>`;
                        },
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                order: [[4, 'desc']], // Sort by timestamp descending
                pageLength: 10
            });
        }

        // View exam detail
        async function viewExamDetail(examId) {
            try {
                const response = await fetch(`api/get_exam_result.php?exam_id=${examId}`);
                const data = await response.json();

                if (data.success) {
                    const result = data.result;
                    const modal = new bootstrap.Modal(document.getElementById('examDetailModal'));
                    const content = document.getElementById('examDetailContent');

                    content.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Loại thi:</strong> ${result.test_name || result.exam_type}
                            </div>
                            <div class="col-md-6">
                                <strong>Lần thi:</strong> ${result.attempt}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Điểm số:</strong> <span class="h4 text-primary">${result.score}/10</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Số câu đúng:</strong> ${result.correct_answers}/${result.total_questions}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Thời gian:</strong> ${new Date(result.timestamp).toLocaleString('vi-VN')}
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong> <span class="badge bg-success">Hoàn thành</span>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Chi Tiết Bài Làm</h5>
                        <div class="accordion" id="questionsAccordion">
                            ${result.question_results.map((q, index) => `
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ${q.is_correct ? '' : 'bg-danger text-white'}" type="button" data-bs-toggle="collapse" data-bs-target="#question${index}">
                                            Câu ${index + 1}: ${q.is_correct ? '✅ Đúng' : '❌ Sai'}
                                        </button>
                                    </h2>
                                    <div id="question${index}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Câu hỏi:</strong> ${q.question}</p>
                                            <p><strong>Đáp án đúng:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.correct_answer)
                                                    : q.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>
                                            ${q.user_answer !== null ? `<p><strong>Đáp án của bạn:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.user_answer)
                                                    : q.user_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>` : '<p><strong>Đáp án của bạn:</strong> <em>Chưa trả lời</em></p>'}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    modal.show();
                } else {
                    alert('Không thể tải chi tiết bài thi');
                }
            } catch (error) {
                console.error('Error loading exam detail:', error);
                alert('Lỗi tải chi tiết bài thi: ' + error.message);
            }
        }

        // Print exam detail
        function printExamDetail() {
            const content = document.getElementById('examDetailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chi Tiết Bài Thi</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .accordion-item { margin-bottom: 10px; border: 1px solid #ddd; }
                        .accordion-button { background: #f8f9fa; border: none; padding: 10px; width: 100%; text-align: left; }
                        .accordion-body { padding: 10px; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .bg-success { background: #28a745; color: white; }
                        .text-primary { color: #007bff; }
                        .h4 { font-size: 1.5rem; font-weight: bold; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Function to load attempts for a specific exam
        async function loadAttemptsForExam(examId, badgeId, testName) {
            try {
                const response = await fetch(`api/check_attempts.php?test_name=${encodeURIComponent(testName)}`);
                const data = await response.json();
                if (data.success) {
                    const attemptsText = data.can_take ? `${data.attempts}/1` : '1/1 (Đã hoàn thành)';
                    document.getElementById(badgeId).textContent = attemptsText;
                    document.getElementById(badgeId).className = data.can_take ? 'badge bg-warning ms-2' : 'badge bg-danger ms-2';
                }
            } catch (error) {
                console.error('Error loading attempts:', error);
                document.getElementById(badgeId).textContent = 'Lỗi';
            }
        }

        // Load attempts for all exams
        function loadAllAttempts() {
            <?php foreach ($approvedExams as $exam): ?>
                loadAttemptsForExam('<?php echo $exam['id']; ?>', 'attempts-<?php echo $exam['id']; ?>', '<?php echo addslashes($exam['test_name']); ?>');
            <?php endforeach; ?>
        }

        // Load results on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadResults();
            loadAllAttempts();
        });
    </script>
</body>
</html>
