<?php
/**
 * Script CLI để rebuild shared/scores/student_score.json từ các file cá nhân.
 * Dùng khi cần phục hồi chỉ mục sau lỗi mất dữ liệu (lost update) cũ.
 *
 * Cách chạy:  php shared/api/rebuild_student_score.php [--force]
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/score_index.php';

$force = in_array('--force', $argv, true);

$indexFile = score_index_file();
echo "Rebuild student_score.json từ các file cá nhân...\n\n";

// Backup file hiện tại nếu có
if (file_exists($indexFile)) {
    $backupFile = dirname($indexFile) . '/student_score_backup_' . date('Ymd_His') . '.json';
    if (copy($indexFile, $backupFile)) {
        echo "Đã backup chỉ mục cũ: $backupFile\n\n";
    }
}

$r = rebuild_student_score_index($force);

echo "--- Kết quả ---\n";
if (empty($r['ok'])) {
    echo "LỖI: " . ($r['error'] ?? 'Không xác định') . "\n";
    exit(1);
}
echo "Rebuild: " . ($r['rebuilt'] ? 'CÓ' : 'KHÔNG (chỉ mục đã mới)') . "\n";
echo "Học sinh: " . $r['students'] . "\n";
echo "Bản ghi chỉ mục: " . $r['entries'] . "\n";
echo "File: $indexFile\n";
echo "\nHoàn tất!\n";
