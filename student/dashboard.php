<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';
$studentSchool = 'Trường THCS Nguyễn Du';

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
<body data-theme="cute">
<script>
(function () {
  var t = null;
  try { t = localStorage.getItem('eduvn_student_theme'); } catch (e) {}
  if (t === 'cute' || t === 'elegant') { document.body.setAttribute('data-theme', t); }
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
          <div class="hero-name">Kiểm tra giữa kỳ — Môn Toán học</div>
          <div class="hero-meta">
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8"/></svg>30 câu trắc nghiệm</span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>Thời lượng 45 phút</span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>Phòng thi trực tuyến</span>
          </div>
          <div class="hero-bottom">
            <div>
              <div class="countdown" data-countdown="2830">
                <span class="num" data-cd-h>00</span><span class="colon">:</span>
                <span class="num" data-cd-m>00</span><span class="colon">:</span>
                <span class="num" data-cd-s>00</span>
              </div>
              <div class="countdown-label">Thời gian còn lại để nộp bài</div>
            </div>
            <button class="btn btn-primary">
              Vào phòng thi
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
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
        <p class="greeting-sub">Cô Nguyễn Thị Hoa · Giáo viên chủ nhiệm lớp <?php echo htmlspecialchars($studentClass ?: $studentClassCode); ?></p>
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
          <li><a href="#" style="color:#D14343"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>Đăng xuất</a></li>
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

  var EXAMS = [
    { subject:"Toán học", title:"Kiểm tra giữa kỳ", status:"open", info:"45 phút · 30 câu trắc nghiệm", cta:"Vào thi" },
    { subject:"Ngữ Văn", title:"Thi học kỳ I", status:"upcoming", info:"Mở lúc 08:00, 15/08/2026 · còn 2 ngày", cta:"Chi tiết" },
    { subject:"Sinh Học", title:"Thi cuối kỳ", status:"upcoming", info:"Mở lúc 07:30, 20/08/2026 · còn 16 ngày", cta:"Chi tiết" },
    { subject:"Tiếng Anh", title:"Kiểm tra 15 phút", status:"done", info:"Nộp lúc 10:20, 03/08/2026", score:"9.5", cta:"Xem bài làm" },
    { subject:"Vật Lý", title:"Ôn tập chương 3", status:"done", info:"Nộp lúc 15:40, 01/08/2026", score:"8.0", cta:"Xem bài làm" },
    { subject:"Hóa Học", title:"Kiểm tra thường xuyên", status:"closed", info:"Đã đóng lúc 20:00, 01/08/2026", cta:"Đã đóng", disabled:true }
  ];

  function examCard(e){
    var scoreBlock = e.score
      ? '<div class="exam-score">'+e.score+'<small>/ 10 điểm</small></div>'
      : '<span></span>';
    var btnClass = e.disabled ? "btn btn-ghost btn-sm" : "btn btn-primary btn-sm";
    return (
      '<article class="exam-card">' +
        '<div class="exam-top">' +
          '<div><div class="exam-subject">'+e.subject+'</div><div class="exam-title">'+e.title+'</div></div>' +
          '<span class="pill pill-'+e.status+'">'+STATUS_LABEL[e.status]+'</span>' +
        '</div>' +
        '<div class="exam-info"><span>'+ICONS.clock+e.info+'</span></div>' +
        '<div class="ticket-divider"></div>' +
        '<div class="exam-bottom">' +
          scoreBlock +
          '<button class="'+btnClass+'"'+(e.disabled?' disabled':'')+'>'+e.cta+'</button>' +
        '</div>' +
      '</article>'
    );
  }

  // populate lists
  var homePreview = document.getElementById('home-exam-preview');
  EXAMS.filter(function(e){ return e.status==='open' || e.status==='upcoming'; })
    .slice(0,3)
    .forEach(function(e){ homePreview.insertAdjacentHTML('beforeend', examCard(e)); });

  var fullList = document.getElementById('exam-full-list');
  function renderFullList(filter){
    fullList.innerHTML = '';
    EXAMS.filter(function(e){ return filter==='all' || e.status===filter; })
      .forEach(function(e){ fullList.insertAdjacentHTML('beforeend', examCard(e)); });
  }
  renderFullList('all');

  // filter chips
  var chips = document.querySelectorAll('#filter-chips .chip');
  chips.forEach(function(chip){
    chip.addEventListener('click', function(){
      chips.forEach(function(c){ c.setAttribute('aria-pressed','false'); });
      chip.setAttribute('aria-pressed','true');
      renderFullList(chip.dataset.filter);
    });
  });

  // stats
  var statsHTML =
    '<div class="stat-card"><div class="stat-num">12</div><div class="stat-label">Đã hoàn thành</div></div>' +
    '<div class="stat-card"><div class="stat-num">8.6</div><div class="stat-label">Điểm trung bình</div></div>' +
    '<div class="stat-card"><div class="stat-num">2</div><div class="stat-label">Bài thi tuần này</div></div>';
  document.getElementById('mobile-stats').innerHTML = statsHTML;
  document.getElementById('desktop-stats').innerHTML = statsHTML;

  // greeting by time of day
  var h = new Date().getHours();
  var g = h < 11 ? "Chào buổi sáng" : h < 13 ? "Chào buổi trưa" : h < 18 ? "Chào buổi chiều" : "Chào buổi tối";
  document.querySelector('[data-greeting]').textContent = g + ", " + STUDENT_NAME;

  // countdown
  var cdEl = document.querySelector('[data-countdown]');
  var remaining = parseInt(cdEl.dataset.countdown, 10); // seconds
  var hEl = document.querySelector('[data-cd-h]'), mEl = document.querySelector('[data-cd-m]'), sEl = document.querySelector('[data-cd-s]');
  function pad(n){ return String(n).padStart(2,'0'); }
  function renderCountdown(){
    var hh = Math.floor(remaining/3600), mm = Math.floor((remaining%3600)/60), ss = remaining%60;
    hEl.textContent = pad(hh); mEl.textContent = pad(mm); sEl.textContent = pad(ss);
  }
  renderCountdown();
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  setInterval(function(){
    if(remaining > 0) remaining -= 1;
    renderCountdown();
  }, reduceMotion ? 60000 : 1000);

  // theme toggle (all instances stay in sync, persisted)
  function setTheme(theme){
    document.body.dataset.theme = theme;
    document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
      btn.setAttribute('aria-pressed', String(btn.dataset.themeBtn === theme));
    });
    try { localStorage.setItem('eduvn_student_theme', theme); } catch (e) {}
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

  var ASSIGNMENTS = [
    { id:1, subject:"Ngữ Văn", title:"Viết đoạn văn nghị luận xã hội", type:"individual", teacher:"Cô Hoa",
      due:"22:00, 06/08/2026", status:"pending", urgent:true,
      desc:"Viết một đoạn văn khoảng 200 chữ trình bày suy nghĩ của em về ý nghĩa của việc đọc sách trong thời đại số." },
    { id:2, subject:"Toán học", title:"Giải hệ phương trình bậc nhất hai ẩn", type:"individual", teacher:"Thầy Long",
      due:"23:59, 08/08/2026", status:"pending",
      desc:"Hoàn thành các bài tập 1–5 trang 42 SGK. Trình bày lời giải chi tiết, có thể chụp ảnh vở hoặc gõ file." },
    { id:3, subject:"Tin học", title:"Thuyết trình nhóm: An toàn thông tin", type:"group", teacher:"Thầy Đức",
      due:"08:00, 12/08/2026", status:"pending", group:"Nhóm 3: Minh Anh, Gia Bảo, Thanh Trúc",
      desc:"Chuẩn bị bài thuyết trình khoảng 10 phút về chủ đề an toàn thông tin cá nhân trên mạng xã hội." },
    { id:4, subject:"GDCD", title:"Sưu tầm tình huống pháp luật thực tế", type:"group", teacher:"Cô Mai",
      due:"21:00, 30/07/2026", status:"submitted", group:"Nhóm 1: Minh Anh, Đức Anh, Hà My",
      submittedAt:"21:00, 30/07/2026",
      desc:"Sưu tầm và phân tích một tình huống vi phạm pháp luật thường gặp ở lứa tuổi học sinh." },
    { id:5, subject:"Tiếng Anh", title:"Bài tập Unit 5 — Writing", type:"individual", teacher:"Cô Lan",
      due:"20:15, 02/08/2026", status:"graded", grade:"9/10",
      submittedAt:"20:15, 02/08/2026", feedback:"Bài viết mạch lạc, chú ý chia đúng thì động từ ở đoạn 2.",
      desc:"Viết một đoạn văn ngắn 100–120 từ miêu tả kế hoạch cuối tuần của em." },
    { id:6, subject:"Vật Lý", title:"Bài tập chương 2: Điện học", type:"individual", teacher:"Thầy Nam",
      due:"23:59, 28/07/2026", status:"late",
      desc:"Hoàn thành phiếu bài tập chương 2 — đã quá hạn nộp, liên hệ giáo viên nếu cần nộp bù." }
  ];

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
    var ctaLabel = a.status==='pending' ? 'Nộp bài' : a.status==='late' ? 'Xem chi tiết' : 'Xem chi tiết';
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
          scoreBlock +
          '<button type="button" class="btn btn-primary btn-sm" data-open-assign="'+a.id+'">'+ctaLabel+'</button>' +
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
      btn.addEventListener('click', function(){ openAssignModal(parseInt(btn.dataset.openAssign,10)); });
    });
  }
  renderAssignments('all');
  document.querySelectorAll('#assign-filter-chips .chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      document.querySelectorAll('#assign-filter-chips .chip').forEach(function(c){ c.setAttribute('aria-pressed','false'); });
      chip.setAttribute('aria-pressed','true');
      renderAssignments(chip.dataset.afilter);
    });
  });

  function fileIconMeta(name){
    var ext = name.split('.').pop().toLowerCase();
    var map = {
      doc:{label:'DOC',color:'#2A56C6'}, docx:{label:'DOC',color:'#2A56C6'},
      xls:{label:'XLS',color:'#0E8F5B'}, xlsx:{label:'XLS',color:'#0E8F5B'},
      pdf:{label:'PDF',color:'#D14343'},
      jpg:{label:'IMG',color:'#8B7CF6'}, jpeg:{label:'IMG',color:'#8B7CF6'}, png:{label:'IMG',color:'#8B7CF6'}
    };
    return map[ext] || {label:'FILE',color:'#8A6F72'};
  }
  function formatSize(bytes){
    if(bytes < 1024) return bytes+' B';
    if(bytes < 1024*1024) return (bytes/1024).toFixed(0)+' KB';
    return (bytes/1024/1024).toFixed(1)+' MB';
  }

  var selectedFiles = [];
  function renderFileChips(){
    var wrap = document.getElementById('assign-file-list');
    if(!wrap) return;
    wrap.innerHTML = selectedFiles.map(function(f,i){
      var meta = fileIconMeta(f.name);
      return '<div class="file-chip">' +
        '<div class="file-chip-icon" style="background:'+meta.color+'">'+meta.label+'</div>' +
        '<div style="min-width:0;flex:1"><div class="file-chip-name">'+f.name+'</div><div class="file-chip-size">'+formatSize(f.size)+'</div></div>' +
        '<button type="button" class="file-chip-remove" data-remove-file="'+i+'" aria-label="Xóa tệp">'+ICONS.close+'</button>' +
      '</div>';
    }).join('');
    wrap.querySelectorAll('[data-remove-file]').forEach(function(btn){
      btn.addEventListener('click', function(){
        selectedFiles.splice(parseInt(btn.dataset.removeFile,10),1);
        renderFileChips();
      });
    });
  }

  function openAssignModal(id){
    var a = ASSIGNMENTS.find(function(x){ return x.id===id; });
    if(!a) return;
    selectedFiles = [];
    var body = document.getElementById('assign-modal-body');
    var typeLine = a.type==='group'
      ? '<span class="type-badge" style="margin-bottom:14px">'+ICONS.users+(a.group||'Nhóm')+'</span>'
      : '<span class="type-badge" style="margin-bottom:14px">'+ICONS.person+'Cá nhân</span>';
    var metaHTML =
      '<div class="exam-info" style="margin-bottom:6px">' +
        '<span>'+ICONS.doc+a.subject+' · GV '+a.teacher+'</span>' +
      '</div>' + typeLine;

    var formHTML =
      '<div class="modal-section">' +
        '<span class="modal-label">Đề bài · Hạn nộp '+a.due+'</span>' +
        '<p style="font-size:13.5px;line-height:1.55">'+a.desc+'</p>' +
      '</div>' +
      '<div class="modal-section">' +
        '<label class="modal-label" for="assign-note">Nội dung / Ghi chú</label>' +
        '<textarea class="modal-textarea" id="assign-note" placeholder="Ghi chú thêm cho giáo viên (không bắt buộc)…"></textarea>' +
      '</div>' +
      '<div class="modal-section">' +
        '<span class="modal-label">Tệp đính kèm</span>' +
        '<div class="dropzone" id="assign-dropzone" tabindex="0" role="button" aria-label="Chọn tệp đính kèm">' +
          ICONS.upload +
          '<p>Kéo thả tệp vào đây hoặc <span class="link">chọn tệp</span></p>' +
          '<p class="hint">Word, Excel, PDF hoặc hình ảnh — tối đa 20MB/tệp</p>' +
          '<input type="file" id="assign-file-input" hidden multiple accept=".doc,.docx,.xls,.xlsx,.pdf,.jpg,.jpeg,.png">' +
        '</div>' +
        '<div class="file-chip-list" id="assign-file-list"></div>' +
      '</div>' +
      '<div class="modal-actions">' +
        '<button type="button" class="btn btn-ghost" data-close-modal>Hủy</button>' +
        '<button type="button" class="btn btn-primary" id="assign-submit-btn">Nộp bài</button>' +
      '</div>';

    var readonlyHTML =
      '<div class="modal-section">' +
        '<span class="modal-label">Đề bài</span>' +
        '<p style="font-size:13.5px;line-height:1.55">'+a.desc+'</p>' +
      '</div>' +
      (a.status==='late'
        ? '<div class="modal-section"><span class="modal-label">Trạng thái</span><p style="font-size:13.5px;color:#D14343">Bài tập đã quá hạn và chưa được nộp. Em liên hệ giáo viên bộ môn nếu cần xin nộp bù.</p></div>'
        : '<div class="modal-section"><span class="modal-label">Đã nộp lúc</span><p style="font-size:13.5px">'+(a.submittedAt||'—')+'</p></div>') +
      (a.grade
        ? '<div class="modal-section"><span class="modal-label">Điểm &amp; nhận xét</span>' +
          '<div class="exam-score" style="margin-bottom:6px">'+a.grade.split('/')[0]+'<small>/ '+a.grade.split('/')[1]+' điểm</small></div>' +
          '<p style="font-size:12.5px;color:var(--ink-soft)">'+(a.feedback||'')+'</p></div>'
        : '') +
      '<div class="modal-actions"><button type="button" class="btn btn-ghost" data-close-modal style="flex:1">Đóng</button></div>';

    body.innerHTML =
      '<div class="modal-head">' +
        '<div><div class="modal-title" id="assign-modal-title">'+a.title+'</div></div>' +
        '<button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button>' +
      '</div>' +
      metaHTML + (a.status==='pending' ? formHTML : readonlyHTML);

    wireModalCloseButtons(body);

    if(a.status==='pending'){
      var dz = document.getElementById('assign-dropzone');
      var input = document.getElementById('assign-file-input');
      dz.addEventListener('click', function(){ input.click(); });
      dz.addEventListener('keydown', function(ev){ if(ev.key==='Enter'||ev.key===' '){ ev.preventDefault(); input.click(); } });
      input.addEventListener('change', function(){
        Array.prototype.forEach.call(input.files, function(f){ selectedFiles.push(f); });
        renderFileChips();
        input.value = '';
      });
      ['dragenter','dragover'].forEach(function(evt){
        dz.addEventListener(evt, function(ev){ ev.preventDefault(); dz.classList.add('dragover'); });
      });
      ['dragleave','drop'].forEach(function(evt){
        dz.addEventListener(evt, function(ev){ ev.preventDefault(); dz.classList.remove('dragover'); });
      });
      dz.addEventListener('drop', function(ev){
        Array.prototype.forEach.call(ev.dataTransfer.files, function(f){ selectedFiles.push(f); });
        renderFileChips();
      });
      document.getElementById('assign-submit-btn').addEventListener('click', function(){
        var note = document.getElementById('assign-note').value.trim();
        if(!note && selectedFiles.length===0){
          alert('Em hãy nhập nội dung hoặc đính kèm ít nhất một tệp trước khi nộp bài nhé.');
          return;
        }
        a.status = 'submitted';
        var now = new Date();
        a.submittedAt = pad(now.getHours())+':'+pad(now.getMinutes())+', hôm nay';
        var activeChip = document.querySelector('#assign-filter-chips .chip[aria-pressed="true"]');
        renderAssignments(activeChip ? activeChip.dataset.afilter : 'all');
        body.innerHTML =
          '<div class="submit-success">' +
            '<div class="submit-success-icon">'+ICONS.check+'</div>' +
            '<div class="submit-success-title">Đã nộp bài thành công!</div>' +
            '<div class="submit-success-sub">Giáo viên sẽ chấm và phản hồi sớm nhất.</div>' +
            '<button type="button" class="btn btn-primary" data-close-modal style="width:100%">Xong</button>' +
          '</div>';
        wireModalCloseButtons(body);
      });
    }

    openModal('assign-modal');
  }

  // ============ TIMETABLE ============
  var PERIOD_TIMES = ['07:00–07:45','07:50–08:35','08:50–09:35','09:40–10:25','10:30–11:15'];
  var DAYS = [
    {key:'T2', label:'Thứ 2'}, {key:'T3', label:'Thứ 3'}, {key:'T4', label:'Thứ 4'},
    {key:'T5', label:'Thứ 5'}, {key:'T6', label:'Thứ 6'}, {key:'T7', label:'Thứ 7'}
  ];
  var TIMETABLE = {
    T2: [ {subject:'Toán học', teacher:'Thầy Long', room:'P.203'}, {subject:'Ngữ Văn', teacher:'Cô Hoa', room:'P.203'},
          {subject:'Tiếng Anh', teacher:'Cô Lan', room:'P.203'}, {subject:'Thể dục', teacher:'Thầy Hùng', room:'Sân trường'} ],
    T3: [ {subject:'Vật Lý', teacher:'Thầy Nam', room:'P. Lý'}, {subject:'Hóa Học', teacher:'Cô Yến', room:'P. Hóa'},
          {subject:'Toán học', teacher:'Thầy Long', room:'P.203'}, {subject:'Tin học', teacher:'Thầy Đức', room:'P. Tin'} ],
    T4: [ {subject:'Ngữ Văn', teacher:'Cô Hoa', room:'P.203'}, {subject:'Sinh Học', teacher:'Cô Thu', room:'P.203'},
          {subject:'GDCD', teacher:'Cô Mai', room:'P.203'}, {subject:'Toán học', teacher:'Thầy Long', room:'P.203'} ],
    T5: [ {subject:'Tiếng Anh', teacher:'Cô Lan', room:'P.203'}, {subject:'Toán học', teacher:'Thầy Long', room:'P.203'},
          {subject:'Lịch Sử', teacher:'Thầy Sơn', room:'P.203'}, {subject:'Địa Lý', teacher:'Cô Hà', room:'P.203'} ],
    T6: [ {subject:'Vật Lý', teacher:'Thầy Nam', room:'P. Lý'}, {subject:'Ngữ Văn', teacher:'Cô Hoa', room:'P.203'},
          {subject:'Công Nghệ', teacher:'Thầy Kiên', room:'P.203'}, {subject:'Thể dục', teacher:'Thầy Hùng', room:'Sân trường'} ],
    T7: [ {subject:'Sinh hoạt lớp', teacher:'Cô Hoa (GVCN)', room:'P.203'}, {subject:'Toán học', teacher:'Thầy Long', room:'P.203'},
          {subject:'Ôn tập', teacher:'Thầy Long', room:'P.203'} ]
  };
  var TODAY_KEY = 'T5';

  function renderDayChips(){
    var wrap = document.getElementById('day-chips');
    wrap.innerHTML = DAYS.map(function(d){
      return '<button type="button" class="day-chip" data-day="'+d.key+'" aria-pressed="'+(d.key===TODAY_KEY)+'">' +
        '<span class="d-label">'+d.label+(d.key===TODAY_KEY?' · Hôm nay':'')+'</span><span class="d-num">'+TIMETABLE[d.key].length+' tiết</span>' +
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
      return '<div class="agenda-item">' +
        '<div class="agenda-time"><strong>Tiết '+(i+1)+'</strong>'+PERIOD_TIMES[i]+'</div>' +
        '<div class="agenda-rail"></div>' +
        '<div class="agenda-body">' +
          '<div class="agenda-subject">'+p.subject+'</div>' +
          '<div class="agenda-meta"><span>'+ICONS.person+p.teacher+'</span><span>'+ICONS.room+p.room+'</span></div>' +
        '</div>' +
      '</div>';
    }).join('');
  }
  function renderWeekGrid(){
    var wrap = document.getElementById('week-grid');
    var maxPeriods = 5;
    var html = '<div class="wg-cell wg-head"></div>';
    DAYS.forEach(function(d){ html += '<div class="wg-cell wg-head">'+d.label+'</div>'; });
    for(var i=0;i<maxPeriods;i++){
      html += '<div class="wg-cell wg-time">Tiết '+(i+1)+'<br>'+PERIOD_TIMES[i]+'</div>';
      DAYS.forEach(function(d){
        var p = TIMETABLE[d.key][i];
        html += p
          ? '<div class="wg-cell"><div class="wg-subject">'+p.subject+'</div><div class="wg-room">'+p.room+'</div></div>'
          : '<div class="wg-cell"></div>';
      });
    }
    wrap.innerHTML = html;
  }
  renderDayChips();
  renderAgenda(TODAY_KEY);
  renderWeekGrid();

  // ============ GVCN NOTICES ============
  var GVCN_POSTS = [
    { pinned:true, title:'Họp phụ huynh học kỳ I', time:'Hôm nay, 08:20',
      body:'Kính mời quý phụ huynh tham dự họp phụ huynh học kỳ I vào 19:00 thứ Bảy, 15/08/2026 tại phòng học lớp 9A2. Rất mong quý phụ huynh sắp xếp thời gian tham dự.' },
    { pinned:false, title:'Nhắc nộp giấy khám sức khỏe đầu năm', time:'Hôm qua, 14:05',
      body:'Các em nộp giấy khám sức khỏe cho cô trước ngày 10/08. Bạn nào chưa khám vui lòng sắp xếp đi khám sớm.' },
    { pinned:false, title:'Lịch nghỉ lễ Quốc khánh 2/9', time:'3 ngày trước',
      body:'Lớp nghỉ học từ 01/09 đến hết 03/09/2026, đi học lại bình thường vào 04/09.' },
    { pinned:false, title:'Khen thưởng tổ 2 tuần vừa qua', time:'1 tuần trước',
      body:'Tổ 2 tuần này giữ trật tự và hoàn thành bài tập đầy đủ nhất lớp. Cô khen cả tổ, các bạn tiếp tục phát huy nhé!' }
  ];
  function renderGvcnPosts(){
    var wrap = document.getElementById('gvcn-list');
    wrap.innerHTML = GVCN_POSTS.map(function(p){
      return '<article class="post-card'+(p.pinned?' pinned':'')+'">' +
        (p.pinned ? '<span class="post-pin">'+ICONS.pin+'</span>' : '') +
        '<div class="post-head">' +
          '<div class="post-avatar">CH</div>' +
          '<div><div class="post-name">Cô Nguyễn Thị Hoa</div><div class="post-role">Giáo viên chủ nhiệm</div></div>' +
          '<span class="post-time">'+p.time+'</span>' +
        '</div>' +
        '<div class="post-title">'+p.title+'</div>' +
        '<div class="post-body">'+p.body+'</div>' +
      '</article>';
    }).join('');
  }
  renderGvcnPosts();

  // ============ PRACTICE ============
  var PRACTICE_SUBJECTS = [
    { key:'toan', name:'Toán học', progress:65, topics:[
        {name:'Phương trình bậc hai', total:20, done:12},
        {name:'Hàm số bậc nhất', total:15, done:9},
        {name:'Hình học: Tam giác đồng dạng', total:18, done:15}
      ]},
    { key:'anh', name:'Tiếng Anh', progress:40, topics:[
        {name:'Unit 4–5: Từ vựng', total:25, done:8},
        {name:'Ngữ pháp: Thì hiện tại hoàn thành', total:16, done:6}
      ]},
    { key:'ly', name:'Vật Lý', progress:22, topics:[
        {name:'Chương 2: Điện học', total:20, done:4},
        {name:'Chương 3: Quang học', total:14, done:3}
      ]},
    { key:'van', name:'Ngữ Văn', progress:10, topics:[
        {name:'Nghị luận xã hội', total:12, done:1},
        {name:'Tác phẩm: Truyện Kiều', total:10, done:1}
      ]}
  ];
  var QUESTION_BANKS = {
    toan: [
      { q:'Nghiệm của phương trình x² − 5x + 6 = 0 là:', options:['x = 2, x = 3','x = 1, x = 6','x = −2, x = −3','x = 2, x = −3'], correct:0, explain:'Phân tích thành nhân tử: (x−2)(x−3) = 0 nên x = 2 hoặc x = 3.' },
      { q:'Hàm số y = 2x − 3 đồng biến hay nghịch biến trên ℝ?', options:['Đồng biến','Nghịch biến','Không xác định','Vừa đồng biến vừa nghịch biến'], correct:0, explain:'Vì hệ số a = 2 > 0 nên hàm số đồng biến trên ℝ.' },
      { q:'Tổng hai nghiệm của phương trình x² − 7x + 10 = 0 bằng:', options:['10','−7','7','5'], correct:2, explain:'Theo định lý Viète: tổng hai nghiệm bằng −b/a = 7.' },
      { q:'Hai tam giác đồng dạng là hai tam giác có:', options:['Diện tích bằng nhau','Các góc tương ứng bằng nhau và cạnh tương ứng tỉ lệ','Chu vi bằng nhau','Cùng nội tiếp một đường tròn'], correct:1, explain:'Đây chính là định nghĩa của hai tam giác đồng dạng.' }
    ],
    anh: [
      { q:'Choose the correct word: "She ___ to school every day."', options:['go','goes','going','gone'], correct:1, explain:'Chủ ngữ số ít "She" đi với động từ thêm s/es ở thì hiện tại đơn.' },
      { q:'"I ___ my homework already." — chọn dạng đúng của thì hiện tại hoàn thành:', options:['have finished','has finished','finished','am finished'], correct:0, explain:'Chủ ngữ "I" dùng "have" + động từ phân từ hai (V3/ed).' },
      { q:'Từ đồng nghĩa với "happy" là:', options:['sad','glad','angry','tired'], correct:1, explain:'"Glad" mang nghĩa tương đương với "happy" (vui vẻ).' },
      { q:'"Although it rained, we ___ the trip." — điền từ phù hợp:', options:['enjoyed','enjoy','enjoying','to enjoy'], correct:0, explain:'Câu kể lại sự việc đã xảy ra nên dùng thì quá khứ đơn.' }
    ]
  };
  QUESTION_BANKS.ly = QUESTION_BANKS.toan;
  QUESTION_BANKS.van = QUESTION_BANKS.anh;

  function renderPracticeSubjects(){
    var wrap = document.getElementById('practice-subjects');
    wrap.innerHTML = PRACTICE_SUBJECTS.map(function(s){
      return '<button type="button" class="subject-card" data-subject="'+s.key+'" aria-pressed="false">' +
        '<div class="subject-icon">'+ICONS.book+'</div>' +
        '<div class="subject-name">'+s.name+'</div>' +
        '<div class="progress-track"><div class="progress-fill" style="width:'+s.progress+'%"></div></div>' +
        '<div class="progress-label">'+s.progress+'% ngân hàng câu hỏi</div>' +
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
    var subject = PRACTICE_SUBJECTS.find(function(s){ return s.key===subjectKey; });
    var wrap = document.getElementById('practice-topics');
    if(!subject){ wrap.innerHTML=''; return; }
    wrap.innerHTML =
      '<div class="section-head"><span class="section-title">Chủ đề · '+subject.name+'</span></div>' +
      subject.topics.map(function(t, i){
        var pct = Math.round((t.done/t.total)*100);
        return '<div class="topic-row">' +
          '<div class="topic-info">' +
            '<div class="topic-name">'+t.name+'</div>' +
            '<div class="topic-meta">'+t.done+'/'+t.total+' câu đã luyện · '+pct+'%</div>' +
          '</div>' +
          '<button type="button" class="btn btn-primary btn-sm" data-quiz-subject="'+subjectKey+'" data-quiz-topic="'+i+'">Luyện tập</button>' +
        '</div>';
      }).join('');
    wrap.querySelectorAll('[data-quiz-subject]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var sKey = btn.dataset.quizSubject;
        var tIdx = parseInt(btn.dataset.quizTopic,10);
        var s = PRACTICE_SUBJECTS.find(function(x){ return x.key===sKey; });
        openQuiz(s.topics[tIdx].name, QUESTION_BANKS[sKey] || QUESTION_BANKS.toan);
      });
    });
  }
  renderPracticeSubjects();

  var quizState = null;
  function openQuiz(topicName, questions){
    quizState = { topic:topicName, questions:questions, index:0, score:0 };
    renderQuizQuestion();
    openModal('quiz-modal');
  }
  function renderQuizQuestion(){
    var body = document.getElementById('quiz-modal-body');
    var st = quizState;
    var q = st.questions[st.index];
    var pct = Math.round((st.index/st.questions.length)*100);
    var letters = ['A','B','C','D'];
    body.innerHTML =
      '<div class="modal-head">' +
        '<div><div class="modal-title" id="quiz-modal-title">'+st.topic+'</div></div>' +
        '<button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button>' +
      '</div>' +
      '<div class="quiz-progress"><span>Câu '+(st.index+1)+'/'+st.questions.length+'</span><span>Điểm: '+st.score+'</span></div>' +
      '<div class="quiz-track"><div style="width:'+pct+'%"></div></div>' +
      '<div class="quiz-question">'+q.q+'</div>' +
      '<div class="quiz-options">' +
        q.options.map(function(opt, i){
          return '<button type="button" class="quiz-option" data-opt="'+i+'" aria-pressed="false">' +
            '<span class="opt-letter">'+letters[i]+'</span><span>'+opt+'</span>' +
          '</button>';
        }).join('') +
      '</div>' +
      '<div class="quiz-explain" id="quiz-explain">'+q.explain+'</div>' +
      '<div class="modal-actions"><button type="button" class="btn btn-primary" id="quiz-next-btn" disabled style="width:100%">Kiểm tra đáp án</button></div>';

    wireModalCloseButtons(body);

    var optionBtns = body.querySelectorAll('.quiz-option');
    var nextBtn = document.getElementById('quiz-next-btn');
    var checked = false;
    var selected = null;

    optionBtns.forEach(function(btn){
      btn.addEventListener('click', function(){
        if(checked) return;
        optionBtns.forEach(function(b){ b.setAttribute('aria-pressed','false'); });
        btn.setAttribute('aria-pressed','true');
        selected = parseInt(btn.dataset.opt,10);
        nextBtn.disabled = false;
      });
    });

    nextBtn.addEventListener('click', function(){
      if(!checked){
        checked = true;
        optionBtns.forEach(function(b){
          var i = parseInt(b.dataset.opt,10);
          b.disabled = true;
          if(i === q.correct) b.classList.add('correct');
          else if(i === selected) b.classList.add('incorrect');
        });
        if(selected === q.correct) st.score += 1;
        document.getElementById('quiz-explain').classList.add('show');
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
      quizState.index = 0; quizState.score = 0;
      renderQuizQuestion();
    });
  }

})();
</script>
</body>
</html>
