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

if (empty($assignmentId)) {
    echo json_encode(['success' => false, 'message' => 'Missing assignment ID']);
    exit;
}

// Verify assignment exists and is for student's class
$assignmentsFile = __DIR__ . '/../../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;

function normalizeClassNames($assignment) {
    $raw = $assignment['class_names'] ?? $assignment['class_name'] ?? [];
    if (is_string($raw)) {
        $raw = [$raw];
    }
    $normalized = [];
    if (is_array($raw)) {
        foreach ($raw as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }
    }
    return array_values(array_unique($normalized));
}

foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId) {
        $classNames = normalizeClassNames($a);
        $myClass = trim(strtolower($studentClass));
        
        foreach ($classNames as $className) {
            if (trim(strtolower($className)) === $myClass) {
                $assignment = $a;
                break 2;
            }
        }
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
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

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
            $newFileName = uniqid($studentCode . '_img_') . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedImages[] = 'uploads/assignments/' . $newFileName;
            }
        }
    }
}

// Handle document uploads (Word, Excel, PDF, etc.)
$uploadedDocuments = [];
if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
    $fileCount = count($_FILES['documents']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['documents']['tmp_name'][$i];
            $fileName = $_FILES['documents']['name'][$i];
            $fileSize = $_FILES['documents']['size'][$i];
            
            // Validate file size
            if ($fileSize > 10 * 1024 * 1024) {
                continue; // Skip files > 10MB
            }
            
            // Validate file extension
            $allowedExtensions = ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                continue; // Skip unsupported files
            }
            
            // Generate unique filename
            $newFileName = uniqid($studentCode . '_doc_') . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedDocuments[] = [
                    'filename' => $fileName,
                    'path' => 'uploads/assignments/' . $newFileName,
                    'size' => $fileSize,
                    'extension' => $extension
                ];
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
    'documents' => $uploadedDocuments,
    'submitted_at' => date('Y-m-d H:i:s'),
    'score' => null,
    'feedback' => null,
    'graded_at' => null,
    'graded_by' => null
];

$submissions[] = $submission;

// Save submissions
file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Create notification for teacher
require_once __DIR__ . '/../../includes/notification_helper.php';
createTeacherNotification(
    $assignment['teacher_username'],
    'assignment_submission',
    'Học sinh nộp bài tập mới',
    $studentName . ' (' . $studentClass . ') đã nộp bài tập: ' . $assignment['title'],
    'view_submissions.php?id=' . $assignmentId,
    [
        'assignment_id' => $assignmentId,
        'student_code' => $studentCode,
        'student_name' => $studentName
    ]
);

echo json_encode([
    'success' => true,
    'message' => 'Assignment submitted successfully',
    'submission_id' => $submission['id']
]);
?>
