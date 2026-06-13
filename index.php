<?php
$selectedRole = $_GET['role'] ?? 'teacher';

if (! in_array($selectedRole, ['admin', 'teacher', 'student'], true)) {
    $selectedRole = 'teacher';
}

$roles = [
    'admin' => [
        'label' => 'Quản trị viên',
        'icon' => 'shield',
        'action' => 'login.php',
        'field' => 'username',
        'field_label' => 'Tên đăng nhập',
        'placeholder' => 'Nhập tài khoản quản trị',
    ],
    'teacher' => [
        'label' => 'Giáo viên',
        'icon' => 'teacher',
        'action' => 'login.php',
        'field' => 'username',
        'field_label' => 'Tên đăng nhập',
        'placeholder' => 'Nhập tài khoản giáo viên',
    ],
    'student' => [
        'label' => 'Học sinh',
        'icon' => 'student',
        'action' => 'student/login.php',
        'field' => 'student_code',
        'field_label' => 'Mã học sinh hoặc tên đăng nhập',
        'placeholder' => 'Nhập mã học sinh hoặc tên đăng nhập',
    ],
];

$activeRole = $roles[$selectedRole];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVD LMS - Đăng nhập hệ thống</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --primary: #1a56db;
            --primary-dark: #1342b0;
            --primary-light: #e8effd;
            --success: #059669;
            --text-main: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e7eb;
            --background: #f0f4ff;
            --surface: #ffffff;
            --radius: 10px;
            --shadow: 0 8px 40px rgba(15, 23, 42, 0.13);
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-main);
            font-family: "Be Vietnam Pro", Arial, sans-serif;
            background:
                radial-gradient(circle at 20% 30%, #c7d7ff 0%, transparent 48%),
                radial-gradient(circle at 80% 70%, #c7f0e8 0%, transparent 48%),
                var(--background);
        }

        button, input {
            font: inherit;
        }

        .login-wrapper {
            display: flex;
            width: min(900px, 100%);
            min-height: 560px;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .login-hero {
            position: relative;
            isolation: isolate;
            width: 44%;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            color: #ffffff;
            background: linear-gradient(145deg, #1a56db 0%, #0f2d6e 100%);
        }

        .login-hero::before,
        .login-hero::after {
            position: absolute;
            z-index: -1;
            content: "";
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
        }

        .login-hero::before {
            width: 300px;
            height: 300px;
            top: -80px;
            right: -80px;
        }

        .login-hero::after {
            width: 200px;
            height: 200px;
            bottom: -40px;
            left: -60px;
            background: rgba(255, 255, 255, 0.04);
        }

        .hero-logo {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .hero-logo-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.18);
        }

        .hero-logo-icon svg {
            width: 24px;
            height: 24px;
        }

        .hero-logo-text {
            font-size: 22px;
            font-weight: 700;
        }

        .hero-logo-sub {
            margin-top: 1px;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.7;
        }

        .hero-content h2 {
            margin: 0 0 12px;
            font-size: 26px;
            line-height: 1.35;
        }

        .hero-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.75;
            opacity: 0.78;
        }

        .hero-features {
            display: grid;
            gap: 10px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.78);
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feature-check {
            width: 20px;
            height: 20px;
            display: grid;
            place-items: center;
            flex: 0 0 20px;
            border-radius: 50%;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.14);
        }

        .login-panel {
            width: 56%;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface);
        }

        .form-heading {
            margin-bottom: 26px;
        }

        .form-heading h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.35;
        }

        .form-heading p {
            margin: 7px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .role-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 24px;
            padding: 4px;
            border-radius: var(--radius);
            background: var(--background);
        }

        .role-tab {
            min-width: 0;
            flex: 1;
            padding: 9px 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 0;
            border-radius: 7px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            background: transparent;
        }

        .role-tab svg {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
        }

        .role-tab.active {
            color: var(--primary);
            background: #ffffff;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.1);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 500;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon,
        .password-toggle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .input-icon {
            left: 12px;
            width: 18px;
            height: 18px;
            pointer-events: none;
        }

        .password-toggle {
            right: 8px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            background: transparent;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
        }

        .form-group input {
            width: 100%;
            min-height: 44px;
            padding: 10px 42px 10px 40px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            color: var(--text-main);
            font-size: 14px;
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
            background: #ffffff;
        }

        .login-note {
            min-height: 19px;
            margin: -7px 0 18px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .btn-login {
            width: 100%;
            min-height: 45px;
            border: 0;
            border-radius: var(--radius);
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            background: var(--primary);
            transition: background 0.2s, transform 0.1s;
        }

        .btn-login:hover {
            background: var(--primary-dark);
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        .form-footer {
            margin: 17px 0 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 760px) {
            body {
                padding: 16px;
                align-items: flex-start;
            }

            .login-wrapper {
                width: min(520px, 100%);
                min-height: auto;
                flex-direction: column;
            }

            .login-hero,
            .login-panel {
                width: 100%;
            }

            .login-hero {
                min-height: 220px;
                padding: 28px;
                gap: 30px;
            }

            .hero-content h2 {
                font-size: 21px;
            }

            .hero-features {
                display: none;
            }

            .login-panel {
                padding: 32px 28px;
            }
        }

        @media (max-width: 430px) {
            body {
                padding: 0;
                background: var(--surface);
            }

            .login-wrapper {
                border-radius: 0;
                box-shadow: none;
            }

            .login-hero {
                min-height: 190px;
                padding: 24px;
            }

            .login-panel {
                padding: 30px 22px 36px;
            }

            .role-tab {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
<main class="login-wrapper">
    <section class="login-hero" aria-label="Giới thiệu CVD LMS">
        <div class="hero-logo">
            <div class="hero-logo-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z"/>
                    <path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5v-16Z"/>
                </svg>
            </div>
            <div>
                <div class="hero-logo-text">CVD LMS</div>
                <div class="hero-logo-sub">Learning System</div>
            </div>
        </div>

        <div class="hero-content">
            <h2>Hệ thống dạy và học trực tuyến</h2>
            <p>Quản lý bài giảng, kiểm tra, luyện tập và theo dõi kết quả học tập trên một hệ thống thống nhất.</p>
        </div>

        <div class="hero-features" aria-label="Tính năng chính">
            <div class="hero-feature"><span class="feature-check">✓</span> Phân quyền quản trị, giáo viên và học sinh</div>
            <div class="hero-feature"><span class="feature-check">✓</span> Dữ liệu lớp học và kết quả được quản lý tập trung</div>
            <div class="hero-feature"><span class="feature-check">✓</span> Hỗ trợ kiểm tra, bài tập và luyện tập trực tuyến</div>
        </div>
    </section>

    <section class="login-panel" aria-label="Đăng nhập">
        <div class="form-heading">
            <h1>Chào mừng trở lại</h1>
            <p>Chọn vai trò và đăng nhập để tiếp tục</p>
        </div>

        <div class="role-tabs" role="tablist" aria-label="Chọn vai trò đăng nhập">
            <button type="button" class="role-tab<?= $selectedRole === 'admin' ? ' active' : '' ?>" data-role="admin" role="tab" aria-selected="<?= $selectedRole === 'admin' ? 'true' : 'false' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                Quản trị
            </button>
            <button type="button" class="role-tab<?= $selectedRole === 'teacher' ? ' active' : '' ?>" data-role="teacher" role="tab" aria-selected="<?= $selectedRole === 'teacher' ? 'true' : 'false' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="7" r="3"/>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2M17 8l3-2v7"/>
                </svg>
                Giáo viên
            </button>
            <button type="button" class="role-tab<?= $selectedRole === 'student' ? ' active' : '' ?>" data-role="student" role="tab" aria-selected="<?= $selectedRole === 'student' ? 'true' : 'false' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m3 9 9-5 9 5-9 5-9-5Z"/>
                    <path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"/>
                </svg>
                Học sinh
            </button>
        </div>

        <form id="login-form" method="post" action="<?= htmlspecialchars($activeRole['action'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="selected-role" name="login_role" value="<?= htmlspecialchars($selectedRole, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label id="login-label" for="login-identifier"><?= htmlspecialchars($activeRole['field_label'], ENT_QUOTES, 'UTF-8') ?></label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input
                        type="text"
                        id="login-identifier"
                        name="<?= htmlspecialchars($activeRole['field'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="<?= htmlspecialchars($activeRole['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>
                    </svg>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" id="password-toggle" aria-label="Hiện mật khẩu" title="Hiện mật khẩu">
                        <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="2.5"/>
                        </svg>
                    </button>
                </div>
            </div>

            <p class="login-note" id="login-note">
                <?= $selectedRole === 'student'
                    ? 'Học sinh có thể dùng mã học sinh hoặc tên đăng nhập đã thiết lập.'
                    : 'Sử dụng tài khoản được cấp để truy cập hệ thống.' ?>
            </p>

            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>

        <p class="form-footer">Không chia sẻ mật khẩu hoặc thông tin đăng nhập cho người khác.</p>
    </section>
</main>

<script>
    const roleConfig = <?= json_encode($roles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const roleTabs = document.querySelectorAll('.role-tab');
    const loginForm = document.getElementById('login-form');
    const loginInput = document.getElementById('login-identifier');
    const loginLabel = document.getElementById('login-label');
    const selectedRole = document.getElementById('selected-role');
    const loginNote = document.getElementById('login-note');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('password-toggle');

    function activateRole(role) {
        const config = roleConfig[role];

        roleTabs.forEach((tab) => {
            const isActive = tab.dataset.role === role;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        loginForm.action = config.action;
        loginInput.name = config.field;
        loginInput.placeholder = config.placeholder;
        loginLabel.textContent = config.field_label;
        selectedRole.value = role;
        loginNote.textContent = role === 'student'
            ? 'Học sinh có thể dùng mã học sinh hoặc tên đăng nhập đã thiết lập.'
            : 'Sử dụng tài khoản được cấp để truy cập hệ thống.';

        const url = new URL(window.location.href);
        url.searchParams.set('role', role);
        window.history.replaceState({}, '', url);
        loginInput.focus();
    }

    roleTabs.forEach((tab) => {
        tab.addEventListener('click', () => activateRole(tab.dataset.role));
    });

    passwordToggle.addEventListener('click', () => {
        const showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        passwordToggle.setAttribute('aria-label', showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        passwordToggle.setAttribute('title', showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
</script>
</body>
</html>
