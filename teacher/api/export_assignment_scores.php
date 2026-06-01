<?php
session_name('CVD_TEACHER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$assignmentId = $_GET['assignment_id'] ?? '';
if ($assignmentId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing assignment ID']);
    exit;
}

$assignmentsFile = __DIR__ . '/../../data/assignments.json';
$submissionsFile = __DIR__ . '/../../data/student_submissions.json';
$studentsFile = __DIR__ . '/../../admin/students.json';
$classesFile = __DIR__ . '/../../admin/classes.json';

$assignments = file_exists($assignmentsFile) ? json_decode(file_get_contents($assignmentsFile), true) : [];
$submissions = file_exists($submissionsFile) ? json_decode(file_get_contents($submissionsFile), true) : [];
$students = file_exists($studentsFile) ? json_decode(file_get_contents($studentsFile), true) : [];
$classes = file_exists($classesFile) ? json_decode(file_get_contents($classesFile), true) : [];

if (!is_array($assignments)) $assignments = [];
if (!is_array($submissions)) $submissions = [];
if (!is_array($students)) $students = [];
if (!is_array($classes)) $classes = [];

function normalizeClassNamesForExport($assignment) {
    $raw = $assignment['class_names'] ?? $assignment['class_name'] ?? [];
    if (is_string($raw)) {
        $raw = [$raw];
    }

    $normalized = [];
    foreach ((array)$raw as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $normalized[] = $value;
        }
    }

    return array_values(array_unique($normalized));
}

$assignment = null;
foreach ($assignments as $item) {
    if (($item['id'] ?? '') === $assignmentId && ($item['teacher_username'] ?? '') === $_SESSION['username']) {
        $assignment = $item;
        break;
    }
}

if (!$assignment) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    exit;
}

$classById = [];
foreach ($classes as $class) {
    $classById[(string)$class['id']] = $class;
}

$allowedClasses = array_map('strtolower', normalizeClassNamesForExport($assignment));
$studentByCode = [];
foreach ($students as $student) {
    $studentByCode[(string)($student['code'] ?? '')] = $student;
}

$submissionByStudentCode = [];
function assignSubmissionToStudent(&$submissionByStudentCode, $studentCode, $submission) {
    $studentCode = trim((string)$studentCode);
    if ($studentCode === '') {
        return;
    }

    if (!isset($submissionByStudentCode[$studentCode])) {
        $submissionByStudentCode[$studentCode] = $submission;
        return;
    }

    $currentTime = strtotime($submissionByStudentCode[$studentCode]['submitted_at'] ?? '') ?: 0;
    $newTime = strtotime($submission['submitted_at'] ?? '') ?: 0;
    if ($newTime >= $currentTime) {
        $submissionByStudentCode[$studentCode] = $submission;
    }
}

function parseGroupMemberCode($member) {
    $member = trim((string)$member);
    if ($member === '') {
        return '';
    }

    $parts = explode(' - ', $member, 2);
    return trim($parts[0]);
}

foreach ($submissions as $submission) {
    if (($submission['assignment_id'] ?? '') === $assignmentId) {
        $submitterCode = (string)($submission['student_code'] ?? '');
        assignSubmissionToStudent($submissionByStudentCode, $submitterCode, $submission);

        $groupMembers = $submission['group_members'] ?? [];
        if (is_array($groupMembers)) {
            foreach ($groupMembers as $member) {
                $memberCode = parseGroupMemberCode($member);
                assignSubmissionToStudent($submissionByStudentCode, $memberCode, $submission);
            }
        }
    }
}

$rows = [];
foreach ($students as $student) {
    $class = $classById[(string)($student['class_id'] ?? '')] ?? null;
    $classCode = $class['code'] ?? $class['name'] ?? '';
    if (!in_array(strtolower(trim((string)$classCode)), $allowedClasses, true)) {
        continue;
    }

    $studentCode = (string)($student['code'] ?? '');
    $submission = $submissionByStudentCode[$studentCode] ?? null;
    $groupMembers = $submission['group_members'] ?? [];
    if (!is_array($groupMembers)) {
        $groupMembers = [];
    }
    $submittedByCode = (string)($submission['student_code'] ?? '');
    $submittedByName = $studentByCode[$submittedByCode]['name'] ?? ($submission['student_name'] ?? '');
    $isGroupMemberScore = $submission && $submittedByCode !== $studentCode;

    $rows[] = [
        'student_code' => $studentCode,
        'student_name' => $student['name'] ?? '',
        'class_name' => $classCode,
        'submitted_at' => $submission['submitted_at'] ?? '',
        'score' => $submission['score'] ?? '',
        'feedback' => $submission['feedback'] ?? '',
        'status' => $submission ? (($submission['score'] ?? null) !== null ? 'Đã chấm' : 'Chưa chấm') : 'Chưa nộp',
        'score_source' => $isGroupMemberScore ? 'Thành viên nhóm' : ($submission ? 'Người nộp' : ''),
        'submitted_by' => $submission ? trim($submittedByCode . ' - ' . $submittedByName) : '',
        'group_members' => implode(', ', $groupMembers)
    ];
}

usort($rows, function($a, $b) {
    $classCompare = strnatcasecmp($a['class_name'], $b['class_name']);
    if ($classCompare !== 0) return $classCompare;
    return strnatcasecmp($a['student_name'], $b['student_name']);
});

echo json_encode([
    'success' => true,
    'assignment' => [
        'id' => $assignment['id'],
        'title' => $assignment['title'] ?? '',
        'max_score' => $assignment['max_score'] ?? ''
    ],
    'rows' => $rows
], JSON_UNESCAPED_UNICODE);
?>
