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

// Load HTML slides metadata
$metadataFile = __DIR__ . '/../data/html_slides_metadata.json';
$htmlSlides = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Load slide if editing
$slideId = $_GET['id'] ?? '';
$currentSlide = null;

if ($slideId && isset($htmlSlides[$slideId]) && $htmlSlides[$slideId]['teacher_username'] === $username) {
    $currentSlide = $htmlSlides[$slideId];
}

// Load template if creating from template
$templateId = $_GET['template'] ?? '';
$templateContent = '';

if ($templateId && !$currentSlide) {
    // Will be loaded by JavaScript
}

$title = $currentSlide ? 'Chỉnh sửa Slide - ' . $currentSlide['title'] : 'Tạo Slide Mới - CVD';
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
            <i class="fas fa-code me-2"></i>
            <input type="text" id="slideTitle" value="<?php echo htmlspecialchars($currentSlide['title'] ?? 'Slide Mới'); ?>" placeholder="Nhập tiêu đề slide...">
        </div>
        <div class="builder-actions">
            <button class="btn btn-secondary" onclick="selectTemplate()">
                <i class="fas fa-palette"></i> Templates
            </button>
            <button class="btn btn-success" onclick="saveSlide()">
                <i class="fas fa-save"></i> Lưu Slide
            </button>
            <a href="slides.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Đóng
            </a>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="builder-toolbar">
        <button class="btn btn-secondary" onclick="formatCode()">
            <i class="fas fa-indent"></i> Format Code
        </button>
        <button class="btn btn-secondary" onclick="updatePreview()">
            <i class="fas fa-sync"></i> Refresh Preview
        </button>
        <button class="btn btn-secondary" onclick="toggleFullscreen()">
            <i class="fas fa-expand"></i> Fullscreen
        </button>
    </div>

    <!-- Main Content -->
    <div class="builder-main">
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
const SLIDE_ID = '<?php echo $slideId; ?>';
const TEMPLATE_ID = '<?php echo $templateId; ?>';
const USERNAME = '<?php echo $username; ?>';

let htmlEditor;

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
        'Ctrl-S': saveSlide,
        'Cmd-S': saveSlide,
        'F11': toggleFullscreen
    }
});

// Load content
<?php if ($currentSlide): ?>
    // Load existing slide
    fetch('../<?php echo $currentSlide['file_path']; ?>')
        .then(r => r.text())
        .then(html => {
            htmlEditor.setValue(html);
            updatePreview();
        });
<?php elseif ($templateId): ?>
    // Load from template
    loadTemplate(TEMPLATE_ID);
<?php else: ?>
    // Start with blank template
    loadTemplate('blank');
<?php endif; ?>

// Update preview on change
htmlEditor.on('change', debounce(updatePreview, 500));

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

function saveSlide() {
    const title = document.getElementById('slideTitle').value.trim();
    const htmlContent = htmlEditor.getValue();
    
    if (!title) {
        alert('Vui lòng nhập tiêu đề slide!');
        return;
    }
    
    if (!htmlContent.trim()) {
        alert('Nội dung slide không được trống!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_html_slide');
    formData.append('slide_id', SLIDE_ID);
    formData.append('title', title);
    formData.append('content', htmlContent);
    
    fetch('api/save_html_slide.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Đã lưu slide thành công!');
            if (!SLIDE_ID) {
                window.location.href = 'slide_builder.php?id=' + data.slide_id;
            }
        } else {
            alert('✗ Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi xảy ra khi lưu slide!');
    });
    
    return false; // Prevent Ctrl-S default
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
    // For future expansion (CSS/JS tabs)
}

function selectTemplate() {
    loadTemplateList();
    document.getElementById('templateModal').style.display = 'block';
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
        });
}

function renderTemplates(templates) {
    const grid = document.getElementById('templateGrid');
    grid.innerHTML = templates.map(t => `
        <div class="template-card" onclick="loadTemplate('${t.id}'); closeTemplateModal();">
            <div class="template-preview">${t.icon || '📄'}</div>
            <h3>${t.name}</h3>
            <p>${t.description}</p>
        </div>
    `).join('');
}

function loadTemplate(templateId) {
    fetch(`api/get_template.php?id=${templateId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                htmlEditor.setValue(data.content);
                updatePreview();
            }
        });
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
