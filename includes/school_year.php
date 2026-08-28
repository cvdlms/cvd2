<?php
/**
 * CVD LMS - School year (năm học) helper
 * Hỗ trợ yêu cầu lưu trữ dữ liệu tối thiểu 5 năm theo chuẩn hệ thống kiểm tra:
 *  - Mọi bản ghi điểm/luyện tập được gắn nhãn `school_year` khi lưu.
 *  - Suy ra năm học từ timestamp để backfill dữ liệu cũ.
 *  - Cung cấp đường dẫn kho lưu trữ dài hạn (archives) cho quy trình cuối năm học.
 *
 * Quy ước năm học: tháng 8 trở đi thuộc năm học mới (vd: 08/2026 -> "2026-2027").
 */

if (!defined('CVD_SCHOOL_YEAR_HELPER')) {
    define('CVD_SCHOOL_YEAR_HELPER', true);

    if (!defined('CVD_SYSTEM_CONFIG_FILE')) {
        define('CVD_SYSTEM_CONFIG_FILE', __DIR__ . '/../admin/system_config.json');
    }

    if (!defined('CVD_ARCHIVE_DIR')) {
        define('CVD_ARCHIVE_DIR', __DIR__ . '/../archives/');
    }

    /**
     * Kiểm tra định dạng năm học "YYYY-YYYY"
     */
    function school_year_is_valid($year) {
        return is_string($year)
            && preg_match('/^(\d{4})-(\d{4})$/', $year, $m)
            && (int)$m[2] === (int)$m[1] + 1;
    }

    /**
     * Tính năm học từ thời điểm (timestamp Unix hoặc chuỗi "Y-m-d H:i:s").
     * Tháng >= 8 (Tháng Tám) thuộc năm học bắt đầu trong năm đó.
     */
    function school_year_from_time($time) {
        $ts = is_numeric($time) ? (int)$time : strtotime((string)$time);
        if (!$ts) {
            $ts = time();
        }
        $start = (int)date('n', $ts) >= 8 ? (int)date('Y', $ts) : (int)date('Y', $ts) - 1;
        return $start . '-' . ($start + 1);
    }

    /**
     * Năm học hiện tại: ưu tiên cấu hình hệ thống (system.school_year),
     * nếu không có/không hợp lệ thì tính theo ngày hiện tại.
     */
    function get_current_school_year() {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $configured = '';
        if (file_exists(CVD_SYSTEM_CONFIG_FILE)) {
            $content = @file_get_contents(CVD_SYSTEM_CONFIG_FILE);
            if ($content !== false) {
                $config = json_decode($content, true);
                $configured = $config['system']['school_year'] ?? '';
            }
        }

        $cached = school_year_is_valid($configured)
            ? $configured
            : school_year_from_time(time());
        return $cached;
    }

    /**
     * Gắn nhãn school_year vào bản ghi (điểm, luyện tập...) nếu chưa có,
     * dựa trên trường timestamp của bản ghi.
     */
    function school_year_stamp_record(array &$record, $timestampKey = 'timestamp') {
        if (!isset($record['school_year']) || !school_year_is_valid($record['school_year'])) {
            $record['school_year'] = school_year_from_time($record[$timestampKey] ?? time());
        }
        return $record['school_year'];
    }

    /**
     * Thư mục lưu trữ dài hạn theo năm học: archives/<nam-hoc>/
     */
    function school_year_archive_dir($schoolYear = null) {
        $year = school_year_is_valid($schoolYear) ? $schoolYear : get_current_school_year();
        return CVD_ARCHIVE_DIR . str_replace('-', '_', $year) . '/';
    }
}
