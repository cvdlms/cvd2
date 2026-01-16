<?php
/**
 * Recommendation Engine
 * Hệ thống gợi ý bài tập dựa trên quy tắc
 */

require_once __DIR__ . '/student_analysis.php';

class RecommendationEngine {
    private $studentCode;
    private $analyzer;
    private $grade;
    private $semester;
    
    public function __construct($studentCode, $grade, $semester = 'hk1') {
        $this->studentCode = $studentCode;
        $this->grade = $grade;
        $this->semester = $semester;
        $this->analyzer = new StudentAnalyzer($studentCode);
    }
    
    /**
     * Tạo danh sách gợi ý
     */
    public function generateRecommendations() {
        $analysis = $this->analyzer->analyzeStudent();
        $recommendations = [];
        
        // Quy tắc 1: Ưu tiên ôn tập chủ đề yếu
        $recommendations = array_merge($recommendations, $this->ruleWeakTopics($analysis));
        
        // Quy tắc 2: Khuyến khích luyện tập đều đặn
        $recommendations = array_merge($recommendations, $this->rulePracticeFrequency($analysis));
        
        // Quy tắc 3: Thử thách nâng cao cho học sinh giỏi
        $recommendations = array_merge($recommendations, $this->ruleChallengeExcellent($analysis));
        
        // Quy tắc 4: Động viên khi tiến bộ
        $recommendations = array_merge($recommendations, $this->ruleProgressReward($analysis));
        
        // Quy tắc 5: Cảnh báo khi tụt giảm
        $recommendations = array_merge($recommendations, $this->ruleDeclineWarning($analysis));
        
        // Giới hạn tối đa 6 gợi ý
        return array_slice($recommendations, 0, 6);
    }
    
    /**
     * Quy tắc 1: Ôn tập chủ đề yếu
     */
    private function ruleWeakTopics($analysis) {
        $recommendations = [];
        $weakTopics = $analysis['weak_topics'];
        
        if (empty($weakTopics)) {
            return $recommendations;
        }
        
        // Lấy tối đa 3 chủ đề yếu nhất
        $topWeak = array_slice($weakTopics, 0, 3);
        
        foreach ($topWeak as $topic) {
            $subjectId = $topic['subject_id'];
            $priority = $topic['priority'];
            
            $recommendations[] = [
                'type' => 'weak_topic',
                'priority' => $priority === 'high' ? 1 : 2,
                'icon' => '📌',
                'title' => 'Ôn tập môn ' . $this->getSubjectName($subjectId),
                'description' => $topic['recommendation'] . ' (Điểm TB: ' . $topic['average_score'] . ')',
                'action' => [
                    'label' => 'Luyện tập ngay',
                    'url' => 'practice.php?subject=' . $subjectId
                ],
                'color' => $priority === 'high' ? 'danger' : 'warning'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Quy tắc 2: Khuyến khích luyện tập
     */
    private function rulePracticeFrequency($analysis) {
        $recommendations = [];
        $frequency = $analysis['practice_frequency'];
        
        if ($frequency['last_7_days'] < 3) {
            $recommendations[] = [
                'type' => 'practice_motivation',
                'priority' => 3,
                'icon' => '💪',
                'title' => 'Hãy luyện tập thêm!',
                'description' => 'Bạn chỉ luyện tập ' . $frequency['last_7_days'] . ' lần trong 7 ngày qua. Mục tiêu: 5 lần/tuần.',
                'action' => [
                    'label' => 'Bắt đầu luyện tập',
                    'url' => 'practice.php'
                ],
                'color' => 'info'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Quy tắc 3: Thử thách cho học sinh giỏi
     */
    private function ruleChallengeExcellent($analysis) {
        $recommendations = [];
        $level = $analysis['level'];
        $performance = $analysis['overall_performance'];
        
        if ($level['level'] === 'excellent' || $level['level'] === 'good') {
            // Tìm môn điểm cao nhất để gợi ý nâng cao
            $subjects = $analysis['subject_performance'];
            $bestSubject = null;
            $bestScore = 0;
            
            foreach ($subjects as $subjectId => $stats) {
                if ($stats['average'] > $bestScore) {
                    $bestScore = $stats['average'];
                    $bestSubject = $subjectId;
                }
            }
            
            if ($bestSubject) {
                $recommendations[] = [
                    'type' => 'challenge',
                    'priority' => 4,
                    'icon' => '🏆',
                    'title' => 'Thử thách nâng cao',
                    'description' => 'Bạn đang rất giỏi ' . $this->getSubjectName($bestSubject) . '! Hãy thử bài tập khó hơn.',
                    'action' => [
                        'label' => 'Thử thách ngay',
                        'url' => 'practice.php?subject=' . $bestSubject . '&level=hard'
                    ],
                    'color' => 'success'
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Quy tắc 4: Khen thưởng khi tiến bộ
     */
    private function ruleProgressReward($analysis) {
        $recommendations = [];
        $trend = $analysis['progress_trend'];
        
        if ($trend['trend'] === 'improving') {
            $recommendations[] = [
                'type' => 'achievement',
                'priority' => 5,
                'icon' => '🎉',
                'title' => 'Chúc mừng! Bạn đang tiến bộ',
                'description' => $trend['message'] . ' Hãy tiếp tục phát huy!',
                'action' => [
                    'label' => 'Xem thống kê',
                    'url' => 'results.php'
                ],
                'color' => 'success'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Quy tắc 5: Cảnh báo khi giảm điểm
     */
    private function ruleDeclineWarning($analysis) {
        $recommendations = [];
        $trend = $analysis['progress_trend'];
        
        if ($trend['trend'] === 'declining') {
            $recommendations[] = [
                'type' => 'warning',
                'priority' => 1,
                'icon' => '⚠️',
                'title' => 'Cần chú ý!',
                'description' => $trend['message'] . ' Hãy dành thời gian ôn tập.',
                'action' => [
                    'label' => 'Xem phân tích',
                    'url' => 'results.php'
                ],
                'color' => 'danger'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Lấy tên môn học
     */
    private function getSubjectName($subjectId) {
        $subjectsFile = __DIR__ . '/../admin/subjects.json';
        if (!file_exists($subjectsFile)) {
            return 'Môn học #' . $subjectId;
        }
        
        $subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
        foreach ($subjects as $subject) {
            if ($subject['id'] == $subjectId) {
                return $subject['name'];
            }
        }
        
        return 'Môn học #' . $subjectId;
    }
    
    /**
     * Sắp xếp gợi ý theo độ ưu tiên
     */
    private function sortByPriority($recommendations) {
        usort($recommendations, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        return $recommendations;
    }
}
?>