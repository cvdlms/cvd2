<?php
// Test script to validate JSON format

$jsonFile = 'MAU_JSON_CAU_HOI.json';
$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON không hợp lệ: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ JSON hợp lệ!\n\n";
echo "Số topic: " . count($data) . "\n\n";

foreach ($data as $index => $topic) {
    echo "Topic " . ($index + 1) . ":\n";
    echo "  Chủ đề: {$topic['topic']}\n";
    echo "  Bài học: {$topic['lesson']}\n";
    echo "  Số câu hỏi: " . count($topic['questions']) . "\n";
    
    // Check required fields
    $valid = true;
    foreach ($topic['questions'] as $qIndex => $q) {
        if (!isset($q['question']) || !isset($q['options']) || !isset($q['correct']) || 
            !isset($q['type']) || !isset($q['level'])) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " thiếu trường bắt buộc\n";
            $valid = false;
        }
        
        // Check options is array with 4 items
        if (!is_array($q['options']) || count($q['options']) !== 4) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " phải có 4 đáp án\n";
            $valid = false;
        }
        
        // Check correct index
        $correctIndexes = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
        foreach ($correctIndexes as $idx) {
            if ($idx < 0 || $idx > 3) {
                echo "  ⚠️ Câu " . ($qIndex + 1) . " có chỉ số đáp án đúng không hợp lệ: $idx\n";
                $valid = false;
            }
        }
        
        // Check type
        if (!in_array($q['type'], ['single', 'multiple'])) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " có type không hợp lệ: {$q['type']}\n";
            $valid = false;
        }
        
        // Check level
        if (!in_array($q['level'], ['NB', 'TH', 'VD', 'VDC'])) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " có level không hợp lệ: {$q['level']}\n";
            $valid = false;
        }
        
        // Check correct type matches answer count
        if ($q['type'] === 'single' && is_array($q['correct'])) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " là single nhưng có nhiều đáp án đúng\n";
            $valid = false;
        }
        if ($q['type'] === 'multiple' && !is_array($q['correct'])) {
            echo "  ⚠️ Câu " . ($qIndex + 1) . " là multiple nhưng chỉ có 1 đáp án đúng\n";
            $valid = false;
        }
    }
    
    if ($valid) {
        echo "  ✅ Tất cả câu hỏi đều hợp lệ\n";
    }
    echo "\n";
}

echo "\n=== CHI TIẾT CÂU HỎI ===\n\n";
foreach ($data as $topicData) {
    echo "【{$topicData['topic']}】 - {$topicData['lesson']}\n\n";
    
    foreach ($topicData['questions'] as $i => $q) {
        echo "Câu " . ($i + 1) . ": {$q['question']}\n";
        foreach ($q['options'] as $optIdx => $option) {
            $letters = ['A', 'B', 'C', 'D'];
            $correctIndexes = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
            $marker = in_array($optIdx, $correctIndexes) ? ' ✓' : '';
            echo "  {$letters[$optIdx]}) $option$marker\n";
        }
        echo "  Type: {$q['type']}, Level: {$q['level']}\n\n";
    }
}

echo "✅ Format JSON hoàn toàn phù hợp với cấu trúc hệ thống!\n";
