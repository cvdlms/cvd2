<?php
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

// Check Premium status
if (file_exists(__DIR__ . '/premium_helper.php')) {
    include_once __DIR__ . '/premium_helper.php';
    $isPremiumUser = isPremiumUser($_SESSION['username']);
    $premiumDaysRemaining = $isPremiumUser ? getPremiumDaysRemaining($_SESSION['username']) : 0;
} else {
    $isPremiumUser = false;
    $premiumDaysRemaining = 0;
}

// Current page detection
$currentPage = basename($_SERVER['PHP_SELF']);

// Page title map
$pageTitles = [
    'teacher.php'            => 'Bảng Điều Khiển',
    'manage_students.php'    => 'Quản Lý Học Sinh',
    'question_bank.php'      => 'Ngân Hàng Câu Hỏi',
    'import_pptx.php'        => 'Upload PowerPoint',
    'my_exams.php'           => 'Quản Lý Đề Thi',
    'exam_creation.php'      => 'Tạo Đề Kiểm Tra',
    'matrix_builder.php'     => 'Xây Dựng Ma Trận',
    'slides.php'             => 'Slide Bài Giảng',
    'slide_builder.php'      => 'Trình Biên Soạn Slide',
    'slide_viewer.php'       => 'Trình Chiếu Slide',
    'manage_assignments.php' => 'Quản Lý Bài Tập',
    'view_submissions.php'   => 'Bài Nộp Của Học Sinh',
    'manage_result.php'      => 'Kết Quả Học Tập',
    'knowledge_assessment.php' => 'Bản Mô Tả Mức Độ Đánh Giá',
    'lesson_plans.php'       => 'Kế Hoạch Bài Dạy',
    'lucky_wheel.php'        => 'Vòng Quay May Mắn',
    'remote_control.php'     => 'Điều Khiển Từ Xa',
    'excel_comments.php'     => 'Nhận Xét VnEdu',
    'notifications.php'      => 'Thông Báo',
    'premium_activation.php' => 'Premium',
    'user_guide.php'         => 'Hướng Dẫn Sử Dụng',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Giáo Viên';

// Searchable page list for topbar quick search
$searchPages = [
    ['label' => 'Bảng Điều Khiển',       'file' => 'teacher.php',           'icon' => 'bi-grid-1x2-fill'],
    ['label' => 'Quản Lý Học Sinh',      'file' => 'manage_students.php',   'icon' => 'bi-people-fill'],
    ['label' => 'Kết Quả Học Tập',       'file' => 'manage_result.php',     'icon' => 'bi-graph-up-arrow'],
    ['label' => 'Chấm Tự Luận',          'file' => 'grade_essays.php',      'icon' => 'bi-pencil-fill'],
    ['label' => 'Bản Mô Tả Mức Độ Đánh Giá',    'file' => 'knowledge_assessment.php', 'icon' => 'bi-clipboard-data-fill'],
    ['label' => 'Ngân Hàng Câu Hỏi',     'file' => 'question_bank.php',     'icon' => 'bi-collection-fill'],
    ['label' => 'Quản Lý Đề Thi',        'file' => 'my_exams.php',          'icon' => 'bi-file-earmark-text-fill'],
    ['label' => 'Tạo Đề Kiểm Tra',       'file' => 'exam_creation.php',     'icon' => 'bi-pencil-square'],
    ['label' => 'Ma Trận Đặc Tả',        'file' => 'matrix_builder.php',    'icon' => 'bi-diagram-3-fill'],
    ['label' => 'Slide Bài Giảng',       'file' => 'slides.php',            'icon' => 'bi-easel-fill'],
    ['label' => 'Quản Lý Bài Tập',       'file' => 'manage_assignments.php','icon' => 'bi-journal-text'],
    ['label' => 'Kế Hoạch Bài Dạy',      'file' => 'lesson_plans.php',      'icon' => 'bi-journal-bookmark-fill'],
    ['label' => 'Vòng Quay May Mắn',     'file' => 'lucky_wheel.php',       'icon' => 'bi-disc-fill'],
    ['label' => 'Điều Khiển Từ Xa',      'file' => 'remote_control.php',    'icon' => 'bi-broadcast'],
    ['label' => 'Nhận Xét VnEdu',        'file' => 'excel_comments.php',    'icon' => 'bi-file-earmark-excel-fill'],
    ['label' => 'Hướng Dẫn Sử Dụng',     'file' => 'user_guide.php',        'icon' => 'bi-question-circle-fill'],
    ['label' => 'Thông Báo',             'file' => 'notifications.php',     'icon' => 'bi-bell-fill'],
    ['label' => 'Premium',               'file' => 'premium_activation.php','icon' => 'bi-stars'],
];

// Sidebar item helper
function eduvn_sidebar_item($file, $label, $icon, $currentPage) {
    $active = $currentPage === $file ? ' active' : '';
    $href = $file === 'teacher.php' ? 'teacher.php' : $file;
    return '<a class="sidebar-item' . $active . '" href="' . $href . '"><i class="bi ' . $icon . '"></i><span>' . $label . '</span></a>';
}
?>
<aside class="eduvn-sidebar" id="eduvnSidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div>
            <div class="logo-text">EDUVN EXAMS</div>
            <div class="logo-sub">Giáo Viên Portal</div>
        </div>
    </div>

    <nav class="sidebar-scroll">
        <div class="sidebar-section">
            <div class="sidebar-section-label">Tổng quan</div>
            <?php echo eduvn_sidebar_item('teacher.php', 'Bảng Điều Khiển', 'bi-grid-1x2-fill', $currentPage); ?>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Học sinh</div>
            <?php echo eduvn_sidebar_item('manage_students.php', 'Quản Lý Học Sinh', 'bi-people-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('manage_result.php', 'Kết Quả Học Tập', 'bi-graph-up-arrow', $currentPage); ?>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Ngân hàng câu hỏi</div>
            <?php echo eduvn_sidebar_item('question_bank.php', 'Ngân Hàng Câu Hỏi', 'bi-collection-fill', $currentPage); ?>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Đề kiểm tra</div>
            <?php echo eduvn_sidebar_item('my_exams.php', 'Quản Lý Đề Thi', 'bi-file-earmark-text-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('exam_creation.php', 'Tạo Đề Kiểm Tra', 'bi-pencil-square', $currentPage); ?>
            <?php echo eduvn_sidebar_item('knowledge_assessment.php', 'Bản Đặc Tả', 'bi-clipboard-data-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('matrix_builder.php', 'Ma Trận Đặc Tả', 'bi-diagram-3-fill', $currentPage); ?>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Dạy học</div>
            <?php echo eduvn_sidebar_item('slides.php', 'Slide Bài Giảng', 'bi-easel-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('manage_assignments.php', 'Quản Lý Bài Tập', 'bi-journal-text', $currentPage); ?>
            <?php echo eduvn_sidebar_item('lesson_plans.php', 'Kế Hoạch Bài Dạy', 'bi-journal-bookmark-fill', $currentPage); ?>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Công cụ hỗ trợ</div>
            <?php echo eduvn_sidebar_item('lucky_wheel.php', 'Vòng Quay May Mắn', 'bi-disc-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('remote_control.php', 'Điều Khiển Từ Xa', 'bi-broadcast', $currentPage); ?>
            <?php echo eduvn_sidebar_item('excel_comments.php', 'Nhận Xét VnEdu', 'bi-file-earmark-excel-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('user_guide.php', 'Hướng Dẫn Sử Dụng', 'bi-question-circle-fill', $currentPage); ?>
        </div>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section">
            <?php echo eduvn_sidebar_item('notifications.php', 'Thông Báo', 'bi-bell-fill', $currentPage); ?>
            <?php echo eduvn_sidebar_item('premium_activation.php', 'Premium', 'bi-stars', $currentPage); ?>
        </div>
    </nav>

    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar">
                <?php echo strtoupper(mb_substr(htmlspecialchars($fullname), 0, 1)); ?>
            </div>
            <div class="flex-grow-1">
                <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                <div class="user-role">Giáo viên</div>
            </div>
            <a href="../logout.php" class="logout-link" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="eduvn-main">
    <header class="eduvn-topbar">
        <button type="button" class="icon-btn" id="sidebarToggle" aria-label="Thu gọn menu">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">
            <i class="bi bi-mortarboard-fill"></i>
            <span><?php echo htmlspecialchars($pageTitle); ?></span>
        </h1>

        <div class="topbar-search d-none d-md-flex" id="topbarSearch">
            <i class="bi bi-search"></i>
            <input type="text" id="topbarSearchInput" placeholder="Tìm nhanh chức năng..." autocomplete="off" />
            <div class="topbar-search-results" id="topbarSearchResults"></div>
        </div>

        <div class="topbar-actions">
            <?php if ($isPremiumUser): ?>
                <a href="premium_activation.php" class="premium-pill" title="Premium">
                    <i class="bi bi-star-fill"></i><span class="pill-text">Premium</span>
                    <?php if ($premiumDaysRemaining <= 7): ?>
                        <span class="badge text-bg-danger ms-1"><?php echo $premiumDaysRemaining; ?>d</span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="premium_activation.php" class="premium-pill locked" title="Nâng Cấp Premium">
                    <i class="bi bi-lock-fill"></i><span class="pill-text">Nâng Cấp Premium</span>
                </a>
            <?php endif; ?>

            <div class="dropdown">
                <button type="button" class="icon-btn" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot" id="notificationBadge" style="display:none;">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg notification-dropdown-menu">
                    <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-3 mb-0">
                        <strong>Thông báo</strong>
                        <a href="notifications.php" class="small text-primary fw-semibold">Xem tất cả</a>
                    </div>
                    <div id="notificationList">
                        <div class="text-center text-muted py-4 small">
                            <i class="bi bi-inbox d-block fs-4 mb-2"></i> Không có thông báo mới
                        </div>
                    </div>
                </div>
            </div>

            <div class="dropdown">
                <button type="button" class="topbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar"><?php echo strtoupper(mb_substr(htmlspecialchars($fullname), 0, 1)); ?></div>
                    <div class="user-meta d-none d-sm-block">
                        <div class="name"><?php echo htmlspecialchars($fullname); ?></div>
                        <div class="role"><i class="bi bi-shield-check"></i> Giáo viên</div>
                    </div>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="premium_activation.php"><i class="bi bi-stars me-2 text-warning"></i>Premium</a></li>
                    <li><a class="dropdown-item" href="notifications.php"><i class="bi bi-bell me-2 text-primary"></i>Thông báo</a></li>
                    <li><a class="dropdown-item" href="user_guide.php"><i class="bi bi-question-circle me-2 text-info"></i>Hướng dẫn</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../change_password.php"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="eduvn-content">

<!-- Auto Keep-Alive Script to prevent session timeout -->
<script>
(function() {
    // Keep session alive every 5 minutes (300000ms)
    setInterval(function() {
        fetch('../api/keep_alive.php')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.warn('Session may have expired');
                }
            })
            .catch(error => {
                console.error('Keep-alive failed:', error);
            });
    }, 300000); // 5 minutes

    // Load notification count
    function loadNotificationCount() {
        fetch('api/get_notifications_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notificationBadge');
                if (!badge) return;
                if (data.success && data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'grid';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }

    // Load notifications on page load
    loadNotificationCount();

    // Refresh notifications every 30 seconds
    setInterval(loadNotificationCount, 30000);

    // Load notification list when dropdown is opened
    document.getElementById('notificationDropdown').addEventListener('click', function() {
        loadNotificationList();
    });

    function loadNotificationList() {
        fetch('api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const listEl = document.getElementById('notificationList');
                if (!listEl) return;
                if (data.success && data.notifications.length > 0) {
                    const listHtml = data.notifications.slice(0, 5).map(notif => {
                        const isRead = notif.is_read ? '' : 'bg-accent-light';
                        const time = formatTime(notif.created_at);
                        return `
                            <a class="dropdown-item d-flex align-items-start gap-3 px-3 py-3 ${isRead}" href="notifications.php" style="border-radius: 10px;">
                                <span class="avatar sm primary" style="flex:none;"><i class="bi bi-journal-check"></i></span>
                                <span class="flex-grow-1" style="min-width:0;">
                                    <span class="d-block fw-bold small">${notif.title}</span>
                                    <span class="d-block text-muted small">${notif.message}</span>
                                    <span class="d-block text-muted" style="font-size:.7rem;"><i class="bi bi-clock"></i> ${time}</span>
                                </span>
                            </a>
                        `;
                    }).join('');
                    listEl.innerHTML = listHtml;
                } else {
                    listEl.innerHTML = `
                        <div class="text-center text-muted py-4 small">
                            <i class="bi bi-inbox d-block fs-4 mb-2"></i> Không có thông báo mới
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading notification list:', error);
            });
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Vừa xong';
        if (diffMins < 60) return diffMins + ' phút trước';
        if (diffHours < 24) return diffHours + ' giờ trước';
        if (diffDays < 7) return diffDays + ' ngày trước';
        return date.toLocaleDateString('vi-VN');
    }

    // Sidebar toggle: collapse on desktop, overlay drawer on mobile
    const sidebar = document.getElementById('eduvnSidebar');
    const mainEl = document.querySelector('.eduvn-main');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggle');

    // Floating tooltip for collapsed sidebar items
    const sidebarTooltip = document.createElement('div');
    sidebarTooltip.className = 'sidebar-tooltip';
    document.body.appendChild(sidebarTooltip);
    function hideSidebarTooltip() { sidebarTooltip.classList.remove('show'); }

    function isDesktop() { return window.innerWidth >= 992; }

    function adjustDataTables() {
        if (window.jQuery && $.fn.dataTable) {
            try {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            } catch (e) {}
        }
    }

    function triggerDataTablesAdjust() {
        adjustDataTables();
        setTimeout(adjustDataTables, 100);
        setTimeout(adjustDataTables, 320);
        setTimeout(adjustDataTables, 600);
    }

    function setSidebarCollapsed(collapsed) {
        if (!sidebar) return;
        sidebar.classList.toggle('collapsed', collapsed);
        if (mainEl) mainEl.classList.toggle('collapsed', collapsed);
        hideSidebarTooltip();
        const ic = toggleBtn ? toggleBtn.querySelector('i') : null;
        if (ic) ic.className = collapsed ? 'bi bi-chevron-double-left' : 'bi bi-list';
        if (toggleBtn) toggleBtn.setAttribute('aria-label', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
        try { localStorage.setItem('teacherSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        triggerDataTablesAdjust();
    }

    // Restore persisted collapsed state on desktop
    try {
        if (isDesktop() && localStorage.getItem('teacherSidebarCollapsed') === '1') {
            setSidebarCollapsed(true);
        }
    } catch (e) {}

    if (mainEl) {
        mainEl.addEventListener('transitionend', function(e) {
            if (e.propertyName === 'margin-left' || e.propertyName === 'width') {
                adjustDataTables();
            }
        });
    }
    window.addEventListener('resize', adjustDataTables);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (isDesktop()) {
                setSidebarCollapsed(!sidebar.classList.contains('collapsed'));
            } else {
                sidebar.classList.add('open');
                backdrop.classList.add('show');
                document.body.classList.add('sidebar-open');
            }
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        });
    }

    // Show tooltip with function name when hovering collapsed sidebar icons
    function positionSidebarTooltip(anchor) {
        const rect = anchor.getBoundingClientRect();
        sidebarTooltip.style.top = Math.round(rect.top + rect.height / 2) + 'px';
        sidebarTooltip.style.left = Math.round(rect.right + 12) + 'px';
    }

    sidebar.addEventListener('mouseover', function(e) {
        if (!isDesktop() || !sidebar.classList.contains('collapsed')) return;
        const item = e.target.closest ? e.target.closest('.sidebar-item') : null;
        if (!item) return;
        const labelSpan = item.querySelector('span');
        if (!labelSpan) return;
        sidebarTooltip.textContent = labelSpan.textContent.trim();
        positionSidebarTooltip(item.querySelector('i') || item);
        sidebarTooltip.classList.add('show');
    });

    sidebar.addEventListener('mousemove', function(e) {
        if (!sidebarTooltip.classList.contains('show')) return;
        const item = e.target.closest ? e.target.closest('.sidebar-item') : null;
        if (item) positionSidebarTooltip(item.querySelector('i') || item);
    });

    sidebar.addEventListener('mouseout', function(e) {
        const to = e.relatedTarget;
        if (to && to.closest && (to.closest('.eduvn-sidebar') || to === sidebarTooltip)) return;
        hideSidebarTooltip();
    });

    // Topbar quick search
    const SEARCH_PAGES = <?php echo json_encode($searchPages, JSON_UNESCAPED_UNICODE); ?>;
    const searchInput = document.getElementById('topbarSearchInput');
    const searchResults = document.getElementById('topbarSearchResults');
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            if (!q) {
                searchResults.classList.remove('show');
                return;
            }
            const matches = SEARCH_PAGES.filter(function(p) {
                return p.label.toLowerCase().includes(q) || p.file.toLowerCase().includes(q);
            });
            if (!matches.length) {
                searchResults.innerHTML = '<div class="search-empty">Không tìm thấy chức năng phù hợp</div>';
            } else {
                searchResults.innerHTML = matches.slice(0, 8).map(function(p) {
                    return '<a href="' + p.file + '"><i class="bi ' + p.icon + '"></i><span>' + p.label + '</span><span class="search-label">' + p.file.replace('.php', '') + '</span></a>';
                }).join('');
            }
            searchResults.classList.add('show');
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { this.blur(); searchResults.classList.remove('show'); }
        });
        document.addEventListener('click', function(e) {
            if (e.target.closest && !e.target.closest('.topbar-search')) {
                searchResults.classList.remove('show');
            }
        });
    }

    // Page-load reveal
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.eduvn-reveal').forEach((el, i) => {
            el.style.animationDelay = (i * 70) + 'ms';
        });
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    });
})();
</script>

<?php if ($isPremiumUser): ?>
<!-- Floating Zalo Contact Button -->
<a href="https://zalo.me/0973384354" target="_blank" class="zalo-float-button" title="Hỗ trợ Premium qua Zalo">
  <span><i class="bi bi-chat-dots-fill"></i></span>
</a>
<?php endif; ?>
