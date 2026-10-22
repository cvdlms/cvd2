<?php
/**
 * config.php — Cấu hình hệ thống Ma trận đề THCS
 * Port từ eduvn/public/tools/matran sang CVD LMS
 */

define('APP_NAME',    'Ma trận đề THCS');
define('APP_VERSION', '1.0.0');

// Lưu trữ file JSON môn học trong data/ (ngoài thư mục public)
define('UPLOAD_DIR',  dirname(__DIR__, 3) . '/data/matran_subjects/');
define('MAX_JSON_MB', 2);

// Màu sắc chuẩn Bộ GD&ĐT cho file Word
define('CLR_NAVY',   '1A3A5C');
define('CLR_BLUE',   'D0E4FF');
define('CLR_GREEN',  'E8F5E9');
define('CLR_YELLOW', 'FFF8E1');
define('CLR_GRAY',   'F5F5F5');

// Cấu hình mặc định đề kiểm tra
define('DEFAULT_TNKQ_NUM', 8);
define('DEFAULT_TNKQ_PPQ', 0);      // 0 => tự suy ra theo bảng 8/16/20 câu
define('DEFAULT_DS_NUM',   2);      // số câu Đúng/Sai (0 = không dùng)
define('DEFAULT_DS_PPQ',   1.0);    // điểm mỗi câu Đúng/Sai (2c x 1đ = 2đ)
define('DS_SUB_PER_Q',     4);      // số ý (a,b,c,d) trong mỗi câu Đúng/Sai
define('DEFAULT_PCT_NB',   35);
define('DEFAULT_PCT_TH',   35);
define('DEFAULT_PCT_VD',   30);
define('DEFAULT_TL_PTS',   4);
define('DEFAULT_TL_NUM',   4);

// Cấu hình giấy A4 nằm ngang (DXA: 1440 = 1 inch)
define('PAGE_W',    15840);
define('PAGE_H',    11906);
define('MARGIN_TOP',  720);
define('MARGIN_BTM',  720);
define('MARGIN_L',   1080);
define('MARGIN_R',   1080);
define('CONTENT_W',  14678);

// Đảm bảo thư mục lưu trữ tồn tại
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
