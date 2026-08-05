<?php
/**
 * Slide Viewer/Fixer - View and Fix Mode Only
 * Simplified editor for viewing presentations and fixing broken images/videos
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$presentationId = $_GET['id'] ?? '';

// Load presentation using new storage
require_once __DIR__ . '/../includes/PresentationStorage.php';
$storage = new PresentationStorage();

$presentation = null;
if ($presentationId) {
    $presentation = $storage->getById($presentationId);
    
    // Security check: Only owner can view
    if (!$presentation || $presentation['teacher_username'] !== $username) {
        die('Không có quyền truy cập bài giảng này');
    }
}

if (!$presentation) {
    die('Không tìm thấy bài giảng');
}

$title = 'Xem Bài Giảng - ' . ($presentation['title'] ?? 'CVD');
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="../styles/slide-system.css">

<style>
/* Simplified Viewer Styles */
.viewer-container {
    display: flex;
    height: calc(100vh - var(--topbar-h));
    background: var(--page-bg);
}

.viewer-sidebar {
    width: 250px;
    background: var(--surface);
    border-right: 1px solid var(--border-soft);
    overflow-y: auto;
    padding: 1rem;
}

.viewer-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.viewer-toolbar {
    background: var(--surface);
    padding: 1rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.viewer-canvas-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    overflow: auto;
}

.viewer-canvas {
    width: 960px;
    height: 540px;
    background: white;
    box-shadow: var(--shadow-md);
    border-radius: var(--radius);
    position: relative;
    transform-origin: center;
}

.slide-thumb {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border: 2px solid transparent;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s;
}

.slide-thumb:hover {
    background: var(--accent-light);
    border-color: var(--accent);
}

.slide-thumb.active {
    background: var(--accent-light);
    border-color: var(--accent);
}

.slide-thumb-preview {
    width: 100%;
    aspect-ratio: 16/9;
    background: var(--border-soft);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: var(--muted);
}

.element-warning {
    position: absolute;
    top: 5px;
    right: 5px;
    background: var(--warning);
    color: #fff;
    padding: 4px 10px;
    font-size: 11px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    border: none;
}

.element-warning:hover {
    background: #D97706;
}

.fix-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, .6);
    backdrop-filter: blur(2px);
}

.fix-modal-content {
    background: var(--surface);
    margin: 8% auto;
    padding: 2rem;
    border-radius: var(--radius-lg);
    width: 500px;
    max-width: 92%;
    box-shadow: var(--shadow-lg);
}

.source-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.source-badge.pptx {
    background: var(--info-light);
    color: #0369A1;
}

.source-badge.template {
    background: var(--violet-light);
    color: #6D28D9;
}
</style>

<div class="viewer-container">
    <!-- Sidebar với slide thumbnails -->
    <div class="viewer-sidebar">
        <div class="section-header">
            <div class="sh-icon alt"><i class="bi bi-collection"></i></div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="mb-0">Slides</h3>
                <span class="badge badge-soft-slate"><?php echo count($presentation['slides']); ?></span>
            </div>
        </div>
        
        <div id="slideThumbs">
            <!-- Populated by JavaScript -->
        </div>
    </div>
    
    <!-- Main viewer -->
    <div class="viewer-main">
        <!-- Toolbar -->
        <div class="viewer-toolbar">
            <div class="section-header mb-0">
                <div class="sh-icon"><i class="bi bi-easel-fill"></i></div>
                <div>
                    <h3 class="mb-0 d-flex align-items-center flex-wrap gap-2">
                        <?php echo htmlspecialchars($presentation['title']); ?>
                        <?php if (isset($presentation['source'])): ?>
                            <span class="source-badge <?php echo $presentation['source']; ?>">
                                <?php echo $presentation['source'] === 'pptx' ? '📤 PowerPoint' : '🎨 Template'; ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p class="mb-0">
                        <i class="bi bi-info-circle"></i> View/Fix Mode - Click element để sửa
                    </p>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="setZoom(0.5)">50%</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="setZoom(0.75)">75%</button>
                <button class="btn btn-sm btn-outline-primary" onclick="setZoom(1)">100%</button>
                
                <div style="border-left: 1px solid var(--border-soft); margin: 0 0.5rem;"></div>
                
                <?php if (isset($presentation['source']) && $presentation['source'] === 'pptx'): ?>
                <a href="api/download_pptx.php?id=<?php echo $presentationId; ?>" 
                   class="btn btn-sm btn-info" 
                   title="Tải file PowerPoint gốc">
                    <i class="bi bi-file-earmark-ppt"></i> Tải PPT Gốc
                </a>
                <?php endif; ?>
                
                <button class="btn btn-sm btn-success" onclick="savePresentation()">
                    <i class="bi bi-save"></i> Lưu
                </button>
                <a href="slide_presenter.php?id=<?php echo $presentationId; ?>" 
                   class="btn btn-sm btn-primary" 
                   target="_blank">
                    <i class="bi bi-play-fill"></i> Trình Chiếu
                </a>
                <a href="slides.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-circle"></i> Đóng
                </a>
            </div>
        </div>
        
        <!-- Canvas -->
        <div class="viewer-canvas-wrapper">
            <div class="viewer-canvas" id="slideCanvas">
                <!-- Current slide rendered here -->
            </div>
        </div>
    </div>
</div>

<!-- Fix Element Modal -->
<div id="fixModal" class="fix-modal">
    <div class="fix-modal-content">
        <h4 style="margin-bottom: 1rem;">
            <i class="bi bi-tools"></i> Sửa Element
        </h4>
        
        <div id="fixContent">
            <!-- Populated dynamically -->
        </div>
        
        <div class="d-flex gap-2 justify-content-end mt-3">
            <button class="btn btn-secondary" onclick="closeFix()">Hủy</button>
            <button class="btn btn-primary" onclick="saveFix()">Lưu</button>
        </div>
    </div>
</div>

<script>
// Global state
let presentationData = <?php echo json_encode($presentation); ?>;
let currentSlideIndex = 0;
let currentZoom = 1;
let fixingElementIndex = -1;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderSlideThumbs();
    renderCurrentSlide();
});

function setZoom(scale) {
    currentZoom = scale;
    document.getElementById('slideCanvas').style.transform = `scale(${scale})`;
}

function renderSlideThumbs() {
    const container = document.getElementById('slideThumbs');
    container.innerHTML = '';
    
    presentationData.slides.forEach((slide, index) => {
        const div = document.createElement('div');
        div.className = 'slide-thumb' + (index === currentSlideIndex ? ' active' : '');
        div.onclick = () => selectSlide(index);
        
        const warnings = slide.elements?.filter(e => e.missing || (!e.content && (e.type === 'image' || e.type === 'video'))).length || 0;
        
        div.innerHTML = `
            <div class="slide-thumb-preview" style="background: ${slide.background || '#fff'}">
                <strong>${index + 1}</strong>
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #666; display: flex; justify-content: space-between;">
                <span>${slide.elements?.length || 0} elements</span>
                ${warnings > 0 ? `<span style="color: #ffc107;">⚠️ ${warnings}</span>` : ''}
            </div>
        `;
        
        container.appendChild(div);
    });
}

function renderCurrentSlide() {
    const canvas = document.getElementById('slideCanvas');
    const slide = presentationData.slides[currentSlideIndex];
    
    canvas.style.background = slide.background || '#ffffff';
    canvas.innerHTML = '';
    
    slide.elements.forEach((element, index) => {
        const elem = createElementDOM(element, index);
        canvas.appendChild(elem);
    });
}

function createElementDOM(element, index) {
    const elem = document.createElement('div');
    elem.className = 'slide-element';
    elem.style.position = 'absolute';
    elem.style.left = (element.position?.x || 50) + 'px';
    elem.style.top = (element.position?.y || 50) + 'px';
    
    if (element.size) {
        elem.style.width = element.size.width || 'auto';
        elem.style.height = element.size.height || 'auto';
    }
    
    if (element.style) {
        Object.assign(elem.style, element.style);
    }
    
    // Render content based on type
    switch (element.type) {
        case 'heading':
        case 'text':
            elem.innerHTML = `<div style="padding: 10px; cursor: pointer;">${element.content || 'Text'}</div>`;
            elem.onclick = () => fixElement(index);
            break;
            
        case 'table':
            // Render table preview
            if (element.tableData && element.tableData.length > 0) {
                let tableHTML = '<table style="width:100%; height:100%; border-collapse: collapse; font-size: 14px; cursor: pointer;">';
                element.tableData.forEach((row, idx) => {
                    tableHTML += '<tr>';
                    row.forEach(cell => {
                        const tag = idx === 0 ? 'th' : 'td';
                        tableHTML += `<${tag} style="border: 1px solid #999; padding: 5px; text-align: left;">${cell || ''}</${tag}>`;
                    });
                    tableHTML += '</tr>';
                });
                tableHTML += '</table>';
                elem.innerHTML = tableHTML;
                elem.onclick = () => fixElement(index);
            }
            break;
            
        case 'image':
            if (element.content && !element.missing) {
                const imgPath = element.content.startsWith('uploads/') ? '../' + element.content : element.content;
                elem.innerHTML = `<img src="${imgPath}" style="width:100%; height:100%; object-fit:contain;" onerror="this.parentElement.innerHTML='⚠️ Error loading image';">`;
            } else {
                elem.innerHTML = `
                    <div style="background:#fff3cd; padding:20px; text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; border: 2px dashed #ffc107;">
                        <div style="font-size: 48px;">📷</div>
                        <div style="margin-top: 10px; font-weight: 600;">Image Missing</div>
                    </div>
                    <div class="element-warning" onclick="fixElement(${index}, event)">
                        <i class="bi bi-tools"></i> Fix
                    </div>
                `;
            }
            break;
            
        case 'video':
            if (element.content) {
                elem.innerHTML = `<iframe src="${element.content}" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>`;
            } else {
                elem.innerHTML = `
                    <div style="background:#fff3cd; padding:20px; text-align:center; border: 2px dashed #ffc107;">
                        <div style="font-size: 48px;">🎬</div>
                        <div>Video URL Missing</div>
                    </div>
                    <div class="element-warning" onclick="fixElement(${index}, event)">
                        <i class="bi bi-tools"></i> Fix
                    </div>
                `;
            }
            break;
            
        default:
            elem.textContent = element.content || 'Element';
    }
    
    return elem;
}

function selectSlide(index) {
    currentSlideIndex = index;
    renderSlideThumbs();
    renderCurrentSlide();
}

function fixElement(index, event) {
    if (event) event.stopPropagation();
    
    fixingElementIndex = index;
    const element = presentationData.slides[currentSlideIndex].elements[index];
    const modal = document.getElementById('fixModal');
    const content = document.getElementById('fixContent');
    
    let html = '';
    
    if (element.type === 'image') {
        html = `
            <div class="mb-3">
                <label class="form-label"><strong>Upload New Image</strong></label>
                <input type="file" class="form-control" id="fixImageFile" accept="image/*">
            </div>
            <div class="text-center my-2">--- HOẶC ---</div>
            <div class="mb-3">
                <label class="form-label"><strong>Enter Image URL</strong></label>
                <input type="text" class="form-control" id="fixImageURL" value="${element.content || ''}" placeholder="https://example.com/image.jpg">
            </div>
        `;
    } else if (element.type === 'video') {
        html = `
            <div class="mb-3">
                <label class="form-label"><strong>Video URL (YouTube Embed)</strong></label>
                <input type="text" class="form-control" id="fixVideoURL" value="${element.content || ''}" placeholder="https://www.youtube.com/embed/...">
                <small class="text-muted">Dùng YouTube embed URL, VD: https://www.youtube.com/embed/VIDEO_ID</small>
            </div>
        `;
    } else if (element.type === 'text' || element.type === 'heading') {
        html = `
            <div class="mb-3">
                <label class="form-label"><strong>Edit Text Content</strong></label>
                <textarea class="form-control" id="fixTextContent" rows="5">${element.content || ''}</textarea>
            </div>
        `;
    }
    
    content.innerHTML = html;
    modal.style.display = 'block';
}

function closeFix() {
    document.getElementById('fixModal').style.display = 'none';
    fixingElementIndex = -1;
}

async function saveFix() {
    const element = presentationData.slides[currentSlideIndex].elements[fixingElementIndex];
    
    if (element.type === 'image') {
        const fileInput = document.getElementById('fixImageFile');
        const urlInput = document.getElementById('fixImageURL');
        
        if (fileInput.files.length > 0) {
            // Upload new image via FormData
            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('presentation_id', presentationData.id);
            
            try {
                // For now, just use URL input
                alert('Upload image feature coming soon. Please use Image URL for now.');
                return;
            } catch (e) {
                alert('Upload error: ' + e.message);
            }
        } else if (urlInput.value) {
            element.content = urlInput.value;
            element.missing = false;
        }
    } else if (element.type === 'video') {
        element.content = document.getElementById('fixVideoURL').value;
    } else if (element.type === 'text' || element.type === 'heading') {
        element.content = document.getElementById('fixTextContent').value;
    }
    
    closeFix();
    renderCurrentSlide();
    renderSlideThumbs();
}

async function savePresentation() {
    try {
        // Use new storage system
        const response = await fetch('api/slides/save_presentation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(presentationData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Đã lưu thành công!');
        } else {
            alert('❌ Lỗi: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        alert('❌ Lỗi kết nối: ' + error.message);
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        savePresentation();
    }
    
    // Arrow keys to navigate slides
    if (e.key === 'ArrowLeft' && currentSlideIndex > 0) {
        selectSlide(currentSlideIndex - 1);
    } else if (e.key === 'ArrowRight' && currentSlideIndex < presentationData.slides.length - 1) {
        selectSlide(currentSlideIndex + 1);
    }
});

// Close modal on outside click
document.getElementById('fixModal').onclick = function(e) {
    if (e.target === this) {
        closeFix();
    }
};
</script>

<?php include '../includes/teacher_footer.php'; ?>
