<?php
include '../includes/session_check.php';
include '../includes/premium_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
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
    .step-wizard {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }
    
    .step-wizard::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #dee2e6;
        z-index: 0;
    }
    
    .step-item {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 8px;
        border: 3px solid #fff;
    }
    
    .step-item.active .step-circle {
        background: #0d6efd;
        color: white;
    }
    
    .step-item.completed .step-circle {
        background: #198754;
        color: white;
    }
    
    .step-label {
        display: block;
        font-size: 12px;
        color: #6c757d;
    }
    
    .step-item.active .step-label {
        color: #0d6efd;
        font-weight: 600;
    }
    
    .activity-panel {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .activity-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }
    
    .activity-header:hover {
        opacity: 0.9;
    }
    
    .activity-header.collapsed {
        background: linear-gradient(135deg, #a8b3e8 0%, #b896c7 100%);
    }
    
    .activity-body {
        padding: 20px;
        background: #f8f9fa;
    }
    
    .sub-section {
        background: white;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        border-left: 4px solid #667eea;
    }
    
    .sub-section-title {
        font-weight: 600;
        color: #667eea;
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .lesson-plan-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .lesson-plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .share-badge {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        color: #0d6efd;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #0d6efd;
        text-transform: uppercase;
    }
    
    .view-objectives {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 8px;
        border-left: 4px solid #0d6efd;
    }
    
    .view-objective-item {
        margin: 12px 0;
        padding-left: 20px;
    }
    
    .view-objective-item strong {
        color: #495057;
        display: inline-block;
        min-width: 120px;
    }
    
    .view-activity {
        background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 5px solid #667eea;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .view-activity-title {
        font-size: 15px;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #dee2e6;
    }
    
    .view-activity-section {
        margin: 15px 0;
    }
    
    .view-activity-section-title {
        font-weight: 600;
        color: #495057;
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
        border-left: 2px solid #e9ecef;
    }
    
    .view-sub-item-title {
        font-style: italic;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .formatted-content {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .formatted-content strong,
    .formatted-content b {
        font-weight: bold;
        color: #212529;
    }
    
    .formatted-content em,
    .formatted-content i {
        font-style: italic;
    }
    
    .formatted-content .math-inline {
        font-family: 'Times New Roman', serif;
        color: #0d6efd;
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
        color: #6c757d;
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
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px 20px;
        border-radius: 5px;
    }
    
    .homework-box {
        background: #d1ecf1;
        border-left: 4px solid #0dcaf0;
        padding: 15px 20px;
        border-radius: 5px;
    }
</style>
<link rel="stylesheet" href="assets/lesson_plans.css">

<div class="container-fluid khbd-workspace">
    <section class="khbd-hero">
        <div>
            <div class="khbd-eyebrow">Hồ sơ chuyên môn</div>
            <h1>Kế hoạch bài dạy (KHBD)</h1>
            <p>Xây dựng, lưu trữ và chia sẻ kế hoạch bài dạy theo tiến trình tổ chức hoạt động học. Dữ liệu được quản lý theo môn học, lớp và ngày dạy.</p>
        </div>
        <button class="btn khbd-primary-btn" type="button" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-2"></i>Tạo KHBD mới
        </button>
    </section>

    <section class="khbd-stats" aria-label="Tổng quan kế hoạch bài dạy">
        <div class="khbd-stat"><div class="khbd-stat-icon"><i class="bi bi-journal-text"></i></div><div><div class="khbd-stat-value" id="statTotal">0</div><div class="khbd-stat-label">Tổng số KHBD</div></div></div>
        <div class="khbd-stat"><div class="khbd-stat-icon"><i class="bi bi-calendar2-week"></i></div><div><div class="khbd-stat-value" id="statUpcoming">0</div><div class="khbd-stat-label">Sắp đến ngày dạy</div></div></div>
        <div class="khbd-stat"><div class="khbd-stat-icon"><i class="bi bi-calendar-check"></i></div><div><div class="khbd-stat-value" id="statThisMonth">0</div><div class="khbd-stat-label">Trong tháng này</div></div></div>
        <div class="khbd-stat"><div class="khbd-stat-icon"><i class="bi bi-people"></i></div><div><div class="khbd-stat-value" id="statShared">0</div><div class="khbd-stat-label">Đang chia sẻ</div></div></div>
    </section>

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
                <div class="khbd-empty-icon"><i class="bi bi-journal-plus"></i></div><h3>Chưa có kế hoạch bài dạy phù hợp</h3><p class="mb-3">Hãy thay đổi bộ lọc hoặc tạo KHBD đầu tiên.</p>
                <button type="button" class="btn khbd-primary-btn" onclick="openCreateModal()"><i class="bi bi-plus-lg me-2"></i>Tạo KHBD</button>
            </div>
        </div>
    </section>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="lessonPlanModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="bi bi-journal-plus me-2"></i>Tạo Kế Hoạch Bài Dạy Mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step Wizard -->
                <div class="step-wizard">
                    <div class="step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <span class="step-label">Thông Tin</span>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <span class="step-label">Mục Tiêu</span>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <span class="step-label">Hoạt Động</span>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-circle">4</div>
                        <span class="step-label">Hoàn Tất</span>
                    </div>
                </div>

                <!-- Formatting Guide -->
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <strong><i class="bi bi-lightbulb me-2"></i>Mẹo định dạng:</strong>
                    <ul class="mb-0 mt-2 small">
                        <li><strong>In đậm:</strong> **văn bản** hoặc __văn bản__</li>
                        <li><strong>In nghiêng:</strong> *văn bản*</li>
                        <li><strong>Công thức toán:</strong> $x^2 + y^2 = z^2$ hoặc $$\frac{a}{b}$$</li>
                        <li><strong>Trắc nghiệm:</strong> A. Đáp án 1 B. Đáp án 2 C. Đáp án 3 D. Đáp án 4</li>
                        <li><strong>Danh sách:</strong> Bắt đầu dòng với - hoặc số thứ tự 1. 2. 3.</li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form id="lessonPlanForm">
                    <input type="hidden" id="lessonPlanId" name="id">
                    
                    <!-- Step 1: Basic Info -->
                    <div class="step-content" data-step="1">
                        <h5 class="mb-4 text-primary">Thông Tin Cơ Bản</h5>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Tên Bài Dạy <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ten_bai_day" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Số Tiết <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="so_tiet" min="1" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Tiết PPCT</label>
                                <input type="text" class="form-control" name="tiet_ppct" placeholder="VD: 1-2">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Ngày Dạy <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="ngay_day" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Môn Học <span class="text-danger">*</span></label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php foreach ($assignedSubjects as $subj): ?>
                                        <option value="<?php echo htmlspecialchars($subj['id']); ?>">
                                            <?php echo htmlspecialchars($subj['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Lớp Học <span class="text-danger">*</span></label>
                                <select class="form-select" name="class_ids[]" multiple size="5" required>
                                    <?php foreach ($assignedClasses as $cls): ?>
                                        <option value="<?php echo htmlspecialchars($cls['id']); ?>">
                                            <?php echo htmlspecialchars($cls['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Giữ Ctrl để chọn nhiều lớp</small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Objectives -->
                    <div class="step-content d-none" data-step="2">
                        <h5 class="mb-4 text-primary">Mục Tiêu & Thiết Bị</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kiến Thức</label>
                            <textarea class="form-control" name="kien_thuc" rows="3" placeholder="VD: Học sinh hiểu được khái niệm **phương trình bậc hai**: $ax^2 + bx + c = 0$
- Nắm được công thức nghiệm
- Biết cách áp dụng vào bài toán thực tế"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Năng Lực</label>
                            <textarea class="form-control" name="nang_luc" rows="3" placeholder="Mục tiêu về năng lực..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Năng Lực Số</label>
                            <textarea class="form-control" name="nang_luc_so" rows="3" placeholder="Mục tiêu về năng lực số..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phẩm Chất</label>
                            <textarea class="form-control" name="pham_chat" rows="3" placeholder="Mục tiêu về phẩm chất..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Thiết Bị Dạy Học và Học Liệu</label>
                            <textarea class="form-control" name="thiet_bi" rows="4" placeholder="VD:
- Máy chiếu, máy tính
- Bảng phụ, bút dạ màu
- **Tài liệu:** SGK Toán 9 trang 45-48
- Phiếu học tập, phiếu bài tập"></textarea>
                        </div>
                    </div>

                    <!-- Step 3: Activities -->
                    <div class="step-content d-none" data-step="3">
                        <h5 class="mb-4 text-primary">Tiến Trình Dạy Học (4 Hoạt Động)</h5>
                        
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="activity-panel" id="activity-<?php echo $i; ?>">
                            <div class="activity-header" onclick="toggleActivity(<?php echo $i; ?>)">
                                <div>
                                    <i class="bi bi-clipboard-check me-2"></i>
                                    <strong>Hoạt Động <?php echo $i; ?>:</strong>
                                    <span class="activity-title-preview ms-2">
                                        <?php 
                                            $titles = [
                                                1 => 'Xác định vấn đề/nhiệm vụ học tập/Mở đầu',
                                                2 => 'Hình thành kiến thức mới/giải quyết vấn đề',
                                                3 => 'Luyện tập',
                                                4 => 'Vận dụng'
                                            ];
                                            echo $titles[$i];
                                        ?>
                                    </span>
                                </div>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="activity-body collapse show">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên Hoạt Động</label>
                                    <input type="text" class="form-control activity-name" 
                                           name="hoat_dong[<?php echo $i-1; ?>][ten]" 
                                           placeholder="VD: <?php echo $titles[$i]; ?>"
                                           value="<?php echo $titles[$i]; ?>">
                                </div>
                                
                                <div class="sub-section">
                                    <div class="sub-section-title">a) Mục tiêu</div>
                                    <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][muc_tieu]" rows="2"></textarea>
                                </div>
                                
                                <div class="sub-section">
                                    <div class="sub-section-title">b) Nội dung</div>
                                    <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][noi_dung]" rows="3" placeholder="<?php if($i==2): ?>VD: Nghiên cứu công thức nghiệm:
Câu hỏi: Cho phương trình $ax^2 + bx + c = 0$ (a≠0), tìm x?
**Bước 1:** Chia 2 vế cho a
**Bước 2:** Chuyển vế và biến đổi...<?php endif; ?>"></textarea>
                                </div>
                                
                                <div class="sub-section">
                                    <div class="sub-section-title">c) Sản phẩm</div>
                                    <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][san_pham]" rows="2"></textarea>
                                </div>
                                
                                <div class="sub-section">
                                    <div class="sub-section-title">d) Tổ chức thực hiện</div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-arrow-right-circle me-2"></i>Giao nhiệm vụ học tập:</label>
                                        <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][giao_nhiem_vu]" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-arrow-right-circle me-2"></i>Thực hiện nhiệm vụ (HS thực hiện; GV theo dõi, hỗ trợ):</label>
                                        <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][thuc_hien]" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-arrow-right-circle me-2"></i>Báo cáo, thảo luận (GV tổ chức, điều hành; HS báo cáo, thảo luận):</label>
                                        <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][bao_cao]" rows="2"></textarea>
                                    </div>
                                    
                                    <div>
                                        <label class="form-label"><i class="bi bi-arrow-right-circle me-2"></i>Kết luận, nhận định:</label>
                                        <textarea class="form-control" name="hoat_dong[<?php echo $i-1; ?>][ket_luan]" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Step 4: Finish -->
                    <div class="step-content d-none" data-step="4">
                        <h5 class="mb-4 text-primary">Hoàn Tất</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hướng Dẫn Về Nhà</label>
                            <textarea class="form-control" name="huong_dan_ve_nha" rows="4" 
                                      placeholder="Bài tập về nhà, chuẩn bị cho bài sau..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Chia Sẻ với Giáo Viên Khác</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="shareWithOthers" name="share_with_others" value="1">
                                <label class="form-check-label" for="shareWithOthers">
                                    Cho phép giáo viên cùng môn học xem và sử dụng KHBD này
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Vui lòng kiểm tra lại thông tin trước khi lưu. Bạn có thể chỉnh sửa sau.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-outline-primary" id="btnPrevious" onclick="previousStep()">
                    <i class="bi bi-arrow-left me-2"></i>Quay Lại
                </button>
                <button type="button" class="btn btn-primary" id="btnNext" onclick="nextStep()">
                    Tiếp Theo<i class="bi bi-arrow-right ms-2"></i>
                </button>
                <button type="button" class="btn btn-success d-none" id="btnSave" onclick="saveLessonPlan()">
                    <i class="bi bi-check-circle me-2"></i>Lưu Kế Hoạch
                </button>
            </div>
        </div>
    </div>
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

<script>
let currentStep = 1;
let currentViewId = null;
let lessonPlansData = [];
let dataTable;

const currentUsername = <?php echo json_encode($username, JSON_UNESCAPED_UNICODE); ?>;
const subjectLookup = <?php echo json_encode($subjects, JSON_UNESCAPED_UNICODE); ?>;

$(document).ready(function() {
    dataTable = $('#lessonPlansTable').DataTable({
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
            ? '<button class="khbd-action-btn" type="button" onclick="editLessonPlan(\'' + safeId + '\')" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>'
              + '<button class="khbd-action-btn danger" type="button" onclick="deleteLessonPlan(\'' + safeId + '\')" title="Xóa"><i class="bi bi-trash"></i></button>'
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

function openCreateModal() {
    $('#lessonPlanId').val('');
    $('#lessonPlanForm')[0].reset();
    $('#modalTitle').html('<i class="bi bi-journal-plus me-2"></i>Tạo Kế Hoạch Bài Dạy Mới');
    currentStep = 1;
    showStep(1);
    $('#lessonPlanModal').modal('show');
}

function showStep(step) {
    $('.step-content').addClass('d-none');
    $(`.step-content[data-step="${step}"]`).removeClass('d-none');
    
    $('.step-item').removeClass('active completed');
    $(`.step-item[data-step="${step}"]`).addClass('active');
    
    for (let i = 1; i < step; i++) {
        $(`.step-item[data-step="${i}"]`).addClass('completed');
    }
    
    $('#btnPrevious').toggleClass('d-none', step === 1);
    $('#btnNext').toggleClass('d-none', step === 4);
    $('#btnSave').toggleClass('d-none', step !== 4);
}

function nextStep() {
    if (validateStep(currentStep)) {
        currentStep++;
        showStep(currentStep);
    }
}

function previousStep() {
    currentStep--;
    showStep(currentStep);
}

function validateStep(step) {
    const stepDiv = $(`.step-content[data-step="${step}"]`);
    const inputs = stepDiv.find('input[required], select[required], textarea[required]');
    let valid = true;
    
    inputs.each(function() {
        if (!this.checkValidity()) {
            $(this).addClass('is-invalid');
            valid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    if (!valid) {
        alert('Vui lòng điền đầy đủ các thông tin bắt buộc!');
    }
    
    return valid;
}

function saveLessonPlan() {
    if (!validateStep(4)) return;
    
    const formData = new FormData($('#lessonPlanForm')[0]);
    const data = {
        action: $('#lessonPlanId').val() ? 'update' : 'create',
        id: $('#lessonPlanId').val(),
        basic_info: {
            ten_bai_day: formData.get('ten_bai_day'),
            so_tiet: formData.get('so_tiet'),
            tiet_ppct: formData.get('tiet_ppct'),
            ngay_day: formData.get('ngay_day')
        },
        subject_id: formData.get('subject_id'),
        class_ids: formData.getAll('class_ids[]'),
        muc_tieu: {
            kien_thuc: formData.get('kien_thuc'),
            nang_luc: formData.get('nang_luc'),
            nang_luc_so: formData.get('nang_luc_so'),
            pham_chat: formData.get('pham_chat')
        },
        thiet_bi: formData.get('thiet_bi'),
        hoat_dong: [],
        huong_dan_ve_nha: formData.get('huong_dan_ve_nha'),
        share_with_others: formData.get('share_with_others') ? true : false
    };
    
    // Collect activities
    for (let i = 0; i < 4; i++) {
        data.hoat_dong.push({
            ten: formData.get(`hoat_dong[${i}][ten]`),
            muc_tieu: formData.get(`hoat_dong[${i}][muc_tieu]`),
            noi_dung: formData.get(`hoat_dong[${i}][noi_dung]`),
            san_pham: formData.get(`hoat_dong[${i}][san_pham]`),
            to_chuc: {
                giao_nhiem_vu: formData.get(`hoat_dong[${i}][giao_nhiem_vu]`),
                thuc_hien: formData.get(`hoat_dong[${i}][thuc_hien]`),
                bao_cao: formData.get(`hoat_dong[${i}][bao_cao]`),
                ket_luan: formData.get(`hoat_dong[${i}][ket_luan]`)
            }
        });
    }
    
    $.ajax({
        url: 'api/lesson_plans_api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#lessonPlanModal').modal('hide');
                loadLessonPlans();
            } else {
                alert('Lỗi: ' + response.message);
            }
        },
        error: function() {
            alert('Có lỗi xảy ra!');
        }
    });
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

function editLessonPlan(id) {
    const plan = lessonPlansData.find(p => p.id === id);
    if (!plan) return;
    
    $('#lessonPlanId').val(plan.id);
    $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Chỉnh Sửa Kế Hoạch Bài Dạy');
    
    // Fill basic info
    $('input[name="ten_bai_day"]').val(plan.basic_info.ten_bai_day);
    $('input[name="so_tiet"]').val(plan.basic_info.so_tiet);
    $('input[name="tiet_ppct"]').val(plan.basic_info.tiet_ppct);
    $('input[name="ngay_day"]').val(plan.basic_info.ngay_day);
    $('select[name="subject_id"]').val(plan.subject_id);
    $('select[name="class_ids[]"]').val(plan.class_ids);
    
    // Fill objectives
    $('textarea[name="kien_thuc"]').val(plan.muc_tieu.kien_thuc);
    $('textarea[name="nang_luc"]').val(plan.muc_tieu.nang_luc);
    $('textarea[name="nang_luc_so"]').val(plan.muc_tieu.nang_luc_so);
    $('textarea[name="pham_chat"]').val(plan.muc_tieu.pham_chat);
    $('textarea[name="thiet_bi"]').val(plan.thiet_bi);
    
    // Fill activities
    plan.hoat_dong.forEach((hd, idx) => {
        $(`input[name="hoat_dong[${idx}][ten]"]`).val(hd.ten);
        $(`textarea[name="hoat_dong[${idx}][muc_tieu]"]`).val(hd.muc_tieu);
        $(`textarea[name="hoat_dong[${idx}][noi_dung]"]`).val(hd.noi_dung);
        $(`textarea[name="hoat_dong[${idx}][san_pham]"]`).val(hd.san_pham);
        $(`textarea[name="hoat_dong[${idx}][giao_nhiem_vu]"]`).val(hd.to_chuc.giao_nhiem_vu);
        $(`textarea[name="hoat_dong[${idx}][thuc_hien]"]`).val(hd.to_chuc.thuc_hien);
        $(`textarea[name="hoat_dong[${idx}][bao_cao]"]`).val(hd.to_chuc.bao_cao);
        $(`textarea[name="hoat_dong[${idx}][ket_luan]"]`).val(hd.to_chuc.ket_luan);
    });
    
    $('textarea[name="huong_dan_ve_nha"]').val(plan.huong_dan_ve_nha);
    $('#shareWithOthers').prop('checked', plan.share_with_others);
    
    currentStep = 1;
    showStep(1);
    $('#lessonPlanModal').modal('show');
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

function toggleActivity(index) {
    $(`#activity-${index} .activity-body`).collapse('toggle');
    $(`#activity-${index} .activity-header`).toggleClass('collapsed');
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

// Update activity name preview
$('.activity-name').on('input', function() {
    const panel = $(this).closest('.activity-panel');
    const preview = panel.find('.activity-title-preview');
    preview.text($(this).val() || 'Chưa đặt tên');
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
