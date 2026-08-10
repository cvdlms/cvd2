<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_gender.php';

$examId = $_GET['exam_id'] ?? '';
if (!$examId) {
    header('Location: results.php');
    exit;
}

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

  .modal-overlay{ position:fixed; inset:0; z-index:60; display:none; align-items:flex-end; justify-content:center;
    background:rgba(10,8,9,.5); backdrop-filter:blur(2px); }
  body[data-theme="elegant"] .modal-overlay{background:rgba(0,0,0,.7)}
  .modal-overlay.open{display:flex}
  @media (min-width:768px){ .modal-overlay{align-items:center; padding:24px} }
  .modal-sheet{ width:100%; max-width:520px; max-height:88vh; overflow-y:auto;
    background:var(--surface); border:var(--card-border-width) solid var(--border);
    border-radius: var(--radius-card) var(--radius-card) 0 0; box-shadow:var(--shadow);
    padding:20px 18px calc(20px + env(safe-area-inset-bottom)); }
  @media (min-width:768px){ .modal-sheet{border-radius:var(--radius-card)} }
  .modal-head{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px}
  .modal-title{font-family:var(--font-display); font-weight:700; font-size:17px}
  .modal-close{ width:32px;height:32px;border-radius:50%;flex:none; display:flex;align-items:center;justify-content:center;
    background:var(--surface-alt); color:var(--ink-soft); }
  .modal-close svg{width:16px;height:16px}
  .modal-label{ font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-soft); margin-bottom:8px; display:block }
  .modal-section{margin-bottom:16px}
  .modal-textarea{ width:100%; min-height:80px; padding:12px; border-radius:var(--radius-sm);
    border:1px solid var(--border); background:var(--surface-alt); color:var(--ink); font-family:var(--font-body); font-size:13.5px; resize:vertical; }
  .modal-textarea:focus-visible{outline:2px solid var(--focus); outline-offset:1px}
  .modal-actions{display:flex; gap:10px; margin-top:4px}
  .modal-actions .btn{flex:1}
  .check-row{ display:flex; align-items:center; gap:10px; padding:10px 4px; border-bottom:1px solid var(--border); font-size:13px }
  .check-row:last-child{border-bottom:none}
  .check-row input{width:16px;height:16px;accent-color:var(--primary)}

  .chip{flex:none; padding:8px 14px; border-radius:var(--radius-pill); font-size:12.5px; font-weight:700;
    background:var(--surface-alt); color:var(--ink-soft); border:1px solid var(--border)}
  .chip[aria-pressed="true"]{background:var(--primary); color:var(--primary-ink); border-color:transparent}

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
  .layout{ max-width:1180px; margin:0 auto; padding:18px 16px 48px; display:flex; flex-direction:column; gap:18px; }
  .cols{ display:flex; flex-direction:column; gap:18px; }
  @media (min-width:1024px){
    .layout{ padding:28px 32px 56px; gap:22px; }
    .cols{ display:grid; grid-template-columns: minmax(0,1fr) 320px; gap:24px; align-items:start; }
  }
  .col-main, .col-side{ display:flex; flex-direction:column; gap:18px; min-width:0 }
  @media (max-width:1023.98px){ .col-side{ order:-1 } }

  .panel-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:16px}
  .panel-title{font-family:var(--font-display); font-weight:700; font-size:13.5px; margin-bottom:12px; display:flex; align-items:center; gap:7px}
  .panel-title svg{width:15px;height:15px; color:var(--primary)}

  /* ============ HERO RESULT ============ */
  .hero-result{ position:relative; background:var(--surface); border:var(--card-border-width) solid var(--border);
    border-radius:var(--radius-card); box-shadow:var(--shadow); padding:24px 22px; overflow:hidden; }
  body[data-theme="cute"] .hero-result::before{
    content:""; position:absolute; top:-8px; left:26px; width:60px; height:20px;
    background:repeating-linear-gradient(115deg, var(--tape-1) 0 6px, #ffe3a3 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
  body[data-theme="elegant"] .hero-result::before, body[data-theme="elegant"] .hero-result::after{
    content:""; position:absolute; width:16px; height:16px; border-radius:50%; background:var(--bg); top:50%; transform:translateY(-50%); }
  body[data-theme="elegant"] .hero-result::before{left:-8px} body[data-theme="elegant"] .hero-result::after{right:-8px}

  .hero-eyebrow{ font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-soft); margin-bottom:6px }
  .hero-exam-title{font-family:var(--font-display); font-weight:700; font-size:19px; margin-bottom:3px}
  .hero-exam-sub{font-size:12.5px; color:var(--ink-soft); margin-bottom:20px}

  .hero-main{ display:flex; flex-direction:column; align-items:center; gap:16px; text-align:center; margin-bottom:20px }
  @media (min-width:560px){ .hero-main{flex-direction:row; text-align:left} }

  .score-ring{ width:150px;height:150px;border-radius:50%; flex:none; display:flex;align-items:center;justify-content:center;
    background:conic-gradient(var(--primary) calc(var(--pct,0) * 1%), var(--surface-alt) 0); }
  .score-ring-inner{ width:120px;height:120px;border-radius:50%; background:var(--surface);
    display:flex;align-items:center;justify-content:center;flex-direction:column; }
  .score-num{font-family:var(--font-display); font-weight:800; font-size:34px; line-height:1}
  .score-den{font-size:11px; color:var(--ink-soft); margin-top:2px}

  .grade-block{flex:1}
  .grade-badge{ display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:var(--radius-pill);
    font-weight:700; font-size:13px; margin-bottom:10px }
  .grade-badge svg{width:14px;height:14px}
  .grade-badge.good{background:var(--good-soft); color:var(--good)}
  .grade-badge.warn{background:var(--warn-soft); color:var(--warn)}
  .grade-badge.bad{background:var(--bad-soft); color:var(--bad)}
  .grade-desc{font-size:12.5px; color:var(--ink-soft); line-height:1.55}

  .hero-meta-row{ display:grid; grid-template-columns:repeat(2,1fr); gap:9px; padding-top:16px; border-top:1px solid var(--border) }
  @media (min-width:640px){ .hero-meta-row{grid-template-columns:repeat(4,1fr)} }
  .hero-meta-item{background:var(--surface-alt); border-radius:var(--radius-sm); padding:10px 12px}
  .hero-meta-label{font-size:10px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px}
  .hero-meta-value{font-weight:700; font-size:13px}

  /* loading / error */
  .load-state{ display:flex; flex-direction:column; align-items:center; gap:10px; padding:10px 0 4px; }
  .spinner{ width:34px;height:34px;border-radius:50%; border:3px solid var(--border); border-top-color:var(--primary);
    animation:spin .8s linear infinite; }
  @keyframes spin{to{transform:rotate(360deg)}}
  .load-err{ text-align:center; padding:22px 10px; }
  .load-err-icon{ width:52px;height:52px;border-radius:50%;margin:0 auto 12px; display:flex;align-items:center;justify-content:center;background:var(--bad-soft);color:var(--bad)}
  .load-err-icon svg{width:24px;height:24px}
  .load-err-title{font-family:var(--font-display);font-weight:700;font-size:16px;margin-bottom:4px}
  .load-err-text{font-size:12.5px;color:var(--ink-soft);margin-bottom:16px;line-height:1.5}

  /* ============ STATS ============ */
  .stats-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:10px}
  @media (min-width:1024px){ .stats-grid{grid-template-columns:1fr 1fr} }
  .stat-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:13px; display:flex; align-items:center; gap:10px}
  .stat-icon{width:34px;height:34px;border-radius:var(--radius-sm); flex:none; display:flex;align-items:center;justify-content:center}
  .stat-icon svg{width:16px;height:16px}
  .stat-icon.good{background:var(--good-soft); color:var(--good)}
  .stat-icon.bad{background:var(--bad-soft); color:var(--bad)}
  .stat-icon.warn{background:var(--warn-soft); color:var(--warn)}
  .stat-icon.neutral{background:var(--surface-alt); color:var(--ink-soft)}
  .stat-num{font-family:var(--font-display); font-weight:700; font-size:17px; line-height:1.1}
  .stat-label{font-size:10.5px; color:var(--ink-soft)}

  /* ============ INTEGRITY ============ */
  .integrity-card{ display:flex; align-items:flex-start; gap:12px; background:var(--surface);
    border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm); box-shadow:var(--shadow-sm); padding:15px; }
  .integrity-icon{width:38px;height:38px;border-radius:50%; flex:none; display:flex;align-items:center;justify-content:center; background:var(--good-soft); color:var(--good)}
  .integrity-icon svg{width:18px;height:18px}
  .integrity-title{font-weight:700; font-size:13px; margin-bottom:3px}
  .integrity-desc{font-size:12px; color:var(--ink-soft); line-height:1.5}

  /* ============ COMPARE ============ */
  .compare-row{margin-bottom:14px}
  .compare-row:last-child{margin-bottom:0}
  .compare-top{display:flex; align-items:baseline; justify-content:space-between; margin-bottom:6px}
  .compare-name{font-size:12px; font-weight:700; color:var(--ink-soft)}
  .compare-value{font-family:var(--font-mono); font-weight:700; font-size:13px}
  .compare-track{height:8px; border-radius:99px; background:var(--surface-alt); overflow:hidden}
  .compare-fill{height:100%; border-radius:99px}
  .compare-fill.self{background:var(--primary)}
  .compare-fill.avg{background:var(--secondary)}
  .compare-fill.max{background:var(--accent)}
  .rank-note{ margin-top:14px; padding-top:14px; border-top:1px solid var(--border); font-size:12.5px; color:var(--ink-soft); text-align:center }
  .rank-note strong{color:var(--ink); font-weight:700}

  /* ============ TEACHER FEEDBACK ============ */
  .feedback-card{display:flex; gap:12px}
  .feedback-avatar{width:38px;height:38px;border-radius:50%;flex:none; background:var(--secondary); color:#fff;
    display:flex;align-items:center;justify-content:center; font-family:var(--font-display); font-weight:700; font-size:14px}
  body[data-theme="elegant"] .feedback-avatar{background:linear-gradient(155deg,var(--secondary),#33445A); border:1px solid var(--border)}
  .feedback-name{font-size:12.5px; font-weight:700}
  .feedback-role{font-size:10.5px; color:var(--ink-soft); margin-bottom:8px}
  .feedback-quote{font-size:12.5px; line-height:1.6; color:var(--ink); font-style:italic; white-space:pre-wrap}

  /* ============ REVIEW (accordion) ============ */
  .review-head{display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; flex-wrap:wrap}
  .review-title{font-family:var(--font-display); font-weight:700; font-size:16.5px}
  .expand-all-btn{font-size:12px; font-weight:700; color:var(--primary)}
  .chips{display:flex; gap:8px; overflow-x:auto; margin-bottom:14px; padding-bottom:2px}
  .chips::-webkit-scrollbar{display:none}

  .review-item{ background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); margin-bottom:10px; overflow:hidden }
  .review-item-header{ width:100%; display:flex; align-items:center; gap:12px; padding:14px 15px; text-align:left }
  .review-status-icon{width:28px;height:28px;border-radius:50%; flex:none; display:flex;align-items:center;justify-content:center}
  .review-status-icon svg{width:14px;height:14px}
  .review-status-icon.correct{background:var(--good-soft); color:var(--good)}
  .review-status-icon.incorrect{background:var(--bad-soft); color:var(--bad)}
  .review-status-icon.blank{background:var(--surface-alt); color:var(--ink-soft)}
  .review-item-title{flex:1; min-width:0}
  .review-item-num{font-size:11px; font-weight:700; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.03em}
  .review-item-text{font-size:13.5px; font-weight:600; line-height:1.4; margin-top:2px;
    display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden}
  .review-item[data-open="true"] .review-item-text{-webkit-line-clamp:unset}
  .review-chevron{width:18px;height:18px; flex:none; color:var(--ink-soft); transition:transform .2s ease}
  .review-item[data-open="true"] .review-chevron{transform:rotate(180deg)}
  .review-body{ padding:0 15px 16px; display:none }
  .review-item[data-open="true"] .review-body{ display:block }
  .review-topic-tag{ display:inline-block; font-size:10.5px; font-weight:700; color:var(--secondary); background:var(--surface-alt);
    padding:3px 9px; border-radius:var(--radius-pill); margin-bottom:10px }
  .review-blank-note{font-size:12px; color:var(--ink-soft); margin-top:6px; font-style:italic}

  .review-answer-line{ display:flex; align-items:center; gap:10px; padding:10px 13px; border-radius:var(--radius-sm);
    margin-bottom:8px; font-size:13px; line-height:1.4 }
  .review-answer-line .lbl{ font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
  .answer-letter{ width:26px;height:26px;border-radius:50%;flex:none; display:flex;align-items:center;justify-content:center;
    font-family:var(--font-display); font-weight:800; font-size:11.5px; background:var(--surface); border:1.5px solid currentColor; }
  .review-answer-line.good{background:var(--good-soft); color:var(--good)}
  .review-answer-line.bad{background:var(--bad-soft); color:var(--bad)}
  .review-answer-line.neutral{background:var(--surface-alt); color:var(--ink-soft)}
  .review-explanation{ font-size:12.5px; color:var(--ink-soft); line-height:1.6; padding:10px 13px; margin-top:4px;
    background:var(--surface-alt); border-left:3px solid var(--secondary); border-radius:var(--radius-sm) }
  .review-lock{ display:flex; align-items:center; gap:10px; padding:12px 13px; border-radius:var(--radius-sm);
    background:var(--warn-soft); color:var(--warn); font-size:12.5px; line-height:1.5; margin-top:4px }
  .review-lock svg{width:16px;height:16px;flex:none}
  .review-lock a{font-weight:700; color:var(--primary); text-decoration:underline}

  /* ============ ACTIONS ============ */
  .actions-list{display:flex; flex-direction:column; gap:10px}
  .actions-list .btn{width:100%}

  [hidden]{display:none !important}

  @media (prefers-reduced-motion: reduce){ *{transition-duration:.001ms !important; animation-duration:.001ms !important} }

  /* ============ PRINT ============ */
  @media print{
    .topbar, .sec-actions, .modal-overlay, .expand-all-btn, .chips{display:none !important}
    body{background:#fff !important}
    .layout{display:block !important; padding:0 !important}
    .cols{display:block !important}
    .sec-review .review-body{display:block !important}
    .panel-card, .hero-result, .review-item{box-shadow:none !important; border:1px solid #ddd !important}
  }
</style>
</head>
<body data-theme="<?php echo htmlspecialchars($stdDesignTheme); ?>">

<header class="topbar">
  <button type="button" class="back-btn" id="back-btn" aria-label="Quay lại">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
  </button>
  <div class="brand">
    <div class="brand-mark">TT</div>
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
  <section class="sec-hero">
    <div class="hero-result">
      <div class="hero-eyebrow">Kết quả bài thi</div>
      <div class="hero-exam-title" id="hero-exam-title">Đang tải kết quả...</div>
      <div class="hero-exam-sub" id="hero-exam-sub"></div>

      <div class="hero-main">
        <div class="score-ring" id="score-ring" style="--pct:0">
          <div class="score-ring-inner">
            <span class="score-num" id="score-num">–</span>
            <span class="score-den" id="score-den">/ 10 điểm</span>
          </div>
        </div>
        <div class="grade-block">
          <div class="grade-badge good" id="grade-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.9 6.3 6.9.7-5.2 4.7 1.6 6.8L12 17l-6.2 3.5 1.6-6.8-5.2-4.7 6.9-.7L12 2Z"/></svg>
            <span id="grade-label">…</span>
          </div>
          <p class="grade-desc" id="grade-desc">Đang tải thông tin bài làm…</p>
        </div>
      </div>

      <div class="hero-meta-row" id="hero-meta-row">
        <div class="hero-meta-item"><div class="hero-meta-label">Ngày thi</div><div class="hero-meta-value" id="meta-date">–</div></div>
        <div class="hero-meta-item"><div class="hero-meta-label">Lần thi</div><div class="hero-meta-value" id="meta-attempt">–</div></div>
        <div class="hero-meta-item"><div class="hero-meta-label">Số câu hỏi</div><div class="hero-meta-value" id="meta-total">–</div></div>
        <div class="hero-meta-item"><div class="hero-meta-label">Câu trả lời đúng</div><div class="hero-meta-value" id="meta-correct">–</div></div>
      </div>
    </div>
  </section>

  <div class="cols">

    <!-- ===== MAIN COLUMN ===== -->
    <div class="col-main">

      <!-- ===== REVIEW ===== -->
      <section class="sec-review" id="sec-review" hidden>
        <div class="review-head">
          <span class="review-title">Xem lại chi tiết bài làm</span>
          <button type="button" class="expand-all-btn" id="expand-all-btn">Mở rộng tất cả</button>
        </div>
        <div class="chips" id="review-filter-chips"></div>
        <div id="review-list"></div>
      </section>

    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="col-side">

      <!-- ===== STATS ===== -->
      <section class="sec-stats" id="sec-stats" hidden>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon good"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
        <div><div class="stat-num" id="stat-correct">0</div><div class="stat-label">Câu đúng</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bad"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg></div>
        <div><div class="stat-num" id="stat-wrong">0</div><div class="stat-label">Câu sai</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon neutral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg></div>
        <div><div class="stat-num" id="stat-blank">0</div><div class="stat-label">Bỏ trống</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4"/></svg></div>
        <div><div class="stat-num" id="stat-attempt">0</div><div class="stat-label">Lần thi</div></div>
      </div>
    </div>
  </section>

  <!-- ===== INTEGRITY ===== -->
  <section class="sec-integrity" id="sec-integrity" hidden>
    <div class="integrity-card">
      <div class="integrity-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/></svg>
      </div>
      <div>
        <div class="integrity-title">Tính toàn vẹn bài thi</div>
        <div class="integrity-desc">Hệ thống không ghi nhận hoạt động bất thường nào trong quá trình em làm bài.</div>
      </div>
    </div>
  </section>

  <!-- ===== COMPARE ===== -->
  <section class="sec-compare" id="sec-compare" hidden>
    <div class="panel-card">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>
        So sánh với lớp
      </div>
      <div id="compare-list"></div>
      <div class="rank-note" id="rank-note"></div>
    </div>
  </section>

  <!-- ===== SUPERVISION NOTES ===== -->
  <section class="sec-feedback" id="sec-feedback" hidden>
    <div class="panel-card">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
        Ghi chú giám sát bài thi
      </div>
      <div class="feedback-card">
        <div class="feedback-avatar">GS</div>
        <div>
          <div class="feedback-name" id="feedback-name">Hệ thống giám sát</div>
          <div class="feedback-role">Các vi phạm được ghi nhận trong lúc làm bài</div>
          <p class="feedback-quote" id="feedback-quote"></p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== ACTIONS ===== -->
  <section class="sec-actions" id="sec-actions" hidden>
    <div class="panel-card actions-list">
      <button type="button" class="btn btn-primary" id="open-appeal-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v18"/><path d="M6 4h11l-2.5 4L17 12H6"/></svg>
        Nộp đơn phúc khảo
      </button>
      <button type="button" class="btn btn-ghost" id="print-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="8" rx="1.5"/></svg>
        In / Lưu kết quả (PDF)
      </button>
      <button type="button" class="btn btn-ghost" id="back-btn-2">Quay lại danh sách bài thi</button>
    </div>
  </section>

    </div>
  </div>

</div>

<!-- ===== APPEAL MODAL ===== -->
<div class="modal-overlay" id="appeal-modal" role="dialog" aria-modal="true">
  <div class="modal-sheet" id="appeal-modal-body"></div>
</div>

<script>
(function(){
  "use strict";

  var EXAM_ID = <?php echo json_encode($examId); ?>;

  var ICONS = {
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    xmark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    dash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>',
    chevron: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>'
  };
  var LETTERS = ['A','B','C','D','E','F'];

  var examResult = null;
  var expandAll = false;
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
  // Navigate directly instead of history.back(): going back lands on
  // exam.php which auto-redirects to this page for completed exams,
  // so back could otherwise take several clicks to escape.
  document.getElementById('back-btn').addEventListener('click', function(){
    window.location.href = 'dashboard.php';
  });
  document.getElementById('back-btn-2').addEventListener('click', function(){
    window.location.href = 'results.php';
  });

  // ============ HELPERS ============
  function pad(n){ return String(n).padStart(2,'0'); }
  function fmtDate(ts){
    var d = new Date(ts);
    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear();
  }
  function answerText(ans){
    if(ans === null || ans === undefined) return '';
    var arr = Array.isArray(ans) ? ans : [ans];
    return arr.map(function(i){ return LETTERS[i]; }).join(', ');
  }
  function questionStatus(q){
    if(q.user_answer === null || q.user_answer === undefined) return 'blank';
    return q.is_correct ? 'correct' : 'incorrect';
  }
  function esc(s){
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }
  function typeset(){
    setTimeout(function(){
      if(window.MathJax && MathJax.typesetPromise){
        MathJax.typesetPromise().catch(function(){});
      }
    }, 60);
  }

  // ============ MODAL HELPERS ============
  var lastFocused = null;
  function openModal(id){
    lastFocused = document.activeElement;
    var overlay = document.getElementById(id);
    overlay.classList.add('open');
    var closeBtn = overlay.querySelector('.modal-close');
    if(closeBtn) closeBtn.focus();
  }
  function closeModal(id){
    document.getElementById(id).classList.remove('open');
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
    overlay.addEventListener('click', function(ev){ if(ev.target === overlay) closeModal(overlay.id); });
  });
  document.addEventListener('keydown', function(ev){
    if(ev.key === 'Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(function(o){ closeModal(o.id); }); }
  });

  // ============ LOAD ============
  function renderError(title, text){
    document.getElementById('hero-exam-title').textContent = 'Không có kết quả bài thi';
    document.getElementById('hero-exam-sub').textContent = '';
    document.getElementById('grade-desc').textContent = '';
    var ring = document.getElementById('score-ring');
    ring.style.setProperty('--pct','0');
    document.getElementById('score-num').textContent = '–';
    document.getElementById('grade-badge').hidden = true;
    document.getElementById('hero-meta-row').innerHTML =
      '<div class="hero-meta-item" style="grid-column:1/-1">' +
        '<div class="load-err">' +
          '<div class="load-err-icon">'+ICONS.xmark+'</div>' +
          '<div class="load-err-title">'+esc(title)+'</div>' +
          '<p class="load-err-text">'+esc(text)+'</p>' +
          '<button type="button" class="btn btn-ghost" onclick="location.href=\'results.php\'">Quay lại danh sách bài thi</button>' +
        '</div>' +
      '</div>';
  }

  function loadExamResult(){
    fetch('api/get_exam_result.php?exam_id=' + encodeURIComponent(EXAM_ID))
      .then(function(resp){ return resp.json(); })
      .then(function(data){
        if(!data.success || !data.result){
          renderError('Không tìm thấy kết quả bài thi', 'Vui lòng liên hệ giáo viên nếu em nghĩ đây là lỗi.');
          return;
        }
        examResult = data.result;
        displayResult();
        typeset();
      })
      .catch(function(err){
        console.error('Error loading exam result:', err);
        renderError('Lỗi tải kết quả', 'Vui lòng thử lại sau hoặc liên hệ giáo viên.');
      });
  }

  // ============ DISPLAY ============
  function gradeInfo(score){
    if(score >= 8.5){
      return { cls:'good', label:'Giỏi', desc:'Em làm bài tốt với ' + examResult.correct_answers + '/' + examResult.total_questions + ' câu trả lời đúng. Hãy giữ vững phong độ này nhé!' };
    }
    if(score >= 7.0){
      return { cls:'good', label:'Khá', desc:'Bài làm khá tốt với ' + examResult.correct_answers + '/' + examResult.total_questions + ' câu đúng. Ôn thêm một chút là đạt mức Giỏi!' };
    }
    if(score >= 5.0){
      return { cls:'warn', label:'Trung bình', desc:'Em đạt ' + examResult.correct_answers + '/' + examResult.total_questions + ' câu đúng. Xem lại chi tiết bên dưới để ôn thêm phần chưa chắc chắn nhé.' };
    }
    return { cls:'bad', label:'Cần cố gắng', desc:'Bài làm cần được cải thiện. Em hãy xem lại chi tiết bên dưới và luyện tập thêm để làm tốt hơn.' };
  }

  function displayResult(){
    var score = Number(examResult.score) || 0;
    var pct = Math.max(0, Math.min(100, Math.round(score * 10)));
    var grade = gradeInfo(score);

    document.getElementById('hero-exam-title').textContent = examResult.test_name || 'Bài kiểm tra trắc nghiệm';
    document.getElementById('hero-exam-sub').textContent =
      'Lớp ' + (examResult.class_code || '') +
      ' · ' + (examResult.student_name || '') +
      ' · Mã HS: ' + (examResult.student_code || '');

    var ring = document.getElementById('score-ring');
    ring.style.setProperty('--pct', String(pct));
    document.getElementById('score-num').textContent = String(Number(score.toFixed(1)));
    document.getElementById('score-den').textContent = '/ ' + (examResult.max_score || 10) + ' điểm';

    var badge = document.getElementById('grade-badge');
    badge.className = 'grade-badge ' + grade.cls;
    badge.hidden = false;
    document.getElementById('grade-label').textContent = grade.label;
    document.getElementById('grade-desc').textContent = grade.desc;

    var metaDate = document.getElementById('meta-date');
    try { metaDate.textContent = fmtDate(examResult.timestamp); } catch(e){ metaDate.textContent = '–'; }
    document.getElementById('meta-attempt').textContent = examResult.attempt ? 'Lần ' + examResult.attempt : 'Lần 1';
    document.getElementById('meta-total').textContent = examResult.total_questions;
    document.getElementById('meta-correct').textContent = examResult.correct_answers;

    // Stats
    var results = examResult.question_results || [];
    var correct = 0, wrong = 0, blank = 0;
    results.forEach(function(q){
      var st = questionStatus(q);
      if(st === 'correct') correct++;
      else if(st === 'incorrect') wrong++;
      else blank++;
    });
    document.getElementById('stat-correct').textContent = correct;
    document.getElementById('stat-wrong').textContent = wrong;
    document.getElementById('stat-blank').textContent = blank;
    document.getElementById('stat-attempt').textContent = examResult.attempt || 1;
    document.getElementById('sec-stats').hidden = false;

    // Integrity
    document.getElementById('sec-integrity').hidden = false;

    // Compare
    var cs = examResult.class_stats;
    if(cs && cs.total > 0){
      var cmp = document.getElementById('compare-list');
      var toPct = function(v){ return Math.max(0, Math.min(100, Math.round(Number(v) * 10))); };
      cmp.innerHTML =
        '<div class="compare-row">' +
          '<div class="compare-top"><span class="compare-name">Điểm của em</span><span class="compare-value">' + cs.self + '</span></div>' +
          '<div class="compare-track"><div class="compare-fill self" style="width:' + toPct(cs.self) + '%"></div></div>' +
        '</div>' +
        '<div class="compare-row">' +
          '<div class="compare-top"><span class="compare-name">Trung bình lớp</span><span class="compare-value">' + cs.avg + '</span></div>' +
          '<div class="compare-track"><div class="compare-fill avg" style="width:' + toPct(cs.avg) + '%"></div></div>' +
        '</div>' +
        '<div class="compare-row">' +
          '<div class="compare-top"><span class="compare-name">Cao nhất lớp</span><span class="compare-value">' + cs.max + '</span></div>' +
          '<div class="compare-track"><div class="compare-fill max" style="width:' + toPct(cs.max) + '%"></div></div>' +
        '</div>';
      document.getElementById('rank-note').innerHTML = 'Em xếp hạng <strong>' + cs.rank + ' / ' + cs.total + '</strong> trong lớp';
      document.getElementById('sec-compare').hidden = false;
    }

    // Feedback
    if(examResult.notes){
      document.getElementById('feedback-quote').textContent = '“' + examResult.notes + '”';
      document.getElementById('sec-feedback').hidden = false;
    }

    // Review
    renderReviewList('all');
    document.getElementById('sec-review').hidden = false;
    document.getElementById('sec-actions').hidden = false;
  }

  // ============ REVIEW LIST ============
  function reviewItem(q, index){
    var status = questionStatus(q);
    var iconMap = { correct: ICONS.check, incorrect: ICONS.xmark, blank: ICONS.dash };
    var userAns = answerText(q.user_answer);
    var correctAns = answerText(q.correct_answer);
    var body = '';

    if(status === 'correct'){
      body = '<div class="review-answer-line good"><span class="answer-letter">' + (userAns || '✓') + '</span><span>Em đã trả lời đúng câu này' + (q.explanation ? '' : '.') + '</span></div>';
      if(q.explanation){
        body += '<div class="review-explanation"><strong>Giải thích:</strong> ' + esc(q.explanation) + '</div>';
      }
    } else if(status === 'incorrect'){
      body = '<div class="review-answer-line bad"><span class="answer-letter">' + (userAns || '–') + '</span><span>Đáp án em đã chọn</span></div>';
      body += '<div class="review-answer-line good"><span class="answer-letter">' + (correctAns || '✓') + '</span><span>Đáp án đúng</span></div>';
      if(q.explanation){
        body += '<div class="review-explanation"><strong>Giải thích:</strong> ' + esc(q.explanation) + '</div>';
      }
    } else {
      body = '<p class="review-blank-note">Em không chọn đáp án nào cho câu này.</p>';
      body += '<div class="review-answer-line good" style="margin-top:8px"><span class="answer-letter">' + (correctAns || '✓') + '</span><span>Đáp án đúng</span></div>';
      if(q.explanation){
        body += '<div class="review-explanation"><strong>Giải thích:</strong> ' + esc(q.explanation) + '</div>';
      }
    }

    return (
      '<div class="review-item" data-status="' + status + '" data-open="false">' +
        '<button type="button" class="review-item-header" data-toggle-review="' + q.question_index + '" aria-expanded="false">' +
          '<span class="review-status-icon ' + status + '">' + iconMap[status] + '</span>' +
          '<span class="review-item-title"><span class="review-item-num">Câu ' + (index + 1) + '</span>' +
            '<span class="review-item-text">' + esc(q.question) + '</span></span>' +
          '<span class="review-chevron">' + ICONS.chevron + '</span>' +
        '</button>' +
        '<div class="review-body">' + body + '</div>' +
      '</div>'
    );
  }

  function renderReviewList(filter){
    currentFilter = filter;
    var qs = (examResult.question_results || []).slice();
    var filtered = qs.filter(function(q){
      return filter === 'all' || questionStatus(q) === filter;
    });
    document.getElementById('review-list').innerHTML = filtered.map(function(q, i){
      return reviewItem(q, qs.indexOf(q));
    }).join('');

    document.getElementById('review-list').querySelectorAll('[data-toggle-review]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var item = btn.closest('.review-item');
        var isOpen = item.dataset.open === 'true';
        item.dataset.open = isOpen ? 'false' : 'true';
        btn.setAttribute('aria-expanded', String(!isOpen));
      });
    });
  }

  function buildFilterChips(){
    var qs = examResult.question_results || [];
    var counts = { correct: 0, incorrect: 0, blank: 0 };
    qs.forEach(function(q){ counts[questionStatus(q)]++; });
    var wrap = document.getElementById('review-filter-chips');
    wrap.innerHTML =
      '<button class="chip" data-rfilter="all" aria-pressed="true">Tất cả (' + qs.length + ')</button>' +
      '<button class="chip" data-rfilter="correct" aria-pressed="false">Đúng (' + counts.correct + ')</button>' +
      '<button class="chip" data-rfilter="incorrect" aria-pressed="false">Sai (' + counts.incorrect + ')</button>' +
      '<button class="chip" data-rfilter="blank" aria-pressed="false">Bỏ trống (' + counts.blank + ')</button>';
    wrap.querySelectorAll('.chip').forEach(function(chip){
      chip.addEventListener('click', function(){
        wrap.querySelectorAll('.chip').forEach(function(c){ c.setAttribute('aria-pressed','false'); });
        chip.setAttribute('aria-pressed','true');
        renderReviewList(chip.dataset.rfilter);
      });
    });
  }

  document.getElementById('expand-all-btn').addEventListener('click', function(){
    expandAll = !expandAll;
    document.querySelectorAll('.review-item').forEach(function(item){
      item.dataset.open = expandAll ? 'true' : 'false';
      item.querySelector('[data-toggle-review]').setAttribute('aria-expanded', String(expandAll));
    });
    this.textContent = expandAll ? 'Thu gọn tất cả' : 'Mở rộng tất cả';
  });

  // ============ APPEAL MODAL ============
  function openAppealModal(){
    var body = document.getElementById('appeal-modal-body');
    var qs = examResult.question_results || [];
    var checks = qs.map(function(q, i){
      var text = String(q.question || '');
      return '<label class="check-row"><input type="checkbox" value="' + i + '"><span>Câu ' + (i + 1) + ' — ' + esc(text.slice(0, 42)) + (text.length > 42 ? '…' : '') + '</span></label>';
    }).join('');
    body.innerHTML =
      '<div class="modal-head"><div class="modal-title">Nộp đơn phúc khảo</div><button type="button" class="modal-close" data-close-modal aria-label="Đóng">' + ICONS.close + '</button></div>' +
      '<div class="modal-section"><span class="modal-label">Chọn câu muốn phúc khảo</span><div>' + checks + '</div></div>' +
      '<div class="modal-section"><label class="modal-label" for="appeal-reason">Lý do phúc khảo</label>' +
        '<textarea class="modal-textarea" id="appeal-reason" placeholder="Ví dụ: em nghĩ đáp án của mình cũng hợp lý ở câu này vì…"></textarea></div>' +
      '<div class="modal-actions">' +
        '<button type="button" class="btn btn-ghost" data-close-modal>Hủy</button>' +
        '<button type="button" class="btn btn-primary" id="appeal-submit-btn">Gửi đơn</button>' +
      '</div>';
    wireModalCloseButtons(body);
    document.getElementById('appeal-submit-btn').addEventListener('click', function(){
      var checked = body.querySelectorAll('input[type="checkbox"]:checked');
      var reason = document.getElementById('appeal-reason').value.trim();
      if(checked.length === 0 || !reason){
        alert('Em hãy chọn ít nhất 1 câu và nhập lý do phúc khảo trước khi gửi nhé.');
        return;
      }
      body.innerHTML =
        '<div style="text-align:center;padding:20px 6px">' +
          '<div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;background:var(--good-soft);color:var(--good)">' + ICONS.check + '</div>' +
          '<div style="font-family:var(--font-display);font-weight:700;font-size:16px;margin-bottom:4px">Đã gửi đơn phúc khảo!</div>' +
          '<div style="font-size:12.5px;color:var(--ink-soft);margin-bottom:18px">Giáo viên sẽ xem xét và phản hồi trong vòng 3 ngày làm việc.</div>' +
          '<button type="button" class="btn btn-primary" data-close-modal style="width:100%">Xong</button>' +
        '</div>';
      wireModalCloseButtons(body);
    });
    openModal('appeal-modal');
  }
  document.getElementById('open-appeal-btn').addEventListener('click', openAppealModal);

  // ============ PRINT ============
  document.getElementById('print-btn').addEventListener('click', function(){
    var wasExpand = expandAll;
    document.querySelectorAll('.review-item').forEach(function(item){ item.dataset.open = 'true'; });
    window.print();
    document.querySelectorAll('.review-item').forEach(function(item){ item.dataset.open = wasExpand ? 'true' : 'false'; });
  });

  // ============ INIT ============
  loadExamResult();

})();
</script>
</body>
</html>
