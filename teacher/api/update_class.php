<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

$classesFile = __DIR__ . '/../classes.json';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['code']) || !isset($input['name']) || !isset($input['year']) || !isset($input['teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

$found = false;
foreach ($classes as &$class) {
    if ($class['id'] === $input['id']) {
        // Check if new code conflicts with other classes
        foreach ($classes as $otherClass) {
            if ($otherClass['id'] !== $input['id'] && $otherClass['code'] === $input['code']) {
                echo json_encode(['success' => false, 'message' => 'Class code already exists']);
                exit;
            }
        }

        $class['code'] = trim($input['code']);
        $class['name'] = trim($input['name']);
        $class['year'] = trim($input['year']);
        $class['teacher'] = trim($input['teacher']);
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Class not found']);
    exit;
}

if (file_put_contents($classesFile, json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Class updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update class']);
}
?>
