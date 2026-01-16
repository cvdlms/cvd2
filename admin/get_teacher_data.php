<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    http_response_code(403);
    exit;
}

if (!isset($_GET['username'])) {
    http_response_code(400);
    exit;
}

$username = $_GET['username'];
$usersFile = 'user.json';
$users = json_decode(file_get_contents($usersFile), true) ?: [];

if (!isset($users[$username])) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
echo json_encode($users[$username]);
?>
