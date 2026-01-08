<?php
$studentsFile = __DIR__ . '/../admin/students.json';
$classesFile = __DIR__ . '/../admin/classes.json';
$students = json_decode(file_get_contents($studentsFile), true);
$classes = json_decode(file_get_contents($classesFile), true);
$classesById = [];
foreach ($classes as $c) $classesById[$c['id']] = $c;
$missing = [];
$counts = [];
foreach ($students as $s) {
    $cid = $s['class_id'] ?? '';
    if (!isset($classesById[$cid])) {
        $missing[] = [$s['code'], $s['name'], $cid];
    } else {
        $counts[$cid] = ($counts[$cid] ?? 0) + 1;
    }
}
echo "Total students: " . count($students) . "\n";
echo "Classes with student counts (sample):\n";
foreach ($counts as $cid => $cnt) {
    echo "  class_id=$cid (code={$classesById[$cid]['code']}) => $cnt students\n";
}
echo "\nStudents with missing/invalid class_id (first 20):\n";
foreach (array_slice($missing,0,20) as $m) {
    echo "  code={$m[0]} | name={$m[1]} | class_id='{$m[2]}'\n";
}
echo "\nTotal missing mappings: " . count($missing) . "\n";
?>