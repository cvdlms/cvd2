<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';

$title = 'Bài Tập - CVD';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Bài tập của tôi — EduVN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  /* ============ RESET ============ */
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  html{-webkit-text-size-adjust:100%}
  body{min-height:100vh;overflow-x:hidden}
  button{font:inherit;color:inherit;background:none;border:none;cursor:pointer}
  a{color:inherit;text-decoration:none}
  img{max-width:100%;display:block}
  svg{display:block}
  :focus-visible{outline:2px solid var(--focus);outline-offset:3px;border-radius:4px}
  ::selection{background:var(--secondary); color:#fff}

  /* ============ TOKENS — CUTE (default) ============ */
  body{
    --bg:#FFF8F4; --bg-dot: rgba(244,86,140,0.16);
    --surface:#FFFFFF; --surface-alt:#FFF1F5;
    --ink:#3D2C2E; --ink-soft:#8A6F72;
    --primary:#F4568C; --primary-ink:#FFFFFF;
    --secondary:#8B7CF6; --accent:#FFC857;
    --good:#0E8F79; --good-soft:#DFF7F1;
    --bad:#D14343; --bad-soft:#FBE4E4;
    --warn:#B8860B; --warn-soft:#FFF4D6;
    --border:#F6DEE4; --focus:#8B7CF6;
    --radius-card:22px; --radius-sm:14px; --radius-pill:999px;
    --shadow: 0 14px 34px -16px rgba(244,86,140,.32), 0 2px 8px rgba(61,44,46,.05);
    --shadow-sm: 0 6px 16px -8px rgba(61,44,46,.14);
    --font-display:'Baloo 2','Be Vietnam Pro',sans-serif;
    --font-body:'Be Vietnam Pro',sans-serif;
    --font-mono:'Space Mono',monospace;
    --card-border-width:0px; --tape-1:#FFC857; --tape-2:#8B7CF6; --tape-3:#4ECDC4;
    background:var(--bg);
    background-image: radial-gradient(var(--bg-dot) 1.4px, transparent 1.4px);
    background-size:15px 15px;
    color:var(--ink); font-family:var(--font-body);
    transition: background-color .4s ease, color .4s ease;
  }
  body[data-theme="elegant"]{
    --bg:#12151A; --surface:#1A1E26; --surface-alt:#20242D;
    --ink:#ECE9E2; --ink-soft:#9BA0AA;
    --primary:#C9A857; --primary-ink:#12151A;
    --secondary:#6F91B5;
    --good:#5FBE9A; --good-soft:rgba(95,190,154,.1);
    --bad:#E07575; --bad-soft:rgba(224,117,117,.1);
    --warn:#C9A857; --warn-soft:rgba(201,168,87,.1);
    --border:rgba(201,168,87,.16); --focus:#C9A857;
    --radius-card:10px; --radius-sm:6px;
    --shadow: 0 24px 48px -22px rgba(0,0,0,.65);
    --shadow-sm: 0 8px 20px -10px rgba(0,0,0,.5);
    --card-border-width:1px;
    background: var(--bg);
    background-image: radial-gradient(60% 40% at 50% -10%, rgba(201,168,87,.10), transparent 60%);
  }

  /* ============ SHARED COMPONENTS ============ */
  .btn{ display:inline-flex; align-items:center; gap:8px; justify-content:center;
    padding:13px 20px; border-radius:var(--radius-pill); font-weight:700; font-size:14px; white-space:nowrap;
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
  .btn svg{width:16px;height:16px}
  .btn-primary{background:var(--primary); color:var(--primary-ink); box-shadow:var(--shadow-sm)}
  .btn-primary:hover{filter:brightness(1.06)}
  .btn-ghost{background:var(--surface-alt); color:var(--ink); border:1px solid var(--border)}
  .btn-sm{padding:9px 14px; font-size:12.5px}
  .btn:disabled{opacity:.5; cursor:not-allowed}

  .theme-toggle{ position:relative;display:flex;align-items:center; background:var(--surface-alt);
    border:1px solid var(--border); border-radius:var(--radius-pill); padding:3px; gap:2px; flex:none; }
  .theme-toggle button{ position:relative;z-index:2;display:flex;align-items:center;gap:6px;
    padding:7px 11px;border-radius:var(--radius-pill); font-size:12px;font-weight:600;color:var(--ink-soft);
    transition:color .25s ease; white-space:nowrap; }
  .theme-toggle button[aria-pressed="true"]{color:var(--primary-ink)}
  .theme-toggle button svg{width:13px;height:13px;flex:none}
  .toggle-pill{ position:absolute; top:3px; bottom:3px; left:3px; width:calc(50% - 3px);
    border-radius:var(--radius-pill); background:var(--primary); transition: transform .32s cubic-bezier(.65,0,.35,1); }
  body[data-theme="elegant"] .toggle-pill{ transform: translateX(100%); }
  .toggle-label{display:none}
  @media (min-width:480px){ .toggle-label{display:inline} }

  .pill{display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--radius-pill); font-size:11.5px; font-weight:700; flex:none}
  body[data-theme="cute"] .pill-pending{background:#FFE1EC;color:#C41E5C}
  body[data-theme="cute"] .pill-submitted{background:#E7EEFF;color:#2A56C6}
  body[data-theme="cute"] .pill-late{background:#F1EDEA;color:#8A7A76}
  body[data-theme="cute"] .pill-graded{background:var(--good-soft);color:var(--good)}
  body[data-theme="elegant"] .pill{background:transparent; border:1px solid; font-family:var(--font-mono); font-weight:400}
  body[data-theme="elegant"] .pill-pending{border-color:#C97575;color:#E39B9B}
  body[data-theme="elegant"] .pill-submitted{border-color:var(--secondary);color:#9EC0E3}
  body[data-theme="elegant"] .pill-late{border-color:var(--ink-soft);color:var(--ink-soft)}
  body[data-theme="elegant"] .pill-graded{border-color:var(--good);color:var(--good)}

  .type-badge{display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:var(--ink-soft);
    background:var(--surface-alt); border:1px solid var(--border); padding:6px 12px; border-radius:var(--radius-pill)}
  .type-badge svg{width:13px;height:13px}

  /* ============ TOPBAR ============ */
  .topbar{ position:sticky; top:0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:12px 16px; background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(10px);
    border-bottom:1px solid var(--border); }
  .back-btn{ width:36px;height:36px;border-radius:var(--radius-sm); background:var(--surface-alt); border:1px solid var(--border);
    display:flex;align-items:center;justify-content:center; flex:none; color:var(--ink) }
  .back-btn svg{width:17px;height:17px}
  .brand{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
  .brand-mark{ width:36px;height:36px;border-radius: var(--radius-sm); background:var(--primary); color:var(--primary-ink);
    display:flex;align-items:center;justify-content:center;flex:none; font-family:var(--font-display); font-weight:800; font-size:14px; }
  .brand-text{min-width:0}
  .brand-title{font-family:var(--font-display);font-weight:700;font-size:14px;line-height:1.2; white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .brand-sub{font-size:10.5px;color:var(--ink-soft)}
  .topbar-right{display:flex;align-items:center;gap:8px;flex:none}

  /* ============ LAYOUT ============ */
  .layout{ max-width:820px; margin:0 auto; padding:18px 16px 48px; display:flex; flex-direction:column; gap:18px; }
  @media (min-width:1024px){ .layout{ padding:28px 24px 56px; gap:22px; } }

  .panel-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:18px}
  .panel-title{font-family:var(--font-display); font-weight:700; font-size:14.5px; margin-bottom:14px; display:flex; align-items:center; gap:8px}
  .panel-title svg{width:16px;height:16px; color:var(--primary)}

  /* ============ HERO ============ */
  .hero-list{ position:relative; background:var(--surface); border:var(--card-border-width) solid var(--border);
    border-radius:var(--radius-card); box-shadow:var(--shadow); padding:24px 22px; overflow:hidden; }
  body[data-theme="cute"] .hero-list::before{
    content:""; position:absolute; top:-8px; left:26px; width:60px; height:20px;
    background:repeating-linear-gradient(115deg, var(--tape-2) 0 6px, #d9cfff 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
  body[data-theme="elegant"] .hero-list::before, body[data-theme="elegant"] .hero-list::after{
    content:""; position:absolute; width:16px; height:16px; border-radius:50%; background:var(--bg); top:50%; transform:translateY(-50%); }
  body[data-theme="elegant"] .hero-list::before{left:-8px} body[data-theme="elegant"] .hero-list::after{right:-8px}

  .hero-eyebrow{ font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); margin-bottom:6px }
  .hero-title-row{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:6px}
  .hero-title{font-family:var(--font-display); font-weight:700; font-size:21px; line-height:1.25}
  .hero-sub{font-size:12.5px; color:var(--ink-soft); line-height:1.55}

  /* ============ STATS ============ */
  .stats-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:10px}
  @media (min-width:640px){ .stats-grid{grid-template-columns:repeat(4,1fr)} }
  .stat-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:13px; display:flex; align-items:center; gap:10px}
  .stat-icon{width:34px;height:34px;border-radius:var(--radius-sm); flex:none; display:flex;align-items:center;justify-content:center}
  .stat-icon svg{width:16px;height:16px}
  .stat-icon.primary{background:var(--surface-alt); color:var(--primary)}
  .stat-icon.secondary{background:var(--surface-alt); color:var(--secondary)}
  .stat-icon.good{background:var(--good-soft); color:var(--good)}
  .stat-icon.warn{background:var(--warn-soft); color:var(--warn)}
  .stat-num{font-family:var(--font-display); font-weight:700; font-size:17px; line-height:1.1}
  .stat-label{font-size:10.5px; color:var(--ink-soft)}

  /* ============ LIST ============ */
  .list-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px }
  .list-head .panel-title{margin-bottom:0}

  .assign-item{ background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card);
    box-shadow:var(--shadow-sm); padding:15px; margin-bottom:11px; }
  .assign-top{display:flex; align-items:flex-start; justify-content:space-between; gap:10px}
  .assign-title{font-family:var(--font-display); font-weight:600; font-size:15px; line-height:1.35; color:var(--ink)}
  .assign-badges{display:flex; gap:6px; flex-wrap:wrap; margin-top:8px}
  .assign-sub{font-size:11.5px; color:var(--ink-soft); margin:8px 0 12px}

  .assign-meta-row{ display:grid; grid-template-columns:repeat(2,1fr); gap:8px; padding-top:12px; border-top:1px solid var(--border) }
  @media (min-width:640px){ .assign-meta-row{grid-template-columns:repeat(4,1fr)} }
  .assign-meta-item{background:var(--surface-alt); border-radius:var(--radius-sm); padding:9px 11px}
  .assign-meta-label{font-size:9.5px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px}
  .assign-meta-value{font-weight:700; font-size:12px; line-height:1.4}
  .assign-meta-value.urgent{color:var(--bad)}

  .assign-foot{display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:12px}
  .assign-foot-left{display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12px}
  .assign-foot-note{font-size:11.5px; color:var(--ink-soft)}
  .assign-score{ display:inline-flex; align-items:center; gap:8px; }
  .assign-score b{font-family:var(--font-display); font-weight:800; font-size:16px; color:var(--good)}

  /* loading / empty / error */
  .load-state{ display:flex; flex-direction:column; align-items:center; gap:10px; padding:28px 10px; }
  .spinner{ width:34px;height:34px;border-radius:50%; border:3px solid var(--border); border-top-color:var(--primary);
    animation:spin .8s linear infinite; }
  @keyframes spin{to{transform:rotate(360deg)}}
  .empty-state{ text-align:center; padding:30px 16px; }
  .empty-icon{ width:56px;height:56px;border-radius:50%; margin:0 auto 12px; background:var(--surface-alt); color:var(--ink-soft);
    display:flex;align-items:center;justify-content:center }
  .empty-icon svg{width:24px;height:24px}
  .empty-title{font-family:var(--font-display); font-weight:700; font-size:15px; margin-bottom:4px}
  .empty-text{font-size:12.5px; color:var(--ink-soft); line-height:1.55}

  [hidden]{display:none !important}
  @media (prefers-reduced-motion: reduce){ *{transition-duration:.001ms !important; animation-duration:.001ms !important} }
</style>
</head>
<body data-theme="<?php echo htmlspecialchars($stdDesignTheme); ?>">

<header class="topbar">
  <button type="button" class="back-btn" id="back-btn" aria-label="Quay lại">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
  </button>
  <div class="brand">
    <div class="brand-mark">BT</div>
    <div class="brand-text">
      <div class="brand-title">Bài tập của tôi</div>
      <div class="brand-sub">Thi Trực Tuyến · EduVN Manager</div>
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
  </div>
</header>

<div class="layout">

  <!-- ===== HERO ===== -->
  <section class="hero-list">
    <div class="hero-eyebrow">Bài tập</div>
    <div class="hero-title-row">
      <h1 class="hero-title">Bài tập của em</h1>
      <a href="my_submissions.php" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9l8-5 8 5v10"/><path d="M9 19v-5h6v5"/></svg>
        Lịch sử nộp bài
      </a>
    </div>
    <p class="hero-sub">Xem và nộp bài tập được giao từ giáo viên · Hạn nộp được tính theo giờ Việt Nam</p>
  </section>

  <!-- ===== STATS ===== -->
  <section class="stats-grid" id="stats-grid" hidden>
    <div class="stat-card">
      <div class="stat-icon primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <div><div class="stat-num" id="stat-total">0</div><div class="stat-label">Tổng bài tập</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/><circle cx="12" cy="12" r="3.2"/></svg></div>
      <div><div class="stat-num" id="stat-pending">0</div><div class="stat-label">Chưa nộp</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></div>
      <div><div class="stat-num" id="stat-submitted">0</div><div class="stat-label">Đã nộp</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
      <div><div class="stat-num" id="stat-graded">0</div><div class="stat-label">Đã chấm điểm</div></div>
    </div>
  </section>

  <!-- ===== LIST ===== -->
  <section class="panel-card" id="list-card">
    <div class="list-head">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Danh sách bài tập
      </div>
    </div>
    <div id="assignments-list">
      <div class="load-state">
        <div class="spinner"></div>
        <p style="font-size:12.5px;color:var(--ink-soft)">Đang tải bài tập…</p>
      </div>
    </div>
  </section>

</div>

<script>
(function(){
  "use strict";

  var SUBJECTS = <?php echo json_encode($subjects, JSON_UNESCAPED_UNICODE); ?>;

  var ICONS = {
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>',
    person: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c1.4-3.2 4-5 6.5-5s5.1 1.8 6.5 5"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>',
    paperclip: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.4 11.6 12.6 20.4a5 5 0 0 1-7.1-7.1l9-9a3.5 3.5 0 0 1 5 5l-9 9a2 2 0 0 1-2.8-2.8l8.1-8.1"/></svg>',
    cal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    box: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>'
  };

  var allAssignments = [];

  // ============ THEME TOGGLE ============
  function setTheme(theme){
    document.body.dataset.theme = theme;
    document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
      btn.setAttribute('aria-pressed', String(btn.dataset.themeBtn === theme));
    });
  }
  (function(){
    var saved;
    try { saved = localStorage.getItem('eduvn_student_theme_v2'); } catch(e){}
    if(saved === 'cute' || saved === 'elegant') setTheme(saved);
  })();
  document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
    btn.addEventListener('click', function(){
      setTheme(btn.dataset.themeBtn);
      try { localStorage.setItem('eduvn_student_theme_v2', btn.dataset.themeBtn); } catch(e){}
    });
  });

  // ============ BACK BUTTON ============
  document.getElementById('back-btn').addEventListener('click', function(){
    if(window.history.length > 1){ window.history.back(); }
    else { window.location.href = 'dashboard.php'; }
  });

  // ============ HELPERS ============
  function esc(s){
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }
  function pad(n){ return String(n).padStart(2,'0'); }
  function parseDate(s){
    return new Date(String(s).replace(' ', 'T'));
  }
  function fmtDate(s){
    var d = parseDate(s);
    if(isNaN(d.getTime())) return '—';
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()+' '+pad(d.getHours())+':'+pad(d.getMinutes());
  }
  function subjectName(id){
    return SUBJECTS[String(id)] || SUBJECTS[id] || (id !== undefined && id !== null ? String(id) : 'Môn học');
  }
  function isExpired(a){
    var d = parseDate(a.due_date);
    return isNaN(d.getTime()) ? false : d.getTime() < Date.now();
  }
  function statusOf(a){
    var sub = a.my_submission;
    if(sub){
      return (sub.score !== null && sub.score !== undefined) ? 'graded' : 'submitted';
    }
    return isExpired(a) ? 'late' : 'pending';
  }
  function remainingText(dueStr){
    var due = parseDate(dueStr);
    if(isNaN(due.getTime())) return '—';
    var diff = due.getTime() - Date.now();
    if(diff <= 0){
      var lateDays = Math.floor(-diff / 86400000);
      return lateDays > 0 ? 'Quá hạn ' + lateDays + ' ngày' : 'Quá hạn hôm nay';
    }
    var days = Math.floor(diff / 86400000);
    var hours = Math.floor((diff % 86400000) / 3600000);
    return 'Còn ' + (days > 0 ? days + ' ngày ' : '') + hours + ' giờ';
  }

  // ============ STATS ============
  function renderStats(){
    var total = allAssignments.length;
    if(total === 0) return;
    var pending = 0, submitted = 0, graded = 0;
    allAssignments.forEach(function(a){
      var st = statusOf(a);
      if(st === 'pending') pending++;
      else if(st === 'submitted') submitted++;
      else if(st === 'graded') graded++;
    });
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-pending').textContent = pending;
    document.getElementById('stat-submitted').textContent = submitted;
    document.getElementById('stat-graded').textContent = graded;
    document.getElementById('stats-grid').hidden = false;
  }

  // ============ LIST ============
  function renderList(){
    var wrap = document.getElementById('assignments-list');

    if(allAssignments.length === 0){
      wrap.innerHTML =
        '<div class="empty-state">' +
          '<div class="empty-icon">' + ICONS.box + '</div>' +
          '<div class="empty-title">Chưa có bài tập nào</div>' +
          '<p class="empty-text">Giáo viên chưa giao bài tập cho lớp của em. Hãy quay lại sau nhé!</p>' +
        '</div>';
      return;
    }

    var STATUS_MAP = {
      pending: ['pill-pending', 'Chưa nộp'],
      submitted: ['pill-submitted', 'Đã nộp'],
      graded: ['pill-graded', 'Đã chấm'],
      late: ['pill-late', 'Quá hạn']
    };
    var BTN_MAP = {
      pending: ['btn-primary', 'Nộp bài'],
      submitted: ['btn-ghost', 'Xem lại bài nộp'],
      graded: ['btn-ghost', 'Xem kết quả'],
      late: ['btn-ghost', 'Xem chi tiết']
    };

    wrap.innerHTML = allAssignments.map(function(a){
      var st = statusOf(a);
      var sub = a.my_submission;
      var isGroup = (parseInt(a.max_group_members, 10) || 1) > 1;
      var typeHtml = (isGroup ? ICONS.users : ICONS.person) + '<span>' + (isGroup ? 'Bài nhóm' : 'Bài cá nhân') + '</span>';
      var attachCount = Array.isArray(a.attachments) ? a.attachments.length : 0;

      var footNote;
      var scoreHtml = '';
      if(st === 'graded'){
        scoreHtml = '<span class="assign-score">Điểm: <b>' + sub.score + ' / ' + (a.max_score || 10) + '</b></span>';
        footNote = 'Đã chấm bởi giáo viên';
      } else if(st === 'submitted'){
        footNote = 'Đang chờ giáo viên chấm điểm';
      } else if(st === 'late'){
        footNote = 'Đã quá hạn nộp bài';
      } else {
        footNote = remainingText(a.due_date);
      }

      return '<div class="assign-item">' +
        '<div class="assign-top">' +
          '<div class="assign-title">' + esc(a.title || 'Bài tập') + '</div>' +
          '<span class="pill ' + STATUS_MAP[st][0] + '">' + STATUS_MAP[st][1] + '</span>' +
        '</div>' +
        '<div class="assign-badges">' +
          '<span class="type-badge">' + typeHtml + '</span>' +
          '<span class="type-badge">' + esc(subjectName(a.subject_id)) + '</span>' +
        '</div>' +
        '<div class="assign-sub">Điểm tối đa: <strong>' + (a.max_score || 10) + '</strong></div>' +
        '<div class="assign-meta-row">' +
          '<div class="assign-meta-item"><div class="assign-meta-label">Ngày giao</div><div class="assign-meta-value">' + (a.created_at ? fmtDate(a.created_at) : '—') + '</div></div>' +
          '<div class="assign-meta-item"><div class="assign-meta-label">Hạn nộp</div><div class="assign-meta-value">' + fmtDate(a.due_date) + '</div></div>' +
          '<div class="assign-meta-item"><div class="assign-meta-label">Thời gian còn lại</div><div class="assign-meta-value' + (st === 'late' ? ' urgent' : '') + '">' + remainingText(a.due_date) + '</div></div>' +
          '<div class="assign-meta-item"><div class="assign-meta-label">Tệp đính kèm</div><div class="assign-meta-value">' + attachCount + ' tệp</div></div>' +
        '</div>' +
        '<div class="assign-foot">' +
          '<div class="assign-foot-left">' + scoreHtml + '<span class="assign-foot-note">' + footNote + '</span></div>' +
          '<button type="button" class="btn ' + BTN_MAP[st][0] + ' btn-sm" data-open-assign="' + esc(a.id) + '">' + BTN_MAP[st][1] + '</button>' +
        '</div>' +
      '</div>';
    }).join('');

    wrap.querySelectorAll('[data-open-assign]').forEach(function(btn){
      btn.addEventListener('click', function(){
        window.location.href = 'submit_assignment.php?id=' + encodeURIComponent(btn.dataset.openAssign);
      });
    });
  }

  // ============ LOAD ============
  function loadAssignments(){
    fetch('api/get_student_assignments.php')
      .then(function(resp){ return resp.json(); })
      .then(function(data){
        if(data.success){
          allAssignments = Array.isArray(data.assignments) ? data.assignments : [];
        } else {
          allAssignments = [];
        }
        renderStats();
        renderList();
      })
      .catch(function(err){
        console.error('Error loading assignments:', err);
        document.getElementById('assignments-list').innerHTML =
          '<div class="empty-state">' +
            '<div class="empty-icon">' + ICONS.box + '</div>' +
            '<div class="empty-title">Lỗi tải bài tập</div>' +
            '<p class="empty-text">Không thể kết nối tới máy chủ. Vui lòng thử lại sau.</p>' +
          '</div>';
      });
  }

  // ============ INIT ============
  loadAssignments();

})();
</script>
</body>
</html>
