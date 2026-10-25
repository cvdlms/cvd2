<?php
/**
 * API: Xuất bảng điểm theo template vnedu_template_bang_diem.xlsx
 * Hỗ trợ xuất 1 lớp hoặc Tất cả các lớp (mỗi lớp 1 sheet).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('CVD_TEACHER_SESSION');
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_username = $_SESSION['username'];

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$class_id   = trim($input['class_id'] ?? 'all');
$tx_column  = strtolower(trim($input['tx_column'] ?? 'tx1'));
$test_types = $input['test_types'] ?? [];
$mon        = trim($input['mon'] ?? '');
$hoc_ky     = trim($input['hoc_ky'] ?? '1');
$nam_hoc    = trim($input['nam_hoc'] ?? '');
$don_vi     = trim($input['don_vi'] ?? '');
$truong     = trim($input['truong'] ?? '');

if (!in_array($tx_column, ['tx1','tx2','tx3','tx4'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cột TX không hợp lệ']);
    exit;
}

// File paths
$teacherClassesFile  = __DIR__ . '/../../admin/teacher_classes.json';
$teacherSubjectsFile = __DIR__ . '/../../admin/teacher_subjects.json';
$classesFile         = __DIR__ . '/../../admin/classes.json';
$studentsFile        = __DIR__ . '/../../admin/students.json';
$scoresFile          = __DIR__ . '/../../shared/scores/student_score.json';
$systemConfigFile    = __DIR__ . '/../../admin/system_config.json';

// Fill defaults from system config if empty
if (file_exists($systemConfigFile)) {
    $sysConfig = json_decode(file_get_contents($systemConfigFile), true) ?? [];
    if ($truong === '' && !empty($sysConfig['system']['school_name'])) {
        $truong = $sysConfig['system']['school_name'];
    }
    if ($nam_hoc === '' && !empty($sysConfig['system']['school_year'])) {
        $nam_hoc = $sysConfig['system']['school_year'];
    }
    if ($don_vi === '' && !empty($sysConfig['system']['school_address'])) {
        if (preg_match('/phường\s+([^,]+)/iu', $sysConfig['system']['school_address'], $pm)) {
            $don_vi = 'UBND PHƯỜNG ' . mb_strtoupper(trim($pm[1]), 'UTF-8');
        }
    }
}
if ($nam_hoc === '') {
    $nam_hoc = date('Y') . '-' . (date('Y') + 1);
}

// Load teacher assignments
$teacher_classes = file_exists($teacherClassesFile)
    ? (json_decode(ltrim(file_get_contents($teacherClassesFile), "\xEF\xBB\xBF"), true) ?: [])
    : [];
$assigned_class_ids = $teacher_classes[$teacher_username] ?? [];

$teacher_subjects = file_exists($teacherSubjectsFile)
    ? (json_decode(ltrim(file_get_contents($teacherSubjectsFile), "\xEF\xBB\xBF"), true) ?: [])
    : [];
$assigned_subjects = $teacher_subjects[$teacher_username] ?? [];

// Load classes
$all_classes = file_exists($classesFile)
    ? (json_decode(ltrim(file_get_contents($classesFile), "\xEF\xBB\xBF"), true) ?: [])
    : [];

$classLookup = [];
foreach ($all_classes as $c) {
    $classLookup[(string)$c['id']] = $c;
}

// Determine target classes
$targetClassIds = [];
if ($class_id === 'all' || $class_id === '') {
    if (!empty($assigned_class_ids)) {
        $targetClassIds = array_map('strval', $assigned_class_ids);
    } else {
        $targetClassIds = array_map(fn($c) => (string)$c['id'], $all_classes);
    }
} else {
    $targetClassIds = [(string)$class_id];
}

// Load students
$allStudents = file_exists($studentsFile)
    ? (json_decode(ltrim(file_get_contents($studentsFile), "\xEF\xBB\xBF"), true) ?: [])
    : [];

$studentsByClass = [];
foreach ($allStudents as $st) {
    $cid = (string)($st['class_id'] ?? '');
    if (in_array($cid, $targetClassIds, true)) {
        $studentsByClass[$cid][] = $st;
    }
}

// Load scores
require_once __DIR__ . '/../../includes/score_index.php';
$allScores = file_exists($scoresFile) ? (json_decode(file_get_contents($scoresFile), true) ?: []) : [];

// Template path
$templatePath = __DIR__ . '/../data/vnedu_template_bang_diem.xlsx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Template không tồn tại']);
    exit;
}

// Build classes to export
$classesToExport = [];
foreach ($targetClassIds as $cid) {
    $cInfo = $classLookup[$cid] ?? null;
    $cName = $cInfo ? ($cInfo['name'] ?? "Lớp $cid") : "Lớp $cid";
    $cStudents = $studentsByClass[$cid] ?? [];

    // Sort students by order_index
    usort($cStudents, function($a, $b) {
        $orderA = isset($a['order_index']) ? (int)$a['order_index'] : PHP_INT_MAX;
        $orderB = isset($b['order_index']) ? (int)$b['order_index'] : PHP_INT_MAX;
        if ($orderA === $orderB) return 0;
        return $orderA <=> $orderB;
    });

    $processedStudents = [];
    foreach ($cStudents as $idx => $st) {
        $code = (string)($st['code'] ?? '');
        $bestScore = null;
        foreach ($allScores as $s) {
            if ((string)($s['student_id'] ?? '') !== $code) continue;
            $sid = $s['subject_id'] ?? null;
            if ($sid !== null && $sid !== '' && !empty($assigned_subjects) && !in_array((int)$sid, $assigned_subjects, true)) {
                continue;
            }
            if (!empty($test_types) && !in_array($s['test_name'] ?? '', $test_types, true)) {
                continue;
            }
            if (!isset($s['score']) || $s['score'] === '' || $s['score'] === null || !is_numeric($s['score'])) {
                continue;
            }
            $val = (float)$s['score'];
            if ($bestScore === null || $val > $bestScore) {
                $bestScore = $val;
            }
        }

        $processedStudents[] = [
            'stt'        => $idx + 1,
            'code'       => $code,
            'name'       => $st['name'] ?? '',
            'birth_date' => $st['birth_date'] ?? '',
            'score'      => $bestScore !== null ? (string)(float)$bestScore : null
        ];
    }

    $classesToExport[] = [
        'id'       => $cid,
        'name'     => $cName,
        'students' => $processedStudents
    ];
}

if (empty($classesToExport)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy lớp học để xuất dữ liệu']);
    exit;
}

/**
 * Generate complete XML for a class worksheet
 */
function generateClassWorksheetXml(
    string $donVi,
    string $truong,
    string $mon,
    string $hocKy,
    string $namHoc,
    string $className,
    array $students,
    string $txCol,
    bool $isActiveTab = false
): string {
    $khoi = preg_match('/^(\d+)/', $className, $m) ? $m[1] : '';

    $headerRows = '<row r="1" ht="17.25" customHeight="1" s="53"><c r="A1" s="47" t="inlineStr"><is><t>' . htmlspecialchars($donVi, ENT_XML1, 'UTF-8') . '</t></is></c></row>'
        . '<row r="2" ht="17.25" customHeight="1" s="53"><c r="A2" s="47" t="inlineStr"><is><t>' . htmlspecialchars($truong, ENT_XML1, 'UTF-8') . '</t></is></c></row>'
        . '<row r="3" ht="20" customHeight="1" s="53"><c r="A3" s="54" t="inlineStr"><is><t>BẢNG ĐIỂM CHI TIẾT - MÔN ' . htmlspecialchars(mb_strtoupper($mon, 'UTF-8'), ENT_XML1, 'UTF-8') . ' - HỌC KỲ ' . htmlspecialchars($hocKy, ENT_XML1, 'UTF-8') . ' - NĂM HỌC ' . htmlspecialchars($namHoc, ENT_XML1, 'UTF-8') . '</t></is></c></row>'
        . '<row r="4" ht="12.75" customHeight="1" s="53"><c r="A4" s="54" t="inlineStr"><is><t>Khối ' . htmlspecialchars($khoi, ENT_XML1, 'UTF-8') . ' - Lớp ' . htmlspecialchars($className, ENT_XML1, 'UTF-8') . '</t></is></c><c r="K4" s="55" t="n"/><c r="L4" s="55" t="n"/></row>'
        . '<row r="5" ht="12.75" customHeight="1" s="53"><c r="A5" s="50" t="n"/><c r="B5" s="56" t="n"/><c r="C5" s="50" t="n"/><c r="D5" s="50" t="n"/><c r="E5" s="50" t="n"/><c r="F5" s="47" t="n"/><c r="L5" s="49"><f>IF(K5&lt;&gt;&quot;&quot;,IF(COUNTIF(F5:K5,&quot;CĐ&quot;)+COUNTIF(F5:K5,&quot;CD&quot;)+COUNTIF(F5:K5,&quot;C&quot;)&gt;=1,&quot;CĐ&quot;,&quot;Đ&quot;),&quot;&quot;)</f><v></v></c></row>'
        . '<row r="6" ht="30" customHeight="1" s="53"><c r="A6" s="57" t="inlineStr"><is><t>STT</t></is></c><c r="B6" s="57" t="inlineStr"><is><t>Mã học sinh</t></is></c><c r="C6" s="57" t="inlineStr"><is><t>Họ và tên</t></is></c><c r="D6" s="58" t="n"/><c r="E6" s="57" t="inlineStr"><is><t>Ngày sinh</t></is></c><c r="F6" s="57" t="inlineStr"><is><t>ĐĐGtx</t></is></c><c r="G6" s="59" t="n"/><c r="H6" s="59" t="n"/><c r="I6" s="60" t="n"/><c r="J6" s="57" t="inlineStr"><is><t>ĐĐGgk</t></is></c><c r="K6" s="57" t="inlineStr"><is><t>ĐĐGck</t></is></c><c r="L6" s="61" t="inlineStr"><is><t>ĐTB &#10;mhk</t></is></c><c r="M6" s="57" t="inlineStr"><is><t>Nhận xét</t></is></c><c r="N6" s="47" t="n"/><c r="O6" s="52" t="n"/><c r="P6" s="52" t="n"/></row>'
        . '<row r="7" ht="12.75" customHeight="1" s="53"><c r="A7" s="62" t="n"/><c r="B7" s="62" t="n"/><c r="C7" s="63" t="n"/><c r="D7" s="64" t="n"/><c r="E7" s="62" t="n"/><c r="F7" s="65" t="inlineStr"><is><t>TX1</t></is></c><c r="G7" s="65" t="inlineStr"><is><t>TX2</t></is></c><c r="H7" s="65" t="inlineStr"><is><t>TX3</t></is></c><c r="I7" s="65" t="inlineStr"><is><t>TX4</t></is></c><c r="J7" s="62" t="n"/><c r="K7" s="62" t="n"/><c r="L7" s="62" t="n"/><c r="M7" s="62" t="n"/></row>';

    $studentCount = count($students);
    $dataRowCount = max(1, $studentCount);
    $lastDataRow  = 8 + $dataRowCount - 1;

    $studentRowsXml = '';
    for ($i = 0; $i < $dataRowCount; $i++) {
        $r = 8 + $i;
        if ($i < $studentCount) {
            $st = $students[$i];
            $stt = $st['stt'];
            $code = $st['code'];

            $parts = explode(' ', trim($st['name']));
            $ten = count($parts) > 1 ? array_pop($parts) : ($parts[0] ?? '');
            $hoDem = implode(' ', $parts);
            $dob = $st['birth_date'] ?? '';
            $score = $st['score'];
        } else {
            $stt = 1;
            $code = '';
            $ten = '';
            $hoDem = '';
            $dob = '';
            $score = null;
        }

        $fCell = ($txCol === 'tx1' && $score !== null) ? '<c r="F' . $r . '" s="67"><v>' . $score . '</v></c>' : '<c r="F' . $r . '" s="67"/>';
        $gCell = ($txCol === 'tx2' && $score !== null) ? '<c r="G' . $r . '" s="67"><v>' . $score . '</v></c>' : '<c r="G' . $r . '" s="67"/>';
        $hCell = ($txCol === 'tx3' && $score !== null) ? '<c r="H' . $r . '" s="67"><v>' . $score . '</v></c>' : '<c r="H' . $r . '" s="67"/>';
        $iCell = ($txCol === 'tx4' && $score !== null) ? '<c r="I' . $r . '" s="70"><v>' . $score . '</v></c>' : '<c r="I' . $r . '" s="70"/>';

        $codeCell = ($code !== '') ? '<c r="B' . $r . '" s="67"><v>' . htmlspecialchars($code, ENT_XML1, 'UTF-8') . '</v></c>' : '<c r="B' . $r . '" s="67"/>';
        $sttCell  = '<c r="A' . $r . '" s="66"><v>' . $stt . '</v></c>';
        $hoDemCell= '<c r="C' . $r . '" s="68" t="inlineStr"><is><t>' . htmlspecialchars($hoDem, ENT_XML1, 'UTF-8') . '</t></is></c>';
        $tenCell  = '<c r="D' . $r . '" s="69" t="inlineStr"><is><t>' . htmlspecialchars($ten, ENT_XML1, 'UTF-8') . '</t></is></c>';
        $dobCell  = '<c r="E' . $r . '" s="67" t="inlineStr"><is><t>' . htmlspecialchars($dob, ENT_XML1, 'UTF-8') . '</t></is></c>';

        $lFormula = 'IF((COUNT(F' . $r . ':I' . $r . ')+COUNT(J' . $r . ':J' . $r . ')*2+COUNT(K' . $r . ':K' . $r . ')*3),ROUND((SUM(F' . $r . ':I' . $r . ')+SUM(J' . $r . ':J' . $r . ')*2+SUM(K' . $r . ':K' . $r . ')*3)/(COUNT(F' . $r . ':I' . $r . ')+COUNT(J' . $r . ':J' . $r . ')*2+COUNT(K' . $r . ':K' . $r . ')*3),1),&quot;&quot;)';
        $lCell    = '<c r="L' . $r . '" s="71"><f>' . $lFormula . '</f><v></v></c>';

        $dummyNtoAK = '<c r="N' . $r . '" s="72" t="n"/><c r="O' . $r . '" s="72" t="n"/><c r="P' . $r . '" s="72" t="n"/><c r="Q' . $r . '" s="73" t="n"/><c r="R' . $r . '" s="73" t="n"/><c r="S' . $r . '" s="73" t="n"/><c r="T' . $r . '" s="73" t="n"/><c r="U' . $r . '" s="73" t="n"/><c r="V' . $r . '" s="73" t="n"/><c r="W' . $r . '" s="73" t="n"/><c r="X' . $r . '" s="73" t="n"/><c r="Y' . $r . '" s="73" t="n"/><c r="Z' . $r . '" s="73" t="n"/><c r="AA' . $r . '" s="73" t="n"/><c r="AB' . $r . '" s="73" t="n"/><c r="AC' . $r . '" s="73" t="n"/><c r="AD' . $r . '" s="73" t="n"/><c r="AE' . $r . '" s="73" t="n"/><c r="AF' . $r . '" s="73" t="n"/><c r="AG' . $r . '" s="73" t="n"/><c r="AH' . $r . '" s="73" t="n"/><c r="AI' . $r . '" s="73" t="n"/><c r="AJ' . $r . '" s="73" t="n"/><c r="AK' . $r . '" s="73" t="n"/>';

        $studentRowsXml .= '<row r="' . $r . '" ht="12.75" customHeight="1" s="53">'
            . $sttCell . $codeCell . $hoDemCell . $tenCell . $dobCell
            . $fCell . $gCell . $hCell . $iCell
            . '<c r="J' . $r . '" s="70"/><c r="K' . $r . '" s="70"/>'
            . $lCell . '<c r="M' . $r . '" s="70"/>'
            . $dummyNtoAK . '</row>';
    }

    // Summary Rows
    $delta = $lastDataRow - 8;
    $r9  = 9 + $delta;
    $r10 = 10 + $delta;
    $r11 = 11 + $delta;
    $r12 = 12 + $delta;
    $r13 = 13 + $delta;
    $r14 = 14 + $delta;

    $dummyRow9 = '<c r="J' . $r9 . '" s="75" t="n"/><c r="K' . $r9 . '" s="76" t="n"/><c r="L' . $r9 . '" s="77" t="n"/><c r="M' . $r9 . '" s="78" t="n"/><c r="N' . $r9 . '" s="72" t="n"/><c r="O' . $r9 . '" s="72" t="n"/><c r="P' . $r9 . '" s="72" t="n"/><c r="Q' . $r9 . '" s="73" t="n"/><c r="R' . $r9 . '" s="73" t="n"/><c r="S' . $r9 . '" s="73" t="n"/><c r="T' . $r9 . '" s="73" t="n"/><c r="U' . $r9 . '" s="73" t="n"/><c r="V' . $r9 . '" s="73" t="n"/><c r="W' . $r9 . '" s="73" t="n"/><c r="X' . $r9 . '" s="73" t="n"/><c r="Y' . $r9 . '" s="73" t="n"/><c r="Z' . $r9 . '" s="73" t="n"/><c r="AA' . $r9 . '" s="73" t="n"/><c r="AB' . $r9 . '" s="73" t="n"/><c r="AC' . $r9 . '" s="73" t="n"/><c r="AD' . $r9 . '" s="73" t="n"/><c r="AE' . $r9 . '" s="73" t="n"/><c r="AF' . $r9 . '" s="73" t="n"/><c r="AG' . $r9 . '" s="73" t="n"/><c r="AH' . $r9 . '" s="73" t="n"/><c r="AI' . $r9 . '" s="73" t="n"/><c r="AJ' . $r9 . '" s="73" t="n"/><c r="AK' . $r9 . '" s="73" t="n"/>';

    $summaryRows = '<row r="' . $r9 . '" ht="12.75" customHeight="1" s="53"><c r="A' . $r9 . '" s="74" t="inlineStr"><is><t>THỐNG KÊ HỌC KỲ ' . htmlspecialchars($hocKy, ENT_XML1, 'UTF-8') . '</t></is></c>' . $dummyRow9 . '</row>'
        . '<row r="' . $r10 . '" ht="12.75" customHeight="1" s="53"><c r="J' . $r10 . '" s="75" t="n"/></row>'
        . '<row r="' . $r11 . '" ht="12.75" customHeight="1" s="53"><c r="A' . $r11 . '" s="79" t="inlineStr"><is><t>Số học sinh đạt</t></is></c><c r="D' . $r11 . '" s="80" t="inlineStr"><is><t>Tốt</t></is></c><c r="F' . $r11 . '" s="81"><f>TEXT(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=&quot;&amp;Q' . $r11 . '),&quot;#0&quot;)</f><v></v></c><c r="H' . $r11 . '" s="81" t="inlineStr"><is><t>-</t></is></c><c r="I' . $r11 . '" s="82"><f>ROUND(F' . $r11 . '/IF(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)=0,1,COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)),4)*100&amp;&quot;%&quot;</f><v></v></c><c r="J' . $r11 . '" s="75" t="n"/><c r="Q' . $r11 . '" s="83" t="n"><v>7.95</v></c></row>'
        . '<row r="' . $r12 . '" ht="12.75" customHeight="1" s="53"><c r="A' . $r12 . '" s="84" t="inlineStr"><is><t>Số lượng - Tỉ lệ (%)</t></is></c><c r="D' . $r12 . '" s="80" t="inlineStr"><is><t>Khá</t></is></c><c r="F' . $r12 . '" s="81"><f>TEXT(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=&quot;&amp;Q' . $r12 . ')-F' . $r11 . ',&quot;#0&quot;)</f><v></v></c><c r="H' . $r12 . '" s="81" t="inlineStr"><is><t>-</t></is></c><c r="I' . $r12 . '" s="82"><f>ROUND(F' . $r12 . '/IF(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)=0,1,COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)),4)*100&amp;&quot;%&quot;</f><v></v></c><c r="J' . $r12 . '" s="75" t="n"/><c r="Q' . $r12 . '" s="83" t="n"><v>6.45</v></c></row>'
        . '<row r="' . $r13 . '" ht="12.75" customHeight="1" s="53"><c r="A' . $r13 . '" s="84" t="n"/><c r="D' . $r13 . '" s="80" t="inlineStr"><is><t>Đạt</t></is></c><c r="F' . $r13 . '" s="81"><f>TEXT(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=&quot;&amp;Q' . $r13 . ')-F' . $r12 . '-F' . $r11 . ',&quot;#0&quot;)</f><v></v></c><c r="H' . $r13 . '" s="81" t="inlineStr"><is><t>-</t></is></c><c r="I' . $r13 . '" s="82"><f>ROUND(F' . $r13 . '/IF(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)=0,1,COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)),4)*100&amp;&quot;%&quot;</f><v></v></c><c r="J' . $r13 . '" s="75" t="n"/><c r="Q' . $r13 . '" s="83" t="n"><v>4.95</v></c></row>'
        . '<row r="' . $r14 . '" ht="12.75" customHeight="1" s="53"><c r="A' . $r14 . '" s="85" t="n"/><c r="B' . $r14 . '" s="86" t="n"/><c r="C' . $r14 . '" s="87" t="n"/><c r="D' . $r14 . '" s="88" t="inlineStr"><is><t>Chưa Đạt</t></is></c><c r="E' . $r14 . '" s="89" t="n"/><c r="F' . $r14 . '" s="90"><f>TEXT(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)-F' . $r11 . '-F' . $r12 . '-F' . $r13 . ',&quot;#0&quot;)</f><v></v></c><c r="G' . $r14 . '" s="89" t="n"/><c r="H' . $r14 . '" s="90" t="inlineStr"><is><t>-</t></is></c><c r="I' . $r14 . '" s="91"><f>ROUND(F' . $r14 . '/IF(COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)=0,1,COUNTIF($L$8:L' . $lastDataRow . ',&quot;&gt;=0&quot;)),4)*100&amp;&quot;%&quot;</f><v></v></c><c r="J' . $r14 . '" s="64" t="n"/></row>';

    $mergeCellsXml = '<mergeCells count="32">'
        . '<mergeCell ref="A1:L1"/><mergeCell ref="A2:L2"/><mergeCell ref="A3:L3"/><mergeCell ref="A4:J4"/><mergeCell ref="F5:J5"/><mergeCell ref="A6:A7"/><mergeCell ref="B6:B7"/><mergeCell ref="C6:D7"/><mergeCell ref="E6:E7"/><mergeCell ref="F6:I6"/><mergeCell ref="J6:J7"/><mergeCell ref="K6:K7"/><mergeCell ref="L6:L7"/><mergeCell ref="M6:M7"/><mergeCell ref="N6:N7"/><mergeCell ref="O6:O7"/><mergeCell ref="P6:P7"/>'
        . '<mergeCell ref="A' . $r9 . ':J' . $r10 . '"/>'
        . '<mergeCell ref="A' . $r11 . ':C' . $r11 . '"/>'
        . '<mergeCell ref="D' . $r11 . ':E' . $r11 . '"/>'
        . '<mergeCell ref="F' . $r11 . ':G' . $r11 . '"/>'
        . '<mergeCell ref="I' . $r11 . ':J' . $r11 . '"/>'
        . '<mergeCell ref="A' . $r12 . ':C' . $r12 . '"/>'
        . '<mergeCell ref="D' . $r12 . ':E' . $r12 . '"/>'
        . '<mergeCell ref="F' . $r12 . ':G' . $r12 . '"/>'
        . '<mergeCell ref="I' . $r12 . ':J' . $r12 . '"/>'
        . '<mergeCell ref="D' . $r13 . ':E' . $r13 . '"/>'
        . '<mergeCell ref="F' . $r13 . ':G' . $r13 . '"/>'
        . '<mergeCell ref="I' . $r13 . ':J' . $r13 . '"/>'
        . '<mergeCell ref="D' . $r14 . ':E' . $r14 . '"/>'
        . '<mergeCell ref="F' . $r14 . ':G' . $r14 . '"/>'
        . '<mergeCell ref="I' . $r14 . ':J' . $r14 . '"/>'
        . '</mergeCells>';

    $dataValidationsXml = '<dataValidations count="2">'
        . '<dataValidation sqref="C5" showDropDown="0" showInputMessage="0" showErrorMessage="1" allowBlank="1" errorTitle="Thông báo" error="Bạn phải nhập [Đạt] = d, Đ, đ, 1   [Chưa đạt] = c, CĐ, cđ, 2" type="list" errorStyle="stop" operator="between"><formula1>&quot;Đ,d,đ,1,CĐ,C,cđ,2&quot;</formula1><formula2>0</formula2></dataValidation>'
        . '<dataValidation sqref="A5 F8:K' . $lastDataRow . '" showDropDown="0" showInputMessage="0" showErrorMessage="1" allowBlank="1" errorTitle="Thông báo" error="Giá trị con điểm phải là số &gt;=0 và &lt;=10 " type="decimal" errorStyle="stop" operator="between"><formula1>0</formula1><formula2>10</formula2></dataValidation>'
        . '</dataValidations>';

    $tabSelectedAttr = $isActiveTab ? ' tabSelected="1"' : '';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetPr filterMode="0"><outlinePr summaryBelow="1" summaryRight="1"/><pageSetUpPr fitToPage="0"/></sheetPr>'
        . '<dimension ref="A1:AK' . $r14 . '"/>'
        . '<sheetViews><sheetView showFormulas="0" showGridLines="1" showRowColHeaders="1" showZeros="1" rightToLeft="0"' . $tabSelectedAttr . ' showOutlineSymbols="1" defaultGridColor="1" view="normal" topLeftCell="A1" colorId="64" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100" workbookViewId="0"><selection pane="topLeft" activeCell="A1" activeCellId="0" sqref="A1"/></sheetView></sheetViews>'
        . '<sheetFormatPr baseColWidth="8" defaultColWidth="8.96484375" defaultRowHeight="12.75" zeroHeight="0" outlineLevelRow="0"/>'
        . '<cols>'
        . '<col width="4.99" customWidth="1" style="47" min="1" max="1"/>'
        . '<col hidden="1" width="5.85" customWidth="1" style="47" min="2" max="2"/>'
        . '<col width="21.99" customWidth="1" style="48" min="3" max="3"/>'
        . '<col width="6.99" customWidth="1" style="48" min="4" max="4"/>'
        . '<col hidden="1" width="10.13" customWidth="1" style="48" min="5" max="5"/>'
        . '<col width="6.43" customWidth="1" style="47" min="6" max="11"/>'
        . '<col width="5.99" customWidth="1" style="49" min="12" max="12"/>'
        . '<col width="9.14" customWidth="1" style="50" min="13" max="13"/>'
        . '<col width="5.85" customWidth="1" style="51" min="14" max="16"/>'
        . '<col width="5.85" customWidth="1" style="52" min="17" max="37"/>'
        . '</cols>'
        . '<sheetData>'
        . $headerRows
        . $studentRowsXml
        . $summaryRows
        . '</sheetData>'
        . $mergeCellsXml
        . $dataValidationsXml
        . '<printOptions horizontalCentered="0" verticalCentered="0" headings="0" gridLines="0" gridLinesSet="1"/>'
        . '<pageMargins left="0.5" right="0" top="0.25" bottom="0.25" header="0.5118" footer="0.5118"/>'
        . '<pageSetup orientation="portrait" paperSize="1" scale="100" fitToHeight="1" fitToWidth="1" pageOrder="downThenOver" blackAndWhite="0" draft="0" horizontalDpi="300" verticalDpi="300" copies="1"/>'
        . '</worksheet>';
}

function toAsciiSlug(string $str): string {
    $str = preg_replace('/[áàảãạăắằẳẵặâấầẩẫậ]/u', 'a', $str);
    $str = preg_replace('/[ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ]/u', 'a', $str);
    $str = preg_replace('/[éèẻẽẹêếềểễệ]/u', 'e', $str);
    $str = preg_replace('/[ÉÈẺẼẸÊẾỀỂỄỆ]/u', 'e', $str);
    $str = preg_replace('/[íìỉĩị]/u', 'i', $str);
    $str = preg_replace('/[ÍÌỈĨỊ]/u', 'i', $str);
    $str = preg_replace('/[óòỏõọôốồổỗộơớờởỡợ]/u', 'o', $str);
    $str = preg_replace('/[ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ]/u', 'o', $str);
    $str = preg_replace('/[úùủũụưứừửữự]/u', 'u', $str);
    $str = preg_replace('/[ÚÙỦŨỤƯỨỪỬỮỰ]/u', 'u', $str);
    $str = preg_replace('/[ýỳỷỹỵ]/u', 'y', $str);
    $str = preg_replace('/[ÝỲỶỸỴ]/u', 'y', $str);
    $str = preg_replace('/[đ]/u', 'd', $str);
    $str = preg_replace('/[Đ]/u', 'd', $str);
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9]+/i', '_', $str);
    return trim($str, '_');
}

// Clone template to temp file
$tmpFile = tempnam(sys_get_temp_dir(), 'vnedu_') . '.xlsx';
copy($templatePath, $tmpFile);

$zip = new ZipArchive();
if ($zip->open($tmpFile) !== true) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể mở template']);
    exit;
}

$sheetCount   = count($classesToExport);
$usedNames    = [];
$sheetListXml = '';
$relsListXml  = '';
$ctListXml    = '';

foreach ($classesToExport as $k => $cData) {
    $sheetIndex = $k + 1;
    $isActiveTab = ($k === 0);
    $xml = generateClassWorksheetXml($don_vi, $truong, $mon, $hoc_ky, $nam_hoc, $cData['name'], $cData['students'], $tx_column, $isActiveTab);

    $sheetPath = "xl/worksheets/sheet{$sheetIndex}.xml";
    $zip->addFromString($sheetPath, $xml);

    // Make unique safe sheet title (max 31 chars)
    $monSlug = toAsciiSlug($mon);
    $classSlug = toAsciiSlug($cData['name']);
    $title = $monSlug !== '' ? ($monSlug . '_' . $classSlug) : $cData['name'];
    $title = preg_replace('/[\\\\\/\?\*\[\]\:]/', '_', $title);
    $title = mb_substr($title, 0, 31, 'UTF-8');
    if (isset($usedNames[$title])) {
        $usedNames[$title]++;
        $suffix = '_' . $usedNames[$title];
        $title = mb_substr($title, 0, 31 - strlen($suffix), 'UTF-8') . $suffix;
    } else {
        $usedNames[$title] = 1;
    }

    $safeTitle    = htmlspecialchars($title, ENT_XML1, 'UTF-8');
    $sheetListXml .= '<sheet name="' . $safeTitle . '" sheetId="' . $sheetIndex . '" state="visible" r:id="rId' . $sheetIndex . '"/>';
    $relsListXml  .= '<Relationship Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetIndex . '.xml" Id="rId' . $sheetIndex . '"/>';
    $ctListXml    .= '<Override PartName="/xl/worksheets/sheet' . $sheetIndex . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
}

// Remove sheet2.xml if we only have 1 class
if ($sheetCount < 2) {
    $zip->deleteName('xl/worksheets/sheet2.xml');
}

// Update xl/workbook.xml
$wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<workbookPr/><workbookProtection/><bookViews><workbookView visibility="visible" minimized="0" showHorizontalScroll="1" showVerticalScroll="1" showSheetTabs="1" xWindow="0" yWindow="0" windowWidth="16384" windowHeight="8192" tabRatio="500" firstSheet="0" activeTab="0" autoFilterDateGrouping="1"/></bookViews>'
    . '<sheets>' . $sheetListXml . '</sheets>'
    . '<definedNames/><calcPr calcId="124519" fullCalcOnLoad="1" refMode="A1" iterate="0" iterateCount="100" iterateDelta="0.001"/>'
    . '</workbook>';
$zip->deleteName('xl/workbook.xml');
$zip->addFromString('xl/workbook.xml', $wbXml);

// Update xl/_rels/workbook.xml.rels
$styleRId = $sheetCount + 1;
$themeRId = $sheetCount + 2;
$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . $relsListXml
    . '<Relationship Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml" Id="rId' . $styleRId . '"/>'
    . '<Relationship Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml" Id="rId' . $themeRId . '"/>'
    . '</Relationships>';
$zip->deleteName('xl/_rels/workbook.xml.rels');
$zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);

// Update [Content_Types].xml
$ctXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-theme+xml"/>'
    . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
    . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
    . $ctListXml
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '</Types>';
$zip->deleteName('[Content_Types].xml');
$zip->addFromString('[Content_Types].xml', $ctXml);

$zip->close();

// Output filename
$txLabel = strtoupper($tx_column);
if ($sheetCount === 1) {
    $safeName = preg_replace('/[\\\\\/\?\*\[\]\:]/', '_', $classesToExport[0]['name']);
    $filename = 'BangDiem_' . $safeName . '_' . $txLabel . '.xlsx';
} else {
    $filename = 'BangDiem_TatCaLop_' . $txLabel . '.xlsx';
}

// Clear any output buffer to ensure pure binary output
if (ob_get_length()) {
    ob_clean();
}
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
header('Pragma: public');
readfile($tmpFile);
@unlink($tmpFile);
exit;
