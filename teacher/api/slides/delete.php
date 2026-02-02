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
    
    // Get presentation to verify ownership
    $presentation = $storage->getById($presentationId);
    
    if (!$presentation) {
        echo json_encode(['success' => false, 'message' => 'Presentation not found']);
        exit;
    }
    
    if ($presentation['teacher_username'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'Not authorized to delete this presentation']);
        exit;
    }
    
    // Delete presentation
    $storage->delete($presentationId);
    
    echo json_encode(['success' => true, 'message' => 'Presentation deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
