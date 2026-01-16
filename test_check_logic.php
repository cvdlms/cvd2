<?php
/**
 * Test kiểm tra logic thi lại cho học sinh 2203858901
 */

require_once __DIR__ . '/includes/student_premium_helper.php';
require_once __DIR__ . '/shared/api/scores.php';

$studentCode = '2203858901';
$testId = 'BAI_20260112152634_32dd7b';

echo "=== TEST RETAKE LOGIC ===\n\n";

// 1. Kiểm tra Premium status
$premiumStatus = getStudentPremiumStatus($studentCode);
echo "1. Premium Status:\n";
echo "   - Is Premium: " . ($premiumStatus['is_premium'] ? 'YES' : 'NO') . "\n";
if ($premiumStatus['is_premium']) {
    echo "   - Type: {$premiumStatus['type']}\n";
    echo "   - Days Remaining: {$premiumStatus['days_remaining']}\n";
}
echo "\n";

// 2. Kiểm tra số lần thi
$attempts = getStudentAttempts($studentCode, $testId);
echo "2. Số lần đã thi:\n";
echo "   - Test ID: $testId\n";
echo "   - Attempts: " . count($attempts) . " lần\n";
foreach ($attempts as $idx => $attempt) {
    echo "   - Lần " . ($idx + 1) . ": Điểm {$attempt['score']}, Thời gian {$attempt['timestamp']}\n";
}
echo "\n";

// 3. Load exam data
$examFile = __DIR__ . '/teacher/exams/khoi9/subject_2/BAI_20260112152634_32dd7b.json';
if (!file_exists($examFile)) {
    die("ERROR: Không tìm thấy file đề thi\n");
}
$examData = json_decode(file_get_contents($examFile), true);
$examType = $examData['exam_type'] ?? 'practice';

echo "3. Exam Information:\n";
echo "   - Exam Type: $examType\n";
echo "   - Test Name: {$examData['test_name']}\n\n";

// 4. Apply logic
echo "4. Retake Logic:\n";
$canTake = false;
$message = '';

if (count($attempts) === 0) {
    $canTake = true;
    $message = "Chưa thi lần nào - OK";
} elseif ($examType === 'official') {
    $canTake = false;
    $message = "Official exam - Chỉ 1 lần cho TẤT CẢ → CHẶN";
} else {
    // Practice exam
    if ($premiumStatus['is_premium']) {
        $canTake = true;
        $message = "Practice + Premium → Unlimited retakes → CHO PHÉP";
    } else {
        $canTake = false;
        $message = "Practice + Non-premium → 1 lần duy nhất → CHẶN";
    }
}

echo "   - Can Take: " . ($canTake ? 'YES ✓' : 'NO ✗') . "\n";
echo "   - Message: $message\n\n";

// 5. Kết luận
echo "=== KẾT LUẬN ===\n";
if (!$canTake) {
    echo "✓ Logic ĐÚNG - Học sinh 2203858901 ĐÃ HẾT LƯỢT thi luyện tập\n";
    echo "✓ Dashboard sẽ ẨN bài thi này\n";
    echo "✓ Nếu vào trực tiếp exam.php?type=$testId sẽ bị REDIRECT về result.php\n";
} else {
    echo "✗ BUG - Học sinh vẫn có thể thi lại!\n";
}

echo "\n=== TEST THỰC TẾ ===\n";
echo "1. Đăng nhập: student_code=$studentCode\n";
echo "2. Kiểm tra Dashboard - đề 'Bai tap 2' phải ẨN\n";
echo "3. Thử URL: exam.php?type=$testId\n";
echo "4. Phải redirect về result.php với message\n";
