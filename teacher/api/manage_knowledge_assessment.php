<?php
session_name('CVD_TEACHER_SESSION');
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$dataFile = __DIR__ . '/../../data/knowledge_assessments.json';

// Ensure data directory exists
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Load existing data
$assessments = [];
if (file_exists($dataFile)) {
    $assessments = json_decode(file_get_contents($dataFile), true) ?: [];
}

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Load assessment
    $subjectId = $_GET['subject_id'] ?? '';
    $grade = $_GET['grade'] ?? '';
    
    if (!$subjectId || !$grade) {
        echo json_encode(['success' => false, 'message' => 'Missing subject_id or grade']);
        exit;
    }
    
    // Find assessment for this subject + grade + teacher
    $found = null;
    foreach ($assessments as $assessment) {
        if ($assessment['subject_id'] == $subjectId && 
            $assessment['grade'] === $grade && 
            $assessment['teacher_username'] === $username) {
            $found = $assessment;
            break;
        }
    }
    
    if ($found) {
        echo json_encode(['success' => true, 'data' => $found]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assessment not found']);
    }
    exit;
}

if ($method === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    
    if ($action === 'save') {
        // Save or update assessment
        $subjectId = $input['subject_id'] ?? '';
        $grade = $input['grade'] ?? '';
        $items = $input['items'] ?? [];
        $id = $input['id'] ?? null;
        
        if (!$subjectId || !$grade || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Validate items
        foreach ($items as $item) {
            if (empty($item['content'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid item data - missing content']);
                exit;
            }
            
            // Check units array
            if (empty($item['units']) || !is_array($item['units'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid item data - missing units array']);
                exit;
            }
            
            // Validate each unit
            foreach ($item['units'] as $unit) {
                if (empty($unit['unit_name'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid unit data - missing unit_name']);
                    exit;
                }
                
                // At least one assessment level must be filled for each unit
                $hasLevel = !empty($unit['nhan_biet']) || !empty($unit['thong_hieu']) || !empty($unit['van_dung']);
                if (!$hasLevel) {
                    echo json_encode(['success' => false, 'message' => 'Invalid unit data - at least one assessment level required']);
                    exit;
                }
            }
        }
        
        if ($id) {
            // Update existing assessment
            $updated = false;
            foreach ($assessments as &$assessment) {
                if ($assessment['id'] === $id && $assessment['teacher_username'] === $username) {
                    $assessment['subject_id'] = $subjectId;
                    $assessment['grade'] = $grade;
                    $assessment['items'] = $items;
                    $assessment['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }
            
            if (!$updated) {
                echo json_encode(['success' => false, 'message' => 'Assessment not found']);
                exit;
            }
            
            $resultId = $id;
        } else {
            // Create new assessment
            $newId = uniqid('assessment_', true);
            
            $newAssessment = [
                'id' => $newId,
                'subject_id' => $subjectId,
                'grade' => $grade,
                'teacher_username' => $username,
                'items' => $items,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $assessments[] = $newAssessment;
            $resultId = $newId;
        }
        
        // Save to file
        if (file_put_contents($dataFile, json_encode($assessments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'id' => $resultId, 'message' => 'Assessment saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        }
        exit;
    }
    
    if ($action === 'delete') {
        // Delete assessment
        $id = $input['id'] ?? '';
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing assessment ID']);
            exit;
        }
        
        // Filter out the assessment
        $originalCount = count($assessments);
        $assessments = array_values(array_filter($assessments, function($assessment) use ($id, $username) {
            return !($assessment['id'] === $id && $assessment['teacher_username'] === $username);
        }));
        
        if (count($assessments) === $originalCount) {
            echo json_encode(['success' => false, 'message' => 'Assessment not found or unauthorized']);
            exit;
        }
        
        // Save to file
        if (file_put_contents($dataFile, json_encode($assessments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Assessment deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
