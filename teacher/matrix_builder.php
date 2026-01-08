<?php
session_start();
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// ============= AJAX POST HANDLER - Generate Matrix =============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    header('Content-Type: text/html; charset=utf-8');
    $raw = $_POST['data'] ?? '';
    $input = json_decode($raw, true);
    if (!$input || !isset($input['topics'])) {
        http_response_code(400);
        echo "<div class='alert alert-danger'>Dữ liệu không hợp lệ.</div>";
        exit;
    }

    // ------------------------
    // Core algorithm v4.5 (server-side) - IMPROVED TL rounding
    // ------------------------
    $topics = $input['topics'];
    $TOTAL_POINTS = 10.0;
    $TYPE_POINTS = ['TNKQ'=>4.0,'DS'=>2.0,'TL'=>4.0];
    $TNKQ_Q = 8;
    $POINT_PER_TNKQ = 0.5;
    $POINT_PER_DS_ITEM = 0.25;
    $TOTAL_DS_ITEMS = 8; // 2c * 4 ý
    $TARGET = ['NB'=>0.35,'TH'=>0.35,'VD'=>0.30];
    function fnum($n){ return number_format($n,2,'.',''); }
    function round05($n){ return round($n*2)/2; }

    // collect units flat list
    $units = [];
    foreach ($topics as $ti => $t) {
        $topic_title = trim($t['title'] ?? "Chủ đề ".($ti+1));
        foreach (($t['units'] ?? []) as $ui => $u) {
            $units[] = [
                'topic' => $topic_title,
                'title' => trim($u['title'] ?? "Đơn vị ".($ui+1)),
                'so_tiet' => max(0, floatval($u['so_tiet'] ?? 0)),
                'levels' => ($u['levels'] ?? ['NB'=>true,'TH'=>true,'VD'=>true]),
                'idx' => count($units)
            ];
        }
    }
    if (empty($units)) {
        echo "<div class='alert alert-warning'>Chưa có đơn vị nào. Vui lòng nhập dữ liệu.</div>";
        exit;
    }
    $total_tiet = array_sum(array_column($units,'so_tiet'));
    if ($total_tiet <= 0) {
        echo "<div class='alert alert-warning'>Tổng số tiết phải > 0.</div>";
        exit;
    }

    // TNKQ allocation (floor then top-up by so_tiet)
    foreach ($units as $i => $u) {
        $tile = $u['so_tiet'] / $total_tiet;
        $units[$i]['tile'] = $tile;
        $raw = $TNKQ_Q * $tile;
        $units[$i]['tnkq_q'] = (int) floor($raw);
        $units[$i]['tnkq_raw'] = $raw;
    }
    $sum_floor = array_sum(array_column($units,'tnkq_q'));
    $need = $TNKQ_Q - $sum_floor;
    if ($need > 0) {
        usort($units, function($a,$b){
            if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx'];
            return $b['so_tiet'] <=> $a['so_tiet'];
        });
        for ($i=0;$i<$need;$i++) {
            $units[$i % count($units)]['tnkq_q']++;
        }
        usort($units, function($a,$b){ return $a['idx'] <=> $b['idx']; });
    }
    foreach ($units as $i => $u) $units[$i]['tnkq_pts'] = $units[$i]['tnkq_q'] * $POINT_PER_TNKQ;

    // DS: assign Câu1 & Câu2 to two units with largest so_tiet
    usort($units, function($a,$b){ if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; return $b['so_tiet'] <=> $a['so_tiet']; });
    $ds_patterns = [
        ['label'=>'Câu 1','desc'=>'1NB + 1TH + 2VD','pts'=>1.0,'items'=>4],
        ['label'=>'Câu 2','desc'=>'2NB + 1TH + 1VD','pts'=>1.0,'items'=>4]
    ];
    for ($i=0;$i<count($units);$i++) {
        if ($i<2) {
            $units[$i]['has_ds'] = true;
            $units[$i]['ds_label'] = $ds_patterns[$i]['label'];
            $units[$i]['ds_desc'] = $ds_patterns[$i]['desc'];
            $units[$i]['ds_pts'] = $ds_patterns[$i]['pts'];
            $units[$i]['ds_items'] = $ds_patterns[$i]['items'];
        } else {
            $units[$i]['has_ds'] = false;
            $units[$i]['ds_label'] = '';
            $units[$i]['ds_desc'] = '';
            $units[$i]['ds_pts'] = 0.0;
            $units[$i]['ds_items'] = 0;
        }
    }
    usort($units, function($a,$b){ return $a['idx'] <=> $b['idx']; });

    // TL: initial allocation by tile, then round05, then "soft-adjust" rules
    foreach ($units as $i => $u) {
        $units[$i]['tl_pts'] = ($TOTAL_POINTS * $units[$i]['tile']) - ($units[$i]['tnkq_pts'] + $units[$i]['ds_pts']);
        if ($units[$i]['tl_pts'] < 0) $units[$i]['tl_pts'] = 0;
        $units[$i]['tl_pts'] = round05($units[$i]['tl_pts']);
    }
    // Soft adjustments
    foreach ($units as $i => $u) {
        if ($units[$i]['tl_pts'] < 1.0 && $units[$i]['so_tiet'] >= 2) $units[$i]['tl_pts'] = 1.0;
        if ($units[$i]['tl_pts'] > 2.0 && $units[$i]['so_tiet'] <= 2) $units[$i]['tl_pts'] = 1.5;
    }
    // Ensure total TL = 4.0
    $sum_tl = array_sum(array_column($units,'tl_pts'));
    while (abs($sum_tl - $TYPE_POINTS['TL']) > 0.001) {
        if ($sum_tl < $TYPE_POINTS['TL']) {
            usort($units, function($a,$b){ if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; return $b['so_tiet'] <=> $a['so_tiet'];});
            $units[0]['tl_pts'] += 0.5;
        } else {
            usort($units, function($a,$b){ if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; return $a['so_tiet'] <=> $b['so_tiet'];});
            $found = false;
            for ($i=0;$i<count($units);$i++){
                if ($units[$i]['tl_pts'] >= 0.5) {
                    $units[$i]['tl_pts'] -= 0.5; $found = true; break;
                }
            }
            if (!$found) break;
        }
        $sum_tl = array_sum(array_column($units,'tl_pts'));
    }
    usort($units, function($a,$b){ return $a['idx'] <=> $b['idx']; });

    // NEW: TL NB/TH/VD breakdown - ENSURE ROUNDED TO 0.5
    foreach ($units as $i => $u) {
        $tl = $units[$i]['tl_pts'];
        if ($tl <= 0) {
            $units[$i]['tl_nb'] = $units[$i]['tl_th'] = $units[$i]['tl_vd'] = 0;
            continue;
        }
        
        // Get available levels
        $avail = [];
        foreach (['NB','TH','VD'] as $lv) if (!empty($units[$i]['levels'][$lv])) $avail[] = $lv;
        if (empty($avail)) $avail = ['NB','TH','VD'];
        
        // Calculate target shares
        $sumTar = 0;
        foreach ($avail as $lv) $sumTar += $TARGET[$lv];
        if ($sumTar <= 0) $sumTar = 1;
        
        // Allocate and round each level
        $tl_nb = $tl_th = $tl_vd = 0;
        foreach (['NB','TH','VD'] as $lv) {
            if (in_array($lv, $avail)) {
                $raw = $TARGET[$lv] / $sumTar * $tl;
                ${"tl_".strtolower($lv)} = round05($raw);
            }
        }
        
        // Adjust to match total (might be off due to rounding)
        $sum_rounded = $tl_nb + $tl_th + $tl_vd;
        $diff = $tl - $sum_rounded;
        
        // Distribute difference in 0.5 increments
        while (abs($diff) >= 0.25) {
            if ($diff > 0) {
                // Need to add 0.5 to largest available
                if (in_array('NB', $avail) && $tl_nb >= max($tl_th, $tl_vd)) $tl_nb += 0.5;
                elseif (in_array('TH', $avail) && $tl_th >= max($tl_nb, $tl_vd)) $tl_th += 0.5;
                elseif (in_array('VD', $avail)) $tl_vd += 0.5;
                else $tl_nb += 0.5;
                $diff -= 0.5;
            } else {
                // Need to subtract 0.5 from smallest available (if >= 0.5)
                if (in_array('VD', $avail) && $tl_vd >= 0.5 && $tl_vd <= min($tl_nb, $tl_th)) $tl_vd -= 0.5;
                elseif (in_array('TH', $avail) && $tl_th >= 0.5 && $tl_th <= min($tl_nb, $tl_vd)) $tl_th -= 0.5;
                elseif (in_array('NB', $avail) && $tl_nb >= 0.5) $tl_nb -= 0.5;
                else break;
                $diff += 0.5;
            }
        }
        
        $units[$i]['tl_nb'] = $tl_nb;
        $units[$i]['tl_th'] = $tl_th;
        $units[$i]['tl_vd'] = $tl_vd;
    }

    // Level NB/TH/VD distribution per unit based on availability and TARGET ratio
    foreach ($units as $i => $u) {
        $unit_total = $units[$i]['tnkq_pts'] + $units[$i]['ds_pts'] + $units[$i]['tl_pts'];
        $available = [];
        foreach (['NB','TH','VD'] as $lv) if (!empty($units[$i]['levels'][$lv])) $available[] = $lv;
        if (empty($available)) $available = ['NB','TH','VD'];
        $sumTarget = 0.0;
        foreach ($available as $lv) $sumTarget += $TARGET[$lv];
        foreach (['NB','TH','VD'] as $lv) {
            if (in_array($lv, $available)) {
                $units[$i]['lvl'][$lv] = $TARGET[$lv] / $sumTarget * $unit_total;
            } else {
                $units[$i]['lvl'][$lv] = 0.0;
            }
        }
    }

    // Totals for summary
    $tot_tnkq_q = array_sum(array_column($units,'tnkq_q'));
    $tot_tnkq_pts = array_sum(array_column($units,'tnkq_pts'));
    $tot_ds_items = array_sum(array_column($units,'ds_items'));
    $tot_ds_pts = array_sum(array_column($units,'ds_pts'));
    $tot_tl_pts = array_sum(array_column($units,'tl_pts'));
    $tot_nb = $tot_th = $tot_vd = 0.0;
    foreach ($units as $u) {
        $tot_nb += $u['lvl']['NB'];
        $tot_th += $u['lvl']['TH'];
        $tot_vd += $u['lvl']['VD'];
    }
    $tot_all = $tot_nb + $tot_th + $tot_vd;
    if ($tot_all <= 0) $tot_all = 1e-9;

    // ---------- Build HTML Table ----------
    ob_start();
    ?>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle text-center" id="matran-table">
        <thead class="table-secondary">
          <tr>
            <th rowspan="3">Chủ đề / Đơn vị (tiết)</th>
            <th colspan="3">TNKQ (<?= $tot_tnkq_q ?>c = <?= fnum($tot_tnkq_pts) ?>đ)</th>
            <th colspan="3">Đúng/Sai (2c = 8ý = <?= fnum($tot_ds_pts) ?>đ)</th>
            <th colspan="3">Tự luận (≈ <?= fnum($tot_tl_pts) ?>đ)</th>
            <th colspan="3">TỔNG mức độ (đ + số câu/ý)</th>
            <th rowspan="3">Tỉ lệ %</th>
          </tr>
          <tr>
            <th>NB</th><th>TH</th><th>VD</th>
            <th>NB</th><th>TH</th><th>VD</th>
            <th>NB</th><th>TH</th><th>VD</th>
            <th class="bg-success-light">NB</th><th class="bg-success-light">TH</th><th class="bg-success-light">VD</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // render grouped by topic
        $grouped = [];
        foreach ($units as $u) $grouped[$u['topic']][] = $u;
        foreach ($grouped as $topic => $arr) {
            echo "<tr class='table-primary'><td colspan='15' class='text-start fw-bold'>{$topic} (" . array_sum(array_column($arr,'so_tiet')) . " tiết)</td></tr>";
            foreach ($arr as $u) {
                echo "<tr>";
                echo "<td class='text-start ps-3'>{$u['title']} ({$u['so_tiet']} tiết)</td>";
                
                // TNKQ distribution
                $tnkq_c = intval($u['tnkq_q']);
                $tn_nb = $tn_th = $tn_vd = 0;
                if ($tnkq_c==1) {
                    if (!empty($u['levels']['NB'])) $tn_nb=1;
                    elseif (!empty($u['levels']['TH'])) $tn_th=1;
                    else $tn_vd=1;
                } elseif ($tnkq_c>=2) {
                    $alloc = $tnkq_c;
                    foreach (['NB','TH','VD'] as $lv) {
                        if ($alloc<=0) break;
                        if (!empty($u['levels'][$lv])) {
                            $give = min(1,$alloc);
                            if ($lv=='NB') $tn_nb += $give;
                            if ($lv=='TH') $tn_th += $give;
                            if ($lv=='VD') $tn_vd += $give;
                            $alloc -= $give;
                        }
                    }
                    if ($alloc>0) $tn_nb += $alloc;
                }
                echo $tn_nb>0 ? "<td>{$tn_nb}c</td>" : "<td></td>";
                echo $tn_th>0 ? "<td>{$tn_th}c</td>" : "<td></td>";
                echo $tn_vd>0 ? "<td>{$tn_vd}c</td>" : "<td></td>";
                
                // DS
                $ds_nb = $ds_th = $ds_vd = '';
                if (!empty($u['has_ds'])) {
                    if ($u['ds_label'] === 'Câu 1') { $ds_nb='1ý'; $ds_th='1ý'; $ds_vd='2ý'; }
                    else { $ds_nb='2ý'; $ds_th='1ý'; $ds_vd='1ý'; }
                }
                echo $ds_nb!=='' ? "<td>{$ds_nb}</td>" : "<td></td>";
                echo $ds_th!=='' ? "<td>{$ds_th}</td>" : "<td></td>";
                echo $ds_vd!=='' ? "<td>{$ds_vd}</td>" : "<td></td>";
                
                // TL - NOW USING PRE-ROUNDED VALUES
                $tl_nb = $u['tl_nb'] ?? 0;
                $tl_th = $u['tl_th'] ?? 0;
                $tl_vd = $u['tl_vd'] ?? 0;
                echo $tl_nb>0 ? "<td>".fnum($tl_nb)."đ</td>" : "<td></td>";
                echo $tl_th>0 ? "<td>".fnum($tl_th)."đ</td>" : "<td></td>";
                echo $tl_vd>0 ? "<td>".fnum($tl_vd)."đ</td>" : "<td></td>";
                
                // Total per level
                $sumNB = fnum($u['lvl']['NB']);
                $sumTH = fnum($u['lvl']['TH']);
                $sumVD = fnum($u['lvl']['VD']);
                echo "<td class='bg-success-light'>{$sumNB}đ</td>";
                echo "<td class='bg-success-light'>{$sumTH}đ</td>";
                echo "<td class='bg-success-light'>{$sumVD}đ</td>";
                
                // Percent
                $pct = ($u['tnkq_pts']+$u['ds_pts']+$u['tl_pts']) / $TOTAL_POINTS * 100;
                echo "<td class='text-success'>".round($pct,1)."%</td>";
                echo "</tr>";
            }
        }
        ?>
          <tr class="table-warning fw-bold">
            <td class="text-start">TỔNG CỘT</td>
            <td colspan="3">TNKQ: <?= intval($tot_tnkq_q) ?> câu = <?= fnum($tot_tnkq_pts) ?>đ</td>
            <td colspan="3">Đ/S: 2 câu = <?= fnum($tot_ds_pts) ?>đ (<?= intval($tot_ds_items) ?> ý)</td>
            <td colspan="3">Tự luận: <?= fnum($tot_tl_pts) ?>đ</td>
            <td class="bg-info">NB: <?= fnum($tot_nb) ?>đ</td>
            <td class="bg-info">TH: <?= fnum($tot_th) ?>đ</td>
            <td class="bg-info">VD: <?= fnum($tot_vd) ?>đ</td>
            <td class="text-danger">100%</td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

// ============= END AJAX HANDLER =============

$title = 'Xây Dựng Ma Trận Đề Kiểm Tra - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
    <div class="container my-5">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">🎯 Xây Dựng Ma trận — Tin học THCS</h4>
                <small class="text-muted">TNKQ 8c (4đ) • Đ/S 2c (8ý=2đ) • TL 4đ (làm tròn 0.5đ)</small>
            </div>

            <div id="form-area">
                <div class="mb-3">
                    <label class="form-label">Tên bộ đề (tùy chọn)</label>
                    <input id="exam-title" class="form-control" placeholder="Ma trận Tin 8 - Giữa kỳ">
                </div>

                <div id="topics-container"></div>

                <div class="d-flex gap-2 mt-3">
                    <button id="add-topic" class="btn btn-outline-primary btn-sm">+ Thêm chủ đề</button>
                    <button id="generate" class="btn btn-primary ms-auto">Tạo ma trận</button>
                    <button id="reset-form" class="btn btn-secondary">Reset</button>
                </div>
                <div class="mt-2 small text-muted">Thêm chủ đề → thêm đơn vị trong chủ đề → nhập số tiết, tick mức độ NB/TH/VD.</div>
            </div>

            <div id="result-area" class="hidden mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Kết quả Ma trận</h5>
                    <div>
                        <button id="back-edit" class="btn btn-outline-secondary btn-sm">Sửa / Quay lại</button>
                        <button id="print-btn" class="btn btn-success btn-sm">In</button>
                    </div>
                </div>
                <div id="matran-output"></div>
            </div>
        </div>
    </div>
</div>

<!-- templates -->
<template id="topic-tpl">
  <div class="topic border rounded p-3 mb-2 bg-white">
    <div class="d-flex gap-2 mb-2">
      <input class="form-control topic-title" placeholder="Tiêu đề chủ đề (ví dụ: A. Máy tính và cộng đồng)">
      <button class="btn btn-danger btn-sm remove-topic">Xóa chủ đề</button>
    </div>
    <div class="units mb-2"></div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm add-unit">+ Thêm đơn vị</button>
    </div>
  </div>
</template>

<template id="unit-tpl">
  <div class="unit border rounded p-2 mb-2 bg-light">
    <div class="d-flex gap-2 mb-2">
      <input class="form-control unit-title" placeholder="Tên đơn vị (ví dụ: 1. Sơ lược về...)">
      <input type="number" class="form-control unit-tiet" min="0" value="1" style="width:110px" title="Số tiết">
      <button class="btn btn-outline-danger btn-sm remove-unit">Xóa</button>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-nb" type="checkbox" checked>
      <label class="form-check-label small">NB</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-th" type="checkbox" checked>
      <label class="form-check-label small">TH</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-vd" type="checkbox" checked>
      <label class="form-check-label small">VD</label>
    </div>
  </div>
</template>

<style>
.hidden{display:none}
.table thead{background:#6c9bd1;color:#fff}
.bg-success-light{background:#e8f5e9}
.table-primary td{background:linear-gradient(90deg,#00b4db33,#0083b033)}
</style>

<script>
(function(){
  const topicsContainer = document.getElementById('topics-container');
  const topicTpl = document.getElementById('topic-tpl');
  const unitTpl = document.getElementById('unit-tpl');

  function addTopic(title=''){
    const node = topicTpl.content.cloneNode(true);
    const el = node.querySelector('.topic');
    el.querySelector('.topic-title').value = title;
    const unitsWrap = el.querySelector('.units');
    el.querySelector('.add-unit').addEventListener('click', ()=> addUnit(unitsWrap));
    el.querySelector('.remove-topic').addEventListener('click', ()=> el.remove());
    addUnit(unitsWrap);
    topicsContainer.appendChild(el);
    return el;
  }
  
  function addUnit(unitsWrap, uTitle='', tiet=1){
    const node = unitTpl.content.cloneNode(true);
    const uel = node.querySelector('.unit');
    uel.querySelector('.unit-title').value = uTitle;
    uel.querySelector('.unit-tiet').value = tiet;
    uel.querySelector('.remove-unit').addEventListener('click', ()=> uel.remove());
    unitsWrap.appendChild(uel);
    return uel;
  }

  // Initial sample data
  const tA = addTopic('A. Máy tính và cộng đồng');
  const unitsA = tA.querySelector('.units');
  unitsA.innerHTML='';
  addUnit(unitsA, '1. Sơ lược về các thành phần của máy tính', 2);
  addUnit(unitsA, '2. Khái niệm hệ điều hành và phần mềm ứng dụng', 3);

  const tB = addTopic('B. Tổ chức lưu trữ, tìm kiếm và trao đổi thông tin');
  const unitsB = tB.querySelector('.units');
  unitsB.innerHTML='';
  addUnit(unitsB, 'Mạng xã hội và một số kênh trao đổi thông tin thông dụng trên Internet', 2);

  document.getElementById('add-topic').addEventListener('click', ()=> addTopic());

  function collectData(){
    const topicsEls = [...topicsContainer.querySelectorAll('.topic')];
    const topics = [];
    for (const tEl of topicsEls) {
      const title = tEl.querySelector('.topic-title').value.trim() || '(Chưa đặt tên chủ đề)';
      const unitEls = [...tEl.querySelectorAll('.unit')];
      const units = [];
      for (const uEl of unitEls) {
        const ut = uEl.querySelector('.unit-title').value.trim() || '(Đơn vị chưa đặt tên)';
        const tiet = parseFloat(uEl.querySelector('.unit-tiet').value) || 0;
        const nb = uEl.querySelector('.level-nb').checked;
        const th = uEl.querySelector('.level-th').checked;
        const vd = uEl.querySelector('.level-vd').checked;
        units.push({ title: ut, so_tiet: tiet, levels: { NB: nb, TH: th, VD: vd }});
      }
      topics.push({ title, units });
    }
    return { topics };
  }

  async function postData(fd){
    const res = await fetch('',{ method:'POST', body: fd });
    return await res.text();
  }

  document.getElementById('generate').addEventListener('click', async ()=> {
    const data = collectData();
    let totalTiet = 0;
    data.topics.forEach(t=> t.units.forEach(u=> totalTiet += Number(u.so_tiet)));
    if (totalTiet <= 0) return alert('Tổng số tiết phải lớn hơn 0.');

    const fd = new FormData();
    fd.append('action','generate');
    fd.append('data', JSON.stringify(data));

    const btn = document.getElementById('generate');
    btn.disabled = true; btn.innerText = 'Đang tạo...';
    try {
      const html = await postData(fd);
      document.getElementById('matran-output').innerHTML = html;
      document.getElementById('form-area').classList.add('hidden');
      document.getElementById('result-area').classList.remove('hidden');
    } catch (e) {
      alert('Lỗi: ' + e.message);
    } finally {
      btn.disabled = false; btn.innerText = 'Tạo ma trận';
    }
  });

  document.getElementById('back-edit').addEventListener('click', ()=>{
    document.getElementById('result-area').classList.add('hidden');
    document.getElementById('form-area').classList.remove('hidden');
  });

  document.getElementById('print-btn').addEventListener('click', ()=> window.print());

  document.getElementById('reset-form').addEventListener('click', ()=>{
    topicsContainer.innerHTML=''; 
    addTopic();
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
