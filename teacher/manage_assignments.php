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
    $_SESSION['error'] = 'Chức năng Bài Tập chỉ dành cho giáo viên Premium!';
    header('Location: teacher.php');
    exit;
}

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
                            <label class="form-label fw-bold">Lớp <span class="text-danger">*</span></label>
                            <select class="form-select" id="assignmentClass" required>
                                <option value="">-- Chọn lớp --</option>
                                <?php 
                                $firstClass = true;
                                foreach ($assignedClasses as $class): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($class['code']); ?>" <?php echo $firstClass ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['code']); ?></option>
                                <?php 
                                $firstClass = false;
                                endforeach; 
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô Tả / Yêu Cầu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="assignmentDescription" rows="5" required placeholder="Nhập yêu cầu chi tiết cho bài tập..."></textarea>
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
                            <label class="form-label fw-bold">Lớp <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAssignmentClass" required>
                                <option value="">-- Chọn lớp --</option>
                                <?php foreach ($assignedClasses as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class['code']); ?>"><?php echo htmlspecialchars($class['code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô Tả / Yêu Cầu <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editAssignmentDescription" rows="5" required></textarea>
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
                    
                    const row = `
                        <tr>
                            <td><strong>${assignment.title}</strong></td>
                            <td>${subjectName}</td>
                            <td>${assignment.class_name}</td>
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
    const className = document.getElementById('assignmentClass').value;
    const description = document.getElementById('assignmentDescription').value;
    const dueDate = document.getElementById('assignmentDueDate').value;
    const maxScore = document.getElementById('assignmentMaxScore').value;
    
    if (!title || !subject || !className || !description || !dueDate || !maxScore) {
        showToast('Vui lòng điền đầy đủ thông tin!', 'warning');
        return;
    }
    
    fetch('api/manage_assignment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'create',
            title: title,
            subject_id: subject,
            class_name: className,
            description: description,
            due_date: dueDate,
            max_score: maxScore
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Tạo bài tập thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createAssignmentModal')).hide();
            document.getElementById('createAssignmentForm').reset();
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
                document.getElementById('editAssignmentClass').value = assignment.class_name;
                document.getElementById('editAssignmentDescription').value = assignment.description;
                document.getElementById('editAssignmentDueDate').value = assignment.due_date.replace(' ', 'T');
                document.getElementById('editAssignmentMaxScore').value = assignment.max_score;
                
                new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
            }
        });
}

function updateAssignment() {
    const id = document.getElementById('editAssignmentId').value;
    const title = document.getElementById('editAssignmentTitle').value;
    const subject = document.getElementById('editAssignmentSubject').value;
    const className = document.getElementById('editAssignmentClass').value;
    const description = document.getElementById('editAssignmentDescription').value;
    const dueDate = document.getElementById('editAssignmentDueDate').value;
    const maxScore = document.getElementById('editAssignmentMaxScore').value;
    
    fetch('api/manage_assignment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update',
            id: id,
            title: title,
            subject_id: subject,
            class_name: className,
            description: description,
            due_date: dueDate,
            max_score: maxScore
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Cập nhật bài tập thành công!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editAssignmentModal')).hide();
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
</script>

<?php include '../includes/teacher_footer.php'; ?>
