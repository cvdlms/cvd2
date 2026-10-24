<?php
/**
 * API: Xuất bảng điểm theo template vnedu_template_bang_diem.xlsx
 *
 * POST params (JSON body):
 *   class_id      string   ID lớp (required)
 *   tx_column     string   'tx1'|'tx2'|'tx3'|'tx4'  – cột TX sẽ điền điểm vào
 *   test_types    array    danh sách test_name đã chọn (rỗng = tất cả)
 *   mon           string   tên môn học
 *   hoc_ky        string   e.g. "I"
 *   nam_hoc       string   e.g. "2025-2026"
 *   don_vi        string   đơn vị chủ quản
 *   truong        string   tên trường
 */

include '../../includes/session_check.php';
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// ── Parse input ──────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$class_id   = $input['class_id']   ?? '';
$tx_column  = strtolower(trim($input['tx_column'] ?? 'tx1'));   // tx1..tx4
$test_types = $input['test_types'] ?? [];   // [] = tất cả
$mon        = $input['mon']        ?? '';
$hoc_ky     = $input['hoc_ky']     ?? 'I';
$nam_hoc    = $input['nam_hoc']    ?? date('Y') . '-' . (date('Y') + 1);
$don_vi     = $input['don_vi']     ?? '';
$truong     = $input['truong']     ?? '';

if (!in_array($tx_column, ['tx1','tx2','tx3','tx4'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'tx_column không hợp lệ']));
}

// ── Load students ────────────────────────────────────────────────────────────
$classesRaw = json_decode(file_get_contents(__DIR__ . '/../../admin/classes.json'), true) ?? [];
$usersRaw   = json_decode(file_get_contents(__DIR__ . '/../../admin/user.json'), true) ?? [];

$className = '';
foreach ($classesRaw as $c) {
    if ((string)$c['id'] === (string)$class_id) {
        $className = $c['name'] ?? '';
        break;
    }
}

// Collect students in class, sorted by stt
$students = [];
foreach ($usersRaw as $uname => $u) {
    if (($u['role'] ?? '') !== 'student') continue;
    if ((string)($u['class_id'] ?? '') !== (string)$class_id) continue;
    $students[] = [
        'code'      => $u['code']     ?? $uname,
        'fullname'  => $u['fullname'] ?? $uname,
        'dob'       => $u['dob']      ?? '',
        'stt'       => $u['stt']      ?? 999,
    ];
}
usort($students, fn($a,$b) => ($a['stt'] <=> $b['stt']) ?: strcmp($a['fullname'], $b['fullname']));

// ── Load scores ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../includes/score_index.php';
$scoreIndexPath = __DIR__ . '/../../shared/scores/student_score.json';
$allScores = file_exists($scoreIndexPath) ? json_decode(file_get_contents($scoreIndexPath), true) : [];

$teacherSubjectsFile = __DIR__ . '/../../admin/teacher_subjects.json';
$teacherSubjectsAll  = json_decode(file_get_contents($teacherSubjectsFile), true) ?? [];
$username            = $_SESSION['username'];
$assignedSubjects    = $teacherSubjectsAll[$username] ?? [];

/**
 * Lấy điểm CAO NHẤT cho học sinh trong tập test_types đã chọn,
 * chỉ tính điểm thuộc môn của giáo viên.
 */
function getBestScore(string $studentCode, array $allScores, array $assignedSubjects, array $testTypes): ?float {
    $best = null;
    foreach ($allScores as $s) {
        if ((string)$s['student_id'] !== $studentCode) continue;
        $sid = $s['subject_id'] ?? null;
        if ($sid !== null && $sid !== '' && !in_array((int)$sid, $assignedSubjects)) continue;
        if (!empty($testTypes) && !in_array($s['test_name'] ?? '', $testTypes)) continue;
        $sc = is_numeric($s['score']) ? (float)$s['score'] : null;
        if ($sc === null) continue;
        if ($best === null || $sc > $best) $best = $sc;
    }
    return $best;
}

// ── Open & clone template ────────────────────────────────────────────────────
$templatePath = __DIR__ . '/../data/vnedu_template_bang_diem.xlsx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Template không tồn tại']));
}

$tmpFile = tempnam(sys_get_temp_dir(), 'bangdiem_') . '.xlsx';
copy($templatePath, $tmpFile);

// ── Manipulate the ZIP (xlsx = zip) ─────────────────────────────────────────
$zip = new ZipArchive();
if ($zip->open($tmpFile) !== true) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Không mở được template']));
}

$sheet1Xml = $zip->getFromName('xl/worksheets/sheet1.xml');

// Helper: HTML-entity decode từ XML (template dùng inlineStr)
function xmlTxt(string $val): string {
    return htmlspecialchars($val, ENT_XML1, 'UTF-8');
}

// Tách họ đệm / tên từ fullname
function splitName(string $fullname): array {
    $parts = explode(' ', trim($fullname));
    $ten   = array_pop($parts);
    $hoDem = implode(' ', $parts);
    return [$hoDem, $ten];
}

// ── Build rows XML for each student ─────────────────────────────────────────
// Template row 8 là row data mẫu; ta sẽ duplicate nó cho từng học sinh
// Đọc row 8 XML nguyên bản
preg_match('/<row r="8"[^>]*>.*?<\/row>/s', $sheet1Xml, $row8Match);
$row8Template = $row8Match[0] ?? '';

$dataRowsXml = '';
$rowIndex    = 8; // bắt đầu từ row 8

foreach ($students as $idx => $student) {
    $stt        = $student['stt'] !== 999 ? $student['stt'] : ($idx + 1);
    [$hoDem, $ten] = splitName($student['fullname']);
    $dob        = $student['dob'];
    $bestScore  = getBestScore($student['code'], $allScores, $assignedSubjects, $test_types);
    $scoreStr   = $bestScore !== null ? (string)$bestScore : '';

    // Tạo giá trị cho từng cột TX (chỉ điền vào cột được chọn)
    $txValues = ['tx1' => '', 'tx2' => '', 'tx3' => '', 'tx4' => ''];
    $txValues[$tx_column] = $scoreStr;

    // Clone row 8 và thay placeholders
    $row = $row8Template;
    // Đổi row number
    $row = preg_replace('/(<row r=")8(")/','${1}' . $rowIndex . '${2}', $row);
    // Đổi cell references (A8 -> A{rowIndex}, ...)
    $row = preg_replace('/(<c r="([A-Z]+))8(")/','${1}' . $rowIndex . '${3}', $row);

    $replacements = [
        '{{stt}}'       => xmlTxt((string)$stt),
        '{{ma_hs}}'     => xmlTxt($student['code']),
        '{{ho_dem}}'    => xmlTxt($hoDem),
        '{{ten}}'       => xmlTxt($ten),
        '{{ngay_sinh}}' => xmlTxt($dob),
        '{{tx1}}'       => xmlTxt($txValues['tx1']),
        '{{tx2}}'       => xmlTxt($txValues['tx2']),
        '{{tx3}}'       => xmlTxt($txValues['tx3']),
        '{{tx4}}'       => xmlTxt($txValues['tx4']),
        '{{gk}}'        => '',
        '{{ck}}'        => '',
        '{{nhan_xet}}'  => '',
    ];
    foreach ($replacements as $ph => $val) {
        $row = str_replace($ph, $val, $row);
    }

    $dataRowsXml .= $row . "\n";
    $rowIndex++;
}

// ── Replace template's single row 8 with all student rows ────────────────────
// Các row thống kê (9+) cần được renumber nếu số học sinh > 1
// Chiến lược: xóa row 8 gốc, chèn tất cả data rows, giữ các row khác
// nhưng renumber row 9..14 thành (rowIndex)...(rowIndex+5)
$statsStartRow = $rowIndex; // row đầu tiên của phần thống kê

// Xóa row 8 gốc
$sheet1Xml = preg_replace('/<row r="8"[^>]*>.*?<\/row>\n?/s', '', $sheet1Xml);

// Renumber rows 9-14 (thống kê cuối bảng)
for ($oldR = 14; $oldR >= 9; $oldR--) {
    $newR = $statsStartRow + ($oldR - 9);
    // Row
    $sheet1Xml = preg_replace('/(<row r=")' . $oldR . '(")/', '${1}' . $newR . '${2}', $sheet1Xml, 1);
    // Cells in that row: match [A-Z]+{oldR}
    $sheet1Xml = preg_replace('/(<c r="[A-Z]+)' . $oldR . '(")/', '${1}' . $newR . '${2}', $sheet1Xml);
}

// Also renumber rows 15+ (empty rows)
for ($oldR = 59; $oldR >= 15; $oldR--) {
    $newR = $oldR + (count($students) - 1);
    $sheet1Xml = preg_replace('/(<row r=")' . $oldR . '(")/', '${1}' . $newR . '${2}', $sheet1Xml, 1);
}

// Update dimension ref
$lastDataRow = $rowIndex - 1;
$sheetLastRow = $lastDataRow + 50; // approximate
$sheet1Xml = preg_replace('/<dimension ref="A1:[A-Z]+\d+"/', '<dimension ref="A1:AK' . $sheetLastRow . '"', $sheet1Xml);

// Renumber formula references in stats rows (họ COUNTIF($L$8:L8,...) → $L$8:L{lastDataRow})
// Replace the dynamic end of COUNTIF ranges pointing to row 8 area
// Actually update formulas: COUNTIF($L$8:L8 → COUNTIF($L$8:L{lastDataRow}
$sheet1Xml = str_replace('COUNTIF($L$8:L8,', 'COUNTIF($L$8:L' . $lastDataRow . ',', $sheet1Xml);

// Insert data rows before sheetData closing tag
$sheet1Xml = str_replace('</sheetData>', $dataRowsXml . '</sheetData>', $sheet1Xml);

// ── Replace header placeholders ──────────────────────────────────────────────
// Parse class name: "10A1" -> khoi="10", lop="10A1"
$khoi = preg_match('/^(\d+)/', $className, $m) ? $m[1] : '';

$headerPh = [
    '{{don_vi_chu_quan}}' => xmlTxt($don_vi),
    '{{truong}}'          => xmlTxt($truong),
    '{{mon}}'             => xmlTxt($mon),
    '{{hoc_ky}}'          => xmlTxt($hoc_ky),
    '{{nam_hoc}}'         => xmlTxt($nam_hoc),
    '{{khoi}}'            => xmlTxt($khoi),
    '{{lop}}'             => xmlTxt($className),
];
foreach ($headerPh as $ph => $val) {
    $sheet1Xml = str_replace($ph, $val, $sheet1Xml);
}

// ── Save modified XML back to zip ────────────────────────────────────────────
$zip->deleteName('xl/worksheets/sheet1.xml');
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);

// Rename sheet "template" → class name (workbook.xml)
$wbXml = $zip->getFromName('xl/workbook.xml');
$safeClassName = preg_replace('/[\[\]\*\/\\\\\?:]/', '_', $className ?: 'BangDiem');
$wbXml = preg_replace('/name="template"/', 'name="' . htmlspecialchars($safeClassName, ENT_XML1) . '"', $wbXml);
$zip->deleteName('xl/workbook.xml');
$zip->addFromString('xl/workbook.xml', $wbXml);

$zip->close();

// ── Stream to browser ────────────────────────────────────────────────────────
$txLabel = strtoupper($tx_column);
$filename = 'BangDiem_' . ($safeClassName) . '_' . $txLabel . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache');
readfile($tmpFile);
unlink($tmpFile);
exit;
