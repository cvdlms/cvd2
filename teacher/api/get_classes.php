<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Verify teacher session
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_username = $_SESSION['username'];
$classesFile = $_SERVER['DOCUMENT_ROOT'] . '/cvd2/admin/classes.json';
$teacherClassesFile = $_SERVER['DOCUMENT_ROOT'] . '/cvd2/admin/teacher_classes.json';

if (!file_exists($classesFile)) {
    echo json_encode(['success' => false, 'message' => 'Classes file not found']);
    exit;
}

if (!file_exists($teacherClassesFile)) {
    echo json_encode(['success' => false, 'message' => 'Teacher classes file not found']);
    exit;
}

$teacher_classes = [];
$content_tc = file_get_contents($teacherClassesFile);
$content_tc = ltrim($content_tc, "\xEF\xBB\xBF"); // remove BOM if present
$teacher_classes = json_decode($content_tc, true) ?: [];

$assigned_class_ids = $teacher_classes[$teacher_username] ?? [];

if (empty($assigned_class_ids)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$content = file_get_contents($classesFile);
$content = ltrim($content, "\xEF\xBB\xBF"); // remove BOM if present
$all_classes = json_decode($content, true) ?: [];

// Filter classes to only assigned ones
$filtered_classes = array_filter($all_classes, function($class) use ($assigned_class_ids) {
    return in_array($class['id'], $assigned_class_ids);
});

$filtered_classes = array_values($filtered_classes); // Reset keys

if ($filtered_classes === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid classes data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $filtered_classes]);
?>
