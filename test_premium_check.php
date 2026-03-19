<?php
// Test script to verify premium status for student 2203269301
require_once 'includes/student_premium_helper.php';

$studentCode = '2203269301';

echo "<h2>Testing Premium Status for Student: {$studentCode}</h2>";
echo "<p>Current Date: " . date('Y-m-d H:i:s') . "</p>";

$status = getStudentPremiumStatus($studentCode);

echo "<h3>Premium Status:</h3>";
echo "<pre>";
print_r($status);
echo "</pre>";

if ($status['is_premium']) {
    echo "<p style='color: green; font-weight: bold;'>✅ Student HAS Premium!</p>";
    echo "<p>Type: {$status['type']}</p>";
    echo "<p>End Date: {$status['end_date']}</p>";
    echo "<p>Days Remaining: {$status['days_remaining']}</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Student DOES NOT have Premium</p>";
}

// Check all premium records for this student
echo "<h3>All Premium Records for this student:</h3>";
$premiumFile = __DIR__ . '/admin/student_premium.json';
$premiumData = json_decode(file_get_contents($premiumFile), true) ?: [];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>#</th><th>Type</th><th>Status</th><th>Start Date</th><th>End Date</th><th>Is Valid?</th></tr>";

$recordNum = 0;
foreach ($premiumData as $record) {
    if ($record['student_code'] === $studentCode) {
        $recordNum++;
        $endDate = strtotime($record['end_date']);
        $isActive = $record['premium_status'] === 'active' && $endDate >= time();
        
        echo "<tr>";
        echo "<td>{$recordNum}</td>";
        echo "<td>{$record['premium_type']}</td>";
        echo "<td>{$record['premium_status']}</td>";
        echo "<td>{$record['start_date']}</td>";
        echo "<td>{$record['end_date']}</td>";
        echo "<td style='color: " . ($isActive ? 'green' : 'red') . "'>" . ($isActive ? '✅ Active' : '❌ Expired') . "</td>";
        echo "</tr>";
    }
}
echo "</table>";

echo "<p><strong>Total Records Found: {$recordNum}</strong></p>";
?>
