<?php
/**
 * calculator.php — Logic phân bổ câu hỏi theo ma trận
 * Port từ eduvn/public/tools/matran sang CVD LMS
 */

require_once __DIR__ . '/config.php';

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
 * Chia $total ý của MỘT đơn vị thành $groups câu Đúng/Sai (kích thước gần đều nhau).
 * Ý được xếp theo thứ tự Biết -> Hiểu -> VD nên mỗi câu sẽ gom được mức phù hợp.
 *
 * @return array<int, array{nb:int, th:int, vd:int}>
 */
function mtnSplitDsGroups(int $total, int $groups, int $nb, int $th, int $vd): array {
    if ($total <= 0) return [];
    $g   = min(max(1, $groups), $total);
    $sizes = array_fill(0, $g, intdiv($total, $g));
    for ($i = 0; $i < $total % $g; $i++) $sizes[$i]++;

    $out = array_fill(0, $g, ['nb' => 0, 'th' => 0, 'vd' => 0]);
    $gi = 0;
    $left = $sizes[0];
    foreach (['nb' => $nb, 'th' => $th, 'vd' => $vd] as $lvl => $cnt) {
        for ($k = 0; $k < (int)$cnt; $k++) {
            while ($left === 0) {
                $gi++;
                if ($gi >= $g) return $out;
                $left = $sizes[$gi];
            }
            $out[$gi][$lvl]++;
            $left--;
        }
    }
    return $out;
}

/**
 * Gán các câu Đúng/Sai ($groups) cho 1 đơn vị: cộng vào tổng, sinh ds_qs và đánh số 1a,1b,...
 * $qNum là bộ đếm CHUNG xuyên suốt các đơn vị (1a,1b,... 2a,2b,...).
 */
function mtnApplyDsGroups(array &$u, array $groups, int &$qNum): void {
    $u['u_ds_nb'] = 0; $u['u_ds_th'] = 0; $u['u_ds_vd'] = 0;
    $u['ds_nb_nums'] = []; $u['ds_th_nums'] = []; $u['ds_vd_nums'] = [];
    $u['ds_qs'] = [];
    $u['ds_hosts'] = 0;

    foreach ($groups as $g) {
        $nb = (int)($g['nb'] ?? 0); $th = (int)($g['th'] ?? 0); $vd = (int)($g['vd'] ?? 0);
        $items = $nb + $th + $vd;
        if ($items <= 0) continue;
        $qNum++;
        $u['u_ds_nb'] += $nb; $u['u_ds_th'] += $th; $u['u_ds_vd'] += $vd;
        $u['ds_hosts']++;
        $lv = ($vd >= max($nb, $th)) ? 'VD' : (($th >= $nb) ? 'TH' : 'NB');
        $u['ds_qs'][] = ['nb' => $nb, 'th' => $th, 'vd' => $vd, 'level' => $lv];
        $sub = 0;
        for ($k = 0; $k < $nb; $k++) $u['ds_nb_nums'][] = $qNum . chr(97 + $sub++);
        for ($k = 0; $k < $th; $k++) $u['ds_th_nums'][] = $qNum . chr(97 + $sub++);
        for ($k = 0; $k < $vd; $k++) $u['ds_vd_nums'][] = $qNum . chr(97 + $sub++);
    }
}

/**
 * Áp dụng bảng tinh chỉnh (overrides) rồi đánh lại số TNKQ / Tự luận / Đúng-Sai.
 * Dùng chung cho generate.php và export_de.php.
 */
function mtnApplyOverrides(array $unitData, array $overrides): array {
    $allowed = ['u_tnkq_nb','u_tnkq_th','u_tnkq_vd','u_ds_nb','u_ds_th','u_ds_vd','u_tl_nb','u_tl_th','u_tl_vd'];
    foreach ($overrides as $i => $fields) {
        if (!isset($unitData[$i]) || !is_array($fields)) continue;
        foreach ($allowed as $f) { if (isset($fields[$f])) $unitData[$i][$f] = max(0, (int)$fields[$f]); }
    }

    $tnkqC = 1; $tlC = 1;
    foreach ($unitData as &$u) {
        $u['tnkq_nb_nums'] = []; for ($k = 0; $k < $u['u_tnkq_nb']; $k++) $u['tnkq_nb_nums'][] = $tnkqC++;
        $u['tnkq_th_nums'] = []; for ($k = 0; $k < $u['u_tnkq_th']; $k++) $u['tnkq_th_nums'][] = $tnkqC++;
        $u['tnkq_vd_nums'] = []; for ($k = 0; $k < $u['u_tnkq_vd']; $k++) $u['tnkq_vd_nums'][] = $tnkqC++;
        $u['tl_nb_nums'] = []; for ($k = 0; $k < $u['u_tl_nb']; $k++) $u['tl_nb_nums'][] = $tlC++;
        $u['tl_th_nums'] = []; for ($k = 0; $k < $u['u_tl_th']; $k++) $u['tl_th_nums'][] = $tlC++;
        $u['tl_vd_nums'] = []; for ($k = 0; $k < $u['u_tl_vd']; $k++) $u['tl_vd_nums'][] = $tlC++;
    }
    unset($u);

    $dsQ = 0;
    foreach ($unitData as &$u) {
        $nb = (int)$u['u_ds_nb']; $th = (int)$u['u_ds_th']; $vd = (int)$u['u_ds_vd'];
        $total = $nb + $th + $vd;
        if ($total <= 0) { mtnApplyDsGroups($u, [], $dsQ); continue; }
        $hosts = max(1, (int)($u['ds_hosts'] ?? 1));
        mtnApplyDsGroups($u, mtnSplitDsGroups($total, $hosts, $nb, $th, $vd), $dsQ);
    }
    unset($u);

    return $unitData;
}


/**
 * Hàm tính toán chính
 */
function calculateMatrix(array $units, array $cfg): array {
    $errors = [];

    // ── Phần I: Trắc nghiệm nhiều lựa chọn ─────────────────────
    $tnkq_total_q  = max(1, (int)($cfg['tnkq_num'] ?? DEFAULT_TNKQ_NUM));
    $tnkq_per_q    = (float)($cfg['tnkq_ppq'] ?? 0);
    if ($tnkq_per_q <= 0) {
        $tnkq_per_q = ([8 => 0.5, 16 => 0.25, 20 => 0.2][$tnkq_total_q] ?? 0.25);
    }
    $tnkq_per_q    = round($tnkq_per_q, 4);
    $pts_tnkq      = round($tnkq_total_q * $tnkq_per_q, 2);

    // ── Phần II: Đúng/Sai (mỗi câu có DS_SUB_PER_Q ý) ──────────
    $ds_num        = max(0, (int)($cfg['ds_num'] ?? DEFAULT_DS_NUM));
    $ds_ppq        = max(0.0, (float)($cfg['ds_ppq'] ?? DEFAULT_DS_PPQ));
    $ds_sub        = DS_SUB_PER_Q;
    $pts_ds        = round($ds_num * $ds_ppq, 2);
    $ds_pt_per_item = ($ds_num > 0 && $ds_ppq > 0)
        ? round($ds_ppq / $ds_sub, 4)
        : DEFAULT_DS_PPQ / $ds_sub;

    // ── Phần III: Tự luận ──────────────────────────────────────
    $pts_tl        = max(0.0, (float)($cfg['tl_pts'] ?? DEFAULT_TL_PTS));
    $tl_num        = max(0, (int)($cfg['tl_num'] ?? DEFAULT_TL_NUM));
    $tl_per_q      = $tl_num > 0 ? $pts_tl / $tl_num : 0;

    $total_pts     = round($pts_tnkq + $pts_ds + $pts_tl, 2);
    $pct_nb        = (int)$cfg['pct_nb'];
    $pct_th        = (int)$cfg['pct_th'];
    $pct_vd        = (int)$cfg['pct_vd'];

    if (abs($total_pts - 10) > 0.01) $errors[] = 'Tổng điểm phải = 10đ (hiện tại: ' . fmtPt($total_pts) . 'đ)';
    if ($pct_nb + $pct_th + $pct_vd !== 100) $errors[] = 'Tổng % phải = 100';
    if (count($units) < 2) $errors[] = 'Cần ít nhất 2 đơn vị kiến thức';
    if ($pts_tl > 0 && $tl_num <= 0) $errors[] = 'Số câu Tự luận phải lớn hơn 0';
    if ($ds_num > 0 && $ds_ppq <= 0) $errors[] = 'Điểm mỗi câu Đúng/Sai phải lớn hơn 0';
    if (!empty($errors)) return ['unitData' => [], 'ctx' => [], 'errors' => $errors];

    $totalTiet = array_sum(array_column($units, 'tiet'));
    $weights   = array_column($units, 'tiet');

    // Bước 1: phân bổ tổng câu cho từng đơn vị theo tiết
    $tnkqPerUnit = allocateInts($tnkq_total_q, $weights);
    $tlPerUnit   = allocateInts($tl_num, $weights);

    // Bước 1b: phân bổ câu Đúng/Sai (ưu tiên đơn vị nhiều tiết; xoay vòng nếu ít đơn vị)
    $dsHostCount = array_fill(0, count($units), 0);
    if ($ds_num > 0) {
        $rank = array_keys($units);
        usort($rank, fn($a, $b) => ($units[$b]['tiet'] ?? 0) <=> ($units[$a]['tiet'] ?? 0));
        $eligible = array_values(array_filter($rank, function ($i) use ($units) {
            $m = $units[$i]['muc'] ?? [];
            return !empty($m['Nhận biết']) || !empty($m['Thông hiểu']) || !empty($m['Vận dụng']) || !empty($m['Vận dụng cao']);
        }));
        if (!$eligible) $eligible = $rank;
        for ($k = 0; $k < $ds_num; $k++) $dsHostCount[$eligible[$k % count($eligible)]]++;
    }

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
        $hosts = (int)($dsHostCount[$i] ?? 0);
        [$ds_nb, $ds_th, $ds_vd] = $hosts > 0
            ? splitByLevel($hosts * $ds_sub, $pct_nb, $pct_th, $pct_vd, $hasNB, $hasTH, $hasVD)
            : [0, 0, 0];

        $unitData[$i] = array_merge($u, [
            'idx'        => $i,   // giữ index gốc để tránh nhầm khi sort
            'ratio'      => $totalTiet > 0 ? $u['tiet'] / $totalTiet : 0,
            'hasNB'      => $hasNB,
            'hasTH'      => $hasTH,
            'hasVD'      => $hasVD,
            'u_tnkq_nb'  => $tq_nb, 'u_tnkq_th' => $tq_th, 'u_tnkq_vd' => $tq_vd,
            'u_ds_nb'    => $ds_nb, 'u_ds_th'   => $ds_th, 'u_ds_vd'   => $ds_vd,
            'u_tl_nb'    => $tl_nb,  'u_tl_th'  => $tl_th,  'u_tl_vd'  => $tl_vd,
            'ds_hosts'   => $hosts, 'ds_qs' => [],
            'tnkq_nb_nums' => [], 'tnkq_th_nums' => [], 'tnkq_vd_nums' => [],
            'tl_nb_nums'   => [], 'tl_th_nums'   => [], 'tl_vd_nums'   => [],
            'ds_nb_nums'   => [], 'ds_th_nums'   => [], 'ds_vd_nums'   => [],
        ]);
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

    // Đánh số Đúng/Sai: 1a,1b,... 2a,2b,... (mỗi câu = 1 đơn vị host)
    $dsQNum = 0;
    foreach ($unitData as &$u) {
        $totalDs = $u['u_ds_nb'] + $u['u_ds_th'] + $u['u_ds_vd'];
        $groups  = $totalDs > 0
            ? mtnSplitDsGroups($totalDs, max(1, $u['ds_hosts']), $u['u_ds_nb'], $u['u_ds_th'], $u['u_ds_vd'])
            : [];
        mtnApplyDsGroups($u, $groups, $dsQNum);
    }
    unset($u);

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
        'tnkq_total_q', 'totalTiet', 'ds_num', 'ds_ppq', 'ds_sub'
    );

    return [
        'unitData' => array_values($unitData),
        'ctx'      => $ctx,
        'warnings' => $warnings,
        'errors'   => [],
    ];
}
