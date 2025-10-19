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

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

// Load all available topics and lessons for the grade by subject
$subjectTopicsLessons = [];

$questionsDir = __DIR__ . '/../teacher/questions/' . $grade;
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
        }
        .practice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
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
        .correct {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .incorrect {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <!-- Selection Phase -->
        <div id="selectionPhase">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">📚 Chọn Môn Học, Chủ Đề và Bài Học để Luyện Tập</h4>
                    
                </div>
            </div>

            <div class="row mb-3 justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Chọn Môn Học</h6>
                        </div>
                        <div class="card-body">
                            <select class="form-select" id="subjectSelect">
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
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Chọn Chủ Đề</h6>
                        </div>
                        <div class="card-body">
                            <select class="form-select" id="topicSelect" disabled>
                                <option value="">Tất cả chủ đề</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Chọn Bài Học</h6>
                        </div>
                        <div class="card-body">
                            <select class="form-select" id="lessonSelect" disabled>
                                <option value="">Tất cả bài học</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-lg" onclick="startPractice()">
                        🚀 Bắt Đầu Luyện Tập
                    </button>
                </div>
            </div>
        </div>

        <!-- Practice Phase -->
        <div id="practicePhase" style="display: none;">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">📝 Bài Luyện Tập</h4>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info">Câu: <span id="currentQuestion">1</span>/<span id="totalQuestions">10</span></span>
                        </div>
                         <div class="text-center">
                    <button class="btn btn-success btn-lg" onclick="submitPractice()">
                        ✅ Nộp Bài
                    </button>
                    <button class="btn btn-secondary btn-lg ms-2" onclick="backToSelection()">
                        🔄 Chọn Lại
                    </button>
                </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div id="questionsContainer">
                        <!-- Questions will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="text-center">
                            <button class="btn btn-outline-primary me-2" id="prevBtn" onclick="previousQuestion()" disabled>
                                ← Câu Trước
                            </button>
                            <button class="btn btn-outline-primary" id="nextBtn" onclick="nextQuestion()">
                                Câu Tiếp →
                            </button>
                        </div>
               
            </div>
        </div>

        <!-- Results Phase -->
        <div id="resultsPhase" style="display: none;">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">📊 Kết Quả Luyện Tập</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success" id="correctCount">0</h5>
                            <p class="card-text">Đúng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger" id="incorrectCount">0</h5>
                            <p class="card-text">Sai</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary" id="scorePercentage">0%</h5>
                            <p class="card-text">Điểm</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Chi Tiết Câu Trả Lời</h6>
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
                    <button class="btn btn-primary btn-lg" onclick="backToSelection()">
                        🔄 Luyện Tập Lại
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

            let optionsHtml = '';
            const isMultiple = question.type === 'multiple';
            const inputType = isMultiple ? 'checkbox' : 'radio';
            const userAnswers = currentAnswers[currentQuestionIndex] || (isMultiple ? [] : null);

            question.options.forEach((option, index) => {
                let checked = '';
                if (isMultiple) {
                    checked = userAnswers.includes(index) ? 'checked' : '';
                } else {
                    checked = userAnswers === index ? 'checked' : '';
                }
                optionsHtml += `
                    <div class="form-check">
                        <input class="form-check-input" type="${inputType}" name="q${currentQuestionIndex}" value="${index}"
                               id="q${currentQuestionIndex}o${index}" ${checked}>
                        <label class="form-check-label option-label w-100" for="q${currentQuestionIndex}o${index}">
                            ${String.fromCharCode(65 + index)}. ${option}
                        </label>
                    </div>
                `;
            });

            const questionTypeText = isMultiple ? '<span class="badge bg-warning">Nhiều lựa chọn</span>' : '<span class="badge bg-info">Một lựa chọn</span>';

            questionDiv.innerHTML = `
                <div class="card-body">
                    <h5 class="card-title">Câu ${currentQuestionIndex + 1}: ${questionTypeText}</h5>
                    <p class="card-text">${question.question}</p>
                    <div class="options">
                        ${optionsHtml}
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Chủ đề:</strong> ${question.topic}<br>
                            <strong>Bài học:</strong> ${question.lesson}
                        </small>
                    </div>
                </div>
            `;

            // Add event listeners
            const inputs = questionDiv.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('change', () => saveAnswer(currentQuestionIndex));
            });

            container.appendChild(questionDiv);

            // Render MathJax
            if (typeof MathJax !== 'undefined') {
                MathJax.typeset();
            }
        }

        function saveAnswer(questionIndex) {
            const question = currentQuestions[questionIndex];
            const inputs = document.querySelectorAll(`input[name="q${questionIndex}"]:checked`);
            if (question.type === 'multiple') {
                const selectedIndices = Array.from(inputs).map(input => parseInt(input.value));
                currentAnswers[questionIndex] = selectedIndices;
            } else {
                currentAnswers[questionIndex] = inputs.length > 0 ? parseInt(inputs[0].value) : null;
            }
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

            // Render MathJax for results
            if (typeof MathJax !== 'undefined') {
                MathJax.typeset();
            }
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
