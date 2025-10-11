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
            foreach ($questionsData as $lesson => $lessonQuestions) {
                foreach ($lessonQuestions as $idx => $q) {
                    $questions[] = [
                        'data' => $q,
                        'lesson' => $lesson,
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
        $requiredFields = ['lesson', 'question_text', 'question_type', 'question_level', 'options'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Thiếu thông tin bắt buộc: $field");
            }
        }

        // Validate correct answers
        if (!isset($_POST['correct']) || empty($_POST['correct'])) {
            throw new Exception("Vui lòng chọn ít nhất một đáp án đúng");
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

        // Add new question to the lesson
        if (!isset($existingData[$lesson])) {
            $existingData[$lesson] = [];
        }
        $existingData[$lesson][] = $newQuestion;

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
        $lesson = $_POST['lesson'] ?? '';
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;

        if (!$lesson || $index < 0) {
            throw new Exception("Thiếu thông tin bài học hoặc chỉ số câu hỏi");
        }

        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
        if (!file_exists($questionsFile)) {
            throw new Exception("File câu hỏi không tồn tại");
        }

        $existingData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (!isset($existingData[$lesson]) || !isset($existingData[$lesson][$index])) {
            throw new Exception("Câu hỏi không tồn tại");
        }

        // Remove the question
        array_splice($existingData[$lesson], $index, 1);

        // If lesson is empty, remove it
        if (empty($existingData[$lesson])) {
            unset($existingData[$lesson]);
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
                                    <label for="lesson" class="form-label">Bài Học</label>
                                    <select id="lesson" name="lesson" class="form-select" required>
                                        <option value="">-- Chọn bài học --</option>
                                        <?php
                                        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
                                        if (file_exists($questionsFile)) {
                                            $data = json_decode(file_get_contents($questionsFile), true);
                                            if (is_array($data)) {
                                                foreach (array_keys($data) as $lesson) {
                                                    echo "<option value=\"$lesson\">$lesson</option>";
                                                }
                                            }
                                        }
                                        ?>
                                        <option value="new_lesson">+ Tạo bài học mới</option>
                                    </select>
                                </div>
                                <div class="col-12" id="newLessonDiv" style="display:none;">
                                    <label for="new_lesson_name" class="form-label">Tên Bài Học Mới</label>
                                    <input type="text" id="new_lesson_name" name="new_lesson_name" class="form-control" placeholder="Ví dụ: bai1, bai2, ...">
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
                        <tr data-bs-toggle="modal" data-bs-target="#questionModal<?php echo $index; ?>" style="cursor:pointer;">
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
                                <button class="btn btn-danger btn-sm delete-question" data-lesson="<?php echo htmlspecialchars($item['lesson']); ?>" data-index="<?php echo $item['index']; ?>" title="Xóa câu hỏi">
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
                                    $importError = 'File JSON phải là object với các bài học.';
                                } else {
                                    $allValid = true;
                                    $normalizedData = [];
                                    foreach ($data as $lesson => $questions) {
                                        if (!is_array($questions)) {
                                            $allValid = false;
                                            break;
                                        }
                                        $valid = true;
                                        foreach ($questions as &$q) {
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
                                        $normalizedData[$lesson] = $questions;
                                    }
                                    if (!$allValid) {
                                        $importError = 'Định dạng câu hỏi không hợp lệ.';
                                    } else {
                                        $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                                        $existing = [];
                                        if (file_exists($subjectQuestionsFile)) {
                                            $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                                        }
                                        foreach ($normalizedData as $lesson => $questions) {
                                            $existing[$lesson] = array_merge($existing[$lesson] ?? [], $questions);
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
                        <h5>📋 Định dạng file JSON mẫu:</h5>
                        <pre class="bg-light p-3 rounded"><code>{
  "bai1": [
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
  ],
  "bai2": [...]
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle lesson selection
            document.getElementById('lesson').addEventListener('change', function() {
                const newLessonDiv = document.getElementById('newLessonDiv');
                if (this.value === 'new_lesson') {
                    newLessonDiv.style.display = 'block';
                    document.getElementById('new_lesson_name').required = true;
                } else {
                    newLessonDiv.style.display = 'none';
                    document.getElementById('new_lesson_name').required = false;
                }
            });

            // Handle adding more options
            let optionIndex = 4; // Start from E
            document.getElementById('addOptionBtn').addEventListener('click', function() {
                const container = document.getElementById('optionsContainer');
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
            });

            // Handle removing options
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option')) {
                    e.target.closest('.input-group').remove();
                }
            });

            // Handle form submission
            document.getElementById('addQuestionFormData').addEventListener('submit', function(e) {
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

            // Handle delete question
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-question')) {
                    const lesson = e.target.getAttribute('data-lesson');
                    const index = e.target.getAttribute('data-index');

                    if (confirm('Bạn có chắc chắn muốn xóa câu hỏi này?')) {
                        // Send delete request
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'delete_question',
                                lesson: lesson,
                                index: index
                            })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Câu hỏi đã được xóa thành công!');
                                location.reload();
                            } else {
                                alert('Lỗi: ' + result.message);
                            }
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi xóa câu hỏi!');
                            console.error(error);
                        });
                    }
                }
            });

            // Handle delete all questions
            document.getElementById('deleteAllBtn').addEventListener('click', function() {
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
        });
    </script>
<?php include '../includes/footer.php'; ?>
