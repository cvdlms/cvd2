<?php
// Simple status endpoint for remote control acknowledgements
// POST { session, status, message }
// GET ?session=... returns last status object

header('Content-Type: application/json');

$dataDir = __DIR__ . '/../../data/remote_control';
if (!is_dir($dataDir)) {
    echo json_encode(['success' => false, 'message' => 'data dir missing']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $session = $body['session'] ?? '';
    $status = $body['status'] ?? '';
    $message = $body['message'] ?? '';

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'missing session']);
        exit;
    }

    $file = $dataDir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $session) . '_status.json';
    $obj = ['status' => $status, 'message' => $message, 'ts' => time()];
    $json = json_encode($obj);
    $ok = file_put_contents($file, $json, LOCK_EX);
    if ($ok === false) {
        echo json_encode(['success' => false, 'message' => 'write failed']);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

// GET
$session = $_GET['session'] ?? '';
if (!$session) {
    echo json_encode(['success' => false, 'message' => 'missing session']);
    exit;
}

$file = $dataDir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $session) . '_status.json';
if (!file_exists($file)) {
    echo json_encode(['success' => true, 'status' => null]);
    exit;
}
$contents = @file_get_contents($file);
if ($contents === false) {
    echo json_encode(['success' => false, 'message' => 'read failed']);
    exit;
}
echo json_encode(['success' => true, 'status' => json_decode($contents, true)]);

?>
