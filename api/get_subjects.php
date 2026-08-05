<?php
require_once __DIR__ . '/../includes/student_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'subjects' => [], 'message' => 'Not logged in']);
    exit;
}

$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjects = [];
if (file_exists($subjectsFile)) {
    $subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
    foreach ($subjectsData as $subject) {
        if (!empty($subject['id'])) {
            $subjects[] = [
                'id' => $subject['id'],
                'name' => $subject['name'],
                'code' => $subject['code'] ?? ''
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'subjects' => $subjects
]);
