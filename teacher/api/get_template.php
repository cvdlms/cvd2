<?php
/**
 * Get Template Content API - Updated to load from files
 */

header('Content-Type: application/json');

$templateId = $_GET['id'] ?? '';

if (empty($templateId)) {
    echo json_encode(['success' => false, 'message' => 'No template ID']);
    exit;
}

// Try loading from metadata first
$metadataFile = __DIR__ . '/../../data/html_templates_metadata.json';
if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
    
    if ($metadata && isset($metadata['templates'])) {
        foreach ($metadata['templates'] as $template) {
            if ($template['id'] === $templateId) {
                $templatePath = __DIR__ . '/../../' . $template['file_path'];
                
                if (file_exists($templatePath)) {
                    $content = file_get_contents($templatePath);
                    echo json_encode([
                        'success' => true,
                        'content' => $content,
                        'template' => $template
                    ]);
                    exit;
                }
            }
        }
    }
}

// Fallback to built-in templates
// Template contents - Ready to use HTML slides
$templates = [
    'blank' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .slide {
            max-width: 1200px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="slide">
        <h1>Bắt đầu tạo slide của bạn...</h1>
    </div>
</body>
</html>',

    'title' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Title Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .slide {
            text-align: center;
            color: white;
        }
        h1 {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        p {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            p { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h1>Tiêu Đề Chính</h1>
        <p>Phụ đề hoặc mô tả của bài giảng</p>
    </div>
</body>
</html>',

    'content' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            padding: 60px;
        }
        .slide {
            max-width: 1200px;
            margin: 0 auto;
        }
        h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 30px;
            border-bottom: 4px solid #2563eb;
            padding-bottom: 15px;
        }
        ul {
            list-style: none;
        }
        li {
            font-size: 1.5rem;
            padding: 15px 0;
            padding-left: 40px;
            position: relative;
            color: #334155;
        }
        li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #2563eb;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            body { padding: 30px; }
            h2 { font-size: 2rem; }
            li { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Nội Dung Chính</h2>
        <ul>
            <li>Điểm quan trọng số 1</li>
            <li>Điểm quan trọng số 2</li>
            <li>Điểm quan trọng số 3</li>
            <li>Điểm quan trọng số 4</li>
        </ul>
    </div>
</body>
</html>',

    'two-columns' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two Columns</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 60px;
        }
        .slide {
            max-width: 1400px;
            margin: 0 auto;
        }
        h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-align: center;
        }
        .columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .column {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .column h3 {
            color: #2563eb;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        .column p {
            color: #64748b;
            font-size: 1.2rem;
            line-height: 1.8;
        }
        @media (max-width: 1024px) {
            .columns { grid-template-columns: 1fr; }
            body { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>So Sánh Hai Khái Niệm</h2>
        <div class="columns">
            <div class="column">
                <h3>Cột Trái</h3>
                <p>Nội dung cho phần bên trái. Mô tả chi tiết về khái niệm, phương pháp hoặc đặc điểm.</p>
            </div>
            <div class="column">
                <h3>Cột Phải</h3>
                <p>Nội dung cho phần bên phải. So sánh hoặc đối chiếu với phần bên trái.</p>
            </div>
        </div>
    </div>
</body>
</html>',

    'image-text' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image + Text</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            padding: 60px;
        }
        .slide {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .image-container {
            background: linear-gradient(135deg, #667eea, #764ba2);
            aspect-ratio: 16/9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        .text-content h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .text-content p {
            color: #64748b;
            font-size: 1.3rem;
            line-height: 1.8;
        }
        @media (max-width: 1024px) {
            .slide { grid-template-columns: 1fr; }
            body { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <div class="image-container">
            🖼️
            <!-- Thay bằng: <img src="your-image.jpg" alt="Description"> -->
        </div>
        <div class="text-content">
            <h2>Tiêu Đề Slide</h2>
            <p>Mô tả chi tiết về hình ảnh hoặc nội dung liên quan. Giải thích ý nghĩa, tầm quan trọng và ứng dụng thực tế.</p>
        </div>
    </div>
</body>
</html>',

    'code' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Slide</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #1e293b;
            padding: 60px;
        }
        .slide {
            max-width: 1400px;
            margin: 0 auto;
        }
        h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        pre {
            margin: 0;
            border-radius: 12px;
            overflow: hidden;
        }
        code {
            font-size: 1.3rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Ví Dụ Code</h2>
        <pre><code class="language-javascript">// Hàm tính giai thừa
function factorial(n) {
    if (n === 0 || n === 1) {
        return 1;
    }
    return n * factorial(n - 1);
}

// Sử dụng
console.log(factorial(5)); // 120</code></pre>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</body>
</html>',

    'quote' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Slide</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Georgia", serif;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }
        .slide {
            max-width: 900px;
            text-align: center;
            color: white;
        }
        .quote-icon {
            font-size: 4rem;
            opacity: 0.5;
            margin-bottom: 30px;
        }
        blockquote {
            font-size: 2.5rem;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .author {
            font-size: 1.5rem;
            opacity: 0.8;
            font-style: normal;
        }
        @media (max-width: 768px) {
            blockquote { font-size: 1.8rem; }
            .author { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <div class="quote-icon">
            <i class="fas fa-quote-left"></i>
        </div>
        <blockquote>
            "Học tập không phải là sự chuẩn bị cho cuộc sống, học tập chính là cuộc sống."
        </blockquote>
        <p class="author">— John Dewey</p>
    </div>
</body>
</html>',

    'full-image' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Image Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .slide {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .slide img {
            width: 100%;
            height: 100vh;
            object-fit: cover;
        }
        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 60px;
            color: white;
        }
        h2 {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        p {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        .placeholder {
            font-size: 10rem;
        }
        @media (max-width: 768px) {
            h2 { font-size: 2rem; }
            p { font-size: 1.2rem; }
            .overlay { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <div class="placeholder">🖼️</div>
        <!-- Thay bằng: <img src="your-image.jpg" alt="Description"> -->
        <div class="overlay">
            <h2>Tiêu Đề Hình Ảnh</h2>
            <p>Mô tả ngắn gọn về hình ảnh</p>
        </div>
    </div>
</body>
</html>',

    'grid' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grid Layout</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 60px;
        }
        .slide {
            max-width: 1400px;
            margin: 0 auto;
        }
        h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-align: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        .grid-item {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .grid-item h3 {
            color: #2563eb;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .grid-item p {
            color: #64748b;
            font-size: 1.2rem;
        }
        .grid-item:nth-child(1) { border-top: 4px solid #f59e0b; }
        .grid-item:nth-child(2) { border-top: 4px solid #ec4899; }
        .grid-item:nth-child(3) { border-top: 4px solid #8b5cf6; }
        .grid-item:nth-child(4) { border-top: 4px solid #14b8a6; }
        @media (max-width: 1024px) {
            .grid { grid-template-columns: 1fr; }
            body { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Bố Cục Lưới</h2>
        <div class="grid">
            <div class="grid-item">
                <h3>Mục 1</h3>
                <p>Nội dung cho mục số 1</p>
            </div>
            <div class="grid-item">
                <h3>Mục 2</h3>
                <p>Nội dung cho mục số 2</p>
            </div>
            <div class="grid-item">
                <h3>Mục 3</h3>
                <p>Nội dung cho mục số 3</p>
            </div>
            <div class="grid-item">
                <h3>Mục 4</h3>
                <p>Nội dung cho mục số 4</p>
            </div>
        </div>
    </div>
</body>
</html>',

    'video' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #1e293b;
            padding: 60px;
        }
        .slide {
            max-width: 1400px;
            margin: 0 auto;
        }
        h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        @media (max-width: 768px) {
            body { padding: 30px; }
            h2 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Video Bài Giảng</h2>
        <div class="video-container">
            <!-- YouTube Video Embed -->
            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                    allowfullscreen></iframe>
            <!-- Thay YOUR_VIDEO_ID bằng ID video YouTube của bạn -->
        </div>
    </div>
</body>
</html>',

    'interactive' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Slide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .slide {
            background: white;
            padding: 60px;
            border-radius: 20px;
            max-width: 800px;
            text-align: center;
        }
        h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        #result {
            margin-top: 30px;
            font-size: 3rem;
            color: #2563eb;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Click để tạo số ngẫu nhiên!</h2>
        <button onclick="generateRandom()">🎲 Tạo Số Ngẫu Nhiên</button>
        <div id="result"></div>
    </div>
    <script>
        function generateRandom() {
            const num = Math.floor(Math.random() * 100) + 1;
            document.getElementById("result").textContent = num;
        }
    </script>
</body>
</html>',

    'timeline' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 60px;
        }
        .slide {
            max-width: 1000px;
            margin: 0 auto;
        }
        h2 {
            color: #2563eb;
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-align: center;
        }
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #2563eb;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            padding-left: 40px;
        }
        .timeline-item:before {
            content: "";
            position: absolute;
            left: -10px;
            top: 5px;
            width: 20px;
            height: 20px;
            background: white;
            border: 4px solid #2563eb;
            border-radius: 50%;
        }
        .timeline-date {
            color: #667eea;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .timeline-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .timeline-content h3 {
            color: #1e293b;
            margin-bottom: 10px;
        }
        .timeline-content p {
            color: #64748b;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            body { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="slide">
        <h2>Dòng Thời Gian</h2>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-date">2020</div>
                <div class="timeline-content">
                    <h3>Sự kiện 1</h3>
                    <p>Mô tả chi tiết về sự kiện đầu tiên</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-date">2021</div>
                <div class="timeline-content">
                    <h3>Sự kiện 2</h3>
                    <p>Mô tả chi tiết về sự kiện thứ hai</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-date">2022</div>
                <div class="timeline-content">
                    <h3>Sự kiện 3</h3>
                    <p>Mô tả chi tiết về sự kiện thứ ba</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>'
];

if (!isset($templates[$templateId])) {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'content' => $templates[$templateId]
]);
