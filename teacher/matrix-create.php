<?php
// matran-pro-strict.php
// Một-file: form + server-side xử lý phân bố nghiêm ngặt theo yêu cầu Sếp.
// Yêu cầu: PHP 7+, server local. Copy & chạy.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    // Lấy dữ liệu từ client
    $raw = $_POST['data'] ?? '';
    $input = json_decode($raw, true);
    if (!$input) { echo 'ERROR: Dữ liệu không hợp lệ'; exit; }

    // ====== CONFIG cứng theo yêu cầu Sếp ======
    $TOTAL_POINTS = 10.0;
    $TYPE_POINTS = ['TNKQ' => 4.0, 'DS' => 2.0, 'TL' => 4.0]; // bắt buộc
    $POINT_PER = ['TNKQ' => 0.5, 'DS' => 1.0 /*=4 ý*0.25*/, 'TL' => null]; // TL per question = 4.0 / n_tl
    // mức độ mục tiêu
    $LV_TARGET = ['VD' => 0.30 * $TOTAL_POINTS]; // VD must equal 3.0
    $LV_BOUNDS = [
      'NB' => [0.30 * $TOTAL_POINTS, 0.40 * $TOTAL_POINTS], // [3.0, 4.0]
      'TH' => [0.30 * $TOTAL_POINTS, 0.40 * $TOTAL_POINTS],
      'VD' => [$LV_TARGET['VD'], $LV_TARGET['VD']]
    ];

    // Chuẩn hóa input thành topics -> units
    $topics = $input['topics'] ?? [];
    // ensure fields
    $units_list = []; // flat list of units for allocation
    $total_tiet = 0.0;
    foreach ($topics as &$t) {
        $t['title'] = trim($t['title'] ?? ($t['chu_de'] ?? '(Chưa tên chủ đề)'));
        $t['so_tiet'] = 0.0;
        foreach ($t['units'] as &$u) {
            $u['title'] = trim($u['title'] ?? '(Đơn vị chưa tên)');
            $u['so_tiet'] = max(0, floatval($u['so_tiet'] ?? 0));
            $u['levels'] = $u['levels'] ?? ['NB'=>true,'TH'=>true,'VD'=>true];
            $t['so_tiet'] += $u['so_tiet'];
            $units_list[] = [
              'topic_title'=>$t['title'],
              'title'=>$u['title'],
              'so_tiet'=>$u['so_tiet'],
              'levels'=>$u['levels']
            ];
            $t['units_parsed'][] = $u;
        }
        $total_tiet += $t['so_tiet'];
    }
    unset($t,$u);

    if ($total_tiet <= 0.0) { echo 'ERROR: tổng số tiết phải > 0'; exit; }

    // ====== TÌM PHÂN BỐ TỔNG (Brute-force nhỏ) ======
    // fixed: TNKQ_count = 8, DS_question_count = 2 (=> DS_items = 8), TL_points=4.0, TL_question_count in [2..4] (user requirement)
    $TNKQ_total_q = 8;
    $DS_total_q = 2;
    $DS_total_items = $DS_total_q * 4; // 8 items (0.25 each)
    $TL_points = 4.0;
    $solutions = [];

    // For TL question count from 2 to 4, we partition TL_count into (nb,th,vd) integer
    for ($tl_count = 2; $tl_count <= 4; $tl_count++) {
        $tl_per_q_point = $TL_points / $tl_count; // point per TL question
        // enumerate partitions for TL_count into 3 non-negative integers (a,b,c)
        for ($a=0; $a <= $tl_count; $a++) {
            for ($b=0; $b <= $tl_count - $a; $b++) {
                $c = $tl_count - $a - $b;
                // TL points per level:
                $TL_pts = ['NB'=>$a * $tl_per_q_point, 'TH'=>$b * $tl_per_q_point, 'VD'=>$c * $tl_per_q_point];

                // Next: partition DS items (8 items) into NB,TH,VD but with constraint each level must have >=1 item across DS
                // so i,j,k >=1 and i+j+k = 8
                for ($i=1; $i<= $DS_total_items - 2; $i++) {
                    for ($j=1; $j<= $DS_total_items - $i -1; $j++) {
                        $k = $DS_total_items - $i - $j;
                        if ($k < 1) continue;
                        $DS_pts = ['NB' => $i * 0.25, 'TH' => $j * 0.25, 'VD' => $k * 0.25];

                        // Partition TNKQ 8 questions into x,y,z (>=0)
                        for ($x=0; $x<= $TNKQ_total_q; $x++) {
                            for ($y=0; $y<= $TNKQ_total_q - $x; $y++) {
                                $z = $TNKQ_total_q - $x - $y;
                                // TNKQ pts:
                                $TNKQ_pts = ['NB' => $x * 0.5, 'TH' => $y * 0.5, 'VD' => $z * 0.5];

                                // TOTAL per level = TNKQ_pts + DS_pts + TL_pts
                                $totNB = $TNKQ_pts['NB'] + $DS_pts['NB'] + $TL_pts['NB'];
                                $totTH = $TNKQ_pts['TH'] + $DS_pts['TH'] + $TL_pts['TH'];
                                $totVD = $TNKQ_pts['VD'] + $DS_pts['VD'] + $TL_pts['VD'];

                                // Check VD exactly target (within tiny epsilon)
                                if (abs($totVD - $LV_TARGET['VD']) > 0.001) continue;

                                // Check NB and TH within bounds
                                $nb_ok = ($totNB >= $LV_BOUNDS['NB'][0] - 0.001 && $totNB <= $LV_BOUNDS['NB'][1] + 0.001);
                                $th_ok = ($totTH >= $LV_BOUNDS['TH'][0] - 0.001 && $totTH <= $LV_BOUNDS['TH'][1] + 0.001);
                                if (!($nb_ok && $th_ok)) continue;

                                // If OK, compute small error metric (deviation from ideal midpoints 3.5 maybe)
                                $error = abs($totNB - (($LV_BOUNDS['NB'][0]+$LV_BOUNDS['NB'][1])/2))
                                       + abs($totTH - (($LV_BOUNDS['TH'][0]+$LV_BOUNDS['TH'][1])/2));
                                $solutions[] = [
                                    'tl_count'=>$tl_count,
                                    'TL_parts'=>['NB'=>$a,'TH'=>$b,'VD'=>$c],
                                    'DS_parts'=>['NB'=>$i,'TH'=>$j,'VD'=>$k],
                                    'TNKQ_parts'=>['NB'=>$x,'TH'=>$y,'VD'=>$z],
                                    'totals'=>['NB'=>$totNB,'TH'=>$totTH,'VD'=>$totVD],
                                    'error'=>$error
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    // If no solution found, relax slightly: allow VD to be within ±0.25 and NB/TH within [2.8,4.2]
    if (empty($solutions)) {
        for ($tl_count = 2; $tl_count <= 4; $tl_count++) {
            $tl_per_q_point = $TL_points / $tl_count;
            for ($a=0; $a <= $tl_count; $a++) {
                for ($b=0; $b <= $tl_count - $a; $b++) {
                    $c = $tl_count - $a - $b;
                    $TL_pts = ['NB'=>$a * $tl_per_q_point, 'TH'=>$b * $tl_per_q_point, 'VD'=>$c * $tl_per_q_point];
                    for ($i=1; $i<= $DS_total_items - 2; $i++) {
                        for ($j=1; $j<= $DS_total_items - $i -1; $j++) {
                            $k = $DS_total_items - $i - $j;
                            if ($k < 1) continue;
                            $DS_pts = ['NB' => $i * 0.25, 'TH' => $j * 0.25, 'VD' => $k * 0.25];
                            for ($x=0; $x<= $TNKQ_total_q; $x++) {
                                for ($y=0; $y<= $TNKQ_total_q - $x; $y++) {
                                    $z = $TNKQ_total_q - $x - $y;
                                    $TNKQ_pts = ['NB' => $x * 0.5, 'TH' => $y * 0.5, 'VD' => $z * 0.5];
                                    $totNB = $TNKQ_pts['NB'] + $DS_pts['NB'] + $TL_pts['NB'];
                                    $totTH = $TNKQ_pts['TH'] + $DS_pts['TH'] + $TL_pts['TH'];
                                    $totVD = $TNKQ_pts['VD'] + $DS_pts['VD'] + $TL_pts['VD'];
                                    if (abs($totVD - $LV_TARGET['VD']) > 0.25) continue;
                                    if ($totNB < 2.8 || $totNB > 4.2) continue;
                                    if ($totTH < 2.8 || $totTH > 4.2) continue;
                                    $error = abs($totNB - 3.5)+abs($totTH - 3.5)+abs($totVD - 3.0);
                                    $solutions[] = [
                                        'tl_count'=>$tl_count,
                                        'TL_parts'=>['NB'=>$a,'TH'=>$b,'VD'=>$c],
                                        'DS_parts'=>['NB'=>$i,'TH'=>$j,'VD'=>$k],
                                        'TNKQ_parts'=>['NB'=>$x,'TH'=>$y,'VD'=>$z],
                                        'totals'=>['NB'=>$totNB,'TH'=>$totTH,'VD'=>$totVD],
                                        'error'=>$error
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if (empty($solutions)) {
        echo 'ERROR: Không tìm được phân bố đáp ứng ràng buộc (hãy thử thay đổi số tiết hoặc cho phép TL 1..4).'; exit;
    }

    // chọn solution có error nhỏ nhất
    usort($solutions, fn($a,$b)=>$a['error'] <=> $b['error']);
    $sol = $solutions[0];

    // Build global totals (counts) from solution
    $global_counts = [
      'TNKQ' => $sol['TNKQ_parts'],
      'DS'   => $sol['DS_parts'], // these are items for DS (counts of items per level)
      'TL'   => $sol['TL_parts']
    ];
    $tl_count = $sol['tl_count'];
    $tl_per_q = $TL_points / $tl_count;

    // Convert DS items into DS question-level counts per level:
    // We have DS_total_q (2 questions), each question has 4 items. For display we keep counts as items -> when rendering table earlier we show DS as number of "ý" or show number of câu?
    // Requirement: show Đúng/Sai as number of "ý" or as "câu"? In the sample Sếp used "1ý, 2ý" -> we'll display number of ý and in final TOTAL we state 2 câu = 8 ý = 2.0đ.
    // For internal per-unit allocation we will allocate DS items (counts of ý) to units.

    // Next: distribute global counts to units according to unit weights (so_tiet) BUT ONLY to units that have that level.
    // Prepare eligible units list per level
    $eligible_units = ['NB'=>[], 'TH'=>[], 'VD'=>[]];
    foreach ($units_list as $idx => $u) {
        foreach (['NB','TH','VD'] as $lv) {
            if (!empty($u['levels'][$lv])) $eligible_units[$lv][] = $idx;
        }
    }

    // for each type-level we need to allocate integer counts across eligible units proportional to unit so_tiet
    $unit_alloc = [];
    $n_units = count($units_list);
    // init unit_alloc structure
    for ($i=0;$i<$n_units;$i++) {
        $unit_alloc[$i] = [
            'TNKQ'=>['NB'=>0,'TH'=>0,'VD'=>0],
            'DS'=>['NB'=>0,'TH'=>0,'VD'=>0], // DS is count of ý
            'TL'=>['NB'=>0,'TH'=>0,'VD'=>0]
        ];
    }

    // helper to allocate integer counts proportionally and adjust rounding
    function allocate_counts_proportional($eligible_idxs, $units_list, $total_count) {
        $res = array_fill(0, count($units_list), 0);
        if ($total_count <= 0 || count($eligible_idxs) == 0) return $res;
        $weights = [];
        $sumw = 0.0;
        foreach ($eligible_idxs as $idx) {
            $w = max(0.0001, $units_list[$idx]['so_tiet']);
            $weights[$idx] = $w; $sumw += $w;
        }
        // initial proportional
        $assigned = 0;
        $frac = [];
        foreach ($eligible_idxs as $idx) {
            $raw = ($weights[$idx] / $sumw) * $total_count;
            $floorv = floor($raw);
            $res[$idx] = (int)$floorv;
            $assigned += $res[$idx];
            $frac[$idx] = $raw - $floorv;
        }
        // assign remaining by largest fractional part
        $rem = $total_count - $assigned;
        if ($rem > 0) {
            arsort($frac);
            foreach ($frac as $idx => $val) {
                if ($rem <= 0) break;
                $res[$idx] += 1; $rem -= 1;
            }
        }
        return $res;
    }

    // allocate for TNKQ (counts are questions)
    foreach (['NB','TH','VD'] as $lv) {
        $count = $sol['TNKQ_parts'][$lv];
        $eligible = $eligible_units[$lv];
        $alloc = allocate_counts_proportional($eligible, $units_list, $count);
        foreach ($alloc as $idx=>$c) if ($c>0) $unit_alloc[$idx]['TNKQ'][$lv] = $c;
    }

    // allocate for DS items (counts of ý)
    foreach (['NB','TH','VD'] as $lv) {
        $count = $sol['DS_parts'][$lv]; // items
        $eligible = $eligible_units[$lv];
        $alloc = allocate_counts_proportional($eligible, $units_list, $count);
        foreach ($alloc as $idx=>$c) if ($c>0) $unit_alloc[$idx]['DS'][$lv] = $c;
    }

    // allocate for TL questions (tl_parts counts per level)
    foreach (['NB','TH','VD'] as $lv) {
        $count = $sol['TL_parts'][$lv];
        $eligible = $eligible_units[$lv];
        $alloc = allocate_counts_proportional($eligible, $units_list, $count);
        foreach ($alloc as $idx=>$c) if ($c>0) $unit_alloc[$idx]['TL'][$lv] = $c;
    }

    // Now build units enriched structure for rendering: include assigned counts and computed points
    for ($i=0;$i<$n_units;$i++) {
        $u = &$units_list[$i];
        // compute points contributed by assigned items:
        $u['TNKQ_q'] = $unit_alloc[$i]['TNKQ']; // array NB/TH/VD counts
        $u['DS_items'] = $unit_alloc[$i]['DS']; // items (0.25 each)
        $u['TL_q'] = $unit_alloc[$i]['TL']; // number of TL questions per level
        // compute points:
        $u['TNKQ_pts'] = ($u['TNKQ_q']['NB'] + $u['TNKQ_q']['TH'] + $u['TNKQ_q']['VD']) * 0.5;
        $u['DS_pts'] = ($u['DS_items']['NB'] + $u['DS_items']['TH'] + $u['DS_items']['VD']) * 0.25;
        $u['TL_pts'] = ($u['TL_q']['NB'] + $u['TL_q']['TH'] + $u['TL_q']['VD']) * $tl_per_q;
        $u['total_pts'] = $u['TNKQ_pts'] + $u['DS_pts'] + $u['TL_pts'];
    }

    // compute totals again for display
    $display_total_cau = ['TNKQ'=>0,'DS_items'=>0,'TL_q'=>0];
    $display_level_pts = ['NB'=>0.0,'TH'=>0.0,'VD'=>0.0];
    foreach ($units_list as $u) {
        $display_total_cau['TNKQ'] += array_sum($u['TNKQ_q']);
        $display_total_cau['DS_items'] += array_sum($u['DS_items']);
        $display_total_cau['TL_q'] += array_sum($u['TL_q']);
        $display_level_pts['NB'] += ($u['TNKQ_q']['NB']*0.5 + $u['DS_items']['NB']*0.25 + $u['TL_q']['NB']*$tl_per_q);
        $display_level_pts['TH'] += ($u['TNKQ_q']['TH']*0.5 + $u['DS_items']['TH']*0.25 + $u['TL_q']['TH']*$tl_per_q);
        $display_level_pts['VD'] += ($u['TNKQ_q']['VD']*0.5 + $u['DS_items']['VD']*0.25 + $u['TL_q']['VD']*$tl_per_q);
    }

    // Render HTML fragment as before (table)
    ob_start();
    ?>
    <div id="matran-result" class="mt-3">
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle text-center">
          <thead class="table-secondary">
            <tr>
              <th rowspan="2">Chủ đề / Đơn vị kiến thức (tiết)</th>
              <th colspan="3">TNKQ (<?= $display_total_cau['TNKQ']; ?>c = <?= number_format($TYPE_POINTS['TNKQ'],1); ?>đ)</th>
              <th colspan="3">Đúng/Sai (<?= $DS_total_q; ?>c = <?= number_format($TYPE_POINTS['DS'],1); ?>đ)</th>
              <th colspan="3">Tự luận (<?= $display_total_cau['TL_q']; ?>c = <?= number_format($TYPE_POINTS['TL'],1); ?>đ)</th>
              <th colspan="3">TỔNG mức độ (đ + số câu)</th>
              <th rowspan="2">Tỉ lệ (%)</th>
            </tr>
            <tr>
              <th>NB</th><th>TH</th><th>VD</th>
              <th>NB</th><th>TH</th><th>VD</th>
              <th>NB</th><th>TH</th><th>VD</th>
              <th class="bg-success-light">NB</th>
              <th class="bg-success-light">TH</th>
              <th class="bg-success-light">VD</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Build a mapping topic -> units for display grouping
            $topics_map = [];
            foreach ($units_list as $u) {
                $topics_map[$u['topic_title']][] = $u;
            }
            foreach ($topics_map as $topic_title => $units) {
                $topic_tiet = array_sum(array_column($units, 'so_tiet'));
                echo "<tr class='bg-primary-custom'><td colspan='15' class='text-start fw-bold'>".htmlspecialchars($topic_title)." ({$topic_tiet} tiết)</td></tr>";
                foreach ($units as $u) {
                    echo "<tr>";
                    echo "<td class='text-start ps-3 small-text'>".htmlspecialchars($u['title'])." ({$u['so_tiet']} tiết)</td>";
                    // TNKQ cells
                    foreach (['NB','TH','VD'] as $lv) {
                        $c = intval($u['TNKQ_q'][$lv] ?? 0);
                        echo $c>0 ? "<td>{$c}c</td>" : "<td></td>";
                    }
                    // DS cells (show number of ý)
                    foreach (['NB','TH','VD'] as $lv) {
                        $c = intval($u['DS_items'][$lv] ?? 0);
                        echo $c>0 ? "<td>{$c}ý</td>" : "<td></td>";
                    }
                    // TL cells (show number of TL questions)
                    foreach (['NB','TH','VD'] as $lv) {
                        $c = intval($u['TL_q'][$lv] ?? 0);
                        echo $c>0 ? "<td>{$c}c</td>" : "<td></td>";
                    }
                    // totals per unit
                    $sumNB = round(($u['TNKQ_q']['NB']*0.5 + $u['DS_items']['NB']*0.25 + $u['TL_q']['NB']*$tl_per_q),2);
                    $sumTH = round(($u['TNKQ_q']['TH']*0.5 + $u['DS_items']['TH']*0.25 + $u['TL_q']['TH']*$tl_per_q),2);
                    $sumVD = round(($u['TNKQ_q']['VD']*0.5 + $u['DS_items']['VD']*0.25 + $u['TL_q']['VD']*$tl_per_q),2);
                    $cntNB = array_sum(array_map(fn($x) => intval($x['TNKQ_q']['NB'] ?? 0), [$u]));
                    // show "đ + số câu" for each level
                    $cntNB = ($u['TNKQ_q']['NB'] ?? 0) + ($u['TL_q']['NB'] ?? 0); // TNKQ q + TL q (DS shown as ý)
                    $cntTH = ($u['TNKQ_q']['TH'] ?? 0) + ($u['TL_q']['TH'] ?? 0);
                    $cntVD = ($u['TNKQ_q']['VD'] ?? 0) + ($u['TL_q']['VD'] ?? 0);
                    // For display we include DS items separately above.
                    echo "<td class='bg-success-light'>{$sumNB}đ<br>({$cntNB}c + ".intval($u['DS_items']['NB'] ?? 0)."ý)</td>";
                    echo "<td class='bg-success-light'>{$sumTH}đ<br>({$cntTH}c + ".intval($u['DS_items']['TH'] ?? 0)."ý)</td>";
                    echo "<td class='bg-success-light'>{$sumVD}đ<br>({$cntVD}c + ".intval($u['DS_items']['VD'] ?? 0)."ý)</td>";
                    $unit_total = $sumNB + $sumTH + $sumVD;
                    echo "<td class='text-success'>".round(($unit_total / $TOTAL_POINTS) * 100, 1)."%</td>";
                    echo "</tr>";
                }
            }
            ?>
            <tr class="table-warning fw-bold">
              <td class="text-start">TỔNG CỘT</td>
              <td colspan="3">TNKQ: <?= $display_total_cau['TNKQ']; ?> câu = <?= number_format($TYPE_POINTS['TNKQ'],1); ?>đ</td>
              <td colspan="3">Đ/S: <?= $DS_total_q; ?> câu = <?= number_format($TYPE_POINTS['DS'],1); ?>đ (<?= $display_total_cau['DS_items']; ?> ý)</td>
              <td colspan="3">Tự luận: <?= $display_total_cau['TL_q']; ?> câu = <?= number_format($TYPE_POINTS['TL'],1); ?>đ</td>
              <td class="bg-info">NB: <?= round($display_level_pts['NB'],1); ?>đ</td>
              <td class="bg-info">TH: <?= round($display_level_pts['TH'],1); ?>đ</td>
              <td class="bg-info">VD: <?= round($display_level_pts['VD'],1); ?>đ</td>
              <td class="text-danger">100%</td>
            </tr>
            <tr class="table-secondary small-text fw-bold">
              <td class="text-start">Ghi chú</td>
              <td colspan="14">
                ✅ TNKQ cố định: 8 câu (4.0đ). &nbsp; ✅ Đúng/Sai cố định: 2 câu (8 ý = 2.0đ), đảm bảo đủ 3 mức NB/TH/VD.<br>
                ✅ Tự luận tổng = 4.0đ, chia thành <?= $tl_count ?> câu (mỗi câu = <?= round($tl_per_q,2) ?>đ).<br>
                ✅ Tổng mức độ: NB = <?= round($display_level_pts['NB'],1) ?>đ, TH = <?= round($display_level_pts['TH'],1) ?>đ, VD = <?= round($display_level_pts['VD'],1) ?>đ (VD = 30% bắt buộc).<br>
                (Nếu Sếp muốn thay thuật toán phân bổ xuống đơn vị khác — ví dụ ưu tiên phân bổ theo số câu/chủ đề thay vì số tiết — em chỉnh dễ).
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

// Nếu không POST generate -> hiển thị trang form (giống bản trước)
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ma trận Pro - Strict Rules</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { padding:20px; background:linear-gradient(135deg,#00b4db 0%,#0083b0 100%); min-height:100vh; }
  .card { border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.25); }
  .fade-me { transition: opacity .35s ease, transform .35s ease; }
  .hidden { opacity:0; transform: translateY(-12px); pointer-events:none; height:0; overflow:hidden; }
  .small-text { font-size: 12px; }
  .bg-primary-custom { background:linear-gradient(135deg,#00b4db 0%,#0083b0 100%) !important; color:white !important; }
  .bg-success-light { background-color:#d4edda !important; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-xl-10">
      <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">🎯 Tạo Ma trận — Tin học THCS (Phiên bản nghiêm ngặt)</h4>
          <small class="text-muted">Yêu cầu: TNKQ 8c, Đ/S 2c (8ý), TL 4.0đ; VD = 30% bắt buộc; NB/TH 30–40%.</small>
        </div>

        <div id="form-area" class="fade-me">
          <div class="mb-3">
            <label class="form-label">Tên bộ đề / ghi chú (tùy chọn)</label>
            <input id="exam-title" class="form-control" placeholder="Ví dụ: Ma trận Tin 8 - GK">
          </div>

          <div id="topics-container"></div>

          <div class="d-flex gap-2">
            <button id="add-topic" class="btn btn-outline-light btn-sm">+ Thêm chủ đề</button>
            <button id="generate" class="btn btn-primary btn-sm ms-auto">Tạo ma trận</button>
            <button id="reset-form" class="btn btn-secondary btn-sm">Reset</button>
          </div>
          <div class="mt-2 small text-white-50">Hướng dẫn: mỗi đơn vị nhập số tiết và tick NB/TH/VD. Hệ thống tự động chia câu theo quy tắc nghiêm ngặt.</div>
        </div>

        <div id="result-area" class="mt-4 hidden fade-me">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Kết quả Ma trận</h5>
            <div>
              <button id="back-edit" class="btn btn-outline-secondary btn-sm">Sửa / Quay lại</button>
              <button id="print-btn" class="btn btn-success btn-sm">In / Xuất PDF</button>
            </div>
          </div>
          <div id="matran-output"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- templates same as earlier -->
<template id="topic-template">
  <div class="topic card p-3 mb-3">
    <div class="d-flex align-items-start gap-2 mb-2">
      <div class="flex-grow-1">
        <input class="form-control topic-title" placeholder="Nhập tiêu đề chủ đề (ví dụ: A. Máy tính và cộng đồng)">
      </div>
      <button class="btn btn-danger btn-sm remove-topic">Xóa chủ đề</button>
    </div>
    <div class="units mb-2"></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm add-unit">+ Thêm đơn vị</button>
    </div>
  </div>
</template>

<template id="unit-template">
  <div class="unit border rounded p-2 mb-2">
    <div class="d-flex gap-2 align-items-center mb-2">
      <input class="form-control unit-title" placeholder="Nhập tên đơn vị kiến thức" />
      <input type="number" class="form-control unit-tiet" min="0" value="1" style="width:100px;" title="Số tiết" />
      <button class="btn btn-outline-danger btn-sm remove-unit">Xóa</button>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-nb" type="checkbox" checked>
      <label class="form-check-label small-text">NB</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-th" type="checkbox" checked>
      <label class="form-check-label small-text">TH</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-vd" type="checkbox" checked>
      <label class="form-check-label small-text">VD</label>
    </div>
  </div>
</template>

<script>
(function(){
  const topicsContainer = document.getElementById('topics-container');
  const topicTpl = document.getElementById('topic-template');
  const unitTpl = document.getElementById('unit-template');

  function addTopic(title = '') {
    const node = topicTpl.content.cloneNode(true);
    const topicEl = node.querySelector('.topic');
    const titleInput = topicEl.querySelector('.topic-title');
    titleInput.value = title;
    const unitsWrap = topicEl.querySelector('.units');

    function addUnit(unitTitle = '', tiet = 1) {
      const uNode = unitTpl.content.cloneNode(true);
      const uEl = uNode.querySelector('.unit');
      uEl.querySelector('.unit-title').value = unitTitle;
      uEl.querySelector('.unit-tiet').value = tiet;
      unitsWrap.appendChild(uEl);
      uEl.querySelector('.remove-unit').addEventListener('click', () => uEl.remove());
    }

    topicEl.querySelector('.add-unit').addEventListener('click', () => addUnit());
    topicEl.querySelector('.remove-topic').addEventListener('click', () => topicEl.remove());
    addUnit();
    topicsContainer.appendChild(topicEl);
    return topicEl;
  }

  addTopic('A. MÁY TÍNH VÀ CỘNG ĐỒNG');

  document.getElementById('add-topic').addEventListener('click', () => addTopic());
  document.getElementById('reset-form').addEventListener('click', () => { topicsContainer.innerHTML=''; addTopic(); });

  document.getElementById('generate').addEventListener('click', async () => {
    const topicsEls = [...topicsContainer.querySelectorAll('.topic')];
    const topics = [];
    for (const tEl of topicsEls) {
      const title = tEl.querySelector('.topic-title').value.trim();
      const unitEls = [...tEl.querySelectorAll('.unit')];
      const units = [];
      for (const uEl of unitEls) {
        const utitle = uEl.querySelector('.unit-title').value.trim();
        const tiet = parseFloat(uEl.querySelector('.unit-tiet').value) || 0;
        const nb = uEl.querySelector('.level-nb').checked;
        const th = uEl.querySelector('.level-th').checked;
        const vd = uEl.querySelector('.level-vd').checked;
        units.push({ title: utitle || '(Đơn vị chưa đặt tên)', so_tiet: tiet, levels: { NB: nb, TH: th, VD: vd }});
      }
      topics.push({ title: title || '(Chưa đặt tên chủ đề)', units });
    }

    let totalTiet = 0;
    topics.forEach(t=> t.units.forEach(u => totalTiet += Number(u.so_tiet)));
    if (totalTiet <= 0) { alert('Tổng số tiết phải lớn hơn 0'); return; }

    const form = new FormData();
    form.append('action','generate');
    form.append('data', JSON.stringify({ topics }));

    const btn = document.getElementById('generate');
    btn.disabled = true; btn.innerText = 'Đang tạo...';
    try {
      const resp = await fetch('', { method:'POST', body: form });
      const html = await resp.text();
      document.getElementById('matran-output').innerHTML = html;
      document.getElementById('form-area').classList.add('hidden');
      setTimeout(()=> document.getElementById('result-area').classList.remove('hidden'), 420);
    } catch (e) {
      alert('Lỗi: '+ e.message);
    } finally { btn.disabled=false; btn.innerText='Tạo ma trận'; }
  });

  document.getElementById('back-edit').addEventListener('click', () => {
    document.getElementById('result-area').classList.add('hidden');
    setTimeout(()=> document.getElementById('form-area').classList.remove('hidden'), 200);
  });

  document.getElementById('print-btn').addEventListener('click', ()=> window.print());
})();
</script>
</body>
</html>
