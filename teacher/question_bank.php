<?php
include '../includes/session_check.php';
include '../includes/common_functions.php';

$username = $_SESSION['username'];
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$teacherSubjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$teacherClasses = json_decode(file_get_contents($teacherClassesFile), true) ?: [];
$classes = json_decode(file_get_contents($classesFile), true) ?: [];

$assignedSubjectIds = $teacherSubjects[$username] ?? [];
$assignedClassIds = $teacherClasses[$username] ?? [];

// Map grades to class prefixes
$gradeToPrefix = [
    'khoi6' => '6',
    'khoi7' => '7',
    'khoi8' => '8',
    'khoi9' => '9',
];

// Get assigned grades for the teacher
$assignedGrades = [];
foreach ($assignedClassIds as $classId) {
    foreach ($classes as $class) {
        if ($class['id'] == $classId) {
            $prefix = substr($class['code'], 0, 1);
            $grade = array_search($prefix, $gradeToPrefix);
            if ($grade && !in_array($grade, $assignedGrades)) {
                $assignedGrades[] = $grade;
            }
            break;
        }
    }
}

$assignedSubjects = array_filter($subjects, function($subj) use ($assignedSubjectIds) {
    return in_array($subj['id'], $assignedSubjectIds);
});

$grades = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
$gradeLabels = [
    'khoi6' => 'Khối 6',
    'khoi7' => 'Khối 7',
    'khoi8' => 'Khối 8',
    'khoi9' => 'Khối 9',
];

$selectedGrade = $_GET['grade'] ?? '';
$selectedSubjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($assignedSubjectIds ? $assignedSubjectIds[0] : 0);

if ($selectedSubjectId && !in_array($selectedSubjectId, $assignedSubjectIds)) {
    die('Môn học không hợp lệ hoặc không được phép.');
}

if ($selectedGrade && !in_array($selectedGrade, $grades)) {
    die('Khối không hợp lệ.');
}



// Filter grades to only show assigned ones
$availableGrades = array_intersect($grades, $assignedGrades);

$questions = [];
$questionsData = [];
if ($selectedGrade && $selectedSubjectId) {
    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
    if (file_exists($questionsFile)) {
        $questionsData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (is_array($questionsData)) {
            foreach ($questionsData as $topicIndex => $topicData) {
                $topic = $topicData['topic'] ?? '';
                $lesson = $topicData['lesson'] ?? '';
                $lessonQuestions = $topicData['questions'] ?? [];
                foreach ($lessonQuestions as $idx => $q) {
                    $questions[] = [
                        'data' => $q,
                        'topic' => $topic,
                        'lesson' => $lesson,
                        'topicIndex' => $topicIndex,
                        'index' => $idx
                    ];
                }
            }
        }
    }
}

$importMessage = '';
$importError = '';

include 'question_bank_handlers.php';



$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

$title = 'Quản Lý Câu Hỏi - CVD';
include '../includes/teacher_header.php';
?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản Lý Câu Hỏi</h2>
            <?php if ($selectedGrade && $selectedSubjectId): ?>
                <div class="d-flex">
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#addQuestionForm" aria-expanded="false" aria-controls="addQuestionForm">
                        ➕ Thêm Câu Hỏi
                    </button>
                    <button class="btn btn-success me-2" onclick="window.location.href='?grade=<?php echo $selectedGrade; ?>&subject_id=<?php echo $selectedSubjectId; ?>&action=export'">
                        📥 Xuất Câu Hỏi
                    </button>
                    <button class="btn btn-danger" id="deleteAllBtn" type="button">
                        🗑️ Xóa Tất Cả
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="grade" class="form-label">Chọn Khối</label>
                <select id="grade" name="grade" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Chọn khối --</option>
                    <?php foreach ($availableGrades as $g): ?>
                        <option value="<?php echo $g; ?>" <?php if ($g === $selectedGrade) echo 'selected'; ?>>
                            <?php echo $gradeLabels[$g] ?? ucfirst($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="subject_id" class="form-label">Chọn Môn Học</label>
                <select id="subject_id" name="subject_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Chọn môn học --</option>
                    <?php foreach ($assignedSubjects as $subj): ?>
                        <option value="<?php echo $subj['id']; ?>" <?php if ($subj['id'] == $selectedSubjectId) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($subj['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($selectedGrade && $selectedSubjectId): ?>
            <?php include 'question_bank_form.php'; ?>
        <?php endif; ?>

        <?php if ($selectedGrade && $selectedSubjectId): ?>
            <div class="accordion" id="topicsAccordion">
                <?php
                $topicCounter = 0;
                $globalIndex = 0;
                foreach ($questionsData as $topicIndex => $topicData):
                    $topic = $topicData['topic'] ?? 'Chủ đề không xác định';
                    $lessons = $topicData['questions'] ?? [];
                    $totalQuestionsInTopic = count($lessons);
                    $topicCounter++;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $topicCounter; ?>">
                            <button class="accordion-button <?php echo $topicCounter > 1 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $topicCounter; ?>" aria-expanded="<?php echo $topicCounter === 1 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $topicCounter; ?>">
                                📚 <?php echo htmlspecialchars($topic); ?> (<?php echo $totalQuestionsInTopic; ?> câu hỏi)
                            </button>
                        </h2>
                        <div id="collapse<?php echo $topicCounter; ?>" class="accordion-collapse collapse <?php echo $topicCounter === 1 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $topicCounter; ?>" data-bs-parent="#topicsAccordion">
                            <div class="accordion-body">
                                <?php
                                $lessonGroups = [];
                                foreach ($lessons as $lessonIndex => $q) {
                                    $lesson = $topicData['lesson'] ?? 'Bài học không xác định';
                                    if (!isset($lessonGroups[$lesson])) {
                                        $lessonGroups[$lesson] = [];
                                    }
                                    $lessonGroups[$lesson][] = ['data' => $q, 'index' => $lessonIndex, 'globalIndex' => $globalIndex++];
                                }
                                foreach ($lessonGroups as $lesson => $lessonQuestions):
                                ?>
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">📖 <?php echo htmlspecialchars($lesson); ?> (<?php echo count($lessonQuestions); ?> câu hỏi)</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Câu hỏi</th>
                                                        <th>Đáp án</th>
                                                        <th>Loại</th>
                                                        <th>Mức độ</th>
                                                        <th>Hành động</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lessonQuestions as $item): ?>
                                                        <?php $q = $item['data']; $flatIndex = $item['globalIndex']; ?>
                                                        <tr onclick="if (!event.target.closest('.delete-question')) { const modal = new bootstrap.Modal(document.getElementById('questionModal<?php echo $flatIndex; ?>')); modal.show(); }" style="cursor:pointer;">
                                                            <td><?php echo $flatIndex + 1; ?></td>
                                                            <td><?php echo strip_tags($q['question'], '<img>'); ?></td>
                                                            <td><?php echo renderCorrect($q['correct'], $q['options']); ?></td>
                                                            <td><?php echo $q['type'] === 'single' ? 'Trắc nghiệm' : 'Trắc nghiệm nhiều đáp án'; ?></td>
                                                            <td>
                                                                <?php
                                                                $levelLabels = ['NB' => 'Nhận biết', 'TH' => 'Thông hiểu', 'VD' => 'Vận dụng', 'VDC' => 'Vận dụng cao'];
                                                                echo $levelLabels[$q['level']] ?? htmlspecialchars($q['level']);
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-danger btn-sm delete-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" title="Xóa câu hỏi">
                                                                    🗑️ Xóa
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        <!-- Modal -->
                                                        <div class="modal fade" id="questionModal<?php echo $flatIndex; ?>" tabindex="-1" aria-labelledby="questionModalLabel<?php echo $flatIndex; ?>" aria-hidden="true">
                                                          <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                              <div class="modal-header">
                                                                <h5 class="modal-title" id="questionModalLabel<?php echo $flatIndex; ?>">Chi tiết câu hỏi #<?php echo $flatIndex + 1; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                              </div>
                                                              <div class="modal-body">
                                                                <p><strong>Câu hỏi:</strong> <?php echo strip_tags($q['question'], '<img>'); ?></p>
                                                                <p><strong>Loại câu hỏi:</strong> <?php echo $q['type'] === 'single' ? 'Trắc nghiệm' : 'Trắc nghiệm nhiều đáp án'; ?></p>
                                                                <p><strong>Mức độ:</strong> <?php echo $levelLabels[$q['level']] ?? htmlspecialchars($q['level']); ?></p>
                                                                <p><strong>Các lựa chọn:</strong></p>
                                                                <ul class="list-unstyled">
                                                                    <?php
                                                                    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                                                                    $correctIndices = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
                                                                    foreach ($q['options'] as $idx => $opt):
                                                                        $isCorrect = in_array($idx, $correctIndices);
                                                                        $correctMark = $isCorrect ? ' <span class="badge bg-success">✓ Đúng</span>' : '';
                                                                    ?>
                                                                        <li><strong><?php echo $letters[$idx]; ?>.</strong> <?php echo htmlspecialchars($opt); ?><?php echo $correctMark; ?></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                              </div>
                                                              <div class="modal-footer">
                                                                <button type="button" class="btn btn-warning edit-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" data-flat-index="<?php echo $flatIndex; ?>" title="Sửa câu hỏi">
                                                                    ✏️ Sửa
                                                                </button>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                              </div>
                                                            </div>
                                                          </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($questionsData)): ?>
                <div class="alert alert-info">Không có câu hỏi nào.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Vui lòng chọn khối và môn học để xem câu hỏi.</div>
        <?php endif; ?>

        <!-- Import Questions Section -->
        <div class="mt-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">📤 Nhập Câu Hỏi Từ File JSON</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Nhập từ file JSON hoặc thêm thủ công từ Excel</h5>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#excelAddModal">Thêm từ Excel</button>
                    </div>
                    <?php
                    // Import messages are handled in question_bank_handlers.php
                    ?>

                    <?php if ($importError): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($importError); ?></div>
                    <?php endif; ?>
                    <?php if ($importMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($importMessage); ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_questions">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="import_grade" class="form-label">Chọn Khối</label>
                        <select id="import_grade" name="import_grade" class="form-select" required>
                            <option value="">-- Chọn khối --</option>
                            <?php foreach ($availableGrades as $g): ?>
                                <option value="<?php echo $g; ?>"><?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?></option>
                            <?php endforeach; ?>
                        </select>
                            </div>
                            <div class="col-md-4">
                                <label for="import_subject_id" class="form-label">Chọn Môn Học</label>
                                <select id="import_subject_id" name="import_subject_id" class="form-select" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php foreach ($assignedSubjects as $subj): ?>
                                        <option value="<?php echo $subj['id']; ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="questions_file" class="form-label">Chọn File JSON</label>
                                <input type="file" id="questions_file" name="questions_file" class="form-control" accept=".json" required />
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">📤 Nhập Câu Hỏi</button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>📋 Định dạng file JSON mẫu:</h5>
                            <button class="btn btn-sm" id="copyJsonBtn">📋 Sao chép</button>
                        </div>
                        <pre class="bg-light p-3 rounded"><code id="jsonSample">[
  {
    "topic": "Chủ đề 1",
    "lesson": "Bài 1",
    "questions": [
      {
        "question": "Câu hỏi 1?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 0,
        "type": "single",
        "level": "NB"
      },
      {
        "question": "Câu hỏi nhiều đáp án?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": [0, 2],
        "type": "multiple",
        "level": "TH"
      }
    ]
  },
  {
    "topic": "Chủ đề 2",
    "lesson": "Bài 2",
    "questions": [...]
  }
]</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'question_bank_modals.php'; ?>

    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
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
    <script>
        window.questionsData = <?php echo json_encode($questionsData); ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof MathJax !== 'undefined' && MathJax.typeset) {
                MathJax.typeset();
            }
            // Handle topic selection
            const topicSelect = document.getElementById('topic');
            if (topicSelect) {
                topicSelect.addEventListener('change', function() {
                    const newTopicDiv = document.getElementById('newTopicDiv');
                    const lessonSelect = document.getElementById('lesson');
                    if (this.value === 'new_topic') {
                        if (newTopicDiv) newTopicDiv.style.display = 'block';
                        const newTopicName = document.getElementById('new_topic_name');
                        if (newTopicName) newTopicName.required = true;
                        if (lessonSelect) lessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
                    } else {
                        if (newTopicDiv) newTopicDiv.style.display = 'none';
                        const newTopicName = document.getElementById('new_topic_name');
                        if (newTopicName) newTopicName.required = false;
                        // Populate lessons for selected topic
                        populateLessons(this.value);
                    }
                });
            }

            // Handle lesson selection
            const lessonSelect = document.getElementById('lesson');
            if (lessonSelect) {
                lessonSelect.addEventListener('change', function() {
                    const newLessonDiv = document.getElementById('newLessonDiv');
                    if (this.value === 'new_lesson') {
                        if (newLessonDiv) newLessonDiv.style.display = 'block';
                        const newLessonName = document.getElementById('new_lesson_name');
                        if (newLessonName) newLessonName.required = true;
                    } else {
                        if (newLessonDiv) newLessonDiv.style.display = 'none';
                        const newLessonName = document.getElementById('new_lesson_name');
                        if (newLessonName) newLessonName.required = false;
                    }
                });
            }

            function populateLessons(selectedTopic) {
                const lessonSelect = document.getElementById('lesson');
                if (lessonSelect) {
                    lessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
                    // Fetch lessons for the topic via AJAX or use existing data
                    // For simplicity, since we have the data, we can use a data attribute or fetch
                    // But to keep it simple, we'll assume we need to fetch or use a global var
                    // Since PHP renders the page, we can embed the data
                    const questionsData = <?php echo json_encode($questionsData); ?>;
                    const lessons = [];
                    questionsData.forEach(item => {
                        if (item.topic === selectedTopic) {
                            lessons.push(item.lesson);
                        }
                    });
                    lessons.forEach(lesson => {
                        const option = document.createElement('option');
                        option.value = lesson;
                        option.textContent = lesson;
                        lessonSelect.appendChild(option);
                    });
                }
            }

            // Handle adding more options
            const addOptionBtn = document.getElementById('addOptionBtn');
            if (addOptionBtn) {
                let optionIndex = 4; // Start from E
                addOptionBtn.addEventListener('click', function() {
                    const container = document.getElementById('optionsContainer');
                    if (container) {
                        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        const letter = letters[optionIndex % 26];

                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'input-group mb-2';
                        optionDiv.innerHTML = `
                            <span class="input-group-text">${letter}</span>
                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án ${letter}" required>
                            <input type="checkbox" name="correct[]" value="${optionIndex}" class="form-check-input ms-2" title="Đáp án đúng">
                            <button type="button" class="btn btn-sm btn-danger remove-option">X</button>
                        `;
                        container.appendChild(optionDiv);
                        optionIndex++;
                    }
                });
            }

            // Handle removing options
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option')) {
                    e.target.closest('.input-group').remove();
                }
            });

            // Handle form submission
            const addQuestionForm = document.getElementById('addQuestionFormData');
            if (addQuestionForm) {
                addQuestionForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    // Validate at least one correct answer is selected
                    const correctAnswers = formData.getAll('correct[]');
                    if (correctAnswers.length === 0) {
                        alert('Vui lòng chọn ít nhất một đáp án đúng!');
                        return;
                    }

                    // Validate question type and correct answers
                    const questionType = data.question_type;
                    if (questionType === 'single' && correctAnswers.length > 1) {
                        alert('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                        return;
                    }
                    if (questionType === 'multiple' && correctAnswers.length < 2) {
                        alert('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                        return;
                    }

                    // Show loading
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '⏳ Đang lưu...';
                    submitBtn.disabled = true;

                    // Send data
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Câu hỏi đã được thêm thành công!');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    })
                    .catch(error => {
                        alert('Có lỗi xảy ra khi thêm câu hỏi!');
                        console.error(error);
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }

            // Handle delete question
            let currentDeleteData = null;
            let isDeleteAll = false;
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-question')) {
                    e.stopPropagation();
                    const topicIndex = e.target.getAttribute('data-topic-index');
                    const index = e.target.getAttribute('data-index');
                    currentDeleteData = { topicIndex, index };
                    isDeleteAll = false;

                    // Reset modal to default
                    document.getElementById('deleteModalBody').textContent = 'Bạn có chắc chắn muốn xóa câu hỏi này?';
                    document.getElementById('deleteModalLabel').textContent = 'Xác nhận xóa';

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                }
            });

            // Handle confirm delete
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    if (isDeleteAll) {
                        // Check confirmation text
                        const confirmText = document.getElementById('confirmText').value.trim();
                        if (confirmText !== 'OK') {
                            alert('Vui lòng gõ "OK" để xác nhận xóa tất cả câu hỏi.');
                            return;
                        }
                        // Send delete all request
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'delete_all_questions'
                            })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Tất cả câu hỏi đã được xóa thành công!');
                                location.reload();
                            } else {
                                alert('Lỗi: ' + result.message);
                            }
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi xóa tất cả câu hỏi!');
                            console.error(error);
                        });
                    } else if (currentDeleteData) {
                        const { topicIndex, index } = currentDeleteData;
                        // Send delete request
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'delete_question',
                                topic_index: topicIndex,
                                index: index
                            })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                // Close modal
                                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                                deleteModal.hide();
                                // Show success toast
                                const toast = new bootstrap.Toast(document.getElementById('successToast'));
                                document.getElementById('toastMessage').textContent = 'Câu hỏi đã được xóa thành công!';
                                toast.show();
                                // Reload after a short delay
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                alert('Lỗi: ' + result.message);
                            }
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi xóa câu hỏi!');
                            console.error(error);
                        });
                    }
                });
            }

            // Handle delete all questions
            const deleteAllBtn = document.getElementById('deleteAllBtn');
            if (deleteAllBtn) {
                deleteAllBtn.addEventListener('click', function() {
                    isDeleteAll = true;
                    // Update modal for delete all
                    document.getElementById('deleteModalBody').innerHTML = 'Bạn có chắc chắn muốn xóa TẤT CẢ câu hỏi? Hành động này không thể hoàn tác!<div id="deleteConfirmInput" style="margin-top: 10px;"><label for="confirmText" class="form-label">Gõ "OK" để xác nhận:</label><input type="text" id="confirmText" class="form-control" placeholder="OK"></div>';
                    document.getElementById('deleteModalLabel').textContent = 'Xác nhận xóa tất cả';
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            }

            // Handle copy JSON sample
            const copyBtn = document.getElementById('copyJsonBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const jsonSample = document.getElementById('jsonSample');
                    if (jsonSample) {
                        const jsonText = jsonSample.textContent;
                        const button = this; // capture the button
                        // Fallback copy function for compatibility
                        function copyToClipboard(text) {
                            const textArea = document.createElement('textarea');
                            textArea.value = text;
                            document.body.appendChild(textArea);
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                // Change button text temporarily to indicate success
                                const originalText = button.textContent;
                                button.textContent = '✅ Đã sao chép!';
                                setTimeout(() => {
                                    button.textContent = originalText;
                                }, 2000);
                            } catch (err) {
                                alert('Không thể sao chép. Vui lòng sao chép thủ công.');
                                console.error('Copy failed:', err);
                            }
                            document.body.removeChild(textArea);
                        }
                        // Try modern clipboard API first
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(jsonText).then(() => {
                                                               // Change button text temporarily to indicate success
                                const originalText = button.textContent;
                                button.textContent = '✅ Đã sao chép!';
                                setTimeout(() => {
                                    button.textContent = originalText;
                                }, 2000);
                            }).catch(() => {
                                // Fallback to old method
                                copyToClipboard(jsonText);
                            });
                        } else {
                            // Fallback to old method
                            copyToClipboard(jsonText);
                        }
                    }
                });
            }

            // Handle edit question
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('edit-question')) {
                    const topicIndex = e.target.getAttribute('data-topic-index');
                    const index = e.target.getAttribute('data-index');
                    const flatIndex = e.target.getAttribute('data-flat-index');

                    // Hide the view modal
                    const viewModal = bootstrap.Modal.getInstance(document.getElementById('questionModal' + flatIndex));
                    if (viewModal) viewModal.hide();

                    // Get question data from questionsData
                    const questionsData = <?php echo json_encode($questionsData); ?>;
                    const topicData = questionsData[topicIndex];
                    const q = topicData.questions[index];

                    // Populate edit form
                    document.getElementById('edit_topic_index').value = topicIndex;
                    document.getElementById('edit_index').value = index;
                    document.getElementById('edit_topic').value = topicData.topic;
                    document.getElementById('edit_question_text').value = q.question;
                    if (q.type === 'single') {
                        document.getElementById('edit_single_choice').checked = true;
                    } else {
                        document.getElementById('edit_multiple_choice').checked = true;
                    }
                    document.getElementById('edit_question_level').value = q.level;

                    // Populate options
                    const optionsContainer = document.getElementById('editOptionsContainer');
                    optionsContainer.innerHTML = '';
                    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    const correctIndices = Array.isArray(q.correct) ? q.correct : [q.correct];
                    q.options.forEach((opt, idx) => {
                        const letter = letters[idx % 26];
                        const isCorrect = correctIndices.includes(idx);
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'input-group mb-2';
                        optionDiv.innerHTML = `
                            <span class="input-group-text">${letter}</span>
                            <input type="text" name="edit_options[]" class="form-control" placeholder="Đáp án ${letter}" value="${opt}" required>
                            <input type="checkbox" name="edit_correct[]" value="${idx}" class="form-check-input ms-2" title="Đáp án đúng" ${isCorrect ? 'checked' : ''}>
                            ${idx >= 4 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-option">X</button>' : ''}
                        `;
                        optionsContainer.appendChild(optionDiv);
                    });

                    // Populate lessons for the selected topic
                    populateEditLessons(topicData.topic);

                    // Set lesson after populating options
                    document.getElementById('edit_lesson').value = topicData.lesson;

                    // Show edit modal
                    const editModal = new bootstrap.Modal(document.getElementById('editQuestionModal'));
                    editModal.show();
                }
            });

            // Handle edit topic selection
            const editTopicSelect = document.getElementById('edit_topic');
            if (editTopicSelect) {
                editTopicSelect.addEventListener('change', function() {
                    const editNewTopicDiv = document.getElementById('editNewTopicDiv');
                    const editLessonSelect = document.getElementById('edit_lesson');
                    if (this.value === 'new_topic') {
                        if (editNewTopicDiv) editNewTopicDiv.style.display = 'block';
                        const editNewTopicName = document.getElementById('edit_new_topic_name');
                        if (editNewTopicName) editNewTopicName.required = true;
                        if (editLessonSelect) editLessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
                    } else {
                        if (editNewTopicDiv) editNewTopicDiv.style.display = 'none';
                        const editNewTopicName = document.getElementById('edit_new_topic_name');
                        if (editNewTopicName) editNewTopicName.required = false;
                        // Populate lessons for selected topic
                        populateEditLessons(this.value);
                    }
                });
            }

            // Handle edit lesson selection
            const editLessonSelect = document.getElementById('edit_lesson');
            if (editLessonSelect) {
                editLessonSelect.addEventListener('change', function() {
                    const editNewLessonDiv = document.getElementById('editNewLessonDiv');
                    if (this.value === 'new_lesson') {
                        if (editNewLessonDiv) editNewLessonDiv.style.display = 'block';
                        const editNewLessonName = document.getElementById('edit_new_lesson_name');
                        if (editNewLessonName) editNewLessonName.required = true;
                    } else {
                        if (editNewLessonDiv) editNewLessonDiv.style.display = 'none';
                        const editNewLessonName = document.getElementById('edit_new_lesson_name');
                        if (editNewLessonName) editNewLessonName.required = false;
                    }
                });
            }

            function populateEditLessons(selectedTopic) {
                const editLessonSelect = document.getElementById('edit_lesson');
                if (editLessonSelect) {
                    editLessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
                    const questionsData = <?php echo json_encode($questionsData); ?>;
                    const lessons = [];
                    questionsData.forEach(item => {
                        if (item.topic === selectedTopic) {
                            lessons.push(item.lesson);
                        }
                    });
                    lessons.forEach(lesson => {
                        const option = document.createElement('option');
                        option.value = lesson;
                        option.textContent = lesson;
                        editLessonSelect.appendChild(option);
                    });
                }
            }

            // Handle adding more options in edit modal
            const editAddOptionBtn = document.getElementById('editAddOptionBtn');
            if (editAddOptionBtn) {
                let editOptionIndex = 4; // Start from E
                editAddOptionBtn.addEventListener('click', function() {
                    const container = document.getElementById('editOptionsContainer');
                    if (container) {
                        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        const letter = letters[editOptionIndex % 26];

                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'input-group mb-2';
                        optionDiv.innerHTML = `
                            <span class="input-group-text">${letter}</span>
                            <input type="text" name="edit_options[]" class="form-control" placeholder="Đáp án ${letter}" required>
                            <input type="checkbox" name="edit_correct[]" value="${editOptionIndex}" class="form-check-input ms-2" title="Đáp án đúng">
                            <button type="button" class="btn btn-sm btn-danger remove-edit-option">X</button>
                        `;
                        container.appendChild(optionDiv);
                        editOptionIndex++;
                    }
                });
            }

            // Handle removing options in edit modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-edit-option')) {
                    e.target.closest('.input-group').remove();
                }
            });

            // Handle edit form submission
            const editQuestionForm = document.getElementById('editQuestionForm');
            if (editQuestionForm) {
                editQuestionForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    // Validate at least one correct answer is selected
                    const correctAnswers = formData.getAll('edit_correct[]');
                    if (correctAnswers.length === 0) {
                        alert('Vui lòng chọn ít nhất một đáp án đúng!');
                        return;
                    }

                    // Validate question type and correct answers
                    const questionType = data.edit_question_type;
                    if (questionType === 'single' && correctAnswers.length > 1) {
                        alert('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                        return;
                    }
                    if (questionType === 'multiple' && correctAnswers.length < 2) {
                        alert('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                        return;
                    }

                    // Show loading
                    const submitBtn = document.querySelector('button[form="editQuestionForm"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '⏳ Đang lưu...';
                    submitBtn.disabled = true;

                    // Send data
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Câu hỏi đã được cập nhật thành công!');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    })
                    .catch(error => {
                        alert('Có lỗi xảy ra khi cập nhật câu hỏi!');
                        console.error(error);
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
        });

        // Function to download Excel template
        function downloadExcelTemplate() {
            window.location.href = '?action=download_excel_template';
        }
    </script>
<?php include '../includes/footer.php'; ?>
