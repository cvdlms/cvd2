<?php
include '../includes/session_check.php'; // Ensure logged in

// Only teachers (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 300);

$default_rules = [
    ['min' => 0,   'max' => 3.5, 'comment' => 'Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn.'],
    ['min' => 3.5, 'max' => 5.0, 'comment' => 'Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn.'],
    ['min' => 5.0, 'max' => 6.5, 'comment' => 'Có cố gắng, cần phát huy thêm.'],
    ['min' => 6.5, 'max' => 8.0, 'comment' => 'Siêng học, cần phát huy thêm.'],
    ['min' => 8.0, 'max' => 10.0, 'comment' => 'Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh.']
];

$comment_rules = isset($_POST['comment_rules']) ? json_decode($_POST['comment_rules'], true) : $default_rules;
if (!$comment_rules) $comment_rules = $default_rules;

function logDebug($message) {
    $logFile = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function find_python_executable() {
    $candidates = [
        '/usr/bin/python3',
        '/usr/bin/python',
        'python3',
        'python'
    ];
    foreach ($candidates as $c) {
        $testCmd = escapeshellcmd($c) . ' -c "import sys; print(1)" 2>&1';
        exec($testCmd, $out, $rc);
        if ($rc === 0) return $c;
    }
    $windowsCandidates = [
        'C:\\Python39\\python.exe',
        'C:\\Python38\\python.exe',
        'C:\\Python37\\python.exe'
    ];
    foreach ($windowsCandidates as $c) {
        if (file_exists($c)) return $c;
    }
    return null;
}

function processWithWorker($inputFile, $outputFile, $commentRules) {
    $pythonScript = __DIR__ . '/minimal_comment_tool/comment_worker.py';
    if (!file_exists($pythonScript)) {
        return ['success' => false, 'message' => 'Worker script not found'];
    }

    $pythonExec = find_python_executable();
    if (!$pythonExec) {
        return ['success' => false, 'message' => 'Python executable not found on server. Install Python or run locally.'];
    }

    $inputData = [
        'input_file' => $inputFile,
        'output_file' => $outputFile,
        'comment_rules' => $commentRules
    ];

    $cmd = escapeshellcmd($pythonExec) . ' ' . escapeshellarg($pythonScript);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($process)) {
        return ['success' => false, 'message' => 'Failed to start Python process'];
    }

    fwrite($pipes[0], json_encode($inputData));
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    logDebug("Worker stdout: $output");
    if (!empty($error)) logDebug("Worker stderr: $error");
    logDebug("Worker return code: $returnCode");

    $result = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON output from worker: ' . $output . ' ' . $error];
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
        exit;
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'xlsx') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only .xlsx accepted']);
        exit;
    }

    try {
        $tempDir = sys_get_temp_dir() . '/cvd_min_worker/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $inputFile = $file['tmp_name'];
        $originalName = $file['name'];
        $outputFile = $tempDir . 'processed_' . time() . '_' . basename($originalName);

        logDebug("Processing with minimal worker: $inputFile -> $outputFile");

        $result = processWithWorker($inputFile, $outputFile, $comment_rules);

        if ($result['success']) {
            $fileSize = filesize($outputFile);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'downloadUrl' => 'download_processed.php?file=' . urlencode(basename($outputFile)),
                'fileSize' => round($fileSize / 1024 / 1024, 2),
                'message' => 'File processed successfully by minimal worker'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error processing file: ' . ($result['message'] ?? 'Unknown')]);
        }

    } catch (Exception $e) {
        logDebug('Error: ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error processing file: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

?>
