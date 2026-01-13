<?php
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Determine grade from class code
$prefix = substr($studentClassCode, 0, 1);
$grade = 'khoi' . $prefix;

// Load system config to get current semester
$configFile = __DIR__ . '/../admin/system_config.json';
$config = json_decode(file_get_contents($configFile), true);
$currentSemester = $config['semester']['current'] ?? 'hk1';

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

// Load all available topics and lessons for the grade by subject
$subjectTopicsLessons = [];

$questionsDir = __DIR__ . '/../teacher/questions/' . $grade . '/' . $currentSemester;
if (is_dir($questionsDir)) {
    $subjectFiles = glob($questionsDir . '/subject_*.json');
    foreach ($subjectFiles as $file) {
        $subjectId = str_replace(['subject_', '.json'], '', basename($file));
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $subjectTopicsLessons[$subjectId] = [
                'topics' => [],
                'lessons' => [],
                'topicLessons' => []
            ];
            foreach ($data as $topicData) {
                $topic = $topicData['topic'] ?? '';
                $lesson = $topicData['lesson'] ?? '';
                if ($topic && !in_array($topic, $subjectTopicsLessons[$subjectId]['topics'])) {
                    $subjectTopicsLessons[$subjectId]['topics'][] = $topic;
                }
                if ($lesson && !in_array($lesson, $subjectTopicsLessons[$subjectId]['lessons'])) {
                    $subjectTopicsLessons[$subjectId]['lessons'][] = $lesson;
                }
                if ($topic && $lesson) {
                    if (!isset($subjectTopicsLessons[$subjectId]['topicLessons'][$topic])) {
                        $subjectTopicsLessons[$subjectId]['topicLessons'][$topic] = [];
                    }
                    if (!in_array($lesson, $subjectTopicsLessons[$subjectId]['topicLessons'][$topic])) {
                        $subjectTopicsLessons[$subjectId]['topicLessons'][$topic][] = $lesson;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luyện Tập - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/main.css">

    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
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
        .practice-card {
            transition: transform 0.2s;
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
        }
        .practice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .question-card {
            margin-bottom: 2rem;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
        }
        .question-text {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        .option-box {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 1.05rem;
        }
        .option-box:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f7f9fc 0%, #eef2ff 100%);
            transform: translateX(5px);
        }
        .option-box.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            font-weight: 500;
        }
        .option-box input[type="radio"],
        .option-box input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
        }
        .option-letter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #e2e8f0;
            border-radius: 50%;
            font-weight: 600;
            margin-right: 12px;
            transition: all 0.3s;
        }
        .option-box.selected .option-letter {
            background: white;
            color: #667eea;
        }
        .correct {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
            border-color: #11998e !important;
            color: white !important;
        }
        .incorrect {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
            border-color: #eb3349 !important;
            color: white !important;
        }
        .selection-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .selection-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        .selection-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
        .stats-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 1rem;
        }
        .option-label {
            cursor: pointer;
            transition: all 0.3s;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .option-label:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-check-input:checked + .option-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .correct {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
            border-color: #11998e !important;
            color: white !important;
        }
        .incorrect {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
            border-color: #eb3349 !important;
            color: white !important;
        }
        .selection-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .selection-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        .selection-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
        .stats-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 1rem;
        }
        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 32px;
            font-size: 1.1rem;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-gradient-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            color: white;
        }
        .btn-gradient-success:hover {
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
            color: white;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .progress-bar-container {
            background: #e2e8f0;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        .question-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .question-nav-btn {
            width: 50px;
            height: 50px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: #4a5568;
        }
        .question-nav-btn:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .question-nav-btn.answered {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        .question-nav-btn.active {
            border-color: #f59e0b;
            border-width: 3px;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }
        .question-info-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .badge-topic {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .badge-lesson {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <!-- Selection Phase -->
        <div id="selectionPhase">
            <div class="page-header text-center">
                <h3><i class="bi bi-book-half me-2"></i>Chọn Nội Dung Luyện Tập</h3>
                <p class="mb-0">Chọn môn học, chủ đề và bài học để bắt đầu luyện tập</p>
            </div>

            <div class="row mb-3 justify-content-center">
                <div class="col-12">
                    <div class="card selection-card">
                        <div class="card-header">
                            <i class="bi bi-book me-2"></i>Chọn Môn Học
                        </div>
                        <div class="card-body">
                            <select class="form-select form-select-lg" id="subjectSelect">
                                <option value="">Chọn môn học</option>
                                <?php foreach ($subjects as $id => $name): ?>
                                    <option value="subject_<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card selection-card">
                        <div class="card-header">
                            <i class="bi bi-bookmark me-2"></i>Chọn Chủ Đề
                        </div>
                        <div class="card-body">
                            <select class="form-select form-select-lg" id="topicSelect" disabled>
                                <option value="">Tất cả chủ đề</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card selection-card">
                        <div class="card-header">
                            <i class="bi bi-journal-text me-2"></i>Chọn Bài Học
                        </div>
                        <div class="card-body">
                            <select class="form-select form-select-lg" id="lessonSelect" disabled>
                                <option value="">Tất cả bài học</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-gradient-primary" onclick="startPractice()">
                        <i class="bi bi-play-circle me-2"></i>Bắt Đầu Luyện Tập
                    </button>
                </div>
            </div>
        </div>

        <!-- Practice Phase -->
        <div id="practicePhase" style="display: none;">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Bài Luyện Tập</h4>
                    </div>
                    <div>
                        <span class="stats-badge"><i class="bi bi-list-ol me-2"></i>Câu <span id="currentQuestion">1</span>/<span id="totalQuestions">10</span></span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="card selection-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>Tiến độ</small>
                        <small class="text-muted" id="progressText">0%</small>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Question Navigation Grid -->
            <div class="card selection-card mb-4">
                <div class="card-header">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Danh Sách Câu Hỏi
                </div>
                <div class="card-body">
                    <div class="question-nav-grid" id="questionNavGrid">
                        <!-- Question nav buttons will be generated here -->
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <span class="me-3"><span class="question-nav-btn answered d-inline-block" style="width: 20px; height: 20px; vertical-align: middle;"></span> Đã trầ lời</span>
                            <span class="me-3"><span class="question-nav-btn d-inline-block" style="width: 20px; height: 20px; vertical-align: middle;"></span> Chưa trả lời</span>
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center mb-4">
                <button class="btn btn-gradient-success btn-lg me-2" onclick="submitPractice()">
                    <i class="bi bi-check-circle me-2"></i>Nộp Bài
                </button>
                <button class="btn btn-secondary btn-lg" onclick="backToSelection()">
                    <i class="bi bi-arrow-left-circle me-2"></i>Chọn Lại
                </button>
            </div>

            <div class="row">
                <div class="col-12">
                    <div id="questionsContainer">
                        <!-- Questions will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="mt-4 mb-5">
                <div class="text-center">
                    <button class="btn btn-outline-primary btn-lg me-2" id="prevBtn" onclick="previousQuestion()" disabled>
                        <i class="bi bi-arrow-left me-2"></i>Câu Trước
                    </button>
                    <button class="btn btn-outline-primary btn-lg" id="nextBtn" onclick="nextQuestion()">
                        Câu Tiếp<i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Phase -->
        <div id="resultsPhase" style="display: none;">
            <div class="page-header text-center">
                <h3><i class="bi bi-graph-up me-2"></i>Kết Quả Luyện Tập</h3>
                <p class="mb-0">Xem kết quả chi tiết của bài luyện tập</p>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center selection-card">
                        <div class="card-body">
                            <div class="display-4 text-success mb-2"><i class="bi bi-check-circle"></i></div>
                            <h3 class="card-title text-success" id="correctCount">0</h3>
                            <p class="card-text text-muted">Câu Đúng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center selection-card">
                        <div class="card-body">
                            <div class="display-4 text-danger mb-2"><i class="bi bi-x-circle"></i></div>
                            <h3 class="card-title text-danger" id="incorrectCount">0</h3>
                            <p class="card-text text-muted">Câu Sai</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center selection-card">
                        <div class="card-body">
                            <div class="display-4 text-primary mb-2"><i class="bi bi-star"></i></div>
                            <h3 class="card-title text-primary" id="scorePercentage">0%</h3>
                            <p class="card-text text-muted">Điểm Số</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card selection-card">
                        <div class="card-header">
                            <i class="bi bi-list-check me-2"></i>Chi Tiết Câu Trả Lời
                        </div>
                        <div class="card-body">
                            <div id="resultsContainer">
                                <!-- Results will be shown here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row my-4">
                <div class="col-12 text-center">
                    <button class="btn btn-gradient-primary btn-lg" onclick="backToSelection()">
                        <i class="bi bi-arrow-repeat me-2"></i>Luyện Tập Lại
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentQuestions = [];
        let currentAnswers = {};
        let currentQuestionIndex = 0;

        // Subject-Topic-Lesson mapping from PHP
        const subjectTopicsLessons = <?php echo json_encode($subjectTopicsLessons); ?>;

        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subjectSelect');
            const topicSelect = document.getElementById('topicSelect');
            const lessonSelect = document.getElementById('lessonSelect');

            subjectSelect.addEventListener('change', function() {
                const selectedSubject = this.value;
                updateTopicSelect(selectedSubject, topicSelect);
                updateLessonSelect('', lessonSelect);
                topicSelect.disabled = !selectedSubject;
                lessonSelect.disabled = !selectedSubject;
            });

            topicSelect.addEventListener('change', function() {
                const selectedSubject = subjectSelect.value;
                const selectedTopic = this.value;
                updateLessonSelect(selectedTopic, lessonSelect, selectedSubject);
            });
        });

        function updateTopicSelect(selectedSubject, topicSelect) {
            topicSelect.innerHTML = '<option value="">Tất cả chủ đề</option>';

            if (selectedSubject && subjectTopicsLessons[selectedSubject.replace('subject_', '')]) {
                const topics = subjectTopicsLessons[selectedSubject.replace('subject_', '')]['topics'];
                topics.forEach(topic => {
                    const option = document.createElement('option');
                    option.value = topic;
                    option.textContent = topic;
                    topicSelect.appendChild(option);
                });
            }

            topicSelect.value = '';
        }

        function updateLessonSelect(selectedTopic, lessonSelect, selectedSubject) {
            lessonSelect.innerHTML = '<option value="">Tất cả bài học</option>';

            let lessonsToShow = [];
            if (selectedSubject && subjectTopicsLessons[selectedSubject.replace('subject_', '')]) {
                const subjectData = subjectTopicsLessons[selectedSubject.replace('subject_', '')];
                if (selectedTopic && subjectData['topicLessons'][selectedTopic]) {
                    lessonsToShow = subjectData['topicLessons'][selectedTopic];
                } else {
                    lessonsToShow = subjectData['lessons'];
                }
            }

            lessonsToShow.forEach(lesson => {
                const option = document.createElement('option');
                option.value = lesson;
                option.textContent = lesson;
                lessonSelect.appendChild(option);
            });

            // Clear the selected lesson when topic changes
            lessonSelect.value = '';
        }

        function startPractice() {
            const subject = document.getElementById('subjectSelect').value;
            const topic = document.getElementById('topicSelect').value;
            const lesson = document.getElementById('lessonSelect').value;

            if (!subject) {
                alert('Vui lòng chọn môn học trước khi bắt đầu luyện tập.');
                return;
            }

            // Fetch questions
            fetch(`../api/get_questions.php?grade=<?php echo $grade; ?>&subject=${encodeURIComponent(subject)}&topic=${encodeURIComponent(topic)}&lesson=${encodeURIComponent(lesson)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentQuestions = data.questions;
                        currentAnswers = {};
                        currentQuestionIndex = 0;

                        document.getElementById('selectionPhase').style.display = 'none';
                        document.getElementById('practicePhase').style.display = 'block';
                        document.getElementById('resultsPhase').style.display = 'none';

                        document.getElementById('totalQuestions').textContent = currentQuestions.length;
                        renderQuestions();
                        updateNavigation();
                        updateQuestionNavGrid();
                        updateProgress();
                    } else {
                        alert('Không thể tải câu hỏi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                });
        }

        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';

            if (currentQuestions.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Không có câu hỏi nào phù hợp với lựa chọn của bạn.</div>';
                return;
            }

            const question = currentQuestions[currentQuestionIndex];
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-card card';

            const isMultiple = question.type === 'multiple';
            const inputType = isMultiple ? 'checkbox' : 'radio';
            const userAnswers = currentAnswers[currentQuestionIndex] || (isMultiple ? [] : null);

            let optionsHtml = '';
            question.options.forEach((option, index) => {
                let checked = '';
                if (isMultiple) {
                    checked = userAnswers.includes(index) ? 'checked' : '';
                } else {
                    checked = userAnswers === index ? 'checked' : '';
                }
                const selectedClass = checked ? 'selected' : '';
                const letter = String.fromCharCode(65 + index);
                
                optionsHtml += `
                    <div class="option-box ${selectedClass}" data-index="${index}" data-type="${inputType}">
                        <input type="${inputType}" 
                               name="q${currentQuestionIndex}" 
                               value="${index}"
                               id="q${currentQuestionIndex}o${index}" 
                               ${checked}
                               style="display: none;">
                        <span class="option-letter">${letter}</span>
                        <span class="option-text">${option}</span>
                    </div>
                `;
            });

            const questionTypeIcon = isMultiple ? 'bi-check2-square' : 'bi-check-circle';
            const questionTypeText = isMultiple ? 'Nhiều lựa chọn' : 'Một lựa chọn';
            const questionTypeBadge = isMultiple ? 'bg-warning' : 'bg-info';

            questionDiv.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-question-circle text-primary me-2"></i>Câu ${currentQuestionIndex + 1}
                        </h5>
                        <span class="badge ${questionTypeBadge}">
                            <i class="bi ${questionTypeIcon} me-1"></i>${questionTypeText}
                        </span>
                    </div>
                    <div class="question-text">${question.question}</div>
                    <div class="options">
                        ${optionsHtml}
                    </div>
                    <div class="mt-4">
                        <span class="question-info-badge badge-topic">
                            <i class="bi bi-bookmark me-1"></i>${question.topic}
                        </span>
                        <span class="question-info-badge badge-lesson">
                            <i class="bi bi-journal-text me-1"></i>${question.lesson}
                        </span>
                    </div>
                </div>
            `;

            container.appendChild(questionDiv);

            // Add click event listeners to option boxes
            const optionBoxes = questionDiv.querySelectorAll('.option-box');
            optionBoxes.forEach((box) => {
                box.addEventListener('click', function() {
                    const optionIndex = parseInt(this.getAttribute('data-index'));
                    const inputType = this.getAttribute('data-type');
                    selectOption(currentQuestionIndex, optionIndex, inputType);
                });
            });

            // Update navigation grid
            updateQuestionNavGrid();
            updateProgress();

            // Render MathJax
            if (typeof MathJax !== 'undefined') {
                MathJax.typeset();
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function selectOption(questionIndex, optionIndex, inputType) {
            const question = currentQuestions[questionIndex];
            
            if (inputType === 'radio') {
                // Single choice - remove all selections first
                const allBoxes = document.querySelectorAll('.option-box');
                allBoxes.forEach(box => box.classList.remove('selected'));
                
                currentAnswers[questionIndex] = optionIndex;
                
                // Add selected class to clicked option
                const clickedBox = document.querySelector(`.option-box[data-index="${optionIndex}"]`);
                if (clickedBox) clickedBox.classList.add('selected');
                
                // Update radio input
                const radioInput = document.getElementById(`q${questionIndex}o${optionIndex}`);
                if (radioInput) radioInput.checked = true;
            } else {
                // Multiple choice - toggle selection
                const clickedBox = document.querySelector(`.option-box[data-index="${optionIndex}"]`);
                const checkbox = document.getElementById(`q${questionIndex}o${optionIndex}`);
                
                if (!currentAnswers[questionIndex]) {
                    currentAnswers[questionIndex] = [];
                }
                
                if (currentAnswers[questionIndex].includes(optionIndex)) {
                    // Remove from selection
                    currentAnswers[questionIndex] = currentAnswers[questionIndex].filter(i => i !== optionIndex);
                    if (clickedBox) clickedBox.classList.remove('selected');
                    if (checkbox) checkbox.checked = false;
                } else {
                    // Add to selection
                    currentAnswers[questionIndex].push(optionIndex);
                    if (clickedBox) clickedBox.classList.add('selected');
                    if (checkbox) checkbox.checked = true;
                }
            }
            
            updateQuestionNavGrid();
            updateProgress();
        }

        function nextQuestion() {
            if (currentQuestionIndex < currentQuestions.length - 1) {
                currentQuestionIndex++;
                renderQuestions();
                updateNavigation();
            }
        }

        function previousQuestion() {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                renderQuestions();
                updateNavigation();
            }
        }

        function updateNavigation() {
            document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
            document.getElementById('prevBtn').disabled = currentQuestionIndex === 0;
            document.getElementById('nextBtn').disabled = currentQuestionIndex === currentQuestions.length - 1;
        }

        function updateQuestionNavGrid() {
            const grid = document.getElementById('questionNavGrid');
            if (!grid) return;

            grid.innerHTML = '';
            currentQuestions.forEach((q, index) => {
                const btn = document.createElement('button');
                btn.className = 'question-nav-btn';
                btn.textContent = index + 1;
                btn.onclick = () => goToQuestion(index);
                
                // Check if answered
                if (currentAnswers[index] !== undefined && currentAnswers[index] !== null) {
                    if (Array.isArray(currentAnswers[index]) && currentAnswers[index].length > 0) {
                        btn.classList.add('answered');
                    } else if (!Array.isArray(currentAnswers[index])) {
                        btn.classList.add('answered');
                    }
                }
                
                // Mark current question
                if (index === currentQuestionIndex) {
                    btn.classList.add('active');
                }
                
                grid.appendChild(btn);
            });
        }

        function updateProgress() {
            const answered = Object.keys(currentAnswers).filter(key => {
                const ans = currentAnswers[key];
                if (Array.isArray(ans)) {
                    return ans.length > 0;
                }
                return ans !== null && ans !== undefined;
            }).length;
            
            const total = currentQuestions.length;
            const percentage = total > 0 ? Math.round((answered / total) * 100) : 0;
            
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (progressBar) progressBar.style.width = percentage + '%';
            if (progressText) progressText.textContent = percentage + '%';
        }

        function goToQuestion(index) {
            currentQuestionIndex = index;
            renderQuestions();
            updateNavigation();
        }

        function submitPractice() {
            let correct = 0;
            let incorrect = 0;

            const resultsHtml = currentQuestions.map((question, index) => {
                const userAnswer = currentAnswers[index];
                let isCorrect = false;
                if (question.type === 'multiple') {
                    isCorrect = Array.isArray(userAnswer) && Array.isArray(question.correct) &&
                                userAnswer.length === question.correct.length &&
                                userAnswer.every(val => question.correct.includes(val));
                } else {
                    isCorrect = userAnswer === question.correct;
                }
                const answerClass = isCorrect ? 'correct' : 'incorrect';

                if (isCorrect) correct++;
                else incorrect++;

                let userAnswerText = '';
                if (question.type === 'multiple') {
                    userAnswerText = Array.isArray(userAnswer) && userAnswer.length > 0 ?
                        userAnswer.map(i => question.options[i]).join(', ') : 'Chưa trả lời';
                } else {
                    userAnswerText = userAnswer !== null ? question.options[userAnswer] : 'Chưa trả lời';
                }

                let correctAnswerText = '';
                if (question.type === 'multiple') {
                    correctAnswerText = Array.isArray(question.correct) ?
                        question.correct.map(i => question.options[i]).join(', ') : question.options[question.correct];
                } else {
                    correctAnswerText = question.options[question.correct];
                }

                const questionTypeText = question.type === 'multiple' ? '<span class="badge bg-warning">Nhiều lựa chọn</span>' : '<span class="badge bg-info">Một lựa chọn</span>';

                return `
                    <div class="card mb-3 ${answerClass}">
                        <div class="card-body">
                            <h6 class="card-title">Câu ${index + 1}: ${questionTypeText}</h6>
                            <p>${question.question}</p>
                            <p><strong>Đáp án của bạn:</strong> ${userAnswerText}</p>
                            <p><strong>Đáp án đúng:</strong> ${correctAnswerText}</p>
                            <p><strong>Kết quả:</strong> ${isCorrect ? '✅ Đúng' : '❌ Sai'}</p>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('correctCount').textContent = correct;
            document.getElementById('incorrectCount').textContent = incorrect;
            const percentage = Math.round((correct / currentQuestions.length) * 100);
            document.getElementById('scorePercentage').textContent = percentage + '%';

            document.getElementById('resultsContainer').innerHTML = resultsHtml;

            document.getElementById('practicePhase').style.display = 'none';
            document.getElementById('resultsPhase').style.display = 'block';

            // Save practice results
            savePracticeResults(correct, incorrect, percentage);

            // Render MathJax for results
            if (typeof MathJax !== 'undefined') {
                MathJax.typeset();
            }
        }

        function savePracticeResults(correct, incorrect, percentage) {
            const subject = document.getElementById('subjectSelect').value;
            const topic = document.getElementById('topicSelect').value;
            const lesson = document.getElementById('lessonSelect').value;

            const practiceData = {
                student_code: '<?php echo $studentCode; ?>',
                student_name: '<?php echo addslashes($studentName); ?>',
                class_code: '<?php echo $studentClassCode; ?>',
                subject: subject,
                topic: topic,
                lesson: lesson,
                total_questions: currentQuestions.length,
                correct_answers: correct,
                incorrect_answers: incorrect,
                score_percentage: percentage,
                timestamp: new Date().toISOString(),
                question_results: currentQuestions.map((question, index) => {
                    const userAnswer = currentAnswers[index];
                    let isCorrect = false;
                    if (question.type === 'multiple') {
                        isCorrect = Array.isArray(userAnswer) && Array.isArray(question.correct) &&
                                    userAnswer.length === question.correct.length &&
                                    userAnswer.every(val => question.correct.includes(val));
                    } else {
                        isCorrect = userAnswer === question.correct;
                    }

                    return {
                        question_index: index,
                        question: question.question,
                        user_answer: userAnswer,
                        correct_answer: question.correct,
                        is_correct: isCorrect,
                        type: question.type,
                        topic: question.topic,
                        lesson: question.lesson
                    };
                })
            };

            fetch('../api/save_practice.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(practiceData)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to save practice results:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving practice results:', error);
            });
        }

        function backToSelection() {
            document.getElementById('selectionPhase').style.display = 'block';
            document.getElementById('practicePhase').style.display = 'none';
            document.getElementById('resultsPhase').style.display = 'none';
            currentQuestions = [];
            currentAnswers = {};
            currentQuestionIndex = 0;
        }
    </script>
</body>
</html>
