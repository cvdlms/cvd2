<?php
header('Content-Type: application/json');

$teacher_username = $_GET['teacher_username'] ?? '';

if (empty($teacher_username)) {
    echo json_encode([]);
    exit;
}

// Auto-detect base path
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('#^(/[^/]+)/admin/#', $requestUri, $matches)) {
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

$teacher_classesFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/teacher_classes.json';
$teacher_classes = json_decode(file_get_contents($teacher_classesFile), true) ?: [];

$assigned_classes = $teacher_classes[$teacher_username] ?? [];

echo json_encode(['success' => true, 'data' => $assigned_classes]);
?>
