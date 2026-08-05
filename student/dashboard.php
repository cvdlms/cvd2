<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';
require_once __DIR__ . '/../includes/exam_helper.php';
require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';
$isNu = (getStudentGender($studentCode) === 'Nữ');

// Get premium status
$premiumStatus = getStudentPremiumStatus($studentCode);

// Function to create URL-friendly slug (shared transliteration with exam_helper)
function create_slug($string) {
    return exam_create_slug($string);
}

// Determine student grade from class code
$prefix = substr($studentClassCode, 0, 1);
$studentGrade = 'khoi' . $prefix;

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    if (!empty($subject['id'])) {
        $subjects[$subject['id']] = $subject['name'];
    }
}

// Load student scores to check attempts
$studentScoreFile = __DIR__ . '/../shared/scores/student_score.json';
$studentScores = [];
if (file_exists($studentScoreFile)) {
    $studentScores = json_decode(file_get_contents($studentScoreFile), true) ?: [];
}

// Load student's own detailed history (per-student file)
$ownHistoryFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '.json';
$ownHistory = [];
if (file_exists($ownHistoryFile)) {
    $ownHistory = json_decode(file_get_contents($ownHistoryFile), true) ?: [];
    if (!is_array($ownHistory)) $ownHistory = [];
}

// Compute XP / level / streak from history
$studentXp = 0;
$studentStreak = 0;
$studentDays = [];
$subjectProgress = []; // subject_id => ['avg' => float, 'count' => int]
$badges = [];
$studentBestScore = 0;
foreach ($ownHistory as $hRec) {
    if (!is_array($hRec)) continue;
    $hScore = (float)($hRec['score'] ?? 0);
    $studentXp += round($hScore * 10, 0);
    $studentBestScore = max($studentBestScore, $hScore);
    if (!empty($hRec['timestamp'])) {
        $studentDays[date('Y-m-d', strtotime($hRec['timestamp']))] = true;
    }
    $sid = $hRec['subject_id'] ?? null;
    if ($sid !== null && $hScore > 0) {
        if (!isset($subjectProgress[$sid])) {
            $subjectProgress[$sid] = ['total' => 0, 'count' => 0];
        }
        $subjectProgress[$sid]['total'] += $hScore;
        $subjectProgress[$sid]['count']++;
    }
}
foreach ($subjectProgress as $sid => $sp) {
    $subjectProgress[$sid]['avg'] = $sp['count'] ? round($sp['total'] / $sp['count'] * 10) / 10 : 0;
}

// Consecutive-day streak ending today or yesterday
$cursor = new DateTime();
if (!isset($studentDays[$cursor->format('Y-m-d')])) {
    $cursor->modify('-1 day');
}
while (isset($studentDays[$cursor->format('Y-m-d')])) {
    $studentStreak++;
    $cursor->modify('-1 day');
}

$studentLevel = intdiv($studentXp, 100) + 1;
$studentLevelProgress = $studentXp % 100;

// Badges from real achievements
if ($studentBestScore >= 9) $badges[] = ['icon' => 'trophy', 'color' => 'amber', 'text' => 'Điểm 9+ đầu'];
if ($studentStreak >= 7) $badges[] = ['icon' => 'flame', 'color' => 'coral', 'text' => $studentStreak . ' ngày streak'];
if (count($ownHistory) >= 5) $badges[] = ['icon' => 'check', 'color' => 'teal', 'text' => 'Chăm chỉ ' . count($ownHistory) . ' bài'];
if ($premiumStatus['is_premium']) $badges[] = ['icon' => 'star', 'color' => 'violet', 'text' => 'Thành viên Premium'];
if (count($badges) < 3) $badges[] = ['icon' => 'trophy', 'color' => 'violet', 'text' => 'Sẵn sàng chinh phục'];

// Load approved exams for the student's grade
$approvedExams = [];
$examsDir = __DIR__ . '/../teacher/exams/' . $studentGrade;
if (is_dir($examsDir)) {
    $subjectDirs = scandir($examsDir);
    foreach ($subjectDirs as $subjectDir) {
        if ($subjectDir === '.' || $subjectDir === '..') continue;
        if (preg_match('/subject_(\d+)/', $subjectDir, $matches)) {
            $subjectId = (int)$matches[1];
            $subjectPath = $examsDir . '/' . $subjectDir;
            if (is_dir($subjectPath)) {
                $files = scandir($subjectPath);
                foreach ($files as $file) {
                    if (preg_match('/\.json$/', $file)) {
                        $examPath = $subjectPath . '/' . $file;
                        $examData = json_decode(file_get_contents($examPath), true);
                        if ($examData && ($examData['approved'] ?? false)) {
                            $testId = $examData['test_id'] ?? null;
                            $testName = $examData['test_name'] ?? $file;

                            if ($testId) {
                                $examId = $testId;
                            } else {
                                $examCode = create_slug($testName);
                                $examId = $subjectId . '_' . $examCode;
                            }

                            $hasCompleted = false;
                            foreach ($studentScores as $score) {
                                if (($score['student_id'] ?? '') !== $studentCode) continue;
                                $storedId = $score['exam_id'] ?? '';
                                if ($storedId === $examId) {
                                    if (!isset($score['subject_id']) || $score['subject_id'] == $subjectId) {
                                        $hasCompleted = true;
                                        break;
                                    }
                                }
                                if ($testId && $storedId === $testId) {
                                    if (!isset($score['subject_id']) || $score['subject_id'] == $subjectId) {
                                        $hasCompleted = true;
                                        break;
                                    }
                                }
                            }

                            $examType = $examData['exam_type'] ?? 'practice';

                            $shouldShow = false;
                            if (!$hasCompleted) {
                                $shouldShow = true;
                            } elseif ($hasCompleted && $examType === 'practice' && $premiumStatus['is_premium']) {
                                $shouldShow = true;
                            }

                            if ($shouldShow) {
                                $approvedExams[] = [
                                    'id' => $examId,
                                    'test_id' => $testId,
                                    'test_name' => $testName,
                                    'subject_id' => $subjectId,
                                    'subject_name' => $subjects[$subjectId] ?? 'Môn học',
                                    'file' => $file,
                                    'exam_type' => $examType,
                                    'has_completed' => $hasCompleted,
                                    'total_questions' => $examData['total_questions'] ?? (isset($examData['questions']) ? count($examData['questions']) : 0),
                                    'time_limit' => is_numeric($examData['time_limit'] ?? null) ? (int)$examData['time_limit'] : 45,
                                    'created_at' => $examData['created_at'] ?? null
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
}

// Sort: uncompleted first, official first, then newest
usort($approvedExams, function ($a, $b) {
    $aDone = $a['has_completed'] ? 1 : 0;
    $bDone = $b['has_completed'] ? 1 : 0;
    if ($aDone !== $bDone) return $aDone - $bDone;
    $aOff = $a['exam_type'] === 'official' ? 0 : 1;
    $bOff = $b['exam_type'] === 'official' ? 0 : 1;
    if ($aOff !== $bOff) return $aOff - $bOff;
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

// Hero exam = first available
$heroExam = $approvedExams[0] ?? null;

// Load assignments for this class (for chips + activity)
$assignmentsData = json_decode(@file_get_contents(__DIR__ . '/../data/assignments.json'), true) ?: [];
$myAssignments = [];
foreach ($assignmentsData as $assign) {
    $classNames = $assign['class_names'] ?? [];
    if (!is_array($classNames)) $classNames = [];
    $assignClass = $assign['class_name'] ?? '';
    if ($assignClass && !in_array($assignClass, $classNames)) $classNames[] = $assignClass;
    $matchesClass = in_array($studentClass, $classNames) || in_array($studentClassCode, $classNames);
    if ($matchesClass) {
        $assign['_due_ts'] = strtotime($assign['due_date'] ?? '');
        $myAssignments[] = $assign;
    }
}
// Pending assignments = not expired
$pendingAssignments = array_filter($myAssignments, function ($a) {
    return $a['_due_ts'] && $a['_due_ts'] > time();
});

// Practice suggestion = subject with lowest average (among subjects having results)
$practiceSubjectId = null;
$practiceSubjectLowest = 11;
foreach ($subjectProgress as $sid => $sp) {
    if ($sp['avg'] < $practiceSubjectLowest) {
        $practiceSubjectLowest = $sp['avg'];
        $practiceSubjectId = $sid;
    }
}
$practiceSubjectName = $practiceSubjectId !== null ? ($subjects[$practiceSubjectId] ?? 'Môn học') : 'mọi môn';

// Recent activity (timeline)
$recentActivity = [];
if (!empty($ownHistory)) {
    $sortedHistory = $ownHistory;
    usort($sortedHistory, function ($a, $b) {
        return strtotime($b['timestamp'] ?? '') - strtotime($a['timestamp'] ?? '');
    });
    $lastResult = $sortedHistory[0];
    if (!empty($lastResult['score']) || isset($lastResult['score'])) {
        $score = (float)($lastResult['score'] ?? 0);
        $recentActivity[] = [
            'tone' => $score >= 8 ? 'amber' : ($score >= 5 ? 'teal' : 'pink'),
            'title' => 'Bạn đạt ' . number_format($score, 1, '.', '') . ' điểm',
            'sub' => ($lastResult['test_name'] ?? 'Bài kiểm tra') . ' · ' . timeAgo($lastResult['timestamp'] ?? '')
        ];
    }
}
if (!empty($approvedExams)) {
    $newestExam = $approvedExams[0];
    $recentActivity[] = [
        'tone' => '',
        'title' => 'Có bài kiểm tra mới dành cho bạn',
        'sub' => htmlspecialchars($newestExam['test_name']) . ' · Môn ' . htmlspecialchars($newestExam['subject_name'])
    ];
}
foreach ($pendingAssignments as $assign) {
    if (count($recentActivity) >= 3) break;
    $daysLeft = max(0, (int)ceil(($assign['_due_ts'] - time()) / 86400));
    $recentActivity[] = [
        'tone' => 'pink',
        'title' => 'Bài tập "' . htmlspecialchars($assign['title'] ?? '') . '" còn ' . $daysLeft . ' ngày',
        'sub' => 'Hạn nộp: ' . date('d/m/Y', $assign['_due_ts'])
    ];
}
if (count($recentActivity) < 3) {
    $recentActivity[] = [
        'tone' => '',
        'title' => 'Hãy làm bài kiểm tra đầu tiên nào!',
        'sub' => 'Cùng nhau học giỏi nhé'
    ];
}

function timeAgo($ts) {
    $diff = time() - strtotime($ts);
    if ($diff < 3600) return 'vừa xong';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 7 * 86400) return floor($diff / 86400) . ' ngày trước';
    return date('d/m/Y', strtotime($ts));
}

// Subject ring colors
$ringColors = [
    'violet' => '#7B5CFA', 'coral' => '#FF5FA2', 'amber' => '#FFC93C', 'teal' => '#00D4B5',
    'pink' => '#FF8AC0', 'blue' => '#5B8DEF'
];
$ringColorKeys = array_keys($ringColors);

$title = 'Trang chủ - EduVN';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Unbounded:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/theme-eduvn-student.css">
    <link rel="stylesheet" href="../styles/main.css">
</head>
<body class="student-page<?php echo $isNu ? ' theme-nu' : ' theme-nam'; ?>">
    <?php include '../includes/student_navbar.php'; ?>

    <div class="std-content std-dash<?php echo $isNu ? ' nu-dash' : ' tpt-dash'; ?>">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'refresh_not_allowed'): ?>
            <div class="std-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><strong>Cảnh báo:</strong> Bạn không được phép refresh trang trong khi thi. Bài thi đã bị hủy.</div>
            </div>
        <?php endif; ?>

        <a class="mobile-profile" href="profile.php">
            <div class="mobile-profile-id">
                <div class="mobile-avatar"><?php echo htmlspecialchars(mb_substr(trim($studentName), 0, 1)); ?></div>
                <div>
                    <div style="font-family:var(--display);font-weight:700;font-size:.85rem"><?php echo htmlspecialchars($studentName); ?></div>
                    <div style="font-size:.66rem;color:var(--ink-soft);font-weight:600"><?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?> · Cấp <?php echo $studentLevel; ?></div>
                </div>
            </div>
            <div class="mobile-profile-go"><i class="bi bi-chevron-right"></i></div>
        </a>

        <?php if ($isNu): ?>
        <?php include 'dashboard_nu.php'; ?>
        <?php else: ?>
        <?php include 'dashboard_nam.php'; ?>
        <?php endif; ?>

        <!-- EXAMS -->
        <div class="focus-block" id="kiem-tra">
            <div class="sec-label">Danh Sách Bài Kiểm Tra <span class="tag"><?php echo count($approvedExams); ?> bài</span></div>
            <?php if (count($approvedExams) === 0): ?>
                <div class="card" style="padding:34px 20px;">
                    <div class="std-empty">
                        <div class="e-icon"><i class="bi bi-inbox"></i></div>
                        <h5>Chưa có bài kiểm tra</h5>
                        <p>Chưa có bài kiểm tra nào được duyệt cho khối của bạn. Hãy quay lại sau nhé!</p>
                        <a href="practice.php" class="btn std-btn std-violet">Luyện tập ngay</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($approvedExams as $exam): ?>
                        <?php $ec = $exam['exam_type'] === 'official' ? 'e-official' : 'e-practice'; ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="dash-exam-card <?php echo $ec; ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="dec-exam">
                                        <i class="bi <?php echo $exam['exam_type'] === 'official' ? 'bi-patch-check-fill' : 'bi-bullseye'; ?>"></i>
                                    </div>
                                    <div style="min-width:0">
                                        <h4 class="text-truncate"><?php echo htmlspecialchars($exam['test_name']); ?></h4>
                                        <p class="d-sub"><i class="bi bi-book me-1"></i><?php echo htmlspecialchars($exam['subject_name']); ?></p>
                                    </div>
                                </div>
                                <div class="d-meta">
                                    <?php if ($exam['exam_type'] === 'official'): ?>
                                        <span class="std-chip coral">📝 Chính thức</span>
                                    <?php else: ?>
                                        <span class="std-chip teal">🎯 Luyện tập</span>
                                    <?php endif; ?>
                                    <?php if ($exam['has_completed']): ?>
                                        <span class="std-chip amber">✓ Đã thi</span>
                                    <?php endif; ?>
                                    <span class="std-chip violet"><i class="bi bi-clock"></i> <?php echo (int)$exam['time_limit']; ?> phút</span>
                                    <span class="std-chip violet"><i class="bi bi-layers"></i> <?php echo (int)$exam['total_questions']; ?> câu</span>
                                </div>
                                <div class="d-meta">
                                    <span class="std-chip violet" id="attempts-<?php echo htmlspecialchars($exam['id']); ?>">Đang tải...</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
                                    <button class="btn std-btn <?php echo $exam['exam_type'] === 'official' ? 'std-coral' : 'std-violet'; ?> btn-sm" onclick="startExam('<?php echo htmlspecialchars($exam['id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($exam['test_id'] ?? $exam['test_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($exam['test_name'], ENT_QUOTES); ?>', <?php echo (int)$exam['time_limit']; ?>, '<?php echo $exam['exam_type']; ?>')">
                                        <?php if ($exam['has_completed']): ?>
                                            <i class="bi bi-arrow-repeat me-1"></i>Thi Lại
                                        <?php else: ?>
                                            <i class="bi bi-rocket-takeoff me-1"></i>Bắt Đầu Thi
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- AI RECOMMENDATIONS -->
        <div id="recommendationsSection" class="focus-block" style="display: none;">
            <div class="sec-label">Gợi Ý Dành Riêng Cho Bạn</div>
            <div id="recommendationsList" class="row g-3"></div>
        </div>

        <!-- JOURNEY -->
        <div class="journey-block">
            <div class="sec-label">Hành trình môn học <span class="tag">Theo điểm số của bạn</span></div>
            <div class="card">
                <div class="journey">
                    <?php
                    $journeySubjects = [];
                    foreach ($subjectProgress as $sid => $sp) {
                        $journeySubjects[] = ['id' => $sid, 'name' => $subjects[$sid] ?? ('Môn ' . $sid), 'pct' => min(100, round($sp['avg'] * 10))];
                    }
                    if (count($journeySubjects) === 0 && $heroExam) {
                        $journeySubjects[] = ['id' => $heroExam['subject_id'], 'name' => $heroExam['subject_name'], 'pct' => 0];
                    }
                    if (count($journeySubjects) === 0) {
                        $journeySubjects[] = ['id' => 0, 'name' => 'Bắt đầu ngay', 'pct' => 0];
                    }
                    $ringIcons = ['book', 'pen', 'globe2', 'flask', 'code-slash', 'calculator'];
                    foreach ($journeySubjects as $i => $js):
                        $key = $ringColorKeys[$i % count($ringColorKeys)];
                        $color = $ringColors[$key];
                        $light = $key === 'violet' ? '#F1EDFF' : ($key === 'coral' ? '#FFEBF3' : ($key === 'amber' ? '#FFF6DC' : ($key === 'teal' ? '#E2FBF7' : ($key === 'pink' ? '#FFEBF3' : '#E8EFFE'))));
                        $pct = $js['pct'];
                        $circ = 2 * 3.14159 * 26;
                        $dash = round($pct / 100 * $circ, 2);
                    ?>
                    <div class="journey-node">
                        <div class="ring-wrap">
                            <svg width="72" height="72" viewBox="0 0 72 72">
                                <circle cx="36" cy="36" r="26" fill="none" stroke="<?php echo $light; ?>" stroke-width="6"/>
                                <circle cx="36" cy="36" r="26" fill="none" stroke="<?php echo $color; ?>" stroke-width="6" stroke-linecap="round" stroke-dasharray="<?php echo $dash; ?> <?php echo $circ; ?>"/>
                            </svg>
                            <div class="center-icon" style="color:<?php echo $color; ?>"><i class="bi bi-<?php echo $ringIcons[$i % count($ringIcons)]; ?> icon"></i></div>
                        </div>
                        <div class="pct"><?php echo $pct; ?>%</div>
                        <div class="name"><?php echo htmlspecialchars($js['name']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- BOTTOM GRID -->
        <div class="bottom-grid" style="margin-bottom:28px;">
            <div class="card">
                <div class="card-title">Huy hiệu của bạn</div>
                <div class="stickers">
                    <?php foreach ($badges as $b): ?>
                        <div class="sticker">
                            <div class="disc" style="background:<?php
                                echo $b['color'] === 'amber' ? 'linear-gradient(150deg,var(--amber),#FFDE7A)' : ($b['color'] === 'coral' ? 'linear-gradient(150deg,var(--coral),#FF8AC0)' : ($b['color'] === 'teal' ? 'linear-gradient(150deg,var(--teal),#5CEBD4)' : 'linear-gradient(150deg,var(--violet),#9C84FF)'));
                            ?>">
                                <i class="bi <?php echo $b['icon'] === 'trophy' ? 'bi-trophy-fill' : ($b['icon'] === 'flame' ? 'bi-fire' : ($b['icon'] === 'star' ? 'bi-star-fill' : 'bi-check-lg')); ?> icon"></i>
                            </div>
                            <div class="txt"><?php echo htmlspecialchars($b['text']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Hoạt động gần đây</div>
                <div class="timeline">
                    <?php foreach ($recentActivity as $act): ?>
                        <div class="t-item <?php echo $act['tone']; ?>">
                            <div class="t-dot"></div>
                            <div class="t-ttl"><?php echo $act['title']; ?></div>
                            <div class="t-sub"><?php echo $act['sub']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- STATISTICS -->
        <div class="sec-label">Thống Kê Học Tập <span class="tag">Tự động cập nhật</span></div>
        <div class="std-stats">
            <div class="std-stat c-violet">
                <div class="s-icon"><i class="bi bi-collection"></i></div>
                <div>
                    <div class="s-num" id="totalExams">-</div>
                    <div class="s-label">Tổng bài thi</div>
                </div>
            </div>
            <div class="std-stat c-amber">
                <div class="s-icon"><i class="bi bi-award"></i></div>
                <div>
                    <div class="s-num" id="averageScore">-</div>
                    <div class="s-label">Điểm trung bình</div>
                </div>
            </div>
            <div class="std-stat c-teal">
                <div class="s-icon"><i class="bi bi-trophy"></i></div>
                <div>
                    <div class="s-num" id="highestScore">-</div>
                    <div class="s-label">Điểm cao nhất</div>
                </div>
            </div>
            <div class="std-stat c-coral">
                <div class="s-icon"><i class="bi bi-flag"></i></div>
                <div>
                    <div class="s-num" id="passRate">-</div>
                    <div class="s-label">Tỷ lệ đỗ</div>
                </div>
            </div>
        </div>

        <!-- RESULTS TABLE -->
        <div class="sec-label">Lịch Sử Bài Thi</div>
        <div class="card std-card">
            <div class="card-body">
                <table id="resultsTable" class="table std-table">
                    <thead>
                        <tr>
                            <th>Bài Thi</th>
                            <th>Lần Thi</th>
                            <th>Điểm</th>
                            <th>Xếp Loại</th>
                            <th>Thời Gian</th>
                            <th>Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Exam Start Confirmation Modal -->
    <div class="modal fade" id="examModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác Nhận Bắt Đầu Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="std-alert warn">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Lưu ý quan trọng:</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>Bài thi sẽ bắt đầu ngay khi bạn nhấn "Bắt Đầu"</li>
                                <li>Thời gian làm bài là <strong id="examTimeLimit">45</strong> phút</li>
                                <li>Không được phép rời khỏi trang trong khi thi</li>
                                <li>Kết quả sẽ được lưu tự động khi hết thời gian</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mb-0">Bạn có chắc muốn bắt đầu bài thi <strong id="examTypeText"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn std-btn std-ghost" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn std-btn std-violet" id="confirmStartBtn">Bắt Đầu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Detail Modal -->
    <div class="modal fade" id="examDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="examDetailContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn std-btn std-teal" onclick="printExamDetail()">In Chi Tiết</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/student_footer.php'; ?>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let selectedExamType = '';
        let selectedExamId = '';
        let selectedExamName = '';
        let selectedExamTypeFlag = 'practice';
        let resultsTable;
        let allResults = [];

        function startExam(examId, testId, testName, timeLimit = 45, examType = 'practice') {
            selectedExamType = examId;
            selectedExamId = testId;
            selectedExamName = testName;
            selectedExamTypeFlag = examType;
            document.getElementById('examTypeText').textContent = testName;
            timeLimit = parseInt(timeLimit) || 45;
            document.getElementById('examTimeLimit').textContent = timeLimit;
            new bootstrap.Modal(document.getElementById('examModal')).show();
        }

        document.getElementById('confirmStartBtn').addEventListener('click', function() {
            fetch(`api/check_attempts.php?test_id=${encodeURIComponent(selectedExamId)}&exam_type=${selectedExamTypeFlag}`)
                .then(response => response.json())
                .then(data => {
                    if (data.can_take) {
                        localStorage.removeItem(`exam_${selectedExamType}`);
                        window.location.href = `exam.php?type=${encodeURIComponent(selectedExamType)}`;
                    } else {
                        const message = data.unlimited
                            ? `Premium: Bạn có thể thi lại không giới hạn! Lần thi: ${data.attempts + 1}`
                            : data.message || `Bạn đã hết lượt thi. Đã thi: ${data.attempts} lần.`;
                        alert(message);
                        bootstrap.Modal.getInstance(document.getElementById('examModal')).hide();
                    }
                })
                .catch(error => {
                    console.error('Error checking attempts:', error);
                    alert('Lỗi kiểm tra số lần thi. Vui lòng thử lại.');
                });
        });

        async function loadResults() {
            try {
                const response = await fetch('api/get_student_results.php');
                const data = await response.json();
                if (data.success) {
                    allResults = data.results;
                    displayStatistics();
                    displayResultsTable();
                    loadRecommendations();
                } else {
                    document.querySelector('#resultsTable tbody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-muted">Chưa có kết quả thi nào.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading results:', error);
            }
        }

        function displayStatistics() {
            const totalExams = allResults.length;
            if (totalExams === 0) {
                document.getElementById('totalExams').textContent = '0';
                document.getElementById('averageScore').textContent = '-';
                document.getElementById('highestScore').textContent = '-';
                document.getElementById('passRate').textContent = '-';
                return;
            }
            let totalScore = 0;
            let highestScore = 0;
            let passedExams = 0;
            allResults.forEach(result => {
                if (result.score !== null) {
                    totalScore += result.score;
                    if (result.score > highestScore) highestScore = result.score;
                    if (result.score >= 5.0) passedExams++;
                }
            });
            const averageScore = (totalScore / totalExams).toFixed(1);
            const passRate = ((passedExams / totalExams) * 100).toFixed(1) + '%';
            document.getElementById('totalExams').textContent = totalExams;
            document.getElementById('averageScore').textContent = averageScore;
            document.getElementById('highestScore').textContent = highestScore.toFixed(1);
            document.getElementById('passRate').textContent = passRate;
        }

        function displayResultsTable() {
            if (resultsTable) resultsTable.destroy();
            resultsTable = $('#resultsTable').DataTable({
                data: allResults,
                columns: [
                    { data: null, render: function(d) { return d.test_name || d.exam_type; } },
                    { data: 'attempt' },
                    {
                        data: 'score',
                        render: function(d) {
                            if (d === null) return '<span class="text-muted">Chưa hoàn thành</span>';
                            return `<strong>${d}</strong>`;
                        }
                    },
                    {
                        data: 'score',
                        render: function(d) {
                            if (d === null) return '<span class="badge bg-secondary">Chưa hoàn thành</span>';
                            let grade = 'F', badgeClass = 'bg-danger';
                            if (d >= 9.0) { grade = 'A+'; badgeClass = 'bg-success'; }
                            else if (d >= 8.5) { grade = 'A'; badgeClass = 'bg-success'; }
                            else if (d >= 8.0) { grade = 'B+'; badgeClass = 'bg-info'; }
                            else if (d >= 7.0) { grade = 'B'; badgeClass = 'bg-info'; }
                            else if (d >= 6.5) { grade = 'C+'; badgeClass = 'bg-warning'; }
                            else if (d >= 6.0) { grade = 'C'; badgeClass = 'bg-warning'; }
                            else if (d >= 5.5) { grade = 'D+'; badgeClass = 'bg-warning'; }
                            else if (d >= 5.0) { grade = 'D'; badgeClass = 'bg-warning'; }
                            return `<span class="badge ${badgeClass} score-badge">${grade}</span>`;
                        }
                    },
                    {
                        data: 'timestamp',
                        render: function(d) { return new Date(d).toLocaleString('vi-VN'); }
                    },
                    {
                        data: null,
                        render: function(d) {
                            if (!d.completed) return '<span class="text-muted">Chưa hoàn thành</span>';
                            return `<button class="btn btn-sm btn-info" onclick="viewExamDetail('${d.id}')">👁️ Xem</button>`;
                        },
                        orderable: false
                    }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json' },
                responsive: true,
                order: [[4, 'desc']],
                pageLength: 10
            });
        }

        async function viewExamDetail(examId) {
            try {
                const response = await fetch(`api/get_exam_result.php?exam_id=${examId}`);
                const data = await response.json();
                if (data.success) {
                    const result = data.result;
                    const modal = new bootstrap.Modal(document.getElementById('examDetailModal'));
                    const content = document.getElementById('examDetailContent');
                    content.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Loại thi:</strong> ${result.test_name || result.exam_type}</div>
                            <div class="col-md-6"><strong>Lần thi:</strong> ${result.attempt}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Điểm số:</strong> <span class="h4 text-primary">${result.score}/10</span></div>
                            <div class="col-md-6"><strong>Số câu đúng:</strong> ${result.correct_answers}/${result.total_questions}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Thời gian:</strong> ${new Date(result.timestamp).toLocaleString('vi-VN')}</div>
                            <div class="col-md-6"><strong>Trạng thái:</strong> <span class="badge bg-success">Hoàn thành</span></div>
                        </div>
                        <h5 class="mt-4 mb-3">Chi Tiết Bài Làm</h5>
                        <div class="accordion" id="questionsAccordion">
                            ${result.question_results.map((q, index) => `
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ${q.is_correct ? '' : 'bg-danger text-white'}" type="button" data-bs-toggle="collapse" data-bs-target="#question${index}">
                                            Câu ${index + 1}: ${q.is_correct ? '✅ Đúng' : '❌ Sai'}
                                        </button>
                                    </h2>
                                    <div id="question${index}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Câu hỏi:</strong> ${q.question}</p>
                                            <p><strong>Đáp án đúng:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.correct_answer)
                                                    : q.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>
                                            ${q.user_answer !== null ? `<p><strong>Đáp án của bạn:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.user_answer)
                                                    : q.user_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>` : '<p><strong>Đáp án của bạn:</strong> <em>Chưa trả lời</em></p>'}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                    modal.show();
                } else {
                    alert('Không thể tải chi tiết bài thi');
                }
            } catch (error) {
                console.error('Error loading exam detail:', error);
                alert('Lỗi tải chi tiết bài thi: ' + error.message);
            }
        }

        function printExamDetail() {
            const content = document.getElementById('examDetailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chi Tiết Bài Thi</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .accordion-item { margin-bottom: 10px; border: 1px solid #ddd; }
                        .accordion-button { background: #f8f9fa; border: none; padding: 10px; width: 100%; text-align: left; }
                        .accordion-body { padding: 10px; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .bg-success { background: #28a745; color: white; }
                        .text-primary { color: #007bff; }
                        .h4 { font-size: 1.5rem; font-weight: bold; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        async function loadAttemptsForExam(examId, badgeId, testId, examType = 'practice') {
            try {
                const response = await fetch(`api/check_attempts.php?test_id=${encodeURIComponent(testId)}&exam_type=${examType}`);
                const data = await response.json();
                if (data.success) {
                    const badge = document.getElementById(badgeId);
                    if (!badge) return;
                    let attemptsText = '';
                    if (data.unlimited) {
                        attemptsText = `${data.attempts} lần (Premium ∞)`;
                    } else if (data.can_take) {
                        attemptsText = `${data.attempts}/${data.attempts + data.remaining}`;
                    } else {
                        attemptsText = `${data.attempts}/${data.attempts} (Hết lượt)`;
                    }
                    badge.textContent = attemptsText;
                }
            } catch (error) {
                const badge = document.getElementById(badgeId);
                if (badge) badge.textContent = 'Lỗi';
            }
        }

        async function loadRecommendations() {
            try {
                const response = await fetch('../api/get_recommendations.php');
                const data = await response.json();
                if (data.success && data.recommendations.length > 0) {
                    displayRecommendations(data.recommendations);
                    document.getElementById('recommendationsSection').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading recommendations:', error);
            }
        }

        function displayRecommendations(recommendations) {
            const container = document.getElementById('recommendationsList');
            container.innerHTML = '';
            const colorMap = {
                'danger': 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)',
                'warning': 'linear-gradient(135deg, #f79d00 0%, #f5af19 100%)',
                'success': 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
                'info': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'primary': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
            };
            recommendations.forEach(rec => {
                const gradient = colorMap[rec.color] || colorMap['info'];
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4 mb-3';
                card.innerHTML = `
                    <div class="card h-100 shadow-sm" style="border-left: 4px solid ${rec.color};">
                        <div class="card-body">
                            <h5 class="card-title"><span style="font-size:1.5rem">${rec.icon}</span> ${rec.title}</h5>
                            <p class="card-text text-muted">${rec.description}</p>
                            <a href="${rec.action.url}" class="btn btn-sm text-white" style="background:${gradient};">${rec.action.label} <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function loadAllAttempts() {
            <?php foreach ($approvedExams as $exam): ?>
                loadAttemptsForExam(
                    '<?php echo htmlspecialchars($exam['id'], ENT_QUOTES); ?>',
                    'attempts-<?php echo htmlspecialchars($exam['id'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($exam['test_id'] ?? $exam['test_name'], ENT_QUOTES); ?>',
                    '<?php echo $exam['exam_type'] ?? 'practice'; ?>'
                );
            <?php endforeach; ?>
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadResults();
            loadAllAttempts();
        });
    </script>
</body>
</html>
