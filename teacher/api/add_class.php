<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$classesFile = __DIR__ . '/../classes.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['code']) || !isset($input['name']) || !isset($input['year']) || !isset($input['teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$classes = [];
if (file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
}

// Check if class code already exists
foreach ($classes as $class) {
    if ($class['code'] === $input['code']) {
        echo json_encode(['success' => false, 'message' => 'Class code already exists']);
        exit;
    }
}

// Generate new ID
$maxId = 0;
foreach ($classes as $class) {
    if ($class['id'] > $maxId) {
        $maxId = $class['id'];
    }
}
$newId = $maxId + 1;

$newClass = [
    'id' => (string)$newId,
    'code' => trim($input['code']),
    'name' => trim($input['name']),
    'year' => trim($input['year']),
    'teacher' => trim($input['teacher'])
];

$classes[] = $newClass;

if (file_put_contents($classesFile, json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Class added successfully', 'data' => $newClass]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save class']);
}
?>
