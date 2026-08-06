<?php

require_once __DIR__ . '/../../includes/api_auth.php';

function readJsonArray(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        throw new RuntimeException('Dữ liệu JSON không hợp lệ: ' . basename($path));
    }

    return $data;
}

function normalizeStudentCode($code): string
{
    return mb_strtoupper(trim((string) $code), 'UTF-8');
}

function createStudentDataBackup(string $studentsFile, string $operation): string
{
    $backupDir = dirname(__DIR__, 2) . '/backups/student_operations';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Không thể tạo thư mục backup.');
    }

    $safeOperation = preg_replace('/[^a-z0-9_-]+/i', '-', $operation);
    $backupName = 'students_' . $safeOperation . '_' . date('Y-m-d_His') . '.json';
    $backupPath = $backupDir . '/' . $backupName;

    if (!copy($studentsFile, $backupPath)) {
        throw new RuntimeException('Không thể tạo backup dữ liệu học sinh.');
    }

    return $backupName;
}

function writeJsonAtomically(string $path, array $data): void
{
    $directory = dirname($path);
    $temporaryPath = tempnam($directory, 'students_');
    if ($temporaryPath === false) {
        throw new RuntimeException('Không thể tạo file tạm.');
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($temporaryPath, $json, LOCK_EX) === false) {
        @unlink($temporaryPath);
        throw new RuntimeException('Không thể ghi dữ liệu học sinh.');
    }

    if (DIRECTORY_SEPARATOR === '\\' && file_exists($path)) {
        $oldPath = $path . '.old';
        @unlink($oldPath);
        if (!rename($path, $oldPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Không thể chuẩn bị thay thế file dữ liệu.');
        }
        if (!rename($temporaryPath, $path)) {
            @rename($oldPath, $path);
            @unlink($temporaryPath);
            throw new RuntimeException('Không thể thay thế file dữ liệu.');
        }
        @unlink($oldPath);
        return;
    }

    if (!rename($temporaryPath, $path)) {
        @unlink($temporaryPath);
        throw new RuntimeException('Không thể thay thế file dữ liệu.');
    }
}

function normalizeStudentOrder(array &$students): void
{
    $indexesByClass = [];
    foreach ($students as $index => $student) {
        $classId = (string) ($student['class_id'] ?? '');
        $indexesByClass[$classId][] = $index;
    }

    foreach ($indexesByClass as $indexes) {
        usort($indexes, static function (int $left, int $right) use ($students): int {
            $leftOrder = (int) ($students[$left]['order_index'] ?? PHP_INT_MAX);
            $rightOrder = (int) ($students[$right]['order_index'] ?? PHP_INT_MAX);
            return $leftOrder <=> $rightOrder;
        });

        foreach ($indexes as $position => $studentIndex) {
            $students[$studentIndex]['order_index'] = $position;
        }
    }
}

function nextStudentId(array $students): int
{
    $maximum = 0;
    foreach ($students as $student) {
        $maximum = max($maximum, (int) ($student['id'] ?? 0));
    }

    return $maximum + 1;
}

