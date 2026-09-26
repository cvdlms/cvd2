<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: ../index.php?role=student');
    exit;
}

$studentId = $_SESSION['student_id'];

// Các thao tác đổi mật khẩu / tên đăng nhập đã được gộp sang change_password.php

// Load student data
$studentsFile = __DIR__ . '/../admin/students.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$student = null;
$class = null;

if (file_exists($studentsFile)) {
    $students = json_decode(file_get_contents($studentsFile), true) ?: [];
    foreach ($students as $s) {
        if ($s['id'] === $studentId) {
            $student = $s;
            break;
        }
    }
}

if ($student && file_exists($classesFile)) {
    $classes = json_decode(file_get_contents($classesFile), true) ?: [];
    foreach ($classes as $c) {
        if ($c['id'] === $student['class_id']) {
            $class = $c;
            break;
        }
    }
}

if (!$student) {
    die('Student data not found.');
}

$title = 'Thông Tin Cá Nhân - EduVN';

// Learning stats from score history
$historyFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $student['code']) . '.json';
$history = [];
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true);
    if (!is_array($history)) $history = [];
}
$profileXp = 0;
$profileStreak = 0;
$profileScoreTotal = 0;
$profileScoreCount = 0;
$profileBest = 0;
$profileDays = [];
foreach ($history as $rec) {
    if (!is_array($rec)) continue;
    $sc = (float)($rec['score'] ?? 0);
    $profileXp += round($sc * 10, 0);
    $profileScoreTotal += $sc;
    $profileScoreCount++;
    $profileBest = max($profileBest, $sc);
    if (!empty($rec['timestamp'])) $profileDays[date('Y-m-d', strtotime($rec['timestamp']))] = true;
}
$profileCursor = new DateTime();
if (!isset($profileDays[$profileCursor->format('Y-m-d')])) $profileCursor->modify('-1 day');
while (isset($profileDays[$profileCursor->format('Y-m-d')])) {
    $profileStreak++;
    $profileCursor->modify('-1 day');
}
$profileLevel = intdiv($profileXp, 100) + 1;
$profileAvg = $profileScoreCount ? round($profileScoreTotal / $profileScoreCount, 1) : null;
$profileAvatarInitial = !empty($student['name']) ? mb_substr(trim($student['name']), 0, 1) : 'HS';
$genderRaw = $student['gender'] ?? '';
$genderLabel = ($genderRaw === 'Nam' || $genderRaw === 'M') ? 'Nam' : (($genderRaw === 'Nữ' || $genderRaw === 'F') ? 'Nữ' : 'Khác');
$usernameLabel = $student['username'] ?? '';
include '../includes/student_header.php';
?>
    <style>
        .reveal { opacity: 0; transform: translateY(16px); animation: profFadeUp .6s cubic-bezier(.22,.68,.35,1) forwards; }
        @keyframes profFadeUp { to { opacity: 1; transform: none; } }

        /* ---------- Hero ---------- */
        .prof-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: var(--card-bg);
            box-shadow: 0 18px 44px -24px rgba(32,34,58,.38);
            margin-bottom: 26px;
        }
        .prof-hero-cover {
            position: relative;
            height: 120px;
            background: linear-gradient(120deg, var(--violet), var(--coral));
        }
        .prof-hero-cover::before, .prof-hero-cover::after {
            content: ''; position: absolute; border-radius: 50%;
            background: rgba(255,255,255,.16);
        }
        .prof-hero-cover::before { width: 190px; height: 190px; right: -40px; top: -90px; }
        .prof-hero-cover::after { width: 130px; height: 130px; right: 100px; top: -60px; background: rgba(255,255,255,.12); }
        .prof-hero-cover .spark { position: absolute; width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,.8); }
        .prof-hero-cover .spark.s1 { left: 26%; top: 22px; }
        .prof-hero-cover .spark.s2 { left: 58%; top: 14px; width: 4px; height: 4px; opacity: .7; }
        .prof-hero-cover .spark.s3 { right: 26%; top: 40px; width: 5px; height: 5px; opacity: .65; }
        .prof-hero-main {
            display: flex; align-items: flex-end; gap: 20px;
            padding: 0 26px 20px;
            margin-top: -54px;
            position: relative;
        }
        .prof-avatar {
            width: 106px; height: 106px;
            border-radius: 28px;
            background: linear-gradient(150deg, var(--violet), var(--coral));
            box-shadow: 0 12px 26px -12px rgba(32,34,58,.45), 0 0 0 6px var(--card-bg);
            display: grid; place-items: center;
            color: #fff; font-family: var(--display); font-weight: 800; font-size: 2.5rem;
            flex-shrink: 0;
        }
        .prof-hero-meta { min-width: 0; padding-top: 60px; }
        .prof-greet { font-size: .75rem; font-weight: 700; color: var(--violet); text-transform: uppercase; letter-spacing: .08em; }
        .prof-name { font-family: var(--display); font-weight: 800; font-size: 1.6rem; color: var(--ink); margin: 3px 0 7px; line-height: 1.15; }
        .prof-chips { display: flex; flex-wrap: wrap; gap: 7px; }
        .prof-stats {
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--border);
            padding: 16px 26px 18px;
        }
        .prof-stat { text-align: center; border-right: 1px solid var(--border); padding: 0 8px; }
        .prof-stat:last-child { border-right: none; }
        .prof-stat .v { font-family: var(--display); font-weight: 800; font-size: 1.3rem; color: var(--ink); }
        .prof-stat .l { font-size: .64rem; font-weight: 700; color: var(--ink-faint); text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }

        /* ---------- Body grid ---------- */
        .prof-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 26px; align-items: start; margin-bottom: 34px; }
        .prof-card {
            background: var(--card-bg);
            border-radius: 22px;
            box-shadow: 0 12px 32px -22px rgba(32,34,58,.32);
            overflow: hidden;
        }
        .prof-card-head { padding: 20px 24px 0; }
        .prof-card-head h3 { font-family: var(--display); font-weight: 800; font-size: 1.05rem; color: var(--ink); margin: 0; }
        .prof-card-head .sub { font-size: .76rem; color: var(--ink-soft); margin: 4px 0 0; font-weight: 500; }

        .prof-info { padding: 6px 24px 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 0 18px; }
        .prof-info-item { display: flex; gap: 12px; padding: 14px 2px; border-bottom: 1px solid var(--border); }
        .prof-info-item .ic {
            width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
            display: grid; place-items: center; font-size: .95rem;
            background: var(--violet-light); color: var(--violet-dark);
        }
        .prof-info-item:nth-child(even) .ic { background: var(--coral-light); color: var(--coral-dark); }
        .prof-info-item .k { font-size: .64rem; font-weight: 700; color: var(--ink-faint); text-transform: uppercase; letter-spacing: .05em; }
        .prof-info-item .v { font-size: .9rem; font-weight: 600; color: var(--ink); margin-top: 2px; word-break: break-word; }

        /* ---------- Settings ---------- */
        .prof-settings { display: flex; flex-direction: column; gap: 26px; }
        .prof-form { padding: 18px 24px 24px; }
        .prof-field { margin-bottom: 16px; }
        .prof-field label { display: block; font-size: .78rem; font-weight: 700; color: var(--ink-soft); margin-bottom: 7px; }
        .prof-field .form-control {
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 10px 14px;
            font-weight: 600;
            color: var(--ink);
            background: #fff;
        }
        .prof-field .form-control:focus { border-color: var(--violet); box-shadow: 0 0 0 4px var(--violet-light); }
        .prof-hint { font-size: .72rem; color: var(--ink-faint); font-weight: 500; margin-top: 6px; }

        .prof-btn { width: 100%; justify-content: center; }

        @media (max-width: 991.98px) {
            .prof-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 575.98px) {
            .prof-stats { grid-template-columns: repeat(2, 1fr); }
            .prof-stat:nth-child(2) { border-right: none; }
            .prof-stat:nth-child(-n+2) { padding-bottom: 12px; }
            .prof-hero-main { flex-direction: column; align-items: flex-start; }
            .prof-info { grid-template-columns: 1fr; }
            .prof-hero-cover { height: 78px; }
            .prof-avatar { width: 84px; height: 84px; font-size: 2rem; border-radius: 22px; }
        }
    </style>

    <div class="std-content">
    <div class="container prof-page">
        <!-- HERO -->
        <section class="prof-hero reveal">
            <div class="prof-hero-cover">
                <span class="spark s1"></span><span class="spark s2"></span><span class="spark s3"></span>
            </div>
            <div class="prof-hero-main">
                <div class="prof-avatar"><?php echo htmlspecialchars($profileAvatarInitial); ?></div>
                <div class="prof-hero-meta">
                    <div class="prof-greet">Hồ sơ học sinh · <?php echo htmlspecialchars($genderLabel); ?></div>
                    <h1 class="prof-name"><?php echo htmlspecialchars($student['name']); ?></h1>
                    <div class="prof-chips">
                        <span class="std-chip violet"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($class ? $class['name'] : 'Chưa xếp lớp'); ?></span>
                        <span class="std-chip amber"><i class="bi bi-person-badge-fill"></i> <?php echo htmlspecialchars($student['code']); ?></span>
                        <?php if ($usernameLabel !== ''): ?>
                            <span class="std-chip teal"><i class="bi bi-at"></i> <?php echo htmlspecialchars($usernameLabel); ?></span>
                        <?php else: ?>
                            <span class="std-chip coral"><i class="bi bi-person-dash-fill"></i> Chưa đặt username</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="prof-stats">
                <div class="prof-stat"><div class="v">Cấp <?php echo $profileLevel; ?></div><div class="l">Cấp độ</div></div>
                <div class="prof-stat"><div class="v"><?php echo number_format($profileXp); ?></div><div class="l">XP tích lũy</div></div>
                <div class="prof-stat"><div class="v"><?php echo $profileStreak; ?></div><div class="l">Ngày liên tiếp</div></div>
                <div class="prof-stat"><div class="v"><?php echo $profileAvg !== null ? number_format($profileAvg, 1, '.', '') : '—'; ?></div><div class="l">Điểm trung bình</div></div>
            </div>
        </section>

        <div class="prof-grid">
            <!-- THÔNG TIN -->
            <section class="prof-card reveal" style="animation-delay:.08s">
                <div class="prof-card-head">
                    <h3><i class="bi bi-person-vcard-fill me-2" style="color:var(--violet)"></i>Thông tin cá nhân</h3>
                    <p class="sub">Thông tin quản lý học sinh trên hệ thống EduVN.</p>
                </div>
                <div class="prof-info">
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <div class="k">Họ và tên</div>
                            <div class="v"><?php echo htmlspecialchars($student['name']); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <div class="k">Mã học sinh</div>
                            <div class="v"><?php echo htmlspecialchars($student['code']); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-at"></i></div>
                        <div>
                            <div class="k">Tên đăng nhập</div>
                            <div class="v"><?php echo htmlspecialchars($usernameLabel !== '' ? $usernameLabel : 'Chưa thiết lập'); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="k">Lớp</div>
                            <div class="v"><?php echo htmlspecialchars($class ? $class['name'] : 'N/A'); ?> <?php echo htmlspecialchars($class ? '(' . $class['code'] . ')' : ''); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-gender-ambiguous"></i></div>
                        <div>
                            <div class="k">Giới tính</div>
                            <div class="v"><?php echo htmlspecialchars($genderLabel); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-calendar-heart-fill"></i></div>
                        <div>
                            <div class="k">Ngày sinh</div>
                            <div class="v"><?php echo htmlspecialchars($student['birth_date'] ?? 'Chưa cập nhật'); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="k">Email</div>
                            <div class="v"><?php echo htmlspecialchars($student['email'] ?: 'Chưa cập nhật'); ?></div>
                        </div>
                    </div>
                    <div class="prof-info-item">
                        <div class="ic"><i class="bi bi-sticky-fill"></i></div>
                        <div>
                            <div class="k">Ghi chú</div>
                            <div class="v"><?php echo htmlspecialchars($student['notes'] ?: 'Không có ghi chú'); ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TÀI KHOẢN -->
            <div class="prof-settings">
                <section class="prof-card reveal" style="animation-delay:.16s">
                    <div class="prof-card-head">
                        <h3><i class="bi bi-person-gear me-2" style="color:var(--teal)"></i>Cài đặt đăng nhập</h3>
                        <p class="sub">Tên đăng nhập và mật khẩu nằm trong cùng một trang.</p>
                    </div>
                    <div class="prof-form">
                        <div class="prof-field">
                            <label>Tên đăng nhập hiện tại</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($usernameLabel !== '' ? $usernameLabel : 'Chưa thiết lập'); ?>" readonly>
                            <div class="prof-hint">Đăng nhập bằng mã học sinh <strong><?php echo htmlspecialchars($student['code']); ?></strong> nếu chưa đặt tên đăng nhập.</div>
                        </div>
                        <a href="change_password.php" class="btn std-btn std-teal prof-btn"><i class="bi bi-pencil-square me-2"></i>Đổi tên đăng nhập / mật khẩu</a>
                    </div>
                </section>

                <section class="prof-card reveal" style="animation-delay:.24s">
                    <div class="prof-card-head">
                        <h3><i class="bi bi-people-fill me-2" style="color:var(--violet)"></i>Liên hệ phụ huynh</h3>
                        <p class="sub">Thông tin người bảo hộ để nhà trường liên hệ khi cần.</p>
                    </div>
                    <div class="prof-form">
                        <a href="parent_info.php" class="btn std-btn prof-btn"><i class="bi bi-arrow-repeat me-2"></i>Quản lý thông tin phụ huynh</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
    </div><!-- /.std-content -->
<?php include '../includes/student_footer.php'; ?>
