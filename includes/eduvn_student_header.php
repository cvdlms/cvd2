<?php
/**
 * Shared shell for student sub-pages that follow the dashboard design.
 *
 * The including page must define these before the include:
 *   $stdDesignTheme  - 'cute' | 'elegant'   (default theme, localStorage wins)
 *   $stdInitials     - avatar initials
 *   $stdPageTitle    - <title> text
 *   $stdActiveNav    - nav key to highlight (see $stdNav below)
 *
 * Optional: $stdPageSubtitle, $stdBackHref.
 */
$stdActiveNav = $stdActiveNav ?? '';

$stdNavItems = [
    'home' => [
        'label' => 'Trang chủ',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/></svg>',
    ],
    'timetable' => [
        'label' => 'Thời khóa biểu',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
    ],
    'assignments' => [
        'label' => 'Bài tập',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>',
    ],
    'exams' => [
        'label' => 'Bài thi',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>',
    ],
    'practice' => [
        'label' => 'Luyện tập',
        'href' => 'practice.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v14a3 3 0 1 1-3-3h3M9 7h11M9 11h11"/></svg>',
    ],
    'gvcn' => [
        'label' => 'Thông báo GVCN',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.3-.7L3 21l1.8-5.4A8.4 8.4 0 1 1 21 11.5Z"/></svg>',
    ],
    'profile' => [
        'label' => 'Cá nhân',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>',
    ],
    'help' => [
        'label' => 'Trợ giúp',
        'href' => 'help.php',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.2a2.5 2.5 0 0 1 4.9.8c0 1.6-2.4 2-2.4 3.5"/><path d="M12 17.2v.1"/></svg>',
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($stdPageTitle ?? 'Cổng học sinh'); ?> — Cổng học sinh</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../styles/eduvn-student.css">
</head>
<body data-theme="<?php echo ($stdDesignTheme ?? 'cute') === 'elegant' ? 'elegant' : 'cute'; ?>">
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

      <a class="avatar-block" href="dashboard.php" style="align-items:center;gap:10px">
        <div class="avatar"><?php echo htmlspecialchars($stdInitials ?? 'HS'); ?></div>
        <div style="text-align:left">
          <div class="avatar-name"><?php echo htmlspecialchars($studentName ?? ''); ?></div>
          <div class="avatar-role">Lớp <?php echo htmlspecialchars($studentClass ?: ($studentClassCode ?: 'Học sinh')); ?></div>
        </div>
      </a>
      <a class="avatar-mobile" href="dashboard.php" aria-label="Trang chủ">
        <div class="avatar"><?php echo htmlspecialchars($stdInitials ?? 'HS'); ?></div>
      </a>
    </div>
  </header>

  <div class="body-grid two-col">

    <nav class="sidenav" aria-label="Điều hướng chính">
      <?php foreach (['home', 'timetable', 'assignments', 'exams', 'practice', 'gvcn', 'profile'] as $stdNavKey): ?>
        <?php $stdNavItem = $stdNavItems[$stdNavKey]; ?>
        <a class="sidenav-item<?php echo $stdActiveNav === $stdNavKey ? '" aria-current="page' : ''; ?>" href="<?php echo $stdNavItem['href']; ?>">
          <?php echo $stdNavItem['icon']; ?>
          <?php echo htmlspecialchars($stdNavItem['label']); ?>
        </a>
      <?php endforeach; ?>
      <div class="sidenav-foot">
        <a class="sidenav-item<?php echo $stdActiveNav === 'help' ? '" aria-current="page' : ''; ?>" href="<?php echo $stdNavItems['help']['href']; ?>">
          <?php echo $stdNavItems['help']['icon']; ?>
          Trợ giúp
        </a>
      </div>
    </nav>

    <main class="main">
