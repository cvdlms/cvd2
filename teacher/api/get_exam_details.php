<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include '../../includes/session_check.php';

if (!isset($_GET['file']) || !isset($_GET['grade']) || !isset($_GET['subject_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

$file = basename($_GET['file']);
$grade = $_GET['grade'];
$subjectId = (int)$_GET['subject_id'];

$examFile = __DIR__ . "/../exams/{$grade}/subject_{$subjectId}/{$file}";

if (!file_exists($examFile)) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đề thi']);
    exit;
}

$examData = json_decode(file_get_contents($examFile), true);

if (!$examData) {
    echo json_encode(['success' => false, 'message' => 'Không thể đọc dữ liệu đề thi']);
    exit;
}

echo json_encode(['success' => true, 'exam' => $examData]);
