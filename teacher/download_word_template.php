<?php
// download_word_template.php - Generate and download Word template
// UTF-8 without BOM

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    
    // Set document properties
    $phpWord->getDocInfo()->setCreator('CVD LMS');
    $phpWord->getDocInfo()->setTitle('Mau Cau Hoi Word');
    
    $section = $phpWord->addSection();

    // Title
    $fontStyleTitle = ['size' => 16, 'bold' => true];
    $section->addText('MAU CAU HOI TRAC NGHIEM - FORMAT CHUAN', $fontStyleTitle, ['alignment' => 'center']);
    $section->addTextBreak(1);

    // Instructions
    $fontStyleHeading = ['size' => 12, 'bold' => true];
    $fontStyleNormal = ['size' => 10];
    
    $section->addText('HUONG DAN:', $fontStyleHeading);
    $section->addText('1. Metadata: Chu de va Bai hoc dat o dau nhom cau hoi', $fontStyleNormal);
    $section->addText('2. Cau hoi: Bat dau bang "Cau [so]:" kem [Muc do: NB/TH/VD/VDC] [Loai: ...]', $fontStyleNormal);
    $section->addText('3. Loai: single/multiple/true_false/true_false_multiple/essay', $fontStyleNormal);
    $section->addText('4. Dap an: Danh dau * sau dap an hoac dong "Dap an dung: A"', $fontStyleNormal);
    $section->addText('5. Phan cach: Dung --- giua cac cau hoi', $fontStyleNormal);
    $section->addTextBreak(1);

    $section->addText('===================================================================', $fontStyleNormal);
    $section->addTextBreak(1);

    // Example metadata
    $section->addText('Chu de: Chuong 1: Phuong trinh bac hai', ['size' => 11, 'bold' => true]);
    $section->addText('Bai hoc: Bai 1: Giai phuong trinh bac hai', ['size' => 11, 'bold' => true]);
    $section->addTextBreak(1);

    // Question 1 - TNKQ Single
    $section->addText('Cau 1: [Muc do: NB] [Loai: single]', ['size' => 10, 'bold' => true]);
    $section->addText('Phuong trinh bac hai mot an co dang tong quat la:', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('A) ax + b = 0', $fontStyleNormal);
    $section->addText('B) ax^2 + bx + c = 0 *', ['size' => 10, 'bold' => true]);
    $section->addText('C) ax^3 + bx^2 + cx + d = 0', $fontStyleNormal);
    $section->addText('D) Phuong trinh khac', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('Dap an dung: B', ['size' => 10, 'italic' => true]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);
    $section->addTextBreak(1);

    // Question 2 - TNKQ Multiple
    $section->addText('Cau 2: [Muc do: VDC] [Loai: multiple]', ['size' => 10, 'bold' => true]);
    $section->addText('Cho phuong trinh x^2 + 2x + 1 = 0. Chon cac phat bieu dung:', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('A) Phuong trinh co nghiem kep x = -1 *', ['size' => 10, 'bold' => true]);
    $section->addText('B) Biet thuc Delta = 0 *', ['size' => 10, 'bold' => true]);
    $section->addText('C) Phuong trinh co 2 nghiem phan biet', $fontStyleNormal);
    $section->addText('D) Tong hai nghiem bang -2 *', ['size' => 10, 'bold' => true]);
    $section->addTextBreak(1);
    $section->addText('Dap an dung: A, B, D', ['size' => 10, 'italic' => true]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);
    $section->addTextBreak(2);

    // === TRUE/FALSE SECTION ===
    $section->addText('===================================================================', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('LOAI CAU HOI: DUNG/SAI (TRUE/FALSE)', ['size' => 12, 'bold' => true]);
    $section->addTextBreak(1);

    $section->addText('Chu de: Chuong 3: Tin hoc van phong', ['size' => 11, 'bold' => true]);
    $section->addText('Bai hoc: Bai 1: Bang tinh Excel', ['size' => 11, 'bold' => true]);
    $section->addTextBreak(1);

    // Question 3 - True/False Simple
    $section->addText('Cau 3: [Muc do: NB] [Loai: true_false]', ['size' => 10, 'bold' => true]);
    $section->addText('Microsoft Excel la phan mem bang tinh.', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('A) Dung *', ['size' => 10, 'bold' => true]);
    $section->addText('B) Sai', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('Dap an dung: A', ['size' => 10, 'italic' => true]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);
    $section->addTextBreak(1);

    // Question 4 - True/False Multiple (PHUC TAP)
    $section->addText('Cau 4: [Muc do: TH] [Loai: true_false_multiple]', ['size' => 10, 'bold' => true]);
    $section->addText('Trong mot lop hoc, giao vien nhap diem kiem tra cua hoc sinh vao bang tinh va su dung cac ham de tinh diem trung binh, diem cao nhat va tong so hoc sinh. Sau do giao vien thay doi mot vai diem so trong bang.', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('a) Khi thay doi diem so, ket qua tinh trung binh se tu dong cap nhat. [Dung] *', ['size' => 10, 'bold' => true]);
    $section->addText('b) Ham SUM dung de tim diem cao nhat. [Sai] *', ['size' => 10, 'bold' => true]);
    $section->addText('c) Ham MAX co the dung de tim diem cao nhat trong lop. [Dung] *', ['size' => 10, 'bold' => true]);
    $section->addText('d) Neu xoa cong thuc thi ket qua van tu cap nhat theo du lieu. [Sai] *', ['size' => 10, 'bold' => true]);
    $section->addTextBreak(1);
    $section->addText('Dap an dung: a=Dung, b=Sai, c=Dung, d=Sai', ['size' => 10, 'italic' => true]);
    $section->addTextBreak(1);
    $section->addText('LUU Y:', ['size' => 10, 'bold' => true]);
    $section->addText('- Moi y (a, b, c, d) co [Dung] hoac [Sai] trong ngoac vuong', ['size' => 9]);
    $section->addText('- Danh dau * sau moi y de xac nhan dap an', ['size' => 9]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);
    $section->addTextBreak(2);

    // === ESSAY SECTION ===
    $section->addText('===================================================================', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('LOAI CAU HOI: TU LUAN (ESSAY)', ['size' => 12, 'bold' => true]);
    $section->addTextBreak(1);

    $section->addText('Chu de: Chuong 4: Lich su may tinh', ['size' => 11, 'bold' => true]);
    $section->addText('Bai hoc: Bai 1: Su phat trien may tinh qua cac the he', ['size' => 11, 'bold' => true]);
    $section->addTextBreak(1);

    // Question 5 - Essay
    $section->addText('Cau 5: [Muc do: VD] [Loai: essay] [Diem: 2.0]', ['size' => 10, 'bold' => true]);
    $section->addText('Phan tich su phat trien cua may tinh qua cac the he va tac dong cua no den xa hoi hien dai.', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('LUU Y:', ['size' => 10, 'bold' => true]);
    $section->addText('- Cau tu luan KHONG co dap an A, B, C, D', ['size' => 9]);
    $section->addText('- Phai co [Diem: X.X] de chi dinh so diem', ['size' => 9]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);
    $section->addTextBreak(1);

    // Question 6 - Essay with sub-questions
    $section->addText('Cau 6: [Muc do: VDC] [Loai: essay] [Diem: 3.0]', ['size' => 10, 'bold' => true]);
    $section->addText('Giai bai toan sau:', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('a) (1.0d) Tim nghiem cua phuong trinh x^2 - 4 = 0', $fontStyleNormal);
    $section->addText('b) (1.0d) Ve do thi ham so y = x^2 - 4', $fontStyleNormal);
    $section->addText('c) (1.0d) Phan tich tinh chat cua ham so', $fontStyleNormal);
    $section->addTextBreak(1);
    $section->addText('LUU Y:', ['size' => 10, 'bold' => true]);
    $section->addText('- Cau tu luan co the co nhieu cau hoi con (a, b, c...)', ['size' => 9]);
    $section->addText('- Moi cau con co the ghi diem rieng (1.0d)', ['size' => 9]);
    $section->addTextBreak(1);
    $section->addText('---', $fontStyleNormal);

    // Save to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'word_template_');
    
    // Save file
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);
    
    // Check file was created
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
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
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Content-Length: ' . filesize($tempFile));
    
    // Output file
    readfile($tempFile);
    
    // Delete temp file
    @unlink($tempFile);
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Loi khi tao file Word: ' . $e->getMessage();
    exit;
}
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
    $section->addTextBreak(2);

    // === SECTION: LOẠI CÂU HỎI ĐÚNG/SAI ===
    $section->addText(str_repeat('=', 80), ['size' => 10]);
    $section->addTextBreak(1);
    $section->addText('LOẠI CÂU HỎI: ĐÚNG/SAI (TRUE/FALSE)', ['bold' => true, 'size' => 14, 'color' => 'FF6600']);
    $section->addTextBreak(1);

    $section->addText('Chủ đề: Chương 3: Tin học văn phòng', ['bold' => true]);
    $section->addText('Bài học: Bài 1: Bảng tính Excel', ['bold' => true]);
    $section->addTextBreak(1);

    // Question 7 - True/False đơn giản
    $section->addText('Câu 7: [Mức độ: NB] [Loại: true_false]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Microsoft Excel là phần mềm bảng tính.');
    $section->addTextBreak(1);
    $section->addText('A) Đúng *', ['bold' => true]);
    $section->addText('B) Sai');
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: A', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 8 - True/False nhiều ý (PHỨC TẠP)
    $section->addText('Câu 8: [Mức độ: TH] [Loại: true_false_multiple]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Trong một lớp học, giáo viên nhập điểm kiểm tra của học sinh vào bảng tính và sử dụng các hàm để tính điểm trung bình, điểm cao nhất và tổng số học sinh. Sau đó giáo viên thay đổi một vài điểm số trong bảng.');
    $section->addTextBreak(1);
    $section->addText('a) Khi thay đổi điểm số, kết quả tính trung bình sẽ tự động cập nhật. [Đúng] *', ['bold' => true]);
    $section->addText('b) Hàm SUM dùng để tìm điểm cao nhất. [Sai] *', ['bold' => true]);
    $section->addText('c) Hàm MAX có thể dùng để tìm điểm cao nhất trong lớp. [Đúng] *', ['bold' => true]);
    $section->addText('d) Nếu xóa công thức thì kết quả vẫn tự cập nhật theo dữ liệu. [Sai] *', ['bold' => true]);
    $section->addTextBreak(1);
    $section->addText('Đáp án đúng: a=Đúng, b=Sai, c=Đúng, d=Sai', ['italic' => true, 'color' => '008000']);
    $section->addTextBreak(1);
    $section->addText('LƯU Ý:', ['bold' => true, 'color' => 'FF0000']);
    $section->addText('- Mỗi ý (a, b, c, d) có [Đúng] hoặc [Sai] trong ngoặc vuông', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Đánh dấu * sau mỗi ý để xác nhận đáp án', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Hoặc viết: Đáp án đúng: a=Đúng, b=Sai, c=Đúng, d=Sai', ['size' => 10, 'color' => 'FF0000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(2);

    // === SECTION: LOẠI CÂU HỎI TỰ LUẬN ===
    $section->addText(str_repeat('=', 80), ['size' => 10]);
    $section->addTextBreak(1);
    $section->addText('LOẠI CÂU HỎI: TỰ LUẬN (ESSAY)', ['bold' => true, 'size' => 14, 'color' => 'FF6600']);
    $section->addTextBreak(1);

    $section->addText('Chủ đề: Chương 4: Lịch sử máy tính', ['bold' => true]);
    $section->addText('Bài học: Bài 1: Sự phát triển máy tính qua các thế hệ', ['bold' => true]);
    $section->addTextBreak(1);

    // Question 9 - Essay
    $section->addText('Câu 9: [Mức độ: VD] [Loại: essay] [Điểm: 2.0]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Phân tích sự phát triển của máy tính qua các thế hệ và tác động của nó đến xã hội hiện đại.');
    $section->addTextBreak(1);
    $section->addText('LƯU Ý:', ['bold' => true, 'color' => 'FF0000']);
    $section->addText('- Câu tự luận KHÔNG có đáp án A, B, C, D', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Phải có [Điểm: X.X] để chỉ định số điểm', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Câu tự luận chỉ có đề bài, không cần đáp án đúng', ['size' => 10, 'color' => 'FF0000']);
    $section->addTextBreak(1);
    $section->addText('---');
    $section->addTextBreak(1);

    // Question 10 - Essay with sub-questions
    $section->addText('Câu 10: [Mức độ: VDC] [Loại: essay] [Điểm: 3.0]', ['bold' => true, 'color' => '0000FF']);
    $section->addText('Giải bài toán sau:');
    $section->addTextBreak(1);
    $section->addText('a) (1.0đ) Tìm nghiệm của phương trình x^2 - 4 = 0');
    $section->addText('b) (1.0đ) Vẽ đồ thị hàm số y = x^2 - 4');
    $section->addText('c) (1.0đ) Phân tích tính chất của hàm số');
    $section->addTextBreak(1);
    $section->addText('LƯU Ý:', ['bold' => true, 'color' => 'FF0000']);
    $section->addText('- Câu tự luận có thể có nhiều câu hỏi con (a, b, c...)', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Mỗi câu con có thể ghi điểm riêng (1.0đ)', ['size' => 10, 'color' => 'FF0000']);
    $section->addText('- Tổng điểm các câu con = Điểm câu lớn', ['size' => 10, 'color' => 'FF0000']);
    $section->addTextBreak(1);
    $section->addText('---');

    // Save to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'word_template_');
    
    // Save file
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);
    
    // Check file was created
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
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
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Content-Length: ' . filesize($tempFile));
    
    // Output file
    readfile($tempFile);
    
    // Delete temp file
    @unlink($tempFile);
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
