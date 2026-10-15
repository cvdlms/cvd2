<?php
/**
 * exporter.php — Tạo file Word (.docx) bằng PhpWord
 * Layout: A4 ngang, không màu nền, border đen, Times New Roman
 * Port từ eduvn/public/tools/matran sang CVD LMS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calculator.php';

// PhpWord: dùng vendor của CVD LMS
if (!class_exists('PhpOffice\PhpWord\PhpWord', false)) {
    $vendorPath = dirname(__DIR__, 3) . '/vendor/autoload.php';
    if (file_exists($vendorPath)) require_once $vendorPath;
}

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table as TableStyle;

/**
 * Đường dẫn file mẫu Word. Nếu tồn tại, hệ thống sẽ điền vào mẫu này;
 * nếu không, hệ thống dựng file trực tiếp bằng code (như trước đây).
 */
function matranTemplatePath(): string {
    return dirname(__DIR__, 2) . '/data/template_matran.docx';
}

/** Escape ký tự đặc biệt trước khi chèn vào XML của file Word. */
function mtxEsc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

const BORDER_SZ  = 8;
const BORDER_CLR = '000000';
const FONT_NAME  = 'Times New Roman';
const FONT_SIZE  = 10;
const FONT_TITLE = 13;

define('CW', [2800, 680, 680, 680, 680, 680, 680, 680, 680, 680, 1280, 1300, 660]);

function buildCW(): array {
    $total  = CONTENT_W;
    $c12    = 700; $c11 = 1400; $c10 = 1400;
    $remain = $total - $c10 - $c11 - $c12;
    $c0     = (int)($remain * 0.30);
    $col9   = $remain - $c0;
    $each   = intdiv($col9, 9);
    $extra  = $col9 - $each * 9;
    $cols   = array_fill(0, 9, $each);
    for ($i = 0; $i < $extra; $i++) $cols[$i]++;
    return array_merge([$c0], $cols, [$c10, $c11, $c12]);
}

function exportMatranWord(array $unitData, array $ctx, array $meta, string $type): void {
    $subj   = preg_replace('/[^a-zA-Z0-9\-_]/', '', str_replace(' ', '-', $meta['subject']));
    $fname  = match($type) { 'matran' => 'ma-tran', 'dacta' => 'dac-ta', default => 'ma-tran-dac-ta' };
    $fname .= "-{$subj}-lop{$meta['grade']}-{$meta['semester']}.docx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Cache-Control: max-age=0');

    // Ưu tiên điền vào file mẫu Word (nếu có)
    $tplPath = matranTemplatePath();
    if (is_file($tplPath) && class_exists('PhpOffice\PhpWord\TemplateProcessor')) {
        try {
            $processor = renderMatranTemplate($tplPath, $unitData, $ctx, $meta, $type);
            $tmp = tempnam(sys_get_temp_dir(), 'cvdmatran');
            if ($tmp !== false) {
                $tmpDocx = $tmp . '.docx';
                @unlink($tmp);
                $processor->saveAs($tmpDocx);
                readfile($tmpDocx);
                @unlink($tmpDocx);
                return;
            }
        } catch (\Throwable $e) {
            error_log('CVD Matran: xuất theo mẫu thất bại, chuyển sang dựng trực tiếp. ' . $e->getMessage());
        }
    }

    $phpWord = buildMatranDoc($unitData, $ctx, $meta, $type);
    \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
}

/**
 * Điền dữ liệu vào file mẫu Word (teacher/data/template_matran.docx).
 */
function renderMatranTemplate(string $tplPath, array $unitData, array $ctx, array $meta, string $type): TemplateProcessor {
    $tpl = new TemplateProcessor($tplPath);

    // Chọn phần cần xuất: ma trận, đặc tả hay cả hai
    // cloneBlock($name, 1) giữ lại block (đồng thời xoá 2 dòng đánh dấu),
    // cloneBlock($name, 0) xoá hẳn block.
    $tpl->cloneBlock('block_matran', ($type === 'dacta')  ? 0 : 1);
    $tpl->cloneBlock('block_pb',     ($type === 'all')    ? 1 : 0);
    $tpl->cloneBlock('block_dacta',  ($type === 'matran') ? 0 : 1);

    // Macro dùng chung
    $tpl->setValues([
        'school'     => mtxEsc($meta['school']),
        'subject'    => mtxEsc($meta['subject']),
        'grade'      => mtxEsc($meta['grade']),
        'examType'   => mtxEsc($meta['examType']),
        'semester'   => mtxEsc($meta['semester']),
        'year'       => mtxEsc($meta['year']),
        'duration'   => mtxEsc($meta['duration']),
        'tnkq_per_q' => (string)(float)$ctx['tnkq_per_q'],
    ]);

    if ($type === 'matran' || $type === 'all') {
        fillMatranBlock($tpl, $unitData, $ctx);
    }
    if ($type === 'dacta' || $type === 'all') {
        fillDacTaBlock($tpl, $unitData);
    }

    return $tpl;
}

function fillMatranBlock(TemplateProcessor $tpl, array $unitData, array $ctx): void {
    $tnkq_per_q     = $ctx['tnkq_per_q'];
    $ds_pt_per_item = $ctx['ds_pt_per_item'];
    $tl_per_q       = $ctx['tl_per_q'];

    $n = count($unitData);
    $tpl->cloneRow('m_nd', $n);
    $tot = array_fill_keys(['tnb','tth','tvd','dnb','dth','dvd','lnb','lth','lvd'], 0);
    foreach ($unitData as $i => $u) {
        $r = $i + 1;
        $tpl->setValue("m_nd#$r", mtxEsc($u['nd']));
        $tpl->setValue("m_dv#$r", mtxEsc($u['dv']));
        $tpl->setValue("m_tiet#$r", (string)$u['tiet']);
        $tpl->setValue("m_tnkq_nb#$r", fmtNums($u['tnkq_nb_nums']));
        $tpl->setValue("m_tnkq_th#$r", fmtNums($u['tnkq_th_nums']));
        $tpl->setValue("m_tnkq_vd#$r", fmtNums($u['tnkq_vd_nums']));
        $tpl->setValue("m_ds_nb#$r",   fmtNums($u['ds_nb_nums']));
        $tpl->setValue("m_ds_th#$r",   fmtNums($u['ds_th_nums']));
        $tpl->setValue("m_ds_vd#$r",   fmtNums($u['ds_vd_nums']));
        $tpl->setValue("m_tl_nb#$r",   fmtNums($u['tl_nb_nums']));
        $tpl->setValue("m_tl_th#$r",   fmtNums($u['tl_th_nums']));
        $tpl->setValue("m_tl_vd#$r",   fmtNums($u['tl_vd_nums']));

        $sumCau = ($u['u_tnkq_nb']+$u['u_tnkq_th']+$u['u_tnkq_vd']).'c TN + '
                . ($u['u_ds_nb']+$u['u_ds_th']+$u['u_ds_vd']).'ý DS + '
                . ($u['u_tl_nb']+$u['u_tl_th']+$u['u_tl_vd']).'c TL';
        $tpl->setValue("m_sum_cau#$r", mtxEsc($sumCau));
        $pts = ($u['u_tnkq_nb']+$u['u_tnkq_th']+$u['u_tnkq_vd'])*$tnkq_per_q
             + ($u['u_ds_nb']+$u['u_ds_th']+$u['u_ds_vd'])*$ds_pt_per_item
             + ($u['u_tl_nb']+$u['u_tl_th']+$u['u_tl_vd'])*$tl_per_q;
        $tpl->setValue("m_pts#$r", fmtPt($pts).'đ');
        $tpl->setValue("m_ratio#$r", round($u['ratio']*100).'%');

        $tot['tnb']+=$u['u_tnkq_nb']; $tot['tth']+=$u['u_tnkq_th']; $tot['tvd']+=$u['u_tnkq_vd'];
        $tot['dnb']+=$u['u_ds_nb'];   $tot['dth']+=$u['u_ds_th'];   $tot['dvd']+=$u['u_ds_vd'];
        $tot['lnb']+=$u['u_tl_nb'];   $tot['lth']+=$u['u_tl_th'];   $tot['lvd']+=$u['u_tl_vd'];
    }

    $totTNKQ=$tot['tnb']+$tot['tth']+$tot['tvd']; $totDS=$tot['dnb']+$tot['dth']+$tot['dvd']; $totTL=$tot['lnb']+$tot['lth']+$tot['lvd'];
    $ptsTNKQ=$totTNKQ*$tnkq_per_q; $ptsDS=$totDS*$ds_pt_per_item; $ptsTL=$totTL*$tl_per_q; $grand=$ptsTNKQ+$ptsDS+$ptsTL;
    $pTNKQ=$grand>0?round($ptsTNKQ/$grand*100):0; $pDS=$grand>0?round($ptsDS/$grand*100):0; $pTL=100-$pTNKQ-$pDS;

    $tpl->setValues([
        'm_t_tnb' => $tot['tnb'].'c', 'm_t_tth' => $tot['tth'].'c', 'm_t_tvd' => $tot['tvd'].'c',
        'm_t_dnb' => $tot['dnb'].'ý', 'm_t_dth' => $tot['dth'].'ý', 'm_t_dvd' => $tot['dvd'].'ý',
        'm_t_lnb' => $tot['lnb'].'c', 'm_t_lth' => $tot['lth'].'c', 'm_t_lvd' => $tot['lvd'].'c',
        'm_t_tong' => $totTNKQ.'c TN + '.$totDS.'ý DS + '.$totTL.'c TL',
        'm_p_tnkq' => fmtPt($ptsTNKQ).'đ', 'm_p_ds' => fmtPt($ptsDS).'đ', 'm_p_tl' => fmtPt($ptsTL).'đ',
        'm_p_total' => fmtPt($grand).'đ',
        'm_r_tnkq' => $pTNKQ.'%', 'm_r_ds' => $pDS.'%', 'm_r_tl' => $pTL.'%',
    ]);
}

function fillDacTaBlock(TemplateProcessor $tpl, array $unitData): void {
    $n = count($unitData);
    $tpl->cloneRow('d_nd', $n);
    foreach ($unitData as $i => $u) {
        $r = $i + 1;
        $tpl->setValue("d_nd#$r", mtxEsc($u['nd']));
        $tpl->setValue("d_dv#$r", mtxEsc($u['dv']));
        $tpl->setComplexValue("d_yccd#$r", reqTextRun($u['muc']));
        $tpl->setValue("d_tnkq_nb#$r", fmtNums($u['tnkq_nb_nums']));
        $tpl->setValue("d_tnkq_th#$r", fmtNums($u['tnkq_th_nums']));
        $tpl->setValue("d_tnkq_vd#$r", fmtNums($u['tnkq_vd_nums']));
        $tpl->setValue("d_ds_nb#$r",   fmtNums($u['ds_nb_nums']));
        $tpl->setValue("d_ds_th#$r",   fmtNums($u['ds_th_nums']));
        $tpl->setValue("d_ds_vd#$r",   fmtNums($u['ds_vd_nums']));
        $tpl->setValue("d_tl_nb#$r",   fmtNums($u['tl_nb_nums']));
        $tpl->setValue("d_tl_th#$r",   fmtNums($u['tl_th_nums']));
        $tpl->setValue("d_tl_vd#$r",   fmtNums($u['tl_vd_nums']));
    }
}

/**
 * Yêu cầu cần đạt dạng nhiều dòng (Nhận biết / Thông hiểu / Vận dụng).
 */
function reqTextRun(array $muc): TextRun {
    $run = new TextRun(['alignment' => Jc::START, 'spaceAfter' => 0, 'spaceBefore' => 0]);
    $groups = [
        ['Nhận biết:',  getReqText($muc, 'NB')],
        ['Thông hiểu:', getReqText($muc, 'TH')],
        ['Vận dụng:',   getReqText($muc, 'VD')],
    ];
    $first = true;
    foreach ($groups as [$label, $items]) {
        if (!$items) continue;
        if (!$first) $run->addTextBreak();
        $run->addText(htmlspecialchars($label), fnt(9, true));
        foreach ($items as $r) {
            $run->addTextBreak();
            $run->addText(htmlspecialchars($r), fnt(9, false));
        }
        $first = false;
    }
    return $run;
}

function buildMatranDoc(array $unitData, array $ctx, array $meta, string $type): PhpWord {
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName(FONT_NAME);
    $phpWord->setDefaultFontSize(FONT_SIZE);
    $section = $phpWord->addSection([
        'orientation' => 'landscape', 'pageSizeW' => 16838, 'pageSizeH' => 11906,
        'marginTop' => MARGIN_TOP, 'marginBottom' => MARGIN_BTM,
        'marginLeft' => MARGIN_L,  'marginRight'  => MARGIN_R,
    ]);
    if ($type === 'matran' || $type === 'all') buildMaTran($section, $unitData, $ctx, $meta);
    if ($type === 'dacta'  || $type === 'all') {
        if ($type === 'all') $section->addPageBreak();
        buildDacTa($section, $unitData, $ctx, $meta);
    }
    return $phpWord;
}

function buildMaTran($section, array $unitData, array $ctx, array $meta): void {
    extract($ctx);
    $cw = buildCW();
    wText($section, strtoupper($meta['school']), FONT_TITLE, true, Jc::CENTER);
    wText($section, 'MA TRẬN ĐỀ KIỂM TRA', FONT_TITLE, true, Jc::CENTER);
    wText($section, "Môn: {$meta['subject']}   Lớp: {$meta['grade']}   {$meta['examType']} {$meta['semester']} – Năm học {$meta['year']}   Thời gian: {$meta['duration']} phút", 10, false, Jc::CENTER, true);
    $tbl = $section->addTable(tblStyle());
    $tbl->addRow(wdPt(28));
    wTh($tbl, $cw[0], 'Chủ đề / Đơn vị kiến thức (tiết)', true, 2);
    wThSpan($tbl, array_sum(array_slice($cw,1,3)), "TNKQ Nhiều lựa chọn\n({$tnkq_per_q}đ/câu)", 3);
    wThSpan($tbl, array_sum(array_slice($cw,4,3)), "TNKQ Đúng/Sai\n(0.25đ/ý)", 3);
    wThSpan($tbl, array_sum(array_slice($cw,7,3)), 'Tự luận', 3);
    wTh($tbl, $cw[10], 'Tổng số câu/ý', true, 2);
    wTh($tbl, $cw[11], 'Tổng điểm', true, 2);
    wTh($tbl, $cw[12], 'Tỉ lệ %', true, 2);
    $tbl->addRow(wdPt(20));
    wThContinue($tbl, $cw[0]);
    foreach (['Biết','Hiểu','VD','Biết','Hiểu','VD','Biết','Hiểu','VD'] as $k => $lbl) wTh($tbl, $cw[$k+1], $lbl, false);
    wThContinue($tbl, $cw[10]); wThContinue($tbl, $cw[11]); wThContinue($tbl, $cw[12]);
    $tot = array_fill_keys(['tnb','tth','tvd','dnb','dth','dvd','lnb','lth','lvd'], 0);
    foreach ($unitData as $u) {
        $tbl->addRow(0, ['cantSplit' => false]);
        $cell = $tbl->addCell($cw[0], tdStyle());
        $cell->addText(htmlspecialchars($u['nd']), fnt(10, true), para(Jc::START));
        $cell->addText(htmlspecialchars($u['dv'] . ' (' . $u['tiet'] . ' tiết)'), fnt(10, false), para(Jc::START));
        wTd($tbl, $cw[1],  fmtNums($u['tnkq_nb_nums'])); wTd($tbl, $cw[2],  fmtNums($u['tnkq_th_nums'])); wTd($tbl, $cw[3],  fmtNums($u['tnkq_vd_nums']));
        wTd($tbl, $cw[4],  fmtNums($u['ds_nb_nums']));   wTd($tbl, $cw[5],  fmtNums($u['ds_th_nums']));   wTd($tbl, $cw[6],  fmtNums($u['ds_vd_nums']));
        wTd($tbl, $cw[7],  fmtNums($u['tl_nb_nums']));   wTd($tbl, $cw[8],  fmtNums($u['tl_th_nums']));   wTd($tbl, $cw[9],  fmtNums($u['tl_vd_nums']));
        $sumCau = ($u['u_tnkq_nb']+$u['u_tnkq_th']+$u['u_tnkq_vd']).'c TN + '.($u['u_ds_nb']+$u['u_ds_th']+$u['u_ds_vd']).'ý DS + '.($u['u_tl_nb']+$u['u_tl_th']+$u['u_tl_vd']).'c TL';
        wTd($tbl, $cw[10], $sumCau, false, Jc::CENTER, 9);
        $pts = ($u['u_tnkq_nb']+$u['u_tnkq_th']+$u['u_tnkq_vd'])*$tnkq_per_q + ($u['u_ds_nb']+$u['u_ds_th']+$u['u_ds_vd'])*$ds_pt_per_item + ($u['u_tl_nb']+$u['u_tl_th']+$u['u_tl_vd'])*$tl_per_q;
        wTd($tbl, $cw[11], fmtPt($pts).'đ', true);
        wTd($tbl, $cw[12], round($u['ratio']*100).'%', true);
        $tot['tnb']+=$u['u_tnkq_nb']; $tot['tth']+=$u['u_tnkq_th']; $tot['tvd']+=$u['u_tnkq_vd'];
        $tot['dnb']+=$u['u_ds_nb'];   $tot['dth']+=$u['u_ds_th'];   $tot['dvd']+=$u['u_ds_vd'];
        $tot['lnb']+=$u['u_tl_nb'];   $tot['lth']+=$u['u_tl_th'];   $tot['lvd']+=$u['u_tl_vd'];
    }
    $totTNKQ=$tot['tnb']+$tot['tth']+$tot['tvd']; $totDS=$tot['dnb']+$tot['dth']+$tot['dvd']; $totTL=$tot['lnb']+$tot['lth']+$tot['lvd'];
    $ptsTNKQ=$totTNKQ*$tnkq_per_q; $ptsDS=$totDS*$ds_pt_per_item; $ptsTL=$totTL*$tl_per_q; $grand=$ptsTNKQ+$ptsDS+$ptsTL;
    $tbl->addRow(wdPt(22));
    wTdB($tbl,$cw[0],'Tổng số câu/ý',Jc::START); wTdB($tbl,$cw[1],$tot['tnb'].'c'); wTdB($tbl,$cw[2],$tot['tth'].'c'); wTdB($tbl,$cw[3],$tot['tvd'].'c'); wTdB($tbl,$cw[4],$tot['dnb'].'ý'); wTdB($tbl,$cw[5],$tot['dth'].'ý'); wTdB($tbl,$cw[6],$tot['dvd'].'ý'); wTdB($tbl,$cw[7],$tot['lnb'].'c'); wTdB($tbl,$cw[8],$tot['lth'].'c'); wTdB($tbl,$cw[9],$tot['lvd'].'c'); wTdBSpan($tbl,$cw[10]+$cw[11],$totTNKQ.'c TN + '.$totDS.'ý DS + '.$totTL.'c TL',2); wTdB($tbl,$cw[12],'');
    $tbl->addRow(wdPt(22));
    wTdB($tbl,$cw[0],'Tổng điểm',Jc::START); wTdBSpan($tbl,array_sum(array_slice($cw,1,3)),fmtPt($ptsTNKQ).'đ',3); wTdBSpan($tbl,array_sum(array_slice($cw,4,3)),fmtPt($ptsDS).'đ',3); wTdBSpan($tbl,array_sum(array_slice($cw,7,3)),fmtPt($ptsTL).'đ',3); wTdBSpan($tbl,$cw[10]+$cw[11],fmtPt($grand).'đ',2); wTdB($tbl,$cw[12],'');
    $pTNKQ=round($ptsTNKQ/$grand*100); $pDS=round($ptsDS/$grand*100); $pTL=100-$pTNKQ-$pDS;
    $tbl->addRow(wdPt(22));
    wTdB($tbl,$cw[0],'Tỉ lệ %',Jc::START); wTdBSpan($tbl,array_sum(array_slice($cw,1,3)),$pTNKQ.'%',3); wTdBSpan($tbl,array_sum(array_slice($cw,4,3)),$pDS.'%',3); wTdBSpan($tbl,array_sum(array_slice($cw,7,3)),$pTL.'%',3); wTdBSpan($tbl,$cw[10]+$cw[11],'100%',2); wTdB($tbl,$cw[12],'');
}

function buildDacTa($section, array $unitData, array $ctx, array $meta): void {
    wText($section,'BẢN ĐẶC TẢ ĐỀ KIỂM TRA',FONT_TITLE,true,Jc::CENTER);
    wText($section,"Môn: {$meta['subject']}   Lớp: {$meta['grade']}   {$meta['examType']} {$meta['semester']} – Năm học {$meta['year']}",10,false,Jc::CENTER,true);
    $total=CONTENT_W; $dcw=[1600,2000,4200]; $remain=$total-array_sum($dcw); $each=intdiv($remain,9); $ext=$remain-$each*9; $c9=array_fill(0,9,$each); for($i=0;$i<$ext;$i++)$c9[$i]++; $dcw=array_merge($dcw,$c9);
    $tbl=$section->addTable(tblStyle());
    $tbl->addRow(wdPt(28));
    wTh($tbl,$dcw[0],'Chương/Chủ đề',true,2); wTh($tbl,$dcw[1],'Đơn vị kiến thức',true,2); wTh($tbl,$dcw[2],'Yêu cầu cần đạt',true,2);
    wThSpan($tbl,array_sum(array_slice($dcw,3,3)),'TNKQ Nhiều lựa chọn',3); wThSpan($tbl,array_sum(array_slice($dcw,6,3)),'TNKQ Đúng/Sai',3); wThSpan($tbl,array_sum(array_slice($dcw,9,3)),'Tự luận',3);
    $tbl->addRow(wdPt(20));
    wThContinue($tbl,$dcw[0]); wThContinue($tbl,$dcw[1]); wThContinue($tbl,$dcw[2]);
    foreach(['Biết','Hiểu','VD','Biết','Hiểu','VD','Biết','Hiểu','VD'] as $k=>$lbl) wTh($tbl,$dcw[$k+3],$lbl,false);
    foreach($unitData as $u){
        $nb=getReqText($u['muc'],'NB'); $th=getReqText($u['muc'],'TH'); $vd=getReqText($u['muc'],'VD');
        $tbl->addRow(0,['cantSplit'=>false]);
        wTdLeft($tbl,$dcw[0],$u['nd'],9); wTdLeft($tbl,$dcw[1],$u['dv'],9);
        $cell=$tbl->addCell($dcw[2],tdStyle('left'));
        if($nb){$cell->addText('Nhận biết:',fnt(9,true),para(Jc::START));foreach($nb as $r)$cell->addText(htmlspecialchars('– '.$r),fnt(9),para(Jc::START));}
        if($th){$cell->addText('Thông hiểu:',fnt(9,true),para(Jc::START));foreach($th as $r)$cell->addText(htmlspecialchars('– '.$r),fnt(9),para(Jc::START));}
        if($vd){$cell->addText('Vận dụng:',fnt(9,true),para(Jc::START));foreach($vd as $r)$cell->addText(htmlspecialchars('– '.$r),fnt(9),para(Jc::START));}
        wTd($tbl,$dcw[3],fmtNums($u['tnkq_nb_nums'])); wTd($tbl,$dcw[4],fmtNums($u['tnkq_th_nums'])); wTd($tbl,$dcw[5],fmtNums($u['tnkq_vd_nums']));
        wTd($tbl,$dcw[6],fmtNums($u['ds_nb_nums']));   wTd($tbl,$dcw[7],fmtNums($u['ds_th_nums']));   wTd($tbl,$dcw[8],fmtNums($u['ds_vd_nums']));
        wTd($tbl,$dcw[9],fmtNums($u['tl_nb_nums']));   wTd($tbl,$dcw[10],fmtNums($u['tl_th_nums']));  wTd($tbl,$dcw[11],fmtNums($u['tl_vd_nums']));
    }
}

function tblStyle(): array { return ['borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ,'cellMarginTop'=>30,'cellMarginBottom'=>30,'cellMarginLeft'=>60,'cellMarginRight'=>60,'width'=>CONTENT_W,'unit'=>TblWidth::TWIP,'layout'=>TableStyle::LAYOUT_FIXED]; }
function tdStyle(string $align='center'): array { return ['valign'=>'center','borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ]; }
function fnt(int $size=FONT_SIZE, bool $bold=false): array { return ['name'=>FONT_NAME,'size'=>$size,'bold'=>$bold]; }
function para(string $jc=Jc::CENTER, int $spaceAfter=0): array { return ['alignment'=>$jc,'spaceAfter'=>$spaceAfter,'spaceBefore'=>0]; }
function wdPt(int $pt): int { return $pt*20; }
function wTh($tbl, int $w, string $text, bool $bold=true, int $rowspan=1): void { $style=['valign'=>'center','borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ]; if($rowspan>1)$style['vMerge']='restart'; $cell=$tbl->addCell($w,$style); foreach(explode("\n",$text) as $line)$cell->addText(htmlspecialchars($line),fnt(FONT_SIZE,$bold),para(Jc::CENTER)); }
function wThSpan($tbl, int $w, string $text, int $span): void { $cell=$tbl->addCell($w,['valign'=>'center','gridSpan'=>$span,'borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ]); foreach(explode("\n",$text) as $line)$cell->addText(htmlspecialchars($line),fnt(FONT_SIZE,true),para(Jc::CENTER)); }
function wThContinue($tbl, int $w): void { $tbl->addCell($w,['vMerge'=>'continue','borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ]); }
function wTd($tbl, int $w, string $text, bool $bold=false, string $jc=Jc::CENTER, int $size=FONT_SIZE): void { $cell=$tbl->addCell($w,tdStyle()); $cell->addText(htmlspecialchars($text),fnt($size,$bold),para($jc)); }
function wTdLeft($tbl, int $w, string $text, int $size=FONT_SIZE): void { $cell=$tbl->addCell($w,tdStyle('left')); $cell->addText(htmlspecialchars($text),fnt($size),para(Jc::START)); }
function wTdB($tbl, int $w, string $text, string $jc=Jc::CENTER): void { $cell=$tbl->addCell($w,tdStyle()); $cell->addText(htmlspecialchars($text),fnt(FONT_SIZE,true),para($jc)); }
function wTdBSpan($tbl, int $w, string $text, int $span, string $jc=Jc::CENTER): void { $cell=$tbl->addCell($w,['valign'=>'center','gridSpan'=>$span,'borderColor'=>BORDER_CLR,'borderSize'=>BORDER_SZ]); $cell->addText(htmlspecialchars($text),fnt(FONT_SIZE,true),para($jc)); }
function wText($section, string $text, int $size, bool $bold, string $jc=Jc::START, bool $italic=false): void { $section->addText(htmlspecialchars($text),['name'=>FONT_NAME,'size'=>$size,'bold'=>$bold,'italic'=>$italic],['alignment'=>$jc,'spaceAfter'=>60,'spaceBefore'=>0]); }
