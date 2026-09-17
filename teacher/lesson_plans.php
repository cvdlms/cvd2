<?php
include '../includes/session_check.php';
include '../includes/premium_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

// Check Premium status
$username = $_SESSION['username'];
$isPremiumUser = isPremiumUser($username);

if (!$isPremiumUser) {
    $_SESSION['error'] = 'Chức năng Kế Hoạch Bài Dạy chỉ dành cho giáo viên Premium!';
    header('Location: teacher.php');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

// Load teacher's assigned subjects and classes
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$teacherSubjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
$allSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$teacherClasses = json_decode(file_get_contents($teacherClassesFile), true) ?: [];
$allClasses = json_decode(file_get_contents($classesFile), true) ?: [];

// Get assigned subject IDs and class IDs for this teacher
$assignedSubjectIds = $teacherSubjects[$username] ?? [];
$assignedClassIds = $teacherClasses[$username] ?? [];

// Filter subjects and classes
$assignedSubjects = array_filter($allSubjects, function($subj) use ($assignedSubjectIds) {
    return in_array($subj['id'], $assignedSubjectIds);
});

$assignedClasses = [];
foreach ($allClasses as $class) {
    if (in_array($class['id'], $assignedClassIds)) {
        $assignedClasses[] = [
            'id' => $class['id'],
            'code' => $class['code'],
            'name' => $class['name']
        ];
    }
}

// Create subjects lookup
$subjects = [];
foreach ($allSubjects as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Kế Hoạch Bài Dạy - CVD';
include '../includes/teacher_header.php';
?>

<style>
    /* Professional View Modal Styles */
    #viewModal .modal-dialog {
        max-width: 90%;
    }
    
    #viewModal .modal-body {
        padding: 20px 30px;
        max-height: 75vh;
        overflow-y: auto;
    }
    
    #viewModalBody {
        font-family: 'Times New Roman', serif;
        font-size: 14px;
        line-height: 1.8;
        color: #333;
    }
    
    .view-header {
        background: var(--grad-accent);
        color: white;
        padding: 25px;
        border-radius: 8px;
        margin: -20px -20px 25px -20px;
        text-align: center;
    }
    
    .view-header h3 {
        font-size: 20px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 12px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }
    
    .view-header .meta-info {
        font-size: 13px;
        opacity: 0.95;
    }
    
    .view-section {
        margin-bottom: 30px;
        page-break-inside: avoid;
    }
    
    .view-section-title {
        font-size: 16px;
        font-weight: bold;
        color: var(--accent);
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--accent);
        text-transform: uppercase;
    }
    
    .view-objectives {
        background: var(--accent-light);
        padding: 15px 20px;
        border-radius: 8px;
        border-left: 4px solid var(--accent);
    }
    
    .view-objective-item {
        margin: 12px 0;
        padding-left: 20px;
    }
    
    .view-objective-item strong {
        color: var(--muted-strong);
        display: inline-block;
        min-width: 120px;
    }
    
    .view-activity {
        background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 5px solid var(--accent);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .view-activity-title {
        font-size: 15px;
        font-weight: bold;
        color: var(--accent);
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--border);
    }
    
    .view-activity-section {
        margin: 15px 0;
    }
    
    .view-activity-section-title {
        font-weight: 600;
        color: var(--muted-strong);
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .view-activity-content {
        padding-left: 20px;
        text-align: justify;
    }
    
    .view-sub-item {
        margin: 10px 0;
        padding-left: 15px;
        border-left: 2px solid var(--border-soft);
    }
    
    .view-sub-item-title {
        font-style: italic;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 5px;
    }
    
    .formatted-content {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .formatted-content strong,
    .formatted-content b {
        font-weight: bold;
        color: var(--ink);
    }
    
    .formatted-content em,
    .formatted-content i {
        font-style: italic;
    }
    
    .formatted-content .math-inline {
        font-family: 'Times New Roman', serif;
        color: var(--accent);
    }
    
    .formatted-content .multiple-choice {
        margin: 10px 0;
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .formatted-content .choice-option {
        padding: 5px 0;
        padding-left: 25px;
        position: relative;
    }
    
    .formatted-content .choice-option::before {
        content: "○";
        position: absolute;
        left: 5px;
        font-size: 14px;
        color: var(--muted);
    }
    
    .formatted-content ul {
        margin: 10px 0;
        padding-left: 25px;
    }
    
    .formatted-content ol {
        margin: 10px 0;
        padding-left: 25px;
    }
    
    .formatted-content li {
        margin: 5px 0;
    }
    
    .equipment-list {
        background: var(--warning-light);
        border-left: 4px solid var(--warning);
        padding: 15px 20px;
        border-radius: 5px;
    }
    
    .homework-box {
        background: var(--info-light);
        border-left: 4px solid var(--info);
        padding: 15px 20px;
        border-radius: 5px;
    }
</style>
<link rel="stylesheet" href="assets/lesson_plans.css">

<div class="container-fluid khbd-workspace">
    <div class="section-header justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1 1 auto;">
            <div class="sh-icon flex-shrink-0">
                <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <div style="min-width: 0;">
                <h3 class="mb-0">Kế hoạch bài dạy (KHBD)</h3>
                <p class="mb-0">Lưu trữ, chia sẻ và xuất kế hoạch bài dạy theo môn học, lớp và ngày dạy.</p>
            </div>
        </div>
    </div>

    <div class="stat-row mb-4" aria-label="Tổng quan kế hoạch bài dạy">
        <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-journal-text"></i></div><div><div class="stat-value" id="statTotal">0</div><div class="stat-label">Tổng số KHBD</div></div></div>
        <div class="stat-card"><div class="stat-icon success"><i class="bi bi-calendar2-week"></i></div><div><div class="stat-value" id="statUpcoming">0</div><div class="stat-label">Sắp đến ngày dạy</div></div></div>
        <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-calendar-check"></i></div><div><div class="stat-value" id="statThisMonth">0</div><div class="stat-label">Trong tháng này</div></div></div>
        <div class="stat-card"><div class="stat-icon violet"><i class="bi bi-people"></i></div><div><div class="stat-value" id="statShared">0</div><div class="stat-label">Đang chia sẻ</div></div></div>
    </div>

    <section class="khbd-panel">
        <div class="khbd-toolbar">
            <div class="khbd-filter-grid">
                <div>
                    <label class="khbd-filter-label" for="filterKeyword">Tìm kiếm</label>
                    <div class="khbd-input-wrap"><i class="bi bi-search"></i><input id="filterKeyword" type="search" class="form-control" placeholder="Tên bài dạy, tiết PPCT..."></div>
                </div>
                <div>
                    <label class="khbd-filter-label" for="filterSubject">Môn học</label>
                    <select id="filterSubject" class="form-select">
                        <option value="">Tất cả môn học</option>
                        <?php foreach ($assignedSubjects as $subj): ?>
                            <option value="<?php echo htmlspecialchars($subj['id']); ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="khbd-filter-label" for="filterClass">Lớp học</label>
                    <select id="filterClass" class="form-select">
                        <option value="">Tất cả lớp học</option>
                        <?php foreach ($assignedClasses as $cls): ?>
                            <option value="<?php echo htmlspecialchars($cls['id']); ?>"><?php echo htmlspecialchars($cls['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="khbd-filter-label" for="filterDate">Ngày dạy</label><input type="date" id="filterDate" class="form-control"></div>
                <div>
                    <label class="khbd-filter-label" for="filterStatus">Phạm vi</label>
                    <select id="filterStatus" class="form-select"><option value="">Tất cả</option><option value="mine">KHBD của tôi</option><option value="shared">Tôi đang chia sẻ</option><option value="received">Được đồng nghiệp chia sẻ</option></select>
                </div>
                <button type="button" class="khbd-reset-btn" onclick="resetFilters()" title="Xóa bộ lọc" aria-label="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></button>
            </div>
        </div>

        <div class="khbd-list-heading"><h2>Danh sách kế hoạch bài dạy</h2><div class="khbd-result-count" id="resultCount">Đang tải dữ liệu...</div></div>
        <div class="khbd-table-wrap">
            <table id="lessonPlansTable" class="table table-hover align-middle">
                <thead><tr><th>Bài dạy</th><th>Môn học</th><th>Số tiết</th><th>Ngày dạy</th><th>Phạm vi</th><th>Cập nhật</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody id="lessonPlansBody"></tbody>
            </table>
            <div class="khbd-empty d-none" id="lessonPlansEmpty">
                <div class="khbd-empty-icon"><i class="bi bi-journal-plus"></i></div><h3>Chưa có kế hoạch bài dạy phù hợp</h3><p class="mb-3">Hãy thay đổi bộ lọc để tìm kế hoạch bài dạy.</p>
            </div>
        </div>
    </section>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Xem Kế Hoạch Bài Dạy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="exportWord(currentViewId)">
                    <i class="bi bi-file-word me-2"></i>Xuất Word
                </button>
                <button type="button" class="btn btn-danger" onclick="exportPDF(currentViewId)">
                    <i class="bi bi-file-pdf me-2"></i>Xuất PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- KaTeX for Math Rendering -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<script>
let currentViewId = null;
let lessonPlansData = [];
let dataTable;

const currentUsername = <?php echo json_encode($username, JSON_UNESCAPED_UNICODE); ?>;
const subjectLookup = <?php echo json_encode($subjects, JSON_UNESCAPED_UNICODE); ?>;

$(document).ready(function() {
    dataTable = $('#lessonPlansTable').DataTable({
        responsive: true,
        language: {
            emptyTable: 'Chưa có kế hoạch bài dạy',
            info: 'Hiển thị _START_–_END_ trong _TOTAL_ KHBD',
            infoEmpty: 'Không có dữ liệu',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            paginate: { previous: 'Trước', next: 'Sau' },
            zeroRecords: 'Không tìm thấy KHBD phù hợp'
        },
        pageLength: 10,
        lengthMenu: [10, 20, 50],
        order: [[5, 'desc']],
        columnDefs: [
            { orderable: false, targets: 6 },
            { className: 'text-end', targets: 6 }
        ]
    });

    $('#filterKeyword').on('input', applyFilters);
    $('#filterSubject, #filterClass, #filterDate, #filterStatus').on('change', applyFilters);
    loadLessonPlans();
});

function loadLessonPlans() {
    $('#resultCount').text('Đang tải dữ liệu...');
    $.ajax({
        url: 'api/lesson_plans_api.php',
        method: 'GET',
        data: { action: 'list' },
        success: function(response) {
            if (response.success) {
                lessonPlansData = Array.isArray(response.data) ? response.data : [];
                updateStatistics();
                applyFilters();
            } else {
                showLessonPlanLoadError(response.message || 'Không thể tải dữ liệu.');
            }
        },
        error: function() {
            showLessonPlanLoadError('Không thể kết nối đến máy chủ.');
        }
    });
}

function showLessonPlanLoadError(message) {
    lessonPlansData = [];
    renderLessonPlans([]);
    $('#resultCount').text(message);
}

function updateStatistics() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const nextWeek = new Date(today);
    nextWeek.setDate(today.getDate() + 7);
    const ownPlans = lessonPlansData.filter(plan => plan.teacher_username === currentUsername);
    const upcoming = ownPlans.filter(plan => {
        const teachingDate = parseLocalDate(plan.basic_info && plan.basic_info.ngay_day);
        return teachingDate && teachingDate >= today && teachingDate <= nextWeek;
    }).length;
    const thisMonth = ownPlans.filter(plan => {
        const teachingDate = parseLocalDate(plan.basic_info && plan.basic_info.ngay_day);
        return teachingDate && teachingDate.getMonth() === today.getMonth() && teachingDate.getFullYear() === today.getFullYear();
    }).length;
    const shared = ownPlans.filter(plan => Boolean(plan.share_with_others)).length;
    $('#statTotal').text(ownPlans.length);
    $('#statUpcoming').text(upcoming);
    $('#statThisMonth').text(thisMonth);
    $('#statShared').text(shared);
}

function renderLessonPlans(plans) {
    dataTable.clear();
    plans.forEach(plan => {
        const isOwner = plan.teacher_username === currentUsername;
        const subjectName = subjectLookup[plan.subject_id] || plan.subject_id || 'Chưa xác định';
        const basicInfo = plan.basic_info || {};
        const status = getPlanStatus(plan, isOwner);
        const updatedAt = plan.updated_at || plan.created_at;
        const safeId = escapeJs(plan.id);
        const ppct = basicInfo.tiet_ppct ? 'Tiết PPCT: ' + escapeHtml(basicInfo.tiet_ppct) : 'Chưa nhập tiết PPCT';
        const ownerActions = isOwner
            ? '<button class="khbd-action-btn danger" type="button" onclick="deleteLessonPlan(\'' + safeId + '\')" title="Xóa"><i class="bi bi-trash"></i></button>'
            : '';
        const actions = '<div class="khbd-actions">'
            + '<button class="khbd-action-btn" type="button" onclick="viewLessonPlan(\'' + safeId + '\')" title="Xem KHBD"><i class="bi bi-eye"></i></button>'
            + '<button class="khbd-action-btn" type="button" onclick="exportWord(\'' + safeId + '\')" title="Xuất Word"><i class="bi bi-file-earmark-word"></i></button>'
            + '<button class="khbd-action-btn" type="button" onclick="exportPDF(\'' + safeId + '\')" title="Xuất PDF"><i class="bi bi-file-earmark-pdf"></i></button>'
            + ownerActions + '</div>';

        dataTable.row.add([
            '<div class="khbd-plan-title">' + escapeHtml(basicInfo.ten_bai_day || 'Chưa đặt tên') + '</div><div class="khbd-plan-meta">' + ppct + '</div>',
            '<span class="khbd-subject-badge"><i class="bi bi-book"></i>' + escapeHtml(subjectName) + '</span>',
            '<strong>' + escapeHtml(String(basicInfo.so_tiet || 0)) + '</strong> tiết',
            formatDisplayDate(basicInfo.ngay_day),
            '<span class="khbd-status-badge ' + status.className + '"><i class="bi ' + status.icon + '"></i>' + status.label + '</span>',
            formatDisplayDateTime(updatedAt),
            actions
        ]);
    });
    dataTable.draw();
    $('#resultCount').text(plans.length + ' kế hoạch bài dạy');
    $('#lessonPlansTable_wrapper').toggleClass('d-none', plans.length === 0);
    $('#lessonPlansEmpty').toggleClass('d-none', plans.length !== 0);
}

function getPlanStatus(plan, isOwner) {
    if (!isOwner) return { className: 'received', icon: 'bi-people', label: 'Được chia sẻ' };
    if (plan.share_with_others) return { className: 'shared', icon: 'bi-share', label: 'Đang chia sẻ' };
    return { className: 'private', icon: 'bi-lock', label: 'Cá nhân' };
}

function parseLocalDate(value) {
    if (!value) return null;
    const parts = value.split('-').map(Number);
    if (parts.length !== 3) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

function formatDisplayDate(value) {
    const date = parseLocalDate(value);
    return date ? date.toLocaleDateString('vi-VN') : '<span class="text-muted">Chưa có</span>';
}

function formatDisplayDateTime(value) {
    if (!value) return '<span class="text-muted">Chưa có</span>';
    const date = new Date(value.replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? escapeHtml(value) : date.toLocaleDateString('vi-VN');
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(value) {
    return String(value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

// Format content with support for math, markdown-like syntax, and multiple choice
function formatContent(text) {
    if (!text || text === 'N/A') return '<span class="text-muted">Chưa có nội dung</span>';
    
    // Escape HTML first
    let formatted = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    
    // Format **bold** text
    formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    
    // Format *italic* text
    formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
    
    // Format __underline__ text
    formatted = formatted.replace(/__(.+?)__/g, '<u>$1</u>');
    
    // Detect and highlight multiple choice questions
    // Pattern: A. ... B. ... C. ... D. ...
    const mcPattern = /([A-D])\.\.?\s*([^\n]+)/gi;
    if (mcPattern.test(formatted)) {
        formatted = formatted.replace(/([A-D])\.\.?\s*([^\n]+)/gi, 
            '<div class="choice-option"><strong>$1.</strong> $2</div>');
    }
    
    // Convert numbered lists (1. 2. 3.)
    formatted = formatted.replace(/^(\d+)\.\.?\s*(.+)$/gm, '<li>$2</li>');
    if (formatted.includes('<li>')) {
        formatted = '<ol>' + formatted + '</ol>';
    }
    
    // Convert bullet points (- or •)
    formatted = formatted.replace(/^[-•]\s+(.+)$/gm, '<li>$1</li>');
    
    // Preserve line breaks
    formatted = formatted.replace(/\n/g, '<br>');
    
    return '<div class="formatted-content">' + formatted + '</div>';
}

// Render math expressions using KaTeX
function renderMath(element) {
    if (typeof renderMathInElement !== 'undefined') {
        renderMathInElement(element, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ],
            throwOnError: false
        });
    }
}

function viewLessonPlan(id) {
    const plan = lessonPlansData.find(p => p.id === id);
    if (!plan) return;
    
    currentViewId = id;
    const subjectName = <?php echo json_encode($subjects); ?>[plan.subject_id] || plan.subject_id;
    
    // Get class names
    const classNames = plan.class_ids.map(cid => {
        const cls = <?php echo json_encode($assignedClasses); ?>.find(c => c.id === cid);
        return cls ? cls.name : cid;
    }).join(', ');
    
    let html = `
        <div class="view-header">
            <h3>${plan.basic_info.ten_bai_day}</h3>
            <div class="meta-info">
                <div class="mb-2">
                    <strong>Số tiết:</strong> ${plan.basic_info.so_tiet} &nbsp;|&nbsp; 
                    <strong>Tiết PPCT:</strong> ${plan.basic_info.tiet_ppct || 'N/A'} &nbsp;|&nbsp; 
                    <strong>Ngày dạy:</strong> ${plan.basic_info.ngay_day}
                </div>
                <div>
                    <strong>Môn học:</strong> ${subjectName} &nbsp;|&nbsp; 
                    <strong>Lớp:</strong> ${classNames}
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h5 class="view-section-title">1. Mục Tiêu</h5>
            <div class="view-objectives">
                <div class="view-objective-item">
                    <strong>• Kiến thức:</strong><br>
                    ${formatContent(plan.muc_tieu.kien_thuc)}
                </div>
                <div class="view-objective-item">
                    <strong>• Năng lực:</strong><br>
                    ${formatContent(plan.muc_tieu.nang_luc)}
                </div>
                <div class="view-objective-item">
                    <strong>• Năng lực số:</strong><br>
                    ${formatContent(plan.muc_tieu.nang_luc_so)}
                </div>
                <div class="view-objective-item">
                    <strong>• Phẩm chất:</strong><br>
                    ${formatContent(plan.muc_tieu.pham_chat)}
                </div>
            </div>
        </div>
        
        <div class="view-section">
            <h5 class="view-section-title">2. Thiết Bị Dạy Học và Học Liệu</h5>
            <div class="equipment-list">
                ${formatContent(plan.thiet_bi)}
            </div>
        </div>
        
        <div class="view-section">
            <h5 class="view-section-title">3. Tiến Trình Dạy Học</h5>
    `;
    
    plan.hoat_dong.forEach((hd, idx) => {
        html += `
            <div class="view-activity">
                <div class="view-activity-title">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Hoạt động ${idx + 1}: ${hd.ten}
                </div>
                
                <div class="view-activity-section">
                    <div class="view-activity-section-title">a) Mục tiêu:</div>
                    <div class="view-activity-content">
                        ${formatContent(hd.muc_tieu)}
                    </div>
                </div>
                
                <div class="view-activity-section">
                    <div class="view-activity-section-title">b) Nội dung:</div>
                    <div class="view-activity-content">
                        ${formatContent(hd.noi_dung)}
                    </div>
                </div>
                
                <div class="view-activity-section">
                    <div class="view-activity-section-title">c) Sản phẩm:</div>
                    <div class="view-activity-content">
                        ${formatContent(hd.san_pham)}
                    </div>
                </div>
                
                <div class="view-activity-section">
                    <div class="view-activity-section-title">d) Tổ chức thực hiện:</div>
                    <div class="view-activity-content">
                        <div class="view-sub-item">
                            <div class="view-sub-item-title">→ Giao nhiệm vụ học tập:</div>
                            ${formatContent(hd.to_chuc.giao_nhiem_vu)}
                        </div>
                        <div class="view-sub-item">
                            <div class="view-sub-item-title">→ Thực hiện nhiệm vụ (HS thực hiện; GV theo dõi, hỗ trợ):</div>
                            ${formatContent(hd.to_chuc.thuc_hien)}
                        </div>
                        <div class="view-sub-item">
                            <div class="view-sub-item-title">→ Báo cáo, thảo luận (GV tổ chức, điều hành; HS báo cáo, thảo luận):</div>
                            ${formatContent(hd.to_chuc.bao_cao)}
                        </div>
                        <div class="view-sub-item">
                            <div class="view-sub-item-title">→ Kết luận, nhận định:</div>
                            ${formatContent(hd.to_chuc.ket_luan)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
        
        <div class="view-section">
            <h5 class="view-section-title">4. Hướng Dẫn Về Nhà</h5>
            <div class="homework-box">
                ${formatContent(plan.huong_dan_ve_nha)}
            </div>
        </div>
    `;
    
    $('#viewModalBody').html(html);
    
    // Render math expressions
    setTimeout(() => {
        renderMath(document.getElementById('viewModalBody'));
    }, 100);
    
    $('#viewModal').modal('show');
}

function deleteLessonPlan(id) {
    if (!confirm('Bạn có chắc muốn xóa kế hoạch bài dạy này?')) return;
    
    $.ajax({
        url: 'api/lesson_plans_api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'delete', id: id }),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadLessonPlans();
            } else {
                alert('Lỗi: ' + response.message);
            }
        }
    });
}

function exportWord(id) {
    window.open(`export_lesson_plan_word.php?id=${id}`, '_blank');
}

function exportPDF(id) {
    window.open(`export_lesson_plan_pdf.php?id=${id}`, '_blank');
}

function applyFilters() {
    const keyword = $('#filterKeyword').val().trim().toLocaleLowerCase('vi');
    const subject = $('#filterSubject').val();
    const classId = $('#filterClass').val();
    const date = $('#filterDate').val();
    const status = $('#filterStatus').val();

    const filtered = lessonPlansData.filter(plan => {
        const basicInfo = plan.basic_info || {};
        const searchableText = [
            basicInfo.ten_bai_day,
            basicInfo.tiet_ppct,
            subjectLookup[plan.subject_id]
        ].filter(Boolean).join(' ').toLocaleLowerCase('vi');
        const isOwner = plan.teacher_username === currentUsername;

        if (keyword && !searchableText.includes(keyword)) return false;
        if (subject && plan.subject_id !== subject) return false;
        if (classId && !(plan.class_ids || []).includes(classId)) return false;
        if (date && basicInfo.ngay_day !== date) return false;
        if (status === 'mine' && !isOwner) return false;
        if (status === 'shared' && (!isOwner || !plan.share_with_others)) return false;
        if (status === 'received' && isOwner) return false;
        return true;
    });

    renderLessonPlans(filtered);
}

function resetFilters() {
    $('#filterKeyword').val('');
    $('#filterSubject, #filterClass, #filterDate, #filterStatus').val('');
    applyFilters();
}
</script>

<?php include '../includes/teacher_footer.php'; ?>
