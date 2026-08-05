<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/student_bulk_common.php';
requireAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $studentsFile = __DIR__ . '/../students.json';
    $classesFile = __DIR__ . '/../classes.json';
    $input = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new InvalidArgumentException('Dữ liệu gửi lên không hợp lệ.');
    }

    $mode = ($input['mode'] ?? 'preview') === 'apply' ? 'apply' : 'preview';
    $rawMappings = $input['mappings'] ?? [];
    $rawOverrides = $input['overrides'] ?? [];
    if (!is_array($rawMappings) || !is_array($rawOverrides)) {
        throw new InvalidArgumentException('Ánh xạ lớp không hợp lệ.');
    }

    $students = readJsonArray($studentsFile);
    $classes = readJsonArray($classesFile);
    $classLookup = [];
    foreach ($classes as $class) {
        $classLookup[(string) $class['id']] = $class;
    }

    $mappings = [];
    foreach ($rawMappings as $sourceId => $targetId) {
        $sourceId = (string) $sourceId;
        $targetId = (string) $targetId;
        if (!isset($classLookup[$sourceId])) {
            throw new InvalidArgumentException('Lớp nguồn không tồn tại: ' . $sourceId);
        }
        if ($targetId !== '__remove__' && !isset($classLookup[$targetId])) {
            throw new InvalidArgumentException('Lớp đích không tồn tại: ' . $targetId);
        }
        $mappings[$sourceId] = $targetId;
    }

    $studentLookup = [];
    foreach ($students as $student) {
        $studentLookup[(string) ($student['id'] ?? '')] = $student;
    }

    $overrides = [];
    foreach ($rawOverrides as $studentId => $targetId) {
        $studentId = (string) $studentId;
        $targetId = (string) $targetId;
        if (!isset($studentLookup[$studentId])) {
            throw new InvalidArgumentException('Không tìm thấy học sinh ngoại lệ: ' . $studentId);
        }
        if ($targetId !== '__remove__' && !isset($classLookup[$targetId])) {
            throw new InvalidArgumentException('Lớp ngoại lệ không tồn tại: ' . $targetId);
        }
        $overrides[$studentId] = $targetId;
    }

    $summary = [
        'moved' => 0,
        'removed' => 0,
        'unchanged' => 0,
        'invalid_class' => 0,
        'by_destination' => [],
        'warnings' => []
    ];
    $updatedStudents = [];

    foreach ($students as $student) {
        $studentId = (string) ($student['id'] ?? '');
        $sourceClassId = (string) ($student['class_id'] ?? '');

        if (!isset($classLookup[$sourceClassId])) {
            $summary['invalid_class']++;
            $summary['warnings'][] = [
                'student_id' => $studentId,
                'code' => $student['code'] ?? '',
                'name' => $student['name'] ?? '',
                'message' => 'Lớp hiện tại không tồn tại'
            ];
            $updatedStudents[] = $student;
            continue;
        }

        $targetClassId = $overrides[$studentId] ?? ($mappings[$sourceClassId] ?? null);
        if ($targetClassId === null || $targetClassId === $sourceClassId) {
            $summary['unchanged']++;
            $updatedStudents[] = $student;
            continue;
        }

        if ($targetClassId === '__remove__') {
            $summary['removed']++;
            continue;
        }

        $student['class_id'] = $targetClassId;
        $summary['moved']++;
        $destinationName = $classLookup[$targetClassId]['name'] ?? $targetClassId;
        $summary['by_destination'][$destinationName] = ($summary['by_destination'][$destinationName] ?? 0) + 1;
        $updatedStudents[] = $student;
    }

    ksort($summary['by_destination'], SORT_NATURAL);

    if ($mode === 'preview') {
        echo json_encode([
            'success' => true,
            'mode' => 'preview',
            'summary' => $summary
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (($input['confirmation'] ?? '') !== 'CHUYEN LOP') {
        throw new InvalidArgumentException('Vui lòng nhập đúng mã xác nhận CHUYEN LOP.');
    }
    if ($summary['moved'] === 0 && $summary['removed'] === 0) {
        throw new InvalidArgumentException('Không có học sinh nào cần cập nhật.');
    }

    normalizeStudentOrder($updatedStudents);
    $backupName = createStudentDataBackup($studentsFile, 'promote');
    writeJsonAtomically($studentsFile, array_values($updatedStudents));

    echo json_encode([
        'success' => true,
        'mode' => 'apply',
        'message' => 'Đã chuyển lớp học sinh thành công.',
        'backup' => $backupName,
        'summary' => $summary
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

