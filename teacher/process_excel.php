<?php
include '../includes/session_check.php'; // Ensure logged in
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

// Increase memory limit for large Excel files
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 300); // 5 minutes

// Cấu hình mốc điểm và nhận xét - load from localStorage or use defaults
$default_rules = [
    ['min' => 0,   'max' => 3.5, 'comment' => 'Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn.'],
    ['min' => 3.5, 'max' => 5.0, 'comment' => 'Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn.'],
    ['min' => 5.0, 'max' => 6.5, 'comment' => 'Có cố gắng, cần phát huy thêm.'],
    ['min' => 6.5, 'max' => 8.0, 'comment' => 'Siêng học, cần phát huy thêm.'],
    ['min' => 8.0, 'max' => 10.0, 'comment' => 'Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh.']
];

// Try to load custom rules from POST data or use defaults
$comment_rules = isset($_POST['comment_rules']) ? json_decode($_POST['comment_rules'], true) : $default_rules;
if (!$comment_rules) {
    $comment_rules = $default_rules;
}

// Hàm ghi log debug
function logDebug($message) {
    $logFile = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Tìm nhận xét phù hợp với điểm số, theo các mốc điểm được cấu hình
function getCommentForScore($score, $commentRules) {
    foreach ($commentRules as $rule) {
        if ($score >= (float)$rule['min'] && $score <= (float)$rule['max']) {
            return $rule['comment'] ?? '';
        }
    }
    return '';
}

// Đọc giá trị 1 ô, trả về chuỗi đã trim (áp dụng calculated value để không vướng công thức)
function readCellString($worksheet, $col, $row) {
    $value = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
    return $value === null ? '' : trim((string)$value);
}

// Hàm xử lý Excel bằng PhpSpreadsheet (thuần PHP, không cần Python/hosting hỗ trợ exec)
function processExcelWithPhpSpreadsheet($inputFile, $outputFile, $commentRules) {
    try {
        $spreadsheet = IOFactory::load($inputFile);

        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $maxRow = $worksheet->getHighestRow();
            $startRow = 8;

            for ($row = $startRow; $row <= $maxRow; $row++) {
                // Cột A: STT, cột B: mã học sinh
                $stt = readCellString($worksheet, 1, $row);
                $studentCode = readCellString($worksheet, 2, $row);

                // Bỏ qua dòng không có STT hợp lệ hoặc không có mã học sinh
                if ($stt === '' || !ctype_digit($stt) || $studentCode === '') {
                    continue;
                }

                // Cột L (cột 12): điểm số
                $scoreRaw = $worksheet->getCellByColumnAndRow(12, $row)->getCalculatedValue();
                if ($scoreRaw === null || $scoreRaw === '') {
                    $score = 0.0;
                } elseif (is_numeric($scoreRaw)) {
                    $score = (float)$scoreRaw;
                } else {
                    $normalized = str_replace(',', '.', (string)$scoreRaw);
                    $score = is_numeric($normalized) ? (float)$normalized : 0.0;
                }

                $comment = getCommentForScore($score, $commentRules);

                // Cột M (cột 13): ghi nhận xét
                $worksheet->getCellByColumnAndRow(13, $row)->setValue($comment);
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputFile);

        return ['success' => true, 'message' => 'File processed successfully with PhpSpreadsheet'];
    } catch (Throwable $e) {
        logDebug('PhpSpreadsheet processing error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi upload file: ' . $file['error']
        ]);
        exit;
    }

    // Kiểm tra định dạng file
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'xlsx') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Chỉ chấp nhận file .xlsx'
        ]);
        exit;
    }

    try {
        // Create temp directory for processing
        $tempDir = sys_get_temp_dir() . '/cvd_excel_processing/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $inputFile = $file['tmp_name'];
        $originalName = $file['name'];
        $outputFile = $tempDir . 'processed_' . time() . '_' . basename($originalName);

        logDebug("Starting Excel processing: $inputFile -> $outputFile");

        // Xử lý file bằng PhpSpreadsheet
        $result = processExcelWithPhpSpreadsheet($inputFile, $outputFile, $comment_rules);

        if ($result['success']) {
            // Get file size for logging
            $fileSize = filesize($outputFile);
            logDebug("Processed file size: " . round($fileSize / 1024 / 1024, 2) . " MB");

            // Return JSON response with download URL
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'downloadUrl' => 'download_processed.php?file=' . urlencode(basename($outputFile)),
                'fileSize' => round($fileSize / 1024 / 1024, 2) . ' MB',
                'message' => 'File processed successfully!'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error processing file: ' . ($result['message'] ?? 'Unknown error')
            ]);
        }

    } catch (Exception $e) {
        logDebug("Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error processing file: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
