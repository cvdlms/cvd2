<?php
ini_set('memory_limit', '2G');
require 'api/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function clean_student_name($name) {
    // Remove leading number and date if present, e.g., "2. Phan Thị Kim Anh 24/05/2012" -> "Phan Thị Kim Anh"
    if (preg_match('/^\d+\.\s*(.+?)\s*\d{2}\/\d{2}\/\d{4}$/', $name, $matches)) {
        return trim($matches[1]);
    }
    return $name;
}

$excelPath = __DIR__ . '/data/all_lop.xls';
$scoresFile = __DIR__ . '/data/scores.json';

if (!file_exists($excelPath)) {
    echo 'Excel file not found';
    exit;
}

try {
    $reader = IOFactory::createReaderForFile($excelPath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($excelPath);
} catch (Exception $e) {
    echo 'Error loading Excel: ' . $e->getMessage();
    exit;
}

$sheetNames = $spreadsheet->getSheetNames();
$allStudents = [];

foreach ($sheetNames as $sheetName) {
    $worksheet = $spreadsheet->getSheetByName($sheetName);
    $highestRow = $worksheet->getHighestRow();

    for ($row = 2; $row <= $highestRow; $row++) {
        $stt = $worksheet->getCell('A' . $row)->getValue();
        $studentCode = $worksheet->getCell('B' . $row)->getValue();
        $ho = $worksheet->getCell('C' . $row)->getValue();
        $ten = $worksheet->getCell('D' . $row)->getValue();

        if ($studentCode && $ho && $ten) {
            $cleanSheet = preg_replace('/tin\s*học\s*/i', '', $sheetName);
            $cleanSheet = str_replace(' ', '', strtolower($cleanSheet));
            $className = '' . $cleanSheet;
            $key = $className . '_' . $studentCode;
            $hoTen = trim($ho . ' ' . $ten);
            $fullName = $stt . '. ' . $studentCode . ' ' . $hoTen;

            $allStudents[$key] = [
                'class_name' => $className,
                'student_code' => $studentCode,
                'ho_ten' => $hoTen,
                'full_name' => $fullName,
                'tx1_score' => null,
                'tx2_score' => null,
                'tx1_attempts' => 0,
                'tx2_attempts' => 0
            ];
        }
    }
}

file_put_contents($scoresFile, json_encode($allStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo 'Scores populated successfully';
?>
