<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

// Check premium status
require_once __DIR__ . '/../includes/student_premium_helper.php';
$studentCode = $_SESSION['student_code'];
$premiumStatus = getStudentPremiumStatus($studentCode);

if (!$premiumStatus['is_premium']) {
    $_SESSION['error'] = 'Chức năng Bài Tập chỉ dành cho học sinh Premium!';
    header('Location: dashboard.php');
    exit;
}

$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$assignmentId = $_GET['id'] ?? '';
if (empty($assignmentId)) {
    header('Location: assignments.php');
    exit;
}

// Load assignment
$assignmentsFile = __DIR__ . '/../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;

function normalizeClassNames($assignment) {
    $raw = $assignment['class_names'] ?? $assignment['class_name'] ?? [];
    if (is_string($raw)) {
        $raw = [$raw];
    }
    $normalized = [];
    if (is_array($raw)) {
        foreach ($raw as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }
    }
    return array_values(array_unique($normalized));
}

foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId) {
        $classNames = normalizeClassNames($a);
        $myClass = trim(strtolower($studentClass));
        
        foreach ($classNames as $className) {
            if (trim(strtolower($className)) === $myClass) {
                $assignment = $a;
                break 2;
            }
        }
    }
}

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Check if already submitted
$submissionsFile = __DIR__ . '/../data/student_submissions.json';
$submissions = json_decode(file_get_contents($submissionsFile), true) ?: [];
foreach ($submissions as $sub) {
    if ($sub['assignment_id'] === $assignmentId && $sub['student_code'] === $studentCode) {
        $_SESSION['error'] = 'Bạn đã nộp bài tập này rồi!';
        header('Location: assignments.php');
        exit;
    }
}

// Check if expired
$dueDate = new DateTime($assignment['due_date']);
if ($dueDate < new DateTime()) {
    $_SESSION['error'] = 'Bài tập này đã quá hạn nộp!';
    header('Location: assignments.php');
    exit;
}

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Nộp Bài Tập - CVD';
include '../includes/student_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h2 class="mb-2"><i class="bi bi-upload me-2"></i><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p class="text-white-50 mb-1">
                    <i class="bi bi-book me-2"></i><?php echo $subjects[$assignment['subject_id']] ?? $assignment['subject_id']; ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-calendar-event me-2"></i>Hạn nộp: <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?>
                </p>
                <p class="text-white-50 mb-0">
                    <i class="bi bi-star me-2"></i>Điểm tối đa: <?php echo $assignment['max_score']; ?>
                </p>
            </div>
            <a href="assignments.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-2"></i>Quay Lại
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Yêu Cầu Bài Tập
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card selection-card">
                <div class="card-header">
                    <i class="bi bi-pencil-square me-2"></i>Bài Làm Của Bạn
                </div>
                <div class="card-body">
                    <form id="submitAssignmentForm" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nội Dung Bài Làm <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" rows="10" placeholder="Nhập nội dung bài làm của bạn..." required></textarea>
                            <small class="text-muted">Trình bày rõ ràng, chi tiết các bước giải và kết quả</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-file-earmark-text me-2"></i>File Bài Tập Đính Kèm</label>
                            <div class="file-upload-section border rounded p-4 mb-3">
                                <div class="row mb-3">
                                    <div class="col-md-12 mb-2">
                                        <button type="button" class="btn btn-gradient-primary w-100" onclick="document.getElementById('documentInput').click()">
                                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Chọn File Bài Tập (Word, Excel, PDF, ...)
                                        </button>
                                        <input type="file" id="documentInput" accept=".doc,.docx,.xls,.xlsx,.pdf,.ppt,.pptx,.txt,.zip,.rar" multiple style="display: none;" onchange="handleDocumentSelect(event)">
                                    </div>
                                </div>
                                
                                <!-- Document Preview -->
                                <div id="documentPreviewSection" style="display: none;" class="mt-3">
                                    <h6><i class="bi bi-files me-2"></i>File đã chọn:</h6>
                                    <div id="documentPreview" class="list-group">
                                        <!-- Document previews will be added here -->
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Hỗ trợ: Word (.doc, .docx), Excel (.xls, .xlsx), PDF (.pdf), PowerPoint (.ppt, .pptx), Text (.txt), ZIP (.zip, .rar). Tối đa: 10MB/file</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-images me-2"></i>Hình Ảnh Đính Kèm (Tùy chọn)</label>
                            <div class="image-upload-section border rounded p-4">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <button type="button" class="btn btn-gradient-primary w-100" onclick="document.getElementById('fileInput').click()">
                                            <i class="bi bi-folder-plus me-2"></i>Chọn Ảnh Từ Máy Tính
                                        </button>
                                        <input type="file" id="fileInput" accept="image/*" multiple style="display: none;" onchange="handleFileSelect(event)">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <button type="button" class="btn btn-gradient-info w-100" onclick="openCamera()">
                                            <i class="bi bi-camera me-2"></i>Chụp Ảnh
                                        </button>
                                    </div>
                                </div>

                                <!-- Camera Preview -->
                                <div id="cameraSection" style="display: none;" class="mb-3">
                                    <div class="camera-container position-relative">
                                        <video id="cameraPreview" autoplay playsinline class="w-100 rounded"></video>
                                        <div class="camera-controls mt-2 text-center">
                                            <button type="button" class="btn btn-gradient-success me-2" onclick="capturePhoto()">
                                                <i class="bi bi-camera-fill me-2"></i>Chụp
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="closeCamera()">
                                                <i class="bi bi-x-circle me-2"></i>Đóng Camera
                                            </button>
                                        </div>
                                    </div>
                                    <canvas id="photoCanvas" style="display: none;"></canvas>
                                </div>

                                <!-- Image Preview -->
                                <div id="imagePreviewSection" class="mt-3">
                                    <h6>Hình ảnh đã chọn:</h6>
                                    <div id="imagePreview" class="d-flex flex-wrap gap-2">
                                        <!-- Image previews will be added here -->
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Bạn có thể upload hoặc chụp nhiều hình ảnh. Định dạng: JPG, PNG. Tối đa: 5MB/ảnh</small>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-secondary btn-lg me-2" onclick="window.location.href='assignments.php'">
                                <i class="bi bi-x-circle me-2"></i>Hủy
                            </button>
                            <button type="submit" class="btn btn-gradient-success btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Nộp Bài
                            </button>
                        </div>
                    </form>
                </div>
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

.btn-gradient-success:hover {
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
    color: white;
}

.btn-gradient-info {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    border: none;
    color: white;
}

.btn-gradient-info:hover {
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

.image-upload-section {
    background: linear-gradient(135deg, #f7f9fc 0%, #eef2ff 100%);
}

.file-upload-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.document-preview-item {
    border-left: 4px solid #667eea;
}

.document-preview-item:hover {
    background-color: #f8f9fa;
}

.camera-container video {
    background: #000;
    max-height: 400px;
    object-fit: cover;
}

.image-preview-item {
    position: relative;
    display: inline-block;
}

.image-preview-item img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.image-preview-item .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.image-preview-item .remove-btn:hover {
    transform: scale(1.1);
}
</style>

<script>
const assignmentId = '<?php echo $assignmentId; ?>';
let selectedImages = [];
let selectedDocuments = [];
let cameraStream = null;

document.getElementById('submitAssignmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitAssignment();
});

function handleDocumentSelect(event) {
    const files = event.target.files;
    const allowedExtensions = ['.doc', '.docx', '.xls', '.xlsx', '.pdf', '.ppt', '.pptx', '.txt', '.zip', '.rar'];
    
    for (let file of files) {
        if (file.size > 10 * 1024 * 1024) {
            alert('File ' + file.name + ' quá lớn (>10MB). Vui lòng chọn file nhỏ hơn.');
            continue;
        }
        
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(fileExt)) {
            alert('File ' + file.name + ' không được hỗ trợ. Vui lòng chọn file Word, Excel, PDF, PowerPoint, Text hoặc ZIP.');
            continue;
        }
        
        selectedDocuments.push(file);
    }
    
    updateDocumentPreview();
    event.target.value = ''; // Reset input
}

function updateDocumentPreview() {
    const container = document.getElementById('documentPreview');
    container.innerHTML = '';
    
    selectedDocuments.forEach((file, index) => {
        const fileSize = (file.size / 1024).toFixed(2);
        const fileExt = file.name.split('.').pop().toLowerCase();
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
        
        const div = document.createElement('div');
        div.className = 'list-group-item document-preview-item d-flex justify-content-between align-items-center';
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${iconClass} fs-3 me-3 text-primary"></i>
                <div>
                    <div class="fw-bold">${file.name}</div>
                    <small class="text-muted">${fileSize} KB</small>
                </div>
            </div>
            <div>
                <span class="badge ${badgeClass} me-2">${fileExt.toUpperCase()}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeDocument(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('documentPreviewSection').style.display = 
        selectedDocuments.length > 0 ? 'block' : 'none';
}

function removeDocument(index) {
    selectedDocuments.splice(index, 1);
    updateDocumentPreview();
}

function handleFileSelect(event) {
    const files = event.target.files;
    for (let file of files) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Ảnh ' + file.name + ' quá lớn (>5MB). Vui lòng chọn ảnh nhỏ hơn.');
            continue;
        }
        
        if (!file.type.startsWith('image/')) {
            alert('File ' + file.name + ' không phải là hình ảnh.');
            continue;
        }
        
        selectedImages.push(file);
    }
    
    updateImagePreview();
    event.target.value = ''; // Reset input
}

async function openCamera() {
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' } // Use back camera on mobile
        });
        
        const video = document.getElementById('cameraPreview');
        video.srcObject = cameraStream;
        document.getElementById('cameraSection').style.display = 'block';
    } catch (error) {
        alert('Không thể mở camera. Vui lòng kiểm tra quyền truy cập camera.');
        console.error('Camera error:', error);
    }
}

function closeCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    document.getElementById('cameraSection').style.display = 'none';
}

function capturePhoto() {
    const video = document.getElementById('cameraPreview');
    const canvas = document.getElementById('photoCanvas');
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    canvas.toBlob(blob => {
        const file = new File([blob], `camera_${Date.now()}.jpg`, { type: 'image/jpeg' });
        selectedImages.push(file);
        updateImagePreview();
        closeCamera();
    }, 'image/jpeg', 0.9);
}

function updateImagePreview() {
    const container = document.getElementById('imagePreview');
    container.innerHTML = '';
    
    selectedImages.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-btn" onclick="removeImage(${index})">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    
    document.getElementById('imagePreviewSection').style.display = 
        selectedImages.length > 0 ? 'block' : 'none';
}

function removeImage(index) {
    selectedImages.splice(index, 1);
    updateImagePreview();
}

async function submitAssignment() {
    const content = document.getElementById('content').value.trim();
    
    if (!content && selectedDocuments.length === 0) {
        alert('Vui lòng nhập nội dung bài làm hoặc đính kèm file bài tập!');
        return;
    }
    
    const formData = new FormData();
    formData.append('assignment_id', assignmentId);
    formData.append('content', content);
    
    selectedDocuments.forEach((file, index) => {
        formData.append('documents[]', file);
    });
    
    selectedImages.forEach((file, index) => {
        formData.append('images[]', file);
    });
    
    try {
        const response = await fetch('api/submit_assignment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Nộp bài thành công!', 'success');
            setTimeout(() => {
                window.location.href = 'assignments.php';
            }, 1500);
        } else {
            showToast('Lỗi: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi nộp bài. Vui lòng thử lại.', 'error');
    }
}

// Cleanup camera when leaving page
window.addEventListener('beforeunload', closeCamera);
</script>

<script src="../includes/toast-notifications.js"></script>

<?php include '../includes/student_footer.php'; ?>
