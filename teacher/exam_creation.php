<?php
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/common_functions.php';
include '../includes/premium_helper.php';
include '../includes/exam_helper.php';

// Check Premium status for exam limit
$username = $_SESSION['username'];
$isPremiumUser = isPremiumUser($username);
$examLimit = $isPremiumUser ? 999 : 10; // Non-Premium: 10 exams max, Premium: unlimited

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

// Load system config for semester
$configFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$defaultSemester = $systemConfig['semester']['current'] ?? 'hk2';
$semesters = ['hk1', 'hk2'];
$semesterLabels = [
    'hk1' => 'Học kì 1',
    'hk2' => 'Học kì 2'
];
$selectedSemester = $_GET['semester'] ?? $defaultSemester;
$currentSemester = $selectedSemester;

$questions = [];
$questionsData = []; // Keep hierarchical structure
$topicsMap = []; // For dropdown
$lessonsMap = []; // For dropdown
$examData = null;

if ($selectedGrade && $selectedSubjectId) {
    // Try semester-based structure first (new)
    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
    
    // Fallback to old structure if new doesn't exist
    if (!file_exists($questionsFile)) {
        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
    }
    
    if (file_exists($questionsFile)) {
        $questionsData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (is_array($questionsData)) {
            foreach ($questionsData as $topicIndex => $topicData) {
                $topic = $topicData['topic'] ?? '';
                $lesson = $topicData['lesson'] ?? '';
                $lessonQuestions = $topicData['questions'] ?? [];
                
                // Build topics and lessons maps
                if ($topic && !in_array($topic, $topicsMap)) {
                    $topicsMap[] = $topic;
                }
                $lessonsMap[$topic][] = $lesson;
                
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

// Handle POST for creating / managing exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json; charset=utf-8');

    try {
        // Validate grade and subject_id from POST
        $postGrade = $_POST['grade'] ?? '';
        $postSubjectId = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        
        if (!$postGrade || !in_array($postGrade, $availableGrades)) {
            throw new Exception("Khối không hợp lệ");
        }
        
        if (!$postSubjectId || !in_array($postSubjectId, $assignedSubjectIds)) {
            throw new Exception("Môn học không hợp lệ");
        }
        
        // Use POST values for exam creation
        $examGrade = $postGrade;
        $examSubjectId = $postSubjectId;

        $postSemester = $_POST['semester'] ?? $currentSemester;
        // Reload questions specifically for the POST requested grade, subject and semester
        $questions = [];
        $questionsFile = __DIR__ . "/questions/{$examGrade}/{$postSemester}/subject_{$examSubjectId}.json";
        if (!file_exists($questionsFile)) {
            $questionsFile = __DIR__ . "/questions/{$examGrade}/subject_{$examSubjectId}.json";
        }
        if (file_exists($questionsFile)) {
            $questionsData = json_decode(file_get_contents($questionsFile), true) ?: [];
            if (is_array($questionsData)) {
                foreach ($questionsData as $topicIndex => $topicData) {
                    $lessonQuestions = $topicData['questions'] ?? [];
                    foreach ($lessonQuestions as $idx => $q) {
                        $questions[] = [
                            'data' => $q,
                            'topic' => $topicData['topic'] ?? '',
                            'lesson' => $topicData['lesson'] ?? '',
                            'topicIndex' => $topicIndex,
                            'index' => $idx
                        ];
                    }
                }
            }
        }
        
        // Check exam count limit for non-Premium users
        if (!$isPremiumUser) {
            $examsDir = __DIR__ . "/exams/{$examGrade}/subject_{$examSubjectId}";
            if (is_dir($examsDir)) {
                $existingExams = glob($examsDir . '/*.json');
                if (count($existingExams) >= $examLimit) {
                    throw new Exception("Bạn đã đạt giới hạn {$examLimit} đề thi. Vui lòng nâng cấp Premium để tạo không giới hạn đề thi.");
                }
            }
        }

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
            $customPoints = $_POST['question_points'] ?? [];
            $singlePPQ = isset($_POST['single_ppq']) ? (float)$_POST['single_ppq'] : 0.25;
            $tfPPQ = isset($_POST['tf_ppq']) ? (float)$_POST['tf_ppq'] : 0.25;
            $essayPPQ = isset($_POST['essay_ppq']) ? (float)$_POST['essay_ppq'] : 1.0;
            $calcTotalPoints = 0.0;

            foreach ($selectedIds as $idx) {
                if (isset($questions[$idx])) {
                    $qData = $questions[$idx]['data'];
                    $qType = $qData['type'] ?? 'single';
                    if ($qType === 'single' || $qType === 'multiple') {
                        $qData['points'] = $singlePPQ;
                        $calcTotalPoints += $singlePPQ;
                    } elseif ($qType === 'true_false_multiple') {
                        $qData['points'] = $tfPPQ;
                        $calcTotalPoints += $tfPPQ;
                    } elseif ($qType === 'essay') {
                        if (isset($customPoints[$idx]) && is_numeric($customPoints[$idx]) && (float)$customPoints[$idx] > 0) {
                            $qData['points'] = (float)$customPoints[$idx];
                        } else {
                            $qData['points'] = $essayPPQ > 0 ? $essayPPQ : (float)($qData['points'] ?? 1.0);
                        }
                        $calcTotalPoints += $qData['points'];
                    }
                    $selectedQuestions[] = $qData;
                }
            }
            if (empty($selectedQuestions)) {
                throw new Exception("Không có câu hỏi hợp lệ được chọn hoặc danh sách câu hỏi trống");
            }

            // Kỳ đánh giá quyết định loại câu hỏi được phép
            $examCategory = in_array($_POST['exam_category'] ?? 'regular', ['regular', 'midterm', 'final'], true)
                ? ($_POST['exam_category'] ?? 'regular') : 'regular';
            if (!exam_allows_new_types($examCategory)) {
                foreach ($selectedQuestions as $_sq) {
                    $_sqType = $_sq['type'] ?? 'single';
                    if ($_sqType === 'true_false_multiple' || $_sqType === 'essay') {
                        throw new Exception('Kiểm tra thường xuyên chỉ gồm câu trắc nghiệm. Hãy chuyển Kỳ đánh giá sang "Giữa kỳ" hoặc "Cuối kỳ" để dùng câu Đúng/Sai và Tự luận, hoặc bỏ các câu đã đánh dấu loại này.');
                    }
                }
            }

            // Save exam
            $examsDir = __DIR__ . "/exams/{$examGrade}/subject_{$examSubjectId}";
            if (!is_dir($examsDir)) {
                if (!mkdir($examsDir, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục đề thi");
                }
            }
            $safeTestName = create_slug($testName);
            $totalPoints = isset($_POST['total_points']) && (float)$_POST['total_points'] > 0
                ? (float)$_POST['total_points']
                : round($calcTotalPoints, 2);
            $subjectName = '';
            foreach ($subjects as $subj) {
                if ($subj['id'] == $examSubjectId) {
                    $subjectName = $subj['name'];
                    break;
                }
            }
            $subjectSlug = create_slug($subjectName);
            $subjectAbbrev = strtoupper(substr($subjectSlug ?: $safeTestName, 0, 3));
            if ($subjectAbbrev === '') $subjectAbbrev = 'SUB';
            $testId = $subjectAbbrev . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
            $examFile = $examsDir . "/{$testId}.json";

            $qCount = count($selectedQuestions);
            $pointsPerQ = $qCount > 0 ? round($totalPoints / $qCount, 2) : 0;

            $examData = [
                'test_id' => $testId,
                'test_name' => $testName,
                'subject_id' => $examSubjectId,
                'exam_type' => $_POST['exam_type'] ?? 'practice',
                'exam_category' => $examCategory ?? 'regular',
                'questions' => $selectedQuestions,
                'created_at' => date('Y-m-d H:i:s'),
                'teacher' => $username,
                'total_questions' => $qCount,
                'points_per_question' => $pointsPerQ,
                'total_points' => $totalPoints,
                'time_limit' => (int)($_POST['time_limit'] ?? 45),
                'max_violations' => (int)($_POST['max_violations'] ?? 6)
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

            // Kỳ đánh giá quyết định loại câu hỏi được phép đưa vào đề
            $examCategory = in_array($_POST['exam_category'] ?? 'regular', ['regular', 'midterm', 'final'], true)
                ? ($_POST['exam_category'] ?? 'regular') : 'regular';
            $allowedTypes = exam_allows_new_types($examCategory)
                ? ['single', 'multiple', 'true_false_multiple', 'essay']
                : ['single', 'multiple'];

            if (empty($questions)) {
                throw new Exception("Ngân hàng câu hỏi của môn học này đang trống, không thể tạo đề tự động.");
            }

            $nb = [];
            $th = [];
            $vd = [];
            $skippedTypes = 0;
            foreach ($questions as $q) {
                $qType = $q['data']['type'] ?? 'single';
                if (!in_array($qType, $allowedTypes, true)) {
                    $skippedTypes++;
                    continue;
                }
                $level = $q['data']['level'] ?? 'NB';
                if ($level === 'NB') $nb[] = $q['data'];
                elseif ($level === 'TH') $th[] = $q['data'];
                elseif ($level === 'VD' || $level === 'VDC') $vd[] = $q['data'];
            }
            if (empty($nb) && empty($th) && empty($vd)) {
                throw new Exception($skippedTypes > 0
                    ? 'Kỳ đánh giá "Thường xuyên" chỉ gồm trắc nghiệm nhưng ngân hàng đang không có câu phù hợp. Hãy chọn Giữa kỳ/Cuối kỳ hoặc bổ sung câu trắc nghiệm.'
                    : 'Không có đủ câu hỏi phù hợp để tạo đề tự động.');
            }
            $nb_count = (int)round($num_questions * $nb_percent / 100);
            $th_count = (int)round($num_questions * $th_percent / 100);
            $vd_count = $num_questions - $nb_count - $th_count;
            if ($vd_count < 0) $vd_count = 0;
            $selectedQuestions = [];
            if ($nb_count > 0 && !empty($nb)) {
                shuffle($nb);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($nb, 0, min($nb_count, count($nb))));
            }
            if ($th_count > 0 && !empty($th)) {
                shuffle($th);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($th, 0, min($th_count, count($th))));
            }
            if ($vd_count > 0 && !empty($vd)) {
                shuffle($vd);
                $selectedQuestions = array_merge($selectedQuestions, array_slice($vd, 0, min($vd_count, count($vd))));
            }
            if (count($selectedQuestions) < $num_questions) {
                $allPool = array_merge($nb, $th, $vd);
                $usedQuestions = array_map(fn($q) => $q['question'] ?? '', $selectedQuestions);
                $unselected = array_filter($allPool, fn($q) => !in_array($q['question'] ?? '', $usedQuestions, true));
                if (!empty($unselected)) {
                    shuffle($unselected);
                    $remaining = $num_questions - count($selectedQuestions);
                    $selectedQuestions = array_merge($selectedQuestions, array_slice($unselected, 0, $remaining));
                }
            }
            shuffle($selectedQuestions);

            if (empty($selectedQuestions)) {
                throw new Exception("Không có đủ câu hỏi phù hợp để tạo đề tự động.");
            }

            // Save exam
            $examsDir = __DIR__ . "/exams/{$examGrade}/subject_{$examSubjectId}";
            if (!is_dir($examsDir)) {
                if (!mkdir($examsDir, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục đề thi");
                }
            }
            if (!is_writable($examsDir)) {
                throw new Exception("Thư mục đề thi không thể ghi: " . $examsDir);
            }
            $safeTestName = create_slug($testName);
            $singlePPQ = isset($_POST['single_ppq']) ? (float)$_POST['single_ppq'] : 0.25;
            $tfPPQ = isset($_POST['tf_ppq']) ? (float)$_POST['tf_ppq'] : 0.25;
            $essayPPQ = isset($_POST['essay_ppq']) ? (float)$_POST['essay_ppq'] : 1.0;
            $calcTotalPoints = 0.0;
            foreach ($selectedQuestions as &$sq) {
                $sqType = $sq['type'] ?? 'single';
                if ($sqType === 'single' || $sqType === 'multiple') {
                    $sq['points'] = $singlePPQ;
                    $calcTotalPoints += $singlePPQ;
                } elseif ($sqType === 'true_false_multiple') {
                    $sq['points'] = $tfPPQ;
                    $calcTotalPoints += $tfPPQ;
                } elseif ($sqType === 'essay') {
                    $sq['points'] = (float)($sq['points'] ?? $essayPPQ);
                    $calcTotalPoints += $sq['points'];
                }
            }
            unset($sq);
            $totalPoints = isset($_POST['total_points']) && (float)$_POST['total_points'] > 0
                ? (float)$_POST['total_points']
                : round($calcTotalPoints, 2);
            $subjectName = '';
            foreach ($subjects as $subj) {
                if ($subj['id'] == $examSubjectId) {
                    $subjectName = $subj['name'];
                    break;
                }
            }
            $subjectSlug = create_slug($subjectName);
            $subjectAbbrev = strtoupper(substr($subjectSlug ?: $safeTestName, 0, 3));
            if ($subjectAbbrev === '') $subjectAbbrev = 'SUB';
            $testId = $subjectAbbrev . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
            $examFile = $examsDir . "/{$testId}.json";

            $qCount = count($selectedQuestions);
            $pointsPerQ = $qCount > 0 ? round($totalPoints / $qCount, 2) : 0;

            $examData = [
                'test_id' => $testId,
                'test_name' => $testName,
                'subject_id' => $examSubjectId,
                'exam_type' => $_POST['exam_type'] ?? 'practice',
                'exam_category' => $examCategory ?? 'regular',
                'questions' => $selectedQuestions,
                'created_at' => date('Y-m-d H:i:s'),
                'teacher' => $username,
                'total_questions' => $qCount,
                'points_per_question' => $pointsPerQ,
                'total_points' => $totalPoints,
                'time_limit' => (int)($_POST['time_limit'] ?? 45),
                'max_violations' => (int)($_POST['max_violations'] ?? 6)
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
            $editCategory = in_array($_POST['exam_category'] ?? '', ['regular', 'midterm', 'final'], true) ? $_POST['exam_category'] : null;
            $editSemester = $_POST['semester'] ?? $currentSemester;
            $removed_questions = $_POST['removed_questions'] ?? [];
            $added_questions = $_POST['added_questions'] ?? [];

            // Load questions for the grade, subject and semester
            $questions = [];
            $questionsFile = __DIR__ . "/questions/{$grade}/{$editSemester}/subject_{$subjectId}.json";
            if (!file_exists($questionsFile)) {
                $questionsFile = __DIR__ . "/questions/{$grade}/subject_{$subjectId}.json";
            }
            
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

            $qCount = count($examData['questions']);
            $pointsPerQ = $qCount > 0 ? round($totalPoints / $qCount, 2) : 0;

            // Update metadata
            if ($editCategory) { $examData['exam_category'] = $editCategory; }
            $examData['test_name'] = $testName;
            $examData['time_limit'] = $timeLimit;
            $examData['total_questions'] = $qCount;
            $examData['points_per_question'] = $pointsPerQ;
            $examData['total_points'] = $totalPoints;
            $examData['max_violations'] = (int)($_POST['max_violations'] ?? ($examData['max_violations'] ?? 6));
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
            $examData = json_decode(file_get_contents($examFile), true);
            if (!$examData) {
                throw new Exception("Không thể đọc dữ liệu đề thi");
            }
            if ($examData['teacher'] !== $username) {
                throw new Exception("Bạn không có quyền xóa đề thi này");
            }
            if (unlink($examFile)) {
                echo json_encode(['success' => true, 'message' => 'Đề thi đã được xóa thành công']);
            } else {
                throw new Exception("Không thể xóa đề thi");
            }
        } else {
            throw new Exception("Hành động không hợp lệ");
        }

    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

?>
<link rel="stylesheet" href="assets/exam_creation.css?v=20260805">
<div class="exam-workspace">
    <header class="exam-hero hero-banner">
        <div>
            <span class="exam-eyebrow badge-glass"><i class="bi bi-journal-check"></i> Công cụ chuyên môn</span>
            <h1>Ra đề kiểm tra</h1>
            <p>Xây dựng, rà soát và duyệt đề theo đúng khối lớp, môn học và mục tiêu đánh giá.</p>
        </div>
        <?php if ($selectedGrade && $selectedSubjectId): ?>
            <button type="button" class="btn btn-primary px-4" id="openExamBuilderBtn">
                <i class="bi bi-plus-lg me-2"></i>Tạo đề mới
            </button>
        <?php endif; ?>
    </header>

    <section class="exam-context-panel" aria-labelledby="examContextTitle">
        <div class="section-header mb-3">
            <span class="sh-icon"><i class="bi bi-funnel"></i></span>
            <div>
                <h3 id="examContextTitle">Phạm vi làm việc</h3>
                <p>Chọn khối lớp và môn học để giới hạn dữ liệu câu hỏi và đề thi.</p>
            </div>
        </div>
        <form method="get" class="row g-3 align-items-end mt-1">
            <?php if (isset($_GET['return'])): ?>
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($_GET['return']); ?>">
            <?php endif; ?>
            <div class="col-lg-3 col-md-4">
                <label for="grade" class="form-label">Khối lớp</label>
                <select id="grade" name="grade" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Chọn khối lớp --</option>
                    <?php foreach ($availableGrades as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php if ($g === $selectedGrade) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-4">
                <label for="subject_id" class="form-label">Môn học</label>
                <select id="subject_id" name="subject_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Chọn môn học --</option>
                    <?php foreach ($assignedSubjects as $subj): ?>
                        <option value="<?php echo (int) $subj['id']; ?>" <?php if ($subj['id'] == $selectedSubjectId) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($subj['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-4">
                <label for="semester" class="form-label">Học kì</label>
                <select id="semester" name="semester" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo htmlspecialchars($sem); ?>" <?php if ($sem === $selectedSemester) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($semesterLabels[$sem] ?? $sem); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <div class="exam-context-note"><i class="bi bi-info-circle"></i> Dữ liệu câu hỏi và đề thi theo phạm vi và học kì đã chọn.</div>
            </div>
        </form>
    </section>

    <?php if ($selectedGrade && $selectedSubjectId): ?>
        <?php
            $examsDir = __DIR__ . "/exams/{$selectedGrade}/subject_{$selectedSubjectId}";
            $examFiles = [];
            if (is_dir($examsDir)) {
                foreach (scandir($examsDir) as $file) {
                    if (preg_match('/\.json$/', $file)) {
                        $examFiles[] = $file;
                    }
                }
            }

            $examsList = [];
            foreach ($examFiles as $file) {
                $path = $examsDir . '/' . $file;
                $content = json_decode(file_get_contents($path), true);
                if ($content && ($content['teacher'] ?? '') === $username) {
                    $examsList[] = [
                        'file' => $file,
                        'test_name' => $content['test_name'] ?? $file,
                        'created_at' => $content['created_at'] ?? '',
                        'teacher' => $content['teacher'] ?? '',
                        'exam_type' => $content['exam_type'] ?? 'official',
                        'exam_category' => $content['exam_category'] ?? 'regular',
                        'total_questions' => $content['total_questions'] ?? 0,
                        'total_points' => $content['total_points'] ?? 0,
                        'time_limit' => $content['time_limit'] ?? 45,
                        'max_violations' => $content['max_violations'] ?? 6,
                        'approved' => $content['approved'] ?? false,
                        'approved_at' => $content['approved_at'] ?? '',
                        'questions' => $content['questions'] ?? [],
                    ];
                }
            }
            usort($examsList, static function ($left, $right) {
                return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
            });
            $approvedExamCount = count(array_filter($examsList, static fn ($exam) => !empty($exam['approved'])));
            $draftExamCount = count($examsList) - $approvedExamCount;
            $currentSubjectName = '';
            foreach ($assignedSubjects as $subjectItem) {
                if ((string) $subjectItem['id'] === (string) $selectedSubjectId) {
                    $currentSubjectName = $subjectItem['name'];
                    break;
                }
            }
        ?>

        <section class="stat-row mb-4" aria-label="Thống kê nhanh">
            <div class="stat-card"><span class="stat-icon primary"><i class="bi bi-question-circle"></i></span><div><div class="stat-value"><?php echo count($questions); ?></div><div class="stat-label">Câu hỏi trong ngân hàng</div></div></div>
            <div class="stat-card"><span class="stat-icon info"><i class="bi bi-files"></i></span><div><div class="stat-value"><?php echo count($examsList); ?></div><div class="stat-label">Đề đã tạo</div></div></div>
            <div class="stat-card"><span class="stat-icon success"><i class="bi bi-patch-check"></i></span><div><div class="stat-value"><?php echo $approvedExamCount; ?></div><div class="stat-label">Đề đã duyệt</div></div></div>
            <div class="stat-card"><span class="stat-icon warning"><i class="bi bi-pencil-square"></i></span><div><div class="stat-value"><?php echo $draftExamCount; ?></div><div class="stat-label">Bản nháp cần rà soát</div></div></div>
        </section>

        <?php if (!$isPremiumUser && count($examsList) >= $examLimit): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div><strong>Đã đạt giới hạn tạo đề.</strong> Tài khoản hiện có <?php echo count($examsList); ?>/<?php echo $examLimit; ?> đề. <a href="premium_activation.php" class="alert-link">Nâng cấp Premium</a> để tiếp tục.</div>
            </div>
        <?php endif; ?>

        <ul class="nav exam-main-nav" id="examTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab"><i class="bi bi-list-check"></i> Danh sách đề</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab"><i class="bi bi-ui-checks-grid"></i> Chọn câu thủ công</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto" type="button" role="tab"><i class="bi bi-magic"></i> Tạo đề tự động</button></li>
        </ul>

        <div class="tab-content" id="examTabsContent">
            <div class="tab-pane fade show active" id="manage" role="tabpanel" aria-labelledby="manage-tab">
                <section class="exam-panel">
                    <div class="exam-panel-header">
                        <div class="section-header mb-0">
                            <span class="sh-icon alt"><i class="bi bi-archive"></i></span>
                            <div><h3>Kho đề kiểm tra</h3><p><?php echo htmlspecialchars($gradeLabels[$selectedGrade] ?? ucfirst($selectedGrade)); ?> · <?php echo htmlspecialchars($currentSubjectName); ?></p></div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-open-builder="manual"><i class="bi bi-plus-lg me-1"></i>Tạo đề</button>
                    </div>
                    <div class="exam-panel-body">
                        <?php if (count($examsList) === 0): ?>
                            <div class="exam-empty"><div class="exam-empty-icon"><i class="bi bi-file-earmark-plus"></i></div><h3>Chưa có đề kiểm tra</h3><p>Bắt đầu bằng cách chọn câu hỏi thủ công hoặc để hệ thống phân bổ câu hỏi theo mức độ nhận thức.</p><button type="button" class="btn btn-primary mt-3" data-open-builder="manual">Tạo đề đầu tiên</button></div>
                        <?php else: ?>
                            <div class="exam-toolbar">
                                <div class="exam-search"><i class="bi bi-search"></i><input type="search" id="examSearch" class="form-control" placeholder="Tìm theo tên đề..."></div>
                                <select id="examTypeFilter" class="form-select" aria-label="Lọc loại đề"><option value="">Tất cả loại đề</option><option value="official">Kiểm tra</option><option value="practice">Luyện tập</option></select>
                                <select id="examStatusFilter" class="form-select" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option><option value="approved">Đã duyệt</option><option value="draft">Chưa duyệt</option></select>
                            </div>
                            <div class="exam-table-wrap">
                                <table class="table exam-table">
                                    <thead><tr><th>Tên đề</th><th>Loại</th><th>Cấu trúc</th><th>Thời gian</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                                    <tbody id="examListBody">
                                        <?php foreach ($examsList as $exam): ?>
<?php $examType = $exam['exam_type'] ?? 'official'; $examCat = exam_category_label($exam['exam_category'] ?? 'regular'); ?>
                                            <tr class="exam-row" data-name="<?php echo htmlspecialchars(mb_strtolower($exam['test_name'], 'UTF-8')); ?>" data-type="<?php echo htmlspecialchars($examType); ?>" data-status="<?php echo $exam['approved'] ? 'approved' : 'draft'; ?>">
                                                <td><span class="exam-name"><?php echo htmlspecialchars($exam['test_name']); ?></span><span class="exam-subtext">Tạo ngày <?php echo htmlspecialchars($exam['created_at'] ?: 'Chưa xác định'); ?></span></td>
                                                <td><span class="exam-type"><i class="bi <?php echo $examType === 'official' ? 'bi-clipboard-check' : 'bi-bullseye'; ?>"></i><?php echo $examType === 'official' ? 'Kiểm tra' : 'Luyện tập'; ?></span><span class="exam-subtext"><?php echo htmlspecialchars($examCat); ?></span></td>
                                                <td><strong><?php echo (int) $exam['total_questions']; ?> câu</strong><span class="exam-subtext"><?php echo htmlspecialchars($exam['total_points']); ?> điểm</span></td>
                                                <td><?php echo (int) $exam['time_limit']; ?> phút</td>
                                                <td><span class="badge badge-soft <?php echo $exam['approved'] ? 'badge-soft-success' : 'badge-soft-warning'; ?>"><i class="bi <?php echo $exam['approved'] ? 'bi-check-circle-fill' : 'bi-clock-fill'; ?> me-1"></i><?php echo $exam['approved'] ? 'Đã duyệt' : 'Chưa duyệt'; ?></span></td>
                                                <td><div class="exam-actions justify-content-end">
                                                    <button class="btn btn-outline-primary btn-sm view-exam-btn" title="Xem đề" data-file="<?php echo htmlspecialchars($exam['file']); ?>" data-exam='<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES); ?>'><i class="bi bi-eye"></i></button>
                                                    <?php if ($exam['teacher'] === $username): ?>
                                                        <?php $questionsDataOnly = array_map(static fn ($q) => $q['data'], $questions); ?>
                                                        <button class="btn btn-outline-secondary btn-sm edit-exam-btn" title="Chỉnh sửa" data-file="<?php echo htmlspecialchars($exam['file']); ?>" data-exam='<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES); ?>' data-questions='<?php echo htmlspecialchars(json_encode($questionsDataOnly), ENT_QUOTES); ?>'><i class="bi bi-pencil"></i></button>
                                                        <?php if (!$exam['approved']): ?><button class="btn btn-outline-success btn-sm approve-exam-btn" title="Duyệt đề" data-file="<?php echo htmlspecialchars($exam['file']); ?>"><i class="bi bi-check-lg"></i></button><?php endif; ?>
                                                        <button class="btn btn-outline-danger btn-sm delete-exam-btn" title="Xóa đề" data-file="<?php echo htmlspecialchars($exam['file']); ?>"><i class="bi bi-trash"></i></button>
                                                    <?php endif; ?>
                                                </div></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="exam-empty py-4 d-none" id="examFilterEmpty"><div class="exam-empty-icon"><i class="bi bi-search"></i></div><h3>Không tìm thấy đề phù hợp</h3><p>Thử thay đổi từ khóa hoặc bộ lọc.</p></div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                <form id="manualForm">
                    <input type="hidden" name="action" value="create_manual"><input type="hidden" name="grade" value="<?php echo htmlspecialchars($selectedGrade); ?>"><input type="hidden" name="subject_id" value="<?php echo (int) $selectedSubjectId; ?>"><input type="hidden" name="semester" value="<?php echo htmlspecialchars($selectedSemester); ?>">
                    <div class="exam-hint-note"><i class="bi bi-ui-checks-grid"></i><p><strong>Chọn câu thủ công:</strong> Giáo viên chủ động lọc theo chủ đề, bài học và kiểm soát từng câu trước khi tạo đề.</p></div>
                    <div class="exam-form-intro">
                        <section class="exam-form-section">
                            <div class="exam-step-label"><span class="exam-step-number">1</span> Thông tin đề kiểm tra</div>
                            <div class="row g-3">
                                <div class="col-lg-6"><label for="test_name_manual" class="form-label">Tên đề kiểm tra</label><input type="text" id="test_name_manual" name="test_name" class="form-control" required placeholder="Ví dụ: Kiểm tra giữa học kỳ I"></div>
                                <div class="col-lg-3 col-md-4"><label for="exam_category_manual" class="form-label">Kỳ đánh giá</label><select id="exam_category_manual" name="exam_category" class="form-select"><option value="regular" selected>Thường xuyên</option><option value="midterm">Giữa kỳ</option><option value="final">Cuối kỳ</option></select><div class="form-text">Đúng/Sai &amp; Tự luận chỉ dùng cho Giữa/Cuối kỳ.</div></div>
                                <div class="col-lg-3 col-md-4"><label for="exam_type_manual" class="form-label">Hình thức</label><select id="exam_type_manual" name="exam_type" class="form-select" required><option value="official" selected>Kiểm tra</option><option value="practice">Luyện tập</option></select></div>
                                <div class="col-lg-3 col-md-4"><label for="time_limit_manual" class="form-label">Thời gian (phút)</label><input type="number" id="time_limit_manual" name="time_limit" class="form-control" value="45" min="1" max="180" required></div>
                                <div class="col-lg-3 col-md-4"><label for="total_points_manual" class="form-label">Thang điểm</label><input type="number" id="total_points_manual" name="total_points" class="form-control" value="10" min="1" max="100" required></div>
                                <div class="col-lg-3 col-md-4"><label for="max_violations_manual" class="form-label">Số lần vi phạm tối đa</label><input type="number" id="max_violations_manual" name="max_violations" class="form-control" value="6" min="1" max="50"><div class="form-text">Vi phạm đủ số lần sẽ tự động nộp bài (mặc định 6).</div></div>
                            </div>
                        </section>
                    </div>
                    <div class="exam-builder-grid">
                        <section class="exam-question-bank exam-form-section">
                            <div class="exam-step-label"><span class="exam-step-number">2</span> Chọn câu hỏi từ ngân hàng</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6"><label for="filterTopic" class="form-label">Chủ đề</label><select id="filterTopic" class="form-select"><option value="">Tất cả chủ đề</option><?php foreach ($topicsMap as $topic): ?><option value="<?php echo htmlspecialchars($topic); ?>"><?php echo htmlspecialchars($topic); ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-6"><label for="filterLesson" class="form-label">Bài học</label><select id="filterLesson" class="form-select" disabled><option value="">Chọn chủ đề trước</option></select></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2"><span class="text-muted small">Đang hiển thị <strong id="totalQuestions"><?php echo count($questions); ?></strong> câu</span><span class="text-muted small">Tối đa 20 câu/đề</span></div>
                            <div class="exam-question-scroll"><table class="table table-hover exam-question-table"><thead><tr><th><input type="checkbox" id="selectAll" aria-label="Chọn tất cả câu đang hiển thị"></th><th>Bài học</th><th>Câu hỏi</th><th>Đáp án</th><th>Mức độ</th></tr></thead><tbody id="questionsTableBody">
                                <?php foreach ($questions as $index => $q): $qType = $q['data']['type'] ?? 'single'; ?><tr data-topic="<?php echo htmlspecialchars($q['topic']); ?>" data-lesson="<?php echo htmlspecialchars($q['lesson']); ?>" data-qtype="<?php echo htmlspecialchars($qType); ?>"><td><input type="checkbox" name="selected_questions[]" value="<?php echo $index; ?>" class="question-checkbox"></td><td><?php echo htmlspecialchars($q['lesson']); ?></td><td><?php echo htmlspecialchars($q['data']['question']); if ($qType === 'true_false_multiple'): ?> <span class="exam-type-mini exam-type-tf">Đúng/Sai · <?php echo count($q['data']['items'] ?? []); ?> ý</span><?php elseif ($qType === 'essay'): ?> <span class="exam-type-mini exam-type-es">Tự luận</span><?php endif; ?></td><td><?php if ($qType === 'true_false_multiple') { $tfCorrect = 0; foreach (($q['data']['items'] ?? []) as $_it) { if (!empty($_it['correct'])) $tfCorrect++; } echo '<span class="text-muted">Đ: ' . (int)$tfCorrect . '/' . count($q['data']['items'] ?? []) . ' ý</span>'; } elseif ($qType === 'essay') { echo '<span class="text-muted">Chấm tay</span>'; } else { echo htmlspecialchars(renderCorrect($q['data']['correct'], $q['data']['options'])); } ?></td><td><span class="level-chip level-<?php echo strtolower($q['data']['level']); ?>"><?php echo htmlspecialchars($q['data']['level']); ?></span></td></tr><?php endforeach; ?>
                            </tbody></table></div>
                        </section>
                        <aside class="exam-selection-summary">
                            <div class="exam-summary-box">
                                <div class="exam-step-label mb-3"><span class="exam-step-number">3</span> Rà soát và tạo đề</div>
                                <div class="exam-summary-count"><span>Số câu đã chọn</span><strong><span id="selectedCount">0</span>/20</strong></div>
                                <div class="progress" role="progressbar" aria-label="Tiến độ chọn câu"><div class="progress-bar" id="selectedProgress" style="width: 0%"></div></div>
                                <div id="typeSummarySection" class="mt-3 mb-2" style="display:none;">
                                    <table class="w-100 small" style="border-collapse:separate;border-spacing:0 4px;">
                                        <thead>
                                            <tr class="text-muted">
                                                <th class="fw-normal ps-1" style="width:40%">Loại câu</th>
                                                <th class="fw-normal text-center" style="width:15%">SL</th>
                                                <th class="fw-normal text-center" style="width:25%">Điểm/câu</th>
                                                <th class="fw-normal text-end pe-1" style="width:20%">Tổng</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="ps-1 py-1 rounded-start" style="background:#f0f4ff;"><i class="bi bi-check-circle text-primary me-1"></i>Trắc nghiệm</td>
                                                <td class="py-1 text-center fw-bold" style="background:#f0f4ff;"><span id="singleCount">0</span></td>
                                                <td class="py-1 text-center" style="background:#f0f4ff;"><input type="number" id="singlePPQ" name="single_ppq" class="form-control form-control-sm text-center ppq-input" value="0.25" min="0" max="10" step="0.05" style="width:70px;display:inline-block;padding:2px 4px;font-size:0.8rem;"></td>
                                                <td class="py-1 text-end pe-1 fw-bold rounded-end" style="background:#f0f4ff;"><span id="singlePoints">0</span>đ</td>
                                            </tr>
                                            <tr>
                                                <td class="ps-1 py-1 rounded-start" style="background:#fff7ed;"><i class="bi bi-toggle2-on text-warning me-1"></i>Đúng/Sai</td>
                                                <td class="py-1 text-center fw-bold" style="background:#fff7ed;"><span id="tfCount">0</span></td>
                                                <td class="py-1 text-center" style="background:#fff7ed;"><input type="number" id="tfPPQ" name="tf_ppq" class="form-control form-control-sm text-center ppq-input" value="0.25" min="0" max="10" step="0.05" style="width:70px;display:inline-block;padding:2px 4px;font-size:0.8rem;"></td>
                                                <td class="py-1 text-end pe-1 fw-bold rounded-end" style="background:#fff7ed;"><span id="tfPoints">0</span>đ</td>
                                            </tr>
                                            <tr>
                                                <td class="ps-1 py-1 rounded-start" style="background:#f0fdf4;"><i class="bi bi-pencil-square text-success me-1"></i>Tự luận</td>
                                                <td class="py-1 text-center fw-bold" style="background:#f0fdf4;"><span id="essayCount">0</span></td>
                                                <td class="py-1 text-center" style="background:#f0fdf4;"><input type="number" id="essayPPQ" name="essay_ppq" class="form-control form-control-sm text-center ppq-input" value="1.0" min="0" max="10" step="0.25" style="width:70px;display:inline-block;padding:2px 4px;font-size:0.8rem;"></td>
                                                <td class="py-1 text-end pe-1 fw-bold rounded-end" style="background:#f0fdf4;"><span id="essayPoints">0</span>đ</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="fw-bold ps-1 pt-2" style="border-top:2px solid #dee2e6;">Tổng điểm</td>
                                                <td class="fw-bold text-end pe-1 pt-2 text-primary" style="border-top:2px solid #dee2e6;"><span id="totalPoints">0</span>đ</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div id="selectedQuestionsSection" style="display:none;"><div id="selectedQuestionsList" class="exam-selected-list"></div></div>
                                <div id="manualEmptySelection" class="text-muted small mb-3">Chưa chọn câu hỏi nào. Dùng bộ lọc và đánh dấu các câu cần đưa vào đề.</div>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-file-earmark-plus me-2"></i>Tạo đề thủ công</button>
                            </div>
                        </aside>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="auto" role="tabpanel" aria-labelledby="auto-tab">
                <form id="autoForm">
                    <input type="hidden" name="action" value="create_auto"><input type="hidden" name="grade" value="<?php echo htmlspecialchars($selectedGrade); ?>"><input type="hidden" name="subject_id" value="<?php echo (int) $selectedSubjectId; ?>"><input type="hidden" name="semester" value="<?php echo htmlspecialchars($selectedSemester); ?>">
                    <div class="exam-hint-note"><i class="bi bi-magic"></i><p><strong>Tạo đề tự động:</strong> Hệ thống chọn ngẫu nhiên theo tỷ lệ nhận biết, thông hiểu và vận dụng do giáo viên thiết lập.</p></div>
                    <div class="exam-form-intro">
                        <section class="exam-form-section">
                            <div class="exam-step-label"><span class="exam-step-number">1</span> Thông tin đề kiểm tra</div>
                            <div class="row g-3">
                                <div class="col-lg-6"><label for="test_name_auto" class="form-label">Tên đề kiểm tra</label><input type="text" id="test_name_auto" name="test_name" class="form-control" required placeholder="Ví dụ: Ôn tập cuối học kỳ I"></div>
                                <div class="col-lg-3 col-md-4"><label for="exam_category_auto" class="form-label">Kỳ đánh giá</label><select id="exam_category_auto" name="exam_category" class="form-select"><option value="regular" selected>Thường xuyên</option><option value="midterm">Giữa kỳ</option><option value="final">Cuối kỳ</option></select><div class="form-text">Đúng/Sai &amp; Tự luận chỉ dùng cho Giữa/Cuối kỳ.</div></div>
                                <div class="col-lg-3 col-md-4"><label for="exam_type_auto" class="form-label">Hình thức</label><select id="exam_type_auto" name="exam_type" class="form-select" required><option value="official" selected>Kiểm tra</option><option value="practice">Luyện tập</option></select></div>
                                <div class="col-lg-3 col-md-4"><label for="time_limit_auto" class="form-label">Thời gian (phút)</label><input type="number" id="time_limit_auto" name="time_limit" class="form-control" value="45" min="1" max="180" required></div>
                                <div class="col-lg-3 col-md-4"><label for="total_points_auto" class="form-label">Thang điểm</label><input type="number" id="total_points_auto" name="total_points" class="form-control" value="10" min="1" max="100" required></div>
                                <div class="col-lg-3 col-md-4"><label for="max_violations_auto" class="form-label">Số lần vi phạm tối đa</label><input type="number" id="max_violations_auto" name="max_violations" class="form-control" value="6" min="1" max="50"><div class="form-text">Vi phạm đủ số lần sẽ tự động nộp bài (mặc định 6).</div></div>
                            </div>
                        </section>
                    </div>
                    <div class="exam-auto-layout">
                        <section class="exam-form-section">
                            <div class="exam-step-label"><span class="exam-step-number">2</span> Thiết lập cấu trúc nhận thức</div>
                            <div class="row g-3 mb-3"><div class="col-md-4"><label for="num_questions" class="form-label">Tổng số câu hỏi</label><input type="number" id="num_questions" name="num_questions" class="form-control" value="20" min="1" max="50" required></div></div>
                            <div class="exam-distribution">
                                <div class="exam-distribution-card"><strong>Nhận biết (NB)</strong><small>Kiến thức và nhận diện cơ bản</small><label for="nb_percent" class="form-label mt-3">Tỷ lệ (%)</label><input type="number" id="nb_percent" name="nb_percent" class="form-control" value="50" min="0" max="100" required></div>
                                <div class="exam-distribution-card"><strong>Thông hiểu (TH)</strong><small>Giải thích và liên hệ kiến thức</small><label for="th_percent" class="form-label mt-3">Tỷ lệ (%)</label><input type="number" id="th_percent" name="th_percent" class="form-control" value="40" min="0" max="100" required></div>
                                <div class="exam-distribution-card"><strong>Vận dụng (VD)</strong><small>Áp dụng kiến thức vào tình huống</small><label for="vd_percent" class="form-label mt-3">Tỷ lệ (%)</label><input type="number" id="vd_percent" name="vd_percent" class="form-control" value="10" min="0" max="100" required></div>
                            </div>
                        </section>
                        <aside class="exam-auto-preview">
                            <div class="exam-step-label"><span class="exam-step-number">3</span> Kiểm tra cấu trúc</div>
                            <h3>Tóm tắt đề dự kiến</h3>
                            <div class="exam-preview-row"><span>Nhận biết</span><strong id="nbCountPreview">10 câu</strong></div><div class="exam-preview-row"><span>Thông hiểu</span><strong id="thCountPreview">8 câu</strong></div><div class="exam-preview-row"><span>Vận dụng</span><strong id="vdCountPreview">2 câu</strong></div><div class="exam-preview-row"><span>Tổng cộng</span><strong id="totalCountPreview">20 câu</strong></div>
                            <div class="exam-percent-state" id="percentState">Tổng tỷ lệ: 100% - hợp lệ</div>
                            <p id="autoDesc" class="text-muted small my-3">Tự động tạo đề với 20 câu: 50% NB, 40% TH, 10% VD</p>
                            <button type="submit" class="btn btn-primary w-100" id="autoSubmitBtn"><i class="bi bi-stars me-2"></i>Tạo đề tự động</button>
                        </aside>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <section class="exam-panel mt-3"><div class="exam-empty"><div class="exam-empty-icon"><i class="bi bi-filter-square"></i></div><h2>Chọn phạm vi để bắt đầu</h2><p>Chọn khối lớp và môn học ở phía trên. Hệ thống sẽ tải đúng ngân hàng câu hỏi, danh sách đề và các công cụ phù hợp.</p></div></section>
    <?php endif; ?>
</div>

    <!-- Modal for viewing exam -->
    <div class="modal fade" id="viewExamModal" tabindex="-1" aria-labelledby="viewExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewExamModalLabel"><i class="bi bi-file-earmark-text me-2"></i>Xem đề kiểm tra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="examModalBody">
                    <!-- Exam details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing exam -->
    <div class="modal fade" id="editExamModal" tabindex="-1" aria-labelledby="editExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editExamModalLabel"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa đề kiểm tra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editExamForm">
                        <input type="hidden" name="action" value="edit_exam">
                        <input type="hidden" name="file" id="editFile">
                        <input type="hidden" name="grade" value="<?php echo $selectedGrade; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $selectedSubjectId; ?>">
                        <div class="exam-form-section mb-3">
                            <div class="exam-step-label"><span class="exam-step-number">1</span> Thông tin chung</div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label for="editTestName" class="form-label">Tên đề kiểm tra</label>
                                    <input type="text" id="editTestName" name="test_name" class="form-control" required>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="editExamCategory" class="form-label">Kỳ đánh giá</label>
                                    <select id="editExamCategory" name="exam_category" class="form-select">
                                        <option value="regular">Thường xuyên</option>
                                        <option value="midterm">Giữa kỳ</option>
                                        <option value="final">Cuối kỳ</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="editTimeLimit" class="form-label">Thời gian (phút)</label>
                                    <input type="number" id="editTimeLimit" name="time_limit" class="form-control" min="1" max="180" required>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="editTotalPoints" class="form-label">Thang điểm</label>
                                    <input type="number" id="editTotalPoints" name="total_points" class="form-control" min="1" max="100" required>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="editMaxViolations" class="form-label">Số lần vi phạm tối đa</label>
                                    <input type="number" id="editMaxViolations" name="max_violations" class="form-control" min="1" max="50" value="6">
                                    <div class="form-text">Vi phạm đủ số lần sẽ tự động nộp bài (mặc định 6).</div>
                                </div>
                            </div>
                        </div>
                        <div class="exam-form-section mb-3">
                            <div class="exam-step-label"><span class="exam-step-number">2</span> Câu hỏi hiện tại</div>
                            <p class="text-muted small">Đánh dấu những câu cần loại khỏi đề.</p>
                            <div id="editQuestionsList">
                                <!-- Current questions will be listed here with remove checkboxes -->
                            </div>
                        </div>
                        <div class="exam-form-section mb-3">
                            <div class="exam-step-label"><span class="exam-step-number">3</span> Bổ sung câu hỏi</div>
                            <p class="text-muted small">Chọn thêm câu hỏi từ ngân hàng hiện tại.</p>
                            <div id="addQuestionsList">
                                <!-- Available questions will be listed here with add checkboxes -->
                            </div>
                        </div>
                        <div class="text-end"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-2"></i>Lưu thay đổi</button></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for confirm delete -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">⚠️ Xác nhận xóa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc muốn xóa đề thi này không?</p>
                    <p class="text-muted small">Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for confirm approve -->
    <div class="modal fade" id="confirmApproveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">✅ Xác nhận duyệt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc muốn duyệt đề thi này không?</p>
                    <p class="text-muted small">Sau khi duyệt, học sinh sẽ có thể thi đề này.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" id="confirmApproveBtn">Duyệt</button>
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
        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        function renderCorrect(correct, options, question) {
            if (question) {
                if (question.type === 'true_false_multiple' && Array.isArray(question.items)) {
                    return question.items.map(it => {
                        const isT = it.correct === true || it.correct === 'true' || it.correct === 1;
                        const badgeBg = isT ? 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;' : 'background:#fee2e2;color:#991b1b;border:1px solid #fecaca;';
                        const text = isT ? 'Đ' : 'S';
                        return `<span class="badge me-1 mb-1 font-monospace" style="${badgeBg};font-size:0.75rem;padding:3px 6px;">${it.label || '•'}: ${text}</span>`;
                    }).join(' ');
                }
                if (question.type === 'essay') {
                    const pts = question.points ? ` (${question.points}đ)` : '';
                    let ansSnippet = '';
                    if (question.suggested_answer) {
                        const safeAns = String(question.suggested_answer).replace(/"/g, '&quot;');
                        ansSnippet = `<div class="small text-muted mt-1" style="font-size:0.75rem;max-width:320px;line-height:1.3;" title="${safeAns}"><i class="bi bi-info-circle text-primary me-1"></i>${question.suggested_answer}</div>`;
                    }
                    return `<div><span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.75rem;"><i class="bi bi-pencil-square me-1"></i>Tự luận${pts} · Chấm tay</span>${ansSnippet}</div>`;
                }
            }
            if (Array.isArray(correct)) {
                return correct.map(c => {
                    if (typeof c === 'number') {
                        const letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                        return `<span class="badge bg-primary font-monospace" style="font-size:0.75rem;padding:3px 7px;">${letters[c] || c}</span>`;
                    }
                    return `<span class="badge bg-primary font-monospace" style="font-size:0.75rem;padding:3px 7px;">${c}</span>`;
                }).join(' ');
            }
            if (typeof correct === 'number') {
                const letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                return `<span class="badge bg-primary font-monospace" style="font-size:0.75rem;padding:3px 7px;">${letters[correct] || correct}</span>`;
            }
            if (correct !== undefined && correct !== null && correct !== '') {
                return `<span class="badge bg-primary font-monospace" style="font-size:0.75rem;padding:3px 7px;">${correct}</span>`;
            }
            return '<span class="text-muted small fst-italic">Chưa có ĐA</span>';
        }

        // Pass lessons data to JavaScript
        const lessonsMap = <?php echo json_encode($lessonsMap); ?>;
        const questionsData = <?php echo json_encode($questions); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-activate tab based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            const successMsg = urlParams.get('success');

            function openBuilder(mode = 'manual') {
                const targetButton = document.getElementById(mode === 'auto' ? 'auto-tab' : 'manual-tab');
                if (!targetButton) {
                    return;
                }
                bootstrap.Tab.getOrCreateInstance(targetButton).show();
                document.getElementById('examTabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            document.getElementById('openExamBuilderBtn')?.addEventListener('click', () => openBuilder('manual'));
            document.querySelectorAll('[data-open-builder]').forEach(button => {
                button.addEventListener('click', () => openBuilder(button.dataset.openBuilder || 'manual'));
            });

            const examSearch = document.getElementById('examSearch');
            const examTypeFilter = document.getElementById('examTypeFilter');
            const examStatusFilter = document.getElementById('examStatusFilter');
            const examRows = Array.from(document.querySelectorAll('.exam-row'));

            function filterExamRows() {
                const keyword = (examSearch?.value || '').trim().toLocaleLowerCase('vi');
                const type = examTypeFilter?.value || '';
                const status = examStatusFilter?.value || '';
                let visibleCount = 0;

                examRows.forEach(row => {
                    const matchesKeyword = !keyword || (row.dataset.name || '').includes(keyword);
                    const matchesType = !type || row.dataset.type === type;
                    const matchesStatus = !status || row.dataset.status === status;
                    const isVisible = matchesKeyword && matchesType && matchesStatus;
                    row.classList.toggle('d-none', !isVisible);
                    if (isVisible) {
                        visibleCount++;
                    }
                });

                document.getElementById('examFilterEmpty')?.classList.toggle('d-none', visibleCount !== 0);
            }

            examSearch?.addEventListener('input', filterExamRows);
            examTypeFilter?.addEventListener('change', filterExamRows);
            examStatusFilter?.addEventListener('change', filterExamRows);
            
            if (activeTab === 'manage') {
                // Activate the manage tab
                const manageTabBtn = document.getElementById('manage-tab');
                const manageTabPane = document.getElementById('manage');
                
                if (manageTabBtn && manageTabPane) {
                    // Deactivate all tabs
                    document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });
                    
                    // Activate manage tab
                    manageTabBtn.classList.add('active');
                    manageTabPane.classList.add('show', 'active');
                }
            }
            
            // Show success message if redirected after creating exam
            if (successMsg === 'created') {
                setTimeout(() => {
                    showToast('✅ Đề thi đã được tạo thành công! Bạn có thể xem lại và duyệt đề trong danh sách bên dưới.', 'success');
                }, 500);
            }
            
            const customEssayPoints = {};

            function updateSelectedCount(source = '') {
                const allChecked = document.querySelectorAll('#questionsTableBody .question-checkbox:checked');
                const totalVisible = document.querySelectorAll('#questionsTableBody tr:not([style*="display: none"])').length;
                const selectedCount = document.getElementById('selectedCount');
                const totalQuestions = document.getElementById('totalQuestions');
                const selectedProgress = document.getElementById('selectedProgress');
                const emptySelection = document.getElementById('manualEmptySelection');
                const typeSummarySection = document.getElementById('typeSummarySection');

                if (selectedCount) selectedCount.textContent = allChecked.length;
                if (totalQuestions) totalQuestions.textContent = totalVisible;
                if (selectedProgress) selectedProgress.style.width = `${Math.min(100, allChecked.length * 5)}%`;
                if (emptySelection) emptySelection.classList.toggle('d-none', allChecked.length > 0);

                // Calculate type breakdown
                let singleCount = 0, tfCount = 0, essayCount = 0;
                let essayPoints = 0;

                const singlePPQ = parseFloat(document.getElementById('singlePPQ')?.value) || 0;
                const tfPPQ = parseFloat(document.getElementById('tfPPQ')?.value) || 0;
                const essayPPQ = parseFloat(document.getElementById('essayPPQ')?.value) || 0;

                allChecked.forEach(checkbox => {
                    const index = parseInt(checkbox.value);
                    const question = questionsData[index];
                    if (!question) return;
                    const qType = question.data.type;
                    if (qType === 'single' || qType === 'multiple') {
                        singleCount++;
                    } else if (qType === 'true_false_multiple') {
                        tfCount++;
                    } else if (qType === 'essay') {
                        essayCount++;
                        // If source === 'essayPPQ', sync all essay inputs with the new common PPQ
                        if (source === 'essayPPQ') {
                            customEssayPoints[index] = essayPPQ;
                        } else if (customEssayPoints[index] === undefined) {
                            customEssayPoints[index] = essayPPQ > 0 ? essayPPQ : (parseFloat(question.data.points) || 1.0);
                        }
                        essayPoints += customEssayPoints[index];
                    }
                });

                // Calculate subtotals
                const singlePoints = Math.round(singleCount * singlePPQ * 100) / 100;
                const tfPoints = Math.round(tfCount * tfPPQ * 100) / 100;
                essayPoints = Math.round(essayPoints * 100) / 100;
                const totalPts = Math.round((singlePoints + tfPoints + essayPoints) * 100) / 100;

                // Update UI
                const singleCountEl = document.getElementById('singleCount');
                const tfCountEl = document.getElementById('tfCount');
                const essayCountEl = document.getElementById('essayCount');
                const singlePointsEl = document.getElementById('singlePoints');
                const tfPointsEl = document.getElementById('tfPoints');
                const essayPointsEl = document.getElementById('essayPoints');
                const totalPointsEl = document.getElementById('totalPoints');

                if (singleCountEl) singleCountEl.textContent = singleCount;
                if (tfCountEl) tfCountEl.textContent = tfCount;
                if (essayCountEl) essayCountEl.textContent = essayCount;
                if (singlePointsEl) singlePointsEl.textContent = singlePoints;
                if (tfPointsEl) tfPointsEl.textContent = tfPoints;
                if (essayPointsEl) essayPointsEl.textContent = essayPoints;
                if (totalPointsEl) totalPointsEl.textContent = totalPts;
                const totalPointsInput = document.getElementById('total_points_manual');
                if (totalPointsInput && totalPts > 0) {
                    totalPointsInput.value = totalPts;
                }

                if (typeSummarySection) {
                    typeSummarySection.style.display = allChecked.length > 0 ? 'block' : 'none';
                }
                
                // Update selected questions list
                if (source !== 'customPointInput') {
                    updateSelectedQuestionsList();
                }
            }
            
            // Recalculate when points-per-question inputs change
            document.getElementById('essayPPQ')?.addEventListener('input', function() {
                updateSelectedCount('essayPPQ');
            });
            document.getElementById('singlePPQ')?.addEventListener('input', function() {
                updateSelectedCount('singlePPQ');
            });
            document.getElementById('tfPPQ')?.addEventListener('input', function() {
                updateSelectedCount('tfPPQ');
            });

            function updateSelectedQuestionsList() {
                const allChecked = document.querySelectorAll('#questionsTableBody .question-checkbox:checked');
                const section = document.getElementById('selectedQuestionsSection');
                const list = document.getElementById('selectedQuestionsList');
                
                if (allChecked.length === 0) {
                    section.style.display = 'none';
                    list.innerHTML = '';
                    return;
                }
                
                section.style.display = 'block';
                const selectedByTopic = {};
                
                allChecked.forEach(checkbox => {
                    const index = parseInt(checkbox.value);
                    const question = questionsData[index];
                    const topic = question.topic;
                    const qType = question.data.type || 'single';
                    
                    if (!selectedByTopic[topic]) {
                        selectedByTopic[topic] = [];
                    }
                    selectedByTopic[topic].push({
                        index: index,
                        question: question.data.question,
                        lesson: question.lesson,
                        level: question.data.level,
                        type: qType,
                        points: customEssayPoints[index] !== undefined ? customEssayPoints[index] : (parseFloat(question.data.points) || 1.0)
                    });
                });
                
                let html = '';
                Object.keys(selectedByTopic).forEach(topic => {
                    html += `<div class="mb-1 small fw-bold text-dark"><i class="bi bi-folder2-open text-primary me-1"></i>${topic} (${selectedByTopic[topic].length} câu)</div>`;
                    html += '<div class="d-flex flex-column gap-1 mb-3">';
                    selectedByTopic[topic].forEach(item => {
                        const isEssay = item.type === 'essay';
                        const isTF = item.type === 'true_false_multiple';
                        const typeBadge = isEssay
                            ? '<span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.68rem;">Tự luận</span>'
                            : (isTF ? '<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:0.68rem;">Đúng/Sai</span>' : '<span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;font-size:0.68rem;">Trắc nghiệm</span>');
                        
                        const pointInput = isEssay
                            ? `<div class="d-flex align-items-center gap-1"><input type="number" name="question_points[${item.index}]" class="form-control form-control-sm text-center custom-essay-input" data-index="${item.index}" value="${item.points}" min="0.25" max="10" step="0.25" style="width:58px;padding:2px 4px;font-size:0.75rem;height:24px;" title="Chỉnh điểm riêng cho câu này"><span class="small text-muted" style="font-size:0.7rem;">đ</span></div>`
                            : '';

                        html += `
                            <div class="d-flex align-items-center justify-content-between p-2 rounded bg-white border" style="font-size:0.78rem;">
                                <div class="d-flex align-items-center flex-grow-1 me-2 text-truncate">
                                    <span class="me-1">${typeBadge}</span>
                                    <span class="text-truncate" title="${item.question}">${item.lesson} · <span class="badge bg-secondary" style="font-size:0.62rem;">${item.level}</span></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    ${pointInput}
                                    <button type="button" class="btn-close" style="font-size:0.55rem;" onclick="removeQuestion(${item.index});" title="Bỏ chọn câu này"></button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                });
                
                list.innerHTML = html;

                // Bind change events to custom essay point inputs
                list.querySelectorAll('.custom-essay-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const idx = parseInt(this.dataset.index);
                        const val = parseFloat(this.value) || 0;
                        customEssayPoints[idx] = val;
                        updateSelectedCount('customPointInput');
                    });
                });
            }
            
            // Global function to remove question from selection
            window.removeQuestion = function(index) {
                const checkbox = document.querySelector(`#questionsTableBody input[value="${index}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                    // Trigger change event manually
                    const event = new Event('change', { bubbles: true });
                    checkbox.dispatchEvent(event);
                }
            };

            // Filter by topic (only if element exists)
            const filterTopicEl = document.getElementById('filterTopic');
            if (filterTopicEl) {
                filterTopicEl.addEventListener('change', function() {
                    const selectedTopic = this.value;
                    const filterLesson = document.getElementById('filterLesson');
                    const rows = document.querySelectorAll('#questionsTableBody tr');
                    
                    // Update lessons dropdown
                    filterLesson.innerHTML = '<option value="">-- Tất cả bài học --</option>';
                    if (selectedTopic && lessonsMap[selectedTopic]) {
                        lessonsMap[selectedTopic].forEach(lesson => {
                            const option = document.createElement('option');
                            option.value = lesson;
                            option.textContent = lesson;
                            filterLesson.appendChild(option);
                        });
                        filterLesson.disabled = false;
                    } else {
                        filterLesson.disabled = true;
                    }
                    
                    // Filter rows by topic (keep checked state)
                    rows.forEach(row => {
                        const rowTopic = row.getAttribute('data-topic');
                        if (!selectedTopic || rowTopic === selectedTopic) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    updateSelectedCount();
                });
            }

            // Filter by lesson (only if element exists)
            const filterLessonEl = document.getElementById('filterLesson');
            if (filterLessonEl) {
                filterLessonEl.addEventListener('change', function() {
                    const selectedLesson = this.value;
                    const selectedTopic = document.getElementById('filterTopic').value;
                    const rows = document.querySelectorAll('#questionsTableBody tr');
                    
                    rows.forEach(row => {
                        const rowTopic = row.getAttribute('data-topic');
                        const rowLesson = row.getAttribute('data-lesson');
                        const topicMatch = !selectedTopic || rowTopic === selectedTopic;
                        const lessonMatch = !selectedLesson || rowLesson === selectedLesson;
                        
                        if (topicMatch && lessonMatch) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    updateSelectedCount();
                });
            }

            // Select all checkbox (only visible rows) - only if element exists
            const selectAllEl = document.getElementById('selectAll');
            if (selectAllEl) {
                selectAllEl.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('#questionsTableBody tr:not([style*="display: none"]) .question-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked && !cb.disabled);
                    updateSelectedCount();
                });
            }

            // Kỳ đánh giá: câu Đúng/Sai & Tự luận chỉ khả dụng khi chọn Giữa kỳ / Cuối kỳ
            function syncCategoryLocks() {
                const sel = document.getElementById('exam_category_manual');
                if (!sel) return;
                const allowNew = sel.value === 'midterm' || sel.value === 'final';
                document.querySelectorAll('#questionsTableBody tr[data-qtype="true_false_multiple"], #questionsTableBody tr[data-qtype="essay"]').forEach(tr => {
                    const cb = tr.querySelector('.question-checkbox');
                    if (!cb) return;
                    cb.disabled = !allowNew;
                    tr.classList.toggle('qtype-locked', !allowNew);
                    if (!allowNew && cb.checked) {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
            const categoryManualEl = document.getElementById('exam_category_manual');
            const categoryAutoEl = document.getElementById('exam_category_auto');
            if (categoryManualEl) categoryManualEl.addEventListener('change', syncCategoryLocks);
            if (categoryAutoEl) categoryAutoEl.addEventListener('change', () => {}); // auto lọc phía server
            syncCategoryLocks();

            // Limit to 20 and update count
            document.querySelectorAll('.question-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const checked = document.querySelectorAll('.question-checkbox:checked');
                    if (checked.length > 20) {
                        this.checked = false;
                        showToast('Chỉ được chọn tối đa 20 câu hỏi', 'warning');
                    }
                    updateSelectedCount();
                });
            });

            // Submit manual form (only if exists)
            const manualForm = document.getElementById('manualForm');
            if (manualForm) {
                manualForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const selected = formData.getAll('selected_questions[]');
                    if (selected.length === 0) {
                        showToast('Vui lòng chọn ít nhất một câu hỏi', 'warning');
                        return;
                    }
                    if (selected.length > 20) {
                        showToast('Không được chọn quá 20 câu hỏi', 'warning');
                        return;
                    }
                    submitForm(formData);
                });
            }

            // Submit auto form (only if exists)
            const autoForm = document.getElementById('autoForm');
            if (autoForm) {
                autoForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const totalPercent = Number(nbPercentEl?.value || 0) + Number(thPercentEl?.value || 0) + Number(vdPercentEl?.value || 0);
                    if (totalPercent !== 100) {
                        showToast('Tổng tỷ lệ NB, TH và VD phải bằng 100%', 'warning');
                        return;
                    }
                    const formData = new FormData(this);
                    submitForm(formData);
                });
            }

            // Update auto description (only if elements exist)
            const numQuestionsEl = document.getElementById('num_questions');
            const nbPercentEl = document.getElementById('nb_percent');
            const thPercentEl = document.getElementById('th_percent');
            const vdPercentEl = document.getElementById('vd_percent');
            const autoDescEl = document.getElementById('autoDesc');
            
            if (numQuestionsEl && nbPercentEl && thPercentEl && vdPercentEl && autoDescEl) {
                function updateAutoDesc() {
                    const num = Number(numQuestionsEl.value || 0);
                    const nb = Number(nbPercentEl.value || 0);
                    const th = Number(thPercentEl.value || 0);
                    const vd = Number(vdPercentEl.value || 0);
                    const totalPercent = nb + th + vd;
                    const nbCount = Math.round(num * nb / 100);
                    const thCount = Math.round(num * th / 100);
                    const vdCount = Math.max(0, num - nbCount - thCount);

                    autoDescEl.textContent = `Tự động tạo đề với ${num} câu: ${nb}% NB, ${th}% TH, ${vd}% VD`;
                    document.getElementById('nbCountPreview').textContent = `${nbCount} câu`;
                    document.getElementById('thCountPreview').textContent = `${thCount} câu`;
                    document.getElementById('vdCountPreview').textContent = `${vdCount} câu`;
                    document.getElementById('totalCountPreview').textContent = `${num} câu`;

                    const percentState = document.getElementById('percentState');
                    const submitButton = document.getElementById('autoSubmitBtn');
                    const isValid = totalPercent === 100;
                    percentState.textContent = isValid
                        ? 'Tổng tỷ lệ: 100% - hợp lệ'
                        : `Tổng tỷ lệ: ${totalPercent}% - cần điều chỉnh về 100%`;
                    percentState.classList.toggle('is-invalid', !isValid);
                    submitButton.disabled = !isValid;
                }
                updateAutoDesc();
                numQuestionsEl.addEventListener('input', updateAutoDesc);
                nbPercentEl.addEventListener('input', updateAutoDesc);
                thPercentEl.addEventListener('input', updateAutoDesc);
                vdPercentEl.addEventListener('input', updateAutoDesc);
            }

            // Handle view, edit, delete buttons in manage tab
            document.querySelectorAll('.view-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const examData = JSON.parse(this.getAttribute('data-exam'));
                    const modalBody = document.getElementById('examModalBody');
                    const catLabel = {regular: 'Thường xuyên', midterm: 'Giữa kỳ', final: 'Cuối kỳ'}[examData.exam_category || 'regular'] || 'Thường xuyên';
                    const typeLabel = (examData.exam_type || 'official') === 'official' ? 'Kiểm tra' : 'Luyện tập';
                    modalBody.innerHTML = `
                        <div class="p-3 mb-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2" style="border-bottom:1px solid #e2e8f0;">
                                <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i>${examData.test_name}</h5>
                                <span class="badge ${examData.approved ? 'bg-success' : 'bg-warning text-dark'} px-3 py-1" style="font-size:0.8rem;">
                                    <i class="bi ${examData.approved ? 'bi-check-circle-fill' : 'bi-clock-fill'} me-1"></i>${examData.approved ? 'Đã duyệt' : 'Chưa duyệt'}
                                </span>
                            </div>
                            <div class="row g-2 text-dark" style="font-size:0.82rem;">
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-calendar-event text-primary fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Ngày tạo</div><div class="fw-semibold">${examData.created_at || 'Chưa xác định'}</div></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-tag text-info fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Kỳ đánh giá · Hình thức</div><div class="fw-semibold">${catLabel} · ${typeLabel}</div></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-ui-checks text-success fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Cấu trúc đề</div><div class="fw-semibold text-success">${examData.total_questions} câu · ${examData.total_points} điểm</div></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-stopwatch text-warning fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Thời gian làm bài</div><div class="fw-semibold">${examData.time_limit} phút</div></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-shield-exclamation text-danger fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Giới hạn vi phạm</div><div class="fw-semibold">${examData.max_violations || 6} lần · Tự nộp bài</div></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="d-flex align-items-center bg-white p-2 rounded border">
                                        <i class="bi bi-person-badge text-secondary fs-5 me-2"></i>
                                        <div><div class="text-muted" style="font-size:0.72rem;">Giáo viên tạo đề</div><div class="fw-semibold">${examData.teacher || '---'}</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-ol me-1 text-primary"></i>Danh sách câu hỏi trong đề (${(examData.questions || []).length} câu)</h6>
                        </div>
                        <div class="table-responsive exam-table-wrap">
                            <table class="table exam-table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;text-align:center;">#</th>
                                        <th style="width:55%;">Nội dung câu hỏi</th>
                                        <th style="width:10%;text-align:center;">Mức độ</th>
                                        <th style="width:30%;">Đáp án đúng / Hướng dẫn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${(examData.questions || []).map((q, idx) => {
                                        const typeBadge = q.type === 'true_false_multiple'
                                            ? '<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">Đúng/Sai</span>'
                                            : (q.type === 'essay'
                                                ? '<span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;">Tự luận</span>'
                                                : '<span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;">Trắc nghiệm</span>');
                                        const levelBadge = `<span class="level-chip level-${(q.level || 'nb').toLowerCase()}">${q.level || 'NB'}</span>`;
                                        return `
                                        <tr>
                                            <td class="text-center fw-bold text-muted">${idx + 1}</td>
                                            <td>
                                                <div class="d-flex align-items-start gap-2">
                                                    <span class="mt-1">${typeBadge}</span>
                                                    <div class="flex-grow-1">${q.question || ''}</div>
                                                </div>
                                            </td>
                                            <td class="text-center">${levelBadge}</td>
                                            <td>${renderCorrect(q.correct, q.options, q)}</td>
                                        </tr>
                                    `}).join('')}
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
                    const catEl = document.getElementById('editExamCategory');
                    if (catEl) catEl.value = examData.exam_category || 'regular';
                    document.getElementById('editTimeLimit').value = examData.time_limit;
                    document.getElementById('editTotalPoints').value = examData.total_points;
                    document.getElementById('editMaxViolations').value = examData.max_violations || 6;
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
                                    ${examData.questions.map((q, idx) => {
        const typeBadge = q.type === 'true_false_multiple' ? '<span class="badge bg-warning text-dark me-1">Đúng/Sai</span>' : (q.type === 'essay' ? '<span class="badge bg-success me-1">Tự luận</span>' : '<span class="badge bg-primary me-1">Trắc nghiệm</span>');
        return `
        <tr>
            <td><input type="checkbox" name="removed_questions[]" value="${idx}" class="remove-checkbox"></td>
            <td>${idx + 1}</td>
            <td>${typeBadge}${q.question}</td>
            <td><span class="badge bg-secondary">${q.level || 'NB'}</span></td>
            <td>${renderCorrect(q.correct, q.options, q)}</td>
        </tr>
    `}).join('')}
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
                                    ${availableQuestions.map((q, idx) => {
        const typeBadge = q.type === 'true_false_multiple' ? '<span class="badge bg-warning text-dark me-1">Đúng/Sai</span>' : (q.type === 'essay' ? '<span class="badge bg-success me-1">Tự luận</span>' : '<span class="badge bg-primary me-1">Trắc nghiệm</span>');
        return `
        <tr>
            <td><input type="checkbox" name="added_questions[]" value="${allQuestions.indexOf(q)}" class="add-checkbox"></td>
            <td>${idx + 1}</td>
            <td>${typeBadge}${q.question}</td>
            <td><span class="badge bg-secondary">${q.level || 'NB'}</span></td>
            <td>${renderCorrect(q.correct, q.options, q)}</td>
        </tr>
    `}).join('')}
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
                    const modal = new bootstrap.Modal(document.getElementById('confirmApproveModal'));
                    const confirmBtn = document.getElementById('confirmApproveBtn');
                    
                    // Remove old event listeners
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                    
                    newConfirmBtn.addEventListener('click', function() {
                        modal.hide();
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
                                showToast('Duyệt đề thi thành công', 'success');
                                location.reload();
                            } else {
                                showToast('Lỗi khi duyệt đề thi: ' + result.message, 'danger');
                            }
                        })
                        .catch(error => {
                            showToast('Có lỗi xảy ra khi duyệt đề thi', 'danger');
                            console.error(error);
                        });
                    });
                    
                    modal.show();
                });
            });
            document.querySelectorAll('.delete-exam-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const file = this.getAttribute('data-file');
                    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    
                    // Remove old event listeners
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
                    
                    newConfirmBtn.addEventListener('click', function() {
                        modal.hide();
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
                                showToast('Xóa đề thi thành công', 'success');
                                location.reload();
                            } else {
                                showToast('Lỗi khi xóa đề thi: ' + result.message, 'danger');
                            }
                        })
                        .catch(error => {
                            showToast('Có lỗi xảy ra khi xóa đề thi', 'danger');
                            console.error(error);
                        });
                    });
                    
                    modal.show();
                });
            });

            function submitForm(formData) {
                console.log('Submitting form...');
                
                // Get grade and subject_id from form data for redirect
                const grade = formData.get('grade');
                const subjectId = formData.get('subject_id');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(result => {
                    console.log('Result:', result);
                    if (result.success) {
                        // Redirect back to exam_creation with correct grade and subject_id
                        showToast('Đề thi đã được tạo thành công! Đang chuyển về danh sách...', 'success');
                        
                        // Build redirect URL with grade and subject_id
                        const semester = formData.get('semester') || '';
                        const redirectUrl = `exam_creation.php?grade=${encodeURIComponent(grade)}&subject_id=${encodeURIComponent(subjectId)}&semester=${encodeURIComponent(semester)}&success=created`;
                        window.location.href = redirectUrl;
                    } else {
                        showToast('Lỗi: ' + result.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showToast('Có lỗi xảy ra khi tạo đề thi', 'danger');
                });
            }
        });
    </script>
<?php include '../includes/footer.php'; ?>
