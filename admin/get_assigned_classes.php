<?php
header('Content-Type: application/json');

$teacher_username = $_GET['teacher_username'] ?? '';

if (empty($teacher_username)) {
    echo json_encode([]);
    exit;
}

$teacher_classesFile = $_SERVER['DOCUMENT_ROOT'] . '/cvd2/admin/teacher_classes.json';
$teacher_classes = json_decode(file_get_contents($teacher_classesFile), true) ?: [];

$assigned_classes = $teacher_classes[$teacher_username] ?? [];

echo json_encode(['success' => true, 'data' => $assigned_classes]);
?>
