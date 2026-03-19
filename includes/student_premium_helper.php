<?php
// Helper functions for student premium features

function getStudentPremiumStatus($studentCode) {
    $premiumFile = __DIR__ . '/../admin/student_premium.json';
    
    if (!file_exists($premiumFile)) {
        return [
            'is_premium' => false,
            'type' => null,
            'end_date' => null,
            'features' => []
        ];
    }
    
    $premiumData = json_decode(file_get_contents($premiumFile), true) ?: [];
    
    // Find the most recent active premium record
    $activePremium = null;
    $latestExpired = null;
    
    foreach ($premiumData as $record) {
        if ($record['student_code'] === $studentCode) {
            $endDate = strtotime($record['end_date']);
            $isActive = $record['premium_status'] === 'active' && $endDate >= time();
            
            if ($isActive) {
                // Prioritize active premium with latest end date
                if (!$activePremium || $endDate > strtotime($activePremium['end_date'])) {
                    $activePremium = $record;
                }
            } else {
                // Keep track of latest expired premium
                if (!$latestExpired || $endDate > strtotime($latestExpired['end_date'])) {
                    $latestExpired = $record;
                }
            }
        }
    }
    
    // Return active premium if found, otherwise return latest expired
    $selectedRecord = $activePremium ?? $latestExpired;
    
    if ($selectedRecord) {
        $endDate = strtotime($selectedRecord['end_date']);
        $isActive = $selectedRecord['premium_status'] === 'active' && $endDate >= time();
        
        return [
            'is_premium' => $isActive,
            'type' => $selectedRecord['premium_type'] ?? null,
            'end_date' => $selectedRecord['end_date'],
            'days_remaining' => $isActive ? ceil(($endDate - time()) / 86400) : 0,
            'features' => $selectedRecord['features'] ?? []
        ];
    }
    
    return [
        'is_premium' => false,
        'type' => null,
        'end_date' => null,
        'features' => []
    ];
}

function isPremiumFeatureEnabled($studentCode, $feature) {
    $premium = getStudentPremiumStatus($studentCode);
    return $premium['is_premium'] && ($premium['features'][$feature] ?? false);
}

function getPremiumBadgeHTML($studentCode) {
    $premium = getStudentPremiumStatus($studentCode);
    
    if (!$premium['is_premium']) {
        return '';
    }
    
    $typeLabel = [
        'month' => 'Tháng',
        'semester' => 'Học Kỳ',
        'year' => 'Năm Học'
    ][$premium['type']] ?? 'Premium';
    
    $daysRemaining = $premium['days_remaining'];
    
    return '
    <div class="premium-badge" style="
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        animation: premium-glow 2s ease-in-out infinite;
        margin-left: 10px;
    ">
        ⭐ Premium ' . htmlspecialchars($typeLabel) . ' 
        <small style="opacity: 0.9;">(' . $daysRemaining . ' ngày)</small>
    </div>
    <style>
        @keyframes premium-glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
            50% { box-shadow: 0 4px 25px rgba(102, 126, 234, 0.8); }
        }
    </style>
    ';
}

// Get premium limits for non-premium users
function getPremiumLimits() {
    return [
        'daily_practice_limit' => 5,  // Số lần luyện tập/ngày
        'exam_retakes' => 2,  // Số lần thi lại
        'view_detailed_answers' => false,  // Xem đáp án chi tiết
        'advanced_statistics' => false  // Thống kê nâng cao
    ];
}

// Check daily practice limit
function checkDailyPracticeLimit($studentCode) {
    $premium = getStudentPremiumStatus($studentCode);
    
    // Premium users have unlimited practice
    if ($premium['is_premium']) {
        return [
            'allowed' => true,
            'count' => 0,
            'limit' => 'unlimited',
            'remaining' => 'unlimited'
        ];
    }
    
    // Non-premium: check daily limit
    $historyFile = __DIR__ . '/../admin/student_practice_history.json';
    $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
    
    $today = date('Y-m-d');
    $todayCount = 0;
    
    foreach ($history as $record) {
        if ($record['student_code'] === $studentCode && $record['date'] === $today) {
            $todayCount++;
        }
    }
    
    $limits = getPremiumLimits();
    $limit = $limits['daily_practice_limit'];
    
    return [
        'allowed' => $todayCount < $limit,
        'count' => $todayCount,
        'limit' => $limit,
        'remaining' => max(0, $limit - $todayCount)
    ];
}

// Record practice session
function recordPracticeSession($studentCode, $subjectId, $questionCount) {
    $historyFile = __DIR__ . '/../admin/student_practice_history.json';
    $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
    
    $history[] = [
        'student_code' => $studentCode,
        'date' => date('Y-m-d'),
        'time' => date('H:i:s'),
        'subject_id' => $subjectId,
        'question_count' => $questionCount,
        'timestamp' => time()
    ];
    
    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
