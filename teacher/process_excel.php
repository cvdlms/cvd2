<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Increase memory limit for large Excel files
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 300); // 5 minutes

// Cấu hình mốc điểm và nhận xét - load from localStorage or use defaults
$default_rules = [
    ['min' => 0,   'max' => 3.5, 'comment' => 'Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn.'],
    ['min' => 3.5, 'max' => 5.0, 'comment' => 'Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn.'],
    ['min' => 5.0, 'max' => 6.5, 'comment' => 'Có cố gắng, cần phát huy thêm.'],
    ['min' => 6.5, 'max' => 8.0, 'comment' => 'Siêng học, cần phát huy thêm.'],
    ['min' => 8.0, 'max' => 10.0, 'comment' => 'Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh.']
];

// Try to load custom rules from POST data or use defaults
$comment_rules = isset($_POST['comment_rules']) ? json_decode($_POST['comment_rules'], true) : $default_rules;
if (!$comment_rules) {
    $comment_rules = $default_rules;
}

// Hàm ghi log debug
function logDebug($message) {
    $logFile = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Hàm xử lý Excel bằng Python/LibreOffice
function processExcelWithPython($inputFile, $outputFile, $commentRules) {
    $pythonScript = __DIR__ . '/process_excel.py';

    // Kiểm tra Python script tồn tại
    if (!file_exists($pythonScript)) {
        return [
            'success' => false,
            'message' => 'Python processing script not found'
        ];
    }

    // Tạo dữ liệu input cho Python script
    $inputData = [
        'input_file' => $inputFile,
        'output_file' => $outputFile,
        'comment_rules' => $commentRules
    ];

    // Chạy Python script
    $cmd = "\"C:\\Python313\\python.exe\" \"$pythonScript\"";
    $descriptors = [
        0 => ['pipe', 'r'], // stdin
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];

    $process = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($process)) {
        return [
            'success' => false,
            'message' => 'Failed to start Python process'
        ];
    }

    // Gửi dữ liệu input
    fwrite($pipes[0], json_encode($inputData));
    fclose($pipes[0]);

    // Đọc output
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    logDebug("Python script output: $output");
    if (!empty($error)) {
        logDebug("Python script error: $error");
    }
    logDebug("Python script return code: $returnCode");

    // Parse JSON output
    $result = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON output from Python script: ' . $output
        ];
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi upload file: ' . $file['error']
        ]);
        exit;
    }

    // Kiểm tra định dạng file
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'xlsx') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Chỉ chấp nhận file .xlsx'
        ]);
        exit;
    }

    try {
        // Create temp directory for processing
        $tempDir = sys_get_temp_dir() . '/cvd_excel_processing/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $inputFile = $file['tmp_name'];
        $originalName = $file['name'];
        $outputFile = $tempDir . 'processed_' . time() . '_' . basename($originalName);

        logDebug("Starting Excel processing: $inputFile -> $outputFile");

        // Xử lý file bằng Python/LibreOffice
        $result = processExcelWithPython($inputFile, $outputFile, $comment_rules);

        if ($result['success']) {
            // Get file size for logging
            $fileSize = filesize($outputFile);
            logDebug("Processed file size: " . round($fileSize / 1024 / 1024, 2) . " MB");

            // Return JSON response with download URL
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'downloadUrl' => 'download_processed.php?file=' . urlencode(basename($outputFile)),
                'fileSize' => round($fileSize / 1024 / 1024, 2) . ' MB',
                'message' => 'File processed successfully with LibreOffice/Python!'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error processing file: ' . ($result['message'] ?? 'Unknown error')
            ]);
        }

    } catch (Exception $e) {
        logDebug("Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error processing file: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
