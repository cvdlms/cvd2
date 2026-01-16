<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'recommendations' => []]);
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentClassCode = $_SESSION['student_class_code'] ?? '';

// Determine grade from class code
$prefix = substr($studentClassCode, 0, 1);
$grade = 'khoi' . $prefix;

// Load system config for semester
$configFile = __DIR__ . '/../admin/system_config.json';
$config = json_decode(file_get_contents($configFile), true);
$semester = $config['semester']['current'] ?? 'hk1';

require_once __DIR__ . '/../includes/recommendation_engine.php';

try {
    $engine = new RecommendationEngine($studentCode, $grade, $semester);
    $recommendations = $engine->generateRecommendations();
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'student_code' => $studentCode,
        'grade' => $grade
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'recommendations' => []
    ]);
}
?>