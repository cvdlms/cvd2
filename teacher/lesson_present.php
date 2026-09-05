<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CVD_TEACHER_SESSION');
    session_start();
}

include_once __DIR__ . '/../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$username = $_SESSION['username'];
$lessonId = $_GET['id'] ?? '';

if (!$lessonId) {
    die('Thiếu mã bài học. <a href="lessons.php">Quay lại danh sách bài học</a>');
}

$lessonsFile = __DIR__ . '/../data/teacher_lessons.json';
$lessons = file_exists($lessonsFile) ? (json_decode(file_get_contents($lessonsFile), true) ?: []) : [];

$lesson = null;
foreach ($lessons as $item) {
    if (($item['id'] ?? '') === $lessonId && ($item['teacher_username'] ?? '') === $username) {
        $lesson = $item;
        break;
    }
}

if (!$lesson) {
    die('Không tìm thấy bài học hoặc bạn không có quyền truy cập. <a href="lessons.php">Quay lại danh sách bài học</a>');
}

$questions = is_array($lesson['questions'] ?? null) ? $lesson['questions'] : [];

// Load classes and students for Random Student Picker
$classesFile = __DIR__ . '/../admin/classes.json';
$studentsFile = __DIR__ . '/../admin/students.json';
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';

$teacherClasses = file_exists($teacherClassesFile) ? (json_decode(file_get_contents($teacherClassesFile), true) ?: []) : [];
$allClasses = file_exists($classesFile) ? (json_decode(file_get_contents($classesFile), true) ?: []) : [];
$allStudents = file_exists($studentsFile) ? (json_decode(file_get_contents($studentsFile), true) ?: []) : [];

$assignedClassIds = $teacherClasses[$username] ?? [];

$classesData = [];
foreach ($allClasses as $c) {
    $cid = (string)($c['id'] ?? '');
    if (empty($assignedClassIds) || in_array($cid, $assignedClassIds) || in_array((int)$cid, $assignedClassIds)) {
        $classesData[$cid] = [
            'id' => $cid,
            'name' => $c['name'] ?? $c['code'] ?? 'Lớp ' . $cid,
            'students' => []
        ];
    }
}

foreach ($allStudents as $st) {
    $cid = (string)($st['class_id'] ?? '');
    if (isset($classesData[$cid])) {
        $classesData[$cid]['students'][] = [
            'name' => $st['name'] ?? 'Học sinh',
            'code' => $st['code'] ?? '',
            'gender' => $st['gender'] ?? 'Nam'
        ];
    }
}
$classesList = array_values($classesData);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - Trình Chiếu TV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --tv-font-scale: 1;
            --q-font-size: calc(34px * var(--tv-font-scale));
            --opt-font-size: calc(26px * var(--tv-font-scale));
            --badge-size: calc(54px * var(--tv-font-scale));
            
            /* Light Theme (Default) */
            --bg-canvas: #f8fafc;
            --bg-slide: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-opt-bg: #ffffff;
            --card-opt-border: #cbd5e1;
            --card-opt-hover: #f1f5f9;
            --correct-bg: #ecfdf5;
            --correct-border: #10b981;
            --correct-text: #065f46;
            --toolbar-bg: rgba(255, 255, 255, 0.95);
            --header-bg: #ffffff;
            --shadow-slide: 0 20px 40px -15px rgba(0, 0, 0, 0.08);
        }

        body.dark-mode {
            /* Dark Theme (Cinema / TV mode) */
            --bg-canvas: #090d16;
            --bg-slide: #131b2e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #1e293b;
            --card-opt-bg: #1a233a;
            --card-opt-border: #334155;
            --card-opt-hover: #222f4c;
            --correct-bg: #064e3b;
            --correct-border: #34d399;
            --correct-text: #ecfdf5;
            --toolbar-bg: rgba(19, 27, 46, 0.95);
            --header-bg: #131b2e;
            --shadow-slide: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            user-select: none;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Top TV Header Bar */
        .tv-header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .tv-lesson-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 50vw;
        }

        .tv-meta-badges {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tv-badge {
            font-size: 0.95rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 999px;
            letter-spacing: 0.5px;
        }

        .tv-badge-qnum {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            font-size: 1.15rem;
            padding: 6px 18px;
        }

        .tv-badge-level {
            font-size: 0.95rem;
        }
        .level-nb { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .level-th { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .level-vd { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .level-vdc { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Slide Main Area */
        .tv-viewport {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 36px 100px 36px;
            position: relative;
        }

        .tv-slide-card {
            background: var(--bg-slide);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            box-shadow: var(--shadow-slide);
            width: 100%;
            max-width: 1560px;
            padding: 38px 48px;
            position: relative;
            transition: all 0.3s ease;
        }

        /* Question Text */
        .tv-question-text {
            font-size: var(--q-font-size);
            font-weight: 700;
            line-height: 1.45;
            color: var(--text-main);
            margin-bottom: 32px;
            letter-spacing: -0.01em;
            word-wrap: break-word;
        }

        .tv-question-image {
            max-height: 380px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 16px;
            margin-bottom: 28px;
            border: 2px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            cursor: zoom-in;
            transition: transform 0.2s;
        }
        .tv-question-image:hover {
            transform: scale(1.01);
        }

        /* Options Grid (Multiple Choice) */
        .tv-options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .tv-options-grid {
                grid-template-columns: 1fr;
            }
        }

        .tv-option-card {
            background: var(--card-opt-bg);
            border: 2.5px solid var(--card-opt-border);
            border-radius: 20px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .tv-option-card:hover {
            background: var(--card-opt-hover);
            border-color: #60a5fa;
            transform: translateY(-2px);
        }

        .tv-option-badge {
            width: var(--badge-size);
            height: var(--badge-size);
            border-radius: 14px;
            background: #f1f5f9;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: calc(24px * var(--tv-font-scale));
            font-weight: 800;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        body.dark-mode .tv-option-badge {
            background: #25334d;
            color: #cbd5e1;
        }

        .tv-option-content {
            font-size: var(--opt-font-size);
            font-weight: 600;
            line-height: 1.4;
            color: var(--text-main);
            flex-grow: 1;
        }

        /* REVEALED ANSWER STATES */
        .tv-option-card.is-correct {
            background: var(--correct-bg) !important;
            border-color: var(--correct-border) !important;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.35);
            transform: scale(1.02);
            z-index: 5;
        }

        .tv-option-card.is-correct .tv-option-badge {
            background: #10b981 !important;
            color: white !important;
        }

        .tv-option-card.is-correct .tv-option-content {
            color: var(--correct-text) !important;
            font-weight: 800;
        }

        .tv-option-card.dimmed {
            opacity: 0.35;
            filter: grayscale(40%);
        }

        .correct-check-icon {
            color: #10b981;
            font-size: calc(30px * var(--tv-font-scale));
            margin-left: auto;
            flex-shrink: 0;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* True/False Table Format for TV */
        .tf-tv-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .tf-tv-row {
            background: var(--card-opt-bg);
            border: 2px solid var(--card-opt-border);
            border-radius: 16px;
            transition: all 0.25s ease;
        }

        .tf-tv-row td {
            padding: 16px 22px;
            vertical-align: middle;
            font-size: calc(24px * var(--tv-font-scale));
            font-weight: 600;
        }

        .tf-label-box {
            background: #3b82f6;
            color: white;
            font-weight: 800;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tf-ans-badge {
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 800;
            font-size: calc(20px * var(--tv-font-scale));
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Explanation Box */
        .tv-explanation-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 18px;
            padding: 24px 30px;
            margin-top: 28px;
            font-size: calc(23px * var(--tv-font-scale));
            line-height: 1.5;
            color: #14532d;
            animation: fadeInDown 0.3s ease;
        }

        body.dark-mode .tv-explanation-box {
            background: #064e3b;
            border-color: #059669;
            color: #ecfdf5;
        }

        /* Bottom Floating TV Toolbar */
        .tv-floating-toolbar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--toolbar-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid var(--border-color);
            border-radius: 999px;
            padding: 10px 22px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 200;
            transition: all 0.3s ease;
        }

        .tb-btn {
            background: transparent;
            border: none;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1rem;
            padding: 9px 18px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .tb-btn:hover {
            background: rgba(0, 0, 0, 0.07);
            transform: translateY(-1px);
        }

        body.dark-mode .tb-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .tb-btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white !important;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            padding: 10px 24px;
            font-size: 1.05rem;
        }

        .tb-btn-primary:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
        }

        .tb-btn-nav {
            background: #e2e8f0;
            color: #1e293b;
            font-size: 1.1rem;
            width: 44px;
            height: 44px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.dark-mode .tb-btn-nav {
            background: #1e293b;
            color: #f8fafc;
        }

        .tb-divider {
            width: 1px;
            height: 28px;
            background: var(--border-color);
            margin: 0 4px;
        }

        /* Black Screen Overlay (Press B) */
        #blackScreenOverlay {
            position: fixed;
            inset: 0;
            background: #000000;
            z-index: 9999;
            display: none;
            cursor: pointer;
        }

        /* Timer Badge on Top */
        .tv-timer-display {
            font-family: monospace;
            font-size: 1.6rem;
            font-weight: 800;
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            border: 1.5px solid #f59e0b;
            padding: 4px 16px;
            border-radius: 999px;
            display: none;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        /* Question Grid Modal for Quick Jump */
        .q-grid-btn {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            font-size: 1.3rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--border-color);
            background: var(--card-opt-bg);
            color: var(--text-main);
            transition: all 0.2s;
        }
        .q-grid-btn:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #1d4ed8;
            transform: scale(1.05);
        }
        .q-grid-btn.current {
            background: #3b82f6;
            color: white;
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        /* Lucky Student Picker Modal */
        .lucky-name-slot {
            font-size: 3.5rem;
            font-weight: 900;
            color: #2563eb;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px dashed #93c5fd;
            border-radius: 20px;
            background: #eff6ff;
            margin: 20px 0;
            text-align: center;
            padding: 10px 20px;
        }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Black Screen Mode (Click or Press B to wake) -->
    <div id="blackScreenOverlay" title="Nhấn phím B hoặc click vào màn hình để tiếp tục"></div>

    <!-- Header Bar -->
    <header class="tv-header">
        <div class="d-flex align-items-center gap-3">
            <a href="lessons.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-bold" title="Quay lại danh sách bài học">
                <i class="bi bi-arrow-left me-1"></i> Thoát
            </a>
            <div class="tv-lesson-title" title="<?php echo htmlspecialchars($lesson['title']); ?>">
                📺 <?php echo htmlspecialchars($lesson['title']); ?>
            </div>
        </div>

        <div class="tv-meta-badges">
            <div class="tv-timer-display" id="timerDisplay" title="Click để dừng hoặc đếm lại">
                <i class="bi bi-stopwatch"></i>
                <span id="timerText">00:30</span>
            </div>

            <span class="tv-badge tv-badge-qnum" id="slideIndexBadge">
                Câu 1 / <?php echo count($questions); ?>
            </span>

            <span class="tv-badge tv-badge-level" id="slideLevelBadge">
                Nhận biết
            </span>

            <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-2" id="btnToggleTheme" title="Chuyển chế độ Sáng / Tối">
                <i class="bi bi-moon-stars-fill fs-5" id="themeIcon"></i>
            </button>

            <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-2" id="btnToggleFullscreen" title="Toàn màn hình (F11)">
                <i class="bi bi-fullscreen fs-5" id="fsIcon"></i>
            </button>
        </div>
    </header>

    <!-- Slide Viewport -->
    <main class="tv-viewport">
        <?php if (empty($questions)): ?>
            <div class="text-center p-5 tv-slide-card">
                <i class="bi bi-exclamation-circle text-warning display-3 mb-3"></i>
                <h3 class="fw-bold">Bài học này chưa có câu hỏi nào!</h3>
                <p class="text-muted fs-4 mb-4">Vui lòng chỉnh sửa bài học và thêm câu hỏi từ Ngân hàng câu hỏi trước khi trình chiếu.</p>
                <a href="lessons.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">
                    <i class="bi bi-pencil-square me-2"></i> Quay lại chọn câu hỏi
                </a>
            </div>
        <?php else: ?>
            <div class="tv-slide-card" id="currentSlideCard">
                <!-- Slide Content dynamically rendered by JS -->
                <div id="slideContent"></div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Floating TV Toolbar -->
    <?php if (!empty($questions)): ?>
    <div class="tv-floating-toolbar">
        <!-- Prev Button -->
        <button type="button" class="tb-btn tb-btn-nav" id="btnPrev" title="Câu trước (← hoặc PageUp)">
            <i class="bi bi-chevron-left"></i>
        </button>

        <!-- Reveal Answer Button (Central) -->
        <button type="button" class="tb-btn tb-btn-primary" id="btnReveal" title="Hiện đáp án (Phím Space hoặc A)">
            <i class="bi bi-check2-circle fs-5" id="revealIcon"></i>
            <span id="revealText">HIỆN ĐÁP ÁN</span>
        </button>

        <!-- Next Button -->
        <button type="button" class="tb-btn tb-btn-nav" id="btnNext" title="Câu tiếp theo (→ hoặc PageDown)">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div class="tb-divider"></div>

        <!-- Timer Button -->
        <button type="button" class="tb-btn" id="btnOpenTimerMenu" title="Đồng hồ đếm ngược (Phím T)">
            <i class="bi bi-stopwatch text-warning fs-5"></i>
            <span>Hẹn giờ</span>
        </button>

        <!-- Lucky Student Picker Button -->
        <button type="button" class="tb-btn" id="btnLuckyStudent" title="Gọi học sinh ngẫu nhiên (Phím R)">
            <i class="bi bi-dice-5 text-primary fs-5"></i>
            <span>Gọi tên</span>
        </button>

        <!-- Font Scale Controls -->
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="tb-btn p-2" id="btnFontDown" title="Giảm cỡ chữ (Phím -)">
                <i class="bi bi-dash-lg"></i> A-
            </button>
            <button type="button" class="tb-btn p-2" id="btnFontUp" title="Tăng cỡ chữ (Phím +)">
                <i class="bi bi-plus-lg"></i> A+
            </button>
        </div>

        <div class="tb-divider"></div>

        <!-- Grid Jump (Menu tất cả câu) -->
        <button type="button" class="tb-btn" id="btnGridJump" title="Xem toàn bộ câu hỏi (Phím G)">
            <i class="bi bi-grid-3x3-gap-fill text-info fs-5"></i>
            <span>Mục lục</span>
        </button>

        <!-- Help Modal -->
        <button type="button" class="tb-btn p-2" id="btnHelp" title="Phím tắt điều khiển">
            <i class="bi bi-keyboard fs-5"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- MODAL: Grid Jump (Mục lục các câu hỏi) -->
    <div class="modal fade" id="gridModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
                        <span>Danh Sách Câu Hỏi Trong Bài (Nhảy nhanh)</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex flex-wrap gap-3 justify-content-center" id="gridButtonsContainer">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Timer Preset Picker -->
    <div class="modal fade" id="timerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-stopwatch text-warning"></i>
                        <span>Đồng Hồ Đếm Ngược Cho Học Sinh</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="text-muted fs-5 mb-4">Chọn thời gian để cả lớp cùng suy nghĩ và trả lời:</p>
                    <div class="d-grid gap-3 col-8 mx-auto">
                        <button type="button" class="btn btn-outline-primary btn-lg rounded-3 fw-bold py-3 btn-set-timer" data-seconds="15">
                            ⚡ 15 Giây (Nhanh)
                        </button>
                        <button type="button" class="btn btn-primary btn-lg rounded-3 fw-bold py-3 btn-set-timer" data-seconds="30">
                            ⏱️ 30 Giây (Tiêu chuẩn)
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg rounded-3 fw-bold py-3 btn-set-timer" data-seconds="45">
                            ⏳ 45 Giây
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg rounded-3 fw-bold py-3 btn-set-timer" data-seconds="60">
                            ⌛ 60 Giây (1 Phút)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Lucky Student Picker (Gọi học sinh) -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-dice-5 text-primary fs-4"></i>
                        <span>Gọi Học Sinh Ngẫu Nhiên Trả Lời</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="col-md-6 mx-auto mb-3">
                        <label class="form-label fw-bold text-muted">Chọn Lớp Học:</label>
                        <select class="form-select form-select-lg" id="selectClassLucky">
                            <?php foreach ($classesList as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                    <?php echo htmlspecialchars($c['name']); ?> (<?php echo count($c['students']); ?> học sinh)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lucky-name-slot" id="luckySlotName">
                        🎲 Bấm nút quay để bắt đầu!
                    </div>

                    <button type="button" class="btn btn-warning btn-lg rounded-pill px-5 py-3 fw-bold fs-4 shadow" id="btnSpinStudent">
                        <i class="bi bi-arrow-repeat me-2"></i> QUAY GỌI TÊN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Phím Tắt & Trợ Giúp -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-keyboard text-primary"></i>
                        <span>Phím Tắt Điều Khiển Giảng Dạy</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <table class="table table-borderless align-middle fs-5">
                        <tbody>
                            <tr>
                                <td><kbd>Space</kbd> hoặc <kbd>A</kbd></td>
                                <td><strong>Hiện / Ẩn đáp án</strong></td>
                            </tr>
                            <tr>
                                <td><kbd>→</kbd> hoặc <kbd>PageDown</kbd></td>
                                <td>Câu tiếp theo (Hỗ trợ bút chỉ slide)</td>
                            </tr>
                            <tr>
                                <td><kbd>←</kbd> hoặc <kbd>PageUp</kbd></td>
                                <td>Câu trước đó (Hỗ trợ bút chỉ slide)</td>
                            </tr>
                            <tr>
                                <td><kbd>F</kbd> hoặc <kbd>F11</kbd></td>
                                <td>Bật / Tắt Toàn màn hình TV</td>
                            </tr>
                            <tr>
                                <td><kbd>T</kbd></td>
                                <td>Bật đồng hồ đếm ngược</td>
                            </tr>
                            <tr>
                                <td><kbd>R</kbd></td>
                                <td>Gọi tên học sinh ngẫu nhiên</td>
                            </tr>
                            <tr>
                                <td><kbd>G</kbd> hoặc <kbd>M</kbd></td>
                                <td>Mở bảng mục lục nhảy nhanh câu</td>
                            </tr>
                            <tr>
                                <td><kbd>B</kbd></td>
                                <td>Tạm thời tắt đen màn hình TV (Blackout)</td>
                            </tr>
                            <tr>
                                <td><kbd>+</kbd> / <kbd>-</kbd></td>
                                <td>Tăng / Giảm cỡ chữ trên TV</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true
            }
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const questions = <?php echo json_encode($questions, JSON_UNESCAPED_UNICODE); ?>;
        const classesData = <?php echo json_encode($classesList, JSON_UNESCAPED_UNICODE); ?>;
        
        let currentIndex = 0;
        let isAnswerRevealed = false;
        let fontScale = parseFloat(localStorage.getItem('tv_font_scale') || '1.0');
        let isDarkMode = localStorage.getItem('tv_dark_mode') === 'true';

        // Modals
        const gridModal = new bootstrap.Modal(document.getElementById('gridModal'));
        const timerModal = new bootstrap.Modal(document.getElementById('timerModal'));
        const studentModal = new bootstrap.Modal(document.getElementById('studentModal'));
        const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));

        // Audio synthesizer via Web Audio API (no external sound files needed!)
        let audioCtx = null;
        function playTone(freq, duration, type = 'sine') {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {
                // Ignore audio error
            }
        }

        function playBellChime() {
            playTone(523.25, 0.4); // C5
            setTimeout(() => playTone(659.25, 0.4), 120); // E5
            setTimeout(() => playTone(783.99, 0.8), 240); // G5
        }

        // Apply theme
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            document.getElementById('themeIcon').className = 'bi bi-sun-fill fs-5 text-warning';
        }

        // Apply font scale
        function applyFontScale() {
            document.documentElement.style.setProperty('--tv-font-scale', fontScale);
            localStorage.setItem('tv_font_scale', fontScale);
        }
        applyFontScale();

        // Level text and class mapping
        const levelMap = {
            'NB': { text: 'Nhận biết', class: 'level-nb' },
            'TH': { text: 'Thông hiểu', class: 'level-th' },
            'VD': { text: 'Vận dụng', class: 'level-vd' },
            'VDC': { text: 'Vận dụng cao', class: 'level-vdc' }
        };

        // Render Current Question Slide
        function renderSlide() {
            if (questions.length === 0) return;
            const q = questions[currentIndex];
            isAnswerRevealed = false;

            // Update top bar badges
            document.getElementById('slideIndexBadge').textContent = `Câu ${currentIndex + 1} / ${questions.length}`;
            
            const lvl = levelMap[q.level] || { text: q.level || 'Cơ bản', class: 'level-nb' };
            const lvlBadge = document.getElementById('slideLevelBadge');
            lvlBadge.textContent = lvl.text;
            lvlBadge.className = `tv-badge tv-badge-level ${lvl.class}`;

            // Reset reveal button state
            const revealBtn = document.getElementById('btnReveal');
            revealBtn.className = 'tb-btn tb-btn-primary';
            document.getElementById('revealText').textContent = 'HIỆN ĐÁP ÁN';
            document.getElementById('revealIcon').className = 'bi bi-check2-circle fs-5';

            // Navigation button disabled states
            document.getElementById('btnPrev').disabled = (currentIndex === 0);
            document.getElementById('btnNext').disabled = (currentIndex === questions.length - 1);

            // Container
            const container = document.getElementById('slideContent');
            container.innerHTML = '';

            // Question Text
            const qTextDiv = document.createElement('div');
            qTextDiv.className = 'tv-question-text';
            qTextDiv.innerHTML = `<span class="text-primary me-2">Câu ${currentIndex + 1}:</span> ${q.question || ''}`;
            container.appendChild(qTextDiv);

            // Question Image if available
            if (q.image) {
                const img = document.createElement('img');
                img.className = 'tv-question-image';
                img.src = q.image;
                img.alt = 'Hình minh họa';
                img.title = 'Click để xem to hơn';
                img.onclick = () => window.open(q.image, '_blank');
                container.appendChild(img);
            }

            // Render Options based on Type
            const qType = q.type || 'single';

            if (qType === 'single' || qType === 'multiple') {
                const gridDiv = document.createElement('div');
                gridDiv.className = 'tv-options-grid';
                gridDiv.id = 'tvOptionsGrid';

                const letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                const opts = Array.isArray(q.options) ? q.options : [];

                opts.forEach((optText, i) => {
                    const card = document.createElement('div');
                    card.className = 'tv-option-card';
                    card.setAttribute('data-index', i);
                    card.innerHTML = `
                        <div class="tv-option-badge">${letters[i] || (i + 1)}</div>
                        <div class="tv-option-content">${optText}</div>
                    `;
                    // Clicking on an option card triggers Reveal or highlight
                    card.addEventListener('click', () => {
                        toggleRevealAnswer();
                    });
                    gridDiv.appendChild(card);
                });
                container.appendChild(gridDiv);
            } 
            else if (qType === 'true_false_multiple' && Array.isArray(q.items)) {
                // Table of True / False statements
                const table = document.createElement('table');
                table.className = 'tf-tv-table';
                table.id = 'tfTvTable';

                q.items.forEach((item, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'tf-tv-row';
                    tr.setAttribute('data-index', idx);
                    tr.setAttribute('data-correct', item.correct ? 'true' : 'false');
                    tr.innerHTML = `
                        <td style="width: 70px;">
                            <div class="tf-label-box">${item.label || String.fromCharCode(97 + idx)}</div>
                        </td>
                        <td>${item.statement || ''}</td>
                        <td style="width: 200px; text-align: right;" class="tf-ans-cell">
                            <span class="text-muted fw-bold fs-5">Chưa trả lời</span>
                        </td>
                    `;
                    table.appendChild(tr);
                });
                container.appendChild(table);
            }
            else {
                // Essay / Short Answer
                const essayDiv = document.createElement('div');
                essayDiv.className = 'p-4 rounded-4 border bg-light text-muted fs-4 text-center my-4';
                essayDiv.innerHTML = '<i class="bi bi-pencil-fill me-2"></i> Câu hỏi Tự luận / Trả lời ngắn. Bấm <strong>"Hiện Đáp Án"</strong> để xem gợi ý chấm.';
                container.appendChild(essayDiv);
            }

            // Explanation box (initially hidden)
            const expDiv = document.createElement('div');
            expDiv.id = 'tvExplanationBox';
            expDiv.className = 'tv-explanation-box d-none';
            container.appendChild(expDiv);

            // MathJax re-render
            if (window.MathJax && window.MathJax.typesetPromise) {
                window.MathJax.typesetPromise([container]).catch(err => console.error(err));
            }
        }

        // Reveal or Hide Answer
        function toggleRevealAnswer() {
            if (questions.length === 0) return;
            const q = questions[currentIndex];
            const qType = q.type || 'single';
            isAnswerRevealed = !isAnswerRevealed;

            const revealBtn = document.getElementById('btnReveal');
            const revealText = document.getElementById('revealText');
            const revealIcon = document.getElementById('revealIcon');
            const expBox = document.getElementById('tvExplanationBox');

            if (isAnswerRevealed) {
                // Audio chime for correct reveal
                playBellChime();

                revealBtn.className = 'tb-btn tb-btn-primary bg-danger text-white border-0';
                revealText.textContent = 'ẨN ĐÁP ÁN';
                revealIcon.className = 'bi bi-eye-slash-fill fs-5';

                // Handle single / multiple
                if (qType === 'single' || qType === 'multiple') {
                    const cards = document.querySelectorAll('.tv-option-card');
                    const correctIdx = Array.isArray(q.correct) ? q.correct : [parseInt(q.correct, 10)];

                    cards.forEach(card => {
                        const idx = parseInt(card.getAttribute('data-index'), 10);
                        if (correctIdx.includes(idx)) {
                            card.classList.add('is-correct');
                            if (!card.querySelector('.correct-check-icon')) {
                                const checkIcon = document.createElement('i');
                                checkIcon.className = 'bi bi-check-circle-fill correct-check-icon';
                                card.appendChild(checkIcon);
                            }
                        } else {
                            card.classList.add('dimmed');
                        }
                    });
                }
                // Handle true_false_multiple
                else if (qType === 'true_false_multiple') {
                    const rows = document.querySelectorAll('.tf-tv-row');
                    rows.forEach(r => {
                        const isTrue = (r.getAttribute('data-correct') === 'true');
                        const cell = r.querySelector('.tf-ans-cell');
                        if (cell) {
                            cell.innerHTML = `
                                <span class="tf-ans-badge ${isTrue ? 'bg-success text-white' : 'bg-danger text-white'}">
                                    <i class="bi bi-${isTrue ? 'check-lg' : 'x-lg'}"></i>
                                    ${isTrue ? 'ĐÚNG' : 'SAI'}
                                </span>
                            `;
                        }
                    });
                }

                // Show Explanation if available
                const explanation = q.suggested_answer || q.explanation || '';
                if (explanation.trim() !== '') {
                    expBox.innerHTML = `
                        <div class="fw-bold mb-2 text-success d-flex align-items-center gap-2">
                            <i class="bi bi-lightbulb-fill text-warning"></i>
                            Gợi ý / Giải thích chi tiết:
                        </div>
                        <div>${explanation}</div>
                    `;
                    expBox.classList.remove('d-none');
                    if (window.MathJax && window.MathJax.typesetPromise) {
                        window.MathJax.typesetPromise([expBox]).catch(err => console.error(err));
                    }
                }
            } else {
                // Hide answer
                revealBtn.className = 'tb-btn tb-btn-primary';
                revealText.textContent = 'HIỆN ĐÁP ÁN';
                revealIcon.className = 'bi bi-check2-circle fs-5';

                document.querySelectorAll('.tv-option-card').forEach(card => {
                    card.classList.remove('is-correct', 'dimmed');
                    const icon = card.querySelector('.correct-check-icon');
                    if (icon) icon.remove();
                });

                document.querySelectorAll('.tf-tv-row').forEach(r => {
                    const cell = r.querySelector('.tf-ans-cell');
                    if (cell) cell.innerHTML = '<span class="text-muted fw-bold fs-5">Chưa trả lời</span>';
                });

                expBox.classList.add('d-none');
            }
        }

        // Navigation
        function goToQuestion(index) {
            if (index < 0 || index >= questions.length) return;
            currentIndex = index;
            renderSlide();
        }

        function nextQuestion() {
            if (currentIndex < questions.length - 1) {
                goToQuestion(currentIndex + 1);
            }
        }

        function prevQuestion() {
            if (currentIndex > 0) {
                goToQuestion(currentIndex - 1);
            }
        }

        document.getElementById('btnReveal')?.addEventListener('click', toggleRevealAnswer);
        document.getElementById('btnNext')?.addEventListener('click', nextQuestion);
        document.getElementById('btnPrev')?.addEventListener('click', prevQuestion);

        // Font size +/-
        document.getElementById('btnFontUp')?.addEventListener('click', () => {
            if (fontScale < 1.6) {
                fontScale += 0.1;
                applyFontScale();
            }
        });

        document.getElementById('btnFontDown')?.addEventListener('click', () => {
            if (fontScale > 0.8) {
                fontScale -= 0.1;
                applyFontScale();
            }
        });

        // Theme Toggle (Dark / Light)
        document.getElementById('btnToggleTheme')?.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            document.body.classList.toggle('dark-mode', isDarkMode);
            localStorage.setItem('tv_dark_mode', isDarkMode);
            document.getElementById('themeIcon').className = isDarkMode 
                ? 'bi bi-sun-fill fs-5 text-warning' 
                : 'bi bi-moon-stars-fill fs-5';
        });

        // Fullscreen Toggle
        document.getElementById('btnToggleFullscreen')?.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => console.error(err));
                document.getElementById('fsIcon').className = 'bi bi-fullscreen-exit fs-5';
            } else {
                document.exitFullscreen();
                document.getElementById('fsIcon').className = 'bi bi-fullscreen fs-5';
            }
        });

        // Black Screen (Press B)
        const blackOverlay = document.getElementById('blackScreenOverlay');
        function toggleBlackScreen() {
            if (blackOverlay.style.display === 'block') {
                blackOverlay.style.display = 'none';
            } else {
                blackOverlay.style.display = 'block';
            }
        }
        blackOverlay.addEventListener('click', toggleBlackScreen);

        // Grid Jump Modal (Mục lục các câu hỏi)
        document.getElementById('btnGridJump')?.addEventListener('click', () => {
            const container = document.getElementById('gridButtonsContainer');
            container.innerHTML = '';
            questions.forEach((q, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'q-grid-btn ' + (idx === currentIndex ? 'current' : '');
                btn.textContent = idx + 1;
                btn.onclick = () => {
                    gridModal.hide();
                    goToQuestion(idx);
                };
                container.appendChild(btn);
            });
            gridModal.show();
        });

        // Timer Logic
        let timerInterval = null;
        let timerRemaining = 0;
        const timerDisplay = document.getElementById('timerDisplay');
        const timerText = document.getElementById('timerText');

        document.getElementById('btnOpenTimerMenu')?.addEventListener('click', () => {
            timerModal.show();
        });

        document.querySelectorAll('.btn-set-timer').forEach(btn => {
            btn.addEventListener('click', function() {
                const sec = parseInt(this.getAttribute('data-seconds'), 10);
                startTimer(sec);
                timerModal.hide();
            });
        });

        function startTimer(seconds) {
            clearInterval(timerInterval);
            timerRemaining = seconds;
            timerDisplay.style.display = 'inline-flex';
            updateTimerDisplay();

            timerInterval = setInterval(() => {
                timerRemaining--;
                updateTimerDisplay();

                if (timerRemaining <= 5 && timerRemaining > 0) {
                    // Gentle beep on last 5 seconds
                    playTone(440, 0.1);
                }

                if (timerRemaining <= 0) {
                    clearInterval(timerInterval);
                    playBellChime();
                    timerText.textContent = 'HẾT GIỜ!';
                    timerDisplay.style.animation = 'popIn 0.5s infinite alternate';
                    setTimeout(() => {
                        timerDisplay.style.animation = '';
                    }, 3000);
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const m = Math.floor(timerRemaining / 60);
            const s = timerRemaining % 60;
            timerText.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        timerDisplay.addEventListener('click', () => {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
                timerText.textContent += ' (Tạm dừng)';
            } else if (timerRemaining > 0) {
                startTimer(timerRemaining);
            }
        });

        // Lucky Student Picker
        document.getElementById('btnLuckyStudent')?.addEventListener('click', () => {
            studentModal.show();
        });

        const btnSpin = document.getElementById('btnSpinStudent');
        const slotName = document.getElementById('luckySlotName');
        const classSelect = document.getElementById('selectClassLucky');
        let spinInterval = null;

        btnSpin.addEventListener('click', () => {
            const cid = classSelect.value;
            const targetClass = classesData.find(c => String(c.id) === String(cid));
            const students = targetClass ? targetClass.students : [];

            if (!students || students.length === 0) {
                slotName.textContent = 'Lớp này chưa có danh sách học sinh!';
                return;
            }

            btnSpin.disabled = true;
            let counter = 0;
            const maxSpins = 30;
            const speed = 70;

            clearInterval(spinInterval);
            spinInterval = setInterval(() => {
                counter++;
                const randomStudent = students[Math.floor(Math.random() * students.length)];
                slotName.textContent = randomStudent.name;
                playTone(600 + (counter * 10), 0.05);

                if (counter >= maxSpins) {
                    clearInterval(spinInterval);
                    btnSpin.disabled = false;
                    playBellChime();
                    slotName.innerHTML = `🎉 <span class="text-success">${randomStudent.name}</span> 🎉`;
                }
            }, speed);
        });

        // Help Modal
        document.getElementById('btnHelp')?.addEventListener('click', () => {
            helpModal.show();
        });

        // Keyboard Shortcuts (Laser Pointer & Keyboard)
        document.addEventListener('keydown', (e) => {
            // Ignore if typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                return;
            }

            switch(e.key) {
                case 'ArrowRight':
                case 'PageDown':
                    nextQuestion();
                    break;
                case 'ArrowLeft':
                case 'PageUp':
                    prevQuestion();
                    break;
                case ' ':
                case 'Enter':
                case 'a':
                case 'A':
                    e.preventDefault();
                    toggleRevealAnswer();
                    break;
                case 'f':
                case 'F':
                    document.getElementById('btnToggleFullscreen').click();
                    break;
                case 't':
                case 'T':
                    timerModal.show();
                    break;
                case 'r':
                case 'R':
                    studentModal.show();
                    break;
                case 'g':
                case 'G':
                case 'm':
                case 'M':
                    document.getElementById('btnGridJump').click();
                    break;
                case 'b':
                case 'B':
                    toggleBlackScreen();
                    break;
                case '+':
                case '=':
                    document.getElementById('btnFontUp').click();
                    break;
                case '-':
                case '_':
                    document.getElementById('btnFontDown').click();
                    break;
            }
        });

        // Initialize Slide
        renderSlide();
    });
    </script>
</body>
</html>
