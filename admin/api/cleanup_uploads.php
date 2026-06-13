<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$uploadsRoot = realpath(__DIR__ . '/../../uploads');
if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
    echo json_encode([
        'success' => true,
        'root_exists' => false,
        'total' => ['files' => 0, 'bytes' => 0],
        'directories' => []
    ]);
    exit;
}

$protectedFiles = ['.htaccess', 'index.html', 'index.php'];

function isPathInside($path, $root) {
    $realPath = realpath($path);
    if ($realPath === false) {
        return false;
    }

    return $realPath === $root || strpos($realPath, $root . DIRECTORY_SEPARATOR) === 0;
}

function getDirectoryStats($dir, $root, $protectedFiles) {
    $stats = [
        'files' => 0,
        'bytes' => 0,
        'last_modified' => null
    ];

    if (!is_dir($dir) || !isPathInside($dir, $root)) {
        return $stats;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        if (in_array($item->getFilename(), $protectedFiles, true)) {
            continue;
        }

        $stats['files']++;
        $stats['bytes'] += $item->getSize();
        $mtime = $item->getMTime();
        if ($stats['last_modified'] === null || $mtime > $stats['last_modified']) {
            $stats['last_modified'] = $mtime;
        }
    }

    return $stats;
}

function removeEmptyDirectories($dir, $root) {
    if (!is_dir($dir) || !isPathInside($dir, $root)) {
        return 0;
    }

    $removed = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if (!$item->isDir()) {
            continue;
        }

        $path = $item->getPathname();
        if (@rmdir($path)) {
            $removed++;
        }
    }

    return $removed;
}

function deleteDirectoryUploads($dir, $root, $protectedFiles) {
    $result = [
        'deleted_files' => 0,
        'deleted_bytes' => 0,
        'removed_dirs' => 0,
        'errors' => []
    ];

    if (!is_dir($dir) || !isPathInside($dir, $root)) {
        $result['errors'][] = 'Invalid upload directory';
        return $result;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        if (in_array($item->getFilename(), $protectedFiles, true)) {
            continue;
        }

        $path = $item->getPathname();
        $size = $item->getSize();
        if (@unlink($path)) {
            $result['deleted_files']++;
            $result['deleted_bytes'] += $size;
        } else {
            $result['errors'][] = 'Không xoá được file: ' . str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        }
    }

    $result['removed_dirs'] = removeEmptyDirectories($dir, $root);
    return $result;
}

function listUploadDirectories($root, $protectedFiles) {
    $directories = [];
    $totalFiles = 0;
    $totalBytes = 0;

    foreach (scandir($root) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $path = $root . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($path)) {
            continue;
        }

        $stats = getDirectoryStats($path, $root, $protectedFiles);
        $totalFiles += $stats['files'];
        $totalBytes += $stats['bytes'];
        $directories[] = [
            'name' => $name,
            'files' => $stats['files'],
            'bytes' => $stats['bytes'],
            'last_modified' => $stats['last_modified'] ? date('Y-m-d H:i:s', $stats['last_modified']) : null
        ];
    }

    usort($directories, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    return [
        'success' => true,
        'root_exists' => true,
        'total' => ['files' => $totalFiles, 'bytes' => $totalBytes],
        'directories' => $directories
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(listUploadDirectories($uploadsRoot, $protectedFiles), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$confirm = trim((string)($input['confirm'] ?? ''));
if ($confirm !== 'DELETE UPLOADS') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đúng mã xác nhận: DELETE UPLOADS']);
    exit;
}

$requestedDirs = $input['directories'] ?? [];
if (!is_array($requestedDirs)) {
    echo json_encode(['success' => false, 'message' => 'Invalid directories']);
    exit;
}

$availableDirs = [];
foreach (scandir($uploadsRoot) as $name) {
    if ($name === '.' || $name === '..') {
        continue;
    }

    $path = $uploadsRoot . DIRECTORY_SEPARATOR . $name;
    if (is_dir($path)) {
        $availableDirs[$name] = $path;
    }
}

if (($input['all'] ?? false) === true) {
    $targetDirs = array_keys($availableDirs);
} else {
    $targetDirs = array_values(array_unique(array_filter($requestedDirs, 'is_string')));
}

if (empty($targetDirs)) {
    echo json_encode(['success' => false, 'message' => 'Chưa chọn thư mục upload nào để xoá']);
    exit;
}

$summary = [
    'success' => true,
    'deleted_files' => 0,
    'deleted_bytes' => 0,
    'removed_dirs' => 0,
    'errors' => [],
    'directories' => []
];

foreach ($targetDirs as $dirName) {
    if (!isset($availableDirs[$dirName])) {
        $summary['errors'][] = 'Thư mục không hợp lệ: ' . $dirName;
        continue;
    }

    $result = deleteDirectoryUploads($availableDirs[$dirName], $uploadsRoot, $protectedFiles);
    $summary['deleted_files'] += $result['deleted_files'];
    $summary['deleted_bytes'] += $result['deleted_bytes'];
    $summary['removed_dirs'] += $result['removed_dirs'];
    $summary['errors'] = array_merge($summary['errors'], $result['errors']);
    $summary['directories'][] = [
        'name' => $dirName,
        'deleted_files' => $result['deleted_files'],
        'deleted_bytes' => $result['deleted_bytes'],
        'removed_dirs' => $result['removed_dirs']
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE);
