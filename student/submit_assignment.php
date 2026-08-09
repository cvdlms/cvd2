<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: ../index.php?role=student');
    exit;
}

require_once __DIR__ . '/../includes/student_gender.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? $studentClass;

$assignmentId = $_GET['id'] ?? '';
if (empty($assignmentId)) {
    header('Location: assignments.php');
    exit;
}

// Load assignment
$assignmentsFile = __DIR__ . '/../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;

function normalizeClassNames($assignment) {
    $raw = $assignment['class_names'] ?? $assignment['class_name'] ?? [];
    if (is_string($raw)) {
        $raw = [$raw];
    }
    $normalized = [];
    if (is_array($raw)) {
        foreach ($raw as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }
    }
    return array_values(array_unique($normalized));
}

foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId) {
        $classNames = normalizeClassNames($a);
        $myClass = trim(strtolower($studentClass));
        foreach ($classNames as $className) {
            if (trim(strtolower($className)) === $myClass) {
                $assignment = $a;
                break 2;
            }
        }
    }
}

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

// Teacher name from user.json
$usersFile = __DIR__ . '/../admin/user.json';
$users = json_decode(file_get_contents($usersFile), true) ?: [];
$teacherName = $users[$assignment['teacher_username'] ?? '']['fullname'] ?? 'Giáo viên bộ môn';

// Existing submission (single submission per student + assignment)
$submissionsFile = __DIR__ . '/../data/student_submissions.json';
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];
$existing = null;
foreach ($submissions as $sub) {
    if ($sub['assignment_id'] === $assignmentId && $sub['student_code'] === $studentCode) {
        $existing = $sub;
        break;
    }
}

// Due date / remaining time
$dueDate = new DateTime($assignment['due_date']);
$now = new DateTime();
$isLate = $dueDate < $now;
$diff = $now->diff($dueDate);
$remainingText = $isLate
    ? 'Đã quá hạn ' . ($diff->days > 0 ? $diff->days . ' ngày' : 'hôm nay')
    : 'Còn ' . ($diff->days > 0 ? $diff->days . ' ngày ' : '') . $diff->h . ' giờ';

// Classmates for group assignments
$studentsFile = __DIR__ . '/../admin/students.json';
$classesFile = __DIR__ . '/../admin/classes.json';
$studentsData = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
$classesData = file_exists($classesFile) ? json_decode(file_get_contents($classesFile), true) : [];
if (!is_array($studentsData)) $studentsData = [];
if (!is_array($classesData)) $classesData = [];

$classIdByCode = [];
foreach ($classesData as $class) {
    $classIdByCode[strtolower(trim((string)($class['code'] ?? '')))] = (string)($class['id'] ?? '');
    $classIdByCode[strtolower(trim((string)($class['name'] ?? '')))] = (string)($class['id'] ?? '');
}

$currentClassId = $classIdByCode[strtolower(trim((string)$studentClassCode))] ?? $classIdByCode[strtolower(trim((string)$studentClass))] ?? '';
$classmates = array_values(array_filter($studentsData, function($student) use ($currentClassId, $studentClassCode, $studentClass) {
    if ($currentClassId !== '' && (string)($student['class_id'] ?? '') === $currentClassId) {
        return true;
    }
    $studentClassValue = strtolower(trim((string)($student['class_name'] ?? $student['class_code'] ?? '')));
    return $studentClassValue !== '' && in_array($studentClassValue, [
        strtolower(trim((string)$studentClassCode)),
        strtolower(trim((string)$studentClass))
    ], true);
}));

usort($classmates, function($a, $b) {
    return strnatcasecmp($a['name'] ?? '', $b['name'] ?? '');
});

function formatFileSize($bytes) {
    $bytes = intval($bytes);
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024) . ' KB';
    return round($bytes / 1024 / 1024, 1) . ' MB';
}

$maxGroupMembers = max(1, intval($assignment['max_group_members'] ?? 1));
$isGroup = $maxGroupMembers > 1;
$status = $existing ? 'submitted' : ($isLate ? 'late' : 'pending');

$existingSubmissionId = $existing['id'] ?? null;
$occupiedMemberCodes = [];
foreach ($submissions as $sub) {
    if (($sub['assignment_id'] ?? '') !== $assignmentId) continue;
    if ($existingSubmissionId !== null && ($sub['id'] ?? '') === $existingSubmissionId) continue;
    $occupiedMemberCodes[] = trim((string)($sub['student_code'] ?? ''));
    foreach (($sub['group_members'] ?? []) as $gc) {
        $gc = trim((string)$gc);
        if ($gc === '') continue;
        $gcCode = explode(' ', $gc)[0];
        if ($gcCode !== '') $occupiedMemberCodes[] = $gcCode;
    }
}
$occupiedMemberCodes = array_values(array_unique(array_filter($occupiedMemberCodes)));
$groupConflict = in_array($studentCode, $occupiedMemberCodes, true);

$pageData = [
    'id' => $assignmentId,
    'subject' => $subjects[$assignment['subject_id']] ?? $assignment['subject_id'],
    'title' => $assignment['title'],
    'type' => $isGroup ? 'group' : 'individual',
    'status' => $status,
    'teacherName' => $teacherName,
    'assignedDate' => date('d/m/Y', strtotime($assignment['created_at'])),
    'dueDate' => date('d/m/Y H:i', strtotime($assignment['due_date'])),
    'maxScore' => $assignment['max_score'],
    'maxGroupMembers' => $maxGroupMembers,
    'remainingText' => $remainingText,
    'isLate' => $isLate,
    'description' => $assignment['description'] ?? '',
    'teacherFiles' => array_map(function($f) {
        return [
            'name' => $f['original_name'] ?? $f['stored_name'] ?? 'Tệp đính kèm',
            'ext' => strtolower($f['type'] ?? pathinfo($f['stored_name'] ?? $f['original_name'] ?? '', PATHINFO_EXTENSION)),
            'size' => formatFileSize($f['size'] ?? 0),
            'url' => 'api/download_file.php?file=' . urlencode($f['path'] ?? '')
        ];
    }, $assignment['attachments'] ?? []),
    'groupConflict' => $groupConflict,
    'groupMembers' => array_map(function($m) use ($studentCode, $occupiedMemberCodes) {
        $code = (string)($m['code'] ?? '');
        return [
            'code' => $code,
            'name' => (string)($m['name'] ?? ''),
            'isYou' => $code === $studentCode,
            'taken' => in_array($code, $occupiedMemberCodes, true)
        ];
    }, $classmates),
    'existing' => $existing ? [
        'content' => $existing['content'] ?? '',
        'documents' => $existing['documents'] ?? [],
        'images' => $existing['images'] ?? [],
        'submitted_at' => $existing['submitted_at'] ?? '',
        'score' => $existing['score'] ?? null,
        'feedback' => $existing['feedback'] ?? null
    ] : null
];

$stdDesignTheme = getStudentGender($studentCode) === 'Nam' ? 'elegant' : 'cute';
$title = 'Nộp Bài Tập - CVD';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Chi tiết bài tập — Nộp bài</title>
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

  .chip{flex:none; padding:8px 14px; border-radius:var(--radius-pill); font-size:12.5px; font-weight:700;
    background:var(--surface-alt); color:var(--ink-soft); border:1px solid var(--border)}

  /* pill trạng thái (tái dùng bảng màu cute/elegant nhất quán toàn hệ thống) */
  .pill{display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--radius-pill); font-size:11.5px; font-weight:700; flex:none}
  body[data-theme="cute"] .pill-pending{background:#FFE1EC;color:#C41E5C}
  body[data-theme="cute"] .pill-submitted{background:#E7EEFF;color:#2A56C6}
  body[data-theme="cute"] .pill-late{background:#F1EDEA;color:#8A7A76}
  body[data-theme="elegant"] .pill{background:transparent; border:1px solid; font-family:var(--font-mono); font-weight:400}
  body[data-theme="elegant"] .pill-pending{border-color:#C97575;color:#E39B9B}
  body[data-theme="elegant"] .pill-submitted{border-color:var(--secondary);color:#9EC0E3}
  body[data-theme="elegant"] .pill-late{border-color:var(--ink-soft);color:var(--ink-soft)}

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
  .layout{ max-width:1180px; margin:0 auto; padding:18px 16px 48px; display:flex; flex-direction:column; gap:18px; }
  .cols{ display:flex; flex-direction:column; gap:18px; }
  @media (min-width:1024px){
    .layout{ padding:28px 32px 56px; gap:22px; }
    .cols{ display:grid; grid-template-columns: minmax(0,1fr) 320px; gap:24px; align-items:start; }
  }
  .col-main, .col-side{ display:flex; flex-direction:column; gap:18px; min-width:0 }

  .panel-card{background:var(--surface); border:var(--card-border-width) solid var(--border); border-radius:var(--radius-sm);
    box-shadow:var(--shadow-sm); padding:18px}
  .panel-title{font-family:var(--font-display); font-weight:700; font-size:14.5px; margin-bottom:14px; display:flex; align-items:center; gap:8px}
  .panel-title svg{width:16px;height:16px; color:var(--primary)}

  /* ============ HERO ============ */
  .hero-assign{ position:relative; background:var(--surface); border:var(--card-border-width) solid var(--border);
    border-radius:var(--radius-card); box-shadow:var(--shadow); padding:24px 22px; overflow:hidden; }
  body[data-theme="cute"] .hero-assign::before{
    content:""; position:absolute; top:-8px; left:26px; width:60px; height:20px;
    background:repeating-linear-gradient(115deg, var(--tape-2) 0 6px, #d9cfff 6px 12px);
    opacity:.9; transform:rotate(-6deg); border-radius:2px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
  body[data-theme="elegant"] .hero-assign::before, body[data-theme="elegant"] .hero-assign::after{
    content:""; position:absolute; width:16px; height:16px; border-radius:50%; background:var(--bg); top:50%; transform:translateY(-50%); }
  body[data-theme="elegant"] .hero-assign::before{left:-8px} body[data-theme="elegant"] .hero-assign::after{right:-8px}

  .hero-subject{font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); margin-bottom:6px}
  .hero-top-row{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px}
  .hero-title{font-family:var(--font-display); font-weight:700; font-size:20px; line-height:1.3}
  .hero-badges{display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px}

  .hero-meta-row{ display:grid; grid-template-columns:repeat(2,1fr); gap:9px; padding-top:16px; border-top:1px solid var(--border) }
  @media (min-width:640px){ .hero-meta-row{grid-template-columns:repeat(4,1fr)} }
  .hero-meta-item{background:var(--surface-alt); border-radius:var(--radius-sm); padding:10px 12px}
  .hero-meta-label{font-size:10px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px}
  .hero-meta-value{font-weight:700; font-size:13px}
  .hero-meta-value.urgent{color:var(--bad)}

  /* ============ ĐỀ BÀI ============ */
  .desc-text{font-size:13.5px; line-height:1.75; color:var(--ink); white-space:pre-wrap}
  .desc-text b{font-weight:700}

  /* ============ TỆP GV ĐÍNH KÈM ============ */
  .file-list{display:flex; flex-direction:column; gap:10px}
  .file-row{ display:flex; align-items:center; gap:12px; padding:12px; background:var(--surface-alt);
    border:1px solid var(--border); border-radius:var(--radius-sm); }
  .file-icon{ width:40px;height:40px;border-radius:9px;flex:none; display:flex;align-items:center;justify-content:center;
    font-size:10px; font-weight:800; color:#fff; font-family:var(--font-mono); }
  .file-meta{flex:1; min-width:0}
  .file-name{font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap}
  .file-size{font-size:11px; color:var(--ink-soft)}
  .file-dl-btn{ flex:none; width:36px;height:36px;border-radius:var(--radius-sm); background:var(--surface); border:1px solid var(--border);
    display:flex;align-items:center;justify-content:center; color:var(--ink-soft) }
  .file-dl-btn svg{width:16px;height:16px}
  .file-dl-btn:hover{color:var(--primary); border-color:var(--primary)}

  /* ============ NỘP BÀI ============ */
  .modal-label{ font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--ink-soft); margin-bottom:8px; display:block }
  .form-section{margin-bottom:16px}
  .form-section:last-child{margin-bottom:0}
  .form-textarea{ width:100%; min-height:96px; padding:13px; border-radius:var(--radius-sm);
    border:1px solid var(--border); background:var(--surface-alt); color:var(--ink); font-family:var(--font-body); font-size:13.5px; resize:vertical; }
  .form-textarea:focus-visible{outline:2px solid var(--focus); outline-offset:1px}

  .dropzone{ border:2px dashed var(--border); border-radius:var(--radius-sm); padding:26px 14px; text-align:center; cursor:pointer;
    background:var(--surface-alt); transition:border-color .2s ease; }
  .dropzone.dragover{border-color:var(--primary)}
  .dropzone svg{width:28px;height:28px; margin:0 auto 10px; color:var(--ink-soft)}
  .dropzone p{font-size:13px; margin-bottom:3px}
  .dropzone .link{color:var(--primary); font-weight:700}
  .dropzone .hint{font-size:11px; color:var(--ink-soft)}

  .file-chip-list{display:flex; flex-direction:column; gap:8px; margin-top:10px}
  .file-chip{ display:flex; align-items:center; gap:10px; padding:9px 10px; background:var(--surface-alt);
    border:1px solid var(--border); border-radius:var(--radius-sm); }
  .file-chip-icon{ width:32px;height:32px;border-radius:8px;flex:none; display:flex;align-items:center;justify-content:center;
    font-size:9.5px; font-weight:800; color:#fff; font-family:var(--font-mono); }
  .file-chip-name{font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap}
  .file-chip-size{font-size:11px; color:var(--ink-soft)}
  .file-chip-remove{margin-left:auto; flex:none; width:24px;height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--ink-soft)}
  .file-chip-remove:hover{background:var(--border)}
  .file-chip-remove svg{width:13px;height:13px}
  .file-chip a{color:inherit}
  .file-chip .file-chip-dl{ margin-left:auto; flex:none; width:24px;height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--ink-soft)}
  .file-chip .file-chip-dl:hover{background:var(--border); color:var(--primary)}
  .file-chip .file-chip-dl svg{width:13px;height:13px}

  .submit-note{font-size:11.5px; color:var(--ink-soft); text-align:center; margin-top:12px; line-height:1.5}

  /* trạng thái đã nộp */
  .submitted-box{ border:1.5px solid var(--good); background:var(--good-soft); border-radius:var(--radius-sm); padding:16px; }
  .submitted-top{display:flex; align-items:center; gap:10px; margin-bottom:10px}
  .submitted-icon{width:32px;height:32px;border-radius:50%;flex:none; background:var(--good); color:#fff; display:flex;align-items:center;justify-content:center}
  .submitted-icon svg{width:16px;height:16px}
  .submitted-title{font-weight:700; font-size:13.5px; color:var(--good)}
  .submitted-time{font-size:11px; color:var(--ink-soft)}
  .submitted-note{font-size:13px; line-height:1.6; margin-bottom:10px; color:var(--ink); white-space:pre-wrap}
  .submitted-feedback{ margin-top:12px; padding:12px 14px; border-radius:var(--radius-sm); background:var(--surface);
    border:1px solid var(--border); }
  .submitted-feedback-title{font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:var(--primary); margin-bottom:4px}
  .submitted-feedback-text{font-size:13px; line-height:1.6; color:var(--ink)}
  .submitted-score-row{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; padding:10px 14px;
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); }
  .submitted-score-lbl{font-size:12px; color:var(--ink-soft); font-weight:700}
  .submitted-score-val{font-family:var(--font-display); font-weight:800; font-size:18px}

  /* trạng thái quá hạn */
  .locked-box{ border:1.5px solid var(--bad); background:var(--bad-soft); border-radius:var(--radius-sm); padding:18px; text-align:center; }
  .locked-icon{width:44px;height:44px;border-radius:50%;margin:0 auto 12px;background:var(--bad);color:#fff;display:flex;align-items:center;justify-content:center}
  .locked-icon svg{width:20px;height:20px}
  .locked-title{font-family:var(--font-display); font-weight:700; font-size:15px; color:var(--bad); margin-bottom:6px}
  .locked-text{font-size:12.5px; color:var(--ink-soft); line-height:1.6}

  /* ============ NHÓM ============ */
  .member-row{display:flex; align-items:center; gap:10px; padding:9px 0; border-top:1px solid var(--border)}
  .member-row:first-of-type{border-top:none; padding-top:0}
  .member-avatar{width:34px;height:34px;border-radius:50%;flex:none; background:var(--secondary); color:#fff;
    display:flex;align-items:center;justify-content:center; font-family:var(--font-display); font-weight:700; font-size:12.5px}
  body[data-theme="elegant"] .member-avatar{background:linear-gradient(155deg,var(--secondary),#33445A); border:1px solid var(--border)}
  .member-avatar.you{background:var(--primary); color:var(--primary-ink)}
  .member-name{font-size:13px; font-weight:700}
  .member-role{font-size:10.5px; color:var(--ink-soft)}
  .member-you-tag{margin-left:auto; font-size:10px; font-weight:800; color:var(--primary); flex:none}
  .member-taken-tag{margin-left:auto; font-size:10px; font-weight:700; color:var(--bad); background:var(--bad-soft); padding:3px 8px; border-radius:var(--radius-pill); flex:none}
  .member-check{margin-left:auto; width:18px;height:18px; flex:none; accent-color:var(--primary); cursor:pointer}
  .member-row.disabled{opacity:.55}
  .group-conflict{ margin-bottom:12px; padding:10px 12px; border-radius:var(--radius-sm); background:var(--warn-soft); color:var(--warn); font-size:12px; line-height:1.5 }
  .group-search{ width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--border);
    background:var(--surface-alt); color:var(--ink); font-family:var(--font-body); font-size:12.5px; margin-bottom:6px; }
  .group-search:focus-visible{outline:2px solid var(--focus); outline-offset:1px}
  .group-note{ font-size:11.5px; color:var(--ink-soft); line-height:1.5; margin-top:12px; padding-top:12px; border-top:1px solid var(--border) }
  .group-empty{font-size:12.5px; color:var(--ink-soft)}

  /* ============ YÊU CẦU ============ */
  .req-list{list-style:none}
  .req-list li{ display:flex; gap:9px; font-size:12.5px; line-height:1.55; margin-bottom:10px; color:var(--ink) }
  .req-list li:last-child{margin-bottom:0}
  .req-list svg{width:15px;height:15px; flex:none; margin-top:1px; color:var(--primary)}

  /* ============ LỊCH SỬ ============ */
  .history-empty{font-size:12.5px; color:var(--ink-soft)}
  .history-item{display:flex; gap:10px; padding:10px 0; border-top:1px solid var(--border)}
  .history-item:first-child{border-top:none; padding-top:0}
  .history-dot{width:9px;height:9px;border-radius:50%; background:var(--good); margin-top:4px; flex:none}
  .history-time{font-size:11px; color:var(--ink-soft); font-family:var(--font-mono)}
  .history-desc{font-size:12.5px; margin-top:2px; line-height:1.5}

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
    <div class="brand-mark">TT</div>
    <div class="brand-text">
      <div class="brand-title">Chi tiết bài tập</div>
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
  <section class="hero-assign">
    <div class="hero-subject" id="a-subject">…</div>
    <div class="hero-top-row">
      <div class="hero-title" id="a-title">…</div>
    </div>
    <div class="hero-badges">
      <span class="type-badge" id="a-type-badge"></span>
      <span class="pill pill-pending" id="a-status-pill">…</span>
    </div>
    <div class="hero-meta-row">
      <div class="hero-meta-item"><div class="hero-meta-label">Giáo viên giao</div><div class="hero-meta-value" id="a-teacher">–</div></div>
      <div class="hero-meta-item"><div class="hero-meta-label">Ngày giao</div><div class="hero-meta-value" id="a-assigned">–</div></div>
      <div class="hero-meta-item"><div class="hero-meta-label">Hạn nộp</div><div class="hero-meta-value" id="a-due">–</div></div>
      <div class="hero-meta-item"><div class="hero-meta-label">Thời gian còn lại</div><div class="hero-meta-value" id="a-remaining">–</div></div>
    </div>
  </section>

  <div class="cols">
    <!-- ===== MAIN COLUMN ===== -->
    <div class="col-main">

      <section class="panel-card" id="de-bai-section">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
          Đề bài
        </div>
        <p class="desc-text" id="a-description"></p>
      </section>

      <section class="panel-card" id="teacher-file-section" hidden>
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.4 11.6 12.6 20.4a5 5 0 0 1-7.1-7.1l9-9a3.5 3.5 0 0 1 5 5l-9 9a2 2 0 0 1-2.8-2.8l8.1-8.1"/></svg>
          Tệp đính kèm từ giáo viên
        </div>
        <div class="file-list" id="teacher-file-list"></div>
      </section>

      <section class="panel-card" id="submission-section">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
          Nộp bài
        </div>
        <div id="submission-content"></div>
      </section>

    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="col-side">

      <section class="panel-card" id="group-section" hidden>
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>
          Thành viên nhóm
        </div>
        <input type="text" class="group-search" id="group-search" placeholder="Tìm thành viên trong lớp…">
        <div id="member-list"></div>
        <p class="group-note" id="group-note"></p>
      </section>

      <section class="panel-card">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Yêu cầu nộp bài
        </div>
        <ul class="req-list" id="req-list"></ul>
      </section>

      <section class="panel-card">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg>
          Lịch sử nộp bài
        </div>
        <div id="history-list"><p class="history-empty">Chưa có lượt nộp nào.</p></div>
      </section>

    </div>
  </div>
</div>

<script>
(function(){
  "use strict";

  var ASSIGNMENT = <?php echo json_encode($pageData, JSON_UNESCAPED_UNICODE); ?>;
  var LAST_SUBMITTED_FILES = [];
  var history = [];

  var ICONS = {
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    download: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M8 11l4 4 4-4"/><path d="M4 17v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>',
    upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 19c1.2-3 3.5-4.6 6.5-4.6s5.3 1.6 6.5 4.6"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M15.5 14.6c2.2.3 4 1.8 5 4.4"/></svg>',
    person: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c1.4-3.2 4-5 6.5-5s5.1 1.8 6.5 5"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>'
  };

  var pad = function(n){ return String(n).padStart(2,'0'); };

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

  // ============ FILE TYPE META ============
  var FILE_TYPE_META = {
    doc:{label:'DOC',color:'#2A56C6'}, docx:{label:'DOC',color:'#2A56C6'},
    xls:{label:'XLS',color:'#0E8F5B'}, xlsx:{label:'XLS',color:'#0E8F5B'},
    ppt:{label:'PPT',color:'#D97706'}, pptx:{label:'PPT',color:'#D97706'},
    pdf:{label:'PDF',color:'#D14343'},
    jpg:{label:'IMG',color:'#8B7CF6'}, jpeg:{label:'IMG',color:'#8B7CF6'}, png:{label:'IMG',color:'#8B7CF6'}
  };
  function fileTypeMeta(nameOrExt){
    var ext = nameOrExt.indexOf('.')>-1 ? nameOrExt.split('.').pop().toLowerCase() : nameOrExt.toLowerCase();
    return FILE_TYPE_META[ext] || {label:'FILE', color:'#8A6F72'};
  }
  function formatSize(bytes){
    if(bytes < 1024) return bytes+' B';
    if(bytes < 1024*1024) return (bytes/1024).toFixed(0)+' KB';
    return (bytes/1024/1024).toFixed(1)+' MB';
  }

  // ============ HERO ============
  function renderHero(){
    document.getElementById('a-subject').textContent = ASSIGNMENT.subject;
    document.getElementById('a-title').textContent = ASSIGNMENT.title;
    var typeBadge = document.getElementById('a-type-badge');
    typeBadge.innerHTML = (ASSIGNMENT.type==='group' ? ICONS.users : ICONS.person) +
      '<span>'+(ASSIGNMENT.type==='group' ? 'Bài nhóm · tối đa '+ASSIGNMENT.maxGroupMembers+' thành viên' : 'Bài cá nhân')+'</span>';
    var statusPill = document.getElementById('a-status-pill');
    var map = { pending:['pill-pending','Chưa nộp'], submitted:['pill-submitted','Đã nộp'], late:['pill-late','Trễ hạn'] };
    statusPill.className = 'pill ' + map[ASSIGNMENT.status][0];
    statusPill.textContent = map[ASSIGNMENT.status][1];
    document.getElementById('a-teacher').textContent = ASSIGNMENT.teacherName;
    document.getElementById('a-assigned').textContent = ASSIGNMENT.assignedDate;
    document.getElementById('a-due').textContent = ASSIGNMENT.dueDate;
    var rem = document.getElementById('a-remaining');
    rem.textContent = ASSIGNMENT.remainingText;
    rem.classList.toggle('urgent', ASSIGNMENT.isLate);
  }

  // ============ ĐỀ BÀI ============
  function renderDescription(){
    document.getElementById('a-description').textContent = ASSIGNMENT.description || 'Giáo viên chưa nhập yêu cầu chi tiết cho bài tập này.';
    if(!ASSIGNMENT.description) document.getElementById('de-bai-section').hidden = true;
  }

  // ============ YÊU CẦU ============
  function renderRequirements(){
    var items = [
      'Điểm tối đa: <b>' + ASSIGNMENT.maxScore + ' điểm</b>.',
      'Hạn nộp: <b>' + ASSIGNMENT.dueDate + '</b>.'
    ];
    if(ASSIGNMENT.type==='group'){
      items.push('Nhóm tối đa <b>' + ASSIGNMENT.maxGroupMembers + ' thành viên</b> (tính cả người nộp bài).');
    }
    items.push('Định dạng tệp chấp nhận: Word, Excel, PowerPoint, PDF, hình ảnh, TXT, ZIP/RAR.');
    items.push('Tài liệu tối đa 10MB/tệp, hình ảnh tối đa 5MB/tệp.');
    document.getElementById('req-list').innerHTML = items.map(function(t){
      return '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>'+t+'</span></li>';
    }).join('');
  }

  // ============ TỆP GV ĐÍNH KÈM ============
  function renderTeacherFiles(){
    var section = document.getElementById('teacher-file-section');
    if(!ASSIGNMENT.teacherFiles || ASSIGNMENT.teacherFiles.length===0){ section.hidden = true; return; }
    section.hidden = false;
    var wrap = document.getElementById('teacher-file-list');
    wrap.innerHTML = ASSIGNMENT.teacherFiles.map(function(f){
      var meta = fileTypeMeta(f.ext);
      return '<div class="file-row">' +
        '<div class="file-icon" style="background:'+meta.color+'">'+meta.label+'</div>' +
        '<div class="file-meta"><div class="file-name">'+f.name+'</div><div class="file-size">'+f.size+'</div></div>' +
        '<a class="file-dl-btn" href="'+f.url+'" aria-label="Tải xuống '+f.name+'">'+ICONS.download+'</a>' +
      '</div>';
    }).join('');
  }

  // ============ NHÓM ============
  function initials(name){
    var parts = String(name || '').trim().split(/\s+/);
    if(parts.length >= 2){ return (parts[parts.length-2][0] + parts[parts.length-1][0]).toUpperCase(); }
    return (String(name||'HS').slice(0,2)).toUpperCase();
  }

  function selectedMemberCodes(){
    return Array.prototype.map.call(document.querySelectorAll('.member-check:checked'), function(cb){
      return cb.dataset.code;
    }).filter(Boolean);
  }

  function enforceGroupLimit(){
    var max = ASSIGNMENT.maxGroupMembers;
    var checked = document.querySelectorAll('.member-check:checked');
    if(checked.length > max){
      alert('Bài tập này chỉ cho phép tối đa ' + max + ' thành viên (tính cả em).');
      checked[checked.length - 1].checked = false;
    }
    renderGroupNote();
  }

  function renderGroupNote(){
    var n = selectedMemberCodes().length;
    document.getElementById('group-note').textContent =
      'Đã chọn ' + n + '/' + ASSIGNMENT.maxGroupMembers + ' thành viên (tính cả em). ' +
      'Chỉ cần một thành viên đại diện nộp bài, cả nhóm sẽ được ghi nhận hoàn thành.';
  }

  function renderGroup(){
    var section = document.getElementById('group-section');
    if(ASSIGNMENT.type !== 'group' || !ASSIGNMENT.groupMembers || ASSIGNMENT.groupMembers.length === 0){
      section.hidden = true;
      return;
    }
    section.hidden = false;
    if(ASSIGNMENT.groupConflict){
      var banner = document.createElement('div');
      banner.className = 'group-conflict';
      banner.innerHTML = '<b>Lưu ý:</b> Em đã được một bạn cùng lớp thêm vào nhóm của bài tập này. Mỗi học sinh chỉ được nằm trong một nhóm duy nhất, vì vậy em không thể tạo thêm nhóm khác. Liên hệ giáo viên nếu cần thay đổi nhóm.';
      section.insertBefore(banner, section.firstChild);
    }
    var wrap = document.getElementById('member-list');
    wrap.innerHTML = ASSIGNMENT.groupMembers.map(function(m){
      var taken = !!m.taken;
      var isYou = !!m.isYou;
      return '<div class="member-row' + (taken ? ' disabled' : '') + '" data-search="' + (m.code + ' ' + m.name).toLowerCase() + '">' +
        '<div class="member-avatar' + (isYou ? ' you' : '') + '">' + initials(m.name) + '</div>' +
        '<div style="min-width:0"><div class="member-name">' + m.name + (isYou ? ' (em)' : '') + '</div><div class="member-role">' + m.code + '</div></div>' +
        (taken ? '<span class="member-taken-tag">Đã vào nhóm khác</span>' : '') +
        '<input type="checkbox" class="member-check" data-code="' + m.code + '" data-name="' + m.name + '"' +
          (isYou ? ' checked disabled' : (taken ? ' disabled' : '')) + ' aria-label="Chọn ' + m.name + '">' +
      '</div>';
    }).join('');
    wrap.querySelectorAll('.member-check:not([disabled])').forEach(function(cb){
      cb.addEventListener('change', enforceGroupLimit);
    });
    document.getElementById('group-search').addEventListener('input', function(){
      var kw = this.value.trim().toLowerCase();
      wrap.querySelectorAll('.member-row').forEach(function(row){
        row.style.display = (kw === '' || row.dataset.search.indexOf(kw) > -1) ? '' : 'none';
      });
    });
    renderGroupNote();
  }

  // ============ HISTORY ============
  function renderHistory(){
    var wrap = document.getElementById('history-list');
    if(history.length === 0){ wrap.innerHTML = '<p class="history-empty">Chưa có lượt nộp nào.</p>'; return; }
    wrap.innerHTML = history.slice().reverse().map(function(h){
      return '<div class="history-item">' +
        '<span class="history-dot"></span>' +
        '<div><div class="history-time">'+h.time+'</div><div class="history-desc">'+h.fileCount+' tệp đính kèm'+(h.note ? ', kèm ghi chú' : '')+'</div></div>' +
      '</div>';
    }).join('');
  }

  function loadExistingHistory(){
    if(ASSIGNMENT.existing){
      var d = ASSIGNMENT.existing.submitted_at ? new Date(ASSIGNMENT.existing.submitted_at.replace(' ', 'T')) : null;
      var time = d ? (pad(d.getHours())+':'+pad(d.getMinutes())+', '+pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()) : 'Đã nộp';
      var fileCount = (ASSIGNMENT.existing.documents || []).length + (ASSIGNMENT.existing.images || []).length;
      history = [{ time: time, fileCount: fileCount, note: !!ASSIGNMENT.existing.content }];
    }
    renderHistory();
  }

  // ============ SUBMISSION VIEW ============
  function renderSubmittedFiles(files){
    if(!files || files.length === 0) return '<p style="font-size:12px;color:var(--ink-soft)">Không có tệp đính kèm.</p>';
    return '<div class="file-chip-list">' + files.map(function(f){
      var meta = fileTypeMeta(f.ext || f.name);
      var dl = f.url ? '<a class="file-chip-dl" href="'+f.url+'" aria-label="Tải '+f.name+'">'+ICONS.download+'</a>' : '';
      return '<div class="file-chip">' +
        '<div class="file-chip-icon" style="background:'+meta.color+'">'+meta.label+'</div>' +
        '<div style="min-width:0;flex:1"><div class="file-chip-name">'+f.name+'</div><div class="file-chip-size">'+f.size+'</div></div>' +
        dl +
      '</div>';
    }).join('') + '</div>';
  }

  function renderSubmittedView(){
    var wrap = document.getElementById('submission-content');
    var s = ASSIGNMENT.existing;
    if(!s){ return; }

    var files = LAST_SUBMITTED_FILES.length
      ? LAST_SUBMITTED_FILES
      : [].concat(
          (s.documents || []).map(function(d){
            return { name: d.filename || d.path.split('/').pop(), ext: d.extension || d.path.split('.').pop(), size: formatSize(d.size || 0), url: 'api/download_file.php?file=' + encodeURIComponent(d.path) };
          }),
          (s.images || []).map(function(img){
            var parts = img.split('/');
            return { name: parts.pop(), ext: parts.pop ? (img.split('.').pop()) : 'img', size: '', url: 'api/download_file.php?file=' + encodeURIComponent(img) };
          })
        );

    var scoreHtml = '';
    if(s.score !== null && s.score !== undefined){
      scoreHtml = '<div class="submitted-score-row"><span class="submitted-score-lbl">Điểm đã chấm</span>' +
        '<span class="submitted-score-val">' + s.score + ' / ' + ASSIGNMENT.maxScore + '</span></div>';
    }
    var feedbackHtml = '';
    if(s.feedback){
      feedbackHtml = '<div class="submitted-feedback"><div class="submitted-feedback-title">Nhận xét của giáo viên</div>' +
        '<div class="submitted-feedback-text">' + s.feedback + '</div></div>';
    }

    var resubmitBtn = ASSIGNMENT.isLate
      ? ''
      : '<button type="button" class="btn btn-ghost" id="resubmit-btn" style="width:100%;margin-top:14px">Nộp lại bài</button>' +
        '<p class="submit-note">Em có thể nộp lại để thay thế bài cũ, miễn là vẫn còn trước hạn nộp.</p>';

    wrap.innerHTML =
      scoreHtml +
      '<div class="submitted-box">' +
        '<div class="submitted-top">' +
          '<div class="submitted-icon">'+ICONS.check+'</div>' +
          '<div><div class="submitted-title">Đã nộp bài thành công</div><div class="submitted-time">Nộp lúc '+(s.submitted_at || '')+'</div></div>' +
        '</div>' +
        (s.content ? '<p class="submitted-note">'+s.content+'</p>' : '') +
        renderSubmittedFiles(files) +
        feedbackHtml +
      '</div>' +
      resubmitBtn;

    var resubmit = document.getElementById('resubmit-btn');
    if(resubmit) resubmit.addEventListener('click', renderSubmissionForm);
  }

  // ============ SUBMISSION FORM ============
  var selectedFiles = [];

  function renderSubmissionForm(){
    var wrap = document.getElementById('submission-content');
    wrap.innerHTML =
      '<div class="form-section">' +
        '<label class="modal-label" for="sub-note">Nội dung / Ghi chú nộp bài</label>' +
        '<textarea class="form-textarea" id="sub-note" placeholder="Ví dụ: em nộp file bài làm và ghi chú ngắn gọn về cách thực hiện…"></textarea>' +
      '</div>' +
      '<div class="form-section">' +
        '<label class="modal-label">Tệp bài làm</label>' +
        '<div class="dropzone" id="sub-dropzone" tabindex="0" role="button" aria-label="Chọn tệp bài làm">' +
          ICONS.upload +
          '<p>Kéo thả tệp vào đây hoặc <span class="link">chọn tệp</span></p>' +
          '<p class="hint">Word, Excel, PowerPoint, PDF, TXT, ZIP/RAR hoặc hình ảnh — tài liệu tối đa 10MB, ảnh tối đa 5MB/tệp</p>' +
          '<input type="file" id="sub-file-input" hidden multiple accept=".doc,.docx,.xls,.xlsx,.ppt,.pptx,.pdf,.txt,.zip,.rar,.jpg,.jpeg,.png">' +
        '</div>' +
        '<div class="file-chip-list" id="sub-file-list"></div>' +
      '</div>' +
      '<div class="form-section">' +
        '<button type="button" class="btn btn-primary" id="sub-submit-btn" style="width:100%">Nộp bài'+(ASSIGNMENT.type==='group'?' (đại diện nhóm)':'')+'</button>' +
        '<p class="submit-note">'+(ASSIGNMENT.type==='group' ? 'Khi em nộp, toàn bộ các thành viên đã chọn sẽ được ghi nhận hoàn thành bài tập này.' : 'Bài nộp chỉ tính cho cá nhân em.')+'</p>' +
      '</div>';

    selectedFiles = [];
    var dz = document.getElementById('sub-dropzone');
    var input = document.getElementById('sub-file-input');
    dz.addEventListener('click', function(){ input.click(); });
    dz.addEventListener('keydown', function(ev){ if(ev.key==='Enter'||ev.key===' '){ ev.preventDefault(); input.click(); } });
    input.addEventListener('change', function(){
      Array.prototype.forEach.call(input.files, function(f){ selectedFiles.push(f); });
      renderFileChips();
      input.value = '';
    });
    ['dragenter','dragover'].forEach(function(evt){ dz.addEventListener(evt, function(ev){ ev.preventDefault(); dz.classList.add('dragover'); }); });
    ['dragleave','drop'].forEach(function(evt){ dz.addEventListener(evt, function(ev){ ev.preventDefault(); dz.classList.remove('dragover'); }); });
    dz.addEventListener('drop', function(ev){
      Array.prototype.forEach.call(ev.dataTransfer.files, function(f){ selectedFiles.push(f); });
      renderFileChips();
    });

    document.getElementById('sub-submit-btn').addEventListener('click', submitAssignment);
  }

  function renderFileChips(){
    var wrap = document.getElementById('sub-file-list');
    if(!wrap) return;
    wrap.innerHTML = selectedFiles.map(function(f,i){
      var meta = fileTypeMeta(f.name);
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

  function submitAssignment(){
    var btn = document.getElementById('sub-submit-btn');
    var note = (document.getElementById('sub-note').value || '').trim();
    if(!note && selectedFiles.length === 0){
      alert('Em hãy nhập nội dung hoặc đính kèm ít nhất một tệp trước khi nộp bài nhé.');
      return;
    }
    var groupMembers = selectedMemberCodes();
    if(ASSIGNMENT.type === 'group' && groupMembers.length > ASSIGNMENT.maxGroupMembers){
      alert('Số thành viên nhóm vượt quá giới hạn cho phép (' + ASSIGNMENT.maxGroupMembers + ').');
      return;
    }
    if(ASSIGNMENT.type === 'group' && ASSIGNMENT.groupConflict){
      alert('Em đã được thêm vào một nhóm khác của bài tập này nên không thể nộp bài với nhóm mới.');
      return;
    }
    if(btn) btn.disabled = true;

    var fd = new FormData();
    fd.append('assignment_id', ASSIGNMENT.id);
    fd.append('content', note);
    groupMembers.forEach(function(code){ fd.append('group_members[]', code); });
    selectedFiles.forEach(function(f){
      if(f.type && f.type.indexOf('image/') === 0) fd.append('images[]', f);
      else fd.append('documents[]', f);
    });

    fetch('api/submit_assignment.php', { method:'POST', body:fd })
      .then(function(resp){ return resp.json(); })
      .then(function(res){
        if(btn) btn.disabled = false;
        if(res.success){
          var now = new Date();
          var timeStr = pad(now.getHours())+':'+pad(now.getMinutes())+', hôm nay';
          history.push({ time: timeStr, fileCount: selectedFiles.length, note: !!note });
          ASSIGNMENT.existing = {
            content: note,
            documents: [],
            images: [],
            submitted_at: timeStr,
            score: null,
            feedback: null
          };
          LAST_SUBMITTED_FILES = selectedFiles.map(function(f){
            return { name: f.name, ext: f.name.split('.').pop(), size: formatSize(f.size), url: '' };
          });
          ASSIGNMENT.status = 'submitted';
          renderHero();
          renderHistory();
          renderSubmittedView();
        } else {
          alert('Lỗi: ' + (res.message || 'Không thể nộp bài, vui lòng thử lại.'));
        }
      })
      .catch(function(err){
        console.error('Error:', err);
        if(btn) btn.disabled = false;
        alert('Có lỗi xảy ra khi nộp bài. Vui lòng thử lại.');
      });
  }

  // ============ INIT ============
  renderHero();
  renderDescription();
  renderTeacherFiles();
  renderGroup();
  renderRequirements();
  loadExistingHistory();

  if(ASSIGNMENT.status === 'submitted'){
    renderSubmittedView();
  } else if(ASSIGNMENT.status === 'late'){
    document.getElementById('submission-content').innerHTML =
      '<div class="locked-box">' +
        '<div class="locked-icon">'+ICONS.lock+'</div>' +
        '<div class="locked-title">Đã quá hạn nộp bài</div>' +
        '<p class="locked-text">Hạn nộp là ' + ASSIGNMENT.dueDate + '. Em không thể nộp bài cho bài tập này nữa.<br>Hãy liên hệ giáo viên nếu cần hỗ trợ.</p>' +
      '</div>';
  } else if(ASSIGNMENT.type === 'group' && ASSIGNMENT.groupConflict){
    document.getElementById('submission-content').innerHTML =
      '<div class="locked-box">' +
        '<div class="locked-icon">'+ICONS.users+'</div>' +
        '<div class="locked-title">Em đã thuộc một nhóm khác</div>' +
        '<p class="locked-text">Một bạn cùng lớp đã thêm em vào nhóm của bài tập này. Mỗi học sinh chỉ được nằm trong một nhóm duy nhất, vì vậy em không thể tạo nhóm khác.<br>Hãy liên hệ giáo viên nếu em cần thay đổi nhóm.</p>' +
      '</div>';
  } else {
    renderSubmissionForm();
  }

})();
</script>
</body>
</html>
