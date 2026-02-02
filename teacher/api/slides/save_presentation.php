<?php
/**
 * API: Save Presentation
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../includes/PresentationStorage.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('Invalid presentation data');
    }
    
    $username = $_SESSION['username'];
    
    // Security check: Only owner can save
    if ($input['teacher_username'] !== $username) {
        throw new Exception('Not authorized to save this presentation');
    }
    
    // Update timestamp
    $input['updated_at'] = date('Y-m-d H:i:s');
    
    // Save using storage class
    $storage = new PresentationStorage();
    $storage->save($input);
    
    echo json_encode([
        'success' => true,
        'message' => 'Presentation saved successfully',
        'presentation_id' => $input['id']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
