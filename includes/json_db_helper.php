<?php
/**
 * CVD LMS - Centralized JSON Data Storage & Lock Safety Helper
 * Quản lý đọc/ghi tệp JSON an toàn với khóa ghi độc quyền (LOCK_EX) và Ghi đè nguyên tử (Atomic Write)
 */

if (!defined('CVD_LOG_DIR')) {
    define('CVD_LOG_DIR', __DIR__ . '/../logs/');
}

/**
 * Ghi dữ liệu array/object xuống file JSON một cách an toàn
 * Sử dụng file tạm (Atomic Write) và cờ khóa đĩa (LOCK_EX) để chống hỏng file khi nhiều truy vấn cùng lúc.
 * 
 * @param string $filePath Đường dẫn tuyệt đối hoặc tương đối tới tệp JSON
 * @param mixed $data Dữ liệu mảng/đối tượng cần lưu
 * @param bool $pretty Định dạng JSON đẹp mắt (JSON_PRETTY_PRINT)
 * @return bool Thành công hay thất bại
 */
function save_json_data($filePath, $data, $pretty = true) {
    if (empty($filePath)) {
        return false;
    }

    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }

    $json = json_encode($data, $flags);
    if ($json === false) {
        cvd_log_error("Lỗi mã hóa JSON cho tệp {$filePath}: " . json_last_error_msg());
        return false;
    }

    // Atomic write qua file tạm để tránh corrupt file nếu ngắt đột ngột
    $tempFile = $filePath . '.' . uniqid('tmp_', true) . '.tmp';
    $writeResult = @file_put_contents($tempFile, $json, LOCK_EX);

    if ($writeResult === false) {
        cvd_log_error("Không thể ghi file tạm: {$tempFile}");
        return false;
    }

    // Đổi tên file tạm thành file đích (Atomic Rename)
    if (!@rename($tempFile, $filePath)) {
        // Fallback ghi trực tiếp nếu rename bị từ chối trên một số môi trường Windows
        $directResult = @file_put_contents($filePath, $json, LOCK_EX);
        @unlink($tempFile);
        return $directResult !== false;
    }

    return true;
}

/**
 * Đọc dữ liệu từ tệp JSON an toàn với xử lý lỗi
 * 
 * @param string $filePath Đường dẫn tới tệp JSON
 * @param mixed $default Giá trị mặc định nếu tệp không tồn tại hoặc lỗi
 * @return mixed Dữ liệu sau khi giải mã hoặc giá trị mặc định
 */
function get_json_data($filePath, $default = []) {
    if (!file_exists($filePath)) {
        return $default;
    }

    $content = @file_get_contents($filePath);
    if ($content === false || trim($content) === '') {
        return $default;
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        cvd_log_error("Lỗi giải mã JSON từ tệp {$filePath}: " . json_last_error_msg());
        return $default;
    }

    return $data;
}

/**
 * Đọc–sửa–ghi JSON dưới khóa độc quyền (flock) dành cho các tệp dùng chung
 * (ví dụ student_score.json, practice_results.json) để chống mất dữ liệu
 * (lost update) khi nhiều tiến trình đồng thời ghi vào cùng một tệp —
 * ví dụ cả lớp nộp bài thi cùng lúc.
 *
 * Khóa được đặt trên tệp riêng (.lock), không phải tệp đích, vì tệp đích bị
 * thay thế bằng rename (atomic write) khi ghi. Việc ghi vẫn dùng save_json_data
 * (atomic) nên người đọc không cần khóa, luôn thấy nội dung hoàn chỉnh.
 *
 * @param string $filePath Đường dẫn tới tệp JSON
 * @param callable $mutator Hàm nhận dữ liệu hiện tại và trả về dữ liệu mới
 * @param mixed $default Giá trị mặc định khi tệp chưa có hoặc lỗi
 * @param bool $pretty Định dạng JSON đẹp mắt
 * @return bool Thành công hay thất bại
 */
function update_json_data($filePath, $mutator, $default = [], $pretty = true) {
    if (empty($filePath) || !is_callable($mutator)) {
        return false;
    }

    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $lockPath = $filePath . '.lock';
    $lockFp = @fopen($lockPath, 'c');
    if (!$lockFp || !flock($lockFp, LOCK_EX)) {
        if ($lockFp) { fclose($lockFp); }
        // Không khóa được: vẫn ghi như cũ để không làm hỏng luồng chính
        return save_json_data($filePath, call_user_func($mutator, get_json_data($filePath, $default)), $pretty);
    }

    try {
        $data = get_json_data($filePath, $default);
        $newData = call_user_func($mutator, $data);
        return save_json_data($filePath, $newData, $pretty);
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

/**
 * Ghi log lỗi hệ thống tập trung vào logs/error.log
 * 
 * @param string $message Thông điệp lỗi
 */
function cvd_log_error($message) {
    $logDir = CVD_LOG_DIR;
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $logFile = $logDir . 'error.log';
    $time = date('Y-m-d H:i:s');
    $logLine = "[{$time}] {$message}\n";
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
?>
