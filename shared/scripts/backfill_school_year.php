<?php
/**
 * CVD LMS - Backfill nhãn năm học (school_year) cho dữ liệu điểm/luyện tập cũ.
 *
 * Yêu cầu lưu trữ 5 năm: mọi bản ghi phải có `school_year`. Script này quét các
 * nguồn lưu trữ hiện có và gắn nhãn theo trường `timestamp` của từng bản ghi.
 *
 * Chạy CLI:  php backfill_school_year.php
 *           php backfill_school_year.php --dry   (chỉ thống kê, không ghi)
 */

if (PHP_SAPI !== 'cli') {
    echo "Chỉ chạy được qua CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../../includes/json_db_helper.php';
require_once __DIR__ . '/../../includes/school_year.php';

$dry = in_array('--dry', $argv, true);
$root = dirname(__DIR__, 2);

$sources = [
    'scores'    => $root . '/shared/scores/',
    'practices' => $root . '/shared/practices/',
];

$stats = ['files' => 0, 'records' => 0, 'stamped' => 0, 'skipped_files' => 0];

/** Đệ quy gắn nhãn cho danh sách bản ghi; trả về true nếu có thay đổi. */
function backfill_stamp_list(array &$records, array &$stats) {
    $changed = false;
    foreach ($records as $idx => $record) {
        if (!is_array($record)) {
            continue;
        }
        $stats['records']++;
        if (!isset($record['school_year']) || !school_year_is_valid($record['school_year'])) {
            school_year_stamp_record($record);
            $records[$idx] = $record;
            $stats['stamped']++;
            $changed = true;
        }
    }
    return $changed;
}

/** Xử lý một file JSON: dạng mảng bản ghi hoặc object đơn (file cũ). */
function backfill_process_file($filePath, array &$stats, $dry) {
    $data = get_json_data($filePath, null);
    if ($data === null) {
        $stats['skipped_files']++;
        return;
    }

    if (isset($data[0]) && is_array($data[0])) {
        // Mảng bản ghi
        $changed = backfill_stamp_list($data, $stats);
    } elseif (isset($data['timestamp']) || isset($data['student_code'])) {
        // Object đơn (định dạng file cá nhân cũ)
        $stats['records']++;
        $changed = false;
        if (!isset($data['school_year']) || !school_year_is_valid($data['school_year'])) {
            school_year_stamp_record($data);
            $stats['stamped']++;
            $changed = true;
        }
    } else {
        // Cấu trúc khác (vd: nhóm theo lớp) — thử từng nhóm con là mảng bản ghi
        $changed = false;
        foreach ($data as $key => $group) {
            if (is_array($group) && isset($group[0]) && is_array($group[0])) {
                if (backfill_stamp_list($data[$key], $stats)) {
                    $changed = true;
                }
            }
        }
    }

    if ($changed && !$dry) {
        save_json_data($filePath, $data);
    }
    if ($changed) {
        $stats['files']++;
    }
}

echo "=== Backfill school_year " . ($dry ? "(DRY RUN)" : "") . " ===\n";

foreach ($sources as $label => $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    foreach (glob($dir . '*.json') as $file) {
        $base = basename($file);
        // Bỏ qua file backup/old trong thư mục điểm
        if (preg_match('/backup|\.old|_backup/i', $base)) {
            continue;
        }
        backfill_process_file($file, $stats, $dry);
    }
}

// Kết quả luyện tập trong data/practice_results/
$practiceResultsFile = $root . '/data/practice_results/practice_results.json';
if (file_exists($practiceResultsFile)) {
    backfill_process_file($practiceResultsFile, $stats, $dry);
}

echo "File có thay đổi : {$stats['files']}\n";
echo "Bản ghi quét     : {$stats['records']}\n";
echo "Bản ghi gắn nhãn : {$stats['stamped']}\n";
echo "File bỏ qua      : {$stats['skipped_files']}\n";
echo $dry ? "(Dry run - chưa ghi gì)\n" : "Hoàn tất.\n";
