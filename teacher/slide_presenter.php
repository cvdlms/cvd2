<?php
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$presentationId = $_GET['id'] ?? '';
if (empty($presentationId)) {
    header('Location: slides.php');
    exit;
}

// Load presentation
$presentationsFile = __DIR__ . '/../data/presentations.json';
$presentations = file_exists($presentationsFile) ? json_decode(file_get_contents($presentationsFile), true) : [];

$presentation = null;
foreach ($presentations as $p) {
    if ($p['id'] === $presentationId) {
        $presentation = $p;
        break;
    }
}

if (!$presentation) {
    header('Location: slides.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($presentation['title']); ?> - Trình Chiếu</title>
    <link rel="stylesheet" href="../styles/slide-system.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- KaTeX for Math -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <style>
        body { margin: 0; overflow: hidden; font-family: Arial, sans-serif; }
        * { box-sizing: border-box; }
    </style>
</head>
<body>

<div class="slide-presenter-container">
    <div class="slide-presenter-main">
        <div class="slide-presenter-wrapper">
            <div class="slide-presenter-slide" id="currentSlide">
                <!-- Slide content will be rendered here -->
            </div>
        </div>
    </div>
    
    <div class="slide-presenter-controls">
        <div class="slide-presenter-nav">
            <button class="slide-presenter-btn" id="btnPrev" onclick="previousSlide()">
                <i class="bi bi-arrow-left"></i> Trước
            </button>
            <button class="slide-presenter-btn" id="btnNext" onclick="nextSlide()">
                Sau <i class="bi bi-arrow-right"></i>
            </button>
        </div>
        
        <div class="slide-presenter-progress">
            <div id="slideCounter">1 / <?php echo count($presentation['slides']); ?></div>
            <div class="slide-presenter-progress-bar">
                <div class="slide-presenter-progress-fill" id="progressBar"></div>
            </div>
        </div>
        
        <div class="slide-presenter-nav">
            <button class="slide-presenter-btn" onclick="toggleFullscreen()" title="Fullscreen (F11)">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <button class="slide-presenter-btn" onclick="exitPresentation()">
                <i class="bi bi-x-circle"></i> Thoát
            </button>
        </div>
    </div>
    
    <!-- Exit Button (Top Right) -->
    <button class="slide-presenter-exit-btn" onclick="exitPresentation()" title="Thoát (ESC)">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<script>
const presentationData = <?php echo json_encode($presentation); ?>;
let currentSlideIndex = 0;
const totalSlides = presentationData.slides.length;

// Initialize
renderSlide();
updateControls();
scalePresentation();

// Enter fullscreen automatically
requestFullscreen();

// Auto-scale on resize
window.addEventListener('resize', scalePresentation);

// Fullscreen API
function requestFullscreen() {
    const container = document.querySelector('.slide-presenter-container');
    if (container.requestFullscreen) {
        container.requestFullscreen().catch(err => {
            console.log('Fullscreen request failed:', err);
        });
    } else if (container.webkitRequestFullscreen) {
        container.webkitRequestFullscreen();
    } else if (container.msRequestFullscreen) {
        container.msRequestFullscreen();
    }
}

function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }
}

function toggleFullscreen() {
    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
        requestFullscreen();
    } else {
        exitFullscreen();
    }
}

// Auto-scale on resize
window.addEventListener('resize', scalePresentation);

function scalePresentation() {
    const wrapper = document.querySelector('.slide-presenter-wrapper');
    const mainContainer = document.querySelector('.slide-presenter-main');
    
    if (!wrapper || !mainContainer) return;
    
    const containerWidth = mainContainer.clientWidth;
    const containerHeight = mainContainer.clientHeight;
    
    // Calculate scale to fit while maintaining aspect ratio
    // Very small margin (10px total = 5px each side) to prevent sub-pixel overflow
    const scaleX = (containerWidth - 10) / 1920;
    const scaleY = (containerHeight - 10) / 1080;
    const scale = Math.min(scaleX, scaleY);
    
    // Scale the wrapper, not the slide itself
    // This preserves absolute positioning of child elements
    wrapper.style.transform = `scale(${scale})`;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') {
        e.preventDefault();
        nextSlide();
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        previousSlide();
    } else if (e.key === 'Escape') {
        e.preventDefault();
        exitPresentation();
    } else if (e.key === 'F11' || e.key === 'f') {
        e.preventDefault();
        toggleFullscreen();
    } else if (e.key === 'Home') {
        e.preventDefault();
        currentSlideIndex = 0;
        renderSlide();
        updateControls();
    } else if (e.key === 'End') {
        e.preventDefault();
        currentSlideIndex = totalSlides - 1;
        renderSlide();
        updateControls();
    }
});

function renderSlide() {
    const slideContainer = document.getElementById('currentSlide');
    const slide = presentationData.slides[currentSlideIndex];
    
    // Set background
    slideContainer.style.background = slide.background || '#ffffff';
    slideContainer.innerHTML = '';
    
    // Render elements
    slide.elements.forEach((element, index) => {
        const elem = createPresentationElement(element, index);
        slideContainer.appendChild(elem);
    });
    
    // Add slide transition
    slideContainer.style.opacity = '0';
    setTimeout(() => {
        slideContainer.style.opacity = '1';
        slideContainer.style.transition = 'opacity 0.3s ease';
        scalePresentation(); // Re-scale after render
    }, 10);
}

function createPresentationElement(element, index) {
    const elem = document.createElement('div');
    elem.style.position = 'absolute';
    
    // Apply position (exact from builder)
    elem.style.left = (element.position?.x || 50) + 'px';
    elem.style.top = (element.position?.y || 50) + 'px';
    
    // Apply size (preserve exact dimensions)
    if (element.size?.width) {
        elem.style.width = typeof element.size.width === 'number' ? element.size.width + 'px' : element.size.width;
    } else {
        elem.style.width = 'auto';
    }
    if (element.size?.height) {
        elem.style.height = typeof element.size.height === 'number' ? element.size.height + 'px' : element.size.height;
    } else {
        elem.style.height = 'auto';
    }
    
    // Apply styles
    if (element.style) {
        Object.assign(elem.style, element.style);
        
        // Enforce minimum font size for readability in presentation
        if (element.style.fontSize) {
            const fontSize = parseInt(element.style.fontSize);
            if (fontSize < 30) {
                elem.style.fontSize = '30px';
            }
        } else if (element.type === 'text' || element.type === 'heading') {
            // Default minimum for text elements
            elem.style.fontSize = '30px';
        }
    }
    
    // Apply animation
    if (element.animation && element.animation !== 'none') {
        elem.classList.add('animate-' + element.animation);
    }
    
    // Set content based on type
    switch (element.type) {
        case 'heading':
            const headingSize = element.style?.fontSize || '56px';
            elem.innerHTML = `<h1 style="margin:0; font-size: ${headingSize}; color: inherit; font-weight: bold;">${element.content || 'Tiêu đề'}</h1>`;
            break;
        case 'text':
            elem.innerHTML = element.content || 'Văn bản';
            elem.style.padding = '15px';
            if (!elem.style.fontSize || parseInt(elem.style.fontSize) < 30) {
                elem.style.fontSize = '30px';
            }
            break;
        case 'image':
            elem.innerHTML = element.content ? 
                `<img src="${element.content}" style="width: 100%; height: 100%; object-fit: contain;">` :
                '';
            break;
        case 'video':
            elem.innerHTML = element.content ? 
                `<iframe width="100%" height="100%" src="${getVideoEmbedUrl(element.content)}" frameborder="0" allowfullscreen></iframe>` :
                '';
            break;
        case 'audio':
            elem.innerHTML = element.content ?
                `<audio controls autoplay style="width: 100%;"><source src="${element.content}"></audio>` :
                '';
            break;
        case 'shape':
            const shapeType = element.shapeType || 'rectangle';
            elem.style.border = '2px solid ' + (element.style?.borderColor || '#333');
            elem.style.backgroundColor = element.style?.backgroundColor || 'transparent';
            if (shapeType === 'circle') {
                elem.style.borderRadius = '50%';
            }
            break;
        case 'math':
            try {
                elem.innerHTML = element.content ? 
                    katex.renderToString(element.content, { throwOnError: false, displayMode: true }) :
                    '';
            } catch (e) {
                elem.textContent = element.content || '';
            }
            elem.style.fontSize = element.style?.fontSize || '48px';
            elem.style.padding = '20px';
            break;
        case 'code':
            const codeSize = element.style?.fontSize || '24px';
            elem.innerHTML = `<pre style="margin:0; background: #2d2d2d; color: #f8f8f2; padding: 20px; border-radius: 8px; overflow-x: auto; font-size: ${codeSize};"><code>${escapeHtml(element.content || '')}</code></pre>`;
            break;
        default:
            elem.textContent = element.content || '';
    }
    
    return elem;
}

function getVideoEmbedUrl(url) {
    if (url.includes('youtube.com/watch')) {
        const videoId = new URL(url).searchParams.get('v');
        return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    } else if (url.includes('youtu.be/')) {
        const videoId = url.split('youtu.be/')[1].split('?')[0];
        return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }
    return url;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function nextSlide() {
    if (currentSlideIndex < totalSlides - 1) {
        currentSlideIndex++;
        renderSlide();
        updateControls();
    }
}

function previousSlide() {
    if (currentSlideIndex > 0) {
        currentSlideIndex--;
        renderSlide();
        updateControls();
    }
}

function updateControls() {
    // Update counter
    document.getElementById('slideCounter').textContent = 
        `${currentSlideIndex + 1} / ${totalSlides}`;
    
    // Update progress bar
    const progress = ((currentSlideIndex + 1) / totalSlides) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    
    // Update button states
    document.getElementById('btnPrev').disabled = currentSlideIndex === 0;
    document.getElementById('btnNext').disabled = currentSlideIndex === totalSlides - 1;
    
    if (currentSlideIndex === 0) {
        document.getElementById('btnPrev').style.opacity = '0.5';
    } else {
        document.getElementById('btnPrev').style.opacity = '1';
    }
    
    if (currentSlideIndex === totalSlides - 1) {
        document.getElementById('btnNext').style.opacity = '0.5';
    } else {
        document.getElementById('btnNext').style.opacity = '1';
    }
}

// Exit presentation function
function exitPresentation() {
    // Exit fullscreen first
    exitFullscreen();
    
    // Try to close window (works if opened by window.open)
    window.close();
    
    // If window.close() fails, navigate back
    setTimeout(() => {
        window.location.href = 'slides.php';
    }, 100);
}

// Prevent accidental navigation away
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '';
});
</script>

</body>
</html>
