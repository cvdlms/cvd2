<?php
/**
 * calculator.php — Logic phân bổ câu hỏi theo ma trận
 * Port từ eduvn/public/tools/matran sang CVD LMS
 */

/**
 * Largest Remainder Method — phân bổ $total đơn vị nguyên theo weights
 */
function allocateInts(int $total, array $weights): array {
    $n    = count($weights);
    $wsum = array_sum($weights);
    if ($total <= 0 || $wsum <= 0) return array_fill(0, $n, 0);

    $raw    = array_map(fn($w) => $total * $w / $wsum, $weights);
    $base   = array_map('floor', $raw);
    $remain = $total - (int)array_sum($base);

    $idx = range(0, $n - 1);
    usort($idx, fn($a, $b) => ($raw[$b] - $base[$b]) <=> ($raw[$a] - $base[$a]));
    for ($k = 0; $k < $remain; $k++) $base[$idx[$k]]++;

    return array_map('intval', $base);
}

/**
 * Chia $total câu vào các mức CÓ SẴN theo pct làm trọng số.
 * Chỉ phân vào mức hasNB/hasTH/hasVD = true.
 * Trả về [nb, th, vd].
 */
function splitByLevel(int $total, int $pNB, int $pTH, int $pVD,
                      bool $hasNB, bool $hasTH, bool $hasVD): array {
    if ($total === 0) return [0, 0, 0];

    $keys = []; $ws = [];
    if ($hasNB) { $keys[] = 'NB'; $ws[] = $pNB; }
    if ($hasTH) { $keys[] = 'TH'; $ws[] = $pTH; }
    if ($hasVD) { $keys[] = 'VD'; $ws[] = $pVD; }
    if (empty($keys)) return [0, 0, 0];

    $split = allocateInts($total, $ws);
    $nb = $th = $vd = 0;
    foreach ($keys as $i => $k) {
        if ($k === 'NB') $nb = $split[$i];
        elseif ($k === 'TH') $th = $split[$i];
        else $vd = $split[$i];
    }
    return [$nb, $th, $vd];
}

/**
 * Lấy text yêu cầu cần đạt theo mức độ
 */
function getReqText(array $muc, string $lvl): array {
    $mapVi = [
        'NB' => ['Nhận biết'],
        'TH' => ['Thông hiểu'],
        'VD' => ['Vận dụng', 'Vận dụng cao'],
    ];
    $texts = [];
    foreach ($mapVi[$lvl] ?? [] as $k) {
        if (!empty($muc[$k])) $texts = array_merge($texts, (array)$muc[$k]);
    }
    return $texts;
}

function getEffectiveLevels(array $muc): array {
    return [
        'hasNB' => !empty($muc['Nhận biết']),
        'hasTH' => !empty($muc['Thông hiểu']),
        'hasVD' => !empty($muc['Vận dụng']) || !empty($muc['Vận dụng cao']),
    ];
}

function fmtPt(float $n): string {
    $v = round($n, 2);
    return (fmod($v, 1.0) == 0) ? number_format($v, 1) : number_format($v, 2);
}

function fmtNums(array $arr): string {
    return count($arr) ? implode(', ', $arr) : '—';
}

/**
 * Hàm tính toán chính
 */
function calculateMatrix(array $units, array $cfg): array {
    $errors = [];

    $tnkq_total_q  = (int)$cfg['tnkq_num'];
    $tnkq_per_q    = $tnkq_total_q === 8 ? 0.5 : 0.25;
    $pts_tnkq      = round($tnkq_total_q * $tnkq_per_q, 2);
    $pts_ds        = 2.0;
    $pts_tl        = (float)$cfg['tl_pts'];
    $tl_num        = (int)$cfg['tl_num'];
    $total_pts     = $pts_tnkq + $pts_ds + $pts_tl;
    $pct_nb        = (int)$cfg['pct_nb'];
    $pct_th        = (int)$cfg['pct_th'];
    $pct_vd        = (int)$cfg['pct_vd'];
    $tl_per_q      = $pts_tl / $tl_num;
    $DS_PARENT_Q   = 2;
    $DS_SUB_PER_Q  = 4;
    $ds_pt_per_item = $pts_ds / ($DS_PARENT_Q * $DS_SUB_PER_Q);

    if (abs($total_pts - 10) > 0.01) $errors[] = 'Tổng điểm phải = 10đ';
    if ($pct_nb + $pct_th + $pct_vd !== 100) $errors[] = 'Tổng % phải = 100';
    if (count($units) < 2) $errors[] = 'Cần ít nhất 2 đơn vị kiến thức';
    if (!empty($errors)) return ['unitData' => [], 'ctx' => [], 'errors' => $errors];

    $totalTiet = array_sum(array_column($units, 'tiet'));
    $weights   = array_column($units, 'tiet');

    // Bước 1: phân bổ tổng câu cho từng đơn vị theo tiết
    $tnkqPerUnit = allocateInts($tnkq_total_q, $weights);
    $tlPerUnit   = allocateInts($tl_num, $weights);

    // Bước 2: trong từng đơn vị, chia theo mức có sẵn
    $unitData = [];
    foreach ($units as $i => $u) {
        $lvl   = getEffectiveLevels($u['muc']);
        $hasNB = $lvl['hasNB'];
        $hasTH = $lvl['hasTH'];
        $hasVD = $lvl['hasVD'];

        [$tq_nb, $tq_th, $tq_vd] = splitByLevel(
            $tnkqPerUnit[$i], $pct_nb, $pct_th, $pct_vd, $hasNB, $hasTH, $hasVD
        );
        [$tl_nb, $tl_th, $tl_vd] = splitByLevel(
            $tlPerUnit[$i], $pct_nb, $pct_th, $pct_vd, $hasNB, $hasTH, $hasVD
        );

        $unitData[$i] = array_merge($u, [
            'idx'        => $i,   // giữ index gốc để tránh nhầm khi sort
            'ratio'      => $totalTiet > 0 ? $u['tiet'] / $totalTiet : 0,
            'hasNB'      => $hasNB,
            'hasTH'      => $hasTH,
            'hasVD'      => $hasVD,
            'u_tnkq_nb'  => $tq_nb, 'u_tnkq_th' => $tq_th, 'u_tnkq_vd' => $tq_vd,
            'u_ds_nb'    => 0,    'u_ds_th'   => 0,    'u_ds_vd'   => 0,
            'u_tl_nb'    => $tl_nb,  'u_tl_th'  => $tl_th,  'u_tl_vd'  => $tl_vd,
            'tnkq_nb_nums' => [], 'tnkq_th_nums' => [], 'tnkq_vd_nums' => [],
            'tl_nb_nums'   => [], 'tl_th_nums'   => [], 'tl_vd_nums'   => [],
            'ds_nb_nums'   => [], 'ds_th_nums'   => [], 'ds_vd_nums'   => [],
        ]);
    }

    // Đúng/Sai: chọn 2 đơn vị tiết cao nhất, giữ thứ tự bảng
    $sortedIdx = array_keys($unitData);
    usort($sortedIdx, fn($a, $b) => $unitData[$b]['tiet'] <=> $unitData[$a]['tiet']);
    $dsHostIdx = array_slice($sortedIdx, 0, $DS_PARENT_Q);
    sort($dsHostIdx); // thứ tự xuất hiện trong bảng

    foreach ($dsHostIdx as $idx) {
        [$a_nb, $a_th, $a_vd] = splitByLevel(
            $DS_SUB_PER_Q, $pct_nb, $pct_th, $pct_vd,
            $unitData[$idx]['hasNB'], $unitData[$idx]['hasTH'], $unitData[$idx]['hasVD']
        );
        $unitData[$idx]['u_ds_nb'] = $a_nb;
        $unitData[$idx]['u_ds_th'] = $a_th;
        $unitData[$idx]['u_ds_vd'] = $a_vd;
    }

    // Đánh số TNKQ và TL xuyên suốt
    $tnkq_counter = 1;
    $tl_counter   = 1;
    foreach ($unitData as &$u) {
        for ($k = 0; $k < $u['u_tnkq_nb']; $k++) $u['tnkq_nb_nums'][] = $tnkq_counter++;
        for ($k = 0; $k < $u['u_tnkq_th']; $k++) $u['tnkq_th_nums'][] = $tnkq_counter++;
        for ($k = 0; $k < $u['u_tnkq_vd']; $k++) $u['tnkq_vd_nums'][] = $tnkq_counter++;
        for ($k = 0; $k < $u['u_tl_nb'];   $k++) $u['tl_nb_nums'][]   = $tl_counter++;
        for ($k = 0; $k < $u['u_tl_th'];   $k++) $u['tl_th_nums'][]   = $tl_counter++;
        for ($k = 0; $k < $u['u_tl_vd'];   $k++) $u['tl_vd_nums'][]   = $tl_counter++;
    }
    unset($u);

    // Đánh số Đúng/Sai: 1a,1b,... 2a,2b,...
    foreach ($dsHostIdx as $qPos => $idx) {
        $qNum = $qPos + 1;
        $sub  = 0;
        for ($k = 0; $k < $unitData[$idx]['u_ds_nb']; $k++)
            $unitData[$idx]['ds_nb_nums'][] = $qNum . chr(97 + $sub++);
        for ($k = 0; $k < $unitData[$idx]['u_ds_th']; $k++)
            $unitData[$idx]['ds_th_nums'][] = $qNum . chr(97 + $sub++);
        for ($k = 0; $k < $unitData[$idx]['u_ds_vd']; $k++)
            $unitData[$idx]['ds_vd_nums'][] = $qNum . chr(97 + $sub++);
    }

    // Cảnh báo nếu có mức bị điều chỉnh
    $warnings = [];
    foreach ($unitData as $u) {
        if (!$u['hasVD'] && ($u['u_tnkq_vd'] > 0 || $u['u_tl_vd'] > 0 || $u['u_ds_vd'] > 0)) {
            $warnings[] = '"' . $u['dv'] . '": không có mức VD - đã chuyển.';
        }
        if (!$u['hasNB'] && ($u['u_tnkq_nb'] > 0 || $u['u_tl_nb'] > 0)) {
            $warnings[] = '"' . $u['dv'] . '": không có mức Biết - đã chuyển lên Hiểu.';
        }
    }

    $ctx = compact(
        'tnkq_per_q', 'ds_pt_per_item', 'pts_ds', 'pts_tl', 'tl_num',
        'pct_nb', 'pct_th', 'pct_vd', 'pts_tnkq', 'total_pts', 'tl_per_q',
        'tnkq_total_q', 'totalTiet'
    );

    return [
        'unitData' => array_values($unitData),
        'ctx'      => $ctx,
        'warnings' => $warnings,
        'errors'   => [],
    ];
}
