<?php
/**
 * Slide Present - Present slides with Swiper transitions
 */

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];

// Load presentation metadata
$presentationId = $_GET['id'] ?? '';
$metadataFile = __DIR__ . '/../data/html_presentations_metadata.json';
$presentations = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];

// Verify presentation exists and belongs to user
if (!$presentationId || !isset($presentations[$presentationId]) || $presentations[$presentationId]['teacher_username'] !== $username) {
    die('Presentation không tồn tại hoặc bạn không có quyền truy cập!');
}

$presentation = $presentations[$presentationId];

// Load slide contents
if (isset($presentation['slides'])) {
    foreach ($presentation['slides'] as &$slide) {
        if (isset($slide['file_path'])) {
            $filePath = __DIR__ . '/../' . $slide['file_path'];
            if (file_exists($filePath)) {
                $slide['content'] = file_get_contents($filePath);
            } else {
                $slide['content'] = '<h1>Slide không tìm thấy</h1>';
            }
        }
    }
    unset($slide);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($presentation['title'] ?? 'Presentation'); ?> - Present Mode</title>
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            overflow: hidden;
        }

        .viewer-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        .swiper {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            transition: opacity 0.5s ease-in-out;
        }

        .swiper-slide iframe {
            width: 100%;
            height: 100%;
            border: none;
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
        }

        /* Navigation Buttons */
        .swiper-button-prev,
        .swiper-button-next {
            color: rgba(255, 255, 255, 0.8);
            background: rgba(0, 0, 0, 0.3);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .swiper-button-prev:hover,
        .swiper-button-next:hover {
            background: rgba(0, 0, 0, 0.6);
            color: white;
        }

        .swiper-button-prev::after,
        .swiper-button-next::after {
            font-size: 20px;
        }

        /* Pagination */
        .swiper-pagination {
            bottom: 80px !important;
        }

        .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        .swiper-pagination-bullet-active {
            background: #007acc;
            width: 30px;
            border-radius: 6px;
        }

        /* Control Bar */
        .control-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .viewer-container:hover .control-bar {
            opacity: 1;
        }

        .control-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .slide-counter {
            color: white;
            font-size: 14px;
            padding: 0 10px;
            font-weight: 500;
        }

        .exit-btn {
            background: #d32f2f;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .exit-btn:hover {
            background: #f44336;
        }

        /* Fullscreen styles */
        .viewer-container:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
        }

        .viewer-container:-moz-full-screen {
            width: 100vw;
            height: 100vh;
        }

        .viewer-container:fullscreen {
            width: 100vw;
            height: 100vh;
        }

    </style>
</head>
<body>
    <div class="viewer-container">
        <!-- Swiper -->
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php foreach ($presentation['slides'] as $index => $slide): ?>
                <div class="swiper-slide" data-transition="<?php echo htmlspecialchars($slide['transition'] ?? 'none'); ?>">
                    <iframe srcdoc="<?php echo htmlspecialchars($slide['content']); ?>"></iframe>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Navigation -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
            
            <!-- Pagination -->
            <div class="swiper-pagination"></div>
        </div>

        <!-- Control Bar -->
        <div class="control-bar">
            <button class="control-btn" onclick="prevSlide()" title="Previous (←)">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <span class="slide-counter">
                <span id="currentSlide">1</span> / <span id="totalSlides"><?php echo count($presentation['slides']); ?></span>
            </span>
            
            <button class="control-btn" onclick="nextSlide()" title="Next (→)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.3);"></div>
            
            <button class="control-btn" onclick="toggleFullscreen()" title="Fullscreen (F)">
                <i class="fas fa-expand"></i>
            </button>
            
            <button class="control-btn exit-btn" onclick="exitPresentation()" title="Exit (ESC)">
                <i class="fas fa-times"></i> Thoát
            </button>
        </div>
    </div>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        let swiper;

        // Initialize Swiper with dynamic effects
        function initSwiper() {
            // Determine primary transition effect
            const slides = document.querySelectorAll('.swiper-slide');
            const transitions = Array.from(slides).map(s => s.dataset.transition);
            const primaryTransition = transitions.find(t => t && t !== 'none') || 'slide';

            // Map transitions to Swiper effects
            const effectMap = {
                'fade': 'fade',
                'slide': 'slide',
                'cube': 'cube',
                'flip': 'flip',
                'coverflow': 'coverflow',
                'zoom': 'creative',
                'none': 'slide'
            };

            const swiperEffect = effectMap[primaryTransition] || 'slide';

            swiper = new Swiper('.swiper', {
                effect: swiperEffect,
                speed: 500,
                
                // Cube effect (simplified)
                cubeEffect: {
                    shadow: false,
                    slideShadows: false,
                },
                
                // Flip effect (simplified)
                flipEffect: {
                    slideShadows: false,
                    limitRotation: true,
                },
                
                // Coverflow effect (simplified)
                coverflowEffect: {
                    rotate: 30,
                    stretch: 0,
                    depth: 80,
                    modifier: 1,
                    slideShadows: false,
                },
                
                // Creative effect for zoom (simplified)
                creativeEffect: {
                    prev: {
                        translate: ['-100%', 0, -200],
                        opacity: 0,
                    },
                    next: {
                        translate: ['100%', 0, -200],
                        opacity: 0,
                    },
                },
                
                // Fade effect
                fadeEffect: {
                    crossFade: true
                },
                
                // Navigation
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                
                // Pagination
                pagination: {
                    el: '.swiper-pagination',
                    type: 'bullets',
                    clickable: true,
                },
                
                // Keyboard
                keyboard: {
                    enabled: true,
                    onlyInViewport: false,
                },
                
                // Mouse wheel
                mousewheel: {
                    enabled: true,
                    forceToAxis: true,
                },
                
                // Events
                on: {
                    init: function() {
                        updateSlideCounter();
                    },
                    slideChange: function() {
                        updateSlideCounter();
                    }
                }
            });
        }

        function updateSlideCounter() {
            if (swiper) {
                document.getElementById('currentSlide').textContent = swiper.activeIndex + 1;
            }
        }

        function prevSlide() {
            if (swiper) {
                swiper.slidePrev();
            }
        }

        function nextSlide() {
            if (swiper) {
                swiper.slideNext();
            }
        }

        function toggleFullscreen() {
            const container = document.querySelector('.viewer-container');
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        function exitPresentation() {
            window.location.href = 'slide_builder.php?id=<?php echo $presentationId; ?>';
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    exitPresentation();
                }
            } else if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            }
        });

        // Initialize on DOM ready (faster than window.load)
        document.addEventListener('DOMContentLoaded', () => {
            initSwiper();
        });
    </script>
</body>
</html>
