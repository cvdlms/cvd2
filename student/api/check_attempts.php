<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Set student session name
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$testName = $_GET['test_name'] ?? '';
$testId = $_GET['test_id'] ?? $_GET['test_name'] ?? ''; // Use test_id if available, fallback to test_name
$examType = $_GET['exam_type'] ?? 'practice'; // Get exam type
$studentCode = $_SESSION['student_code'];

if (!$testId) {
    echo json_encode(['success' => false, 'message' => 'Test ID required']);
    exit;
}

// Check Premium status
require_once __DIR__ . '/../../includes/student_premium_helper.php';
$premiumStatus = getStudentPremiumStatus($studentCode);

// Load scores data
$scoresFile = __DIR__ . '/../../shared/api/scores.php';
if (!file_exists($scoresFile)) {
    echo json_encode(['success' => false, 'message' => 'Scores file not found']);
    exit;
}

require_once $scoresFile;

// Get attempts for this student and test ID
$attempts = getStudentAttempts($studentCode, $testId);
$currentAttempts = count($attempts);

// DEBUG LOG
$logFile = __DIR__ . '/../../logs/check_attempts_debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$debugInfo = date('[Y-m-d H:i:s] ') . "Student: $studentCode, TestID: $testId, ExamType: $examType, Attempts: $currentAttempts, IsPremium: " . ($premiumStatus['is_premium'] ? 'YES' : 'NO') . "\n";
file_put_contents($logFile, $debugInfo, FILE_APPEND);

// Logic for retakes:
// 1. Official exams: Everyone gets 1 attempt only (fair for rankings)
// 2. Practice exams: Non-premium gets 1 attempt, Premium gets unlimited
if ($examType === 'official') {
    // Official exam - strict 1 attempt for everyone
    $maxAttempts = 1;
    if ($currentAttempts >= $maxAttempts) {
        echo json_encode([
            'success' => true,
            'can_take' => false,
            'attempts' => $currentAttempts,
            'message' => 'Đây là bài thi chính thức, chỉ được thi 1 lần duy nhất.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'can_take' => true,
            'attempts' => $currentAttempts,
            'remaining' => $maxAttempts - $currentAttempts
        ]);
    }
} else {
    // Practice exam - check Premium status
    if ($premiumStatus['is_premium']) {
        // Premium: Unlimited retakes for practice
        echo json_encode([
            'success' => true,
            'can_take' => true,
            'attempts' => $currentAttempts,
            'unlimited' => true,
            'message' => 'Premium: Thi lại không giới hạn'
        ]);
    } else {
        // Non-premium: 1 attempt for practice
        $maxAttempts = 1;
        if ($currentAttempts >= $maxAttempts) {
            echo json_encode([
                'success' => true,
                'can_take' => false,
                'attempts' => $currentAttempts,
                'message' => 'Bạn đã hết lượt thi. Nâng cấp Premium để thi lại không giới hạn!'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'can_take' => true,
                'attempts' => $currentAttempts,
                'remaining' => $maxAttempts - $currentAttempts
            ]);
        }
    }
}
?>
