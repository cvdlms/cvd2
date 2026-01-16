<?php
/**
 * Test script để kiểm tra logic thi lại
 * Mô phỏng học sinh 2203858901 đã thi 1 lần
 */

// Test 1: Tạo file điểm giả cho học sinh 2203858901
$studentCode = '2203858901';
$studentScoreFile = __DIR__ . '/shared/scores/' . $studentCode . '.json';

// Lấy 1 đề thi bất kỳ để test
$examFiles = glob(__DIR__ . '/teacher/exams/khoi9/subject_*/SUB_*.json');
if (empty($examFiles)) {
    die("Không tìm thấy đề thi nào để test\n");
}

$examFile = $examFiles[0];
$examData = json_decode(file_get_contents($examFile), true);
$testId = $examData['test_id'];
$examType = $examData['exam_type'] ?? 'practice';

echo "=== TEST RETAKE LOGIC ===\n\n";
echo "Học sinh: $studentCode\n";
echo "Đề thi: {$examData['test_name']} (ID: $testId)\n";
echo "Loại thi: $examType\n\n";

// Tạo điểm giả (đã thi 1 lần)
$fakeScore = [
    [
        'id' => 'TEST_' . time(),
        'student_id' => $studentCode,
        'exam_id' => $testId,
        'source_exam_id' => $testId,
        'score' => 7.5,
        'total_questions' => 10,
        'correct_answers' => 8,
        'timestamp' => date('Y-m-d H:i:s'),
        'subject_id' => $examData['subject_id']
    ]
];

// Lưu file điểm
file_put_contents($studentScoreFile, json_encode($fakeScore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✓ Đã tạo file điểm giả: $studentScoreFile\n";
echo "✓ Học sinh đã thi 1 lần với điểm: 7.5\n\n";

// Test 2: Kiểm tra API check_attempts
echo "=== TEST API CHECK_ATTEMPTS ===\n";
require_once __DIR__ . '/includes/student_premium_helper.php';
require_once __DIR__ . '/shared/api/scores.php';

$premiumStatus = getStudentPremiumStatus($studentCode);
$attempts = getStudentAttempts($studentCode, $testId);

echo "Premium Status: " . ($premiumStatus['is_premium'] ? 'YES' : 'NO') . "\n";
echo "Số lần đã thi: " . count($attempts) . "\n";
echo "Exam Type: $examType\n\n";

// Logic check
if ($examType === 'official') {
    echo "➜ Kết luận: ĐÃ HẾT LƯỢT (Official exam - 1 lần cho tất cả)\n";
} else {
    if ($premiumStatus['is_premium']) {
        echo "➜ Kết luận: CÒN ĐƯỢC THI (Premium - unlimited retakes)\n";
    } else {
        echo "➜ Kết luận: ĐÃ HẾT LƯỢT (Non-premium practice - 1 lần duy nhất)\n";
    }
}

echo "\n=== HƯỚNG DẪN TEST ===\n";
echo "1. Đăng nhập với mã HS: $studentCode\n";
echo "2. Vào Dashboard - sẽ KHÔNG thấy đề thi '{$examData['test_name']}' (đã ẩn)\n";
echo "3. Thử truy cập trực tiếp: exam.php?type=$testId\n";
echo "4. Sẽ bị redirect về result.php với thông báo giới hạn\n\n";

echo "=== XÓA FILE TEST ===\n";
echo "Để xóa file điểm test: unlink('$studentScoreFile')\n";
