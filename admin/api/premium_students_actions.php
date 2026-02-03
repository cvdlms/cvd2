<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Auto-detect base path
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('#^(/[^/]+)/admin/#', $requestUri, $matches)) {
    $basePath = $matches[1];
} else {
    $basePath = '/cvd2';
}

$studentsFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/students.json';
$classesFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/classes.json';
$premiumFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . '/admin/student_premium.json';

// Ensure premium file exists
if (!file_exists($premiumFile)) {
    file_put_contents($premiumFile, json_encode([]));
}

// Load data
$students = json_decode(file_get_contents($studentsFile), true) ?: [];
$classes = json_decode(file_get_contents($classesFile), true) ?: [];
$premiumData = json_decode(file_get_contents($premiumFile), true) ?: [];

// Create class lookup
$classLookup = [];
foreach ($classes as $class) {
    $classLookup[$class['id']] = $class['name'];
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'list') {
        $result = [];
        
        foreach ($students as $student) {
            $code = $student['code'];
            $premium = $premiumData[$code] ?? null;
            
            $isPremium = false;
            $premiumActive = false;
            $premiumExpiry = null;
            
            if ($premium) {
                $isPremium = true;
                
                if ($premium['expiry_date'] === 'permanent') {
                    $premiumActive = true;
                    $premiumExpiry = 'permanent';
                } else {
                    $expiryTime = strtotime($premium['expiry_date']);
                    $premiumActive = $expiryTime > time();
                    $premiumExpiry = $premium['expiry_date'];
                }
            }
            
            $result[] = [
                'code' => $code,
                'name' => $student['name'],
                'class_name' => $classLookup[$student['class_id']] ?? 'N/A',
                'is_premium' => $isPremium,
                'premium_active' => $premiumActive,
                'premium_expiry' => $premiumExpiry,
                'premium_granted_date' => $premium['granted_date'] ?? null
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'grant') {
        $studentCode = $input['student_code'] ?? '';
        $months = intval($input['months'] ?? 6);
        $note = $input['note'] ?? '';
        
        if (empty($studentCode)) {
            echo json_encode(['success' => false, 'message' => 'Missing student code']);
            exit;
        }
        
        // Calculate expiry date
        $grantedDate = date('Y-m-d H:i:s');
        
        if ($months === -1) {
            $expiryDate = 'permanent';
        } else {
            // If extending existing premium, add months from current expiry
            if (isset($premiumData[$studentCode])) {
                $currentExpiry = $premiumData[$studentCode]['expiry_date'];
                if ($currentExpiry !== 'permanent') {
                    $currentExpiryTime = strtotime($currentExpiry);
                    if ($currentExpiryTime > time()) {
                        // Extend from current expiry
                        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$months} months", $currentExpiryTime));
                    } else {
                        // Expired, start from now
                        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$months} months"));
                    }
                } else {
                    $expiryDate = 'permanent';
                }
            } else {
                // New premium
                $expiryDate = date('Y-m-d H:i:s', strtotime("+{$months} months"));
            }
        }
        
        // Update premium data
        $premiumData[$studentCode] = [
            'granted_date' => $grantedDate,
            'expiry_date' => $expiryDate,
            'months' => $months,
            'note' => $note,
            'granted_by' => 'admin'
        ];
        
        // Save
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'message' => 'Premium granted successfully']);
        exit;
    }
    
    if ($action === 'revoke') {
        $studentCode = $input['student_code'] ?? '';
        
        if (empty($studentCode)) {
            echo json_encode(['success' => false, 'message' => 'Missing student code']);
            exit;
        }
        
        // Remove premium
        unset($premiumData[$studentCode]);
        
        // Save
        file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'message' => 'Premium revoked successfully']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
