<?php
/**
 * Script để dọn dẹp file premium data - loại bỏ các giá trị null
 * Chỉ chạy 1 lần để làm sạch dữ liệu
 */

$premiumFile = __DIR__ . '/student_premium.json';
$requestsFile = __DIR__ . '/student_premium_requests.json';

echo "<h2>Cleanup Premium Data</h2>";

// Clean premium data
if (file_exists($premiumFile)) {
    $premiumData = json_decode(file_get_contents($premiumFile), true);
    
    if (is_array($premiumData)) {
        $originalCount = count($premiumData);
        
        // Remove null values
        $premiumData = array_filter($premiumData, function($item) {
            return $item !== null && is_array($item);
        });
        
        // Re-index array (remove gaps)
        $premiumData = array_values($premiumData);
        
        $cleanCount = count($premiumData);
        $removed = $originalCount - $cleanCount;
        
        if ($removed > 0) {
            // Backup original file
            copy($premiumFile, $premiumFile . '.backup.' . date('Ymd_His'));
            
            // Save cleaned data
            file_put_contents($premiumFile, json_encode($premiumData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            echo "<p style='color: green;'>✅ Cleaned student_premium.json</p>";
            echo "<p>Original records: {$originalCount}</p>";
            echo "<p>After cleanup: {$cleanCount}</p>";
            echo "<p>Removed null records: {$removed}</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ student_premium.json is already clean (no null values)</p>";
        }
    }
}

// Clean requests data
if (file_exists($requestsFile)) {
    $requestsData = json_decode(file_get_contents($requestsFile), true);
    
    if (is_array($requestsData)) {
        $originalCount = count($requestsData);
        
        // Remove null values
        $requestsData = array_filter($requestsData, function($item) {
            return $item !== null && is_array($item);
        });
        
        // Re-index array
        $requestsData = array_values($requestsData);
        
        $cleanCount = count($requestsData);
        $removed = $originalCount - $cleanCount;
        
        if ($removed > 0) {
            // Backup original file
            copy($requestsFile, $requestsFile . '.backup.' . date('Ymd_His'));
            
            // Save cleaned data
            file_put_contents($requestsFile, json_encode($requestsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            echo "<p style='color: green;'>✅ Cleaned student_premium_requests.json</p>";
            echo "<p>Original records: {$originalCount}</p>";
            echo "<p>After cleanup: {$cleanCount}</p>";
            echo "<p>Removed null records: {$removed}</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ student_premium_requests.json is already clean (no null values)</p>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Done!</strong> Backup files created with timestamp.</p>";
echo "<p><a href='manage_student_premium.php'>← Back to Manage Premium</a></p>";
?>
