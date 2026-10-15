<?php
/**
 * exam_exporter.php — Xuất đề thi từ ma trận ra Word (.docx)
 * Gồm nhiều đề (A, B) + đáp án từng đề trong 1 file.
 * Tái sử dụng layout từ teacher/export_exam_word.php
 */

require_once dirname(__DIR__, 2) . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\AbstractContainer;
use Matran\PhpWord\Element\Container as PhpWordBlock;

require_once __DIR__ . '/PhpWordBlock.php';

if (!function_exists('mtn_convert_latex')) {
    function mtn_convert_latex($text) {
        if (empty($text)) return '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = str_replace(['&nbsp;', '&lt;', '&gt;', '&amp;'], [' ', '<', '>', '&'], $text);
        $text = str_replace(['$$', '$', '\\(', '\\)', '\\[', '\\]'], '', $text);
        $text = str_replace(['\\begin{cases}', '\\end{cases}'], ['{', '}'], $text);
        $text = str_replace('\\\\', ' ; ', $text);
        $text = preg_replace('/\\\\frac\{([^}]+)\}\{([^}]+)\}/', '($1)/($2)', $text);
        $text = preg_replace('/\\\\sqrt\{([^}]+)\}/', '√($1)', $text);
        $text = str_replace('\\sqrt', '√', $text);
        $symbols = [
            '\\ne' => '≠', '\\neq' => '≠', '\\le' => '≤', '\\leq' => '≤',
            '\\ge' => '≥', '\\geq' => '≥', '\\pm' => '±', '\\mp' => '∓',
            '\\times' => '×', '\\cdot' => '·', '\\div' => '÷',
            '\\alpha' => 'α', '\\beta' => 'β', '\\gamma' => 'γ', '\\delta' => 'δ',
            '\\theta' => 'θ', '\\lambda' => 'λ', '\\mu' => 'μ', '\\pi' => 'π',
            '\\sigma' => 'σ', '\\phi' => 'φ', '\\omega' => 'ω',
            '\\Delta' => 'Δ', '\\Sigma' => 'Σ', '\\Omega' => 'Ω',
            '\\sum' => 'Σ', '\\prod' => 'Π', '\\int' => '∫', '\\infty' => '∞',
            '\\approx' => '≈', '\\equiv' => '≡', '\\subset' => '⊂', '\\supset' => '⊃',
            '\\in' => '∈', '\\notin' => '∉', '\\rightarrow' => '→', '\\leftarrow' => '←',
            '\\Rightarrow' => '⇒', '\\Leftarrow' => '⇐', '\\leftrightarrow' => '↔',
            '\\Leftrightarrow' => '⇔', '\\forall' => '∀', '\\exists' => '∃',
            '\\partial' => '∂', '\\nabla' => '∇', '\\angle' => '∠',
            '\\perp' => '⊥', '\\parallel' => '∥',
        ];
        $text = str_replace(array_keys($symbols), array_values($symbols), $text);
        $text = preg_replace('/\\\\[a-zA-Z]+/', '', $text);
        $text = str_replace('\\', '', $text);
        $text = str_replace(['{', '}'], '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}

if (!function_exists('mtn_exam_slug')) {
    function mtn_exam_slug($string) {
        $vietnamese = [
            'á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
            'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
            'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
            'ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
            'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
            'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y','đ'=>'d',
            'Á'=>'A','À'=>'A','Ả'=>'A','Ã'=>'A','Ạ'=>'A','Ă'=>'A','Ắ'=>'A','Ằ'=>'A','Ẳ'=>'A','Ẵ'=>'A','Ặ'=>'A',
            'Â'=>'A','Ấ'=>'A','Ầ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ậ'=>'A','É'=>'E','È'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ẹ'=>'E',
            'Ê'=>'E','Ế'=>'E','Ề'=>'E','Ể'=>'E','Ễ'=>'E','Ệ'=>'E','Í'=>'I','Ì'=>'I','Ỉ'=>'I','Ĩ'=>'I','Ị'=>'I',
            'Ó'=>'O','Ò'=>'O','Ỏ'=>'O','Õ'=>'O','Ọ'=>'O','Ô'=>'O','Ố'=>'O','Ồ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ộ'=>'O',
            'Ơ'=>'O','Ớ'=>'O','Ờ'=>'O','Ở'=>'O','Ỡ'=>'O','Ợ'=>'O','Ú'=>'U','Ù'=>'U','Ủ'=>'U','Ũ'=>'U','Ụ'=>'U',
            'Ư'=>'U','Ứ'=>'U','Ừ'=>'U','Ử'=>'U','Ữ'=>'U','Ự'=>'U','Ý'=>'Y','Ỳ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Ỵ'=>'Y','Đ'=>'D',
        ];
        $string = str_replace(array_keys($vietnamese), array_values($vietnamese), $string);
        $string = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $string);
        $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        $string = trim($string, '-');
        $string = strtolower($string);
        if (strlen($string) > 50) $string = rtrim(substr($string, 0, 50), '-');
        return $string ?: 'de-thi-' . date('Ymd');
    }
}

/**
 * Xuất toàn bộ đề (nhiều variant + đáp án) ra 1 file Word và stream download.
 *
 * @param array $exam {
 *   school, subject, grade, semester, year, duration, examType, title,
 *   variants: [ ['variant'=>'A','questions'=>[...], 'total_questions', 'total_points'], ... ],
 *   difference: [ 'A-B' => 62.5 ],
 * }
 */
/**
 * Đường dẫn file mẫu đề thi (.docx) - có thể chỉnh sửa thủ công bằng Word.
 */
function examTemplatePath(): string {
    return dirname(__DIR__, 2) . '/data/template_dethi.docx';
}

function examEsc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

/**
 * Chuyển LaTeX -> text thường rồi escape để ghi an toàn vào XML Word
 * (PhpWord đang tắt output escaping nên phải tự escape).
 */
function examText($s): string {
    return examEsc(mtn_convert_latex((string)$s));
}

function examStyles(): array {
    return [
        'title'   => ['name' => 'Times New Roman', 'size' => 15, 'bold' => true],
        'heading' => ['name' => 'Times New Roman', 'size' => 13, 'bold' => true],
        'normal'  => ['name' => 'Times New Roman', 'size' => 13],
        'bold'    => ['name' => 'Times New Roman', 'size' => 13, 'bold' => true],
        'italic'  => ['name' => 'Times New Roman', 'size' => 12, 'italic' => true],
        'viet'    => ['name' => 'Times New Roman', 'size' => 12],
    ];
}

/**
 * Chia danh sách câu hỏi thành 3 phần: TNKQ nhiều lựa chọn / Đúng-Sai / Tự luận.
 */
function examSplitParts(array $questions): array {
    $part1 = $part2 = $part3 = [];
    foreach ($questions as $q) {
        $t = $q['question_type'] ?? '';
        if ($t === 'DS') $part2[] = $q;
        elseif ($t === 'TL') $part3[] = $q;
        else $part1[] = $q;
    }
    return [$part1, $part2, $part3];
}

function examCalcPts(array $list): float {
    $pts = 0.0;
    foreach ($list as $q) {
        if (isset($q['points']) && is_numeric($q['points'])) $pts += (float)$q['points'];
    }
    return round($pts, 2);
}

/**
 * Ghi phần câu hỏi (PHẦN I/II/III) của một mã đề vào container.
 */
function examRenderQuestions(AbstractContainer $c, array $variant): void {
    $st = examStyles();
    $romanNumerals = ['I', 'II', 'III', 'IV', 'V'];
    [$part1, $part2, $part3] = examSplitParts($variant['questions'] ?? []);
    $partIdx = 0;

    if (!empty($part1)) {
        $roman = $romanNumerals[$partIdx++];
        $c->addText("PHẦN {$roman}. TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN (" . examCalcPts($part1) . " điểm)", $st['heading'], ['spaceBefore' => 120, 'spaceAfter' => 40]);
        $c->addText("Học sinh trả lời từ câu 1 đến câu " . count($part1) . ", mỗi câu chỉ chọn một phương án.", $st['italic'], ['spaceAfter' => 80]);
        foreach ($part1 as $idx => $q) {
            $n = $idx + 1;
            $c->addText("Câu {$n}: " . examText($q['question'] ?? ''), $st['bold'], ['spaceAfter' => 30]);
            if (!empty($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $oi => $opt) {
                    $c->addText('     ' . chr(65 + $oi) . '. ' . examText($opt), $st['normal'], ['spaceAfter' => 20]);
                }
            }
            $c->addText('', $st['normal'], ['spaceAfter' => 50]);
        }
    }

    if (!empty($part2)) {
        $roman = $romanNumerals[$partIdx++];
        $c->addText("PHẦN {$roman}. TRẮC NGHIỆM ĐÚNG SAI (" . examCalcPts($part2) . " điểm)", $st['heading'], ['spaceBefore' => 120, 'spaceAfter' => 40]);
        $c->addText("Trong mỗi ý a), b), c), d) ở mỗi câu, học sinh ghi Đúng hoặc Sai.", $st['italic'], ['spaceAfter' => 80]);
        foreach ($part2 as $idx => $q) {
            $n = $idx + 1;
            $c->addText("Câu {$n}: " . examText($q['question'] ?? ''), $st['bold'], ['spaceAfter' => 30]);
            if (!empty($q['items']) && is_array($q['items'])) {
                foreach ($q['items'] as $ii => $item) {
                    $lbl = $item['label'] ?? chr(97 + $ii);
                    $c->addText('     ' . examEsc((string)$lbl) . ') ' . examText($item['statement'] ?? $item['text'] ?? ''), $st['normal'], ['spaceAfter' => 20]);
                }
            }
            $c->addText('', $st['normal'], ['spaceAfter' => 50]);
        }
    }

    if (!empty($part3)) {
        $roman = $romanNumerals[$partIdx++];
        $c->addText("PHẦN {$roman}. TỰ LUẬN (" . examCalcPts($part3) . " điểm)", $st['heading'], ['spaceBefore' => 120, 'spaceAfter' => 80]);
        foreach ($part3 as $idx => $q) {
            $n = $idx + 1;
            $c->addText("Câu {$n} (" . ($q['points'] ?? '') . " điểm): " . examText($q['question'] ?? ''), $st['bold'], ['spaceAfter' => 40]);
            $c->addText('', $st['normal'], ['spaceAfter' => 60]);
        }
    }
}

/**
 * Ghi phần đáp án (bảng đáp án + hướng dẫn chấm) của một mã đề vào container.
 * KHÔNG bao gồm dòng tiêu đề "ĐÁP ÁN..." (do mẫu/legacy tự thêm).
 */
function examRenderAnswer(AbstractContainer $c, array $variant): void {
    $st = examStyles();
    [$part1, $part2, $part3] = examSplitParts($variant['questions'] ?? []);

    if (!empty($part1)) {
        $c->addText('I. ĐÁP ÁN TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN', $st['heading'], ['spaceBefore' => 60, 'spaceAfter' => 40]);
        $t1 = $c->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 50, 'unit' => 'pct']);
        $t1->addRow(330);
        $t1->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Câu', $st['bold'], ['alignment' => 'center']);
        $t1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $st['bold'], ['alignment' => 'center']);
        $t1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $st['bold'], ['alignment' => 'center']);
        $t1->addCell(4500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $st['bold'], ['alignment' => 'center']);
        foreach ($part1 as $idx => $q) {
            $ans = '';
            if (isset($q['correct'])) {
                if (is_array($q['correct'])) {
                    $ans = implode(', ', array_map(fn($z) => is_numeric($z) ? chr(65 + (int)$z) : strtoupper((string)$z), $q['correct']));
                } elseif (is_numeric($q['correct'])) {
                    $ans = chr(65 + (int)$q['correct']);
                } else {
                    $ans = strtoupper((string)$q['correct']);
                }
            }
            $t1->addRow();
            $t1->addCell(1500)->addText($idx + 1, $st['normal'], ['alignment' => 'center']);
            $t1->addCell(2000)->addText(examEsc($ans), $st['bold'], ['alignment' => 'center']);
            $t1->addCell(2000)->addText(examEsc((string)($q['level'] ?? 'NB')), $st['normal'], ['alignment' => 'center']);
            $t1->addCell(4500)->addText($q['points'] ?? '', $st['normal'], ['alignment' => 'center']);
        }
        $c->addText('', $st['normal'], ['spaceAfter' => 80]);
    }

    if (!empty($part2)) {
        $c->addText('II. ĐÁP ÁN TRẮC NGHIỆM ĐÚNG SAI', $st['heading'], ['spaceBefore' => 60, 'spaceAfter' => 40]);
        $t2 = $c->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 50, 'unit' => 'pct']);
        $t2->addRow(330);
        $t2->addCell(1200, ['bgColor' => 'E0E0E0'])->addText('Câu', $st['bold'], ['alignment' => 'center']);
        $t2->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Ý', $st['bold'], ['alignment' => 'center']);
        $t2->addCell(3000, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $st['bold'], ['alignment' => 'center']);
        $t2->addCell(2500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $st['bold'], ['alignment' => 'center']);
        foreach ($part2 as $idx => $q) {
            $items = $q['items'] ?? [];
            foreach ($items as $ii => $item) {
                $lbl = $item['label'] ?? chr(97 + $ii);
                $isT = ($item['correct'] === true || $item['correct'] === 1 || $item['correct'] === 'true' || $item['correct'] === 'dung' || $item['correct'] === 'Đúng');
                $t2->addRow();
                $t2->addCell(1200)->addText($ii === 0 ? ($idx + 1) : '', $st['bold'], ['alignment' => 'center']);
                $t2->addCell(2000)->addText(examEsc((string)$lbl) . ')', $st['normal'], ['alignment' => 'center']);
                $t2->addCell(3000)->addText($isT ? 'Đúng' : 'Sai', $st['bold'], ['alignment' => 'center']);
                $t2->addCell(2500)->addText($ii === 0 ? ($q['points'] ?? '') : '', $st['normal'], ['alignment' => 'center']);
            }
        }
        $c->addText('', $st['normal'], ['spaceAfter' => 80]);
    }

    if (!empty($part3)) {
        $c->addText('III. HƯỚNG DẪN CHẤM TỰ LUẬN', $st['heading'], ['spaceBefore' => 60, 'spaceAfter' => 40]);
        foreach ($part3 as $idx => $q) {
            $c->addText('Câu ' . ($idx + 1) . ' (' . ($q['points'] ?? '') . ' điểm):', $st['bold'], ['spaceAfter' => 20]);
            $guide = examText($q['suggested_answer'] ?? $q['answer'] ?? 'Theo biểu điểm và đáp án chi tiết của tổ chuyên môn.');
            $c->addText('   ' . $guide, $st['normal'], ['spaceAfter' => 40]);
        }
    }
}

/**
 * Xuất đề thi: ưu tiên dùng mẫu Word (template_dethi.docx) nếu có, nếu lỗi thì dựng trực tiếp.
 */
function exportExamPaper(array $exam) {
    $tplPath = examTemplatePath();
    if (is_file($tplPath) && class_exists(TemplateProcessor::class)) {
        try {
            exportExamPaperFromTemplate($exam, $tplPath);
            return;
        } catch (\Throwable $e) {
            error_log('CVD ExamDe: dùng mẫu thất bại, chuyển dựng trực tiếp. ' . $e->getMessage());
        }
    }
    exportExamPaperDirect($exam);
}

/**
 * Xuất đề thi dựa trên file mẫu: mỗi mã đề render riêng rồi ghép lại thành 1 file.
 */
function exportExamPaperFromTemplate(array $exam, string $tplPath): void {
    $variants = $exam['variants'] ?? [];
    if (empty($variants)) {
        throw new Exception('Không có mã đề để xuất.');
    }

    $st = examStyles();
    $lastVariant = count($variants) - 1;
    $tmpFiles = [];

    try {
        foreach ($variants as $vIdx => $variant) {
            $label = $variant['variant'] ?? chr(65 + $vIdx);

            $tpl = new TemplateProcessor($tplPath);
            $vars = $tpl->getVariables();
            foreach (['EXAM_BODY', 'ANSWER_BODY'] as $need) {
                if (!in_array($need, $vars, true)) {
                    throw new Exception("Mẫu đề thi thiếu macro \${$need}. Hãy chạy lại build_template.php.");
                }
            }

            $tpl->setValues([
                'school'      => examEsc(mb_strtoupper((string)($exam['school'] ?? 'THCS'), 'UTF-8')),
                'titleHeader' => examEsc(mb_strtoupper(($exam['examType'] ?? 'KIỂM TRA') . ' - MÔN ' . ($exam['subject'] ?? ''), 'UTF-8')),
                'year'        => examEsc((string)($exam['year'] ?? '')),
                'semester'    => examEsc((string)($exam['semester'] ?? '')),
                'duration'    => examEsc((string)($exam['duration'] ?? '45')),
                'variant'     => examEsc((string)$label),
            ]);

            $questionsBlock = new PhpWordBlock();
            examRenderQuestions($questionsBlock, $variant);
            $tpl->setComplexBlock('EXAM_BODY', $questionsBlock);

            $answerBlock = new PhpWordBlock();
            examRenderAnswer($answerBlock, $variant);
            if ($vIdx === $lastVariant && !empty($exam['difference'])) {
                $notes = [];
                foreach ($exam['difference'] as $pair => $pct) {
                    $notes[] = 'Đề ' . examEsc((string)$pair) . ": khác nhau {$pct}%";
                }
                $answerBlock->addText('', $st['normal'], ['spaceAfter' => 100]);
                $answerBlock->addText('(Tham khảo nội bộ) ' . implode(' | ', $notes), $st['viet'], ['alignment' => 'right']);
            }
            $tpl->setComplexBlock('ANSWER_BODY', $answerBlock);

            $tmp = tempnam(sys_get_temp_dir(), 'cvd_de_tpl_') . '.docx';
            $tpl->saveAs($tmp);
            if (!is_file($tmp) || filesize($tmp) === 0) {
                throw new Exception('Không thể tạo file Word từ mẫu.');
            }
            $tmpFiles[] = $tmp;
        }

        $merged = tempnam(sys_get_temp_dir(), 'cvd_de_merge_') . '.docx';
        mergeExamDocx($tmpFiles, $merged);

        $slug = mtn_exam_slug(($exam['title'] ?? 'de-thi'));
        try {
            streamDocxFile($merged, 'DeThi_' . $slug . '_' . date('Ymd_His') . '.docx');
        } finally {
            @unlink($merged);
        }
    } finally {
        foreach ($tmpFiles as $f) @unlink($f);
    }
}

/**
 * Ghép nhiều file .docx (cùng dựa trên một mẫu) thành 1 file, ngăn cách bằng ngắt trang.
 */
function mergeExamDocx(array $files, string $outPath): void {
    if (empty($files)) {
        throw new Exception('Không có đề để ghép.');
    }

    $baseXml = null;
    $sectPr = '';
    $bodies = [];

    foreach ($files as $i => $file) {
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            throw new Exception('Không mở được file Word tạm.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === null) {
            throw new Exception('File Word tạm không hợp lệ.');
        }

        if ($i === 0) {
            $baseXml = $xml;
            if (preg_match('/<w:sectPr\b.*?<\/w:sectPr>/s', $xml, $m)) {
                $sectPr = $m[0];
            }
        }

        if (!preg_match('/<w:body>(.*)<\/w:body>/s', $xml, $m)) {
            continue;
        }
        $bodies[] = preg_replace('/<w:sectPr\b.*?<\/w:sectPr>\s*$/s', '', $m[1]);
    }

    if ($baseXml === null || empty($bodies)) {
        throw new Exception('Không có nội dung để ghép.');
    }

    $pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    $inner = implode($pageBreak, $bodies) . $sectPr;

    $openPos = strpos($baseXml, '<w:body>');
    $closePos = strrpos($baseXml, '</w:body>');
    if ($openPos === false || $closePos === false) {
        throw new Exception('Cấu trúc file Word không hợp lệ.');
    }
    $newXml = substr($baseXml, 0, $openPos + 8) . $inner . substr($baseXml, $closePos);

    if (!copy($files[0], $outPath)) {
        throw new Exception('Không tạo được file Word kết quả.');
    }
    $zip = new \ZipArchive();
    if ($zip->open($outPath) !== true) {
        throw new Exception('Không mở được file Word kết quả.');
    }
    $zip->deleteName('word/document.xml');
    $zip->addFromString('word/document.xml', $newXml);
    $zip->close();
}

/**
 * Gửi file Word cho trình duyệt tải về.
 */
function streamDocxFile(string $path, string $outputFilename): void {
    $fileSize = filesize($path);

    while (ob_get_level()) ob_end_clean();
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
    @ini_set('zlib.output_compression', 'Off');

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $outputFilename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new Exception('Không thể đọc file Word.');
    }
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
}

/**
 * Dựng đề thi trực tiếp bằng PhpWord (không dùng mẫu) - phương án dự phòng.
 */

function exportExamPaperDirect(array $exam) {
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(13);

    $section = $phpWord->addSection([
        'marginTop'    => 900,
        'marginBottom' => 900,
        'marginLeft'   => 1100,
        'marginRight'  => 1100,
    ]);

    $titleStyle   = ['name' => 'Times New Roman', 'size' => 15, 'bold' => true];
    $headingStyle = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];
    $normalStyle  = ['name' => 'Times New Roman', 'size' => 13];
    $boldStyle    = ['name' => 'Times New Roman', 'size' => 13, 'bold' => true];
    $italicStyle  = ['name' => 'Times New Roman', 'size' => 12, 'italic' => true];
    $viet           = ['name' => 'Times New Roman', 'size' => 12];

    $romanNumerals = ['I', 'II', 'III', 'IV', 'V'];

    $variants = $exam['variants'] ?? [];
    $lastVariant = count($variants) - 1;

    foreach ($variants as $vIdx => $variant) {
        $label = $variant['variant'] ?? chr(65 + $vIdx);
        $questions = $variant['questions'] ?? [];

        // Phân loại phần
        $part1 = $part2 = $part3 = [];
        foreach ($questions as $q) {
            $t = $q['question_type'] ?? '';
            if ($t === 'DS') $part2[] = $q;
            elseif ($t === 'TL') $part3[] = $q;
            else $part1[] = $q;
        }

        $calcPts = function ($list) {
            $pts = 0.0;
            foreach ($list as $q) {
                if (isset($q['points']) && is_numeric($q['points'])) $pts += (float)$q['points'];
            }
            return round($pts, 2);
        };

        // ── Header đề ──────────────────────────────────────────────
        $section->addText('TRƯỜNG ' . examEsc(mb_strtoupper((string)($exam['school'] ?? 'THCS'), 'UTF-8')), $normalStyle, ['alignment' => 'left']);
        $section->addText(
            examEsc(mb_strtoupper(($exam['examType'] ?? 'KIỂM TRA') . ' - MÔN ' . ($exam['subject'] ?? ''), 'UTF-8')),
            $titleStyle, ['alignment' => 'center', 'spaceBefore' => 40]
        );
        $section->addText('Năm học ' . examEsc((string)($exam['year'] ?? '')) . ' - ' . examEsc((string)($exam['semester'] ?? '')), $normalStyle, ['alignment' => 'center']);
        $section->addText('ĐỀ ' . examEsc((string)$label), ['name' => 'Times New Roman', 'size' => 16, 'bold' => true], ['alignment' => 'center', 'spaceAfter' => 40]);
        $section->addText('Thời gian làm bài: ' . examEsc((string)($exam['duration'] ?? '45')) . ' phút (không kể thời gian phát đề)', $normalStyle, ['alignment' => 'center', 'spaceAfter' => 60]);
        $section->addText('Họ và tên học sinh: ...............................................  Lớp: .............', $normalStyle);
        $section->addText('', $normalStyle, ['spaceAfter' => 40]);

        $partIdx = 0;

        // PHẦN I — TNKQ nhiều lựa chọn
        if (!empty($part1)) {
            $roman = $romanNumerals[$partIdx++];
            $section->addText("PHẦN {$roman}. TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN (" . $calcPts($part1) . " điểm)", $headingStyle, ['spaceBefore' => 120, 'spaceAfter' => 40]);
            $section->addText("Học sinh trả lời từ câu 1 đến câu " . count($part1) . ", mỗi câu chỉ chọn một phương án.", $italicStyle, ['spaceAfter' => 80]);
            foreach ($part1 as $idx => $q) {
                $n = $idx + 1;
                $section->addText("Câu {$n}: " . examText($q['question'] ?? ''), $boldStyle, ['spaceAfter' => 30]);
                if (!empty($q['options']) && is_array($q['options'])) {
                    foreach ($q['options'] as $oi => $opt) {
                        $section->addText('     ' . chr(65 + $oi) . '. ' . examText($opt), $normalStyle, ['spaceAfter' => 20]);
                    }
                }
                $section->addText('', $normalStyle, ['spaceAfter' => 50]);
            }
        }

        // PHẦN II — Đúng/Sai
        if (!empty($part2)) {
            $roman = $romanNumerals[$partIdx++];
            $section->addText("PHẦN {$roman}. TRẮC NGHIỆM ĐÚNG SAI (" . $calcPts($part2) . " điểm)", $headingStyle, ['spaceBefore' => 120, 'spaceAfter' => 40]);
            $section->addText("Trong mỗi ý a), b), c), d) ở mỗi câu, học sinh ghi Đúng hoặc Sai.", $italicStyle, ['spaceAfter' => 80]);
            foreach ($part2 as $idx => $q) {
                $n = $idx + 1;
                $section->addText("Câu {$n}: " . examText($q['question'] ?? ''), $boldStyle, ['spaceAfter' => 30]);
                if (!empty($q['items']) && is_array($q['items'])) {
                    foreach ($q['items'] as $ii => $item) {
                        $lbl = $item['label'] ?? chr(97 + $ii);
                        $section->addText('     ' . examEsc((string)$lbl) . ') ' . examText($item['statement'] ?? $item['text'] ?? ''), $normalStyle, ['spaceAfter' => 20]);
                    }
                }
                $section->addText('', $normalStyle, ['spaceAfter' => 50]);
            }
        }

        // PHẦN III — Tự luận
        if (!empty($part3)) {
            $roman = $romanNumerals[$partIdx++];
            $section->addText("PHẦN {$roman}. TỰ LUẬN (" . $calcPts($part3) . " điểm)", $headingStyle, ['spaceBefore' => 120, 'spaceAfter' => 80]);
            foreach ($part3 as $idx => $q) {
                $n = $idx + 1;
                $section->addText("Câu {$n} (" . ($q['points'] ?? '') . " điểm): " . examText($q['question'] ?? ''), $boldStyle, ['spaceAfter' => 40]);
                $section->addText('', $normalStyle, ['spaceAfter' => 60]);
            }
        }

        // ── Đáp án đề ──────────────────────────────────────────────
        $section->addPageBreak();
        $section->addText('ĐÁP ÁN VÀ HƯỚNG DẪN CHẤM - ĐỀ ' . $label, $titleStyle, ['alignment' => 'center', 'spaceAfter' => 120]);

        if (!empty($part1)) {
            $section->addText('I. ĐÁP ÁN TRẮC NGHIỆM NHIỀU LỰA CHỌN', $headingStyle, ['spaceBefore' => 60, 'spaceAfter' => 40]);
            $t1 = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 50, 'unit' => 'pct']);
            $t1->addRow(330);
            $t1->addCell(1500, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
            $t1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Đáp án', $boldStyle, ['alignment' => 'center']);
            $t1->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Mức độ', $boldStyle, ['alignment' => 'center']);
            $t1->addCell(4500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $boldStyle, ['alignment' => 'center']);
            foreach ($part1 as $idx => $q) {
                $ans = '';
                if (isset($q['correct'])) {
                    if (is_array($q['correct'])) {
                        $ans = implode(', ', array_map(fn($c) => is_numeric($c) ? chr(65 + (int)$c) : strtoupper((string)$c), $q['correct']));
                    } elseif (is_numeric($q['correct'])) {
                        $ans = chr(65 + (int)$q['correct']);
                    } else {
                        $ans = strtoupper((string)$q['correct']);
                    }
                }
                $t1->addRow();
                $t1->addCell(1500)->addText($idx + 1, $normalStyle, ['alignment' => 'center']);
                $t1->addCell(2000)->addText(examEsc($ans), $boldStyle, ['alignment' => 'center']);
                $t1->addCell(2000)->addText(examEsc((string)($q['level'] ?? 'NB')), $normalStyle, ['alignment' => 'center']);
                $t1->addCell(4500)->addText($q['points'] ?? '', $normalStyle, ['alignment' => 'center']);
            }
            $section->addText('', $normalStyle, ['spaceAfter' => 80]);
        }

        if (!empty($part2)) {
            $section->addText('II. ĐÁP ÁN TRẮC NGHIỆM ĐÚNG SAI', $headingStyle, ['spaceBefore' => 60, 'spaceAfter' => 40]);
            $t2 = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 50, 'unit' => 'pct']);
            $t2->addRow(330);
            $t2->addCell(1200, ['bgColor' => 'E0E0E0'])->addText('Câu', $boldStyle, ['alignment' => 'center']);
            $t2->addCell(2000, ['bgColor' => 'E0E0E0'])->addText('Ý', $boldStyle, ['alignment' => 'center']);
            $t2->addCell(3000, ['bgColor' => 'E0E0E0'])->addText('Đáp án (Đ/S)', $boldStyle, ['alignment' => 'center']);
            $t2->addCell(2500, ['bgColor' => 'E0E0E0'])->addText('Điểm', $boldStyle, ['alignment' => 'center']);
            foreach ($part2 as $idx => $q) {
                $items = $q['items'] ?? [];
                foreach ($items as $ii => $item) {
                    $lbl = $item['label'] ?? chr(97 + $ii);
                    $isT = ($item['correct'] === true || $item['correct'] === 1 || $item['correct'] === 'true' || $item['correct'] === 'dung' || $item['correct'] === 'Đúng');
                    $t2->addRow();
                    $t2->addCell(1200)->addText($ii === 0 ? ($idx + 1) : '', $boldStyle, ['alignment' => 'center']);
                    $t2->addCell(2000)->addText(examEsc((string)$lbl) . ')', $normalStyle, ['alignment' => 'center']);
                    $t2->addCell(3000)->addText($isT ? 'Đúng' : 'Sai', $boldStyle, ['alignment' => 'center']);
                    $t2->addCell(2500)->addText($ii === 0 ? ($q['points'] ?? '') : '', $normalStyle, ['alignment' => 'center']);
                }
            }
            $section->addText('', $normalStyle, ['spaceAfter' => 80]);
        }

        if (!empty($part3)) {
            $section->addText('III. HƯỚNG DẪN CHẤM TỰ LUẬN', $headingStyle, ['spaceBefore' => 60, 'spaceAfter' => 40]);
            foreach ($part3 as $idx => $q) {
                $section->addText('Câu ' . ($idx + 1) . ' (' . ($q['points'] ?? '') . ' điểm):', $boldStyle, ['spaceAfter' => 20]);
                $guide = examText($q['suggested_answer'] ?? $q['answer'] ?? 'Theo biểu điểm và đáp án chi tiết của tổ chuyên môn.');
                $section->addText('   ' . $guide, $normalStyle, ['spaceAfter' => 50]);
            }
        }

        if ($vIdx < $lastVariant) {
            $section->addPageBreak();
        }
    }

    // Ghi chú tỷ lệ khác nhau (chỉ mang tính tham khảo khi soạn)
    if (!empty($exam['difference'])) {
        $section->addText('', $normalStyle, ['spaceAfter' => 100]);
        $note = [];
        foreach ($exam['difference'] as $pair => $pct) {
            $note[] = 'Đề ' . examEsc((string)$pair) . ": khác nhau {$pct}%";
        }
        $section->addText('(Tham khảo nội bộ) ' . implode(' | ', $note), $viet, ['alignment' => 'right']);
    }

    $slug = mtn_exam_slug(($exam['title'] ?? 'de-thi'));
    $outputFilename = 'DeThi_' . $slug . '_' . date('Ymd_His') . '.docx';
    $tempPath = sys_get_temp_dir() . '/' . uniqid('cvd_de_', true) . '.docx';

    IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

    if (!is_file($tempPath) || filesize($tempPath) === 0) {
        @unlink($tempPath);
        throw new Exception('Không thể tạo file Word.');
    }

    $fileSize = filesize($tempPath);

    while (ob_get_level()) ob_end_clean();
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
    @ini_set('zlib.output_compression', 'Off');

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $outputFilename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);

    $handle = fopen($tempPath, 'rb');
    if ($handle === false) {
        @unlink($tempPath);
        throw new Exception('Không thể đọc file Word.');
    }
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    @unlink($tempPath);
}
