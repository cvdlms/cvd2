<?php
/**
 * Game show "Ai Là Triệu Phú" — sinh câu hỏi từ Ngân hàng câu hỏi.
 *
 * Endpoint:
 *   ?action=meta      → danh sách khối/học kỳ/môn + số câu sẵn có theo mức độ
 *   ?action=questions → chọn câu hỏi theo cấu hình (grade, semester, subjects[], nb/th/vd/vdc)
 *
 * Quyền truy cập: mọi tài khoản đã đăng nhập (giáo viên / admin) — người dẫn chương trình.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('CVD_TEACHER_SESSION');
    session_start();
}

// Người dẫn chương trình phải là tài khoản giáo viên/admin đã đăng nhập
if (empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập tài khoản giáo viên trước khi mở trò chơi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$GRADES = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
$GRADE_LABELS = [
    'khoi6' => 'Khối 6',
    'khoi7' => 'Khối 7',
    'khoi8' => 'Khối 8',
    'khoi9' => 'Khối 9',
];
$SEMESTERS = ['hk1', 'hk2'];
$SEMESTER_LABELS = ['hk1' => 'Học kì 1', 'hk2' => 'Học kì 2'];

$configFile = __DIR__ . '/../admin/system_config.json';
$config = json_decode(file_get_contents($configFile), true);
$defaultSemester = $config['semester']['current'] ?? 'hk1';

$grade = $_GET['grade'] ?? 'khoi7';
$semester = $_GET['semester'] ?? $defaultSemester;

if (!in_array($grade, $GRADES, true)) {
    echo json_encode(['success' => false, 'message' => 'Khối không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($semester, $SEMESTERS, true)) {
    $semester = $defaultSemester;
}

function game_subject_list(): array
{
    $file = __DIR__ . '/../admin/subjects.json';
    if (!is_file($file)) return [];
    $data = json_decode(file_get_contents($file), true) ?: [];
    $list = [];
    foreach ($data as $s) {
        if (empty($s['id'])) continue;
        $list[] = [
            'id' => (int)$s['id'],
            'name' => $s['name'],
            'code' => $s['code'] ?? '',
        ];
    }
    return $list;
}

function game_level_counts(string $grade, string $semester, int $subjectId): array
{
    $file = __DIR__ . '/../teacher/questions/' . $grade . '/' . $semester . '/subject_' . $subjectId . '.json';
    $counts = ['NB' => 0, 'TH' => 0, 'VD' => 0];
    if (!is_file($file)) return $counts;
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) return $counts;

    foreach ($data as $topicData) {
        foreach (($topicData['questions'] ?? []) as $q) {
            $type = $q['type'] ?? 'single';
            // Trò chơi chỉ hiển thị 1 đáp án đúng → bỏ multiple (nhiều đáp án) và essay
            if (!in_array($type, ['single', 'true_false'], true)) continue;
            if (!isset($q['options']) || !is_array($q['options'])) continue;
            if (is_array($q['correct'] ?? null)) continue;
            if (($q['image'] ?? '') !== '') continue;
            $level = $q['level'] ?? 'NB';
            // Ngân hàng chỉ dùng 3 mức Biết / Hiểu / Vận dụng; VDC (cũ) gộp vào VD
            if ($level === 'VDC') $level = 'VD';
            if (!isset($counts[$level])) $level = 'NB';
            $counts[$level]++;
        }
    }
    return $counts;
}

function game_pool(string $grade, string $semester, array $subjectIds): array
{
    $subjects = game_subject_list();
    $names = [];
    foreach ($subjects as $s) $names[$s['id']] = $s['name'];

    $pool = [];
    foreach ($subjectIds as $sid) {
        $file = __DIR__ . '/../teacher/questions/' . $grade . '/' . $semester . '/subject_' . $sid . '.json';
        if (!is_file($file)) continue;
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) continue;

        foreach ($data as $topicData) {
            $topic = $topicData['topic_name'] ?? ($topicData['topic'] ?? '');
            $unit = $topicData['unit_name'] ?? ($topicData['lesson'] ?? '');
            foreach (($topicData['questions'] ?? []) as $q) {
                $type = $q['type'] ?? 'single';
                if (!in_array($type, ['single', 'true_false'], true)) continue;
                if (!isset($q['options']) || !is_array($q['options'])) continue;
                if (is_array($q['correct'] ?? null)) continue;
                if (($q['image'] ?? '') !== '') continue;
                $level = $q['level'] ?? 'NB';
                // Ngân hàng chỉ dùng 3 mức Biết / Hiểu / Vận dụng; VDC (cũ) gộp vào VD
                if ($level === 'VDC') $level = 'VD';
                if (!isset(['NB' => 1, 'TH' => 1, 'VD' => 1][$level])) $level = 'NB';
                // Loại bỏ thẻ <img> (base64) vốn game không hiển thị được
                $text = preg_replace('/<img[^>]*>/i', '', (string)$q['question']);
                $text = preg_replace('/\s+/u', ' ', $text);
                $pool[] = [
                    'question' => trim($text),
                    'options' => $q['options'],
                    'correct' => $q['correct'],
                    'type' => $type,
                    'level' => $level,
                    'sid' => $sid,
                    'subject' => $names[$sid] ?? ('Môn ' . $sid),
                    'topic' => $topic,
                    'unit' => $unit,
                ];
            }
        }
    }
    return $pool;
}

// Xen kẽ các môn đã chọn trong cùng một mức độ, để trò chơi không bị dồn hết về môn có nhiều câu hơn
function game_interleave_subjects(array $list): array
{
    $queues = [];
    foreach ($list as $q) {
        $queues[$q['sid']][] = $q;
    }
    $out = [];
    while ($queues) {
        foreach ($queues as $sid => $queue) {
            if (!$queue) { unset($queues[$sid]); continue; }
            $out[] = array_shift($queue);
            $queues[$sid] = $queue;
        }
    }
    return $out;
}

// Xáo thứ tự đáp án, trả về vị trí đáp án đúng mới
function game_shuffle_options(array $q): array
{
    $opts = $q['options'];
    $correct = $q['correct'];
    $idx = range(0, count($opts) - 1);
    shuffle($idx);
    $newOpts = [];
    $newCorrect = 0;
    foreach ($idx as $old) {
        $newOpts[] = $opts[$old];
        if ($old === $correct) $newCorrect = count($newOpts) - 1;
    }
    $q['options'] = $newOpts;
    $q['correct'] = $newCorrect;
    return $q;
}

// ---------- ACTION: meta ----------
if (($_GET['action'] ?? 'questions') === 'meta') {
    $subjects = [];
    foreach (game_subject_list() as $s) {
        $counts = game_level_counts($grade, $semester, $s['id']);
        $subjects[] = [
            'id' => $s['id'],
            'name' => $s['name'],
            'code' => $s['code'],
            'levels' => $counts,
            'total' => array_sum($counts),
        ];
    }
    echo json_encode([
        'success' => true,
        'grades' => $GRADES,
        'gradeLabels' => $GRADE_LABELS,
        'semesters' => $SEMESTERS,
        'semesterLabels' => $SEMESTER_LABELS,
        'currentSemester' => $defaultSemester,
        'subjects' => $subjects,
        'grade' => $grade,
        'semester' => $semester,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- ACTION: questions ----------
$subjectIds = $_GET['subjects'] ?? [];
if (!is_array($subjectIds)) {
    $subjectIds = [$subjectIds];
}
$subjectIds = array_filter(array_map('intval', $subjectIds), fn($id) => $id > 0);
if (!$subjectIds) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ít nhất một môn học.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$requested = [
    'NB' => max(0, min(30, (int)($_GET['nb'] ?? 5))),
    'TH' => max(0, min(30, (int)($_GET['th'] ?? 4))),
    'VD' => max(0, min(30, (int)($_GET['vd'] ?? 4))),
];

$pool = game_pool($grade, $semester, $subjectIds);
if (!$pool) {
    echo json_encode(['success' => false, 'message' => 'Môn học này chưa có câu hỏi trắc nghiệm ' . $SEMESTER_LABELS[$semester] . ' cho ' . $GRADE_LABELS[$grade] . '. Hãy thêm câu hỏi hoặc chọn môn/khối khác.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Gom theo mức độ, mỗi nhóm xáo thứ tự
$byLevel = ['NB' => [], 'TH' => [], 'VD' => []];
foreach ($pool as $q) {
    $byLevel[$q['level']][] = $q;
}
foreach ($byLevel as $lv => &$list) shuffle($list);
unset($list);
foreach ($byLevel as $lv => $list) $byLevel[$lv] = game_interleave_subjects($list);

// Bảng dự phòng: nếu mức cao không đủ, vay từ mức thấp gần nhất
$borrow = [
    'NB' => [],
    'TH' => ['NB'],
    'VD' => ['TH', 'NB'],
];

$selected = ['NB' => [], 'TH' => [], 'VD' => []];
$warnings = [];
$levelName = ['NB' => 'Nhận biết', 'TH' => 'Thông hiểu', 'VD' => 'Vận dụng'];

foreach (['NB', 'TH', 'VD'] as $lv) {
    $selected[$lv] = array_splice($byLevel[$lv], 0, $requested[$lv]);
    $need = $requested[$lv] - count($selected[$lv]);
    if ($need > 0) {
        foreach ($borrow[$lv] as $source) {
            while ($need > 0 && count($byLevel[$source]) > 0) {
                $selected[$lv][] = array_shift($byLevel[$source]);
                $need--;
            }
        }
    }
    if ($need > 0) {
        $warnings[] = 'Mức "' . $levelName[$lv] . '" không đủ câu hỏi (cần ' . $requested[$lv] . ', có ' . count($selected[$lv]) . '). Trò chơi sẽ tự thích ứng.';
    }
}

$questions = [];
foreach (['NB', 'TH', 'VD'] as $lv) {
    foreach ($selected[$lv] as $q) {
        $questions[] = game_shuffle_options([
            'question' => $q['question'],
            'options' => $q['options'],
            'correct' => $q['correct'],
            'type' => $q['type'],
            'level' => $q['level'],
            'subject' => $q['subject'],
            'topic' => $q['topic'],
            'unit' => $q['unit'],
        ]);
    }
}

if (!$questions) {
    echo json_encode(['success' => false, 'message' => 'Không có câu hỏi phù hợp với cấu hình đã chọn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$actualCounts = ['NB' => 0, 'TH' => 0, 'VD' => 0];
foreach ($questions as $q) $actualCounts[$q['level']]++;

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'metadata' => [
        'grade' => $grade,
        'grade_label' => $GRADE_LABELS[$grade],
        'semester' => $semester,
        'semester_label' => $SEMESTER_LABELS[$semester],
        'requested' => $requested,
        'actual' => $actualCounts,
        'total' => count($questions),
        'warnings' => $warnings,
    ],
], JSON_UNESCAPED_UNICODE);