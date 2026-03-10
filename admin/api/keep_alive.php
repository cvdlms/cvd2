<?php
/**
 * Keep Alive API
 * Giữ session admin không bị timeout
 */

session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Update last activity
$_SESSION['LAST_ACTIVITY'] = time();

echo json_encode([
    'success' => true,
    'last_activity' => $_SESSION['LAST_ACTIVITY'],
    'username' => $_SESSION['username']
]);
?>
