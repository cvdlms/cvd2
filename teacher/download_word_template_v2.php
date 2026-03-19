<?php
// download_word_template_v2.php - Simple working version
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();

    // Title
    $section->addText('MAU CAU HOI - FORMAT CHUAN', ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // === PHAN 1: TRAC NGHIEM ===
    $section->addText('=== PHAN 1: TRAC NGHIEM (TNKQ) ===', ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);
    
    $section->addText('Chu de: Chuong 1: Phuong trinh bac hai', ['bold' => true]);
    $section->addText('Bai hoc: Bai 1: Giai phuong trinh bac hai', ['bold' => true]);
    $section->addTextBreak(1);

    // Cau 1
    $section->addText('Cau 1: [Muc do: NB] [Loai: single]', ['bold' => true]);
    $section->addText('Phuong trinh bac hai mot an co dang tong quat la:');
    $section->addTextBreak(1);
    $section->addText('A) ax + b = 0');
    $section->addText('B) ax^2 + bx + c = 0 *', ['bold' => true]);
    $section->addText('C) ax^3 + bx^2 + cx + d = 0');
    $section->addText('D) Phuong trinh khac');
    $section->addTextBreak(1);
    $section->addText('Dap an dung: B');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Cau 2
    $section->addText('Cau 2: [Muc do: TH] [Loai: single]', ['bold' => true]);
    $section->addText('De phuong trinh co hai nghiem phan biet thi:');
    $section->addTextBreak(1);
    $section->addText('A) Delta > 0 *', ['bold' => true]);
    $section->addText('B) Delta = 0');
    $section->addText('C) Delta < 0');
    $section->addText('D) Delta >= 0');
    $section->addTextBreak(1);
    $section->addText('Dap an dung: A');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(2);

    // === PHAN 2: DUNG/SAI ===
    $section->addText('=== PHAN 2: DUNG/SAI (DS) ===', ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);
    
    $section->addText('Chu de: Chuong 2: Tin hoc van phong', ['bold' => true]);
    $section->addText('Bai hoc: Bai 1: Bang tinh Excel', ['bold' => true]);
    $section->addTextBreak(1);

    // Cau 3 - DS don gian
    $section->addText('Cau 3: [Muc do: NB] [Loai: true_false]', ['bold' => true]);
    $section->addText('Microsoft Excel la phan mem bang tinh.');
    $section->addTextBreak(1);
    $section->addText('A) Dung *', ['bold' => true]);
    $section->addText('B) Sai');
    $section->addTextBreak(1);
    $section->addText('Dap an dung: A');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Cau 4 - DS nhieu y
    $section->addText('Cau 4: [Muc do: TH] [Loai: true_false_multiple]', ['bold' => true]);
    $section->addText('Trong mot lop hoc, giao vien nhap diem kiem tra cua hoc sinh vao bang tinh va su dung cac ham de tinh diem trung binh, diem cao nhat va tong so hoc sinh. Sau do giao vien thay doi mot vai diem so trong bang.');
    $section->addTextBreak(1);
    $section->addText('a) Khi thay doi diem so, ket qua tinh trung binh se tu dong cap nhat. [Dung] *', ['bold' => true]);
    $section->addText('b) Ham SUM dung de tim diem cao nhat. [Sai] *', ['bold' => true]);
    $section->addText('c) Ham MAX co the dung de tim diem cao nhat trong lop. [Dung] *', ['bold' => true]);
    $section->addText('d) Neu xoa cong thuc thi ket qua van tu cap nhat theo du lieu. [Sai] *', ['bold' => true]);
    $section->addTextBreak(1);
    $section->addText('Dap an dung: a=Dung, b=Sai, c=Dung, d=Sai');
    $section->addTextBreak(1);
    $section->addText('LUU Y: Moi y (a, b, c, d) co [Dung] hoac [Sai] trong ngoac vuong. Danh dau * sau moi y.');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(2);

    // === PHAN 3: TU LUAN ===
    $section->addText('=== PHAN 3: TU LUAN (TL) ===', ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);
    
    $section->addText('Chu de: Chuong 3: Lich su may tinh', ['bold' => true]);
    $section->addText('Bai hoc: Bai 1: Su phat trien may tinh qua cac the he', ['bold' => true]);
    $section->addTextBreak(1);

    // Cau 5 - Tu luan don gian
    $section->addText('Cau 5: [Muc do: VD] [Loai: essay] [Diem: 2.0]', ['bold' => true]);
    $section->addText('Phan tich su phat trien cua may tinh qua cac the he va tac dong cua no den xa hoi hien dai.');
    $section->addTextBreak(1);
    $section->addText('LUU Y: Cau tu luan KHONG co dap an A, B, C, D. Phai co [Diem: X.X]');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Cau 6 - Tu luan co cau con
    $section->addText('Cau 6: [Muc do: VDC] [Loai: essay] [Diem: 3.0]', ['bold' => true]);
    $section->addText('Giai bai toan sau:');
    $section->addTextBreak(1);
    $section->addText('a) (1.0d) Tim nghiem cua phuong trinh x^2 - 4 = 0');
    $section->addText('b) (1.0d) Ve do thi ham so y = x^2 - 4');
    $section->addText('c) (1.0d) Phan tich tinh chat cua ham so');
    $section->addTextBreak(1);
    $section->addText('LUU Y: Cau tu luan co the co nhieu cau hoi con (a, b, c...). Moi cau con co the ghi diem rieng.');
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(2);

    // HUONG DAN
    $section->addText('=== TONG KET ===', ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);
    $section->addText('3 LOAI CAU HOI:');
    $section->addText('1. TNKQ: [Loai: single] hoac [Loai: multiple] - Co dap an A), B), C), D)');
    $section->addText('2. Dung/Sai don gian: [Loai: true_false] - Co 2 dap an A) Dung, B) Sai');
    $section->addText('3. Dung/Sai nhieu y: [Loai: true_false_multiple] - Co a), b), c), d) voi [Dung]/[Sai]');
    $section->addText('4. Tu luan: [Loai: essay] [Diem: X.X] - KHONG co dap an ABCD');
    $section->addTextBreak(1);
    $section->addText('QUY TAC:');
    $section->addText('- Moi cau bat dau: Cau [so]: [Muc do: NB/TH/VD/VDC] [Loai: ...]');
    $section->addText('- Danh dau dap an dung bang dau * sau dap an');
    $section->addText('- Phan cach cau hoi bang ---');

    // Create temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'word_');
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);
    
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception('Khong tao duoc file');
    }
    
    // Clear buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="mau_cau_hoi_word.docx"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: no-cache');
    
    readfile($tempFile);
    @unlink($tempFile);
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    echo 'Loi: ' . $e->getMessage();
    exit;
}
