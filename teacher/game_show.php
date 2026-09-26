<?php
include '../includes/session_check.php';
include '../includes/common_functions.php';

$username = $_SESSION['username'];
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? 'Giáo Viên';

$config = [];
if (is_file(__DIR__ . '/../admin/system_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
}
$defaultSemester = $config['semester']['current'] ?? 'hk1';

$title = 'Trò Chơi Ai Là Triệu Phú - EDUVN EXAMS';
include '../includes/teacher_header.php';
?>
<style>
    /* ============================================================
       Game Show Setup Dashboard · EduVN Royal Design System
       ============================================================ */

    /* Hero Banner */
    .game-hero-banner {
        position: relative;
        background: linear-gradient(135deg, #131b40 0%, #1c2763 45%, #2a388f 100%);
        border: 1px solid rgba(250, 204, 21, 0.35);
        border-radius: 1.25rem;
        padding: 2.25rem 2rem;
        color: #ffffff;
        box-shadow: 0 15px 35px -10px rgba(19, 27, 64, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        overflow: hidden;
    }
    .game-hero-banner::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(250, 204, 21, 0.22) 0%, rgba(250, 204, 21, 0) 70%);
        pointer-events: none;
    }
    .game-hero-banner::after {
        content: '';
        position: absolute;
        bottom: -80px;
        left: 20%;
        width: 320px;
        height: 180px;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.3) 0%, rgba(79, 70, 229, 0) 70%);
        pointer-events: none;
    }
    .game-hero-banner .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(250, 204, 21, 0.15);
        border: 1px solid rgba(250, 204, 21, 0.45);
        color: #fde047;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        backdrop-filter: blur(4px);
    }
    .game-hero-banner .hero-title {
        font-size: 1.85rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.3px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    .game-hero-banner .hero-title .trophy-icon {
        color: #facc15;
        filter: drop-shadow(0 0 14px rgba(250, 204, 21, 0.65));
        animation: trophyShine 3s ease-in-out infinite;
    }
    @keyframes trophyShine {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 12px rgba(250, 204, 21, 0.6)); }
        50% { transform: scale(1.08) rotate(3deg); filter: drop-shadow(0 0 22px rgba(250, 204, 21, 0.9)); }
    }
    .game-hero-banner .hero-desc {
        color: #cdd5f3;
        font-size: 0.925rem;
        line-height: 1.55;
        max-width: 680px;
    }

    /* Hero Quick Stat Pills */
    .hero-stat-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        border-radius: 0.85rem;
        padding: 0.75rem 1rem;
        min-width: 130px;
        text-align: center;
        transition: transform 0.2s ease, background 0.2s ease;
    }
    .hero-stat-card:hover {
        background: rgba(255, 255, 255, 0.14);
        transform: translateY(-2px);
    }
    .hero-stat-card .stat-label {
        font-size: 0.72rem;
        color: #cbd5e1;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .hero-stat-card .stat-val {
        font-size: 1.35rem;
        font-weight: 800;
        color: #facc15;
        line-height: 1.2;
    }

    /* Modern SaaS Container Cards */
    .eduvn-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 4px 20px -6px rgba(15, 23, 42, 0.06);
        transition: box-shadow 0.2s ease;
    }
    .eduvn-card:hover {
        box-shadow: 0 10px 30px -8px rgba(15, 23, 42, 0.1);
    }
    .eduvn-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: transparent;
    }
    .eduvn-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .eduvn-card-body {
        padding: 1.5rem;
    }

    /* Form Section Dividers */
    .setup-section-title {
        font-size: 0.825rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .setup-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #f1f5f9;
    }

    /* Select Dropdowns */
    .custom-select-wrap {
        position: relative;
    }
    .custom-select-wrap i.select-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6366f1;
        font-size: 1rem;
        pointer-events: none;
    }
    .custom-select-wrap .form-select,
    .custom-select-wrap .form-control {
        padding-left: 2.75rem;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        color: #1e293b;
        box-shadow: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .custom-select-wrap .form-select:focus,
    .custom-select-wrap .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Quick Preset Buttons */
    .preset-chip-btn {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 0.45rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .preset-chip-btn:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
        transform: translateY(-1px);
    }
    .preset-chip-btn.active {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }

    /* Subject Interactive Cards */
    .subject-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 0.85rem;
        background: #ffffff;
        padding: 0.85rem 1rem;
        cursor: pointer;
        transition: all 0.18s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        user-select: none;
    }
    .subject-card:hover:not(.disabled) {
        border-color: #818cf8;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -4px rgba(99, 102, 241, 0.15);
    }
    .subject-card.selected {
        border-color: #4f46e5;
        background: #f5f7ff;
        box-shadow: 0 4px 14px -2px rgba(79, 70, 229, 0.18);
    }
    .subject-card.disabled {
        background: #f8fafc;
        border-color: #e2e8f0;
        opacity: 0.55;
        cursor: not-allowed;
    }
    .subject-card .subj-header {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 0.5rem;
    }
    .subject-card .subj-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 0.6rem;
        background: #eff6ff;
        color: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .subject-card.selected .subj-icon-box {
        background: #4f46e5;
        color: #ffffff;
    }
    .subject-card .subj-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.9rem;
        line-height: 1.25;
        flex: 1;
        word-break: break-word;
    }
    .subject-card .subj-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #4f46e5;
        cursor: pointer;
        flex-shrink: 0;
    }
    .subject-card .subj-metrics {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.3rem 0.45rem;
        font-size: 0.72rem;
        color: #64748b;
    }
    .subj-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.45rem;
        border-radius: 0.35rem;
        font-weight: 600;
    }
    .subj-pill.nb { background: #dcfce7; color: #166534; }
    .subj-pill.th { background: #e0f2fe; color: #075985; }
    .subj-pill.vd { background: #fef3c7; color: #92400e; }
    .subj-pill.total { background: #f1f5f9; color: #334155; font-weight: 700; margin-left: auto; }

    /* Level Stepper Cards */
    .level-stepper-card {
        border-radius: 0.85rem;
        padding: 1.15rem 1rem;
        text-align: center;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        transition: all 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .level-stepper-card.lvl-nb { border-top: 4px solid #10b981; }
    .level-stepper-card.lvl-th { border-top: 4px solid #0ea5e9; }
    .level-stepper-card.lvl-vd { border-top: 4px solid #f59e0b; }
    
    .level-stepper-card .lvl-icon-badge {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin: 0 auto 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .lvl-nb .lvl-icon-badge { background: #ecfdf5; color: #059669; }
    .lvl-th .lvl-icon-badge { background: #f0f9ff; color: #0284c7; }
    .lvl-vd .lvl-icon-badge { background: #fffbeb; color: #d97706; }

    .level-stepper-card .lvl-name {
        font-weight: 700;
        font-size: 0.925rem;
        color: #1e293b;
        margin-bottom: 0.15rem;
    }
    .level-stepper-card .lvl-sub {
        font-size: 0.74rem;
        color: #64748b;
        margin-bottom: 0.85rem;
    }

    /* Stepper Input Controls */
    .stepper-control {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }
    .stepper-btn {
        width: 34px;
        height: 34px;
        border-radius: 0.55rem;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        padding: 0;
        line-height: 1;
    }
    .stepper-btn:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        transform: scale(1.05);
    }
    .stepper-input {
        width: 60px;
        height: 38px;
        border: 1.5px solid #cbd5e1;
        border-radius: 0.6rem;
        text-align: center;
        font-weight: 800;
        font-size: 1.15rem;
        color: #1e293b;
        background: #ffffff;
        padding: 0;
        box-shadow: none;
    }
    .stepper-input:focus {
        border-color: #6366f1;
        outline: none;
    }

    /* Difficulty Ratio Segmented Bar */
    .difficulty-ratio-bar {
        height: 10px;
        border-radius: 999px;
        display: flex;
        overflow: hidden;
        background: #e2e8f0;
        margin-top: 1rem;
    }
    .diff-seg {
        height: 100%;
        transition: width 0.3s ease;
    }
    .diff-seg.nb { background: #10b981; }
    .diff-seg.th { background: #0ea5e9; }
    .diff-seg.vd { background: #f59e0b; }

    /* Right Sidebar Readiness Box */
    .readiness-banner {
        border-radius: 0.85rem;
        padding: 1.15rem;
        margin-bottom: 1.25rem;
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
    }
    .readiness-banner.ready {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }
    .readiness-banner.warn {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    .readiness-banner.empty {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
    }
    .readiness-banner .r-icon {
        font-size: 1.4rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .readiness-banner .r-title {
        font-weight: 700;
        font-size: 0.925rem;
        margin-bottom: 0.2rem;
    }
    .readiness-banner .r-text {
        font-size: 0.8rem;
        line-height: 1.45;
        margin: 0;
    }

    /* Progress Rows for Availability */
    .avail-meter-row {
        margin-bottom: 0.85rem;
    }
    .avail-meter-row:last-child {
        margin-bottom: 0;
    }
    .avail-meter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.825rem;
        margin-bottom: 0.35rem;
    }
    .avail-meter-bar {
        height: 7px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .avail-meter-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.3s ease;
    }
    .avail-meter-fill.ok { background: #10b981; }
    .avail-meter-fill.bad { background: #f59e0b; }

    /* Prize Ladder Simulation Preview */
    .prize-preview-box {
        background: #0d1430;
        border-radius: 0.85rem;
        padding: 1rem;
        color: #ffffff;
        border: 1px solid #293570;
    }
    .ladder-preview-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.3rem;
        background: rgba(255, 255, 255, 0.04);
        color: #94a3b8;
    }
    .ladder-preview-item.safe-milestone {
        background: rgba(250, 204, 21, 0.12);
        border: 1px solid rgba(250, 204, 21, 0.4);
        color: #fde047;
        font-weight: 700;
    }
    .ladder-preview-item.grand-prize {
        background: linear-gradient(90deg, rgba(250, 204, 21, 0.25), rgba(245, 158, 11, 0.35));
        border: 1px solid #facc15;
        color: #facc15;
        font-weight: 800;
        box-shadow: 0 0 10px rgba(250, 204, 21, 0.2);
    }

    /* Launch Call-to-Action Center */
    .btn-launch-game {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 60%, #b45309 100%);
        color: #ffffff !important;
        border: none;
        border-radius: 0.85rem;
        padding: 0.95rem 1.85rem;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: 0.3px;
        box-shadow: 0 10px 25px -4px rgba(217, 119, 6, 0.45);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .btn-launch-game:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px -4px rgba(217, 119, 6, 0.6);
        background: linear-gradient(135deg, #fbbf24 0%, #d97706 60%, #b45309 100%);
    }
    .btn-launch-game:active {
        transform: translateY(1px);
    }
    .btn-launch-game i {
        font-size: 1.35rem;
    }

    /* Shortcuts & Tips */
    .kbd-chip {
        display: inline-block;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 0.1rem 0.4rem;
        font-size: 0.75rem;
        font-family: var(--mono, monospace);
        font-weight: 700;
        color: #334155;
    }
</style>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="teacher.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2-fill me-1"></i>Bảng Điều Khiển</a></li>
            <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page"><i class="bi bi-trophy-fill me-1 text-warning"></i>Trò Chơi Ai Là Triệu Phú</li>
        </ol>
    </nav>

    <!-- Royal Game Hero Banner -->
    <div class="game-hero-banner mb-4">
        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <div class="col-lg-8">
                <div class="hero-badge mb-2">
                    <i class="bi bi-stars"></i> Sân Chơi Tri Thức · Sinh Hoạt Dưới Cờ
                </div>
                <div class="hero-title mb-2">
                    <i class="bi bi-trophy-fill trophy-icon"></i>
                    <span>Ai Là Triệu Phú — EDUVN EXAMS</span>
                </div>
                <p class="hero-desc mb-0">
                    Cấu hình trò chơi trực tiếp từ <b>Ngân hàng câu hỏi</b> trường học. Đề thi được phân hóa tự động theo 3 cấp độ nhận thức (Nhận biết, Thông hiểu, Vận dụng) với hiệu ứng âm thanh kịch tính, sẵn sàng hiển thị trên Smart TV &amp; Máy chiếu sân trường.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    <div class="hero-stat-card">
                        <div class="stat-label">Khối Lớp</div>
                        <div class="stat-val" id="statGrade">Khối 7</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="stat-label">Môn Đã Chọn</div>
                        <div class="stat-val" id="statSubjCount">0</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="stat-label">Tổng Số Câu</div>
                        <div class="stat-val" id="statTotalQ">13</div>
                    </div>
                    <div class="hero-stat-card">
                        <div class="stat-label">Thời Lượng</div>
                        <div class="stat-val" id="statTime">~15p</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Workspace -->
    <div class="row g-4">
        <!-- Left: Configuration Dashboard -->
        <div class="col-xl-8 col-lg-7">
            <div class="eduvn-card mb-4">
                <div class="eduvn-card-header">
                    <h5 class="eduvn-card-title">
                        <i class="bi bi-sliders text-primary"></i>
                        <span>Thiết Lập Bộ Câu Hỏi Game Show</span>
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                        <i class="bi bi-magic me-1"></i>Trích xuất tự động
                    </span>
                </div>

                <div class="eduvn-card-body">
                    <!-- Step 1: Grade & Semester -->
                    <div class="setup-section-title">
                        <i class="bi bi-1-circle-fill text-primary"></i>
                        <span>1. Phạm vi kiến thức &amp; Khối lớp</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">Khối Lớp</label>
                            <div class="custom-select-wrap">
                                <i class="bi bi-mortarboard-fill select-icon"></i>
                                <select class="form-select" id="gsGrade"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">Học Kỳ</label>
                            <div class="custom-select-wrap">
                                <i class="bi bi-calendar3 select-icon"></i>
                                <select class="form-select" id="gsSemester"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">Tổng Số Câu Lượt Chơi</label>
                            <div class="custom-select-wrap">
                                <i class="bi bi-check2-circle select-icon"></i>
                                <input type="text" class="form-control bg-light text-primary fw-bold" id="gsTotal" value="13" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Subject Selector -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="setup-section-title mb-0">
                            <i class="bi bi-2-circle-fill text-primary"></i>
                            <span>2. Chọn môn học tham gia trò chơi</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btnSelectAll" onclick="gsSelectAll(true)">
                                <i class="bi bi-check-all me-1"></i>Chọn tất cả có câu
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" id="btnDeselectAll" onclick="gsSelectAll(false)">
                                <i class="bi bi-x me-1"></i>Bỏ chọn
                            </button>
                        </div>
                    </div>
                    <p class="small text-muted mb-3">
                        Bạn có thể chọn <b>một môn</b> hoặc chọn <b>nhiều môn kết hợp</b> để tạo gói câu hỏi liên môn phong phú trong các buổi sinh hoạt ngoại khóa.
                    </p>

                    <!-- Filter / Search subjects input -->
                    <div class="row mb-3">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="gsSubjSearch" placeholder="Tìm kiếm môn học..." oninput="gsFilterSubjects()">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-7 text-md-end mt-2 mt-md-0">
                            <div class="small fw-semibold text-primary" id="gsSubjectsMsg">Đang tải danh sách môn...</div>
                        </div>
                    </div>

                    <!-- Subjects Card Grid -->
                    <div class="row g-3 mb-4" id="gsSubjects">
                        <div class="col-12 py-4 text-center text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Đang kết nối Ngân hàng câu hỏi...
                        </div>
                    </div>

                    <!-- Step 3: Difficulty Breakdown & Question Counts -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="setup-section-title mb-0">
                            <i class="bi bi-3-circle-fill text-primary"></i>
                            <span>3. Phân bổ số câu hỏi theo mức độ tư duy</span>
                        </div>
                        <!-- Presets -->
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="preset-chip-btn active" data-nb="5" data-th="4" data-vd="4" onclick="gsApplyPreset(5,4,4,this)">
                                ⚡ Tiêu chuẩn (13 câu)
                            </button>
                            <button type="button" class="preset-chip-btn" data-nb="5" data-th="5" data-vd="5" onclick="gsApplyPreset(5,5,5,this)">
                                ⭐ Chuẩn VTV (15 câu)
                            </button>
                            <button type="button" class="preset-chip-btn" data-nb="4" data-th="3" data-vd="3" onclick="gsApplyPreset(4,3,3,this)">
                                🚀 Khởi động (10 câu)
                            </button>
                            <button type="button" class="preset-chip-btn" data-nb="3" data-th="2" data-vd="2" onclick="gsApplyPreset(3,2,2,this)">
                                🎯 Nhanh (7 câu)
                            </button>
                        </div>
                    </div>
                    <p class="small text-muted mb-3">
                        Hệ thống tự động xếp thứ tự từ câu Dễ đến Khó. Điểm thưởng và mức kịch tính tăng dần theo thang câu hỏi.
                    </p>

                    <div class="row g-3 mb-3">
                        <!-- Mức 1 -->
                        <div class="col-md-4">
                            <div class="level-stepper-card lvl-nb">
                                <div>
                                    <div class="lvl-icon-badge"><i class="bi bi-lightbulb-fill"></i></div>
                                    <div class="lvl-name">Mức 1 · Nhận Biết</div>
                                    <div class="lvl-sub">Câu hỏi dễ · Khởi động thang điểm</div>
                                </div>
                                <div class="stepper-control mt-2">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsNB', -1)">−</button>
                                    <input type="number" class="stepper-input" id="gsNB" value="5" min="0" max="30">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsNB', 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Mức 2 -->
                        <div class="col-md-4">
                            <div class="level-stepper-card lvl-th">
                                <div>
                                    <div class="lvl-icon-badge"><i class="bi bi-lightning-charge-fill"></i></div>
                                    <div class="lvl-name">Mức 2 · Thông Hiểu</div>
                                    <div class="lvl-sub">Câu hỏi vừa · Vượt chướng ngại vật</div>
                                </div>
                                <div class="stepper-control mt-2">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsTH', -1)">−</button>
                                    <input type="number" class="stepper-input" id="gsTH" value="4" min="0" max="30">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsTH', 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Mức 3 -->
                        <div class="col-md-4">
                            <div class="level-stepper-card lvl-vd">
                                <div>
                                    <div class="lvl-icon-badge"><i class="bi bi-trophy-fill"></i></div>
                                    <div class="lvl-name">Mức 3 · Vận Dụng</div>
                                    <div class="lvl-sub">Câu hỏi khó · Chinh phục đỉnh cao</div>
                                </div>
                                <div class="stepper-control mt-2">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsVD', -1)">−</button>
                                    <input type="number" class="stepper-input" id="gsVD" value="4" min="0" max="30">
                                    <button type="button" class="stepper-btn" onclick="gsStepCount('gsVD', 1)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Difficulty Segmented Ratio Bar -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="d-flex justify-content-between align-items-center small fw-semibold text-muted mb-1">
                            <span>Tỷ lệ phân bố câu hỏi theo độ khó:</span>
                            <span class="text-primary fw-bold" id="gsRatioSummary">NB: 38% · TH: 31% · VD: 31%</span>
                        </div>
                        <div class="difficulty-ratio-bar">
                            <div class="diff-seg nb" id="barNB" style="width: 38.5%;" title="Mức 1: Nhận biết"></div>
                            <div class="diff-seg th" id="barTH" style="width: 30.7%;" title="Mức 2: Thông hiểu"></div>
                            <div class="diff-seg vd" id="barVD" style="width: 30.8%;" title="Mức 3: Vận dụng"></div>
                        </div>
                    </div>

                    <!-- Step 4: Launch Actions -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2 border-top">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-launch-game" onclick="gsOpenGame()">
                                <i class="bi bi-play-circle-fill"></i>
                                <span>MỞ MÀN HÌNH TRÒ CHƠI (TRÌNH CHIẾU)</span>
                            </button>
                            <button class="btn btn-outline-secondary fw-semibold px-3" onclick="gsLoadMeta(true)" title="Cập nhật lại số lượng từ ngân hàng">
                                <i class="bi bi-arrow-clockwise me-1"></i>Làm mới
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="remote_control.php" class="btn btn-light border text-muted small fw-semibold" target="_blank">
                                <i class="bi bi-phone me-1 text-primary"></i>Điều Khiển Từ Xa (Remote)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Real-time Bank Audit & Game Playbook -->
        <div class="col-xl-4 col-lg-5">
            <!-- Readiness Card -->
            <div class="eduvn-card mb-4">
                <div class="eduvn-card-header">
                    <h6 class="eduvn-card-title">
                        <i class="bi bi-shield-check text-success"></i>
                        <span>Kiểm Tra Khả Dụng Ngân Hàng</span>
                    </h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small" id="availStatusBadge">
                        Đang kiểm tra
                    </span>
                </div>

                <div class="eduvn-card-body">
                    <!-- Status Banner -->
                    <div class="readiness-banner ready" id="gsReadinessBanner">
                        <i class="bi bi-check-circle-fill r-icon" id="gsReadinessIcon"></i>
                        <div>
                            <div class="r-title" id="gsReadinessTitle">Sẵn Sàng Khởi Chạy!</div>
                            <p class="r-text" id="gsReadinessDesc">Ngân hàng có đủ số lượng câu hỏi phù hợp cho toàn bộ các mức độ đã thiết lập.</p>
                        </div>
                    </div>

                    <!-- Progress Details -->
                    <div class="mb-3" id="gsAvailMeters">
                        <div class="text-muted small py-2 text-center">Đang nạp dữ liệu thống kê...</div>
                    </div>

                    <!-- Bank Question Pool Summary -->
                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border small text-muted">
                        <span>Tổng kho câu hỏi các môn đã chọn:</span>
                        <b class="text-primary fs-6" id="gsTotalPool">0 câu</b>
                    </div>
                </div>
            </div>

            <!-- Prize Ladder Preview Simulation -->
            <div class="eduvn-card mb-4">
                <div class="eduvn-card-header">
                    <h6 class="eduvn-card-title">
                        <i class="bi bi-award-fill text-warning"></i>
                        <span>Mô Phỏng Thang Điểm &amp; Mốc An Toàn</span>
                    </h6>
                </div>
                <div class="eduvn-card-body">
                    <p class="small text-muted mb-2">
                        Người chơi trả lời đúng sẽ leo dần các bậc câu hỏi. Các <b>mốc an toàn</b> giúp người chơi bảo toàn giải thưởng dù trả lời sai ở các câu sau:
                    </p>

                    <div class="prize-preview-box" id="gsPrizeLadder">
                        <!-- Populated dynamically based on total questions -->
                    </div>
                </div>
            </div>

            <!-- Projector & TV Guide -->
            <div class="eduvn-card">
                <div class="eduvn-card-header">
                    <h6 class="eduvn-card-title">
                        <i class="bi bi-projector text-info"></i>
                        <span>Cẩm Nang Trình Chiếu Sân Khấu</span>
                    </h6>
                </div>
                <div class="eduvn-card-body small text-muted">
                    <div class="mb-2">
                        <i class="bi bi-tv me-1 text-primary"></i> <b>Kết nối màn hình:</b>
                        Cắm cáp HDMI hoặc chiếu không dây (Miracast/AirPlay) lên Smart TV hoặc máy chiếu sân trường.
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-keyboard me-1 text-primary"></i> <b>Phím tắt thao tác nhanh:</b>
                        <ul class="mb-2 ps-3 mt-1">
                            <li><span class="kbd-chip">F11</span> : Bật/Tắt Toàn màn hình (Fullscreen)</li>
                            <li><span class="kbd-chip">Space</span> : Bắt đầu / Sang câu hỏi tiếp theo</li>
                            <li><span class="kbd-chip">M</span> : Bật/Tắt âm thanh hiệu ứng kịch tính</li>
                        </ul>
                    </div>
                    <div class="mb-0">
                        <i class="bi bi-magic me-1 text-warning"></i> <b>Cơ chế tự bù câu hỏi:</b>
                        Nếu ngân hàng thiếu câu ở mức Khó (VD), hệ thống sẽ tự động bù đắp từ mức Vừa (TH) hoặc Dễ (NB) để bảo đảm đủ thời lượng trò chơi.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const GS = {
        meta: null,
        grade: 'khoi7',
        semester: '<?php echo $defaultSemester; ?>',
        subjects: [],
        counts: { NB: 5, TH: 4, VD: 4 }
    };

    const $ = (id) => document.getElementById(id);

    // Subject icon dictionary
    const SUBJ_ICONS = {
        'toan': 'bi-calculator-fill',
        'tin': 'bi-laptop-fill',
        'anh': 'bi-translate',
        'van': 'bi-book-half',
        'khtn': 'bi-radioactive',
        'su-dia': 'bi-globe-americas',
        'congnghe': 'bi-cpu-fill',
        'gdcd': 'bi-shield-check',
        'amnhac': 'bi-music-note-beamed',
        'mythuat': 'bi-palette-fill'
    };

    function getSubjIcon(code, name) {
        const c = (code || '').toLowerCase().trim();
        const n = (name || '').toLowerCase();
        if (SUBJ_ICONS[c]) return SUBJ_ICONS[c];
        if (n.includes('toán')) return 'bi-calculator-fill';
        if (n.includes('tin')) return 'bi-laptop-fill';
        if (n.includes('anh') || n.includes('ngoại ngữ')) return 'bi-translate';
        if (n.includes('văn')) return 'bi-book-half';
        if (n.includes('khoa học') || n.includes('khtn') || n.includes('sinh') || n.includes('hóa') || n.includes('lý')) return 'bi-radioactive';
        if (n.includes('sử') || n.includes('địa')) return 'bi-globe-americas';
        if (n.includes('công nghệ')) return 'bi-cpu-fill';
        return 'bi-journal-bookmark-fill';
    }

    function readUrlParams() {
        const p = new URLSearchParams(location.search);
        GS.grade = p.get('grade') || GS.grade;
        GS.semester = p.get('semester') || GS.semester;
        const subs = p.get('subjects');
        if (subs) GS.subjects = subs.split(',').map(Number).filter(n => n > 0);
        GS.counts.NB = clampInt(p.get('nb'), GS.counts.NB);
        GS.counts.TH = clampInt(p.get('th'), GS.counts.TH);
        GS.counts.VD = clampInt(p.get('vd'), GS.counts.VD);
    }

    function clampInt(v, d) {
        const n = parseInt(v, 10);
        return Number.isFinite(n) ? Math.max(0, Math.min(30, n)) : d;
    }

    function updateTotal() {
        const t = GS.counts.NB + GS.counts.TH + GS.counts.VD;
        $('gsTotal').value = t;
        $('statTotalQ').textContent = t;

        // Estimated duration (~1.2 - 1.5 minutes per question)
        const estMin = Math.max(5, Math.round(t * 1.3));
        $('statTime').textContent = '~' + estMin + 'p';
        $('statSubjCount').textContent = GS.subjects.length;

        // Update ratio bars
        const safeTotal = t > 0 ? t : 1;
        const pctNB = Math.round((GS.counts.NB / safeTotal) * 100);
        const pctTH = Math.round((GS.counts.TH / safeTotal) * 100);
        const pctVD = Math.max(0, 100 - pctNB - pctTH);

        $('barNB').style.width = pctNB + '%';
        $('barTH').style.width = pctTH + '%';
        $('barVD').style.width = pctVD + '%';
        $('gsRatioSummary').textContent = 'NB: ' + pctNB + '% · TH: ' + pctTH + '% · VD: ' + pctVD + '%';

        renderPrizeLadder(t);
    }

    function setCountInputs() {
        $('gsNB').value = GS.counts.NB;
        $('gsTH').value = GS.counts.TH;
        $('gsVD').value = GS.counts.VD;
    }

    window.gsStepCount = function (id, delta) {
        const el = $(id);
        const cur = clampInt(el.value, 0);
        const next = Math.max(0, Math.min(30, cur + delta));
        el.value = next;
        
        const key = id.replace('gs', '');
        GS.counts[key] = next;
        updateTotal();
        renderAvailability();
        clearPresetActive();
    };

    window.gsApplyPreset = function (nb, th, vd, btn) {
        GS.counts.NB = nb;
        GS.counts.TH = th;
        GS.counts.VD = vd;
        setCountInputs();
        updateTotal();
        renderAvailability();

        document.querySelectorAll('.preset-chip-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
    };

    function clearPresetActive() {
        document.querySelectorAll('.preset-chip-btn').forEach(b => b.classList.remove('active'));
    }

    window.gsSelectAll = function (select) {
        if (!GS.meta || !GS.meta.subjects) return;
        if (select) {
            // Select all subjects that have questions > 0
            GS.subjects = GS.meta.subjects.filter(s => s.total > 0).map(s => s.id);
        } else {
            GS.subjects = [];
        }
        renderSubjects();
        renderAvailability();
        updateTotal();
    };

    window.gsFilterSubjects = function () {
        const q = ($('gsSubjSearch').value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('#gsSubjects .subj-col');
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            if (!q || name.includes(q)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    };

    function renderSubjects() {
        const box = $('gsSubjects');
        box.innerHTML = '';
        if (!GS.meta || !GS.meta.subjects || !GS.meta.subjects.length) {
            box.innerHTML = '<div class="col-12 py-3 text-center text-muted">Không có dữ liệu môn học cho học kỳ này.</div>';
            return;
        }

        GS.subjects.sort((a, b) => a - b);
        const subjMeta = new Map(GS.meta.subjects.map(s => [s.id, s]));

        GS.meta.subjects.forEach((s) => {
            const col = document.createElement('div');
            col.className = 'col-sm-6 col-lg-4 col-xl-4 subj-col';
            col.setAttribute('data-name', s.name.toLowerCase());
            
            const hasQ = s.total > 0;
            const isSelected = GS.subjects.includes(s.id);
            const iconClass = getSubjIcon(s.code, s.name);

            col.innerHTML = `
                <div class="subject-card ${isSelected ? 'selected' : ''} ${hasQ ? '' : 'disabled'}" data-id="${s.id}">
                    <div class="subj-header">
                        <div class="subj-icon-box">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="subj-name">${esc(s.name)}</div>
                        <input type="checkbox" class="subj-checkbox" value="${s.id}" ${isSelected ? 'checked' : ''} ${hasQ ? '' : 'disabled'}>
                    </div>
                    <div class="subj-metrics">
                        ${hasQ ? `
                            <span class="subj-pill nb" title="Nhận biết">NB ${s.levels.NB}</span>
                            <span class="subj-pill th" title="Thông hiểu">TH ${s.levels.TH}</span>
                            <span class="subj-pill vd" title="Vận dụng">VD ${s.levels.VD}</span>
                            <span class="subj-pill total">${s.total} câu</span>
                        ` : `
                            <span class="badge bg-secondary-subtle text-secondary small">Chưa có câu hỏi</span>
                        `}
                    </div>
                </div>
            `;

            const card = col.querySelector('.subject-card');
            const inp = col.querySelector('input');

            if (hasQ) {
                card.addEventListener('click', (e) => {
                    if (e.target !== inp) {
                        inp.checked = !inp.checked;
                    }
                    if (inp.checked) {
                        if (!GS.subjects.includes(s.id)) GS.subjects.push(s.id);
                    } else {
                        GS.subjects = GS.subjects.filter(x => x !== s.id);
                    }
                    GS.subjects.sort((a, b) => a - b);
                    card.classList.toggle('selected', inp.checked);
                    updateTotal();
                    renderAvailability();
                    updateSelectedMessage(subjMeta);
                });
            }

            box.appendChild(col);
        });

        updateSelectedMessage(subjMeta);
    }

    function updateSelectedMessage(subjMeta) {
        const msgEl = $('gsSubjectsMsg');
        if (!GS.subjects.length) {
            msgEl.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Chưa chọn môn nào</span>';
        } else {
            const names = GS.subjects.map(id => subjMeta.get(id)?.name).filter(Boolean);
            msgEl.innerHTML = '<span class="text-success"><i class="bi bi-check2-circle me-1"></i>Đã chọn ' + GS.subjects.length + ' môn: <b>' + esc(names.join(', ')) + '</b></span>';
        }
        $('statSubjCount').textContent = GS.subjects.length;
    }

    function summarizeSelected() {
        const sum = { NB: 0, TH: 0, VD: 0, total: 0 };
        const meta = new Map((GS.meta ? GS.meta.subjects : []).map(s => [s.id, s]));
        GS.subjects.forEach(id => {
            const s = meta.get(id);
            if (s) {
                sum.NB += (s.levels?.NB || 0);
                sum.TH += (s.levels?.TH || 0);
                sum.VD += (s.levels?.VD || 0);
                sum.total += (s.total || 0);
            }
        });
        return sum;
    }

    const LEVEL_DEFS = [
        { key: 'NB', label: 'Mức 1 · Nhận biết (Dễ)', badgeClass: 'nb' },
        { key: 'TH', label: 'Mức 2 · Thông hiểu (Vừa)', badgeClass: 'th' },
        { key: 'VD', label: 'Mức 3 · Vận dụng (Khó)', badgeClass: 'vd' }
    ];

    function renderAvailability() {
        const metersBox = $('gsAvailMeters');
        const bannerBox = $('gsReadinessBanner');
        const iconEl = $('gsReadinessIcon');
        const titleEl = $('gsReadinessTitle');
        const descEl = $('gsReadinessDesc');
        const badgeEl = $('availStatusBadge');

        metersBox.innerHTML = '';

        if (!GS.meta) {
            metersBox.innerHTML = '<div class="text-muted small py-2 text-center">Đang tải dữ liệu...</div>';
            return;
        }

        const sum = summarizeSelected();
        $('gsTotalPool').textContent = sum.total + ' câu';

        if (!GS.subjects.length) {
            bannerBox.className = 'readiness-banner empty';
            iconEl.className = 'bi bi-info-circle-fill r-icon text-secondary';
            titleEl.textContent = 'Chờ Chọn Môn Học';
            descEl.textContent = 'Vui lòng tích chọn ít nhất một môn học ở danh sách bên trái để kiểm tra ngân hàng đề thi.';
            badgeEl.className = 'badge bg-secondary-subtle text-secondary border rounded-pill small';
            badgeEl.textContent = 'Chưa chọn môn';

            metersBox.innerHTML = '<div class="text-center text-muted small py-3"><i class="bi bi-arrow-left me-1"></i>Chọn môn học bên trái để xem thống kê câu hỏi</div>';
            return;
        }

        let allOk = true;
        let deficitMsg = [];

        const metersHtml = LEVEL_DEFS.map(def => {
            const req = GS.counts[def.key];
            const have = sum[def.key];
            const ok = req <= have;
            const pct = have > 0 ? Math.min(100, Math.round((have / (req || 1)) * 100)) : 0;

            if (!ok && req > 0) {
                allOk = false;
                deficitMsg.push(`${def.key} (thiếu ${req - have} câu)`);
            }

            return `
                <div class="avail-meter-row">
                    <div class="avail-meter-header">
                        <span class="fw-semibold text-slate-700">${def.label}</span>
                        <span class="${ok ? 'text-success' : 'text-warning'} fw-bold">
                            Cần ${req} / Có ${have}
                            <i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} ms-1"></i>
                        </span>
                    </div>
                    <div class="avail-meter-bar">
                        <div class="avail-meter-fill ${ok ? 'ok' : 'bad'}" style="width: ${Math.min(100, pct)}%;"></div>
                    </div>
                </div>
            `;
        }).join('');

        metersBox.innerHTML = metersHtml;

        if (allOk) {
            bannerBox.className = 'readiness-banner ready';
            iconEl.className = 'bi bi-check-circle-fill r-icon text-success';
            titleEl.textContent = 'Sẵn Sàng Khởi Chạy!';
            descEl.textContent = 'Ngân hàng có đủ số lượng câu hỏi phù hợp cho toàn bộ các mức độ đã thiết lập.';
            badgeEl.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill small';
            badgeEl.textContent = 'Hoàn hảo';
        } else {
            bannerBox.className = 'readiness-banner warn';
            iconEl.className = 'bi bi-exclamation-triangle-fill r-icon text-warning';
            titleEl.textContent = 'Tự Động Bù Câu Hỏi';
            descEl.textContent = `Ngân hàng hiện thiếu ở mức: ${deficitMsg.join(', ')}. Trò chơi sẽ tự động bù đắp từ các mức câu dễ hơn khi trình chiếu.`;
            badgeEl.className = 'badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small';
            badgeEl.textContent = 'Sẽ tự bù câu';
        }
    }

    function renderPrizeLadder(totalQ) {
        const ladderBox = $('gsPrizeLadder');
        if (!ladderBox) return;

        const count = Math.max(5, totalQ);
        const milestone1 = Math.min(5, Math.floor(count / 3));
        const milestone2 = Math.min(10, Math.floor((count * 2) / 3));

        ladderBox.innerHTML = `
            <div class="ladder-preview-item grand-prize">
                <span>👑 Câu ${count} · Đỉnh Cao Triệu Phú</span>
                <span class="badge bg-warning text-dark fw-bold">VÔ ĐỊCH</span>
            </div>
            <div class="ladder-preview-item ${count >= 10 ? 'safe-milestone' : ''}">
                <span>⭐ Câu ${milestone2} · Mốc An Toàn 2</span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">BẢO TOÀN</span>
            </div>
            <div class="ladder-preview-item safe-milestone">
                <span>⭐ Câu ${milestone1} · Mốc An Toàn 1</span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">BẢO TOÀN</span>
            </div>
            <div class="ladder-preview-item">
                <span>🏁 Câu 1 · Khởi động</span>
                <span class="text-white-50">Nhận biết</span>
            </div>
        `;
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function buildGameUrl() {
        const p = new URLSearchParams();
        p.set('grade', GS.grade);
        p.set('semester', GS.semester);
        if (GS.subjects.length) p.set('subjects', GS.subjects.join(','));
        p.set('nb', GS.counts.NB);
        p.set('th', GS.counts.TH);
        p.set('vd', GS.counts.VD);
        return '../html/ai-la-trieu-phu.html?' + p.toString();
    }

    window.gsLoadMeta = function (isRefresh = false) {
        const p = new URLSearchParams({ action: 'meta', grade: GS.grade, semester: GS.semester });
        if (isRefresh) {
            $('gsSubjects').innerHTML = '<div class="col-12 py-4 text-center text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Đang làm mới dữ liệu...</div>';
        }
        fetch('../api/game_questions.php?' + p.toString())
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message || 'Không tải được dữ liệu ngân hàng câu hỏi.');
                GS.meta = d;
                renderSubjects();
                renderAvailability();
                updateTotal();
            })
            .catch(e => {
                $('gsSubjects').innerHTML = '<div class="col-12 text-danger small py-3"><i class="bi bi-x-circle me-1"></i>' + esc(e.message || 'Không kết nối được máy chủ.') + '</div>';
                $('gsAvailMeters').innerHTML = '<div class="text-danger small py-2 text-center">' + esc(e.message) + '</div>';
            });
    };

    window.gsOpenGame = function () {
        if (!GS.subjects.length) {
            alert('Vui lòng chọn ít nhất một môn học để khởi chạy trò chơi.');
            return;
        }
        updateTotal();
        const win = window.open(buildGameUrl(), '_blank', 'noopener');
        if (!win) {
            alert('Trình duyệt đã chặn cửa sổ bật lên. Vui lòng cấp quyền mở tab mới để trình chiếu trò chơi.');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        readUrlParams();
        setCountInputs();
        updateTotal();

        // Grade & Semester selectors
        const gSel = $('gsGrade'), sSel = $('gsSemester');
        const grades = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
        const gradeLabels = { khoi6: 'Khối 6 (Lớp 6)', khoi7: 'Khối 7 (Lớp 7)', khoi8: 'Khối 8 (Lớp 8)', khoi9: 'Khối 9 (Lớp 9)' };
        grades.forEach(g => {
            const o = document.createElement('option');
            o.value = g; o.textContent = gradeLabels[g]; gSel.add(o);
        });
        gSel.value = GS.grade;
        $('statGrade').textContent = gradeLabels[GS.grade] ? gradeLabels[GS.grade].split(' ')[0] + ' ' + gradeLabels[GS.grade].split(' ')[1] : GS.grade;

        ['hk1', 'hk2'].forEach(h => {
            const o = document.createElement('option');
            o.value = h; o.textContent = h === 'hk1' ? 'Học kỳ 1' : 'Học kỳ 2'; sSel.add(o);
        });
        sSel.value = GS.semester;

        gSel.addEventListener('change', () => { 
            GS.grade = gSel.value; 
            $('statGrade').textContent = gradeLabels[GS.grade] ? gradeLabels[GS.grade].split(' ')[0] + ' ' + gradeLabels[GS.grade].split(' ')[1] : GS.grade;
            gsLoadMeta(); 
        });
        sSel.addEventListener('change', () => { 
            GS.semester = sSel.value; 
            gsLoadMeta(); 
        });

        // Direct input change listeners
        ['gsNB', 'gsTH', 'gsVD'].forEach((id, i) => {
            $(id).addEventListener('input', () => {
                GS.counts[['NB', 'TH', 'VD'][i]] = clampInt($(id).value, 0);
                updateTotal();
                renderAvailability();
                clearPresetActive();
            });
        });

        gsLoadMeta();
    });
})();
</script>

<?php include '../includes/teacher_footer.php'; ?>