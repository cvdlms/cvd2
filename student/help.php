<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';
require_once __DIR__ . '/../includes/json_db_helper.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';
$stdPageTitle = 'Trợ giúp & hỗ trợ';
$stdActiveNav = 'help';

$stdNameParts = preg_split('/\s+/u', trim($studentName));
if (count($stdNameParts) > 1) {
    $stdGiven = array_slice($stdNameParts, 1);
    $stdInitials = strtoupper(mb_substr($stdGiven[0], 0, 1) . mb_substr(end($stdGiven), 0, 1));
} else {
    $stdInitials = strtoupper(mb_substr($studentName, 0, 2)) ?: 'HS';
}

$systemConfig = get_json_data(__DIR__ . '/../admin/system_config.json', []);
$schoolName = trim((string)($systemConfig['system']['school_name'] ?? '')) ?: 'Trường THCS Nguyễn Du';
$supportPhone = trim((string)($systemConfig['support']['phone'] ?? ''));
$supportEmail = trim((string)($systemConfig['support']['email'] ?? ''));
$officeHours = trim((string)($systemConfig['support']['office_hours'] ?? '')) ?: 'Sáng: 7h30 – 11h30 · Chiều: 13h30 – 17h00';

$requestsFile = __DIR__ . '/../data/student_support_requests.json';
$requestTopics = [
    'tai-khoan'    => 'Đăng nhập / Tài khoản',
    'bai-thi'      => 'Bài thi & điểm số',
    'bai-tap'      => 'Bài tập & nộp bài',
    'thoi-khoa-bieu'=> 'Thời khóa biểu',
    'khac'         => 'Vấn đề khác',
];

$errors = [];
$success = '';
$formTopic = 'tai-khoan';
$formMessage = '';

// Danh sách yêu cầu đã gửi của học sinh này
$myRequests = array_values(array_filter(
    get_json_data($requestsFile, []),
    fn($r) => is_array($r) && (string)($r['student_code'] ?? '') === (string)$studentCode
));
usort($myRequests, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

// ------------------------------------------------------------------ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formTopic = (string)($_POST['topic'] ?? 'tai-khoan');
    $formMessage = trim((string)($_POST['message'] ?? ''));

    if (!array_key_exists($formTopic, $requestTopics)) {
        $errors[] = 'Vui lòng chọn loại vấn đề cần hỗ trợ.';
    }
    if (mb_strlen($formMessage) < 10) {
        $errors[] = 'Mô tả vấn đề cần ít nhất 10 ký tự để hỗ trợ được chính xác.';
    }
    if (mb_strlen($formMessage) > 2000) {
        $errors[] = 'Mô tả vấn đề không được vượt quá 2000 ký tự.';
    }

    if (!$errors) {
        $record = [
            'id'           => uniqid('sup_'),
            'student_code' => (string)$studentCode,
            'student_name' => (string)$studentName,
            'class'        => (string)($studentClass ?: $studentClassCode),
            'topic'        => $formTopic,
            'topic_label'  => $requestTopics[$formTopic],
            'message'      => $formMessage,
            'status'       => 'open',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $ok = update_json_data($requestsFile, function ($data) use ($record) {
            if (!is_array($data)) { $data = []; }
            $data[] = $record;
            return $data;
        }, []);

        if (!$ok) {
            $errors[] = 'Không lưu được yêu cầu. Vui lòng thử lại hoặc liên hệ trực tiếp GVCN.';
        } else {
            array_unshift($myRequests, $record);
            $success = 'Đã gửi yêu cầu hỗ trợ. Giáo viên chủ nhiệm sẽ phản hồi qua thông báo.';
            $formMessage = '';
        }
    }
}

$faqs = [
    [
        'q' => 'Quên mật khẩu thì làm sao?',
        'a' => 'Học sinh liên hệ giáo viên chủ nhiệm để được đặt lại mật khẩu. Nếu bạn vẫn nhớ mật khẩu cũ, tự đổi tại <code>Trang chủ → Cá nhân → Đổi mật khẩu</code>.',
    ],
    [
        'q' => 'Không vào được phòng thi?',
        'a' => 'Kiểm tra trạng thái bài thi ở tab <strong>Bài thi</strong>. Bài chỉ vào được khi trạng thái là <strong>Đang mở</strong> và đúng giờ. Nếu đã quá hạn hoặc bài bị đóng, hãy gửi yêu cầu hỗ trợ bên dưới kèm tên bài thi.',
    ],
    [
        'q' => 'Nộp bài tập bị lỗi hoặc bị trễ?',
        'a' => 'Vào <strong>Bài tập của tôi</strong> để kiểm tra trạng thái nộp. Nếu tệp tải lên bị lỗi, thử lại với định dạng phổ biến (<code>.pdf</code>, <code>.docx</code>, <code>.jpg</code>) và dung lượng nhỏ hơn. Trường hợp đã nộp hạn, liên hệ GVCN kèm ảnh chụp màn hình.',
    ],
    [
        'q' => 'Điểm bài thi chưa hiện?',
        'a' => 'Điểm được cập nhật sau khi giáo viên chấm xong. Xem tại <strong>Kết quả</strong> hoặc <strong>Thống kê</strong>. Nếu đã có thông báo công bố điểm mà vẫn chưa thấy, hãy dùng mẫu yêu cầu hỗ trợ bên dưới.',
    ],
    [
        'q' => 'Sai thông tin hồ sơ (tên, lớp, ngày sinh)?',
        'a' => 'Phần tên đăng nhập bạn tự sửa tại <strong>Hồ sơ &amp; tên đăng nhập</strong>. Họ tên, mã học sinh và lớp do nhà trường quản lý — vui lòng gửi yêu cầu hỗ trợ kèm lý do để được sửa.',
    ],
    [
        'q' => 'Đăng nhập bị báo lỗi hoặc tự động thoát?',
        'a' => 'Phiên đăng nhập có thời gian chờ hạn chế. Nếu bị thoát, hãy đăng nhập lại bằng mã học sinh hoặc tên đăng nhập. Quên tên đăng nhập thì dùng mã học sinh.',
    ],
];

include __DIR__ . '/../includes/eduvn_student_header.php';
?>

<p class="greeting">Trợ giúp &amp; hỗ trợ</p>
<p class="greeting-sub">Câu hở giúp, hướng dẫn và gửi yêu cầu cho nhà trường · <?php echo htmlspecialchars($schoolName); ?></p>

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

<p class="section-title" style="margin-bottom:10px">Tìm nhanh</p>
<ul class="list-menu" style="margin-bottom:26px">
  <li><a href="user_guide.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5Z"/><path d="M9 8h7M9 12h5"/></svg>Hướng dẫn sử dụng đầy đủ<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="change_password.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Đổi mật khẩu<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="parent_info.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>Thông tin phụ huynh<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
  <li><a href="results.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>Xem kết quả &amp; điểm<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
</ul>

<p class="section-title" style="margin-bottom:10px">Câu hỏi thường gặp</p>
<div class="faq" style="margin-bottom:26px">
  <?php foreach ($faqs as $i => $faq): ?>
    <details<?php echo $i === 0 ? ' open' : ''; ?>>
      <summary>
        <span class="faq-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>
        </span>
        <?php echo htmlspecialchars($faq['q']); ?>
        <svg class="faq-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
      </summary>
      <div class="faq-a"><?php echo $faq['a']; ?></div>
    </details>
  <?php endforeach; ?>
</div>

<p class="section-title" style="margin-bottom:10px">Gửi yêu cầu hỗ trợ</p>
<section class="panel-card" style="padding:22px;margin-bottom:26px">
  <form method="POST" action="help.php">
    <div class="field">
      <label for="topic">Loại vấn đề <span class="req">*</span></label>
      <select class="input" id="topic" name="topic">
        <?php foreach ($requestTopics as $value => $label): ?>
          <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $formTopic === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="message">Mô tả vấn đề <span class="req">*</span></label>
      <textarea class="input" id="message" name="message" required minlength="10" maxlength="2000"
                placeholder="Mô tả cụ thể: bạn đang làm gì, thấy lỗi gì, vào thời điểm nào."><?php echo htmlspecialchars($formMessage); ?></textarea>
      <div class="field-hint">Tối thiểu 10 ký tự. Nêu rõ tên bài thi/bài tập để được xử lý nhanh hơn.</div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/></svg>
        Gửi yêu cầu
      </button>
      <a class="btn btn-ghost" href="dashboard.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Quay lại
      </a>
    </div>
  </form>
</section>

<?php if ($myRequests): ?>
  <p class="section-title" style="margin-bottom:10px">Yêu cầu đã gửi (<?php echo count($myRequests); ?>)</p>
  <div class="exam-list" style="margin-bottom:26px">
    <?php foreach (array_slice($myRequests, 0, 5) as $req): ?>
      <article class="exam-card">
        <div class="exam-top">
          <div>
            <div class="exam-subject"><?php echo htmlspecialchars((string)($req['topic_label'] ?? 'Hỗ trợ')); ?></div>
            <div class="exam-title" style="font-size:14px"><?php echo htmlspecialchars(mb_strimwidth((string)($req['message'] ?? ''), 0, 140, '…')); ?></div>
          </div>
          <span class="pill <?php echo ($req['status'] ?? 'open') === 'open' ? 'pill-upcoming' : 'pill-done'; ?>">
            <?php echo ($req['status'] ?? 'open') === 'open' ? 'Đang chờ' : 'Đã xử lý'; ?>
          </span>
        </div>
        <div class="exam-info">
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?php echo htmlspecialchars((string)($req['created_at'] ?? '')); ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<p class="section-title" style="margin-bottom:10px">Kênh liên hệ</p>
<div class="contact-grid" style="margin-bottom:26px">
  <div class="contact-card">
    <div class="contact-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.3-.7L3 21l1.8-5.4A8.4 8.4 0 1 1 21 11.5Z"/></svg></div>
    <div>
      <div class="contact-k">Giáo viên chủ nhiệm</div>
      <div class="contact-v">Xem tab Thông báo GVCN</div>
      <div class="contact-sub">Nhanh nhất cho việc thi, điểm số và nghỉ học.</div>
    </div>
  </div>
  <div class="contact-card">
    <div class="contact-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-6h6v6"/></svg></div>
    <div>
      <div class="contact-k">Văn phòng nhà trường</div>
      <div class="contact-v"><?php echo htmlspecialchars($schoolName); ?></div>
      <div class="contact-sub"><?php echo htmlspecialchars($officeHours); ?></div>
    </div>
  </div>
  <?php if ($supportPhone !== ''): ?>
    <div class="contact-card">
      <div class="contact-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7A2 2 0 0 1 22 16.9Z"/></svg></div>
      <div>
        <div class="contact-k">Hotline hỗ trợ</div>
        <div class="contact-v"><a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $supportPhone)); ?>"><?php echo htmlspecialchars($supportPhone); ?></a></div>
        <div class="contact-sub">Gọi trong giờ hành chính.</div>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($supportEmail !== ''): ?>
    <div class="contact-card">
      <div class="contact-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></div>
      <div>
        <div class="contact-k">Email hỗ trợ</div>
        <div class="contact-v"><a href="mailto:<?php echo htmlspecialchars($supportEmail); ?>"><?php echo htmlspecialchars($supportEmail); ?></a></div>
        <div class="contact-sub">Phản hồi trong 1 ngày làm việc.</div>
      </div>
    </div>
  <?php endif; ?>
</div>

<p class="section-title" style="margin-bottom:10px">Quy trình hỗ trợ</p>
<section class="panel-card" style="padding:22px">
  <div class="steps">
    <div class="step">
      <div class="step-t">Tự kiểm tra</div>
      <div class="step-d">Đọc mục Câu hỏi thường gặp ở trên và <a href="user_guide.php" style="color:var(--primary);font-weight:700">Hướng dẫn sử dụng</a>. Phần lớn vấn đề được giải quyết tại đây.</div>
    </div>
    <div class="step">
      <div class="step-t">Gửi yêu cầu trực tuyến</div>
      <div class="step-d">Điền mẫu ở trên, nêu rõ tên bài thi/bài tập và thời điểm gặp lỗi để hỗ trợ chính xác.</div>
    </div>
    <div class="step">
      <div class="step-t">Chờ phản hồi</div>
      <div class="step-d">Theo dõi trạng thái trong mục <strong>Yêu cầu đã gửi</strong> và thông báo trên trang chủ.</div>
    </div>
    <div class="step">
      <div class="step-t">Liên hệ trực tiếp nếu gấp</div>
      <div class="step-d">Trong giờ hành chính, liên hệ GVCN hoặc văn phòng nhà trường với mã học sinh của bạn.</div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/eduvn_student_footer.php'; ?>
