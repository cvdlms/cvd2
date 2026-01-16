<?php
/**
 * Student Analysis Module
 * Phân tích trình độ học sinh và xác định điểm yếu
 */

class StudentAnalyzer {
    private $studentCode;
    private $scoreFile;
    private $practiceFile;
    private $questionsDir;
    
    public function __construct($studentCode) {
        $this->studentCode = $studentCode;
        $this->scoreFile = __DIR__ . '/../shared/scores/student_score.json';
        $this->practiceFile = __DIR__ . '/../admin/student_practice_history.json';
        $this->questionsDir = __DIR__ . '/../teacher/questions/';
    }
    
    /**
     * Phân tích tổng quan trình độ học sinh
     */
    public function analyzeStudent() {
        $scores = $this->getStudentScores();
        $practiceHistory = $this->getPracticeHistory();
        
        return [
            'overall_performance' => $this->calculateOverallPerformance($scores),
            'subject_performance' => $this->analyzeBySubject($scores),
            'weak_topics' => $this->identifyWeakTopics($scores),
            'practice_frequency' => $this->analyzePracticeFrequency($practiceHistory),
            'progress_trend' => $this->calculateProgressTrend($scores),
            'level' => $this->determineLevel($scores)
        ];
    }
    
    /**
     * Lấy điểm thi của học sinh
     */
    private function getStudentScores() {
        if (!file_exists($this->scoreFile)) {
            return [];
        }
        
        $allScores = json_decode(file_get_contents($this->scoreFile), true) ?: [];
        $studentScores = [];
        
        foreach ($allScores as $score) {
            $studentId = $score['student_code'] ?? $score['student_id'] ?? '';
            if ($studentId === $this->studentCode) {
                $studentScores[] = $score;
            }
        }
        
        return $studentScores;
    }
    
    /**
     * Lấy lịch sử luyện tập
     */
    private function getPracticeHistory() {
        if (!file_exists($this->practiceFile)) {
            return [];
        }
        
        $allHistory = json_decode(file_get_contents($this->practiceFile), true) ?: [];
        return array_filter($allHistory, function($record) {
            return $record['student_code'] === $this->studentCode;
        });
    }
    
    /**
     * Tính điểm trung bình tổng
     */
    private function calculateOverallPerformance($scores) {
        if (empty($scores)) {
            return [
                'average_score' => 0,
                'total_exams' => 0,
                'best_score' => 0,
                'worst_score' => 0
            ];
        }
        
        $scoreValues = array_map(function($s) {
            return floatval($s['score'] ?? 0);
        }, $scores);
        
        return [
            'average_score' => round(array_sum($scoreValues) / count($scoreValues), 2),
            'total_exams' => count($scores),
            'best_score' => max($scoreValues),
            'worst_score' => min($scoreValues)
        ];
    }
    
    /**
     * Phân tích theo môn học
     */
    private function analyzeBySubject($scores) {
        $subjectStats = [];
        
        foreach ($scores as $score) {
            $subjectId = $score['subject_id'] ?? 'unknown';
            if (!isset($subjectStats[$subjectId])) {
                $subjectStats[$subjectId] = [
                    'scores' => [],
                    'count' => 0
                ];
            }
            
            $subjectStats[$subjectId]['scores'][] = floatval($score['score'] ?? 0);
            $subjectStats[$subjectId]['count']++;
        }
        
        // Tính trung bình mỗi môn
        foreach ($subjectStats as $subjectId => &$stats) {
            $stats['average'] = round(array_sum($stats['scores']) / $stats['count'], 2);
            $stats['best'] = max($stats['scores']);
            $stats['worst'] = min($stats['scores']);
        }
        
        return $subjectStats;
    }
    
    /**
     * Xác định chủ đề yếu (cần ôn tập)
     */
    private function identifyWeakTopics($scores) {
        $subjectPerformance = $this->analyzeBySubject($scores);
        $weakTopics = [];
        
        foreach ($subjectPerformance as $subjectId => $stats) {
            if ($stats['average'] < 5) {
                $weakTopics[] = [
                    'subject_id' => $subjectId,
                    'average_score' => $stats['average'],
                    'priority' => 'high', // < 5 điểm = ưu tiên cao
                    'recommendation' => 'Cần ôn tập cơ bản'
                ];
            } elseif ($stats['average'] < 7) {
                $weakTopics[] = [
                    'subject_id' => $subjectId,
                    'average_score' => $stats['average'],
                    'priority' => 'medium', // 5-7 điểm = ưu tiên trung bình
                    'recommendation' => 'Cần luyện tập thêm'
                ];
            }
        }
        
        // Sắp xếp theo điểm thấp nhất
        usort($weakTopics, function($a, $b) {
            return $a['average_score'] <=> $b['average_score'];
        });
        
        return $weakTopics;
    }
    
    /**
     * Phân tích tần suất luyện tập
     */
    private function analyzePracticeFrequency($practiceHistory) {
        $today = date('Y-m-d');
        $last7Days = date('Y-m-d', strtotime('-7 days'));
        $last30Days = date('Y-m-d', strtotime('-30 days'));
        
        $count7Days = 0;
        $count30Days = 0;
        
        foreach ($practiceHistory as $record) {
            $date = $record['date'] ?? '';
            if ($date >= $last7Days) {
                $count7Days++;
            }
            if ($date >= $last30Days) {
                $count30Days++;
            }
        }
        
        return [
            'last_7_days' => $count7Days,
            'last_30_days' => $count30Days,
            'total' => count($practiceHistory),
            'frequency_rating' => $this->ratePracticeFrequency($count7Days)
        ];
    }
    
    /**
     * Đánh giá tần suất luyện tập
     */
    private function ratePracticeFrequency($count7Days) {
        if ($count7Days >= 5) return 'Xuất sắc';
        if ($count7Days >= 3) return 'Tốt';
        if ($count7Days >= 1) return 'Trung bình';
        return 'Cần cố gắng hơn';
    }
    
    /**
     * Tính xu hướng tiến bộ
     */
    private function calculateProgressTrend($scores) {
        if (count($scores) < 2) {
            return [
                'trend' => 'neutral',
                'message' => 'Chưa đủ dữ liệu để đánh giá'
            ];
        }
        
        // Sắp xếp theo thời gian
        usort($scores, function($a, $b) {
            return strtotime($a['timestamp'] ?? '0') <=> strtotime($b['timestamp'] ?? '0');
        });
        
        // So sánh 3 bài gần nhất vs 3 bài trước đó
        $recent = array_slice($scores, -3);
        $previous = array_slice($scores, max(0, count($scores) - 6), 3);
        
        $recentAvg = array_sum(array_map(function($s) {
            return floatval($s['score'] ?? 0);
        }, $recent)) / count($recent);
        
        $previousAvg = array_sum(array_map(function($s) {
            return floatval($s['score'] ?? 0);
        }, $previous)) / count($previous);
        
        $diff = $recentAvg - $previousAvg;
        
        if ($diff > 1) {
            return [
                'trend' => 'improving',
                'message' => 'Đang tiến bộ rõ rệt! (+' . round($diff, 1) . ' điểm)',
                'value' => $diff
            ];
        } elseif ($diff < -1) {
            return [
                'trend' => 'declining',
                'message' => 'Cần chú ý, điểm đang giảm (' . round($diff, 1) . ' điểm)',
                'value' => $diff
            ];
        } else {
            return [
                'trend' => 'stable',
                'message' => 'Kết quả ổn định',
                'value' => $diff
            ];
        }
    }
    
    /**
     * Xác định trình độ tổng thể
     */
    private function determineLevel($scores) {
        $performance = $this->calculateOverallPerformance($scores);
        $avg = $performance['average_score'];
        
        if ($avg >= 9) {
            return [
                'level' => 'excellent',
                'label' => 'Xuất sắc',
                'description' => 'Bạn đang ở trình độ cao, hãy thử thách bản thân với bài nâng cao!',
                'color' => '#11998e'
            ];
        } elseif ($avg >= 8) {
            return [
                'level' => 'good',
                'label' => 'Giỏi',
                'description' => 'Kết quả tốt! Tiếp tục duy trì và nâng cao kiến thức.',
                'color' => '#38ef7d'
            ];
        } elseif ($avg >= 6.5) {
            return [
                'level' => 'average',
                'label' => 'Khá',
                'description' => 'Bạn đang học tốt, hãy luyện tập thêm để đạt điểm cao hơn.',
                'color' => '#f5af19'
            ];
        } elseif ($avg >= 5) {
            return [
                'level' => 'fair',
                'label' => 'Trung bình',
                'description' => 'Cần cố gắng thêm! Tập trung vào các chủ đề yếu.',
                'color' => '#f79d00'
            ];
        } else {
            return [
                'level' => 'weak',
                'label' => 'Cần cố gắng',
                'description' => 'Đừng lo lắng! Hãy ôn tập từ cơ bản và luyện tập đều đặn.',
                'color' => '#eb3349'
            ];
        }
    }
}
?>