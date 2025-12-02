<?php
/**
 * download_source.php
 * Allows teachers to download the source files as a ZIP archive
 */

// Get the files to include in the ZIP
$files_to_zip = [
    'socketio_server.py',
    'run_local_server.py',
    'run_local_server.bat',
    'static/socketio_client.html',
    'requirements_socketio.txt',
    'README_FOR_TEACHER.md',
];

$base_path = __DIR__;
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
        $zip->addFile($full_path, basename($full_path));
    }
}

$zip->close();

// Send file to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="PowerPoint_Remote_Control.zip"');
header('Content-Length: ' . filesize($zip_file));

readfile($zip_file);

// Clean up
unlink($zip_file);
?>
