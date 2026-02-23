<?php
session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$dataFile = __DIR__ . '/../../data/lesson_plans.json';

// Ensure data file exists
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(new stdClass()));
}

// Load data
$lessonPlans = json_decode(file_get_contents($dataFile), true) ?: [];

// Get request data
$requestMethod = $_SERVER['REQUEST_METHOD'];
$input = null;

if ($requestMethod === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_GET;
}

$action = $input['action'] ?? '';

// Handle actions
switch ($action) {
    case 'list':
        handleList();
        break;
    
    case 'get':
        handleGet($input);
        break;
    
    case 'create':
        handleCreate($input);
        break;
    
    case 'update':
        handleUpdate($input);
        break;
    
    case 'delete':
        handleDelete($input);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleList() {
    global $lessonPlans, $username;
    
    // Load subjects for sharing check
    $subjectsFile = __DIR__ . '/../../admin/teacher_subjects.json';
    $teacherSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
    $mySubjects = $teacherSubjects[$username] ?? [];
    
    // Filter: own plans + shared plans with same subject
    $filteredPlans = [];
    foreach ($lessonPlans as $plan) {
        if ($plan['teacher_username'] === $username) {
            // Own plans
            $filteredPlans[] = $plan;
        } elseif ($plan['share_with_others'] && in_array($plan['subject_id'], $mySubjects)) {
            // Shared plans with same subject
            $filteredPlans[] = $plan;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => array_values($filteredPlans)
    ]);
}

function handleGet($input) {
    global $lessonPlans, $username;
    
    $id = $input['id'] ?? '';
    
    if (!isset($lessonPlans[$id])) {
        echo json_encode(['success' => false, 'message' => 'Lesson plan not found']);
        return;
    }
    
    $plan = $lessonPlans[$id];
    
    // Check permission
    $subjectsFile = __DIR__ . '/../../admin/teacher_subjects.json';
    $teacherSubjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
    $mySubjects = $teacherSubjects[$username] ?? [];
    
    $canView = ($plan['teacher_username'] === $username) || 
               ($plan['share_with_others'] && in_array($plan['subject_id'], $mySubjects));
    
    if (!$canView) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $plan
    ]);
}

function handleCreate($input) {
    global $lessonPlans, $username, $dataFile;
    
    // Generate ID
    $id = 'LP_' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Validate required fields
    if (empty($input['basic_info']['ten_bai_day']) || 
        empty($input['subject_id']) || 
        empty($input['class_ids'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Create plan
    $plan = [
        'id' => $id,
        'teacher_username' => $username,
        'subject_id' => $input['subject_id'],
        'class_ids' => $input['class_ids'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'basic_info' => [
            'ten_bai_day' => $input['basic_info']['ten_bai_day'],
            'so_tiet' => $input['basic_info']['so_tiet'],
            'tiet_ppct' => $input['basic_info']['tiet_ppct'] ?? '',
            'ngay_day' => $input['basic_info']['ngay_day']
        ],
        'muc_tieu' => [
            'kien_thuc' => $input['muc_tieu']['kien_thuc'] ?? '',
            'nang_luc' => $input['muc_tieu']['nang_luc'] ?? '',
            'nang_luc_so' => $input['muc_tieu']['nang_luc_so'] ?? '',
            'pham_chat' => $input['muc_tieu']['pham_chat'] ?? ''
        ],
        'thiet_bi' => $input['thiet_bi'] ?? '',
        'hoat_dong' => $input['hoat_dong'] ?? [],
        'huong_dan_ve_nha' => $input['huong_dan_ve_nha'] ?? '',
        'share_with_others' => $input['share_with_others'] ?? false
    ];
    
    // Save
    $lessonPlans[$id] = $plan;
    file_put_contents($dataFile, json_encode($lessonPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo kế hoạch bài dạy thành công!',
        'id' => $id
    ]);
}

function handleUpdate($input) {
    global $lessonPlans, $username, $dataFile;
    
    $id = $input['id'] ?? '';
    
    if (!isset($lessonPlans[$id])) {
        echo json_encode(['success' => false, 'message' => 'Lesson plan not found']);
        return;
    }
    
    // Check ownership
    if ($lessonPlans[$id]['teacher_username'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'You can only edit your own plans']);
        return;
    }
    
    // Update
    $lessonPlans[$id]['updated_at'] = date('Y-m-d H:i:s');
    $lessonPlans[$id]['subject_id'] = $input['subject_id'];
    $lessonPlans[$id]['class_ids'] = $input['class_ids'];
    $lessonPlans[$id]['basic_info'] = [
        'ten_bai_day' => $input['basic_info']['ten_bai_day'],
        'so_tiet' => $input['basic_info']['so_tiet'],
        'tiet_ppct' => $input['basic_info']['tiet_ppct'] ?? '',
        'ngay_day' => $input['basic_info']['ngay_day']
    ];
    $lessonPlans[$id]['muc_tieu'] = [
        'kien_thuc' => $input['muc_tieu']['kien_thuc'] ?? '',
        'nang_luc' => $input['muc_tieu']['nang_luc'] ?? '',
        'nang_luc_so' => $input['muc_tieu']['nang_luc_so'] ?? '',
        'pham_chat' => $input['muc_tieu']['pham_chat'] ?? ''
    ];
    $lessonPlans[$id]['thiet_bi'] = $input['thiet_bi'] ?? '';
    $lessonPlans[$id]['hoat_dong'] = $input['hoat_dong'] ?? [];
    $lessonPlans[$id]['huong_dan_ve_nha'] = $input['huong_dan_ve_nha'] ?? '';
    $lessonPlans[$id]['share_with_others'] = $input['share_with_others'] ?? false;
    
    // Save
    file_put_contents($dataFile, json_encode($lessonPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật kế hoạch bài dạy thành công!'
    ]);
}

function handleDelete($input) {
    global $lessonPlans, $username, $dataFile;
    
    $id = $input['id'] ?? '';
    
    if (!isset($lessonPlans[$id])) {
        echo json_encode(['success' => false, 'message' => 'Lesson plan not found']);
        return;
    }
    
    // Check ownership
    if ($lessonPlans[$id]['teacher_username'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own plans']);
        return;
    }
    
    // Delete
    unset($lessonPlans[$id]);
    file_put_contents($dataFile, json_encode($lessonPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa kế hoạch bài dạy thành công!'
    ]);
}
