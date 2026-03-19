<?php
/**
 * TEST MAPPING - API endpoint cho demo_mapping.html
 * 
 * File này xử lý các request test mapping giữa Ma trận và Ngân hàng câu hỏi
 */

require_once 'mapping_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'get_stats':
            handleGetStats($data);
            break;
            
        case 'get_questions':
            handleGetQuestions($data);
            break;
            
        case 'check_feasibility':
            handleCheckFeasibility($data);
            break;
            
        case 'create_exam':
            handleCreateExam($data);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Xử lý request lấy thống kê
 */
function handleGetStats($data) {
    $requiredFields = ['unit', 'grade', 'semester', 'subject_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $matrixUnit = [
        'title' => $data['unit'],
        'topic' => $data['topic'] ?? ''
    ];
    
    $stats = getQuestionStats(
        $matrixUnit,
        $data['grade'],
        $data['semester'],
        (int)$data['subject_id']
    );
    
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Xử lý request lấy danh sách câu hỏi
 */
function handleGetQuestions($data) {
    $requiredFields = ['unit', 'grade', 'semester', 'subject_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $matrixUnit = [
        'title' => $data['unit'],
        'topic' => $data['topic'] ?? ''
    ];
    
    $questions = getQuestionsFromBank(
        $matrixUnit,
        $data['grade'],
        $data['semester'],
        (int)$data['subject_id'],
        $data['level'] ?? null,
        $data['type'] ?? null
    );
    
    echo json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Xử lý request kiểm tra tính khả thi
 */
function handleCheckFeasibility($data) {
    if (!isset($data['matrix'])) {
        throw new Exception('Missing matrix data');
    }
    
    $warnings = checkMatrixFeasibility(
        $data['matrix'],
        $data['grade'] ?? '',
        $data['semester'] ?? '',
        (int)($data['subject_id'] ?? 0)
    );
    
    echo json_encode([
        'feasible' => empty($warnings),
        'warnings' => $warnings,
        'total_warnings' => count($warnings)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Xử lý request tạo đề thi từ ma trận
 */
function handleCreateExam($data) {
    if (!isset($data['matrix'])) {
        throw new Exception('Missing matrix data');
    }
    
    $result = createExamFromMatrix(
        $data['matrix'],
        $data['grade'] ?? '',
        $data['semester'] ?? '',
        (int)($data['subject_id'] ?? 0)
    );
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
