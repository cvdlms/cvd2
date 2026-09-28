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

/**
 * subjects.json có thể chứa nhiều bản ghi trùng tên cho cùng một môn
 * (đồng bộ EduVN tạo id mới thay vì tái sử dụng id cũ).
 * Chuẩn hoá tên để gom về 1 môn duy nhất, không phụ thuộc dấu/hoa-thường.
 */
function game_normalize_subject_name(string $name): string
{
    static $map = null;
    if ($map === null) {
        $groups = [
            'a' => 'àáạảâầấậẩẫăằắặẳẵ',
            'e' => 'èéẹẻêềếệểễ',
            'i' => 'ìíịỉĩ',
            'o' => 'òóọỏôồốộổỗơờớợỡở',
            'u' => 'ùúụủũưừứựửữ',
            'y' => 'ỳýỵỷỹ',
            'd' => 'đ',
        ];
        $map = [];
        foreach ($groups as $base => $chars) {
            foreach (preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $map[$ch] = $base;
            }
        }
    }
    $s = mb_strtolower(trim($name), 'UTF-8');
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
    return trim((string)$s);
}

/**
 * Danh sách môn đã gộp trùng tên. Mỗi môn trả về:
 *   id  → id chính (dùng cho giao diện & URL)
 *   ids → toàn bộ id trùng tên (dùng để gộp ngân hàng câu hỏi)
 */
function game_subject_list(): array
{
    $file = __DIR__ . '/../admin/subjects.json';
    if (!is_file($file)) return [];
    $data = json_decode(file_get_contents($file), true) ?: [];

    $override = game_subjects_override();
    $groups = [];
    foreach ($data as $s) {
        if (!is_array($s) || empty($s['id'])) continue;
        $id = (int)$s['id'];
        $name = trim((string)($override[$id] ?? ($s['name'] ?? '')));
        $code = trim((string)($s['code'] ?? ''));

        $key = game_normalize_subject_name($name);
        if ($key === '') $key = 'subject_id_' . $id;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'id' => $id,
                'name' => $name !== '' ? $name : ('Môn ' . $id),
                'code' => $code,
                'ids' => [$id],
            ];
            continue;
        }
        $groups[$key]['ids'][] = $id;
        if ($groups[$key]['code'] === '' && $code !== '') {
            $groups[$key]['code'] = $code;
        }
    }

    return array_values($groups);
}

/**
 * Danh bạ môn học cũ (trước khi đồng bộ EduVN gán lại id mới).
 * Các file ngân hàng câu hỏi subject_<id>.json vẫn được đặt tên theo id cũ này,
 * trong khi subjects.json đã bị đổi sang id mới (vd 1→Tin học thành 18, 2→Toán thành 20...).
 * Giữ ánh xạ này để game nhận diện đúng môn học dù id cũ không còn tồn tại trong subjects.json.
 */
const GAME_LEGACY_SUBJECTS = [
    1 => ['name' => 'Tin học', 'code' => 'tin'],
    2 => ['name' => 'Toán học', 'code' => 'toan'],
    3 => ['name' => 'Anh văn', 'code' => 'anh'],
    4 => ['name' => 'Ngữ văn', 'code' => 'van'],
    5 => ['name' => 'Công nghệ', 'code' => 'congnghe'],
    6 => ['name' => 'Khoa học tự nhiên', 'code' => 'khtn'],
    7 => ['name' => 'Lịch sử và Địa lí', 'code' => 'su-dia'],
    8 => ['name' => 'Khoa học tự nhiên', 'code' => 'khtn'],
    9 => ['name' => 'Giáo dục công dân', 'code' => 'gdcd'],
];

function game_legacy_subjects(): array
{
    return GAME_LEGACY_SUBJECTS;
}

/**
 * Ghi đè tên môn theo id ngân hàng câu hỏi cho từng deployment.
 * File runtime admin/game_subjects_override.json: { "<subjectId>": "<Tên môn đúng>" }.
 * Dùng khi id trong subjects.json (hoặc id cũ của ngân hàng) bị gán nhãn nhầm —
 * vd host gán id 2 = "Công nghệ" nhưng nội dung subject_2.json lại là Toán học:
 *     { "2": "Toán học" }
 */
function game_subjects_override(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    $file = __DIR__ . '/../admin/game_subjects_override.json';
    if (!is_file($file)) return $map;
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) return $map;
    foreach ($data as $id => $name) {
        $id = (int)$id;
        if ($id <= 0) continue;
        $name = trim((string)$name);
        if ($name === '') continue;
        $map[$id] = $name;
    }
    return $map;
}

/**
 * Danh mục môn dùng chung cho toàn API:
 * gộp subjects.json (id mới) với danh bạ cũ (id legacy của ngân hàng câu hỏi) theo tên chuẩn hoá.
 * Mỗi môn: id → id chính, ids → toàn bộ id cùng môn (cũ + mới), from_subjects → có nguồn từ subjects.json.
 */
function game_subject_catalog(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $groups = [];
    $usedIds = [];

    foreach (game_subject_list() as $s) {
        $key = game_normalize_subject_name($s['name']);
        $s['from_subjects'] = true;
        $groups[$key] = $s;
        foreach ($s['ids'] as $id) $usedIds[$id] = true;
    }

    // Id không có trong subjects.json: gom theo override (nếu có), ngược lại theo danh bạ cũ.
    // Override được ưu tiên, kể cả với id vượt ngoài danh bạ legacy.
    $override = game_subjects_override();
    $extra = [];
    foreach (game_legacy_subjects() as $id => $meta) $extra[$id] = $meta['name'];
    foreach ($override as $id => $name) $extra[$id] = $name;

    foreach ($extra as $id => $name) {
        if (isset($usedIds[$id])) continue; // tránh trùng id đang thuộc subjects.json
        $key = game_normalize_subject_name($name);
        if ($key === '') $key = 'subject_id_' . $id;
        if (isset($groups[$key])) {
            if (!in_array($id, $groups[$key]['ids'], true)) {
                $groups[$key]['ids'][] = $id;
            }
        } else {
            $groups[$key] = [
                'id' => $id,
                'name' => $name,
                'code' => (GAME_LEGACY_SUBJECTS[$id]['code'] ?? ''),
                'ids' => [$id],
                'from_subjects' => false,
            ];
        }
    }

    $cache = array_values($groups);
    return $cache;
}

/** id bất kỳ → toàn bộ id cùng tên môn (kể cả id cũ của ngân hàng câu hỏi) */
function game_subject_alias_map(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    foreach (game_subject_catalog() as $s) {
        foreach ($s['ids'] as $id) $map[$id] = $s['ids'];
    }
    return $map;
}

/** Tên + code của mọi subject id (tra subjects.json trước, rồi legacy, cuối cùng là tên mặc định) */
function game_subject_meta(int $id): array
{
    static $memo = null;
    if ($memo === null) {
        $memo = [];
        foreach (game_subject_catalog() as $s) {
            foreach ($s['ids'] as $i) $memo[$i] = ['name' => $s['name'], 'code' => $s['code']];
        }
    }
    if (isset($memo[$id])) return $memo[$id];
    if (isset(GAME_LEGACY_SUBJECTS[$id])) return GAME_LEGACY_SUBJECTS[$id];
    return ['name' => 'Môn ' . $id, 'code' => ''];
}

/**
 * Quét thư mục ngân hàng câu hỏi của khối/học kỳ,
 * trả về các subject id thực sự có câu hỏi chơi được (kể cả id cũ không còn trong subjects.json).
 */
function game_bank_subject_ids(string $grade, string $semester): array
{
    $dir = __DIR__ . '/../teacher/questions/' . $grade . '/' . $semester;
    if (!is_dir($dir)) return [];
    $ids = [];
    $files = glob($dir . '/subject_*.json');
    foreach ($files as $file) {
        if (!preg_match('/subject_(\d+)\.json$/', basename($file), $m)) continue;
        $id = (int)$m[1];
        if ($id <= 0) continue;
        if (array_sum(game_level_counts($grade, $semester, [$id])) > 0) {
            $ids[] = $id;
        }
    }
    sort($ids);
    return $ids;
}

/** Mở rộng danh sách id môn được chọn → gồm cả các id trùng tên (cũ + mới) */
function game_expand_subject_ids(array $ids): array
{
    $map = game_subject_alias_map();
    $out = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;
        foreach ($map[$id] ?? [$id] as $groupId) {
            if (!in_array($groupId, $out, true)) $out[] = $groupId;
        }
    }
    return $out;
}

/** id chính của mỗi môn (để gom nhóm câu hỏi khi xen kẽ) */
function game_primary_subject_map(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    foreach (game_subject_catalog() as $s) {
        foreach ($s['ids'] as $id) $map[$id] = $s['id'];
    }
    return $map;
}

function game_level_counts(string $grade, string $semester, $subjectIds): array
{
    if (!is_array($subjectIds)) $subjectIds = [(int)$subjectIds];
    $counts = ['NB' => 0, 'TH' => 0, 'VD' => 0];

    foreach ($subjectIds as $subjectId) {
        $file = __DIR__ . '/../teacher/questions/' . $grade . '/' . $semester . '/subject_' . (int)$subjectId . '.json';
        if (!is_file($file)) continue;
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) continue;

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
    }
    return $counts;
}

function game_pool(string $grade, string $semester, array $subjectIds): array
{
    $subjectIds = game_expand_subject_ids($subjectIds);
    $primaryOf = game_primary_subject_map();

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
                    'sid' => $primaryOf[$sid] ?? $sid,
                    'subject' => game_subject_meta($sid)['name'],
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
    $bankIds = game_bank_subject_ids($grade, $semester);
    $bankSet = [];
    foreach ($bankIds as $id) $bankSet[$id] = true;

    // Môn từ subjects.json (giữ nguyên kể cả khi chưa có câu hỏi) + gom luôn ngân hàng id cũ cùng tên
    foreach (game_subject_catalog() as $s) {
        $hasBank = false;
        foreach ($s['ids'] as $id) {
            if (isset($bankSet[$id])) { $hasBank = true; break; }
        }
        // Môn chỉ tồn tại ở danh bạ cũ (không có câu hỏi, không thuộc subjects.json) → bỏ qua
        if (!$s['from_subjects'] && !$hasBank) continue;

        $counts = game_level_counts($grade, $semester, $s['ids']);
        $subjects[] = [
            'id' => $s['id'],
            'ids' => $s['ids'],
            'name' => $s['name'],
            'code' => $s['code'],
            'levels' => $counts,
            'total' => array_sum($counts),
        ];
        foreach ($s['ids'] as $id) $bankSet[$id] = true;
    }

    // Ngân hàng còn có id không nằm trong danh mục nào → tạo môn riêng để không sót câu hỏi
    foreach ($bankIds as $id) {
        if (isset($bankSet[$id])) continue;
        $meta = game_subject_meta($id);
        $counts = game_level_counts($grade, $semester, [$id]);
        $subjects[] = [
            'id' => $id,
            'ids' => [$id],
            'name' => $meta['name'],
            'code' => $meta['code'],
            'levels' => $counts,
            'total' => array_sum($counts),
        ];
        $bankSet[$id] = true;
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
// Gộp các id trùng tên môn để không bỏ sót ngân hàng câu hỏi
$subjectIds = game_expand_subject_ids($subjectIds);
if (!$subjectIds) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ít nhất một môn học.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$requested = [
    'NB' => max(0, min(30, (int)($_GET['nb'] ?? 5))),
    'TH' => max(0, min(30, (int)($_GET['th'] ?? 5))),
    'VD' => max(0, min(30, (int)($_GET['vd'] ?? 5))),
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