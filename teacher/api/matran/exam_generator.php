<?php
/**
 * exam_generator.php — Logic sinh nhiều đề từ ma trận + ngân hàng câu hỏi.
 * Đảm bảo các đề khác nhau tối thiểu $minDiff% (mặc định 50%).
 *
 * Thuần logic, không phụ thuộc session/POST => có thể kiểm thử riêng.
 */

/** Chuẩn hoá chuỗi để so khớp đơn vị kiến thức */
function mtnNorm(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

function mtnMapQuestionType(string $type): string {
    $t = strtolower(trim($type));
    if (in_array($t, ['true_false_multiple', 'true_false', 'true_false_multi', 'dungsai'], true)) return 'DS';
    if (in_array($t, ['essay', 'tuluan', 'short_answer', 'tl'], true)) return 'TL';
    return 'TNKQ';
}

function mtnNormalizeLevel(string $level): string {
    $l = strtoupper(trim($level));
    return in_array($l, ['VDC', 'VD', 'TH', 'NB'], true) ? $l : 'NB';
}

function mtnQuestionId(array $q): string {
    return md5(mtnNorm($q['question'] ?? '') . '|' . ($q['question_type'] ?? ''));
}

function mtnTokenOverlap(string $a, string $b): float {
    $ta = array_filter(explode(' ', $a));
    $tb = array_filter(explode(' ', $b));
    if (!$ta || !$tb) return 0.0;
    $inter = count(array_intersect($ta, $tb));
    return $inter / max(1, min(count($ta), count($tb)));
}

function mtnUnitMatch(array $q, array $slot): bool {
    $u  = mtnNorm($slot['unit'] ?? '');  $qu = mtnNorm($q['_unit'] ?? '');
    $t  = mtnNorm($slot['topic'] ?? ''); $qt = mtnNorm($q['_topic'] ?? '');
    if ($u !== '' && $qu !== '' && (mb_strpos($qu, $u) !== false || mb_strpos($u, $qu) !== false)) return true;
    if ($t !== '' && $qt !== '' && (mb_strpos($qt, $t) !== false || mb_strpos($t, $qt) !== false)) return true;
    return mtnTokenOverlap($u, $qu) >= 0.5;
}

function mtnLevelEq(string $slotLevel, string $qLevel): bool {
    if ($slotLevel === 'VD') return in_array($qLevel, ['VD', 'VDC'], true);
    return $slotLevel === $qLevel;
}

/** Chuẩn hoá mảng thô từ file JSON ngân hàng thành danh sách câu phẳng */
function mtnFlattenBank(array $raw): array {
    $bank = [];
    foreach ($raw as $topic) {
        if (!is_array($topic)) continue;
        $topicName = $topic['topic_name'] ?? $topic['topic'] ?? '';
        $unitName  = $topic['unit_name'] ?? $topic['unit'] ?? $topic['lesson'] ?? '';
        foreach (($topic['questions'] ?? []) as $q) {
            if (empty($q['question'])) continue;
            $q['_topic']        = $topicName;
            $q['_unit']         = $unitName;
            $q['question_type'] = mtnMapQuestionType($q['type'] ?? 'single');
            $q['level']         = mtnNormalizeLevel($q['level'] ?? 'NB');
            $q['_qid']          = mtnQuestionId($q);
            $bank[] = $q;
        }
    }
    return $bank;
}

/** Tạo slots (yêu cầu) từ unitData của ma trận */
function mtnBuildSlots(array $unitData, array $ctx): array {
    $slots = [];
    $dims  = ['nb' => 'NB', 'th' => 'TH', 'vd' => 'VD'];
    foreach ($unitData as $u) {
        foreach ($dims as $k => $lv) {
            $c = (int)($u['u_tnkq_' . $k] ?? 0);
            if ($c > 0) $slots[] = ['type' => 'TNKQ', 'level' => $lv, 'count' => $c, 'unit' => $u['dv'] ?? '', 'topic' => $u['nd'] ?? '', 'points' => (float)$ctx['tnkq_per_q']];
        }
        foreach ($dims as $k => $lv) {
            $c = (int)($u['u_tl_' . $k] ?? 0);
            if ($c > 0) $slots[] = ['type' => 'TL', 'level' => $lv, 'count' => $c, 'unit' => $u['dv'] ?? '', 'topic' => $u['nd'] ?? '', 'points' => (float)$ctx['tl_per_q']];
        }
        $dsNb = (int)($u['u_ds_nb'] ?? 0);
        $dsTh = (int)($u['u_ds_th'] ?? 0);
        $dsVd = (int)($u['u_ds_vd'] ?? 0);
        $dsItems = $dsNb + $dsTh + $dsVd;
        if ($dsItems > 0) {
            $lv = ($dsVd >= max($dsNb, $dsTh)) ? 'VD' : (($dsTh >= $dsNb) ? 'TH' : 'NB');
            $slots[] = ['type' => 'DS', 'level' => $lv, 'count' => 1, 'unit' => $u['dv'] ?? '', 'topic' => $u['nd'] ?? '', 'points' => round($dsItems * (float)$ctx['ds_pt_per_item'], 2)];
        }
    }
    return $slots;
}

function mtnPickPool(array $bank, array $slot, int $minNeed): array {
    $byLevel = array_values(array_filter($bank, function ($q) use ($slot) {
        return $q['question_type'] === $slot['type'] && mtnLevelEq($slot['level'], $q['level']);
    }));
    $exact = array_values(array_filter($byLevel, fn($q) => mtnUnitMatch($q, $slot)));
    $pool = $exact;
    if (count($pool) < $minNeed) {
        $seen = array_column($pool, '_qid');
        foreach ($byLevel as $q) {
            if (!in_array($q['_qid'], $seen, true)) { $pool[] = $q; $seen[] = $q['_qid']; }
        }
    }
    if (!empty($pool)) return ['pool' => $pool, 'fallback' => false];

    // Fallback: cùng loại, bất kỳ mức nào (khi ngân hàng không có đúng mức yêu cầu)
    $anyLevel = array_values(array_filter($bank, fn($q) => $q['question_type'] === $slot['type']));
    $ex = array_values(array_filter($anyLevel, fn($q) => mtnUnitMatch($q, $slot)));
    $pool = $ex;
    $seen = array_column($pool, '_qid');
    foreach ($anyLevel as $q) {
        if (!in_array($q['_qid'], $seen, true)) { $pool[] = $q; $seen[] = $q['_qid']; }
    }
    return ['pool' => $pool, 'fallback' => true];
}

function mtnQidSet(array $list): array {
    return array_values(array_unique(array_column($list, '_qid')));
}

function mtnDiffPercent(array $a, array $b): float {
    $sa = mtnQidSet($a); $sb = mtnQidSet($b);
    $total = max(count($sa), count($sb));
    if ($total === 0) return 100.0;
    $inter = count(array_intersect($sa, $sb));
    return round((1 - $inter / $total) * 100, 1);
}

function mtnFindReplacement(array $bank, array $q, array $usedQids): ?array {
    foreach ($bank as $cand) {
        if ($cand['_qid'] === $q['_qid']) continue;
        if (isset($usedQids[$cand['_qid']])) continue;
        if ($cand['question_type'] !== ($q['_slotType'] ?? '')) continue;
        if (!mtnLevelEq($q['_slotLevel'] ?? 'NB', $cand['level'])) continue;
        return $cand;
    }
    return null;
}

/**
 * Sinh các phương án đề từ slots.
 *
 * @return array { variants: array<int,array>, difference: array<string,float>, warnings: string[] }
 */
function mtnGenerateVariants(array $bank, array $slots, int $variantCount, float $minDiff = 50.0): array {
    $variantCount = max(2, min(4, $variantCount));
    $variants = array_fill(0, $variantCount, []);
    $warnings = [];
    $typeOrder = ['TNKQ' => 0, 'DS' => 1, 'TL' => 2];

    foreach ($slots as $slot) {
        $need = max(1, (int)$slot['count']);
        $picked = mtnPickPool($bank, $slot, $need * $variantCount);
        $pool = $picked['pool'];
        if (empty($pool)) {
            $warnings[] = 'Thiếu câu ' . $slot['type'] . ' mức ' . $slot['level'] . ' cho "' . ($slot['unit'] ?: $slot['topic']) . '".';
            continue;
        }
        if ($picked['fallback']) {
            $warnings[] = 'Không có câu ' . $slot['type'] . ' mức ' . $slot['level'] . ' cho "' . ($slot['unit'] ?: $slot['topic']) . '", đã thay bằng mức khác.';
        }
        $np = count($pool);
        if ($np < $need * $variantCount) {
            $warnings[] = 'Ngân hàng chỉ có ' . $np . ' câu ' . $slot['type'] . ' mức ' . $slot['level']
                . ' (cần ' . ($need * $variantCount) . '), một số câu sẽ trùng giữa các đề.';
        }
        for ($v = 0; $v < $variantCount; $v++) {
            $chosen = [];
            for ($i = 0; $i < $need; $i++) {
                $idx = ($v + $i * $variantCount) % $np;
                $tries = 0;
                while (in_array($idx, $chosen, true) && count($chosen) < $np && $tries < $np) {
                    $idx = ($idx + 1) % $np;
                    $tries++;
                }
                $chosen[] = $idx;
                $copy = $pool[$idx];
                $copy['points']     = $slot['points'];
                $copy['_slotType']  = $slot['type'];
                $copy['_slotLevel'] = $slot['level'];
                $variants[$v][] = $copy;
            }
        }
    }

    foreach ($variants as &$list) {
        usort($list, fn($a, $b) => ($typeOrder[$a['_slotType']] ?? 9) <=> ($typeOrder[$b['_slotType']] ?? 9));
    }
    unset($list);

    // Vòng lặp bù trừ để đạt ngưỡng khác biệt
    $usedGlobal = [];
    foreach ($variants as $list) foreach ($list as $q) $usedGlobal[$q['_qid']] = true;

    for ($attempt = 0; $attempt < 8; $attempt++) {
        $changed = false;
        for ($i = 0; $i < $variantCount; $i++) {
            for ($j = $i + 1; $j < $variantCount; $j++) {
                if (mtnDiffPercent($variants[$i], $variants[$j]) >= $minDiff) continue;
                $shared = array_intersect(mtnQidSet($variants[$i]), mtnQidSet($variants[$j]));
                if (empty($shared)) continue;
                foreach ($variants[$j] as $k => $q) {
                    if (!in_array($q['_qid'], $shared, true)) continue;
                    $rep = mtnFindReplacement($bank, $q, $usedGlobal);
                    if ($rep) {
                        $rep['points']     = $q['points'];
                        $rep['_slotType']  = $q['_slotType'];
                        $rep['_slotLevel'] = $q['_slotLevel'];
                        $variants[$j][$k] = $rep;
                        $usedGlobal[$rep['_qid']] = true;
                        $changed = true;
                    }
                }
            }
        }
        if (!$changed) break;
    }

    $difference = [];
    for ($i = 0; $i < $variantCount; $i++) {
        for ($j = $i + 1; $j < $variantCount; $j++) {
            $difference[chr(65 + $i) . '-' . chr(65 + $j)] = mtnDiffPercent($variants[$i], $variants[$j]);
        }
    }
    foreach ($difference as $pair => $pct) {
        if ($pct < $minDiff) {
            $warnings[] = "Đề {$pair} chỉ khác nhau {$pct}% (dưới {$minDiff}%) do ngân hàng thiếu câu. Nên bổ sung thêm câu hỏi.";
        }
    }

    return ['variants' => $variants, 'difference' => $difference, 'warnings' => $warnings];
}
