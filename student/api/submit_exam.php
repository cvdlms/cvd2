<?php
header('Content-Type: application/json');

session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../../includes/exam_helper.php';
require_once __DIR__ . '/../../shared/api/scores.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['exam_id']) || !isset($input['answers']) || !is_array($input['answers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam data']);
    exit;
}

$examId = $input['exam_id'];
$answers = $input['answers'];
$violations = (int)($input['violations'] ?? 0);
if ($violations < 0) $violations = 0;
$violationLog = $input['violation_log'] ?? [];
if (!is_array($violationLog)) $violationLog = [];

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'] ?? '';
$classCode = $_SESSION['student_class_code'] ?? '';

// Resolve the exam file server-side using the student's grade. The client
// never supplies the exam file path or the correct answers — everything is
// loaded from the teacher's canonical file here.
$prefix = substr($classCode, 0, 1);
$grade = 'khoi' . $prefix;
$resolved = exam_resolve_file($examId, $grade);

if (!$resolved) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài thi. Vui lòng thử lại.']);
    exit;
}

$examFile = $resolved['file'];
$subjectId = $resolved['subject_id'];

$examData = json_decode(file_get_contents($examFile), true);
if (!is_array($examData) || empty($examData['questions'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu bài thi không hợp lệ.']);
    exit;
}

$canonicalTestId = $examData['test_id'] ?? null;
$examType = $examData['exam_type'] ?? 'official';
$testName = $examData['test_name'] ?? $examId;

// Server-side retake enforcement (client redirects can be bypassed):
// 1. Official exams: 1 attempt only
// 2. Practice exams: unlimited attempts for everyone
if (exam_has_completed($studentCode, $canonicalTestId, $subjectId) && $examType === 'official') {
    echo json_encode(['success' => false, 'message' => 'Đây là bài thi chính thức, chỉ được thi 1 lần duy nhất.']);
    exit;
}

// Rebuild the SAME deterministic question order the student saw, then grade
// against the canonical correct answers loaded from the exam file.
$questions = exam_shuffle_questions($examData['questions'], $studentCode, $canonicalTestId);

$totalQuestions = count($questions);
if ($totalQuestions === 0) {
    echo json_encode(['success' => false, 'message' => 'Bài thi không có câu hỏi nào.']);
    exit;
}

$correctAnswers = 0;   // số câu trắc nghiệm đúng hoàn toàn (thống kê)
$earnedUnits = 0.0;    // điểm thô: MCQ đúng = 1, Đúng/Sai = tỷ lệ ý đúng
$essayCount = 0;       // số câu tự luận chờ giáo viên chấm
$questionResults = [];
$totalExamMaxPoints = 0.0;
$autoGradableMaxPoints = 0.0;
$autoEarnedPoints = 0.0;

foreach ($questions as $index => $question) {
    $userAnswer = isset($answers[$index]) ? $answers[$index] : null;
    $correctAnswer = $question['correct'] ?? null;
    $isCorrect = false;
    $qType = $question['type'] ?? 'single';
    $qPoints = isset($question['points']) && (float)$question['points'] > 0
        ? (float)$question['points']
        : (isset($examData['points_per_question']) && (float)$examData['points_per_question'] > 0 ? (float)$examData['points_per_question'] : 1.0);
    $totalExamMaxPoints += $qPoints;

    if ($qType === 'true_false_multiple') {
        // Chấm từng ý: điểm câu = (số ý đúng) / (tổng số ý)
        $items = is_array($question['items'] ?? null) ? $question['items'] : [];
        $userItems = is_array($userAnswer) ? $userAnswer : [];
        $matched = 0;
        $itemsDetail = [];
        foreach ($items as $j => $item) {
            $u = array_key_exists($j, $userItems) ? filter_var($userItems[$j], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
            $c = !empty($item['correct']);
            $ok = ($u === $c);
            if ($ok) $matched++;
            $itemsDetail[] = [
                'label' => $item['label'] ?? chr(97 + $j),
                'statement' => $item['statement'] ?? '',
                'user_answer' => $u,
                'correct_answer' => $c,
                'is_correct' => $ok
            ];
        }
        $totalItems = max(1, count($items));
        $fraction = count($items) > 0 ? ($matched / $totalItems) : 0.0;
        $earnedUnits += $fraction;
        $autoEarnedPoints += $fraction * $qPoints;
        $autoGradableMaxPoints += $qPoints;
        $isCorrect = (count($items) > 0 && $matched === $totalItems);
        if ($isCorrect) {
            $correctAnswers++; // Đúng hoàn toàn mới tính vào số câu đúng
        }

        $questionResults[] = [
            'question_index' => $index,
            'question' => $question['question'] ?? '',
            'image' => $question['image'] ?? '',
            'user_answer' => $userItems,
            'correct_answer' => array_map(static fn ($it) => !empty($it['correct']), $items),
            'is_correct' => $isCorrect,
            'type' => 'true_false_multiple',
            'points' => $qPoints,
            'max_points' => $qPoints,
            'items_detail' => $itemsDetail,
            'fraction' => $fraction,
            'explanation' => $question['explanation'] ?? null
        ];
        continue;
    }

    if ($qType === 'essay') {
        // Tự luận: lưu bài làm, chờ giáo viên chấm — không tính vào điểm tự động
        $essayCount++;
        $questionResults[] = [
            'question_index' => $index,
            'question' => $question['question'] ?? '',
            'image' => $question['image'] ?? '',
            'user_answer' => is_string($userAnswer) ? $userAnswer : '',
            'correct_answer' => null,
            'is_correct' => null,
            'type' => 'essay',
            'needs_grading' => true,
            'points' => $qPoints,
            'max_points' => $qPoints,
            'explanation' => $question['explanation'] ?? null
        ];
        continue;
    }

    if ($qType === 'single') {
        if ($userAnswer !== null && !is_array($userAnswer)) {
            $isCorrect = ((int)$userAnswer === (int)$correctAnswer);
        }
    } else {
        // multiple choice
        if (is_array($userAnswer) && is_array($correctAnswer)) {
            sort($userAnswer);
            sort($correctAnswer);
            $isCorrect = ($userAnswer === $correctAnswer);
        }
    }

    $autoGradableMaxPoints += $qPoints;
    if ($isCorrect) {
        $correctAnswers++;
        $earnedUnits += 1;
        $autoEarnedPoints += $qPoints;
    }

    $questionResults[] = [
        'question_index' => $index,
        'question' => $question['question'] ?? '',
        'image' => $question['image'] ?? '',
        'user_answer' => $userAnswer,
        'correct_answer' => $correctAnswer,
        'is_correct' => $isCorrect,
        'type' => $qType,
        'points' => $qPoints,
        'max_points' => $qPoints,
        'explanation' => $question['explanation'] ?? null
    ];
}

// Điểm tự động: chỉ tính phần trắc nghiệm + Đúng/Sai; câu tự luận chờ chấm.
// Khi có tự luận, điểm là ĐIỂM TẠM (phần tự động), giáo viên chấm xong sẽ cập nhật.
$gradableUnits = $totalQuestions - $essayCount;
$hasPendingEssay = $essayCount > 0;
if ($gradableUnits > 0 && $autoGradableMaxPoints > 0) {
    $score = round(min(10, max(0, ($autoEarnedPoints / $autoGradableMaxPoints) * 10)), 1);
} else {
    $score = 0; // đề toàn tự luận: đợi chấm tay
}

$violationNote = '';
if ($violations > 0) {
    $reasons = [];
    foreach ($violationLog as $msg) {
        $msg = trim((string)$msg);
        if ($msg === '') continue;
        $label = exam_violation_reason_label($msg);
        $reasons[$label] = ($reasons[$label] ?? 0) + 1;
    }
    if (empty($reasons)) {
        $violationNote = "⚠️ Vi phạm: $violations lần";
    } else {
        $parts = [];
        foreach ($reasons as $label => $n) {
            $parts[] = $n > 1 ? "$label ($n)" : $label;
        }
        $violationNote = "⚠️ Vi phạm: $violations lần (" . implode(', ', $parts) . ")";
    }
}

/**
 * Map an on-screen violation message to a short human-readable reason.
 */
function exam_violation_reason_label($msg)
{
    $hay = mb_strtolower($msg);
    if (mb_strpos($hay, 'điện thoại') !== false) return 'Phát hiện điện thoại';
    if (mb_strpos($hay, 'nhiều hơn một khuôn mặt') !== false || mb_strpos($hay, 'nhiều người') !== false) return 'Nhiều người trong khung hình';
    if (mb_strpos($hay, 'không phát hiện khuôn mặt') !== false || mb_strpos($hay, 'không thấy khuôn mặt') !== false) return 'Không thấy khuôn mặt';
    if (mb_strpos($hay, 'rời khỏi tab') !== false || mb_strpos($hay, 'chuyển sang ứng dụng') !== false || mb_strpos($hay, 'mất tiêu điểm') !== false) return 'Rời khỏi tab / đổi ứng dụng';
    if (mb_strpos($hay, 'toàn màn hình') !== false) return 'Thoát chế độ toàn màn hình';
    if (mb_strpos($hay, 'camera') !== false || mb_strpos($hay, 'mất kết nối') !== false) return 'Tắt camera giám sát';
    if (mb_strpos($hay, 'nút back') !== false) return 'Sử dụng nút Back';
    if (mb_strpos($hay, 'nút f11') !== false) return 'Thoát toàn màn hình (F11)';
    if (mb_strpos($hay, 'thời gian') !== false) return 'Thời gian trả lời bất thường';
    return mb_strlen($msg) > 60 ? mb_substr($msg, 0, 60) . '…' : $msg;
}

$isPracticeExam = ($examType === 'practice');

// Attempt number counts previous completed attempts for this canonical exam.
$attemptNumber = 1;
if (!$isPracticeExam) {
    $attempts = getStudentAttempts($studentCode, $canonicalTestId);
    $attemptNumber = count($attempts) + 1;
} else {
    $practiceFile = __DIR__ . '/../../data/practice_results/practice_results.json';
    if (file_exists($practiceFile)) {
        $practiceResults = json_decode(file_get_contents($practiceFile), true) ?? [];
        $attemptNumber = 1;
        foreach ($practiceResults as $entry) {
            if (($entry['student_code'] ?? '') === $studentCode
                && (($entry['source_exam_id'] ?? ($entry['exam_id'] ?? '')) === $canonicalTestId)) {
                $attemptNumber++;
            }
        }
    }
}

$examResult = [
    'id' => uniqid('exam_', true),
    'student_code' => $studentCode,
    'student_name' => $studentName,
    'class_code' => $classCode,
    'exam_type' => $examType,
    'exam_category' => $examData['exam_category'] ?? 'regular',
    'test_name' => $testName,
    'source_exam_id' => $canonicalTestId ?: $examId,
    'subject_id' => $subjectId,
    'attempt' => $attemptNumber,
    'score' => $score,
    'total_questions' => $totalQuestions,
    'correct_answers' => $correctAnswers,
    'essay_count' => $essayCount,
    'pending_essay' => $hasPendingEssay,
    'timestamp' => date('Y-m-d H:i:s'),
    'completed' => true,
    'is_practice' => $isPracticeExam,
    'question_results' => $questionResults,
    'notes' => $violationNote
];

// Gắn nhãn năm học phục vụ lưu trữ tối thiểu 5 năm theo chuẩn kiểm tra đánh giá
school_year_stamp_record($examResult);

if ($isPracticeExam) {
    $practiceResultsDir = __DIR__ . '/../../data/practice_results';
    if (!is_dir($practiceResultsDir)) {
        @mkdir($practiceResultsDir, 0755, true);
    }
    $practiceFile = $practiceResultsDir . '/practice_results.json';
    // Ghi dưới khóa để nhiều học sinh luyện tập cùng lúc không đè nhau
    $result = update_json_data($practiceFile, function($practiceResults) use ($examResult) {
        if (!is_array($practiceResults)) { $practiceResults = []; }
        $practiceResults[] = $examResult;
        return $practiceResults;
    }, []);
} else {
    $result = saveExamResult($examResult);
}

if ($result) {
    if ($isPracticeExam) {
        $responseMessage = 'Bài luyện tập hoàn thành! Điểm không được lưu vào bảng điểm.';
    } elseif ($hasPendingEssay) {
        $responseMessage = 'Bài kiểm tra đã nộp thành công! Điểm hiện tại là phần tự động (' . $essayCount . ' câu tự luận đang chờ giáo viên chấm).';
    } else {
        $responseMessage = 'Bài kiểm tra đã nộp thành công và điểm đã được lưu.';
    }

    echo json_encode([
        'success' => true,
        'exam_id' => $examResult['id'],
        'score' => $score,
        'correct_answers' => $correctAnswers,
        'total_questions' => $totalQuestions,
        'essay_count' => $essayCount,
        'pending_essay' => $hasPendingEssay,
        'attempt' => $attemptNumber,
        'is_practice' => $isPracticeExam,
        'message' => $responseMessage
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save exam result']);
}
