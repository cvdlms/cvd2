<?php
/**
 * build_template.php — Sinh file mẫu Word (template) cho việc xuất
 * "Ma trận + Bản đặc tả" của trang teacher/matrix_builder.php.
 *
 * Cách chạy (dòng lệnh):
 *   php teacher/api/matran/build_template.php
 *
 * Kết quả: teacher/data/template_matran.docx
 *
 * ---------------------------------------------------------------------------
 * MẪU NÀY DÙNG CÁC MACRO DẠNG ${...}. Thầy/cô có thể mở file .docx bằng Word
 * để tuỳ chỉnh (đổi font, màu, thêm logo, thêm dòng ký tên, ...) nhưng PHẢI
 * GIỮ NGUYÊN các macro dưới đây thì việc xuất file mới chạy đúng.
 *
 * Macro dùng chung (điền 1 lần cho toàn file):
 *   ${school} ${subject} ${grade} ${examType} ${semester} ${year} ${duration}
 *   ${tnkq_per_q}   – điểm mỗi câu TNKQ nhiều lựa chọn
 *   ${ds_pt_item}   – điểm mỗi ý câu Đúng/Sai
 *
 * Bảng MA TRẬN (dòng dữ liệu - hệ thống tự nhân bản theo số đơn vị):
 *   ${m_nd} ${m_dv} ${m_tiet}
 *   ${m_tnkq_nb} ${m_tnkq_th} ${m_tnkq_vd}
 *   ${m_ds_nb} ${m_ds_th} ${m_ds_vd}
 *   ${m_tl_nb} ${m_tl_th} ${m_tl_vd}
 *   ${m_tong_nb} ${m_tong_th} ${m_tong_vd} ${m_pts} ${m_ratio}
 *
 * Bảng MA TRẬN (các dòng tổng):
 *   ${m_t_tnb} ${m_t_tth} ${m_t_tvd} ${m_t_dnb} ${m_t_dth} ${m_t_dvd}
 *   ${m_t_lnb} ${m_t_lth} ${m_t_lvd}
 *   ${m_t_tong_nb} ${m_t_tong_th} ${m_t_tong_vd}
 *   ${m_p_tnkq} ${m_p_ds} ${m_p_tl} ${m_p_total}
 *   ${m_r_tnkq} ${m_r_ds} ${m_r_tl}
 *
 * Bảng ĐẶC TẢ (dòng dữ liệu - hệ thống tự nhân bản theo số đơn vị):
 *   ${d_nd} ${d_dv} ${d_yccd}
 *   ${d_tnkq_nb} ${d_tnkq_th} ${d_tnkq_vd}
 *   ${d_ds_nb} ${d_ds_th} ${d_ds_vd}
 *   ${d_tl_nb} ${d_tl_th} ${d_tl_vd}
 *
 * File mẫu ĐỀ THI (teacher/data/template_dethi.docx) - mỗi mã đề là 1 bản mẫu:
 *   ${school} ${grade} ${year} ${semester} ${duration} ${variant}
 *   ${titleHeader} – dòng tiêu đề in hoa (VD: "KIỂM TRA GIỮA KỲ - MÔN TIN HỌC")
 *   ${EXAM_BODY}   – hệ thống chèn câu hỏi các phần I/II/III vào đây
 *   ${ANSWER_BODY} – hệ thống chèn bảng đáp án + hướng dẫn chấm vào đây
 *
 * Lưu ý: KHÔNG xoá các macro, không sửa tên macro, không tách macro thành
 * nhiều đoạn văn/chạy (run) khác nhau trong Word.
 * ---------------------------------------------------------------------------
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Script chỉ chạy được từ dòng lệnh (CLI).');
}

require_once __DIR__ . '/exporter.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

$M  = fn(string $k): string => '${' . $k . '}';
$NL = "\n";

$php = new PhpWord();
$php->setDefaultFontName(FONT_NAME);
$php->setDefaultFontSize(FONT_SIZE);
$section = $php->addSection([
    'orientation' => 'landscape', 'pageSizeW' => 16838, 'pageSizeH' => 11906,
    'marginTop' => MARGIN_TOP, 'marginBottom' => MARGIN_BTM,
    'marginLeft' => MARGIN_L,  'marginRight'  => MARGIN_R,
]);

// ───────────────────────────────────────────────────────────────────────
// BLOCK 1: MA TRẬN
// ───────────────────────────────────────────────────────────────────────
wText($section, $M('block_matran'), 1, false, Jc::START);
wText($section, $M('school'), FONT_TITLE, true, Jc::CENTER);
wText($section, 'MA TRẬN ĐỀ KIỂM TRA', FONT_TITLE, true, Jc::CENTER);
wText(
    $section,
    'Môn: ' . $M('subject') . '   Lớp: ' . $M('grade') . '   ' . $M('examType') . ' ' . $M('semester')
        . ' – Năm học ' . $M('year') . '   -   Thời gian: ' . $M('duration') . ' phút',
    10, false, Jc::CENTER, true
);

$cw  = buildCW();
$tbl = $section->addTable(tblStyle());
$tbl->addRow(wdPt(28));
wTh($tbl, $cw[0], 'Chủ đề / Đơn vị kiến thức (tiết)', true, 2);
wThSpan($tbl, array_sum(array_slice($cw, 1, 3)), 'TNKQ Nhiều lựa chọn' . $NL . '(' . $M('tnkq_per_q') . 'đ/câu)', 3);
wThSpan($tbl, array_sum(array_slice($cw, 4, 3)), 'TNKQ Đúng/Sai' . $NL . '(' . $M('ds_pt_item') . 'đ/ý)', 3);
wThSpan($tbl, array_sum(array_slice($cw, 7, 3)), 'Tự luận', 3);
wThSpan($tbl, array_sum(array_slice($cw, 10, 3)), 'Tổng số câu/ý', 3);
wTh($tbl, $cw[13], 'Tổng điểm', true, 2);
wTh($tbl, $cw[14], 'Tỉ lệ %', true, 2);
$tbl->addRow(wdPt(20));
wThContinue($tbl, $cw[0]);
foreach (['Biết', 'Hiểu', 'VD', 'Biết', 'Hiểu', 'VD', 'Biết', 'Hiểu', 'VD'] as $k => $lbl) {
    wTh($tbl, $cw[$k + 1], $lbl, false);
}
wTh($tbl, $cw[10], 'Biết', false);
wTh($tbl, $cw[11], 'Hiểu', false);
wTh($tbl, $cw[12], 'VD', false);
wThContinue($tbl, $cw[13]);
wThContinue($tbl, $cw[14]);

// Dòng dữ liệu mẫu (sẽ được nhân bản)
$tbl->addRow(0, ['cantSplit' => false]);
$cell = $tbl->addCell($cw[0], tdStyle());
$cell->addText($M('m_nd'), fnt(10, true), para(Jc::START));
$cell->addText($M('m_dv') . ' (' . $M('m_tiet') . ' tiết)', fnt(10, false), para(Jc::START));
wTd($tbl, $cw[1],  $M('m_tnkq_nb'));
wTd($tbl, $cw[2],  $M('m_tnkq_th'));
wTd($tbl, $cw[3],  $M('m_tnkq_vd'));
wTd($tbl, $cw[4],  $M('m_ds_nb'));
wTd($tbl, $cw[5],  $M('m_ds_th'));
wTd($tbl, $cw[6],  $M('m_ds_vd'));
wTd($tbl, $cw[7],  $M('m_tl_nb'));
wTd($tbl, $cw[8],  $M('m_tl_th'));
wTd($tbl, $cw[9],  $M('m_tl_vd'));
wTd($tbl, $cw[10], $M('m_tong_nb'), false, Jc::CENTER, 9);
wTd($tbl, $cw[11], $M('m_tong_th'), false, Jc::CENTER, 9);
wTd($tbl, $cw[12], $M('m_tong_vd'), false, Jc::CENTER, 9);
wTd($tbl, $cw[13], $M('m_pts'), true);
wTd($tbl, $cw[14], $M('m_ratio'), true);

// Dòng "Tổng số câu/ý"
$tbl->addRow(wdPt(22));
wTdB($tbl, $cw[0], 'Tổng số câu/ý', Jc::START);
wTdB($tbl, $cw[1], $M('m_t_tnb'));
wTdB($tbl, $cw[2], $M('m_t_tth'));
wTdB($tbl, $cw[3], $M('m_t_tvd'));
wTdB($tbl, $cw[4], $M('m_t_dnb'));
wTdB($tbl, $cw[5], $M('m_t_dth'));
wTdB($tbl, $cw[6], $M('m_t_dvd'));
wTdB($tbl, $cw[7], $M('m_t_lnb'));
wTdB($tbl, $cw[8], $M('m_t_lth'));
wTdB($tbl, $cw[9], $M('m_t_lvd'));
wTdB($tbl, $cw[10], $M('m_t_tong_nb'));
wTdB($tbl, $cw[11], $M('m_t_tong_th'));
wTdB($tbl, $cw[12], $M('m_t_tong_vd'));
wTdB($tbl, $cw[13], '');
wTdB($tbl, $cw[14], '');

// Dòng "Tổng điểm"
$tbl->addRow(wdPt(22));
wTdB($tbl, $cw[0], 'Tổng điểm', Jc::START);
wTdBSpan($tbl, array_sum(array_slice($cw, 1, 3)), $M('m_p_tnkq'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 4, 3)), $M('m_p_ds'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 7, 3)), $M('m_p_tl'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 10, 4)), $M('m_p_total'), 4);
wTdB($tbl, $cw[14], '');

// Dòng "Tỉ lệ %"
$tbl->addRow(wdPt(22));
wTdB($tbl, $cw[0], 'Tỉ lệ %', Jc::START);
wTdBSpan($tbl, array_sum(array_slice($cw, 1, 3)), $M('m_r_tnkq'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 4, 3)), $M('m_r_ds'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 7, 3)), $M('m_r_tl'), 3);
wTdBSpan($tbl, array_sum(array_slice($cw, 10, 4)), '100%', 4);
wTdB($tbl, $cw[14], '');

wText($section, $M('/block_matran'), 1, false, Jc::START);

// Dấu ngắt trang giữa 2 bảng (chỉ dùng khi xuất cả hai)
wText($section, $M('block_pb'), 1, false, Jc::START);
$section->addPageBreak();
wText($section, $M('/block_pb'), 1, false, Jc::START);

// ───────────────────────────────────────────────────────────────────────
// BLOCK 2: BẢN ĐẶC TẢ
// ───────────────────────────────────────────────────────────────────────
wText($section, $M('block_dacta'), 1, false, Jc::START);
wText($section, 'BẢN ĐẶC TẢ ĐỀ KIỂM TRA', FONT_TITLE, true, Jc::CENTER);
wText(
    $section,
    'Môn: ' . $M('subject') . '   Lớp: ' . $M('grade') . '   ' . $M('examType') . ' ' . $M('semester')
        . ' – Năm học ' . $M('year'),
    10, false, Jc::CENTER, true
);

$total = CONTENT_W;
$dcw   = [1600, 2000, 4200];
$remain = $total - array_sum($dcw);
$each = intdiv($remain, 9);
$ext  = $remain - $each * 9;
$c9   = array_fill(0, 9, $each);
for ($i = 0; $i < $ext; $i++) {
    $c9[$i]++;
}
$dcw = array_merge($dcw, $c9);

$dtbl = $section->addTable(tblStyle());
$dtbl->addRow(wdPt(28));
wTh($dtbl, $dcw[0], 'Chương/Chủ đề', true, 2);
wTh($dtbl, $dcw[1], 'Đơn vị kiến thức', true, 2);
wTh($dtbl, $dcw[2], 'Yêu cầu cần đạt', true, 2);
wThSpan($dtbl, array_sum(array_slice($dcw, 3, 3)), 'TNKQ Nhiều lựa chọn', 3);
wThSpan($dtbl, array_sum(array_slice($dcw, 6, 3)), 'TNKQ Đúng/Sai', 3);
wThSpan($dtbl, array_sum(array_slice($dcw, 9, 3)), 'Tự luận', 3);
$dtbl->addRow(wdPt(20));
wThContinue($dtbl, $dcw[0]);
wThContinue($dtbl, $dcw[1]);
wThContinue($dtbl, $dcw[2]);
foreach (['Biết', 'Hiểu', 'VD', 'Biết', 'Hiểu', 'VD', 'Biết', 'Hiểu', 'VD'] as $k => $lbl) {
    wTh($dtbl, $dcw[$k + 3], $lbl, false);
}

// Dòng dữ liệu mẫu (sẽ được nhân bản)
$dtbl->addRow(0, ['cantSplit' => false]);
$dcell = $dtbl->addCell($dcw[0], tdStyle('left'));
$dcell->addText($M('d_nd'), fnt(9), para(Jc::START));
$dcell = $dtbl->addCell($dcw[1], tdStyle('left'));
$dcell->addText($M('d_dv'), fnt(9), para(Jc::START));
$dcell = $dtbl->addCell($dcw[2], tdStyle('left'));
$dcell->addText($M('d_yccd'), fnt(9), para(Jc::START));
wTd($dtbl, $dcw[3], $M('d_tnkq_nb'));
wTd($dtbl, $dcw[4], $M('d_tnkq_th'));
wTd($dtbl, $dcw[5], $M('d_tnkq_vd'));
wTd($dtbl, $dcw[6], $M('d_ds_nb'));
wTd($dtbl, $dcw[7], $M('d_ds_th'));
wTd($dtbl, $dcw[8], $M('d_ds_vd'));
wTd($dtbl, $dcw[9], $M('d_tl_nb'));
wTd($dtbl, $dcw[10], $M('d_tl_th'));
wTd($dtbl, $dcw[11], $M('d_tl_vd'));

wText($section, $M('/block_dacta'), 1, false, Jc::START);

// ───────────────────────────────────────────────────────────────────────
$outDir = dirname(__DIR__, 2) . '/data';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
$outFile = $outDir . '/template_matran.docx';

try {
    \PhpOffice\PhpWord\IOFactory::createWriter($php, 'Word2007')->save($outFile);
    echo "Đã tạo template: {$outFile}\n";
} catch (\Throwable $e) {
    echo "BỎ QUA {$outFile} (đang bị mở/khoá bởi chương trình khác - hãy đóng Word rồi chạy lại).\n";
}

// ───────────────────────────────────────────────────────────────────────
// FILE MẪU ĐỀ THI (khổ dọc A4)
// ───────────────────────────────────────────────────────────────────────
$ST = [
    'normal' => ['name' => 'Times New Roman', 'size' => 13],
    'bold'   => ['name' => 'Times New Roman', 'size' => 13, 'bold' => true],
    'title'  => ['name' => 'Times New Roman', 'size' => 15, 'bold' => true],
    'italic' => ['name' => 'Times New Roman', 'size' => 12, 'italic' => true],
    'de'     => ['name' => 'Times New Roman', 'size' => 16, 'bold' => true],
];

$phpEx = new PhpWord();
$phpEx->setDefaultFontName('Times New Roman');
$phpEx->setDefaultFontSize(13);
$exSec = $phpEx->addSection([
    'orientation'  => 'portrait',
    'pageSizeW'    => 11906,
    'pageSizeH'    => 16838,
    'marginTop'    => 900,
    'marginBottom' => 900,
    'marginLeft'   => 1100,
    'marginRight'  => 1100,
]);

$exSec->addText('TRƯỜNG ' . $M('school'), $ST['normal'], ['alignment' => 'left']);
$exSec->addText($M('titleHeader'), $ST['title'], ['alignment' => 'center', 'spaceBefore' => 40]);
$exSec->addText('Năm học ' . $M('year') . ' - ' . $M('semester'), $ST['normal'], ['alignment' => 'center']);
$exSec->addText('ĐỀ ' . $M('variant'), $ST['de'], ['alignment' => 'center', 'spaceAfter' => 40]);
$exSec->addText('Thời gian làm bài: ' . $M('duration') . ' phút (không kể thời gian phát đề)', $ST['normal'], ['alignment' => 'center', 'spaceAfter' => 60]);
$exSec->addText('Họ và tên học sinh: ...............................................  Lớp: .............', $ST['normal']);
$exSec->addText('', $ST['normal'], ['spaceAfter' => 40]);

// Điểm chèn nội dung câu hỏi (các phần I/II/III)
$exSec->addText($M('EXAM_BODY'), $ST['normal']);

// Điểm chèn nội dung đáp án (bảng + hướng dẫn chấm)
$exSec->addPageBreak();
$exSec->addText('ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM - ĐỀ ' . $M('variant'), $ST['title'], ['alignment' => 'center', 'spaceAfter' => 120]);
$exSec->addText($M('ANSWER_BODY'), $ST['normal']);

$outExam = $outDir . '/template_dethi.docx';
try {
    \PhpOffice\PhpWord\IOFactory::createWriter($phpEx, 'Word2007')->save($outExam);
    echo "Đã tạo template: {$outExam}\n";
} catch (\Throwable $e) {
    echo "BỎ QUA {$outExam} (đang bị mở/khoá bởi chương trình khác - hãy đóng Word rồi chạy lại).\n";
}
