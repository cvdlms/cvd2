<?php
require_once __DIR__ . '/../../includes/api_auth.php';
requireTeacherSession();

header('Content-Type: application/json');
$data_dir = realpath(__DIR__ . '/../../data/remote_control');
$writable = false;
$can_create = false;
$test_file = '';
if ($data_dir && is_dir($data_dir)) {
    $writable = is_writable($data_dir);
    // try to write a small temp file
    $test_file = $data_dir . DIRECTORY_SEPARATOR . 'perm_test_' . uniqid() . '.tmp';
    $can_create = (file_put_contents($test_file, "ok") !== false);
    if ($can_create) unlink($test_file);
}

echo json_encode([
    'exists' => is_dir($data_dir),
    'writable' => $writable,
    'can_create_file' => $can_create,
]);
