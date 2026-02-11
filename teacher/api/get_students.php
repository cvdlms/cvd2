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

// Auto-detect base path (works for any project folder name)
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('#^(/[^/]+)/teacher/#', $requestUri, $matches)) {
    $basePath = $matches[1];
} else {
    // Fallback: try to detect from SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (preg_match('#^(/[^/]+)/#', $scriptName, $matches)) {
        $basePath = $matches[1];
    } else {
        $basePath = '';
    }
}

$studentsFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/students.json';
$classesFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/classes.json';
$teacherClassesFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/teacher_classes.json';

if (!file_exists($studentsFile)) {
    echo json_encode(['success' => false, 'message' => 'Students file not found']);
    exit;
}

$content = file_get_contents($studentsFile);
$content = ltrim($content, "\xEF\xBB\xBF"); // remove BOM if present
$students = json_decode($content, true) ?: [];
$classes = [];
if (file_exists($classesFile)) {
    $content_c = file_get_contents($classesFile);
    $content_c = ltrim($content_c, "\xEF\xBB\xBF"); // remove BOM if present
    $classes = json_decode($content_c, true) ?: [];
}

$teacher_classes = [];
if (file_exists($teacherClassesFile)) {
    $content_tc = file_get_contents($teacherClassesFile);
    $content_tc = ltrim($content_tc, "\xEF\xBB\xBF"); // remove BOM if present
    $teacher_classes = json_decode($content_tc, true) ?: [];
}

$assigned_class_ids = $teacher_classes[$teacher_username] ?? [];

// Create class lookup
$classLookup = [];
foreach ($classes as $class) {
    $classLookup[$class['id']] = $class;
}

// Get class_ids param (single or comma-separated)
$class_ids_str = $_GET['class_ids'] ?? ($_GET['class_id'] ?? '');
$class_ids = array_filter(explode(',', $class_ids_str));

if (empty($class_ids)) {
    // Default to all assigned
    $class_ids = $assigned_class_ids;
}

if (empty($class_ids)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Verify all requested class_ids are assigned to teacher
$valid_class_ids = array_intersect($class_ids, $assigned_class_ids);
if (count($valid_class_ids) !== count($class_ids)) {
    echo json_encode(['success' => false, 'message' => 'Access denied to some classes']);
    exit;
}

// Filter students by valid class_ids
$filtered_students = array_filter($students, function($student) use ($valid_class_ids) {
    return in_array($student['class_id'], $valid_class_ids);
});

// Add class info and scores to each student
foreach ($filtered_students as &$student) {
    $classInfo = $classLookup[$student['class_id']] ?? null;
    $student['class_name'] = $classInfo ? $classInfo['name'] : 'Unknown';
    $student['class_code'] = $classInfo ? $classInfo['code'] : 'Unknown';
    $student['tx1'] = 'N/A';
    $student['tx2'] = 'N/A';
    $student['homework'] = 'N/A';
}

$filtered_students = array_values($filtered_students); // Reset keys

if ($filtered_students === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid students data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $filtered_students]);
?>
