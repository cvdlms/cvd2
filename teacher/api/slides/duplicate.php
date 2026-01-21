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

// Find original
$original = null;
foreach ($presentations as $pres) {
    if ($pres['id'] === $presentationId && $pres['teacher_username'] === $username) {
        $original = $pres;
        break;
    }
}

if (!$original) {
    echo json_encode(['success' => false, 'message' => 'Presentation not found']);
    exit;
}

// Create duplicate
$newId = 'pres_' . uniqid();
$duplicate = $original;
$duplicate['id'] = $newId;
$duplicate['title'] = $original['title'] . ' (Bản sao)';
$duplicate['created_at'] = date('Y-m-d H:i:s');
$duplicate['updated_at'] = date('Y-m-d H:i:s');
$duplicate['statistics'] = [
    'total_views' => 0,
    'unique_viewers' => 0,
    'total_presentations' => 0,
    'avg_completion_rate' => 0,
    'avg_time_spent' => 0
];

// Add to list
$presentations[] = $duplicate;

// Save
if (file_put_contents($presentationsFile, json_encode($presentations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Presentation duplicated successfully',
        'presentation_id' => $newId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to duplicate']);
}
?>
