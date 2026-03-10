<?php
/**
 * API TẠO ĐỀ KIỂM TRA TỰ ĐỘNG
 * 
 * Endpoints:
 *   POST ?action=generate - Tạo đề từ ma trận
 *   POST ?action=replace_question - Thay câu hỏi trong đề
 *   POST ?action=save_exam - Lưu đề đã tạo
 *   GET  ?action=get_exam&id=xxx - Lấy đề đã tạo
 */

// Start output buffering FIRST
ob_start();

// Suppress all errors
error_reporting(0);
ini_set('display_errors', '0');

session_name('CVD_TEACHER_SESSION');
session_start();

// Clean any previous output
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Teacher login required.'
    ]);
    exit;
}

class ExamGenerator {
    private $questionsDir;
    private $examsDir;
    private $teacherId;
    
    public function __construct($teacherId) {
        $this->questionsDir = __DIR__ . '/../questions';
        $this->examsDir = __DIR__ . '/../exams/generated';
        $this->teacherId = $teacherId;
        
        // Tạo thư mục exams nếu chưa có
        if (!is_dir($this->examsDir)) {
            mkdir($this->examsDir, 0755, true);
        }
    }
    
    /**
     * Main: Tạo đề từ ma trận
     */
    public function generateExam($data) {
        try {
            // Validate input
            $required = ['matrix_data', 'exam_title', 'grade', 'subject', 'semester'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $matrixData = $data['matrix_data'];
            $examTitle = $data['exam_title'];
            $grade = $data['grade'];
            $subject = $data['subject'];
            $semester = $data['semester'];
            $options = $data['options'] ?? [];
            
            // Load questions từ database
            $allQuestions = $this->loadQuestions($grade, $semester, $subject);
            
            // Parse ma trận để lấy requirements
            $requirements = $this->parseMatrixRequirements($matrixData);
            
            // Generate exam variants
            $variantCount = $options['create_variants'] ?? 1;
            $variants = [];
            
            for ($i = 0; $i < $variantCount; $i++) {
                $variantLabel = chr(65 + $i); // A, B, C, D
                $variant = $this->generateVariant($allQuestions, $requirements, $options);
                $variant['variant'] = $variantLabel;
                $variants[] = $variant;
            }
            
            // Tạo exam ID
            $examId = $this->generateExamId();
            
            // Lưu exam
            $exam = [
                'id' => $examId,
                'title' => $examTitle,
                'grade' => $grade,
                'subject' => $subject,
                'semester' => $semester,
                'teacher_id' => $this->teacherId,
                'created_at' => date('Y-m-d H:i:s'),
                'variants' => $variants,
                'requirements' => $requirements,
                'options' => $options
            ];
            
            $this->saveExam($examId, $exam);
            
            return [
                'success' => true,
                'exam_id' => $examId,
                'exam' => $exam
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Load tất cả câu hỏi từ JSON
     */
    private function loadQuestions($grade, $semester, $subject) {
        // Format: questions/khoi8/hk1/subject_1.json
        $filePath = "{$this->questionsDir}/{$grade}/{$semester}/subject_{$subject}.json";
        
        if (!file_exists($filePath)) {
            throw new Exception("Question file not found: {$grade}/{$semester}/subject_{$subject}.json");
        }
        
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parse error: " . json_last_error_msg());
        }
        
        // Flatten questions với metadata
        $allQuestions = [];
        
        foreach ($data as $topicData) {
            $topic = $topicData['topic'] ?? '';
            $unit = $topicData['unit'] ?? $topicData['lesson'] ?? ''; // Support both 'unit' and 'lesson'
            
            foreach ($topicData['questions'] as $question) {
                $question['_topic'] = $topic;
                $question['_unit'] = $unit;
                
                // Map type to question_type
                $type = $question['type'] ?? 'single';
                if ($type === 'single') {
                    $question['question_type'] = 'TNKQ';
                } elseif ($type === 'true_false') {
                    $question['question_type'] = 'DS';
                } elseif ($type === 'essay') {
                    $question['question_type'] = 'TL';
                } else {
                    $question['question_type'] = 'TNKQ'; // Default
                }
                
                $allQuestions[] = $question;
            }
        }
        
        return $allQuestions;
    }
    
    /**
     * Parse ma trận để lấy requirements
     */
    private function parseMatrixRequirements($matrixData) {
        $requirements = [
            'TNKQ' => [],
            'DS' => [],
            'TL' => []
        ];
        
        if (!isset($matrixData['topics']) || !is_array($matrixData['topics'])) {
            return $requirements;
        }
        
        foreach ($matrixData['topics'] as $topic) {
            $topicTitle = $topic['title'] ?? '';
            $units = $topic['units'] ?? [];
            
            foreach ($units as $unit) {
                $unitTitle = $unit['title'] ?? '';
                
                // TNKQ requirements
                $tnkq = $unit['tnkq'] ?? [];
                foreach (['nb', 'th', 'vd'] as $levelKey) {
                    $count = $tnkq[$levelKey] ?? 0;
                    if ($count > 0) {
                        $level = strtoupper($levelKey);
                        $requirements['TNKQ'][] = [
                            'topic' => $topicTitle,
                            'unit' => $unitTitle,
                            'level' => $level,
                            'count' => $count,
                            'points' => $count * 0.5, // Each TNKQ = 0.5 điểm
                            'question_nums' => $tnkq[$levelKey . '_nums'] ?? []
                        ];
                    }
                }
                
                // DS requirements
                $ds = $unit['ds'] ?? [];
                foreach (['nb', 'th', 'vd'] as $levelKey) {
                    $count = $ds[$levelKey] ?? 0;
                    if ($count > 0) {
                        $level = strtoupper($levelKey);
                        $requirements['DS'][] = [
                            'topic' => $topicTitle,
                            'unit' => $unitTitle,
                            'level' => $level,
                            'count' => $count,
                            'points' => $count * 0.25, // Each DS item = 0.25 điểm
                            'question_nums' => $ds[$levelKey . '_nums'] ?? []
                        ];
                    }
                }
                
                // TL requirements
                $tl = $unit['tl'] ?? [];
                $tl_subquestions = $unit['tl_subquestions'] ?? [];
                
                foreach (['nb', 'th', 'vd'] as $levelKey) {
                    $points = $tl[$levelKey] ?? 0;
                    if ($points > 0) {
                        $level = strtoupper($levelKey);
                        $requirements['TL'][] = [
                            'topic' => $topicTitle,
                            'unit' => $unitTitle,
                            'level' => $level,
                            'count' => 1, // TL is scored by points, not count
                            'points' => $points,
                            'question_nums' => $tl[$levelKey . '_nums'] ?? [],
                            'subquestions' => array_filter($tl_subquestions, function($sq) use ($level) {
                                return ($sq['focus'] ?? '') === $level;
                            })
                        ];
                    }
                }
            }
        }
        
        return $requirements;
    }
    
    /**
     * Tạo 1 variant đề thi
     */
    private function generateVariant($allQuestions, $requirements, $options) {
        $selectedQuestions = [];
        $questionNumber = 1;
        
        // Duyệt qua từng loại câu hỏi: TNKQ, DS, TL
        foreach (['TNKQ', 'DS', 'TL'] as $qType) {
            if (empty($requirements[$qType])) continue;
            
            foreach ($requirements[$qType] as $req) {
                // Lọc câu hỏi matching - CHỈ theo type và level, không theo topic/unit
                $matching = $this->filterQuestions($allQuestions, [
                    'question_type' => $qType,
                    'level' => $req['level']
                    // Removed: topic và unit filtering (không khớp với JSON)
                ]);
                
                // Random select
                $selected = $this->randomSelect($matching, $req['count'], $options);
                
                // Thêm vào danh sách với số thứ tự
                foreach ($selected as $q) {
                    $q['number'] = $questionNumber++;
                    $q['required_points'] = $req['points'] / $req['count'];
                    $selectedQuestions[] = $q;
                }
            }
        }
        
        // Shuffle options nếu cần
        if ($options['randomize_answers'] ?? true) {
            foreach ($selectedQuestions as &$q) {
                if (isset($q['options']) && is_array($q['options'])) {
                    $q = $this->shuffleOptions($q);
                }
            }
        }
        
        // Shuffle question order nếu cần
        if ($options['randomize_questions'] ?? false) {
            // Nhưng giữ nguyên group TNKQ/DS/TL
            // (Không shuffle cross-type)
        }
        
        // Tính tổng
        $distribution = [
            'TNKQ' => 0,
            'DS' => 0,
            'TL' => 0
        ];
        
        $totalPoints = 0;
        
        foreach ($selectedQuestions as $q) {
            $distribution[$q['question_type']]++;
            $totalPoints += $q['required_points'];
        }
        
        return [
            'questions' => $selectedQuestions,
            'total_questions' => count($selectedQuestions),
            'total_points' => round($totalPoints, 2),
            'distribution' => $distribution
        ];
    }
    
    /**
     * Lọc câu hỏi theo điều kiện
     */
    private function filterQuestions($allQuestions, $filters) {
        return array_filter($allQuestions, function($q) use ($filters) {
            foreach ($filters as $key => $value) {
                // Xử lý key đặc biệt _topic, _unit
                if ($key === 'topic') {
                    if (($q['_topic'] ?? '') !== $value) return false;
                } elseif ($key === 'unit') {
                    if (($q['_unit'] ?? '') !== $value) return false;
                } else {
                    if (($q[$key] ?? null) !== $value) return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Random select câu hỏi
     */
    private function randomSelect($questions, $count, $options) {
        $questions = array_values($questions); // Reset keys
        
        if (count($questions) <= $count) {
            return $questions; // Không đủ câu, lấy hết
        }
        
        // Tính weight dựa trên usage_count (ưu tiên câu ít dùng)
        $weights = [];
        foreach ($questions as $q) {
            $usageCount = $q['usage_count'] ?? 0;
            $weights[] = 1 / ($usageCount + 1);
        }
        
        $totalWeight = array_sum($weights);
        
        // Random select theo weight
        $selected = [];
        $selectedIds = [];
        
        while (count($selected) < $count && count($questions) > 0) {
            $rand = mt_rand() / mt_getrandmax() * $totalWeight;
            $cumulative = 0;
            
            foreach ($questions as $idx => $q) {
                $cumulative += $weights[$idx];
                
                if ($rand <= $cumulative) {
                    $qId = $q['id'] ?? $idx;
                    if (!in_array($qId, $selectedIds)) {
                        $selected[] = $q;
                        $selectedIds[] = $qId;
                        
                        // Remove khỏi pool và weight
                        unset($questions[$idx]);
                        $totalWeight -= $weights[$idx];
                        unset($weights[$idx]);
                        
                        $questions = array_values($questions);
                        $weights = array_values($weights);
                    }
                    break;
                }
            }
        }
        
        return $selected;
    }
    
    /**
     * Shuffle options của câu hỏi TNKQ
     */
    private function shuffleOptions($question) {
        if (!isset($question['options']) || !isset($question['correct'])) {
            return $question;
        }
        
        $options = $question['options'];
        $correctIndex = $question['correct'];
        $correctAnswer = $options[$correctIndex];
        
        // Shuffle
        shuffle($options);
        
        // Tìm vị trí mới của correct answer
        $newCorrectIndex = array_search($correctAnswer, $options);
        
        $question['options'] = $options;
        $question['correct'] = $newCorrectIndex;
        $question['correct_text'] = chr(65 + $newCorrectIndex); // A, B, C, D
        
        return $question;
    }
    
    /**
     * Thay thế 1 câu hỏi trong đề
     */
    public function replaceQuestion($data) {
        try {
            $examId = $data['exam_id'] ?? null;
            $variant = $data['variant'] ?? 'A';
            $questionNumber = $data['question_number'] ?? null;
            $constraints = $data['constraints'] ?? [];
            
            if (!$examId || !$questionNumber) {
                throw new Exception("Missing exam_id or question_number");
            }
            
            // Load exam
            $exam = $this->loadExam($examId);
            if (!$exam) {
                throw new Exception("Exam not found: $examId");
            }
            
            // Tìm variant
            $variantIndex = ord($variant) - 65;
            if (!isset($exam['variants'][$variantIndex])) {
                throw new Exception("Variant not found: $variant");
            }
            
            $targetVariant = &$exam['variants'][$variantIndex];
            $questions = &$targetVariant['questions'];
            
            // Tìm câu hỏi cần thay
            $targetIndex = null;
            foreach ($questions as $idx => $q) {
                if ($q['number'] == $questionNumber) {
                    $targetIndex = $idx;
                    break;
                }
            }
            
            if ($targetIndex === null) {
                throw new Exception("Question number not found: $questionNumber");
            }
            
            $oldQuestion = $questions[$targetIndex];
            
            // Load all questions
            $allQuestions = $this->loadQuestions(
                $exam['grade'], 
                $exam['semester'], 
                $exam['subject']
            );
            
            // Lọc theo constraints
            $matching = $this->filterQuestions($allQuestions, $constraints);
            
            // Loại trừ câu hiện tại và các câu đã chọn
            $usedIds = array_map(function($q) { return $q['id'] ?? null; }, $questions);
            $matching = array_filter($matching, function($q) use ($usedIds) {
                return !in_array($q['id'] ?? null, $usedIds);
            });
            
            if (empty($matching)) {
                throw new Exception("No alternative questions found");
            }
            
            // Random chọn 1 câu mới
            $newQuestion = $matching[array_rand($matching)];
            $newQuestion['number'] = $questionNumber;
            $newQuestion['required_points'] = $oldQuestion['required_points'];
            
            // Shuffle options nếu là TNKQ
            if ($newQuestion['question_type'] === 'TNKQ' && isset($newQuestion['options'])) {
                $newQuestion = $this->shuffleOptions($newQuestion);
            }
            
            // Thay thế
            $questions[$targetIndex] = $newQuestion;
            
            // Lưu lại
            $this->saveExam($examId, $exam);
            
            return [
                'success' => true,
                'new_question' => $newQuestion
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tạo exam ID
     */
    private function generateExamId() {
        return 'EXAM_' . date('Ymd_His') . '_' . substr(md5(microtime()), 0, 4);
    }
    
    /**
     * Lưu exam vào file
     */
    private function saveExam($examId, $exam) {
        $filePath = "{$this->examsDir}/{$examId}.json";
        $json = json_encode($exam, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $json);
    }
    
    /**
     * Load exam từ file
     */
    private function loadExam($examId) {
        $filePath = "{$this->examsDir}/{$examId}.json";
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }
}

// ===== MAIN HANDLER =====

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing action parameter'
    ]);
    exit;
}

try {
    $teacherId = $_SESSION['username'] ?? 'unknown';
    $generator = new ExamGenerator($teacherId);

    switch ($action) {
        case 'generate':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $generator->generateExam($data);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;
        
    case 'replace_question':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $generator->replaceQuestion($data);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;
        
    case 'get_exam':
        $examId = $_GET['id'] ?? null;
        if (!$examId) {
            echo json_encode(['success' => false, 'error' => 'Missing exam ID']);
        } else {
            $exam = $generator->loadExam($examId);
            if ($exam) {
                echo json_encode(['success' => true, 'exam' => $exam], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Exam not found']);
            }
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action: ' . $action
        ]);
}
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
