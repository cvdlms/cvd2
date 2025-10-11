<?php
require 'api/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = 'data/all_lop.xls';

if (!file_exists($excelPath)) {
    echo 'File not found';
    exit;
}

try {
    $reader = IOFactory::createReaderForFile($excelPath);
    $spreadsheet = $reader->load($excelPath);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

$sheetNames = $spreadsheet->getSheetNames();
echo "Sheets: " . implode(', ', $sheetNames) . "\n";

foreach ($sheetNames as $sheetName) {
    echo "\nSheet: $sheetName\n";
    $worksheet = $spreadsheet->getSheetByName($sheetName);
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    echo "Rows: $highestRow, Columns: $highestColumn\n";

    // Read first 10 rows
    for ($row = 1; $row <= min(10, $highestRow); $row++) {
        echo "Row $row: ";
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $value = $worksheet->getCell($col . $row)->getValue();
            echo "$col: '$value' | ";
        }
        echo "\n";
    }
}
?>
