<?php
/**
 * MAPPING HELPER - Kết nối Ma trận và Ngân hàng câu hỏi
 * 
 * File này cung cấp các hàm để:
 * - Lấy câu hỏi từ Ngân hàng dựa trên ĐVKT trong Ma trận
 * - Kiểm tra số lượng câu hỏi có sẵn
 * - Thống kê câu hỏi theo ĐVKT và mức độ
 */

/**
 * Chuẩn hóa chuỗi để so sánh
 * 
 * @param string $str
 * @return string
 */
function normalizeString($str) {
    return trim(mb_strtolower($str, 'UTF-8'));
}

/**
 * Lấy câu hỏi từ Ngân hàng theo ĐVKT và level
 * 
 * @param array $matrixUnit - Unit từ ma trận {"title": "Bài 1: ...", "topic": "Chương 1: ..."}
 * @param string $grade - khoi6, khoi7, khoi8, khoi9
 * @param string $semester - hk1, hk2
 * @param int $subjectId - ID môn học
 * @param string $level - NB, TH, VD, VDC (optional, null = all)
 * @param string $type - single, multiple (optional, null = all)
 * @return array - Danh sách câu hỏi phù hợp
 */
function getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, $level = null, $type = null) {
    $bankFile = __DIR__ . "/questions/{$grade}/{$semester}/subject_{$subjectId}.json";
    
    if (!file_exists($bankFile)) {
        return [];
    }
    
    $bankData = json_decode(file_get_contents($bankFile), true) ?: [];
    $matchedQuestions = [];
    
    foreach ($bankData as $topicData) {
        // So sánh Topic (tùy chọn)
        if (isset($matrixUnit['topic']) && 
            normalizeString($topicData['topic']) !== normalizeString($matrixUnit['topic'])) {
            continue;
        }
        
        // So sánh Lesson (ĐVKT) - QUAN TRỌNG
        if (normalizeString($topicData['lesson']) !== normalizeString($matrixUnit['title'])) {
            continue;
        }
        
        // Lọc câu hỏi theo level và type
        foreach ($topicData['questions'] as $question) {
            // Filter by level
            if ($level !== null && $question['level'] !== $level) {
                continue;
            }
            
            // Filter by type
            if ($type !== null && $question['type'] !== $type) {
                continue;
            }
            
            $matchedQuestions[] = $question;
        }
    }
    
    return $matchedQuestions;
}

/**
 * Lấy thống kê số lượng câu hỏi theo ĐVKT
 * 
 * @param array $matrixUnit
 * @param string $grade 
 * @param string $semester
 * @param int $subjectId
 * @return array
 */
function getQuestionStats($matrixUnit, $grade, $semester, $subjectId) {
    $stats = [
        'unit' => $matrixUnit['title'],
        'topic' => $matrixUnit['topic'] ?? '',
        'total' => 0,
        'by_level' => [
            'NB' => 0,
            'TH' => 0,
            'VD' => 0,
            'VDC' => 0
        ],
        'by_type' => [
            'single' => 0,
            'multiple' => 0
        ]
    ];
    
    foreach (['NB', 'TH', 'VD', 'VDC'] as $level) {
        $questions = getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, $level);
        $stats['by_level'][$level] = count($questions);
    }
    
    foreach (['single', 'multiple'] as $type) {
        $questions = getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, null, $type);
        $stats['by_type'][$type] = count($questions);
    }
    
    $stats['total'] = array_sum($stats['by_level']);
    
    return $stats;
}

/**
 * Kiểm tra tính khả thi của Ma trận với Ngân hàng câu hỏi
 * 
 * @param array $matrixData - Dữ liệu ma trận đầy đủ
 * @param string $grade
 * @param string $semester  
 * @param int $subjectId
 * @return array - Danh sách warnings
 */
function checkMatrixFeasibility($matrixData, $grade, $semester, $subjectId) {
    $warnings = [];
    
    if (!isset($matrixData['topics']) || !is_array($matrixData['topics'])) {
        return [['type' => 'error', 'message' => 'Dữ liệu ma trận không hợp lệ']];
    }
    
    foreach ($matrixData['topics'] as $topicIndex => $topic) {
        $topicTitle = $topic['title'] ?? "Chủ đề " . ($topicIndex + 1);
        
        if (!isset($topic['units']) || !is_array($topic['units'])) {
            continue;
        }
        
        foreach ($topic['units'] as $unitIndex => $unit) {
            $unitTitle = $unit['title'] ?? "Đơn vị " . ($unitIndex + 1);
            
            $matrixUnit = [
                'title' => $unitTitle,
                'topic' => $topicTitle
            ];
            
            $stats = getQuestionStats($matrixUnit, $grade, $semester, $subjectId);
            
            // Kiểm tra tổng số câu hỏi
            if ($stats['total'] === 0) {
                $warnings[] = [
                    'type' => 'error',
                    'unit' => $unitTitle,
                    'topic' => $topicTitle,
                    'message' => "ĐVKT này không có câu hỏi nào trong Ngân hàng"
                ];
                continue;
            }
            
            // Kiểm tra từng level được yêu cầu
            $levels = $unit['levels'] ?? [];
            foreach (['NB', 'TH', 'VD', 'VDC'] as $level) {
                if (!empty($levels[$level]) && $stats['by_level'][$level] === 0) {
                    $warnings[] = [
                        'type' => 'warning',
                        'unit' => $unitTitle,
                        'topic' => $topicTitle,
                        'level' => $level,
                        'message' => "Không có câu hỏi mức độ {$level}"
                    ];
                }
            }
            
            // Kiểm tra số câu hỏi trắc nghiệm (nếu cần)
            $tnkqNeeded = $unit['tnkq_q'] ?? 0;
            if ($tnkqNeeded > 0 && $stats['by_type']['single'] < $tnkqNeeded) {
                $warnings[] = [
                    'type' => 'warning',
                    'unit' => $unitTitle,
                    'topic' => $topicTitle,
                    'message' => "Cần {$tnkqNeeded} câu TNKQ nhưng chỉ có {$stats['by_type']['single']} câu"
                ];
            }
        }
    }
    
    return $warnings;
}

/**
 * Random chọn câu hỏi từ Ngân hàng theo yêu cầu
 * 
 * @param array $matrixUnit
 * @param string $grade
 * @param string $semester
 * @param int $subjectId
 * @param string $level
 * @param int $count - Số câu cần chọn
 * @param string $type - single hoặc multiple
 * @return array - Danh sách câu hỏi đã chọn
 */
function randomSelectQuestions($matrixUnit, $grade, $semester, $subjectId, $level, $count, $type = 'single') {
    $allQuestions = getQuestionsFromBank($matrixUnit, $grade, $semester, $subjectId, $level, $type);
    
    if (empty($allQuestions)) {
        return [];
    }
    
    // Nếu yêu cầu nhiều hơn số câu có sẵn, trả về tất cả
    if ($count >= count($allQuestions)) {
        return $allQuestions;
    }
    
    // Random chọn
    $selectedKeys = array_rand($allQuestions, $count);
    
    // array_rand returns single value if $count = 1
    if (!is_array($selectedKeys)) {
        $selectedKeys = [$selectedKeys];
    }
    
    $selected = [];
    foreach ($selectedKeys as $key) {
        $selected[] = $allQuestions[$key];
    }
    
    return $selected;
}

/**
 * Tạo đề thi tự động từ Ma trận
 * 
 * @param array $matrixData - Ma trận hoàn chỉnh
 * @param string $grade
 * @param string $semester
 * @param int $subjectId
 * @return array - ['questions' => [...], 'warnings' => [...]]
 */
function createExamFromMatrix($matrixData, $grade, $semester, $subjectId) {
    $exam = [];
    $warnings = [];
    
    foreach ($matrixData['topics'] ?? [] as $topic) {
        $topicTitle = $topic['title'] ?? '';
        
        foreach ($topic['units'] ?? [] as $unit) {
            $unitTitle = $unit['title'] ?? '';
            $matrixUnit = ['title' => $unitTitle, 'topic' => $topicTitle];
            
            // TNKQ questions
            foreach (['NB', 'TH', 'VD', 'VDC'] as $level) {
                $neededKey = 'tnkq_' . strtolower($level);
                $needed = $unit[$neededKey] ?? 0;
                
                if ($needed > 0) {
                    $selected = randomSelectQuestions(
                        $matrixUnit, 
                        $grade, 
                        $semester, 
                        $subjectId, 
                        $level, 
                        $needed, 
                        'single'
                    );
                    
                    if (count($selected) < $needed) {
                        $warnings[] = [
                            'unit' => $unitTitle,
                            'level' => $level,
                            'needed' => $needed,
                            'found' => count($selected),
                            'message' => "Không đủ câu hỏi {$level}"
                        ];
                    }
                    
                    foreach ($selected as $q) {
                        $exam[] = array_merge($q, [
                            'source_unit' => $unitTitle,
                            'source_topic' => $topicTitle
                        ]);
                    }
                }
            }
        }
    }
    
    return [
        'questions' => $exam,
        'warnings' => $warnings,
        'total_questions' => count($exam)
    ];
}

/**
 * API endpoint để lấy thống kê (dùng cho AJAX)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_stats') {
    header('Content-Type: application/json; charset=utf-8');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['unit', 'grade', 'semester', 'subject_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['error' => "Missing field: {$field}"]);
            exit;
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
    
    echo json_encode($stats, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * API endpoint để kiểm tra tính khả thi
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_feasibility') {
    header('Content-Type: application/json; charset=utf-8');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $warnings = checkMatrixFeasibility(
        $data['matrix'] ?? [],
        $data['grade'] ?? '',
        $data['semester'] ?? '',
        (int)($data['subject_id'] ?? 0)
    );
    
    echo json_encode([
        'feasible' => empty($warnings),
        'warnings' => $warnings
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
