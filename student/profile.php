<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['student_id'];

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'Vui lòng nhập đầy đủ thông tin!';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Mật khẩu mới và xác nhận không khớp!';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        $messageType = 'danger';
    } else {
        // Load students data
        $studentsFile = __DIR__ . '/../admin/students.json';
        $students = [];

        if (file_exists($studentsFile)) {
            $students = json_decode(file_get_contents($studentsFile), true) ?: [];
        }

        // Find and update student
        $updated = false;
        foreach ($students as &$s) {
            if ($s['id'] === $studentId) {
                $storedPassword = $s['password'] ?? '123456';
                if ($currentPassword === $storedPassword) {
                    $s['password'] = $newPassword;
                    $updated = true;
                } else {
                    $message = 'Mật khẩu hiện tại không đúng!';
                    $messageType = 'danger';
                }
                break;
            }
        }
        unset($s);

        if ($updated) {
            // Save back to file
            if (file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $message = 'Đổi mật khẩu thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi khi lưu dữ liệu. Vui lòng thử lại!';
                $messageType = 'danger';
            }
        }
    }
}

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    $usernameInput = trim($_POST['student_username'] ?? '');
    $normalizedUsername = strtolower($usernameInput);
    $studentsFile = __DIR__ . '/../admin/students.json';
    $students = [];

    if (file_exists($studentsFile)) {
        $students = json_decode(file_get_contents($studentsFile), true) ?: [];
    }

    if ($usernameInput !== '' && !preg_match('/^[a-zA-Z0-9._-]{4,30}$/', $usernameInput)) {
        $message = 'Tên đăng nhập phải có 4-30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
        $messageType = 'danger';
    } else {
        $duplicate = false;
        foreach ($students as $s) {
            $existingCode = strtolower(trim((string)($s['code'] ?? '')));
            $existingUsername = strtolower(trim((string)($s['username'] ?? '')));
            $isCurrentStudent = ($s['id'] ?? null) === $studentId;

            if ($usernameInput !== '' && $normalizedUsername === $existingCode) {
                $duplicate = true;
                break;
            }

            if (!$isCurrentStudent && $usernameInput !== '' && $existingUsername !== '' && $normalizedUsername === $existingUsername) {
                $duplicate = true;
                break;
            }
        }

        if ($duplicate) {
            $message = 'Tên đăng nhập này đã được sử dụng hoặc trùng với mã học sinh. Vui lòng chọn tên khác.';
            $messageType = 'danger';
        } else {
            $updated = false;
            foreach ($students as &$s) {
                if (($s['id'] ?? null) === $studentId) {
                    if ($usernameInput === '') {
                        unset($s['username']);
                    } else {
                        $s['username'] = $normalizedUsername;
                    }
                    $updated = true;
                    break;
                }
            }
            unset($s);

            if ($updated && file_put_contents($studentsFile, json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $message = $usernameInput === '' ? 'Đã xoá tên đăng nhập.' : 'Cập nhật tên đăng nhập thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi khi lưu dữ liệu. Vui lòng thử lại!';
                $messageType = 'danger';
            }
        }
    }
}

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
require_once __DIR__ . '/../includes/student_premium_helper.php';
$premiumStatus = getStudentPremiumStatus($student['code']);
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
$alertClass = $messageType === 'success' ? 'success' : ($messageType === 'danger' ? 'danger' : 'info');
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
        .pw-wrap { position: relative; }
        .pw-wrap .form-control { padding-right: 46px; }
        .pw-toggle {
            position: absolute; right: 5px; top: 5px; bottom: 5px; width: 38px;
            border: none; background: transparent; color: var(--ink-faint);
            border-radius: 11px; cursor: pointer; font-size: 1rem;
        }
        .pw-toggle:hover { background: var(--page-bg); color: var(--ink-soft); }
        .strength { height: 6px; border-radius: 99px; background: var(--border); overflow: hidden; margin-top: 9px; }
        .strength-bar { display: block; height: 100%; width: 0%; border-radius: 99px; background: var(--coral); transition: width .3s ease, background .3s ease; }
        .strength-txt { font-size: .68rem; font-weight: 700; color: var(--ink-faint); margin-top: 6px; min-height: 1em; }

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
        <?php if ($message): ?>
                <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

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
                        <?php if ($premiumStatus['is_premium']): ?>
                            <span class="std-chip amber"><i class="bi bi-star-fill"></i> Premium</span>
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
                        <h3><i class="bi bi-person-gear me-2" style="color:var(--teal)"></i>Tên đăng nhập</h3>
                        <p class="sub">Đặt username để đăng nhập thay cho mã học sinh.</p>
                    </div>
                    <div class="prof-form">
                        <form method="POST" action="">
                            <input type="hidden" name="update_username" value="1">
                            <div class="prof-field">
                                <label for="student_username">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="student_username" name="student_username"
                                       value="<?php echo htmlspecialchars($usernameLabel); ?>"
                                       pattern="[A-Za-z0-9._-]{4,30}" maxlength="30"
                                       placeholder="Ví dụ: an.nguyen">
                                <div class="prof-hint">4-30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang. Để trống và lưu nếu muốn xoá.</div>
                            </div>
                            <button type="submit" class="btn std-btn std-teal prof-btn"><i class="bi bi-check2-circle me-2"></i>Lưu Tên Đăng Nhập</button>
                        </form>
                    </div>
                </section>

                <section class="prof-card reveal" style="animation-delay:.24s">
                    <div class="prof-card-head">
                        <h3><i class="bi bi-shield-lock-fill me-2" style="color:var(--coral)"></i>Đổi mật khẩu</h3>
                        <p class="sub">Mật khẩu phải có ít nhất 6 ký tự. Không cần đăng nhập lại sau khi đổi.</p>
                    </div>
                    <div class="prof-form">
                        <form method="POST" action="">
                            <input type="hidden" name="change_password" value="1">
                            <div class="prof-field">
                                <label for="current_password">Mật khẩu hiện tại *</label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button type="button" class="pw-toggle" data-target="current_password" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="prof-field">
                                <label for="new_password">Mật khẩu mới *</label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <button type="button" class="pw-toggle" data-target="new_password" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="strength"><span class="strength-bar" id="strengthBar"></span></div>
                                <div class="strength-txt" id="strengthTxt"></div>
                            </div>
                            <div class="prof-field mb-4">
                                <label for="confirm_password">Xác nhận mật khẩu mới *</label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Hiện/ẩn mật khẩu"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn std-btn std-coral prof-btn"><i class="bi bi-arrow-repeat me-2"></i>Đổi Mật Khẩu</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
    </div><!-- /.std-content -->

    <script>
        document.querySelectorAll('.pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-target'));
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
            });
        });

        var pw = document.getElementById('new_password');
        var bar = document.getElementById('strengthBar');
        var txt = document.getElementById('strengthTxt');
        if (pw && bar && txt) {
            pw.addEventListener('input', function () {
                var v = pw.value;
                var score = 0;
                if (v.length >= 6) score++;
                if (v.length >= 10) score++;
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                if (/\d/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v)) score++;
                bar.style.width = (score * 20) + '%';
                var colors = ['#FF5FA2', '#FF5FA2', '#FFB020', '#FFB020', '#00D4B5', '#00D4B5'];
                bar.style.background = colors[score];
                var labels = ['Rất yếu', 'Rất yếu', 'Yếu', 'Khá', 'Mạnh', 'Rất mạnh'];
                txt.textContent = v.length === 0 ? '' : 'Độ mạnh: ' + labels[score];
            });
        }
    </script>
<?php include '../includes/student_footer.php'; ?>
