<?php
include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
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

$title = 'Quản Lý Bài Tập - CVD';
include '../includes/teacher_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-0"><i class="bi bi-journal-check me-2"></i>Quản Lý Bài Tập</h2>
                <p class="text-white-50 mb-0 mt-2">Tạo và quản lý bài tập cho học sinh</p>
            </div>
            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                <i class="bi bi-plus-circle me-2"></i>Tạo Bài Tập Mới
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-list-task me-2"></i>Danh Sách Bài Tập
                </div>
                <div class="card-body">
                    <table id="assignmentsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tiêu Đề</th>
                                <th>Môn Học</th>
                                <th>Lớp</th>
                                <th>Hạn Nộp</th>
                                <th>Trạng Thái</th>
                                <th>Bài Nộp</th>
                                <th>Hành Động</th>
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

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tạo Bài Tập Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createAssignmentForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu Đề Bài Tập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="assignmentTitle" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Môn Học <span class="text-danger">*</span></label>
                            <select class="form-select" id="assignmentSubject" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php 
                                $first = true;
                                foreach ($assignedSubjects as $subject): 
                                ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $first ? 'selected' : ''; ?>><?php echo $subject['name']; ?></option>
                                <?php 
                                $first = false;
                                endforeach; 
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold d-flex align-items-center justify-content-between">
                                <span>Lớp học <span class="text-danger">*</span></span>
                                <span class="badge bg-primary class-counter" id="createClassCounter">0 lớp</span>
                            </label>
                            
                            <!-- Selected Classes Display -->
                            <div class="selected-classes-display mb-3" id="createSelectedDisplay">
                                <div class="text-muted small text-center py-2" id="createEmptyMessage">
                                    <i class="bi bi-info-circle me-1"></i>Chọn lớp từ danh sách bên dưới
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="selectAllClasses('assignmentClass')">
                                    <i class="bi bi-check-all me-1"></i>Tất cả
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="clearAllClasses('assignmentClass')">
                                    <i class="bi bi-x-circle me-1"></i>Xóa hết
                                </button>
                            </div>
                            
                            <!-- Class Tags Grid -->
                            <div class="class-tags-grid" id="assignmentClassContainer">
                                <?php 
                                foreach ($assignedClasses as $class): 
                                ?>
                                <div class="class-tag" data-class-code="<?php echo htmlspecialchars($class['code']); ?>" 
                                     onclick="toggleClassTag(this, 'assignmentClass')">
                                    <input type="checkbox" name="assignmentClass[]" 
                                           value="<?php echo htmlspecialchars($class['code']); ?>" 
                                           id="create_class_<?php echo htmlspecialchars($class['id']); ?>" 
                                           style="display: none;">
                                    <i class="bi bi-check-circle-fill tag-check"></i>
                                    <span class="tag-code"><?php echo htmlspecialchars($class['code']); ?></span>
                                    <span class="tag-name"><?php echo htmlspecialchars($class['name']); ?></span>
                                </div>
                                <?php 
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô Tả / Yêu Cầu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="assignmentDescription" rows="5" required placeholder="Nhập yêu cầu chi tiết cho bài tập..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">File đính kèm</label>
                        <input type="file" class="form-control" id="assignmentAttachments" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <div class="form-text">Hỗ trợ hình ảnh, PDF, Word, Excel. Mỗi file tối đa 20MB.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Hạn Nộp <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="assignmentDueDate" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Điểm Tối Đa <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="assignmentMaxScore" min="1" max="100" value="10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Số thành viên tối đa <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="assignmentMaxGroupMembers" min="1" max="20" value="1" required>
                        <div class="form-text">Tính cả học sinh nộp bài. Đặt 1 nếu bài làm cá nhân.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-gradient-primary" onclick="createAssignment()">
                    <i class="bi bi-save me-2"></i>Tạo Bài Tập
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Chỉnh Sửa Bài Tập</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAssignmentForm">
                    <input type="hidden" id="editAssignmentId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu Đề Bài Tập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editAssignmentTitle" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Môn Học <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAssignmentSubject" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($assignedSubjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo $subject['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold d-flex align-items-center justify-content-between">
                                <span>Lớp học <span class="text-danger">*</span></span>
                                <span class="badge bg-primary class-counter" id="editClassCounter">0 lớp</span>
                            </label>
                            
                            <!-- Selected Classes Display -->
                            <div class="selected-classes-display mb-3" id="editSelectedDisplay">
                                <div class="text-muted small text-center py-2" id="editEmptyMessage">
                                    <i class="bi bi-info-circle me-1"></i>Chọn lớp từ danh sách bên dưới
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="selectAllClasses('editAssignmentClass')">
                                    <i class="bi bi-check-all me-1"></i>Tất cả
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="clearAllClasses('editAssignmentClass')">
                                    <i class="bi bi-x-circle me-1"></i>Xóa hết
                                </button>
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
                                    <span class="tag-name"><?php echo htmlspecialchars($class['name']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô Tả / Yêu Cầu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editAssignmentDescription" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">File đính kèm hiện có</label>
                        <div class="list-group" id="editAssignmentAttachmentsList">
                            <div class="list-group-item text-muted">Chưa có file đính kèm</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Thêm file đính kèm</label>
                        <input type="file" class="form-control" id="editAssignmentAttachments" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <div class="form-text">File mới sẽ được thêm vào danh sách hiện có. Mỗi file tối đa 20MB.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Hạn Nộp <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="editAssignmentDueDate" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Điểm Tối Đa <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editAssignmentMaxScore" min="1" max="100" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Số thành viên tối đa <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="editAssignmentMaxGroupMembers" min="1" max="20" required>
                        <div class="form-text">Tính cả học sinh nộp bài. Đặt 1 nếu bài làm cá nhân.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-gradient-primary" onclick="updateAssignment()">
                    <i class="bi bi-save me-2"></i>Cập Nhật
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.selection-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.selection-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border-radius: 12px 12px 0 0 !important;
}

.btn-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.btn-gradient-primary:hover {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    color: white;
}

.btn-gradient-danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    border: none;
    color: white;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.badge-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.badge-active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.badge-expired {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
}

/* Premium Class Selector Styles */
.selected-classes-display {
    min-height: 60px;
    max-height: 120px;
    overflow-y: auto;
    padding: 10px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-content: flex-start;
    transition: all 0.3s ease;
}

.selected-classes-display:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.selected-class-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    animation: slideIn 0.3s ease;
    cursor: pointer;
    transition: all 0.2s ease;
}

.selected-class-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.selected-class-badge .remove-icon {
    font-size: 1rem;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.selected-class-badge:hover .remove-icon {
    opacity: 1;
}

.class-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding: 12px;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
}

.class-tag {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.class-tag::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.class-tag:hover {
    transform: translateY(-3px);
    border-color: #667eea;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
}

.class-tag:hover::before {
    opacity: 1;
}

.class-tag.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.class-tag.selected .tag-check {
    opacity: 1;
    transform: scale(1);
}

.class-tag .tag-check {
    position: absolute;
    top: 6px;
    right: 6px;
    font-size: 1.2rem;
    color: white;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.class-tag .tag-code {
    position: relative;
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}

.class-tag .tag-name {
    position: relative;
    font-size: 0.75rem;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.class-counter {
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 12px;
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
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0);
    }
}

/* Scrollbar Styling */
.class-tags-grid::-webkit-scrollbar,
.selected-classes-display::-webkit-scrollbar {
    width: 6px;
}

.class-tags-grid::-webkit-scrollbar-track,
.selected-classes-display::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.class-tags-grid::-webkit-scrollbar-thumb,
.selected-classes-display::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.class-tags-grid::-webkit-scrollbar-thumb:hover,
.selected-classes-display::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
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
                        '<span class="badge badge-status badge-expired">Đã hết hạn</span>' : 
                        '<span class="badge badge-status badge-active">Đang mở</span>';
                    
                    const subjectName = subjects[assignment.subject_id] || assignment.subject_id;
                    
                    const classDisplay = assignment.class_display || (Array.isArray(assignment.class_names) ? assignment.class_names.join(', ') : assignment.class_name);
                    const attachmentCount = Array.isArray(assignment.attachments) ? assignment.attachments.length : 0;
                    const attachmentBadge = attachmentCount > 0 ? `<div class="small text-muted mt-1"><i class="bi bi-paperclip me-1"></i>${attachmentCount} file đính kèm</div>` : '';
                    const row = `
                        <tr>
                            <td><strong>${assignment.title}</strong>${attachmentBadge}</td>
                            <td>${subjectName}</td>
                            <td>${classDisplay || ''}</td>
                            <td>${formatDateTime(assignment.due_date)}</td>
                            <td>${statusBadge}</td>
                            <td>
                                <a href="view_submissions.php?id=${assignment.id}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye me-1"></i>${assignment.submission_count || 0}
                                </a>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-gradient-primary" onclick="editAssignment('${assignment.id}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-gradient-danger" onclick="deleteAssignment('${assignment.id}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                if (assignmentsTable) {
                    assignmentsTable.destroy();
                }
                
                assignmentsTable = $('#assignmentsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 50,
                    order: [[3, 'desc']]
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
        const className = tagElement.querySelector('.tag-name').textContent;
        
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
