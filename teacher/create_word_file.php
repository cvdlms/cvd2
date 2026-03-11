<?php
// Không có bất kỳ output nào trước đây
header('Content-Type: application/json; charset=utf-8');

require_once '../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

try {
    // Đường dẫn file output
    $outputDir = __DIR__ . '/generated_templates';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $outputFile = $outputDir . '/mau_cau_hoi_word.docx';
    
    // Tạo document mới
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    // Tiêu đề
    $section->addText(
        'MẪU ĐỊNH DẠNG CÂU HỎI',
        ['bold' => true, 'size' => 16, 'color' => 'FF0000'],
        ['alignment' => 'center']
    );
    $section->addTextBreak(1);
    
    // Metadata
    $section->addText('Chủ đề: Đại số', ['bold' => true, 'size' => 12]);
    $section->addText('Bài học: Phương trình bậc hai', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 1: Toán học ===
    $section->addText('Câu 1: [Mức độ: NB] [Loại: single] Giải phương trình $x^2 + 5x + 6 = 0$', ['bold' => true, 'size' => 11]);
    $section->addText('A) $x = -2$ hoặc $x = -3$ *', ['size' => 11]);
    $section->addText('B) $x = 2$ hoặc $x = 3$', ['size' => 11]);
    $section->addText('C) $x = 1$ hoặc $x = 6$', ['size' => 11]);
    $section->addText('D) Vô nghiệm', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 2: Hóa học ===
    $section->addText('Câu 2: [Mức độ: TH] [Loại: single] Cân bằng phương trình hóa học sau: $2H_2 + O_2 \\to 2H_2O$. Tỉ lệ mol giữa H₂ và O₂ là bao nhiêu?', ['bold' => true, 'size' => 11]);
    $section->addText('A) 1:1', ['size' => 11]);
    $section->addText('B) 2:1 *', ['size' => 11]);
    $section->addText('C) 1:2', ['size' => 11]);
    $section->addText('D) 3:1', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 3: Vật lý ===
    $section->addText('Câu 3: [Mức độ: VD] [Loại: single] Công thức tính động năng là gì?', ['bold' => true, 'size' => 11]);
    $section->addText('A) $E_k = mgh$', ['size' => 11]);
    $section->addText('B) $E_k = \\frac{1}{2}mv^2$ *', ['size' => 11]);
    $section->addText('C) $E_k = mc^2$', ['size' => 11]);
    $section->addText('D) $E_k = \\frac{mv^2}{2g}$', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 4: Trắc nghiệm nhiều đáp án đúng ===
    $section->addText('Câu 4: [Mức độ: VDC] [Loại: multiple] Các kim loại nào sau đây phản ứng với nước ở nhiệt độ thường?', ['bold' => true, 'size' => 11]);
    $section->addText('A) Natri (Na) *', ['size' => 11]);
    $section->addText('B) Kali (K) *', ['size' => 11]);
    $section->addText('C) Sắt (Fe)', ['size' => 11]);
    $section->addText('D) Đồng (Cu)', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 5: Công thức phức tạp ===
    $section->addText('Câu 5: [Mức độ: VDC] [Loại: single] Tính giới hạn: $$\\lim_{x \\to 0} \\frac{\\sin x}{x}$$', ['bold' => true, 'size' => 11]);
    $section->addText('A) 0', ['size' => 11]);
    $section->addText('B) 1 *', ['size' => 11]);
    $section->addText('C) $\\infty$', ['size' => 11]);
    $section->addText('D) Không tồn tại', ['size' => 11]);
    $section->addTextBreak(1);
    
    // === CÂU 6: Tích phân ===
    $section->addText('Câu 6: [Mức độ: VD] [Loại: single] Tích phân $\\int_0^1 x^2 dx$ bằng:', ['bold' => true, 'size' => 11]);
    $section->addText('A) $\\frac{1}{2}$', ['size' => 11]);
    $section->addText('B) $\\frac{1}{3}$ *', ['size' => 11]);
    $section->addText('C) $\\frac{2}{3}$', ['size' => 11]);
    $section->addText('D) 1', ['size' => 11]);
    $section->addTextBreak(2);
    
    // Hướng dẫn
    $section->addText('LƯU Ý:', ['bold' => true, 'size' => 12, 'color' => 'FF0000']);
    $section->addText('- Đánh dấu * sau đáp án đúng', ['size' => 10]);
    $section->addText('- Format câu hỏi: Câu X: [Mức độ: NB/TH/VD/VDC] [Loại: single/multiple] Nội dung câu hỏi', ['size' => 10]);
    $section->addText('- Công thức toán học: Dùng $...$ cho inline, $$...$$ cho display', ['size' => 10]);
    $section->addText('- Mỗi câu hỏi bắt đầu bằng "Câu X:"', ['size' => 10]);
    $section->addText('- Các đáp án: A), B), C), D)', ['size' => 10]);
    $section->addText('- Metadata [Mức độ:] và [Loại:] không bắt buộc (mặc định: NB, single)', ['size' => 10]);
    
    // Lưu file
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($outputFile);
    
    // Kiểm tra file đã tạo
    if (file_exists($outputFile)) {
        $fileSize = filesize($outputFile);
        echo json_encode([
            'success' => true,
            'message' => 'File đã được tạo thành công',
            'file' => 'generated_templates/mau_cau_hoi_word.docx',
            'size' => $fileSize
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Không thể tạo file');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
