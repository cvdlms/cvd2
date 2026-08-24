<?php
/**
 * Shared exam helpers for student/exam.php and student/api/submit_exam.php.
 * Ensures the deterministic question order matches on both ends, prevents
 * path traversal, and enables server-side grading.
 */

/**
 * Vietnamese -> ASCII transliteration map. Windows iconv TRANSLIT fails on
 * "Đ/đ" (returns false for the whole string) and leaves caret artifacts on
 * other accented letters, so we map explicitly before iconv.
 */
function exam_slug_transliterate($string) {
    $map = [
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd',
        'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
        'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
        'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
        'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
        'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
        'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
        'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
        'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
        'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        'Đ' => 'D',
    ];
    $string = preg_replace('/\p{Mn}/u', '', (string)$string);
    $string = strtr($string, $map);
    return $string;
}

function exam_create_slug($string) {
    $string = exam_slug_transliterate($string);
    $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    if ($string === false || $string === '') {
        $string = preg_replace('/[^\x20-\x7E]/', '-', exam_slug_transliterate($string));
    }
    $string = preg_replace('/[^a-zA-Z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return strtolower($string);
}

/**
 * Resolve the teacher exam file for a given exam id (legacy "subject_slug" or
 * canonical test_id). Returns null if not found / unsafe. The resolved file is
 * always verified to live inside teacher/exams/.
 *
 * @return array{file:string, subject_id:int, grade:string}|null
 */
function exam_resolve_file($examId, $grade) {
    $baseExams = realpath(__DIR__ . '/../teacher/exams/');
    if (!$baseExams) return null;
    $examId = str_replace('\\', '/', (string)$examId);

    // Legacy format: subject_id_slug
    if (preg_match('/^(\d+)_(.+)$/', $examId, $m)) {
        $subjectId = (int)$m[1];
        $slug = exam_create_slug($m[2]);
        $examDir = $baseExams . DIRECTORY_SEPARATOR . $grade . DIRECTORY_SEPARATOR . 'subject_' . $subjectId . DIRECTORY_SEPARATOR;

        $candidate = realpath($examDir . $slug . '.json');
        if ($candidate && strpos($candidate, $baseExams . DIRECTORY_SEPARATOR) === 0) {
            return ['file' => $candidate, 'subject_id' => $subjectId, 'grade' => $grade];
        }

        // Fallback: match by slug of test_name
        $files = @glob($examDir . '*.json') ?: [];
        foreach ($files as $f) {
            $data = json_decode(file_get_contents($f), true);
            if ($data && exam_create_slug($data['test_name'] ?? '') === $slug) {
                return ['file' => $f, 'subject_id' => $subjectId, 'grade' => $grade];
            }
        }
        return null;
    }

    // Canonical test_id format: search student's grade first, then all grades
    $searchDirs = [];
    if (is_dir($baseExams . DIRECTORY_SEPARATOR . $grade)) {
        $searchDirs[] = $baseExams . DIRECTORY_SEPARATOR . $grade;
    }
    foreach (@glob($baseExams . DIRECTORY_SEPARATOR . 'khoi*', GLOB_ONLYDIR) ?: [] as $d) {
        $searchDirs[] = $d;
    }

    foreach (array_unique($searchDirs) as $gradeDir) {
        $subjectDirs = @glob($gradeDir . DIRECTORY_SEPARATOR . 'subject_*', GLOB_ONLYDIR) ?: [];
        foreach ($subjectDirs as $subjectDir) {
            if (!preg_match('/subject_(\d+)/', $subjectDir, $m2)) continue;
            $sid = (int)$m2[1];
            foreach (@glob($subjectDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
                $data = json_decode(file_get_contents($f), true);
                if ($data && ($data['test_id'] ?? '') === $examId) {
                    return ['file' => $f, 'subject_id' => $sid, 'grade' => basename($gradeDir)];
                }
            }
        }
    }
    return null;
}

/**
 * Deterministic Fisher-Yates shuffle so exam.php and submit_exam.php agree
 * on the exact question order presented to the student.
 */
function exam_shuffle_questions($questions, $studentCode, $canonicalTestId) {
    if (!is_array($questions) || empty($questions)) return [];
    $seed = crc32((string)$studentCode . '_' . (string)$canonicalTestId);
    mt_srand($seed);
    $count = count($questions);
    for ($i = $count - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $tmp = $questions[$i];
        $questions[$i] = $questions[$j];
        $questions[$j] = $tmp;
    }
    mt_srand();
    return $questions;
}

/**
 * Exam categories: "regular" (kiểm tra thường xuyên) chỉ cho phép trắc nghiệm.
 * Đúng/Sai nhiều ý và Tự luận chỉ xuất hiện trong giữa kỳ / cuối kỳ.
 */
function exam_category_label($category) {
    switch ($category) {
        case 'midterm': return 'Giữa kỳ';
        case 'final': return 'Cuối kỳ';
        default: return 'Thường xuyên';
    }
}

function exam_allows_new_types($category) {
    return in_array($category, ['midterm', 'final'], true);
}

/**
 * Strip correct answers before sending questions to the browser.
 * Type-aware: Đúng/Sai gửi items nhưng bỏ cờ correct; Tự luận KHÔNG BAO GIỜ
 * gửi suggested_answer xuống client (chống gian lận).
 */
function exam_strip_answers($questions) {
    $out = [];
    foreach ($questions as $q) {
        if (!is_array($q)) continue;
        $type = $q['type'] ?? 'single';
        $row = [
            'question' => $q['question'] ?? '',
            'type' => $type,
            'level' => $q['level'] ?? '',
            'image' => $q['image'] ?? ''
        ];
        if ($type === 'true_false_multiple') {
            $items = [];
            foreach (($q['items'] ?? []) as $it) {
                if (!is_array($it)) continue;
                $items[] = [
                    'label' => $it['label'] ?? '',
                    'statement' => $it['statement'] ?? ''
                ];
            }
            $row['items'] = $items;
        } elseif ($type === 'essay') {
            $row['points'] = (float)($q['points'] ?? 0);
        } else {
            $row['options'] = $q['options'] ?? [];
        }
        $out[] = $row;
    }
    return $out;
}

/**
 * Check whether the student already has a completed attempt recorded for this
 * exam (canonical test_id, matched by subject). Checks official scores plus
 * the practice-results store so practice retakes are also enforced.
 */
function exam_has_completed($studentCode, $canonicalTestId, $subjectId) {
    $consolidatedFile = __DIR__ . '/../shared/scores/student_score.json';
    if (file_exists($consolidatedFile)) {
        $data = json_decode(file_get_contents($consolidatedFile), true) ?: [];
        foreach ($data as $entry) {
            if (($entry['student_id'] ?? '') !== $studentCode) continue;
            $storedId = $entry['exam_id'] ?? '';
            if ($canonicalTestId && $storedId === $canonicalTestId) {
                if (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId) {
                    return true;
                }
            }
        }
    }
    $perStudentFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '.json';
    if (file_exists($perStudentFile)) {
        $data = json_decode(file_get_contents($perStudentFile), true) ?: [];
        foreach ($data as $entry) {
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            if ($canonicalTestId && $storedId === $canonicalTestId) {
                if (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId) {
                    return true;
                }
            }
        }
    }
    $practiceFile = __DIR__ . '/../data/practice_results/practice_results.json';
    if (file_exists($practiceFile)) {
        $data = json_decode(file_get_contents($practiceFile), true) ?: [];
        foreach ($data as $entry) {
            if (($entry['student_code'] ?? '') !== $studentCode) continue;
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            if ($canonicalTestId && $storedId === $canonicalTestId) {
                if (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Return the stored result id for the student's completed attempt of this
 * exam, or null when none exists. Used to redirect to the result page.
 */
function exam_find_result_id($studentCode, $canonicalTestId, $subjectId) {
    $consolidatedFile = __DIR__ . '/../shared/scores/student_score.json';
    if (file_exists($consolidatedFile)) {
        $data = json_decode(file_get_contents($consolidatedFile), true) ?: [];
        foreach ($data as $entry) {
            if (($entry['student_id'] ?? '') !== $studentCode) continue;
            if (($entry['exam_id'] ?? '') === $canonicalTestId
                && (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId)) {
                return $entry['result_id'] ?? $entry['id'] ?? null;
            }
        }
    }
    $perStudentFile = __DIR__ . '/../shared/scores/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $studentCode) . '.json';
    if (file_exists($perStudentFile)) {
        $data = json_decode(file_get_contents($perStudentFile), true) ?: [];
        foreach ($data as $entry) {
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            if ($storedId === $canonicalTestId
                && (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId)) {
                return $entry['id'] ?? null;
            }
        }
    }
    $practiceFile = __DIR__ . '/../data/practice_results/practice_results.json';
    if (file_exists($practiceFile)) {
        $data = json_decode(file_get_contents($practiceFile), true) ?: [];
        foreach ($data as $entry) {
            if (($entry['student_code'] ?? '') !== $studentCode) continue;
            $storedId = $entry['source_exam_id'] ?? ($entry['exam_id'] ?? '');
            if ($storedId === $canonicalTestId
                && (!isset($entry['subject_id']) || (int)$entry['subject_id'] === (int)$subjectId)) {
                return $entry['id'] ?? null;
            }
        }
    }
    return null;
}

/**
 * Tính lại điểm của một bản ghi kết quả SAU KHI giáo viên chấm tự luận.
 * Mỗi câu góp 1 đơn vị: MCQ đúng=1, Đúng/Sai=fraction, Tự luận=awarded/max.
 * Điểm cuối = tổng đơn vị / tổng số câu x 10 (thang 10, làm tròn 1 chữ số).
 *
 * @param array $record bản ghi kết quả (thay đổi tại chỗ: score, pending_essay)
 * @return array{0:float,1:bool} [điểm mới, còn bài chờ chấm không]
 */
function exam_recompute_after_grading(&$record) {
    $qr = $record['question_results'] ?? [];
    $total = is_array($qr) ? count($qr) : 0;
    if ($total === 0) {
        return [(float)($record['score'] ?? 0), false];
    }
    $earned = 0.0;
    $pending = false;
    foreach ($qr as $q) {
        switch ($q['type'] ?? 'single') {
            case 'essay':
                if (!empty($q['needs_grading'])) { $pending = true; break; }
                $max = (float)($q['max_points'] ?? ($q['points'] ?? 0));
                if ($max <= 0) break;
                $awarded = (float)($q['awarded_points'] ?? 0);
                $earned += max(0.0, min($awarded, $max)) / $max;
                break;
            case 'true_false_multiple':
                $earned += isset($q['fraction'])
                    ? (float)$q['fraction']
                    : (!empty($q['is_correct']) ? 1.0 : 0.0);
                break;
            default:
                $earned += !empty($q['is_correct']) ? 1.0 : 0.0;
        }
    }
    $record['score'] = round(min(10.0, $earned / $total * 10.0), 1);
    $record['pending_essay'] = $pending;
    return [$record['score'], $pending];
}

/**
 * Bộ môn có thuộc danh sách giáo viên được phụ trách không.
 * Bản ghi không có subject_id (null/'') luôn hiển thị cho mọi GV.
 */
function exam_subject_allowed($subjectId, array $allowedSubjects) {
    if (!isset($subjectId) || $subjectId === '' || $subjectId === null) return true;
    return in_array((int)$subjectId, array_map('intval', $allowedSubjects), true);
}

/**
 * Quét toàn bộ kho kết quả tìm các bài còn câu TỰ LUẬN chưa chấm.
 * Nguồn: file cá nhân shared/scores/{code}.json + practice_results.json
 * (student_score.json chỉ là bản rút gọn nên không đủ dữ liệu chấm).
 * Gợi ý đáp án lấy từ file đề nếu còn tồn tại (chỉ dành cho UI giáo viên).
 */
function exam_scan_pending_essays(array $allowedSubjects) {
    $pendingList = [];
    $sources = [];

    foreach (@glob(__DIR__ . '/../shared/scores/*.json') ?: [] as $f) {
        if (basename($f) === 'student_score.json') continue;
        $data = json_decode(file_get_contents($f), true);
        if (!is_array($data)) continue;
        // File cá nhân có thể là mảng nhiều bài hoặc 1 object duy nhất
        $records = isset($data[0]) || empty($data) ? $data : [$data];
        foreach ($records as $rec) {
            if (is_array($rec)) $sources[] = ['storage' => 'score', 'rec' => $rec];
        }
    }

    $practiceFile = __DIR__ . '/../data/practice_results/practice_results.json';
    if (file_exists($practiceFile)) {
        $data = json_decode(file_get_contents($practiceFile), true);
        if (is_array($data)) {
            foreach ($data as $rec) {
                if (is_array($rec)) $sources[] = ['storage' => 'practice', 'rec' => $rec];
            }
        }
    }

    foreach ($sources as $src) {
        $rec = $src['rec'];
        if (!exam_subject_allowed($rec['subject_id'] ?? null, $allowedSubjects)) continue;

        $essays = [];
        foreach (($rec['question_results'] ?? []) as $qr) {
            if (($qr['type'] ?? '') !== 'essay' || empty($qr['needs_grading'])) continue;
            $essays[] = [
                'question_index' => (int)($qr['question_index'] ?? 0),
                'question' => $qr['question'] ?? '',
                'image' => $qr['image'] ?? '',
                'points' => (float)($qr['points'] ?? 0),
                'answer' => (string)($qr['user_answer'] ?? ''),
                'suggested' => '' // bổ sung từ file đề bên dưới
            ];
        }
        if (empty($essays)) continue;

        // Làm giàu từ file đề: điểm chuẩn + gợi ý đáp án cho GV
        $sourceExamId = $rec['source_exam_id'] ?? ($rec['exam_id'] ?? '');
        foreach (@glob(__DIR__ . '/../teacher/exams/khoi*', GLOB_ONLYDIR) ?: [] as $gdir) {
            $resolved = exam_resolve_file($sourceExamId, basename($gdir));
            if ($resolved !== null) {
                $examData = json_decode(file_get_contents($resolved['file']), true);
                if (is_array($examData)) {
                    $questions = $examData['questions'] ?? [];
                    foreach ($essays as &$es) {
                        $qi = $es['question_index'];
                        if (isset($questions[$qi]) && ($questions[$qi]['type'] ?? '') === 'essay') {
                            $eq = $questions[$qi];
                            $es['points'] = (float)($eq['points'] ?? $es['points']);
                            $es['suggested'] = (string)($eq['suggested_answer'] ?? '');
                        }
                    }
                    unset($es);
                }
                break;
            }
        }

        $pendingList[] = [
            'storage' => $src['storage'],
            'result_id' => (string)($rec['id'] ?? ''),
            'student_code' => (string)($rec['student_code'] ?? ''),
            'student_name' => (string)($rec['student_name'] ?? ''),
            'class_name' => (string)($rec['class_name'] ?? ''),
            'test_name' => (string)($rec['test_name'] ?? ''),
            'exam_id' => (string)$sourceExamId,
            'subject_id' => $rec['subject_id'] ?? null,
            'attempt' => (int)($rec['attempt'] ?? 1),
            'timestamp' => (string)($rec['timestamp'] ?? ''),
            'auto_score' => (float)($rec['score'] ?? 0),
            'total_questions' => (int)($rec['total_questions'] ?? count($rec['question_results'] ?? [])),
            'essay_count' => count($essays),
            'essays' => $essays
        ];
    }

    usort($pendingList, static function ($a, $b) {
        return strcmp((string)$b['timestamp'], (string)$a['timestamp']);
    });
    return $pendingList;
}

/**
 * Đếm nhanh số bài/câu tự luận đang chờ chấm của giáo viên.
 */
function exam_pending_essay_count(array $allowedSubjects) {
    $list = exam_scan_pending_essays($allowedSubjects);
    $questions = 0;
    foreach ($list as $item) $questions += (int)$item['essay_count'];
    return ['bai' => count($list), 'cau' => $questions];
}
