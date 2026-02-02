<?php
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$presentationId = $_GET['id'] ?? '';
$templateId = $_GET['template'] ?? '';

// Load existing presentation or create from template
$presentation = null;
if ($presentationId) {
    $presentationsFile = __DIR__ . '/../data/presentations.json';
    $presentations = file_exists($presentationsFile) ? json_decode(file_get_contents($presentationsFile), true) : [];
    foreach ($presentations as $p) {
        if ($p['id'] === $presentationId && $p['teacher_username'] === $username) {
            $presentation = $p;
            break;
        }
    }
} elseif ($templateId) {
    // Load templates from both files
    $templates = [];
    $templatesFile = __DIR__ . '/../data/slide_templates.json';
    $templatesCompleteFile = __DIR__ . '/../data/slide_templates_complete.json';
    
    if (file_exists($templatesFile)) {
        $basicTemplates = json_decode(file_get_contents($templatesFile), true);
        if (is_array($basicTemplates)) {
            $templates = array_merge($templates, $basicTemplates);
        }
    }
    if (file_exists($templatesCompleteFile)) {
        $completeTemplates = json_decode(file_get_contents($templatesCompleteFile), true);
        if (is_array($completeTemplates)) {
            $templates = array_merge($templates, $completeTemplates);
        }
    }
    
    foreach ($templates as $t) {
        if ($t['id'] === $templateId) {
            $presentation = [
                'id' => '',
                'title' => 'Bài Giảng Mới',
                'slides' => $t['slides'] ?? [],
                'settings' => ['theme' => $templateId]
            ];
            break;
        }
    }
}

// Default presentation
if (!$presentation) {
    $presentation = [
        'id' => '',
        'title' => 'Bài Giảng Mới',
        'slides' => [
            [
                'id' => 'slide_' . uniqid(),
                'type' => 'title',
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'elements' => [
                    [
                        'type' => 'heading',
                        'content' => 'Tiêu Đề Bài Giảng',
                        'style' => [
                            'fontSize' => '48px',
                            'color' => '#ffffff',
                            'textAlign' => 'center',
                            'top' => '40%',
                            'left' => '50%',
                            'transform' => 'translate(-50%, -50%)'
                        ]
                    ]
                ]
            ]
        ]
    ];
}

$title = 'Chỉnh Sửa Slide - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="../styles/slide-system.css">
<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- KaTeX for Math -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<!-- Interact.js for Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.19/dist/interact.min.js"></script>

<div class="slide-builder-container">
    <!-- Sidebar - Slide List -->
    <div class="slide-builder-sidebar">
        <div class="slide-sidebar-section">
            <h3>Slides</h3>
            <div class="slide-thumbs-list" id="slideThumbs">
                <!-- Will be populated by JavaScript -->
            </div>
            <div class="d-flex gap-2">
                <button class="slide-btn slide-btn-primary w-100 mt-3" onclick="addSlide()">
                    <i class="bi bi-plus-circle"></i> Thêm Slide
                </button>
            </div>
            <div class="mt-2">
                <label class="slide-property-label" style="font-size: 0.75rem;">Master Slides:</label>
                <select class="slide-property-input" id="masterSlideSelect" onchange="addMasterSlide(this.value)">
                    <option value="">Chọn template...</option>
                    <option value="title">Title Slide</option>
                    <option value="content">Content Slide</option>
                    <option value="two-column">Two Columns</option>
                    <option value="image-text">Image + Text</option>
                    <option value="full-image">Full Image</option>
                </select>
            </div>
        </div>
        
        <div class="slide-sidebar-section">
            <h3>Elements</h3>
            <div class="slide-elements-grid">
                <button class="slide-element-btn" onclick="addElement('heading')" title="Thêm tiêu đề">
                    <i class="bi bi-type-h1"></i>
                    <span>Tiêu đề</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('text')" title="Thêm văn bản">
                    <i class="bi bi-fonts"></i>
                    <span>Văn bản</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('image')" title="Thêm hình ảnh">
                    <i class="bi bi-image"></i>
                    <span>Hình ảnh</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('video')" title="Thêm video">
                    <i class="bi bi-play-circle"></i>
                    <span>Video</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('audio')" title="Thêm âm thanh">
                    <i class="bi bi-music-note"></i>
                    <span>Âm thanh</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('shape')" title="Thêm hình dạng">
                    <i class="bi bi-square"></i>
                    <span>Hình dạng</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('math')" title="Thêm công thức">
                    <i class="bi bi-calculator"></i>
                    <span>Công thức</span>
                </button>
                <button class="slide-element-btn" onclick="addElement('code')" title="Thêm code">
                    <i class="bi bi-code-slash"></i>
                    <span>Code</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Canvas -->
    <div class="slide-builder-main">
        <div class="slide-builder-toolbar">
            <input type="text" class="slide-builder-title" id="presentationTitle" value="<?php echo htmlspecialchars($presentation['title']); ?>" placeholder="Tên bài giảng">
            
            <div class="slide-builder-actions">
                <button class="slide-btn slide-btn-secondary" onclick="undoAction()" title="Hoàn tác (Ctrl+Z)">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <button class="slide-btn slide-btn-secondary" onclick="redoAction()" title="Làm lại (Ctrl+Y)">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <span class="mx-2">|</span>
                <button class="slide-btn slide-btn-secondary" onclick="previewPresentation()">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="slide-btn slide-btn-success" onclick="savePresentation()">
                    <i class="bi bi-save"></i> Lưu
                </button>
                <a href="slides.php" class="slide-btn slide-btn-secondary">
                    <i class="bi bi-x-circle"></i> Đóng
                </a>
            </div>
        </div>
        
        <div class="slide-builder-canvas-wrapper">
            <!-- Zoom Controls -->
            <div style="position: absolute; top: 1rem; right: 1rem; z-index: 100; background: white; border-radius: 8px; padding: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; gap: 8px; align-items: center;">
                <button class="slide-btn-icon" onclick="setCanvasZoom(0.33)" title="33% - Thu nhỏ">
                    <i class="bi bi-zoom-out"></i>
                </button>
                <button class="slide-btn-icon" onclick="setCanvasZoom(0.5)" title="50% - Mặc định" style="background: #e7f3ff;">
                    <i class="bi bi-aspect-ratio"></i>
                </button>
                <button class="slide-btn-icon" onclick="setCanvasZoom(0.67)" title="67% - Phóng to">
                    <i class="bi bi-zoom-in"></i>
                </button>
                <button class="slide-btn-icon" onclick="setCanvasZoom(1)" title="100% - Toàn màn">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <span id="zoomLevel" style="font-size: 12px; color: #666; min-width: 40px; text-align: center; font-weight: 600;">50%</span>
            </div>
            
            <!-- Keyboard Shortcuts Hint -->
            <div style="position: absolute; bottom: 1rem; right: 1rem; z-index: 100; background: rgba(255,255,255,0.95); border-radius: 8px; padding: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 250px; font-size: 11px;">
                <div style="font-weight: bold; margin-bottom: 6px; color: #667eea; display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-keyboard"></i> Phím tắt
                </div>
                <div style="color: #666; line-height: 1.6;">
                    <div><kbd style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 10px;">←↑↓→</kbd> Di chuyển (1px)</div>
                    <div><kbd style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 10px;">Shift+Arrow</kbd> Di chuyển (10px)</div>
                    <div><kbd style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 10px;">Ctrl+D</kbd> Nhân bản</div>
                    <div><kbd style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 10px;">Delete</kbd> Xóa</div>
                    <div><kbd style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 10px;">Ctrl+Z/Y</kbd> Undo/Redo</div>
                </div>
            </div>
            
            <div class="slide-builder-canvas" id="slideCanvas">
                <!-- Current slide will be rendered here -->
            </div>
        </div>
    </div>
    
    <!-- Properties Panel -->
    <div class="slide-builder-properties">
        <!-- Theme Selector -->
        <div class="slide-property-group">
            <h4><i class="bi bi-palette"></i> Theme</h4>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Quick Apply Theme</label>
                <div class="slide-themes-grid" id="themesGrid">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Color Palette</label>
                <div class="slide-color-palette" id="colorPalette">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="slide-property-group">
            <h4>Slide Properties</h4>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Background</label>
                <div class="d-flex gap-2">
                    <input type="color" class="slide-property-input" id="slideBackground" onchange="updateSlideBackground(this.value)" style="width: 60px;">
                    <select class="slide-property-input" id="slideBackgroundGradient" onchange="updateSlideBackgroundGradient(this.value)">
                        <option value="">Solid Color</option>
                        <option value="gradient1">Gradient 1</option>
                        <option value="gradient2">Gradient 2</option>
                        <option value="gradient3">Gradient 3</option>
                    </select>
                </div>
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Transition</label>
                <select class="slide-property-input" id="slideTransition">
                    <option value="none">None</option>
                    <option value="fade">Fade</option>
                    <option value="slide">Slide</option>
                    <option value="zoom">Zoom</option>
                </select>
            </div>
        </div>
        
        <div class="slide-property-group" id="elementProperties" style="display: none;">
            <h4>Element Properties</h4>
            
            <div class="slide-property-field" id="contentField">
                <label class="slide-property-label">Content</label>
                <div id="richTextEditor" style="background: white; min-height: 150px;"></div>
                <input type="text" class="slide-property-input" id="elementContentSimple" style="display: none;" oninput="updateElementContent(this.value)">
            </div>
            
            <!-- Image Upload Field -->
            <div class="slide-property-field" id="imageUploadField" style="display: none;">
                <label class="slide-property-label">Upload Image</label>
                <input type="file" class="slide-property-input" id="imageUpload" accept="image/*" onchange="uploadImage(this)">
                <small class="text-muted">Hoặc nhập URL hình ảnh vào Content</small>
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Position</label>
                <div class="d-flex gap-2">
                    <input type="number" class="slide-property-input" id="elementX" placeholder="X" onchange="updateElementPosition()">
                    <input type="number" class="slide-property-input" id="elementY" placeholder="Y" onchange="updateElementPosition()">
                </div>
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Size</label>
                <div class="d-flex gap-2">
                    <input type="number" class="slide-property-input" id="elementWidth" placeholder="Width" onchange="updateElementSize()">
                    <input type="number" class="slide-property-input" id="elementHeight" placeholder="Height" onchange="updateElementSize()">
                </div>
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Font Size</label>
                <input type="number" class="slide-property-input" id="elementFontSize" value="24" min="12" max="120" onchange="updateElementStyle('fontSize', this.value + 'px')">
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Color</label>
                <input type="color" class="slide-property-input" id="elementColor" value="#333333" onchange="updateElementStyle('color', this.value)">
            </div>
            
            <div class="slide-property-field">
                <label class="slide-property-label">Animation</label>
                <select class="slide-property-input" id="elementAnimation" onchange="updateElementAnimation(this.value)">
                    <option value="none">None</option>
                    <option value="fadeIn">Fade In</option>
                    <option value="slideInLeft">Slide In Left</option>
                    <option value="slideInRight">Slide In Right</option>
                    <option value="slideInUp">Slide In Up</option>
                    <option value="slideInDown">Slide In Down</option>
                    <option value="zoomIn">Zoom In</option>
                    <option value="bounceIn">Bounce In</option>
                </select>
            </div>
            
            <div class="slide-property-field">
                <button class="slide-btn slide-btn-danger w-100" onclick="deleteElement()">
                    <i class="bi bi-trash"></i> Xóa Element
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script>
// Global state
let presentationData = <?php echo json_encode($presentation); ?>;
let currentSlideIndex = 0;
let selectedElementIndex = -1;
let quillEditor = null;
let historyStack = [];
let historyIndex = -1;
let isDragging = false;
let themesData = [];
let currentTheme = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadThemes();
    initializeQuill();
    renderSlideThumbs();
    renderCurrentSlide();
    setupKeyboardShortcuts();
    saveHistory();
});

// Canvas zoom control
let currentZoom = 0.5;

function setCanvasZoom(scale) {
    currentZoom = scale;
    const canvas = document.getElementById('slideCanvas');
    
    // Simply update the scale, container will auto-adjust
    canvas.style.transform = `scale(${scale})`;
    
    // Update zoom level display
    document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
    
    // Highlight active button
    const buttons = document.querySelectorAll('.slide-builder-canvas-wrapper .slide-btn-icon');
    buttons.forEach(btn => btn.style.background = '');
    event.target.closest('.slide-btn-icon').style.background = '#e7f3ff';
}

// Load themes from JSON
async function loadThemes() {
    try {
        const response = await fetch('../data/slide_themes.json');
        themesData = await response.json();
        renderThemes();
    } catch (error) {
        console.error('Error loading themes:', error);
    }
}

// Render theme selector
function renderThemes() {
    const grid = document.getElementById('themesGrid');
    grid.innerHTML = '';
    
    themesData.forEach(theme => {
        const btn = document.createElement('button');
        btn.className = 'slide-theme-btn';
        btn.onclick = () => applyTheme(theme.id);
        btn.title = theme.description;
        btn.innerHTML = `
            <span class="theme-emoji">${theme.thumbnail}</span>
            <span class="theme-name">${theme.name}</span>
        `;
        grid.appendChild(btn);
    });
}

// Apply theme to presentation
function applyTheme(themeId) {
    const theme = themesData.find(t => t.id === themeId);
    if (!theme) return;
    
    currentTheme = theme;
    
    // Update color palette display
    renderColorPalette(theme);
    
    // Update current theme gradients
    const gradientSelect = document.getElementById('slideBackgroundGradient');
    gradientSelect.innerHTML = '<option value="">Solid Color</option>';
    theme.gradients.forEach((grad, i) => {
        const opt = document.createElement('option');
        opt.value = 'gradient' + (i + 1);
        opt.textContent = 'Gradient ' + (i + 1);
        opt.dataset.gradient = grad;
        gradientSelect.appendChild(opt);
    });
    
    // Apply theme to current slide if no custom background
    const currentSlide = presentationData.slides[currentSlideIndex];
    if (!currentSlide.customBackground) {
        currentSlide.background = theme.gradients[0];
        renderCurrentSlide();
    }
    
    // Store theme in presentation settings
    if (!presentationData.settings) presentationData.settings = {};
    presentationData.settings.theme = themeId;
    
    saveHistory();
    
    // Visual feedback
    document.querySelectorAll('.slide-theme-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.slide-theme-btn').classList.add('active');
}

// Render color palette
function renderColorPalette(theme) {
    const palette = document.getElementById('colorPalette');
    palette.innerHTML = '';
    
    Object.entries(theme.colors).forEach(([name, color]) => {
        const colorBtn = document.createElement('div');
        colorBtn.className = 'slide-color-swatch';
        colorBtn.style.backgroundColor = color;
        colorBtn.title = name + ': ' + color;
        colorBtn.onclick = () => {
            if (selectedElementIndex >= 0) {
                updateElementStyle('color', color);
            } else {
                updateSlideBackground(color);
            }
        };
        palette.appendChild(colorBtn);
    });
}

// Update background gradient
function updateSlideBackgroundGradient(value) {
    if (!value) return;
    
    const select = document.getElementById('slideBackgroundGradient');
    const gradient = select.selectedOptions[0]?.dataset.gradient;
    
    if (gradient) {
        presentationData.slides[currentSlideIndex].background = gradient;
        presentationData.slides[currentSlideIndex].customBackground = true;
        saveHistory();
        renderCurrentSlide();
    }
}

// Initialize Quill Rich Text Editor
function initializeQuill() {
    quillEditor = new Quill('#richTextEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });
    
    quillEditor.on('text-change', function() {
        if (selectedElementIndex >= 0) {
            const content = quillEditor.root.innerHTML;
            presentationData.slides[currentSlideIndex].elements[selectedElementIndex].content = content;
            renderCurrentSlide();
        }
    });
}

// Keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ignore if typing in input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.classList.contains('ql-editor')) {
            return;
        }
        
        // Undo/Redo
        if (e.ctrlKey && e.key === 'z') {
            e.preventDefault();
            undoAction();
        } else if (e.ctrlKey && e.key === 'y') {
            e.preventDefault();
            redoAction();
        }
        // Duplicate element
        else if (e.ctrlKey && e.key === 'd' && selectedElementIndex >= 0) {
            e.preventDefault();
            duplicateElement();
        }
        // Delete element
        else if ((e.key === 'Delete' || e.key === 'Backspace') && selectedElementIndex >= 0) {
            e.preventDefault();
            deleteElement();
        }
        // Arrow keys to move element
        else if (selectedElementIndex >= 0 && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
            e.preventDefault();
            moveElementWithArrows(e.key, e.shiftKey);
        }
    });
}

// Move element using arrow keys
function moveElementWithArrows(key, shiftKey) {
    const element = presentationData.slides[currentSlideIndex].elements[selectedElementIndex];
    if (!element.position) {
        element.position = { x: 50, y: 50 };
    }
    
    const step = shiftKey ? 10 : 1; // Shift = 10px, normal = 1px
    
    switch(key) {
        case 'ArrowLeft':
            element.position.x -= step;
            break;
        case 'ArrowRight':
            element.position.x += step;
            break;
        case 'ArrowUp':
            element.position.y -= step;
            break;
        case 'ArrowDown':
            element.position.y += step;
            break;
    }
    
    // Keep within bounds
    element.position.x = Math.max(0, Math.min(960, element.position.x));
    element.position.y = Math.max(0, Math.min(540, element.position.y));
    
    renderCurrentSlide();
    updatePropertiesPanel();
    saveHistory();
}

// Duplicate selected element
function duplicateElement() {
    const element = presentationData.slides[currentSlideIndex].elements[selectedElementIndex];
    const newElement = JSON.parse(JSON.stringify(element));
    
    // Offset position
    if (newElement.position) {
        newElement.position.x += 20;
        newElement.position.y += 20;
    }
    
    presentationData.slides[currentSlideIndex].elements.push(newElement);
    selectedElementIndex = presentationData.slides[currentSlideIndex].elements.length - 1;
    
    renderCurrentSlide();
    updatePropertiesPanel();
    saveHistory();
}

// History management
function saveHistory() {
    const snapshot = JSON.parse(JSON.stringify(presentationData));
    historyStack = historyStack.slice(0, historyIndex + 1);
    historyStack.push(snapshot);
    historyIndex++;
    
    // Limit history to 50 states
    if (historyStack.length > 50) {
        historyStack.shift();
        historyIndex--;
    }
}

function undoAction() {
    if (historyIndex > 0) {
        historyIndex--;
        presentationData = JSON.parse(JSON.stringify(historyStack[historyIndex]));
        renderSlideThumbs();
        renderCurrentSlide();
    }
}

function redoAction() {
    if (historyIndex < historyStack.length - 1) {
        historyIndex++;
        presentationData = JSON.parse(JSON.stringify(historyStack[historyIndex]));
        renderSlideThumbs();
        renderCurrentSlide();
    }
}

function renderSlideThumbs() {
    const container = document.getElementById('slideThumbs');
    container.innerHTML = '';
    
    presentationData.slides.forEach((slide, index) => {
        const div = document.createElement('div');
        div.className = 'slide-thumb-item' + (index === currentSlideIndex ? ' active' : '');
        div.onclick = () => selectSlide(index);
        
        div.innerHTML = `
            <div class="slide-thumb-number">${index + 1}</div>
            <div class="slide-thumb-preview">Slide ${index + 1}</div>
            ${presentationData.slides.length > 1 ? `<button class="slide-thumb-delete" onclick="event.stopPropagation(); deleteSlide(${index})">×</button>` : ''}
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
        
        // Enable drag & drop with Interact.js
        makeElementDraggable(elem, index);
    });
}

function createElementDOM(element, index) {
    const elem = document.createElement('div');
    elem.className = 'slide-element';
    elem.dataset.index = index;
    elem.style.position = 'absolute';
    elem.style.cursor = 'move';
    
    // Apply position
    elem.style.left = (element.position?.x || 50) + 'px';
    elem.style.top = (element.position?.y || 50) + 'px';
    elem.style.width = (element.size?.width || 'auto');
    elem.style.height = (element.size?.height || 'auto');
    
    // Apply styles
    if (element.style) {
        Object.assign(elem.style, element.style);
    }
    
    // Apply animation class
    if (element.animation && element.animation !== 'none') {
        elem.classList.add('animate-' + element.animation);
    }
    
    // Set content based on type
    switch (element.type) {
        case 'heading':
            elem.innerHTML = `<h1 style="margin:0; font-size: inherit; color: inherit;">${element.content || 'Tiêu đề'}</h1>`;
            break;
        case 'text':
            elem.innerHTML = element.content || 'Văn bản';
            elem.style.padding = '15px';
            break;
        case 'image':
            elem.innerHTML = element.content ? 
                `<img src="${element.content}" style="width: 100%; height: 100%; object-fit: contain;">` :
                `<div style="background: #f0f0f0; padding: 20px; text-align: center;">📷 Hình ảnh<br><small>Nhập URL trong properties</small></div>`;
            break;
        case 'video':
            elem.innerHTML = element.content ? 
                `<iframe width="100%" height="100%" src="${getVideoEmbedUrl(element.content)}" frameborder="0" allowfullscreen></iframe>` :
                `<div style="background: #f0f0f0; padding: 20px; text-align: center;">🎬 Video<br><small>Nhập YouTube URL</small></div>`;
            break;
        case 'audio':
            elem.innerHTML = element.content ?
                `<audio controls style="width: 100%;"><source src="${element.content}"></audio>` :
                `<div style="background: #f0f0f0; padding: 20px; text-align: center;">🎵 Audio<br><small>Nhập audio URL</small></div>`;
            break;
        case 'shape':
            const shapeType = element.shapeType || 'rectangle';
            elem.style.border = '2px solid ' + (element.style?.borderColor || '#333');
            elem.style.backgroundColor = element.style?.backgroundColor || 'transparent';
            if (shapeType === 'circle') {
                elem.style.borderRadius = '50%';
            }
            elem.style.width = (element.size?.width || '100px');
            elem.style.height = (element.size?.height || '100px');
            break;
        case 'math':
            try {
                elem.innerHTML = element.content ? 
                    katex.renderToString(element.content, { throwOnError: false }) :
                    'x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}';
            } catch (e) {
                elem.textContent = element.content || 'LaTeX formula';
            }
            elem.style.fontSize = '24px';
            break;
        case 'code':
            elem.innerHTML = `<pre style="margin:0; background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto;"><code>${escapeHtml(element.content || '// Code here')}</code></pre>`;
            break;
        default:
            elem.textContent = element.content || 'Element';
    }
    
    elem.onclick = (e) => {
        e.stopPropagation();
        selectElement(index);
    };
    
    // Add resize handles for selected element
    if (index === selectedElementIndex) {
        elem.classList.add('selected');
        const handles = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
        handles.forEach(dir => {
            const handle = document.createElement('div');
            handle.className = `resize-handle resize-handle-${dir}`;
            handle.style.position = 'absolute';
            handle.style.width = '12px';
            handle.style.height = '12px';
            handle.style.background = '#667eea';
            handle.style.border = '2px solid white';
            handle.style.borderRadius = '50%';
            handle.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
            handle.style.zIndex = '1001';
            elem.appendChild(handle);
        });
    }
    
    return elem;
}

function getVideoEmbedUrl(url) {
    // Convert YouTube watch URL to embed URL
    if (url.includes('youtube.com/watch')) {
        const videoId = new URL(url).searchParams.get('v');
        return `https://www.youtube.com/embed/${videoId}`;
    } else if (url.includes('youtu.be/')) {
        const videoId = url.split('youtu.be/')[1].split('?')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    }
    return url;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function makeElementDraggable(elem, index) {
    interact(elem)
        .draggable({
            listeners: {
                start(event) {
                    // Enhanced visual feedback
                    event.target.style.opacity = '0.9';
                    event.target.style.cursor = 'grabbing';
                    event.target.style.zIndex = '1000';
                    event.target.style.boxShadow = '0 12px 40px rgba(102, 126, 234, 0.5)';
                    event.target.style.outline = '3px solid #667eea';
                    event.target.style.outlineOffset = '3px';
                    event.target.style.transform = 'scale(1.02)';
                },
                move(event) {
                    const target = event.target;
                    // Compensate for canvas zoom scale
                    const scaleFactor = 1 / currentZoom;
                    const dx = event.dx * scaleFactor;
                    const dy = event.dy * scaleFactor;
                    
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + dy;
                    
                    target.style.transform = `translate(${x}px, ${y}px)`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                },
                end(event) {
                    const target = event.target;
                    let x = parseFloat(target.style.left) + (parseFloat(target.getAttribute('data-x')) || 0);
                    let y = parseFloat(target.style.top) + (parseFloat(target.getAttribute('data-y')) || 0);
                    
                    // Snap to grid (10px)
                    x = Math.round(x / 10) * 10;
                    y = Math.round(y / 10) * 10;
                    
                    // Update element position
                    const elementIndex = parseInt(target.dataset.index);
                    if (!presentationData.slides[currentSlideIndex].elements[elementIndex].position) {
                        presentationData.slides[currentSlideIndex].elements[elementIndex].position = {};
                    }
                    presentationData.slides[currentSlideIndex].elements[elementIndex].position.x = x;
                    presentationData.slides[currentSlideIndex].elements[elementIndex].position.y = y;
                    
                    // Reset transform and restore styles
                    target.style.left = x + 'px';
                    target.style.top = y + 'px';
                    target.style.transform = '';
                    target.setAttribute('data-x', 0);
                    target.setAttribute('data-y', 0);
                    target.style.opacity = '';
                    target.style.cursor = 'move';
                    target.style.zIndex = '';
                    target.style.boxShadow = '';
                    target.style.outline = '';
                    target.style.outlineOffset = '';
                    
                    saveHistory();
                    updatePropertiesPanel();
                }
            },
            modifiers: [
                interact.modifiers.snap({
                    targets: [
                        interact.snappers.grid({ x: 10, y: 10 })
                    ],
                    range: Infinity,
                    relativePoints: [ { x: 0, y: 0 } ]
                })
            ]
        })
        .resizable({
            edges: { left: true, right: true, bottom: true, top: true },
            listeners: {
                start(event) {
                    // Enhanced visual feedback for resize
                    event.target.style.opacity = '0.9';
                    event.target.style.boxShadow = '0 8px 30px rgba(102, 126, 234, 0.4)';
                    
                    // Create tooltip for dimensions
                    const tooltip = document.createElement('div');
                    tooltip.id = 'resize-tooltip';
                    tooltip.style.cssText = 'position: fixed; background: #667eea; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; z-index: 10000; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                    document.body.appendChild(tooltip);
                },
                move(event) {
                    const target = event.target;
                    let x = (parseFloat(target.getAttribute('data-x')) || 0);
                    let y = (parseFloat(target.getAttribute('data-y')) || 0);
                    
                    // Compensate for canvas zoom
                    const scaleFactor = 1 / currentZoom;
                    
                    target.style.width = event.rect.width + 'px';
                    target.style.height = event.rect.height + 'px';
                    
                    x += event.deltaRect.left * scaleFactor;
                    y += event.deltaRect.top * scaleFactor;
                    
                    target.style.transform = `translate(${x}px, ${y}px)`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                    
                    // Update tooltip with dimensions
                    const tooltip = document.getElementById('resize-tooltip');
                    if (tooltip) {
                        tooltip.textContent = `${Math.round(event.rect.width)} × ${Math.round(event.rect.height)} px`;
                        tooltip.style.left = (event.clientX + 15) + 'px';
                        tooltip.style.top = (event.clientY + 15) + 'px';
                    }
                },
                end(event) {
                    const target = event.target;
                    const elementIndex = parseInt(target.dataset.index);
                    
                    // Update size
                    if (!presentationData.slides[currentSlideIndex].elements[elementIndex].size) {
                        presentationData.slides[currentSlideIndex].elements[elementIndex].size = {};
                    }
                    presentationData.slides[currentSlideIndex].elements[elementIndex].size.width = target.style.width;
                    presentationData.slides[currentSlideIndex].elements[elementIndex].size.height = target.style.height;
                    
                    // Restore opacity and remove tooltip
                    target.style.opacity = '';
                    target.style.boxShadow = '';
                    const tooltip = document.getElementById('resize-tooltip');
                    if (tooltip) {
                        tooltip.remove();
                    }
                    
                    saveHistory();
                    updatePropertiesPanel();
                }
            },
            modifiers: [
                interact.modifiers.restrictSize({
                    min: { width: 50, height: 30 }
                })
            ]
        });
}

function selectSlide(index) {
    currentSlideIndex = index;
    selectedElementIndex = -1;
    renderSlideThumbs();
    renderCurrentSlide();
    document.getElementById('elementProperties').style.display = 'none';
}

function selectElement(index) {
    selectedElementIndex = index;
    renderCurrentSlide(); // Re-render to show resize handles
    updatePropertiesPanel();
}

function updatePropertiesPanel() {
    const element = presentationData.slides[currentSlideIndex].elements[selectedElementIndex];
    
    // Show properties panel
    document.getElementById('elementProperties').style.display = 'block';
    
    // Update content based on element type
    if (element.type === 'text' || element.type === 'heading') {
        document.getElementById('richTextEditor').style.display = 'block';
        document.getElementById('elementContentSimple').style.display = 'none';
        document.getElementById('imageUploadField').style.display = 'none';
        quillEditor.root.innerHTML = element.content || '';
    } else if (element.type === 'image') {
        document.getElementById('richTextEditor').style.display = 'none';
        document.getElementById('elementContentSimple').style.display = 'block';
        document.getElementById('imageUploadField').style.display = 'block';
        document.getElementById('elementContentSimple').value = element.content || '';
    } else {
        document.getElementById('richTextEditor').style.display = 'none';
        document.getElementById('elementContentSimple').style.display = 'block';
        document.getElementById('imageUploadField').style.display = 'none';
        document.getElementById('elementContentSimple').value = element.content || '';
    }
    
    // Update position
    document.getElementById('elementX').value = element.position?.x || 50;
    document.getElementById('elementY').value = element.position?.y || 50;
    
    // Update size
    document.getElementById('elementWidth').value = parseInt(element.size?.width) || '';
    document.getElementById('elementHeight').value = parseInt(element.size?.height) || '';
    
    // Update style
    document.getElementById('elementFontSize').value = parseInt(element.style?.fontSize || '24');
    document.getElementById('elementColor').value = element.style?.color || '#333333';
    
    // Update animation
    document.getElementById('elementAnimation').value = element.animation || 'none';
}

function selectSlide(index) {
    currentSlideIndex = index;
    selectedElementIndex = -1;
    renderSlideThumbs();
    renderCurrentSlide();
    document.getElementById('elementProperties').style.display = 'none';
}

function addSlide() {
    const newSlide = {
        id: 'slide_' + Date.now(),
        type: 'content',
        background: currentTheme ? currentTheme.gradients[0] : '#ffffff',
        elements: []
    };
    
    presentationData.slides.push(newSlide);
    currentSlideIndex = presentationData.slides.length - 1;
    saveHistory();
    renderSlideThumbs();
    renderCurrentSlide();
}

function addMasterSlide(type) {
    if (!type) return;
    
    const templates = {
        'title': {
            type: 'title',
            background: currentTheme ? currentTheme.gradients[0] : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            elements: [
                {
                    type: 'heading',
                    content: 'Tiêu Đề Chính',
                    position: { x: 80, y: 200 },
                    size: { width: '800px', height: 'auto' },
                    style: { fontSize: '56px', color: '#ffffff', textAlign: 'center' },
                    animation: 'fadeIn'
                },
                {
                    type: 'text',
                    content: 'Phụ đề hoặc mô tả',
                    position: { x: 280, y: 320 },
                    size: { width: '400px', height: 'auto' },
                    style: { fontSize: '24px', color: '#ffffff', textAlign: 'center' },
                    animation: 'slideInUp'
                }
            ]
        },
        'content': {
            type: 'content',
            background: currentTheme ? currentTheme.colors.background : '#ffffff',
            elements: [
                {
                    type: 'heading',
                    content: 'Tiêu Đề Nội Dung',
                    position: { x: 80, y: 60 },
                    size: { width: '800px', height: 'auto' },
                    style: { fontSize: '40px', color: currentTheme ? currentTheme.colors.primary : '#2c3e50' },
                    animation: 'slideInDown'
                },
                {
                    type: 'text',
                    content: '<ul><li>Điểm 1</li><li>Điểm 2</li><li>Điểm 3</li></ul>',
                    position: { x: 100, y: 180 },
                    size: { width: '760px', height: 'auto' },
                    style: { fontSize: '24px', color: currentTheme ? currentTheme.colors.text : '#333' },
                    animation: 'slideInLeft'
                }
            ]
        },
        'two-column': {
            type: 'content',
            background: currentTheme ? currentTheme.colors.background : '#ffffff',
            elements: [
                {
                    type: 'heading',
                    content: 'Hai Cột',
                    position: { x: 80, y: 40 },
                    size: { width: '800px', height: 'auto' },
                    style: { fontSize: '40px', color: currentTheme ? currentTheme.colors.primary : '#2c3e50' },
                    animation: 'fadeIn'
                },
                {
                    type: 'text',
                    content: '<h3>Cột Trái</h3><p>Nội dung cột trái</p>',
                    position: { x: 60, y: 150 },
                    size: { width: '400px', height: 'auto' },
                    style: { fontSize: '20px', color: currentTheme ? currentTheme.colors.text : '#333' },
                    animation: 'slideInLeft'
                },
                {
                    type: 'text',
                    content: '<h3>Cột Phải</h3><p>Nội dung cột phải</p>',
                    position: { x: 500, y: 150 },
                    size: { width: '400px', height: 'auto' },
                    style: { fontSize: '20px', color: currentTheme ? currentTheme.colors.text : '#333' },
                    animation: 'slideInRight'
                }
            ]
        },
        'image-text': {
            type: 'content',
            background: currentTheme ? currentTheme.colors.background : '#ffffff',
            elements: [
                {
                    type: 'heading',
                    content: 'Hình Ảnh & Văn Bản',
                    position: { x: 80, y: 40 },
                    size: { width: '800px', height: 'auto' },
                    style: { fontSize: '40px', color: currentTheme ? currentTheme.colors.primary : '#2c3e50' },
                    animation: 'fadeIn'
                },
                {
                    type: 'image',
                    content: '',
                    position: { x: 60, y: 140 },
                    size: { width: '400px', height: '300px' },
                    animation: 'slideInLeft'
                },
                {
                    type: 'text',
                    content: '<p>Mô tả hình ảnh hoặc nội dung bổ sung.</p>',
                    position: { x: 500, y: 180 },
                    size: { width: '400px', height: 'auto' },
                    style: { fontSize: '20px', color: currentTheme ? currentTheme.colors.text : '#333' },
                    animation: 'slideInRight'
                }
            ]
        },
        'full-image': {
            type: 'content',
            background: '#000000',
            elements: [
                {
                    type: 'image',
                    content: '',
                    position: { x: 0, y: 0 },
                    size: { width: '960px', height: '540px' },
                    animation: 'zoomIn'
                }
            ]
        }
    };
    
    const template = templates[type];
    if (template) {
        const newSlide = {
            id: 'slide_' + Date.now(),
            ...template
        };
        
        presentationData.slides.push(newSlide);
        currentSlideIndex = presentationData.slides.length - 1;
        saveHistory();
        renderSlideThumbs();
        renderCurrentSlide();
        
        // Reset select
        document.getElementById('masterSlideSelect').value = '';
    }
}

function deleteSlide(index) {
    if (presentationData.slides.length === 1) {
        alert('Không thể xóa slide cuối cùng!');
        return;
    }
    
    presentationData.slides.splice(index, 1);
    if (currentSlideIndex >= presentationData.slides.length) {
        currentSlideIndex = presentationData.slides.length - 1;
    }
    saveHistory();
    renderSlideThumbs();
    renderCurrentSlide();
}

function addElement(type) {
    const newElement = {
        type: type,
        content: getDefaultContent(type),
        position: { x: 100, y: 100 },
        size: { width: type === 'shape' ? '100px' : 'auto', height: type === 'shape' ? '100px' : 'auto' },
        style: {
            fontSize: '24px',
            color: '#333333'
        },
        animation: 'none'
    };
    
    if (type === 'shape') {
        newElement.shapeType = 'rectangle';
        newElement.style.borderColor = '#333333';
        newElement.style.backgroundColor = 'transparent';
    }
    
    presentationData.slides[currentSlideIndex].elements.push(newElement);
    saveHistory();
    renderCurrentSlide();
    selectElement(presentationData.slides[currentSlideIndex].elements.length - 1);
}

function getDefaultContent(type) {
    const defaults = {
        'heading': 'Tiêu đề mới',
        'text': 'Văn bản mới',
        'image': '',
        'video': '',
        'audio': '',
        'shape': '',
        'math': 'x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}',
        'code': '// Your code here\nfunction hello() {\n  console.log("Hello World!");\n}'
    };
    return defaults[type] || '';
}

function deleteElement() {
    if (selectedElementIndex >= 0) {
        if (confirm('Xóa element này?')) {
            presentationData.slides[currentSlideIndex].elements.splice(selectedElementIndex, 1);
            selectedElementIndex = -1;
            saveHistory();
            renderCurrentSlide();
            document.getElementById('elementProperties').style.display = 'none';
        }
    }
}

function updateSlideBackground(color) {
    presentationData.slides[currentSlideIndex].background = color;
    presentationData.slides[currentSlideIndex].customBackground = true;
    saveHistory();
    renderCurrentSlide();
}

function updateElementContent(content) {
    if (selectedElementIndex >= 0) {
        presentationData.slides[currentSlideIndex].elements[selectedElementIndex].content = content;
        renderCurrentSlide();
    }
}

function updateElementPosition() {
    if (selectedElementIndex >= 0) {
        const x = parseInt(document.getElementById('elementX').value) || 0;
        const y = parseInt(document.getElementById('elementY').value) || 0;
        
        if (!presentationData.slides[currentSlideIndex].elements[selectedElementIndex].position) {
            presentationData.slides[currentSlideIndex].elements[selectedElementIndex].position = {};
        }
        presentationData.slides[currentSlideIndex].elements[selectedElementIndex].position.x = x;
        presentationData.slides[currentSlideIndex].elements[selectedElementIndex].position.y = y;
        
        saveHistory();
        renderCurrentSlide();
        selectElement(selectedElementIndex);
    }
}

function updateElementSize() {
    if (selectedElementIndex >= 0) {
        const width = document.getElementById('elementWidth').value;
        const height = document.getElementById('elementHeight').value;
        
        if (!presentationData.slides[currentSlideIndex].elements[selectedElementIndex].size) {
            presentationData.slides[currentSlideIndex].elements[selectedElementIndex].size = {};
        }
        if (width) presentationData.slides[currentSlideIndex].elements[selectedElementIndex].size.width = width + 'px';
        if (height) presentationData.slides[currentSlideIndex].elements[selectedElementIndex].size.height = height + 'px';
        
        saveHistory();
        renderCurrentSlide();
        selectElement(selectedElementIndex);
    }
}

function updateElementStyle(property, value) {
    if (selectedElementIndex >= 0) {
        if (!presentationData.slides[currentSlideIndex].elements[selectedElementIndex].style) {
            presentationData.slides[currentSlideIndex].elements[selectedElementIndex].style = {};
        }
        presentationData.slides[currentSlideIndex].elements[selectedElementIndex].style[property] = value;
        saveHistory();
        renderCurrentSlide();
    }
}

function updateElementAnimation(animation) {
    if (selectedElementIndex >= 0) {
        presentationData.slides[currentSlideIndex].elements[selectedElementIndex].animation = animation;
        saveHistory();
        renderCurrentSlide();
    }
}

async function uploadImage(input) {
    const file = input.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('image', file);
    
    try {
        const response = await fetch('api/slides/upload_image.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            // Update element content with uploaded image URL
            document.getElementById('elementContentSimple').value = result.url;
            updateElementContent(result.url);
            alert('Đã tải lên hình ảnh!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        alert('Có lỗi xảy ra khi tải lên hình ảnh!');
    }
}

async function savePresentation() {
    const title = document.getElementById('presentationTitle').value;
    presentationData.title = title;
    
    try {
        const endpoint = presentationData.id ? 'api/slides/update.php' : 'api/slides/save.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                presentation_id: presentationData.id,
                title: title,
                slides: presentationData.slides,
                settings: presentationData.settings || {}
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Đã lưu bài giảng!');
            if (!presentationData.id) {
                presentationData.id = result.presentation_id;
                window.location.href = 'slide_builder.php?id=' + result.presentation_id;
            }
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        alert('Có lỗi xảy ra khi lưu!');
    }
}

function previewPresentation() {
    if (!presentationData.id) {
        alert('Vui lòng lưu bài giảng trước!');
        return;
    }
    window.open('slide_presenter.php?id=' + presentationData.id, '_blank');
}
</script>

<?php include '../includes/teacher_footer.php'; ?>
