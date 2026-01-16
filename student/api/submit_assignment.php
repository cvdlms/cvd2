<?php
session_name('CVD_STUDENT_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check premium status
require_once __DIR__ . '/../../includes/student_premium_helper.php';
$studentCode = $_SESSION['student_code'];
$premiumStatus = getStudentPremiumStatus($studentCode);

if (!$premiumStatus['is_premium']) {
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho học sinh Premium']);
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$assignmentId = $_POST['assignment_id'] ?? '';
$content = $_POST['content'] ?? '';

if (empty($assignmentId) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify assignment exists and is for student's class
$assignmentsFile = __DIR__ . '/../../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;
foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId && $a['class_name'] === $studentClass) {
        $assignment = $a;
        break;
    }
}

if (!$assignment) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    exit;
}

// Check if already submitted
$submissionsFile = __DIR__ . '/../../data/student_submissions.json';
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
if (!is_array($submissions)) $submissions = [];

foreach ($submissions as $sub) {
    if ($sub['assignment_id'] === $assignmentId && $sub['student_code'] === $studentCode) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted this assignment']);
        exit;
    }
}

// Handle image uploads
$uploadedImages = [];
$uploadDir = __DIR__ . '/../../uploads/assignments/';

if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $fileCount = count($_FILES['images']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['images']['tmp_name'][$i];
            $fileName = $_FILES['images']['name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileType = $_FILES['images']['type'][$i];
            
            // Validate file
            if ($fileSize > 5 * 1024 * 1024) {
                continue; // Skip files > 5MB
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($fileType, $allowedTypes)) {
                continue; // Skip non-image files
            }
            
            // Generate unique filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid($studentCode . '_') . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedImages[] = 'uploads/assignments/' . $newFileName;
            }
        }
    }
}

// Create submission
$submission = [
    'id' => uniqid('sub_'),
    'assignment_id' => $assignmentId,
    'student_code' => $studentCode,
    'student_name' => $studentName,
    'student_class' => $studentClass,
    'content' => $content,
    'images' => $uploadedImages,
    'submitted_at' => date('Y-m-d H:i:s'),
    'score' => null,
    'feedback' => null,
    'graded_at' => null,
    'graded_by' => null
];

$submissions[] = $submission;

// Save submissions
file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode([
    'success' => true,
    'message' => 'Assignment submitted successfully',
    'submission_id' => $submission['id']
]);
?>
