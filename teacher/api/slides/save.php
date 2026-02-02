<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../includes/PresentationStorage.php';

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? 'Bài Giảng Mới';
$description = $input['description'] ?? '';
$subjectId = $input['subject_id'] ?? '';
$slides = $input['slides'] ?? [];
$settings = $input['settings'] ?? [];
$tags = $input['tags'] ?? [];

try {
    // Generate ID
    $presentationId = 'pres_' . uniqid();
    
    // Create presentation object
    $presentation = [
        'id' => $presentationId,
        'title' => $title,
        'description' => $description,
        'teacher_username' => $username,
        'subject_id' => $subjectId,
        'class_names' => [],
        'grade' => '',
        'thumbnail' => '',
        'source' => 'template',
        'settings' => array_merge([
            'theme' => 'modern-blue',
            'font_family' => 'Arial',
            'transition' => 'slide',
            'auto_play' => false,
            'show_progress' => true
        ], $settings),
        'slides' => $slides,
        'tags' => $tags,
        'is_published' => false,
        'is_shared' => false,
        'visibility' => 'private',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'statistics' => [
            'total_views' => 0,
            'unique_viewers' => 0,
            'total_presentations' => 0,
            'avg_completion_rate' => 0,
            'avg_time_spent' => 0
        ]
    ];
    
    // Save using new storage
    $storage = new PresentationStorage();
    $storage->save($presentation);
    
    echo json_encode([
        'success' => true,
        'message' => 'Presentation created successfully',
        'presentation_id' => $presentationId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
