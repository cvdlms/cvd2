<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../includes/PresentationStorage.php';

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);
$presentationId = $input['presentation_id'] ?? '';

if (empty($presentationId)) {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
    exit;
}

try {
    $storage = new PresentationStorage();
    
    // Get original presentation
    $original = $storage->getById($presentationId);
    
    if (!$original) {
        echo json_encode(['success' => false, 'message' => 'Presentation not found']);
        exit;
    }
    
    if ($original['teacher_username'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
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
    
    // Save duplicate
    $storage->save($duplicate);
    
    echo json_encode([
        'success' => true,
        'message' => 'Presentation duplicated successfully',
        'presentation_id' => $newId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
