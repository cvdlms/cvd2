<?php
/**
 * export_de.php — Tạo đề thi (>= 2 đề khác nhau >= 50%) từ ma trận và xuất Word
 *
 * Input POST (giống generate.php) + :
 *   bank_grade, bank_subject_id, variant_count
 * Output: file .docx (Đề A + đáp án A + Đề B + đáp án B)
 */

session_name('CVD_TEACHER_SESSION');
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calculator.php';
require_once __DIR__ . '/exam_generator.php';

// ── Auth ───────────────────────────────────────────────────────
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$teacherId = $_SESSION['username'];

ob_start();
@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING & ~E_STRICT);
set_error_handler(function ($errno, $errstr) {
    if (!(error_reporting() & $errno)) return false; // bỏ qua mức đã tắt
    jsonFail('Đã xảy ra lỗi xử lý. Vui lòng thử lại.');
});
set_exception_handler(function (Throwable $e) {
    error_log('CVD ExportDe: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    jsonFail('Đã xảy ra lỗi khi tạo đề: ' . $e->getMessage());
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        jsonFail('Đã xảy ra lỗi xử lý. Vui lòng thử lại.');
    }
});

function jsonFail(string $msg): void {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Đọc input ──────────────────────────────────────────────────
$units = json_decode($_POST['units'] ?? '[]', true);
if (!is_array($units) || count($units) < 2) {
    jsonFail('Cần ít nhất 2 đơn vị kiến thức trong ma trận.');
}

$bankGrade    = preg_replace('/[^0-9]/', '', $_POST['bank_grade'] ?? '');
$bankSubject  = (int)($_POST['bank_subject_id'] ?? 0);
$bankSemester = ($_POST['semester'] ?? 'HK2') === 'HK1' ? 'hk1' : 'hk2';
$variantCount = max(2, min(4, (int)($_POST['variant_count'] ?? 2)));

if ($bankGrade === '' || $bankSubject <= 0) {
    jsonFail('Vui lòng chọn ngân hàng câu hỏi (khối + môn) trước khi tạo đề.');
}

$bankFile = dirname(__DIR__, 2) . '/questions/khoi' . $bankGrade . '/' . $bankSemester . '/subject_' . $bankSubject . '.json';
if (!is_file($bankFile)) {
    jsonFail('Không tìm thấy ngân hàng câu hỏi: khoi' . $bankGrade . '/' . $bankSemester . '/subject_' . $bankSubject . '.json');
}

$cfg = [
    'tnkq_num' => (int)($_POST['tnkq_num'] ?? DEFAULT_TNKQ_NUM),
    'pct_nb'   => (int)($_POST['pct_nb']   ?? DEFAULT_PCT_NB),
    'pct_th'   => (int)($_POST['pct_th']   ?? DEFAULT_PCT_TH),
    'pct_vd'   => (int)($_POST['pct_vd']   ?? DEFAULT_PCT_VD),
    'tl_pts'   => (float)($_POST['tl_pts'] ?? DEFAULT_TL_PTS),
    'tl_num'   => (int)($_POST['tl_num']   ?? DEFAULT_TL_NUM),
];

$meta = [
    'school'   => trim($_POST['school']   ?? 'Trường THCS'),
    'subject'  => trim($_POST['subject']  ?? 'Môn học'),
    'grade'    => trim($_POST['grade']    ?? $bankGrade),
    'semester' => trim($_POST['semester'] ?? 'HK2'),
    'year'     => trim($_POST['year']     ?? date('Y') . '-' . (date('Y') + 1)),
    'duration' => trim($_POST['duration'] ?? '45'),
    'examType' => trim($_POST['examType'] ?? 'Kiểm tra'),
];

// ── Tính ma trận ───────────────────────────────────────────────
$result = calculateMatrix($units, $cfg);
if (!empty($result['errors'])) jsonFail(implode('; ', $result['errors']));
$unitData = $result['unitData'];
$ctx      = $result['ctx'];

$overridesRaw = $_POST['overrides'] ?? null;
if ($overridesRaw) {
    $overrides = json_decode($overridesRaw, true);
    if (is_array($overrides)) $unitData = applyOverrides($unitData, $overrides, $ctx);
}

function applyOverrides(array $unitData, array $overrides, array $ctx): array {
    foreach ($overrides as $i => $fields) {
        if (!isset($unitData[$i])) continue;
        $allowed = ['u_tnkq_nb','u_tnkq_th','u_tnkq_vd','u_ds_nb','u_ds_th','u_ds_vd','u_tl_nb','u_tl_th','u_tl_vd'];
        foreach ($allowed as $f) { if (isset($fields[$f])) $unitData[$i][$f] = max(0, (int)$fields[$f]); }
    }
    return $unitData;
}

// ── Nạp ngân hàng câu hỏi ──────────────────────────────────────
$raw = json_decode(file_get_contents($bankFile), true);
if (!is_array($raw)) jsonFail('Ngân hàng câu hỏi không hợp lệ.');
$bank = mtnFlattenBank($raw);
if (count($bank) < 4) jsonFail('Ngân hàng câu hỏi có quá ít câu (' . count($bank) . ' câu). Hãy thêm câu hỏi trước.');

$slots = mtnBuildSlots($unitData, $ctx);
if (empty($slots)) jsonFail('Ma trận không có câu hỏi nào để tạo đề.');

// ── Sinh các phương án đề ──────────────────────────────────────
$gen = mtnGenerateVariants($bank, $slots, $variantCount, 50.0);
$variants  = $gen['variants'];
$difference = $gen['difference'];
$warnings  = $gen['warnings'];

// ── Đóng gói variants (gán số thứ tự) ──────────────────────────
$outVariants = [];
foreach ($variants as $i => $list) {
    $pts = 0.0; $dist = ['TNKQ' => 0, 'DS' => 0, 'TL' => 0];
    $num = 1;
    $clean = [];
    foreach ($list as $q) {
        $q['number'] = $num++;
        unset($q['_qid'], $q['_slotType'], $q['_slotLevel']);
        $pts += (float)($q['points'] ?? 0);
        $dist[$q['question_type']] = ($dist[$q['question_type']] ?? 0) + 1;
        $clean[] = $q;
    }
    $outVariants[] = [
        'variant'         => chr(65 + $i),
        'questions'       => $clean,
        'total_questions' => count($clean),
        'total_points'    => round($pts, 2),
        'distribution'    => $dist,
    ];
    unset($clean);
}
$examId = 'EXAM_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
$title  = 'Đề ' . $meta['examType'] . ' - ' . $meta['subject'] . ' ' . $meta['grade'];

$examRecord = [
    'id'          => $examId,
    'title'       => $title,
    'grade'       => 'khoi' . $bankGrade,
    'subject'     => $meta['subject'],
    'subject_id'  => $bankSubject,
    'semester'    => $bankSemester,
    'teacher_id'  => $teacherId,
    'created_at'  => date('Y-m-d H:i:s'),
    'source'      => 'matrix_builder',
    'meta'        => $meta,
    'variants'    => $outVariants,
    'difference'  => $difference,
    'warnings'    => $warnings,
    'options'     => ['variant_count' => $variantCount],
];

$examsDir = dirname(__DIR__, 2) . '/exams/generated';
if (!is_dir($examsDir)) @mkdir($examsDir, 0755, true);
@file_put_contents(
    $examsDir . '/' . $examId . '.json',
    json_encode($examRecord, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

// ── Xuất Word ──────────────────────────────────────────────────
require_once __DIR__ . '/exam_exporter.php';

while (ob_get_level()) ob_end_clean();

try {
    exportExamPaper([
        'school'     => $meta['school'],
        'subject'    => $meta['subject'],
        'grade'      => $meta['grade'],
        'semester'   => $meta['semester'],
        'year'       => $meta['year'],
        'duration'   => $meta['duration'],
        'examType'   => $meta['examType'],
        'title'      => $title,
        'variants'   => $outVariants,
        'difference' => $difference,
    ]);
} catch (Throwable $e) {
    error_log('CVD ExportDe Word: ' . $e->getMessage());
    jsonFail('Không thể tạo file Word: ' . $e->getMessage());
}
exit;
