<?php
/**
 * HTML Slide Builder - Code Editor Only (No WYSIWYG)
 * Edit HTML/CSS/JS directly with syntax highlighting
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

// Load HTML presentations metadata (multi-slide support)
$metadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
$presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Load presentation if editing
$presentationId = $_GET['id'] ?? '';
$currentPresentation = null;

if ($presentationId && isset($presentations[$presentationId]) && $presentations[$presentationId]['teacher_username'] === $username) {
    $currentPresentation = $presentations[$presentationId];
    
    // Load slide contents from files
    if (isset($currentPresentation['slides'])) {
        foreach ($currentPresentation['slides'] as &$slide) {
            if (isset($slide['file_path'])) {
                $filePath = __DIR__ . '/../' . $slide['file_path'];
                if (file_exists($filePath)) {
                    $slide['content'] = file_get_contents($filePath);
                } else {
                    // If file doesn't exist, create default content
                    $slide['content'] = '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($slide['title'] ?? 'Slide') . '</title>
    <style>
        body { margin: 0; padding: 40px; font-family: Arial, sans-serif; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($slide['title'] ?? 'Slide') . '</h1>
</body>
</html>';
                }
            }
        }
        unset($slide); // Break reference
    }
}

// Create new presentation if none exists
if (!$presentationId) {
    $presentationId = 'pres_' . time() . '_' . uniqid();
    $currentPresentation = [
        'id' => $presentationId,
        'title' => 'Presentation Mới',
        'teacher_username' => $username,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'slides' => []
    ];
}

$title = $currentPresentation ? 'Chỉnh sửa - ' . $currentPresentation['title'] : 'Tạo Presentation Mới - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #1e1e1e;
        color: #d4d4d4;
    }

    .builder-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .builder-header {
        background: #252526;
        border-bottom: 1px solid #3e3e42;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .builder-title {
        color: #cccccc;
        font-size: 18px;
        font-weight: 600;
    }

    .builder-title input {
        background: transparent;
        border: none;
        color: white;
        font-size: 18px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .builder-title input:focus {
        outline: none;
        background: #3e3e42;
    }

    .builder-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary {
        background: #0e639c;
        color: white;
    }

    .btn-primary:hover {
        background: #1177bb;
    }

    .btn-success {
        background: #16825d;
        color: white;
    }

    .btn-success:hover {
        background: #1a9870;
    }

    .btn-secondary {
        background: #464647;
        color: #cccccc;
    }

    .btn-secondary:hover {
        background: #5a5a5a;
    }

    .builder-toolbar {
        background: #2d2d30;
        border-bottom: 1px solid #3e3e42;
        padding: 10px 20px;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .builder-main {
        flex: 1;
        display: flex;
        overflow: hidden;
    }

    .slides-sidebar {
        width: 250px;
        background: #252526;
        border-right: 1px solid #3e3e42;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    .slides-header {
        padding: 15px;
        border-bottom: 1px solid #3e3e42;
    }

    .slides-header h3 {
        color: white;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .add-slide-btn {
        width: 100%;
        padding: 8px;
        background: #0e639c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .add-slide-btn:hover {
        background: #1177bb;
    }

    .slides-list {
        flex: 1;
        padding: 10px;
    }

    .slide-item {
        background: #2d2d30;
        border: 2px solid #3e3e42;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .slide-item:hover {
        background: #37373d;
    }

    .slide-item.active {
        border-color: #007acc;
        background: #37373d;
    }

    .slide-item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 8px;
    }

    .slide-number {
        background: #007acc;
        color: white;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
    }

    .slide-delete {
        background: #d32f2f;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
    }

    .slide-delete:hover {
        background: #f44336;
    }

    .slide-title {
        color: #cccccc;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .slide-template {
        color: #969696;
        font-size: 11px;
    }

    .editor-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #3e3e42;
    }

    .editor-tabs {
        background: #2d2d30;
        display: flex;
        border-bottom: 1px solid #3e3e42;
    }

    .editor-tab {
        padding: 10px 20px;
        background: transparent;
        border: none;
        color: #969696;
        cursor: pointer;
        font-size: 13px;
        border-bottom: 2px solid transparent;
    }

    .editor-tab.active {
        color: white;
        border-bottom-color: #007acc;
    }

    .editor-tab:hover {
        background: #37373d;
    }

    .editor-content {
        flex: 1;
        overflow: hidden;
    }

    .CodeMirror {
        height: 100% !important;
        font-size: 14px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    .preview-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
    }

    .preview-toolbar {
        background: #f3f3f3;
        padding: 10px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .preview-iframe {
        flex: 1;
        border: none;
        background: white;
    }

    .template-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        z-index: 9999;
        overflow-y: auto;
        padding: 40px 20px;
    }

    .template-modal-content {
        max-width: 1200px;
        margin: 0 auto;
        background: #252526;
        border-radius: 8px;
        padding: 30px;
    }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .template-card {
        background: #3e3e42;
        border: 2px solid #3e3e42;
        border-radius: 8px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .template-card:hover {
        border-color: #007acc;
        transform: translateY(-4px);
    }

    .template-preview {
        background: #2d2d30;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 15px;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
    }

    .template-card h3 {
        color: white;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .template-card p {
        color: #969696;
        font-size: 13px;
        margin: 0;
    }

    @media (max-width: 1024px) {
        .builder-main {
            flex-direction: column;
        }
        
        .slides-sidebar {
            width: 100%;
            max-height: 200px;
            border-right: none;
            border-bottom: 1px solid #3e3e42;
        }

        .slides-list {
            display: flex;
            overflow-x: auto;
            padding: 10px;
        }

        .slide-item {
            min-width: 180px;
            margin-right: 10px;
            margin-bottom: 0;
        }
        
        .editor-panel, .preview-panel {
            border-right: none;
            border-bottom: 1px solid #3e3e42;
        }
    }
</style>

<div class="builder-container">
    <!-- Header -->
    <div class="builder-header">
        <div class="builder-title">
            <i class="fas fa-presentation me-2"></i>
            <input type="text" id="presentationTitle" value="<?php echo htmlspecialchars($currentPresentation['title'] ?? 'Presentation Mới'); ?>" placeholder="Nhập tiêu đề presentation...">
        </div>
        <div class="builder-actions">
            <button class="btn btn-success" onclick="savePresentation()">
                <i class="fas fa-save"></i> Lưu Presentation
            </button>
            <button class="btn btn-secondary" onclick="exportPresentation()">
                <i class="fas fa-download"></i> Export
            </button>
            <a href="slides.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Đóng
            </a>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="builder-toolbar">
        <button class="btn btn-secondary" onclick="selectTemplateForCurrentSlide()">
            <i class="fas fa-palette"></i> Chọn Template
        </button>
        <button class="btn btn-secondary" onclick="formatCode()">
            <i class="fas fa-indent"></i> Format Code
        </button>
        <button class="btn btn-secondary" onclick="updatePreview()">
            <i class="fas fa-sync"></i> Refresh Preview
        </button>
        <button class="btn btn-secondary" onclick="toggleFullscreen()">
            <i class="fas fa-expand"></i> Fullscreen
        </button>
        <div style="flex: 1;"></div>
        <span id="currentSlideInfo" style="color: #969696; font-size: 13px;">Chọn hoặc tạo slide mới</span>
    </div>

    <!-- Main Content -->
    <div class="builder-main">
        <!-- Slides Sidebar -->
        <div class="slides-sidebar">
            <div class="slides-header">
                <h3><i class="fas fa-layer-group me-2"></i>Slides</h3>
                <button class="add-slide-btn" onclick="addNewSlide()">
                    <i class="fas fa-plus"></i> Thêm Slide Mới
                </button>
            </div>
            <div class="slides-list" id="slidesList">
                <!-- Slides will be rendered here -->
            </div>
        </div>

        <!-- Editor Panel -->
        <div class="editor-panel">
            <div class="editor-tabs">
                <button class="editor-tab active" data-mode="html" onclick="switchEditorMode('html')">
                    <i class="fab fa-html5"></i> HTML
                </button>
            </div>
            <div class="editor-content">
                <textarea id="htmlEditor"></textarea>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="preview-panel">
            <div class="preview-toolbar">
                <strong style="color: #333;"><i class="fas fa-eye me-2"></i>Live Preview</strong>
                <div style="flex: 1;"></div>
                <button class="btn btn-secondary" onclick="openInNewTab()">
                    <i class="fas fa-external-link-alt"></i> Mở tab mới
                </button>
            </div>
            <iframe id="previewFrame" class="preview-iframe"></iframe>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div id="templateModal" class="template-modal">
    <div class="template-modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="color: white;"><i class="fas fa-palette me-2"></i>Chọn Template</h2>
            <button class="btn btn-secondary" onclick="closeTemplateModal()">
                <i class="fas fa-times"></i> Đóng
            </button>
        </div>
        <div class="template-grid" id="templateGrid"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.14.0/beautify-html.min.js"></script>

<script>
const PRESENTATION_ID = '<?php echo $presentationId; ?>';
const USERNAME = '<?php echo $username; ?>';

let htmlEditor;
let currentSlideIndex = -1;
let presentationData = <?php echo json_encode($currentPresentation); ?>;

// Initialize CodeMirror
htmlEditor = CodeMirror.fromTextArea(document.getElementById('htmlEditor'), {
    mode: 'htmlmixed',
    theme: 'monokai',
    lineNumbers: true,
    autoCloseTags: true,
    autoCloseBrackets: true,
    matchBrackets: true,
    indentUnit: 2,
    tabSize: 2,
    lineWrapping: true,
    extraKeys: {
        'Ctrl-S': savePresentation,
        'Cmd-S': savePresentation,
        'F11': toggleFullscreen
    }
});

// Initialize
if (!presentationData.slides || presentationData.slides.length === 0) {
    // Create first slide
    addNewSlide();
} else {
    renderSlidesList();
    selectSlide(0);
}

// Update preview on change
htmlEditor.on('change', debounce(() => {
    if (currentSlideIndex >= 0) {
        presentationData.slides[currentSlideIndex].content = htmlEditor.getValue();
        updatePreview();
    }
}, 500));

function renderSlidesList() {
    const list = document.getElementById('slidesList');
    list.innerHTML = presentationData.slides.map((slide, index) => `
        <div class="slide-item ${index === currentSlideIndex ? 'active' : ''}" onclick="selectSlide(${index})">
            <div class="slide-item-header">
                <span class="slide-number">Slide ${index + 1}</span>
                ${presentationData.slides.length > 1 ? `
                    <button class="slide-delete" onclick="deleteSlide(${index}); event.stopPropagation();">
                        <i class="fas fa-trash"></i>
                    </button>
                ` : ''}
            </div>
            <div class="slide-title">${slide.title || 'Untitled'}</div>
            <div class="slide-template">📄 ${slide.template || 'Custom'}</div>
        </div>
    `).join('');
}

function selectSlide(index) {
    if (index < 0 || index >= presentationData.slides.length) return;
    
    // Save current slide content before switching
    if (currentSlideIndex >= 0) {
        presentationData.slides[currentSlideIndex].content = htmlEditor.getValue();
    }
    
    currentSlideIndex = index;
    const slide = presentationData.slides[index];
    
    htmlEditor.setValue(slide.content || '');
    updatePreview();
    renderSlidesList();
    
    document.getElementById('currentSlideInfo').textContent = `Slide ${index + 1} / ${presentationData.slides.length}`;
}

function addNewSlide() {
    const newSlide = {
        id: 'slide_' + Date.now(),
        title: 'Slide ' + (presentationData.slides.length + 1),
        template: 'blank',
        content: `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slide ${presentationData.slides.length + 1}</title>
    <style>
        body {
            margin: 0;
            padding: 40px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
        }
        h1 {
            color: #333;
            font-size: 48px;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            font-size: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Slide ${presentationData.slides.length + 1}</h1>
        <p>Nhập nội dung của bạn tại đây...</p>
    </div>
</body>
</html>`,
        created_at: new Date().toISOString()
    };
    
    presentationData.slides.push(newSlide);
    renderSlidesList();
    selectSlide(presentationData.slides.length - 1);
}

function deleteSlide(index) {
    if (presentationData.slides.length <= 1) {
        alert('Không thể xóa slide cuối cùng!');
        return;
    }
    
    if (!confirm('Bạn có chắc muốn xóa slide này?')) {
        return;
    }
    
    presentationData.slides.splice(index, 1);
    
    if (currentSlideIndex >= presentationData.slides.length) {
        currentSlideIndex = presentationData.slides.length - 1;
    }
    
    renderSlidesList();
    selectSlide(currentSlideIndex);
}

function selectTemplateForCurrentSlide() {
    if (currentSlideIndex < 0) {
        alert('Vui lòng chọn một slide trước!');
        return;
    }
    loadTemplateList();
    document.getElementById('templateModal').style.display = 'block';
}

function updatePreview() {
    const html = htmlEditor.getValue();
    const iframe = document.getElementById('previewFrame');
    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
    
    iframeDoc.open();
    iframeDoc.write(html);
    iframeDoc.close();
}

function formatCode() {
    const formatted = html_beautify(htmlEditor.getValue(), {
        indent_size: 2,
        wrap_line_length: 80,
        preserve_newlines: true
    });
    htmlEditor.setValue(formatted);
}

function savePresentation() {
    const title = document.getElementById('presentationTitle').value.trim();
    
    if (!title) {
        alert('Vui lòng nhập tiêu đề presentation!');
        return;
    }
    
    if (presentationData.slides.length === 0) {
        alert('Presentation phải có ít nhất 1 slide!');
        return;
    }
    
    // Save current slide content
    if (currentSlideIndex >= 0) {
        presentationData.slides[currentSlideIndex].content = htmlEditor.getValue();
    }
    
    presentationData.title = title;
    presentationData.updated_at = new Date().toISOString();
    
    const formData = new FormData();
    formData.append('action', 'save_html_presentation');
    formData.append('presentation_id', PRESENTATION_ID);
    formData.append('data', JSON.stringify(presentationData));
    
    fetch('api/save_html_presentation.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Đã lưu presentation thành công!');
        } else {
            alert('✗ Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi xảy ra khi lưu presentation!');
    });
    
    return false;
}

function exportPresentation() {
    alert('Tính năng export đang được phát triển!');
}

function openInNewTab() {
    const html = htmlEditor.getValue();
    const newWindow = window.open();
    newWindow.document.write(html);
    newWindow.document.close();
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
}

function switchEditorMode(mode) {
    // For future expansion
}

function closeTemplateModal() {
    document.getElementById('templateModal').style.display = 'none';
}

function loadTemplateList() {
    fetch('api/get_templates.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderTemplates(data.templates);
            }
        })
        .catch(() => {
            // Fallback templates if API fails
            renderTemplates([
                { id: 'blank', name: 'Blank', description: 'Slide trống', icon: '📄' },
                { id: 'title', name: 'Title Slide', description: 'Slide tiêu đề', icon: '🎯' },
                { id: 'content', name: 'Content', description: 'Slide nội dung', icon: '📝' },
                { id: 'two-column', name: 'Two Columns', description: '2 cột', icon: '📊' },
                { id: 'image-text', name: 'Image + Text', description: 'Hình ảnh + Văn bản', icon: '🖼️' }
            ]);
        });
}

function renderTemplates(templates) {
    const grid = document.getElementById('templateGrid');
    grid.innerHTML = templates.map(t => `
        <div class="template-card" onclick="applyTemplate('${t.id}'); closeTemplateModal();">
            <div class="template-preview">${t.icon || '📄'}</div>
            <h3>${t.name}</h3>
            <p>${t.description}</p>
        </div>
    `).join('');
}

function applyTemplate(templateId) {
    if (currentSlideIndex < 0) return;
    
    fetch(`api/get_template.php?id=${templateId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                presentationData.slides[currentSlideIndex].template = templateId;
                presentationData.slides[currentSlideIndex].content = data.content;
                htmlEditor.setValue(data.content);
                updatePreview();
                renderSlidesList();
            }
        })
        .catch(() => {
            // Fallback template
            const templates = getBuiltInTemplates();
            if (templates[templateId]) {
                presentationData.slides[currentSlideIndex].template = templateId;
                presentationData.slides[currentSlideIndex].content = templates[templateId];
                htmlEditor.setValue(templates[templateId]);
                updatePreview();
                renderSlidesList();
            }
        });
}

function getBuiltInTemplates() {
    return {
        'blank': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Slide</title>
    <style>
        body { margin: 0; padding: 40px; font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 60px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Nội dung của bạn</h1>
    </div>
</body>
</html>`,
        'title': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Title Slide</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .title-container {
            text-align: center;
        }
        h1 {
            font-size: 72px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        p {
            font-size: 32px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="title-container">
        <h1>Tiêu Đề Chính</h1>
        <p>Tiêu đề phụ hoặc mô tả</p>
    </div>
</body>
</html>`,
        'content': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Slide</title>
    <style>
        body {
            margin: 0;
            padding: 60px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            font-size: 42px;
            margin-bottom: 30px;
        }
        ul {
            font-size: 24px;
            line-height: 2;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Nội Dung Chính</h2>
        <ul>
            <li>Điểm thứ nhất</li>
            <li>Điểm thứ hai</li>
            <li>Điểm thứ ba</li>
        </ul>
    </div>
</body>
</html>`,
        'two-column': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two Column Slide</title>
    <style>
        body {
            margin: 0;
            padding: 60px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            font-size: 42px;
            margin-bottom: 30px;
            text-align: center;
        }
        .columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .column {
            padding: 20px;
        }
        h3 {
            color: #555;
            font-size: 28px;
            margin-bottom: 15px;
        }
        p {
            font-size: 20px;
            line-height: 1.6;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tiêu Đề Chính</h2>
        <div class="columns">
            <div class="column">
                <h3>Cột Trái</h3>
                <p>Nội dung cột trái...</p>
            </div>
            <div class="column">
                <h3>Cột Phải</h3>
                <p>Nội dung cột phải...</p>
            </div>
        </div>
    </div>
</body>
</html>`,
        'image-text': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image + Text Slide</title>
    <style>
        body {
            margin: 0;
            padding: 60px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        .image-section {
            background: #ddd;
            height: 400px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        .text-section h2 {
            color: #333;
            font-size: 42px;
            margin-bottom: 20px;
        }
        .text-section p {
            font-size: 20px;
            line-height: 1.6;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-section">
            🖼️
        </div>
        <div class="text-section">
            <h2>Tiêu Đề</h2>
            <p>Mô tả nội dung bên cạnh hình ảnh...</p>
        </div>
    </div>
</body>
</html>`,
        'quote': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Slide</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .quote-container {
            max-width: 800px;
            text-align: center;
            padding: 40px;
        }
        .quote-mark {
            font-size: 120px;
            opacity: 0.3;
            line-height: 0.5;
        }
        blockquote {
            font-size: 36px;
            font-style: italic;
            margin: 40px 0;
            line-height: 1.6;
        }
        .author {
            font-size: 24px;
            margin-top: 30px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="quote-container">
        <div class="quote-mark">"</div>
        <blockquote>
            Giáo dục là vũ khí mạnh mẽ nhất mà bạn có thể sử dụng để thay đổi thế giới.
        </blockquote>
        <div class="author">— Nelson Mandela</div>
    </div>
</body>
</html>`,
        'thank-you': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .thank-you-container {
            text-align: center;
        }
        h1 {
            font-size: 80px;
            margin-bottom: 30px;
            font-weight: bold;
        }
        p {
            font-size: 32px;
            opacity: 0.9;
        }
        .icon {
            font-size: 100px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="icon">🙏</div>
        <h1>Cảm ơn!</h1>
        <p>Cảm ơn các bạn đã theo dõi</p>
    </div>
</body>
</html>`,
        'section': `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Break</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .section-container {
            text-align: center;
        }
        .section-number {
            font-size: 120px;
            font-weight: bold;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 64px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        p {
            font-size: 28px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="section-container">
        <div class="section-number">01</div>
        <h1>Phần Tiếp Theo</h1>
        <p>Mô tả ngắn về phần này</p>
    </div>
</body>
</html>`
    };
}

// Utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ESC to close modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeTemplateModal();
    }
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
