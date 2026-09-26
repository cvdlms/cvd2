<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';
require_once __DIR__ . '/../includes/json_db_helper.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';
$studentId = $_SESSION['student_id'] ?? '';

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';
$stdPageTitle = 'Đổi mật khẩu';
$stdActiveNav = 'profile';

$stdNameParts = preg_split('/\s+/u', trim($studentName));
if (count($stdNameParts) > 1) {
    $stdGiven = array_slice($stdNameParts, 1);
    $stdInitials = strtoupper(mb_substr($stdGiven[0], 0, 1) . mb_substr(end($stdGiven), 0, 1));
} else {
    $stdInitials = strtoupper(mb_substr($studentName, 0, 2)) ?: 'HS';
}

$studentsFile = __DIR__ . '/../admin/students.json';
$systemConfigFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($systemConfigFile)
    ? json_decode(file_get_contents($systemConfigFile), true)
    : [];
$minLength = max(6, (int)($systemConfig['security']['password_min_length'] ?? 6));

$errors = [];
$success = '';

// Nạp tên đăng nhập hiện tại
$usernameValue = '';
$students = get_json_data($studentsFile, []);
if (is_array($students)) {
    foreach ($students as $row) {
        if ((string)($row['id'] ?? '') === (string)$studentId
            || (string)($row['code'] ?? '') === (string)$studentCode) {
            $usernameValue = trim((string)($row['username'] ?? ''));
            break;
        }
    }
}

// ------------------------------------------------------------------ POST
$formAction = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (string)($_POST['form_action'] ?? 'password')
    : '';

if ($formAction === 'username') {
    $usernameInput = trim((string)($_POST['student_username'] ?? ''));
    $normalizedUsername = strtolower($usernameInput);

    if ($usernameInput !== '' && !preg_match('/^[a-zA-Z0-9._-]{4,30}$/', $usernameInput)) {
        $errors[] = 'Tên đăng nhập phải có 4-30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
    } elseif ($usernameInput !== '' && $normalizedUsername === strtolower(trim($usernameValue))) {
        $errors[] = 'Tên đăng nhập không thay đổi.';
    }

    if (!$errors) {
        $found = false;
        $duplicate = false;

        $saved = update_json_data($studentsFile, function ($data) use ($studentId, $studentCode, $normalizedUsername, $usernameInput, &$found, &$duplicate) {
            if (!is_array($data)) { return $data; }

            $isSelf = function ($row) use ($studentId, $studentCode) {
                return (string)($row['id'] ?? '') === (string)$studentId
                    || (string)($row['code'] ?? '') === (string)$studentCode;
            };

            if ($normalizedUsername !== '') {
                foreach ($data as $row) {
                    $existingCode = strtolower(trim((string)($row['code'] ?? '')));
                    $existingUsername = strtolower(trim((string)($row['username'] ?? '')));

                    if ($normalizedUsername === $existingCode
                        || (!$isSelf($row) && $existingUsername !== '' && $normalizedUsername === $existingUsername)) {
                        $duplicate = true;
                        return $data;
                    }
                }
            }

            foreach ($data as $idx => $row) {
                if ($isSelf($row)) {
                    $found = true;
                    if ($usernameInput === '') {
                        unset($data[$idx]['username']);
                    } else {
                        $data[$idx]['username'] = $normalizedUsername;
                    }
                    break;
                }
            }
            return $data;
        }, null);

        if (!$saved) {
            $errors[] = 'Lỗi khi lưu tên đăng nhập. Vui lòng thử lại.';
        } elseif ($duplicate) {
            $errors[] = 'Tên đăng nhập này đã được sử dụng hoặc trùng với mã học sinh. Vui lòng chọn tên khác.';
        } elseif (!$found) {
            $errors[] = 'Không tìm thấy hồ sơ học sinh trên hệ thống.';
        } else {
            $usernameValue = $usernameInput === '' ? '' : $normalizedUsername;
            $success = $usernameInput === ''
                ? 'Đã xoá tên đăng nhập. Từ giờ bạn đăng nhập bằng mã học sinh.'
                : 'Cập nhật tên đăng nhập thành công! Dùng tên này để đăng nhập từ lần sau.';
        }
    }
} elseif ($formAction === 'password') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $errors[] = 'Vui lòng điền đầy đủ cả ba trường.';
    }
    if ($newPassword !== '' && $confirmPassword !== '' && $newPassword !== $confirmPassword) {
        $errors[] = 'Mật khẩu mới và xác nhận không khớp.';
    }
    if ($newPassword !== '' && strlen($newPassword) < $minLength) {
        $errors[] = "Mật khẩu mới phải có ít nhất {$minLength} ký tự.";
    }
    if ($newPassword !== '' && $currentPassword !== '' && $currentPassword === $newPassword) {
        $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    }

    if (!$errors) {
        $found = false;
        $passwordOk = false;

        $saved = update_json_data($studentsFile, function ($data) use ($studentId, $studentCode, $currentPassword, $newPassword, &$found, &$passwordOk) {
            if (!is_array($data)) { return $data; }
            foreach ($data as $idx => $row) {
                if ((string)($row['id'] ?? '') === (string)$studentId
                    || (string)($row['code'] ?? '') === (string)$studentCode) {
                    $found = true;
                    if (($row['password'] ?? '123456') !== $currentPassword) {
                        return $data;
                    }
                    $passwordOk = true;
                    $data[$idx]['password'] = $newPassword;
                    break;
                }
            }
            return $data;
        }, null);

        if (!$saved) {
            $errors[] = 'Lỗi khi lưu mật khẩu mới. Vui lòng thử lại.';
        } elseif (!$found) {
            $errors[] = 'Không tìm thấy hồ sơ học sinh trên hệ thống.';
        } elseif (!$passwordOk) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        } else {
            $success = 'Đổi mật khẩu thành công! Bạn vẫn đang đăng nhập, không cần đăng nhập lại.';
        }
    }
}

include __DIR__ . '/../includes/eduvn_student_header.php';
?>

<p class="greeting">Đổi mật khẩu</p>
<p class="greeting-sub">Cập nhật mật khẩu đăng nhập của tài khoản học sinh · <?php echo htmlspecialchars($studentName); ?></p>

<?php if ($success): ?>
  <div class="alert alert-success">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    <div><?php echo htmlspecialchars($success); ?></div>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
    <div>
      <?php foreach ($errors as $err): ?>
        <div><?php echo htmlspecialchars($err); ?></div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<section class="panel-card" style="padding:22px;margin-bottom:22px">
  <div class="panel-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
    Mật khẩu đăng nhập
  </div>

  <form method="POST" action="change_password.php" autocomplete="off" style="margin-top:16px">
    <input type="hidden" name="form_action" value="password">
    <div class="field">
      <label for="current_password">Mật khẩu hiện tại <span class="req">*</span></label>
      <div class="pw-wrap">
        <input type="password" class="input" id="current_password" name="current_password" required
               autocomplete="current-password" placeholder="Nhập mật khẩu đang dùng">
        <button type="button" class="pw-toggle" data-target="current_password" aria-label="Hiện mật khẩu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <div class="field">
      <label for="new_password">Mật khẩu mới <span class="req">*</span></label>
      <div class="pw-wrap">
        <input type="password" class="input" id="new_password" name="new_password" required
               minlength="<?php echo $minLength; ?>" autocomplete="new-password" placeholder="Tối thiểu <?php echo $minLength; ?> ký tự">
        <button type="button" class="pw-toggle" data-target="new_password" aria-label="Hiện mật khẩu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <div class="strength"><span class="strength-bar" id="strengthBar"></span></div>
      <div class="strength-txt" id="strengthTxt"></div>
    </div>

    <div class="field">
      <label for="confirm_password">Xác nhận mật khẩu mới <span class="req">*</span></label>
      <div class="pw-wrap">
        <input type="password" class="input" id="confirm_password" name="confirm_password" required
               minlength="<?php echo $minLength; ?>" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
        <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Hiện mật khẩu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <div class="form-note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
      <div>Nên kết hợp chữ in hoa, chữ thường, số và ký tự đặc biệt. Không chia sẻ mật khẩu cho bạn bè — nếu bị lộ hãy đổi ngay.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
        Lưu mật khẩu mới
      </button>
      <a class="btn btn-ghost" href="dashboard.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Quay lại
      </a>
    </div>
  </form>
</section>

<p class="section-title" style="margin-bottom:10px">Tên đăng nhập</p>
<section class="panel-card" style="padding:22px;margin-bottom:22px">
  <div class="panel-title">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5Z"/><path d="m4 8.5 8 4.5 8-4.5M12 13v7"/></svg>
    Đăng nhập bằng tên thay cho mã học sinh
  </div>

  <form method="POST" action="change_password.php" autocomplete="off" style="margin-top:16px">
    <input type="hidden" name="form_action" value="username">

    <div class="alert alert-info" style="margin-bottom:16px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
      <div>
        Mã học sinh: <strong><?php echo htmlspecialchars($studentCode); ?></strong> ·
        Tên đăng nhập hiện tại:
        <strong><?php echo $usernameValue !== '' ? htmlspecialchars($usernameValue) : 'Chưa thiết lập'; ?></strong>
        <?php if ($usernameValue === ''): ?>
          — đang đăng nhập bằng mã học sinh.
        <?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label for="student_username">Tên đăng nhập mới</label>
      <input type="text" class="input" id="student_username" name="student_username"
             value="<?php echo htmlspecialchars($usernameValue); ?>"
             pattern="[A-Za-z0-9._\-]{4,30}" maxlength="30"
             placeholder="Ví dụ: an.nguyen">
      <div class="field-hint">4-30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang. Để trống và lưu nếu muốn xoá.</div>
    </div>

    <div class="form-note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
      <div>Tên đăng nhập phải khác với mã học sinh và không trùng với tên đăng nhập của học sinh khác. Mật khẩu không thay đổi.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        Lưu tên đăng nhập
      </button>
      <a class="btn btn-ghost" href="dashboard.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Quay lại
      </a>
    </div>
  </form>
</section>

<p class="section-title" style="margin-bottom:10px">Tài khoản khác</p>
<ul class="list-menu">
  <li><a href="parent_info.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>Thông tin phụ huynh<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="profile.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/><path d="M9.5 12.8a2.6 2.6 0 0 0 5 0"/></svg>Hồ sơ cá nhân<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="help.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>Trợ giúp &amp; hỗ trợ<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="logout.php" style="color:#D14343"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>Đăng xuất</a></li>
</ul>

<?php include __DIR__ . '/../includes/eduvn_student_footer.php'; ?>
