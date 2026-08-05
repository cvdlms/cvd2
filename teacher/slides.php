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

// Load HTML slides (old single-slide format)
$htmlMetadataFile = __DIR__ . '/../data/html_slides_metadata.json';
$htmlSlides = file_exists($htmlMetadataFile) ? json_decode(file_get_contents($htmlMetadataFile), true) : [];
$myHTMLSlides = array_filter($htmlSlides, fn($s) => $s['teacher_username'] === $username);

// Load HTML presentations (new multi-slide format)
$presentationsMetadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
$presentations = file_exists($presentationsMetadataFile) ? json_decode(file_get_contents($presentationsMetadataFile), true) : [];
$myPresentations = array_filter($presentations, fn($p) => $p['teacher_username'] === $username);

// Load HTML slide templates for quick start
$templateMetadataFile = __DIR__ . '/../data/html_templates_metadata.json';
$templateMetadata = file_exists($templateMetadataFile) ? json_decode(file_get_contents($templateMetadataFile), true) : [];
$htmlTemplates = is_array($templateMetadata) ? ($templateMetadata['templates'] ?? []) : [];
$templateCategories = is_array($templateMetadata) ? ($templateMetadata['categories'] ?? []) : [];

// Load teacher's assigned subjects for the upload form
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$assignedSubjectIds = [];
if (file_exists($teacherSubjectsFile)) {
    $teacherSubjectsData = json_decode(file_get_contents($teacherSubjectsFile), true);
    $assignedSubjectIds = $teacherSubjectsData[$username] ?? [];
}
$allSubjects = file_exists($subjectsFile) ? json_decode(file_get_contents($subjectsFile), true) : [];
$teacherSubjects = array_values(array_filter($allSubjects, function($s) use ($assignedSubjectIds) {
    return in_array($s['id'], $assignedSubjectIds);
}));

// Flash message from import_pptx.php redirect
$pptFlash = $_SESSION['ppt_flash'] ?? null;
unset($_SESSION['ppt_flash']);

$title = 'Quản Lý Slides - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
    .slides-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .slides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
    }

    .slide-card {
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .slide-card:hover {
        border-color: rgba(79, 70, 229, .35);
        box-shadow: var(--shadow-md);
        transform: translateY(-4px);
    }

    .slide-thumbnail {
        width: 100%;
        height: 200px;
        background: var(--grad-accent);
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
        color: var(--ink);
        font-size: 18px;
        font-weight: 600;
    }

    .slide-body p {
        margin: 0 0 16px 0;
        color: var(--muted);
        font-size: 14px;
    }

    .slide-meta {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: var(--muted);
    }

    .slide-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .slide-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }

    .slide-tag {
        background: var(--border-soft);
        color: var(--muted-strong);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .slide-actions {
        display: flex;
        gap: 8px;
    }

    .template-section {
        margin: 0 0 30px;
    }

    .template-category-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }

    .template-filter {
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--muted-strong);
        border-radius: 999px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
    }

    .template-filter.active,
    .template-filter:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }

    .template-card {
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .template-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .template-thumb {
        height: 120px;
        background: var(--grad-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 42px;
    }

    .template-body {
        padding: 14px;
    }

    .template-body h3 {
        margin: 0 0 7px;
        font-size: 15px;
        color: var(--ink);
    }

    .template-body p {
        min-height: 38px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.5;
        margin: 0 0 12px;
    }

    .ppt-viewer-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(10, 14, 30, .92);
        z-index: 9999;
        padding: 20px;
    }

    .ppt-viewer-frame {
        background: var(--surface);
        border-radius: var(--radius-lg);
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: var(--shadow-lg);
    }

    .ppt-viewer-header {
        background: var(--ink-rail);
        color: #fff;
        padding: 15px 20px;
        font-size: 17px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ppt-viewer-title {
        display: flex;
        align-items: center;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ppt-viewer-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .ppt-viewer-iframe {
        width: 100%;
        flex: 1;
        border: none;
    }

    .upload-zone {
        border: 2px dashed var(--border);
        border-radius: var(--radius);
        padding: 32px 20px;
        text-align: center;
        background: #F8F9FD;
        transition: all .25s ease;
        cursor: pointer;
        margin-bottom: 20px;
    }

    .upload-zone:hover {
        border-color: var(--accent);
        background: var(--accent-light);
    }

    .upload-zone.dragover {
        border-color: var(--success);
        background: var(--success-light);
    }

    .upload-zone i {
        font-size: 44px;
        color: var(--accent);
        margin-bottom: 12px;
    }

    .upload-zone h3 {
        color: var(--ink);
        margin-bottom: 6px;
        font-family: var(--display);
        font-size: 17px;
    }

    .upload-zone p {
        color: var(--muted);
        margin-bottom: 14px;
        font-size: 14px;
    }

    .btn-upload {
        background: var(--grad-accent);
        color: #fff;
        border: none;
        padding: 11px 26px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all .25s ease;
    }

    .btn-upload:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-accent);
    }

    .file-info {
        background: var(--surface);
        border: 1px solid var(--border-soft);
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
        font-size: 14px;
    }

    .slides-container .tab-content {
        display: none;
    }

    .slides-container .tab-content.active {
        display: block;
    }
</style>

<div class="slides-container">
    <?php if ($pptFlash): ?>
    <div class="alert alert-<?php echo $pptFlash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4 ppt-flash" role="alert">
        <i class="fas fa-<?php echo $pptFlash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
        <?php echo htmlspecialchars($pptFlash['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="section-header mb-4">
        <div class="sh-icon">
            <i class="fas fa-presentation"></i>
        </div>
        <div>
            <h3>Quản Lý Slides Giảng Dạy</h3>
            <p>Import PowerPoint để xem online hoặc tạo HTML slides từ code với nhiều templates đẹp</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stat-row mb-4">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-file-powerpoint"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo count($myPPTFiles); ?></div>
                <div class="stat-label">PowerPoint Files</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-code"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo count($myHTMLSlides) + count($myPresentations); ?></div>
                <div class="stat-label">HTML Slides</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo count($myPPTFiles) + count($myHTMLSlides) + count($myPresentations); ?></div>
                <div class="stat-label">Tổng Slides</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-3 mb-4 flex-wrap">
        <button type="button" class="btn btn-warning btn-action-custom" data-bs-toggle="modal" data-bs-target="#uploadPptModal">
            <i class="fas fa-cloud-upload"></i> Upload PowerPoint
        </button>
        <a href="slide_builder.php" class="btn btn-primary btn-action-custom">
            <i class="fas fa-code"></i> Tạo HTML Slide Mới
        </a>
    </div>

    <?php if (!empty($htmlTemplates)): ?>
        <div class="template-section">
            <div class="section-header mb-3">
                <div class="sh-icon alt">
                    <i class="fas fa-palette"></i>
                </div>
                <div>
                    <h3>Templates HTML Mẫu</h3>
                    <p><?php echo count($htmlTemplates); ?> mẫu có sẵn</p>
                </div>
            </div>

            <?php if (!empty($templateCategories)): ?>
                <div class="template-category-filters">
                    <button class="template-filter active" type="button" onclick="filterTemplateCards('all', this)">Tất cả</button>
                    <?php foreach ($templateCategories as $category): ?>
                        <button class="template-filter" type="button" onclick="filterTemplateCards('<?php echo htmlspecialchars($category['id'] ?? ''); ?>', this)">
                            <?php echo htmlspecialchars($category['name'] ?? ($category['id'] ?? '')); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="template-grid">
                <?php foreach ($htmlTemplates as $template): ?>
                    <?php
                        $templateId = $template['id'] ?? '';
                        $templateCategory = $template['category'] ?? '';
                        $templateIcon = $template['icon'] ?? '📄';
                    ?>
                    <div class="template-card eduvn-card" data-template-category="<?php echo htmlspecialchars($templateCategory); ?>">
                        <div class="template-thumb">
                            <?php echo htmlspecialchars($templateIcon); ?>
                        </div>
                        <div class="template-body">
                            <h3><?php echo htmlspecialchars($template['name'] ?? $templateId); ?></h3>
                            <p><?php echo htmlspecialchars($template['description'] ?? 'Mẫu slide HTML'); ?></p>
                            <a class="btn btn-sm btn-primary btn-action-custom w-100 justify-content-center" href="slide_builder.php?template=<?php echo urlencode($templateId); ?>">
                                <i class="fas fa-plus-circle"></i> Sử dụng Template
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tab-line">
        <button class="tab tab-btn active" data-tab="all" onclick="switchTab('all', this)">
            <i class="fas fa-th"></i> Tất Cả
        </button>
        <button class="tab tab-btn" data-tab="ppt" onclick="switchTab('ppt', this)">
            <i class="fas fa-file-powerpoint"></i> PowerPoint
        </button>
        <button class="tab tab-btn" data-tab="html" onclick="switchTab('html', this)">
            <i class="fas fa-code"></i> HTML Slides
        </button>
    </div>

    <!-- All Slides Tab -->
    <div id="tab-all" class="tab-content active">
        <?php if (empty($myPPTFiles) && empty($myHTMLSlides) && empty($myPresentations)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <h6>Chưa có slide nào</h6>
                <p>Hãy upload PowerPoint hoặc tạo HTML slide đầu tiên!</p>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <!-- PPT Files -->
                <?php foreach ($myPPTFiles as $ppt): ?>
                    <div class="slide-card eduvn-card">
                        <div class="slide-thumbnail" style="background: var(--grad-warning);">
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

                            <small class="d-block text-muted mb-4">
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

                <!-- HTML Slides (Old format - single slides) -->
                <?php foreach ($myHTMLSlides as $slide): ?>
                    <div class="slide-card eduvn-card">
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

                            <small class="d-block text-muted mb-4">
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

                <!-- HTML Presentations (New format - multi-slides) -->
                <?php foreach ($myPresentations as $pres): ?>
                    <div class="slide-card eduvn-card">
                        <div class="slide-thumbnail">
                            <i class="fas fa-layer-group" style="color: white; font-size: 40px;"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($pres['title']); ?></h3>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-layer-group"></i> Presentation
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-images"></i> <?php echo count($pres['slides'] ?? []); ?> slides
                                </div>
                            </div>

                            <small class="d-block text-muted mb-4">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($pres['updated_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <a href="slide_builder.php?id=<?php echo $pres['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="viewPresentation('<?php echo $pres['id']; ?>')">
                                    <i class="fas fa-play"></i> Xem
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deletePresentation('<?php echo $pres['id']; ?>')">
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
                <div class="empty-icon"><i class="fas fa-file-powerpoint"></i></div>
                <h6>Chưa có file PowerPoint</h6>
                <p>Hãy upload file PPT/PPTX đầu tiên!</p>
                <button type="button" class="btn btn-warning btn-action-custom mt-3" data-bs-toggle="modal" data-bs-target="#uploadPptModal">
                    <i class="fas fa-cloud-upload"></i> Upload PowerPoint
                </button>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <?php foreach ($myPPTFiles as $ppt): ?>
                    <div class="slide-card eduvn-card">
                        <div class="slide-thumbnail" style="background: var(--grad-warning);">
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

                            <small class="d-block text-muted mb-4">
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
        <?php if (empty($myHTMLSlides) && empty($myPresentations)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-code"></i></div>
                <h6>Chưa có HTML slide</h6>
                <p>Hãy tạo HTML slide đầu tiên từ templates có sẵn!</p>
                <a href="slide_builder.php" class="btn btn-primary btn-action-custom mt-3">
                    <i class="fas fa-code"></i> Tạo HTML Slide
                </a>
            </div>
        <?php else: ?>
            <div class="slides-grid">
                <!-- HTML Slides (Old format) -->
                <?php foreach ($myHTMLSlides as $slide): ?>
                    <div class="slide-card eduvn-card">
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

                            <small class="d-block text-muted mb-4">
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

                <!-- HTML Presentations (New format - multi-slides) -->
                <?php foreach ($myPresentations as $pres): ?>
                    <div class="slide-card eduvn-card">
                        <div class="slide-thumbnail">
                            <i class="fas fa-layer-group" style="color: white; font-size: 40px;"></i>
                        </div>
                        <div class="slide-body">
                            <h3><?php echo htmlspecialchars($pres['title']); ?></h3>
                            
                            <div class="slide-meta">
                                <div class="slide-meta-item">
                                    <i class="fas fa-layer-group"></i> Presentation
                                </div>
                                <div class="slide-meta-item">
                                    <i class="fas fa-images"></i> <?php echo count($pres['slides'] ?? []); ?> slides
                                </div>
                            </div>

                            <small class="d-block text-muted mb-4">
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($pres['updated_at'])); ?>
                            </small>

                            <div class="slide-actions">
                                <a href="slide_builder.php?id=<?php echo $pres['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="viewPresentation('<?php echo $pres['id']; ?>')">
                                    <i class="fas fa-play"></i> Xem
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deletePresentation('<?php echo $pres['id']; ?>')">
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

<!-- Upload PowerPoint Modal -->
<div class="modal fade" id="uploadPptModal" tabindex="-1" aria-labelledby="uploadPptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border: none; box-shadow: var(--shadow-lg); border-radius: var(--radius-lg);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="uploadPptModalLabel">
                    <i class="fas fa-file-powerpoint text-warning me-2"></i>Upload PowerPoint
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="import_pptx.php" enctype="multipart/form-data" id="pptUploadForm">
                <div class="modal-body pt-3">
                    <div class="upload-zone" id="uploadZone">
                        <i class="fas fa-file-powerpoint"></i>
                        <h3>Kéo thả file PPT/PPTX vào đây</h3>
                        <p>hoặc click để chọn file từ máy</p>
                        <input type="file" name="ppt_file" id="pptFileInput" accept=".ppt,.pptx" style="display: none;" required>
                        <button type="button" class="btn-upload" onclick="document.getElementById('pptFileInput').click()">
                            <i class="fas fa-folder-open me-2"></i>Chọn File
                        </button>
                        <p style="margin-top: 15px; font-size: 13px;">Giới hạn: 100MB</p>
                    </div>

                    <div id="fileInfo" class="file-info" style="display: none;">
                        <strong>File đã chọn:</strong> <span id="fileName"></span>
                        <span style="float: right; color: var(--muted);" id="fileSize"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="pptTitleInput" class="form-label fw-bold">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="pptTitleInput" class="form-control" placeholder="Nhập tiêu đề bài giảng..." required>
                        </div>
                        <div class="col-md-6">
                            <label for="pptSubjectInput" class="form-label fw-bold">Môn học</label>
                            <select name="subject_id" id="pptSubjectInput" class="form-select">
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($teacherSubjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="pptDescInput" class="form-label fw-bold">Mô tả</label>
                            <textarea name="description" id="pptDescInput" class="form-control" rows="2" placeholder="Mô tả ngắn gọn về nội dung bài giảng..."></textarea>
                        </div>
                        <div class="col-12">
                            <label for="pptTagsInput" class="form-label fw-bold">Tags (phân cách bởi dấu phẩy)</label>
                            <input type="text" name="tags" id="pptTagsInput" class="form-control" placeholder="Ví dụ: Toán, Đại số, Hình học">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-soft-slate" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning btn-action-custom">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Upload PowerPoint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PPT Viewer Modal -->
<div id="pptViewerModal" class="ppt-viewer-modal" style="display: none;">
    <div class="ppt-viewer-frame">
        <div class="ppt-viewer-header">
            <div class="ppt-viewer-title">
                <i class="fas fa-file-powerpoint me-2"></i><span id="pptViewerTitle"></span>
            </div>
            <div class="ppt-viewer-actions">
                <button onclick="presentPPT()" class="btn btn-success btn-sm">
                    <i class="fas fa-play-circle me-1"></i>Trình chiếu
                </button>
                <a id="pptDirectLink" href="#" target="_blank" download class="btn btn-outline-light btn-sm">
                    <i class="fas fa-download me-1"></i>Tải xuống
                </a>
                <button onclick="closePPTViewer()" class="btn btn-danger btn-sm">
                    <i class="fas fa-times me-1"></i>Đóng
                </button>
            </div>
        </div>
        <iframe id="pptViewerIframe" class="ppt-viewer-iframe"></iframe>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function swalToast(icon, title) {
    if (typeof Swal === 'undefined') { alert(title); return; }
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true
    });
}

function swalConfirm(title, text, icon, confirmColor) {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon || 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy',
        confirmButtonColor: confirmColor || '#EF4444',
        reverseButtons: true
    }).then(result => result.isConfirmed);
}

function switchTab(tab, btn) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    (btn || document.querySelector('[data-tab="' + tab + '"]')).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
}

function viewPPT(filename, title) {
    // Auto-detect base path (works for both /cvd2/ and /cvdlms/)
    const basePath = window.location.pathname.split('/teacher/')[0];
    const fileUrl = window.location.origin + basePath + '/uploads/ppt_files/' + filename;
    
    // Store URL globally for presentation mode
    currentPPTUrl = fileUrl;
    
    // Check if running on localhost
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname.includes('192.168');
    
    if (isLocalhost) {
        // For localhost: Download file
        alert('⚠️ Online viewer không hoạt động với localhost!\n\nFile sẽ được tải xuống để bạn mở bằng PowerPoint.');
        window.open(fileUrl, '_blank');
        return;
    }
    
    // Use Microsoft Office Online Viewer (embed mode for preview)
    const viewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
    
    document.getElementById('pptViewerTitle').textContent = title;
    const iframe = document.getElementById('pptViewerIframe');
    iframe.src = viewerUrl;
    
    // Add error handling - if viewer fails, offer direct download
    iframe.onerror = function() {
        if (confirm('Không thể tải viewer. Bạn có muốn tải file xuống không?')) {
            window.open(fileUrl, '_blank');
            closePPTViewer();
        }
    };
    
    document.getElementById('pptViewerModal').style.display = 'block';
    
    // Add direct link in modal footer
    document.getElementById('pptDirectLink').href = fileUrl;
}

function closePPTViewer() {
    document.getElementById('pptViewerModal').style.display = 'none';
    document.getElementById('pptViewerIframe').src = '';
}

// Global variable to store current file URL for presentation
let currentPPTUrl = '';

function presentPPT() {
    if (currentPPTUrl) {
        // Open in new tab with Office Online in view mode (allows slideshow)
        const presentUrl = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(currentPPTUrl)}`;
        window.open(presentUrl, '_blank', 'fullscreen=yes');
    }
}

function deletePPT(fileId) {
    swalConfirm('Xóa file PowerPoint?', 'File sẽ bị xóa vĩnh viễn khỏi hệ thống.', 'warning').then(confirmed => {
        if (!confirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'import_pptx.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="file_id" value="${fileId}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

function filterTemplateCards(category, button) {
    document.querySelectorAll('.template-filter').forEach(btn => btn.classList.remove('active'));
    if (button) {
        button.classList.add('active');
    }

    document.querySelectorAll('.template-card').forEach(card => {
        const cardCategory = card.getAttribute('data-template-category');
        card.style.display = (category === 'all' || cardCategory === category) ? '' : 'none';
    });
}

function viewHTMLSlide(slideId) {
    window.open('../uploads/html_slides/' + slideId + '.html', '_blank');
}

function deleteHTMLSlide(slideId) {
    swalConfirm('Xóa HTML slide?', 'Slide sẽ bị xóa vĩnh viễn.', 'warning').then(confirmed => {
        if (!confirmed) return;

        fetch('api/delete_html_slide.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({slide_id: slideId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                swalToast('success', 'Đã xóa HTML slide thành công!');
                setTimeout(() => location.reload(), 800);
            } else {
                swalToast('error', 'Lỗi: ' + data.message);
            }
        });
    });
}

function viewPresentation(presentationId) {
    // Open presentation viewer in fullscreen mode
    window.open('slide_present.php?id=' + presentationId, '_blank');
}

function deletePresentation(presentationId) {
    swalConfirm('Xóa presentation?', 'Tất cả slides trong presentation sẽ bị xóa!', 'warning').then(confirmed => {
        if (!confirmed) return;

        fetch('api/delete_presentation.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({presentation_id: presentationId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                swalToast('success', 'Đã xóa presentation thành công!');
                setTimeout(() => location.reload(), 800);
            } else {
                swalToast('error', 'Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            swalToast('error', 'Có lỗi xảy ra khi xóa presentation!');
        });
    });
}

// ESC to close viewer
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closePPTViewer();
    }
});

// Upload modal - file handling
const pptFileInput = document.getElementById('pptFileInput');
const uploadZone = document.getElementById('uploadZone');

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function handlePPTFile(files) {
    if (!files || !files.length) return;
    const file = files[0];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['ppt', 'pptx'].includes(ext)) {
        swalToast('error', 'Chỉ chấp nhận file .ppt hoặc .pptx');
        pptFileInput.value = '';
        return;
    }
    if (file.size > 100 * 1024 * 1024) {
        swalToast('error', 'File quá lớn. Giới hạn 100MB.');
        pptFileInput.value = '';
        return;
    }
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatBytes(file.size);
    document.getElementById('fileInfo').style.display = 'block';
}

pptFileInput.addEventListener('change', function() {
    handlePPTFile(this.files);
});

uploadZone.addEventListener('click', function(e) {
    if (!e.target.closest('button')) {
        pptFileInput.click();
    }
});

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
        pptFileInput.files = e.dataTransfer.files;
        handlePPTFile(e.dataTransfer.files);
    }
});

// Reset form + file info when modal closes
document.getElementById('uploadPptModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('pptUploadForm').reset();
    document.getElementById('fileInfo').style.display = 'none';
});

// Auto-dismiss flash alert
setTimeout(function() {
    const flash = document.querySelector('.ppt-flash');
    if (flash) flash.remove();
}, 5000);
</script>

<?php include '../includes/teacher_footer.php'; ?>
