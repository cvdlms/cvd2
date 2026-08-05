<?php
/**
 * generate.php — Xử lý POST: tính ma trận hoặc xuất Word
 * Port từ eduvn/public/tools/matran sang CVD LMS
 */

// ── Auth (TRƯỚC ob_start) ──────────────────────────────────────
session_name('CVD_TEACHER_SESSION');
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calculator.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
    exit;
}
// ───────────────────────────────────────────────────────────────

ob_start();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    error_log("CVD Matran PHP Error [{$errno}]: {$errstr} in " . basename($errfile) . ":{$errline}");
    echo json_encode(['ok' => false, 'error' => 'Đã xảy ra lỗi xử lý. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
    exit;
});

set_exception_handler(function (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    error_log('CVD Matran Exception: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    echo json_encode(['ok' => false, 'error' => 'Đã xảy ra lỗi xử lý. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        error_log('CVD Matran Fatal: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']);
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Đã xảy ra lỗi xử lý. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
    } else {
        ob_end_flush();
    }
});

// PhpWord chỉ dùng khi export Word
$vendorLoaded = class_exists('PhpOffice\PhpWord\PhpWord', false);

// ── Đọc input ──────────────────────────────────────────────────
$action   = $_POST['action'] ?? 'preview';
$unitsRaw = $_POST['units'] ?? '[]';
$units    = json_decode($unitsRaw, true);
if (!is_array($units) || count($units) === 0) {
    jsonError('Chưa có đơn vị kiến thức. Vui lòng thêm ít nhất 2 đơn vị.');
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
    'school'   => trim($_POST['school']   ?? 'Trường THCS ...'),
    'subject'  => trim($_POST['subject']  ?? 'Tin học'),
    'grade'    => trim($_POST['grade']    ?? '8'),
    'semester' => trim($_POST['semester'] ?? 'HK2'),
    'year'     => trim($_POST['year']     ?? '2024-2025'),
    'duration' => trim($_POST['duration'] ?? '45'),
    'examType' => trim($_POST['examType'] ?? 'Kiểm tra giữa kỳ'),
];

$result   = calculateMatrix($units, $cfg);
if (!empty($result['errors'])) jsonError(implode('; ', $result['errors']));
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
    $tnkq_c=1; $tl_c=1;
    foreach ($unitData as &$u) {
        $u['tnkq_nb_nums']=[]; for($k=0;$k<$u['u_tnkq_nb'];$k++) $u['tnkq_nb_nums'][]=$tnkq_c++;
        $u['tnkq_th_nums']=[]; for($k=0;$k<$u['u_tnkq_th'];$k++) $u['tnkq_th_nums'][]=$tnkq_c++;
        $u['tnkq_vd_nums']=[]; for($k=0;$k<$u['u_tnkq_vd'];$k++) $u['tnkq_vd_nums'][]=$tnkq_c++;
        $u['tl_nb_nums']  =[]; for($k=0;$k<$u['u_tl_nb'];$k++)   $u['tl_nb_nums'][]  =$tl_c++;
        $u['tl_th_nums']  =[]; for($k=0;$k<$u['u_tl_th'];$k++)   $u['tl_th_nums'][]  =$tl_c++;
        $u['tl_vd_nums']  =[]; for($k=0;$k<$u['u_tl_vd'];$k++)   $u['tl_vd_nums'][]  =$tl_c++;
    } unset($u);
    $dsQNum=1;
    foreach ($unitData as &$u) {
        $total=$u['u_ds_nb']+$u['u_ds_th']+$u['u_ds_vd'];
        $u['ds_nb_nums']=[]; $u['ds_th_nums']=[]; $u['ds_vd_nums']=[];
        if(!$total) continue;
        $sub=0;
        for($k=0;$k<$u['u_ds_nb'];$k++) $u['ds_nb_nums'][]=$dsQNum.chr(97+$sub++);
        for($k=0;$k<$u['u_ds_th'];$k++) $u['ds_th_nums'][]=$dsQNum.chr(97+$sub++);
        for($k=0;$k<$u['u_ds_vd'];$k++) $u['ds_vd_nums'][]=$dsQNum.chr(97+$sub++);
        $dsQNum++;
    } unset($u);
    return $unitData;
}

if ($action === 'preview') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'unitData' => $unitData, 'ctx' => $ctx, 'meta' => $meta, 'warnings' => $result['warnings'] ?? []], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/exporter.php';
$typeMap = ['export_matran' => 'matran', 'export_dacta' => 'dacta', 'export_all' => 'all'];
$exportType = $typeMap[$action] ?? 'all';
ob_end_clean();
exportMatranWord($unitData, $ctx, $meta, $exportType);
exit;

function jsonError(string $msg): void {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
