<?php
// ==========================================
// Test Script: Direct Exam Generation Test
// Purpose: Test ExamGenerator class directly
// ==========================================

// Set up environment
session_name('CVD_TEACHER_SESSION');
session_start();
$_SESSION['username'] = 'visal';

// Load the ExamGenerator class
require_once __DIR__ . '/generate_exam.php';

// Test Case: Khoi 8, HK1, Tin học (Subject 1)
$testData = [
    'grade' => 'khoi8',
    'semester' => 'hk1',
    'subject' => 1,
    'exam_title' => 'Test Exam - New Format',
    'num_variants' => 2,
    'requirements' => [
        'TNKQ' => [
            [
                'topic' => 'Chủ đề A. Máy tính và cộng đồng',
                'unit' => 'Sơ lược về lịch sử phát triển máy tính',
                'level' => 'NB',
                'count' => 3,
                'points' => 1.5
            ],
            [
                'topic' => 'Chủ đề C. Tổ chức lưu trữ, tìm kiếm và trao đổi thông tin',
                'unit' => '1. Đặc điểm của thông tin trong môi trường số',
                'level' => 'TH',
                'count' => 2,
                'points' => 1.0
            ]
        ]
    ],
    'options' => [
        'randomize_answers' => true,
        'randomize_questions' => false
    ]
];

echo "🧪 TESTING EXAM GENERATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "📚 Test Parameters:\n";
echo "   Grade: {$testData['grade']}\n";
echo "   Semester: {$testData['semester']}\n";
echo "   Subject: {$testData['subject']}\n";
echo "   Requirements: " . count($testData['requirements']['TNKQ']) . " TNKQ rows\n\n";

try {
    $generator = new ExamGenerator('visal');
    
    echo "🔄 Calling generateExam()...\n\n";
    $result = $generator->generateExam($testData);
    
    if (!$result['success']) {
        echo "❌ FAILED: {$result['error']}\n";
        exit(1);
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RESULT:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "✅ SUCCESS!\n\n";
    echo "Exam ID: {$result['exam_id']}\n";
    
    $exam = $result['exam'];
    $variants = $exam['variants'] ?? [];
    
    echo "Generated Variants: " . count($variants) . "\n\n";
    
    foreach ($variants as $idx => $variant) {
        $variantNum = $idx + 1;
        $questions = $variant['questions'] ?? [];
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "VARIANT $variantNum\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "   Total Questions: " . count($questions) . "\n\n";
        
        // Group by type and level
        $byType = ['TNKQ' => 0, 'DS' => 0, 'TL' => 0];
        $byLevel = ['NB' => 0, 'TH' => 0, 'VD' => 0, 'VDC' => 0];
        $totalPoints = 0;
        
        foreach ($questions as $q) {
            $type = $q['question_type'] ?? 'UNKNOWN';
            $level = $q['level'] ?? 'UNKNOWN';
            $points = $q['required_points'] ?? 0;
            
            if (isset($byType[$type])) $byType[$type]++;
            if (isset($byLevel[$level])) $byLevel[$level]++;
            $totalPoints += $points;
        }
        
        echo "   📝 By Type:\n";
        echo "      TNKQ: {$byType['TNKQ']}\n";
        echo "      DS:   {$byType['DS']}\n";
        echo "      TL:   {$byType['TL']}\n\n";
        
        echo "   🎯 By Level:\n";
        echo "      NB:  {$byLevel['NB']}\n";
        echo "      TH:  {$byLevel['TH']}\n";
        echo "      VD:  {$byLevel['VD']}\n";
        echo "      VDC: {$byLevel['VDC']}\n\n";
        
        echo "   💯 Total Points: " . number_format($totalPoints, 1) . "đ\n\n";
        
        // Show all questions with mapping info
        echo "   📋 Questions Detail:\n";
        foreach ($questions as $i => $q) {
            $num = $i + 1;
            $text = substr($q['question'], 0, 70);
            echo "   $num. [{$q['question_type']}][{$q['level']}] $text...\n";
            
            if (isset($q['topic_name'])) {
                echo "      📚 Topic: {$q['topic_name']}\n";
            }
            if (isset($q['unit_name'])) {
                echo "      📖 Unit:  {$q['unit_name']}\n";
            }
            echo "      💰 Points: {$q['required_points']}đ\n\n";
        }
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ TEST PASSED - System works with new format!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
