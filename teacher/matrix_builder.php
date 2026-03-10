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
    // Debug configuration - Set to true to show debug info
    $ENABLE_DEBUG = false;  // Set to true to enable debug output
    
    header('Content-Type: text/html; charset=utf-8');
    $raw = $_POST['data'] ?? '';
    $input = json_decode($raw, true);
    if (!$input || !isset($input['topics'])) {
        http_response_code(400);
        echo "<div class='alert alert-danger'>Dữ liệu không hợp lệ.</div>";
        exit;
    }

    // ------------------------
    // Core algorithm v4.6 (server-side) - SPLIT oversized questions
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

    // DS: assign Câu1 & Câu2 to units with largest so_tiet
    // Special case: if only 1 unit, assign both DS questions to it
    usort($units, function($a,$b){ if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; return $b['so_tiet'] <=> $a['so_tiet']; });
    $ds_patterns = [
        ['label'=>'Câu 1','desc'=>'1NB + 2TH + 1VD','pts'=>1.0,'items'=>4],
        ['label'=>'Câu 2','desc'=>'2NB + 1TH + 1VD','pts'=>1.0,'items'=>4]
    ];
    
    // Initialize all units without DS first
    for ($i=0;$i<count($units);$i++) {
        $units[$i]['has_ds'] = false;
        $units[$i]['ds_labels'] = []; // Array to hold multiple DS questions
        $units[$i]['ds_desc'] = '';
        $units[$i]['ds_pts'] = 0.0;
        $units[$i]['ds_items'] = 0;
    }
    
    // Assign DS questions
    if (count($units) == 1) {
        // Only 1 unit: assign both DS questions to it
        $units[0]['has_ds'] = true;
        $units[0]['ds_labels'] = ['Câu 1', 'Câu 2'];
        $units[0]['ds_desc'] = 'Câu 1 + Câu 2';
        $units[0]['ds_pts'] = 2.0;
        $units[0]['ds_items'] = 8; // 4 items per question
        $units[0]['ds_label'] = 'Câu 1'; // Keep for backward compatibility
    } else {
        // Multiple units: assign to 2 units with largest so_tiet
        for ($i=0;$i<min(2, count($units));$i++) {
            $units[$i]['has_ds'] = true;
            $units[$i]['ds_labels'] = [$ds_patterns[$i]['label']];
            $units[$i]['ds_label'] = $ds_patterns[$i]['label'];
            $units[$i]['ds_desc'] = $ds_patterns[$i]['desc'];
            $units[$i]['ds_pts'] = $ds_patterns[$i]['pts'];
            $units[$i]['ds_items'] = $ds_patterns[$i]['items'];
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

    // TL: compensate to achieve target proportion per unit
    // Target: each unit should have total points = 10đ * tile
    // TL fills the gap between target and (TNKQ + DS)
    $num_units = count($units);
    $tl_max_per_unit = 1.5; // Default max
    if ($num_units == 1) {
        $tl_max_per_unit = 4.0; // Single unit gets all TL points
    } elseif ($num_units == 2) {
        $tl_max_per_unit = 2.0; // 2 units share 4.0đ TL equally
    } elseif ($num_units == 3) {
        $tl_max_per_unit = 2.5;
    }
    
    // Calculate TL to achieve target proportion
    $tl_allocation_log = [];
    foreach ($units as $i => $u) {
        $target_total = $TOTAL_POINTS * $units[$i]['tile'];
        $current_allocated = $units[$i]['tnkq_pts'] + $units[$i]['ds_pts'];
        $units[$i]['tl_pts'] = $target_total - $current_allocated;
        
        $tl_allocation_log[] = "Unit $i ({$u['so_tiet']} tiết, tile=" . fnum($u['tile']) . "): Target=" . fnum($target_total) . ", TNKQ+DS=" . fnum($current_allocated) . ", TL_raw=" . fnum($units[$i]['tl_pts']);
        
        // Ensure non-negative
        if ($units[$i]['tl_pts'] < 0) $units[$i]['tl_pts'] = 0;
        
        // Round to 0.5
        $units[$i]['tl_pts'] = round05($units[$i]['tl_pts']);
        
        $tl_allocation_log[] = "  → After round05: TL=" . fnum($units[$i]['tl_pts']);
    }
    
    // Soft adjustments: enforce 0.5-max per unit
    $count_half = 0;
    foreach ($units as $i => $u) {
        $tl = $units[$i]['tl_pts'];
        // Minimum is 0.5đ if any TL assigned, max depends on num_units
        if ($tl > 0 && $tl < 0.5) {
            $units[$i]['tl_pts'] = 0.5;
        } elseif ($tl > $tl_max_per_unit) {
            $units[$i]['tl_pts'] = $tl_max_per_unit;
        }
        // Round to valid increments based on max
        $tl = $units[$i]['tl_pts'];
        if ($tl > 0) {
            if ($tl_max_per_unit >= 4.0) {
                $valid = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5, 2.75, 3.0, 3.25, 3.5, 3.75, 4.0];
            } elseif ($tl_max_per_unit >= 3.0) {
                $valid = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5, 2.75, 3.0, 3.25, 3.5];
            } elseif ($tl_max_per_unit >= 2.0) {
                $valid = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5];
            } else {
                $valid = [0.5, 0.75, 1.0, 1.25, 1.5];
            }
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
        } else {
            $units[$i]['tl_pts'] = 0;
        }
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
    $tl_allocation_log[] = "Before TL=4.0 enforcement: sum=" . fnum($sum_tl) . " (target=4.0)";
    $max_iterations = 20; // Prevent infinite loop
    $iteration = 0;
    while (abs($sum_tl - $TYPE_POINTS['TL']) > 0.001 && $iteration < $max_iterations) {
        $iteration++;
        if ($sum_tl < $TYPE_POINTS['TL']) {
            // Sort by so_tiet (DESCENDING) to add to larger units first, preserving proportion
            usort($units, function($a,$b){ 
                if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; 
                return $b['so_tiet'] <=> $a['so_tiet'];
            });
            // Find first unit that can be increased without exceeding max
            $increased = false;
            foreach ($units as $idx => $u) {
                if ($units[$idx]['tl_pts'] + 0.25 <= $tl_max_per_unit) {
                    $units[$idx]['tl_pts'] += 0.25;
                    $tl_allocation_log[] = "Iter $iteration: Increased Unit {$units[$idx]['idx']} ({$u['so_tiet']} tiết) by 0.25 (now " . fnum($units[$idx]['tl_pts']) . ")";
                    $increased = true;
                    break;
                }
            }
            if (!$increased) break; // Can't increase anymore, stop
        } else {
            usort($units, function($a,$b){ if ($a['so_tiet']==$b['so_tiet']) return $a['idx'] <=> $b['idx']; return $a['so_tiet'] <=> $b['so_tiet'];});
            $found = false;
            for ($i=0;$i<count($units);$i++){
                if ($units[$i]['tl_pts'] >= 0.5) {
                    $units[$i]['tl_pts'] -= 0.25;
                    $tl_allocation_log[] = "Iter $iteration: Decreased Unit {$units[$i]['idx']} by 0.25 (now " . fnum($units[$i]['tl_pts']) . ")";
                    $found = true;
                    break;
                }
            }
            if (!$found) break;
        }
        $sum_tl = array_sum(array_column($units,'tl_pts'));
    }
    $tl_allocation_log[] = "After TL=4.0 enforcement: sum=" . fnum($sum_tl) . " | Final TL: Unit 0=" . fnum($units[0]['tl_pts']) . ", Unit 1=" . fnum($units[1]['tl_pts'] ?? 0);
    usort($units, function($a,$b){ return $a['idx'] <=> $b['idx']; });

    // First, calculate TNKQ and DS allocations for each level
    $tnkq_allocations = [];
    $ds_allocations = [];
    
    foreach ($units as $i => $u) {
        // TNKQ allocation
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
            if ($remaining > 0) $alloc['NB'] += $remaining * $POINT_PER_TNKQ;
        }
        $tnkq_allocations[$i] = $alloc;
        
        // DS allocation
        $ds_alloc = ['NB'=>0, 'TH'=>0, 'VD'=>0];
        if (!empty($u['has_ds'])) {
            // Check if this unit has multiple DS questions
            if (!empty($u['ds_labels']) && count($u['ds_labels']) > 1) {
                // Unit has both Câu 1 and Câu 2
                // Câu 1: 1NB + 2TH + 1VD
                $ds_alloc['NB'] += 1 * $POINT_PER_DS_ITEM;
                $ds_alloc['TH'] += 2 * $POINT_PER_DS_ITEM;
                $ds_alloc['VD'] += 1 * $POINT_PER_DS_ITEM;
                // Câu 2: 2NB + 1TH + 1VD
                $ds_alloc['NB'] += 2 * $POINT_PER_DS_ITEM;
                $ds_alloc['TH'] += 1 * $POINT_PER_DS_ITEM;
                $ds_alloc['VD'] += 1 * $POINT_PER_DS_ITEM;
                // Total: 3NB + 3TH + 2VD = 0.75 + 0.75 + 0.50 = 2.0đ
            } else {
                // Single DS question (backward compatibility)
                // Câu 1: 1NB + 2TH + 1VD, Câu 2: 2NB + 1TH + 1VD
                if ($u['ds_label'] == 'Câu 1') {
                    $ds_alloc['NB'] = 1 * $POINT_PER_DS_ITEM;
                    $ds_alloc['TH'] = 2 * $POINT_PER_DS_ITEM;
                    $ds_alloc['VD'] = 1 * $POINT_PER_DS_ITEM;
                } else { // Câu 2
                    $ds_alloc['NB'] = 2 * $POINT_PER_DS_ITEM;
                    $ds_alloc['TH'] = 1 * $POINT_PER_DS_ITEM;
                    $ds_alloc['VD'] = 1 * $POINT_PER_DS_ITEM;
                }
            }
        }
        $ds_allocations[$i] = $ds_alloc;
    }
    
    // Calculate total points already allocated to each level from TNKQ + DS
    $total_nb_allocated = 0;
    $total_th_allocated = 0;
    $total_vd_allocated = 0;
    foreach ($units as $i => $u) {
        $total_nb_allocated += $tnkq_allocations[$i]['NB'] + $ds_allocations[$i]['NB'];
        $total_th_allocated += $tnkq_allocations[$i]['TH'] + $ds_allocations[$i]['TH'];
        $total_vd_allocated += $tnkq_allocations[$i]['VD'] + $ds_allocations[$i]['VD'];
    }
    
    // Calculate needed TL points for each level to reach target
    $target_nb = $TOTAL_POINTS * $TARGET['NB']; // 4.0
    $target_th = $TOTAL_POINTS * $TARGET['TH']; // 3.0
    $target_vd = $TOTAL_POINTS * $TARGET['VD']; // 3.0
    
    $needed_tl_nb = max(0, $target_nb - $total_nb_allocated);
    $needed_tl_th = max(0, $target_th - $total_th_allocated);
    $needed_tl_vd = max(0, $target_vd - $total_vd_allocated);
    
    // Total TL available
    $total_tl = array_sum(array_column($units, 'tl_pts'));
    
    // Calculate actual allocation totals needed
    $total_needed = $needed_tl_nb + $needed_tl_th + $needed_tl_vd;
    
    // NO SCALING - we will use needed values directly and adjust at the end
    // This preserves the exact ratio needed for each level
    
    // Allocate TL to each unit based on UNIT-SPECIFIC needs
    foreach ($units as $i => $u) {
        $tl = $units[$i]['tl_pts'];
        if ($tl <= 0) {
            $units[$i]['tl_nb'] = $units[$i]['tl_th'] = $units[$i]['tl_vd'] = 0;
            continue;
        }
        
        // Calculate this unit's target for each level (proportional to its tile)
        $unit_total_target = $TOTAL_POINTS * $units[$i]['tile'];
        $unit_target_nb = $unit_total_target * $TARGET['NB'];
        $unit_target_th = $unit_total_target * $TARGET['TH'];
        $unit_target_vd = $unit_total_target * $TARGET['VD'];
        
        // What this unit already has from TNKQ and DS
        $already_nb = $tnkq_allocations[$i]['NB'] + $ds_allocations[$i]['NB'];
        $already_th = $tnkq_allocations[$i]['TH'] + $ds_allocations[$i]['TH'];
        $already_vd = $tnkq_allocations[$i]['VD'] + $ds_allocations[$i]['VD'];
        
        // What this unit needs from TL (can be negative if already over)
        $unit_needed_nb = $unit_target_nb - $already_nb;
        $unit_needed_th = $unit_target_th - $already_th;
        $unit_needed_vd = $unit_target_vd - $already_vd;
        
        // Debug: store for logging
        if (!isset($allocation_debug)) $allocation_debug = [];
        $allocation_debug[$i] = [
            'unit' => $i,
            'target_total' => $unit_total_target,
            'already' => ['NB' => $already_nb, 'TH' => $already_th, 'VD' => $already_vd],
            'needed' => ['NB' => $unit_needed_nb, 'TH' => $unit_needed_th, 'VD' => $unit_needed_vd]
        ];
        
        // Clamp to non-negative (can't have negative TL allocation)
        $unit_nb = max(0, $unit_needed_nb);
        $unit_th = max(0, $unit_needed_th);
        $unit_vd = max(0, $unit_needed_vd);
        
        // Normalize to ensure sum = tl for this unit
        $sum = $unit_nb + $unit_th + $unit_vd;
        if ($sum > 0.01) {
            $unit_nb = ($unit_nb / $sum) * $tl;
            $unit_th = ($unit_th / $sum) * $tl;
            $unit_vd = ($unit_vd / $sum) * $tl;
        } else {
            // No specific needs (or all needs already met), distribute TL equally
            $unit_nb = $tl / 3.0;
            $unit_th = $tl / 3.0;
            $unit_vd = $tl / 3.0;
        }
        
        // Round to nearest 0.25
        $unit_nb = round($unit_nb / 0.25) * 0.25;
        $unit_th = round($unit_th / 0.25) * 0.25;
        $unit_vd = round($unit_vd / 0.25) * 0.25;
        
        // Adjust for rounding errors to maintain unit's total TL
        $sum_rounded = $unit_nb + $unit_th + $unit_vd;
        $diff = $tl - $sum_rounded;
        if (abs($diff) >= 0.25) {
            // Add/subtract difference to level with largest needed amount for THIS unit
            if ($unit_needed_th > $unit_needed_nb && $unit_needed_th > $unit_needed_vd) {
                $unit_th += $diff;
            } elseif ($unit_needed_nb > $unit_needed_vd) {
                $unit_nb += $diff;
            } else {
                $unit_vd += $diff;
            }
        }
        
        $units[$i]['tl_nb'] = max(0, $unit_nb);
        $units[$i]['tl_th'] = max(0, $unit_th);
        $units[$i]['tl_vd'] = max(0, $unit_vd);
    }
    
    // Store TNKQ and DS allocations for final calculation
    foreach ($units as $i => $u) {
        $units[$i]['_tnkq_lvl'] = $tnkq_allocations[$i];
        $units[$i]['_ds_lvl'] = $ds_allocations[$i];
    }

    // Level NB/TH/VD distribution per unit - CALCULATE FROM ACTUAL COMPONENTS
    foreach ($units as $i => $u) {
        // Use pre-calculated TNKQ allocations
        $tnkq_per_level = $units[$i]['_tnkq_lvl'];
        
        // DS contribution
        $ds_per_level = $units[$i]['_ds_lvl'];
        
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
        
        // Keep temp data for summary calculation later
        // unset($units[$i]['_tnkq_lvl']);
        // unset($units[$i]['_ds_lvl']);
    }

    // CREATE 3 TL QUESTIONS GLOBALLY
    // Strategy: Always create exactly 3 questions total
    
    $total_tl = array_sum(array_column($units, 'tl_pts'));
    
    // Initialize all units with empty subquestions
    foreach ($units as $i => $u) {
        $units[$i]['tl_subquestions'] = [];
    }
    
    // Skip if no TL allocation
    if ($total_tl < 1.0) {
        // Not enough TL to create 3 questions, skip
    } else {
        // Define 3-4 questions with points that sum to total_tl
        // STRATEGY: If total_tl > 3.0, create 4 questions to avoid split
        // Otherwise create 3 questions
        $num_questions = ($total_tl > 3.0) ? 4 : 3;
        
        $questions = [];
        if ($num_questions == 4) {
            // 4 questions: NB, TH, VD, TH (balanced)
            $q_avg = $total_tl / 4.0;
            $questions[] = ['pts' => round($q_avg * 0.9 / 0.25) * 0.25, 'focus' => 'NB'];  // Slightly smaller
            $questions[] = ['pts' => round($q_avg * 1.1 / 0.25) * 0.25, 'focus' => 'TH'];  // Slightly larger
            $questions[] = ['pts' => round($q_avg * 1.0 / 0.25) * 0.25, 'focus' => 'VD'];
            $questions[] = ['pts' => 0, 'focus' => 'TH']; // Will adjust
            
            // Fix last question to ensure sum = total_tl
            $sum_so_far = $questions[0]['pts'] + $questions[1]['pts'] + $questions[2]['pts'];
            $questions[3]['pts'] = $total_tl - $sum_so_far;
            
            // Ensure all questions >= 0.5đ and <= 2.0đ
            foreach ($questions as $idx => &$q) {
                if ($q['pts'] < 0.5) $q['pts'] = 0.5;
                if ($q['pts'] > 2.0) $q['pts'] = 2.0;
            }
            
            // Recalculate to match total
            $current_sum = array_sum(array_column($questions, 'pts'));
            if (abs($current_sum - $total_tl) > 0.01) {
                $questions[3]['pts'] += ($total_tl - $current_sum);
            }
        } else {
            // 3 questions: NB, VD, TH
            $target_q1 = round((min(1.0, $total_tl * 0.25)) / 0.25) * 0.25; // ~25% for NB
            $target_q2 = round((min(2.0, $total_tl * 0.35)) / 0.25) * 0.25; // ~35% for VD
            $target_q3 = $total_tl - $target_q1 - $target_q2; // remainder for TH
            
            // Ensure valid ranges and MAX 2.0đ per question
            if ($target_q1 < 0.5) $target_q1 = 0.5;
            if ($target_q1 > 2.0) $target_q1 = 2.0;
            
            if ($target_q2 < 0.75) $target_q2 = 0.75;
            if ($target_q2 > 2.0) $target_q2 = 2.0;
            
            if ($target_q3 < 0.75) $target_q3 = 0.75;
            if ($target_q3 > 2.0) $target_q3 = 2.0;
            
            // Recalculate to ensure sum = total_tl
            $sum_targets = $target_q1 + $target_q2 + $target_q3;
            if (abs($sum_targets - $total_tl) > 0.01) {
                $target_q3 = $total_tl - $target_q1 - $target_q2;
            }
            
            $questions[] = ['pts' => $target_q1, 'focus' => 'NB'];
            $questions[] = ['pts' => $target_q2, 'focus' => 'VD'];
            $questions[] = ['pts' => $target_q3, 'focus' => 'TH'];
        }
        
        // Get units with TL points, sorted by TL allocation
        $units_with_tl = [];
        foreach ($units as $i => $u) {
            if ($u['tl_pts'] > 0) {
                $units_with_tl[] = $i;
            }
        }
        usort($units_with_tl, function($a,$b) use ($units) {
            return $units[$b]['tl_pts'] <=> $units[$a]['tl_pts'];
        });
        
        if (!empty($units_with_tl)) {
            // Greedy assignment: assign each question to a unit with enough capacity
            $assigned_pts = array_fill(0, count($units), 0.0);
            
            // Add 'unit' field to questions
            foreach ($questions as &$q) {
                $q['unit'] = null;
            }
            
            // Sort questions by size (largest first for better fit)
            usort($questions, function($a,$b) { return $b['pts'] <=> $a['pts']; });
            
            foreach ($questions as $q_idx => $q) {
                $best_unit = null;
                $best_score = -1;
                
                foreach ($units_with_tl as $u_idx) {
                    $remaining = $units[$u_idx]['tl_pts'] - $assigned_pts[$u_idx];
                    if ($remaining >= $q['pts'] * 0.5) { // At least 50% of question points
                        // Score: prefer units with more remaining capacity
                        $score = $remaining;
                        // Bonus if unit has the focus level available
                        if (!empty($units[$u_idx]['levels'][$q['focus']])) {
                            $score += 10;
                        }
                        // Penalty for units with many questions already
                        $score -= count($units[$u_idx]['tl_subquestions']) * 5;
                        
                        if ($score > $best_score) {
                            $best_score = $score;
                            $best_unit = $u_idx;
                        }
                    }
                }
                
                // Fallback: if no unit fits, assign to unit with most remaining capacity
                if ($best_unit === null && !empty($units_with_tl)) {
                    $max_remaining = -1;
                    foreach ($units_with_tl as $u_idx) {
                        $remaining = $units[$u_idx]['tl_pts'] - $assigned_pts[$u_idx];
                        if ($remaining > $max_remaining) {
                            $max_remaining = $remaining;
                            $best_unit = $u_idx;
                        }
                    }
                }
                
                if ($best_unit !== null) {
                    $questions[$q_idx]['unit'] = $best_unit;
                    $assigned_pts[$best_unit] += $q['pts'];
                }
            }
            
            // Now create actual subquestions for each unit
            foreach ($units_with_tl as $u_idx) {
                $unit_questions = [];
                foreach ($questions as $q) {
                    if ($q['unit'] === $u_idx) {
                        $unit_questions[] = $q;
                    }
                }
                
                if (!empty($unit_questions)) {
                    $unit_tl = $units[$u_idx]['tl_pts'];
                    $total_assigned = array_sum(array_column($unit_questions, 'pts'));
                    
                    // Debug info
                    $debug_info = "Unit $u_idx: TL={$unit_tl}đ, Questions=" . count($unit_questions);
                    
                    // Scale questions to match unit's actual TL
                    if ($total_assigned > 0 && abs($total_assigned - $unit_tl) > 0.01) {
                        $scale = $unit_tl / $total_assigned;
                        foreach ($unit_questions as &$uq) {
                            $old_pts = $uq['pts'];
                            $uq['pts'] = round($uq['pts'] * $scale / 0.25) * 0.25;
                            $debug_info .= " | Scaled {$old_pts}→{$uq['pts']}";
                        }
                        // Fix rounding errors
                        $sum = array_sum(array_column($unit_questions, 'pts'));
                        if (abs($sum - $unit_tl) >= 0.25) {
                            $unit_questions[count($unit_questions)-1]['pts'] += ($unit_tl - $sum);
                        }
                    }
                    
                    // Split any question > 2.0đ into 2 smaller questions
                    $split_count = 0;
                    $final_questions = [];
                    foreach ($unit_questions as $uq) {
                        if ($uq['pts'] > 2.01) {  // Use 2.01 to avoid floating point issues
                            $split_count++;
                            $debug_info .= " | SPLIT: {$uq['pts']}đ ({$uq['focus']})";
                            // Split into 2 questions
                            $q1_pts = round(($uq['pts'] * 0.5) / 0.25) * 0.25;
                            $q2_pts = round(($uq['pts'] - $q1_pts) / 0.25) * 0.25;
                            
                            // Ensure both >= 0.5
                            if ($q1_pts < 0.5) $q1_pts = 0.5;
                            if ($q2_pts < 0.5) $q2_pts = 0.5;
                            
                            // Adjust if sum doesn't match
                            $sum_split = $q1_pts + $q2_pts;
                            if (abs($sum_split - $uq['pts']) >= 0.25) {
                                $q2_pts = $uq['pts'] - $q1_pts;
                                if ($q2_pts < 0.5) {
                                    $q1_pts = $uq['pts'] - 0.5;
                                    $q2_pts = 0.5;
                                }
                            }
                            
                            $debug_info .= " → {$q1_pts}+{$q2_pts}";
                            $final_questions[] = ['pts' => $q1_pts, 'focus' => $uq['focus']];
                            $final_questions[] = ['pts' => $q2_pts, 'focus' => $uq['focus']];
                        } else {
                            $final_questions[] = $uq;
                        }
                    }
                    
                    // LIMIT: Maximum 4 TL questions total
                    // If after split we have >4 questions, merge smaller ones
                    if (count($final_questions) > 4) {
                        // Sort by points, merge smallest pairs
                        usort($final_questions, function($a, $b) {
                            return $a['pts'] <=> $b['pts'];
                        });
                        
                        while (count($final_questions) > 4 && count($final_questions) >= 2) {
                            // Merge two smallest questions
                            $q1 = array_shift($final_questions);
                            $q2 = array_shift($final_questions);
                            $merged_pts = $q1['pts'] + $q2['pts'];
                            // Use focus from larger question
                            $merged_focus = ($q1['pts'] > $q2['pts']) ? $q1['focus'] : $q2['focus'];
                            array_unshift($final_questions, ['pts' => $merged_pts, 'focus' => $merged_focus]);
                            $debug_info .= " | MERGE: {$q1['pts']}+{$q2['pts']}→{$merged_pts}";
                        }
                    }
                    
                    // RESCALE after split to ensure sum = target TL
                    if (!empty($final_questions)) {
                        $final_sum = array_sum(array_column($final_questions, 'pts'));
                        if (abs($final_sum - $unit_tl) > 0.01) {
                            $scale = $unit_tl / $final_sum;
                            $debug_info .= " | RESCALE: {$final_sum}đ → {$unit_tl}đ (×" . round($scale, 3) . ")";
                            foreach ($final_questions as &$fq) {
                                $fq['pts'] = round($fq['pts'] * $scale / 0.25) * 0.25;
                            }
                            // Fix final rounding error
                            $final_sum = array_sum(array_column($final_questions, 'pts'));
                            if (abs($final_sum - $unit_tl) >= 0.25) {
                                $final_questions[count($final_questions)-1]['pts'] += ($unit_tl - $final_sum);
                            }
                        }
                    }
                    
                    // Store debug info temporarily
                    $units[$u_idx]['_debug'] = $debug_info;
                    
                    // Create subquestions
                    foreach ($final_questions as $uq) {
                        $q_pts = $uq['pts'];
                        $focus = $uq['focus'];
                        
                        // Distribute across NB/TH/VD based on unit's ratios, boosting focus
                        $total_unit_tl = $units[$u_idx]['tl_pts'];
                        $ratio_nb = $total_unit_tl > 0 ? ($units[$u_idx]['tl_nb'] / $total_unit_tl) : 0.33;
                        $ratio_th = $total_unit_tl > 0 ? ($units[$u_idx]['tl_th'] / $total_unit_tl) : 0.33;
                        $ratio_vd = $total_unit_tl > 0 ? ($units[$u_idx]['tl_vd'] / $total_unit_tl) : 0.34;
                        
                        // Boost focus level
                        if ($focus == 'NB') $ratio_nb = max($ratio_nb, 0.6);
                        elseif ($focus == 'TH') $ratio_th = max($ratio_th, 0.6);
                        elseif ($focus == 'VD') $ratio_vd = max($ratio_vd, 0.6);
                        
                        // Normalize
                        $sum_ratio = $ratio_nb + $ratio_th + $ratio_vd;
                        if ($sum_ratio > 0) {
                            $ratio_nb /= $sum_ratio;
                            $ratio_th /= $sum_ratio;
                            $ratio_vd /= $sum_ratio;
                        }
                        
                        $q_nb = round($q_pts * $ratio_nb / 0.25) * 0.25;
                        $q_th = round($q_pts * $ratio_th / 0.25) * 0.25;
                        $q_vd = $q_pts - $q_nb - $q_th;
                        
                        $units[$u_idx]['tl_subquestions'][] = [
                            'pts' => $q_pts,
                            'nb' => max(0, $q_nb),
                            'th' => max(0, $q_th),
                            'vd' => max(0, $q_vd),
                            'focus' => $focus
                        ];
                    }
                }
            }
        }
    }

    // Totals for summary
    $tot_tnkq_q = array_sum(array_column($units,'tnkq_q'));
    $tot_tnkq_pts = array_sum(array_column($units,'tnkq_pts'));
    $tot_ds_items = array_sum(array_column($units,'ds_items'));
    $tot_ds_pts = array_sum(array_column($units,'ds_pts'));
    $tot_tl_pts = array_sum(array_column($units,'tl_pts'));
    // Count total TL questions (each unit may have multiple subquestions)
    $tot_tl_questions = 0;
    foreach ($units as $u) {
        $tot_tl_questions += count($u['tl_subquestions'] ?? []);
    }
    $tot_nb = $tot_th = $tot_vd = 0.0;
    foreach ($units as $u) {
        $tot_nb += $u['lvl']['NB'];
        $tot_th += $u['lvl']['TH'];
        $tot_vd += $u['lvl']['VD'];
    }
    
    // FINAL ADJUSTMENT: Ensure totals match target as closely as possible
    // Iteratively transfer from levels with excess to levels with deficit
    $target_nb = $TOTAL_POINTS * $TARGET['NB'];
    $target_th = $TOTAL_POINTS * $TARGET['TH'];
    $target_vd = $TOTAL_POINTS * $TARGET['VD'];
    
    $adjustment_log = [];
    $max_iterations = 100;
    $iteration = 0;
    $consecutive_failures = 0;
    
    while ($iteration < $max_iterations) {
        $iteration++;
        
        // Calculate current differences
        $diff_nb = $target_nb - $tot_nb;
        $diff_th = $target_th - $tot_th;
        $diff_vd = $target_vd - $tot_vd;
        
        // Stop if all differences are small enough
        if (abs($diff_nb) < 0.2 && abs($diff_th) < 0.2 && abs($diff_vd) < 0.2) {
            $adjustment_log[] = "Iteration $iteration: Converged (NB=" . fnum($tot_nb) . ", TH=" . fnum($tot_th) . ", VD=" . fnum($tot_vd) . ")";
            break;
        }
        
        // Stop if differences are very small (relaxed threshold for stuck cases)
        if (abs($diff_nb) < 0.35 && abs($diff_th) < 0.35 && abs($diff_vd) < 0.35 && $consecutive_failures >= 1) {
            $adjustment_log[] = "Iteration $iteration: Near-converged (NB=" . fnum($tot_nb) . ", TH=" . fnum($tot_th) . ", VD=" . fnum($tot_vd) . ") | Diffs: NB=" . fnum($diff_nb) . " TH=" . fnum($diff_th) . " VD=" . fnum($diff_vd);
            break;
        }
        
        // Find level with largest excess
        $excess_level = null;
        $excess_amount = 0;
        if ($diff_nb < -0.2) {
            $excess_level = 'NB';
            $excess_amount = abs($diff_nb);
        }
        if ($diff_th < -0.2 && abs($diff_th) > $excess_amount) {
            $excess_level = 'TH';
            $excess_amount = abs($diff_th);
        }
        if ($diff_vd < -0.2 && abs($diff_vd) > $excess_amount) {
            $excess_level = 'VD';
            $excess_amount = abs($diff_vd);
        }
        
        if (!$excess_level) {
            $adjustment_log[] = "Iter $iteration: No excess level found (diffs: NB=" . fnum($diff_nb) . ", TH=" . fnum($diff_th) . ", VD=" . fnum($diff_vd) . ")";
        }
        
        // Find level with largest deficit
        $deficit_level = null;
        $deficit_amount = 0;
        if ($diff_nb > 0.2) {
            $deficit_level = 'NB';
            $deficit_amount = $diff_nb;
        }
        if ($diff_th > 0.2 && $diff_th > $deficit_amount) {
            $deficit_level = 'TH';
            $deficit_amount = $diff_th;
        }
        if ($diff_vd > 0.2 && $diff_vd > $deficit_amount) {
            $deficit_level = 'VD';
            $deficit_amount = $diff_vd;
        }
        
        if (!$deficit_level && $excess_level) {
            $adjustment_log[] = "  No deficit level found";
        }
        
        // If we have both excess and deficit, transfer between them
        if ($excess_level && $deficit_level) {
            $adjustment_log[] = "Iter $iteration: Excess=$excess_level (" . fnum($excess_amount) . "), Deficit=$deficit_level (" . fnum($deficit_amount) . ")";
            $transferred = false;
            $excess_key = 'tl_' . strtolower($excess_level);
            $deficit_key = 'tl_' . strtolower($deficit_level);
            
            // Strategy 1: Find unit with excess that can transfer (same-unit)
            foreach ($units as $i => $u) {
                if ($units[$i][$excess_key] >= 0.25 && $units[$i]['tl_pts'] > 0) {
                    
                    // CHECK: Don't let any level exceed 2.5đ or the unit's total TL
                    $after_transfer = $units[$i][$deficit_key] + 0.25;
                    if ($after_transfer > 2.5) {
                        $adjustment_log[] = "  Strategy 1: Unit $i skipped ({$deficit_level} would be {$after_transfer}đ > 2.5đ limit)";
                        continue;
                    }
                    if ($after_transfer > $units[$i]['tl_pts']) {
                        $adjustment_log[] = "  Strategy 1: Unit $i skipped ({$deficit_level} would be {$after_transfer}đ > tl_pts=" . fnum($units[$i]['tl_pts']) . ")";
                        continue;
                    }
                    
                    // Transfer 0.25 from excess to deficit
                    $units[$i][$excess_key] -= 0.25;
                    $units[$i][$deficit_key] += 0.25;
                    $units[$i]['lvl'][$excess_level] -= 0.25;
                    $units[$i]['lvl'][$deficit_level] += 0.25;
                    
                    // Update totals
                    if ($excess_level == 'NB') $tot_nb -= 0.25;
                    elseif ($excess_level == 'TH') $tot_th -= 0.25;
                    else $tot_vd -= 0.25;
                    
                    if ($deficit_level == 'NB') $tot_nb += 0.25;
                    elseif ($deficit_level == 'TH') $tot_th += 0.25;
                    else $tot_vd += 0.25;
                    
                    $adjustment_log[] = "  Strategy 1: Unit $i transferred 0.25đ from $excess_level to $deficit_level";
                    $transferred = true;
                    break;
                }
            }
            
            // Strategy 2: Try 0.5 transfer if 0.25 didn't work (same-unit)
            if (!$transferred) {
                foreach ($units as $i => $u) {
                    if ($units[$i][$excess_key] >= 0.5 && $units[$i]['tl_pts'] > 0) {
                        
                        // CHECK: Don't let any level exceed 2.5đ or the unit's total TL
                        $after_transfer = $units[$i][$deficit_key] + 0.5;
                        if ($after_transfer > 2.5) {
                            $adjustment_log[] = "  Strategy 2: Unit $i skipped ({$deficit_level} would be {$after_transfer}đ > 2.5đ limit)";
                            continue;
                        }
                        if ($after_transfer > $units[$i]['tl_pts']) {
                            $adjustment_log[] = "  Strategy 2: Unit $i skipped ({$deficit_level} would be {$after_transfer}đ > tl_pts=" . fnum($units[$i]['tl_pts']) . ")";
                            continue;
                        }
                        
                        $units[$i][$excess_key] -= 0.5;
                        $units[$i][$deficit_key] += 0.5;
                        $units[$i]['lvl'][$excess_level] -= 0.5;
                        $units[$i]['lvl'][$deficit_level] += 0.5;
                        
                        if ($excess_level == 'NB') $tot_nb -= 0.5;
                        elseif ($excess_level == 'TH') $tot_th -= 0.5;
                        else $tot_vd -= 0.5;
                        
                        if ($deficit_level == 'NB') $tot_nb += 0.5;
                        elseif ($deficit_level == 'TH') $tot_th += 0.5;
                        else $tot_vd += 0.5;
                        
                        $adjustment_log[] = "  Strategy 2: Unit $i transferred 0.5đ from $excess_level to $deficit_level";
                        $transferred = true;
                        break;
                    }
                }
            }
            
            // Strategy 3: Cross-unit transfer with question reassignment
            if (!$transferred) {
                // Find unit with excess and another with deficit available
                $excess_unit = null;
                $deficit_unit = null;
                
                foreach ($units as $i => $u) {
                    if ($excess_unit === null && $units[$i][$excess_key] >= 0.25) {
                        $excess_unit = $i;
                    }
                    // For Strategy 3, don't check levels since questions will be reassigned
                    if ($deficit_unit === null && $units[$i]['tl_pts'] > 0 && $i !== $excess_unit) {
                        // CHECK: Don't pick a unit that would exceed 3.5đ OR its total TL
                        $after_transfer = $units[$i][$deficit_key] + 0.25;
                        if ($after_transfer > 3.5) {
                            $adjustment_log[] = "  Strategy 3: Unit $i skipped as deficit ({$deficit_level} would be {$after_transfer}đ > 3.5đ)";
                            continue;
                        }
                        if ($after_transfer > $units[$i]['tl_pts']) {
                            $adjustment_log[] = "  Strategy 3: Unit $i skipped as deficit ({$deficit_level} would be {$after_transfer}đ > tl_pts=" . fnum($units[$i]['tl_pts']) . ")";
                            continue;
                        }
                        $deficit_unit = $i;
                    }
                    if ($excess_unit !== null && $deficit_unit !== null) break;
                }
                
                if ($excess_unit !== null && $deficit_unit !== null && $excess_unit != $deficit_unit) {
                    // Transfer 0.25 from excess to deficit
                    $units[$excess_unit][$excess_key] -= 0.25;
                    $units[$excess_unit]['lvl'][$excess_level] -= 0.25;
                    
                    $units[$deficit_unit][$deficit_key] += 0.25;
                    $units[$deficit_unit]['lvl'][$deficit_level] += 0.25;
                    
                    // Update totals
                    if ($excess_level == 'NB') $tot_nb -= 0.25;
                    elseif ($excess_level == 'TH') $tot_th -= 0.25;
                    else $tot_vd -= 0.25;
                    
                    if ($deficit_level == 'NB') $tot_nb += 0.25;
                    elseif ($deficit_level == 'TH') $tot_th += 0.25;
                    else $tot_vd += 0.25;
                    
                    $adjustment_log[] = "  Strategy 3: Unit $excess_unit (-$excess_level 0.25) → Unit $deficit_unit (+$deficit_level 0.25) | Questions will be reassigned later";
                    $transferred = true;
                }
            }
            
            if (!$transferred) {
                $adjustment_log[] = "  Failed to transfer (no valid unit found)";
                $consecutive_failures++;
                if ($consecutive_failures >= 2) {
                    $adjustment_log[] = "  Stopping: Cannot make further progress (consecutive failures=$consecutive_failures)";
                    break; // Can't transfer anymore
                }
            } else {
                $consecutive_failures = 0; // Reset on successful transfer
            }
        } else {
            $adjustment_log[] = "Iter $iteration: No clear excess/deficit pair (diffs: NB=" . fnum($diff_nb) . ", TH=" . fnum($diff_th) . ", VD=" . fnum($diff_vd) . ")";
            break; // No clear excess/deficit pair
        }
    }
    
    // SANITY CHECK: Ensure each unit's tl_nb+tl_th+tl_vd = tl_pts
    foreach ($units as $i => $u) {
        if ($u['tl_pts'] <= 0) continue;
        $sum_levels = $u['tl_nb'] + $u['tl_th'] + $u['tl_vd'];
        if (abs($sum_levels - $u['tl_pts']) > 0.01) {
            $adjustment_log[] = "⚠ Unit $i: Level sum=" . fnum($sum_levels) . " ≠ tl_pts=" . fnum($u['tl_pts']) . " | Rescaling";
            // Rescale proportionally
            if ($sum_levels > 0) {
                $scale = $u['tl_pts'] / $sum_levels;
                $old_nb = $u['tl_nb'];
                $old_th = $u['tl_th'];
                $old_vd = $u['tl_vd'];
                
                $units[$i]['tl_nb'] = round($u['tl_nb'] * $scale / 0.25) * 0.25;
                $units[$i]['tl_th'] = round($u['tl_th'] * $scale / 0.25) * 0.25;
                $units[$i]['tl_vd'] = $u['tl_pts'] - $units[$i]['tl_nb'] - $units[$i]['tl_th'];
                
                // Update lvl too
                $units[$i]['lvl']['NB'] += ($units[$i]['tl_nb'] - $old_nb);
                $units[$i]['lvl']['TH'] += ($units[$i]['tl_th'] - $old_th);
                $units[$i]['lvl']['VD'] += ($units[$i]['tl_vd'] - $old_vd);
                
                // Update global totals
                $tot_nb += ($units[$i]['tl_nb'] - $old_nb);
                $tot_th += ($units[$i]['tl_th'] - $old_th);
                $tot_vd += ($units[$i]['tl_vd'] - $old_vd);
                
                $adjustment_log[] = "  → Rescaled: NB " . fnum($old_nb) . "→" . fnum($units[$i]['tl_nb']) . ", TH " . fnum($old_th) . "→" . fnum($units[$i]['tl_th']) . ", VD " . fnum($old_vd) . "→" . fnum($units[$i]['tl_vd']);
            }
        }
    }
    
    // REDISTRIBUTE subquestions after adjustment to match new tl_nb/tl_th/tl_vd
    $redistribute_log = [];
    foreach ($units as $i => $u) {
        if (empty($u['tl_subquestions'])) continue;
        
        $unit_tl = $u['tl_pts'];
        if ($unit_tl <= 0) continue;
        
        $target_nb = $u['tl_nb'];
        $target_th = $u['tl_th'];
        $target_vd = $u['tl_vd'];
        
        $redistribute_log[] = "Unit $i: Redistributing " . count($u['tl_subquestions']) . " questions | Targets: NB=" . fnum($target_nb) . " TH=" . fnum($target_th) . " VD=" . fnum($target_vd);
        
        // REASSIGN FOCUS if needed
        // Count current focus distribution
        $focus_counts = ['NB' => 0, 'TH' => 0, 'VD' => 0];
        $focus_pts = ['NB' => 0, 'TH' => 0, 'VD' => 0];
        foreach ($u['tl_subquestions'] as $sq) {
            $f = $sq['focus'] ?? 'VD';
            $focus_counts[$f]++;
            $focus_pts[$f] += $sq['pts'];
        }
        
        // Calculate needed changes
        // If unit has many VD-focus questions but target VD is low, reassign some
        $need_reassign = [];
        
        // TH: If target is 0 but we need TH globally, reassign from VD
        if ($target_th == 0 && $focus_counts['VD'] >= 2) {
            // Check if we lost VD points (questions still VD but target is low)
            if ($focus_pts['VD'] > $target_vd + 0.5) {
                $need_reassign['TH'] = true;
            }
        }
        
        // NB: Similar logic
        if ($target_nb == 0 && $focus_counts['VD'] >= 2) {
            if ($focus_pts['VD'] > $target_vd + 0.5) {
                $need_reassign['NB'] = true;
            }
        }
        
        // Standard logic: if target > 0 but focus_pts is too low
        if ($target_th > 0.25 && $focus_pts['TH'] < $target_th - 0.25) $need_reassign['TH'] = true;
        if ($target_nb > 0.25 && $focus_pts['NB'] < $target_nb - 0.25) $need_reassign['NB'] = true;
        
        // Reassign from VD to TH/NB if needed, but KEEP at least 1 VD-focus
        if (!empty($need_reassign) && $focus_counts['VD'] > 1) {
            $reassigned = 0;
            $max_reassign = $focus_counts['VD'] - 1; // Keep at least 1 VD
            
            foreach ($units[$i]['tl_subquestions'] as $sq_idx => $sq) {
                if ($sq['focus'] == 'VD' && !empty($need_reassign) && $reassigned < $max_reassign) {
                    // Priority: TH first, then NB
                    if (!empty($need_reassign['TH'])) {
                        $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'TH';
                        $redistribute_log[] = "  Reassigned Q$sq_idx from VD → TH (" . fnum($sq['pts']) . "đ)";
                        unset($need_reassign['TH']);
                        $reassigned++;
                    } elseif (!empty($need_reassign['NB'])) {
                        $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'NB';
                        $redistribute_log[] = "  Reassigned Q$sq_idx from VD → NB (" . fnum($sq['pts']) . "đ)";
                        unset($need_reassign['NB']);
                        $reassigned++;
                    }
                }
            }
        }
        
        // ADDITIONAL: Ensure at most 1 VD-focus question per unit (for cleaner display)
        $vd_focus_questions = [];
        foreach ($units[$i]['tl_subquestions'] as $sq_idx => $sq) {
            if ($units[$i]['tl_subquestions'][$sq_idx]['focus'] == 'VD') {
                $vd_focus_questions[] = $sq_idx;
            }
        }
        
        if (count($vd_focus_questions) > 1) {
            // Keep only the largest VD-focus question, reassign others
            $kept_vd = null;
            $max_pts = -1;
            foreach ($vd_focus_questions as $vd_idx) {
                if ($units[$i]['tl_subquestions'][$vd_idx]['pts'] > $max_pts) {
                    $max_pts = $units[$i]['tl_subquestions'][$vd_idx]['pts'];
                    $kept_vd = $vd_idx;
                }
            }
            
            // Reassign other VD questions to TH or NB (alternate)
            $reassign_to = 'TH';
            foreach ($vd_focus_questions as $vd_idx) {
                if ($vd_idx != $kept_vd) {
                    $units[$i]['tl_subquestions'][$vd_idx]['focus'] = $reassign_to;
                    $redistribute_log[] = "  Consolidate: Reassigned Q$vd_idx from VD → $reassign_to (" . fnum($units[$i]['tl_subquestions'][$vd_idx]['pts']) . "đ) [keep only 1 VD]";
                    $reassign_to = ($reassign_to == 'TH') ? 'NB' : 'TH'; // Alternate
                }
            }
        }
        
        // Recalculate each subquestion's distribution to hit EXACT targets
        // First pass: distribute proportionally based on question points
        $temp_nb = $temp_th = $temp_vd = 0;
        foreach ($u['tl_subquestions'] as $sq_idx => $sq) {
            $q_pts = $sq['pts'];
            $ratio = $unit_tl > 0 ? ($q_pts / $unit_tl) : (1.0 / count($u['tl_subquestions']));
            
            $q_nb = $target_nb * $ratio;
            $q_th = $target_th * $ratio;
            $q_vd = $target_vd * $ratio;
            
            // Round to 0.25
            $q_nb = round($q_nb / 0.25) * 0.25;
            $q_th = round($q_th / 0.25) * 0.25;
            $q_vd = round($q_vd / 0.25) * 0.25;
            
            $units[$i]['tl_subquestions'][$sq_idx]['nb'] = max(0, $q_nb);
            $units[$i]['tl_subquestions'][$sq_idx]['th'] = max(0, $q_th);
            $units[$i]['tl_subquestions'][$sq_idx]['vd'] = max(0, $q_vd);
            
            $temp_nb += $q_nb;
            $temp_th += $q_th;
            $temp_vd += $q_vd;
        }
        
        // Second pass: fix rounding errors to match exact targets
        $diff_nb = round(($target_nb - $temp_nb) / 0.25) * 0.25;
        $diff_th = round(($target_th - $temp_th) / 0.25) * 0.25;
        $diff_vd = round(($target_vd - $temp_vd) / 0.25) * 0.25;
        
        if (abs($diff_nb) >= 0.25 || abs($diff_th) >= 0.25 || abs($diff_vd) >= 0.25) {
            // Apply differences to first question (or distribute across all)
            if (count($u['tl_subquestions']) > 0) {
                $units[$i]['tl_subquestions'][0]['nb'] = max(0, $units[$i]['tl_subquestions'][0]['nb'] + $diff_nb);
                $units[$i]['tl_subquestions'][0]['th'] = max(0, $units[$i]['tl_subquestions'][0]['th'] + $diff_th);
                $units[$i]['tl_subquestions'][0]['vd'] = max(0, $units[$i]['tl_subquestions'][0]['vd'] + $diff_vd);
            }
        }
        
        // Third pass: Ensure each question has proper focus alignment
        // If unit target for a level is 0, don't force that level - reassign focus instead
        foreach ($u['tl_subquestions'] as $sq_idx => $sq) {
            $focus = $units[$i]['tl_subquestions'][$sq_idx]['focus'];
            $q_nb = $units[$i]['tl_subquestions'][$sq_idx]['nb'];
            $q_th = $units[$i]['tl_subquestions'][$sq_idx]['th'];
            $q_vd = $units[$i]['tl_subquestions'][$sq_idx]['vd'];
            
            // If focus level has 0 target AND 0 points, reassign focus to actual max level
            if ($focus == 'NB' && $target_nb == 0 && $q_nb == 0) {
                // Reassign to level with most points
                if ($q_th >= $q_vd && $q_th > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'TH';
                } elseif ($q_vd > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'VD';
                }
            } elseif ($focus == 'TH' && $target_th == 0 && $q_th == 0) {
                if ($q_nb >= $q_vd && $q_nb > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'NB';
                } elseif ($q_vd > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'VD';
                }
            } elseif ($focus == 'VD' && $target_vd == 0 && $q_vd == 0) {
                if ($q_th >= $q_nb && $q_th > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'TH';
                } elseif ($q_nb > 0) {
                    $units[$i]['tl_subquestions'][$sq_idx]['focus'] = 'NB';
                }
            } elseif ($focus == 'NB' && $q_nb == 0 && $target_nb > 0 && $sq['pts'] >= 0.5) {
                // Focus is NB, target needs NB, but question has 0 NB - steal from another level
                if ($q_vd >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['vd'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['nb'] = 0.25;
                } elseif ($q_th >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['th'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['nb'] = 0.25;
                }
            } elseif ($focus == 'TH' && $q_th == 0 && $target_th > 0 && $sq['pts'] >= 0.5) {
                if ($q_vd >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['vd'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['th'] = 0.25;
                } elseif ($q_nb >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['nb'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['th'] = 0.25;
                }
            } elseif ($focus == 'VD' && $q_vd == 0 && $target_vd > 0 && $sq['pts'] >= 0.5) {
                if ($q_th >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['th'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['vd'] = 0.25;
                } elseif ($q_nb >= 0.5) {
                    $units[$i]['tl_subquestions'][$sq_idx]['nb'] -= 0.25;
                    $units[$i]['tl_subquestions'][$sq_idx]['vd'] = 0.25;
                }
            }
        }
        
        // Log the changes
        foreach ($u['tl_subquestions'] as $sq_idx => $sq) {
            $focus = $units[$i]['tl_subquestions'][$sq_idx]['focus'];
            $q_nb = $units[$i]['tl_subquestions'][$sq_idx]['nb'];
            $q_th = $units[$i]['tl_subquestions'][$sq_idx]['th'];
            $q_vd = $units[$i]['tl_subquestions'][$sq_idx]['vd'];
            $redistribute_log[] = "  Q$sq_idx ({$sq['pts']}đ, $focus): NB=" . fnum($q_nb) . " TH=" . fnum($q_th) . " VD=" . fnum($q_vd);
        }
        
        // Summary for this unit
        $final_nb = $final_th = $final_vd = 0;
        foreach ($units[$i]['tl_subquestions'] as $sq) {
            $final_nb += $sq['nb'];
            $final_th += $sq['th'];
            $final_vd += $sq['vd'];
        }
        
        // Store old values before update for lvl adjustment
        $old_tl_nb = $units[$i]['tl_nb'] ?? 0;
        $old_tl_th = $units[$i]['tl_th'] ?? 0;
        $old_tl_vd = $units[$i]['tl_vd'] ?? 0;
        
        // Update unit totals with final values after all reassignments
        $units[$i]['tl_nb'] = $final_nb;
        $units[$i]['tl_th'] = $final_th;
        $units[$i]['tl_vd'] = $final_vd;
        
        // Update lvl accordingly to reflect the new TL distribution
        $units[$i]['lvl']['NB'] += ($final_nb - $old_tl_nb);
        $units[$i]['lvl']['TH'] += ($final_th - $old_tl_th);
        $units[$i]['lvl']['VD'] += ($final_vd - $old_tl_vd);
        
        $redistribute_log[] = "  Unit $i Final: NB=" . fnum($final_nb) . " TH=" . fnum($final_th) . " VD=" . fnum($final_vd) . " (sum=" . fnum($final_nb + $final_th + $final_vd) . ")";
    }
    
    $tot_all = $tot_nb + $tot_th + $tot_vd;
    if ($tot_all <= 0) $tot_all = 1e-9;

    // Calculate total questions/items per level for summary
    // Separate main questions (c) and sub-items (ý)
    $tot_nb_main = 0;  // TNKQ + TL main questions
    $tot_nb_sub = 0;   // DS items + TL sub-items
    $tot_th_main = 0;
    $tot_th_sub = 0;
    $tot_vd_main = 0;
    $tot_vd_sub = 0;
    
    // Initialize totals for each section
    $tot_tnkq_nb = 0;  // TNKQ NB questions
    $tot_tnkq_th = 0;
    $tot_tnkq_vd = 0;
    $tot_ds_nb = 0;    // DS NB items (ý)
    $tot_ds_th = 0;
    $tot_ds_vd = 0;
    
    // Initialize TL counts tracker
    $tl_counts = [
        'NB' => ['main' => 0, 'sub' => 0],
        'TH' => ['main' => 0, 'sub' => 0],
        'VD' => ['main' => 0, 'sub' => 0]
    ];
    
    foreach ($units as $i => $u) {
        // TNKQ distribution - Calculate from actual points stored in _tnkq_lvl
        // Each TNKQ question = 0.5đ
        $tnkq_nb_count = 0;
        $tnkq_th_count = 0;
        $tnkq_vd_count = 0;
        
        // Calculate TNKQ counts from final values (lvl - DS - TL)
        // Use $units[$i] to get updated tl_nb/th/vd values after realignment
        $tnkq_nb_pts = $units[$i]['lvl']['NB'] - ($units[$i]['_ds_lvl']['NB'] ?? 0) - ($units[$i]['tl_nb'] ?? 0);
        $tnkq_th_pts = $units[$i]['lvl']['TH'] - ($units[$i]['_ds_lvl']['TH'] ?? 0) - ($units[$i]['tl_th'] ?? 0);
        $tnkq_vd_pts = $units[$i]['lvl']['VD'] - ($units[$i]['_ds_lvl']['VD'] ?? 0) - ($units[$i]['tl_vd'] ?? 0);
        
        $tnkq_nb_count = max(0, round($tnkq_nb_pts / 0.5));
        $tnkq_th_count = max(0, round($tnkq_th_pts / 0.5));
        $tnkq_vd_count = max(0, round($tnkq_vd_pts / 0.5));
        
        // Accumulate TNKQ totals
        $tot_tnkq_nb += $tnkq_nb_count;
        $tot_tnkq_th += $tnkq_th_count;
        $tot_tnkq_vd += $tnkq_vd_count;
        
        // Debug TNKQ calculation
        if (!isset($count_debug)) $count_debug = [];
        $count_debug[] = "<strong>Unit $i TNKQ Calculation:</strong>";
        $count_debug[] = "  lvl: NB=" . fnum($units[$i]['lvl']['NB']) . " TH=" . fnum($units[$i]['lvl']['TH']) . " VD=" . fnum($units[$i]['lvl']['VD']);
        $count_debug[] = "  _ds_lvl: NB=" . fnum($units[$i]['_ds_lvl']['NB'] ?? 0) . " TH=" . fnum($units[$i]['_ds_lvl']['TH'] ?? 0) . " VD=" . fnum($units[$i]['_ds_lvl']['VD'] ?? 0);
        $count_debug[] = "  tl: NB=" . fnum($units[$i]['tl_nb'] ?? 0) . " TH=" . fnum($units[$i]['tl_th'] ?? 0) . " VD=" . fnum($units[$i]['tl_vd'] ?? 0);
        $count_debug[] = "  TNKQ (lvl-DS-TL): NB=" . fnum($tnkq_nb_pts) . "đ (" . $tnkq_nb_count . "c), TH=" . fnum($tnkq_th_pts) . "đ (" . $tnkq_th_count . "c), VD=" . fnum($tnkq_vd_pts) . "đ (" . $tnkq_vd_count . "c)";
        
        // DS items
        $ds_nb_items = 0;
        $ds_th_items = 0;
        $ds_vd_items = 0;
        if (!empty($u['has_ds'])) {
            // Check if unit has multiple DS questions
            if (!empty($u['ds_labels']) && count($u['ds_labels']) > 1) {
                // Unit has both Câu 1 and Câu 2
                // Câu 1: 1NB + 2TH + 1VD
                $ds_nb_items += 1;
                $ds_th_items += 2;
                $ds_vd_items += 1;
                // Câu 2: 2NB + 1TH + 1VD
                $ds_nb_items += 2;
                $ds_th_items += 1;
                $ds_vd_items += 1;
                // Total: 3NB + 3TH + 2VD
            } else {
                // Single DS question (backward compatibility)
                // Câu 1: 1NB + 2TH + 1VD, Câu 2: 2NB + 1TH + 1VD
                if ($u['ds_label'] == 'Câu 1') {
                    $ds_nb_items = 1;
                    $ds_th_items = 2;
                    $ds_vd_items = 1;
                } else { // Câu 2
                    $ds_nb_items = 2;
                    $ds_th_items = 1;
                    $ds_vd_items = 1;
                }
            }
        }
        
        // Accumulate DS totals
        $tot_ds_nb += $ds_nb_items;
        $tot_ds_th += $ds_th_items;
        $tot_ds_vd += $ds_vd_items;
        
        $ds_label_display = !empty($u['ds_labels']) ? implode(' + ', $u['ds_labels']) : $u['ds_label'];
        $count_debug[] = "  DS ({$ds_label_display}): NB={$ds_nb_items}ý, TH={$ds_th_items}ý, VD={$ds_vd_items}ý";
        
        // TL questions - Separate main questions (by focus) and sub-items (other levels)
        // Main question (c): count questions with this focus
        // Sub-items (ý): count 0.25đ increments from other levels in each question
        $tl_nb_main = 0;  // câu chính focus=NB
        $tl_nb_sub = 0;   // ý phụ (NB points in non-NB questions)
        $tl_th_main = 0;
        $tl_th_sub = 0;
        $tl_vd_main = 0;
        $tl_vd_sub = 0;
        
        foreach ($units[$i]['tl_subquestions'] ?? [] as $sq_idx => $tq) {
            $focus = $tq['focus'] ?? '';
            $q_nb = $tq['nb'] ?? 0;
            $q_th = $tq['th'] ?? 0;
            $q_vd = $tq['vd'] ?? 0;
            
            $count_debug[] = "    Q{$sq_idx} (focus={$focus}): NB=" . fnum($q_nb) . " TH=" . fnum($q_th) . " VD=" . fnum($q_vd);
            
            // Count main question by focus
            if ($focus == 'NB') {
                $tl_nb_main++;
                $count_debug[] = "      → Counted as NB main (1c)";
            } elseif ($focus == 'TH') {
                $tl_th_main++;
                $count_debug[] = "      → Counted as TH main (1c)";
            } elseif ($focus == 'VD') {
                $tl_vd_main++;
                $count_debug[] = "      → Counted as VD main (1c)";
            }
            
            // Count sub-items from other levels (each 0.25đ = 1 item) - for debug only
            if ($focus != 'NB' && $q_nb > 0) {
                $nb_yi = round($q_nb / 0.25);
                $tl_nb_sub += $nb_yi;
                $count_debug[] = "      → NB sub: " . fnum($q_nb) . "đ = {$nb_yi}ý [not counted in summary ý]";
            }
            if ($focus != 'TH' && $q_th > 0) {
                $th_yi = round($q_th / 0.25);
                $tl_th_sub += $th_yi;
                $count_debug[] = "      → TH sub: " . fnum($q_th) . "đ = {$th_yi}ý [not counted in summary ý]";
            }
            if ($focus != 'VD' && $q_vd > 0) {
                $vd_yi = round($q_vd / 0.25);
                $tl_vd_sub += $vd_yi;
                $count_debug[] = "      → VD sub: " . fnum($q_vd) . "đ = {$vd_yi}ý [not counted in summary ý]";
            }
        }
        
        $count_debug[] = "  TL Summary: NB={$tl_nb_main}c, TH={$tl_th_main}c, VD={$tl_vd_main}c (sub-items not counted in ý)";
        
        // Accumulate to global totals
        $tl_counts['NB']['main'] += $tl_nb_main;
        $tl_counts['NB']['sub'] += $tl_nb_sub;
        $tl_counts['TH']['main'] += $tl_th_main;
        $tl_counts['TH']['sub'] += $tl_th_sub;
        $tl_counts['VD']['main'] += $tl_vd_main;
        $tl_counts['VD']['sub'] += $tl_vd_sub;
        
        // For unit row display, sum main+sub
        $tl_nb_qcount = $tl_nb_main + $tl_nb_sub;
        $tl_th_qcount = $tl_th_main + $tl_th_sub;
        $tl_vd_qcount = $tl_vd_main + $tl_vd_sub;
        
        // Sum totals (separate main and sub)
        // Main (c): TNKQ questions + TL main questions
        $tot_nb_main += $tnkq_nb_count + $tl_nb_main;
        $tot_th_main += $tnkq_th_count + $tl_th_main;
        $tot_vd_main += $tnkq_vd_count + $tl_vd_main;
        
        // Sub (ý): DS items ONLY (not TL sub-items)
        $tot_nb_sub += $ds_nb_items;
        $tot_th_sub += $ds_th_items;
        $tot_vd_sub += $ds_vd_items;
        
        $count_debug[] = "  <strong>Unit $i Total:</strong> NB=" . ($tnkq_nb_count + $tl_nb_main) . "c+" . $ds_nb_items . "ý, " .
                         "TH=" . ($tnkq_th_count + $tl_th_main) . "c+" . $ds_th_items . "ý, " .
                         "VD=" . ($tnkq_vd_count + $tl_vd_main) . "c+" . $ds_vd_items . "ý";
    }
    
    // Final totals debug
    if (!empty($count_debug)) {
        $count_debug[] = "<strong>TỔNG CỘT FINAL:</strong>";
        $count_debug[] = "  NB: {$tot_nb_main}c + {$tot_nb_sub}ý = " . fnum($tot_nb) . "đ";
        $count_debug[] = "  TH: {$tot_th_main}c + {$tot_th_sub}ý = " . fnum($tot_th) . "đ";
        $count_debug[] = "  VD: {$tot_vd_main}c + {$tot_vd_sub}ý = " . fnum($tot_vd) . "đ";
        
        // Calculate total ý
        $total_yi = $tot_nb_sub + $tot_th_sub + $tot_vd_sub;
        
        $count_debug[] = "<strong>Chi tiết 'ý':</strong> Tổng = {$total_yi}ý = CHỈ từ DS (Đúng/Sai)";
        $count_debug[] = "  → DS: 2 câu × 4 ý = 8 ý (Câu 1: 1NB+2TH+1VD, Câu 2: 2NB+1TH+1VD)";
        $count_debug[] = "  → TL sub-items KHÔNG tính vào 'ý', chỉ hiển thị ở cột TL";
    }
    
    // Helper function to format count display as "Xc+Yý" or "Xc" or "Yý"
    function format_count($main, $sub) {
        if ($main > 0 && $sub > 0) {
            return $main . "c+" . $sub . "ý";
        } elseif ($main > 0) {
            return $main . "c";
        } elseif ($sub > 0) {
            return $sub . "ý";
        } else {
            return "0";
        }
    }

    // ---------- Build HTML Table ----------
    ob_start();
    ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center matrix-result-table" id="matran-table">
        <thead>
          <tr class="table-header-main">
            <th rowspan="3" class="col-title">Chủ đề / Đơn vị (tiết)</th>
            <th colspan="6" class="header-tnkq">TNKQ</th>
            <th colspan="3" rowspan="2" class="header-tl">Tự luận</th>
            <th colspan="3" rowspan="2" class="header-total">Tổng</th>
            <th rowspan="3" class="col-percent">Tỉ lệ %</th>
          </tr>
          <tr class="table-header-type">
            <th colspan="3" class="header-mc">Nhiều lựa chọn</th>
            <th colspan="3" class="header-ds">Đúng/Sai</th>
          </tr>
          <tr class="table-header-sub">
            <th class="level-nb">Biết</th><th class="level-th">Hiểu</th><th class="level-vd">Vận dụng</th>
            <th class="level-nb">Biết</th><th class="level-th">Hiểu</th><th class="level-vd">Vận dụng</th>
            <th class="level-nb">Biết</th><th class="level-th">Hiểu</th><th class="level-vd">Vận dụng</th>
            <th class="level-nb-total">Biết</th><th class="level-th-total">Hiểu</th><th class="level-vd-total">Vận dụng</th>
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
                
                // TNKQ distribution - Use actual allocation from _tnkq_lvl
                // Calculate from final lvl after removing DS and TL
                $tnkq_nb_pts = $u['lvl']['NB'] - ($u['_ds_lvl']['NB'] ?? 0) - ($u['tl_nb'] ?? 0);
                $tnkq_th_pts = $u['lvl']['TH'] - ($u['_ds_lvl']['TH'] ?? 0) - ($u['tl_th'] ?? 0);
                $tnkq_vd_pts = $u['lvl']['VD'] - ($u['_ds_lvl']['VD'] ?? 0) - ($u['tl_vd'] ?? 0);
                
                $tn_nb = max(0, round($tnkq_nb_pts / 0.5));
                $tn_th = max(0, round($tnkq_th_pts / 0.5));
                $tn_vd = max(0, round($tnkq_vd_pts / 0.5));
                echo $tn_nb>0 ? "<td class='cell-tnkq'>{$tn_nb}c</td>" : "<td class='cell-empty'></td>";
                echo $tn_th>0 ? "<td class='cell-tnkq'>{$tn_th}c</td>" : "<td class='cell-empty'></td>";
                echo $tn_vd>0 ? "<td class='cell-tnkq'>{$tn_vd}c</td>" : "<td class='cell-empty'></td>";
                
                // DS
                $ds_nb = $ds_th = $ds_vd = '';
                $ds_nb_count = $ds_th_count = $ds_vd_count = 0;
                if (!empty($u['has_ds'])) {
                    // Check if unit has multiple DS questions
                    if (!empty($u['ds_labels']) && count($u['ds_labels']) > 1) {
                        // Unit has both Câu 1 and Câu 2
                        // Câu 1: 1NB + 2TH + 1VD, Câu 2: 2NB + 1TH + 1VD
                        // Total: 3NB + 3TH + 2VD
                        $ds_nb='3ý'; $ds_th='3ý'; $ds_vd='2ý';
                        $ds_nb_count=3; $ds_th_count=3; $ds_vd_count=2;
                    } else {
                        // Single DS question (backward compatibility)
                        // Câu 1: 1NB + 2TH + 1VD, Câu 2: 2NB + 1TH + 1VD
                        if ($u['ds_label'] == 'Câu 1') {
                            $ds_nb='1ý'; $ds_th='2ý'; $ds_vd='1ý';
                            $ds_nb_count=1; $ds_th_count=2; $ds_vd_count=1;
                        } else { // Câu 2
                            $ds_nb='2ý'; $ds_th='1ý'; $ds_vd='1ý';
                            $ds_nb_count=2; $ds_th_count=1; $ds_vd_count=1;
                        }
                    }
                }
                echo $ds_nb!=='' ? "<td class='cell-ds'>{$ds_nb}</td>" : "<td class='cell-empty'></td>";
                echo $ds_th!=='' ? "<td class='cell-ds'>{$ds_th}</td>" : "<td class='cell-empty'></td>";
                echo $ds_vd!=='' ? "<td class='cell-ds'>{$ds_vd}</td>" : "<td class='cell-empty'></td>";
                
                // TL - Show by level with question count
                $tl_nb = $u['tl_nb'] ?? 0;
                $tl_th = $u['tl_th'] ?? 0;
                $tl_vd = $u['tl_vd'] ?? 0;
                $tl_subquestions = $u['tl_subquestions'] ?? [];
                
                // Count questions by focus level only (each question counted once)
                $nb_qcount = 0;
                $th_qcount = 0;
                $vd_qcount = 0;
                foreach ($tl_subquestions as $tq) {
                    $focus = $tq['focus'] ?? '';
                    if ($focus == 'NB') $nb_qcount++;
                    elseif ($focus == 'TH') $th_qcount++;
                    elseif ($focus == 'VD') $vd_qcount++;
                }
                
                // Show NB
                if ($tl_nb > 0 && $nb_qcount > 0) {
                    echo "<td class='cell-tl'>{$nb_qcount}c(".fnum($tl_nb)."đ)</td>";
                } else {
                    echo "<td class='cell-empty'></td>";
                }
                
                // Show TH
                if ($tl_th > 0 && $th_qcount > 0) {
                    echo "<td class='cell-tl'>{$th_qcount}c(".fnum($tl_th)."đ)</td>";
                } else {
                    echo "<td class='cell-empty'></td>";
                }
                
                // Show VD
                if ($tl_vd > 0 && $vd_qcount > 0) {
                    echo "<td class='cell-tl'>{$vd_qcount}c(".fnum($tl_vd)."đ)</td>";
                } else {
                    echo "<td class='cell-empty'></td>";
                }
                
                // Total per level (with question/item counts)
                $sumNB = fnum($u['lvl']['NB']);
                $sumTH = fnum($u['lvl']['TH']);
                $sumVD = fnum($u['lvl']['VD']);
                
                // Count total questions/items per level
                // Separate main questions (c) and sub-items (ý)
                // For UNIT ROWS: c = TNKQ + TL main, ý = DS items ONLY
                // (TL sub-items will be counted in TỔNG CỘT only)
                
                // Main questions (c): TNKQ + TL main
                $nb_main = $tn_nb + $nb_qcount;
                $th_main = $tn_th + $th_qcount;
                $vd_main = $tn_vd + $vd_qcount;
                
                // Sub items (ý): DS items ONLY (not TL sub)
                $nb_sub = $ds_nb_count;
                $th_sub = $ds_th_count;
                $vd_sub = $ds_vd_count;
                
                // Display format: "điểm (Xc+Yý)" or "điểm (Xc)" or "điểm (Yý)"
                $nb_display = $sumNB . "đ";
                if ($nb_main > 0 || $nb_sub > 0) {
                    $nb_display .= "<br><small>(" . format_count($nb_main, $nb_sub) . ")</small>";
                }
                
                $th_display = $sumTH . "đ";
                if ($th_main > 0 || $th_sub > 0) {
                    $th_display .= "<br><small>(" . format_count($th_main, $th_sub) . ")</small>";
                }
                
                $vd_display = $sumVD . "đ";
                if ($vd_main > 0 || $vd_sub > 0) {
                    $vd_display .= "<br><small>(" . format_count($vd_main, $vd_sub) . ")</small>";
                }
                
                echo "<td class='cell-total-nb fw-bold'>{$nb_display}</td>";
                echo "<td class='cell-total-th fw-bold'>{$th_display}</td>";
                echo "<td class='cell-total-vd fw-bold'>{$vd_display}</td>";
                
                // Percent
                $pct = ($u['tnkq_pts']+$u['ds_pts']+$u['tl_pts']) / $TOTAL_POINTS * 100;
                echo "<td class='cell-percent fw-bold'>".round($pct,1)."%</td>";
                echo "</tr>";
            }
        }
        ?>
          <tr class="summary-count-row">
            <td class="text-start fw-bold ps-3">Tổng số câu</td>
            <td class="text-center"><?= $tot_tnkq_nb ?>c</td>
            <td class="text-center"><?= $tot_tnkq_th ?>c</td>
            <td class="text-center"><?= $tot_tnkq_vd ?>c</td>
            <td class="text-center"><?= $tot_ds_nb ?>ý</td>
            <td class="text-center"><?= $tot_ds_th ?>ý</td>
            <td class="text-center"><?= $tot_ds_vd ?>ý</td>
            <td class="text-center"><?= $tl_counts['NB']['main'] ?>c</td>
            <td class="text-center"><?= $tl_counts['TH']['main'] ?>c</td>
            <td class="text-center"><?= $tl_counts['VD']['main'] ?>c</td>
            <td class="text-center fw-bold"><?= format_count($tot_nb_main, $tot_nb_sub) ?></td>
            <td class="text-center fw-bold"><?= format_count($tot_th_main, $tot_th_sub) ?></td>
            <td class="text-center fw-bold"><?= format_count($tot_vd_main, $tot_vd_sub) ?></td>
            <td class="text-center"></td>
          </tr>
          <tr class="summary-points-row">
            <td class="text-start fw-bold ps-3">Tổng số điểm</td>
            <td colspan="3" class="text-center"><?= fnum($tot_tnkq_pts) ?>đ</td>
            <td colspan="3" class="text-center"><?= fnum($tot_ds_pts) ?>đ</td>
            <td colspan="3" class="text-center"><?= fnum($tot_tl_pts) ?>đ</td>
            <td class="text-center fw-bold"><?= fnum($tot_nb) ?>đ</td>
            <td class="text-center fw-bold"><?= fnum($tot_th) ?>đ</td>
            <td class="text-center fw-bold"><?= fnum($tot_vd) ?>đ</td>
            <td class="text-center"></td>
          </tr>
          <tr class="total-row">
            <td class="text-start fw-bold ps-3">Tỉ lệ</td>
            <td colspan="3" class="summary-tnkq fw-bold"><?= round($tot_tnkq_pts / $TOTAL_POINTS * 100) ?>%</td>
            <td colspan="3" class="summary-ds fw-bold"><?= round($tot_ds_pts / $TOTAL_POINTS * 100) ?>%</td>
            <td colspan="3" class="summary-tl fw-bold"><?= round($tot_tl_pts / $TOTAL_POINTS * 100) ?>%</td>
            <td class="summary-total-nb fw-bold"><?= round($tot_nb / $TOTAL_POINTS * 100) ?>%</td>
            <td class="summary-total-th fw-bold"><?= round($tot_th / $TOTAL_POINTS * 100) ?>%</td>
            <td class="summary-total-vd fw-bold"><?= round($tot_vd / $TOTAL_POINTS * 100) ?>%</td>
            <td class="summary-percent fw-bold">100%</td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Hidden data for specification matrix -->
    <script type="application/json" id="matrix-data">
    <?php 
    // Prepare data for specification matrix
    $matrix_data = [
        'topics' => [],
        'totals' => [
            'tnkq_q' => $tot_tnkq_q,
            'tnkq_pts' => $tot_tnkq_pts,
            'ds_items' => $tot_ds_items,
            'ds_pts' => $tot_ds_pts,
            'tl_questions' => $tot_tl_questions,
            'tl_pts' => $tot_tl_pts,
            'nb' => $tot_nb,
            'th' => $tot_th,
            'vd' => $tot_vd
        ]
    ];
    
    // Track question numbers - separate counter for each question type
    $tnkq_counter = 1;
    $ds_counter = 1;
    $tl_counter = 1;
    
    foreach ($grouped as $topic => $units_arr) {
        $topic_units = [];
        foreach ($units_arr as $u) {
            // TNKQ distribution - Same logic as main matrix display
            $tnkq_nb_pts = $u['lvl']['NB'] - ($u['_ds_lvl']['NB'] ?? 0) - ($u['tl_nb'] ?? 0);
            $tnkq_th_pts = $u['lvl']['TH'] - ($u['_ds_lvl']['TH'] ?? 0) - ($u['tl_th'] ?? 0);
            $tnkq_vd_pts = $u['lvl']['VD'] - ($u['_ds_lvl']['VD'] ?? 0) - ($u['tl_vd'] ?? 0);
            
            $tn_nb = max(0, round($tnkq_nb_pts / 0.5));
            $tn_th = max(0, round($tnkq_th_pts / 0.5));
            $tn_vd = max(0, round($tnkq_vd_pts / 0.5));
            
            // Track TNKQ question numbers
            $tnkq_nb_nums = [];
            $tnkq_th_nums = [];
            $tnkq_vd_nums = [];
            for ($i = 0; $i < $tn_nb; $i++) $tnkq_nb_nums[] = $tnkq_counter++;
            for ($i = 0; $i < $tn_th; $i++) $tnkq_th_nums[] = $tnkq_counter++;
            for ($i = 0; $i < $tn_vd; $i++) $tnkq_vd_nums[] = $tnkq_counter++;
            
            // DS distribution - Same logic as main matrix
            $ds_nb = $ds_th = $ds_vd = 0;
            $ds_nb_nums = [];
            $ds_th_nums = [];
            $ds_vd_nums = [];
            if (!empty($u['has_ds'])) {
                // Check if unit has multiple DS questions
                if (!empty($u['ds_labels']) && count($u['ds_labels']) > 1) {
                    // Unit has both Câu 1 and Câu 2
                    // Câu 1: 1NB + 2TH + 1VD
                    $ds_q_num_1 = $ds_counter++;
                    $ds_nb += 1; $ds_th += 2; $ds_vd += 1;
                    $ds_nb_nums[] = $ds_q_num_1 . 'a';
                    $ds_th_nums[] = $ds_q_num_1 . 'b';
                    $ds_th_nums[] = $ds_q_num_1 . 'c';
                    $ds_vd_nums[] = $ds_q_num_1 . 'd';
                    
                    // Câu 2: 2NB + 1TH + 1VD
                    $ds_q_num_2 = $ds_counter++;
                    $ds_nb += 2; $ds_th += 1; $ds_vd += 1;
                    $ds_nb_nums[] = $ds_q_num_2 . 'a';
                    $ds_nb_nums[] = $ds_q_num_2 . 'b';
                    $ds_th_nums[] = $ds_q_num_2 . 'c';
                    $ds_vd_nums[] = $ds_q_num_2 . 'd';
                    // Total: 3NB + 3TH + 2VD
                } else {
                    // Single DS question (backward compatibility)
                    $ds_q_num = $ds_counter++;
                    if ($u['ds_label'] == 'Câu 1') {
                        $ds_nb = 1; $ds_th = 2; $ds_vd = 1;
                        $ds_nb_nums[] = $ds_q_num . 'a';
                        $ds_th_nums[] = $ds_q_num . 'b';
                        $ds_th_nums[] = $ds_q_num . 'c';
                        $ds_vd_nums[] = $ds_q_num . 'd';
                    } else { // Câu 2
                        $ds_nb = 2; $ds_th = 1; $ds_vd = 1;
                        $ds_nb_nums[] = $ds_q_num . 'a';
                        $ds_nb_nums[] = $ds_q_num . 'b';
                        $ds_th_nums[] = $ds_q_num . 'c';
                        $ds_vd_nums[] = $ds_q_num . 'd';
                    }
                }
            }
            
            // TL question numbers - each subquestion gets its own number
            $tl_nb_nums = [];
            $tl_th_nums = [];
            $tl_vd_nums = [];
            if (!empty($u['tl_subquestions'])) {
                foreach ($u['tl_subquestions'] as $idx => $sq) {
                    // Each subquestion gets its own number
                    $sq_label = $tl_counter;
                    $tl_counter++;
                    
                    if ($sq['focus'] === 'NB') $tl_nb_nums[] = $sq_label;
                    else if ($sq['focus'] === 'TH') $tl_th_nums[] = $sq_label;
                    else if ($sq['focus'] === 'VD') $tl_vd_nums[] = $sq_label;
                }
            }
            
            $topic_units[] = [
                'title' => $u['title'],
                'tnkq' => [
                    'nb' => $tn_nb, 'th' => $tn_th, 'vd' => $tn_vd,
                    'nb_nums' => $tnkq_nb_nums,
                    'th_nums' => $tnkq_th_nums,
                    'vd_nums' => $tnkq_vd_nums
                ],
                'ds' => [
                    'nb' => $ds_nb, 'th' => $ds_th, 'vd' => $ds_vd,
                    'nb_nums' => $ds_nb_nums,
                    'th_nums' => $ds_th_nums,
                    'vd_nums' => $ds_vd_nums
                ],
                'tl' => [
                    'nb' => $u['tl_nb'] ?? 0, 
                    'th' => $u['tl_th'] ?? 0, 
                    'vd' => $u['tl_vd'] ?? 0,
                    'nb_nums' => $tl_nb_nums,
                    'th_nums' => $tl_th_nums,
                    'vd_nums' => $tl_vd_nums
                ],
                'tl_subquestions' => $u['tl_subquestions'] ?? [],
                'levels' => $u['levels']
            ];
        }
        $matrix_data['topics'][] = [
            'title' => $topic,
            'units' => $topic_units
        ];
    }
    
    echo json_encode($matrix_data, JSON_UNESCAPED_UNICODE);
    ?>
    </script>
    <?php
    
    // Debug output (controlled by $ENABLE_DEBUG)
    $debug_output = [];
    
    if ($ENABLE_DEBUG && !empty($tl_allocation_log)) {
        $debug_output[] = "<strong>TL Allocation (Period-Proportional):</strong>";
        foreach ($tl_allocation_log as $log) {
            $debug_output[] = $log;
        }
    }
    
    if ($ENABLE_DEBUG && !empty($allocation_debug)) {
        $debug_output[] = "<strong>Unit-Specific TL Needs:</strong>";
        foreach ($allocation_debug as $ad) {
            $debug_output[] = "Unit {$ad['unit']} (Target=" . fnum($ad['target_total']) . "): Already from TNKQ+DS: NB=" . fnum($ad['already']['NB']) . " TH=" . fnum($ad['already']['TH']) . " VD=" . fnum($ad['already']['VD']);
            $debug_output[] = "  → Needs from TL: NB=" . fnum($ad['needed']['NB']) . " TH=" . fnum($ad['needed']['TH']) . " VD=" . fnum($ad['needed']['VD']);
        }
    }
    
    if ($ENABLE_DEBUG) {
        foreach ($units as $i => $u) {
            if (!empty($u['_debug'])) {
                $debug_output[] = $u['_debug'];
            }
        }
    }
    if ($ENABLE_DEBUG && !empty($adjustment_log)) {
        $debug_output[] = "<strong>Iterative Adjustment:</strong>";
        foreach ($adjustment_log as $log) {
            $debug_output[] = $log;
        }
    }
    if ($ENABLE_DEBUG && !empty($redistribute_log)) {
        $debug_output[] = "<strong>Redistribute Subquestions:</strong>";
        foreach ($redistribute_log as $log) {
            $debug_output[] = $log;
        }
    }
    
    if ($ENABLE_DEBUG && !empty($count_debug)) {
        $debug_output[] = "<strong>Count Calculation (c/ý):</strong>";
        foreach ($count_debug as $log) {
            $debug_output[] = $log;
        }
    }
    
    $html = ob_get_clean();
    
    // Display debug info (only if enabled)
    if ($ENABLE_DEBUG && !empty($debug_output)) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;'>";
        echo "<strong>🔍 Debug Info (Auto-split):</strong><br>";
        foreach ($debug_output as $debug) {
            echo "<small style='font-family: monospace; color: #856404;'>" . htmlspecialchars($debug) . "</small><br>";
        }
        echo "</div>";
    }
    
    echo "<div style='text-align: right; padding: 10px; color: #666; font-size: 12px;'>";
    echo "Tạo lúc: " . date('H:i:s d/m/Y') . " | Version: 4.6 (Auto-split >2.0đ)";
    echo "</div>";
    echo $html;
    exit;
}

// ============= END AJAX HANDLER =============

// ============= WORD EXPORT HANDLER =============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'export_word') {
    $html_content = $_POST['html_content'] ?? '';
    $spec_matrix_html = $_POST['spec_matrix_html'] ?? '';
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
    echo 'th { font-weight: bold; }';
    echo '.topic-row td { text-align: left; }';
    echo '.total-row td { font-weight: bold; }';
    echo '.summary-count-row td { font-weight: bold; }';
    echo '.summary-points-row td { font-weight: bold; }';
    echo '.level-nb-total, .level-th-total, .level-vd-total { font-weight: bold; }';
    echo '.requirement-cell { text-align: left; font-size: 8pt; }';
    echo 'h2, h3 { text-align: center; font-weight: bold; }';
    echo 'h2 { font-size: 16pt; margin-bottom: 10pt; }';
    echo 'h3 { font-size: 13pt; margin-bottom: 15pt; }';
    echo '.page-break { page-break-before: always; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="Section1">';
    echo '<h2>MA TRẬN ĐỀ KIỂM TRA</h2>';
    echo '<h3>' . htmlspecialchars($exam_title) . '</h3>';
    echo $html_content;
    
    // Add specification matrix if available
    if (!empty($spec_matrix_html)) {
        echo '<div class="page-break"></div>';
        echo '<h2>MA TRẬN ĐẶC TẢ ĐỀ KIỂM TRA</h2>';
        echo '<h3>' . htmlspecialchars($exam_title) . '</h3>';
        echo $spec_matrix_html;
    }
    
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
            <div id="form-area">
                <!-- Filter Section -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-funnel"></i> Chọn Môn Học và Khối
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Môn học <span class="text-danger">*</span></label>
                                <select id="select-subject" class="form-select">
                                    <option value="">-- Chọn môn học --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Khối <span class="text-danger">*</span></label>
                                <select id="select-grade" class="form-select" disabled>
                                    <option value="">-- Chọn khối --</option>
                                    <option value="khoi6">Khối 6</option>
                                    <option value="khoi7">Khối 7</option>
                                    <option value="khoi8">Khối 8</option>
                                    <option value="khoi9">Khối 9</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Dữ liệu sẽ được tải tự động từ Bản Đặc Tả khi chọn khối.</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tên bộ đề (tùy chọn)</label>
                    <input id="exam-title" class="form-control" placeholder="Ma trận Tin 8 - Giữa kỳ">
                </div>

                <div id="topics-container"></div>

                <div class="d-flex gap-2 mt-3">
                    <button id="add-topic" class="btn btn-outline-primary btn-sm" disabled>+ Thêm chủ đề</button>
                    <button id="load-last-config" class="btn btn-outline-info btn-sm" title="Tải cấu hình lần trước">📂 Tải lần trước</button>
                    <button id="clear-saved-config" class="btn btn-outline-danger btn-sm" title="Xóa cấu hình đã lưu">🗑️ Xóa đã lưu</button>
                    <button id="generate" class="btn btn-primary ms-auto">Tạo ma trận</button>
                    <button id="reset-form" class="btn btn-secondary">Reset</button>
                </div>
                <div class="mt-2 small text-muted">
                    Chọn môn học và khối để tải dữ liệu, sau đó thêm chủ đề → chọn nội dung → thêm đơn vị → nhập số tiết.
                    <span class="badge bg-info" id="last-saved-indicator" style="display:none;"></span>
                </div>
            </div>

            <div id="result-area" class="hidden mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Kết quả Ma trận</h5>
                    <div>
                        <button id="back-edit" class="btn btn-outline-secondary btn-sm">Sửa / Quay lại</button>
                        <button id="export-word-btn" class="btn btn-primary btn-sm">📄 Xuất Word</button>
                        <button id="generate-exam-btn" class="btn btn-success btn-sm">📝 Tạo đề Kiểm tra</button>
                        <button id="print-btn" class="btn btn-success btn-sm">🖨️ In</button>
                    </div>
                </div>
                <div id="matran-output"></div>
            </div>
            
            <!-- Specification Matrix Area -->
            <div id="spec-area" class="hidden mt-4">
                <div id="spec-output"></div>
            </div>
        </div>
    </div>
</div>

<!-- templates -->
<template id="topic-tpl">
  <div class="topic border rounded p-3 mb-2 bg-white">
    <div class="d-flex gap-2 mb-2">
      <select class="form-select topic-select">
        <option value="">-- Chọn nội dung kiến thức --</option>
      </select>
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
      <select class="form-select unit-select">
        <option value="">-- Chọn đơn vị kiến thức --</option>
      </select>
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

/* Professional Matrix Table Styling - International Standard */
.matrix-result-table {
  border: 2px solid #3b82f6 !important;
  font-size: 0.9rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.matrix-result-table thead th {
  font-weight: 600;
  vertical-align: middle;
  border: 1px solid #cbd5e1 !important;
  padding: 12px 8px;
}

.matrix-result-table tbody td {
  border: 1px solid #e2e8f0 !important;
  padding: 8px;
}

/* Header styling - Lighter professional colors */
.table-header-main th {
  background: #594f95 !important;
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.table-header-type th {
  background: #594f95 !important;
  color: white;
  font-size: 0.9rem;
  font-weight: 600;
}

.table-header-sub th {
  background: #64748b !important;
  color: white;
  font-size: 0.85rem;
  font-weight: 600;
}

.header-mc {
  border-right: 2px solid rgba(255,255,255,0.3) !important;
}

.col-title {
  background: #3b82f6 !important;
  min-width: 200px;
}

.col-percent {
  background: #3b82f6 !important;
  min-width: 80px;
}

/* Level colors in sub-header - Clear distinction */
.level-nb {
  background: #10b981 !important;
  color: white;
  font-weight: 600;
}

.level-th {
  background: #f59e0b !important;
  color: white;
  font-weight: 600;
}

.level-vd {
  background: #ef4444 !important;
  color: white;
  font-weight: 600;
}

.level-nb-total, .level-th-total, .level-vd-total {
  background: #3b82f6 !important;
  color: #fff;
  font-weight: 700;
}

/* Topic row - Professional dark blue */
.topic-row td {
  background: #dbeafe  !important;
  color: #1e3a8a !important;
  font-size: 1rem;
  font-weight: 600;
  padding: 12px 10px !important;
  border: 1px solid #1e293b !important;
}

/* Unit row */
.unit-row td:first-child {
  background: #f8fafc;
  font-weight: 500;
  color: #0f172a;
  text-align: left !important;
}

/* Cell types with distinct professional colors */
.cell-tnkq {
  background: #d1fae5 !important;
  color: #065f46;
  font-weight: 600;
}

.cell-ds {
  background: #fef3c7 !important;
  color: #92400e;
  font-weight: 600;
}

.cell-tl {
  background: #fee2e2 !important;
  color: #991b1b;
  font-weight: 600;
}

.cell-total-nb {
  background: #bbf7d0 !important;
  color: #14532d;
  font-weight: 600;
}

.cell-total-th {
  background: #fed7aa !important;
  color: #7c2d12;
  font-weight: 600;
}

.cell-total-vd {
  background: #fecaca !important;
  color: #7f1d1d;
  font-weight: 600;
}

.cell-percent {
  background: #e0e7ff !important;
  color: #3730a3;
  font-weight: 600;
}

.cell-empty {
  background: #fafafa !important;
}

/* Summary rows - New professional styling */
.summary-count-row td {
  background: #dbeafe !important;
  color: #1e40af !important;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 10px 8px !important;
  border: 1px solid #93c5fd !important;
}

.summary-points-row td {
  background: #bfdbfe !important;
  color: #1e40af !important;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 10px 8px !important;
  border: 1px solid #60a5fa !important;
}

/* Total row (Tỉ lệ) at bottom */
.total-row td {
  background: #594f95 !important;
  color: white !important;
  font-size: 1rem;
  font-weight: 700;
  padding: 12px 10px !important;
  border: 1px solid #2563eb !important;
}

.summary-tnkq {
  background: #60a5fa !important;
}

.summary-ds {
  background: #60a5fa !important;
}

.summary-tl {
  background: #60a5fa !important;
}

.summary-total-nb, .summary-total-th, .summary-total-vd {
  background: #2563eb !important;
  font-size: 0.95rem;
}

.summary-percent {
  background: #2563eb !important;
  color: white !important;
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

  // Global data storage
  let knowledgeAssessmentData = null;
  let currentSubject = null;
  let currentGrade = null;

  // Load teacher subjects
  loadTeacherSubjects();

  function loadTeacherSubjects() {
    fetch('../admin/teacher_subjects.json')
      .then(r => r.json())
      .then(data => {
        const username = '<?php echo $username; ?>';
        const subjects = data[username] || [];
        const subjectSelect = document.getElementById('select-subject');
        
        fetch('../admin/subjects.json')
          .then(r => r.json())
          .then(subjectsArray => {
            subjects.forEach(subId => {
              const subject = subjectsArray.find(s => s.id == subId);
              if (subject) {
                const opt = document.createElement('option');
                opt.value = subject.id;
                opt.textContent = subject.name;
                subjectSelect.appendChild(opt);
              }
            });
            
            // If only one subject, auto-select and enable grade
            if (subjects.length === 1) {
              subjectSelect.value = subjects[0];
              currentSubject = subjects[0];
              document.getElementById('select-grade').disabled = false;
            }
          });
      });
  }

  // Enable grade when subject selected
  document.getElementById('select-subject').addEventListener('change', function() {
    const subject = this.value;
    const gradeSelect = document.getElementById('select-grade');
    
    if (subject) {
      gradeSelect.disabled = false;
      currentSubject = subject;
    } else {
      gradeSelect.disabled = true;
      gradeSelect.value = '';
      currentSubject = null;
      knowledgeAssessmentData = null;
      topicsContainer.innerHTML = '';
      document.getElementById('add-topic').disabled = true;
    }
  });

  // Auto-load data when grade selected
  document.getElementById('select-grade').addEventListener('change', function() {
    const grade = this.value;
    
    if (!grade || !currentSubject) {
      return;
    }
    
    currentGrade = grade;
    loadKnowledgeAssessment(currentSubject, grade);
  });

  function loadKnowledgeAssessment(subjectId, grade) {
    fetch('api/manage_knowledge_assessment.php?action=load&subject_id=' + subjectId + '&grade=' + grade, {
      credentials: 'include'
    })
      .then(r => r.json())
      .then(response => {
        if (!response.success) {
          alert('⚠️ Không tìm thấy bản đặc tả cho môn học và khối này!\nVui lòng tạo Bản Đặc Tả trước.');
          knowledgeAssessmentData = null;
          topicsContainer.innerHTML = '';
          document.getElementById('add-topic').disabled = true;
          return;
        }
        
        knowledgeAssessmentData = response.data;
        
        if (!knowledgeAssessmentData.items || knowledgeAssessmentData.items.length === 0) {
          alert('⚠️ Bản đặc tả không có nội dung nào!');
          document.getElementById('add-topic').disabled = true;
          return;
        }
        
        // Clear topics container
        topicsContainer.innerHTML = '';
        
        // Enable add topic button
        document.getElementById('add-topic').disabled = false;
        
        // Add first topic automatically
        addTopic();
        
        alert('✅ Đã tải ' + knowledgeAssessmentData.items.length + ' nội dung kiến thức!');
      })
      .catch(err => {
        alert('❌ Có lỗi khi tải dữ liệu!');
        document.getElementById('add-topic').disabled = true;
      });
  }

  function addTopic(title=''){
    const node = topicTpl.content.cloneNode(true);
    const el = node.querySelector('.topic');
    const topicSelect = el.querySelector('.topic-select');
    const unitsWrap = el.querySelector('.units');
    
    // Populate topic select with knowledge assessment items
    if (knowledgeAssessmentData && knowledgeAssessmentData.items) {
      knowledgeAssessmentData.items.forEach((item, index) => {
        const opt = document.createElement('option');
        opt.value = index;
        opt.textContent = item.content;
        topicSelect.appendChild(opt);
      });
    }
    
    // Handle topic selection change
    topicSelect.addEventListener('change', function() {
      // Clear units when topic changes
      unitsWrap.innerHTML = '';
      if (this.value !== '') {
        addUnit(unitsWrap, this.value);
      }
    });
    
    el.querySelector('.add-unit').addEventListener('click', ()=> {
      const selectedTopicIdx = topicSelect.value;
      if (selectedTopicIdx === '') {
        alert('Vui lòng chọn nội dung kiến thức trước!');
        return;
      }
      addUnit(unitsWrap, selectedTopicIdx);
    });
    
    el.querySelector('.remove-topic').addEventListener('click', ()=> el.remove());
    
    topicsContainer.appendChild(el);
    return el;
  }
  
  function addUnit(unitsWrap, topicIdx, uTitle='', tiet=1, levels={NB:true, TH:true, VD:true}){
    const node = unitTpl.content.cloneNode(true);
    const uel = node.querySelector('.unit');
    const unitSelect = uel.querySelector('.unit-select');
    
    // Populate unit select based on selected topic
    if (knowledgeAssessmentData && knowledgeAssessmentData.items && topicIdx !== '') {
      const topic = knowledgeAssessmentData.items[topicIdx];
      if (topic && topic.units) {
        topic.units.forEach((unit, unitIndex) => {
          const opt = document.createElement('option');
          opt.value = unitIndex;
          opt.textContent = unit.unit_name;
          opt.dataset.nb = unit.nhan_biet ? '1' : '0';
          opt.dataset.th = unit.thong_hieu ? '1' : '0';
          opt.dataset.vd = unit.van_dung ? '1' : '0';
          unitSelect.appendChild(opt);
        });
      }
    }
    
    // Handle unit selection change - auto check levels
    unitSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption && selectedOption.value !== '') {
        const nb = selectedOption.dataset.nb === '1';
        const th = selectedOption.dataset.th === '1';
        const vd = selectedOption.dataset.vd === '1';
        
        uel.querySelector('.level-nb').checked = nb;
        uel.querySelector('.level-th').checked = th;
        uel.querySelector('.level-vd').checked = vd;
      }
    });
    
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

  // Don't add empty topic by default - wait for data load

  document.getElementById('add-topic').addEventListener('click', ()=> addTopic());
  
  // ========== CONFIG SAVE/LOAD FUNCTIONS ==========
  
  function saveCurrentConfig() {
    const data = collectData();
    if (!data) return false;
    
    const config = {
      subject: currentSubject,
      grade: currentGrade,
      examTitle: document.getElementById('exam-title').value,
      topics: []
    };
    
    // Collect detailed topic/unit structure
    const topicsEls = [...topicsContainer.querySelectorAll('.topic')];
    for (const tEl of topicsEls) {
      const topicSelect = tEl.querySelector('.topic-select');
      const topicIdx = topicSelect.value;
      if (topicIdx === '') continue;
      
      const topicData = {
        topicIdx: topicIdx,
        topicTitle: topicSelect.options[topicSelect.selectedIndex].text,
        units: []
      };
      
      const unitEls = [...tEl.querySelectorAll('.unit')];
      for (const uEl of unitEls) {
        const unitSelect = uEl.querySelector('.unit-select');
        const unitIdx = unitSelect.value;
        if (unitIdx === '') continue;
        
        topicData.units.push({
          unitIdx: unitIdx,
          unitTitle: unitSelect.options[unitSelect.selectedIndex].text,
          tiet: parseFloat(uEl.querySelector('.unit-tiet').value) || 0,
          levels: {
            NB: uEl.querySelector('.level-nb').checked,
            TH: uEl.querySelector('.level-th').checked,
            VD: uEl.querySelector('.level-vd').checked
          }
        });
      }
      
      config.topics.push(topicData);
    }
    
    // Save to localStorage with timestamp
    config.timestamp = new Date().toISOString();
    localStorage.setItem('matrix_builder_last_config', JSON.stringify(config));
    
    // Update indicator
    updateLastSavedIndicator();
    return true;
  }
  
  function loadSavedConfig() {
    try {
      const savedConfig = localStorage.getItem('matrix_builder_last_config');
      if (!savedConfig) {
        alert('Không có cấu hình đã lưu!');
        return;
      }
      
      const config = JSON.parse(savedConfig);
      
      // Set subject and grade
      const subjectSelect = document.getElementById('select-subject');
      subjectSelect.value = config.subject || '';
      currentSubject = config.subject;
      
      const gradeSelect = document.getElementById('select-grade');
      gradeSelect.disabled = false;
      gradeSelect.value = config.grade || '';
      currentGrade = config.grade;
      
      // Set exam title
      document.getElementById('exam-title').value = config.examTitle || '';
      
      // Load knowledge assessment data first
      if (currentSubject && currentGrade) {
        fetch('api/manage_knowledge_assessment.php?action=load&subject_id=' + currentSubject + '&grade=' + currentGrade, {
          credentials: 'include'
        })
          .then(r => r.json())
          .then(response => {
            if (!response.success) {
              alert('⚠️ Không tìm thấy bản đặc tả!');
              return;
            }
            
            knowledgeAssessmentData = response.data;
            document.getElementById('add-topic').disabled = false;
            
            // Clear and rebuild topics
            topicsContainer.innerHTML = '';
            
            config.topics.forEach(topicData => {
              const topicEl = addTopic();
              const topicSelect = topicEl.querySelector('.topic-select');
              
              // Set topic selection
              topicSelect.value = topicData.topicIdx;
              
              // Trigger change to populate units
              const unitsWrap = topicEl.querySelector('.units');
              unitsWrap.innerHTML = '';
              
              // Add units
              topicData.units.forEach(unitData => {
                const unitEl = addUnit(unitsWrap, topicData.topicIdx, unitData.unitTitle, unitData.tiet, unitData.levels);
                const unitSelect = unitEl.querySelector('.unit-select');
                unitSelect.value = unitData.unitIdx;
                
                // Set levels
                unitEl.querySelector('.level-nb').checked = unitData.levels.NB;
                unitEl.querySelector('.level-th').checked = unitData.levels.TH;
                unitEl.querySelector('.level-vd').checked = unitData.levels.VD;
                
                // Set tiet
                unitEl.querySelector('.unit-tiet').value = unitData.tiet;
              });
            });
            
            alert('✅ Đã tải cấu hình lần trước!\nThời gian: ' + new Date(config.timestamp).toLocaleString('vi-VN'));
          })
          .catch(err => {
            alert('❌ Lỗi khi tải dữ liệu: ' + err.message);
          });
      }
    } catch (e) {
      console.error('Error loading config:', e);
      alert('❌ Lỗi khi tải cấu hình: ' + e.message);
    }
  }
  
  function updateLastSavedIndicator() {
    const savedConfig = localStorage.getItem('matrix_builder_last_config');
    const indicator = document.getElementById('last-saved-indicator');
    
    if (savedConfig) {
      try {
        const config = JSON.parse(savedConfig);
        const date = new Date(config.timestamp);
        indicator.textContent = '💾 Lần lưu: ' + date.toLocaleString('vi-VN', { 
          dateStyle: 'short', 
          timeStyle: 'short' 
        });
        indicator.style.display = 'inline-block';
      } catch (e) {
        indicator.style.display = 'none';
      }
    } else {
      indicator.style.display = 'none';
    }
  }
  
  // Initialize indicator on load
  updateLastSavedIndicator();
  
  // Auto-prompt to load last config if exists (only on fresh page load)
  window.addEventListener('load', () => {
    const savedConfig = localStorage.getItem('matrix_builder_last_config');
    if (savedConfig && topicsContainer.children.length === 0) {
      try {
        const config = JSON.parse(savedConfig);
        const date = new Date(config.timestamp);
        const timeDiff = Date.now() - date.getTime();
        const hoursDiff = timeDiff / (1000 * 60 * 60);
        
        // Only auto-prompt if saved within last 24 hours
        if (hoursDiff < 24) {
          setTimeout(() => {
            if (confirm('📂 Tìm thấy cấu hình đã lưu từ ' + date.toLocaleString('vi-VN') + 
                        '\n\nBạn có muốn tải lại không?')) {
              loadSavedConfig();
            }
          }, 500); // Small delay to ensure page is fully loaded
        }
      } catch (e) {
        console.error('Error checking saved config:', e);
      }
    }
  });
  
  // Load last config button
  document.getElementById('load-last-config').addEventListener('click', loadSavedConfig);
  
  // Clear saved config button
  document.getElementById('clear-saved-config').addEventListener('click', () => {
    const savedConfig = localStorage.getItem('matrix_builder_last_config');
    if (!savedConfig) {
      alert('ℹ️ Không có cấu hình đã lưu để xóa!');
      return;
    }
    
    if (confirm('⚠️ Bạn có chắc muốn xóa cấu hình đã lưu?\n\nHành động này không thể hoàn tác!')) {
      localStorage.removeItem('matrix_builder_last_config');
      updateLastSavedIndicator();
      alert('✅ Đã xóa cấu hình đã lưu!');
    }
  });
  
  // ========== END CONFIG SAVE/LOAD ==========

  function collectData(){
    const topicsEls = [...topicsContainer.querySelectorAll('.topic')];
    const topics = [];
    for (const tEl of topicsEls) {
      const topicSelect = tEl.querySelector('.topic-select');
      const topicIdx = topicSelect.value;
      
      if (topicIdx === '') {
        alert('Có chủ đề chưa được chọn!');
        return null;
      }
      
      const title = topicSelect.options[topicSelect.selectedIndex].text;
      const unitEls = [...tEl.querySelectorAll('.unit')];
      const units = [];
      
      for (const uEl of unitEls) {
        const unitSelect = uEl.querySelector('.unit-select');
        const unitIdx = unitSelect.value;
        
        if (unitIdx === '') {
          alert('Có đơn vị chưa được chọn!');
          return null;
        }
        
        const ut = unitSelect.options[unitSelect.selectedIndex].text;
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
    
    if (!data) {
      return; // Error already shown in collectData
    }
    
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
      
      // Auto-generate specification matrix
      try {
        const matrixDataEl = document.getElementById('matrix-data');
        if (matrixDataEl && knowledgeAssessmentData && knowledgeAssessmentData.items) {
          const matrixData = JSON.parse(matrixDataEl.textContent);
          const examTitle = document.getElementById('exam-title').value || 'Ma Trận Đặc Tả';
          const specContent = generateSpecificationMatrixInline(matrixData, knowledgeAssessmentData, examTitle);
          document.getElementById('spec-output').innerHTML = specContent;
          document.getElementById('spec-area').classList.remove('hidden');
        }
      } catch (e) {
        console.error('Error auto-generating specification matrix:', e);
      }
      
      // Auto-save configuration after successful generation
      saveCurrentConfig();
    } catch (e) {
      alert('Lỗi: ' + e.message);
    } finally {
      btn.disabled = false; btn.innerText = 'Tạo ma trận';
    }
  });

  document.getElementById('back-edit').addEventListener('click', ()=>{
    document.getElementById('result-area').classList.add('hidden');
    document.getElementById('spec-area').classList.add('hidden');
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
    
    // Generate specification matrix table HTML
    let specMatrixHtml = '';
    try {
      if (!knowledgeAssessmentData || !knowledgeAssessmentData.items) {
        console.warn('Không có dữ liệu Bản Đặc Tả để xuất');
      } else {
        specMatrixHtml = generateSpecificationMatrixTableOnly();
        console.log('Generated spec matrix HTML length:', specMatrixHtml.length);
      }
    } catch (e) {
      console.error('Error generating specification matrix:', e);
    }
    
    const fd = new FormData();
    fd.append('action', 'export_word');
    fd.append('exam_title', examTitle);
    fd.append('html_content', htmlContent);
    fd.append('spec_matrix_html', specMatrixHtml);
    
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
    if (confirm('⚠️ Bạn có chắc muốn xóa tất cả và bắt đầu lại?\n\n(Cấu hình đã lưu sẽ không bị xóa)')) {
      topicsContainer.innerHTML='';
      document.getElementById('exam-title').value = '';
      document.getElementById('select-subject').value = '';
      document.getElementById('select-grade').value = '';
      document.getElementById('select-grade').disabled = true;
      document.getElementById('add-topic').disabled = true;
      currentSubject = null;
      currentGrade = null;
      knowledgeAssessmentData = null;
    }
  });

  // Generate Specification Matrix
  // Removed generate-spec-btn event listener - specification matrix now auto-generates with main matrix

  // Removed back-to-matrix and print-spec-btn event listeners - buttons no longer needed

  // Generate inline specification matrix (for in-page display)
  function generateSpecificationMatrixInline(matrixData, knowledgeData, examTitle) {
    if (!matrixData.topics || !knowledgeData.items) {
      return '<div class="alert alert-warning">Không có dữ liệu</div>';
    }

    let html = `
<style>
/* Spec matrix specific styles - table uses shared .matrix-result-table classes */
#spec-output h3 {
  text-align: center;
  font-weight: bold;
  margin-bottom: 10px;
  font-size: 1.5rem;
  color: #1e40af;
}

#spec-output h4 {
  text-align: center;
  margin-bottom: 20px;
  font-size: 1.1rem;
  color: #64748b;
}

/* Adjust column widths for spec matrix */
#spec-output .col-tt { width: 30px; }
#spec-output .col-topic { min-width: 120px; }
#spec-output .col-unit { min-width: 150px; }
#spec-output .col-cau { width: 40px; }

/* Requirement cell specific styling */
#spec-output .requirement-cell {
  font-size: 0.85rem;
  line-height: 1.5;
  text-align: left;
  padding: 8px;
  min-width: 200px;
}

#spec-output .requirement-cell strong {
  display: block;
  margin-bottom: 3px;
  color: #1e40af;
}

/* Print styles for spec matrix */
@media print {
  #spec-output h3,
  #spec-output h4 {
    display: block !important;
  }
  
  #spec-output .requirement-cell {
    font-size: 0.65rem !important;
  }
}
</style>

<h3>MA TRẬN ĐẶC TẢ ĐỀ KIỂM TRA</h3>
<h4>${examTitle}</h4>
<div style="overflow-x: auto;">
  <table class="table table-bordered align-middle text-center matrix-result-table">
      <thead>
        <tr class="table-header-main">
          <th rowspan="3" class="col-topic">Chương/<br>Chủ đề</th>
          <th rowspan="3" class="col-unit">Nội dung/<br>Đơn vị kiến thức</th>
          <th rowspan="3">Yêu cầu cần đạt</th>
          <th colspan="9">Số câu hỏi ở các mức độ đánh giá</th>
        </tr>
        <tr class="table-header-type">
          <th colspan="3">TNKQ - Nhiều lựa chọn</th>
          <th colspan="3">TNKQ - Đúng/Sai</th>
          <th colspan="3">Tự luận</th>
        </tr>
        <tr class="table-header-sub">
          <th class="level-nb">Biết</th>
          <th class="level-th">Hiểu</th>
          <th class="level-vd">Vận dụng</th>
          <th class="level-nb">Biết</th>
          <th class="level-th">Hiểu</th>
          <th class="level-vd">Vận dụng</th>
          <th class="level-nb">Biết</th>
          <th class="level-th">Hiểu</th>
          <th class="level-vd">Vận dụng</th>
        </tr>
      </thead>
      <tbody>`;

    let topicIndex = 1;
    
    // Helper functions - updated to show question numbers
    const formatCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return 'Câu ' + nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + '';
      return '';
    };
    const formatYCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + 'ý';
      return '';
    };
    const formatTLCell = (pts, qcount, nums) => {
      if (pts > 0 && nums && Array.isArray(nums) && nums.length > 0) {
        // Keep full labels with subquestion letters (e.g., '1a', '1b', '2c')
        return 'Câu ' + nums.join(',') + ' (' + pts.toFixed(2).replace(/\\.?0+$/, '') + 'đ)';
      }
      return '';
    };
    const formatNum = (n) => n.toFixed(2).replace(/\\.?0+$/, '');
    
    // Count totals
    let total_tnkq_nb = 0, total_tnkq_th = 0, total_tnkq_vd = 0;
    let total_ds_nb = 0, total_ds_th = 0, total_ds_vd = 0;
    let total_tl_nb = 0, total_tl_th = 0, total_tl_vd = 0;
    
    // Process each topic
    matrixData.topics.forEach((topic) => {
      const topicData = knowledgeData.items.find(item => item.content === topic.title);
      if (!topicData) return;

      let totalUnitRows = 0;
      topic.units.forEach(unit => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (unitData) {
          // Always show 3 rows for each unit (Biết, Hiểu, Vận dụng)
          totalUnitRows += 3;
        }
      });

      let firstUnitInTopic = true;
      topic.units.forEach((unit) => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (!unitData) return;

        // Always show all 3 requirement rows regardless of content
        const unitRows = 3;
        let firstRowInUnit = true;
        
        // Count TL questions from nums arrays
        let tl_nb_qcount = (unit.tl.nb_nums || []).length;
        let tl_th_qcount = (unit.tl.th_nums || []).length;
        let tl_vd_qcount = (unit.tl.vd_nums || []).length;

        total_tnkq_nb += unit.tnkq.nb || 0;
        total_tnkq_th += unit.tnkq.th || 0;
        total_tnkq_vd += unit.tnkq.vd || 0;
        total_ds_nb += unit.ds.nb || 0;
        total_ds_th += unit.ds.th || 0;
        total_ds_vd += unit.ds.vd || 0;
        total_tl_nb += (tl_nb_qcount > 0 ? 1 : 0);
        total_tl_th += (tl_th_qcount > 0 ? 1 : 0);
        total_tl_vd += (tl_vd_qcount > 0 ? 1 : 0);

        // Row 1: Biết (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic && firstRowInUnit) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Biết:</strong> ${unitData.nhan_biet || ''}</td>`;
        html += `<td class="${unit.tnkq.nb_nums && unit.tnkq.nb_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="${unit.ds.nb_nums && unit.ds.nb_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="${unit.tl.nb_nums && unit.tl.nb_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.nb || 0, tl_nb_qcount, unit.tl.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 2: Hiểu (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Hiểu:</strong> ${unitData.thong_hieu || ''}</td>`;
        html += `<td class="cell-empty"></td><td class="${unit.tnkq.th_nums && unit.tnkq.th_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.th_nums)}</td><td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td><td class="${unit.ds.th_nums && unit.ds.th_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.th_nums)}</td><td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td><td class="${unit.tl.th_nums && unit.tl.th_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.th || 0, tl_th_qcount, unit.tl.th_nums)}</td><td class="cell-empty"></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 3: Vận dụng (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Vận dụng:</strong> ${unitData.van_dung || ''}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td><td class="${unit.tnkq.vd_nums && unit.tnkq.vd_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.vd_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td><td class="${unit.ds.vd_nums && unit.ds.vd_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.vd_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td><td class="${unit.tl.vd_nums && unit.tl.vd_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.vd || 0, tl_vd_qcount, unit.tl.vd_nums)}</td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;
      });
      
      topicIndex++;
    });

    const total_tnkq_nb_pts = total_tnkq_nb * 0.5;
    const total_tnkq_th_pts = total_tnkq_th * 0.5;
    const total_tnkq_vd_pts = total_tnkq_vd * 0.5;
    const total_ds_nb_pts = total_ds_nb * 0.25;
    const total_ds_th_pts = total_ds_th * 0.25;
    const total_ds_vd_pts = total_ds_vd * 0.25;
    const nb_pts_actual = matrixData.totals.nb || 0;
    const th_pts_actual = matrixData.totals.th || 0;
    const vd_pts_actual = matrixData.totals.vd || 0;
    const total_tl_nb_pts = nb_pts_actual - total_tnkq_nb_pts - total_ds_nb_pts;
    const total_tl_th_pts = th_pts_actual - total_tnkq_th_pts - total_ds_th_pts;
    const total_tl_vd_pts = vd_pts_actual - total_tnkq_vd_pts - total_ds_vd_pts;

    // Calculate combined counts: main questions (c) + sub items (ý)
    const total_nb_main = total_tnkq_nb + total_tl_nb;
    const total_th_main = total_tnkq_th + total_tl_th;
    const total_vd_main = total_tnkq_vd + total_tl_vd;
    const total_nb_sub = total_ds_nb;
    const total_th_sub = total_ds_th;
    const total_vd_sub = total_ds_vd;
    
    // Format count display
    const formatCount = (main, sub) => {
      if (main > 0 && sub > 0) return main + 'c+' + sub + 'ý';
      if (main > 0) return main + 'c';
      if (sub > 0) return sub + 'ý';
      return '';
    };

    html += `
      <tr class="summary-count-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tổng số câu</td>
        <td>${total_tnkq_nb > 0 ? total_tnkq_nb + 'c' : ''}</td>
        <td>${total_tnkq_th > 0 ? total_tnkq_th + 'c' : ''}</td>
        <td>${total_tnkq_vd > 0 ? total_tnkq_vd + 'c' : ''}</td>
        <td>${formatYCell(total_ds_nb)}</td>
        <td>${formatYCell(total_ds_th)}</td>
        <td>${formatYCell(total_ds_vd)}</td>
        <td>${total_tl_nb > 0 ? total_tl_nb + 'c' : ''}</td>
        <td>${total_tl_th > 0 ? total_tl_th + 'c' : ''}</td>
        <td>${total_tl_vd > 0 ? total_tl_vd + 'c' : ''}</td>
      </tr>
      <tr class="summary-points-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tổng số điểm</td>
        <td colspan="3">${formatNum(total_tnkq_nb_pts + total_tnkq_th_pts + total_tnkq_vd_pts)}đ</td>
        <td colspan="3">${formatNum(total_ds_nb_pts + total_ds_th_pts + total_ds_vd_pts)}đ</td>
        <td colspan="3">${formatNum(total_tl_nb_pts + total_tl_th_pts + total_tl_vd_pts)}đ</td>
      </tr>
      <tr class="total-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tỉ lệ</td>
        <td colspan="3" class="summary-tnkq fw-bold">${Math.round(matrixData.totals.tnkq_pts / 10 * 100)}%</td>
        <td colspan="3" class="summary-ds fw-bold">${Math.round(matrixData.totals.ds_pts / 10 * 100)}%</td>
        <td colspan="3" class="summary-tl fw-bold">${Math.round(matrixData.totals.tl_pts / 10 * 100)}%</td>
      </tr>
    </tbody>
    </table>
</div>`;

    return html;
  }

  // Generate specification matrix table content only (for Word export)
  function generateSpecificationMatrixTableOnly() {
    const matrixDataEl = document.getElementById('matrix-data');
    const matrixData = matrixDataEl ? JSON.parse(matrixDataEl.textContent) : {};
    const knowledgeData = knowledgeAssessmentData;
    
    if (!matrixData.topics || !knowledgeData || !knowledgeData.items) {
      return '';
    }

    let html = `<table>
                    <thead>
                        <tr>
                            <th rowspan="3" class="col-topic">Chương/<br>Chủ đề</th>
                            <th rowspan="3" class="col-unit">Nội dung/<br>Đơn vị kiến thức</th>
                            <th rowspan="3">Yêu cầu cần đạt</th>
                            <th colspan="9">Số câu hỏi ở các mức độ đánh giá</th>
                        </tr>
                        <tr>
                            <th colspan="3">TNKQ - Nhiều lựa chọn</th>
                            <th colspan="3">TNKQ - Đúng/Sai</th>
                            <th colspan="3">Tự luận</th>
                        </tr>
                        <tr>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                        </tr>
                    </thead>
                    <tbody>`;

    let topicIndex = 1;
    
    // Helper functions - updated to show question numbers (Word export)
    const formatCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return 'Câu ' + nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + '';
      return '';
    };
    const formatYCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + 'ý';
      return '';
    };
    const formatTLCell = (pts, qcount, nums) => {
      if (pts > 0 && nums && Array.isArray(nums) && nums.length > 0) {
        // Keep full labels with subquestion letters (e.g., '1a', '1b', '2c')
        return 'Câu ' + nums.join(',') + ' (' + pts.toFixed(2).replace(/\\.?0+$/, '') + 'đ)';
      }
      return '';
    };
    const formatNum = (n) => n.toFixed(2).replace(/\\.?0+$/, '');
    
    // Count totals across all levels
    let total_tnkq_nb = 0, total_tnkq_th = 0, total_tnkq_vd = 0;
    let total_ds_nb = 0, total_ds_th = 0, total_ds_vd = 0;
    let total_tl_nb = 0, total_tl_th = 0, total_tl_vd = 0;
    
    // Process each topic
    matrixData.topics.forEach((topic, tIdx) => {
      const topicData = knowledgeData.items.find(item => item.content === topic.title);
      if (!topicData) return;

      // Count total unit rows for rowspan
      let totalUnitRows = 0;
      topic.units.forEach(unit => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (unitData) {
          // Always show 3 rows for each unit (Biết, Hiểu, Vận dụng)
          totalUnitRows += 3;
        }
      });

      // Process each unit in topic
      let firstUnitInTopic = true;
      topic.units.forEach((unit, uIdx) => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (!unitData) return;

        // Always show all 3 requirement rows regardless of content
        const unitRows = 3;
        let firstRowInUnit = true;
        
        // Count TL questions from nums arrays (Word export)
        let tl_nb_qcount = (unit.tl.nb_nums || []).length;
        let tl_th_qcount = (unit.tl.th_nums || []).length;
        let tl_vd_qcount = (unit.tl.vd_nums || []).length;

        // Accumulate totals
        total_tnkq_nb += unit.tnkq.nb || 0;
        total_tnkq_th += unit.tnkq.th || 0;
        total_tnkq_vd += unit.tnkq.vd || 0;
        total_ds_nb += unit.ds.nb || 0;
        total_ds_th += unit.ds.th || 0;
        total_ds_vd += unit.ds.vd || 0;
        total_tl_nb += tl_nb_qcount;
        total_tl_th += tl_th_qcount;
        total_tl_vd += tl_vd_qcount;

        // Row 1: Biết (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic && firstRowInUnit) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Biết:</strong> ${unitData.nhan_biet || ''}</td>`;
        html += `<td class="${unit.tnkq.nb_nums && unit.tnkq.nb_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `<td class="${unit.ds.nb_nums && unit.ds.nb_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `<td class="${unit.tl.nb_nums && unit.tl.nb_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.nb || 0, tl_nb_qcount, unit.tl.nb_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 2: Hiểu (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Hiểu:</strong> ${unitData.thong_hieu || ''}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="${unit.tnkq.th_nums && unit.tnkq.th_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.th_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="${unit.ds.th_nums && unit.ds.th_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.th_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="cell-empty"></td>`;
        html += `<td class="${unit.tl.th_nums && unit.tl.th_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.th || 0, tl_th_qcount, unit.tl.th_nums)}</td>`;
        html += `<td class="cell-empty"></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 3: Vận dụng (always show)
        html += `<tr class="unit-row">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Vận dụng:</strong> ${unitData.van_dung || ''}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `<td class="${unit.tnkq.vd_nums && unit.tnkq.vd_nums.length > 0 ? 'cell-tnkq' : 'cell-empty'}">${formatCell(unit.tnkq.vd_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `<td class="${unit.ds.vd_nums && unit.ds.vd_nums.length > 0 ? 'cell-ds' : 'cell-empty'}">${formatYCell(unit.ds.vd_nums)}</td>`;
        html += `<td class="cell-empty"></td><td class="cell-empty"></td>`;
        html += `<td class="${unit.tl.vd_nums && unit.tl.vd_nums.length > 0 ? 'cell-tl' : 'cell-empty'}">${formatTLCell(unit.tl.vd || 0, tl_vd_qcount, unit.tl.vd_nums)}</td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;
      });
      
      topicIndex++;
    });

    // Calculate point totals
    const total_tnkq_nb_pts = total_tnkq_nb * 0.5;
    const total_tnkq_th_pts = total_tnkq_th * 0.5;
    const total_tnkq_vd_pts = total_tnkq_vd * 0.5;
    const total_ds_nb_pts = total_ds_nb * 0.25;
    const total_ds_th_pts = total_ds_th * 0.25;
    const total_ds_vd_pts = total_ds_vd * 0.25;
    
    // Get TL points from totals
    const nb_pts_actual = matrixData.totals.nb || 0;
    const th_pts_actual = matrixData.totals.th || 0;
    const vd_pts_actual = matrixData.totals.vd || 0;
    const total_tl_nb_pts = nb_pts_actual - total_tnkq_nb_pts - total_ds_nb_pts;
    const total_tl_th_pts = th_pts_actual - total_tnkq_th_pts - total_ds_th_pts;
    const total_tl_vd_pts = vd_pts_actual - total_tnkq_vd_pts - total_ds_vd_pts;

    // Summary rows matching main matrix structure
    html += `
      <tr class="summary-count-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tổng số câu</td>
        <td class="text-center">${total_tnkq_nb > 0 ? total_tnkq_nb + 'c' : ''}</td>
        <td class="text-center">${total_tnkq_th > 0 ? total_tnkq_th + 'c' : ''}</td>
        <td class="text-center">${total_tnkq_vd > 0 ? total_tnkq_vd + 'c' : ''}</td>
        <td class="text-center">${formatYCell(total_ds_nb)}</td>
        <td class="text-center">${formatYCell(total_ds_th)}</td>
        <td class="text-center">${formatYCell(total_ds_vd)}</td>
        <td class="text-center">${total_tl_nb > 0 ? total_tl_nb + 'c' : ''}</td>
        <td class="text-center">${total_tl_th > 0 ? total_tl_th + 'c' : ''}</td>
        <td class="text-center">${total_tl_vd > 0 ? total_tl_vd + 'c' : ''}</td>
      </tr>
      <tr class="summary-points-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tổng số điểm</td>
        <td colspan="3" class="text-center">${formatNum(total_tnkq_nb_pts + total_tnkq_th_pts + total_tnkq_vd_pts)}đ</td>
        <td colspan="3" class="text-center">${formatNum(total_ds_nb_pts + total_ds_th_pts + total_ds_vd_pts)}đ</td>
        <td colspan="3" class="text-center">${formatNum(total_tl_nb_pts + total_tl_th_pts + total_tl_vd_pts)}đ</td>
      </tr>
      <tr class="total-row">
        <td colspan="3" class="text-start fw-bold ps-3">Tỉ lệ</td>
        <td colspan="3" class="summary-tnkq fw-bold">${Math.round(matrixData.totals.tnkq_pts / 10 * 100)}%</td>
        <td colspan="3" class="summary-ds fw-bold">${Math.round(matrixData.totals.ds_pts / 10 * 100)}%</td>
        <td colspan="3" class="summary-tl fw-bold">${Math.round(matrixData.totals.tl_pts / 10 * 100)}%</td>
      </tr>
    `;

    html += `</tbody></table>`;
    return html;
  }

  function generateSpecificationMatrixHTML() {
    // Get data from hidden JSON script
    const matrixDataEl = document.getElementById('matrix-data');
    const matrixData = matrixDataEl ? JSON.parse(matrixDataEl.textContent) : {};
    const knowledgeData = knowledgeAssessmentData;
    const examTitle = document.getElementById('exam-title').value || 'Ma Trận Đặc Tả';
    
    if (!matrixData.topics || !knowledgeData || !knowledgeData.items) {
      return '<html><body><h1>Không có dữ liệu</h1></body></html>';
    }

    let html = `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${examTitle}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f1f5f9;
            padding: 20px;
            min-height: 100vh;
            font-size: 13px;
        }
        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 { font-size: 22px; margin-bottom: 8px; font-weight: bold; }
        .header p { font-size: 15px; opacity: 0.95; }
        .toolbar {
            padding: 12px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            text-align: right;
        }
        .toolbar button {
            padding: 8px 16px;
            margin-left: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-print {
            background: #3b82f6;
            color: white;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .content { padding: 20px; }
        .table-wrapper {
            overflow-x: auto;
            margin-top: 15px;
            border-radius: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 11px;
        }
        th {
            background: #3b82f6;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #2563eb;
            vertical-align: middle;
        }
        td {
            padding: 6px 4px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            text-align: center;
        }
        .requirement-cell {
            font-size: 11px;
            line-height: 1.5;
            text-align: left;
            padding: 6px;
            min-width: 200px;
        }
        .requirement-cell strong {
            display: block;
            margin-bottom: 3px;
            color: #1e40af;
        }
        .total-row {
            background: #e0e7ff;
            font-weight: bold;
            font-size: 12px;
        }
        .highlight { background: #fef3c7; }
        .col-tt { width: 30px; }
        .col-topic { min-width: 120px; }
        .col-unit { min-width: 150px; }
        .col-cau { width: 40px; }
        .col-level { width: 50px; }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            body { background: white; padding: 0; font-size: 9pt; }
            .toolbar { display: none; }
            .container { box-shadow: none; border-radius: 0; }
            table { font-size: 8pt; page-break-inside: avoid; }
            th, td { padding: 4px 3px; }
            .header { padding: 12px; }
            .header h1 { font-size: 16pt; }
            .header p { font-size: 11pt; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MA TRẬN ĐẶC TẢ ĐỀ KIỂM TRA</h1>
            <p>${examTitle}</p>
        </div>
        
        <div class="toolbar">
            <button class="btn-print" onclick="window.print()">🖨️ In Ma Trận</button>
            <button class="btn-print" onclick="window.close()">❌ Đóng</button>
        </div>

        <div class="content">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="3" class="col-topic">Chương/<br>Chủ đề</th>
                            <th rowspan="3" class="col-unit">Nội dung/<br>Đơn vị kiến thức</th>
                            <th rowspan="3">Yêu cầu cần đạt</th>
                            <th colspan="9">Số câu hỏi ở các mức độ đánh giá</th>
                        </tr>
                        <tr>
                            <th colspan="3">TNKQ - Nhiều lựa chọn</th>
                            <th colspan="3">TNKQ - Đúng/Sai</th>
                            <th colspan="3">Tự luận</th>
                        </tr>
                        <tr>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                            <th class="col-level">Biết</th>
                            <th class="col-level">Hiểu</th>
                            <th class="col-level">Vận dụng</th>
                        </tr>
                    </thead>
                    <tbody>`;

    let topicIndex = 1;
    
    // Helper functions - updated to show question numbers (Popup version)
    const formatCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return 'Câu ' + nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + '';
      return '';
    };
    const formatYCell = (nums) => {
      if (!nums) return '';
      if (Array.isArray(nums)) {
        if (nums.length === 0) return '';
        return nums.join(',');
      }
      if (typeof nums === 'number' && nums > 0) return nums + 'ý';
      return '';
    };
    const formatTLCell = (pts, qcount, nums) => {
      if (pts > 0 && nums && Array.isArray(nums) && nums.length > 0) {
        // Keep full labels with subquestion letters (e.g., '1a', '1b', '2c')
        return 'Câu ' + nums.join(',') + ' (' + pts.toFixed(2).replace(/\\.?0+$/, '') + 'đ)';
      }
      return '';
    };
    const formatNum = (n) => n.toFixed(2).replace(/\\.?0+$/, '');
    
    // Count totals across all levels
    let total_tnkq_nb = 0, total_tnkq_th = 0, total_tnkq_vd = 0;
    let total_ds_nb = 0, total_ds_th = 0, total_ds_vd = 0;
    let total_tl_nb = 0, total_tl_th = 0, total_tl_vd = 0;
    
    // Process each topic
    matrixData.topics.forEach((topic, tIdx) => {
      const topicData = knowledgeData.items.find(item => item.content === topic.title);
      if (!topicData) return;

      // Count total unit rows for rowspan
      let totalUnitRows = 0;
      topic.units.forEach(unit => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (unitData) {
          // Always show 3 rows for each unit (Biết, Hiểu, Vận dụng)
          totalUnitRows += 3;
        }
      });

      // Process each unit in topic
      let firstUnitInTopic = true;
      topic.units.forEach((unit, uIdx) => {
        const unitData = topicData.units.find(u => u.unit_name === unit.title);
        if (!unitData) return;

        // Always show all 3 requirement rows regardless of content
        const unitRows = 3;
        let firstRowInUnit = true;
        
        // Count TL questions from nums arrays (Popup version)
        let tl_nb_qcount = (unit.tl.nb_nums || []).length;
        let tl_th_qcount = (unit.tl.th_nums || []).length;
        let tl_vd_qcount = (unit.tl.vd_nums || []).length;

        // Accumulate totals
        total_tnkq_nb += unit.tnkq.nb || 0;
        total_tnkq_th += unit.tnkq.th || 0;
        total_tnkq_vd += unit.tnkq.vd || 0;
        total_ds_nb += unit.ds.nb || 0;
        total_ds_th += unit.ds.th || 0;
        total_ds_vd += unit.ds.vd || 0;
        total_tl_nb += tl_nb_qcount;
        total_tl_th += tl_th_qcount;
        total_tl_vd += tl_vd_qcount;

        // Row 1: Biết (always show)
        html += `<tr class="${firstUnitInTopic && firstRowInUnit ? 'topic-row' : 'subtopic-row'}">`;
        if (firstUnitInTopic && firstRowInUnit) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Biết:</strong> ${unitData.nhan_biet || ''}</td>`;
        html += `<td>${formatCell(unit.tnkq.nb_nums)}</td>`;
        html += `<td></td><td></td>`;
        html += `<td>${formatYCell(unit.ds.nb_nums)}</td>`;
        html += `<td></td><td></td>`;
        html += `<td>${formatTLCell(unit.tl.nb || 0, tl_nb_qcount, unit.tl.nb_nums)}</td>`;
        html += `<td></td><td></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 2: Hiểu (always show)
        html += `<tr class="${firstUnitInTopic ? 'topic-row' : 'subtopic-row'}">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Hiểu:</strong> ${unitData.thong_hieu || ''}</td>`;
        html += `<td></td>`;
        html += `<td>${formatCell(unit.tnkq.th_nums)}</td>`;
        html += `<td></td>`;
        html += `<td></td>`;
        html += `<td>${formatYCell(unit.ds.th_nums)}</td>`;
        html += `<td></td>`;
        html += `<td></td>`;
        html += `<td>${formatTLCell(unit.tl.th || 0, tl_th_qcount, unit.tl.th_nums)}</td>`;
        html += `<td></td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;

        // Row 3: Vận dụng (always show)
        html += `<tr class="${firstUnitInTopic ? 'topic-row' : 'subtopic-row'}">`;
        if (firstUnitInTopic) {
          html += `<td rowspan="${totalUnitRows}"><strong>${topic.title}</strong></td>`;
        }
        if (firstRowInUnit) {
          html += `<td rowspan="${unitRows}"><strong>${unit.title}</strong></td>`;
        }
        html += `<td class="requirement-cell"><strong>Vận dụng:</strong> ${unitData.van_dung || ''}</td>`;
        html += `<td></td><td></td>`;
        html += `<td>${formatCell(unit.tnkq.vd_nums)}</td>`;
        html += `<td></td><td></td>`;
        html += `<td>${formatYCell(unit.ds.vd_nums)}</td>`;
        html += `<td></td><td></td>`;
        html += `<td>${formatTLCell(unit.tl.vd || 0, tl_vd_qcount, unit.tl.vd_nums)}</td>`;
        html += `</tr>`;
        firstRowInUnit = false;
        firstUnitInTopic = false;
      });

      topicIndex++;
    });
    
    // Calculate points totals
    const total_tnkq_nb_pts = total_tnkq_nb * 0.5;
    const total_tnkq_th_pts = total_tnkq_th * 0.5;
    const total_tnkq_vd_pts = total_tnkq_vd * 0.5;
    const total_ds_nb_pts = total_ds_nb * 0.25;
    const total_ds_th_pts = total_ds_th * 0.25;
    const total_ds_vd_pts = total_ds_vd * 0.25;
    
    // Get TL points from totals
    const nb_pts_actual = matrixData.totals.nb || 0;
    const th_pts_actual = matrixData.totals.th || 0;
    const vd_pts_actual = matrixData.totals.vd || 0;
    const total_tl_nb_pts = nb_pts_actual - total_tnkq_nb_pts - total_ds_nb_pts;
    const total_tl_th_pts = th_pts_actual - total_tnkq_th_pts - total_ds_th_pts;
    const total_tl_vd_pts = vd_pts_actual - total_tnkq_vd_pts - total_ds_vd_pts;

    // Total rows
    html += `
      <tr class="total-row">
        <td colspan="3">TỔNG SỐ CÂU</td>
        <td>${formatCell(total_tnkq_nb)}</td>
        <td>${formatCell(total_tnkq_th)}</td>
        <td>${formatCell(total_tnkq_vd)}</td>
        <td>${formatYCell(total_ds_nb)}</td>
        <td>${formatYCell(total_ds_th)}</td>
        <td>${formatYCell(total_ds_vd)}</td>
        <td>${formatCell(total_tl_nb)}</td>
        <td>${formatCell(total_tl_th)}</td>
        <td>${formatCell(total_tl_vd)}</td>
      </tr>
      <tr class="total-row">
        <td colspan="3">TỔNG SỐ ĐIỂM</td>
        <td>${formatNum(total_tnkq_nb_pts)}</td>
        <td>${formatNum(total_tnkq_th_pts)}</td>
        <td>${formatNum(total_tnkq_vd_pts)}</td>
        <td>${formatNum(total_ds_nb_pts)}</td>
        <td>${formatNum(total_ds_th_pts)}</td>
        <td>${formatNum(total_ds_vd_pts)}</td>
        <td>${formatNum(total_tl_nb_pts)}</td>
        <td>${formatNum(total_tl_th_pts)}</td>
        <td>${formatNum(total_tl_vd_pts)}</td>
      </tr>
      <tr class="total-row highlight">
        <td colspan="3">TỈ LỆ %</td>
        <td colspan="3">${Math.round(matrixData.totals.tnkq_pts / 10 * 100)}%</td>
        <td colspan="3">${Math.round(matrixData.totals.ds_pts / 10 * 100)}%</td>
        <td colspan="3">${Math.round(matrixData.totals.tl_pts / 10 * 100)}%</td>
      </tr>
      <tr class="total-row">
        <td colspan="3"><strong>TỔNG CỘNG</strong></td>
        <td colspan="9"><strong>${formatNum(matrixData.totals.nb)}đ (${Math.round(matrixData.totals.nb / 10 * 100)}%) + ${formatNum(matrixData.totals.th)}đ (${Math.round(matrixData.totals.th / 10 * 100)}%) + ${formatNum(matrixData.totals.vd)}đ (${Math.round(matrixData.totals.vd / 10 * 100)}%) = 10.00đ</strong></td>
      </tr>
    `;

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>`;

    return html;
  }
})();
</script>

<!-- MODAL TẠO ĐỀ KIỂM TRA -->
<div class="modal fade" id="exam-generator-modal" tabindex="-1">
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header bg-success text-white">
      <h5 class="modal-title">📝 Tạo đề Kiểm tra từ Ma trận</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div id="exam-config-section">
        <div class="mb-3">
          <label class="form-label fw-bold">Tiêu đề đề thi:</label>
          <input type="text" id="exam-title" class="form-control" value="Kiểm tra giữa kỳ I" placeholder="Nhập tiêu đề...">
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-bold">Số đề (A, B, C...):</label>
          <select id="variant-count" class="form-select">
            <option value="1">1 đề</option>
            <option value="2" selected>2 đề (A, B)</option>
            <option value="3">3 đề (A, B, C)</option>
            <option value="4">4 đề (A, B, C, D)</option>
          </select>
        </div>
        
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="randomize-questions" checked>
            <label class="form-check-label" for="randomize-questions">
              🔀 Random thứ tự câu hỏi
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="randomize-answers" checked>
            <label class="form-check-label" for="randomize-answers">
              🔀 Random thứ tự đáp án
            </label>
          </div>
        </div>
        
        <div class="alert alert-info">
          <small>
            <strong>Lưu ý:</strong> Hệ thống sẽ tự động chọn câu hỏi từ ngân hàng câu hỏi theo yêu cầu của ma trận. 
            Bạn có thể thay thế câu hỏi sau khi tạo đề.
          </small>
        </div>
        
        <div class="d-grid gap-2">
          <button id="btn-generate-exam" class="btn btn-success btn-lg">
            ✨ Tạo đề ngay
          </button>
        </div>
      </div>
      
      <!-- PREVIEW SECTION (Hidden initially) -->
      <div id="exam-preview-section" class="hidden">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Đề đã tạo:</h6>
          <select id="variant-selector" class="form-select form-select-sm w-auto">
            <!-- Will be populated dynamically -->
          </select>
        </div>
        
        <div id="exam-preview-content" style="max-height: 500px; overflow-y: auto;">
          <!-- Questions will be rendered here -->
        </div>
        
        <div id="exam-stats" class="alert alert-secondary mt-3">
          <!-- Stats will be shown here -->
        </div>
        
        <div class="d-grid gap-2 mt-3">
          <button id="btn-export-exam-word" class="btn btn-primary">📄 Xuất đề Word</button>
          <button id="btn-back-to-config" class="btn btn-outline-secondary">← Quay lại cấu hình</button>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
// EXAM GENERATOR LOGIC
(function() {
  let currentExamData = null;
  
  // Show modal when button clicked
  document.getElementById('generate-exam-btn')?.addEventListener('click', function() {
    // Check if matrix exists
    const matrixDataEl = document.getElementById('matrix-data');
    if (!matrixDataEl || !matrixDataEl.textContent) {
      alert('Chưa có ma trận! Vui lòng tạo ma trận trước khi tạo đề kiểm tra.');
      return;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('exam-generator-modal'));
    modal.show();
    
    // Reset to config view
    document.getElementById('exam-config-section').classList.remove('hidden');
    document.getElementById('exam-preview-section').classList.add('hidden');
  });
  
  // Generate exam
  document.getElementById('btn-generate-exam')?.addEventListener('click', async function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';
    
    try {
      // Get matrix data
      const matrixDataEl = document.getElementById('matrix-data');
      const matrixData = JSON.parse(matrixDataEl.textContent);
      
      // Get form values
      const examTitle = document.getElementById('exam-title').value.trim();
      const variantCount = parseInt(document.getElementById('variant-count').value);
      const randomizeQuestions = document.getElementById('randomize-questions').checked;
      const randomizeAnswers = document.getElementById('randomize-answers').checked;
      
      // Get grade/subject from select elements in the page
      const gradeSelect = document.getElementById('select-grade');
      const subjectSelect = document.getElementById('select-subject');
      
      if (!gradeSelect || !subjectSelect) {
        alert('Không tìm thấy thông tin môn học/khối!');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
      }
      
      const grade = gradeSelect.value;
      const subject = subjectSelect.value;
      const semester = 'hk1'; // Default to học kỳ 1
      
      if (!grade || !subject) {
        alert('Vui lòng chọn môn học và khối trước khi tạo đề!');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
      }
      
      // Call API
      const response = await fetch('api/generate_exam.php?action=generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include', // Send session cookie
        body: JSON.stringify({
          matrix_data: matrixData,
          exam_title: examTitle,
          grade: grade,
          subject: subject,
          semester: semester,
          options: {
            create_variants: variantCount,
            randomize_questions: randomizeQuestions,
            randomize_answers: randomizeAnswers,
            allow_duplicate: false
          }
        })
      });
      
      // Debug: Log response text
      const responseText = await response.text();
      console.log('API Response:', responseText);
      
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (e) {
        console.error('JSON Parse Error:', e);
        console.error('Response was:', responseText.substring(0, 500));
        alert('Lỗi parse JSON. Kiểm tra Console (F12) để xem chi tiết.');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
      }
      
      if (result.success) {
        currentExamData = result.exam;
        showExamPreview(result.exam);
      } else {
        alert('Lỗi tạo đề: ' + (result.error || 'Unknown error'));
      }
      
    } catch (error) {
      console.error('Generate exam error:', error);
      alert('Lỗi: ' + error.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });
  
  // Show exam preview
  function showExamPreview(exam) {
    // Switch views
    document.getElementById('exam-config-section').classList.add('hidden');
    document.getElementById('exam-preview-section').classList.remove('hidden');
    
    // Populate variant selector
    const variantSelector = document.getElementById('variant-selector');
    variantSelector.innerHTML = '';
    exam.variants.forEach((v, idx) => {
      const option = document.createElement('option');
      option.value = idx;
      option.text = `Đề ${v.variant}`;
      variantSelector.appendChild(option);
    });
    
    // Show first variant
    renderVariant(exam, 0);
    
    // Variant selector change
    variantSelector.addEventListener('change', function() {
      renderVariant(exam, parseInt(this.value));
    });
  }
  
  // Render a variant
  function renderVariant(exam, variantIndex) {
    const variant = exam.variants[variantIndex];
    const container = document.getElementById('exam-preview-content');
    
    let html = `<div class="exam-questions">`;
    
    variant.questions.forEach(q => {
      html += `
        <div class="question-item card mb-3 p-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <strong>Câu ${q.number}</strong>
              <span class="badge bg-primary">${q.question_type}</span>
              <span class="badge bg-secondary">${q.level}</span>
              <span class="badge bg-success">${q.required_points.toFixed(2)}đ</span>
            </div>
            <button class="btn btn-sm btn-outline-warning" onclick="replaceQuestion(${q.number}, '${exam.variants[variantIndex].variant}')">
              🔄 Thay
            </button>
          </div>
          <div class="question-text mb-2">${escapeHtml(q.question)}</div>
      `;
      
      if (q.options && q.options.length > 0) {
        html += `<ol type="A" class="mb-0">`;
        q.options.forEach(opt => {
          html += `<li>${escapeHtml(opt)}</li>`;
        });
        html += `</ol>`;
      }
      
      html += `</div>`;
    });
    
    html += `</div>`;
    
    container.innerHTML = html;
    
    // Update stats
    const statsHtml = `
      <strong>Tổng: ${variant.total_questions} câu | ${variant.total_points} điểm</strong><br>
      TNKQ: ${variant.distribution.TNKQ} câu | 
      Đúng/Sai: ${variant.distribution.DS} câu | 
      Tự luận: ${variant.distribution.TL} câu
    `;
    document.getElementById('exam-stats').innerHTML = statsHtml;
  }
  
  // Helper: Escape HTML
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Back to config
  document.getElementById('btn-back-to-config')?.addEventListener('click', function() {
    document.getElementById('exam-config-section').classList.remove('hidden');
    document.getElementById('exam-preview-section').classList.add('hidden');
  });
  
  // Export to Word (placeholder)
  document.getElementById('btn-export-exam-word')?.addEventListener('click', function() {
    if (!currentExamData) {
      alert('Không có dữ liệu đề thi để xuất!');
      return;
    }
    
    // Get current selected variant
    const variantIndex = parseInt(document.getElementById('variant-selector').value);
    const variant = currentExamData.variants[variantIndex].variant;
    
    // Open export URL
    const exportUrl = `api/export_exam.php?action=export_word&exam_id=${currentExamData.id}&variant=${variant}&include_answers=1`;
    window.open(exportUrl, '_blank');
  });
  
  // Replace question (global function)
  window.replaceQuestion = async function(questionNumber, variant) {
    if (!currentExamData) return;
    
    if (!confirm(`Bạn muốn thay câu ${questionNumber} bằng câu khác?`)) return;
    
    try {
      // Get constraints from the question
      const variantIndex = currentExamData.variants.findIndex(v => v.variant === variant);
      const question = currentExamData.variants[variantIndex].questions.find(q => q.number === questionNumber);
      
      const response = await fetch('api/generate_exam.php?action=replace_question', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          exam_id: currentExamData.id,
          variant: variant,
          question_number: questionNumber,
          constraints: {
            question_type: question.question_type,
            topic: question._topic,
            unit: question._unit,
            level: question.level
          }
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Update local data
        const qIndex = currentExamData.variants[variantIndex].questions.findIndex(q => q.number === questionNumber);
        currentExamData.variants[variantIndex].questions[qIndex] = result.new_question;
        
        // Re-render
        renderVariant(currentExamData, variantIndex);
        
        alert('✓ Đã thay câu hỏi mới');
      } else {
        alert('Lỗi: ' + (result.error || 'Không thể thay câu hỏi'));
      }
    } catch (error) {
      console.error('Replace question error:', error);
      alert('Lỗi: ' + error.message);
    }
  };
  
})();
</script>

<?php include '../includes/footer.php'; ?>
