    </main>

  </div>

  <nav class="bottomnav" aria-label="Điều hướng chính">
    <a class="bottomnav-item<?php echo $stdActiveNav === 'home' ? '" aria-current="page' : ''; ?>" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/></svg>
      <span>Trang chủ</span>
    </a>
    <a class="bottomnav-item<?php echo $stdActiveNav === 'timetable' ? '" aria-current="page' : ''; ?>" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
      <span>TKB</span>
    </a>
    <a class="bottomnav-item<?php echo $stdActiveNav === 'assignments' ? '" aria-current="page' : ''; ?>" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V3Z"/><path d="M14 3v4h4M9 13h6M9 17h6"/></svg>
      <span>Bài tập</span>
    </a>
    <a class="bottomnav-item<?php echo $stdActiveNav === 'exams' ? '" aria-current="page' : ''; ?>" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
      <span>Bài thi</span>
    </a>
    <a class="bottomnav-item<?php echo $stdActiveNav === 'profile' ? '" aria-current="page' : ''; ?>" href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20c1.6-3.6 4.6-5.5 7.5-5.5s5.9 1.9 7.5 5.5"/></svg>
      <span>Cá nhân</span>
    </a>
  </nav>

</div>

<script>
(function () {
  "use strict";

  document.querySelectorAll('[data-theme-btn]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var theme = btn.getAttribute('data-theme-btn');
      document.body.setAttribute('data-theme', theme);
      document.querySelectorAll('[data-theme-btn]').forEach(function (other) {
        other.setAttribute('aria-pressed', String(other === btn));
      });
      try { localStorage.setItem('eduvn_student_theme_v2', theme); } catch (e) {}
    });
  });

  document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      btn.innerHTML = show
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 10.7a2 2 0 0 0 2.7 2.7"/><path d="M9.4 5.3A9.5 9.5 0 0 1 12 5c5 0 9 4.5 9 7a12 12 0 0 1-2.2 3.2M6.3 6.8A12.7 12.7 0 0 0 3 12c0 2.5 4 7 9 7a9.6 9.6 0 0 0 3.5-.6"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    });
  });

  var strengthInput = document.getElementById('new_password');
  var strengthBar = document.getElementById('strengthBar');
  var strengthTxt = document.getElementById('strengthTxt');
  if (strengthInput && strengthBar && strengthTxt) {
    strengthInput.addEventListener('input', function () {
      var v = strengthInput.value;
      var score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      strengthBar.style.width = (score * 20) + '%';
      strengthBar.style.background = ['#F4568C', '#F4568C', '#FFC857', '#FFC857', '#2FB6A3', '#2FB6A3'][score];
      strengthTxt.textContent = v.length === 0 ? '' : 'Độ mạnh: ' + ['Rất yếu', 'Rất yếu', 'Yếu', 'Khá', 'Mạnh', 'Rất mạnh'][score];
    });
  }
})();
</script>
