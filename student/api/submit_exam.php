<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Debug logging for submit problems (temporary)
$debugLog = __DIR__ . '/../../logs/submit_debug.log';
try {
    $raw = file_get_contents('php://input');
    $log = "----\n" . date('Y-m-d H:i:s') . "\n";
    $log .= "SESSION student_code=" . ($_SESSION['student_code'] ?? 'NULL') . ", student_name=" . ($_SESSION['student_name'] ?? 'NULL') . "\n";
    $log .= "RAW INPUT: " . substr($raw, 0, 1000) . "\n";
    file_put_contents($debugLog, $log, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    // ignore logging errors
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['exam_id']) && !isset($input['type']) || !isset($input['questions']) || !isset($input['answers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam data']);
    exit;
}

// Parse exam ID - handle both legacy (subject_id_slug) and new (test_id) formats
$examId = $input['exam_id'] ?? $input['type'];
$subjectId = null;
$slug = null;

if (preg_match('/^(\d+)_(.+)$/', $examId, $matches)) {
    // Legacy format: subject_id_slug
    $subjectId = (int)$matches[1];
    $slug = $matches[2];
} else {
    // New format: test_id (e.g., SUB_20251229110817_b70bfc)
    // Need to search teacher exams to find subject_id
    // For now, set a placeholder; will be resolved by test_id matching
    $subjectId = 0;
    $slug = '';
}

$testName = $input['test_name'] ?? $examId;
$questions = $input['questions'];
$answers = $input['answers'];
$violations = $input['violations'] ?? 0;  // Get violation count
$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$classCode = $_SESSION['student_class_code'];

// Try to resolve the canonical test_id from teacher exam files (useful when filenames use test_id)
function simple_slug($string) {
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return strtolower($string);
}

$resolvedSourceId = null;
$resolvedSubjectId = null;
$resolvedExamType = 'official'; // Default to official (needs saving score)

// Search teacher exams for the matching exam
// If new format (test_id), search all grades/subjects
// If legacy format, search specific subject folder
$baseExams = __DIR__ . '/../../teacher/exams/';

if ($subjectId > 0 && !empty($slug)) {
    // Legacy format: search specific subject folder
    $gradeDirs = @glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
    foreach ($gradeDirs as $gradeDir) {
        $subjectDir = $gradeDir . '/subject_' . $subjectId;
        if (!is_dir($subjectDir)) continue;
        $files = @glob($subjectDir . '/*.json') ?: [];
        foreach ($files as $f) {
            $d = json_decode(file_get_contents($f), true);
            if (!$d) continue;
            $fname = pathinfo($f, PATHINFO_FILENAME);
            // Match by test_id, filename, or slug(test_name)
            if (!empty($d['test_id']) && ($d['test_id'] === $slug || $d['test_id'] === $examId)) {
                $resolvedSourceId = $d['test_id'];
                $resolvedSubjectId = $subjectId;
                $resolvedExamType = $d['exam_type'] ?? 'official';
                break 2;
            }
            if ($fname === $slug) {
                $resolvedSourceId = $d['test_id'] ?? $fname;
                $resolvedSubjectId = $subjectId;
                $resolvedExamType = $d['exam_type'] ?? 'official';
                break 2;
            }
            if (!empty($d['test_name']) && simple_slug($d['test_name']) === simple_slug($slug)) {
                $resolvedSourceId = $d['test_id'] ?? $fname;
                $resolvedSubjectId = $subjectId;
                $resolvedExamType = $d['exam_type'] ?? 'official';
                break 2;
            }
        }
    }
} else {
    // New format (test_id): search all grades/subjects
    $gradeDirs = @glob($baseExams . 'khoi*', GLOB_ONLYDIR) ?: [];
    foreach ($gradeDirs as $gradeDir) {
        $subjectDirs = @glob($gradeDir . '/subject_*', GLOB_ONLYDIR) ?: [];
        foreach ($subjectDirs as $subjectDir) {
            if (preg_match('/subject_(\d+)/', $subjectDir, $m)) {
                $sid = (int)$m[1];
                $files = @glob($subjectDir . '/*.json') ?: [];
                foreach ($files as $f) {
                    $d = json_decode(file_get_contents($f), true);
                    if (!$d) continue;
                    // Match by test_id
                    if (!empty($d['test_id']) && $d['test_id'] === $examId) {
                        $resolvedSourceId = $d['test_id'];
                        $resolvedSubjectId = $sid;
                        $resolvedExamType = $d['exam_type'] ?? 'official';
                        break 3;
                    }
                }
            }
        }
    }
}

// CRITICAL: source_exam_id must be the canonical test_id
$sourceExamId = $resolvedSourceId ?? $examId;
// Use resolved subject_id or keep original (fallback to 0 if new format not resolved)
$subjectId = $resolvedSubjectId ?? $subjectId;

// Calculate score
$correctAnswers = 0;
$totalQuestions = count($questions);
$questionResults = [];

foreach ($questions as $index => $question) {
    $userAnswer = $answers[$index] ?? null;
    $correctAnswer = $question['correct'];
    $isCorrect = false;

    if ($question['type'] === 'single') {
        $isCorrect = ($userAnswer !== null && $userAnswer === $correctAnswer);
    } else if ($question['type'] === 'multiple') {
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
        'question' => $question['question'],
        'user_answer' => $userAnswer,
        'correct_answer' => $correctAnswer,
        'is_correct' => $isCorrect,
        'type' => $question['type']
    ];
}

$score = round(($correctAnswers / $totalQuestions) * 10, 1);

// Create violation note if any
$violationNote = '';
if ($violations > 0) {
    $violationNote = "⚠️ Vi phạm: $violations lần (Thoát chế độ toàn màn hình)";
}

// Check if this is a practice exam (no need to save score to manage_result)
$isPracticeExam = ($resolvedExamType === 'practice');

// Load existing scores and get attempt number (only if not practice exam)
$attemptNumber = 1;
if (!$isPracticeExam) {
    $scoresFile = __DIR__ . '/../../shared/api/scores.php';
    if (!file_exists($scoresFile)) {
        echo json_encode(['success' => false, 'message' => 'Scores file not found']);
        exit;
    }
    require_once $scoresFile;
    
    // Get attempt number for official exams
    $attempts = getStudentAttempts($studentCode, $examId);
    $attemptNumber = count($attempts) + 1;
}

// Create exam result
$examResult = [
    'id' => uniqid('exam_', true),
    'student_code' => $studentCode,
    'student_name' => $studentName,
    'class_code' => $classCode,
    'exam_type' => $testName,
    'test_name' => $testName,
    'source_exam_id' => $sourceExamId,
    'subject_id' => $subjectId,
    'attempt' => $attemptNumber,
    'score' => $score,
    'total_questions' => $totalQuestions,
    'correct_answers' => $correctAnswers,
    'timestamp' => date('Y-m-d H:i:s'),
    'completed' => true,
    'is_practice' => $isPracticeExam,
    'question_results' => $questionResults,
    'notes' => $violationNote  // Add violation note
];

// Save the result
$result = true;
if ($isPracticeExam) {
    // Save practice exam to temporary file for result display only
    $practiceResultsDir = __DIR__ . '/../../data/practice_results';
    if (!is_dir($practiceResultsDir)) {
        mkdir($practiceResultsDir, 0755, true);
    }
    $practiceFile = $practiceResultsDir . '/practice_results.json';
    $practiceResults = [];
    if (file_exists($practiceFile)) {
        $practiceResults = json_decode(file_get_contents($practiceFile), true) ?? [];
    }
    $practiceResults[] = $examResult;
    $result = file_put_contents($practiceFile, json_encode($practiceResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
} else {
    // Save official exam to scores.php
    $result = saveExamResult($examResult);
}

if ($result) {
    $responseMessage = $isPracticeExam ? 
        'Bài luyện tập hoàn thành! Điểm không được lưu vào bảng điểm.' : 
        'Bài kiểm tra đã nộp thành công và điểm đã được lưu.';
    
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
?>
