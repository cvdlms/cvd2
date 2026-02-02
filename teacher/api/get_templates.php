<?php
/**
 * Get Templates List API
 */

header('Content-Type: application/json');

// HTML Slide Templates
$templates = [
    [
        'id' => 'blank',
        'name' => 'Blank Slide',
        'description' => 'Slide trống để bắt đầu từ đầu',
        'icon' => '📄'
    ],
    [
        'id' => 'title',
        'name' => 'Title Slide',
        'description' => 'Slide tiêu đề với heading lớn',
        'icon' => '📌'
    ],
    [
        'id' => 'content',
        'name' => 'Content Slide',
        'description' => 'Slide nội dung với danh sách',
        'icon' => '📝'
    ],
    [
        'id' => 'two-columns',
        'name' => 'Two Columns',
        'description' => 'Layout 2 cột cân bằng',
        'icon' => '⚖️'
    ],
    [
        'id' => 'image-text',
        'name' => 'Image + Text',
        'description' => 'Hình ảnh bên trái, text bên phải',
        'icon' => '🖼️'
    ],
    [
        'id' => 'code',
        'name' => 'Code Slide',
        'description' => 'Hiển thị code với syntax highlighting',
        'icon' => '💻'
    ],
    [
        'id' => 'quote',
        'name' => 'Quote Slide',
        'description' => 'Trích dẫn hoặc câu nói nổi tiếng',
        'icon' => '💬'
    ],
    [
        'id' => 'full-image',
        'name' => 'Full Image',
        'description' => 'Hình ảnh toàn màn hình',
        'icon' => '🌄'
    ],
    [
        'id' => 'grid',
        'name' => 'Grid Layout',
        'description' => 'Bố cục lưới 2x2',
        'icon' => '⊞'
    ],
    [
        'id' => 'video',
        'name' => 'Video Slide',
        'description' => 'Embed video YouTube hoặc local',
        'icon' => '🎥'
    ],
    [
        'id' => 'interactive',
        'name' => 'Interactive Slide',
        'description' => 'Slide tương tác với JavaScript',
        'icon' => '🎮'
    ],
    [
        'id' => 'timeline',
        'name' => 'Timeline',
        'description' => 'Dòng thời gian sự kiện',
        'icon' => '📅'
    ]
];

echo json_encode([
    'success' => true,
    'templates' => $templates
]);
