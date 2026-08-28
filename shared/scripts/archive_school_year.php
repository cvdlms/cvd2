<?php
/**
 * CVD LMS - Lưu trữ dài hạn dữ liệu theo năm học (chuẩn kiểm tra: giữ tối thiểu 5 năm).
 *
 * Chạy vào cuối năm học (hoặc bất cứ lúc nào) để chụp bản lưu trữ có kiểm chứng:
 *   - Điểm kiểm tra      : shared/scores/            -> archives/<nam-hoc>/scores/
 *   - Kết quả luyện tập  : data/practice_results/    -> archives/<nam-hoc>/practice_results/
 *                          shared/practices/         -> archives/<nam-hoc>/practices/
 *   - Kho câu hỏi        : teacher/questions/        -> archives/<nam-hoc>/questions/
 *   - Đề thi             : teacher/exams/            -> archives/<nam-hoc>/exams/
 *
 * Mỗi lần chạy tạo manifest.json (kèm sha256 từng file) để đối chiếu toàn vẹn,
 * và cập nhật archives/index.json. Không xóa/sửa dữ liệu nguồn.
 *
 * Chạy CLI:
 *   php archive_school_year.php                 -> lưu trữ năm học vừa kết thúc
 *   php archive_school_year.php 2024-2025      -> lưu trữ năm học chỉ định
 *   php archive_school_year.php 2024-2025 --force -> chụp lại (ghi đè manifest)
 */

if (PHP_SAPI !== 'cli') {
    echo "Chỉ chạy được qua CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../../includes/json_db_helper.php';
require_once __DIR__ . '/../../includes/school_year.php';

$root = dirname(__DIR__, 2);

// Xác định năm học cần lưu trữ
$targetYear = null;
foreach (array_slice($argv, 1) as $arg) {
    if (school_year_is_valid($arg)) {
        $targetYear = $arg;
    }
}
if ($targetYear === null) {
    // Mặc định: năm học vừa kết thúc (năm trước năm học hiện tại)
    $current = get_current_school_year();
    $startYear = (int)substr($current, 0, 4) - 1;
    $targetYear = $startYear . '-' . ($startYear + 1);
}

$force = in_array('--force', $argv, true);
$archiveDir = school_year_archive_dir($targetYear);
$manifestFile = $archiveDir . 'manifest.json';
$indexFile = CVD_ARCHIVE_DIR . 'index.json';

if (!$force && file_exists($manifestFile)) {
    echo "Năm học {$targetYear} đã được lưu trữ ({$manifestFile}). Dùng --force để chụp lại.\n";
    exit(1);
}

echo "=== Lưu trữ năm học {$targetYear} ===\n";
echo "Đích: {$archiveDir}\n\n";

/** Sao chép danh sách file, trả về mục ghi nhận cho manifest. */
function archive_copy_files(array $files, $srcBase, $destBase, array &$entries, &$copiedBytes) {
    foreach ($files as $file) {
        $relative = ltrim(substr($file, strlen($srcBase)), '/\\');
        $dest = $destBase . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        $destParent = dirname($dest);
        if (!is_dir($destParent)) {
            @mkdir($destParent, 0755, true);
        }
        if (@copy($file, $dest)) {
            $content = @file_get_contents($dest);
            $entries[] = [
                'path' => $relative,
                'size' => filesize($dest),
                'sha256' => hash('sha256', $content !== false ? $content : '')
            ];
            $copiedBytes += filesize($dest);
        }
    }
}

$entries = [];
$copiedBytes = 0;
$fileCount = 0;

// 1. Điểm kiểm tra
$scoresDir = $root . '/shared/scores/';
if (is_dir($scoresDir)) {
    $files = array_filter(glob($scoresDir . '*.json') ?: [], function($f) {
        return !preg_match('/backup|\.old/i', basename($f));
    });
    archive_copy_files($files, $scoresDir, $archiveDir . 'scores/', $entries, $copiedBytes);
    $fileCount += count($files);
}

// 2. Kết quả luyện tập
foreach ([
    $root . '/data/practice_results/' => $archiveDir . 'practice_results/',
    $root . '/shared/practices/'      => $archiveDir . 'practices/'
] as $src => $dest) {
    if (is_dir($src)) {
        $files = glob($src . '*.json') ?: [];
        archive_copy_files($files, $src, $dest, $entries, $copiedBytes);
        $fileCount += count($files);
    }
}

// 3. Kho câu hỏi + đề thi (snapshot toàn bộ cây thư mục)
foreach ([
    $root . '/teacher/questions/' => $archiveDir . 'questions/',
    $root . '/teacher/exams/'     => $archiveDir . 'exams/'
] as $src => $dest) {
    if (!is_dir($src)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $files = [];
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'json') {
            $files[] = $fileInfo->getPathname();
        }
    }
    archive_copy_files($files, $src, $dest, $entries, $copiedBytes);
    $fileCount += count($files);
}

if ($fileCount === 0) {
    echo "Không tìm thấy dữ liệu nào để lưu trữ.\n";
    exit(1);
}

// 4. Ghi manifest kèm checksum
$manifest = [
    'school_year' => $targetYear,
    'generated_at' => date('Y-m-d H:i:s'),
    'generated_by' => 'archive_school_year.php',
    'retention_years' => 5,
    'keep_until' => ((int)substr($targetYear, 5, 4)) . '-12-31',
    'file_count' => count($entries),
    'total_bytes' => $copiedBytes,
    'files' => $entries
];
if (!save_json_data($manifestFile, $manifest)) {
    echo "LỖI: không ghi được manifest.json\n";
    exit(1);
}
echo "Đã sao chép " . count($entries) . "/{$fileCount} file (" . round($copiedBytes / 1024 / 1024, 2) . " MB)\n";
echo "Manifest: {$manifestFile}\n";

// 5. Cập nhật sổ đăng ký lưu trữ
update_json_data($indexFile, function($index) use ($targetYear, $manifest) {
    if (!is_array($index)) { $index = ['years' => []]; }
    if (!isset($index['years'])) { $index['years'] = []; }
    $index['years'][$targetYear] = [
        'archived_at' => $manifest['generated_at'],
        'file_count' => $manifest['file_count'],
        'total_bytes' => $manifest['total_bytes'],
        'keep_until' => $manifest['keep_until']
    ];
    return $index;
}, ['years' => []]);
echo "Sổ đăng ký: {$indexFile}\n";
echo "Hoàn tất. Giữ tối thiểu đến " . $manifest['keep_until'] . " (5 năm).\n";
