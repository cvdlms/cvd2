<?php
header('Content-Type: application/json; charset=utf-8');

try {
    session_name('CVD_STUDENT_SESSION');
    session_start();
    if (!isset($_SESSION['student_code'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }

    $testName = $_GET['test_name'] ?? '';
    $testId = $_GET['test_id'] ?? $_GET['test_name'] ?? '';
    $examType = $_GET['exam_type'] ?? 'practice';
    $studentCode = $_SESSION['student_code'];

    if (!$testId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đề thi (Test ID)']);
        exit;
    }

    // Check Premium status
    require_once __DIR__ . '/../../includes/student_premium_helper.php';
    $premiumStatus = getStudentPremiumStatus($studentCode);

    // Load scores API
    $scoresFile = __DIR__ . '/../../shared/api/scores.php';
    if (!file_exists($scoresFile)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy dữ liệu điểm thi']);
        exit;
    }

    require_once $scoresFile;

    // Get attempts for this student and test ID
    $attempts = function_exists('getStudentAttempts') ? getStudentAttempts($studentCode, $testId) : [];
    $currentAttempts = count($attempts);

    // Logic for retakes:
    // 1. Official exams: Everyone gets 1 attempt only
    // 2. Practice exams: Non-premium gets 1 attempt, Premium gets unlimited
    if ($examType === 'official') {
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
        if (!empty($premiumStatus['is_premium'])) {
            echo json_encode([
                'success' => true,
                'can_take' => true,
                'attempts' => $currentAttempts,
                'unlimited' => true,
                'message' => 'Premium: Thi lại không giới hạn'
            ]);
        } else {
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

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'can_take' => true,
        'attempts' => 0,
        'message' => $e->getMessage()
    ]);
}
?>
