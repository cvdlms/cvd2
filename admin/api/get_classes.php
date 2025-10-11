<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
