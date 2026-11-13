<?php
/**
 * Parser cho tính năng "Tạo đề từ file Word" (mẫu đề kiểm tra chuẩn Bộ GD-ĐT:
 * PHẦN I. TRẮC NGHIỆM {A. nhiều lựa chọn, B. đúng/sai} + PHẦN II. TỰ LUẬN),
 * cùng với file đáp án/hướng dẫn chấm riêng.
 *
 * Không liên quan đến action=import_from_word trong question_bank_handlers.php
 * (format tự quy ước khác, chỉ trắc nghiệm).
 */

/**
 * Đi qua toàn bộ Section/Element của 1 PhpWord document, trả về danh sách
 * tuần tự các block (giữ đúng thứ tự xuất hiện trong file) dạng:
 *   ['type' => 'text',  'content' => string]
 *   ['type' => 'table', 'rows' => [[cell1, cell2, ...], ...]]
 * Cần giữ thứ tự vì phần Đúng/Sai dựa vào bảng nằm NGAY SAU đoạn tình huống.
 */
function exam_word_extract_blocks($phpWordDoc) {
    $blocks = [];

    $collectText = function ($element) use (&$collectText) {
        if (method_exists($element, 'getText')) {
            $t = $element->getText();
            return is_string($t) ? $t : '';
        }
        if (method_exists($element, 'getElements')) {
            $out = '';
            foreach ($element->getElements() as $child) {
                $out .= $collectText($child);
            }
            return $out;
        }
        return '';
    };

    foreach ($phpWordDoc->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                $rows = [];
                foreach ($element->getRows() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cellText = '';
                        foreach ($cell->getElements() as $cellEl) {
                            $cellText .= $collectText($cellEl) . ' ';
                        }
                        $cells[] = trim(preg_replace('/\s+/u', ' ', $cellText));
                    }
                    $rows[] = $cells;
                }
                $blocks[] = ['type' => 'table', 'rows' => $rows];
            } else {
                $text = trim($collectText($element));
                if ($text !== '') {
                    $blocks[] = ['type' => 'text', 'content' => $text];
                }
            }
        }
    }

    return $blocks;
}

/** So khớp không phân biệt hoa/thường, bỏ khoảng trắng thừa. */
function exam_word_norm($s) {
    $s = preg_replace('/\s+/u', ' ', (string)$s);
    return trim($s);
}

/** Tìm số mã đề ("ĐỀ 1", "ĐỀ 2"...) trong 1 đoạn text, null nếu không có. */
function exam_word_detect_de_number($text) {
    if (preg_match('/ĐỀ\s*(\d+)/iu', (string)$text, $m)) {
        return (int)$m[1];
    }
    return null;
}

/** Quét 1 block (text hoặc table) để tìm số mã đề, dùng để nhận diện ranh giới sang mã đề khác. */
function exam_word_block_de_number(array $block) {
    if ($block['type'] === 'text') {
        return exam_word_detect_de_number($block['content']);
    }
    foreach ($block['rows'] as $row) {
        foreach ($row as $cell) {
            $de = exam_word_detect_de_number($cell);
            if ($de !== null) return $de;
        }
    }
    return null;
}

/**
 * Nhận diện dòng "------------------------ HẾT ------------------------"
 * (chỉ gồm dấu gạch/khoảng trắng + đúng chữ "HẾT"), KHÔNG dùng substring vì
 * nhiều từ tiếng Anh/Việt chứa "HET" như chữ con ("PhET Interactive
 * Simulations") sẽ bị nhận lầm là mốc kết thúc đề.
 */
function exam_word_is_het_marker($text) {
    $stripped = mb_strtoupper(preg_replace('/[^\p{L}]/u', '', (string)$text), 'UTF-8');
    return $stripped === 'HET' || $stripped === 'HẾT';
}

/**
 * Chuẩn hóa chuỗi tiêu đề để so khớp: viết hoa + gộp khoảng trắng (exam_word_norm)
 * + bỏ khoảng trắng quanh dấu "/" và "-" (nhiều đề gõ "ĐÚNG / SAI" thay vì
 * "ĐÚNG/SAI", "ĐÚNG - SAI" thay vì "ĐÚNG-SAI").
 */
function exam_word_heading_normalize($s) {
    $s = mb_strtoupper(exam_word_norm($s), 'UTF-8');
    $s = preg_replace('/\s*\/\s*/u', '/', $s);
    $s = preg_replace('/\s*-\s*/u', '-', $s);
    return $s;
}

function exam_word_is_heading($text, array $needles) {
    $norm = exam_word_heading_normalize($text);
    foreach ($needles as $needle) {
        if (mb_strpos($norm, exam_word_heading_normalize($needle)) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Tách 1 đoạn text chứa nhiều phương án "A. ...B. ...C. ...D. ..." (có thể
 * cách nhau bằng xuống dòng, nhiều khoảng trắng, hoặc dính liền) thành mảng
 * options theo thứ tự A,B,C,D (bỏ nhãn "A." / "A)" ở đầu mỗi phần).
 */
function exam_word_split_options($text) {
    $text = exam_word_norm($text);
    $parts = preg_split('/(?=[A-D][.)]\s)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $options = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') continue;
        $part = preg_replace('/^[A-D][.)]\s*/u', '', $part);
        $part = trim($part);
        if ($part !== '') $options[] = $part;
    }
    return $options;
}

/**
 * Cắt file "đề kiểm tra" thành 3 phần theo mốc tiêu đề chuẩn, chỉ lấy mã đề
 * đầu tiên nếu file có nhiều mã đề (phân tách bằng dòng "HẾT").
 *
 * Trả về:
 * [
 *   'mcq'   => [ ['num'=>1, 'question'=>'...', 'options'=>['...','...']], ... ],
 *   'tf'    => [ ['num'=>1, 'question'=>'...', 'items'=>[['label'=>'a','statement'=>'...'], ...]], ... ],
 *   'essay' => [ ['num'=>1, 'points'=>1.0, 'question'=>'...', 'items'=>[['label'=>'a','text'=>'...'], ...]], ... ],
 *   'multi_de_detected' => bool
 * ]
 */
function exam_word_parse_exam_doc(array $blocks) {
    $SECTION_NONE = 0;
    $SECTION_MCQ = 1;
    $SECTION_TF = 2;
    $SECTION_ESSAY = 3;

    $section = $SECTION_NONE;
    $mcq = [];
    $tf = [];
    $essay = [];
    $multiDeDetected = false;
    $stopped = false;
    $targetDe = null;
    $pendingTfQuestion = null; // ['num'=>, 'question'=>] đang chờ bảng theo sau

    foreach ($blocks as $block) {
        if ($stopped) break;

        $deNum = exam_word_block_de_number($block);
        if ($deNum !== null) {
            if ($targetDe === null) {
                $targetDe = $deNum;
            } elseif ($deNum !== $targetDe) {
                $multiDeDetected = true;
                $stopped = true;
                break;
            }
        }

        if ($block['type'] === 'text') {
            $text = $block['content'];

            if (exam_word_is_het_marker($text)) {
                if ($section !== $SECTION_NONE || !empty($mcq) || !empty($tf) || !empty($essay)) {
                    // Dòng "HẾT" đánh dấu kết thúc 1 mã đề - file có thể còn mã đề khác theo sau.
                    $multiDeDetected = true;
                    $stopped = true;
                    break;
                }
                continue;
            }
            if (exam_word_is_heading($text, ['B. TRẮC NGHIỆM ĐÚNG', 'B. TRẮC NGHIỆM ĐUNG', 'TRẮC NGHIỆM ĐÚNG/SAI', 'TRẮC NGHIỆM ĐÚNG - SAI'])) {
                $section = $SECTION_TF;
                $pendingTfQuestion = null;
                continue;
            }
            if (exam_word_is_heading($text, ['A. TRẮC NGHIỆM NHIỀU', 'TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN', 'TRẮC NGHIỆM NHIỀU LỰA CHỌN'])) {
                $section = $SECTION_MCQ;
                continue;
            }
            if (exam_word_is_heading($text, ['PHẦN II', 'PHAN II', 'TỰ LUẬN'])) {
                $section = $SECTION_ESSAY;
                $pendingTfQuestion = null;
                continue;
            }
            if (exam_word_is_heading($text, ['PHẦN I', 'PHAN I'])) {
                // Tiêu đề tổng của phần trắc nghiệm, chưa biết là A hay B, bỏ qua
                continue;
            }

            if ($section === $SECTION_MCQ) {
                if (preg_match('/^Câu\s*(\d+)\s*[:.]\s*(.*)$/u', $text, $m)) {
                    $rest = $m[2];
                    // Câu hỏi có thể nằm trên dòng này, các option trên (các) dòng khác -> ghép khi gặp option
                    $firstOptPos = preg_match('/[A-D][.)]\s/u', $rest, $mm, PREG_OFFSET_CAPTURE) ? $mm[0][1] : null;
                    if ($firstOptPos !== null) {
                        $questionText = trim(substr($rest, 0, $firstOptPos));
                        $optionsText = substr($rest, $firstOptPos);
                        $mcq[] = ['num' => (int)$m[1], 'question' => $questionText, 'options' => exam_word_split_options($optionsText)];
                    } else {
                        $mcq[] = ['num' => (int)$m[1], 'question' => trim($rest), 'options' => []];
                    }
                } elseif (!empty($mcq) && preg_match('/^[A-D][.)]\s/u', $text)) {
                    // Các phương án nằm ở đoạn text riêng, nối vào câu gần nhất
                    $last = &$mcq[count($mcq) - 1];
                    $opts = exam_word_split_options($text);
                    $last['options'] = array_merge($last['options'], $opts);
                    unset($last);
                }
            } elseif ($section === $SECTION_TF) {
                if (preg_match('/^Câu\s*(\d+)\s*[:.]\s*(.*)$/u', $text, $m)) {
                    $pendingTfQuestion = ['num' => (int)$m[1], 'question' => trim($m[2])];
                }
            } elseif ($section === $SECTION_ESSAY) {
                if (preg_match('/^Câu\s*(\d+)\s*\(([\d.,]+)\s*điểm\)\s*[:.]\s*(.*)$/u', $text, $m)) {
                    $pointsNum = (float)str_replace(',', '.', $m[2]);
                    $rest = $m[3];
                    $firstItemPos = preg_match('/[a-d]\)\s/u', $rest, $mm, PREG_OFFSET_CAPTURE) ? $mm[0][1] : null;
                    if ($firstItemPos !== null) {
                        $questionText = trim(substr($rest, 0, $firstItemPos));
                        $itemsText = substr($rest, $firstItemPos);
                        $essay[] = ['num' => (int)$m[1], 'points' => $pointsNum, 'question' => $questionText, 'items' => exam_word_split_sub_items($itemsText)];
                    } else {
                        $essay[] = ['num' => (int)$m[1], 'points' => $pointsNum, 'question' => trim($rest), 'items' => []];
                    }
                } elseif (!empty($essay) && preg_match('/^[a-d]\)\s/u', $text)) {
                    $last = &$essay[count($essay) - 1];
                    $items = exam_word_split_sub_items($text);
                    $last['items'] = array_merge($last['items'], $items);
                    unset($last);
                }
            }
        } elseif ($block['type'] === 'table') {
            if ($section === $SECTION_TF && $pendingTfQuestion !== null) {
                $items = exam_word_table_to_tf_items($block['rows']);
                if (!empty($items)) {
                    $tf[] = ['num' => $pendingTfQuestion['num'], 'question' => $pendingTfQuestion['question'], 'items' => $items];
                    $pendingTfQuestion = null;
                }
            }
        }
    }

    return [
        'mcq' => $mcq,
        'tf' => $tf,
        'essay' => $essay,
        'multi_de_detected' => $multiDeDetected,
    ];
}

/** Tách "a) ... b) ... c) ... d) ..." thành mảng [['label'=>'a','text'=>'...'], ...]. */
function exam_word_split_sub_items($text) {
    $text = exam_word_norm($text);
    $parts = preg_split('/(?=[a-d]\)\s)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $items = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if (!preg_match('/^([a-d])\)\s*(.*)$/u', $part, $m)) continue;
        $content = trim($m[2]);
        if ($content === '') continue;
        $items[] = ['label' => $m[1], 'text' => $content];
    }
    return $items;
}

/**
 * Bảng Đúng/Sai trong file đề: hàng đầu là header (Nội dung|Đúng|Sai), các
 * hàng sau có cột đầu dạng "a) ...". Trả về [['label'=>'a','statement'=>'...'], ...].
 */
function exam_word_table_to_tf_items(array $rows) {
    $items = [];
    foreach ($rows as $row) {
        if (empty($row[0])) continue;
        if (!preg_match('/^([a-d])\)\s*(.*)$/u', trim($row[0]), $m)) continue;
        $statement = trim($m[2]);
        if ($statement === '') continue;
        $items[] = ['label' => $m[1], 'statement' => $statement];
    }
    return $items;
}

/**
 * Cắt file "đáp án"/"hướng dẫn chấm" thành map theo số câu:
 *   'mcq'   => [ n => ['answer'=>'B', 'level'=>'NB'|null, 'points'=>float|null] ]
 *   'tf'    => [ n => [ 'a' => ['correct'=>bool, 'level'=>..., 'points'=>...], ... ] ]
 *   'essay' => [ n => [ 'level'=>..., 'items' => [ 'a' => ['points'=>.., 'model_answer'=>'...'], ... ] ] ]
 */
function exam_word_parse_answer_doc(array $blocks) {
    $SECTION_NONE = 0;
    $SECTION_MCQ = 1;
    $SECTION_TF = 2;
    $SECTION_ESSAY = 3;
    $section = $SECTION_NONE;

    $mcq = [];
    $tf = [];
    $essay = [];
    $stopped = false;
    $targetDe = null;

    foreach ($blocks as $block) {
        if ($stopped) break;

        $deNum = exam_word_block_de_number($block);
        if ($deNum !== null) {
            if ($targetDe === null) {
                $targetDe = $deNum;
            } elseif ($deNum !== $targetDe) {
                $stopped = true;
                break;
            }
        }

        if ($block['type'] === 'text') {
            $text = $block['content'];
            if (exam_word_is_het_marker($text) && ($mcq || $tf || $essay)) {
                $stopped = true;
                break;
            }
            if (exam_word_is_heading($text, ['TRẮC NGHIỆM ĐÚNG/SAI', 'TRẮC NGHIỆM ĐÚNG - SAI', 'TRẮC NGHIỆM ĐÚNG/ SAI'])) {
                $section = $SECTION_TF;
                continue;
            }
            if (exam_word_is_heading($text, ['TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN', 'TRẮC NGHIỆM NHIỀU LỰA CHỌN'])) {
                $section = $SECTION_MCQ;
                continue;
            }
            if (exam_word_is_heading($text, ['TỰ LUẬN'])) {
                $section = $SECTION_ESSAY;
                continue;
            }

            if ($section === $SECTION_ESSAY) {
                // "Câu 1 (1,0 điểm) – Mức độ: ..." hoặc "Câu 1a: ... (0,25 điểm)"
                if (preg_match('/^Câu\s*(\d+)\b(?!\s*[a-d]\s*[:.)])/u', $text, $m)) {
                    // "Câu 1 (1,0 điểm) – Mức độ: ..." (file hướng dẫn chấm) hoặc chỉ "Câu 1:" (file đáp án trần)
                    $num = (int)$m[1];
                    if (!isset($essay[$num])) $essay[$num] = ['level' => null, 'items' => []];
                    if (preg_match('/Mức\s*độ\s*[:\-–]?\s*([^;,.]+)/iu', $text, $lm)) {
                        $essay[$num]['level'] = exam_word_guess_level($lm[1]);
                    }
                } elseif (preg_match('/^Câu\s*(\d+)\s*([a-d])\s*[:.)]\s*(.*)$/u', $text, $m)) {
                    $num = (int)$m[1];
                    $label = $m[2];
                    $body = $m[3];
                    $points = null;
                    if (preg_match('/\(([\d.,]+)\s*điểm\)/u', $body, $pm)) {
                        $points = (float)str_replace(',', '.', $pm[1]);
                        $body = trim(str_replace($pm[0], '', $body));
                    }
                    if (!isset($essay[$num])) $essay[$num] = ['level' => null, 'items' => []];
                    $essay[$num]['items'][$label] = ['points' => $points, 'model_answer' => trim($body)];
                } elseif (!empty($essay) && preg_match('/^([a-d])\)\s*(.*)$/u', $text, $m)) {
                    $num = max(array_keys($essay));
                    $label = $m[1];
                    $body = $m[2];
                    $points = null;
                    if (preg_match('/\(([\d.,]+)\s*điểm\)/u', $body, $pm)) {
                        $points = (float)str_replace(',', '.', $pm[1]);
                        $body = trim(str_replace($pm[0], '', $body));
                    }
                    $essay[$num]['items'][$label] = ['points' => $points, 'model_answer' => trim($body)];
                }
            }
        } elseif ($block['type'] === 'table') {
            $rows = $block['rows'];
            if (count($rows) < 2) continue;
            $header = array_map(function ($c) { return mb_strtoupper(exam_word_norm($c), 'UTF-8'); }, $rows[0]);

            $colIdx = function ($needles) use ($header) {
                foreach ($header as $i => $h) {
                    foreach ($needles as $needle) {
                        if (mb_strpos($h, mb_strtoupper($needle, 'UTF-8')) !== false) return $i;
                    }
                }
                return null;
            };

            if ($section === $SECTION_MCQ) {
                // Định dạng "ngang": mỗi cột header là 1 câu (C1 / CÂU 1 / Câu1...),
                // dòng dữ liệu ngay dưới header là đáp án tương ứng từng cột.
                $pivotCols = [];
                foreach ($header as $i => $h) {
                    if (preg_match('/^C(?:ÂU)?\s*(\d+)$/u', $h, $m)) {
                        $pivotCols[$i] = (int)$m[1];
                    }
                }
                if (count($pivotCols) >= 2 && isset($rows[1])) {
                    foreach ($pivotCols as $i => $num) {
                        $answer = trim(preg_replace('/[^A-Da-d]/u', '', $rows[1][$i] ?? ''));
                        if ($answer === '') continue;
                        $mcq[$num] = ['answer' => mb_strtoupper($answer, 'UTF-8'), 'level' => null, 'points' => null];
                    }
                    continue;
                }

                // Định dạng "dọc": cột Câu / Đáp án / Mức độ / Điểm, mỗi dòng 1 câu.
                $cauI = $colIdx(['CÂU']);
                $dapAnI = $colIdx(['ĐÁP ÁN']);
                $mucDoI = $colIdx(['MỨC ĐỘ']);
                $diemI = $colIdx(['ĐIỂM']);
                if ($cauI === null || $dapAnI === null) continue;
                for ($r = 1; $r < count($rows); $r++) {
                    $row = $rows[$r];
                    if (!preg_match('/(\d+)/u', $row[$cauI] ?? '', $m)) continue;
                    $num = (int)$m[1];
                    $answer = trim(preg_replace('/[^A-Da-d]/u', '', $row[$dapAnI] ?? ''));
                    if ($answer === '') continue;
                    $mcq[$num] = [
                        'answer' => mb_strtoupper($answer, 'UTF-8'),
                        'level' => $mucDoI !== null ? exam_word_guess_level($row[$mucDoI] ?? '') : null,
                        'points' => $diemI !== null ? exam_word_parse_points($row[$diemI] ?? '') : null,
                    ];
                }
            } elseif ($section === $SECTION_TF) {
                // Định dạng "ngang": header có các cột "Ý a)".."Ý d)", mỗi dòng dưới
                // là 1 câu (cột đầu "Câu N"), các cột còn lại là Đúng/Sai cho từng ý.
                $yCols = [];
                foreach ($header as $i => $h) {
                    if (preg_match('/^Ý\s*([A-D])\b/u', $h, $m)) {
                        $yCols[$i] = mb_strtolower($m[1], 'UTF-8');
                    }
                }
                if (count($yCols) >= 2) {
                    for ($r = 1; $r < count($rows); $r++) {
                        $row = $rows[$r];
                        if (!preg_match('/(\d+)/u', $row[0] ?? '', $m)) continue;
                        $num = (int)$m[1];
                        if (!isset($tf[$num])) $tf[$num] = [];
                        foreach ($yCols as $i => $label) {
                            $ans = exam_word_norm($row[$i] ?? '');
                            if ($ans === '') continue;
                            $isTrue = (mb_stripos($ans, 'Đúng') !== false) || (mb_stripos($ans, 'Dung') !== false) || mb_strtoupper($ans, 'UTF-8') === 'Đ';
                            $tf[$num][$label] = ['correct' => $isTrue, 'level' => null, 'points' => null];
                        }
                    }
                    continue;
                }

                // Định dạng "dọc": cột Câu / Ý / Đáp án / Mức độ / Điểm, mỗi dòng 1 ý.
                $cauI = $colIdx(['CÂU']);
                $yI = $colIdx(['Ý']);
                $dapAnI = $colIdx(['ĐÁP ÁN']);
                $mucDoI = $colIdx(['MỨC ĐỘ']);
                $diemI = $colIdx(['ĐIỂM']);
                if ($yI === null || $dapAnI === null) continue;
                $currentNum = null;
                for ($r = 1; $r < count($rows); $r++) {
                    $row = $rows[$r];
                    if ($cauI !== null && preg_match('/(\d+)/u', $row[$cauI] ?? '', $m)) {
                        $currentNum = (int)$m[1];
                    }
                    if ($currentNum === null) continue;
                    $label = trim(mb_strtolower(preg_replace('/[^A-Za-d]/u', '', $row[$yI] ?? ''), 'UTF-8'));
                    if ($label === '') continue;
                    $ans = exam_word_norm($row[$dapAnI] ?? '');
                    $isTrue = (mb_stripos($ans, 'Đúng') !== false) || (mb_stripos($ans, 'Dung') !== false) || mb_strtoupper($ans, 'UTF-8') === 'Đ';
                    if (!isset($tf[$currentNum])) $tf[$currentNum] = [];
                    $tf[$currentNum][$label] = [
                        'correct' => $isTrue,
                        'level' => $mucDoI !== null ? exam_word_guess_level($row[$mucDoI] ?? '') : null,
                        'points' => $diemI !== null ? exam_word_parse_points($row[$diemI] ?? '') : null,
                    ];
                }
            }
        }
    }

    return ['mcq' => $mcq, 'tf' => $tf, 'essay' => $essay];
}

function exam_word_parse_points($s) {
    $s = trim(str_replace(',', '.', (string)$s));
    return is_numeric($s) ? (float)$s : null;
}

function exam_word_guess_level($s) {
    $s = mb_strtoupper(exam_word_norm($s), 'UTF-8');
    if ($s === '') return null;
    if (mb_strpos($s, 'BIẾT') !== false || mb_strpos($s, 'NHẬN BIẾT') !== false || $s === 'NB') return 'NB';
    if (mb_strpos($s, 'HIỂU') !== false || mb_strpos($s, 'THÔNG HIỂU') !== false || $s === 'TH') return 'TH';
    if (mb_strpos($s, 'VẬN DỤNG CAO') !== false || $s === 'VDC') return 'VDC';
    if (mb_strpos($s, 'VẬN DỤNG') !== false || $s === 'VD') return 'VD';
    return null;
}

/**
 * Ghép đề + đáp án thành danh sách câu hỏi đúng schema hệ thống (giống
 * qb_build_question_payload trong question_bank_handlers.php).
 * $assessmentKind: 'regular' (Thường xuyên - bỏ Tự luận) | 'midterm' | 'final'.
 *
 * Trả về ['questions' => [...], 'warnings' => [...]].
 * Mỗi question có thêm field tạm '_needs_review' (bool) để UI preview tô đỏ/bỏ check,
 * field này KHÔNG được lưu vào ngân hàng câu hỏi (phải unset trước khi lưu).
 */
function exam_word_merge(array $examParsed, array $answerParsed, $assessmentKind) {
    $questions = [];
    $warnings = [];

    foreach ($examParsed['mcq'] as $q) {
        $num = $q['num'];
        $ans = $answerParsed['mcq'][$num] ?? null;
        $needsReview = false;
        $correctIdx = null;
        if ($ans && !empty($ans['answer'])) {
            $letterIdx = strpos('ABCD', mb_strtoupper($ans['answer'][0], 'UTF-8'));
            if ($letterIdx !== false && isset($q['options'][$letterIdx])) {
                $correctIdx = $letterIdx;
            }
        }
        if ($correctIdx === null) {
            $needsReview = true;
            $warnings[] = "Câu {$num} (trắc nghiệm): không xác định được đáp án đúng, cần kiểm tra lại.";
            $correctIdx = 0;
        }
        if (count($q['options']) < 2) {
            $needsReview = true;
            $warnings[] = "Câu {$num} (trắc nghiệm): không đọc đủ các phương án, cần kiểm tra lại.";
        }
        $questions[] = [
            'question' => $q['question'],
            'type' => 'single',
            'level' => ($ans['level'] ?? null) ?: 'NB',
            'image' => '',
            'options' => $q['options'],
            'correct' => $correctIdx,
            '_needs_review' => $needsReview,
            '_source_label' => "Câu {$num}",
        ];
    }

    foreach ($examParsed['tf'] as $q) {
        $num = $q['num'];
        $ansMap = $answerParsed['tf'][$num] ?? [];
        $needsReview = empty($q['items']);
        $items = [];
        $level = null;
        foreach ($q['items'] as $it) {
            $a = $ansMap[$it['label']] ?? null;
            if ($a === null) $needsReview = true;
            $items[] = [
                'label' => $it['label'],
                'statement' => $it['statement'],
                'correct' => (bool)($a['correct'] ?? false),
            ];
            if ($a && !empty($a['level'])) $level = $a['level'];
        }
        if (count($items) < 2) {
            $needsReview = true;
        }
        if ($needsReview) {
            $warnings[] = "Câu {$num} (đúng/sai): thiếu đáp án hoặc thiếu ý, cần kiểm tra lại.";
        }
        $questions[] = [
            'question' => $q['question'],
            'type' => 'true_false_multiple',
            'level' => $level ?: 'TH',
            'image' => '',
            'items' => $items,
            '_needs_review' => $needsReview,
            '_source_label' => "Câu {$num}",
        ];
    }

    if ($assessmentKind === 'regular') {
        // Thường xuyên: không dùng Tự luận, bỏ qua toàn bộ phần này.
        if (!empty($examParsed['essay'])) {
            $warnings[] = 'Đề "Thường xuyên" chỉ gồm Trắc nghiệm và Đúng/Sai - đã bỏ qua phần Tự luận trong file.';
        }
    } else {
        foreach ($examParsed['essay'] as $q) {
            $num = $q['num'];
            $ansInfo = $answerParsed['essay'][$num] ?? null;
            $needsReview = empty($q['items']);
            $subQuestions = [];
            $modelAnswerParts = [];
            foreach ($q['items'] as $it) {
                $a = $ansInfo['items'][$it['label']] ?? null;
                $points = $a['points'] ?? null;
                if ($points === null) {
                    $points = count($q['items']) > 0 ? round($q['points'] / count($q['items']), 2) : 0;
                }
                $subQuestions[] = ['label' => $it['label'], 'text' => $it['text'], 'points' => $points];
                if ($a && !empty($a['model_answer'])) {
                    $modelAnswerParts[] = "{$it['label']}) " . $a['model_answer'];
                } else {
                    $needsReview = true;
                }
            }
            if ($needsReview) {
                $warnings[] = "Câu {$num} (tự luận): thiếu đáp án mẫu cho một số ý, cần kiểm tra lại.";
            }
            $questions[] = [
                'question' => $q['question'],
                'type' => 'essay',
                'level' => ($ansInfo['level'] ?? null) ?: 'TH',
                'points' => $q['points'],
                'sub_questions' => $subQuestions,
                'suggested_answer' => implode("\n", $modelAnswerParts),
                '_needs_review' => $needsReview,
                '_source_label' => "Câu {$num}",
            ];
        }
    }

    return ['questions' => $questions, 'warnings' => $warnings];
}
