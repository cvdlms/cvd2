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

    if (!is_array($input) || !isset($input['students']) || !is_array($input['students'])) {
        throw new InvalidArgumentException('Danh sách học sinh không hợp lệ.');
    }

    $mode = ($input['mode'] ?? 'preview') === 'import' ? 'import' : 'preview';
    $students = readJsonArray($studentsFile);
    $classes = readJsonArray($classesFile);

    $classById = [];
    $classByCode = [];
    foreach ($classes as $class) {
        $classById[(string) $class['id']] = $class;
        $classByCode[mb_strtoupper(trim((string) $class['code']), 'UTF-8')] = $class;
    }

    $existingCodes = [];
    foreach ($students as $student) {
        $existingCodes[normalizeStudentCode($student['code'] ?? '')] = true;
    }

    $seenImportCodes = [];
    $rows = [];
    $validRows = [];
    $counts = ['new' => 0, 'duplicate' => 0, 'invalid' => 0];

    foreach ($input['students'] as $index => $row) {
        $code = normalizeStudentCode($row['code'] ?? '');
        $name = trim((string) ($row['name'] ?? ''));
        $gender = trim((string) ($row['gender'] ?? ''));
        $birthDate = trim((string) ($row['birth_date'] ?? ''));
        $classCode = mb_strtoupper(trim((string) ($row['class_code'] ?? '')), 'UTF-8');
        $classId = trim((string) ($row['class_id'] ?? ''));
        $class = $classId !== '' ? ($classById[$classId] ?? null) : ($classByCode[$classCode] ?? null);
        $status = 'new';
        $message = 'Sẵn sàng thêm';

        if ($code === '' || $name === '' || $gender === '' || $birthDate === '') {
            $status = 'invalid';
            $message = 'Thiếu trường bắt buộc';
        } elseif ($class === null) {
            $status = 'invalid';
            $message = 'Mã lớp không tồn tại';
        } elseif (isset($existingCodes[$code])) {
            $status = 'duplicate';
            $message = 'Mã học sinh đã tồn tại, sẽ bỏ qua';
        } elseif (isset($seenImportCodes[$code])) {
            $status = 'duplicate';
            $message = 'Mã học sinh bị trùng trong file, sẽ bỏ qua';
        }

        $seenImportCodes[$code] = true;
        $counts[$status]++;
        $previewRow = [
            'row' => $index + 2,
            'code' => $code,
            'name' => $name,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'class_id' => $class['id'] ?? '',
            'class_code' => $class['code'] ?? $classCode,
            'class_name' => $class['name'] ?? $classCode,
            'status' => $status,
            'message' => $message
        ];
        $rows[] = $previewRow;

        if ($status === 'new') {
            $validRows[] = $previewRow;
        }
    }

    if ($mode === 'preview') {
        echo json_encode([
            'success' => true,
            'mode' => 'preview',
            'counts' => $counts,
            'rows' => $rows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (($input['confirmation'] ?? '') !== 'NHAP HOC SINH') {
        throw new InvalidArgumentException('Vui lòng nhập đúng mã xác nhận NHAP HOC SINH.');
    }
    if (count($validRows) === 0) {
        throw new InvalidArgumentException('Không có học sinh mới hợp lệ để nhập.');
    }

    $nextId = nextStudentId($students);
    $nextOrderByClass = [];
    foreach ($students as $student) {
        $classId = (string) ($student['class_id'] ?? '');
        $nextOrderByClass[$classId] = max(
            $nextOrderByClass[$classId] ?? 0,
            ((int) ($student['order_index'] ?? -1)) + 1
        );
    }

    foreach ($validRows as $row) {
        $classId = (string) $row['class_id'];
        $students[] = [
            'id' => (string) $nextId++,
            'code' => $row['code'],
            'name' => $row['name'],
            'gender' => $row['gender'],
            'birth_date' => $row['birth_date'],
            'class_id' => $classId,
            'email' => '',
            'notes' => '',
            'password' => '123456',
            'order_index' => $nextOrderByClass[$classId] ?? 0
        ];
        $nextOrderByClass[$classId] = ($nextOrderByClass[$classId] ?? 0) + 1;
    }

    $backupName = createStudentDataBackup($studentsFile, 'bulk-import');
    writeJsonAtomically($studentsFile, array_values($students));

    echo json_encode([
        'success' => true,
        'mode' => 'import',
        'message' => 'Đã nhập học sinh mới thành công.',
        'backup' => $backupName,
        'counts' => $counts,
        'imported' => count($validRows)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

