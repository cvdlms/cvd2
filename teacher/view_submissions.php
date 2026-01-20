<?php
include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$assignmentId = $_GET['id'] ?? '';
if (empty($assignmentId)) {
    header('Location: manage_assignments.php');
    exit;
}

// Load assignment
$assignmentsFile = __DIR__ . '/../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;
foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId && $a['teacher_username'] === $_SESSION['username']) {
        $assignment = $a;
        break;
    }
}

if (!$assignment) {
    header('Location: manage_assignments.php');
    exit;
}

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Xem Bài Nộp - CVD';
include '../includes/teacher_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h2 class="mb-2"><i class="bi bi-file-earmark-text me-2"></i><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p class="text-white-50 mb-1">
                    <i class="bi bi-book me-2"></i><?php echo $subjects[$assignment['subject_id']] ?? $assignment['subject_id']; ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-people me-2"></i><?php echo htmlspecialchars($assignment['class_name']); ?>
                </p>
                <p class="text-white-50 mb-0">
                    <i class="bi bi-calendar-event me-2"></i>Hạn nộp: <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-star me-2"></i>Điểm tối đa: <?php echo $assignment['max_score']; ?>
                </p>
            </div>
            <a href="manage_assignments.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-2"></i>Quay Lại
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Mô Tả Bài Tập
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-collection me-2"></i>Danh Sách Bài Nộp
                </div>
                <div class="card-body">
                    <table id="submissionsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Học Sinh</th>
                                <th>Lớp</th>
                                <th>Thời Gian Nộp</th>
                                <th>Trạng Thái</th>
                                <th>Điểm</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="submissionsBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div class="modal fade" id="viewSubmissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Bài Nộp Của Học Sinh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Học sinh:</strong> <span id="viewStudentName"></span></p>
                        <p class="mb-1"><strong>Mã số:</strong> <span id="viewStudentCode"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Thời gian nộp:</strong> <span id="viewSubmittedAt"></span></p>
                        <p class="mb-1"><strong>Trạng thái:</strong> <span id="viewStatus"></span></p>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <h6><i class="bi bi-file-text me-2"></i>Nội Dung Bài Làm:</h6>
                    <div id="viewContent" class="border rounded p-3 bg-light" style="white-space: pre-wrap; min-height: 100px;"></div>
                </div>
                <div class="mb-3" id="documentsSection" style="display: none;">
                    <h6><i class="bi bi-file-earmark-text me-2"></i>File Bài Tập Đính Kèm:</h6>
                    <div id="viewDocuments" class="list-group"></div>
                </div>
                <div class="mb-3">
                    <h6><i class="bi bi-images me-2"></i>Hình Ảnh Đính Kèm:</h6>
                    <div id="viewImages" class="d-flex flex-wrap gap-2"></div>
                </div>
                <hr>
                <div class="grading-section">
                    <h6><i class="bi bi-pencil-square me-2"></i>Chấm Điểm:</h6>
                    <input type="hidden" id="gradeSubmissionId">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Điểm <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="gradeScore" min="0" max="<?php echo $assignment['max_score']; ?>" step="0.5">
                            <small class="text-muted">Tối đa: <?php echo $assignment['max_score']; ?> điểm</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nhận Xét</label>
                            <textarea class="form-control" id="gradeFeedback" rows="3" placeholder="Nhập nhận xét cho học sinh..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-gradient-success" onclick="saveGrade()">
                    <i class="bi bi-check-circle me-2"></i>Lưu Điểm
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

.btn-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    color: white;
}

.btn-gradient-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    border: none;
    color: white;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.badge-submitted {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
}

.badge-not-submitted {
    background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
}

.badge-graded {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
}

.submission-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s;
}

.submission-image:hover {
    transform: scale(1.05);
}

.document-item {
    border-left: 4px solid #667eea;
    transition: all 0.3s;
}

.document-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.download-btn {
    transition: all 0.3s;
}

.download-btn:hover {
    transform: scale(1.1);
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
const assignmentId = '<?php echo $assignmentId; ?>';
let submissionsTable;

$(document).ready(function() {
    loadSubmissions();
});

function loadSubmissions() {
    fetch(`api/get_submissions.php?assignment_id=${assignmentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('submissionsBody');
                tbody.innerHTML = '';
                
                result.submissions.forEach(submission => {
                    let statusBadge;
                    if (submission.score !== null && submission.score !== undefined) {
                        statusBadge = '<span class="badge badge-graded">Đã chấm</span>';
                    } else {
                        statusBadge = '<span class="badge badge-submitted">Chưa chấm</span>';
                    }
                    
                    const scoreDisplay = submission.score !== null ? 
                        `<strong class="text-primary">${submission.score}/${<?php echo $assignment['max_score']; ?>}</strong>` : 
                        '<span class="text-muted">---</span>';
                    
                    const row = `
                        <tr>
                            <td>${submission.student_name}</td>
                            <td>${submission.student_class}</td>
                            <td>${formatDateTime(submission.submitted_at)}</td>
                            <td>${statusBadge}</td>
                            <td>${scoreDisplay}</td>
                            <td>
                                <button class="btn btn-sm btn-gradient-primary" onclick="viewSubmission('${submission.id}')">
                                    <i class="bi bi-eye me-1"></i>Xem & Chấm
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                if (submissionsTable) {
                    submissionsTable.destroy();
                }
                
                submissionsTable = $('#submissionsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 50,
                    order: [[2, 'desc']]
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function viewSubmission(submissionId) {
    fetch(`api/get_submissions.php?id=${submissionId}`)
        .then(response => response.json())
        .then(result => {
            console.log('Submission result:', result);
            if (result.success && result.submission) {
                const sub = result.submission;
                
                document.getElementById('viewStudentName').textContent = sub.student_name;
                document.getElementById('viewStudentCode').textContent = sub.student_code;
                document.getElementById('viewSubmittedAt').textContent = formatDateTime(sub.submitted_at);
                
                const statusBadge = sub.score !== null ? 
                    '<span class="badge badge-graded">Đã chấm</span>' : 
                    '<span class="badge badge-submitted">Chưa chấm</span>';
                document.getElementById('viewStatus').innerHTML = statusBadge;
                
                document.getElementById('viewContent').textContent = sub.content || '(Không có nội dung)';
                
                // Display documents
                const documentsContainer = document.getElementById('viewDocuments');
                const documentsSection = document.getElementById('documentsSection');
                documentsContainer.innerHTML = '';
                if (sub.documents && sub.documents.length > 0) {
                    documentsSection.style.display = 'block';
                    sub.documents.forEach(doc => {
                        const fileExt = doc.extension || doc.path.split('.').pop().toLowerCase();
                        let iconClass = 'bi-file-earmark';
                        let badgeClass = 'bg-secondary';
                        
                        // Set icon and badge based on file type
                        if (fileExt === 'doc' || fileExt === 'docx') {
                            iconClass = 'bi-file-earmark-word';
                            badgeClass = 'bg-primary';
                        } else if (fileExt === 'xls' || fileExt === 'xlsx') {
                            iconClass = 'bi-file-earmark-excel';
                            badgeClass = 'bg-success';
                        } else if (fileExt === 'pdf') {
                            iconClass = 'bi-file-earmark-pdf';
                            badgeClass = 'bg-danger';
                        } else if (fileExt === 'ppt' || fileExt === 'pptx') {
                            iconClass = 'bi-file-earmark-ppt';
                            badgeClass = 'bg-warning';
                        } else if (fileExt === 'zip' || fileExt === 'rar') {
                            iconClass = 'bi-file-earmark-zip';
                            badgeClass = 'bg-info';
                        }
                        
                        const fileSize = doc.size ? (doc.size / 1024).toFixed(2) + ' KB' : 'N/A';
                        const fileName = doc.filename || doc.path.split('/').pop();
                        
                        const div = document.createElement('div');
                        div.className = 'list-group-item document-item d-flex justify-content-between align-items-center';
                        div.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="bi ${iconClass} fs-3 me-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold">${fileName}</div>
                                    <small class="text-muted">${fileSize}</small>
                                </div>
                            </div>
                            <div>
                                <span class="badge ${badgeClass} me-2">${fileExt.toUpperCase()}</span>
                                <a href="api/download_file.php?file=${encodeURIComponent(doc.path)}" class="btn btn-sm btn-primary download-btn" title="Tải xuống" target="_blank">
                                    <i class="bi bi-download"></i> Tải xuống
                                </a>
                            </div>
                        `;
                        documentsContainer.appendChild(div);
                    });
                } else {
                    documentsSection.style.display = 'none';
                }
                
                // Display images
                const imagesContainer = document.getElementById('viewImages');
                imagesContainer.innerHTML = '';
                if (sub.images && sub.images.length > 0) {
                    sub.images.forEach(imagePath => {
                        const img = document.createElement('img');
                        img.src = 'api/download_file.php?file=' + encodeURIComponent(imagePath);
                        img.className = 'submission-image';
                        img.onclick = () => window.open('api/download_file.php?file=' + encodeURIComponent(imagePath), '_blank');
                        imagesContainer.appendChild(img);
                    });
                } else {
                    imagesContainer.innerHTML = '<p class="text-muted">Không có hình ảnh đính kèm</p>';
                }
                
                // Set grading fields
                document.getElementById('gradeSubmissionId').value = sub.id;
                document.getElementById('gradeScore').value = sub.score || '';
                document.getElementById('gradeFeedback').value = sub.feedback || '';
                
                new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
            } else {
                console.error('Error:', result.message);
                showToast('Không thể tải bài nộp: ' + (result.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Có lỗi xảy ra khi tải bài nộp', 'error');
        });
}

function saveGrade() {
    const submissionId = document.getElementById('gradeSubmissionId').value;
    const score = parseFloat(document.getElementById('gradeScore').value);
    const feedback = document.getElementById('gradeFeedback').value;
    
    if (isNaN(score) || score < 0 || score > <?php echo $assignment['max_score']; ?>) {
        showToast('Vui lòng nhập điểm hợp lệ (0 - <?php echo $assignment['max_score']; ?>)!', 'warning');
        return;
    }
    
    fetch('api/grade_submission.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            submission_id: submissionId,
            score: score,
            feedback: feedback
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('Đã lưu điểm thành công!', 'success');
            
            // Update modal status immediately
            document.getElementById('viewStatus').innerHTML = '<span class="badge badge-graded">Đã chấm</span>';
            
            // Close modal and reload submissions
            bootstrap.Modal.getInstance(document.getElementById('viewSubmissionModal')).hide();
            
            // Reload submissions to update the table
            setTimeout(() => {
                loadSubmissions();
            }, 300);
        } else {
            showToast('Lỗi: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi lưu điểm', 'error');
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

<script src="../includes/toast-notifications.js"></script>

<?php include '../includes/teacher_footer.php'; ?>
