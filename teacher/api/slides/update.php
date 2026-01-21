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

// Find and update
$found = false;
foreach ($presentations as $key => $pres) {
    if ($pres['id'] === $presentationId && $pres['teacher_username'] === $username) {
        // Update fields
        if (isset($input['title'])) $presentations[$key]['title'] = $input['title'];
        if (isset($input['description'])) $presentations[$key]['description'] = $input['description'];
        if (isset($input['slides'])) $presentations[$key]['slides'] = $input['slides'];
        if (isset($input['settings'])) $presentations[$key]['settings'] = $input['settings'];
        if (isset($input['tags'])) $presentations[$key]['tags'] = $input['tags'];
        
        $presentations[$key]['updated_at'] = date('Y-m-d H:i:s');
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Presentation not found']);
    exit;
}

// Save
if (file_put_contents($presentationsFile, json_encode($presentations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Presentation updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save']);
}
?>
