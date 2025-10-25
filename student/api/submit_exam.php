<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['exam_id']) && !isset($input['type']) || !isset($input['questions']) || !isset($input['answers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam data']);
    exit;
}

// Extract subject_id from exam_id (format: subjectId_slug)
$examId = $input['exam_id'] ?? $input['type'];
$parts = explode('_', $examId, 2);
$subjectId = (int)$parts[0];

$testName = $input['test_name'] ?? $examId;
$questions = $input['questions'];
$answers = $input['answers'];
$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$classCode = $_SESSION['student_class_code'];

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

// Load existing scores
$scoresFile = __DIR__ . '/../../shared/api/scores.php';
if (!file_exists($scoresFile)) {
    echo json_encode(['success' => false, 'message' => 'Scores file not found']);
    exit;
}

require_once $scoresFile;

// Get attempt number
$attempts = getStudentAttempts($studentCode, $examId);
$attemptNumber = count($attempts) + 1;

// Create exam result
$examResult = [
    'id' => uniqid('exam_', true),
    'student_code' => $studentCode,
    'student_name' => $studentName,
    'class_code' => $classCode,
    'exam_type' => $testName,
    'test_name' => $testName,
    'subject_id' => $subjectId,
    'attempt' => $attemptNumber,
    'score' => $score,
    'total_questions' => $totalQuestions,
    'correct_answers' => $correctAnswers,
    'timestamp' => date('Y-m-d H:i:s'),
    'completed' => true,
    'question_results' => $questionResults
];

// Save the result
$result = saveExamResult($examResult);

if ($result) {
    echo json_encode([
        'success' => true,
        'exam_id' => $examResult['id'],
        'score' => $score,
        'correct_answers' => $correctAnswers,
        'total_questions' => $totalQuestions,
        'attempt' => $attemptNumber,
        'message' => 'Exam submitted successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save exam result']);
}
?>
