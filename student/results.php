<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';

$title = 'Kết Quả Bài Thi - EduVN';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Kết quả bài thi — EduVN</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<script>
    window.MathJax = {
        tex: {
            inlineMath: [['$', '$'], ['\\(', '\\)'],],
            displayMath: [['$$', '$$'], ['\\[', '\\]']],
            processEscapes: true,
            packages: {'[+]': ['mhchem']}
        },
        loader: {
            load: ['[tex]/mhchem']
        },
        startup: {
            typeset: false
        }
    };
</script>
<script id="MathJax-script" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>
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

  .chip{flex:none; padding:8px 14px; border-radius:var(--radius-pill); font-size:12.5px; font-weight:700;
    background:var(--surface-alt); color:var(--ink-soft); border:1px solid var(--border)}
  .chip[aria-pressed="true"]{background:var(--primary); color:var(--primary-ink); border-color:transparent}

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
    background:repeating-linear-gradient(115deg, var(--tape-1) 0 6px, #ffe3a3 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
  body[data-theme="elegant"] .hero-list::before, body[data-theme="elegant"] .hero-list::after{
    content:""; position:absolute; width:16px; height:16px; border-radius:50%; background:var(--bg); top:50%; transform:translateY(-50%); }
  body[data-theme="elegant"] .hero-list::before{left:-8px} body[data-theme="elegant"] .hero-list::after{right:-8px}

  .hero-eyebrow{ font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); margin-bottom:6px }
  .hero-title-row{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:6px}
  .hero-title{font-family:var(--font-display); font-weight:700; font-size:21px; line-height:1.25}
  .hero-meta-chip{ font-size:11px; font-weight:700; color:var(--ink-soft); background:var(--surface-alt);
    border:1px solid var(--border); padding:6px 12px; border-radius:var(--radius-pill); margin-bottom:10px }
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
  .chips{display:flex; gap:8px; overflow-x:auto; padding-bottom:2px}
  .chips::-webkit-scrollbar{display:none}

  .result-item{ background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card);
    box-shadow:var(--shadow-sm); padding:15px; margin-bottom:11px; }
  .result-top{display:flex; align-items:flex-start; justify-content:space-between; gap:10px}
  .result-title{font-family:var(--font-display); font-weight:600; font-size:15px; line-height:1.35; color:var(--ink)}
  .result-sub{font-size:11.5px; color:var(--ink-soft); margin:6px 0 13px}
  .result-foot{display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    border-top:1px solid var(--border); padding-top:12px}
  .result-left{display:flex; align-items:center; gap:12px; flex-wrap:wrap}

  .score-chip{ display:flex; flex-direction:column; align-items:center; justify-content:center; width:54px; height:54px;
    border-radius:16px; flex:none; font-family:var(--font-display); font-weight:800; }
  .score-chip .num{font-size:18px; line-height:1}
  .score-chip .den{font-size:8.5px; opacity:.75; margin-top:2px; font-weight:700}
  body[data-theme="elegant"] .score-chip{border-radius:6px}
  .score-chip.good{background:var(--good-soft); color:var(--good)}
  .score-chip.warn{background:var(--warn-soft); color:var(--warn)}
  .score-chip.bad{background:var(--bad-soft); color:var(--bad)}

  .result-meta-tag{font-size:11.5px; color:var(--ink-soft); display:inline-flex; align-items:center; gap:6px}
  .result-meta-tag svg{width:13px;height:13px}
  .result-meta-tag strong{color:var(--ink); font-weight:700}
  .result-graded-note{font-size:11.5px; color:var(--ink-soft); font-style:italic}

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
    <div class="brand-mark">KQ</div>
    <div class="brand-text">
      <div class="brand-title">Kết quả bài thi</div>
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
    <div class="hero-eyebrow">Kết quả bài thi</div>
    <div class="hero-title-row">
      <h1 class="hero-title">Bảng điểm của em</h1>
      <span class="hero-meta-chip" id="hero-chip">…</span>
    </div>
    <p class="hero-sub">Tổng hợp kết quả các bài thi trắc nghiệm của em · Cập nhật <span id="hero-date">hôm nay</span></p>
  </section>

  <!-- ===== STATS ===== -->
  <section class="stats-grid" id="stats-grid" hidden>
    <div class="stat-card">
      <div class="stat-icon primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4"/></svg></div>
      <div><div class="stat-num" id="stat-total">0</div><div class="stat-label">Tổng bài thi</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="3.2"/></svg></div>
      <div><div class="stat-num" id="stat-avg">–</div><div class="stat-label">Điểm trung bình</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><path d="M6 19h12"/><path d="M6 5h12v4l-4 3h-4L6 9V5Z"/><path d="M12 12v7"/></svg></div>
      <div><div class="stat-num" id="stat-high">–</div><div class="stat-label">Điểm cao nhất</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.9 6.3 6.9.7-5.2 4.7 1.6 6.8L12 17l-6.2 3.5 1.6-6.8-5.2-4.7 6.9-.7L12 2Z"/><path d="M9.5 9.5l5 5M14.5 9.5l-5 5"/></svg></div>
      <div><div class="stat-num" id="stat-pass">–</div><div class="stat-label">Tỷ lệ đạt (≥ 5)</div></div>
    </div>
  </section>

  <!-- ===== LIST ===== -->
  <section class="panel-card" id="list-card">
    <div class="list-head">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
        Lịch sử các bài thi
      </div>
      <div class="chips" id="filter-chips"></div>
    </div>
    <div id="results-list">
      <div class="load-state">
        <div class="spinner"></div>
        <p style="font-size:12.5px;color:var(--ink-soft)">Đang tải kết quả…</p>
      </div>
    </div>
  </section>

</div>

<script>
(function(){
  "use strict";

  var STUDENT = <?php echo json_encode([
    'code' => $studentCode,
    'name' => $studentName,
    'class' => $studentClass
  ], JSON_UNESCAPED_UNICODE); ?>;

  var ICONS = {
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>',
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c1.4-3.2 4-5 6.5-5s5.1 1.8 6.5 5"/></svg>',
    layers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="M3 13l9 5 9-5"/></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    box: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>'
  };

  var allResults = [];
  var currentFilter = 'all';

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
  function fmtDate(ts){
    var d = new Date(ts);
    if(isNaN(d.getTime())) return '—';
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear();
  }
  function typeset(){
    setTimeout(function(){
      if(window.MathJax && MathJax.typesetPromise){
        MathJax.typesetPromise().catch(function(){});
      }
    }, 60);
  }
  function gradeInfo(score){
    if(score >= 8.5) return { cls:'good', label:'Giỏi' };
    if(score >= 7.0) return { cls:'good', label:'Khá' };
    if(score >= 5.0) return { cls:'warn', label:'Trung bình' };
    return { cls:'bad', label:'Cần cố gắng' };
  }
  function scoreClass(score){
    if(score >= 7.0) return 'good';
    if(score >= 5.0) return 'warn';
    return 'bad';
  }

  // ============ HERO ============
  function renderHero(){
    document.getElementById('hero-chip').textContent =
      'Mã HS: ' + STUDENT.code + ' · Lớp ' + STUDENT.class;
    var d = new Date();
    document.getElementById('hero-date').textContent =
      pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear();
  }

  // ============ STATS ============
  function renderStats(){
    var total = allResults.length;
    if(total === 0){ return; }

    var totalScore = 0, highest = 0, passed = 0, scored = 0;
    allResults.forEach(function(r){
      if(r.score !== null && r.score !== undefined){
        scored++;
        totalScore += Number(r.score);
        if(Number(r.score) > highest) highest = Number(r.score);
        if(Number(r.score) >= 5) passed++;
      }
    });

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-avg').textContent = scored > 0 ? (totalScore / scored).toFixed(1) : '–';
    document.getElementById('stat-high').textContent = scored > 0 ? String(Number(highest.toFixed(1))) : '–';
    document.getElementById('stat-pass').textContent = Math.round((passed / total) * 100) + '%';
    document.getElementById('stats-grid').hidden = false;
  }

  // ============ FILTER CHIPS ============
  function renderChips(){
    var wrap = document.getElementById('filter-chips');
    var filters = [
      { key:'all', label:'Tất cả' },
      { key:'official', label:'Thi chính thức' },
      { key:'practice', label:'Luyện tập' }
    ];
    wrap.innerHTML = filters.map(function(f){
      return '<button type="button" class="chip" data-filter="' + f.key + '" aria-pressed="' + (currentFilter === f.key) + '">' + f.label + '</button>';
    }).join('');
    wrap.querySelectorAll('[data-filter]').forEach(function(btn){
      btn.addEventListener('click', function(){
        currentFilter = btn.dataset.filter;
        renderChips();
        renderList();
      });
    });
  }

  function matchesFilter(r){
    if(currentFilter === 'all') return true;
    var practice = !!r.is_practice;
    if(currentFilter === 'practice') return practice;
    return !practice;
  }

  // ============ LIST ============
  function renderList(){
    var wrap = document.getElementById('results-list');
    var items = allResults.filter(matchesFilter);

    if(items.length === 0){
      wrap.innerHTML =
        '<div class="empty-state">' +
          '<div class="empty-icon">' + ICONS.box + '</div>' +
          '<div class="empty-title">' + (allResults.length === 0 ? 'Chưa có kết quả thi nào' : 'Không có bài thi nào') + '</div>' +
          '<p class="empty-text">' + (allResults.length === 0 ? 'Khi em hoàn thành một bài thi trắc nghiệm, kết quả sẽ xuất hiện tại đây.' : 'Hãy chọn bộ lọc khác để xem tất cả bài thi của em.') + '</p>' +
        '</div>';
      return;
    }

    wrap.innerHTML = items.map(function(r){
      var score = (r.score === null || r.score === undefined) ? null : Number(r.score);
      var grade = score === null ? null : gradeInfo(score);
      var isPractice = !!r.is_practice;

      var scoreHtml;
      if(score === null){
        scoreHtml = '<span class="result-graded-note">Chưa hoàn thành</span>';
      } else {
        var den = r.max_score || 10;
        scoreHtml =
          '<div class="score-chip ' + scoreClass(score) + '">' +
            '<span class="num">' + score + '</span>' +
            '<span class="den">/ ' + den + '</span>' +
          '</div>' +
          '<span class="pill pill-graded">' + grade.label + '</span>';
      }

      var viewBtn = (score === null || !r.completed)
        ? ''
        : '<button type="button" class="btn btn-ghost btn-sm" data-view-exam="' + esc(r.id) + '">' + ICONS.arrow + ' Xem chi tiết</button>';

      return '<div class="result-item">' +
        '<div class="result-top">' +
          '<div class="result-title">' + esc(r.test_name || 'Bài kiểm tra trắc nghiệm') + '</div>' +
          '<span class="type-badge">' + (isPractice ? 'Luyện tập' : 'Thi chính thức') + '</span>' +
        '</div>' +
        '<div class="result-sub">' +
          'Lớp ' + esc(r.class_code || '') + ' · Lần thi ' + (r.attempt || 1) + ' · Ngày ' + fmtDate(r.timestamp) +
        '</div>' +
        '<div class="result-foot">' +
          '<div class="result-left">' +
            scoreHtml +
            '<span class="result-meta-tag">' + ICONS.target + ' Đúng <strong>' + (r.correct_answers || 0) + '</strong>/' + (r.total_questions || 0) + ' câu</span>' +
          '</div>' +
          viewBtn +
        '</div>' +
      '</div>';
    }).join('');

    wrap.querySelectorAll('[data-view-exam]').forEach(function(btn){
      btn.addEventListener('click', function(){
        window.location.href = 'result.php?exam_id=' + encodeURIComponent(btn.dataset.viewExam);
      });
    });

    typeset();
  }

  // ============ LOAD ============
  function loadResults(){
    fetch('api/get_student_results.php')
      .then(function(resp){ return resp.json(); })
      .then(function(data){
        if(data.success){
          allResults = Array.isArray(data.results) ? data.results : [];
        } else {
          allResults = [];
        }
        renderStats();
        renderChips();
        renderList();
      })
      .catch(function(err){
        console.error('Error loading results:', err);
        document.getElementById('results-list').innerHTML =
          '<div class="empty-state">' +
            '<div class="empty-icon">' + ICONS.box + '</div>' +
            '<div class="empty-title">Lỗi tải kết quả</div>' +
            '<p class="empty-text">Không thể kết nối tới máy chủ. Vui lòng thử lại sau.</p>' +
          '</div>';
      });
  }

  // ============ INIT ============
  renderHero();
  loadResults();

})();
</script>
</body>
</html>
