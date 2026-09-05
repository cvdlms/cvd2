<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/exam_helper.php';
require_once __DIR__ . '/../includes/student_gender.php';

$examId = $_GET['exam_id'] ?? $_GET['type'] ?? '';
if (!$examId) {
    header('Location: dashboard.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';

// Determine grade level from class code
$prefix = substr($studentClassCode, 0, 1);
$grade = 'khoi' . $prefix;
$gradeLevel = $prefix;

// Resolve the exam file safely (guards against path traversal, supports
// both legacy "subject_slug" and canonical test_id formats).
$resolved = exam_resolve_file($examId, $grade);
if (!$resolved) {
    header('Location: dashboard.php');
    exit;
}

$examFile = $resolved['file'];
$subjectId = $resolved['subject_id'];

$examData = json_decode(file_get_contents($examFile), true);
if (!is_array($examData)) {
    header('Location: dashboard.php');
    exit;
}

$canonicalTestId = $examData['test_id'] ?? null;
$examType = $examData['exam_type'] ?? 'practice';
$questions = $examData['questions'] ?? [];
$timeLimit = (int)($examData['time_limit'] ?? 45);
$testName = $examData['test_name'] ?? $examId;

if (empty($questions)) {
    header('Location: dashboard.php');
    exit;
}

// Retake rules:
// 1. Official exams: 1 attempt for everyone (fair rankings)
// 2. Practice exams: unlimited attempts for everyone
$submittedResultId = exam_find_result_id($studentCode, $canonicalTestId, $subjectId);
if ($submittedResultId && $examType === 'official') {
    $_SESSION['exam_limit_msg'] = "Đây là bài thi chính thức, chỉ được thi 1 lần duy nhất để đảm bảo công bằng.";
    header('Location: result.php?exam_id=' . urlencode($submittedResultId));
    exit;
}

// Deterministic shuffle so each student gets the same order on every reload
// and the server can re-grade the same order.
$questions = exam_shuffle_questions($questions, $studentCode, $canonicalTestId, $examData['exam_category'] ?? null);

// Load subjects for the subject name
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}
$subjectName = $subjects[$subjectId] ?? 'Unknown';

$questionCount = count($questions);
$hasMultiple = false; $hasTfm = false; $hasEssay = false;
foreach ($questions as $q) {
    $t = $q['type'] ?? 'single';
    if ($t === 'multiple') $hasMultiple = true;
    elseif ($t === 'true_false_multiple') $hasTfm = true;
    elseif ($t === 'essay') $hasEssay = true;
}
$labelParts = [$hasMultiple ? 'Trắc nghiệm (có câu nhiều đáp án)' : 'Trắc nghiệm 1 đáp án'];
if ($hasTfm) $labelParts[] = 'Đúng/Sai nhiều ý';
if ($hasEssay) $labelParts[] = 'Tự luận';
$formLabel = implode(' + ', $labelParts);

// Safe payloads for JS injection
$jsExam = json_encode([
    'examId' => $canonicalTestId ?: $examId,
    'type' => $canonicalTestId ?: $examId,
    'testName' => $testName,
    'subjectName' => $subjectName,
    'studentName' => $studentName,
    'studentCode' => $studentCode,
    'classCode' => $studentClassCode,
    'gradeLevel' => $gradeLevel,
    'timeLimit' => $timeLimit,
    'examType' => $examType,
    'maxViolations' => (int)($examData['max_violations'] ?? 6),
    'questionCount' => $questionCount,
    'formLabel' => $formLabel,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
// NOTE: correct answers are STRIPPED before sending to the client (anti-cheat).
$jsQuestions = json_encode(exam_strip_answers($questions), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Phòng thi trực tuyến — <?php echo htmlspecialchars($testName); ?></title>
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
        }
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>
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
    --bg:#FFF8F4;
    --bg-dot: rgba(244,86,140,0.16);
    --surface:#FFFFFF;
    --surface-alt:#FFF1F5;
    --ink:#3D2C2E;
    --ink-soft:#8A6F72;
    --primary:#F4568C;
    --primary-ink:#FFFFFF;
    --secondary:#8B7CF6;
    --border: #F6DEE4;
    --focus:#8B7CF6;
    --radius-card: 22px;
    --radius-sm: 14px;
    --radius-pill: 999px;
    --shadow: 0 14px 34px -16px rgba(244,86,140,.32), 0 2px 8px rgba(61,44,46,.05);
    --shadow-sm: 0 6px 16px -8px rgba(61,44,46,.14);
    --font-display:'Baloo 2', 'Be Vietnam Pro', sans-serif;
    --font-body:'Be Vietnam Pro', sans-serif;
    --font-mono:'Space Mono', monospace;
    --card-border-width: 0px;
    --tape-1:#FFC857;
    background:var(--bg);
    background-image: radial-gradient(var(--bg-dot) 1.4px, transparent 1.4px);
    background-size: 15px 15px;
    color:var(--ink);
    font-family:var(--font-body);
    transition: background-color .4s ease, color .4s ease;
  }
  body[data-theme="elegant"]{
    --bg:#12151A;
    --surface:#1A1E26;
    --surface-alt:#20242D;
    --ink:#ECE9E2;
    --ink-soft:#9BA0AA;
    --primary:#C9A857;
    --primary-ink:#12151A;
    --secondary:#6F91B5;
    --border: rgba(201,168,87,.16);
    --focus:#C9A857;
    --radius-card: 10px;
    --radius-sm: 6px;
    --shadow: 0 24px 48px -22px rgba(0,0,0,.65);
    --shadow-sm: 0 8px 20px -10px rgba(0,0,0,.5);
    --card-border-width: 1px;
    background: var(--bg);
    background-image: radial-gradient(60% 40% at 50% -10%, rgba(201,168,87,.10), transparent 60%);
  }

  /* ============ SHARED COMPONENTS ============ */
  .btn{
    display:inline-flex; align-items:center; gap:8px; justify-content:center;
    padding:13px 20px; border-radius:var(--radius-pill);
    font-weight:700; font-size:14px; white-space:nowrap;
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease, opacity .18s ease;
  }
  .btn svg{width:16px;height:16px}
  .btn-primary{background:var(--primary); color:var(--primary-ink); box-shadow:var(--shadow-sm)}
  .btn-primary:hover{filter:brightness(1.06)}
  .btn-primary:disabled{opacity:.45; cursor:not-allowed; filter:none}
  .btn-ghost{background:var(--surface-alt); color:var(--ink); border:1px solid var(--border)}
  .btn-ghost:disabled{opacity:.45; cursor:not-allowed}
  .btn-sm{padding:9px 14px; font-size:12.5px}

  .theme-toggle{
    position:relative;display:flex;align-items:center;
    background:var(--surface-alt); border:1px solid var(--border);
    border-radius:var(--radius-pill); padding:3px; gap:2px; flex:none;
  }
  .theme-toggle button{
    position:relative;z-index:2;display:flex;align-items:center;gap:6px;
    padding:7px 11px;border-radius:var(--radius-pill);
    font-size:12px;font-weight:600;color:var(--ink-soft);
    transition:color .25s ease; white-space:nowrap;
  }
  .theme-toggle button[aria-pressed="true"]{color:var(--primary-ink)}
  .theme-toggle button svg{width:13px;height:13px;flex:none}
  .toggle-pill{
    position:absolute; top:3px; bottom:3px; left:3px;
    width:calc(50% - 3px); border-radius:var(--radius-pill);
    background:var(--primary); transition: transform .32s cubic-bezier(.65,0,.35,1);
  }
  body[data-theme="elegant"] .toggle-pill{ transform: translateX(100%); }
  .toggle-label{display:none}
  @media (min-width:480px){ .toggle-label{display:inline} }

  /* modal (submit confirm / log / drawer) */
  .modal-overlay{
    position:fixed; inset:0; z-index:60; display:none;
    align-items:flex-end; justify-content:center;
    background:rgba(10,8,9,.5); backdrop-filter:blur(2px);
  }
  body[data-theme="elegant"] .modal-overlay{background:rgba(0,0,0,.7)}
  .modal-overlay.open{display:flex}
  @media (min-width:768px){ .modal-overlay{align-items:center; padding:24px} }
  .modal-sheet{
    width:100%; max-width:480px; max-height:88vh; overflow-y:auto;
    background:var(--surface); border:var(--card-border-width) solid var(--border);
    border-radius: var(--radius-card) var(--radius-card) 0 0;
    box-shadow:var(--shadow); padding:20px 18px calc(20px + env(safe-area-inset-bottom));
  }
  @media (min-width:768px){ .modal-sheet{border-radius:var(--radius-card)} }
  .modal-head{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px}
  .modal-title{font-family:var(--font-display); font-weight:700; font-size:17px}
  .modal-close{
    width:32px;height:32px;border-radius:50%;flex:none;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-alt); color:var(--ink-soft);
  }
  .modal-close svg{width:16px;height:16px}
  .modal-actions{display:flex; gap:10px; margin-top:4px}
  .modal-actions .btn{flex:1}

  /* ============ TOPBAR ============ */
  .topbar{
    position:sticky; top:0; z-index:40;
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:12px 16px;
    background: color-mix(in srgb, var(--bg) 90%, transparent);
    backdrop-filter: blur(10px);
    border-bottom:1px solid var(--border);
  }
  .brand{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
  .brand-mark{
    width:36px;height:36px;border-radius: var(--radius-sm);
    background:var(--primary); color:var(--primary-ink);
    display:flex;align-items:center;justify-content:center;flex:none;
    font-family:var(--font-display); font-weight:800; font-size:14px;
  }
  .brand-text{min-width:0}
  .brand-title{font-family:var(--font-display);font-weight:700;font-size:13.5px;line-height:1.2;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .brand-sub{font-size:10.5px;color:var(--ink-soft)}
  .topbar-right{display:flex;align-items:center;gap:8px;flex:none}

  .timer-pill{
    display:flex; align-items:center; gap:6px; padding:8px 12px; border-radius:var(--radius-pill);
    font-family:var(--font-mono); font-weight:700; font-size:14px;
    background:var(--surface-alt); border:1px solid var(--border); color:var(--ink); flex:none;
  }
  .timer-pill svg{width:14px;height:14px}
  .timer-pill.warning{ color:#B8860B; border-color:#E8C568; background:rgba(232,197,104,.14) }
  .timer-pill.critical{ color:#D14343; border-color:#D14343; background:rgba(209,67,67,.12) }
  @media (prefers-reduced-motion:no-preference){ .timer-pill.critical{ animation: pulse 1s ease-in-out infinite } }
  @keyframes pulse{ 0%,100%{opacity:1} 50%{opacity:.5} }

  .violation-badge{
    display:flex; align-items:center; gap:5px; padding:7px 10px; border-radius:var(--radius-pill);
    background:rgba(209,67,67,.1); color:#D14343; border:1px solid rgba(209,67,67,.3);
    font-size:11px; font-weight:700; flex:none;
  }
  .violation-badge.critical{ background:#D14343; color:#fff; border-color:#D14343 }
  .violation-badge svg{width:12px;height:12px}

  /* ============ EXIT-FULLSCREEN BANNER ============ */
  .exit-banner{
    display:none; align-items:center; justify-content:center; gap:12px;
    background:#D14343; color:#fff; padding:9px 16px; font-size:12px; font-weight:600; text-align:center;
    position:sticky; top:0; z-index:41;
  }
  .exit-banner.show{ display:flex }
  .exit-banner button{ background:rgba(255,255,255,.22); padding:5px 12px; border-radius:var(--radius-pill); font-weight:700; font-size:11px; flex:none }

  /* ============ TOASTS ============ */
  .toast-wrap{ position:fixed; top:14px; left:50%; transform:translateX(-50%); z-index:70;
    display:flex; flex-direction:column; gap:8px; width:min(420px,92vw); pointer-events:none; align-items:center }
  .toast{
    background:#2A2020; color:#fff; padding:11px 14px; border-radius:var(--radius-sm); font-size:12.5px;
    display:flex; align-items:flex-start; gap:9px; box-shadow:var(--shadow);
    opacity:0; transform:translateY(-8px); transition:opacity .25s ease, transform .25s ease; line-height:1.4;
  }
  .toast.show{ opacity:1; transform:translateY(0) }
  .toast svg{ width:15px;height:15px;flex:none; color:#FFC857; margin-top:1px }
  body[data-theme="elegant"] .toast{ background:#20242D; border:1px solid rgba(201,168,87,.3) }

  /* ============ GATE SCREEN ============ */
  .gate-wrap{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding:28px 16px }
  .gate-card{ position:relative; width:100%; max-width:560px; background:var(--surface);
    border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card);
    box-shadow:var(--shadow); padding:26px 22px; overflow:hidden }
  body[data-theme="cute"] .gate-card::before{
    content:""; position:absolute; top:-8px; left:28px; width:60px; height:20px;
    background:repeating-linear-gradient(115deg, var(--tape-1) 0 6px, #ffe3a3 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px; box-shadow:0 2px 4px rgba(0,0,0,.08);
  }
  body[data-theme="elegant"] .gate-card::before,
  body[data-theme="elegant"] .gate-card::after{
    content:""; position:absolute; width:16px; height:16px; border-radius:50%; background:var(--bg); top:50%; transform:translateY(-50%);
  }
  body[data-theme="elegant"] .gate-card::before{ left:-8px } body[data-theme="elegant"] .gate-card::after{ right:-8px }

  .gate-head-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
  .gate-icon{ width:50px;height:50px;border-radius:var(--radius-sm); background:var(--surface-alt); color:var(--primary);
    display:flex;align-items:center;justify-content:center; flex:none }
  .gate-icon svg{width:24px;height:24px}
  .gate-title{ font-family:var(--font-display); font-weight:700; font-size:19px; margin-bottom:3px }
  .gate-sub{ font-size:12.5px; color:var(--ink-soft); margin-bottom:18px }

  .gate-meta-row{ display:grid; grid-template-columns:repeat(2,1fr); gap:9px; margin-bottom:20px }
  .gate-meta-item{ background:var(--surface-alt); border-radius:var(--radius-sm); padding:11px 12px }
  .gate-meta-label{ font-size:10px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px }
  .gate-meta-value{ font-weight:700; font-size:13.5px }

  .gate-section-title{font-weight:700;font-size:13px;margin-bottom:10px}
  .rules-list{ margin-bottom:22px }
  .rules-list li{ display:flex; gap:10px; font-size:12.5px; line-height:1.55; margin-bottom:10px; color:var(--ink) }
  .rules-list svg{ width:16px;height:16px; flex:none; margin-top:1px; color:var(--primary) }

  /* camera check */
  .camera-check{ margin-bottom:20px }
  .camera-preview{
    position:relative; width:100%; max-width:260px; aspect-ratio:4/3; margin:0 auto 12px;
    border-radius:var(--radius-sm); overflow:hidden; background:var(--surface-alt);
    border:1.5px dashed var(--border); display:flex; align-items:center; justify-content:center;
  }
  .camera-preview.active{ border-style:solid; border-color:var(--primary) }
  .camera-preview video{
    position:absolute; inset:0; width:100%;height:100%;object-fit:cover;
    transform:scaleX(-1); opacity:0; transition:opacity .2s ease; pointer-events:none;
  }
  .camera-preview.active video{ opacity:1 }
  .camera-placeholder{ position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; gap:7px; color:var(--ink-soft); font-size:11.5px; text-align:center; padding:14px }
  .camera-placeholder svg{ width:26px;height:26px }
  .camera-status{ position:absolute; top:8px; left:8px; display:flex; align-items:center; gap:5px;
    background:rgba(0,0,0,.55); color:#fff; font-size:10px; font-weight:700; padding:4px 9px; border-radius:999px }
  .camera-status .dot{ width:6px;height:6px;border-radius:50%; background:#34D399 }
  @media (prefers-reduced-motion:no-preference){ .camera-status .dot{ animation: pulse 1.6s ease-in-out infinite } }
  .camera-hint{ font-size:11px; color:var(--ink-soft); text-align:center; margin:8px 0 6px; line-height:1.5; min-height:32px }
  .camera-skip{ display:block; margin:2px auto 0; font-size:10.5px; color:var(--ink-soft); text-decoration:underline; text-underline-offset:2px }

  .agree-row{ display:flex; align-items:flex-start; gap:10px; padding:12px; background:var(--surface-alt); border-radius:var(--radius-sm); margin-bottom:16px; cursor:pointer }
  .agree-row input{ margin-top:3px; width:16px; height:16px; flex:none; accent-color:var(--primary) }
  .agree-row span{ font-size:12.5px; line-height:1.5 }

  .resume-box{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px;
    background:rgba(11,143,121,.09); border:1px solid rgba(11,143,121,.35); border-radius:var(--radius-sm); margin-bottom:16px }
  .resume-box .resume-text{ font-size:12.5px; line-height:1.5; color:var(--ink); flex:1; min-width:0 }
  .resume-box .btn{ flex:none }

  /* ============ EXAM BODY LAYOUT ============ */
  .exam-body{ max-width:1200px; margin:0 auto; padding:18px 16px 100px; }
  @media (min-width:1024px){
    .exam-body{ display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:26px; padding:26px 32px 60px; }
  }
  .panel-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:16px}
  .panel-title{font-family:var(--font-display); font-weight:700; font-size:13.5px; margin-bottom:12px}

  .nav-sidebar{ display:none }
  @media (min-width:1024px){ .nav-sidebar{ display:flex; flex-direction:column; gap:16px; position:sticky; top:88px; height:fit-content } }

  /* question card */
  .question-card{ background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card);
    box-shadow:var(--shadow); padding:22px 20px; }
  .q-head{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px }
  .q-number{ font-family:var(--font-mono); font-size:12.5px; font-weight:700; color:var(--ink-soft) }
  .q-type-tag{ display:inline-block; margin-left:8px; padding:2px 8px; border-radius:999px;
    background:var(--surface-alt); border:1px solid var(--border); color:var(--ink-soft);
    font-family:var(--font-body); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.03em }
  .flag-btn{ display:flex; align-items:center; gap:6px; padding:7px 12px; border-radius:var(--radius-pill);
    font-size:11.5px; font-weight:700; color:var(--ink-soft); background:var(--surface-alt); border:1px solid var(--border) }
  .flag-btn svg{ width:13px;height:13px }
  .flag-btn.active{ background:#FFF4D6; color:#B8860B; border-color:#E8C568 }
  body[data-theme="elegant"] .flag-btn.active{ background:rgba(201,168,87,.14); color:var(--primary); border-color:var(--primary) }
  .q-text{ font-family:var(--font-display); font-weight:700; font-size:17px; line-height:1.5; margin-bottom:20px }
  .q-image{ display:block; max-width:100%; max-height:300px; margin:14px auto 4px; border-radius:var(--radius-sm); border:1px solid var(--border); box-shadow:var(--shadow-sm) }

  .exam-option{
    position:relative; display:flex; align-items:center; gap:14px;
    padding:15px 16px; border-radius:var(--radius-sm); border:1.5px solid var(--border);
    background:var(--surface-alt); cursor:pointer; margin-bottom:11px;
    transition:border-color .15s ease, background .15s ease;
  }
  .exam-option-input{ position:absolute; opacity:0; width:1px; height:1px; }
  .exam-option.selected,
  .exam-option:has(.exam-option-input:checked){ border-color:var(--primary); background:color-mix(in srgb, var(--primary) 9%, var(--surface-alt)); }
  .exam-option:has(.exam-option-input:focus-visible){ outline:2px solid var(--focus); outline-offset:2px; }
  .exam-option .opt-letter{
    width:30px;height:30px;border-radius:50%;flex:none;
    display:flex;align-items:center;justify-content:center;
    border:1.5px solid var(--border); background:var(--surface); font-weight:700; font-size:13px; color:var(--ink-soft);
  }
  .exam-option.selected .opt-letter,
  .exam-option:has(.exam-option-input:checked) .opt-letter{ background:var(--primary); border-color:var(--primary); color:var(--primary-ink); }
  .exam-option .opt-text{ font-size:14px; font-weight:500; line-height:1.45 }

  .q-nav-row{ display:none; align-items:center; justify-content:space-between; margin-top:20px; gap:10px }
  @media (min-width:1024px){ .q-nav-row{ display:flex } }

  /* navigator grid */
  .nav-grid{ display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin-bottom:14px }
  @media (min-width:1024px){ .nav-grid{grid-template-columns:repeat(4,1fr)} }
  .nav-cell{
    aspect-ratio:1; border-radius:9px; display:flex;align-items:center;justify-content:center;
    font-weight:700; font-size:12.5px; border:1.5px solid var(--border); background:var(--surface);
    color:var(--ink-soft); position:relative;
  }
  .nav-cell.answered{ background:var(--primary); border-color:var(--primary); color:var(--primary-ink); }

  /* ===== Đúng/Sai nhiều ý & Tự luận (Phase B) ===== */
  .tfm-hint{ font-size:13px; color:var(--ink-soft); margin:-4px 0 14px; }
  .tfm-items{ display:flex; flex-direction:column; gap:10px; margin-bottom:18px; }
  .tfm-row{
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; border-radius:var(--radius-sm);
    background:var(--surface-alt); border:1.5px solid transparent;
    transition:border-color .15s;
  }
  .tfm-row:focus-within{ border-color:var(--primary); }
  .tfm-letter{
    flex:none; width:28px; height:28px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--primary); color:#fff;
    font-family:var(--font-display); font-weight:700; font-size:14px;
  }
  .tfm-statement{ flex:1; font-size:15px; line-height:1.5; min-width:0; }
  .tfm-toggles{ flex:none; display:flex; gap:8px; }
  .tfm-toggle{ position:relative; cursor:pointer; }
  .tfm-toggle input{ position:absolute; opacity:0; pointer-events:none; }
  .tfm-toggle span{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:56px; padding:7px 12px; border-radius:var(--radius-pill);
    border:1.5px solid var(--border); background:var(--surface);
    font-weight:700; font-size:13px; color:var(--ink-soft);
    transition:all .12s;
  }
  .tfm-toggle input:checked + span{ color:#fff; }
  .tfm-toggle.on-yes span{ background:var(--good, #16A34A); border-color:var(--good, #16A34A); }
  .tfm-toggle.on-no span{ background:var(--bad, #DC2626); border-color:var(--bad, #DC2626); }
  @media (max-width:560px){
    .tfm-row{ flex-wrap:wrap; }
    .tfm-statement{ flex-basis:100%; order:3; }
    .tfm-toggles{ margin-left:auto; }
  }
  .essay-input{
    width:100%; resize:vertical; min-height:180px;
    padding:14px 16px; border-radius:var(--radius-sm);
    border:1.5px solid var(--border); background:var(--surface);
    font:inherit; font-size:15px; line-height:1.6; color:var(--ink);
    margin-bottom:18px;
  }
  .essay-input:focus{ outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(244,86,140,.14); }
  .nav-cell.flagged::after{ content:""; position:absolute; top:-3px; right:-3px; width:9px;height:9px;border-radius:50%; background:#FFC857; border:1.5px solid var(--surface); }
  .nav-cell.current{ outline:2px solid var(--secondary); outline-offset:2px; }

  .legend{ display:flex; flex-direction:column; gap:7px; margin-bottom:14px; font-size:11px; color:var(--ink-soft) }
  .legend span{ display:flex; align-items:center; gap:8px }
  .legend i{ width:14px;height:14px;border-radius:4px; display:inline-block; border:1.5px solid var(--border); background:var(--surface); flex:none }
  .legend i.lg-answered{ background:var(--primary); border-color:var(--primary) }
  .legend i.lg-flagged{ position:relative }
  .legend i.lg-flagged::after{ content:""; position:absolute; top:-3px;right:-3px;width:7px;height:7px;border-radius:50%;background:#FFC857 }
  .legend i.lg-current{ outline:2px solid var(--secondary); outline-offset:1px }

  .progress-summary{ font-size:12px; font-weight:700; color:var(--ink-soft); margin-bottom:12px; text-align:center;
    background:var(--surface-alt); border-radius:var(--radius-sm); padding:9px }

  /* mobile footer */
  .exam-footer{ position:fixed; left:0; right:0; bottom:0; z-index:35; display:flex; align-items:center; gap:8px;
    padding:10px 12px calc(10px + env(safe-area-inset-bottom));
    background:color-mix(in srgb, var(--surface) 94%, transparent); backdrop-filter:blur(10px); border-top:1px solid var(--border); }
  @media(min-width:1024px){ .exam-footer{ display:none } }
  .exam-footer .btn{ flex:none }
  .grid-fab{ width:46px;height:46px;border-radius:var(--radius-sm); background:var(--surface-alt); border:1px solid var(--border);
    display:flex;align-items:center;justify-content:center; position:relative; flex:1 }
  .grid-fab svg{width:19px;height:19px}
  .grid-fab .fab-badge{ position:absolute; top:-6px; right:6px; background:var(--primary); color:var(--primary-ink);
    font-size:9.5px; font-weight:800; padding:1px 6px; border-radius:999px; min-width:16px; text-align:center }

  /* camera picture-in-picture (during exam) */
  .camera-pip{ position:fixed; right:14px; bottom:78px; z-index:32; width:100px; height:76px; border-radius:12px;
    overflow:hidden; border:2px solid var(--surface); box-shadow:var(--shadow); background:#000; }
  @media (min-width:1024px){ .camera-pip{ bottom:24px; right:24px; width:150px; height:112px } }
  .camera-pip video{ width:100%;height:100%;object-fit:cover; display:block; transform:scaleX(-1) }
  .camera-pip .pip-status{ position:absolute; left:5px; bottom:4px; display:flex; align-items:center; gap:4px;
    font-size:8.5px; font-weight:700; color:#fff; background:rgba(0,0,0,.45); padding:2px 6px; border-radius:999px }
  .camera-pip .pip-dot{ width:5px;height:5px;border-radius:50%; background:#34D399 }
  @media (prefers-reduced-motion:no-preference){ .camera-pip .pip-dot{ animation: pulse 1.6s ease-in-out infinite } }

  /* violation log */
  .log-row{ display:flex; gap:10px; padding:10px 0; border-top:1px solid var(--border); font-size:12.5px }
  .log-row:first-child{ border-top:none }
  .log-thumb{ width:64px;height:48px; border-radius:8px; object-fit:cover; flex:none; border:1px solid var(--border); background:var(--surface-alt) }
  .log-body{ flex:1; min-width:0 }
  .log-time{ flex:none; font-family:var(--font-mono); color:var(--ink-soft); font-size:10.5px; display:block; margin-bottom:2px }
  .log-msg{ flex:1; line-height:1.45 }
  .log-ai-tag{ display:inline-block; font-size:9.5px; font-weight:800; color:var(--secondary); letter-spacing:.03em; margin-bottom:2px }

  /* AI status */
  .ai-status{ display:flex; align-items:center; justify-content:center; gap:6px; font-size:10.5px; font-weight:700; margin-top:2px; padding:6px 0 }
  .ai-status::before{ content:""; width:6px;height:6px;border-radius:50%; background:currentColor; flex:none }
  .ai-status.loading{ color:var(--ink-soft) }
  .ai-status.ready{ color:#0E8F79 }
  .ai-status.off{ color:#B8860B }
  @media (prefers-reduced-motion:no-preference){ .ai-status.loading::before{ animation: pulse 1.2s ease-in-out infinite } }

  /* submitted screen */
  .submitted-wrap{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px 16px }
  .submitted-card{ position:relative; text-align:center; max-width:440px; background:var(--surface);
    border:var(--card-border-width) solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow); padding:32px 26px; overflow:hidden }
  body[data-theme="cute"] .submitted-card::before{
    content:""; position:absolute; top:-8px; left:32px; width:56px; height:20px;
    background:repeating-linear-gradient(115deg, #4ECDC4 0 6px, #b9f2e9 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px;
  }
  .submitted-icon{ width:68px;height:68px;border-radius:50%; margin:0 auto 16px; display:flex;align-items:center;justify-content:center;
    background:#DFF7F1; color:#0E8F79 }
  body[data-theme="elegant"] .submitted-icon{ background:rgba(201,168,87,.12); color:var(--primary) }
  .submitted-icon svg{width:30px;height:30px}
  .submitted-title{ font-family:var(--font-display); font-weight:700; font-size:19px; margin-bottom:6px }
  .submitted-sub{ font-size:13px; color:var(--ink-soft); margin-bottom:20px; line-height:1.5 }
  .submitted-stats{ display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px }
  .submitted-stat{ background:var(--surface-alt); border-radius:var(--radius-sm); padding:12px }
  .submitted-stat b{ display:block; font-family:var(--font-display); font-size:17px; margin-bottom:2px }
  .submitted-stat span{ font-size:10.5px; color:var(--ink-soft) }

  [hidden]{ display:none !important }

  @media (prefers-reduced-motion: reduce){
    *{transition-duration:.001ms !important; animation-duration:.001ms !important}
  }
</style>
</head>
<body data-theme="<?php echo htmlspecialchars($stdDesignTheme); ?>">

<div class="toast-wrap" id="toast-wrap"></div>
<canvas id="ai-snapshot-canvas" hidden></canvas>

<!-- ===== GATE SCREEN ===== -->
<div class="gate-wrap" id="gate-screen">
  <div class="gate-card">
    <div class="gate-head-row">
      <div class="gate-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/></svg>
      </div>
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

    <div class="gate-title" id="gate-title"></div>
    <div class="gate-sub" id="gate-sub"></div>

    <div class="gate-meta-row">
      <div class="gate-meta-item"><div class="gate-meta-label">Thời gian</div><div class="gate-meta-value" id="gate-time">–</div></div>
      <div class="gate-meta-item"><div class="gate-meta-label">Số câu</div><div class="gate-meta-value" id="gate-count">–</div></div>
      <div class="gate-meta-item"><div class="gate-meta-label">Hình thức</div><div class="gate-meta-value" id="gate-form">–</div></div>
      <div class="gate-meta-item"><div class="gate-meta-label">Thí sinh</div><div class="gate-meta-value" id="gate-student">–</div></div>
    </div>

    <p class="gate-section-title">Quy chế phòng thi</p>
    <ul class="rules-list">
      <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>Không rời khỏi màn hình làm bài, chuyển tab hoặc mở ứng dụng khác trong lúc thi.</li>
      <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3.2"/></svg>Camera phải được bật trong suốt bài thi để hệ thống giám sát.</li>
      <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.5 18a1.8 1.8 0 0 0 1.5 2.7h16a1.8 1.8 0 0 0 1.5-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0Z"/></svg>Hệ thống ghi nhận các hoạt động bất thường (rời màn hình, thoát toàn màn hình, dừng quá lâu ở một câu…). Vi phạm nhiều lần có thể bị hủy bài thi.</li>
      <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>Bài thi tự động nộp khi hết giờ — hãy kiểm tra kỹ trước khi bấm nộp bài.</li>
    </ul>

    <p class="gate-section-title">Kiểm tra camera giám sát</p>
    <div class="camera-check">
      <div class="camera-preview" id="camera-preview">
        <video id="camera-video" autoplay playsinline muted></video>
        <div class="camera-placeholder" id="camera-placeholder">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3.2"/></svg>
          <span>Camera chưa được bật</span>
        </div>
        <span class="camera-status" id="camera-status" hidden><span class="dot"></span>Đang hoạt động</span>
      </div>
      <button type="button" class="btn btn-ghost" id="camera-btn" style="width:100%">Bật camera giám sát</button>
      <p class="camera-hint" id="camera-hint">Hệ thống yêu cầu bật camera trước khi vào phòng thi, theo quy chế thi trực tuyến.</p>
      <button type="button" class="camera-skip" id="camera-skip">Bỏ qua bước này (thiết bị không có camera)</button>
      <div class="ai-status" id="ai-status" hidden></div>
    </div>

    <label class="agree-row">
      <input type="checkbox" id="agree-check">
      <span>Em đã đọc và đồng ý tuân thủ quy chế phòng thi trực tuyến.</span>
    </label>

    <div class="resume-box" id="resume-box" hidden>
      <span class="resume-text" id="resume-text"></span>
      <button type="button" class="btn btn-ghost btn-sm" id="resume-exam-btn">Tiếp tục làm bài</button>
    </div>

    <button type="button" class="btn btn-primary" id="start-exam-btn" disabled style="width:100%">
      Bắt đầu làm bài
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>
  </div>
</div>

<!-- ===== EXAM SCREEN ===== -->
<div id="exam-screen" hidden>
  <div class="exit-banner" id="exit-banner">
    <span>⚠ Em đã thoát chế độ toàn màn hình. Vui lòng quay lại để tiếp tục làm bài.</span>
    <button type="button" id="reenter-fullscreen-btn">Vào lại toàn màn hình</button>
  </div>

  <header class="topbar">
    <div class="brand">
      <div class="brand-mark">TT</div>
      <div class="brand-text">
        <div class="brand-title" id="brand-title"></div>
        <div class="brand-sub" id="brand-sub"></div>
      </div>
    </div>
    <div class="topbar-right">
      <button type="button" class="violation-badge" id="violation-badge" hidden aria-label="Xem nhật ký giám sát"></button>
      <div class="timer-pill" id="timer-pill">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>
        <span id="timer-text">--:--</span>
      </div>
      <button type="button" class="btn btn-primary btn-sm" id="open-submit-btn">Nộp bài</button>
    </div>
  </header>

  <div class="exam-body">
    <main>
      <div class="question-card" id="question-card"></div>
    </main>

    <aside class="nav-sidebar">
      <div class="panel-card">
        <div class="panel-title">Danh sách câu hỏi</div>
        <div class="nav-grid" id="nav-grid-desktop"></div>
        <div class="legend">
          <span><i class="lg-answered"></i>Đã trả lời</span>
          <span><i></i>Chưa trả lời</span>
          <span><i class="lg-flagged"></i>Đã đánh dấu xem lại</span>
          <span><i class="lg-current"></i>Câu hiện tại</span>
        </div>
        <div class="progress-summary" data-progress-summary>Đã làm: 0/0 câu</div>
        <button type="button" class="btn btn-primary" id="open-submit-btn-2" style="width:100%">Nộp bài</button>
      </div>
    </aside>
  </div>

  <div class="exam-footer">
    <button type="button" class="btn btn-ghost btn-sm" id="prev-btn">← Trước</button>
    <button type="button" class="grid-fab" id="open-drawer-btn" aria-label="Danh sách câu hỏi">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      <span class="fab-badge" id="fab-badge">0/0</span>
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="next-btn">Tiếp →</button>
  </div>

  <div class="camera-pip" id="camera-pip" hidden>
    <video id="camera-pip-video" autoplay playsinline muted></video>
    <span class="pip-status"><span class="pip-dot"></span>Giám sát</span>
  </div>
</div>

<!-- ===== NAV DRAWER (mobile) ===== -->
<div class="modal-overlay" id="nav-drawer" role="dialog" aria-modal="true">
  <div class="modal-sheet">
    <div class="modal-head">
      <div class="modal-title">Danh sách câu hỏi</div>
      <button type="button" class="modal-close" data-close-modal aria-label="Đóng"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="nav-grid" id="nav-grid-mobile"></div>
    <div class="legend">
      <span><i class="lg-answered"></i>Đã trả lời</span>
      <span><i></i>Chưa trả lời</span>
      <span><i class="lg-flagged"></i>Đã đánh dấu xem lại</span>
      <span><i class="lg-current"></i>Câu hiện tại</span>
    </div>
    <div class="progress-summary" data-progress-summary>Đã làm: 0/0 câu</div>
    <button type="button" class="btn btn-primary" id="drawer-submit-btn" style="width:100%">Nộp bài</button>
  </div>
</div>

<!-- ===== SUBMIT CONFIRM MODAL ===== -->
<div class="modal-overlay" id="submit-modal" role="dialog" aria-modal="true">
  <div class="modal-sheet" id="submit-modal-body"></div>
</div>

<!-- ===== VIOLATION LOG MODAL ===== -->
<div class="modal-overlay" id="log-modal" role="dialog" aria-modal="true">
  <div class="modal-sheet" id="log-modal-body"></div>
</div>

<!-- ===== SUBMITTED SCREEN ===== -->
<div class="submitted-wrap" id="submitted-screen" hidden>
  <div class="submitted-card">
    <div class="submitted-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    </div>
    <div class="submitted-title">Đã nộp bài thành công!</div>
    <div class="submitted-sub" id="submitted-sub"></div>
    <div class="submitted-stats">
      <div class="submitted-stat"><b id="submitted-count">0/0</b><span>Câu đã trả lời</span></div>
      <div class="submitted-stat"><b id="submitted-time">--:--</b><span>Thời gian nộp bài</span></div>
    </div>
    <button type="button" class="btn btn-primary" id="view-result-btn" style="width:100%">Xem kết quả</button>
  </div>
</div>

<script>
(function(){
  "use strict";

  var ICONS = {
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.5 18a1.8 1.8 0 0 0 1.5 2.7h16a1.8 1.8 0 0 0 1.5-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0Z"/></svg>',
    flag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v18"/><path d="M6 4h11l-2.5 4L17 12H6"/></svg>',
    shieldSmall: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3Z"/></svg>'
  };

  var pad = function(n){ return String(n).padStart(2,'0'); };
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ============ EXAM DATA ============
  var EXAM_META = <?php echo $jsExam; ?>;
  var IDLE_THRESHOLD_SEC = 45; // ngưỡng "ở quá lâu 1 câu"

  var QUESTIONS = (<?php echo $jsQuestions; ?> || []).map(function(q, i){
    return {
      index: i,
      text: q.question || '',
      options: q.options || [],
      type: q.type || 'single',
      items: q.items || null,
      image: q.image || ''
    };
  });

  var SAVE_KEY = 'cvd_exam_' + (EXAM_META.examId || 'unknown');

  var state = {
    screen:'gate', current:0, answers:{}, flags:new Set(),
    totalSeconds: (parseInt(EXAM_META.timeLimit,10) || 45) * 60,
    startedAt: null, violations:0, violationLog:[],
    idleWarned:new Set(), questionEnteredAt: Date.now(),
    timerId:null, idleIntervalId:null, aiIntervalId:null, fullscreenActive:false,
    submitting:false
  };
  var cameraStream = null;
  var cameraReady = false; // true | 'skipped' | false

  // ============ THEME ============
  function setTheme(theme){
    document.body.dataset.theme = theme;
    document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
      btn.setAttribute('aria-pressed', String(btn.dataset.themeBtn === theme));
    });
  }
  (function initTheme(){
    var saved = null;
    try { saved = localStorage.getItem('eduvn_student_theme_v2'); } catch(e) {}
    if(saved === 'cute' || saved === 'elegant') setTheme(saved);
  })();
  document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
    btn.addEventListener('click', function(){
      setTheme(btn.dataset.themeBtn);
      try { localStorage.setItem('eduvn_student_theme_v2', btn.dataset.themeBtn); } catch(e) {}
    });
  });

  // ============ AI GIÁM SÁT (khuôn mặt + điện thoại) ============
  var AI_SCRIPTS = [
    'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs',
    'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface',
    'https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd'
  ];
  var aiState = {
    ready:false, faceModel:null, objModel:null,
    lastFaceViolationAt:0, lastPhoneViolationAt:0, cooldownMs:20000
  };

  function loadScript(src){
    return new Promise(function(resolve, reject){
      var s = document.createElement('script');
      s.src = src; s.async = true;
      s.onload = function(){ resolve(); };
      s.onerror = function(){ reject(new Error('Không tải được '+src)); };
      document.head.appendChild(s);
    });
  }
  function setAiStatus(status){
    var el = document.getElementById('ai-status');
    if(!el) return;
    el.hidden = false;
    if(status==='loading'){ el.className='ai-status loading'; el.textContent='Đang tải AI giám sát…'; }
    else if(status==='ready'){ el.className='ai-status ready'; el.textContent='AI giám sát: khuôn mặt & điện thoại — sẵn sàng'; }
    else { el.className='ai-status off'; el.textContent='AI giám sát: không khả dụng trong môi trường này (vẫn giám sát bằng cách khác)'; }
  }
  function initAI(){
    setAiStatus('loading');
    loadScript(AI_SCRIPTS[0])
      .then(function(){ return Promise.all([loadScript(AI_SCRIPTS[1]), loadScript(AI_SCRIPTS[2])]); })
      .then(function(){ return Promise.all([window.blazeface.load(), window.cocoSsd.load()]); })
      .then(function(models){
        aiState.faceModel = models[0];
        aiState.objModel = models[1];
        aiState.ready = true;
        setAiStatus('ready');
      })
      .catch(function(err){
        console.warn('AI giám sát không khả dụng:', err);
        setAiStatus('off');
      });
  }
  initAI();

  function captureSnapshot(video){
    try{
      var canvas = document.getElementById('ai-snapshot-canvas');
      canvas.width = 240; canvas.height = 180;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, 240, 180);
      return canvas.toDataURL('image/jpeg', 0.6);
    }catch(e){ return null; }
  }

  function runAiScan(){
    if(state.screen !== 'exam' || !aiState.ready || !cameraStream) return;
    var video = document.getElementById('camera-pip-video');
    if(!video || video.readyState < 2) return;
    var now = Date.now();

    aiState.faceModel.estimateFaces(video, false).then(function(faces){
      if(faces.length === 0 && now - aiState.lastFaceViolationAt > aiState.cooldownMs){
        aiState.lastFaceViolationAt = now;
        registerViolation('AI giám sát: không phát hiện khuôn mặt trong khung hình.', captureSnapshot(video));
      } else if(faces.length > 1 && now - aiState.lastFaceViolationAt > aiState.cooldownMs){
        aiState.lastFaceViolationAt = now;
        registerViolation('AI giám sát: phát hiện nhiều hơn một khuôn mặt trong khung hình.', captureSnapshot(video));
      }
    }).catch(function(){ /* bỏ qua lỗi từng lượt quét, không làm gián đoạn bài thi */ });

    aiState.objModel.detect(video).then(function(objects){
      var hasPhone = objects.some(function(o){ return o.class === 'cell phone' && o.score > 0.5; });
      if(hasPhone && now - aiState.lastPhoneViolationAt > aiState.cooldownMs){
        aiState.lastPhoneViolationAt = now;
        registerViolation('AI giám sát: phát hiện có điện thoại trong khung hình.', captureSnapshot(video));
      }
    }).catch(function(){ /* bỏ qua lỗi từng lượt quét */ });
  }

  // ============ TOAST ============
  function showToast(message){
    var wrap = document.getElementById('toast-wrap');
    var el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = ICONS.warning + '<span>'+message+'</span>';
    wrap.appendChild(el);
    requestAnimationFrame(function(){ el.classList.add('show'); });
    setTimeout(function(){
      el.classList.remove('show');
      setTimeout(function(){ el.remove(); }, 300);
    }, 4200);
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
    if(ev.key === 'Escape'){
      document.querySelectorAll('.modal-overlay.open').forEach(function(o){ closeModal(o.id); });
    }
  });
  wireModalCloseButtons(document);

  // ============ CAMERA CHECK (gate) ============
  var cameraBtn = document.getElementById('camera-btn');
  cameraBtn.addEventListener('click', function(){
    cameraBtn.disabled = true;
    cameraBtn.textContent = 'Đang yêu cầu quyền camera…';
    if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
      document.getElementById('camera-hint').textContent = 'Trình duyệt này không hỗ trợ camera, hoặc trang chưa chạy trên kết nối an toàn (HTTPS).';
      cameraBtn.disabled = false; cameraBtn.textContent = 'Thử lại';
      return;
    }
    navigator.mediaDevices.getUserMedia({ video:{ width:320, height:240 }, audio:false }).then(function(stream){
      cameraStream = stream;
      var video = document.getElementById('camera-video');
      video.srcObject = stream;
      document.getElementById('camera-preview').classList.add('active');
      document.getElementById('camera-placeholder').hidden = true;
      document.getElementById('camera-status').hidden = false;
      document.getElementById('camera-hint').textContent = 'Camera đã sẵn sàng — hệ thống sẽ giám sát trong suốt quá trình làm bài.';
      cameraBtn.textContent = 'Camera đã bật';
      cameraReady = true;
      updateStartButtonState();
      stream.getVideoTracks()[0].addEventListener('ended', function(){
        cameraReady = false;
        if(state.screen==='exam' && !state.submitting){ registerViolation('Camera giám sát đã bị tắt hoặc mất kết nối trong khi làm bài.'); }
      });
    }).catch(function(err){
      document.getElementById('camera-hint').textContent = 'Không thể truy cập camera (' + (err && err.name ? err.name : 'lỗi không xác định') + '). Vui lòng cấp quyền camera cho trình duyệt/thiết bị rồi thử lại.';
      cameraBtn.disabled = false; cameraBtn.textContent = 'Thử lại';
    });
  });
  document.getElementById('camera-skip').addEventListener('click', function(){
    cameraReady = 'skipped';
    updateStartButtonState();
    document.getElementById('camera-hint').textContent = 'Đã bỏ qua yêu cầu camera. Hệ thống vẫn giám sát bằng các cách khác (toàn màn hình, chuyển tab, thời gian mỗi câu).';
  });
  document.getElementById('agree-check').addEventListener('change', updateStartButtonState);
  function updateStartButtonState(){
    var agree = document.getElementById('agree-check').checked;
    document.getElementById('start-exam-btn').disabled = !(agree && cameraReady);
  }

  // ============ FULLSCREEN ============
  function tryEnterFullscreen(){
    var el = document.documentElement;
    var req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
    if(req){ try{ req.call(el); }catch(e){ /* có thể bị chặn trong khung xem trước (iframe) */ } }
  }
  document.addEventListener('fullscreenchange', function(){
    var isFs = !!document.fullscreenElement;
    if(state.screen==='exam' && !state.submitting && !isFs && state.fullscreenActive){
      document.getElementById('exit-banner').classList.add('show');
      registerViolation('Em đã thoát chế độ toàn màn hình.');
    } else if(isFs){
      document.getElementById('exit-banner').classList.remove('show');
    }
    state.fullscreenActive = isFs;
  });
  document.getElementById('reenter-fullscreen-btn').addEventListener('click', tryEnterFullscreen);

  // ============ VIOLATIONS ============
  function registerViolation(msg, image){
    if(state.screen !== 'exam' || state.submitting) return;
    state.violations++;
    var now = new Date();
    state.violationLog.push({ time: pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds()), msg: msg, image: image || null });
    var badge = document.getElementById('violation-badge');
    badge.hidden = false;
    badge.innerHTML = ICONS.warning + '<span>'+state.violations+' vi phạm</span>';
    if(state.violations>=3) badge.classList.add('critical');
    showToast(msg + ' (Vi phạm thứ '+state.violations+')');
    saveExamData();
    if(state.violations >= EXAM_META.maxViolations){
      showToast('Em đã vi phạm '+EXAM_META.maxViolations+' lần. Bài thi sẽ tự động nộp.');
      setTimeout(function(){ doSubmit(); }, 800);
    }
  }
  document.getElementById('violation-badge').addEventListener('click', openLogModal);
  function openLogModal(){
    var body = document.getElementById('log-modal-body');
    var rows = state.violationLog.length
      ? state.violationLog.map(function(v){
          var isAi = v.msg.indexOf('AI giám sát')===0;
          var thumb = v.image ? '<img class="log-thumb" src="'+v.image+'" alt="Ảnh bằng chứng">' : '';
          return '<div class="log-row">' + thumb +
            '<div class="log-body">' +
              (isAi ? '<span class="log-ai-tag">AI PHÁT HIỆN</span><br>' : '') +
              '<span class="log-time">'+v.time+'</span>' +
              '<div class="log-msg">'+v.msg+'</div>' +
            '</div>' +
          '</div>';
        }).join('')
      : '<p style="font-size:13px;color:var(--ink-soft)">Chưa có hoạt động bất thường nào được ghi nhận.</p>';
    body.innerHTML =
      '<div class="modal-head"><div class="modal-title">Nhật ký giám sát</div><button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button></div>' +
      '<p style="font-size:12px;color:var(--ink-soft);margin-bottom:12px">Toàn bộ hoạt động bất thường được ghi lại (kèm ảnh nếu do AI phát hiện) và gửi cùng bài thi để giáo viên xem xét.</p>' +
      '<div>'+rows+'</div>' +
      '<div class="modal-actions" style="margin-top:14px"><button type="button" class="btn btn-ghost" data-close-modal style="flex:1">Đóng</button></div>';
    wireModalCloseButtons(body);
    openModal('log-modal');
  }

  // tab switch / chuyển ứng dụng
  document.addEventListener('visibilitychange', function(){
    if(document.hidden && state.screen==='exam' && !state.submitting){
      registerViolation('Em vừa rời khỏi tab hoặc chuyển sang ứng dụng khác.');
    }
  });

  // phím F11
  document.addEventListener('keydown', function(ev){
    if(state.screen==='exam' && !state.submitting && (ev.key==='F11' || ev.keyCode===122)){
      try{ ev.preventDefault(); }catch(e){}
      registerViolation('Em vừa nhấn phím F11. Hãy dùng nút toàn màn hình của hệ thống.');
    }
  });

  // chặn chuột phải / sao chép / phím tắt trong lúc thi
  document.addEventListener('contextmenu', function(ev){ if(state.screen==='exam') ev.preventDefault(); });
  document.addEventListener('copy', function(ev){
    if(state.screen==='exam'){ ev.preventDefault(); showToast('Đã tắt sao chép nội dung trong quá trình làm bài.'); }
  });
  document.addEventListener('keydown', function(ev){
    if(state.screen!=='exam') return;
    if(ev.ctrlKey && ['u','s','a','c','v','p'].indexOf(ev.key.toLowerCase()) !== -1) ev.preventDefault();
    if(ev.key === 'F12' || (ev.ctrlKey && ev.shiftKey && ev.key.toLowerCase()==='i')) ev.preventDefault();
    if(ev.key === 'Escape'){ ev.preventDefault(); return false; }
  });

  // cảnh báo trước khi rời/tải lại trang
  window.addEventListener('beforeunload', function(ev){
    if(state.screen==='exam' && !state.submitting){ ev.preventDefault(); ev.returnValue=''; }
  });

  // chặn nút Back trong lúc thi
  history.pushState(null, null, location.href);
  window.onpopstate = function(){
    history.pushState(null, null, location.href);
    if(state.screen==='exam' && !state.submitting){
      registerViolation('Em vừa sử dụng nút Back trong lúc làm bài.');
    }
  };

  // dừng quá lâu ở một câu chưa trả lời
  state.idleIntervalId = setInterval(function(){
    if(state.screen !== 'exam' || state.submitting) return;
    var q = QUESTIONS[state.current];
    if(!q) return;
    if(state.answers[q.index] !== undefined) return;
    var elapsed = (Date.now() - state.questionEnteredAt) / 1000;
    if(elapsed >= IDLE_THRESHOLD_SEC && !state.idleWarned.has(q.index)){
      state.idleWarned.add(q.index);
      registerViolation('Em đang dành khá nhiều thời gian cho câu '+(state.current+1)+' mà chưa trả lời — có thể là dấu hiệu tra cứu đáp án bên ngoài.');
    }
  }, 5000);

  // ============ PERSISTENCE (resume after refresh) ============
  function saveExamData(){
    try{
      localStorage.setItem(SAVE_KEY, JSON.stringify({
        startedAt: state.startedAt,
        answers: state.answers,
        flags: Array.from(state.flags),
        violations: state.violations,
        violationLog: state.violationLog,
        current: state.current,
        idleWarned: Array.from(state.idleWarned)
      }));
    }catch(e){}
  }
  function clearSavedExamData(){
    try{ localStorage.removeItem(SAVE_KEY); }catch(e){}
  }

  function nowRemaining(){
    if(!state.startedAt) return state.totalSeconds;
    var elapsed = Math.floor((Date.now() - state.startedAt) / 1000);
    return Math.max(0, state.totalSeconds - elapsed);
  }

  // ============ TIMER ============
  function formatTime(sec){
    var m = Math.floor(sec/60), s = sec%60;
    return pad(m)+':'+pad(s);
  }
  function tick(){
    var rem = nowRemaining();
    document.getElementById('timer-text').textContent = formatTime(rem);
    var pill = document.getElementById('timer-pill');
    if(rem<=300 && rem>60){ pill.classList.add('warning'); pill.classList.remove('critical'); }
    else if(rem<=60){ pill.classList.remove('warning'); pill.classList.add('critical'); }
    else { pill.classList.remove('warning'); pill.classList.remove('critical'); }
    if(rem<=0){
      clearInterval(state.timerId);
      autoSubmit();
    }
  }

  // ============ MATHJAX ============
  function typesetMath(el){
    setTimeout(function(){
      if(window.MathJax && MathJax.typesetPromise){
        MathJax.typesetPromise([el]).catch(function(){});
      }
    }, 0);
  }

  // ============ START EXAM ============
  function beginExam(entry){
    state.screen = 'exam';
    document.getElementById('gate-screen').hidden = true;
    document.getElementById('exam-screen').hidden = false;
    if(entry !== 'resume'){ tryEnterFullscreen(); }

    if(cameraStream){
      var pip = document.getElementById('camera-pip');
      var pipVideo = document.getElementById('camera-pip-video');
      pipVideo.srcObject = cameraStream;
      pip.hidden = false;
      if(aiState.ready){ state.aiIntervalId = setInterval(runAiScan, 4000); }
    }

    if(!state.startedAt) state.startedAt = Date.now();
    state.questionEnteredAt = Date.now();
    document.getElementById('timer-text').textContent = formatTime(nowRemaining());
    clearInterval(state.timerId);
    state.timerId = setInterval(tick, 1000);
    renderQuestion();
    renderNavGrid();
    saveExamData();
  }

  document.getElementById('start-exam-btn').addEventListener('click', function(){
    clearSavedExamData();
    state.startedAt = null;
    state.answers = {};
    state.flags = new Set();
    state.violations = 0;
    state.violationLog = [];
    state.current = 0;
    state.idleWarned = new Set();
    state.questionEnteredAt = Date.now();
    beginExam('gate');
  });

  document.getElementById('resume-exam-btn').addEventListener('click', function(){
    attemptResume();
  });

  // ============ QUESTION RENDER ============
  function goTo(index){
    state.current = index;
    state.questionEnteredAt = Date.now();
    renderQuestion();
    renderNavGrid();
    saveExamData();
  }

  function renderQuestion(){
    var q = QUESTIONS[state.current];
    var card = document.getElementById('question-card');
    var letters = ['A','B','C','D','E','F'];
    var flagged = state.flags.has(q.index);
    var isMulti = q.type === 'multiple';
    var isTfm = q.type === 'true_false_multiple';
    var isEssay = q.type === 'essay';
    var typeTag = isMulti ? '<span class="q-type-tag">Nhiều đáp án</span>'
      : isTfm ? '<span class="q-type-tag">Đúng/Sai nhiều ý</span>'
      : isEssay ? '<span class="q-type-tag">Tự luận</span>'
      : (EXAM_META.formLabel && EXAM_META.formLabel.indexOf('+') !== -1 ? '<span class="q-type-tag">Trắc nghiệm</span>' : '');

    var bodyHtml = '';
    if (isTfm) {
      var tfAns = state.answers[q.index] || [];
      bodyHtml =
        '<p class="tfm-hint">Đọc kỹ các ý sau và chọn <strong>Đúng</strong> hoặc <strong>Sai</strong> cho từng ý.</p>' +
        '<div class="tfm-items">' +
          (q.items || []).map(function(it, j){
            var v = tfAns[j];
            return '<div class="tfm-row">' +
              '<span class="tfm-letter">'+(it.label || String.fromCharCode(97+j))+'</span>' +
              '<span class="tfm-statement">'+it.statement+'</span>' +
              '<span class="tfm-toggles">' +
                '<label class="tfm-toggle'+(v===true?' on-yes':'')+'"><input type="radio" name="q-'+q.index+'-i-'+j+'" value="yes"'+(v===true?' checked':'')+'><span>Đúng</span></label>' +
                '<label class="tfm-toggle'+(v===false?' on-no':'')+'"><input type="radio" name="q-'+q.index+'-i-'+j+'" value="no"'+(v===false?' checked':'')+'><span>Sai</span></label>' +
              '</span>' +
            '</div>';
          }).join('') +
        '</div>';
    } else if (isEssay) {
      var essayVal = typeof state.answers[q.index] === 'string' ? state.answers[q.index] : '';
      bodyHtml =
        '<p class="tfm-hint">Trình bày bài làm của em trong khung bên dưới (câu trả lời sẽ được giáo viên chấm).</p>' +
        '<textarea id="essay-input" class="essay-input" rows="9" placeholder="Nhập bài làm...">'+essayVal.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</textarea>';
    } else {
      bodyHtml =
        '<div class="q-options">' +
          (q.options||[]).map(function(opt,i){
            var selected = isMulti
              ? (state.answers[q.index] || []).indexOf(i) !== -1
              : state.answers[q.index] === i;
            var inputType = isMulti ? 'checkbox' : 'radio';
            return '<label class="exam-option'+(selected?' selected':'')+'">' +
              '<input type="'+inputType+'" class="exam-option-input" name="q-'+q.index+'" value="'+i+'"'+(selected?' checked':'')+'>' +
              '<span class="opt-letter">'+letters[i]+'</span><span class="opt-text">'+opt+'</span>' +
            '</label>';
          }).join('') +
        '</div>';
    }

    card.innerHTML =
      '<div class="q-head">' +
        '<span class="q-number">CÂU '+(state.current+1)+' / '+QUESTIONS.length+typeTag+'</span>' +
        '<button type="button" class="flag-btn'+(flagged?' active':'')+'" id="flag-btn">'+ICONS.flag+(flagged?'Đã đánh dấu':'Đánh dấu xem lại')+'</button>' +
      '</div>' +
      '<div class="q-text">'+q.text+(q.image ? '<img class="q-image" src="'+q.image+'" alt="Hình minh họa câu hỏi">' : '')+'</div>' +
      bodyHtml +
      '<div class="q-nav-row">' +
        '<button type="button" class="btn btn-ghost" id="prev-btn-desktop"'+(state.current===0?' disabled':'')+'>← Câu trước</button>' +
        '<button type="button" class="btn btn-ghost" id="next-btn-desktop"'+(state.current===QUESTIONS.length-1?' disabled':'')+'>Câu tiếp →</button>' +
      '</div>';

    if (isTfm) {
      card.querySelectorAll('.tfm-toggle input').forEach(function(input){
        input.addEventListener('change', function(){
          var parts = input.name.split('-i-');
          var j = parseInt(parts[1], 10);
          var arr = Array.isArray(state.answers[q.index]) ? state.answers[q.index].slice() : new Array((q.items||[]).length).fill(null);
          arr[j] = (input.value === 'yes');
          state.answers[q.index] = arr;
          var row = input.closest('.tfm-row');
          row.querySelectorAll('.tfm-toggle').forEach(function(t){ t.classList.remove('on-yes','on-no'); });
          input.closest('.tfm-toggle').classList.add(input.value === 'yes' ? 'on-yes' : 'on-no');
          renderNavGrid();
          saveExamData();
        });
      });
    } else if (isEssay) {
      var ta = document.getElementById('essay-input');
      ta.addEventListener('input', function(){
        var v = ta.value;
        if (v.trim().length) { state.answers[q.index] = v; } else { delete state.answers[q.index]; }
        saveExamData();
        clearTimeout(ta._navT);
        ta._navT = setTimeout(renderNavGrid, 400);
      });
    } else {
      card.querySelectorAll('.exam-option-input').forEach(function(input){
        input.addEventListener('change', function(){
          var oi = parseInt(input.value,10);
          if(isMulti){
            var arr = (state.answers[q.index] || []).slice();
            var idx = arr.indexOf(oi);
            if(idx >= 0){ arr.splice(idx, 1); } else { arr.push(oi); }
            if(arr.length){ state.answers[q.index] = arr; } else { delete state.answers[q.index]; }
            input.closest('.exam-option').classList.toggle('selected', arr.indexOf(oi) !== -1);
          } else {
            state.answers[q.index] = oi;
            card.querySelectorAll('.exam-option').forEach(function(lbl){ lbl.classList.remove('selected'); });
            input.closest('.exam-option').classList.add('selected');
          }
          renderNavGrid();
          saveExamData();
        });
      });
    }
    document.getElementById('flag-btn').addEventListener('click', function(){
      if(state.flags.has(q.index)) state.flags.delete(q.index); else state.flags.add(q.index);
      renderQuestion();
      renderNavGrid();
      saveExamData();
    });
    var prevD = document.getElementById('prev-btn-desktop');
    var nextD = document.getElementById('next-btn-desktop');
    if(prevD) prevD.addEventListener('click', function(){ if(state.current>0) goTo(state.current-1); });
    if(nextD) nextD.addEventListener('click', function(){ if(state.current<QUESTIONS.length-1) goTo(state.current+1); });

    document.getElementById('prev-btn').disabled = state.current===0;
    document.getElementById('next-btn').disabled = state.current===QUESTIONS.length-1;
    typesetMath(card);
    window.scrollTo({top:0, behavior: reduceMotion ? 'auto' : 'smooth'});
  }

  document.getElementById('prev-btn').addEventListener('click', function(){ if(state.current>0) goTo(state.current-1); });
  document.getElementById('next-btn').addEventListener('click', function(){ if(state.current<QUESTIONS.length-1) goTo(state.current+1); });

  // ============ NAV GRID ============
  function isAnswered(idx){
    var a = state.answers[idx];
    var q = QUESTIONS[idx];
    if (a === undefined || a === null) return false;
    if (q && q.type === 'true_false_multiple') {
      return Array.isArray(a) && q.items && a.length === q.items.length
        && a.every(function(v){ return v === true || v === false; });
    }
    if (q && q.type === 'essay') {
      return typeof a === 'string' && a.trim().length > 0;
    }
    if(Array.isArray(a)) return a.length > 0;
    if(typeof a === 'string') return a.trim().length > 0;
    return true;
  }
  function renderNavGrid(){
    var answeredCount = QUESTIONS.filter(function(q){ return isAnswered(q.index); }).length;
    ['nav-grid-desktop','nav-grid-mobile'].forEach(function(id){
      var el = document.getElementById(id);
      if(!el) return;
      el.innerHTML = QUESTIONS.map(function(q,i){
        var cls = 'nav-cell';
        if(isAnswered(q.index)) cls += ' answered';
        if(state.flags.has(q.index)) cls += ' flagged';
        if(i===state.current) cls += ' current';
        return '<button type="button" class="'+cls+'" data-goto="'+i+'">'+(i+1)+'</button>';
      }).join('');
      el.querySelectorAll('[data-goto]').forEach(function(btn){
        btn.addEventListener('click', function(){
          goTo(parseInt(btn.dataset.goto,10));
          closeModal('nav-drawer');
        });
      });
    });
    document.querySelectorAll('[data-progress-summary]').forEach(function(el){
      el.textContent = 'Đã làm: '+answeredCount+'/'+QUESTIONS.length+' câu';
    });
    document.getElementById('fab-badge').textContent = answeredCount+'/'+QUESTIONS.length;
  }

  document.getElementById('open-drawer-btn').addEventListener('click', function(){ openModal('nav-drawer'); });

  // ============ SUBMIT FLOW ============
  function openSubmitModal(){
    var answeredCount = QUESTIONS.filter(function(q){ return isAnswered(q.index); }).length;
    var unanswered = [];
    QUESTIONS.forEach(function(q,i){ if(!isAnswered(q.index)) unanswered.push(i+1); });
    var body = document.getElementById('submit-modal-body');
    body.innerHTML =
      '<div class="modal-head"><div class="modal-title">Xác nhận nộp bài</div><button type="button" class="modal-close" data-close-modal aria-label="Đóng">'+ICONS.close+'</button></div>' +
      '<p style="font-size:13.5px;margin-bottom:12px">Em đã trả lời <strong>'+answeredCount+'/'+QUESTIONS.length+'</strong> câu.</p>' +
      (unanswered.length
        ? '<p style="font-size:13px;color:#D14343;margin-bottom:16px">Còn '+unanswered.length+' câu chưa trả lời: Câu '+unanswered.join(', ')+'.</p>'
        : '<p style="font-size:13px;color:#0E8F79;margin-bottom:16px">Em đã hoàn thành tất cả các câu hỏi.</p>') +
      '<div class="modal-actions"><button type="button" class="btn btn-ghost" data-close-modal>Tiếp tục làm bài</button><button type="button" class="btn btn-primary" id="confirm-submit-btn">Nộp bài</button></div>';
    wireModalCloseButtons(body);
    document.getElementById('confirm-submit-btn').addEventListener('click', function(){
      closeModal('submit-modal');
      doSubmit();
    });
    openModal('submit-modal');
  }
  document.getElementById('open-submit-btn').addEventListener('click', openSubmitModal);
  document.getElementById('open-submit-btn-2').addEventListener('click', openSubmitModal);
  document.getElementById('drawer-submit-btn').addEventListener('click', function(){ closeModal('nav-drawer'); openSubmitModal(); });

  function autoSubmit(){
    showToast('Hết giờ làm bài! Bài thi đã được tự động nộp.');
    doSubmit();
  }

  function stopMedia(){
    clearInterval(state.timerId);
    clearInterval(state.idleIntervalId);
    clearInterval(state.aiIntervalId);
    state.fullscreenActive = false;
    var banner = document.getElementById('exit-banner');
    if(banner) banner.classList.remove('show');
    if(document.fullscreenElement && document.exitFullscreen){ try{ document.exitFullscreen(); }catch(e){} }
    if(cameraStream){ cameraStream.getTracks().forEach(function(t){ t.stop(); }); }
    var pip = document.getElementById('camera-pip');
    if(pip) pip.hidden = true;
  }

  function showSubmitted(result){
    state.screen = 'submitted';
    document.getElementById('exam-screen').hidden = true;
    document.getElementById('submitted-screen').hidden = false;
    var answeredCount = QUESTIONS.filter(function(q){ return isAnswered(q.index); }).length;
    document.getElementById('submitted-count').textContent = answeredCount+'/'+QUESTIONS.length;
    var now = new Date();
    document.getElementById('submitted-time').textContent = pad(now.getHours())+':'+pad(now.getMinutes());
    document.getElementById('submitted-sub').innerHTML =
      (EXAM_META.testName || '') + ' · Lớp ' + (EXAM_META.classCode || '') + '<br>' +
      'Kết quả sẽ được hiển thị ngay sau đây.';
    var resultUrl = 'result.php?exam_id=' + encodeURIComponent(result.exam_id);
    document.getElementById('view-result-btn').onclick = function(){ window.location.href = resultUrl; };
    setTimeout(function(){ window.location.href = resultUrl; }, 3000);
  }

  function doSubmit(){
    if(state.submitting) return;
    state.submitting = true;
    stopMedia();
    clearSavedExamData();

    fetch('api/submit_exam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        exam_id: EXAM_META.examId,
        answers: state.answers,
        violations: state.violations || 0,
        violation_log: (state.violationLog || []).map(function(v){ return v.msg; })
      })
    }).then(function(resp){
      if(!resp.ok) throw new Error('HTTP ' + resp.status);
      return resp.json();
    }).then(function(result){
      if(result && result.success){
        showSubmitted(result);
      } else {
        showToast('Lỗi nộp bài: ' + (result && result.message ? result.message : 'Không rõ'), 6000);
        state.submitting = false;
        resumeAfterFailedSubmit();
      }
    }).catch(function(err){
      console.error('Lỗi nộp bài:', err);
      showToast('Lỗi kết nối khi nộp bài. Vui lòng liên hệ giáo viên.', 6000);
      state.submitting = false;
      resumeAfterFailedSubmit();
    });
  }

  function resumeAfterFailedSubmit(){
    // Khôi phục từ localStorage để không mất câu trả lời, quay lại màn hình thi.
    try {
      var saved = JSON.parse(localStorage.getItem(SAVE_KEY));
      if(saved){
        state.answers = saved.answers || {};
        state.flags = new Set(saved.flags || []);
        state.violations = saved.violations || 0;
        state.violationLog = saved.violationLog || [];
        state.current = saved.current || 0;
        state.idleWarned = new Set(saved.idleWarned || []);
        state.startedAt = saved.startedAt || Date.now();
        state.submitting = false;
        state.timerId = setInterval(tick, 1000);
        state.idleIntervalId = setInterval(function(){
          if(state.screen !== 'exam') return;
          var q = QUESTIONS[state.current];
          if(!q) return;
          if(state.answers[q.index] !== undefined) return;
          var elapsed = (Date.now() - state.questionEnteredAt) / 1000;
          if(elapsed >= IDLE_THRESHOLD_SEC && !state.idleWarned.has(q.index)){
            state.idleWarned.add(q.index);
            registerViolation('Em đang dành khá nhiều thời gian cho câu '+(state.current+1)+' mà chưa trả lời — có thể là dấu hiệu tra cứu đáp án bên ngoài.');
          }
        }, 5000);
        document.getElementById('exam-screen').hidden = false;
        renderQuestion();
        renderNavGrid();
      } else {
        window.location.reload();
      }
    } catch(e){
      window.location.reload();
    }
  }

  // ============ GATE META + RESUME ============
  function fillGateMeta(){
    document.getElementById('gate-title').textContent = EXAM_META.testName || '';
    document.getElementById('gate-sub').textContent = 'Lớp ' + (EXAM_META.classCode || '') + ' · ' + (EXAM_META.subjectName || '');
    document.getElementById('gate-time').textContent = EXAM_META.timeLimit + ' phút';
    document.getElementById('gate-count').textContent = QUESTIONS.length + ' câu trắc nghiệm';
    document.getElementById('gate-form').textContent = EXAM_META.formLabel || '';
    document.getElementById('gate-student').textContent = (EXAM_META.studentName || '') + ' (' + (EXAM_META.studentCode || '') + ')';
    document.getElementById('brand-title').textContent = EXAM_META.testName || '';
    document.getElementById('brand-sub').textContent = 'Lớp ' + (EXAM_META.classCode || '') + ' · ' + (EXAM_META.studentName || '');
  }

  function getSavedExamData(){
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(SAVE_KEY)); } catch(e){ saved = null; }
    if(!saved || !saved.startedAt) return null;
    var elapsed = Math.floor((Date.now() - saved.startedAt) / 1000);
    if(elapsed >= state.totalSeconds){ clearSavedExamData(); return null; }
    return saved;
  }

  function attemptResume(){
    var saved = getSavedExamData();
    if(!saved) return false;
    state.answers = saved.answers || {};
    state.flags = new Set(saved.flags || []);
    state.violations = saved.violations || 0;
    state.violationLog = saved.violationLog || [];
    state.current = saved.current || 0;
    state.idleWarned = new Set(saved.idleWarned || []);
    state.startedAt = saved.startedAt;
    beginExam('resume');
    showToast('Đã khôi phục bài thi đang làm dở. Còn ' + formatTime(nowRemaining()) + ' thời gian.');
    return true;
  }

  fillGateMeta();
  var savedExam = getSavedExamData();
  if(savedExam){
    var remaining = Math.max(0, state.totalSeconds - Math.floor((Date.now() - savedExam.startedAt) / 1000));
    document.getElementById('resume-text').textContent = 'Em có bài thi đang làm dở. Còn ' + formatTime(remaining) + ' thời gian.';
    document.getElementById('resume-box').hidden = false;
  }

})();
</script>
</body>
</html>
