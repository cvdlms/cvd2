<?php
/**
 * Presentation Viewer - Xem presentation với nhiều slides
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$presentationId = $_GET['id'] ?? '';

if (!$presentationId) {
    die('Presentation ID is required');
}

// Load presentations
$metadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
$presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

if (!isset($presentations[$presentationId])) {
    die('Presentation not found');
}

$presentation = $presentations[$presentationId];

// Load slide contents
foreach ($presentation['slides'] as $index => $slide) {
    if (isset($slide['file_path'])) {
        $filePath = __DIR__ . '/../' . $slide['file_path'];
        if (file_exists($filePath)) {
            $presentation['slides'][$index]['content'] = file_get_contents($filePath);
        } else {
            $presentation['slides'][$index]['content'] = '<html><body><h1>File not found: ' . htmlspecialchars($slide['file_path']) . '</h1></body></html>';
        }
    } else {
        $presentation['slides'][$index]['content'] = '<html><body><h1>No file path for slide</h1></body></html>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($presentation['title']); ?> - Presentation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #000;
            overflow: hidden;
        }

        .presentation-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .slide-view {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .slide-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            background: white;
            display: none;
        }

        .slide-frame.active {
            display: block;
        }

        .controls {
            background: rgba(0, 0, 0, 0.9);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .controls-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .slide-counter {
            font-size: 16px;
            color: #fff;
            min-width: 100px;
        }

        .btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .btn-primary {
            background: #667eea;
            border-color: #667eea;
        }

        .btn-primary:hover {
            background: #5568d3;
            border-color: #5568d3;
        }

        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .presentation-title {
            font-size: 18px;
            font-weight: 600;
        }

        .thumbnail-strip {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            display: none;
            background: rgba(0, 0, 0, 0.9);
            padding: 10px;
            border-radius: 8px;
            max-width: 90vw;
            overflow-x: auto;
        }

        .thumbnail-strip.show {
            display: flex;
        }

        .thumbnail-list {
            display: flex;
            gap: 10px;
        }

        .thumbnail {
            width: 120px;
            height: 80px;
            border: 2px solid transparent;
            cursor: pointer;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }

        .thumbnail.active {
            border-color: #667eea;
        }

        .thumbnail:hover {
            border-color: #888;
        }

        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 999;
            display: none;
        }

        .fullscreen-overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .controls {
                padding: 10px 15px;
            }

            .presentation-title {
                font-size: 14px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="presentation-container">
        <!-- Slide View -->
        <div class="slide-view">
            <?php foreach ($presentation['slides'] as $index => $slide): ?>
                <iframe 
                    class="slide-frame <?php echo $index === 0 ? 'active' : ''; ?>" 
                    id="slide-<?php echo $index; ?>"
                    data-slide-index="<?php echo $index; ?>"
                ></iframe>
            <?php endforeach; ?>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="controls-left">
                <span class="presentation-title">
                    <i class="fas fa-presentation"></i>
                    <?php echo htmlspecialchars($presentation['title']); ?>
                </span>
                <span class="slide-counter" id="slideCounter">
                    Slide 1 / <?php echo count($presentation['slides']); ?>
                </span>
            </div>

            <div class="controls-right" style="display: flex; gap: 10px;">
                <button class="btn" onclick="previousSlide()" id="btnPrev">
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
                <button class="btn" onclick="nextSlide()" id="btnNext">
                    Tiếp <i class="fas fa-chevron-right"></i>
                </button>
                <button class="btn" onclick="toggleThumbnails()">
                    <i class="fas fa-th"></i> Slides
                </button>
                <button class="btn" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i> Toàn màn hình
                </button>
                <a href="slides.php" class="btn btn-primary">
                    <i class="fas fa-times"></i> Đóng
                </a>
            </div>
        </div>

        <!-- Thumbnail Strip -->
        <div class="thumbnail-strip" id="thumbnailStrip">
            <div class="thumbnail-list">
                <?php foreach ($presentation['slides'] as $index => $slide): ?>
                    <div 
                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                        onclick="goToSlide(<?php echo $index; ?>)"
                        id="thumb-<?php echo $index; ?>"
                    >
                        Slide <?php echo $index + 1; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        let currentSlide = 0;
        const totalSlides = <?php echo count($presentation['slides']); ?>;
        
        // Slides content
        const slidesContent = <?php 
            echo json_encode(
                array_values(array_map(function($slide) {
                    return $slide['content'] ?? '';
                }, $presentation['slides'])), 
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ); 
        ?>;

        // Debug logging
        console.log('Total slides:', totalSlides);
        console.log('Slides content array:', slidesContent);
        slidesContent.forEach((content, idx) => {
            console.log(`Slide ${idx + 1} length:`, content.length);
            console.log(`Slide ${idx + 1} first 100 chars:`, content.substring(0, 100));
        });

        function loadSlideContent(index) {
            const iframe = document.getElementById('slide-' + index);
            if (iframe && slidesContent[index] && !iframe.hasAttribute('data-loaded')) {
                console.log(`Loading slide ${index + 1}, content length: ${slidesContent[index].length}`);
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(slidesContent[index]);
                iframeDoc.close();
                iframe.setAttribute('data-loaded', 'true');
                console.log(`Slide ${index + 1} loaded successfully`);
            } else {
                console.log(`Slide ${index + 1} already loaded or no content`);
            }
        }

        function showSlide(index) {
            // Load content for current, next, and previous slides (preload)
            loadSlideContent(index);
            if (index > 0) loadSlideContent(index - 1);
            if (index < totalSlides - 1) loadSlideContent(index + 1);
            
            // Hide all slides
            document.querySelectorAll('.slide-frame').forEach(frame => {
                frame.classList.remove('active');
            });

            // Show current slide
            document.getElementById('slide-' + index).classList.add('active');

            // Update counter
            document.getElementById('slideCounter').textContent = `Slide ${index + 1} / ${totalSlides}`;

            // Update thumbnails
            document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });

            // Update button states
            document.getElementById('btnPrev').disabled = index === 0;
            document.getElementById('btnNext').disabled = index === totalSlides - 1;

            currentSlide = index;
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                showSlide(currentSlide + 1);
            }
        }

        function previousSlide() {
            if (currentSlide > 0) {
                showSlide(currentSlide - 1);
            }
        }

        function goToSlide(index) {
            showSlide(index);
        }

        function toggleThumbnails() {
            document.getElementById('thumbnailStrip').classList.toggle('show');
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case 'ArrowRight':
                case ' ':
                case 'PageDown':
                    nextSlide();
                    e.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'PageUp':
                    previousSlide();
                    e.preventDefault();
                    break;
                case 'Home':
                    showSlide(0);
                    e.preventDefault();
                    break;
                case 'End':
                    showSlide(totalSlides - 1);
                    e.preventDefault();
                    break;
                case 'Escape':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    }
                    break;
            }
        });

        // Initialize
        showSlide(0);
    </script>
</body>
</html>
