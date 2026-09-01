<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';
$studentSchool = 'Trường THCS Nguyễn Du';
$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';

// Avatar initials: gồm các từ trừ họ (vd: "Nguyễn Minh Anh" -> "MA")
$stdNameParts = preg_split('/\s+/u', trim($studentName));
if (count($stdNameParts) > 1) {
    $stdGiven = array_slice($stdNameParts, 1);
    $stdInitials = strtoupper(mb_substr($stdGiven[0], 0, 1) . mb_substr(end($stdGiven), 0, 1));
} else {
    $stdInitials = strtoupper(mb_substr($studentName, 0, 2)) ?: 'HS';
}

// Ngày hôm nay (tiếng Việt)
$stdDow = ['Chủ nhật', 'thứ hai', 'thứ ba', 'thứ tư', 'thứ năm', 'thứ sáu', 'thứ bảy'][(int)date('w')];
$stdToday = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thi Trực Tuyến — Cổng học sinh</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../styles/eduvn-student.css">
</head>
<body data-theme="<?php echo $stdDesignTheme; ?>">
<script>
(function () {
  var def = document.body.getAttribute('data-theme') || 'cute';
  var saved = null;
  try { saved = localStorage.getItem('eduvn_student_theme_v2'); } catch (e) {}
  var theme = (saved === 'cute' || saved === 'elegant') ? saved : def;
  function applyTheme(t) {
    document.body.setAttribute('data-theme', t);
    document.querySelectorAll('[data-theme-btn]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', String(btn.getAttribute('data-theme-btn') === t));
    });
  }
  applyTheme(theme);
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { applyTheme(theme); });
  }
})();
</script>
<div class="app">

  <!-- ===== TOP BAR ===== -->
  <header class="topbar">
    <div class="brand">
      <div class="brand-mark">TT</div>
      <div class="brand-text">
        <div class="brand-title">Thi Trực Tuyến</div>
        <div class="brand-sub">EduVN Manager</div>
      </div>
    </div>

    <div class="topbar-right">
      <div class="theme-toggle" role="group" aria-label="Chọn giao diện">
        <div class="toggle-pill" aria-hidden="true"></div>
        <button type="button" data-theme-btn="cute" aria-pressed="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/><circle cx="12" cy="12" r="3.2"/></svg>
          <span class="toggle-label">Dễ thương</span>
        </button>
        <button type="button" data-theme-btn="elegant" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l3 5-9 13L3 8l3-5Z"/><path d="M3 8h18M9 3l3 5 3-5M12 8l-2 5 2 9 2-9-2-5"/></svg>
          <span class="toggle-label">Lịch lãm</span>
        </button>
      </div>

      <div class="bell-wrap">
        <button class="bell" type="button" id="bell-btn" aria-haspopup="true" aria-expanded="false" aria-label="Thông báo, 2 thông báo mới">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
          <span class="dot"></span>
        </button>
        <div class="dropdown-panel" id="bell-dropdown" role="menu" aria-label="Danh sách thông báo">
          <div class="dropdown-head">
            <div class="panel-title" style="margin-bottom:0">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
              Thông báo
            </div>
            <span class="dropdown-mark">2 mới</span>
          </div>
          <div class="dropdown-list">
            <div class="notif">
              <span class="notif-dot"></span>
              <div><div class="notif-text">Giáo viên đã công bố điểm bài <strong>Tiếng Anh</strong></div><div class="notif-time">2 giờ trước</div></div>
            </div>
            <div class="notif">
              <span class="notif-dot"></span>
              <div><div class="notif-text">Bài tập <strong>Ngữ Văn</strong> sắp đến hạn nộp, còn 5 giờ</div><div class="notif-time">3 giờ trước</div></div>
            </div>
            <div class="notif">
              <span class="notif-dot" style="background:var(--ink-soft)"></span>
              <div><div class="notif-text">Bài thi <strong>Hóa Học</strong> đã đóng, không ghi nhận bài làm</div><div class="notif-time">Hôm qua</div></div>
            </div>
            <div class="notif">
              <span class="notif-dot" style="background:var(--ink-soft)"></span>
              <div><div class="notif-text">Cô Hoa (GVCN) vừa đăng thông báo mới</div><div class="notif-time">2 ngày trước</div></div>
            </div>
          </div>
          <div class="dropdown-foot">
            <a href="#">Xem tất cả thông báo</a>
          </div>
        </div>
      </div>

      <button class="avatar-mobile" type="button" data-tab-target="profile" aria-label="Trang cá nhân">
        <div class="avatar"><?php echo htmlspecialchars($stdInitials); ?></div>
      </button>
      <button class="avatar-block" type="button" data-tab-target="profile" aria-label="Trang cá nhân">
        <div class="avatar"><?php echo htmlspecialchars($stdInitials); ?></div>
        <div style="text-align:left">
          <div class="avatar-name"><?php echo htmlspecialchars($studentName); ?></div>
          <div class="avatar-role">Lớp <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?></div>
        </div>
      </button>
    </div>
  </header>

  <div class="body-grid">

    <!-- ===== SIDE NAV (desktop) ===== -->
    <nav class="sidenav" aria-label="Điều hướng chính">
      <button class="sidenav-item" data-tab-target="home" aria-current="page">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/></svg>
        Trang chủ
      </button>
      <button class="sidenav-item" data-tab-target="timetable">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
        Thời khóa biểu
      </button>
      <button class="sidenav-item" data-tab-target="assignments">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
        Bài tập
      </button>
      <button class="sidenav-item" data-tab-target="exams">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        Bài thi
      </button>
      <button class="sidenav-item" data-tab-target="practice">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v14a3 3 0 1 1-3-3h3M9 7h11M9 11h11"/></svg>
        Luyện tập
      </button>
      <button class="sidenav-item" data-tab-target="gvcn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.3-.7L3 21l1.8-5.4A8.4 8.4 0 1 1 21 11.5Z"/></svg>
        Thông báo GVCN
      </button>
      <button class="sidenav-item" data-tab-target="profile">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>
        Cá nhân
      </button>
      <div class="sidenav-foot">
        <a class="sidenav-item" href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>
          Trợ giúp
        </a>
      </div>
    </nav>

    <!-- ===== MAIN ===== -->
    <main class="main">

      <!-- --- TAB: HOME --- -->
      <section class="tab-panel enter" id="tab-home">
        <p class="greeting" data-greeting>Chào bạn quay lại, <?php echo htmlspecialchars($studentName); ?></p>
        <p class="greeting-sub">Hôm nay là <?php echo $stdDow; ?>, <?php echo $stdToday; ?> · Chúc em thi tốt!</p>

        <article class="hero-ticket">
          <span class="hero-eyebrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>
            Bài thi gần nhất
          </span>
          <div class="hero-name" data-hero-name>Đang tải bài thi…</div>
          <div class="hero-meta">
            <span data-hero-q><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8"/></svg></span>
            <span data-hero-t><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg></span>
            <span data-hero-room><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>
          </div>
          <div class="hero-bottom">
            <div data-hero-timer>
              <div class="countdown" data-countdown>
                <span class="num" data-cd-h>00</span><span class="colon">:</span>
                <span class="num" data-cd-m>00</span><span class="colon">:</span>
                <span class="num" data-cd-s>00</span>
              </div>
              <div class="countdown-label" data-countdown-label>Thời lượng làm bài</div>
            </div>
            <a class="btn btn-primary" data-hero-cta href="exam.php">
              Vào phòng thi
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        </article>

        <div class="shortcuts">
          <button class="shortcut-card" data-tab-target="timetable">
            <div class="shortcut-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></div>
            <div><div class="shortcut-title">Thời khóa biểu</div><div class="shortcut-sub">Hôm nay: 4 tiết học</div></div>
          </button>
          <button class="shortcut-card" data-tab-target="gvcn">
            <div class="shortcut-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.3-.7L3 21l1.8-5.4A8.4 8.4 0 1 1 21 11.5Z"/></svg></div>
            <div><div class="shortcut-title">Thông báo GVCN</div><div class="shortcut-sub">2 tin mới từ Cô Hoa</div></div>
          </button>
          <button class="shortcut-card" data-tab-target="assignments">
            <div class="shortcut-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg></div>
            <div><div class="shortcut-title">Bài tập</div><div class="shortcut-sub">3 bài cần nộp</div></div>
          </button>
          <button class="shortcut-card" data-tab-target="practice">
            <div class="shortcut-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v14a3 3 0 1 1-3-3h3M9 7h11M9 11h11"/></svg></div>
            <div><div class="shortcut-title">Luyện tập</div><div class="shortcut-sub">Ôn theo chủ đề</div></div>
          </button>
        </div>

        <div class="section-head">
          <span class="section-title">Bài thi sắp tới</span>
          <button class="section-link" data-tab-target="exams">
            Xem tất cả
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
          </button>
        </div>
        <div class="exam-list enter-list" id="home-exam-preview"></div>

        <!-- mobile-only quick stats (desktop has right panel) -->
        <div class="section-head" style="margin-top:6px">
          <span class="section-title">Tổng quan</span>
        </div>
        <div class="stat-row" style="margin-bottom:26px" id="mobile-stats"></div>
      </section>

      <!-- --- TAB: TIMETABLE --- -->
      <section class="tab-panel" id="tab-timetable" hidden>
        <p class="greeting">Thời khóa biểu</p>
        <p class="greeting-sub">Lớp <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?> · Học kỳ I, năm học 2026–2027</p>

        <div class="day-chips" id="day-chips"></div>
        <div class="agenda-wrap" id="agenda-list"></div>

        <div class="week-grid" id="week-grid"></div>
      </section>

      <!-- --- TAB: ASSIGNMENTS --- -->
      <section class="tab-panel" id="tab-assignments" hidden>
        <p class="greeting">Bài tập của em</p>
        <p class="greeting-sub">Bài tập cá nhân và nhóm do giáo viên giao</p>
        <div class="chips" id="assign-filter-chips">
          <button class="chip" data-afilter="all" aria-pressed="true">Tất cả</button>
          <button class="chip" data-afilter="pending" aria-pressed="false">Chưa nộp</button>
          <button class="chip" data-afilter="individual" aria-pressed="false">Cá nhân</button>
          <button class="chip" data-afilter="group" aria-pressed="false">Nhóm</button>
          <button class="chip" data-afilter="graded" aria-pressed="false">Đã chấm</button>
        </div>
        <div class="exam-list grid enter-list" id="assignment-list"></div>
      </section>

      <!-- --- TAB: EXAMS --- -->
      <section class="tab-panel" id="tab-exams" hidden>
        <p class="greeting">Bài thi của em</p>
        <p class="greeting-sub">6 bài thi trong học kỳ này</p>
        <div class="chips" id="filter-chips">
          <button class="chip" data-filter="all" aria-pressed="true">Tất cả</button>
          <button class="chip" data-filter="open" aria-pressed="false">Đang mở</button>
          <button class="chip" data-filter="upcoming" aria-pressed="false">Sắp diễn ra</button>
          <button class="chip" data-filter="done" aria-pressed="false">Đã hoàn thành</button>
          <button class="chip" data-filter="closed" aria-pressed="false">Đã đóng</button>
        </div>
        <div class="exam-list grid enter-list" id="exam-full-list"></div>
      </section>

      <!-- --- TAB: PRACTICE --- -->
      <section class="tab-panel" id="tab-practice" hidden>
        <p class="greeting">Luyện tập</p>
        <p class="greeting-sub">Ôn câu hỏi trắc nghiệm trong ngân hàng đề theo chủ đề</p>
        <div class="subject-grid" id="practice-subjects"></div>
        <div id="practice-topics"></div>
      </section>

      <!-- --- TAB: GVCN --- -->
      <section class="tab-panel" id="tab-gvcn" hidden>
        <p class="greeting">Thông báo từ GVCN</p>
        <p class="greeting-sub"><span id="gvcn-teacher-name">Giáo viên chủ nhiệm</span> lớp <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?></p>
        <div id="gvcn-list"></div>
      </section>

      <!-- --- TAB: PROFILE --- -->
      <section class="tab-panel" id="tab-profile" hidden>
        <p class="greeting" style="margin-bottom:16px">Cá nhân</p>
        <div class="profile-card">
          <div class="profile-avatar"><?php echo htmlspecialchars($stdInitials); ?></div>
          <div>
            <div class="profile-name"><?php echo htmlspecialchars($studentName); ?></div>
            <div class="profile-meta">Lớp <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?> · <?php echo htmlspecialchars($studentSchool); ?></div>
            <div class="profile-code">Mã học sinh: <?php echo htmlspecialchars($studentCode); ?></div>
          </div>
        </div>

        <p class="section-title" style="margin-bottom:10px">Giao diện</p>
        <div class="panel-card" style="margin-bottom:20px">
          <p style="font-size:12.5px;color:var(--ink-soft);margin-bottom:12px">Chọn phong cách hiển thị em thích — có thể đổi lại bất cứ lúc nào.</p>
          <div class="theme-toggle" role="group" aria-label="Chọn giao diện" style="width:100%">
            <div class="toggle-pill" aria-hidden="true"></div>
            <button type="button" data-theme-btn="cute" aria-pressed="true" style="flex:1;justify-content:center">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/><circle cx="12" cy="12" r="3.2"/></svg>
              <span class="toggle-label">Dễ thương</span>
            </button>
            <button type="button" data-theme-btn="elegant" aria-pressed="false" style="flex:1;justify-content:center">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l3 5-9 13L3 8l3-5Z"/><path d="M3 8h18M9 3l3 5 3-5M12 8l-2 5 2 9 2-9-2-5"/></svg>
              <span class="toggle-label">Lịch lãm</span>
            </button>
          </div>
        </div>

        <p class="section-title" style="margin-bottom:10px">Tài khoản</p>
        <ul class="list-menu">
          <li><a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Đổi mật khẩu<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
          <li><a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>Thông tin phụ huynh<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
          <li><a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>Trợ giúp &amp; hỗ trợ<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></li>
          <li><a href="logout.php" style="color:#D14343"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>Đăng xuất</a></li>
        </ul>
      </section>

    </main>

    <!-- ===== RIGHT PANEL (desktop) ===== -->
    <aside class="rightpanel">
      <div class="stat-row" id="desktop-stats"></div>

      <div class="panel-card">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
          Thông báo
        </div>
        <div class="notif">
          <span class="notif-dot"></span>
          <div><div class="notif-text">Giáo viên đã công bố điểm bài <strong>Tiếng Anh</strong></div><div class="notif-time">2 giờ trước</div></div>
        </div>
        <div class="notif">
          <span class="notif-dot"></span>
          <div><div class="notif-text">Bài thi <strong>Hóa Học</strong> đã đóng, không ghi nhận bài làm</div><div class="notif-time">Hôm qua</div></div>
        </div>
        <div class="notif">
          <span class="notif-dot" style="background:var(--ink-soft)"></span>
          <div><div class="notif-text">Lịch thi học kỳ I môn <strong>Ngữ Văn</strong> đã được cập nhật</div><div class="notif-time">2 ngày trước</div></div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
          Lịch thi tuần này
        </div>
        <div class="week-row">
          <div class="week-day"><span>T2</span><span class="week-dot"></span></div>
          <div class="week-day"><span>T3</span><span class="week-dot"></span></div>
          <div class="week-day"><span>T4</span><span class="week-dot"></span></div>
          <div class="week-day"><span>T5</span><span class="week-dot active"></span></div>
          <div class="week-day"><span>T6</span><span class="week-dot"></span></div>
          <div class="week-day"><span>T7</span><span class="week-dot"></span></div>
          <div class="week-day"><span>CN</span><span class="week-dot"></span></div>
        </div>
      </div>
    </aside>

  </div>

  <!-- ===== BOTTOM NAV (mobile) ===== -->
  <nav class="bottomnav" aria-label="Điều hướng chính">
    <button class="bottomnav-item" data-tab-target="home" aria-current="page">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/></svg>
      <span>Trang chủ</span>
    </button>
    <button class="bottomnav-item" data-tab-target="timetable">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
      <span>TKB</span>
    </button>
    <button class="bottomnav-item" data-tab-target="assignments">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
      <span>Bài tập</span>
    </button>
    <button class="bottomnav-item" data-tab-target="exams">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
      <span>Bài thi</span>
    </button>
    <button class="bottomnav-item" data-tab-target="profile">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>
      <span>Cá nhân</span>
    </button>
  </nav>

  <!-- ===== MODAL: ASSIGNMENT DETAIL / SUBMIT ===== -->
  <div class="modal-overlay" id="assign-modal" role="dialog" aria-modal="true" aria-labelledby="assign-modal-title">
    <div class="modal-sheet" id="assign-modal-body"></div>
  </div>

  <!-- ===== MODAL: PRACTICE QUIZ ===== -->
  <div class="modal-overlay" id="quiz-modal" role="dialog" aria-modal="true" aria-labelledby="quiz-modal-title">
    <div class="modal-sheet" id="quiz-modal-body"></div>
  </div>

</div>

<script>
(function(){
  "use strict";

  var STUDENT_NAME = <?php echo json_encode($studentName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
  var STUDENT_CODE = <?php echo json_encode($studentCode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var STUDENT_CLASS_CODE = <?php echo json_encode($studentClassCode ?: $studentClass, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

  var ICONS = {
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>',
    doc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8"/></svg>',
    calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>',
    person: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c1.4-3.2 4-5 6.5-5s5.1 1.8 6.5 5"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>',
    room: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.4"/></svg>',
    pin: '<svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2a5 5 0 0 0-5 5c0 3.2 3.2 5.6 4.2 9.6.1.5.5.9 1 .9s.9-.4 1-.9C14.2 12.6 17 10.2 17 7a5 5 0 0 0-5-5Zm0 6.8A1.8 1.8 0 1 1 12 5.2a1.8 1.8 0 0 1 0 3.6Z"/></svg>',
    book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"/></svg>'
  };

  var STATUS_LABEL = { open:"Đang mở", upcoming:"Sắp diễn ra", done:"Đã hoàn thành", closed:"Đã đóng" };

  var EXAMS = [];
  var STATS = { done:0, avg:0, open:0 };

  function examCard(e){
    var scoreBlock = (e.score != null && e.status === 'done')
      ? '<div class="exam-score">'+e.score+'<small>/ '+(e.total_points||10)+' điểm</small></div>'
      : '<span></span>';
    var cta;
    if(e.status === 'open'){
      cta = '<a class="btn btn-primary btn-sm" href="exam.php?exam_id='+encodeURIComponent(e.test_id)+'">Vào thi</a>';
    } else if(e.status === 'done' && e.result_id){
      cta = '<a class="btn btn-primary btn-sm" href="result.php?exam_id='+encodeURIComponent(e.result_id)+'">Xem bài làm</a>';
    } else {
      cta = '<button class="btn btn-ghost btn-sm" disabled>Không khả dụng</button>';
    }
    var infoText = e.time_limit + ' phút · ' + e.total_questions + ' câu trắc nghiệm';
    return (
      '<article class="exam-card">' +
        '<div class="exam-top">' +
          '<div><div class="exam-subject">'+e.subject_name+'</div><div class="exam-title">'+e.test_name+'</div></div>' +
          '<span class="pill pill-'+e.status+'">'+STATUS_LABEL[e.status]+'</span>' +
        '</div>' +
        '<div class="exam-info"><span>'+ICONS.clock+infoText+'</span></div>' +
        '<div class="ticket-divider"></div>' +
        '<div class="exam-bottom">'+scoreBlock+cta+'</div>' +
      '</article>'
    );
  }

  function renderHomePreview(){
    var wrap = document.getElementById('home-exam-preview');
    wrap.innerHTML = '';
    EXAMS.filter(function(e){ return e.status==='open' || e.status==='upcoming'; })
      .slice(0,3)
      .forEach(function(e){ wrap.insertAdjacentHTML('beforeend', examCard(e)); });
  }

  var fullList = document.getElementById('exam-full-list');
  function renderFullList(filter){
    fullList.innerHTML = '';
    EXAMS.filter(function(e){ return filter==='all' || e.status===filter; })
      .forEach(function(e){ fullList.insertAdjacentHTML('beforeend', examCard(e)); });
  }

  var chips = document.querySelectorAll('#filter-chips .chip');
  chips.forEach(function(chip){
    chip.addEventListener('click', function(){
      chips.forEach(function(c){ c.setAttribute('aria-pressed','false'); });
      chip.setAttribute('aria-pressed','true');
      renderFullList(chip.dataset.filter);
    });
  });

  function renderStats(){
    var html =
      '<div class="stat-card"><div class="stat-num">'+STATS.done+'</div><div class="stat-label">Đã hoàn thành</div></div>' +
      '<div class="stat-card"><div class="stat-num">'+STATS.avg+'</div><div class="stat-label">Điểm trung bình</div></div>' +
      '<div class="stat-card"><div class="stat-num">'+STATS.open+'</div><div class="stat-label">Bài thi đang mở</div></div>';
    document.getElementById('mobile-stats').innerHTML = html;
    document.getElementById('desktop-stats').innerHTML = html;
  }

  function renderHero(){
    var nameEl = document.querySelector('[data-hero-name]');
    var qEl = document.querySelector('[data-hero-q]');
    var tEl = document.querySelector('[data-hero-t]');
    var rEl = document.querySelector('[data-hero-room]');
    var ctaEl = document.querySelector('[data-hero-cta]');
    var timerWrap = document.querySelector('[data-hero-timer]');
    var openExam = EXAMS.find(function(e){ return e.status === 'open'; });
    var doneExam = EXAMS.find(function(e){ return e.status === 'done'; });
    var hero = openExam || doneExam;

    if(!hero){
      if(nameEl) nameEl.textContent = 'Chưa có bài thi nào';
      if(qEl) qEl.textContent = 'Giáo viên sẽ công bố bài thi khi có lịch';
      if(tEl) tEl.textContent = '';
      if(rEl) rEl.textContent = '';
      if(ctaEl){ ctaEl.classList.add('disabled'); ctaEl.removeAttribute('href'); ctaEl.textContent = 'Chưa mở'; }
      if(timerWrap) timerWrap.style.display = 'none';
      return;
    }

    if(nameEl) nameEl.textContent = hero.test_name + ' — Môn ' + hero.subject_name;
    if(qEl) qEl.textContent = hero.total_questions + ' câu trắc nghiệm';
    if(tEl) tEl.textContent = 'Thời lượng ' + hero.time_limit + ' phút';
    if(rEl) rEl.textContent = 'Phòng thi trực tuyến';
    if(timerWrap) timerWrap.style.display = '';

    if(openExam){
      if(ctaEl){
        ctaEl.classList.remove('disabled');
        ctaEl.setAttribute('href', 'exam.php?exam_id=' + encodeURIComponent(openExam.test_id));
        ctaEl.textContent = 'Vào phòng thi';
      }
      remaining = (openExam.time_limit || 45) * 60;
      var cdLabel = document.querySelector('[data-countdown-label]');
      if(cdLabel) cdLabel.textContent = 'Thời lượng làm bài';
    } else if(doneExam){
      if(ctaEl){
        ctaEl.classList.remove('disabled');
        ctaEl.setAttribute('href', 'result.php?exam_id=' + encodeURIComponent(doneExam.result_id || ''));
        ctaEl.textContent = 'Xem bài làm';
      }
      remaining = 0;
      var cdLabel2 = document.querySelector('[data-countdown-label]');
      if(cdLabel2) cdLabel2.textContent = 'Đã hoàn thành';
    }
    renderCountdown();
  }

  // greeting by time of day
  var h = new Date().getHours();
  var g = h < 11 ? "Chào buổi sáng" : h < 13 ? "Chào buổi trưa" : h < 18 ? "Chào buổi chiều" : "Chào buổi tối";
  document.querySelector('[data-greeting]').textContent = g + ", " + STUDENT_NAME;

  // countdown (static display of exam time limit; not tied to a real deadline)
  var cdEl = document.querySelector('[data-countdown]');
  var remaining = 0;
  var hEl = document.querySelector('[data-cd-h]'), mEl = document.querySelector('[data-cd-m]'), sEl = document.querySelector('[data-cd-s]');
  function pad(n){ return String(n).padStart(2,'0'); }
  function renderCountdown(){
    var hh = Math.floor(remaining/3600), mm = Math.floor((remaining%3600)/60), ss = remaining%60;
    if(hEl) hEl.textContent = pad(hh);
    if(mEl) mEl.textContent = pad(mm);
    if(sEl) sEl.textContent = pad(ss);
  }
  renderCountdown();
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ============ LOAD REAL EXAM DATA ============
  fetch('api/list_exams.php')
    .then(function(r){ return r.json(); })
    .then(function(data){
      if(!data || !data.success) return;
      EXAMS = data.exams || [];
      STATS = data.stats || { done:0, avg:0, open:0 };
      renderHomePreview();
      renderFullList('all');
      renderStats();
      renderHero();
      var subEl = document.querySelector('#tab-exams .greeting-sub');
      if(subEl) subEl.textContent = EXAMS.length + ' bài thi trong học kỳ này';
      var todayKey = ['CN','T2','T3','T4','T5','T6','T7'][new Date().getDay()];
      document.querySelectorAll('.week-day').forEach(function(d){
        var dot = d.querySelector('.week-dot');
        var keyEl = d.querySelector('span');
        if(dot && keyEl && keyEl.textContent.trim() === todayKey) dot.classList.add('active');
      });
    })
    .catch(function(){ renderStats(); });

  // theme toggle (all instances stay in sync, persisted)
  function setTheme(theme){
    document.body.dataset.theme = theme;
    document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
      btn.setAttribute('aria-pressed', String(btn.dataset.themeBtn === theme));
    });
    try { localStorage.setItem('eduvn_student_theme_v2', theme); } catch (e) {}
  }
  document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
    btn.addEventListener('click', function(){ setTheme(btn.dataset.themeBtn); });
  });

  // tab switching
  var tabs = ['home','timetable','assignments','exams','practice','gvcn','profile'];
  function setTab(name){
    tabs.forEach(function(t){
      document.getElementById('tab-'+t).hidden = (t !== name);
    });
    document.querySelectorAll('[data-tab-target]').forEach(function(el){
      if(el.hasAttribute('aria-current') || el.classList.contains('sidenav-item') || el.classList.contains('bottomnav-item')){
        if(el.dataset.tabTarget === name){ el.setAttribute('aria-current','page'); }
        else { el.removeAttribute('aria-current'); }
      }
    });
    window.scrollTo({top:0, behavior: reduceMotion ? 'auto' : 'smooth'});
  }
  document.querySelectorAll('[data-tab-target]').forEach(function(el){
    el.addEventListener('click', function(){ setTab(el.dataset.tabTarget); });
  });

  // ============ NOTIFICATION DROPDOWN ============
  var bellBtn = document.getElementById('bell-btn');
  var bellDropdown = document.getElementById('bell-dropdown');
  bellBtn.addEventListener('click', function(ev){
    ev.stopPropagation();
    var isOpen = bellDropdown.classList.toggle('open');
    bellBtn.setAttribute('aria-expanded', String(isOpen));
  });
  document.addEventListener('click', function(ev){
    if(!bellDropdown.contains(ev.target) && ev.target !== bellBtn){
      bellDropdown.classList.remove('open');
      bellBtn.setAttribute('aria-expanded','false');
    }
  });

  // ============ MODAL HELPERS ============
  var lastFocused = null;
  function openModal(id){
    lastFocused = document.activeElement;
    var overlay = document.getElementById(id);
    overlay.classList.add('open');
    var closeBtn = overlay.querySelector('.modal-close');
    if(closeBtn) closeBtn.focus();
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id){
    document.getElementById(id).classList.remove('open');
    if(!document.querySelector('.modal-overlay.open')) document.body.style.overflow = '';
    if(lastFocused && lastFocused.focus) lastFocused.focus();
  }
  function wireModalCloseButtons(scopeEl){
    scopeEl.querySelectorAll('[data-close-modal]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var overlay = btn.closest('.modal-overlay');
        if(overlay) closeModal(overlay.id);
      });
    });
  }
  document.querySelectorAll('.modal-overlay').forEach(function(overlay){
    overlay.addEventListener('click', function(ev){
      if(ev.target === overlay) closeModal(overlay.id);
    });
  });
  document.addEventListener('keydown', function(ev){
    if(ev.key === 'Escape'){
      document.querySelectorAll('.modal-overlay.open').forEach(function(o){ closeModal(o.id); });
      bellDropdown.classList.remove('open');
      bellBtn.setAttribute('aria-expanded','false');
    }
  });

  // ============ ASSIGNMENTS ============
  var ASSIGN_STATUS_LABEL = { pending:"Chưa nộp", submitted:"Đã nộp", graded:"Đã chấm", late:"Trễ hạn" };
  var ASSIGN_STATUS_PILL  = { pending:"open", submitted:"upcoming", graded:"done", late:"closed" };
  var ASSIGNMENTS = [];
  var SUBJECT_NAMES = {};

  function fmtDateTime(v){
    if(!v) return '—';
    var d = new Date(v);
    if(isNaN(d.getTime())) return String(v);
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()+' '+pad(d.getHours())+':'+pad(d.getMinutes());
  }

  function mapAssignment(a){
    var sub = a.my_submission;
    var now = new Date();
    var dueDate = new Date(a.due_date);
    var status;
    if(sub){
      status = (sub.score != null && sub.score !== '') ? 'graded' : 'submitted';
    } else {
      status = (!isNaN(dueDate.getTime()) && dueDate < now) ? 'late' : 'pending';
    }
    return {
      id: a.id,
      subject: SUBJECT_NAMES[a.subject_id] || (a.subject_id ? 'Môn '+a.subject_id : 'Môn học'),
      title: a.title,
      type: a.max_group_members ? 'group' : 'individual',
      teacher: a.teacher_username || 'Giáo viên',
      due: fmtDateTime(a.due_date),
      status: status,
      desc: a.description || '',
      attachments: a.attachments || [],
      submittedAt: sub ? fmtDateTime(sub.submitted_at) : null,
      grade: (sub && sub.score != null && sub.score !== '') ? sub.score + '/' + a.max_score : null,
      feedback: sub ? (sub.feedback || '') : '',
      submittedContent: sub ? (sub.content || '') : ''
    };
  }

  function assignmentCard(a){
    var typeIcon = a.type==='group' ? ICONS.users : ICONS.person;
    var typeLabel = a.type==='group' ? 'Nhóm' : 'Cá nhân';
    var dueLine = (a.status==='pending')
      ? '<span class="assign-due'+(a.urgent?' urgent':'')+'">'+ICONS.clock+'Hạn nộp: '+a.due+'</span>'
      : (a.status==='late')
        ? '<span class="assign-due urgent">'+ICONS.clock+'Đã quá hạn: '+a.due+'</span>'
        : '<span>'+ICONS.clock+'Đã nộp: '+(a.submittedAt||a.due)+'</span>';
    var scoreBlock = a.grade
      ? '<div class="exam-score">'+a.grade.split('/')[0]+'<small>/ '+a.grade.split('/')[1]+' điểm</small></div>'
      : '<span></span>';
    var cta = (a.status==='pending')
      ? '<a class="btn btn-primary btn-sm" href="submit_assignment.php?id='+encodeURIComponent(a.id)+'">Nộp bài</a>'
      : '<button type="button" class="btn btn-primary btn-sm" data-open-assign="'+a.id+'">Xem chi tiết</button>';
    return (
      '<article class="exam-card">' +
        '<div class="exam-top">' +
          '<div><div class="exam-subject">'+a.subject+'</div><div class="exam-title">'+a.title+'</div>' +
            '<span class="type-badge" style="margin-top:6px">'+typeIcon+typeLabel+'</span></div>' +
          '<span class="pill pill-'+ASSIGN_STATUS_PILL[a.status]+'">'+ASSIGN_STATUS_LABEL[a.status]+'</span>' +
        '</div>' +
        '<div class="exam-info">'+dueLine+'</div>' +
        '<div class="ticket-divider"></div>' +
        '<div class="exam-bottom">' +
          scoreBlock + cta +
        '</div>' +
      '</article>'
    );
  }

  function renderAssignments(filter){
    var list = document.getElementById('assignment-list');
    list.innerHTML = '';
    ASSIGNMENTS.filter(function(a){
      if(filter==='all') return true;
      if(filter==='pending') return a.status==='pending';
      if(filter==='graded') return a.status==='graded';
      return a.type===filter;
    }).forEach(function(a){ list.insertAdjacentHTML('beforeend', assignmentCard(a)); });
    list.querySelectorAll('[data-open-assign]').forEach(function(btn){
      btn.addEventListener('click', function(){ openAssignModal(String(btn.dataset.openAssign)); });
    });
  }
  document.querySelectorAll('#assign-filter-chips .chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      document.querySelectorAll('#assign-filter-chips .chip').forEach(function(c){ c.setAttribute('aria-pressed','false'); });
      chip.setAttribute('aria-pressed','true');
      renderAssignments(chip.dataset.afilter);
    });
  });

  function fileIconMeta(name){
    var ext = (name || '').split('.').pop().toLowerCase();
    var map = {
      doc:{label:'DOC',color:'#2A56C6'}, docx:{label:'DOC',color:'#2A56C6'},
      xls:{label:'XLS',color:'#0E8F5B'}, xlsx:{label:'XLS',color:'#0E8F5B'},
      pdf:{label:'PDF',color:'#D14343'},
      jpg:{label:'IMG',color:'#8B7CF6'}, jpeg:{label:'IMG',color:'#8B7CF6'}, png:{label:'IMG',color:'#8B7CF6'}
    };
    return map[ext] || {label:'FILE',color:'#8A6F72'};
  }

  function openAssignModal(id){
    var a = ASSIGNMENTS.find(function(x){ return x.id===id; });
    if(!a) return;
    var body = document.getElementById('assign-modal-body');
    var typeLine = a.type==='group'
      ? '<span class="type-badge" style="margin-bottom:14px">'+ICONS.users+'Nhóm</span>'
      : '<span class="type-badge" style="margin-bottom:14px">'+ICONS.person+'Cá nhân</span>';
    var metaHTML =
      '<div class="exam-info" style="margin-bottom:6px">' +
        '<span>'+ICONS.doc+a.subject+' · GV '+a.teacher+'</span>' +
      '</div>' + typeLine;

    var attachHTML = '';
    if(a.attachments && a.attachments.length){
      attachHTML =
        '<div class="modal-section">' +
          '<span class="modal-label">Tài liệu đính kèm</span>' +
          a.attachments.map(function(f){
            var meta = fileIconMeta(f.original_name || f.stored_name || '');
            var name = f.original_name || f.stored_name || 'file';
            return '<div class="file-chip" style="margin-top:6px">' +
              '<div class="file-chip-icon" style="background:'+meta.color+'">'+meta.label+'</div>' +
              '<div style="min-width:0;flex:1"><div class="file-chip-name">'+name+'</div></div>' +
              '<a class="btn btn-sm btn-ghost" href="api/download_file.php?file='+encodeURIComponent(f.path||'')+'" target="_blank">Tải</a>' +
            '</div>';
          }).join('') +
        '</div>';
    }

    var statusHTML = (a.status==='late')
      ? '<div class="modal-section"><span class="modal-label">Trạng thái</span><p style="font-size:13.5px;color:#D14343">Bài tập đã quá hạn và chưa được nộp. Em liên hệ giáo viên bộ môn nếu cần xin nộp bù.</p></div>'
      : (a.status==='pending')
        ? '<div class="modal-section"><span class="modal-label">Trạng thái</span><p style="font-size:13.5px">Chưa nộp · hạn nộp '+a.due+'</p></div>'
        : '<div class="modal-section"><span class="modal-label">Đã nộp lúc</span><p style="font-size:13.5px">'+(a.submittedAt||'—')+'</p>' +
          (a.submittedContent ? '<p style="font-size:12.5px;color:var(--ink-soft);margin-top:4px">'+a.submittedContent+'</p>' : '') + '</div>';

    var gradeHTML = a.grade
      ? '<div class="modal-section"><span class="modal-label">Điểm &amp; nhận xét</span>' +
        '<div class="exam-score" style="margin-bottom:6px">'+a.grade.split('/')[0]+'<small>/ '+a.grade.split('/')[1]+' điểm</small></div>' +
        '<p style="font-size:12.5px;color:var(--ink-soft)">'+(a.feedback||'')+'</p></div>'
      : '';

    body.innerHTML =
      '<div class="modal-head">' +
        '<div><div class="modal-title" id="assign-modal-title">'+a.title+'</div></div>' +
        '<button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button>' +
      '</div>' +
      metaHTML +
      '<div class="modal-section">' +
        '<span class="modal-label">Đề bài · Hạn nộp '+a.due+'</span>' +
        '<p style="font-size:13.5px;line-height:1.55">'+a.desc+'</p>' +
      '</div>' +
      attachHTML + statusHTML + gradeHTML +
      '<div class="modal-actions">' +
        '<button type="button" class="btn btn-ghost" data-close-modal style="flex:1">Đóng</button>' +
        (a.status==='pending' ? '<a class="btn btn-primary" style="flex:1" href="submit_assignment.php?id='+encodeURIComponent(a.id)+'">Nộp bài</a>' : '') +
      '</div>';

    wireModalCloseButtons(body);
    openModal('assign-modal');
  }

  function loadAssignments(){
    fetch('api/get_student_assignments.php')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(!data || !data.success) return;
        ASSIGNMENTS = (data.assignments || []).map(mapAssignment);
        renderAssignments('all');
        var shortcutSub = document.querySelector('.shortcut-card[data-tab-target="assignments"] .shortcut-sub');
        if(shortcutSub){
          var pending = ASSIGNMENTS.filter(function(a){ return a.status==='pending' || a.status==='late'; }).length;
          shortcutSub.textContent = pending + ' bài cần xử lý';
        }
      })
      .catch(function(){});
  }

  (function initAssignments(){
    fetch('../api/get_subjects.php')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(data && data.success){
          (data.subjects || []).forEach(function(s){ SUBJECT_NAMES[s.id] = s.name; });
        }
        loadAssignments();
      })
      .catch(function(){ loadAssignments(); });
  })();

  // ============ TIMETABLE ============
  var PERIOD_TIMES = ['07:00–07:45','07:50–08:35','08:50–09:35','09:40–10:25','10:30–11:15'];
  var DAYS = [
    {key:'T2', label:'Thứ 2'}, {key:'T3', label:'Thứ 3'}, {key:'T4', label:'Thứ 4'},
    {key:'T5', label:'Thứ 5'}, {key:'T6', label:'Thứ 6'}, {key:'T7', label:'Thứ 7'}
  ];
  var TIMETABLE = {
    T2: [ {subject:'Toán', teacher:'Thầy Long', room:'P.203'}, {subject:'Ngữ văn', teacher:'Cô Hoa', room:'P.203'},
          {subject:'Tiếng Anh', teacher:'Cô Lan', room:'P.203'}, {subject:'Thể dục', teacher:'Thầy Hùng', room:'Sân trường'} ],
    T3: [ {subject:'Vật lí', teacher:'Thầy Nam', room:'P. Lý'}, {subject:'Hóa học', teacher:'Cô Yến', room:'P. Hóa'},
          {subject:'Toán', teacher:'Thầy Long', room:'P.203'}, {subject:'Tin học', teacher:'Thầy Đức', room:'P. Tin'} ],
    T4: [ {subject:'Ngữ văn', teacher:'Cô Hoa', room:'P.203'}, {subject:'Sinh học', teacher:'Cô Thu', room:'P.203'},
          {subject:'Giáo dục công dân', teacher:'Cô Mai', room:'P.203'}, {subject:'Toán', teacher:'Thầy Long', room:'P.203'} ],
    T5: [ {subject:'Tiếng Anh', teacher:'Cô Lan', room:'P.203'}, {subject:'Toán', teacher:'Thầy Long', room:'P.203'},
          {subject:'Lịch sử', teacher:'Thầy Sơn', room:'P.203'}, {subject:'Địa lí', teacher:'Cô Hà', room:'P.203'} ],
    T6: [ {subject:'Vật lí', teacher:'Thầy Nam', room:'P. Lý'}, {subject:'Ngữ văn', teacher:'Cô Hoa', room:'P.203'},
          {subject:'Công nghệ', teacher:'Thầy Kiên', room:'P.203'}, {subject:'Thể dục', teacher:'Thầy Hùng', room:'Sân trường'} ],
    T7: [ {subject:'Sinh hoạt lớp', teacher:'Cô Hoa (GVCN)', room:'P.203'}, {subject:'Toán', teacher:'Thầy Long', room:'P.203'},
          {subject:'Hoạt động trải nghiệm', teacher:'Thầy Long', room:'P.203'} ]
  };
  var TODAY_KEY = 'T5';

  var SUBJECT_MAP = {
    'TD': 'Thể dục', 'GDTC': 'Giáo dục thể chất',
    'AN': 'Âm nhạc', 'MT': 'Mỹ thuật',
    'Tin': 'Tin học', 'TIN': 'Tin học',
    'Văn': 'Ngữ văn', 'VAN': 'Ngữ văn',
    'Toán': 'Toán', 'TOAN': 'Toán',
    'Anh': 'Tiếng Anh', 'ANH': 'Tiếng Anh', 'TA': 'Tiếng Anh',
    'Lý': 'Vật lí', 'LY': 'Vật lí', 'VL': 'Vật lí',
    'Hóa': 'Hóa học', 'HOA': 'Hóa học',
    'Sinh': 'Sinh học', 'SINH': 'Sinh học',
    'Sử': 'Lịch sử', 'SU': 'Lịch sử',
    'Địa': 'Địa lí', 'DIA': 'Địa lí',
    'CN': 'Công nghệ', 'CONGNGHE': 'Công nghệ',
    'GDCD': 'Giáo dục công dân',
    'GDĐP': 'Giáo dục địa phương', 'GDDP': 'Giáo dục địa phương',
    'SHDC': 'Sinh hoạt dưới cờ', 'SHL': 'Sinh hoạt lớp',
    'HĐTN': 'Hoạt động trải nghiệm', 'HDTN': 'Hoạt động trải nghiệm',
    'HĐTN,HN': 'Hoạt động trải nghiệm', 'HĐTN-HN': 'Hoạt động trải nghiệm',
    'KHTN': 'Khoa học tự nhiên',
    'LS-ĐL': 'Lịch sử và Địa lí', 'LS&ĐL': 'Lịch sử và Địa lí', 'LSĐL': 'Lịch sử và Địa lí',
    'GDQP': 'Giáo dục quốc phòng', 'GDQP-AN': 'Giáo dục quốc phòng'
  };

  function formatSubjectName(s) {
    if (!s) return '';
    var trimmed = String(s).trim();
    return SUBJECT_MAP[trimmed] || SUBJECT_MAP[trimmed.toUpperCase()] || trimmed;
  }

  function formatTeacherShort(name) {
    if (!name) return '';
    var clean = String(name).trim();
    if (!clean) return '';
    clean = clean.replace(/^(thầy|cô|gv\.?|giáo viên)\s+/i, '');
    var parts = clean.split(/\s+/);
    if (parts.length === 1) return 'GV. ' + parts[0];
    var lastName = parts[parts.length - 1];
    if (parts.length >= 4 && !['thị', 'văn', 'ngọc', 'hữu', 'minh', 'viết'].includes(parts[parts.length - 2].toLowerCase())) {
      lastName = parts[parts.length - 2] + ' ' + parts[parts.length - 1];
    }
    return 'GV. ' + lastName;
  }

  function formatTeacherFull(name) {
    if (!name) return '';
    var clean = String(name).trim();
    if (!clean) return '';
    if (/^(thầy|cô|gv\.?|giáo viên)/i.test(clean)) return clean;
    return 'GV. ' + clean;
  }

  function escapeHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function renderDayChips(){
    var wrap = document.getElementById('day-chips');
    wrap.innerHTML = DAYS.map(function(d){
      var rows = TIMETABLE[d.key] || [];
      var count = rows.filter(function(x){ return !!x; }).length;
      return '<button type="button" class="day-chip" data-day="'+d.key+'" aria-pressed="'+(d.key===TODAY_KEY)+'">' +
        '<span class="d-label">'+escapeHtml(d.label)+(d.key===TODAY_KEY?' · Hôm nay':'')+'</span><span class="d-num">'+count+' tiết</span>' +
      '</button>';
    }).join('');
    wrap.querySelectorAll('.day-chip').forEach(function(chip){
      chip.addEventListener('click', function(){
        wrap.querySelectorAll('.day-chip').forEach(function(c){ c.setAttribute('aria-pressed','false'); });
        chip.setAttribute('aria-pressed','true');
        renderAgenda(chip.dataset.day);
      });
    });
  }
  function renderAgenda(dayKey){
    var wrap = document.getElementById('agenda-list');
    var periods = TIMETABLE[dayKey] || [];
    wrap.innerHTML = periods.map(function(p, i){
      if(!p) return '';
      var subName = formatSubjectName(p.subject);
      var teacherName = p.teacher ? formatTeacherFull(p.teacher) : '';
      return '<div class="agenda-item">' +
        '<div class="agenda-time"><strong>Tiết '+(i+1)+'</strong>'+PERIOD_TIMES[i]+'</div>' +
        '<div class="agenda-rail"></div>' +
        '<div class="agenda-body">' +
          '<div class="agenda-subject">'+escapeHtml(subName)+'</div>' +
          '<div class="agenda-meta">' +
            (teacherName ? '<span class="agenda-tag teacher">'+ICONS.person+escapeHtml(teacherName)+'</span>' : '<span class="agenda-tag">Chưa xếp GV</span>') +
            (p.room ? '<span class="agenda-tag room">'+ICONS.room+escapeHtml(p.room)+'</span>' : '') +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');
  }
  function renderWeekGrid(){
    var wrap = document.getElementById('week-grid');
    var maxPeriods = PERIOD_TIMES.length > 5 ? PERIOD_TIMES.length : 5;
    var html = '<div class="wg-cell wg-head"></div>';
    DAYS.forEach(function(d){ html += '<div class="wg-cell wg-head">'+escapeHtml(d.label)+'</div>'; });
    for(var i=0;i<maxPeriods;i++){
      html += '<div class="wg-cell wg-time">Tiết '+(i+1)+'<br>'+(PERIOD_TIMES[i]||'')+'</div>';
      DAYS.forEach(function(d){
        var p = (TIMETABLE[d.key]||[])[i];
        if(p){
          var subName = formatSubjectName(p.subject);
          var teacherShort = p.teacher ? formatTeacherShort(p.teacher) : '';
          var teacherFull = p.teacher ? formatTeacherFull(p.teacher) : '';
          var tooltip = 'Môn: ' + subName + (teacherFull ? ' · ' + teacherFull : '') + (p.room ? ' · Phòng: ' + p.room : '');
          html += '<div class="wg-cell has-lesson" title="'+escapeHtml(tooltip)+'">' +
            '<div class="wg-subject">'+escapeHtml(subName)+'</div>' +
            (teacherShort ? '<div class="wg-teacher">'+ICONS.person+escapeHtml(teacherShort)+'</div>' : '') +
            (p.room ? '<div class="wg-room">'+ICONS.room+escapeHtml(p.room)+'</div>' : '') +
            '</div>';
        } else {
          html += '<div class="wg-cell empty-cell"></div>';
        }
      });
    }
    wrap.innerHTML = html;
  }

  function teacherInitials(name){
    if(!name) return 'GV';
    var parts = name.trim().split(/\s+/);
    if(parts.length > 1){
      return (parts[parts.length-2][0] + parts[parts.length-1][0]).toUpperCase();
    }
    return name.substr(0, 2).toUpperCase();
  }

  // ============ CLASS DATA (timetable + GVCN) ============
  var GVCN_TEACHER = '';
  var GVCN_POSTS = [];

  function loadClassData(){
    fetch('api/get_class_dashboard.php')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(!data || !data.success) return;
        if(data.today_key) TODAY_KEY = data.today_key;
        GVCN_TEACHER = data.teacher || '';
        GVCN_POSTS = data.posts || [];

        if(data.timetable){
          if(data.timetable.period_times) PERIOD_TIMES = data.timetable.period_times;
          if(data.timetable.days && data.timetable.days.length) DAYS = data.timetable.days;
          if(data.timetable.schedule) TIMETABLE = data.timetable.schedule;
        }

        var nameEl = document.getElementById('gvcn-teacher-name');
        if(nameEl && GVCN_TEACHER){
          nameEl.textContent = 'Thầy/Cô ' + GVCN_TEACHER + ' · Giáo viên chủ nhiệm';
        }

        renderDayChips();
        renderAgenda(TODAY_KEY);
        renderWeekGrid();
        renderGvcnPosts();
      })
      .catch(function(){
        renderDayChips();
        renderAgenda(TODAY_KEY);
        renderWeekGrid();
        renderGvcnPosts();
      });
  }

  function renderGvcnPosts(){
    var wrap = document.getElementById('gvcn-list');
    if(!GVCN_POSTS.length){
      wrap.innerHTML = '<p class="empty-note">Chưa có thông báo nào từ giáo viên chủ nhiệm.</p>';
      return;
    }
    wrap.innerHTML = GVCN_POSTS.map(function(p){
      return '<article class="post-card'+(p.pinned?' pinned':'')+'">' +
        (p.pinned ? '<span class="post-pin">'+ICONS.pin+'</span>' : '') +
        '<div class="post-head">' +
          '<div class="post-avatar">'+teacherInitials(GVCN_TEACHER)+'</div>' +
          '<div><div class="post-name">'+GVCN_TEACHER+'</div><div class="post-role">Giáo viên chủ nhiệm</div></div>' +
          '<span class="post-time">'+p.time+'</span>' +
        '</div>' +
        '<div class="post-title">'+p.title+'</div>' +
        '<div class="post-body">'+p.body+'</div>' +
      '</article>';
    }).join('');
  }
  loadClassData();

  // ============ PRACTICE ============
  var PRACTICE_SUBJECTS = [];
  var PRACTICE_GRADE = '';

  function loadPracticeSubjects(){
    fetch('api/list_practice_subjects.php')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(!data || !data.success) return;
        PRACTICE_GRADE = data.grade || '';
        PRACTICE_SUBJECTS = data.subjects || [];
        renderPracticeSubjects();
      })
      .catch(function(){});
  }

  function renderPracticeSubjects(){
    var wrap = document.getElementById('practice-subjects');
    wrap.innerHTML = PRACTICE_SUBJECTS.map(function(s){
      var pct = s.total > 0 ? Math.round((s.done/s.total)*100) : 0;
      return '<button type="button" class="subject-card" data-subject="'+s.subject+'" aria-pressed="false">' +
        '<div class="subject-icon">'+ICONS.book+'</div>' +
        '<div class="subject-name">'+s.name+'</div>' +
        '<div class="progress-track"><div class="progress-fill" style="width:'+pct+'%"></div></div>' +
        '<div class="progress-label">'+pct+'% ngân hàng câu hỏi</div>' +
      '</button>';
    }).join('');
    wrap.querySelectorAll('.subject-card').forEach(function(card){
      card.addEventListener('click', function(){
        wrap.querySelectorAll('.subject-card').forEach(function(c){ c.setAttribute('aria-pressed','false'); });
        card.setAttribute('aria-pressed','true');
        renderTopics(card.dataset.subject);
      });
    });
  }
  function renderTopics(subjectKey){
    var subject = PRACTICE_SUBJECTS.find(function(s){ return s.subject===subjectKey; });
    var wrap = document.getElementById('practice-topics');
    if(!subject){ wrap.innerHTML=''; return; }
    wrap.innerHTML =
      '<div class="section-head"><span class="section-title">Chủ đề · '+subject.name+'</span></div>' +
      subject.topics.map(function(t){
        var pct = t.total > 0 ? Math.round((t.done/t.total)*100) : 0;
        return '<div class="topic-row">' +
          '<div class="topic-info">' +
            '<div class="topic-name">'+t.name+'</div>' +
            '<div class="topic-meta">'+t.done+'/'+t.total+' câu đã luyện · '+pct+'%</div>' +
          '</div>' +
          '<button type="button" class="btn btn-primary btn-sm" data-quiz-subject="'+subjectKey+'" data-quiz-topic="'+encodeURIComponent(t.name)+'">Luyện tập</button>' +
        '</div>';
      }).join('');
    wrap.querySelectorAll('[data-quiz-subject]').forEach(function(btn){
      btn.addEventListener('click', function(){
        startQuiz(btn.dataset.quizSubject, decodeURIComponent(btn.dataset.quizTopic));
      });
    });
  }

  function startQuiz(subjectKey, topicName){
    var subject = PRACTICE_SUBJECTS.find(function(s){ return s.subject===subjectKey; });
    if(!subject || !PRACTICE_GRADE) return;
    var url = '../api/get_questions.php?grade='+encodeURIComponent(PRACTICE_GRADE)+
              '&subject='+encodeURIComponent(subjectKey)+
              '&topic='+encodeURIComponent(topicName)+'&limit=10';
    fetch(url)
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(!data || !data.success || !data.questions.length){
          alert('Chưa có câu hỏi cho chủ đề này.');
          return;
        }
        var questions = data.questions.map(function(q){
          return {
            q: q.question,
            options: q.options,
            correct: q.correct,
            type: q.type || 'single',
            explain: '',
            topic: q.topic || topicName,
            lesson: q.lesson || '',
            image: q.image || ''
          };
        });
        openQuiz(topicName, subjectKey, questions);
      })
      .catch(function(){});
  }
  loadPracticeSubjects();

  var quizState = null;
  function openQuiz(topicName, subjectKey, questions){
    quizState = { topic:topicName, subject:subjectKey, questions:questions, index:0, score:0, answers:[] };
    renderQuizQuestion();
    openModal('quiz-modal');
  }
  function renderQuizQuestion(){
    var body = document.getElementById('quiz-modal-body');
    var st = quizState;
    var q = st.questions[st.index];
    var pct = Math.round((st.index/st.questions.length)*100);
    var letters = ['A','B','C','D'];
    var isMulti = q.type === 'multiple' || Array.isArray(q.correct);
    body.innerHTML =
      '<div class="modal-head">' +
        '<div><div class="modal-title" id="quiz-modal-title">'+st.topic+'</div></div>' +
        '<button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button>' +
      '</div>' +
      '<div class="quiz-progress"><span>Câu '+(st.index+1)+'/'+st.questions.length+'</span><span>Điểm: '+st.score+'</span></div>' +
      '<div class="quiz-track"><div style="width:'+pct+'%"></div></div>' +
      '<div class="quiz-question">'+q.q+(q.image ? '<img class="quiz-image" src="'+q.image+'" alt="Hình minh họa câu hỏi">' : '')+'</div>' +
      '<div class="quiz-options" data-multi="'+(isMulti?'1':'0')+'">' +
        q.options.map(function(opt, i){
          return '<button type="button" class="quiz-option" data-opt="'+i+'" aria-pressed="false">' +
            '<span class="opt-letter">'+letters[i]+'</span><span>'+opt+'</span>' +
          '</button>';
        }).join('') +
      '</div>' +
      (isMulti ? '<div class="quiz-hint">Chọn tất cả đáp án đúng</div>' : '') +
      '<div class="modal-actions"><button type="button" class="btn btn-primary" id="quiz-next-btn" disabled style="width:100%">Kiểm tra đáp án</button></div>';

    wireModalCloseButtons(body);

    var optionBtns = body.querySelectorAll('.quiz-option');
    var nextBtn = document.getElementById('quiz-next-btn');
    var checked = false;
    var selected = [];

    optionBtns.forEach(function(btn){
      btn.addEventListener('click', function(){
        if(checked) return;
        var i = parseInt(btn.dataset.opt,10);
        if(isMulti){
          var idx = selected.indexOf(i);
          if(idx === -1){ selected.push(i); btn.setAttribute('aria-pressed','true'); }
          else { selected.splice(idx,1); btn.setAttribute('aria-pressed','false'); }
        } else {
          selected = [i];
          optionBtns.forEach(function(b){ b.setAttribute('aria-pressed','false'); });
          btn.setAttribute('aria-pressed','true');
        }
        nextBtn.disabled = selected.length === 0;
      });
    });

    nextBtn.addEventListener('click', function(){
      if(!checked){
        checked = true;
        var isCorrect;
        if(isMulti){
          var correctArr = [].concat(q.correct).map(Number).sort();
          var selArr = selected.slice().map(Number).sort();
          isCorrect = correctArr.length === selArr.length && correctArr.every(function(v,i){ return v === selArr[i]; });
          optionBtns.forEach(function(b){
            var i = parseInt(b.dataset.opt,10);
            b.disabled = true;
            if(correctArr.indexOf(i) !== -1) b.classList.add('correct');
            else if(selected.indexOf(i) !== -1) b.classList.add('incorrect');
          });
        } else {
          var c = Number(q.correct);
          isCorrect = selected.length === 1 && selected[0] === c;
          optionBtns.forEach(function(b){
            var i = parseInt(b.dataset.opt,10);
            b.disabled = true;
            if(i === c) b.classList.add('correct');
            else if(selected[0] === i) b.classList.add('incorrect');
          });
        }
        if(isCorrect) st.score += 1;
        st.answers.push({
          question_index: st.index,
          question: q.q,
          user_answer: isMulti ? selected.slice().sort() : (selected[0] ?? null),
          correct_answer: isMulti ? [].concat(q.correct).map(Number).sort() : Number(q.correct),
          is_correct: isCorrect,
          type: q.type || 'single',
          topic: q.topic || st.topic,
          lesson: q.lesson || ''
        });
        body.querySelector('.quiz-progress span:last-child').textContent = 'Điểm: '+st.score;
        nextBtn.textContent = (st.index === st.questions.length - 1) ? 'Xem kết quả' : 'Câu tiếp theo';
      } else if(st.index < st.questions.length - 1){
        st.index += 1;
        renderQuizQuestion();
      } else {
        renderQuizResult();
      }
    });
  }
  function renderQuizResult(){
    var body = document.getElementById('quiz-modal-body');
    var st = quizState;
    var pct = Math.round((st.score/st.questions.length)*100);
    var msg = pct>=80 ? 'Xuất sắc! Em nắm rất chắc chủ đề này.' : pct>=50 ? 'Khá ổn! Luyện thêm để chắc kiến thức hơn nhé.' : 'Em nên ôn lại chủ đề này thêm một chút nhé.';
    body.innerHTML =
      '<div class="modal-head">' +
        '<div><div class="modal-title">Kết quả luyện tập</div></div>' +
        '<button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button>' +
      '</div>' +
      '<div class="quiz-result">' +
        '<div class="quiz-score-ring" style="--pct:'+pct+'"><div class="quiz-score-ring-inner">' +
          '<span class="quiz-score-num">'+st.score+'/'+st.questions.length+'</span><span class="quiz-score-den">câu đúng</span>' +
        '</div></div>' +
        '<div class="quiz-result-title">'+st.topic+'</div>' +
        '<div class="quiz-result-sub">'+msg+'</div>' +
        '<div class="modal-actions">' +
          '<button type="button" class="btn btn-ghost" id="quiz-retry-btn">Làm lại</button>' +
          '<button type="button" class="btn btn-primary" data-close-modal>Đóng</button>' +
        '</div>' +
      '</div>';
    wireModalCloseButtons(body);
    document.getElementById('quiz-retry-btn').addEventListener('click', function(){
      quizState.index = 0; quizState.score = 0; quizState.answers = [];
      renderQuizQuestion();
    });
    savePracticeResult(st);
  }

  function savePracticeResult(st){
    var correctAnswers = 0;
    st.answers.forEach(function(a){ if(a.is_correct) correctAnswers += 1; });
    var payload = {
      student_code: STUDENT_CODE,
      student_name: STUDENT_NAME,
      class_code: STUDENT_CLASS_CODE,
      subject: st.subject,
      topic: st.topic,
      lesson: (st.answers[0] && st.answers[0].lesson) || '',
      total_questions: st.questions.length,
      correct_answers: correctAnswers,
      incorrect_answers: st.questions.length - correctAnswers,
      score_percentage: Math.round((correctAnswers/st.questions.length)*100),
      timestamp: new Date().toISOString(),
      question_results: st.answers
    };
    fetch('../api/save_practice.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function(r){ return r.json(); }).then(function(){
      loadPracticeSubjects();
    }).catch(function(){});
  }

})();
</script>
</body>
</html>
