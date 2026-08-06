<?php
include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$username = $_SESSION['username'];

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$username = $_SESSION['username'];
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

// Filter subjects that are assigned to this teacher
$assignedSubjects = array_filter($allSubjects, function($subj) use ($assignedSubjectIds) {
    return in_array($subj['id'], $assignedSubjectIds);
});

// Filter classes that are assigned to this teacher and create lookup by code
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

// Create subjects lookup for display
$subjects = [];
foreach ($allSubjects as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

// Compute assignment stats for this teacher
$teacherAssignmentsFile = __DIR__ . '/../data/assignments.json';
$teacherAssignmentsData = file_exists($teacherAssignmentsFile) ? json_decode(file_get_contents($teacherAssignmentsFile), true) : [];
if (!is_array($teacherAssignmentsData)) $teacherAssignmentsData = [];
$myAssignments = array_values(array_filter($teacherAssignmentsData, function($a) use ($username) {
    return ($a['teacher_username'] ?? '') === $username;
}));

$totalAssignments = count($myAssignments);
$activeAssignments = 0;
$expiredAssignments = 0;
$nowTs = time();
foreach ($myAssignments as $a) {
    $due = strtotime(str_replace('T', ' ', $a['due_date'] ?? ''));
    if ($due !== false && $due < $nowTs) {
        $expiredAssignments++;
    } else {
        $activeAssignments++;
    }
}

$submissionsData = __DIR__ . '/../data/student_submissions.json';
$allSubmissions = file_exists($submissionsData) ? json_decode(file_get_contents($submissionsData), true) : [];
if (!is_array($allSubmissions)) $allSubmissions = [];
$myAssignmentIds = array_flip(array_column($myAssignments, 'id'));
$totalSubmissions = 0;
foreach ($allSubmissions as $s) {
    if (isset($myAssignmentIds[$s['assignment_id'] ?? ''])) {
        $totalSubmissions++;
    }
}

$title = 'Quản Lý Bài Tập - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
<div class="container my-5">
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon primary"><i class="bi bi-journal-text"></i></span>
                <div>
                    <div class="stat-value"><?php echo $totalAssignments; ?></div>
                    <div class="stat-label">Tổng bài tập</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon success"><i class="bi bi-check-circle"></i></span>
                <div>
                    <div class="stat-value"><?php echo $activeAssignments; ?></div>
                    <div class="stat-label">Đang mở</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon danger"><i class="bi bi-clock-history"></i></span>
                <div>
                    <div class="stat-value"><?php echo $expiredAssignments; ?></div>
                    <div class="stat-label">Đã hết hạn</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <span class="stat-icon violet"><i class="bi bi-people"></i></span>
                <div>
                    <div class="stat-value"><?php echo $totalSubmissions; ?></div>
                    <div class="stat-label">Bài đã nộp</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-header justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center gap-3">
            <div class="sh-icon"><i class="bi bi-journal-check"></i></div>
            <div>
                <h3 class="mb-0">Quản Lý Bài Tập</h3>
                <p class="mb-0">Tạo và quản lý bài tập cho học sinh</p>
            </div>
        </div>
        <div class="ms-auto">
            <button class="btn btn-primary btn-action-custom" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                <i class="bi bi-plus-circle me-2"></i>Tạo Bài Tập Mới
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card eduvn-card">
                <div class="card-header d-flex align-items-center justify-content-between text-dark">
                    <span class="fw-bold fs-6"><i class="bi bi-list-task me-2 text-primary"></i>Danh Sách Bài Tập</span>
                    <span class="badge badge-soft-slate rounded-pill px-3 py-2 fw-semibold" id="assignmentCountBadge"><?php echo $totalAssignments; ?> bài tập</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-hover align-middle eduvn-table w-100">
                            <thead>
                                <tr>
                                    <th>Tiêu Đề</th>
                                    <th>Môn Học</th>
                                    <th>Lớp</th>
                                    <th>Hạn Nộp</th>
                                    <th class="text-center">Trạng Thái</th>
                                    <th class="text-center">Bài Nộp</th>
                                    <th class="text-center">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody id="assignmentsBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content assignment-modal">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge">
                        <i class="bi bi-journal-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Tạo Bài Tập Mới</h5>
                        <div class="modal-subtitle">Soạn nội dung, đính kèm tài liệu và phân công bài tập cho học sinh</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createAssignmentForm">
                    <!-- Section 1: Thông tin cơ bản & Phân công -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="bi bi-info-circle-fill"></i> Thông tin bài tập &amp; Phân công
                        </div>
                        
                        <!-- Top Row: Tiêu đề & Môn học -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">
                                    <i class="bi bi-type text-primary me-1"></i>Tiêu Đề Bài Tập <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="assignmentTitle" placeholder="Ví dụ: Bài tập ôn tập chương 1 - Hàm số bậc nhất" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bi bi-book text-primary me-1"></i>Môn Học <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="assignmentSubject" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php 
                                    $first = true;
                                    foreach ($assignedSubjects as $subject): 
                                    ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo $first ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php 
                                    $first = false;
                                    endforeach; 
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Bottom Block: Phân công Lớp học -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0">
                                    <i class="bi bi-people text-primary me-1"></i>Lớp học <span class="text-danger">*</span>
                                    <span class="badge bg-primary class-counter ms-2" id="createClassCounter">0 lớp</span>
                                </label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-soft-primary px-3 py-1 fw-semibold" onclick="selectAllClasses('assignmentClass')">
                                        <i class="bi bi-check-all me-1"></i>Chọn tất cả
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-slate px-3 py-1 fw-semibold" onclick="clearAllClasses('assignmentClass')">
                                        <i class="bi bi-x-circle me-1"></i>Bỏ chọn
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Selected Classes Display -->
                            <div class="selected-classes-display mb-2" id="createSelectedDisplay">
                                <div class="text-muted small text-center py-1" id="createEmptyMessage">
                                    <i class="bi bi-info-circle me-1"></i>Chọn lớp từ danh sách bên dưới
                                </div>
                            </div>
                            
                            <!-- Class Tags Grid -->
                            <div class="class-tags-grid" id="assignmentClassContainer">
                                <?php foreach ($assignedClasses as $class): ?>
                                <div class="class-tag" data-class-code="<?php echo htmlspecialchars($class['code']); ?>" 
                                     onclick="toggleClassTag(this, 'assignmentClass')">
                                    <input type="checkbox" name="assignmentClass[]" 
                                           value="<?php echo htmlspecialchars($class['code']); ?>" 
                                           id="create_class_<?php echo htmlspecialchars($class['id']); ?>" 
                                           style="display: none;">
                                    <i class="bi bi-check-circle-fill tag-check"></i>
                                    <span class="tag-code"><?php echo htmlspecialchars($class['code']); ?></span>
                                    <?php if (trim($class['name']) !== trim($class['code'])): ?>
                                        <span class="tag-name"><?php echo htmlspecialchars($class['name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Yêu cầu & File đính kèm -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="bi bi-file-earmark-text-fill"></i> Nội dung yêu cầu &amp; Đính kèm
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-pencil-square text-primary me-1"></i>Mô Tả / Yêu Cầu Bài Tập <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="assignmentDescription" rows="4" required placeholder="Nhập hướng dẫn, yêu cầu chi tiết hoặc câu hỏi cho bài tập..."></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                <i class="bi bi-paperclip text-primary me-1"></i>File đính kèm
                            </label>
                            <input type="file" class="form-control" id="assignmentAttachments" multiple
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                            <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>Hỗ trợ hình ảnh, PDF, Word, Excel. Tối đa 20MB / file.</div>
                        </div>
                    </div>
                    
                    <!-- Section 3: Quy định nộp bài -->
                    <div class="modal-section-card mb-0">
                        <div class="modal-section-title">
                            <i class="bi bi-sliders"></i> Quy định nộp bài &amp; Thang điểm
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">
                                    <i class="bi bi-calendar-event text-primary me-1"></i>Hạn Nộp <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" class="form-control" id="assignmentDueDate" required>
                            </div>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">
                                    <i class="bi bi-star text-primary me-1"></i>Điểm Tối Đa <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="assignmentMaxScore" min="1" max="100" value="10" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bi bi-people-fill text-primary me-1"></i>Số TV Nhóm <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="assignmentMaxGroupMembers" min="1" max="20" value="1" required>
                            </div>
                        </div>
                        <div class="form-text mt-2"><i class="bi bi-shield-check me-1"></i>Đặt "1" nếu là bài tập cá nhân, hoặc chọn số lượng nếu làm bài theo nhóm.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-slate btn-action-custom px-4 d-inline-flex align-items-center gap-2" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i><span>Hủy</span>
                </button>
                <button type="button" class="btn btn-primary btn-action-custom px-4 shadow-sm d-inline-flex align-items-center gap-2" onclick="createAssignment()">
                    <i class="bi bi-check-circle"></i><span>Tạo Bài Tập</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content assignment-modal">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-badge">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Chỉnh Sửa Bài Tập</h5>
                        <div class="modal-subtitle">Cập nhật thông tin, nội dung hoặc thời hạn bài tập</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAssignmentForm">
                    <input type="hidden" id="editAssignmentId">
                    
                    <!-- Section 1: Thông tin cơ bản & Phân công -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="bi bi-info-circle-fill"></i> Thông tin bài tập &amp; Phân công
                        </div>
                        
                        <!-- Top Row: Tiêu đề & Môn học -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">
                                    <i class="bi bi-type text-primary me-1"></i>Tiêu Đề Bài Tập <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="editAssignmentTitle" placeholder="Tiêu đề bài tập..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bi bi-book text-primary me-1"></i>Môn Học <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="editAssignmentSubject" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php foreach ($assignedSubjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Bottom Block: Phân công Lớp học -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0">
                                    <i class="bi bi-people text-primary me-1"></i>Lớp học <span class="text-danger">*</span>
                                    <span class="badge bg-primary class-counter ms-2" id="editClassCounter">0 lớp</span>
                                </label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-soft-primary px-3 py-1 fw-semibold" onclick="selectAllClasses('editAssignmentClass')">
                                        <i class="bi bi-check-all me-1"></i>Chọn tất cả
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-slate px-3 py-1 fw-semibold" onclick="clearAllClasses('editAssignmentClass')">
                                        <i class="bi bi-x-circle me-1"></i>Bỏ chọn
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Selected Classes Display -->
                            <div class="selected-classes-display mb-2" id="editSelectedDisplay">
                                <div class="text-muted small text-center py-1" id="editEmptyMessage">
                                    <i class="bi bi-info-circle me-1"></i>Chọn lớp từ danh sách bên dưới
                                </div>
                            </div>
                            
                            <!-- Class Tags Grid -->
                            <div class="class-tags-grid" id="editAssignmentClassContainer">
                                <?php foreach ($assignedClasses as $class): ?>
                                <div class="class-tag" data-class-code="<?php echo htmlspecialchars($class['code']); ?>" 
                                     onclick="toggleClassTag(this, 'editAssignmentClass')">
                                    <input type="checkbox" name="editAssignmentClass[]" 
                                           value="<?php echo htmlspecialchars($class['code']); ?>" 
                                           id="edit_class_<?php echo htmlspecialchars($class['id']); ?>" 
                                           style="display: none;">
                                    <i class="bi bi-check-circle-fill tag-check"></i>
                                    <span class="tag-code"><?php echo htmlspecialchars($class['code']); ?></span>
                                    <?php if (trim($class['name']) !== trim($class['code'])): ?>
                                        <span class="tag-name"><?php echo htmlspecialchars($class['name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 2: Yêu cầu & File đính kèm -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="bi bi-file-earmark-text-fill"></i> Nội dung yêu cầu &amp; Đính kèm
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-pencil-square text-primary me-1"></i>Mô Tả / Yêu Cầu <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="editAssignmentDescription" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-paperclip text-primary me-1"></i>File đính kèm hiện có</label>
                            <div class="list-group shadow-xs rounded-3 overflow-hidden" id="editAssignmentAttachmentsList">
                                <div class="list-group-item text-muted">Chưa có file đính kèm</div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label"><i class="bi bi-file-earmark-plus text-primary me-1"></i>Thêm file đính kèm mới</label>
                            <input type="file" class="form-control" id="editAssignmentAttachments" multiple
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                            <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>File mới sẽ được thêm vào danh sách hiện có. Mỗi file tối đa 20MB.</div>
                        </div>
                    </div>
                    
                    <!-- Section 3: Quy định nộp bài -->
                    <div class="modal-section-card mb-0">
                        <div class="modal-section-title">
                            <i class="bi bi-sliders"></i> Quy định nộp bài &amp; Thang điểm
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label"><i class="bi bi-calendar-event text-primary me-1"></i>Hạn Nộp <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="editAssignmentDueDate" required>
                            </div>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label"><i class="bi bi-star text-primary me-1"></i>Điểm Tối Đa <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editAssignmentMaxScore" min="1" max="100" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="bi bi-people-fill text-primary me-1"></i>Số TV Nhóm <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editAssignmentMaxGroupMembers" min="1" max="20" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft-slate btn-action-custom px-4 d-inline-flex align-items-center gap-2" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i><span>Hủy</span>
                </button>
                <button type="button" class="btn btn-primary btn-action-custom px-4 shadow-sm d-inline-flex align-items-center gap-2" onclick="updateAssignment()">
                    <i class="bi bi-check-circle"></i><span>Cập Nhật</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ---------- Quản Lý Bài Tập · EduVN Styling ---------- */

/* Modal custom styling */
.assignment-modal {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
}

.assignment-modal .modal-header {
    background: var(--grad-accent);
    color: #fff;
    padding: 22px 28px;
    border: none;
    position: relative;
}

.assignment-modal .modal-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(120% 160% at 100% 0, rgba(255, 255, 255, 0.18) 0%, transparent 46%);
    pointer-events: none;
}

.assignment-modal .modal-header .modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
}

.assignment-modal .modal-header .modal-subtitle {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.85);
    margin-top: 2px;
    font-weight: 400;
}

.assignment-modal .modal-header .modal-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
    flex-shrink: 0;
}

.assignment-modal .modal-header .btn-close {
    z-index: 2;
    filter: invert(1) grayscale(100%) brightness(200%);
    opacity: 0.85;
}

.assignment-modal .modal-header .btn-close:hover {
    opacity: 1;
}

.assignment-modal .modal-body {
    padding: 26px;
    background: #FAFBFF;
}

.modal-section-card {
    background: #ffffff;
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-xs);
    transition: all 0.2s ease;
}

.modal-section-card:hover {
    border-color: var(--accent-mist);
    box-shadow: var(--shadow-sm);
}

.modal-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-soft);
}

.modal-section-title i {
    color: var(--accent);
    font-size: 1rem;
}

.assignment-modal .form-label {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--muted-strong);
    margin-bottom: 6px;
}

.assignment-modal .form-control,
.assignment-modal .form-select {
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.92rem;
    transition: all 0.2s ease;
    background-color: #ffffff;
}

.assignment-modal .form-control:focus,
.assignment-modal .form-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.assignment-modal textarea.form-control {
    line-height: 1.6;
}

.assignment-modal .modal-footer {
    padding: 16px 26px;
    background: #ffffff;
    border-top: 1px solid var(--border-soft);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

/* Selected classes display (modal) */
.selected-classes-display {
    min-height: 44px;
    max-height: 90px;
    overflow-y: auto;
    padding: 8px 12px;
    background: var(--page-bg);
    border: 1.5px dashed var(--border);
    border-radius: var(--radius-sm);
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-content: flex-start;
    transition: border-color .2s ease, background .2s ease;
}

.selected-classes-display:hover {
    border-color: var(--accent);
    background: var(--surface);
}

.selected-class-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: var(--grad-accent);
    color: #fff;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 500;
    box-shadow: var(--shadow-accent);
    animation: slideIn .25s ease;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
}

.selected-class-badge:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.selected-class-badge .remove-icon {
    font-size: .95rem;
    opacity: .75;
    transition: opacity .2s;
}

.selected-class-badge:hover .remove-icon {
    opacity: 1;
}

/* Class tags grid */
.class-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 8px;
    max-height: 160px;
    overflow-y: auto;
    padding: 10px;
    background: var(--surface);
    border: 1.5px solid var(--border-soft);
    border-radius: var(--radius-sm);
}

.class-tag {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 10px 12px;
    background: var(--surface);
    border: 1.5px solid var(--border-soft);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all .25s cubic-bezier(.4, 0, .2, 1);
    overflow: hidden;
}

.class-tag::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--accent-light);
    opacity: 0;
    transition: opacity .25s ease;
}

.class-tag:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
    box-shadow: var(--shadow-sm);
}

.class-tag:hover::before {
    opacity: 1;
}

.class-tag.selected {
    background: var(--grad-accent);
    border-color: transparent;
    color: #fff;
    box-shadow: var(--shadow-accent);
}

.class-tag.selected .tag-check {
    opacity: 1;
    transform: scale(1);
}

.class-tag .tag-check {
    position: absolute;
    top: 6px;
    right: 6px;
    font-size: 1.1rem;
    color: #fff;
    opacity: 0;
    transform: scale(0);
    transition: all .25s cubic-bezier(.68, -0.55, .265, 1.55);
}

.class-tag .tag-code {
    position: relative;
    font-size: .92rem;
    font-weight: 700;
    margin-bottom: 2px;
    letter-spacing: .3px;
}

.class-tag .tag-name {
    position: relative;
    font-size: .75rem;
    opacity: .75;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.class-tag.selected .tag-name {
    opacity: .85;
}

.class-counter {
    font-size: .76rem;
    padding: 4px 10px;
    border-radius: 999px;
    animation: pulse 2s infinite;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(79, 70, 229, .35);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0);
    }
}

/* Scrollbar */
.class-tags-grid::-webkit-scrollbar,
.selected-classes-display::-webkit-scrollbar {
    width: 6px;
}

.class-tags-grid::-webkit-scrollbar-track,
.selected-classes-display::-webkit-scrollbar-track {
    background: var(--border-soft);
    border-radius: 10px;
}

.class-tags-grid::-webkit-scrollbar-thumb,
.selected-classes-display::-webkit-scrollbar-thumb {
    background: var(--accent-mist);
    border-radius: 10px;
}

.class-tags-grid::-webkit-scrollbar-thumb:hover,
.selected-classes-display::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
}

#createAssignmentForm .form-text,
#editAssignmentForm .form-text {
    font-size: .78rem;
    color: var(--muted);
}
</style>

<script src="../includes/toast-notifications.js"></script>

<script>
let assignmentsTable;
const subjects = <?php echo json_encode($subjects); ?>;

$(document).ready(function() {
    loadAssignments();
    
    // Reset class selection display when modals open
    $('#createAssignmentModal').on('shown.bs.modal', function() {
        clearAllClasses('assignmentClass');
    });
    
    $('#editAssignmentModal').on('shown.bs.modal', function() {
        // Display will be updated by editAssignment function
    });
});

function loadAssignments() {
    fetch('api/get_assignments.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('assignmentsBody');
                tbody.innerHTML = '';
                
                result.assignments.forEach(assignment => {
                    const dueDate = new Date(assignment.due_date);
                    const now = new Date();
                    const isExpired = dueDate < now;
                    const statusBadge = isExpired ? 
                        '<span class="badge badge-soft-danger"><i class="bi bi-clock-history me-1"></i>Đã hết hạn</span>' : 
                        '<span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>Đang mở</span>';
                    
                    const subjectName = subjects[assignment.subject_id] || assignment.subject_id;
                    
                    const classDisplay = assignment.class_display || (Array.isArray(assignment.class_names) ? assignment.class_names.join(', ') : assignment.class_name);
                    const attachmentCount = Array.isArray(assignment.attachments) ? assignment.attachments.length : 0;
                    const attachmentBadge = attachmentCount > 0 ? `<div class="small text-muted mt-1"><i class="bi bi-paperclip me-1"></i>${attachmentCount} file đính kèm</div>` : '';
                    
                    const submissionCount = assignment.submission_count || 0;
                    const submissionBtnClass = submissionCount > 0 ? 'btn-soft-primary' : 'btn-soft-slate';

                    const row = `
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(assignment.title)}</div>
                                ${attachmentBadge}
                            </td>
                            <td><span class="badge badge-soft-info">${escapeHtml(subjectName)}</span></td>
                            <td><span class="fw-medium">${escapeHtml(classDisplay || '')}</span></td>
                            <td><span class="text-muted"><i class="bi bi-calendar3 me-1"></i>${formatDateTime(assignment.due_date)}</span></td>
                            <td class="text-center">${statusBadge}</td>
                            <td class="text-center">
                                <a href="view_submissions.php?id=${assignment.id}" class="btn btn-sm ${submissionBtnClass} rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1" title="Xem danh sách bài nộp">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>${submissionCount} bài</span>
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-1.5 justify-content-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-soft-primary d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-semibold" onclick="editAssignment('${assignment.id}')" title="Chỉnh sửa bài tập">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Sửa</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-semibold" onclick="deleteAssignment('${assignment.id}')" title="Xóa bài tập">
                                        <i class="bi bi-trash"></i>
                                        <span>Xóa</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                const countBadge = document.getElementById('assignmentCountBadge');
                if (countBadge) {
                    countBadge.textContent = result.assignments.length + ' bài tập';
                }
                
                if (assignmentsTable) {
                    assignmentsTable.destroy();
                }
                
                assignmentsTable = $('#assignmentsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 50,
                    order: [[3, 'desc']],
                    columnDefs: [
                        { className: "text-center", targets: [4, 5, 6] }
                    ]
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function createAssignment() {
    const title = document.getElementById('assignmentTitle').value;
    const subject = document.getElementById('assignmentSubject').value;
    const classNames = getSelectedValues('assignmentClass');
    const description = document.getElementById('assignmentDescription').value;
    const dueDate = document.getElementById('assignmentDueDate').value;
    const maxScore = document.getElementById('assignmentMaxScore').value;
    const maxGroupMembers = document.getElementById('assignmentMaxGroupMembers').value;
    const attachmentsInput = document.getElementById('assignmentAttachments');
    
    if (!title || !subject || classNames.length === 0 || !description || !dueDate || !maxScore || !maxGroupMembers) {
        showToast('Vui lòng điền đầy đủ thông tin!', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('title', title);
    formData.append('subject_id', subject);
    classNames.forEach(className => formData.append('class_names[]', className));
    formData.append('description', description);
    formData.append('due_date', dueDate);
    formData.append('max_score', maxScore);
    formData.append('max_group_members', maxGroupMembers);
    Array.from(attachmentsInput.files).forEach(file => {
        formData.append('attachments[]', file);
    });

    fetch('api/manage_assignment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Tạo bài tập thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createAssignmentModal')).hide();
            document.getElementById('createAssignmentForm').reset();
            attachmentsInput.value = '';
            loadAssignments();
        } else {
            showToast('Lỗi: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi tạo bài tập', 'error');
    });
}

function editAssignment(assignmentId) {
    fetch(`api/get_assignments.php?id=${assignmentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.assignment) {
                const assignment = result.assignment;
                document.getElementById('editAssignmentId').value = assignment.id;
                document.getElementById('editAssignmentTitle').value = assignment.title;
                document.getElementById('editAssignmentSubject').value = assignment.subject_id;
                const classNames = Array.isArray(assignment.class_names) ? assignment.class_names : (assignment.class_name ? [assignment.class_name] : []);
                setSelectedValues('editAssignmentClass', classNames);
                document.getElementById('editAssignmentDescription').value = assignment.description;
                document.getElementById('editAssignmentDueDate').value = assignment.due_date.replace(' ', 'T');
                document.getElementById('editAssignmentMaxScore').value = assignment.max_score;
                document.getElementById('editAssignmentMaxGroupMembers').value = assignment.max_group_members || 1;
                document.getElementById('editAssignmentAttachments').value = '';
                renderEditAttachments(assignment.attachments || []);
                
                new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
            }
        });
}

function renderEditAttachments(attachments) {
    const container = document.getElementById('editAssignmentAttachmentsList');
    container.innerHTML = '';

    if (!attachments.length) {
        container.innerHTML = '<div class="list-group-item text-muted">Chưa có file đính kèm</div>';
        return;
    }

    attachments.forEach(file => {
        const link = document.createElement('a');
        link.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        link.href = `api/download_file.php?file=${encodeURIComponent(file.path)}`;
        link.innerHTML = `
            <span><i class="bi bi-file-earmark-arrow-down me-2"></i>${escapeHtml(file.original_name || file.stored_name || 'file')}</span>
            <small class="text-muted">${formatFileSize(file.size || 0)}</small>
        `;
        container.appendChild(link);
    });
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

function formatFileSize(bytes) {
    const size = Number(bytes) || 0;
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}

function updateAssignment() {
    const id = document.getElementById('editAssignmentId').value;
    const title = document.getElementById('editAssignmentTitle').value;
    const subject = document.getElementById('editAssignmentSubject').value;
    const classNames = getSelectedValues('editAssignmentClass');
    const description = document.getElementById('editAssignmentDescription').value;
    const dueDate = document.getElementById('editAssignmentDueDate').value;
    const maxScore = document.getElementById('editAssignmentMaxScore').value;
    const maxGroupMembers = document.getElementById('editAssignmentMaxGroupMembers').value;
    const attachmentsInput = document.getElementById('editAssignmentAttachments');
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('title', title);
    formData.append('subject_id', subject);
    classNames.forEach(className => formData.append('class_names[]', className));
    formData.append('description', description);
    formData.append('due_date', dueDate);
    formData.append('max_score', maxScore);
    formData.append('max_group_members', maxGroupMembers);
    Array.from(attachmentsInput.files).forEach(file => {
        formData.append('attachments[]', file);
    });

    fetch('api/manage_assignment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Cập nhật bài tập thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editAssignmentModal')).hide();
            attachmentsInput.value = '';
            loadAssignments();
        } else {
            showToast('Lỗi: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi cập nhật', 'error');
    });
}

function deleteAssignment(assignmentId) {
    if (!confirm('Bạn có chắc muốn xóa bài tập này?')) return;
    
    fetch('api/manage_assignment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'delete',
            id: assignmentId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Đã xóa bài tập thành công!', 'success');
            loadAssignments();
        } else {
            showToast('Lỗi: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi xóa bài tập', 'error');
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Toggle class tag when clicked
function toggleClassTag(tagElement, fieldId) {
    const checkbox = tagElement.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        tagElement.classList.add('selected');
    } else {
        tagElement.classList.remove('selected');
    }
    
    updateClassDisplay(fieldId);
    updateCounter(fieldId);
}

// Update selected classes display with badges
function updateClassDisplay(fieldId) {
    const prefix = fieldId.includes('edit') ? 'edit' : 'create';
    const displayContainer = document.getElementById(`${prefix}SelectedDisplay`);
    const emptyMessage = document.getElementById(`${prefix}EmptyMessage`);
    const checkboxes = document.querySelectorAll(`input[name="${fieldId}[]"]:checked`);
    
    // Clear current display
    displayContainer.innerHTML = '';
    
    if (checkboxes.length === 0) {
        displayContainer.innerHTML = `<div class="text-muted small text-center py-2" id="${prefix}EmptyMessage">
            <i class="bi bi-info-circle me-1"></i>Chọn lớp từ danh sách bên dưới
        </div>`;
        return;
    }
    
    // Add selected badges
    checkboxes.forEach(checkbox => {
        const classCode = checkbox.value;
        const tagElement = checkbox.closest('.class-tag');
        const nameElem = tagElement.querySelector('.tag-name');
        const className = nameElem ? nameElem.textContent.trim() : '';
        
        const badgeLabel = (className && className !== classCode) ? `<strong>${classCode}</strong> ${className}` : `<strong>${classCode}</strong>`;
        
        const badge = document.createElement('div');
        badge.className = 'selected-class-badge';
        badge.innerHTML = `
            <span><strong>${classCode}</strong> ${className}</span>
            <i class="bi bi-x-circle remove-icon"></i>
        `;
        badge.onclick = (e) => {
            e.stopPropagation();
            toggleClassTag(tagElement, fieldId);
        };
        
        displayContainer.appendChild(badge);
    });
}

// Update counter
function updateCounter(fieldId) {
    const prefix = fieldId.includes('edit') ? 'edit' : 'create';
    const counter = document.getElementById(`${prefix}ClassCounter`);
    const checkboxes = document.querySelectorAll(`input[name="${fieldId}[]"]:checked`);
    const count = checkboxes.length;
    
    counter.textContent = count === 0 ? '0 lớp' : (count === 1 ? '1 lớp' : `${count} lớp`);
    counter.style.animation = 'none';
    setTimeout(() => counter.style.animation = '', 10);
}

function getSelectedValues(selectId) {
    const checkboxes = document.querySelectorAll(`input[name="${selectId}[]"]:checked`);
    return Array.from(checkboxes).map(cb => cb.value).filter(value => value);
}

function setSelectedValues(selectId, values) {
    const valueSet = new Set(values);
    const tags = document.querySelectorAll(`input[name="${selectId}[]"]`);
    
    tags.forEach(checkbox => {
        const tagElement = checkbox.closest('.class-tag');
        const isSelected = valueSet.has(checkbox.value);
        
        checkbox.checked = isSelected;
        if (isSelected) {
            tagElement.classList.add('selected');
        } else {
            tagElement.classList.remove('selected');
        }
    });
    
    updateClassDisplay(selectId);
    updateCounter(selectId);
}

function selectAllClasses(fieldId) {
    const tags = document.querySelectorAll(`input[name="${fieldId}[]"]`);
    
    tags.forEach(checkbox => {
        checkbox.checked = true;
        checkbox.closest('.class-tag').classList.add('selected');
    });
    
    updateClassDisplay(fieldId);
    updateCounter(fieldId);
}

function clearAllClasses(fieldId) {
    const tags = document.querySelectorAll(`input[name="${fieldId}[]"]`);
    
    tags.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.class-tag').classList.remove('selected');
    });
    
    updateClassDisplay(fieldId);
    updateCounter(fieldId);
}
</script>

<?php include '../includes/teacher_footer.php'; ?>
