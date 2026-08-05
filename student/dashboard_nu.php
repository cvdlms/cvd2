<?php
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
$gradeDots = ['violet', 'pink', 'amber', 'teal'];

$gpaTotal = 0;
$gpaCount = 0;
foreach ($ownHistory as $hRec) {
    if (is_array($hRec) && isset($hRec['score'])) {
        $gpaTotal += (float)$hRec['score'];
        $gpaCount++;
    }
}
$gpa = $gpaCount ? number_format($gpaTotal / $gpaCount, 1, '.', '') : '—';
$gpaRank = $gpa === '—' ? 'Chưa có điểm' : ($gpa >= 8 ? 'Giỏi' : ($gpa >= 6.5 ? 'Khá' : ($gpa >= 5 ? 'Trung bình' : 'Cần cố gắng')));

$actionRows = [];
if ($heroExam) {
    $actionRows[] = [
        'grad' => 'linear-gradient(150deg,var(--pink),#FF8AC0)',
        'icon' => 'bi-patch-check-fill',
        'ttl' => $heroExam['test_name'],
        'sub' => $heroExam['subject_name'] . ' · ' . $heroExam['total_questions'] . ' câu · ' . $heroExam['time_limit'] . ' phút',
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
    $dueTxt = $daysLeft === 0 ? 'Hết hạn hôm nay' : ($daysLeft === 1 ? 'Còn 1 ngày' : 'Còn ' . $daysLeft . ' ngày');
    $actionRows[] = [
        'grad' => 'linear-gradient(150deg,var(--amber),#FFDE7A)',
        'icon' => 'bi-clipboard-check-fill',
        'ttl' => $assign['title'] ?? 'Bài tập',
        'sub' => 'Hạn nộp: ' . date('d/m/Y', $assign['_due_ts']),
        'due' => $due, 'due_txt' => $dueTxt,
        'btn' => 'Nộp bài',
        'href' => 'assignments.php',
        'onclick' => ''
    ];
}
if (count($actionRows) < 3) {
    $actionRows[] = [
        'grad' => 'linear-gradient(150deg,var(--violet),#9C84FF)',
        'icon' => 'bi-bullseye',
        'ttl' => 'Ôn luyện ' . $practiceSubjectName,
        'sub' => 'Cải thiện điểm số mỗi ngày',
        'due' => 'later', 'due_txt' => 'Mọi lúc',
        'btn' => 'Luyện tập',
        'href' => 'practice.php',
        'onclick' => ''
    ];
}
?>

<!-- GREETING -->
<div class="greet-row">
    <svg class="mascot" viewBox="0 0 100 100" aria-hidden="true">
        <line x1="50" y1="14" x2="50" y2="4" stroke="#FADCEA" stroke-width="3" stroke-linecap="round"/>
        <path d="M50 0l2.5 5-2.5 5-2.5-5z" fill="var(--amber)"/>
        <path d="M50 15c22 0 36 15 34 35-1.5 17-15 28-34 28S17.5 67 16 51C14 31 28 15 50 15z" fill="url(#numgrad)"/>
        <defs><linearGradient id="numgrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF8AC0"/><stop offset="1" stop-color="var(--pink)"/></linearGradient></defs>
        <circle cx="38" cy="54" r="4.2" fill="var(--ink)"/><circle cx="64" cy="54" r="4.2" fill="var(--ink)"/>
        <circle cx="26" cy="63" r="5.5" fill="#fff" opacity="0.45"/><circle cx="74" cy="63" r="5.5" fill="#fff" opacity="0.45"/>
        <path d="M40 66q10 8 20 0" stroke="var(--ink)" stroke-width="3" fill="none" stroke-linecap="round"/>
    </svg>
    <div class="greet-bubble">
        <div class="hi">Chào <span><?php echo htmlspecialchars(explode(' ', trim($studentName))[0]); ?></span>!</div>
        <div class="sub"><?php
            if (count($pendingAssignments) > 0) {
                echo 'Hôm nay bạn còn ' . count($pendingAssignments) . ' việc cần hoàn thành đó';
            } elseif ($heroExam) {
                echo 'Có bài kiểm tra mới đang chờ bạn đó';
            } else {
                echo 'Hãy ôn luyện mỗi ngày để tiến bộ nhé!';
            }
        ?></div>
    </div>
    <div class="streak-chip">
        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1 3-3 4-3 8a3 3 0 0 0 6 0c0-1-.5-2-.5-2 2 1 3 3.5 3 5.5A5.5 5.5 0 0 1 12 19a5.5 5.5 0 0 1-5.5-5.5C6.5 8 12 6 12 2z"/></svg>
        <?php echo $studentStreak; ?> ngày liên tiếp
    </div>
</div>

<!-- VIỆC CẦN LÀM -->
<div class="focus-block">
    <div class="sec-label">Việc cần làm <span class="tag"><?php echo count($actionRows); ?> việc</span></div>
    <div class="nu-card">
        <?php foreach ($actionRows as $row): ?>
            <div class="nu-action">
                <div class="a-ic" style="background:<?php echo $row['grad']; ?>"><i class="bi <?php echo $row['icon']; ?>"></i></div>
                <div class="a-body">
                    <div class="a-ttl"><?php echo htmlspecialchars($row['ttl']); ?></div>
                    <div class="a-sub"><?php echo htmlspecialchars($row['sub']); ?></div>
                </div>
                <span class="a-due <?php echo $row['due']; ?>"><?php echo htmlspecialchars($row['due_txt']); ?></span>
                <?php if ($row['onclick']): ?>
                    <button class="a-btn" onclick="<?php echo $row['onclick']; ?>"><?php echo htmlspecialchars($row['btn']); ?></button>
                <?php else: ?>
                    <a class="a-btn" href="<?php echo $row['href']; ?>"><?php echo htmlspecialchars($row['btn']); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- LỊCH SẮP TỚI -->
<div class="focus-block">
    <div class="sec-label">Lịch sắp tới <span class="tag">7 ngày tới</span></div>
    <div class="nu-cal">
        <?php $dayCursor = new DateTime(); for ($i = 0; $i < 7; $i++): ?>
            <?php
            $day = (clone $dayCursor);
            $d = $day->add(new DateInterval('P' . $i . 'D'));
            $dow = ($d->format('N') + 1) % 7;
            $dowLabel = $weekDays[$dow];
            $isToday = ($i === 0);
            $hasDue = isset($dueByDate[$d->format('Y-m-d')]);
            ?>
            <div class="cal-day <?php echo $isToday ? 'today' : ''; ?>">
                <div class="dow"><?php echo $dowLabel; ?></div>
                <div class="num"><?php echo $d->format('d'); ?></div>
                <div class="cal-dot <?php echo $isToday ? '' : ($hasDue ? 'amber' : 'none'); ?>"></div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- KẾT QUẢ HỌC TẬP -->
<div class="focus-block">
    <div class="sec-label">Kết quả học tập <span class="tag">Theo điểm số của bạn</span></div>
    <div class="nu-grade">
        <?php if (count($gradeSubjects) === 0): ?>
            <div class="grade-cell" style="grid-column:1/-1;border-right:none;text-align:center;color:var(--ink-soft);font-size:.78rem;font-weight:600;">
                Bạn chưa có kết quả thi nào. Hãy làm bài đầu tiên nhé!
            </div>
        <?php else: ?>
            <?php foreach ($gradeSubjects as $i => $gs): ?>
                <?php
                $dotKey = $gradeDots[$i % count($gradeDots)];
                $dotColor = $dotKey === 'violet' ? 'var(--violet)' : ($dotKey === 'pink' ? 'var(--pink)' : ($dotKey === 'amber' ? 'var(--amber)' : 'var(--teal)'));
                $avg = $gs['avg'];
                $trendClass = $avg >= 7.5 ? 'up' : ($avg >= 5 ? 'flat' : 'down');
                $trendTxt = $avg >= 7.5 ? '▲' : ($avg >= 5 ? '–' : '▼');
                ?>
                <div class="grade-cell">
                    <div class="subj"><span class="dot" style="background:<?php echo $dotColor; ?>"></span><?php echo htmlspecialchars($gs['name']); ?></div>
                    <div class="val-row">
                        <span class="val"><?php echo $avg > 0 ? number_format($avg, 1, '.', '') : '—'; ?></span>
                        <span class="trend <?php echo $trendClass; ?>"><?php echo $trendTxt; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="nu-gpa">
        <span class="lbl">Điểm trung bình</span>
        <span class="num"><?php echo $gpa; ?> · <?php echo htmlspecialchars($gpaRank); ?></span>
    </div>
</div>

<!-- THÔNG BÁO -->
<div class="focus-block">
    <div class="sec-label">Thông báo <span class="tag">Mới nhất</span></div>
    <div class="nu-card">
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
            <div class="nu-notice">
                <div class="notice-av"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <div class="notice-ttl"><?php echo $nt['title']; ?></div>
                    <div class="notice-txt"><?php echo $nt['sub']; ?></div>
                    <div class="notice-time">Hôm nay</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
