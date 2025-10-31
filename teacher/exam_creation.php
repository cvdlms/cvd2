<?php
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/common_functions.php';

// Function to create URL-friendly slug
function create_slug($string) {
    // Remove accents
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
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

// Function to sanitize data for JSON encoding (fix malformed UTF-8)
function sanitize_for_json($data) {
    if (is_string($data)) {
        // Convert to valid UTF-8, replacing invalid sequences
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    } elseif (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_for_json($value);
        }
    } elseif (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->$key = sanitize_for_json($value);
        }
    }
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $title = 'Tạo Bài Kiểm Tra - CVD';
    include '../includes/teacher_header.php';
}

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

// Filter grades to only show assigned ones
$availableGrades = array_intersect($grades, $assignedGrades);

$selectedGrade = $_GET['grade'] ?? '';
$selectedSubjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($assignedSubjectIds ? $assignedSubjectIds[0] : 0);

if ($selectedSubjectId && !in_array($selectedSubjectId, $assignedSubjectIds)) {
    die('Môn học không hợp lệ hoặc không được phép.');
}

if ($selectedGrade && !in_array($selectedGrade, $availableGrades)) {
    die('Khối không hợp lệ.');
}

$questions = [];
$examData = null;
if ($selectedGrade && $selectedSubjectId) {
    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
    if (file_exists($questionsFile)) {
        $data = json_decode(file_get_contents($questionsFile), true);
        if (is_array($data)) {
            foreach ($data as $topicData) {
                $lessonQuestions = $topicData['questions'] ?? [];
                foreach ($lessonQuestions as $q) {
                    $questions[] = $q;
                }
            }
        }
    }

    // Check if exam exists
    $examFile = __DIR__ . "/exams/{$selectedGrade}/subject_{$selectedSubjectId}_exam.json";
    if (file_exists($examFile)) {
        $examData = json_decode(file_get_contents($examFile), true);
    }
}

// Handle POST for creating exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'create_manual') {
            if (!isset($_POST['selected_questions']) || empty($_POST['selected_questions'])) {
                throw new Exception("Vui lòng chọn ít nhất một câu hỏi");
            }
            if (!isset($_POST['test_name']) || trim($_POST['test_name']) === '') {
                throw new Exception("Vui lòng nhập tên đề kiểm tra");
            }
            $testName = trim($_POST['test_name']);
            $selectedIds = array_map('intval', $_POST['selected_questions']);
            $selectedQuestions = [];
            foreach ($selectedIds as $idx) {
                if (isset($questions[$idx])) {
                    $selectedQuestions[] = $questions[$idx];
                }
            }
            if (empty($selectedQuestions)) {
                throw new Exception("Không có câu hỏi hợp lệ được chọn");
            }

            // Save exam
            $examsDir = __DIR__ . "/exams/{$selectedGrade}/subject_{$selectedSubjectId}";
            if (!is_dir($examsDir)) {
                if (!mkdir($examsDir, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục đề thi");
                }
            }
            // Sanitize test name for filename
            $safeTestName = create_slug($testName);
            $examFile = $examsDir . "/{$safeTestName}.json";
            $totalPoints = (int)$_POST['total_points'];
            // Generate test_id
            $subjectName = '';
            foreach ($subjects as $subj) {
                if ($subj['id'] == $selectedSubjectId) {
                    $subjectName = $subj['name'];
                    break;
                }
            }
            $subjectAbbrev = strtoupper(substr($subjectName, 0, 3));
            $testId = $subjectAbbrev . $safeTestName . '_' . date('YmdHis');
            $examData = [
                'test_id' => $testId,
                'test_name' => $testName,
                'subject_id' => $selectedSubjectId,
                'questions' => $selectedQuestions,
                'created_at' => date('Y-m-d H:i:s'),
                'teacher' => $username,
                'total_questions' => count($selectedQuestions),
                'points_per_question' => round($totalPoints / count($selectedQuestions), 2),
                'total_points' => $totalPoints,
                'time_limit' => (int)$_POST['time_limit']
            ];
            $examData = sanitize_for_json($examData);
            $json = json_encode($examData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new Exception("Không thể mã hóa dữ liệu đề thi: " . json_last_error_msg());
            }
            if (file_put_contents($examFile, $json)) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được tạo thành công']);
            } else {
                throw new Exception("Không thể lưu đề thi");
            }
        } elseif ($_POST['action'] === 'create_auto') {
            if (!isset($_POST['test_name']) || trim($_POST['test_name']) === '') {
                throw new Exception("Vui lòng nhập tên đề kiểm tra");
            }
            $testName = trim($_POST['test_name']);
            $num_questions = (int)($_POST['num_questions'] ?? 20);
            $nb_percent = (int)($_POST['nb_percent'] ?? 50);
            $th_percent = (int)($_POST['th_percent'] ?? 40);
            $vd_percent = (int)($_POST['vd_percent'] ?? 10);
            if ($nb_percent + $th_percent + $vd_percent !== 100) {
                throw new Exception("Tổng phần trăm NB, TH, VD phải bằng 100%");
            }
            if ($num_questions < 1 || $num_questions > 50) {
                throw new Exception("Số câu hỏi phải từ 1 đến 50");
            }
            $nb = [];
            $th = [];
            $vd = [];
            foreach ($questions as $q) {
                if ($q['level'] === 'NB') $nb[] = $q;
                elseif ($q['level'] === 'TH') $th[] = $q;
                elseif ($q['level'] === 'VD') $vd[] = $q;
            }
            $nb_count = (int)round($num_questions * $nb_percent / 100);
            $th_count = (int)round($num_questions * $th_percent / 100);
            $vd_count = $num_questions - $nb_count - $th_count;
            if ($vd_count < 0) $vd_count = 0;
            $selectedQuestions = [];
            if ($nb_count > 0) {
                shuffle($nb);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($nb, 0, min($nb_count, count($nb))));
            }
            if ($th_count > 0) {
                shuffle($th);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($th, 0, min($th_count, count($th))));
            }
            if ($vd_count > 0) {
                shuffle($vd);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($vd, 0, min($vd_count, count($vd))));
            }
            if (count($selectedQuestions) < $num_questions) {
                $remaining = $num_questions - count($selectedQuestions);
                $all = array_merge($nb, $th, $vd);
                shuffle($all);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($all, 0, $remaining));
            }
            shuffle($selectedQuestions);

            // Save exam
            $examsDir = __DIR__ . "/exams/{$selectedGrade}/subject_{$selectedSubjectId}";
            if (!is_dir($examsDir)) {
                if (!mkdir($examsDir, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục đề thi");
                }
            }
            if (!is_writable($examsDir)) {
                throw new Exception("Thư mục đề thi không thể ghi: " . $examsDir);
            }
            // Sanitize test name for filename
            $safeTestName = create_slug($testName);
            $examFile = $examsDir . "/{$safeTestName}.json";
            $totalPoints = (int)$_POST['total_points'];
            // Generate test_id
            $subjectName = '';
            foreach ($subjects as $subj) {
                if ($subj['id'] == $selectedSubjectId) {
                    $subjectName = $subj['name'];
                    break;
                }
            }
            $subjectAbbrev = strtoupper(substr($subjectName, 0, 3));
            $testId = $subjectAbbrev . $safeTestName . '_' . date('YmdHis');
            $examData = [
                'test_id' => $testId,
                'test_name' => $testName,
                'subject_id' => $selectedSubjectId,
                'questions' => $selectedQuestions,
                'created_at' => date('Y-m-d H:i:s'),
                'teacher' => $username,
                'total_questions' => count($selectedQuestions),
                'points_per_question' => round($totalPoints / count($selectedQuestions), 2),
                'total_points' => $totalPoints,
                'time_limit' => (int)$_POST['time_limit']
            ];
            $examData = sanitize_for_json($examData);
            $json = json_encode($examData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new Exception("Không thể mã hóa dữ liệu đề thi");
            }
            if (file_put_contents($examFile, $json)) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được tạo thành công']);
            } else {
                throw new Exception("Không thể lưu đề thi vào: " . $examFile);
            }
        } elseif ($_POST['action'] === 'approve_exam') {
            if (!isset($_POST['file']) || !isset($_POST['grade']) || !isset($_POST['subject_id'])) {
                throw new Exception("Thiếu thông tin để duyệt đề thi");
            }
            $file = basename($_POST['file']);
            $grade = $_POST['grade'];
            $subjectId = (int)$_POST['subject_id'];
            $examsDir = __DIR__ . "/exams/{$grade}/subject_{$subjectId}";
            $examFile = $examsDir . '/' . $file;
            if (!file_exists($examFile)) {
                throw new Exception("Đề thi không tồn tại");
            }
            $examData = json_decode(file_get_contents($examFile), true);
            if (!$examData) {
                throw new Exception("Không thể đọc dữ liệu đề thi");
            }
            $examData['approved'] = true;
            $examData['approved_at'] = date('Y-m-d H:i:s');
            $examData = sanitize_for_json($examData);
            if (file_put_contents($examFile, json_encode($examData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được duyệt thành công']);
            } else {
                throw new Exception("Không thể lưu trạng thái duyệt");
            }
        } elseif ($_POST['action'] === 'edit_exam') {
            if (!isset($_POST['file']) || !isset($_POST['grade']) || !isset($_POST['subject_id']) || !isset($_POST['test_name']) || !isset($_POST['time_limit']) || !isset($_POST['total_points'])) {
                throw new Exception("Thiếu thông tin để sửa đề thi");
            }
            $file = basename($_POST['file']);
            $grade = $_POST['grade'];
            $subjectId = (int)$_POST['subject_id'];
            $testName = trim($_POST['test_name']);
            $timeLimit = (int)$_POST['time_limit'];
            $totalPoints = (int)$_POST['total_points'];
            $removed_questions = $_POST['removed_questions'] ?? [];
            $added_questions = $_POST['added_questions'] ?? [];

            // Load questions for the grade and subject
            $questions = [];
            $questionsFile = __DIR__ . "/questions/{$grade}/subject_{$subjectId}.json";
            if (file_exists($questionsFile)) {
                $data = json_decode(file_get_contents($questionsFile), true);
                if (is_array($data)) {
                    foreach ($data as $topicData) {
                        $lessonQuestions = $topicData['questions'] ?? [];
                        foreach ($lessonQuestions as $q) {
                            $questions[] = $q;
                        }
                    }
                }
            }

            $examsDir = __DIR__ . "/exams/{$grade}/subject_{$subjectId}";
            $examFile = $examsDir . '/' . $file;
            if (!file_exists($examFile)) {
                throw new Exception("Đề thi không tồn tại");
            }
            $examData = json_decode(file_get_contents($examFile), true);
            if (!$examData) {
                throw new Exception("Không thể đọc dữ liệu đề thi");
            }

            // Remove selected questions
            $examData['questions'] = array_values(array_filter($examData['questions'], function($key) use ($removed_questions) {
                return !in_array($key, $removed_questions);
            }, ARRAY_FILTER_USE_KEY));

            // Add selected questions
            foreach ($added_questions as $idx) {
                $idx = (int)$idx;
                if (isset($questions[$idx])) {
                    $examData['questions'][] = $questions[$idx];
                }
            }

            if (empty($examData['questions'])) {
                throw new Exception("Đề thi phải có ít nhất một câu hỏi");
            }

            // Update metadata
            $examData['test_name'] = $testName;
            $examData['time_limit'] = $timeLimit;
            $examData['total_questions'] = count($examData['questions']);
            $examData['points_per_question'] = round($totalPoints / count($examData['questions']), 2);
            $examData['total_points'] = $totalPoints;
            $examData['updated_at'] = date('Y-m-d H:i:s');
            $examData = sanitize_for_json($examData);

            if (file_put_contents($examFile, json_encode($examData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được sửa thành công']);
            } else {
                throw new Exception("Không thể lưu thay đổi đề thi");
            }
        } elseif ($_POST['action'] === 'delete_exam') {
            if (!isset($_POST['file']) || !isset($_POST['grade']) || !isset($_POST['subject_id'])) {
                throw new Exception("Thiếu thông tin để xóa đề thi");
            }
            $file = basename($_POST['file']);
            $grade = $_POST['grade'];
            $subjectId = (int)$_POST['subject_id'];
            $examsDir = __DIR__ . "/exams/{$grade}/subject_{$subjectId}";
            $examFile = $examsDir . '/' . $file;
            if (!file_exists($examFile)) {
                throw new Exception("Đề thi không tồn tại");
            }
            if (unlink($examFile)) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được xóa thành công']);
            } else {
                throw new Exception("Không thể xóa đề thi");
            }
        } else {
            throw new Exception("Hành động không hợp lệ");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

?>

    <div class="container my-4 exam-creation-section">
        <h2>Ra Đề Kiểm Tra</h2>

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
            <?php
                // Load all exams for this grade and subject
                $examsDir = __DIR__ . "/exams/{$selectedGrade}/subject_{$selectedSubjectId}";
                $examFiles = [];
                if (is_dir($examsDir)) {
                    $files = scandir($examsDir);
                    foreach ($files as $file) {
                        if (preg_match('/\.json$/', $file)) {
                            $examFiles[] = $file;
                        }
                    }
                }
                $examsList = [];
                foreach ($examFiles as $file) {
                    $path = $examsDir . '/' . $file;
                    $content = json_decode(file_get_contents($path), true);
                    if ($content) {
                        $examsList[] = [
                            'file' => $file,
                            'test_name' => $content['test_name'] ?? $file,
                            'created_at' => $content['created_at'] ?? '',
                            'teacher' => $content['teacher'] ?? '',
                            'total_questions' => $content['total_questions'] ?? 0,
                            'total_points' => $content['total_points'] ?? 0,
                            'time_limit' => $content['time_limit'] ?? 45,
                            'approved' => $content['approved'] ?? false,
                            'approved_at' => $content['approved_at'] ?? '',
                            'questions' => $content['questions'] ?? [],
                        ];
                    }
                }
            ?>
            <ul class="nav nav-tabs" id="examTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab">Quản Lý Đề Kiểm Tra</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Chọn Thủ Công</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto" type="button" role="tab">Tự Động</button>
                </li>               
            </ul>
            <div class="tab-content" id="examTabsContent">
                <div class="tab-pane fade" id="manual" role="tabpanel">
        <form id="manualForm" class="mt-3">
            <input type="hidden" name="action" value="create_manual">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="test_name_manual" class="form-label">Tên đề kiểm tra</label>
                    <input type="text" id="test_name_manual" name="test_name" class="form-control" required placeholder="Nhập tên đề kiểm tra" />
                </div>
                <div class="col-md-3">
                    <label for="time_limit_manual" class="form-label">Thời gian (phút)</label>
                    <input type="number" id="time_limit_manual" name="time_limit" class="form-control" value="45" min="1" max="180" required>
                </div>
                <div class="col-md-3">
                    <label for="total_points_manual" class="form-label">Số điểm</label>
                    <input type="number" id="total_points_manual" name="total_points" class="form-control" value="10" min="1" max="100" required>
                </div>

            </div>
            <div class="mt-3">
                <p>Chọn câu hỏi:</p>
                <p>Số câu hỏi đã chọn: <span id="selectedCount">0</span></p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Câu hỏi</th>
                                <th>Mức độ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $index => $q): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_questions[]" value="<?php echo $index; ?>" class="question-checkbox"></td>
                                    <td><?php echo htmlspecialchars($q['question']); ?></td>
                                    <td><?php echo $q['level']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">Tạo Đề Thi Thủ Công</button>
            </div>
        </form>
                </div>
                <div class="tab-pane fade" id="auto" role="tabpanel">
        <form id="autoForm" class="mt-3">
            <input type="hidden" name="action" value="create_auto">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="test_name_auto" class="form-label">Tên đề kiểm tra</label>
                    <input type="text" id="test_name_auto" name="test_name" class="form-control" required placeholder="Nhập tên đề kiểm tra" />
                </div>
                <div class="col-md-3">
                    <label for="time_limit_auto" class="form-label">Thời gian (phút)</label>
                    <input type="number" id="time_limit_auto" name="time_limit" class="form-control" value="45" min="1" max="180" required>
                </div>
                <div class="col-md-3">
                    <label for="total_points_auto" class="form-label">Số điểm</label>
                    <input type="number" id="total_points_auto" name="total_points" class="form-control" value="10" min="1" max="100" required>
                </div>

            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="num_questions" class="form-label">Số câu hỏi</label>
                    <input type="number" id="num_questions" name="num_questions" class="form-control" value="20" min="1" max="50" required>
                </div>
                <div class="col-md-3">
                    <label for="nb_percent" class="form-label">NB (%)</label>
                    <input type="number" id="nb_percent" name="nb_percent" class="form-control" value="50" min="0" max="100" required>
                </div>
                <div class="col-md-3">
                    <label for="th_percent" class="form-label">TH (%)</label>
                    <input type="number" id="th_percent" name="th_percent" class="form-control" value="40" min="0" max="100" required>
                </div>
                <div class="col-md-3">
                    <label for="vd_percent" class="form-label">VD (%)</label>
                    <input type="number" id="vd_percent" name="vd_percent" class="form-control" value="10" min="0" max="100" required>
                </div>
            </div>
            <div class="mt-3">
                <p id="autoDesc">Tự động tạo đề với 20 câu: 50% NB, 40% TH, 10% VD</p>
                <button type="submit" class="btn btn-primary d-block">Tạo Đề Thi Tự Động</button>
            </div>
        </form>
                </div>
                <div class="tab-pane fade show active" id="manage" role="tabpanel">
                    <h4 class="mt-3">Danh sách đề kiểm tra đã tạo</h4>
                    <?php if (count($examsList) === 0): ?>
                        <p>Chưa có đề kiểm tra nào được tạo.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tên đề kiểm tra</th>
                                        <th>Ngày tạo</th>
                                        <th>Giáo viên</th>
                                        <th>Số câu hỏi</th>
                                        <th>Tổng điểm</th>
                                        <th>Thời gian</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($examsList as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['test_name']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['teacher']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['total_questions']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['total_points']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['time_limit']); ?> phút</td>
                                            <td><?php echo $exam['approved'] ? 'Đã duyệt' : 'Chưa duyệt'; ?></td>
                                            <td>
                                                <button class="btn btn-info btn-sm view-exam-btn" data-file="<?php echo htmlspecialchars($exam['file']); ?>" data-exam='<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES); ?>'>Xem</button>
                                                <button class="btn btn-warning btn-sm edit-exam-btn" data-file="<?php echo htmlspecialchars($exam['file']); ?>" data-exam='<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES); ?>' data-questions='<?php echo htmlspecialchars(json_encode($questions), ENT_QUOTES); ?>'>Sửa</button>
                                                <?php if (!$exam['approved']): ?>
                                                    <button class="btn btn-success btn-sm approve-exam-btn" data-file="<?php echo htmlspecialchars($exam['file']); ?>">Duyệt</button>
                                                <?php endif; ?>
                                                <button class="btn btn-danger btn-sm delete-exam-btn" data-file="<?php echo htmlspecialchars($exam['file']); ?>">Xóa</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($examData): ?>
                <div class="mt-5">
                    <h3>📋 Xem Đề Thi Đã Tạo</h3>
                    <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars($examData['created_at']); ?></p>
                    <p><strong>Giáo viên:</strong> <?php echo htmlspecialchars($examData['teacher']); ?></p>
                    <p><strong>Tổng số câu:</strong> <?php echo $examData['total_questions']; ?> (<?php echo $examData['total_points']; ?> điểm)</p>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Câu hỏi</th>
                                    <th>Mức độ</th>
                                    <th>Đáp án đúng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examData['questions'] as $idx => $q): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($q['question']); ?></td>
                                        <td><?php echo htmlspecialchars($q['level']); ?></td>
                                        <td><?php echo htmlspecialchars(renderCorrect($q['correct'], $q['options'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success">✅ Duyệt Đề Thi (Cho học sinh thi)</button>
                        <button class="btn btn-warning ms-2">✏️ Chỉnh Sửa Đề Thi</button>
                        <button class="btn btn-danger ms-2">🗑️ Xóa Đề Thi</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Vui lòng chọn khối và môn học để tạo đề thi.</div>
        <?php endif; ?>
    </div>

    <!-- Modal for viewing exam -->
    <div class="modal fade" id="viewExamModal" tabindex="-1" aria-labelledby="viewExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewExamModalLabel">Xem Đề Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="examModalBody">
                    <!-- Exam details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing exam -->
    <div class="modal fade" id="editExamModal" tabindex="-1" aria-labelledby="editExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editExamModalLabel">Sửa Đề Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editExamForm">
                        <input type="hidden" name="action" value="edit_exam">
                        <input type="hidden" name="file" id="editFile">
                        <input type="hidden" name="grade" value="<?php echo $selectedGrade; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $selectedSubjectId; ?>">
                        <div class="mb-3">
                            <label for="editTestName" class="form-label">Tên đề kiểm tra</label>
                            <input type="text" id="editTestName" name="test_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTimeLimit" class="form-label">Thời gian (phút)</label>
                            <input type="number" id="editTimeLimit" name="time_limit" class="form-control" min="1" max="180" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTotalPoints" class="form-label">Số điểm</label>
                            <input type="number" id="editTotalPoints" name="total_points" class="form-control" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <p><strong>Câu hỏi hiện tại (đánh dấu để xóa):</strong></p>
                            <div id="editQuestionsList">
                                <!-- Current questions will be listed here with remove checkboxes -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <p><strong>Thêm câu hỏi mới:</strong></p>
                            <div id="addQuestionsList">
                                <!-- Available questions will be listed here with add checkboxes -->
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

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
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <script>
        function renderCorrect(correct, options) {
            if (Array.isArray(correct)) {
                return correct.map(c => {
                    if (typeof c === 'number') {
                        const letters = ['A', 'B', 'C', 'D'];
                        return letters[c] || c;
                    }
                    return c;
                }).join(', ');
            }
            if (typeof correct === 'number') {
                const letters = ['A', 'B', 'C', 'D'];
                return letters[correct] || correct;
            }
            return correct;
        }

        document.addEventListener('DOMContentLoaded', function() {
            function updateSelectedCount() {
                const checked = document.querySelectorAll('.question-checkbox:checked');
                document.getElementById('selectedCount').textContent = checked.length;
            }

            // Select all checkbox
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.question-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSelectedCount();
            });

            // Limit to 20 and update count
            document.querySelectorAll('.question-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const checked = document.querySelectorAll('.question-checkbox:checked');
                    if (checked.length > 20) {
                        this.checked = false;
                        alert('Chỉ được chọn tối đa 20 câu hỏi');
                    }
                    updateSelectedCount();
                });
            });

            // Submit manual form
            document.getElementById('manualForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const selected = formData.getAll('selected_questions[]');
                if (selected.length === 0) {
                    alert('Vui lòng chọn ít nhất một câu hỏi');
                    return;
                }
                if (selected.length > 20) {
                    alert('Không được chọn quá 20 câu hỏi');
                    return;
                }
                submitForm(formData);
            });

            // Submit auto form
            document.getElementById('autoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                submitForm(formData);
            });

            // Update auto description
            function updateAutoDesc() {
                const num = document.getElementById('num_questions').value;
                const nb = document.getElementById('nb_percent').value;
                const th = document.getElementById('th_percent').value;
                const vd = document.getElementById('vd_percent').value;
                document.getElementById('autoDesc').textContent = `Tự động tạo đề với ${num} câu: ${nb}% NB, ${th}% TH, ${vd}% VD`;
            }
            updateAutoDesc();
            document.getElementById('num_questions').addEventListener('input', updateAutoDesc);
            document.getElementById('nb_percent').addEventListener('input', updateAutoDesc);
            document.getElementById('th_percent').addEventListener('input', updateAutoDesc);
            document.getElementById('vd_percent').addEventListener('input', updateAutoDesc);

            // Handle view, edit, delete buttons in manage tab
            document.querySelectorAll('.view-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const examData = JSON.parse(this.getAttribute('data-exam'));
                    const modalBody = document.getElementById('examModalBody');
                    modalBody.innerHTML = `
                        <h5>${examData.test_name}</h5>
                        <p><strong>Ngày tạo:</strong> ${examData.created_at}</p>
                        <p><strong>Giáo viên:</strong> ${examData.teacher}</p>
                        <p><strong>Tổng số câu:</strong> ${examData.total_questions} (${examData.total_points} điểm)</p>
                        <p><strong>Thời gian:</strong> ${examData.time_limit} phút</p>
                        <p><strong>Trạng thái:</strong> ${examData.approved ? 'Đã duyệt' : 'Chưa duyệt'}</p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Câu hỏi</th>
                                        <th>Mức độ</th>
                                        <th>Đáp án đúng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${examData.questions.map((q, idx) => `
                                        <tr>
                                            <td>${idx + 1}</td>
                                            <td>${q.question}</td>
                                            <td>${q.level}</td>
                                            <td>${renderCorrect(q.correct, q.options)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    MathJax.typeset([modalBody]);
                    const modal = new bootstrap.Modal(document.getElementById('viewExamModal'));
                    modal.show();
                });
            });
            document.querySelectorAll('.edit-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const file = this.getAttribute('data-file');
                    const examData = JSON.parse(this.getAttribute('data-exam').replace(/"/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&'));
                    const allQuestions = JSON.parse(this.getAttribute('data-questions').replace(/"/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&'));
                    // Populate the edit modal
                    document.getElementById('editFile').value = file;
                    document.getElementById('editTestName').value = examData.test_name;
                    document.getElementById('editTimeLimit').value = examData.time_limit;
                    document.getElementById('editTotalPoints').value = examData.total_points;
                    // List current questions with remove checkboxes
                    const questionsList = document.getElementById('editQuestionsList');
                    questionsList.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllRemove"></th>
                                        <th>#</th>
                                        <th>Câu hỏi</th>
                                        <th>Mức độ</th>
                                        <th>Đáp án đúng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${examData.questions.map((q, idx) => `
                                        <tr>
                                            <td><input type="checkbox" name="removed_questions[]" value="${idx}" class="remove-checkbox"></td>
                                            <td>${idx + 1}</td>
                                            <td>${q.question}</td>
                                            <td>${q.level}</td>
                                            <td>${renderCorrect(q.correct, q.options)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    // List available questions for adding
                    const currentQuestions = examData.questions.map(q => JSON.stringify(q));
                    const availableQuestions = allQuestions.filter(q => !currentQuestions.includes(JSON.stringify(q)));
                    const addQuestionsList = document.getElementById('addQuestionsList');
                    addQuestionsList.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllAdd"></th>
                                        <th>#</th>
                                        <th>Câu hỏi</th>
                                        <th>Mức độ</th>
                                        <th>Đáp án đúng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${availableQuestions.map((q, idx) => `
                                        <tr>
                                            <td><input type="checkbox" name="added_questions[]" value="${allQuestions.indexOf(q)}" class="add-checkbox"></td>
                                            <td>${idx + 1}</td>
                                            <td>${q.question}</td>
                                            <td>${q.level}</td>
                                            <td>${renderCorrect(q.correct, q.options)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    // Handle select all for remove
                    document.getElementById('selectAllRemove').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.remove-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                    // Handle select all for add
                    document.getElementById('selectAllAdd').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.add-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                    MathJax.typeset([questionsList, addQuestionsList]);
                    const modal = new bootstrap.Modal(document.getElementById('editExamModal'));
                    modal.show();
                });
            });

            // Submit edit exam form
            document.getElementById('editExamForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                submitForm(formData);
            });
            document.querySelectorAll('.approve-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const file = this.getAttribute('data-file');
                    if (confirm('Bạn có chắc muốn duyệt đề thi này?')) {
                        fetch(window.location.href, {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'approve_exam',
                                file: file,
                                grade: '<?php echo $selectedGrade; ?>',
                                subject_id: '<?php echo $selectedSubjectId; ?>'
                            }),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Duyệt đề thi thành công');
                                location.reload();
                            } else {
                                alert('Lỗi khi duyệt đề thi: ' + result.message);
                            }
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi duyệt đề thi');
                            console.error(error);
                        });
                    }
                });
            });
            document.querySelectorAll('.delete-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const file = this.getAttribute('data-file');
                    if (confirm('Bạn có chắc muốn xóa đề thi này?')) {
                        fetch(window.location.href, {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'delete_exam',
                                file: file,
                                grade: '<?php echo $selectedGrade; ?>',
                                subject_id: '<?php echo $selectedSubjectId; ?>'
                            }),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Xóa đề thi thành công');
                                location.reload();
                            } else {
                                alert('Lỗi khi xóa đề thi: ' + result.message);
                            }
                        })
                        .catch(error => {
                            alert('Có lỗi xảy ra khi xóa đề thi');
                            console.error(error);
                        });
                    }
                });
            });

            function submitForm(formData) {
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert('Lỗi: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('Có lỗi xảy ra');
                    console.error(error);
                });
            }
        });
    </script>
<?php include '../includes/footer.php'; ?>
