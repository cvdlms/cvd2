<?php
/**
 * Slides Manager - Quản lý PowerPoint & HTML Slides
 * 1. Import PPT - Xem bằng Microsoft Office Online
 * 2. HTML Slides - Chỉnh sửa code trực tiếp
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

// Load PPT files
$pptMetadataFile = __DIR__ . '/../data/ppt_metadata.json';
$pptFiles = file_exists($pptMetadataFile) ? json_decode(file_get_contents($pptMetadataFile), true) : [];
$myPPTFiles = array_filter($pptFiles, fn($f) => $f['teacher_username'] === $username);

// Load HTML slides
$htmlMetadataFile = __DIR__ . '/../data/html_slides_metadata.json';
$htmlSlides = file_exists($htmlMetadataFile) ? json_decode(file_get_contents($htmlMetadataFile), true) : [];
$myHTMLSlides = array_filter($htmlSlides, fn($s) => $s['teacher_username'] === $username);

$title = 'Quản Lý Slides - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
    }

    .slides-container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 40px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }

    .page-header h1 {
        margin: 0 0 10px 0;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .page-header p {
        margin: 0;
        opacity: 0.95;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .stat-icon.ppt { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-icon.html { background: linear-gradient(135deg, #667eea, #764ba2); }

    .stat-info h3 {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    .stat-info p {
        margin: 4px 0 0 0;
        color: #1e293b;
        font-size: 24px;
        font-weight: 700;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-warning {
        background: linear-gradient(135deg, var(--warning), #d97706);
        color: white;
    }

    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
    }

    .tabs {
        display: flex;
        gap: 10px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 30px;
    }

    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab:hover {
        color: var(--primary);
    }

    .tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .slides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
    }

    .slide-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
    }

    .slide-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(-4px);
    }

    .slide-thumbnail {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 64px;
    }

    .slide-body {
        padding: 20px;
    }

    .slide-body h3 {
        margin: 0 0 8px 0;
        color: #1e293b;
        font-size: 18px;
        font-weight: 600;
    }

    .slide-body p {
        margin: 0 0 16px 0;
        color: #64748b;
        font-size: 14px;
    }

    .slide-meta {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #64748b;
    }

    .slide-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .slide-actions {
        display: flex;
        gap: 8px;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-secondary {
        background: #64748b;
        color: white;
    }

    .btn-secondary:hover {
        background: #475569;
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #64748b;
        margin-bottom: 10px;
    }

    .slide-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }

    .slide-tag {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
    }
</style>

<div class="slides-container">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-presentation me-3"></i>Quản Lý Slides Giảng Dạy</h1>
        <p>Import PowerPoint để xem online hoặc tạo HTML slides từ code với nhiều templates đẹp</p>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon ppt">
                <i class="fas fa-file-powerpoint"></i>
            </div>
            <div class="stat-info">
                <h3>PowerPoint Files</h3>
                <p><?php echo count($myPPTFiles); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon html">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-info">
                <h3>HTML Slides</h3>
                <p><?php echo count($myHTMLSlides); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3>Tổng Slides</h3>
                <p><?php echo count($myPPTFiles) + count($myHTMLSlides); ?></p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="import_pptx.php" class="btn btn-warning">
            <i class="fas fa-cloud-upload"></i> Upload PowerPoint
        </a>
        <a href="slide_builder.php" class="btn btn-primary">
            <i class="fas fa-code"></i> Tạo HTML Slide Mới
        </a>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchTab('all')">
            <i class="fas fa-th"></i> Tất Cả
        </button>
        <button class="tab" onclick="switchTab('ppt')">
            <i class="fas fa-file-powerpoint"></i> PowerPoint
        </button>
        <button class="tab" onclick="switchTab('html')">
            <i class="fas fa-code"></i> HTML Slides
        </button>
    </div>

    <!-- All Slides Tab -->
    <div id="tab-all" class="tab-content active">
        <?php if (empty($myPPTFiles) && empty($myHTMLSlides)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Chưa có slide nào</h3>
                <p>Hãy upload PowerPoint hoặc tạo HTML slide đầu tiên!</p>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <!-- PPT Files -->
                <?php foreach ($myPPTFiles as $ppt): ?>
                    <div class="slide-card">
                        <div class="slide-thumbnail" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-file-powerpoint"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($ppt['title']); ?></h3>
                            <?php if (!empty($ppt['description'])): ?>
                                <p><?php echo htmlspecialchars(substr($ppt['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-file"></i> <?php echo $ppt['extension']; ?>
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-hdd"></i> <?php echo $ppt['file_size_formatted']; ?>
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-eye"></i> <?php echo $ppt['views']; ?>
                                </div>
                            </div>

                            <?php if (!empty($ppt['tags'])): ?>
                                <div class="slide-tags">
                                    <?php foreach (array_slice($ppt['tags'], 0, 3) as $tag): ?>
                                        <span class="slide-tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <small style="display: block; color: #94a3b8; margin-bottom: 16px;">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($ppt['created_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <button class="btn btn-sm btn-primary" onclick="viewPPT('<?php echo $ppt['stored_filename']; ?>', '<?php echo htmlspecialchars($ppt['title']); ?>')">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                <a href="../<?php echo $ppt['file_path']; ?>" download class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Tải
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deletePPT('<?php echo $ppt['id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- HTML Slides -->
                <?php foreach ($myHTMLSlides as $slide): ?>
                    <div class="slide-card">
                        <div class="slide-thumbnail">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-code"></i> HTML
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-eye"></i> <?php echo $slide['views'] ?? 0; ?>
                                </div>
                            </div>

                            <small style="display: block; color: #94a3b8; margin-bottom: 16px;">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($slide['updated_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <a href="slide_builder.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="viewHTMLSlide('<?php echo $slide['id']; ?>')">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHTMLSlide('<?php echo $slide['id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PPT Only Tab -->
    <div id="tab-ppt" class="tab-content">
        <?php if (empty($myPPTFiles)): ?>
            <div class="empty-state">
                <i class="fas fa-file-powerpoint"></i>
                <h3>Chưa có file PowerPoint</h3>
                <p>Hãy upload file PPT/PPTX đầu tiên!</p>
                <a href="import_pptx.php" class="btn btn-warning" style="margin-top: 20px;">
                    <i class="fas fa-cloud-upload"></i> Upload PowerPoint
                </a>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <?php foreach ($myPPTFiles as $ppt): ?>
                    <div class="slide-card">
                        <div class="slide-thumbnail" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-file-powerpoint"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($ppt['title']); ?></h3>
                            <?php if (!empty($ppt['description'])): ?>
                                <p><?php echo htmlspecialchars(substr($ppt['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-file"></i> <?php echo $ppt['extension']; ?>
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-hdd"></i> <?php echo $ppt['file_size_formatted']; ?>
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-eye"></i> <?php echo $ppt['views']; ?>
                                </div>
                            </div>

                            <?php if (!empty($ppt['tags'])): ?>
                                <div class="slide-tags">
                                    <?php foreach (array_slice($ppt['tags'], 0, 3) as $tag): ?>
                                        <span class="slide-tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <small style="display: block; color: #94a3b8; margin-bottom: 16px;">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($ppt['created_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <button class="btn btn-sm btn-primary" onclick="viewPPT('<?php echo $ppt['stored_filename']; ?>', '<?php echo htmlspecialchars($ppt['title']); ?>')">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                <a href="../<?php echo $ppt['file_path']; ?>" download class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Tải
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deletePPT('<?php echo $ppt['id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- HTML Only Tab -->
    <div id="tab-html" class="tab-content">
        <?php if (empty($myHTMLSlides)): ?>
            <div class="empty-state">
                <i class="fas fa-code"></i>
                <h3>Chưa có HTML slide</h3>
                <p>Hãy tạo HTML slide đầu tiên từ templates có sẵn!</p>
                <a href="slide_builder.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-code"></i> Tạo HTML Slide
                </a>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <?php foreach ($myHTMLSlides as $slide): ?>
                    <div class="slide-card">
                        <div class="slide-thumbnail">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-code"></i> HTML
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-eye"></i> <?php echo $slide['views'] ?? 0; ?>
                                </div>
                            </div>

                            <small style="display: block; color: #94a3b8; margin-bottom: 16px;">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($slide['updated_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <a href="slide_builder.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Sửa Code
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="viewHTMLSlide('<?php echo $slide['id']; ?>')">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHTMLSlide('<?php echo $slide['id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PPT Viewer Modal -->
<div id="pptViewerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999;">
    <div style="position: relative; width: 100%; height: 100%; padding: 20px;">
        <button onclick="closePPTViewer()" style="position: absolute; top: 30px; right: 30px; background: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 16px; z-index: 10000;">
            <i class="fas fa-times me-2"></i>Đóng
        </button>
        <div style="background: white; border-radius: 12px; height: 100%; overflow: hidden;">
            <div style="background: #667eea; color: white; padding: 15px 20px; font-size: 18px; font-weight: 600;">
                <i class="fas fa-file-powerpoint me-2"></i><span id="pptViewerTitle"></span>
            </div>
            <iframe id="pptViewerIframe" style="width: 100%; height: calc(100% - 60px); border: none;"></iframe>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.closest('.tab').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
}

function viewPPT(filename, title) {
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

function deletePPT(fileId) {
    if (!confirm('Bạn có chắc muốn xóa file PowerPoint này?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'import_pptx.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file_id" value="${fileId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewHTMLSlide(slideId) {
    window.open('../uploads/html_slides/' + slideId + '.html', '_blank');
}

function deleteHTMLSlide(slideId) {
    if (!confirm('Bạn có chắc muốn xóa HTML slide này?')) return;
    
    fetch('api/delete_html_slide.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({slide_id: slideId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    });
}

// ESC to close viewer
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closePPTViewer();
    }
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
