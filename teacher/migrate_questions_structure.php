<?php
/**
 * MIGRATION SCRIPT: Chuẩn hóa cấu trúc câu hỏi
 * 
 * Chuyển đổi từ:
 *   - type: "single" → question_type: "TNKQ"
 *   - lesson → unit
 *   - Thêm các field mới: id, points, difficulty, tags, usage_count
 * 
 * Sử dụng:
 *   php migrate_questions_structure.php [--dry-run] [--file=path]
 * 
 * Options:
 *   --dry-run: Xem preview không thay đổi file
 *   --file=path: Chỉ migrate 1 file cụ thể
 */

class QuestionMigrator {
    private $questionsDir;
    private $backupDir;
    private $logFile;
    private $stats;
    
    public function __construct() {
        $this->questionsDir = __DIR__ . '/questions';
        $this->backupDir = __DIR__ . '/questions_backup_' . date('Ymd_His');
        $this->logFile = __DIR__ . '/migration_log_' . date('Ymd_His') . '.txt';
        $this->stats = [
            'total_files' => 0,
            'total_topics' => 0,
            'total_questions' => 0,
            'migrated_questions' => 0,
            'errors' => []
        ];
    }
    
    /**
     * Chạy migration toàn bộ
     */
    public function run($dryRun = false, $specificFile = null) {
        $this->log("===== BẮT ĐẦU MIGRATION =====");
        $this->log("Dry Run: " . ($dryRun ? 'YES' : 'NO'));
        $this->log("Time: " . date('Y-m-d H:i:s'));
        $this->log("");
        
        // Backup nếu không phải dry-run
        if (!$dryRun) {
            $this->createBackup();
        }
        
        // Lấy danh sách files
        if ($specificFile) {
            $files = [$specificFile];
        } else {
            $files = $this->getAllQuestionFiles();
        }
        
        $this->log("Tìm thấy " . count($files) . " file(s)");
        $this->log("");
        
        // Migrate từng file
        foreach ($files as $file) {
            $this->migrateFile($file, $dryRun);
        }
        
        // Báo cáo
        $this->printReport();
        
        return $this->stats;
    }
    
    /**
     * Tạo backup toàn bộ thư mục questions
     */
    private function createBackup() {
        $this->log("Tạo backup: " . $this->backupDir);
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        $this->copyDirectory($this->questionsDir, $this->backupDir);
        $this->log("✓ Backup hoàn tất");
        $this->log("");
    }
    
    /**
     * Copy đệ quy thư mục
     */
    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;
                
                if (is_dir($srcPath)) {
                    $this->copyDirectory($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Lấy tất cả file JSON trong thư mục questions
     */
    private function getAllQuestionFiles() {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->questionsDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Migrate 1 file
     */
    private function migrateFile($filePath, $dryRun = false) {
        $this->stats['total_files']++;
        
        $relativePath = str_replace($this->questionsDir . DIRECTORY_SEPARATOR, '', $filePath);
        $this->log("► File: " . $relativePath);
        
        // Đọc file
        if (!file_exists($filePath)) {
            $this->log("  ✗ File không tồn tại");
            $this->stats['errors'][] = "File not found: $relativePath";
            return;
        }
        
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("  ✗ Lỗi parse JSON: " . json_last_error_msg());
            $this->stats['errors'][] = "JSON error in $relativePath: " . json_last_error_msg();
            return;
        }
        
        if (!is_array($data)) {
            $this->log("  ✗ Dữ liệu không phải array");
            $this->stats['errors'][] = "Invalid data structure in $relativePath";
            return;
        }
        
        // Migrate từng topic
        $migratedData = [];
        $fileQuestionCount = 0;
        
        foreach ($data as $topicIndex => $topic) {
            $this->stats['total_topics']++;
            
            $migratedTopic = $this->migrateTopic($topic);
            $migratedData[] = $migratedTopic;
            
            $qCount = isset($migratedTopic['questions']) ? count($migratedTopic['questions']) : 0;
            $fileQuestionCount += $qCount;
        }
        
        $this->log("  → Migrated: " . count($data) . " topic(s), " . $fileQuestionCount . " question(s)");
        $this->stats['total_questions'] += $fileQuestionCount;
        $this->stats['migrated_questions'] += $fileQuestionCount;
        
        // Lưu file nếu không phải dry-run
        if (!$dryRun) {
            $newJson = json_encode($migratedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($filePath, $newJson);
            $this->log("  ✓ Đã lưu");
        } else {
            $this->log("  ⊘ Dry-run: Không lưu");
        }
        
        $this->log("");
    }
    
    /**
     * Migrate 1 topic
     */
    private function migrateTopic($topic) {
        $migrated = [];
        
        // Chuyển "lesson" → "unit" (nếu có)
        if (isset($topic['lesson'])) {
            $migrated['unit'] = $topic['lesson'];
        } elseif (isset($topic['unit'])) {
            $migrated['unit'] = $topic['unit'];
        }
        
        // Giữ nguyên topic
        if (isset($topic['topic'])) {
            $migrated['topic'] = $topic['topic'];
        }
        
        // Migrate questions
        if (isset($topic['questions']) && is_array($topic['questions'])) {
            $migrated['questions'] = [];
            
            foreach ($topic['questions'] as $index => $question) {
                $migrated['questions'][] = $this->migrateQuestion($question, $index);
            }
        }
        
        return $migrated;
    }
    
    /**
     * Migrate 1 câu hỏi
     */
    private function migrateQuestion($question, $index) {
        $migrated = [];
        
        // 1. Tạo ID duy nhất
        $level = $question['level'] ?? 'NB';
        $questionType = $this->inferQuestionType($question);
        $migrated['id'] = $this->generateQuestionId($questionType, $level, $index);
        
        // 2. Giữ nguyên các field cơ bản
        $migrated['question'] = $question['question'] ?? '';
        
        if (isset($question['options'])) {
            $migrated['options'] = $question['options'];
        }
        
        if (isset($question['correct'])) {
            $migrated['correct'] = $question['correct'];
        }
        
        // 3. Chuyển đổi type → answer_type + question_type
        $migrated['answer_type'] = $question['type'] ?? 'single';
        $migrated['question_type'] = $questionType;
        
        // 4. Level
        $migrated['level'] = $level;
        
        // 5. Thêm các field mới
        $migrated['points'] = $this->calculateDefaultPoints($questionType);
        $migrated['difficulty'] = $this->inferDifficulty($level);
        $migrated['tags'] = $this->extractTags($question['question'] ?? '');
        $migrated['explanation'] = $question['explanation'] ?? '';
        $migrated['usage_count'] = 0;
        $migrated['last_used'] = null;
        
        return $migrated;
    }
    
    /**
     * Phán đoán question_type từ cấu trúc câu hỏi
     */
    private function inferQuestionType($question) {
        $type = $question['type'] ?? 'single';
        
        // Nếu là "single" hoặc "multiple" → TNKQ (trắc nghiệm khách quan)
        if (in_array($type, ['single', 'multiple'])) {
            // Kiểm tra xem có phải dạng Đúng/Sai không
            if (isset($question['options']) && count($question['options']) >= 4) {
                // Nếu có 4 options và tất cả đều ngắn (< 20 ký tự) → có thể là Đúng/Sai
                $allShort = true;
                foreach ($question['options'] as $opt) {
                    if (strlen($opt) > 50) {
                        $allShort = false;
                        break;
                    }
                }
                
                // Check xem có chứa "đúng" "sai" không
                $questionText = strtolower($question['question'] ?? '');
                if ($allShort && (
                    strpos($questionText, 'đúng') !== false || 
                    strpos($questionText, 'sai') !== false ||
                    strpos($questionText, 'nhận định') !== false ||
                    strpos($questionText, 'khẳng định') !== false
                )) {
                    return 'DS'; // Đúng/Sai
                }
            }
            
            return 'TNKQ'; // Trắc nghiệm khách quan thông thường
        }
        
        // Nếu là tự luận hoặc không có options
        if ($type === 'essay' || !isset($question['options']) || empty($question['options'])) {
            return 'TL'; // Tự luận
        }
        
        // Mặc định TNKQ
        return 'TNKQ';
    }
    
    /**
     * Tạo ID câu hỏi
     */
    private function generateQuestionId($type, $level, $index) {
        $timestamp = microtime(true);
        $random = substr(md5($timestamp . $index), 0, 6);
        return "Q_{$type}_{$level}_{$random}";
    }
    
    /**
     * Tính điểm mặc định theo loại câu
     */
    private function calculateDefaultPoints($questionType) {
        switch ($questionType) {
            case 'TNKQ':
                return 0.5;
            case 'DS':
                return 0.25; // Mỗi ý 0.25
            case 'TL':
                return 1.0;
            default:
                return 0.5;
        }
    }
    
    /**
     * Suy ra độ khó từ level
     */
    private function inferDifficulty($level) {
        switch ($level) {
            case 'NB': // Biết
                return 1;
            case 'TH': // Hiểu
                return 2;
            case 'VD': // Vận dụng
                return 3;
            case 'VDC': // Vận dụng cao
                return 4;
            default:
                return 2;
        }
    }
    
    /**
     * Trích xuất tags từ câu hỏi
     */
    private function extractTags($questionText) {
        // Đơn giản: lấy các từ khóa dài > 4 ký tự
        $words = preg_split('/[\s,.\?!;:]+/u', mb_strtolower($questionText));
        $tags = [];
        
        $stopWords = ['là', 'của', 'và', 'có', 'được', 'trong', 'với', 'một', 'các', 'này', 'đó'];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) > 4 && !in_array($word, $stopWords) && !is_numeric($word)) {
                $tags[] = $word;
            }
        }
        
        // Giới hạn 5 tags
        return array_slice(array_unique($tags), 0, 5);
    }
    
    /**
     * Ghi log
     */
    private function log($message) {
        echo $message . "\n";
        file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
    }
    
    /**
     * In báo cáo cuối cùng
     */
    private function printReport() {
        $this->log("");
        $this->log("===== BÁO CÁO MIGRATION =====");
        $this->log("Tổng số file: " . $this->stats['total_files']);
        $this->log("Tổng số topic: " . $this->stats['total_topics']);
        $this->log("Tổng số câu hỏi: " . $this->stats['total_questions']);
        $this->log("Đã migrate: " . $this->stats['migrated_questions'] . " câu");
        $this->log("Lỗi: " . count($this->stats['errors']));
        
        if (!empty($this->stats['errors'])) {
            $this->log("");
            $this->log("Chi tiết lỗi:");
            foreach ($this->stats['errors'] as $error) {
                $this->log("  - " . $error);
            }
        }
        
        $this->log("");
        $this->log("Log file: " . $this->logFile);
        if (is_dir($this->backupDir)) {
            $this->log("Backup: " . $this->backupDir);
        }
        $this->log("===== HOÀN TẤT =====");
    }
}

// ===== MAIN EXECUTION =====

// Parse arguments
$dryRun = in_array('--dry-run', $argv);
$specificFile = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--file=') === 0) {
        $specificFile = substr($arg, 7);
    }
}

// Chạy migration
$migrator = new QuestionMigrator();
$stats = $migrator->run($dryRun, $specificFile);

// Exit code
exit(count($stats['errors']) === 0 ? 0 : 1);
