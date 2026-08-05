<?php
// Keep session alive
require_once __DIR__ . '/../includes/student_session.php';

if (!isset($_SESSION['student_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Update last activity
$_SESSION['LAST_ACTIVITY'] = time();

echo json_encode([
    'success' => true,
    'last_activity' => $_SESSION['LAST_ACTIVITY'],
    'student_code' => $_SESSION['student_code']
]);
?>