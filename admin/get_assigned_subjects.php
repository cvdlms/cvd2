<?php
header('Content-Type: application/json');

$teacher_username = $_GET['teacher_username'] ?? '';

if (!$teacher_username) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$teacher_subjects = json_decode(file_get_contents('teacher_subjects.json'), true) ?: [];

$assigned = $teacher_subjects[$teacher_username] ?? [];

echo json_encode(['success' => true, 'data' => $assigned]);
?>
