<?php
session_start();

// Directory for storing remote control data
$data_dir = '../../data/remote_control';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

header('Content-Type: application/json');

// Handle GET request (polling for commands)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $session = $_GET['session'] ?? '';
    if (empty($session)) {
        echo json_encode(['error' => 'Missing session parameter']);
        exit;
    }

    $commands_file = $data_dir . '/' . $session . '_commands.json';

    if (file_exists($commands_file)) {
        $commands = json_decode(file_get_contents($commands_file), true);
        // Clear commands after reading (one-time execution)
        file_put_contents($commands_file, json_encode([]));
        echo json_encode(['commands' => $commands]);
    } else {
        echo json_encode(['commands' => []]);
    }
    exit;
}

// Handle POST request (sending commands)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $session = $input['session'] ?? '';
    $command = $input['command'] ?? '';

    if (empty($session) || empty($command)) {
        echo json_encode(['success' => false, 'message' => 'Missing session or command']);
        exit;
    }

    $commands_file = $data_dir . '/' . $session . '_commands.json';

    // Load existing commands
    $commands = [];
    if (file_exists($commands_file)) {
        $commands = json_decode(file_get_contents($commands_file), true);
    }

    // Add new command
    $commands[] = [
        'type' => $command,
        'timestamp' => time(),
        'id' => uniqid()
    ];

    // Save commands
    if (file_put_contents($commands_file, json_encode($commands))) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save command']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>
