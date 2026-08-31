<?php
/**
 * Lưu điểm chấm Tự luận của giáo viên.
 * Input: {storage: 'score'|'practice', result_id, student_code, grades: [{question_index, awarded, comment}]}
 * - Điểm mỗi câu tự luận nằm trong [0, points] của đề (bước 0.25).
 * - Cập nhật bản ghi đầy đủ (file cá nhân hoặc practice_results) rồi tính lại
 *   điểm cuối bằng exam_recompute_after_grading; phản ánh sang student_score.json.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/json_db_helper.php';
require_once __DIR__ . '/../../includes/exam_helper.php';
require_once __DIR__ . '/../../includes/api_auth.php';
requireTeacherSession();

$input = json_decode(file_get_contents('php://input'), true);
$storage = ($input['storage'] ?? '') === 'practice' ? 'practice' : 'score';
$resultId = (string)($input['result_id'] ?? '');
$studentCode = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)($input['student_code'] ?? ''));
$grades = $input['grades'] ?? [];
$gradedBy = $_SESSION['fullname'] ?? ($_SESSION['username'] ?? 'Giáo viên');

if ($resultId === '' || $studentCode === '' || !is_array($grades)) {
    echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ']);
    exit;
}

// Chuẩn hoá điểm: số, không âm, bước 0.25, comment giới hạn độ dài
$cleanGrades = [];
foreach ($grades as $g) {
    if (!is_array($g)) continue;
    $qi = (int)($g['question_index'] ?? -1);
    if ($qi < 0) continue;
    $awarded = round((float)($g['awarded'] ?? 0) * 4) / 4;
    $awarded = max(0.0, min($awarded, 100.0));
    $comment = mb_substr(trim((string)($g['comment'] ?? '')), 0, 500);
    $cleanGrades[$qi] = ['awarded' => $awarded, 'comment' => $comment];
}
if (empty($cleanGrades)) {
    echo json_encode(['success' => false, 'message' => 'Không có câu nào để chấm']);
    exit;
}

$scoresDir = __DIR__ . '/../../shared/scores/';
$recordFile = $storage === 'practice'
    ? __DIR__ . '/../../data/practice_results/practice_results.json'
    : $scoresDir . $studentCode . '.json';

if (!file_exists($recordFile)) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài làm']);
    exit;
}

$matched = 0;
$newScore = null;
$stillPending = null;

$update = update_json_data($recordFile, function ($allRecords) use (
    $resultId,
    $studentCode,
    $cleanGrades,
    $gradedBy,
    &$matched,
    &$newScore,
    &$stillPending
) {
    if (!is_array($allRecords)) return $allRecords;
    $single = isset($allRecords['id']) || isset($allRecords['source_exam_id']) || isset($allRecords['exam_id']);
    $records = $single ? [$allRecords] : $allRecords;

    foreach ($records as &$rec) {
        if (($rec['id'] ?? '') !== $resultId) continue;
        if (($rec['student_code'] ?? '') !== $studentCode) continue; // kho chung nhiều HS

        // Lưu ý: phải tách biến trung gian trước khi duyệt theo tham chiếu —
        // foreach (((expr) ?? []) as &$qr) ghi vào bản tạm và mất sửa đổi.
        $qrs = $rec['question_results'] ?? [];
        foreach ($qrs as &$qr) {
            if (($qr['type'] ?? '') !== 'essay' || empty($qr['needs_grading'])) continue;
            $qi = (int)($qr['question_index'] ?? -1);
            if (!isset($cleanGrades[$qi])) continue;

            $maxPoints = max(0.0, (float)($qr['points'] ?? ($qr['max_points'] ?? 0)));
            if ($maxPoints <= 0) $maxPoints = 1.0;

            $awarded = min(max(0.0, $cleanGrades[$qi]['awarded']), $maxPoints);
            $qr['needs_grading'] = false;
            $qr['max_points'] = $maxPoints;
            $qr['points'] = $maxPoints;
            $qr['awarded_points'] = $awarded;
            $qr['graded_at'] = date('Y-m-d H:i:s');
            $qr['graded_by'] = $gradedBy;
            if ($cleanGrades[$qi]['comment'] !== '') {
                $qr['teacher_comment'] = $cleanGrades[$qi]['comment'];
            }
            $matched++;
        }
        unset($qr);
        $rec['question_results'] = $qrs;

        if ($matched > 0) {
            list($newScore, $stillPending) = exam_recompute_after_grading($rec);
            $rec['graded_at'] = date('Y-m-d H:i:s');
            $rec['graded_by'] = $gradedBy;
        }
        break;
    }
    unset($rec);

    return $single ? $records[0] : $records;
}, []);

if (!$update || $matched === 0 || $newScore === null) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài làm phù hợp']);
    exit;
}

// Phản ánh điểm mới vào bảng điểm tổng hợp (chỉ bài chính thức)
if ($storage === 'score') {
    $consolidatedFile = $scoresDir . 'student_score.json';
    update_json_data($consolidatedFile, function ($entries) use (
        $studentCode,
        $resultId,
        $newScore,
        $stillPending
    ) {
        if (!is_array($entries)) return $entries;
        foreach ($entries as &$entry) {
            if (($entry['student_id'] ?? '') !== $studentCode) continue;
            if (($entry['result_id'] ?? '') !== $resultId) continue;
            $entry['score'] = $newScore;
            $entry['pending_essay'] = $stillPending;
            break;
        }
        unset($entry);
        return $entries;
    }, []);
}

echo json_encode([
    'success' => true,
    'message' => 'Đã lưu điểm chấm tự luận',
    'new_score' => $newScore,
    'pending_essay' => $stillPending,
    'graded_count' => $matched
], JSON_UNESCAPED_UNICODE);
