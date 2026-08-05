<?php
// --- Xếp hạng trong lớp (theo điểm trung bình) ---
$classRank = null;
$classId = null;
$studentsData = json_decode(@file_get_contents(__DIR__ . '/../admin/students.json'), true) ?: [];
foreach ($studentsData as $sd) {
    if ((string)($sd['code'] ?? '') === (string)$studentCode) {
        $classId = $sd['class_id'] ?? null;
        break;
    }
}
if ($classId !== null) {
    $scores = [];
    foreach ($studentsData as $sd) {
        if ((string)($sd['class_id'] ?? '') !== (string)$classId) continue;
        $scFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $sd['code']) . '.json';
        $total = 0;
        $cnt = 0;
        if (file_exists($scFile)) {
            $arr = json_decode(file_get_contents($scFile), true);
            if (is_array($arr)) {
                foreach ($arr as $r) {
                    if (is_array($r)) {
                        $total += (float)($r['score'] ?? 0);
                        $cnt++;
                    }
                }
            }
        }
        $scores[(string)$sd['code']] = $cnt ? round($total / $cnt, 2) : -1;
    }
    $myAvg = $scores[(string)$studentCode] ?? -1;
    if ($myAvg >= 0) {
        $higher = 0;
        foreach ($scores as $c => $a) {
            if ($a > $myAvg) $higher++;
        }
        $classRank = $higher + 1;
    }
}

// --- Các biến hỗ trợ ---
$weekDays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
$dueByDate = [];
foreach ($pendingAssignments as $assign) {
    if (!empty($assign['_due_ts'])) {
        $dueByDate[date('Y-m-d', $assign['_due_ts'])] = true;
    }
}

$gradeSubjects = [];
foreach ($subjectProgress as $sid => $sp) {
    $gradeSubjects[] = ['id' => $sid, 'name' => $subjects[$sid] ?? ('Môn ' . $sid), 'avg' => $sp['avg'], 'count' => $sp['count']];
}
usort($gradeSubjects, function ($a, $b) {
    if ($b['count'] !== $a['count']) return $b['count'] - $a['count'];
    return $b['avg'] - $a['avg'];
});
$gradeSubjects = array_slice($gradeSubjects, 0, 4);

$gpaTotal = 0;
$gpaCount = 0;
foreach ($ownHistory as $hRec) {
    if (is_array($hRec) && isset($hRec['score'])) {
        $gpaTotal += (float)$hRec['score'];
        $gpaCount++;
    }
}
$gpa = $gpaCount ? number_format($gpaTotal / $gpaCount, 1, '.', '') : '—';

$vietDays = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
$greetSub = mb_strtoupper($studentClass ?: $studentClassCode) . ' · ' . mb_strtoupper($vietDays[(int)date('w')]) . ', ' . date('d/m');

$actionRows = [];
if ($heroExam) {
    $actionRows[] = [
        'icon' => 'bi-patch-check-fill',
        'ttl' => $heroExam['test_name'],
        'sub' => mb_strtoupper($heroExam['subject_name']) . ' · ' . $heroExam['total_questions'] . ' CÂU · ' . $heroExam['time_limit'] . ' PHÚT',
        'due' => 'urgent', 'due_txt' => 'Cần làm ngay',
        'btn' => 'Vào thi',
        'href' => '#',
        'onclick' => "startExam('" . htmlspecialchars($heroExam['id'], ENT_QUOTES) . "', '" . htmlspecialchars($heroExam['test_id'] ?? $heroExam['test_name'], ENT_QUOTES) . "', '" . htmlspecialchars($heroExam['test_name'], ENT_QUOTES) . "', " . (int)$heroExam['time_limit'] . ", '" . $heroExam['exam_type'] . "')"
    ];
}
foreach ($pendingAssignments as $assign) {
    if (count($actionRows) >= 3) break;
    $daysLeft = max(0, (int)ceil(($assign['_due_ts'] - time()) / 86400));
    $due = $daysLeft <= 1 ? 'urgent' : ($daysLeft <= 3 ? 'soon' : 'later');
    $dueTxt = $daysLeft === 0 ? 'HẾT HẠN HÔM NAY' : ($daysLeft === 1 ? 'CÒN 1 NGÀY' : 'CÒN ' . $daysLeft . ' NGÀY');
    $actionRows[] = [
        'icon' => 'bi-clipboard-check-fill',
        'ttl' => $assign['title'] ?? 'Bài tập',
        'sub' => 'HẠN NỘP: ' . mb_strtoupper(date('d/m/Y', $assign['_due_ts'])),
        'due' => $due, 'due_txt' => $dueTxt,
        'btn' => 'Nộp bài',
        'href' => 'assignments.php',
        'onclick' => ''
    ];
}
if (count($actionRows) < 3) {
    $actionRows[] = [
        'icon' => 'bi-bullseye',
        'ttl' => 'Ôn luyện ' . $practiceSubjectName,
        'sub' => 'CẢI THIỆN ĐIỂM SỐ MỖI NGÀY',
        'due' => 'later', 'due_txt' => 'MỌI LÚC',
        'btn' => 'Luyện tập',
        'href' => 'practice.php',
        'onclick' => ''
    ];
}
?>

<!-- GREETING -->
<div class="tpt-greet">
    <div class="tpt-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr(trim($studentName), 0, 1))); ?></div>
    <div>
        <div class="tpt-hi">Chào <?php echo htmlspecialchars(explode(' ', trim($studentName))[0]); ?></div>
        <div class="tpt-sub"><?php echo htmlspecialchars($greetSub); ?></div>
    </div>
    <span class="tpt-spacer"></span>
    <?php if ($classRank): ?>
        <span class="tpt-rank-chip"><i class="bi bi-graph-up-arrow"></i> #<?php echo $classRank; ?> lớp</span>
    <?php endif; ?>
    <span class="tpt-streak-chip"><i class="bi bi-fire"></i> <?php echo $studentStreak; ?> ngày</span>
</div>

<!-- VIỆC CẦN LÀM -->
<div class="focus-block">
    <div class="sec-label">Việc cần làm <span class="tag"><?php echo count($actionRows); ?> việc</span></div>
    <div class="tpt-card">
        <?php foreach ($actionRows as $row): ?>
            <div class="tpt-action">
                <div class="tpt-a-ic"><i class="bi <?php echo $row['icon']; ?>"></i></div>
                <div class="tpt-a-body">
                    <div class="tpt-a-ttl"><?php echo htmlspecialchars($row['ttl']); ?></div>
                    <div class="tpt-a-sub"><?php echo htmlspecialchars($row['sub']); ?></div>
                </div>
                <span class="tpt-a-due <?php echo $row['due']; ?>"><?php echo htmlspecialchars($row['due_txt']); ?></span>
                <?php if ($row['onclick']): ?>
                    <button class="tpt-a-btn" onclick="<?php echo $row['onclick']; ?>"><?php echo htmlspecialchars($row['btn']); ?></button>
                <?php else: ?>
                    <a class="tpt-a-btn" href="<?php echo $row['href']; ?>"><?php echo htmlspecialchars($row['btn']); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- LỊCH SẮP TỚI -->
<div class="focus-block">
    <div class="sec-label">Lịch sắp tới <span class="tag">7 ngày tới</span></div>
    <div class="tpt-cal">
        <?php $dayCursor = new DateTime(); for ($i = 0; $i < 7; $i++): ?>
            <?php
            $day = (clone $dayCursor);
            $d = $day->add(new DateInterval('P' . $i . 'D'));
            $dow = ($d->format('N') + 1) % 7;
            $isToday = ($i === 0);
            $hasDue = isset($dueByDate[$d->format('Y-m-d')]);
            ?>
            <div class="tpt-cal-day <?php echo $isToday ? 'today' : ''; ?>">
                <div class="tpt-dow"><?php echo $weekDays[$dow]; ?></div>
                <div class="tpt-num"><?php echo $d->format('d'); ?></div>
                <div class="tpt-dot <?php echo $isToday ? '' : ($hasDue ? 'gold' : 'none'); ?>"></div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- KẾT QUẢ HỌC TẬP -->
<div class="focus-block">
    <div class="sec-label">Kết quả học tập <span class="tag">Theo điểm số của bạn</span></div>
    <div class="tpt-grade">
        <?php if (count($gradeSubjects) === 0): ?>
            <div class="tpt-grade-cell" style="grid-column:1/-1;border-right:none;text-align:center;color:var(--ink-soft);font-size:.78rem;font-weight:600;">
                Bạn chưa có kết quả thi nào. Hãy làm bài đầu tiên nhé!
            </div>
        <?php else: ?>
            <?php foreach ($gradeSubjects as $gs): ?>
                <?php
                $avg = $gs['avg'];
                $trendClass = $avg >= 7.5 ? 'up' : ($avg >= 5 ? 'flat' : 'down');
                $trendTxt = $avg >= 7.5 ? '▲' : ($avg >= 5 ? '–' : '▼');
                ?>
                <div class="tpt-grade-cell">
                    <div class="tpt-subj"><?php echo htmlspecialchars($gs['name']); ?></div>
                    <div class="tpt-val-row">
                        <span class="tpt-val"><?php echo $avg > 0 ? number_format($avg, 1, '.', '') : '—'; ?></span>
                        <span class="tpt-trend <?php echo $trendClass; ?>"><?php echo $trendTxt; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="tpt-gpa">
        <span class="lbl">Điểm trung bình</span>
        <span class="num"><?php echo $gpa; ?></span>
    </div>
</div>

<!-- THÔNG BÁO -->
<div class="focus-block">
    <div class="sec-label">Thông báo <span class="tag">Mới nhất</span></div>
    <div class="tpt-card">
        <?php $notices = array_slice($recentActivity, 0, 3); ?>
        <?php foreach ($notices as $nt): ?>
            <?php
            $words = array_values(array_filter(explode(' ', trim($nt['title']))));
            $initials = '';
            foreach (array_slice($words, 0, 2) as $w) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            }
            $initials = $initials !== '' ? $initials : 'EV';
            ?>
            <div class="tpt-notice">
                <div class="tpt-notice-av"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <div class="tpt-notice-ttl"><?php echo $nt['title']; ?></div>
                    <div class="tpt-notice-txt"><?php echo $nt['sub']; ?></div>
                    <div class="tpt-notice-time">HÔM NAY</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
