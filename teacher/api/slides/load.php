<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$presentationId = $_GET['id'] ?? '';

if (empty($presentationId)) {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
    exit;
}

// Load presentations
$presentationsFile = __DIR__ . '/../../../data/presentations.json';
$presentations = file_exists($presentationsFile) ? json_decode(file_get_contents($presentationsFile), true) : [];

// Find presentation
$presentation = null;
foreach ($presentations as $pres) {
    if ($pres['id'] === $presentationId) {
        $presentation = $pres;
        break;
    }
}

if (!$presentation) {
    echo json_encode(['success' => false, 'message' => 'Presentation not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'presentation' => $presentation
]);
?>
