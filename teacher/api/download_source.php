<?php
/**
 * download_source.php
 * Allows teachers to download the source files as a ZIP archive
 */

// Files to include in the ZIP (relative to teacher directory, not api/)
$files_to_zip = [
    'socketio_server.py',
    'run_local_server.py',
    'run_local_server.bat',
    'static/socketio_client.html',
    'static/qr_connect.html',
    'requirements_socketio.txt',
    'README_FOR_TEACHER.md',
];

// Base path is parent directory (teacher/) not api/
$base_path = dirname(__DIR__);
$zip_file = sys_get_temp_dir() . '/PowerPoint_Remote_Control.zip';

// Create ZIP
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die('Failed to create ZIP file');
}

foreach ($files_to_zip as $file) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        // Add file preserving relative path inside the ZIP so 'static/' folder is kept
        $zip->addFile($full_path, $file);
    } else {
        error_log("File not found: $full_path");
    }
}

// Also include the minimal_comment_tool directory (recursively) so shipped configs
// like comment_rules.json are included in the download ZIP.
$extras_dir = 'minimal_comment_tool';
$extras_path = $base_path . '/' . $extras_dir;
if (is_dir($extras_path)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extras_path));
    foreach ($rii as $fileinfo) {
        if ($fileinfo->isFile()) {
            $full = $fileinfo->getRealPath();
            // Create local name inside zip preserving the directory
            $local = $extras_dir . '/' . substr($full, strlen($extras_path) + 1);
            $zip->addFile($full, $local);
        }
    }
}

$zip->close();

// Check if ZIP is not empty
if (filesize($zip_file) == 0) {
    http_response_code(500);
    die('ZIP file is empty. No files were added.');
}

// Send file to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="PowerPoint_Remote_Control.zip"');
header('Content-Length: ' . filesize($zip_file));

readfile($zip_file);

// Clean up
@unlink($zip_file);
?>
