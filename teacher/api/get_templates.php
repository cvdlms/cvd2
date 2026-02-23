<?php
/**
 * Get Templates List API - Updated with Categories
 */

header('Content-Type: application/json');

// Load templates from metadata file
$metadataFile = __DIR__ . '/../../data/html_templates_metadata.json';
$response = ['success' => false, 'templates' => [], 'categories' => []];

if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
    
    if ($metadata) {
        $response['success'] = true;
        $response['categories'] = $metadata['categories'] ?? [];
        $response['templates'] = $metadata['templates'] ?? [];
    }
}

// Fallback templates if file doesn't exist or is empty
if (empty($response['templates'])) {
    $response['success'] = true;
    $response['templates'] = [
        [
            'id' => 'blank',
            'name' => 'Blank Slide',
            'category' => 'basic',
            'description' => 'Slide trống để bắt đầu từ đầu',
            'icon' => '📄'
        ],
        [
            'id' => 'title',
            'name' => 'Title Slide',
            'category' => 'basic',
            'description' => 'Slide tiêu đề với heading lớn',
            'icon' => '📌'
        ],
        [
            'id' => 'content',
            'name' => 'Content Slide',
            'category' => 'basic',
            'description' => 'Slide nội dung với danh sách',
            'icon' => '📝'
        ]
    ];
}

echo json_encode($response);

