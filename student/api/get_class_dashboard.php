<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireStudentSession();

// Tự cập nhật TKB nếu EduVN có tuần mới hơn (không chặn nếu import thất bại)
require_once __DIR__ . '/../../includes/eduvn_sync.php';
if (eduvn_sync_timetable_is_stale()) {
    eduvn_sync_import_timetable();
}
// Tự cập nhật thông báo GVCN nếu EduVN có thông báo mới
if (eduvn_sync_gvcn_posts_is_stale()) {
    eduvn_sync_import_gvcn_posts();
}

header('Content-Type: application/json; charset=utf-8');

$studentCode = $_SESSION['student_code'];
$studentClassCode = $_SESSION['student_class_code'] ?? $_SESSION['student_class'] ?? '';
$studentClass = $_SESSION['student_class'] ?? '';

$teacherName = '';
$classesFile = __DIR__ . '/../../admin/classes.json';
if (file_exists($classesFile)) {
    foreach (json_decode(file_get_contents($classesFile), true) ?: [] as $c) {
        $code = $c['code'] ?? '';
        if ($code !== '' && ($code === $studentClassCode || $code === $studentClass)) {
            $teacherName = $c['teacher'] ?? '';
            break;
        }
    }
}

$timetable = [];
$timetablesFile = __DIR__ . '/../../data/timetables.json';
if (file_exists($timetablesFile)) {
    $all = json_decode(file_get_contents($timetablesFile), true) ?: [];
    if (isset($all[$studentClassCode])) {
        $timetable = $all[$studentClassCode];
    } elseif (isset($all[$studentClass])) {
        $timetable = $all[$studentClass];
    } elseif (isset($all['default'])) {
        $timetable = $all['default'];
    }
}

$posts = [];
$postsFile = __DIR__ . '/../../data/gvcn_posts.json';
if (file_exists($postsFile)) {
    $all = json_decode(file_get_contents($postsFile), true) ?: [];
    if (isset($all[$studentClassCode])) {
        $posts = $all[$studentClassCode];
    } elseif (isset($all[$studentClass])) {
        $posts = $all[$studentClass];
    } elseif (isset($all['default'])) {
        $posts = $all['default'];
    }
}

// Map today (date('w'): 0=Sun..6=Sat) to day key (T2..T7)
$dowMap = ['T2', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
$todayKey = $dowMap[(int)date('w')] ?? 'T2';

echo json_encode([
    'success'     => true,
    'class_code'  => $studentClassCode ?: $studentClass,
    'teacher'     => $teacherName,
    'today_key'   => $todayKey,
    'timetable'   => $timetable,
    'posts'       => $posts
]);
