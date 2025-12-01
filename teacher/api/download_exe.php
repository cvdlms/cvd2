<?php
/**
 * API endpoint to download ppt_controller.exe
 * Usage: GET /cvd2/teacher/api/download_exe.php
 */

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="ppt_controller.exe"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Path to the executable
$exe_path = __DIR__ . '/../dist/ppt_controller.exe';

// Check if file exists
if (!file_exists($exe_path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Error: ppt_controller.exe not found. Please build it first using build_exe.bat';
    exit;
}

// Check if file is readable
if (!is_readable($exe_path)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo 'Error: ppt_controller.exe is not readable';
    exit;
}

// Get file size and serve it
$file_size = filesize($exe_path);
header('Content-Length: ' . $file_size);

// Stream file to client in chunks
$chunk_size = 8192;
$file = fopen($exe_path, 'rb');
if ($file === false) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error: Could not open file';
    exit;
}

while (!feof($file)) {
    echo fread($file, $chunk_size);
    flush();
}
fclose($file);
exit;
