<?php
require_once __DIR__ . '/../vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();

$section->addText('MẪU CÂU HỎI TRẮC NGHIỆM', ['bold' => true, 'size' => 16], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
$section->addTextBreak(1);

$section->addText('Chủ đề: Chương 1: Toán học', ['bold' => true]);
$section->addText('Bài học: Bài 1: Phương trình bậc hai', ['bold' => true]);
$section->addTextBreak(1);

$section->addText('Câu 1: [Mức độ: NB] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
$section->addText('Phương trình bậc hai có dạng:');
$section->addTextBreak(1);
$section->addText('A) ax + b = 0');
$section->addText('B) ax^2 + bx + c = 0 *', ['bold' => true]);
$section->addText('C) ax^3 + bx^2 + cx + d = 0');
$section->addTextBreak(1);
$section->addText('Đáp án đúng: B', ['italic' => true, 'color' => '008000']);

$filename = __DIR__ . '/test_clean.docx';
$writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($filename);

echo "File created: $filename\n";
echo "File size: " . filesize($filename) . " bytes\n";

// Check first 4 bytes
$handle = fopen($filename, 'rb');
$bytes = fread($handle, 4);
fclose($handle);

$hex = bin2hex($bytes);
echo "First 4 bytes (hex): $hex\n";

if (substr($hex, 0, 4) === '504b') {
    echo "✓ Valid ZIP/DOCX signature!\n";
} else {
    echo "✗ Invalid signature\n";
}
