<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Lịch Sử Nộp Bài - CVD';
include '../includes/student_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Lịch Sử Nộp Bài</h2>
                <p class="text-white-50 mb-0 mt-2">Xem lại các bài tập đã nộp và điểm số</p>
            </div>
            <a href="assignments.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-2"></i>Danh Sách Bài Tập
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-check me-2"></i>Bài Đã Nộp
                </div>
                <div class="card-body">
                    <table id="submissionsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tiêu Đề</th>
                                <th>Môn Học</th>
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
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-file-earmark-text me-2"></i>Chi Tiết Bài Nộp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Môn học:</strong> <span id="viewSubject"></span></p>
                        <p class="mb-1"><strong>Thời gian nộp:</strong> <span id="viewSubmittedAt"></span></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-1"><strong>Điểm:</strong> <span id="viewScore" class="fs-4"></span></p>
                        <p class="mb-1"><strong>Trạng thái:</strong> <span id="viewStatus"></span></p>
                    </div>
                </div>
                <hr>
                
                <div class="mb-3">
                    <h6><i class="bi bi-clipboard-check me-2"></i>Yêu Cầu Bài Tập:</h6>
                    <div class="border rounded p-3 bg-light" id="viewRequirement" style="white-space: pre-wrap;"></div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-file-text me-2"></i>Nội Dung Bài Làm:</h6>
                    <div class="border rounded p-3 bg-light" id="viewContent" style="white-space: pre-wrap;"></div>
                </div>
                
                <div class="mb-3">
                    <h6><i class="bi bi-images me-2"></i>Hình Ảnh Đính Kèm:</h6>
                    <div id="viewImages" class="d-flex flex-wrap gap-2"></div>
                </div>
                
                <div id="feedbackSection" style="display: none;">
                    <hr>
                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-chat-left-text me-2"></i>Nhận Xét Của Giáo Viên:</h6>
                        <p id="viewFeedback" class="mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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

.btn-gradient-info {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    border: none;
    color: white;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.badge-graded {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
}

.badge-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
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
    border: 2px solid #e2e8f0;
}

.submission-image:hover {
    transform: scale(1.05);
}

.score-excellent {
    color: #11998e;
    font-weight: bold;
}

.score-good {
    color: #667eea;
    font-weight: bold;
}

.score-average {
    color: #f59e0b;
    font-weight: bold;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
const studentCode = '<?php echo $studentCode; ?>';
const subjects = <?php echo json_encode($subjects); ?>;
let submissionsTable;

$(document).ready(function() {
    loadSubmissions();
});

function loadSubmissions() {
    fetch(`api/get_my_submissions.php`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('submissionsBody');
                tbody.innerHTML = '';
                
                result.submissions.forEach(submission => {
                    let statusBadge;
                    let scoreDisplay;
                    let scoreClass = '';
                    
                    if (submission.score !== null) {
                        statusBadge = '<span class="badge badge-graded">Đã chấm</span>';
                        const percentage = (submission.score / submission.max_score) * 100;
                        if (percentage >= 80) scoreClass = 'score-excellent';
                        else if (percentage >= 50) scoreClass = 'score-good';
                        else scoreClass = 'score-average';
                        
                        scoreDisplay = `<strong class="${scoreClass}">${submission.score}/${submission.max_score}</strong>`;
                    } else {
                        statusBadge = '<span class="badge badge-pending">Chưa chấm</span>';
                        scoreDisplay = '<span class="text-muted">Chưa có</span>';
                    }
                    
                    const subjectName = subjects[submission.subject_id] || submission.subject_id;
                    
                    const row = `
                        <tr>
                            <td><strong>${submission.title}</strong></td>
                            <td>${subjectName}</td>
                            <td>${formatDateTime(submission.submitted_at)}</td>
                            <td>${statusBadge}</td>
                            <td>${scoreDisplay}</td>
                            <td>
                                <button class="btn btn-sm btn-gradient-info" onclick="viewSubmission('${submission.id}')">
                                    <i class="bi bi-eye me-1"></i>Xem Chi Tiết
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
    fetch(`api/get_my_submissions.php?id=${submissionId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.submission) {
                const sub = result.submission;
                
                document.getElementById('modalTitle').innerHTML = 
                    `<i class="bi bi-file-earmark-text me-2"></i>${sub.title}`;
                document.getElementById('viewSubject').textContent = 
                    subjects[sub.subject_id] || sub.subject_id;
                document.getElementById('viewSubmittedAt').textContent = 
                    formatDateTime(sub.submitted_at);
                document.getElementById('viewRequirement').textContent = sub.description;
                document.getElementById('viewContent').textContent = sub.content;
                
                // Display score
                if (sub.score !== null) {
                    const percentage = (sub.score / sub.max_score) * 100;
                    let scoreClass = '';
                    if (percentage >= 80) scoreClass = 'score-excellent';
                    else if (percentage >= 50) scoreClass = 'score-good';
                    else scoreClass = 'score-average';
                    
                    document.getElementById('viewScore').innerHTML = 
                        `<span class="${scoreClass}">${sub.score}/${sub.max_score}</span>`;
                    document.getElementById('viewStatus').innerHTML = 
                        '<span class="badge badge-graded">Đã chấm</span>';
                } else {
                    document.getElementById('viewScore').textContent = 'Chưa có';
                    document.getElementById('viewStatus').innerHTML = 
                        '<span class="badge badge-pending">Chưa chấm</span>';
                }
                
                // Display images
                const imagesContainer = document.getElementById('viewImages');
                imagesContainer.innerHTML = '';
                if (sub.images && sub.images.length > 0) {
                    sub.images.forEach(imagePath => {
                        const img = document.createElement('img');
                        img.src = '../' + imagePath;
                        img.className = 'submission-image';
                        img.onclick = () => window.open('../' + imagePath, '_blank');
                        imagesContainer.appendChild(img);
                    });
                } else {
                    imagesContainer.innerHTML = '<p class="text-muted">Không có hình ảnh đính kèm</p>';
                }
                
                // Display feedback
                if (sub.feedback) {
                    document.getElementById('viewFeedback').textContent = sub.feedback;
                    document.getElementById('feedbackSection').style.display = 'block';
                } else {
                    document.getElementById('feedbackSection').style.display = 'none';
                }
                
                new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
            }
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

<?php include '../includes/student_footer.php'; ?>
