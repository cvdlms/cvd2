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

$correctAnswers = 0;
$questionResults = [];

foreach ($questions as $index => $question) {
    $userAnswer = isset($answers[$index]) ? $answers[$index] : null;
    $correctAnswer = $question['correct'] ?? null;
    $isCorrect = false;

    if (($question['type'] ?? 'single') === 'single') {
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

    if ($isCorrect) {
        $correctAnswers++;
    }

    $questionResults[] = [
        'question_index' => $index,
        'question' => $question['question'] ?? '',
        'user_answer' => $userAnswer,
        'correct_answer' => $correctAnswer,
        'is_correct' => $isCorrect,
        'type' => $question['type'] ?? 'single',
        'explanation' => $question['explanation'] ?? null
    ];
}

$score = round(($correctAnswers / $totalQuestions) * 10, 1);

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
    'test_name' => $testName,
    'source_exam_id' => $canonicalTestId ?: $examId,
    'subject_id' => $subjectId,
    'attempt' => $attemptNumber,
    'score' => $score,
    'total_questions' => $totalQuestions,
    'correct_answers' => $correctAnswers,
    'timestamp' => date('Y-m-d H:i:s'),
    'completed' => true,
    'is_practice' => $isPracticeExam,
    'question_results' => $questionResults,
    'notes' => $violationNote
];

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
    $responseMessage = $isPracticeExam
        ? 'Bài luyện tập hoàn thành! Điểm không được lưu vào bảng điểm.'
        : 'Bài kiểm tra đã nộp thành công và điểm đã được lưu.';

    echo json_encode([
        'success' => true,
        'exam_id' => $examResult['id'],
        'score' => $score,
        'correct_answers' => $correctAnswers,
        'total_questions' => $totalQuestions,
        'attempt' => $attemptNumber,
        'is_practice' => $isPracticeExam,
        'message' => $responseMessage
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save exam result']);
}
