<?php
header('Content-Type: application/json');

try {
    $subjectsFile = __DIR__ . '/../subjects.json';
    
    if (!file_exists($subjectsFile)) {
        echo json_encode(['success' => false, 'message' => 'File subjects.json không tồn tại']);
        exit;
    }
    
    $subjects = json_decode(file_get_contents($subjectsFile), true);
    
    if ($subjects === null) {
        echo json_encode(['success' => false, 'message' => 'Không thể đọc file subjects.json']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $subjects]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
