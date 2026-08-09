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

$title = 'Lịch Sử Nộp Bài - CVD';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Lịch sử nộp bài — EduVN</title>
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
  body[data-theme="cute"] .pill-graded{background:var(--good-soft);color:var(--good)}
  body[data-theme="elegant"] .pill{background:transparent; border:1px solid; font-family:var(--font-mono); font-weight:400}
  body[data-theme="elegant"] .pill-pending{border-color:#C97575;color:#E39B9B}
  body[data-theme="elegant"] .pill-graded{border-color:var(--good);color:var(--good)}

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
    background:repeating-linear-gradient(115deg, var(--tape-3) 0 6px, #c3f0ea 6px 12px);
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

  .sub-item{ background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card);
    box-shadow:var(--shadow-sm); margin-bottom:11px; overflow:hidden; }
  .sub-head{ width:100%; display:flex; align-items:center; gap:12px; padding:15px; text-align:left }
  .sub-title-block{flex:1; min-width:0}
  .sub-title{font-family:var(--font-display); font-weight:600; font-size:15px; line-height:1.35; color:var(--ink)}
  .sub-meta{font-size:11.5px; color:var(--ink-soft); margin-top:4px; display:flex; align-items:center; gap:6px; flex-wrap:wrap}
  .sub-meta svg{width:13px;height:13px}
  .sub-right{display:flex; align-items:center; gap:10px; flex:none}
  .sub-chevron{width:18px;height:18px; flex:none; color:var(--ink-soft); transition:transform .2s ease}
  .sub-item[data-open="true"] .sub-chevron{transform:rotate(180deg)}

  .score-chip{ display:flex; flex-direction:column; align-items:center; justify-content:center; width:46px; height:46px;
    border-radius:14px; flex:none; font-family:var(--font-display); font-weight:800; }
  .score-chip .num{font-size:16px; line-height:1}
  .score-chip .den{font-size:8px; opacity:.75; margin-top:2px; font-weight:700}
  body[data-theme="elegant"] .score-chip{border-radius:6px}
  .score-chip.good{background:var(--good-soft); color:var(--good)}
  .score-chip.warn{background:var(--warn-soft); color:var(--warn)}
  .score-chip.bad{background:var(--bad-soft); color:var(--bad)}

  .sub-body{ padding:0 15px 15px; display:none }
  .sub-item[data-open="true"] .sub-body{ display:block }
  .sub-detail{margin-bottom:14px}
  .sub-detail:last-child{margin-bottom:0}
  .sub-detail-label{font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-soft);
    margin-bottom:7px; display:flex; align-items:center; gap:6px}
  .sub-detail-label svg{width:13px;height:13px}
  .sub-detail-text{font-size:13px; line-height:1.65; white-space:pre-wrap; color:var(--ink)}
  .sub-detail-empty{font-size:12px; color:var(--ink-soft); font-style:italic}

  .doc-row{ display:flex; align-items:center; gap:10px; padding:9px 10px; background:var(--surface-alt);
    border:1px solid var(--border); border-radius:var(--radius-sm); margin-bottom:8px; }
  .doc-icon{ width:32px;height:32px;border-radius:8px; flex:none; display:flex;align-items:center;justify-content:center;
    font-size:9.5px; font-weight:800; color:#fff; font-family:var(--font-mono); }
  .doc-meta{flex:1; min-width:0}
  .doc-name{font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap}
  .doc-size{font-size:11px; color:var(--ink-soft)}
  .doc-dl{ flex:none; width:30px;height:30px;border-radius:var(--radius-sm); background:var(--surface); border:1px solid var(--border);
    display:flex;align-items:center;justify-content:center; color:var(--ink-soft) }
  .doc-dl svg{width:14px;height:14px}
  .doc-dl:hover{color:var(--primary); border-color:var(--primary)}

  .sub-images{display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:8px}
  .sub-images a{ border-radius:var(--radius-sm); overflow:hidden; border:1px solid var(--border); background:var(--surface-alt); display:block }
  body[data-theme="elegant"] .sub-images a{border-radius:6px}
  .sub-images img{width:100%;height:92px;object-fit:cover}

  .sub-feedback{ background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px 14px }
  .sub-feedback-title{font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:var(--primary); margin-bottom:4px}
  .sub-feedback-text{font-size:13px; line-height:1.6; white-space:pre-wrap}
  .sub-feedback-meta{font-size:11px; color:var(--ink-soft); margin-top:6px}

  .sub-body-actions{ display:flex; justify-content:flex-end; margin-top:4px }

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
    <div class="brand-mark">NB</div>
    <div class="brand-text">
      <div class="brand-title">Lịch sử nộp bài</div>
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
    <div class="hero-eyebrow">Lịch sử</div>
    <div class="hero-title-row">
      <h1 class="hero-title">Các bài đã nộp</h1>
      <a href="assignments.php" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Danh sách bài tập
      </a>
    </div>
    <p class="hero-sub">Xem lại nội dung bài làm, tệp đính kèm và nhận xét, điểm số từ giáo viên</p>
  </section>

  <!-- ===== STATS ===== -->
  <section class="stats-grid" id="stats-grid" hidden>
    <div class="stat-card">
      <div class="stat-icon primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9l8-5 8 5v10"/><path d="M9 19v-5h6v5"/></svg></div>
      <div><div class="stat-num" id="stat-total">0</div><div class="stat-label">Bài đã nộp</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
      <div><div class="stat-num" id="stat-graded">0</div><div class="stat-label">Đã chấm điểm</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg></div>
      <div><div class="stat-num" id="stat-pending">0</div><div class="stat-label">Chưa chấm</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="3.2"/></svg></div>
      <div><div class="stat-num" id="stat-avg">–</div><div class="stat-label">Điểm TB (thang 10)</div></div>
    </div>
  </section>

  <!-- ===== LIST ===== -->
  <section class="panel-card" id="list-card">
    <div class="list-head">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
        Danh sách bài đã nộp
      </div>
    </div>
    <div id="subs-list">
      <div class="load-state">
        <div class="spinner"></div>
        <p style="font-size:12.5px;color:var(--ink-soft)">Đang tải lịch sử nộp bài…</p>
      </div>
    </div>
  </section>

</div>

<script>
(function(){
  "use strict";

  var SUBJECTS = <?php echo json_encode($subjects, JSON_UNESCAPED_UNICODE); ?>;

  var ICONS = {
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>',
    clip: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    file: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4"/></svg>',
    images: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
    chat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.3-.7L3 21l1.8-5.4A8.4 8.4 0 1 1 21 11.5Z"/></svg>',
    download: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M8 11l4 4 4-4"/><path d="M4 17v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>',
    chevron: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>',
    box: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>'
  };

  var FILE_TYPE_META = {
    doc:{label:'DOC',color:'#2A56C6'}, docx:{label:'DOC',color:'#2A56C6'},
    xls:{label:'XLS',color:'#0E8F5B'}, xlsx:{label:'XLS',color:'#0E8F5B'},
    ppt:{label:'PPT',color:'#D97706'}, pptx:{label:'PPT',color:'#D97706'},
    pdf:{label:'PDF',color:'#D14343'},
    jpg:{label:'IMG',color:'#8B7CF6'}, jpeg:{label:'IMG',color:'#8B7CF6'}, png:{label:'IMG',color:'#8B7CF6'}
  };

  var allSubs = [];

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
    else { window.location.href = 'assignments.php'; }
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
  function fmtDateTime(s){
    var d = parseDate(s);
    if(isNaN(d.getTime())) return '—';
    return pad(d.getHours())+':'+pad(d.getMinutes())+' · '+pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear();
  }
  function subjectName(id){
    return SUBJECTS[String(id)] || SUBJECTS[id] || (id !== undefined && id !== null ? String(id) : 'Môn học');
  }
  function formatSize(bytes){
    var b = Number(bytes) || 0;
    if(b < 1024) return b + ' B';
    if(b < 1024 * 1024) return (b / 1024).toFixed(0) + ' KB';
    return (b / 1024 / 1024).toFixed(1) + ' MB';
  }
  function fileTypeMeta(nameOrExt){
    var ext = nameOrExt.indexOf('.') > -1 ? nameOrExt.split('.').pop().toLowerCase() : nameOrExt.toLowerCase();
    return FILE_TYPE_META[ext] || { label:'FILE', color:'#8A6F72' };
  }
  function scoreClass(score, max){
    var pct = max > 0 ? (score / max) * 100 : 0;
    if(pct >= 80) return 'good';
    if(pct >= 50) return 'warn';
    return 'bad';
  }
  function dlUrl(path){
    return 'api/download_file.php?file=' + encodeURIComponent(path);
  }

  // ============ STATS ============
  function renderStats(){
    var total = allSubs.length;
    if(total === 0) return;
    var graded = 0, sum = 0;
    allSubs.forEach(function(s){
      if(s.score !== null && s.score !== undefined){
        graded++;
        var max = Number(s.max_score) || 10;
        sum += (Number(s.score) / max) * 10;
      }
    });
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-graded').textContent = graded;
    document.getElementById('stat-pending').textContent = total - graded;
    document.getElementById('stat-avg').textContent = graded > 0 ? (sum / graded).toFixed(1) : '–';
    document.getElementById('stats-grid').hidden = false;
  }

  // ============ LIST ============
  function renderDocs(docs){
    if(!docs || docs.length === 0){
      return '<p class="sub-detail-empty">Không có tài liệu đính kèm.</p>';
    }
    return docs.map(function(d){
      var ext = d.extension || String(d.path || '').split('.').pop() || 'file';
      var name = d.filename || String(d.path || '').split('/').pop() || 'Tệp';
      var meta = fileTypeMeta(ext);
      return '<div class="doc-row">' +
        '<div class="doc-icon" style="background:' + meta.color + '">' + meta.label + '</div>' +
        '<div class="doc-meta"><div class="doc-name">' + esc(name) + '</div><div class="doc-size">' + formatSize(d.size) + '</div></div>' +
        '<a class="doc-dl" href="' + dlUrl(d.path) + '" target="_blank" rel="noopener" aria-label="Tải xuống">' + ICONS.download + '</a>' +
      '</div>';
    }).join('');
  }

  function renderImages(images){
    if(!images || images.length === 0){
      return '<p class="sub-detail-empty">Không có hình ảnh đính kèm.</p>';
    }
    return '<div class="sub-images">' + images.map(function(img){
      return '<a href="' + dlUrl(img) + '" target="_blank" rel="noopener">' +
        '<img src="' + dlUrl(img) + '" alt="Hình ảnh bài làm" loading="lazy">' +
      '</a>';
    }).join('') + '</div>';
  }

  function renderItem(s){
    var graded = s.score !== null && s.score !== undefined;
    var max = Number(s.max_score) || 10;
    var scoreHtml = graded
      ? '<div class="score-chip ' + scoreClass(s.score, max) + '"><span class="num">' + s.score + '</span><span class="den">/ ' + max + '</span></div>'
      : '<span class="pill pill-pending">Chưa chấm</span>';

    var groupLine = '';
    if(Array.isArray(s.group_members) && s.group_members.length > 0){
      groupLine = '<span style="display:inline-flex;align-items:center;gap:4px">' + ICONS.users + ' Nhóm: ' +
        s.group_members.map(function(m){ return esc(m); }).join(', ') + '</span>';
    }

    var feedbackHtml = '';
    if(s.feedback){
      var gradedBy = s.graded_by ? ' · bởi ' + esc(s.graded_by) : '';
      feedbackHtml =
        '<div class="sub-detail">' +
          '<div class="sub-detail-label">' + ICONS.chat + ' Nhận xét của giáo viên</div>' +
          '<div class="sub-feedback">' +
            '<div class="sub-feedback-title">Điểm: ' + s.score + ' / ' + max + '</div>' +
            '<div class="sub-feedback-text">' + esc(s.feedback) + '</div>' +
            '<div class="sub-feedback-meta">Chấm lúc ' + fmtDateTime(s.graded_at) + gradedBy + '</div>' +
          '</div>' +
        '</div>';
    }

    return '<div class="sub-item" data-open="false">' +
      '<button type="button" class="sub-head" aria-expanded="false">' +
        '<div class="sub-title-block">' +
          '<div class="sub-title">' + esc(s.title || 'Bài tập') + '</div>' +
          '<div class="sub-meta">' +
            '<span>' + esc(subjectName(s.subject_id)) + '</span>' +
            '<span style="display:inline-flex;align-items:center;gap:4px">' + ICONS.clock + ' Nộp lúc ' + fmtDateTime(s.submitted_at) + '</span>' +
            groupLine +
          '</div>' +
        '</div>' +
        '<div class="sub-right">' + scoreHtml + '<span class="sub-chevron">' + ICONS.chevron + '</span></div>' +
      '</button>' +
      '<div class="sub-body">' +
        '<div class="sub-detail">' +
          '<div class="sub-detail-label">' + ICONS.clip + ' Yêu cầu bài tập</div>' +
          '<div class="sub-detail-text">' + (s.description ? esc(s.description) : 'Không có mô tả.') + '</div>' +
        '</div>' +
        '<div class="sub-detail">' +
          '<div class="sub-detail-label">' + ICONS.file + ' Nội dung bài làm</div>' +
          '<div class="sub-detail-text">' + (s.content ? esc(s.content) : 'Không có ghi chú kèm theo.') + '</div>' +
        '</div>' +
        '<div class="sub-detail">' +
          '<div class="sub-detail-label">' + ICONS.file + ' Tài liệu đính kèm</div>' +
          renderDocs(s.documents) +
        '</div>' +
        '<div class="sub-detail">' +
          '<div class="sub-detail-label">' + ICONS.images + ' Hình ảnh đính kèm</div>' +
          renderImages(s.images) +
        '</div>' +
        feedbackHtml +
        '<div class="sub-body-actions">' +
          '<a href="submit_assignment.php?id=' + encodeURIComponent(s.assignment_id) + '" class="btn btn-ghost btn-sm">' + ICONS.file + ' Mở bài tập</a>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function renderList(){
    var wrap = document.getElementById('subs-list');

    if(allSubs.length === 0){
      wrap.innerHTML =
        '<div class="empty-state">' +
          '<div class="empty-icon">' + ICONS.box + '</div>' +
          '<div class="empty-title">Chưa có bài nộp nào</div>' +
          '<p class="empty-text">Khi em nộp bài tập, lịch sử và kết quả chấm điểm sẽ xuất hiện tại đây.</p>' +
        '</div>';
      return;
    }

    wrap.innerHTML = allSubs.map(renderItem).join('');

    wrap.querySelectorAll('.sub-head').forEach(function(btn){
      btn.addEventListener('click', function(){
        var item = btn.closest('.sub-item');
        var open = item.dataset.open === 'true';
        item.dataset.open = open ? 'false' : 'true';
        btn.setAttribute('aria-expanded', String(!open));
      });
    });
  }

  // ============ LOAD ============
  function loadSubs(){
    fetch('api/get_my_submissions.php')
      .then(function(resp){ return resp.json(); })
      .then(function(data){
        if(data.success){
          allSubs = Array.isArray(data.submissions) ? data.submissions : [];
        } else {
          allSubs = [];
        }
        renderStats();
        renderList();
      })
      .catch(function(err){
        console.error('Error loading submissions:', err);
        document.getElementById('subs-list').innerHTML =
          '<div class="empty-state">' +
            '<div class="empty-icon">' + ICONS.box + '</div>' +
            '<div class="empty-title">Lỗi tải lịch sử nộp bài</div>' +
            '<p class="empty-text">Không thể kết nối tới máy chủ. Vui lòng thử lại sau.</p>' +
          '</div>';
      });
  }

  // ============ INIT ============
  loadSubs();

})();
</script>
</body>
</html>
