<?php
/**
 * PPT/PPTX Uploader - Upload PowerPoint for Online Viewing Only
 * View files using Microsoft Office Online Viewer
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];

// Load user data
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

// Load teacher's assigned subjects
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';

$assignedSubjectIds = [];
$allSubjects = [];

if (file_exists($teacherSubjectsFile)) {
    $teacherSubjectsData = json_decode(file_get_contents($teacherSubjectsFile), true);
    $assignedSubjectIds = $teacherSubjectsData[$username] ?? [];
}

if (file_exists($subjectsFile)) {
    $allSubjects = json_decode(file_get_contents($subjectsFile), true);
}

// Filter subjects to only assigned ones
$teacherSubjects = array_filter($allSubjects, function($subject) use ($assignedSubjectIds) {
    return in_array($subject['id'], $assignedSubjectIds);
});

// Ensure upload directory exists
$uploadDir = __DIR__ . '/../uploads/ppt_files';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Load PPT metadata
$metadataFile = __DIR__ . '/../data/ppt_metadata.json';
$pptFiles = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

$error = '';
$success = '';

// Handle PPT/PPTX Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ppt_file'])) {
    try {
        $file = $_FILES['ppt_file'];
        
        // Validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi upload: ' . $file['error']);
        }
        
        // Check file size (100MB max)
        if ($file['size'] > 100 * 1024 * 1024) {
            throw new Exception('File quá lớn. Giới hạn 100MB.');
        }
        
        // Check file extension
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['ppt', 'pptx'])) {
            throw new Exception('Chỉ chấp nhận file .ppt hoặc .pptx');
        }
        
        // Get form data
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $subject_id = $_POST['subject_id'] ?? '';
        $tags = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
        
        if (empty($title)) {
            $title = pathinfo($filename, PATHINFO_FILENAME);
        }
        
        // Generate unique ID and filename
        $fileId = 'ppt_' . uniqid() . '_' . time();
        $uniqueFilename = $fileId . '.' . $ext;
        $targetPath = $uploadDir . '/' . $uniqueFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Không thể lưu file');
        }
        
        // Create metadata entry
        $metadata = [
            'id' => $fileId,
            'title' => $title,
            'description' => $description,
            'original_filename' => $filename,
            'stored_filename' => $uniqueFilename,
            'file_path' => 'uploads/ppt_files/' . $uniqueFilename,
            'file_size' => $file['size'],
            'file_size_formatted' => formatBytes($file['size']),
            'extension' => $ext,
            'subject_id' => $subject_id,
            'tags' => $tags,
            'teacher_username' => $username,
            'teacher_fullname' => $fullname,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'views' => 0,
            'downloads' => 0
        ];
        
        // Add to metadata array
        $pptFiles[$fileId] = $metadata;
        
        // Save metadata
        file_put_contents($metadataFile, json_encode($pptFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $success = 'Upload thành công! File: ' . $title;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle delete
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];
    
    if (isset($pptFiles[$fileId]) && $pptFiles[$fileId]['teacher_username'] === $username) {
        // Delete file
        $filePath = __DIR__ . '/../' . $pptFiles[$fileId]['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Remove from metadata
        unset($pptFiles[$fileId]);
        file_put_contents($metadataFile, json_encode($pptFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $success = 'Đã xóa file thành công!';
    }
}

// Filter files for this teacher
$myPPTFiles = array_filter($pptFiles, function($file) use ($username) {
    return $file['teacher_username'] === $username;
});

// Helper function
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$title = 'Upload PowerPoint - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .ppt-upload-container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .page-header h1 {
        margin: 0 0 10px 0;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .page-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 1.1rem;
    }

    .upload-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .upload-zone {
        border: 3px dashed #cbd5e1;
        border-radius: 12px;
        padding: 60px 20px;
        text-align: center;
        background: #f8fafc;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 30px;
    }

    .upload-zone:hover {
        border-color: #667eea;
        background: #eff6ff;
    }

    .upload-zone.dragover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .upload-zone i {
        font-size: 64px;
        color: #667eea;
        margin-bottom: 20px;
    }

    .upload-zone h3 {
        color: #334155;
        margin-bottom: 10px;
    }

    .upload-zone p {
        color: #64748b;
        margin-bottom: 20px;
    }

    .btn-upload {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-weight: 600;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .ppt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
        margin-top: 30px;
    }

    .ppt-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s ease;
    }

    .ppt-card:hover {
        border-color: #667eea;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(-4px);
    }

    .ppt-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        margin-bottom: 16px;
    }

    .ppt-card h3 {
        color: #1e293b;
        font-size: 18px;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .ppt-card p {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 12px;
    }

    .ppt-meta {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #64748b;
    }

    .ppt-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ppt-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }

    .ppt-tag {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
    }

    .ppt-actions {
        display: flex;
        gap: 8px;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5a67d8;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 80px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #475569;
        margin-bottom: 10px;
    }
</style>

<div class="ppt-upload-container">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-file-powerpoint me-3"></i>Upload PowerPoint</h1>
        <p>Upload file PPT/PPTX để quản lý và xem trực tuyến qua Microsoft Office Online</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="upload-card">
        <h2 style="margin-bottom: 30px;"><i class="fas fa-cloud-upload-alt me-2"></i>Upload File PowerPoint</h2>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-zone" id="uploadZone">
                <i class="fas fa-file-powerpoint"></i>
                <h3>Kéo thả file PPT/PPTX vào đây</h3>
                <p>hoặc click để chọn file</p>
                <input type="file" name="ppt_file" id="pptFileInput" accept=".ppt,.pptx" style="display: none;" required>
                <button type="button" class="btn-upload" onclick="document.getElementById('pptFileInput').click()">
                    <i class="fas fa-folder-open me-2"></i>Chọn File
                </button>
                <p style="margin-top: 15px; font-size: 13px;">Giới hạn: 100MB</p>
            </div>

            <div id="fileInfo" style="display: none; background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>File đã chọn:</strong> <span id="fileName"></span>
                <span style="float: right; color: #64748b;" id="fileSize"></span>
            </div>

            <div class="form-group">
                <label>Tiêu đề *</label>
                <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề bài giảng..." required>
            </div>

            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Mô tả ngắn gọn về nội dung..."></textarea>
            </div>

            <div class="form-group">
                <label>Môn học</label>
                <select name="subject_id" class="form-control">
                    <option value="">-- Chọn môn học --</option>
                    <?php foreach ($teacherSubjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tags (phân cách bởi dấu phẩy)</label>
                <input type="text" name="tags" class="form-control" placeholder="Ví dụ: Toán, Lớp 10, Đại số">
            </div>

            <button type="submit" class="btn-upload" style="width: 100%;">
                <i class="fas fa-upload me-2"></i>Upload PowerPoint
            </button>
        </form>
    </div>

    <!-- PPT Files List -->
    <div class="upload-card">
        <h2 style="margin-bottom: 30px;"><i class="fas fa-list me-2"></i>File PowerPoint Đã Upload</h2>
        
        <?php if (empty($myPPTFiles)): ?>
            <div class="empty-state">
                <i class="fas fa-file-powerpoint"></i>
                <h3>Chưa có file nào</h3>
                <p>Hãy upload file PowerPoint đầu tiên của bạn!</p>
            </div>
        <?php else: ?>
            <div class="ppt-grid">
                <?php foreach ($myPPTFiles as $file): ?>
                    <div class="ppt-card">
                        <div class="ppt-icon">
                            <i class="fas fa-file-powerpoint"></i>
                        </div>
                        
                        <h3><?php echo htmlspecialchars($file['title']); ?></h3>
                        
                        <?php if (!empty($file['description'])): ?>
                            <p><?php echo htmlspecialchars($file['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="ppt-meta">
                            <div class="ppt-meta-item">
                                <i class="fas fa-hdd"></i>
                                <?php echo $file['file_size_formatted']; ?>
                            </div>
                            <div class="ppt-meta-item">
                                <i class="fas fa-eye"></i>
                                <?php echo $file['views']; ?> lượt xem
                            </div>
                            <div class="ppt-meta-item">
                                <i class="fas fa-download"></i>
                                <?php echo $file['downloads']; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($file['tags'])): ?>
                            <div class="ppt-tags">
                                <?php foreach ($file['tags'] as $tag): ?>
                                    <span class="ppt-tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <small style="display: block; color: #64748b; margin-bottom: 16px;">
                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($file['created_at'])); ?>
                        </small>
                        
                        <div class="ppt-actions">
                            <button class="btn btn-primary" onclick="viewPPT('<?php echo $file['stored_filename']; ?>', '<?php echo htmlspecialchars($file['title']); ?>')">
                                <i class="fas fa-eye"></i> Xem Online
                            </button>
                            <a href="../<?php echo $file['file_path']; ?>" download class="btn btn-success">
                                <i class="fas fa-download"></i> Tải về
                            </a>
                            <button class="btn btn-danger" onclick="deletePPT('<?php echo $file['id']; ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Microsoft Office Online Viewer Modal -->
<div id="pptViewerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999;">
    <div style="position: relative; width: 100%; height: 100%; padding: 20px;">
        <div style="position: absolute; top: 20px; right: 20px; z-index: 10000;">
            <button onclick="closePPTViewer()" style="background: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 16px;">
                <i class="fas fa-times"></i> Đóng
            </button>
        </div>
        <div style="background: white; border-radius: 12px; height: 100%; overflow: hidden;">
            <div style="background: #667eea; color: white; padding: 15px 20px; font-size: 18px; font-weight: 600;">
                <i class="fas fa-file-powerpoint me-2"></i><span id="pptViewerTitle"></span>
            </div>
            <iframe id="pptViewerIframe" style="width: 100%; height: calc(100% - 60px); border: none;"></iframe>
        </div>
    </div>
</div>

<script>
// File input change event
document.getElementById('pptFileInput').addEventListener('change', function(e) {
    if (this.files.length > 0) {
        const file = this.files[0];
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatBytes(file.size);
        document.getElementById('fileInfo').style.display = 'block';
    }
});

// Drag & drop
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('pptFileInput');

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

// View PPT with Microsoft Office Online
function viewPPT(filename, title) {
    // Get file path
    const fileUrl = window.location.origin + '/cvd2/uploads/ppt_files/' + filename;
    
    // Check if running on localhost - Office Online Viewer doesn't work with localhost
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname.includes('192.168');
    
    if (isLocalhost) {
        // For localhost: Show warning and provide download option
        alert('⚠️ Microsoft Office Online Viewer không hoạt động với localhost!\n\n' +
              'Để xem PowerPoint online, bạn cần:\n' +
              '1. Deploy lên server public (có domain)\n' +
              '2. Hoặc dùng ngrok: ngrok http 80\n\n' +
              'File sẽ được tải xuống để bạn mở bằng PowerPoint.');
        
        // Download the file instead
        window.open(fileUrl, '_blank');
        return;
    }
    
    // For public servers: Use Office Online Viewer
    const viewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
    
    document.getElementById('pptViewerTitle').textContent = title;
    document.getElementById('pptViewerIframe').src = viewerUrl;
    document.getElementById('pptViewerModal').style.display = 'block';
}

function closePPTViewer() {
    document.getElementById('pptViewerModal').style.display = 'none';
    document.getElementById('pptViewerIframe').src = '';
}

// Delete PPT
function deletePPT(fileId) {
    if (!confirm('Bạn có chắc muốn xóa file này?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file_id" value="${fileId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// ESC to close viewer
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closePPTViewer();
    }
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
