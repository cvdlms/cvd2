<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$premiumFile = __DIR__ . '/../student_premium.json';
$requestsFile = __DIR__ . '/../student_premium_requests.json';

$premiumData = file_exists($premiumFile) ? json_decode(file_get_contents($premiumFile), true) : [];
$requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) : [];

switch ($action) {
    case 'approve':
        $index = $input['index'] ?? -1;
        
        // Debug logging
        error_log("Approve request - Index: $index, Total requests: " . count($requests));
        error_log("Request data: " . json_encode($requests));
        
        if ($index < 0 || !isset($requests[$index])) {
            error_log("Invalid index or request not found");
            echo json_encode(['success' => false, 'message' => 'Invalid request', 'debug' => ['index' => $index, 'total' => count($requests)]]);
            exit;
        }
        
        $request = $requests[$index];
        
        // Get package type (support both old and new format)
        $packageType = $request['package_type'] ?? $request['premium_type'] ?? 'month';
        
        // Calculate dates
        $startDate = date('Y-m-d');
        $days = [
            'month' => 30,
            'semester' => 120,
            'year' => 270
        ][$packageType] ?? 30;
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        // Add to premium data
        $premiumData[] = [
            'student_code' => $request['student_code'],
            'premium_type' => $packageType,
            'premium_status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'features' => [
                'unlimited_practice' => true,
                'unlimited_retakes' => true,
                'detailed_answers' => true,
                'advanced_statistics' => true
            ],
            'approved_by' => $_SESSION['username'],
            'approved_date' => date('Y-m-d H:i:s')
        ];
        
        // Update request status
        $requests[$index]['status'] = 'approved';
        $requests[$index]['approved_date'] = date('Y-m-d H:i:s');
        $requests[$index]['approved_by'] = $_SESSION['username'];
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'reject':
        $index = $input['index'] ?? -1;
        if ($index < 0 || !isset($requests[$index])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        $requests[$index]['status'] = 'rejected';
        $requests[$index]['rejected_date'] = date('Y-m-d H:i:s');
        $requests[$index]['rejected_by'] = $_SESSION['username'];
        
        file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'extend':
        $index = $input['index'] ?? -1;
        $days = intval($input['days'] ?? 0);
        
        if ($index < 0 || !isset($premiumData[$index]) || $days <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $currentEndDate = strtotime($premiumData[$index]['end_date']);
        $newEndDate = date('Y-m-d', strtotime("+{$days} days", $currentEndDate));
        $premiumData[$index]['end_date'] = $newEndDate;
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'cancel':
        $index = $input['index'] ?? -1;
        if ($index < 0 || !isset($premiumData[$index])) {
            echo json_encode(['success' => false, 'message' => 'Invalid premium']);
            exit;
        }
        
        $premiumData[$index]['premium_status'] = 'cancelled';
        $premiumData[$index]['cancelled_date'] = date('Y-m-d H:i:s');
        $premiumData[$index]['cancelled_by'] = $_SESSION['username'];
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'renew':
        $index = $input['index'] ?? -1;
        $type = $input['type'] ?? 'month';
        
        if ($index < 0 || !isset($premiumData[$index])) {
            echo json_encode(['success' => false, 'message' => 'Invalid premium']);
            exit;
        }
        
        $days = [
            'month' => 30,
            'semester' => 120,
            'year' => 270
        ][$type] ?? 30;
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $premiumData[$index]['premium_status'] = 'active';
        $premiumData[$index]['premium_type'] = $type;
        $premiumData[$index]['start_date'] = $startDate;
        $premiumData[$index]['end_date'] = $endDate;
        $premiumData[$index]['renewed_date'] = date('Y-m-d H:i:s');
        $premiumData[$index]['renewed_by'] = $_SESSION['username'];
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'add':
        $studentCode = $input['student_code'] ?? '';
        $premiumType = $input['premium_type'] ?? 'month';
        $startDate = $input['start_date'] ?? date('Y-m-d');
        
        if (!$studentCode) {
            echo json_encode(['success' => false, 'message' => 'Student code required']);
            exit;
        }
        
        // Check if student already has active premium
        foreach ($premiumData as $record) {
            if ($record['student_code'] === $studentCode && 
                $record['premium_status'] === 'active' && 
                strtotime($record['end_date']) >= time()) {
                echo json_encode(['success' => false, 'message' => 'Học sinh đã có Premium hoạt động']);
                exit;
            }
        }
        
        $days = [
            'month' => 30,
            'semester' => 120,
            'year' => 270
        ][$premiumType] ?? 30;
        
        $endDate = date('Y-m-d', strtotime("+{$days} days", strtotime($startDate)));
        
        $premiumData[] = [
            'student_code' => $studentCode,
            'premium_type' => $premiumType,
            'premium_status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'features' => [
                'unlimited_practice' => true,
                'unlimited_retakes' => true,
                'detailed_answers' => true,
                'advanced_statistics' => true
            ],
            'added_by' => $_SESSION['username'],
            'added_date' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        break;
    
    case 'add_class':
        $classId = $input['class_id'] ?? '';
        $premiumType = $input['premium_type'] ?? 'month';
        $startDate = $input['start_date'] ?? date('Y-m-d');
        $note = $input['note'] ?? '';
        
        if (!$classId) {
            echo json_encode(['success' => false, 'message' => 'Class ID required']);
            exit;
        }
        
        // Load students
        $studentsFile = __DIR__ . '/../students.json';
        $students = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
        
        // Filter students by class
        $classStudents = array_filter($students, function($s) use ($classId) {
            return $s['class_id'] === $classId;
        });
        
        if (empty($classStudents)) {
            echo json_encode(['success' => false, 'message' => 'Lớp không có học sinh nào']);
            exit;
        }
        
        // Calculate dates
        if ($premiumType === 'permanent') {
            $endDate = '2099-12-31';
            $days = 999999;
        } else {
            $days = [
                'month' => 30,
                'semester' => 120,
                'year' => 270
            ][$premiumType] ?? 30;
            $endDate = date('Y-m-d', strtotime("+{$days} days", strtotime($startDate)));
        }
        
        $count = 0;
        foreach ($classStudents as $student) {
            $studentCode = $student['code'] ?? $student['student_code'] ?? $student['student_id'] ?? null;
            if (!$studentCode) continue;
            
            // Check if already has active premium - skip or extend
            $hasActive = false;
            foreach ($premiumData as &$record) {
                if ($record['student_code'] === $studentCode && 
                    $record['premium_status'] === 'active' && 
                    strtotime($record['end_date']) >= time()) {
                    // Extend existing premium
                    $record['end_date'] = date('Y-m-d', strtotime("+{$days} days", strtotime($record['end_date'])));
                    $record['extended_by'] = $_SESSION['username'];
                    $record['extended_date'] = date('Y-m-d H:i:s');
                    $hasActive = true;
                    $count++;
                    break;
                }
            }
            
            if (!$hasActive) {
                // Add new premium
                $premiumData[] = [
                    'student_code' => $studentCode,
                    'premium_type' => $premiumType,
                    'premium_status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'features' => [
                        'unlimited_practice' => true,
                        'unlimited_retakes' => true,
                        'detailed_answers' => true,
                        'advanced_statistics' => true
                    ],
                    'added_by' => $_SESSION['username'],
                    'added_date' => date('Y-m-d H:i:s'),
                    'note' => $note,
                    'class_grant' => true
                ];
                $count++;
            }
        }
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'count' => $count]);
        break;
    
    case 'renew_class':
        $classId = $input['class_id'] ?? '';
        $days = intval($input['days'] ?? 0);
        $note = $input['note'] ?? '';
        
        if (!$classId || $days <= 0) {
            echo json_encode(['success' => false, 'message' => 'Class ID and valid days required']);
            exit;
        }
        
        // Load students
        $studentsFile = __DIR__ . '/../students.json';
        $students = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
        
        // Filter students by class
        $classStudents = array_filter($students, function($s) use ($classId) {
            return $s['class_id'] === $classId;
        });
        
        if (empty($classStudents)) {
            echo json_encode(['success' => false, 'message' => 'Lớp không có học sinh nào']);
            exit;
        }
        
        $count = 0;
        foreach ($classStudents as $student) {
            $studentCode = $student['code'] ?? $student['student_code'] ?? $student['student_id'] ?? null;
            if (!$studentCode) continue;
            
            // Find and extend active premium
            foreach ($premiumData as &$record) {
                if ($record['student_code'] === $studentCode && 
                    $record['premium_status'] === 'active' && 
                    strtotime($record['end_date']) >= time()) {
                    
                    // Extend the end date
                    $currentEndDate = strtotime($record['end_date']);
                    $newEndDate = date('Y-m-d', strtotime("+{$days} days", $currentEndDate));
                    $record['end_date'] = $newEndDate;
                    
                    // Add renewal metadata
                    $record['renewed_by'] = $_SESSION['username'];
                    $record['renewed_date'] = date('Y-m-d H:i:s');
                    if ($note) {
                        $record['renewal_note'] = $note;
                    }
                    $record['class_renewal'] = true;
                    
                    $count++;
                    break; // Only extend the first active premium found
                }
            }
        }
        
        if ($count === 0) {
            echo json_encode(['success' => false, 'message' => 'Không có học sinh nào có Premium hoạt động trong lớp này']);
            exit;
        }
        
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>