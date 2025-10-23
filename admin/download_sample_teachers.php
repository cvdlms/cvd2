<?php
// Sample data for teachers (subject_ids and class_codes can be comma-separated for multiple assignments)
$data = [
    ['username', 'password', 'fullname', 'email', 'dob', 'subject_ids', 'class_codes'],
    ['teacher1', 'password123', 'Nguyễn Văn A', 'teacher1@example.com', '1990-01-01', '1,2', '6A1,6B1'],
    ['teacher2', 'password123', 'Trần Thị B', 'teacher2@example.com', '1985-05-15', '3', '7A1'],
];

// Output CSV file for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sample_teachers.csv"');
header('Cache-Control: max-age=0');

// Open output stream
$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write data
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
