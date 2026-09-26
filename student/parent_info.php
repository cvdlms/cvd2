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
$stdPageTitle = 'Thông tin phụ huynh';
$stdActiveNav = 'profile';

$stdNameParts = preg_split('/\s+/u', trim($studentName));
if (count($stdNameParts) > 1) {
    $stdGiven = array_slice($stdNameParts, 1);
    $stdInitials = strtoupper(mb_substr($stdGiven[0], 0, 1) . mb_substr(end($stdGiven), 0, 1));
} else {
    $stdInitials = strtoupper(mb_substr($studentName, 0, 2)) ?: 'HS';
}

$studentsFile = __DIR__ . '/../admin/students.json';

// Trường lưu trữ trong bản ghi học sinh
$parentFields = [
    'parent_name'     => 'Họ và tên phụ huynh',
    'parent_relation' => 'Quan hệ với học sinh',
    'parent_phone'    => 'Số điện thoại',
    'parent_email'    => 'Email',
    'parent_job'      => 'Nghề nghiệp',
    'parent_address'  => 'Địa chỉ',
];
$parentRelations = ['Bố', 'Mẹ', 'Ông', 'Bà', 'Người giám hộ khác'];

$errors = [];
$success = '';
$parent = array_fill_keys(array_keys($parentFields), '');

// Nạp dữ liệu hiện có để hiển thị
$students = get_json_data($studentsFile, []);
if (is_array($students)) {
    foreach ($students as $row) {
        if ((string)($row['id'] ?? '') === (string)$studentId
            || (string)($row['code'] ?? '') === (string)$studentCode) {
            foreach (array_keys($parentFields) as $key) {
                $parent[$key] = trim((string)($row[$key] ?? ''));
            }
            break;
        }
    }
}

// ------------------------------------------------------------------ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clean = [];
    foreach ($parentFields as $key => $label) {
        $clean[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if ($clean['parent_name'] === '') {
        $errors[] = 'Vui lòng nhập họ tên phụ huynh hoặc người giám hộ.';
    }
    if ($clean['parent_phone'] === '' && $clean['parent_email'] === '') {
        $errors[] = 'Cần có ít nhất một cách liên hệ: số điện thoại hoặc email.';
    }
    if ($clean['parent_phone'] !== '' && !preg_match('/^[0-9+\s().-]{8,20}$/', $clean['parent_phone'])) {
        $errors[] = 'Số điện thoại không hợp lệ (cần 8-20 ký tự số, có thể chứa +, khoảng trắng, (), . hoặc -).';
    }
    if ($clean['parent_email'] !== '' && !filter_var($clean['parent_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ($clean['parent_relation'] !== '' && !in_array($clean['parent_relation'], $parentRelations, true)) {
        $errors[] = 'Quan hệ không hợp lệ. Vui lòng chọn trong danh sách.';
    }

    if (!$errors) {
        $updated = false;
        $saved = update_json_data($studentsFile, function ($data) use ($studentId, $studentCode, $clean, &$updated) {
            if (!is_array($data)) { return $data; }
            foreach ($data as $idx => $row) {
                if ((string)($row['id'] ?? '') === (string)$studentId
                    || (string)($row['code'] ?? '') === (string)$studentCode) {
                    foreach ($clean as $key => $value) {
                        if ($value === '') {
                            unset($data[$idx][$key]);
                        } else {
                            $data[$idx][$key] = $value;
                        }
                    }
                    $updated = true;
                    break;
                }
            }
            return $data;
        }, null);

        if (!$saved) {
            $errors[] = 'Lỗi khi lưu thông tin. Vui lòng thử lại.';
        } elseif (!$updated) {
            $errors[] = 'Không tìm thấy hồ sơ học sinh trên hệ thống.';
        } else {
            $parent = $clean;
            $success = 'Đã lưu thông tin phụ huynh. Thông tin này giúp nhà trường liên hệ khi cần.';
        }
    } else {
        // giữ lại dữ liệu người dùng vừa nhập để không mất
        foreach (array_keys($parentFields) as $key) {
            $parent[$key] = $clean[$key];
        }
    }
}

include __DIR__ . '/../includes/eduvn_student_header.php';
?>

<p class="greeting">Thông tin phụ huynh</p>
<p class="greeting-sub">Thông tin liên hệ của phụ huynh/người giám hộ · <?php echo htmlspecialchars($studentName); ?> · <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?></p>

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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>
    Người liên hệ
  </div>

  <form method="POST" action="parent_info.php" style="margin-top:16px">
    <div class="form-grid">
      <div class="field full">
        <label for="parent_name">Họ và tên phụ huynh <span class="req">*</span></label>
        <input type="text" class="input" id="parent_name" name="parent_name" maxlength="120" required
               value="<?php echo htmlspecialchars($parent['parent_name']); ?>" placeholder="Ví dụ: Nguyễn Văn A">
      </div>

      <div class="field">
        <label for="parent_relation">Quan hệ với học sinh</label>
        <select class="input" id="parent_relation" name="parent_relation">
          <option value="">— Chọn quan hệ —</option>
          <?php foreach ($parentRelations as $rel): ?>
            <option value="<?php echo htmlspecialchars($rel); ?>"<?php echo $parent['parent_relation'] === $rel ? ' selected' : ''; ?>><?php echo htmlspecialchars($rel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="parent_phone">Số điện thoại</label>
        <input type="tel" class="input" id="parent_phone" name="parent_phone" maxlength="20"
               value="<?php echo htmlspecialchars($parent['parent_phone']); ?>" placeholder="09xx xxx xxx">
      </div>

      <div class="field">
        <label for="parent_email">Email</label>
        <input type="email" class="input" id="parent_email" name="parent_email" maxlength="120"
               value="<?php echo htmlspecialchars($parent['parent_email']); ?>" placeholder="email@example.com">
      </div>

      <div class="field">
        <label for="parent_job">Nghề nghiệp</label>
        <input type="text" class="input" id="parent_job" name="parent_job" maxlength="120"
               value="<?php echo htmlspecialchars($parent['parent_job']); ?>" placeholder="Ví dụ: Công nhân">
      </div>

      <div class="field full">
        <label for="parent_address">Địa chỉ</label>
        <textarea class="input" id="parent_address" name="parent_address" maxlength="255"
                  placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"><?php echo htmlspecialchars($parent['parent_address']); ?></textarea>
      </div>
    </div>

    <div class="form-note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
      <div>Chỉ cần nhập số điện thoại <strong>hoặc</strong> email. Thông tin được lưu vào hồ sơ học sinh và hiển thị với giáo viên chủ nhiệm và ban quản lý.</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
        Lưu thông tin
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
  <li><a href="change_password.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Đổi mật khẩu<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="profile.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/><path d="M9.5 12.8a2.6 2.6 0 0 0 5 0"/></svg>Hồ sơ cá nhân<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="help.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>Trợ giúp &amp; hỗ trợ<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="logout.php" style="color:#D14343"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>Đăng xuất</a></li>
</ul>

<?php include __DIR__ . '/../includes/eduvn_student_footer.php'; ?>
