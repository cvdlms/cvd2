<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireAdminSession();
header('Content-Type: application/json');

$classesFile = __DIR__ . '/../classes.json';

if (!file_exists($classesFile)) {
    echo json_encode(['success' => false, 'message' => 'Classes file not found']);
    exit;
}

$classes = json_decode(file_get_contents($classesFile), true);

if ($classes === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid classes data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $classes]);
?>
