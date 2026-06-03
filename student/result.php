<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';

$examId = $_GET['exam_id'] ?? '';
if (!$examId) {
    header('Location: dashboard.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'];

// Get premium status
$premiumStatus = getStudentPremiumStatus($studentCode);

// Check for exam limit message
$examLimitMsg = $_SESSION['exam_limit_msg'] ?? $_SESSION['premium_limit_msg'] ?? '';
unset($_SESSION['exam_limit_msg']);
unset($_SESSION['premium_limit_msg']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Bài Thi - CVD</title>
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
    <script id="MathJax-script" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>
    <style>
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto;
        }
        .score-excellent { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .score-good { background: linear-gradient(135deg, #007bff, #6610f2); color: white; }
        .score-average { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
        .score-poor { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        .question-review {
            border-left: 4px solid;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .correct { border-left-color: #28a745; }
        .incorrect { border-left-color: #dc3545; }
        .unanswered { border-left-color: #6c757d; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🏫 CVD - Học Sinh</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="results.php">📈 Kết Quả</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            👤 <?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentCode); ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">👤 Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">🚪 Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($examLimitMsg): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading">⚠️ Đã đạt giới hạn</h5>
                <p><?php echo htmlspecialchars($examLimitMsg); ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="card-title mb-4">Kết Quả Bài Thi</h2>

                        <!-- Score Display -->
                        <div id="scoreDisplay" class="mb-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Đang tải kết quả...</p>
                        </div>

                        <!-- Action Buttons -->
                        <div id="actionButtons" class="mt-4" style="display: none;">
                            <a href="dashboard.php" class="btn btn-primary me-2">🏠 Về Trang Chủ</a>
                            <a href="results.php" class="btn btn-info me-2">📊 Xem Chi Tiết</a>
                            <button class="btn btn-success" onclick="printResult()">🖨️ In Kết Quả</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Results -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card" id="detailedResults" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">Chi Tiết Bài Làm</h5>
                    </div>
                    <div class="card-body">
                        <div id="questionReview">
                            <!-- Question review will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let examResult = null;

        // Load exam result
        async function loadExamResult() {
            try {
                const examId = '<?php echo $examId; ?>';
                const response = await fetch(`api/get_exam_result.php?exam_id=${examId}`);
                const data = await response.json();

                if (data.success) {
                    examResult = data.result;
                    displayResult();
                } else {
                    document.getElementById('scoreDisplay').innerHTML = `
                        <div class="alert alert-danger">
                            <h4>Không tìm thấy kết quả bài thi</h4>
                            <p>Vui lòng liên hệ giáo viên nếu bạn nghĩ đây là lỗi.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading exam result:', error);
                document.getElementById('scoreDisplay').innerHTML = `
                    <div class="alert alert-danger">
                        <h4>Lỗi tải kết quả</h4>
                        <p>Vui lòng thử lại sau hoặc liên hệ giáo viên.</p>
                    </div>
                `;
            }
        }

        // Display result
        function displayResult() {
            const score = examResult.score;
            const correctAnswers = examResult.correct_answers;
            const totalQuestions = examResult.total_questions;
            const percentage = ((correctAnswers / totalQuestions) * 100).toFixed(1);

            // Determine score class
            let scoreClass = 'score-poor';
            if (score >= 8.0) scoreClass = 'score-excellent';
            else if (score >= 6.5) scoreClass = 'score-good';
            else if (score >= 5.0) scoreClass = 'score-average';

            // Determine grade
            let grade = 'F: Chưa đạt!';
            if (score >= 9.0) grade = 'A+: Xuất sắc!';
            else if (score >= 8.5) grade = 'A: Giỏi lắm!';
            else if (score >= 8.0) grade = 'B+: Giỏi!';
            else if (score >= 7.0) grade = 'B: Khá lắm!';
            else if (score >= 6.5) grade = 'C+: Khá đó!';
            else if (score >= 6.0) grade = 'C: Tốt rồi!';
            else if (score >= 5.5) grade = 'D+: Trung bình khá!';
            else if (score >= 5.0) grade = 'D: Trung bình!';

            const scoreDisplay = document.getElementById('scoreDisplay');
            scoreDisplay.innerHTML = `
                <div class="score-circle ${scoreClass} mb-3">
                    ${score}
                </div>
                <h4 class="mb-3">Xếp Loại: ${grade}</h4>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0">${correctAnswers}/${totalQuestions}</div>
                            <small class="text-muted">Câu đúng</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0">${percentage}%</div>
                            <small class="text-muted">Tỷ lệ đúng</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0">${examResult.test_name || examResult.exam_type.replace(/_/g, ' ')}</div>
                            <small class="text-muted">Tên bài thi</small>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-3">
                    Hoàn thành vào ${new Date(examResult.timestamp).toLocaleString('vi-VN')}
                </p>
            `;

            document.getElementById('actionButtons').style.display = 'block';
            document.getElementById('detailedResults').style.display = 'block';

            renderQuestionReview();
        }

        // Render question review
        function renderQuestionReview() {
            const reviewContainer = document.getElementById('questionReview');
            reviewContainer.innerHTML = '';

            const isPremium = <?php echo $premiumStatus['is_premium'] ? 'true' : 'false'; ?>;

            examResult.question_results.forEach((result, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = `question-review ${result.is_correct ? 'correct' : 'incorrect'}`;

                let userAnswerText = 'Chưa trả lời';
                if (result.user_answer !== null) {
                    if (result.type === 'single') {
                        userAnswerText = String.fromCharCode(65 + result.user_answer);
                    } else if (result.type === 'multiple') {
                        userAnswerText = result.user_answer.map(i => String.fromCharCode(65 + i)).join(', ');
                    }
                }

                let correctAnswerText = '';
                if (result.type === 'single') {
                    correctAnswerText = String.fromCharCode(65 + result.correct_answer);
                } else if (result.type === 'multiple') {
                    correctAnswerText = result.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ');
                }

                // Show detailed answers only for Premium users
                let detailedAnswerHtml = '';
                if (isPremium) {
                    detailedAnswerHtml = `
                        <p><strong>Đáp án đúng:</strong> ${correctAnswerText}</p>
                        ${result.explanation ? `<p class="text-muted"><strong>Giải thích:</strong> ${result.explanation}</p>` : ''}
                    `;
                } else if (!result.is_correct) {
                    detailedAnswerHtml = `
                        <div class="alert alert-warning small mt-2">
                            <i class="bi bi-lock me-1"></i>
                            <a href="premium.php" class="alert-link">Nâng cấp Premium</a> để xem đáp án đúng và lời giải chi tiết
                        </div>
                    `;
                }

                questionDiv.innerHTML = `
                    <h6>Câu ${index + 1}: ${result.is_correct ? '✅ Đúng' : '❌ Sai'}</h6>
                    <p><strong>Câu hỏi:</strong> ${result.question}</p>
                    <p><strong>Đáp án của bạn:</strong> ${userAnswerText}</p>
                    ${detailedAnswerHtml}
                `;

                reviewContainer.appendChild(questionDiv);
            });
        }

        // Print result
        function printResult() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Kết Quả Bài Thi - ${examResult.test_name || examResult.exam_type.replace(/_/g, ' ')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .score { font-size: 2rem; font-weight: bold; text-align: center; margin: 20px 0; }
                        .info { margin: 10px 0; }
                        .question { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; }
                        .correct { border-left-color: #28a745; }
                        .incorrect { border-left-color: #dc3545; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Kết Quả Bài Thi</h1>
                        <h2>${examResult.test_name || examResult.exam_type.replace(/_/g, ' ')} - Tin Học</h2>
                    </div>

                    <div class="info">
                        <p><strong>Học sinh:</strong> ${examResult.student_name}</p>
                        <p><strong>Mã học sinh:</strong> ${examResult.student_code}</p>
                        <p><strong>Lớp:</strong> ${examResult.class_code}</p>
                        <p><strong>Điểm số:</strong> ${examResult.score}/10</p>
                        <p><strong>Số câu đúng:</strong> ${examResult.correct_answers}/${examResult.total_questions}</p>
                        <p><strong>Lần thi:</strong> ${examResult.attempt}</p>
                        <p><strong>Thời gian:</strong> ${new Date(examResult.timestamp).toLocaleString('vi-VN')}</p>
                    </div>

                    <h3>Chi Tiết Bài Làm</h3>
                    ${examResult.question_results.map((result, index) => `
                        <div class="question ${result.is_correct ? 'correct' : 'incorrect'}">
                            <h4>Câu ${index + 1}: ${result.is_correct ? 'Đúng' : 'Sai'}</h4>
                            <p><strong>Câu hỏi:</strong> ${result.question}</p>
                            <p><strong>Đáp án đúng:</strong> ${
                                result.type === 'single'
                                    ? String.fromCharCode(65 + result.correct_answer)
                                    : result.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                            }</p>
                        </div>
                    `).join('')}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Load result on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadExamResult();

            // Render math formulas on page load
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
