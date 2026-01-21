<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);
$presentationId = $input['presentation_id'] ?? '';

if (empty($presentationId)) {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
    exit;
}

// Load presentations
$presentationsFile = __DIR__ . '/../../../data/presentations.json';
$presentations = file_exists($presentationsFile) ? json_decode(file_get_contents($presentationsFile), true) : [];

// Find and delete
$found = false;
$filtered = [];
foreach ($presentations as $pres) {
    if ($pres['id'] === $presentationId && $pres['teacher_username'] === $username) {
        $found = true;
        continue; // Skip this one (delete it)
    }
    $filtered[] = $pres;
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Presentation not found']);
    exit;
}

// Save
if (file_put_contents($presentationsFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Presentation deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete']);
}
?>
