<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: ../index.php?role=student');
    exit;
}

$studentCode = $_SESSION['student_code'];

$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Bài Tập - CVD';
include '../includes/student_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-0"><i class="bi bi-journal-check me-2"></i>Bài Tập Của Tôi</h2>
                <p class="text-white-50 mb-0 mt-2">Xem và nộp bài tập được giao từ giáo viên</p>
            </div>
            <a href="my_submissions.php" class="btn btn-light">
                <i class="bi bi-clock-history me-2"></i>Lịch Sử Nộp Bài
            </a>
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
                                <th>Hạn Nộp</th>
                                <th>Trạng Thái</th>
                                <th>Điểm</th>
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

<!-- View Assignment Modal -->
<div class="modal fade" id="viewAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-file-earmark-text me-2"></i>Chi Tiết Bài Tập</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Môn học:</strong> <span id="modalSubject"></span></p>
                        <p class="mb-1"><strong>Hạn nộp:</strong> <span id="modalDueDate"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Điểm tối đa:</strong> <span id="modalMaxScore"></span></p>
                        <p class="mb-1"><strong>Trạng thái:</strong> <span id="modalStatus"></span></p>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <h6><i class="bi bi-info-circle me-2"></i>Yêu Cầu:</h6>
                    <div class="border rounded p-3 bg-light" id="modalDescription" style="white-space: pre-wrap;"></div>
                </div>
                <div class="mb-3" id="modalAttachmentsSection" style="display: none;">
                    <h6><i class="bi bi-paperclip me-2"></i>Tài liệu đính kèm:</h6>
                    <div class="list-group" id="modalAttachments"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-gradient-success" id="submitButton" onclick="goToSubmit()">
                    <i class="bi bi-upload me-2"></i>Nộp Bài
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<script>
const studentCode = '<?php echo $studentCode; ?>';
const studentClass = '<?php echo $studentClass; ?>';
const subjects = <?php echo json_encode($subjects); ?>;
let assignmentsTable;
let currentAssignmentId = '';

$(document).ready(function() {
    loadAssignments();
});

function loadAssignments() {
    fetch(`api/get_student_assignments.php?class=${encodeURIComponent(studentClass)}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('assignmentsBody');
                tbody.innerHTML = '';
                
                result.assignments.forEach(assignment => {
                    const dueDate = new Date(assignment.due_date);
                    const now = new Date();
                    const isExpired = dueDate < now;
                    
                    let statusBadge;
                    let scoreDisplay = '<span class="text-muted">---</span>';
                    
                    if (assignment.my_submission) {
                        if (assignment.my_submission.score !== null) {
                            statusBadge = '<span class="badge badge-graded">Đã chấm</span>';
                            scoreDisplay = `<strong class="text-primary">${assignment.my_submission.score}/${assignment.max_score}</strong>`;
                        } else {
                            statusBadge = '<span class="badge badge-submitted">Đã nộp</span>';
                        }
                    } else {
                        if (isExpired) {
                            statusBadge = '<span class="badge badge-expired">Quá hạn</span>';
                        } else {
                            statusBadge = '<span class="badge badge-pending">Chưa nộp</span>';
                        }
                    }
                    
                    const subjectName = subjects[assignment.subject_id] || assignment.subject_id;
                    const attachmentCount = Array.isArray(assignment.attachments) ? assignment.attachments.length : 0;
                    const attachmentBadge = attachmentCount > 0 ? `<div class="small text-muted mt-1"><i class="bi bi-paperclip me-1"></i>${attachmentCount} tài liệu</div>` : '';
                    
                    const row = `
                        <tr>
                            <td><strong>${assignment.title}</strong>${attachmentBadge}</td>
                            <td>${subjectName}</td>
                            <td>${formatDateTime(assignment.due_date)}</td>
                            <td>${statusBadge}</td>
                            <td>${scoreDisplay}</td>
                            <td>
                                <button class="btn btn-sm btn-gradient-info" onclick="viewAssignment('${assignment.id}')">
                                    <i class="bi bi-eye me-1"></i>Xem
                                </button>
                                ${!assignment.my_submission && !isExpired ? 
                                    `<a href="submit_assignment.php?id=${assignment.id}" class="btn btn-sm btn-gradient-success">
                                        <i class="bi bi-upload me-1"></i>Nộp
                                    </a>` : ''}
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
                    order: [[2, 'asc']]
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function viewAssignment(assignmentId) {
    fetch(`api/get_student_assignments.php?id=${assignmentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.assignment) {
                const assignment = result.assignment;
                currentAssignmentId = assignment.id;
                
                document.getElementById('modalTitle').innerHTML = `<i class="bi bi-file-earmark-text me-2"></i>${assignment.title}`;
                document.getElementById('modalSubject').textContent = subjects[assignment.subject_id] || assignment.subject_id;
                document.getElementById('modalDueDate').textContent = formatDateTime(assignment.due_date);
                document.getElementById('modalMaxScore').textContent = assignment.max_score + ' điểm';
                document.getElementById('modalDescription').textContent = assignment.description;
                renderAttachments(assignment.attachments || []);
                
                const dueDate = new Date(assignment.due_date);
                const now = new Date();
                const isExpired = dueDate < now;
                
                let statusBadge;
                if (assignment.my_submission) {
                    if (assignment.my_submission.score !== null) {
                        statusBadge = '<span class="badge badge-graded">Đã chấm</span>';
                    } else {
                        statusBadge = '<span class="badge badge-submitted">Đã nộp</span>';
                    }
                    document.getElementById('submitButton').style.display = 'none';
                } else {
                    if (isExpired) {
                        statusBadge = '<span class="badge badge-expired">Quá hạn</span>';
                        document.getElementById('submitButton').style.display = 'none';
                    } else {
                        statusBadge = '<span class="badge badge-pending">Chưa nộp</span>';
                        document.getElementById('submitButton').style.display = 'inline-block';
                    }
                }
                
                document.getElementById('modalStatus').innerHTML = statusBadge;
                
                new bootstrap.Modal(document.getElementById('viewAssignmentModal')).show();
            }
        });
}

function goToSubmit() {
    window.location.href = `submit_assignment.php?id=${currentAssignmentId}`;
}

function renderAttachments(attachments) {
    const section = document.getElementById('modalAttachmentsSection');
    const container = document.getElementById('modalAttachments');
    container.innerHTML = '';

    if (!attachments.length) {
        section.style.display = 'none';
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

    section.style.display = 'block';
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

<?php include '../includes/student_footer.php'; ?>
