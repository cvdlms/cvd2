<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? 'Bài Giảng Mới';
$description = $input['description'] ?? '';
$subjectId = $input['subject_id'] ?? '';
$slides = $input['slides'] ?? [];
$settings = $input['settings'] ?? [];
$tags = $input['tags'] ?? [];

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

// Load existing presentations
$presentationsFile = __DIR__ . '/../../../data/presentations.json';
$presentations = file_exists($presentationsFile) ? json_decode(file_get_contents($presentationsFile), true) : [];
if (!is_array($presentations)) $presentations = [];

// Add new presentation
$presentations[] = $presentation;

// Save
if (file_put_contents($presentationsFile, json_encode($presentations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => 'Presentation created successfully',
        'presentation_id' => $presentationId
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save presentation'
    ]);
}
?>
