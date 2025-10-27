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

// Handle POST request for adding questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    header('Content-Type: application/json');

    try {
        // Validate required fields
        $requiredFields = ['topic', 'lesson', 'question_text', 'question_type', 'question_level', 'options'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Thiếu thông tin bắt buộc: $field");
            }
        }

        // Validate correct answers
        if (!isset($_POST['correct']) || empty($_POST['correct'])) {
            throw new Exception("Vui lòng chọn ít nhất một đáp án đúng");
        }

        $topic = $_POST['topic'];
        if ($topic === 'new_topic') {
            if (!isset($_POST['new_topic_name']) || empty(trim($_POST['new_topic_name']))) {
                throw new Exception("Vui lòng nhập tên chủ đề mới");
            }
            $topic = trim($_POST['new_topic_name']);
        }

        $lesson = $_POST['lesson'];
        if ($lesson === 'new_lesson') {
            if (!isset($_POST['new_lesson_name']) || empty(trim($_POST['new_lesson_name']))) {
                throw new Exception("Vui lòng nhập tên bài học mới");
            }
            $lesson = trim($_POST['new_lesson_name']);
        }

        $questionType = $_POST['question_type'];
        $correctAnswers = $_POST['correct'];

        // Validate question type and correct answers
        if ($questionType === 'single' && count($correctAnswers) > 1) {
            throw new Exception("Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng");
        }
        if ($questionType === 'multiple' && count($correctAnswers) < 2) {
            throw new Exception("Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng");
        }

        // Prepare question data
        $newQuestion = [
            'question' => trim($_POST['question_text']),
            'options' => array_map('trim', $_POST['options']),
            'correct' => $questionType === 'single' ? (int)$correctAnswers[0] : array_map('intval', $correctAnswers),
            'type' => $questionType,
            'level' => $_POST['question_level']
        ];

        // Load existing questions
        $questionsDir = __DIR__ . "/questions/{$selectedGrade}";
        if (!is_dir($questionsDir)) {
            mkdir($questionsDir, 0755, true);
        }

        $questionsFile = $questionsDir . "/subject_{$selectedSubjectId}.json";
        $existingData = [];
        if (file_exists($questionsFile)) {
            $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        }

        // Find or add topic/lesson
        $topicIndex = null;
        foreach ($existingData as $idx => $item) {
            if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                $topicIndex = $idx;
                break;
            }
        }
        if ($topicIndex === null) {
            $existingData[] = [
                'topic' => $topic,
                'lesson' => $lesson,
                'questions' => []
            ];
            $topicIndex = count($existingData) - 1;
        }

        // Add new question
        $existingData[$topicIndex]['questions'][] = $newQuestion;

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được thêm thành công']);
        } else {
            throw new Exception("Không thể lưu câu hỏi vào file");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for deleting questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    header('Content-Type: application/json');

    try {
        $topicIndex = isset($_POST['topic_index']) ? (int)$_POST['topic_index'] : -1;
        $questionIndex = isset($_POST['index']) ? (int)$_POST['index'] : -1;

        if ($topicIndex < 0 || $questionIndex < 0) {
            throw new Exception("Thiếu thông tin chỉ số chủ đề hoặc câu hỏi");
        }

        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
        if (!file_exists($questionsFile)) {
            throw new Exception("File câu hỏi không tồn tại");
        }

        $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (!isset($existingData[$topicIndex]) || !isset($existingData[$topicIndex]['questions'][$questionIndex])) {
            throw new Exception("Câu hỏi không tồn tại");
        }

        // Remove the question
        array_splice($existingData[$topicIndex]['questions'], $questionIndex, 1);

        // If questions array is empty, remove the topic
        if (empty($existingData[$topicIndex]['questions'])) {
            unset($existingData[$topicIndex]);
            // Reindex the array
            $existingData = array_values($existingData);
        }

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được xóa thành công']);
        } else {
            throw new Exception("Không thể lưu file sau khi xóa câu hỏi");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for deleting all questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all_questions') {
    header('Content-Type: application/json');

    try {
        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";

        if (file_put_contents($questionsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Tất cả câu hỏi đã được xóa thành công']);
        } else {
            throw new Exception("Không thể xóa tất cả câu hỏi");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

// Handle POST request for editing questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_question') {
    header('Content-Type: application/json');

    try {
        // Validate required fields
        $requiredFields = ['edit_topic', 'edit_lesson', 'edit_question_text', 'edit_question_type', 'edit_question_level', 'edit_topic_index', 'edit_index'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                throw new Exception("Thiếu thông tin bắt buộc: $field");
            }
        }

        // Validate correct answers
        if (!isset($_POST['edit_correct']) || empty($_POST['edit_correct'])) {
            throw new Exception("Vui lòng chọn ít nhất một đáp án đúng");
        }

        $topicIndex = (int)$_POST['edit_topic_index'];
        $questionIndex = (int)$_POST['edit_index'];

        $topic = $_POST['edit_topic'];
        if ($topic === 'new_topic') {
            if (!isset($_POST['edit_new_topic_name']) || empty(trim($_POST['edit_new_topic_name']))) {
                throw new Exception("Vui lòng nhập tên chủ đề mới");
            }
            $topic = trim($_POST['edit_new_topic_name']);
        }

        $lesson = $_POST['edit_lesson'];
        if ($lesson === 'new_lesson') {
            if (!isset($_POST['edit_new_lesson_name']) || empty(trim($_POST['edit_new_lesson_name']))) {
                throw new Exception("Vui lòng nhập tên bài học mới");
            }
            $lesson = trim($_POST['edit_new_lesson_name']);
        }

        $questionType = $_POST['edit_question_type'];
        $correctAnswers = $_POST['edit_correct'];

        // Validate question type and correct answers
        if ($questionType === 'single' && count($correctAnswers) > 1) {
            throw new Exception("Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng");
        }
        if ($questionType === 'multiple' && count($correctAnswers) < 2) {
            throw new Exception("Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng");
        }

        // Load existing questions
        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
        if (!file_exists($questionsFile)) {
            throw new Exception("File câu hỏi không tồn tại");
        }

        $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (!isset($existingData[$topicIndex]) || !isset($existingData[$topicIndex]['questions'][$questionIndex])) {
            throw new Exception("Câu hỏi không tồn tại");
        }

        // Prepare updated question data
        $updatedQuestion = [
            'question' => trim($_POST['edit_question_text']),
            'options' => array_map('trim', $_POST['edit_options']),
            'correct' => $questionType === 'single' ? (int)$correctAnswers[0] : array_map('intval', $correctAnswers),
            'type' => $questionType,
            'level' => $_POST['edit_question_level']
        ];

        // If topic or lesson changed, handle moving
        $currentTopic = $existingData[$topicIndex]['topic'];
        $currentLesson = $existingData[$topicIndex]['lesson'];
        if ($topic !== $currentTopic || $lesson !== $currentLesson) {
            // Remove from current topic/lesson
            array_splice($existingData[$topicIndex]['questions'], $questionIndex, 1);
            // If questions empty, remove topic
            if (empty($existingData[$topicIndex]['questions'])) {
                unset($existingData[$topicIndex]);
                $existingData = array_values($existingData);
            }
            // Find or add new topic/lesson
            $newTopicIndex = null;
            foreach ($existingData as $idx => $item) {
                if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                    $newTopicIndex = $idx;
                    break;
                }
            }
            if ($newTopicIndex === null) {
                $existingData[] = [
                    'topic' => $topic,
                    'lesson' => $lesson,
                    'questions' => []
                ];
                $newTopicIndex = count($existingData) - 1;
            }
            // Add to new topic/lesson
            $existingData[$newTopicIndex]['questions'][] = $updatedQuestion;
        } else {
            // Update in place
            $existingData[$topicIndex]['questions'][$questionIndex] = $updatedQuestion;
        }

        // Save back to file
        if (file_put_contents($questionsFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Câu hỏi đã được cập nhật thành công']);
        } else {
            throw new Exception("Không thể lưu câu hỏi đã cập nhật");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}



function renderOptions($options) {
    $html = '<ul>';
    foreach ($options as $opt) {
        $html .= '<li>' . htmlspecialchars($opt) . '</li>';
    }
    $html .= '</ul>';
    return $html;
}



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
            <!-- Add Question Form -->
            <div class="collapse mb-4" id="addQuestionForm">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Thêm Câu Hỏi Mới</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="addQuestionFormData">
                            <input type="hidden" name="action" value="add_question">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="topic" class="form-label">Chủ Đề</label>
                                    <select id="topic" name="topic" class="form-select" required>
                                        <option value="">-- Chọn chủ đề --</option>
                                        <?php
                                        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
                                        if (file_exists($questionsFile)) {
                                            $data = json_decode(file_get_contents($questionsFile), true);
                                            if (is_array($data)) {
                                                $topics = [];
                                                foreach ($data as $item) {
                                                    $topics[$item['topic']] = true;
                                                }
                                                foreach (array_keys($topics) as $topic) {
                                                    echo "<option value=\"$topic\">$topic</option>";
                                                }
                                            }
                                        }
                                        ?>
                                        <option value="new_topic">+ Tạo chủ đề mới</option>
                                    </select>
                                </div>
                                <div class="col-12" id="newTopicDiv" style="display:none;">
                                    <label for="new_topic_name" class="form-label">Tên Chủ Đề Mới</label>
                                    <input type="text" id="new_topic_name" name="new_topic_name" class="form-control" placeholder="Ví dụ: Chủ đề 1: Máy tính và cộng đồng">
                                </div>
                                <div class="col-12">
                                    <label for="lesson" class="form-label">Bài Học</label>
                                    <select id="lesson" name="lesson" class="form-select" required>
                                        <option value="">-- Chọn bài học --</option>
                                        <option value="new_lesson">+ Tạo bài học mới</option>
                                    </select>
                                </div>
                                <div class="col-12" id="newLessonDiv" style="display:none;">
                                    <label for="new_lesson_name" class="form-label">Tên Bài Học Mới</label>
                                    <input type="text" id="new_lesson_name" name="new_lesson_name" class="form-control" placeholder="Ví dụ: Bài 1: Thiết bị vào và thiết bị ra">
                                </div>
                                <div class="col-12">
                                    <label for="question_text" class="form-label">Câu Hỏi</label>
                                    <textarea id="question_text" name="question_text" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Loại Câu Hỏi</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="question_type" id="single_choice" value="single" checked>
                                        <label class="form-check-label" for="single_choice">
                                            Trắc nghiệm (1 đáp án đúng)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="question_type" id="multiple_choice" value="multiple">
                                        <label class="form-check-label" for="multiple_choice">
                                            Trắc nghiệm nhiều đáp án
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="question_level" class="form-label">Mức Độ</label>
                                    <select id="question_level" name="question_level" class="form-select" required>
                                        <option value="NB">Nhận biết</option>
                                        <option value="TH">Thông hiểu</option>
                                        <option value="VD">Vận dụng</option>
                                        <option value="VDC">Vận dụng cao</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Đáp Án</label>
                                    <div id="optionsContainer">
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">A</span>
                                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án A" required>
                                            <input type="checkbox" name="correct[]" value="0" class="form-check-input ms-2" title="Đáp án đúng">
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">B</span>
                                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án B" required>
                                            <input type="checkbox" name="correct[]" value="1" class="form-check-input ms-2" title="Đáp án đúng">
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">C</span>
                                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án C" required>
                                            <input type="checkbox" name="correct[]" value="2" class="form-check-input ms-2" title="Đáp án đúng">
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">D</span>
                                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án D" required>
                                            <input type="checkbox" name="correct[]" value="3" class="form-check-input ms-2" title="Đáp án đúng">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addOptionBtn">+ Thêm đáp án</button>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success">💾 Lưu Câu Hỏi</button>
                                    <button type="button" class="btn btn-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#addQuestionForm" onclick="document.getElementById('addQuestionFormData').reset();">Hủy</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selectedGrade && $selectedSubjectId): ?>
            <table class="table table-bordered table-striped">
                <thead class="table-success">
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
                    <?php foreach ($questions as $index => $item): ?>
                        <?php $q = $item['data']; ?>
                        <tr onclick="if (!event.target.closest('.delete-question')) { const modal = new bootstrap.Modal(document.getElementById('questionModal<?php echo $index; ?>')); modal.show(); }" style="cursor:pointer;">
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($q['question']); ?></td>
                            <td><?php echo renderCorrect($q['correct'], $q['options']); ?></td>
                            <td><?php echo $q['type'] === 'single' ? 'Trắc nghiệm' : 'Trắc nghiệm nhiều đáp án'; ?></td>
                            <td>
                                <?php
                                $levelLabels = ['NB' => 'Nhận biết', 'TH' => 'Thông hiểu', 'VD' => 'Vận dụng', 'VDC' => 'Vận dụng cao'];
                                echo $levelLabels[$q['level']] ?? htmlspecialchars($q['level']);
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-question" data-topic-index="<?php echo $item['topicIndex']; ?>" data-index="<?php echo $item['index']; ?>" title="Xóa câu hỏi">
                                    🗑️ Xóa
                                </button>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="questionModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="questionModalLabel<?php echo $index; ?>" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="questionModalLabel<?php echo $index; ?>">Chi tiết câu hỏi #<?php echo $index + 1; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <p><strong>Câu hỏi:</strong> <?php echo htmlspecialchars($q['question']); ?></p>
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
                                <button type="button" class="btn btn-warning edit-question" data-topic-index="<?php echo $item['topicIndex']; ?>" data-index="<?php echo $index; ?>" data-flat-index="<?php echo $index; ?>" title="Sửa câu hỏi">
                                    ✏️ Sửa
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                              </div>
                            </div>
                          </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="6" class="text-center">Không có câu hỏi nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
                    <?php
                    $importMessage = '';
                    $importError = '';

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_questions') {
                        $grade = $_POST['import_grade'] ?? '';
                        $subjectId = (int)($_POST['import_subject_id'] ?? 0);

                        if (!in_array($grade, $availableGrades)) {
                            $importError = 'Khối không hợp lệ.';
                        } elseif (!in_array($subjectId, $assignedSubjectIds)) {
                            $importError = 'Môn học không hợp lệ hoặc không được phép.';
                        } elseif (!isset($_FILES['questions_file']) || $_FILES['questions_file']['error'] !== UPLOAD_ERR_OK) {
                            $importError = 'Vui lòng chọn file JSON hợp lệ để tải lên.';
                        } else {
                            $questionsDir = __DIR__ . '/questions/' . $grade . '/';
                            if (!is_dir($questionsDir)) {
                                mkdir($questionsDir, 0755, true);
                            }
                            $fileContent = file_get_contents($_FILES['questions_file']['tmp_name']);
                            $data = json_decode($fileContent, true);
                            if ($data === null) {
                                $importError = 'File JSON không hợp lệ.';
                            } else {
                                if (!is_array($data)) {
                                    $importError = 'File JSON phải là mảng các chủ đề/bài học.';
                                } else {
                                    $allValid = true;
                                    $normalizedData = [];
                                    foreach ($data as $topicItem) {
                                        if (!isset($topicItem['topic'], $topicItem['lesson'], $topicItem['questions']) || !is_array($topicItem['questions'])) {
                                            $allValid = false;
                                            break;
                                        }
                                        $valid = true;
                                        foreach ($topicItem['questions'] as &$q) {
                                            if (!isset($q['question'], $q['options'], $q['correct'], $q['type'], $q['level'])) {
                                                $valid = false;
                                                break;
                                            }
                                            if ($q['type'] === 'single') {
                                                if (is_array($q['correct']) && count($q['correct']) === 1) {
                                                    $q['correct'] = $q['correct'][0];
                                                } elseif (!is_int($q['correct'])) {
                                                    $valid = false;
                                                    break;
                                                }
                                            } elseif ($q['type'] === 'multiple' && !is_array($q['correct'])) {
                                                $valid = false;
                                                break;
                                            }
                                        }
                                        unset($q);
                                        if (!$valid) {
                                            $allValid = false;
                                            break;
                                        }
                                        $normalizedData[] = $topicItem;
                                    }
                                    if (!$allValid) {
                                        $importError = 'Định dạng câu hỏi không hợp lệ.';
                                    } else {
                                        $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                                        $existing = [];
                                        if (file_exists($subjectQuestionsFile)) {
                                            $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                                        }
                                        // Merge imported data into existing, avoiding duplicates
                                        foreach ($normalizedData as $newTopicItem) {
                                            $topic = $newTopicItem['topic'];
                                            $lesson = $newTopicItem['lesson'];
                                            $newQuestions = $newTopicItem['questions'];
                                            $merged = false;
                                            foreach ($existing as &$existingTopic) {
                                                if ($existingTopic['topic'] === $topic && $existingTopic['lesson'] === $lesson) {
                                                    // Merge questions, avoiding duplicates based on question text
                                                    foreach ($newQuestions as $newQ) {
                                                        $duplicate = false;
                                                        foreach ($existingTopic['questions'] as $existingQ) {
                                                            if ($existingQ['question'] === $newQ['question']) {
                                                                $duplicate = true;
                                                                break;
                                                            }
                                                        }
                                                        if (!$duplicate) {
                                                            $existingTopic['questions'][] = $newQ;
                                                        }
                                                    }
                                                    $merged = true;
                                                    break;
                                                }
                                            }
                                            unset($existingTopic);
                                            if (!$merged) {
                                                $existing[] = $newTopicItem;
                                            }
                                        }
                                        if (file_put_contents($subjectQuestionsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                                            $importMessage = 'Câu hỏi đã được nhập thành công cho môn học.';
                                        } else {
                                            $importError = 'Lỗi khi lưu câu hỏi.';
                                        }
                                    }
                                }
                            }
                        }
                    }
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa câu hỏi này?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editQuestionModalLabel">Sửa Câu Hỏi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="editQuestionForm">
                        <input type="hidden" name="action" value="edit_question">
                        <input type="hidden" name="edit_topic_index" id="edit_topic_index">
                        <input type="hidden" name="edit_index" id="edit_index">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="edit_topic" class="form-label">Chủ Đề</label>
                                <select id="edit_topic" name="edit_topic" class="form-select" required>
                                    <option value="">-- Chọn chủ đề --</option>
                                    <?php
                                    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
                                    if (file_exists($questionsFile)) {
                                        $data = json_decode(file_get_contents($questionsFile), true);
                                        if (is_array($data)) {
                                            $topics = [];
                                            foreach ($data as $item) {
                                                $topics[$item['topic']] = true;
                                            }
                                            foreach (array_keys($topics) as $topic) {
                                                echo "<option value=\"$topic\">$topic</option>";
                                            }
                                        }
                                    }
                                    ?>
                                    <option value="new_topic">+ Tạo chủ đề mới</option>
                                </select>
                            </div>
                            <div class="col-12" id="editNewTopicDiv" style="display:none;">
                                <label for="edit_new_topic_name" class="form-label">Tên Chủ Đề Mới</label>
                                <input type="text" id="edit_new_topic_name" name="edit_new_topic_name" class="form-control" placeholder="Ví dụ: Chủ đề 1: Máy tính và cộng đồng">
                            </div>
                            <div class="col-12">
                                <label for="edit_lesson" class="form-label">Bài Học</label>
                                <select id="edit_lesson" name="edit_lesson" class="form-select" required>
                                    <option value="">-- Chọn bài học --</option>
                                    <option value="new_lesson">+ Tạo bài học mới</option>
                                </select>
                            </div>
                            <div class="col-12" id="editNewLessonDiv" style="display:none;">
                                <label for="edit_new_lesson_name" class="form-label">Tên Bài Học Mới</label>
                                <input type="text" id="edit_new_lesson_name" name="edit_new_lesson_name" class="form-control" placeholder="Ví dụ: Bài 1: Thiết bị vào và thiết bị ra">
                            </div>
                            <div class="col-12">
                                <label for="edit_question_text" class="form-label">Câu Hỏi</label>
                                <textarea id="edit_question_text" name="edit_question_text" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Loại Câu Hỏi</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="edit_question_type" id="edit_single_choice" value="single" checked>
                                    <label class="form-check-label" for="edit_single_choice">
                                        Trắc nghiệm (1 đáp án đúng)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="edit_question_type" id="edit_multiple_choice" value="multiple">
                                    <label class="form-check-label" for="edit_multiple_choice">
                                        Trắc nghiệm nhiều đáp án
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_question_level" class="form-label">Mức Độ</label>
                                <select id="edit_question_level" name="edit_question_level" class="form-select" required>
                                    <option value="NB">Nhận biết</option>
                                    <option value="TH">Thông hiểu</option>
                                    <option value="VD">Vận dụng</option>
                                    <option value="VDC">Vận dụng cao</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Đáp Án</label>
                                <div id="editOptionsContainer">
                                    <!-- Options will be populated by JS -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddOptionBtn">+ Thêm đáp án</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success" form="editQuestionForm">💾 Lưu Thay Đổi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Câu hỏi đã được xóa thành công!
            </div>
        </div>
    </div>

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
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-question')) {
                    e.stopPropagation();
                    const topicIndex = e.target.getAttribute('data-topic-index');
                    const index = e.target.getAttribute('data-index');
                    currentDeleteData = { topicIndex, index };

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                }
            });

            // Handle confirm delete
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    if (currentDeleteData) {
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
                    if (confirm('Bạn có chắc chắn muốn xóa TẤT CẢ câu hỏi? Hành động này không thể hoàn tác!')) {
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
                    }
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

                    // Hide the view modal
                    const viewModal = bootstrap.Modal.getInstance(document.getElementById('questionModal' + index));
                    if (viewModal) viewModal.hide();

                    // Get question data from questions array
                    const questionData = <?php echo json_encode($questions); ?>[index];
                    const q = questionData.data;

                    // Populate edit form
                    document.getElementById('edit_topic_index').value = questionData.topicIndex;
                    document.getElementById('edit_index').value = questionData.index;
                    document.getElementById('edit_topic').value = questionData.topic;
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
                    populateEditLessons(questionData.topic);

                    // Set lesson after populating options
                    document.getElementById('edit_lesson').value = questionData.lesson;

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
    </script>
<?php include '../includes/footer.php'; ?>
