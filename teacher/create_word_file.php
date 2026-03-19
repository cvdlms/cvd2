<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $outputDir = __DIR__ . '/generated_templates';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $outputFile = $outputDir . '/mau_cau_hoi_word.doc';
    
    // Tạo HTML content
    $html = '
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <title>Mẫu Câu Hỏi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; }
        .title { font-size: 16pt; font-weight: bold; margin-bottom: 20px; }
        .section { font-size: 14pt; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
        .question { font-weight: bold; margin-top: 15px; }
        .note { font-style: italic; color: #666; font-size: 10pt; }
        .separator { margin: 20px 0; border-top: 1px solid #ccc; }
    </style>
</head>
<body>

<p class="title">MAU CAU HOI TRAC NGHIEM (TNKQ)</p>

<p style="font-weight:bold; color:red;">CHI HO TRO: TRAC NGHIEM (4 DAP AN A/B/C/D)</p>
<p>- <strong>single:</strong> Mot dap an dung</p>
<p>- <strong>multiple:</strong> Nhieu dap an dung</p>
<p>- Danh dau dap an dung bang dau <strong>*</strong></p>

<p style="font-weight:bold; margin-top:20px;">2 CACH VIET CONG THUC:</p>
<p>CACH 1: Dung Unicode (x², H₂SO₄) - don gian, de doc</p>
<p>CACH 2: Dung MathJax ($x^2$, $\\ce{H2SO4}$) - dep tren web</p>

<div class="separator"></div>

<p class="section">CHU DE: Chuong 1: Phuong trinh</p>
<p class="section">BAI HOC: Bai 1: Giai phuong trinh bac hai</p>

<p class="section">=== VI DU CAU HOI ===</p>

<p class="question">Cau 1: [Muc do: NB] [Loai: single]</p>
<p>CACH 1 - Unicode: Giai phuong trinh: x² - 5x + 6 = 0. Nghiem cua phuong trinh la:</p>
<p>A) x = 1 va x = 6</p>
<p><strong>B) x = 2 va x = 3 *</strong></p>
<p>C) x = -2 va x = -3</p>
<p>D) Phuong trinh vo nghiem</p>
<p>Dap an dung: B</p>
<p>---</p>

<p class="question">Cau 1b: [Muc do: NB] [Loai: single]</p>
<p>CACH 2 - MathJax: Giai phuong trinh: $x^2 - 5x + 6 = 0$. Nghiem cua phuong trinh la:</p>
<p>A) $x = 1$ va $x = 6$</p>
<p><strong>B) $x = 2$ va $x = 3$ *</strong></p>
<p>C) $x = -2$ va $x = -3$</p>
<p>D) Phuong trinh vo nghiem</p>
<p>Dap an dung: B</p>
<p class="note">(Luu y: Tren web, $x^2$ se render thanh x²)</p>
<p>---</p>

<p class="question">Cau 2: [Muc do: TH] [Loai: multiple]</p>
<p>CACH 1 - Unicode: Phuong trinh phan ung: 2H₂ + O₂ → 2H₂O. Chon cac phat bieu DUNG:</p>
<p><strong>A) Day la phan ung oxi hoa khu *</strong></p>
<p><strong>B) H₂ bi oxi hoa *</strong></p>
<p><strong>C) O₂ la chat oxi hoa *</strong></p>
<p>D) Khoi luong H₂O bang khoi luong H₂</p>
<p>Dap an dung: A, B, C</p>
<p>---</p>

<p class="question">Cau 2b: [Muc do: TH] [Loai: multiple]</p>
<p>CACH 2 - MathJax: Phuong trinh: $\\ce{2H2 + O2 -> 2H2O}$. Chon cac phat bieu DUNG:</p>
<p><strong>A) Day la phan ung oxi hoa khu *</strong></p>
<p><strong>B) $\\ce{H2}$ bi oxi hoa *</strong></p>
<p><strong>C) $\\ce{O2}$ la chat oxi hoa *</strong></p>
<p>D) Khoi luong $\\ce{H2O}$ bang khoi luong $\\ce{H2}$</p>
<p>Dap an dung: A, B, C</p>
<p class="note">(Luu y: \\ce{...} danh cho phuong trinh hoa hoc)</p>
<p>---</p>

<p class="question">Cau 3: [Muc do: TH] [Loai: single]</p>
<p>CACH 1 - Unicode: Mot doan mach co dien tro R = 20Ω duoc dat duoi hieu dien the U = 12V. Cuong do dong dien la:</p>
<p>Biet: I = U/R</p>
<p><strong>A) I = 0.6A *</strong></p>
<p>B) I = 1.2A</p>
<p>C) I = 2.4A</p>
<p>D) I = 240A</p>
<p>Dap an dung: A</p>
<p>---</p>

<p class="question">Cau 3b: [Muc do: TH] [Loai: single]</p>
<p>CACH 2 - MathJax: Mot doan mach co dien tro $R = 20\\Omega$ duoc dat duoi hieu dien the $U = 12V$. Cuong do dong dien la:</p>
<p>Biet: $I = \\frac{U}{R}$</p>
<p><strong>A) $I = 0.6A$ *</strong></p>
<p>B) $I = 1.2A$</p>
<p>C) $I = 2.4A$</p>
<p>D) $I = 240A$</p>
<p>Dap an dung: A</p>
<p class="note">(Luu y: \\frac{U}{R} render thanh phan so dep)</p>
<p>---</p>

<p class="question">Cau 4: [Muc do: VD] [Loai: single]</p>
<p>Phuong trinh $x^2 + 5x - 14 = 0$ co bao nhieu nghiem?</p>
<p>A) Vo nghiem</p>
<p><strong>B) Co 2 nghiem phan biet *</strong></p>
<p>C) Co nghiem kep</p>
<p>D) Co 3 nghiem</p>
<p>Dap an dung: B</p>

<div class="separator"></div>

<p class="section">=== HUONG DAN CHI TIET ===</p>

<p style="font-weight:bold; color:blue;">QUY TAC VIET CAU HOI:</p>
<p>1. <strong>Cau X:</strong> hoac <strong>Cau X</strong> (khong can dau :)</p>
<p>2. <strong>[Muc do: NB/TH/VD/VDC]</strong> (co the viet "Mức độ" hoac "Muc do")</p>
<p>3. <strong>[Loai: single/multiple]</strong> (co the viet "Loại" hoac "Loai")</p>
<p>4. Noi dung cau hoi</p>
<p>5. 4 dap an: A), B), C), D) hoac A., B., C., D.</p>
<p>6. Danh dau <strong>dap an dung bang dau *</strong> ngay sau dap an</p>
<p>7. Dung <strong>---</strong> de ngan cach giua cac cau hoi</p>
<br>

<p style="font-weight:bold;">CACH 1: UNICODE (DON GIAN)</p>
<p>- Luy thua: x², x³, x⁴ (copy ky tu: ²³⁴⁵⁶⁷⁸⁹⁰)</p>
<p>- Chi so duoi: H₂SO₄, CO₂ (copy ky tu: ₀₁₂₃₄)</p>
<p>- Phan so: (1/2), (3/4), (-b)/(2a)</p>
<p>- Ky hieu: π, ≈, ≥, ≤, Ω, °, →</p>
<p>Vi du: x² - 5x + 6 = 0, H₂SO₄, I = U/R</p>
<br>

<p style="font-weight:bold;">CACH 2: MATHJAX (DEP TREN WEB)</p>
<p>Quy tac: Boc cong thuc trong $...$ (inline)</p>
<br>
<p><strong>TOAN HOC:</strong></p>
<p>- Luy thua: $x^2$, $a^{10}$, $2^n$</p>
<p>- Phan so: $\\frac{1}{2}$, $\\frac{-b}{2a}$</p>
<p>- Can bac hai: $\\sqrt{2}$, $\\sqrt{x^2 + 1}$</p>
<p>- Vi du: $x^2 - 5x + 6 = 0$, $\\Delta = b^2 - 4ac$</p>
<br>
<p><strong>HINH HOC:</strong></p>
<p>- Luong giac: $\\sin$, $\\cos$, $\\tan$</p>
<p>- Goc: $\\alpha$, $\\beta$, $\\theta$, $90^\\circ$</p>
<p>- Vi du: $\\sin^2\\alpha + \\cos^2\\alpha = 1$, $S = \\pi r^2$</p>
<br>
<p><strong>HOA HOC:</strong></p>
<p>- Phuong trinh: $\\ce{2H2 + O2 -> 2H2O}$</p>
<p>- Can bang: $\\ce{H2SO4 + 2NaOH -> Na2SO4 + 2H2O}$</p>
<p>- Ion: $\\ce{Fe^{2+}}$, $\\ce{SO4^{2-}}$</p>
<br>
<p><strong>VAT LY:</strong></p>
<p>- Cong thuc: $F = ma$, $v = \\frac{s}{t}$, $W = \\frac{1}{2}mv^2$</p>
<p>- Don vi: $\\Omega$ (ohm), $\\mu$ (micro)</p>
<p>- Vi du: $I = \\frac{U}{R}$, $P = UI$, $E = mc^2$</p>
<br>

<p style="font-weight:bold; color:red;">LUU Y QUAN TRONG:</p>
<p style="color:red;">1. CHI HO TRO TRAC NGHIEM (TNKQ) - 4 dap an A/B/C/D</p>
<p style="color:red;">2. KHONG HO TRO cau hoi Dung/Sai hoac Tu luan</p>
<p>3. MathJax chi render dep tren WEB, trong Word se hien $...$</p>
<p>4. Co the ket hop CA 2 cach trong cung 1 de thi!</p>
<p>5. Test cong thuc truoc khi nhap nhieu cau hoi!</p>
<p>6. Dung dau <strong>*</strong> sau dap an de danh dau dap an dung</p>
<p>7. <strong>MOI CAU HOI PHAI CO IT NHAT 1 DAP AN DUNG</strong></p>

</body>
</html>
';
    
    // Ghi file (với retry nếu file đang bị lock)
    $maxRetries = 3;
    $retryDelay = 500000; // 0.5 giây (microseconds)
    $written = false;
    
    for ($i = 0; $i < $maxRetries; $i++) {
        // Thử xóa file cũ nếu tồn tại
        if (file_exists($outputFile)) {
            @unlink($outputFile);
            usleep(100000); // Đợi 0.1 giây
        }
        
        // Thử ghi file
        $result = @file_put_contents($outputFile, $html);
        
        if ($result !== false) {
            $written = true;
            break;
        }
        
        // Nếu thất bại, đợi và thử lại
        if ($i < $maxRetries - 1) {
            usleep($retryDelay);
        }
    }
    
    if (!$written) {
        throw new Exception('Khong the tao file. Hay dong Word neu dang mo file mau, roi thu lai!');
    }
    
    if (file_exists($outputFile)) {
        $fileSize = filesize($outputFile);
        echo json_encode([
            'success' => true,
            'message' => 'File HTML da duoc tao thanh cong',
            'file' => 'generated_templates/mau_cau_hoi_word.doc',
            'size' => $fileSize
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Khong the tao file');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
