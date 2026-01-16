<?php
// Keep session alive
session_start();

if (!isset($_SESSION['username'])) {
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