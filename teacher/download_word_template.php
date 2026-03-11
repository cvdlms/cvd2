<?php
// download_word_template.php - Generate and download Word template
// UTF-8 without BOM

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();

    // Title
    $section->addText('MẪU CÂU HỎI TRẮC NGHIỆM - FORMAT CHUẨN', 
        ['bold' => true, 'size' => 16], 
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    $section->addTextBreak(1);

    // Instructions
    $section->addText('HƯỚNG DẪN:', ['bold' => true, 'size' => 12, 'color' => 'FF0000']);
    $section->addText('1. Metadata: Chủ đề và Bài học đặt ở đầu nhóm câu hỏi', ['size' => 10]);
    $section->addText('2. Câu hỏi: Bắt đầu bằng "Câu [số]:" kèm [Mức độ: NB/TH/VD/VDC] [Loại: single/multiple]', ['size' => 10]);
    $section->addText('3. Đáp án: Bắt đầu bằng A), B), C), D)...', ['size' => 10]);
    $section->addText('4. Đáp án đúng: Đánh dấu * sau đáp án hoặc dòng "Đáp án đúng: A"', ['size' => 10]);
    $section->addText('5. Công thức toán: Dùng $...$ cho công thức LaTeX', ['size' => 10]);
    $section->addText('6. Phân cách: Dùng --- giữa các câu hỏi', ['size' => 10]);
    $section->addTextBreak(1);

    $section->addText(str_repeat('=', 80), ['size' => 10]);
    $section->addTextBreak(1);

    // Example metadata
    $section->addText('Chủ đề: Chương 1: Phương trình bậc hai', ['bold' => true]);
    $section->addText('Bài học: Bài 1: Giải phương trình bậc hai', ['bold' => true]);
    $section->addTextBreak(1);

    // Question 1
    $section->addText('Câu 1: [Mức độ: NB] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Phương trình bậc hai một ẩn có dạng tổng quát là:');
    $section->addTextBreak(1);
    $section->addText('A) ax + b = 0');
    $section->addText('B) ax^2 + bx + c = 0 *', ['bold' => true]);
    $section->addText('C) ax^3 + bx^2 + cx + d = 0');
    $section->addText('D) Phương trình khác');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: B', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 2
    $section->addText('Câu 2: [Mức độ: TH] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Để phương trình có hai nghiệm phân biệt thì:');
    $section->addTextBreak(1);
    $section->addText('A) Delta > 0 *', ['bold' => true]);
    $section->addText('B) Delta = 0');
    $section->addText('C) Delta < 0');
    $section->addText('D) Delta >= 0');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: A', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 3
    $section->addText('Câu 3: [Mức độ: VD] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Tìm nghiệm của phương trình: x^2 - 5x + 6 = 0');
    $section->addTextBreak(1);
    $section->addText('A) x = 1 hoặc x = 6');
    $section->addText('B) x = 2 hoặc x = 3 *', ['bold' => true]);
    $section->addText('C) x = -2 hoặc x = -3');
    $section->addText('D) Vô nghiệm');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: B', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 4 - Multiple choice
    $section->addText('Câu 4: [Mức độ: VDC] [Loại: multiple]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Cho phương trình x^2 + 2x + 1 = 0. Chọn các phát biểu đúng:');
    $section->addTextBreak(1);
    $section->addText('A) Phương trình có nghiệm kép x = -1 *', ['bold' => true]);
    $section->addText('B) Biệt thức Delta = 0 *', ['bold' => true]);
    $section->addText('C) Phương trình có 2 nghiệm phân biệt');
    $section->addText('D) Tổng hai nghiệm bằng -2 *', ['bold' => true]);
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: A, B, D', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(2);

    // New topic
    $section->addText(str_repeat('=', 80), ['size' => 10]);
    $section->addTextBreak(1);
    $section->addText('Chủ đề: Chương 2: Hóa học cơ bản', ['bold' => true]);
    $section->addText('Bài học: Bài 1: Cấu tạo nguyên tử', ['bold' => true]);
    $section->addTextBreak(1);

    // Question 5 - Chemistry
    $section->addText('Câu 5: [Mức độ: NB] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Công thức hóa học của nước là:');
    $section->addTextBreak(1);
    $section->addText('A) H2O *', ['bold' => true]);
    $section->addText('B) H2O2');
    $section->addText('C) HO');
    $section->addText('D) H3O');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: A', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 6 - Physics
    $section->addText('Câu 6: [Mức độ: TH] [Loại: single]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Gia tốc trọng trường trên Trái Đất:');
    $section->addTextBreak(1);
    $section->addText('A) 9.8 m/s');
    $section->addText('B) 9.8 m/s^2 *', ['bold' => true]);
    $section->addText('C) 98 m/s^2');
    $section->addText('D) 0.98 m/s^2');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: B', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');

    // Save to public file instead of temp
    $publicFile = __DIR__ . '/generated_templates/mau_cau_hoi_word.docx';
    
    // Create directory if not exists
    $dir = dirname($publicFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Save file
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($publicFile);
    
    // Check file was created
    if (!file_exists($publicFile) || filesize($publicFile) === 0) {
        throw new Exception('Failed to create Word file');
    }
    
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="mau_cau_hoi_word.docx"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($publicFile));
    
    // Output file
    readfile($publicFile);
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Lỗi khi tạo file Word: ' . $e->getMessage();
    exit;
}
