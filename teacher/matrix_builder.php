<?php
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
    // VD fixed at 30%, NB and TH compensate each other (30-40% each)
    $TARGET = ['NB'=>0.40,'TH'=>0.30,'VD'=>0.30];
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

    // Dynamic NB/TH adjustment: balance between 30-40%, VD stays 30%
    // Count units with strong NB vs TH content
    $nb_strong = $th_strong = 0;
    foreach ($units as $u) {
        if (!empty($u['levels']['NB']) && empty($u['levels']['TH'])) $nb_strong++;
        if (!empty($u['levels']['TH']) && empty($u['levels']['NB'])) $th_strong++;
    }
    // Adjust TARGET based on content availability
    if ($nb_strong > $th_strong) {
        $TARGET['NB'] = 0.40;
        $TARGET['TH'] = 0.30;
    } elseif ($th_strong > $nb_strong) {
        $TARGET['NB'] = 0.30;
        $TARGET['TH'] = 0.40;
    } else {
        // Equal or mixed - use 35/35
        $TARGET['NB'] = 0.35;
        $TARGET['TH'] = 0.35;
    }
    $TARGET['VD'] = 0.30; // Always fixed

    // TL: initial allocation by tile, then adjust to valid ranges
    foreach ($units as $i => $u) {
        $units[$i]['tl_pts'] = ($TOTAL_POINTS * $units[$i]['tile']) - ($units[$i]['tnkq_pts'] + $units[$i]['ds_pts']);
        if ($units[$i]['tl_pts'] < 0) $units[$i]['tl_pts'] = 0;
        $units[$i]['tl_pts'] = round05($units[$i]['tl_pts']);
    }
    // Soft adjustments: enforce 0.5-1.5đ per question, max one 0.5đ question
    $count_half = 0;
    foreach ($units as $i => $u) {
        $tl = $units[$i]['tl_pts'];
        if ($tl < 0.5) {
            $units[$i]['tl_pts'] = 0.5;
        } elseif ($tl > 1.5) {
            $units[$i]['tl_pts'] = 1.5;
        }
        // Round to valid increments: 0.5, 0.75, 1.0, 1.25, 1.5
        $tl = $units[$i]['tl_pts'];
        $valid = [0, 0.5, 0.75, 1.0, 1.25, 1.5];
        $closest = $valid[0];
        $minDiff = abs($tl - $closest);
        foreach ($valid as $v) {
            $diff = abs($tl - $v);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $v;
            }
        }
        $units[$i]['tl_pts'] = $closest;
        if ($closest == 0.5) $count_half++;
    }
    // Ensure max 1 question with 0.5đ
    if ($count_half > 1) {
        $sorted = $units;
        usort($sorted, function($a,$b) { return $a['tl_pts'] <=> $b['tl_pts']; });
        $upgraded = 0;
        foreach ($sorted as $s) {
            if ($s['tl_pts'] == 0.5 && $upgraded < $count_half - 1) {
                for ($i=0; $i<count($units); $i++) {
                    if ($units[$i]['idx'] == $s['idx']) {
                        $units[$i]['tl_pts'] = 0.75;
                        $upgraded++;
                        break;
                    }
                }
            }
        }
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

    // NEW: TL NB/TH/VD breakdown - FOCUSED allocation (1-2 levels per unit, not spread)
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
        
        // FOCUSED STRATEGY: Assign to 1-2 levels, not spread across all 3
        $tl_nb = $tl_th = $tl_vd = 0;
        
        // Priority order based on TARGET (which level needs more coverage)
        $priority = [];
        foreach ($avail as $lv) $priority[$lv] = $TARGET[$lv];
        arsort($priority);
        $levels_sorted = array_keys($priority);
        
        // Assign to top 1-2 priority levels
        if (count($levels_sorted) >= 1) {
            $primary = $levels_sorted[0];
            
            if ($tl <= 0.75) {
                // Small amount: assign to 1 level only
                ${"tl_".strtolower($primary)} = $tl;
            } else {
                // Larger amount: split between top 2 levels
                $secondary = $levels_sorted[1] ?? $primary;
                
                // Primary gets 60-70%, secondary gets rest
                $primary_share = ceil($tl / 0.5) * 0.5 * 0.6;
                $primary_share = round($primary_share / 0.25) * 0.25; // Round to 0.25
                $secondary_share = $tl - $primary_share;
                
                if ($primary_share < 0.5) $primary_share = 0.5;
                if ($secondary_share < 0) $secondary_share = 0;
                if ($secondary_share > 0 && $secondary_share < 0.5) {
                    $primary_share += $secondary_share;
                    $secondary_share = 0;
                }
                
                ${"tl_".strtolower($primary)} = $primary_share;
                if ($secondary_share > 0) {
                    ${"tl_".strtolower($secondary)} = $secondary_share;
                }
            }
        }
        
        $units[$i]['tl_nb'] = $tl_nb;
        $units[$i]['tl_th'] = $tl_th;
        $units[$i]['tl_vd'] = $tl_vd;
    }
    
    // Calculate FIXED VD from DS (always 0.75 points from 3 VD items total)
    $total_ds_vd = 0.0;
    foreach ($units as $u) {
        if (!empty($u['has_ds'])) {
            if ($u['ds_label'] === 'Câu 1') {
                $total_ds_vd += 2 * $POINT_PER_DS_ITEM; // 0.5
            } else {
                $total_ds_vd += 1 * $POINT_PER_DS_ITEM; // 0.25
            }
        }
    }
    
    // Target: Total VD = 30% = 3.0 points
    $target_total_vd = $TOTAL_POINTS * 0.30;
    
    // Calculate VD needed from TNKQ + TL combined
    // Strategy: Minimize VD in TNKQ (favor NB/TH), maximize room for TL to adjust
    
    // For now, allocate TNKQ primarily to NB/TH, minimal to VD
    // Then calculate needed TL VD to reach target
    
    $total_tnkq_vd_allocated = 0.0;
    $tnkq_allocations = [];
    
    foreach ($units as $i => $u) {
        $tnkq_c = intval($u['tnkq_q']);
        $alloc = ['NB'=>0, 'TH'=>0, 'VD'=>0];
        
        if ($tnkq_c > 0) {
            $available = [];
            foreach (['NB','TH','VD'] as $lv) if (!empty($u['levels'][$lv])) $available[] = $lv;
            if (empty($available)) $available = ['NB','TH','VD'];
            
            // Distribute: Prefer NB, then TH, then VD
            $remaining = $tnkq_c;
            foreach (['NB','TH','VD'] as $lv) {
                if ($remaining > 0 && in_array($lv, $available)) {
                    $give = min(1, $remaining);
                    $alloc[$lv] = $give * $POINT_PER_TNKQ;
                    $remaining -= $give;
                }
            }
            // Any extra goes to NB
            if ($remaining > 0) {
                $alloc['NB'] += $remaining * $POINT_PER_TNKQ;
            }
            
            $total_tnkq_vd_allocated += $alloc['VD'];
        }
        
        $tnkq_allocations[$i] = $alloc;
    }
    
    // Now calculate needed TL VD
    $needed_tl_vd = $target_total_vd - $total_ds_vd - $total_tnkq_vd_allocated;
    
    // Adjust TL allocations to match needed VD
    $current_tl_vd = array_sum(array_column($units, 'tl_vd'));
    $tl_vd_diff = $needed_tl_vd - $current_tl_vd;
    
    if (abs($tl_vd_diff) >= 0.25) {
        $vd_available = [];
        foreach ($units as $i => $u) {
            if (!empty($u['levels']['VD'])) {
                $vd_available[] = $i;
            }
        }
        
        if (!empty($vd_available)) {
            usort($vd_available, function($a, $b) use ($units) {
                return $units[$b]['so_tiet'] <=> $units[$a]['so_tiet'];
            });
            
            $remaining = $tl_vd_diff;
            $max_iterations = 100;
            $iteration = 0;
            
            while (abs($remaining) >= 0.25 && $iteration < $max_iterations) {
                $iteration++;
                $adjusted = false;
                
                foreach ($vd_available as $idx) {
                    if (abs($remaining) < 0.25) break;
                    
                    if ($remaining > 0) {
                        // Need more VD - take from NB or TH
                        if ($units[$idx]['tl_nb'] >= 0.5) {
                            $units[$idx]['tl_nb'] -= 0.5;
                            $units[$idx]['tl_vd'] += 0.5;
                            $remaining -= 0.5;
                            $adjusted = true;
                        } elseif ($units[$idx]['tl_th'] >= 0.5) {
                            $units[$idx]['tl_th'] -= 0.5;
                            $units[$idx]['tl_vd'] += 0.5;
                            $remaining -= 0.5;
                            $adjusted = true;
                        }
                    } else {
                        // Need less VD - move to NB or TH
                        if ($units[$idx]['tl_vd'] >= 0.5) {
                            $units[$idx]['tl_vd'] -= 0.5;
                            if (!empty($units[$idx]['levels']['NB'])) {
                                $units[$idx]['tl_nb'] += 0.5;
                            } else {
                                $units[$idx]['tl_th'] += 0.5;
                            }
                            $remaining += 0.5;
                            $adjusted = true;
                        }
                    }
                }
                
                if (!$adjusted) break;
            }
        }
    }
    
    // Store TNKQ allocations for final calculation
    foreach ($units as $i => $u) {
        $units[$i]['_tnkq_lvl'] = $tnkq_allocations[$i];
    }

    // Level NB/TH/VD distribution per unit - CALCULATE FROM ACTUAL COMPONENTS
    foreach ($units as $i => $u) {
        // Use pre-calculated TNKQ allocations
        $tnkq_per_level = $units[$i]['_tnkq_lvl'];
        
        // DS contribution
        $ds_per_level = ['NB'=>0, 'TH'=>0, 'VD'=>0];
        if (!empty($u['has_ds'])) {
            if ($u['ds_label'] === 'Câu 1') {
                $ds_per_level['NB'] = 1 * $POINT_PER_DS_ITEM;
                $ds_per_level['TH'] = 1 * $POINT_PER_DS_ITEM;
                $ds_per_level['VD'] = 2 * $POINT_PER_DS_ITEM;
            } else {
                $ds_per_level['NB'] = 2 * $POINT_PER_DS_ITEM;
                $ds_per_level['TH'] = 1 * $POINT_PER_DS_ITEM;
                $ds_per_level['VD'] = 1 * $POINT_PER_DS_ITEM;
            }
        }
        
        // TL contribution (already calculated and balanced)
        $tl_per_level = [
            'NB' => $u['tl_nb'] ?? 0,
            'TH' => $u['tl_th'] ?? 0,
            'VD' => $u['tl_vd'] ?? 0
        ];
        
        // TOTAL per level = TNKQ + DS + TL
        $units[$i]['lvl']['NB'] = $tnkq_per_level['NB'] + $ds_per_level['NB'] + $tl_per_level['NB'];
        $units[$i]['lvl']['TH'] = $tnkq_per_level['TH'] + $ds_per_level['TH'] + $tl_per_level['TH'];
        $units[$i]['lvl']['VD'] = $tnkq_per_level['VD'] + $ds_per_level['VD'] + $tl_per_level['VD'];
        
        // Clean up temp data
        unset($units[$i]['_tnkq_lvl']);
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
    
    // ENFORCE: NB ≤ 40%, TH ≥ 30%, VD = 30%
    $max_nb = $TOTAL_POINTS * 0.40; // 4.0
    $min_th = $TOTAL_POINTS * 0.30; // 3.0
    $target_vd = $TOTAL_POINTS * 0.30; // 3.0
    
    // If NB exceeds 40%, transfer excess to TH
    if ($tot_nb > $max_nb + 0.01) {
        $excess_nb = $tot_nb - $max_nb;
        
        // Find units with NB in TL and transfer to TH
        foreach ($units as $i => $u) {
            if ($excess_nb < 0.25) break;
            
            if ($u['tl_nb'] >= 0.5 && !empty($u['levels']['TH'])) {
                $transfer = min($u['tl_nb'], $excess_nb, 1.0);
                $transfer = floor($transfer / 0.5) * 0.5; // Round down to 0.5
                
                $units[$i]['tl_nb'] -= $transfer;
                $units[$i]['tl_th'] += $transfer;
                
                // Recalculate lvl
                $units[$i]['lvl']['NB'] -= $transfer;
                $units[$i]['lvl']['TH'] += $transfer;
                
                $excess_nb -= $transfer;
                $tot_nb -= $transfer;
                $tot_th += $transfer;
            }
        }
    }
    
    // If TH is below 30%, transfer from NB
    if ($tot_th < $min_th - 0.01) {
        $needed_th = $min_th - $tot_th;
        
        foreach ($units as $i => $u) {
            if ($needed_th < 0.25) break;
            
            if ($u['tl_nb'] >= 0.5 && !empty($u['levels']['TH'])) {
                $transfer = min($u['tl_nb'], $needed_th, 1.0);
                $transfer = floor($transfer / 0.5) * 0.5;
                
                $units[$i]['tl_nb'] -= $transfer;
                $units[$i]['tl_th'] += $transfer;
                
                $units[$i]['lvl']['NB'] -= $transfer;
                $units[$i]['lvl']['TH'] += $transfer;
                
                $needed_th -= $transfer;
                $tot_nb -= $transfer;
                $tot_th += $transfer;
            }
        }
    }
    
    // ENFORCE VD = exactly 30% (3.0 points)
    if ($tot_vd > $target_vd + 0.01) {
        // VD exceeds 30% - transfer excess to NB or TH
        $excess_vd = $tot_vd - $target_vd;
        
        // Prioritize transferring to whichever is lower (NB or TH)
        $transfer_to = ($tot_nb < $tot_th) ? 'NB' : 'TH';
        
        // Find units with VD in TL and reduce
        foreach ($units as $i => $u) {
            if ($excess_vd < 0.25) break;
            
            if ($u['tl_vd'] >= 0.5 && !empty($u['levels'][$transfer_to])) {
                $transfer = min($u['tl_vd'], $excess_vd, 1.0);
                $transfer = floor($transfer / 0.25) * 0.25; // Round down to 0.25
                
                $units[$i]['tl_vd'] -= $transfer;
                $units[$i]['tl_'.strtolower($transfer_to)] += $transfer;
                
                // Recalculate lvl
                $units[$i]['lvl']['VD'] -= $transfer;
                $units[$i]['lvl'][$transfer_to] += $transfer;
                
                $excess_vd -= $transfer;
                $tot_vd -= $transfer;
                if ($transfer_to === 'NB') {
                    $tot_nb += $transfer;
                } else {
                    $tot_th += $transfer;
                }
            }
        }
        
        // If still excess, try reducing any remaining VD
        if ($excess_vd >= 0.25) {
            foreach ($units as $i => $u) {
                if ($excess_vd < 0.25) break;
                
                if ($u['tl_vd'] >= 0.25) {
                    $transfer = min($u['tl_vd'], $excess_vd, 0.5);
                    $transfer = floor($transfer / 0.25) * 0.25;
                    
                    $units[$i]['tl_vd'] -= $transfer;
                    
                    // Add to NB or TH based on which has more room (< 40%)
                    $target_level = ($tot_nb < $max_nb - 0.25) ? 'NB' : 'TH';
                    $units[$i]['tl_'.strtolower($target_level)] += $transfer;
                    
                    $units[$i]['lvl']['VD'] -= $transfer;
                    $units[$i]['lvl'][$target_level] += $transfer;
                    
                    $excess_vd -= $transfer;
                    $tot_vd -= $transfer;
                    if ($target_level === 'NB') {
                        $tot_nb += $transfer;
                    } else {
                        $tot_th += $transfer;
                    }
                }
            }
        }
    } elseif ($tot_vd < $target_vd - 0.01) {
        // VD is below 30% - transfer from NB or TH to VD
        $needed_vd = $target_vd - $tot_vd;
        
        // Take from whichever is higher (NB or TH)
        $take_from = ($tot_nb > $tot_th) ? 'NB' : 'TH';
        
        foreach ($units as $i => $u) {
            if ($needed_vd < 0.25) break;
            
            $from_field = 'tl_'.strtolower($take_from);
            if ($u[$from_field] >= 0.5 && !empty($u['levels']['VD'])) {
                $transfer = min($u[$from_field], $needed_vd, 1.0);
                $transfer = floor($transfer / 0.25) * 0.25;
                
                $units[$i][$from_field] -= $transfer;
                $units[$i]['tl_vd'] += $transfer;
                
                $units[$i]['lvl'][$take_from] -= $transfer;
                $units[$i]['lvl']['VD'] += $transfer;
                
                $needed_vd -= $transfer;
                $tot_vd += $transfer;
                if ($take_from === 'NB') {
                    $tot_nb -= $transfer;
                } else {
                    $tot_th -= $transfer;
                }
            }
        }
    }
    
    // ========== TỐI ƯU TỰ LUẬN: GỘP CÂU NHỎ THÀNH CÂU LỚN ==========
    // Mục tiêu: Tối đa 4 câu TL, ưu tiên 1-1.5đ, hạn chế 0.5đ
    
    // Collect all TL questions with their points
    $tl_questions = [];
    foreach ($units as $i => $u) {
        if ($u['tl_pts'] > 0) {
            $tl_questions[] = [
                'unit_idx' => $i,
                'points' => $u['tl_pts'],
                'nb' => $u['tl_nb'],
                'th' => $u['tl_th'],
                'vd' => $u['tl_vd']
            ];
        }
    }
    
    // If more than 4 TL questions, merge smaller ones
    if (count($tl_questions) > 4) {
        // Sort by points (ascending) to merge smallest first
        usort($tl_questions, function($a, $b) {
            return $a['points'] <=> $b['points'];
        });
        
        while (count($tl_questions) > 4) {
            // Find two smallest questions that can be merged
            $merged = false;
            for ($i = 0; $i < count($tl_questions) - 1; $i++) {
                $sum = $tl_questions[$i]['points'] + $tl_questions[$i + 1]['points'];
                if ($sum <= 1.5) {
                    // Merge question i+1 into question i
                    $tl_questions[$i]['points'] = $sum;
                    $tl_questions[$i]['nb'] += $tl_questions[$i + 1]['nb'];
                    $tl_questions[$i]['th'] += $tl_questions[$i + 1]['th'];
                    $tl_questions[$i]['vd'] += $tl_questions[$i + 1]['vd'];
                    
                    // Clear the merged unit
                    $merged_idx = $tl_questions[$i + 1]['unit_idx'];
                    $units[$merged_idx]['tl_pts'] = 0;
                    $units[$merged_idx]['tl_nb'] = 0;
                    $units[$merged_idx]['tl_th'] = 0;
                    $units[$merged_idx]['tl_vd'] = 0;
                    
                    // Remove from array
                    array_splice($tl_questions, $i + 1, 1);
                    $merged = true;
                    break;
                }
            }
            
            // If can't merge within 1.5đ limit, force merge two smallest
            if (!$merged && count($tl_questions) > 4) {
                $tl_questions[1]['points'] += $tl_questions[0]['points'];
                $tl_questions[1]['nb'] += $tl_questions[0]['nb'];
                $tl_questions[1]['th'] += $tl_questions[0]['th'];
                $tl_questions[1]['vd'] += $tl_questions[0]['vd'];
                
                $merged_idx = $tl_questions[0]['unit_idx'];
                $units[$merged_idx]['tl_pts'] = 0;
                $units[$merged_idx]['tl_nb'] = 0;
                $units[$merged_idx]['tl_th'] = 0;
                $units[$merged_idx]['tl_vd'] = 0;
                
                array_splice($tl_questions, 0, 1);
            }
        }
        
        // Update remaining questions back to units
        foreach ($tl_questions as $q) {
            $idx = $q['unit_idx'];
            $units[$idx]['tl_pts'] = $q['points'];
            $units[$idx]['tl_nb'] = $q['nb'];
            $units[$idx]['tl_th'] = $q['th'];
            $units[$idx]['tl_vd'] = $q['vd'];
            
            // Update lvl totals
            $tnkq_nb = $units[$idx]['_tnkq_lvl']['NB'] ?? 0;
            $tnkq_th = $units[$idx]['_tnkq_lvl']['TH'] ?? 0;
            $tnkq_vd = $units[$idx]['_tnkq_lvl']['VD'] ?? 0;
            $ds_nb = $units[$idx]['has_ds'] ? ($units[$idx]['ds_label'] === 'Câu 2' ? 0.5 : 0.25) : 0;
            $ds_th = $units[$idx]['has_ds'] ? 0.25 : 0;
            $ds_vd = $units[$idx]['has_ds'] ? ($units[$idx]['ds_label'] === 'Câu 1' ? 0.5 : 0.25) : 0;
            
            $units[$idx]['lvl']['NB'] = $tnkq_nb + $ds_nb + $q['nb'];
            $units[$idx]['lvl']['TH'] = $tnkq_th + $ds_th + $q['th'];
            $units[$idx]['lvl']['VD'] = $tnkq_vd + $ds_vd + $q['vd'];
        }
    }
    
    // Optimize 0.5đ questions - try to merge or upgrade
    $count_half = 0;
    foreach ($units as $u) {
        if ($u['tl_pts'] == 0.5) $count_half++;
    }
    
    if ($count_half > 1) {
        // Try to merge pairs of 0.5đ into 1.0đ
        $half_indices = [];
        foreach ($units as $i => $u) {
            if ($u['tl_pts'] == 0.5) {
                $half_indices[] = $i;
            }
        }
        
        // Merge pairs
        for ($p = 0; $p < count($half_indices) - 1; $p += 2) {
            if ($p + 1 < count($half_indices)) {
                $idx1 = $half_indices[$p];
                $idx2 = $half_indices[$p + 1];
                
                // Merge idx2 into idx1
                $units[$idx1]['tl_pts'] = 1.0;
                $units[$idx1]['tl_nb'] += $units[$idx2]['tl_nb'];
                $units[$idx1]['tl_th'] += $units[$idx2]['tl_th'];
                $units[$idx1]['tl_vd'] += $units[$idx2]['tl_vd'];
                
                // Clear idx2
                $units[$idx2]['tl_pts'] = 0;
                $units[$idx2]['tl_nb'] = 0;
                $units[$idx2]['tl_th'] = 0;
                $units[$idx2]['tl_vd'] = 0;
                
                // Update lvl
                $tnkq_nb1 = $units[$idx1]['_tnkq_lvl']['NB'] ?? 0;
                $tnkq_th1 = $units[$idx1]['_tnkq_lvl']['TH'] ?? 0;
                $tnkq_vd1 = $units[$idx1]['_tnkq_lvl']['VD'] ?? 0;
                $ds_nb1 = $units[$idx1]['has_ds'] ? ($units[$idx1]['ds_label'] === 'Câu 2' ? 0.5 : 0.25) : 0;
                $ds_th1 = $units[$idx1]['has_ds'] ? 0.25 : 0;
                $ds_vd1 = $units[$idx1]['has_ds'] ? ($units[$idx1]['ds_label'] === 'Câu 1' ? 0.5 : 0.25) : 0;
                
                $units[$idx1]['lvl']['NB'] = $tnkq_nb1 + $ds_nb1 + $units[$idx1]['tl_nb'];
                $units[$idx1]['lvl']['TH'] = $tnkq_th1 + $ds_th1 + $units[$idx1]['tl_th'];
                $units[$idx1]['lvl']['VD'] = $tnkq_vd1 + $ds_vd1 + $units[$idx1]['tl_vd'];
                
                $tnkq_nb2 = $units[$idx2]['_tnkq_lvl']['NB'] ?? 0;
                $tnkq_th2 = $units[$idx2]['_tnkq_lvl']['TH'] ?? 0;
                $tnkq_vd2 = $units[$idx2]['_tnkq_lvl']['VD'] ?? 0;
                $ds_nb2 = $units[$idx2]['has_ds'] ? ($units[$idx2]['ds_label'] === 'Câu 2' ? 0.5 : 0.25) : 0;
                $ds_th2 = $units[$idx2]['has_ds'] ? 0.25 : 0;
                $ds_vd2 = $units[$idx2]['has_ds'] ? ($units[$idx2]['ds_label'] === 'Câu 1' ? 0.5 : 0.25) : 0;
                
                $units[$idx2]['lvl']['NB'] = $tnkq_nb2 + $ds_nb2;
                $units[$idx2]['lvl']['TH'] = $tnkq_th2 + $ds_th2;
                $units[$idx2]['lvl']['VD'] = $tnkq_vd2 + $ds_vd2;
            }
        }
    }
    // ========== END TỐI ƯU TỰ LUẬN ==========
    
    $tot_all = $tot_nb + $tot_th + $tot_vd;
    if ($tot_all <= 0) $tot_all = 1e-9;

    // ---------- Build HTML Table ----------
    ob_start();
    ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center matrix-result-table" id="matran-table">
        <thead>
          <tr class="table-header-main">
            <th rowspan="2" class="col-title">Chủ đề / Đơn vị (tiết)</th>
            <th colspan="3" class="header-tnkq">TNKQ (<?= $tot_tnkq_q ?>c = <?= fnum($tot_tnkq_pts) ?>đ)</th>
            <th colspan="3" class="header-ds">Đúng/Sai (2c = 8ý = <?= fnum($tot_ds_pts) ?>đ)</th>
            <th colspan="3" class="header-tl">Tự luận (≈ <?= fnum($tot_tl_pts) ?>đ)</th>
            <th colspan="3" class="header-total">TỔNG mức độ (đ + số câu/ý)</th>
            <th rowspan="2" class="col-percent">Tỉ lệ %</th>
          </tr>
          <tr class="table-header-sub">
            <th class="level-nb">NB</th><th class="level-th">TH</th><th class="level-vd">VD</th>
            <th class="level-nb">NB</th><th class="level-th">TH</th><th class="level-vd">VD</th>
            <th class="level-nb">NB</th><th class="level-th">TH</th><th class="level-vd">VD</th>
            <th class="level-nb-total">NB</th><th class="level-th-total">TH</th><th class="level-vd-total">VD</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // render grouped by topic
        $grouped = [];
        foreach ($units as $u) $grouped[$u['topic']][] = $u;
        foreach ($grouped as $topic => $arr) {
            echo "<tr class='topic-row'><td colspan='14' class='text-start fw-bold'>{$topic} (" . array_sum(array_column($arr,'so_tiet')) . " tiết)</td></tr>";
            foreach ($arr as $u) {
                echo "<tr class='unit-row'>";
                echo "<td class='text-start ps-4'>{$u['title']} <span class='text-muted'>({$u['so_tiet']} tiết)</span></td>";
                
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
                echo $tn_nb>0 ? "<td class='cell-tnkq'>{$tn_nb}c</td>" : "<td class='cell-empty'></td>";
                echo $tn_th>0 ? "<td class='cell-tnkq'>{$tn_th}c</td>" : "<td class='cell-empty'></td>";
                echo $tn_vd>0 ? "<td class='cell-tnkq'>{$tn_vd}c</td>" : "<td class='cell-empty'></td>";
                
                // DS
                $ds_nb = $ds_th = $ds_vd = '';
                if (!empty($u['has_ds'])) {
                    if ($u['ds_label'] === 'Câu 1') { $ds_nb='1ý'; $ds_th='1ý'; $ds_vd='2ý'; }
                    else { $ds_nb='2ý'; $ds_th='1ý'; $ds_vd='1ý'; }
                }
                echo $ds_nb!=='' ? "<td class='cell-ds'>{$ds_nb}</td>" : "<td class='cell-empty'></td>";
                echo $ds_th!=='' ? "<td class='cell-ds'>{$ds_th}</td>" : "<td class='cell-empty'></td>";
                echo $ds_vd!=='' ? "<td class='cell-ds'>{$ds_vd}</td>" : "<td class='cell-empty'></td>";
                
                // TL - NOW USING PRE-ROUNDED VALUES
                $tl_nb = $u['tl_nb'] ?? 0;
                $tl_th = $u['tl_th'] ?? 0;
                $tl_vd = $u['tl_vd'] ?? 0;
                echo $tl_nb>0 ? "<td class='cell-tl'>".fnum($tl_nb)."đ</td>" : "<td class='cell-empty'></td>";
                echo $tl_th>0 ? "<td class='cell-tl'>".fnum($tl_th)."đ</td>" : "<td class='cell-empty'></td>";
                echo $tl_vd>0 ? "<td class='cell-tl'>".fnum($tl_vd)."đ</td>" : "<td class='cell-empty'></td>";
                
                // Total per level
                $sumNB = fnum($u['lvl']['NB']);
                $sumTH = fnum($u['lvl']['TH']);
                $sumVD = fnum($u['lvl']['VD']);
                echo "<td class='cell-total-nb fw-bold'>{$sumNB}đ</td>";
                echo "<td class='cell-total-th fw-bold'>{$sumTH}đ</td>";
                echo "<td class='cell-total-vd fw-bold'>{$sumVD}đ</td>";
                
                // Percent
                $pct = ($u['tnkq_pts']+$u['ds_pts']+$u['tl_pts']) / $TOTAL_POINTS * 100;
                echo "<td class='cell-percent fw-bold'>".round($pct,1)."%</td>";
                echo "</tr>";
            }
        }
        ?>
          <tr class="total-row">
            <td class="text-start fw-bold ps-3">TỔNG CỘT</td>
            <td colspan="3" class="summary-tnkq fw-bold">TNKQ: <?= intval($tot_tnkq_q) ?> câu = <?= fnum($tot_tnkq_pts) ?>đ</td>
            <td colspan="3" class="summary-ds fw-bold">Đ/S: 2 câu = <?= fnum($tot_ds_pts) ?>đ (<?= intval($tot_ds_items) ?> ý)</td>
            <td colspan="3" class="summary-tl fw-bold">Tự luận: <?= fnum($tot_tl_pts) ?>đ</td>
            <td class="summary-total-nb fw-bold">NB: <?= fnum($tot_nb) ?>đ</td>
            <td class="summary-total-th fw-bold">TH: <?= fnum($tot_th) ?>đ</td>
            <td class="summary-total-vd fw-bold">VD: <?= fnum($tot_vd) ?>đ</td>
            <td class="summary-percent fw-bold">100%</td>
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

// ============= WORD EXPORT HANDLER =============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'export_word') {
    $html_content = $_POST['html_content'] ?? '';
    $exam_title = $_POST['exam_title'] ?? 'Ma_Tran_De_Thi';
    
    if (empty($html_content)) {
        http_response_code(400);
        echo "Không có dữ liệu để xuất";
        exit;
    }
    
    // Sanitize filename
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam_title);
    $filename = date('Y-m-d') . '_' . $filename . '.doc';
    
    // Set headers for Word download
    header('Content-Type: application/vnd.ms-word');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Create complete HTML document for Word with LANDSCAPE orientation
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>Ma Trận Đề Thi</title>';
    
    // Word-specific XML for LANDSCAPE orientation
    echo '<!--[if gte mso 9]><xml>';
    echo '<w:WordDocument>';
    echo '<w:View>Print</w:View>';
    echo '<w:Zoom>100</w:Zoom>';
    echo '<w:DoNotOptimizeForBrowser/>';
    echo '</w:WordDocument>';
    echo '</xml><![endif]-->';
    
    echo '<!--[if gte mso 9]><xml>';
    echo '<w:LatentStyles DefLockedState="false" DefUnhideWhenUsed="true" DefSemiHidden="true" DefQFormat="false" DefPriority="99" LatentStyleCount="267">';
    echo '</w:LatentStyles>';
    echo '</xml><![endif]-->';
    
    // Page setup for LANDSCAPE
    echo '<style>';
    echo '@page Section1 { ';
    echo '  size: 841.95pt 595.35pt; '; // A4 landscape dimensions in points
    echo '  margin: 72pt 72pt 72pt 72pt; '; // 1 inch margins
    echo '  mso-page-orientation: landscape; ';
    echo '  mso-header-margin: 36pt; ';
    echo '  mso-footer-margin: 36pt; ';
    echo '  mso-paper-source: 0; ';
    echo '}';
    echo 'div.Section1 { page: Section1; }';
    echo 'body { font-family: "Times New Roman", Times, serif; font-size: 11pt; }';
    echo 'table { border-collapse: collapse; width: 100%; font-size: 9pt; }';
    echo 'th, td { border: 1px solid black; padding: 6px 4px; text-align: center; vertical-align: middle; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; }';
    echo '.topic-row td { background-color: #5B9BD5; color: white; font-weight: bold; text-align: left; }';
    echo '.total-row td { background-color: #70AD47; color: white; font-weight: bold; }';
    echo '.cell-tnkq { background-color: #E7F4F7; color: #16a085; font-weight: 600; }';
    echo '.cell-ds { background-color: #FEF5E7; color: #d68910; font-weight: 600; }';
    echo '.cell-tl { background-color: #FDECEA; color: #cb4335; font-weight: 600; }';
    echo '.cell-total-nb { background-color: #D5F4E6; color: #1e8449; }';
    echo '.cell-total-th { background-color: #FDEBD0; color: #b9770e; }';
    echo '.cell-total-vd { background-color: #FADBD8; color: #943126; }';
    echo '.cell-percent { background-color: #E8DAEF; color: #6c3483; }';
    echo '.level-nb { background-color: #27ae60; color: white; }';
    echo '.level-th { background-color: #f39c12; color: white; }';
    echo '.level-vd { background-color: #e74c3c; color: white; }';
    echo '.level-nb-total, .level-th-total, .level-vd-total { background-color: #2c3e50; color: white; font-weight: 700; }';
    echo '.cell-empty { background-color: #f8f9fa; }';
    echo 'h2, h3 { text-align: center; font-weight: bold; }';
    echo 'h2 { font-size: 16pt; margin-bottom: 10pt; }';
    echo 'h3 { font-size: 13pt; margin-bottom: 15pt; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="Section1">';
    echo '<h2>MA TRẬN ĐỀ KIỂM TRA</h2>';
    echo '<h3>' . htmlspecialchars($exam_title) . '</h3>';
    echo $html_content;
    echo '</div>';
    echo '</body></html>';
    exit;
}
// ============= END WORD EXPORT HANDLER =============

$title = 'Xây Dựng Ma Trận Đề Kiểm Tra - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
    <div class="container mb-5">
        <div>
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
                        <button id="export-word-btn" class="btn btn-primary btn-sm">📄 Xuất Word</button>
                        <button id="print-btn" class="btn btn-success btn-sm">🖨️ In</button>
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
      <input class="form-check-input level-nb" type="checkbox">
      <label class="form-check-label small">NB</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-th" type="checkbox">
      <label class="form-check-label small">TH</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input level-vd" type="checkbox">
      <label class="form-check-label small">VD</label>
    </div>
  </div>
</template>

<style>
.hidden{display:none}

/* Ensure checkboxes are clickable */
.form-check-input {
  pointer-events: auto !important;
  cursor: pointer !important;
  width: 18px;
  height: 18px;
  margin-right: 4px;
}

.form-check-label {
  cursor: pointer !important;
  user-select: none;
  transition: all 0.15s ease;
  font-size: 0.875rem;
  padding-top: 4px;
    padding-left: 5px;
}

/* Clean inline checkbox layout */
.form-check-inline {
  margin-right: 12px !important;
  margin-bottom: 0;
  display: inline-flex;
  align-items: center;
}

/* When checkbox is checked - subtle highlight on label */
.form-check-inline:has(.form-check-input:checked) .form-check-label {
  color: #0d6efd;
  font-weight: 600;
}

/* When checkbox is unchecked - dimmed label */
.form-check-inline:has(.form-check-input:not(:checked)) .form-check-label {
  color: #6c757d;
  opacity: 0.6;
}

/* Hover effect - brighten dimmed items */
.form-check-inline:hover .form-check-label {
  opacity: 1 !important;
}

/* Professional Matrix Table Styling */
.matrix-result-table {
  border: 2px solid #2c3e50 !important;
  font-size: 0.9rem;
}

.matrix-result-table thead th {
  font-weight: 600;
  vertical-align: middle;
  border: 1px solid #34495e !important;
  padding: 10px 8px;
}

.matrix-result-table tbody td {
  border: 1px solid #bdc3c7 !important;
  padding: 8px;
}

/* Header styling */
.table-header-main th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 0.95rem;
}

.table-header-sub th {
  background: #34495e;
  color: white;
  font-size: 0.85rem;
}

.col-title {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  min-width: 200px;
}

.col-percent {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  min-width: 80px;
}

/* Level colors in sub-header */
.level-nb {
  background: #27ae60 !important;
  color: white;
}

.level-th {
  background: #f39c12 !important;
  color: white;
}

.level-vd {
  background: #e74c3c !important;
  color: white;
}

.level-nb-total, .level-th-total, .level-vd-total {
  background: #2c3e50 !important;
  color: #fff;
  font-weight: 700;
}

/* Topic row */
.topic-row td {
  background: linear-gradient(90deg, #3498db, #2980b9) !important;
  color: white !important;
  font-size: 1rem;
  padding: 12px 10px !important;
  border: 1px solid #2471a3 !important;
}

/* Unit row */
.unit-row td:first-child {
  background: #ecf0f1;
  font-weight: 500;
  color: #2c3e50;
}

/* Cell types with distinct colors */
.cell-tnkq {
  background: #e8f8f5 !important;
  color: #16a085;
  font-weight: 600;
}

.cell-ds {
  background: #fef5e7 !important;
  color: #d68910;
  font-weight: 600;
}

.cell-tl {
  background: #fdecea !important;
  color: #cb4335;
  font-weight: 600;
}

.cell-total-nb {
  background: #d5f4e6 !important;
  color: #1e8449;
}

.cell-total-th {
  background: #fdebd0 !important;
  color: #b9770e;
}

.cell-total-vd {
  background: #fadbd8 !important;
  color: #943126;
}

.cell-percent {
  background: #e8daef !important;
  color: #6c3483;
}

.cell-empty {
  background: #f8f9fa !important;
}

/* Total row at bottom */
.total-row td {
  background: linear-gradient(90deg, #1abc9c, #16a085) !important;
  color: white !important;
  font-size: 1rem;
  padding: 12px 10px !important;
  border: 1px solid #138d75 !important;
}

.summary-tnkq {
  background: #45b39d !important;
}

.summary-ds {
  background: #52be80 !important;
}

.summary-tl {
  background: #58d68d !important;
}

.summary-total-nb, .summary-total-th, .summary-total-vd {
  background: #196f3d !important;
  font-size: 0.95rem;
}

.summary-percent {
  background: #7dcea0 !important;
  color: #0b5345 !important;
  font-size: 1.1rem;
}

/* Hover effects */
.unit-row:hover {
  background: #f8f9fa;
}

.matrix-result-table tbody tr:hover td:not(.topic-row td, .total-row td) {
  background-blend-mode: overlay;
  opacity: 0.9;
}

/* Print Styles */
@media print {
  /* Set landscape orientation */
  @page {
    size: A4 landscape;
    margin: 10mm 70px; /* Top/bottom 10mm, left/right 70px */
  }
  
  /* Hide header navigation menu */
  header,
  nav,
  .navbar,
  .main-nav,
  .top-bar,
  .sidebar {
    display: none !important;
  }
  
  /* Hide buttons and controls */
  #form-area,
  #back-edit,
  #print-btn,
  button {
    display: none !important;
  }
  
  /* Adjust main content for print */
  .main-content {
    margin: 0 !important;
    padding: 0 !important;
    max-width: 100% !important;
  }
  
  .container {
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  /* Ensure matrix table takes full width */
  #result-area {
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .table-responsive {
    overflow: visible !important;
  }
  
  .matrix-result-table {
    width: 100% !important;
    font-size: 0.75rem !important;
    page-break-inside: avoid;
  }
  
  .matrix-result-table th,
  .matrix-result-table td {
    padding: 4px 6px !important;
    font-size: 0.7rem !important;
  }
  
  /* Preserve colors in print */
  .matrix-result-table * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }
  
  /* Hide any footer */
  footer {
    display: none !important;
  }
}
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
  
  function addUnit(unitsWrap, uTitle='', tiet=1, levels={NB:true, TH:true, VD:true}){
    const node = unitTpl.content.cloneNode(true);
    const uel = node.querySelector('.unit');
    uel.querySelector('.unit-title').value = uTitle;
    uel.querySelector('.unit-tiet').value = tiet;
    
    // Generate unique IDs for checkboxes
    const uniqueId = Date.now() + Math.random();
    const nbCheckbox = uel.querySelector('.level-nb');
    const thCheckbox = uel.querySelector('.level-th');
    const vdCheckbox = uel.querySelector('.level-vd');
    
    // Set IDs and link labels
    nbCheckbox.id = 'nb-' + uniqueId;
    thCheckbox.id = 'th-' + uniqueId;
    vdCheckbox.id = 'vd-' + uniqueId;
    
    uel.querySelectorAll('.form-check-label')[0].setAttribute('for', nbCheckbox.id);
    uel.querySelectorAll('.form-check-label')[1].setAttribute('for', thCheckbox.id);
    uel.querySelectorAll('.form-check-label')[2].setAttribute('for', vdCheckbox.id);
    
    // Set checkbox states
    nbCheckbox.checked = levels.NB !== false;
    thCheckbox.checked = levels.TH !== false;
    vdCheckbox.checked = levels.VD !== false;
    
    uel.querySelector('.remove-unit').addEventListener('click', ()=> uel.remove());
    unitsWrap.appendChild(uel);
    return uel;
  }

  // Initial sample data
  const tA = addTopic('A. Máy tính và cộng đồng');
  const unitsA = tA.querySelector('.units');
  unitsA.innerHTML='';
  addUnit(unitsA, '1. Sơ lược về các thành phần của máy tính', 2);
  addUnit(unitsA, '2. Khái niệm hệ điều hành và phần mềm ứng dụng', 1);

  const tC = addTopic('C. Tổ chức lưu trữ, tìm kiếm và trao đổi thông tin');
  const unitsC = tC.querySelector('.units');
  unitsC.innerHTML='';
  addUnit(unitsC, 'Mạng xã hội và một số kênh trao đổi thông tin thông dụng trên Internet', 2);

  const tD = addTopic('D. Đạo đức, pháp luật và văn hoá trong môi trường số');
  const unitsD = tD.querySelector('.units');
  unitsD.innerHTML='';
  addUnit(unitsD, 'Văn hoá ứng xử qua phương tiện truyền thông số', 3);

  const tE = addTopic('E. Ứng dụng tin học');
  const unitsE = tE.querySelector('.units');
  unitsE.innerHTML='';
  addUnit(unitsE, 'Bảng tính điện tử cơ bản', 4);

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

  document.getElementById('export-word-btn').addEventListener('click', async ()=>{
    const matranOutput = document.getElementById('matran-output');
    if (!matranOutput || !matranOutput.innerHTML.trim()) {
      alert('Chưa có ma trận để xuất. Vui lòng tạo ma trận trước.');
      return;
    }
    
    const examTitle = document.getElementById('exam-title').value.trim() || 'Ma_Tran_De_Thi';
    const htmlContent = matranOutput.innerHTML;
    
    const fd = new FormData();
    fd.append('action', 'export_word');
    fd.append('exam_title', examTitle);
    fd.append('html_content', htmlContent);
    
    const btn = document.getElementById('export-word-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang xuất...';
    
    try {
      const res = await fetch('', { method: 'POST', body: fd });
      if (!res.ok) throw new Error('Lỗi khi xuất file');
      
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = examTitle.replace(/[^a-zA-Z0-9_-]/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.doc';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      
      // Show success message
      const successMsg = document.createElement('div');
      successMsg.className = 'alert alert-success mt-2';
      successMsg.innerHTML = '✅ Đã xuất file Word thành công!';
      successMsg.style.position = 'fixed';
      successMsg.style.top = '20px';
      successMsg.style.right = '20px';
      successMsg.style.zIndex = '9999';
      document.body.appendChild(successMsg);
      setTimeout(() => successMsg.remove(), 3000);
    } catch (e) {
      alert('Lỗi khi xuất Word: ' + e.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  document.getElementById('reset-form').addEventListener('click', ()=>{
    topicsContainer.innerHTML=''; 
    addTopic();
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
