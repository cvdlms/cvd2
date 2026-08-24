<?php
/**
 * Danh sách các bài còn câu Tự luận đang chờ giáo viên chấm.
 * Lọc theo bộ môn giáo viên phụ trách (bản ghi không môn hiển thị cho mọi GV).
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/exam_helper.php';
require_once __DIR__ . '/../../includes/api_auth.php';
requireTeacherSession();

$username = $_SESSION['username'];
$teacherSubjects = [];
$subjectsFile = __DIR__ . '/../../admin/teacher_subjects.json';
if (file_exists($subjectsFile)) {
    $map = json_decode(file_get_contents($subjectsFile), true) ?: [];
    $teacherSubjects = $map[$username] ?? [];
}

echo json_encode([
    'success' => true,
    'data' => exam_scan_pending_essays($teacherSubjects)
], JSON_UNESCAPED_UNICODE);
