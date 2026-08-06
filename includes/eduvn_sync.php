<?php
/**
 * Trợ giúp đồng bộ dữ liệu giữa CVDLMS và EduVN (import + push)
 */

$eduvnSyncConfig = file_exists(__DIR__ . '/sso_config.php') ? require __DIR__ . '/sso_config.php' : [];

function eduvn_sync_config(string $key, $default = '') {
    global $eduvnSyncConfig;
    return $eduvnSyncConfig[$key] ?? $default;
}

function eduvn_sync_base_url(): string {
    $base = eduvn_sync_config('eduvn_base_url', '');
    if ($base !== '') {
        return rtrim($base, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/eduvn';
}

function eduvn_sync_request(string $path, array $payload = [], string $method = 'POST'): array {
    $url = eduvn_sync_base_url() . '/' . ltrim($path, '/');
    $key = eduvn_sync_config('sync_api_key', '');
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json', 'X-API-Key: ' . $key];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'decoded' => ($body !== false && $body !== '') ? json_decode($body, true) : null,
        'raw' => $body === false ? null : $body,
        'error' => $error,
    ];
}

function eduvn_sync_json_read(string $file): array {
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function eduvn_sync_json_write(string $file, array $data): bool {
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function eduvn_sync_fetch_all(): array {
    return eduvn_sync_request('tools/cvdlms_data_api.php?entity=all', [], 'GET');
}

function eduvn_sync_gender_to_cvd(?string $gender): string {
    $g = strtolower(trim((string)$gender));
    if ($g === 'male' || $g === 'nam') {
        return 'Nam';
    }
    if ($g === 'female' || $g === 'nữ' || $g === 'nu') {
        return 'Nữ';
    }
    return trim((string)$gender);
}

function eduvn_sync_gender_to_edu(?string $gender): string {
    $g = strtolower(trim((string)$gender));
    if ($g === 'nam') {
        return 'male';
    }
    if ($g === 'nữ' || $g === 'nu') {
        return 'female';
    }
    return trim((string)$gender);
}

function eduvn_sync_to_dmy(?string $date): string {
    $d = trim((string)$date);
    if ($d === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        $parts = explode('-', $d);
        return $parts[2] . '/' . $parts[1] . '/' . $parts[0];
    }
    return $d;
}

function eduvn_sync_to_ymd(?string $date): string {
    $d = trim((string)$date);
    if ($d === '') {
        return '';
    }
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $d, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return $d;
}

function eduvn_sync_make_username(string $email, string $fullName, string $code): string {
    $candidate = '';
    if ($email !== '' && strpos($email, '@') !== false) {
        $candidate = explode('@', $email)[0];
    }
    if ($candidate === '') {
        $candidate = $code;
    }
    if ($candidate === '') {
        $candidate = preg_replace('/[^a-zA-Z0-9]/u', '', $fullName);
    }
    $candidate = strtolower(preg_replace('/[^a-zA-Z0-9_.-]/', '', $candidate));
    return $candidate !== '' ? $candidate : 'user' . substr(md5($fullName . $code), 0, 6);
}

/**
 * Import dữ liệu từ EduVN vào CVDLMS (lớp, môn, học sinh, giáo viên, phân công).
 * $edu là mảng nhận từ cvdlms_data_api (có khóa: classes, subjects, students, teachers, teaching_assignments).
 */
function eduvn_sync_import(array $edu): array {
    $adminDir = dirname(__DIR__) . '/admin';
    $classesFile = $adminDir . '/classes.json';
    $subjectsFile = $adminDir . '/subjects.json';
    $studentsFile = $adminDir . '/students.json';
    $usersFile = $adminDir . '/user.json';
    $teacherClassesFile = $adminDir . '/teacher_classes.json';
    $teacherSubjectsFile = $adminDir . '/teacher_subjects.json';

    $classes = eduvn_sync_json_read($classesFile);
    $subjects = eduvn_sync_json_read($subjectsFile);
    $students = eduvn_sync_json_read($studentsFile);
    $users = eduvn_sync_json_read($usersFile);
    $teacherClasses = eduvn_sync_json_read($teacherClassesFile);
    $teacherSubjects = eduvn_sync_json_read($teacherSubjectsFile);

    $report = [
        'classes' => ['added' => 0, 'updated' => 0, 'matched' => 0],
        'subjects' => ['added' => 0, 'updated' => 0, 'matched' => 0],
        'students' => ['added' => 0, 'updated' => 0, 'matched' => 0],
        'teachers' => ['added' => 0, 'updated' => 0, 'matched' => 0],
        'assignments' => ['teachers' => 0, 'classes' => 0, 'subjects' => 0],
    ];

    $maxIntId = function (array $items): int {
        $m = 0;
        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id > $m) {
                $m = $id;
            }
        }
        return $m;
    };

    // ---- Lớp học ----
    $classNext = $maxIntId($classes);
    $classByEduId = [];
    foreach (($edu['classes'] ?? []) as $ec) {
        if (!is_array($ec)) {
            continue;
        }
        $eduId = (string)($ec['id'] ?? '');
        $code = trim((string)($ec['name'] ?? $ec['code'] ?? ''));
        if ($code === '') {
            continue;
        }
        $idx = null;
        foreach ($classes as $i => $c) {
            if ((string)($c['eduvn_id'] ?? '') === $eduId || (isset($c['code']) && (string)$c['code'] === $code)) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            $classNext++;
            $classes[] = [
                'id' => (string)$classNext,
                'code' => $code,
                'name' => $code,
                'year' => (string)($ec['school_year'] ?? ''),
                'teacher' => '',
                'eduvn_id' => $eduId,
            ];
            $idx = count($classes) - 1;
            $report['classes']['added']++;
        } else {
            if (!empty($ec['school_year'])) {
                $classes[$idx]['year'] = (string)$ec['school_year'];
            }
            if (isset($classes[$idx]['name']) && trim((string)$classes[$idx]['name']) === '') {
                $classes[$idx]['name'] = $code;
            }
            $classes[$idx]['eduvn_id'] = $eduId;
            $report['classes']['updated']++;
        }
        $classByEduId[$eduId] = (string)$classes[$idx]['id'];
    }
    $report['classes']['matched'] = count($classByEduId);
    eduvn_sync_json_write($classesFile, $classes);

    // ---- Môn học ----
    $subjectNext = $maxIntId($subjects);
    $subjectByEduId = [];
    foreach (($edu['subjects'] ?? []) as $es) {
        if (!is_array($es)) {
            continue;
        }
        $eduId = (string)($es['id'] ?? '');
        $name = trim((string)($es['name'] ?? ''));
        $code = strtoupper(trim((string)($es['code'] ?? '')));
        if ($name === '' && $code === '') {
            continue;
        }
        $idx = null;
        foreach ($subjects as $i => $s) {
            if ((string)($s['eduvn_id'] ?? '') === $eduId || (isset($s['code']) && strtoupper((string)$s['code']) === $code)) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            $subjectNext++;
            $subjects[] = [
                'id' => $subjectNext,
                'name' => $name !== '' ? $name : $code,
                'code' => $code !== '' ? $code : strtolower($name),
                'eduvn_id' => $eduId,
            ];
            $idx = count($subjects) - 1;
            $report['subjects']['added']++;
        } else {
            if ($name !== '') {
                $subjects[$idx]['name'] = $name;
            }
            if ($code !== '') {
                $subjects[$idx]['code'] = $code;
            }
            $subjects[$idx]['eduvn_id'] = $eduId;
            $report['subjects']['updated']++;
        }
        $subjectByEduId[$eduId] = (int)$subjects[$idx]['id'];
    }
    $report['subjects']['matched'] = count($subjectByEduId);
    eduvn_sync_json_write($subjectsFile, $subjects);

    // ---- Học sinh ----
    $studentNext = $maxIntId($students);
    $studentByEduId = [];
    $orderByClass = [];
    foreach (($edu['students'] ?? []) as $es) {
        if (!is_array($es)) {
            continue;
        }
        $eduId = (string)($es['id'] ?? '');
        $code = trim((string)($es['code'] ?? ''));
        if ($code === '') {
            continue;
        }
        $name = trim((string)($es['name'] ?? ''));
        $gender = eduvn_sync_gender_to_cvd($es['gender'] ?? '');
        $dob = eduvn_sync_to_dmy($es['dob'] ?? '');
        $classId = $classByEduId[(string)($es['class_id'] ?? '')] ?? '';

        $idx = null;
        foreach ($students as $i => $st) {
            if ((string)($st['eduvn_id'] ?? '') === $eduId || (isset($st['code']) && (string)$st['code'] === $code)) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            $studentNext++;
            $students[] = [
                'id' => (string)$studentNext,
                'code' => $code,
                'name' => $name,
                'gender' => $gender,
                'birth_date' => $dob,
                'class_id' => $classId,
                'email' => '',
                'notes' => '',
                'password' => '123456',
                'order_index' => 0,
                'eduvn_id' => $eduId,
            ];
            $idx = count($students) - 1;
            $report['students']['added']++;
        } else {
            if ($name !== '') {
                $students[$idx]['name'] = $name;
            }
            if ($gender !== '') {
                $students[$idx]['gender'] = $gender;
            }
            if ($dob !== '') {
                $students[$idx]['birth_date'] = $dob;
            }
            if ($classId !== '') {
                $students[$idx]['class_id'] = $classId;
            }
            $students[$idx]['eduvn_id'] = $eduId;
            $report['students']['updated']++;
        }
        $studentByEduId[$eduId] = (string)$students[$idx]['id'];
    }

    // Chuẩn hóa lại order_index theo từng lớp
    $classIndexes = [];
    foreach ($students as $i => $st) {
        $classKey = (string)($st['class_id'] ?? '');
        if (!isset($classIndexes[$classKey])) {
            $classIndexes[$classKey] = 0;
        }
        $students[$i]['order_index'] = $classIndexes[$classKey];
        $classIndexes[$classKey]++;
    }
    $report['students']['matched'] = count($studentByEduId);
    eduvn_sync_json_write($studentsFile, $students);

    // ---- Giáo viên (tài khoản) ----
    $teacherByEduId = [];
    foreach (($edu['teachers'] ?? []) as $et) {
        if (!is_array($et)) {
            continue;
        }
        $eduId = (string)($et['id'] ?? '');
        $fullName = trim((string)($et['name'] ?? ''));
        $email = trim((string)($et['email'] ?? $et['school_email'] ?? ''));
        if ($fullName === '') {
            continue;
        }
        $username = null;
        foreach ($users as $un => $u) {
            if ((string)($u['eduvn_id'] ?? '') === $eduId) {
                $username = (string)$un;
                break;
            }
            if ($email !== '' && strtolower((string)($u['email'] ?? '')) === strtolower($email)) {
                $username = (string)$un;
                break;
            }
        }
        if ($username === null) {
            $username = eduvn_sync_make_username($email, $fullName, (string)($et['code'] ?? ''));
            $baseUsername = $username;
            $suffix = 1;
            while (isset($users[$username])) {
                $username = $baseUsername . '_' . $suffix;
                $suffix++;
            }
            $users[$username] = [
                'fullname' => $fullName,
                'username' => $username,
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'email' => $email,
                'dob' => eduvn_sync_to_ymd($et['dob'] ?? ''),
                'eduvn_id' => $eduId,
                'created_via_eduvn' => true,
            ];
            $report['teachers']['added']++;
        } else {
            if (!empty($fullName)) {
                $users[$username]['fullname'] = $fullName;
            }
            if (!empty($email)) {
                $users[$username]['email'] = $email;
            }
            if (empty($users[$username]['password'])) {
                $users[$username]['password'] = password_hash('123456', PASSWORD_DEFAULT);
            }
            $users[$username]['eduvn_id'] = $eduId;
            $report['teachers']['updated']++;
        }
        $teacherByEduId[$eduId] = $username;
    }
    $report['teachers']['matched'] = count($teacherByEduId);
    eduvn_sync_json_write($usersFile, $users);

    // ---- Phân công giảng dạy ----
    foreach (($edu['teaching_assignments'] ?? []) as $ta) {
        if (!is_array($ta)) {
            continue;
        }
        $tEduId = (string)($ta['teacher_id'] ?? '');
        $username = $teacherByEduId[$tEduId] ?? null;
        if ($username === null) {
            continue;
        }
        if (!isset($teacherClasses[$username])) {
            $teacherClasses[$username] = [];
        }
        if (!isset($teacherSubjects[$username])) {
            $teacherSubjects[$username] = [];
        }
        $classIds = [];
        foreach (($ta['class_ids'] ?? []) as $cid) {
            $cvdId = $classByEduId[(string)$cid] ?? null;
            if ($cvdId !== null && !in_array($cvdId, $teacherClasses[$username], true)) {
                $teacherClasses[$username][] = $cvdId;
            }
            if ($cvdId !== null) {
                $classIds[] = $cvdId;
            }
        }
        $subjectIds = [];
        foreach (($ta['subject_ids'] ?? []) as $sid) {
            $cvdId = $subjectByEduId[(string)$sid] ?? null;
            if ($cvdId !== null && !in_array($cvdId, $teacherSubjects[$username], true)) {
                $teacherSubjects[$username][] = $cvdId;
            }
            if ($cvdId !== null) {
                $subjectIds[] = $cvdId;
            }
        }
        if (!empty($classIds)) {
            $report['assignments']['classes'] += count($classIds);
        }
        if (!empty($subjectIds)) {
            $report['assignments']['subjects'] += count($subjectIds);
        }
        $report['assignments']['teachers']++;
    }
    eduvn_sync_json_write($teacherClassesFile, $teacherClasses);
    eduvn_sync_json_write($teacherSubjectsFile, $teacherSubjects);

    return $report;
}

/**
 * Gom kết quả thi (chính thức + luyện tập) của CVDLMS để đẩy sang EduVN.
 */
function eduvn_sync_collect_results(bool $includeDetails = false): array {
    $results = [];
    $scoresDir = dirname(__DIR__) . '/shared/scores';
    $perStudentFiles = glob($scoresDir . '/[0-9]*.json');
    if (is_array($perStudentFiles)) {
        foreach ($perStudentFiles as $file) {
            $records = eduvn_sync_json_read($file);
            foreach ($records as $r) {
                if (!is_array($r) || empty($r['id'])) {
                    continue;
                }
                $results[] = eduvn_sync_normalize_result($r, $includeDetails);
            }
        }
    }

    $practiceFile = dirname(__DIR__) . '/data/practice_results/practice_results.json';
    foreach (eduvn_sync_json_read($practiceFile) as $r) {
        if (!is_array($r) || empty($r['id'])) {
            continue;
        }
        $results[] = eduvn_sync_normalize_result($r, $includeDetails);
    }

    return $results;
}

function eduvn_sync_normalize_result(array $r, bool $includeDetails): array {
    $subjectMap = [];
    $subjectFile = dirname(__DIR__) . '/admin/subjects.json';
    foreach (eduvn_sync_json_read($subjectFile) as $s) {
        $subjectMap[(string)($s['id'] ?? '')] = [
            'code' => (string)($s['code'] ?? ''),
            'name' => (string)($s['name'] ?? ''),
        ];
    }

    $subjectId = (string)($r['subject_id'] ?? '');
    $item = [
        'id' => (string)($r['id'] ?? ''),
        'student_code' => (string)($r['student_code'] ?? $r['student_id'] ?? ''),
        'student_name' => (string)($r['student_name'] ?? ''),
        'class_code' => (string)($r['class_code'] ?? ''),
        'exam_id' => (string)($r['source_exam_id'] ?? $r['exam_id'] ?? ''),
        'result_id' => (string)($r['id'] ?? ''),
        'test_name' => (string)($r['test_name'] ?? ''),
        'subject_id' => $subjectId,
        'subject_code' => $subjectMap[$subjectId]['code'] ?? '',
        'subject_name' => $subjectMap[$subjectId]['name'] ?? (string)($r['subject_name'] ?? ''),
        'score' => (float)($r['score'] ?? 0),
        'max_score' => 10.0,
        'total_questions' => (int)($r['total_questions'] ?? 0),
        'correct_answers' => (int)($r['correct_answers'] ?? 0),
        'attempts' => max(1, (int)($r['attempt'] ?? $r['attempts'] ?? 1)),
        'timestamp' => (string)($r['timestamp'] ?? ''),
        'is_practice' => !empty($r['is_practice']),
        'notes' => (string)($r['notes'] ?? ''),
    ];
    if ($includeDetails && !empty($r['question_results'])) {
        $item['question_results'] = $r['question_results'];
    }
    return $item;
}

/**
 * Gom tài khoản + phân lớp của CVDLMS để đẩy sang EduVN.
 */
function eduvn_sync_collect_accounts(): array {
    $adminDir = dirname(__DIR__) . '/admin';
    $classes = eduvn_sync_json_read($adminDir . '/classes.json');
    $subjects = eduvn_sync_json_read($adminDir . '/subjects.json');
    $students = eduvn_sync_json_read($adminDir . '/students.json');
    $users = eduvn_sync_json_read($adminDir . '/user.json');
    $teacherClasses = eduvn_sync_json_read($adminDir . '/teacher_classes.json');
    $teacherSubjects = eduvn_sync_json_read($adminDir . '/teacher_subjects.json');

    $classCodeById = [];
    foreach ($classes as $c) {
        $classCodeById[(string)($c['id'] ?? '')] = (string)($c['code'] ?? '');
    }

    $studentPayload = [];
    foreach ($students as $st) {
        $classId = (string)($st['class_id'] ?? '');
        $studentPayload[] = [
            'code' => (string)($st['code'] ?? ''),
            'name' => (string)($st['name'] ?? ''),
            'gender' => eduvn_sync_gender_to_edu($st['gender'] ?? ''),
            'birth_date' => eduvn_sync_to_ymd($st['birth_date'] ?? ''),
            'class_code' => $classCodeById[$classId] ?? '',
            'email' => (string)($st['email'] ?? ''),
            'username' => (string)($st['code'] ?? ''),
            'password' => (string)($st['password'] ?? '123456'),
        ];
    }

    $teacherPayload = [];
    foreach ($users as $username => $u) {
        if ($username === 'admin' || ($u['created_via_eduvn'] ?? false) === true) {
            continue;
        }
        $teacherPayload[] = [
            'username' => (string)$username,
            'fullname' => (string)($u['fullname'] ?? $username),
            'email' => (string)($u['email'] ?? ''),
            'dob' => (string)($u['dob'] ?? ''),
        ];
    }

    $assignmentPayload = [];
    foreach ($teacherClasses as $username => $classIds) {
        $classCodes = [];
        foreach ((array)$classIds as $cid) {
            if (isset($classCodeById[(string)$cid]) && $classCodeById[(string)$cid] !== '') {
                $classCodes[] = $classCodeById[(string)$cid];
            }
        }
        $subjectCodes = [];
        foreach ((array)($teacherSubjects[$username] ?? []) as $sid) {
            foreach ($subjects as $s) {
                if ((string)($s['id'] ?? '') === (string)$sid) {
                    $subjectCodes[] = (string)($s['code'] ?? '');
                    break;
                }
            }
        }
        $assignmentPayload[] = [
            'username' => (string)$username,
            'class_codes' => array_values(array_unique($classCodes)),
            'subject_codes' => array_values(array_unique($subjectCodes)),
        ];
    }

    $classPayload = [];
    foreach ($classes as $c) {
        $classPayload[] = [
            'code' => (string)($c['code'] ?? ''),
            'name' => (string)($c['name'] ?? $c['code'] ?? ''),
            'year' => (string)($c['year'] ?? ''),
        ];
    }

    $subjectPayload = [];
    foreach ($subjects as $s) {
        $subjectPayload[] = [
            'code' => (string)($s['code'] ?? ''),
            'name' => (string)($s['name'] ?? ''),
            'cvdlms_id' => (string)($s['id'] ?? ''),
        ];
    }

    return [
        'classes' => $classPayload,
        'subjects' => $subjectPayload,
        'students' => $studentPayload,
        'teachers' => $teacherPayload,
        'teacher_assignments' => $assignmentPayload,
    ];
}

function eduvn_sync_push_results(bool $includeDetails = false): array {
    $results = eduvn_sync_collect_results($includeDetails);
    return eduvn_sync_request('public/tools/cvdlms_push_api.php', [
        'action' => 'results',
        'results' => $results,
    ]);
}

function eduvn_sync_push_accounts(): array {
    $accounts = eduvn_sync_collect_accounts();
    return eduvn_sync_request('public/tools/cvdlms_push_api.php', [
        'action' => 'accounts',
        'accounts' => $accounts,
    ]);
}
