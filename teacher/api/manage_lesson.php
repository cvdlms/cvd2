<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CVD_TEACHER_SESSION');
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc không có quyền truy cập.']);
    exit;
}

$username = $_SESSION['username'];
$lessonsFile = __DIR__ . '/../../data/teacher_lessons.json';

// Ensure data folder and file exist
if (!file_exists($lessonsFile)) {
    file_put_contents($lessonsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $input = $_POST;
}

$action = $input['action'] ?? ($_GET['action'] ?? '');

function loadLessons($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveLessons($file, $data) {
    return file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

switch ($action) {
    case 'get_lessons':
        $lessons = loadLessons($lessonsFile);
        $userLessons = array_values(array_filter($lessons, function($item) use ($username) {
            return ($item['teacher_username'] ?? '') === $username;
        }));

        // Sort by updated_at descending
        usort($userLessons, function($a, $b) {
            $ta = strtotime($a['updated_at'] ?? $a['created_at'] ?? 0);
            $tb = strtotime($b['updated_at'] ?? $b['created_at'] ?? 0);
            return $tb - $ta;
        });

        echo json_encode([
            'success' => true,
            'lessons' => $userLessons
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_lesson':
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID bài học.']);
            exit;
        }

        $lessons = loadLessons($lessonsFile);
        $found = null;
        foreach ($lessons as $item) {
            if (($item['id'] ?? '') === $id && ($item['teacher_username'] ?? '') === $username) {
                $found = $item;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài học hoặc không có quyền truy cập.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'lesson' => $found
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'save_lesson':
        $title = trim($input['title'] ?? '');
        if ($title === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên bài học.']);
            exit;
        }

        $id = trim($input['id'] ?? '');
        $grade = trim($input['grade'] ?? 'khoi6');
        $semester = trim($input['semester'] ?? 'hk1');
        $subjectId = (int)($input['subject_id'] ?? 1);
        $subjectName = trim($input['subject_name'] ?? 'Môn học');
        $description = trim($input['description'] ?? '');
        $questions = is_array($input['questions'] ?? null) ? $input['questions'] : [];

        $lessons = loadLessons($lessonsFile);
        $now = date('Y-m-d H:i:s');

        if ($id === '') {
            // Create new
            $id = 'les_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
            $newLesson = [
                'id' => $id,
                'teacher_username' => $username,
                'title' => $title,
                'grade' => $grade,
                'semester' => $semester,
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'description' => $description,
                'questions' => $questions,
                'created_at' => $now,
                'updated_at' => $now
            ];
            $lessons[] = $newLesson;
        } else {
            // Update existing
            $index = -1;
            foreach ($lessons as $i => $item) {
                if (($item['id'] ?? '') === $id && ($item['teacher_username'] ?? '') === $username) {
                    $index = $i;
                    break;
                }
            }

            if ($index === -1) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài học để cập nhật.']);
                exit;
            }

            $lessons[$index]['title'] = $title;
            $lessons[$index]['grade'] = $grade;
            $lessons[$index]['semester'] = $semester;
            $lessons[$index]['subject_id'] = $subjectId;
            $lessons[$index]['subject_name'] = $subjectName;
            $lessons[$index]['description'] = $description;
            $lessons[$index]['questions'] = $questions;
            $lessons[$index]['updated_at'] = $now;
        }

        if (saveLessons($lessonsFile, $lessons)) {
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Đã lưu bài học thành công!'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu vào hệ thống.']);
        }
        break;

    case 'delete_lesson':
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID bài học.']);
            exit;
        }

        $lessons = loadLessons($lessonsFile);
        $originalCount = count($lessons);
        $lessons = array_filter($lessons, function($item) use ($id, $username) {
            return !(($item['id'] ?? '') === $id && ($item['teacher_username'] ?? '') === $username);
        });

        if (count($lessons) === $originalCount) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài học để xóa.']);
            exit;
        }

        saveLessons($lessonsFile, $lessons);
        echo json_encode(['success' => true, 'message' => 'Đã xóa bài học thành công!'], JSON_UNESCAPED_UNICODE);
        break;

    case 'duplicate_lesson':
        $id = $input['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID bài học.']);
            exit;
        }

        $lessons = loadLessons($lessonsFile);
        $target = null;
        foreach ($lessons as $item) {
            if (($item['id'] ?? '') === $id && ($item['teacher_username'] ?? '') === $username) {
                $target = $item;
                break;
            }
        }

        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài học gốc.']);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $newId = 'les_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
        $clone = $target;
        $clone['id'] = $newId;
        $clone['title'] = $target['title'] . ' (Bản sao)';
        $clone['created_at'] = $now;
        $clone['updated_at'] = $now;

        $lessons[] = $clone;
        saveLessons($lessonsFile, $lessons);

        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Đã nhân bản bài học thành công!'
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_bank_questions':
        $grade = $input['grade'] ?? ($_GET['grade'] ?? 'khoi6');
        $semester = $input['semester'] ?? ($_GET['semester'] ?? 'hk1');
        $subjectId = (int)($input['subject_id'] ?? ($_GET['subject_id'] ?? 1));

        $questionsFile = __DIR__ . "/../questions/{$grade}/{$semester}/subject_{$subjectId}.json";
        if (!file_exists($questionsFile)) {
            $questionsFile = __DIR__ . "/../questions/{$grade}/subject_{$subjectId}.json";
        }

        if (!file_exists($questionsFile)) {
            echo json_encode([
                'success' => true,
                'topics' => [],
                'total_questions' => 0,
                'message' => 'Chưa có câu hỏi nào trong ngân hàng cho môn/khối này.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = json_decode(file_get_contents($questionsFile), true) ?: [];
        $totalQuestions = 0;
        $topics = [];

        foreach ($raw as $tIdx => $topicItem) {
            $topicName = $topicItem['topic'] ?? 'Chủ đề chung';
            $lessonName = $topicItem['lesson'] ?? 'Bài học chung';
            $qList = $topicItem['questions'] ?? [];

            $formattedQuestions = [];
            foreach ($qList as $qIdx => $q) {
                $totalQuestions++;
                $formattedQuestions[] = [
                    'bank_uid' => "{$grade}_{$semester}_{$subjectId}_{$tIdx}_{$qIdx}",
                    'topic_index' => $tIdx,
                    'question_index' => $qIdx,
                    'topic' => $topicName,
                    'lesson' => $lessonName,
                    'question' => $q['question'] ?? '',
                    'options' => $q['options'] ?? [],
                    'correct' => $q['correct'] ?? 0,
                    'type' => $q['type'] ?? 'single',
                    'level' => $q['level'] ?? 'NB',
                    'image' => $q['image'] ?? '',
                    'items' => $q['items'] ?? [], // for true_false_multiple
                    'suggested_answer' => $q['suggested_answer'] ?? ($q['explanation'] ?? ''),
                    'points' => $q['points'] ?? 1
                ];
            }

            $topics[] = [
                'topic' => $topicName,
                'lesson' => $lessonName,
                'questions' => $formattedQuestions
            ];
        }

        echo json_encode([
            'success' => true,
            'topics' => $topics,
            'total_questions' => $totalQuestions
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'get_classes_and_students':
        $classesFile = __DIR__ . '/../../admin/classes.json';
        $studentsFile = __DIR__ . '/../../admin/students.json';
        $teacherClassesFile = __DIR__ . '/../../admin/teacher_classes.json';

        $teacherClasses = file_exists($teacherClassesFile) ? (json_decode(file_get_contents($teacherClassesFile), true) ?: []) : [];
        $allClasses = file_exists($classesFile) ? (json_decode(file_get_contents($classesFile), true) ?: []) : [];
        $allStudents = file_exists($studentsFile) ? (json_decode(file_get_contents($studentsFile), true) ?: []) : [];

        $assignedClassIds = $teacherClasses[$username] ?? [];

        // Build list of classes assigned to this teacher
        $classesList = [];
        foreach ($allClasses as $c) {
            $cid = (string)($c['id'] ?? '');
            if (empty($assignedClassIds) || in_array($cid, $assignedClassIds) || in_array((int)$cid, $assignedClassIds)) {
                $classesList[$cid] = [
                    'id' => $cid,
                    'name' => $c['name'] ?? $c['code'] ?? 'Lớp ' . $cid,
                    'students' => []
                ];
            }
        }

        // Group students into assigned classes
        foreach ($allStudents as $st) {
            $cid = (string)($st['class_id'] ?? '');
            if (isset($classesList[$cid])) {
                $classesList[$cid]['students'][] = [
                    'id' => $st['id'] ?? '',
                    'name' => $st['name'] ?? '',
                    'code' => $st['code'] ?? '',
                    'gender' => $st['gender'] ?? 'Nam'
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'classes' => array_values($classesList)
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ: ' . htmlspecialchars($action)]);
        break;
}
