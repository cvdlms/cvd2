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
        // Clear commands after reading (one-time execution) using exclusive lock
        file_put_contents($commands_file, json_encode([]), LOCK_EX);
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
    // command can be a string type or an object with type+payload
    $command_input = $input['command'] ?? null;
    $payload = $input['payload'] ?? null;

    if (empty($session) || empty($command_input)) {
        echo json_encode(['success' => false, 'message' => 'Missing session or command']);
        exit;
    }

    $command_type = is_string($command_input) ? $command_input : ($command_input['type'] ?? null);
    if (empty($command_type)) {
        echo json_encode(['success' => false, 'message' => 'Invalid command format']);
        exit;
    }

    $commands_file = $data_dir . '/' . $session . '_commands.json';

    // Load existing commands
    $commands = [];
    if (file_exists($commands_file)) {
        $commands = json_decode(file_get_contents($commands_file), true);
    }

    // Add new command (include optional payload)
    $cmdObj = [
        'type' => $command_type,
        'timestamp' => time(),
        'id' => uniqid()
    ];
    if (!empty($payload)) {
        $cmdObj['payload'] = $payload;
    } elseif (!is_string($command_input) && is_array($command_input) && isset($command_input['payload'])) {
        $cmdObj['payload'] = $command_input['payload'];
    }
    $commands[] = $cmdObj;

    // Save commands with exclusive lock
    if (file_put_contents($commands_file, json_encode($commands), LOCK_EX) !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save command']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>
