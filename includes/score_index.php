<?php
/**
 * CVD LMS - Score index (shared/scores/student_score.json) rebuild on demand
 *
 * Phương án (a) trong DATA_STORAGE_DECISION.md:
 *  - File cá nhân shared/scores/{code}.json là NGUỒN CHUẨN (mỗi HS một file,
 *    không tranh chấp khi cả lớp nộp bài cùng lúc).
 *  - student_score.json chỉ là chỉ mục tạm, được rebuild lại từ file cá nhân
 *    mỗi khi giáo viên mở trang "Quản lý kết quả" để đảm bảo khớp 100%.
 *  - saveExamResult vẫn cập nhật nhanh chỉ mục (dưới flock) để học sinh xem
 *    kết quả không phải chờ; rebuild chỉ hòa giải khi có chênh lệch.
 */

require_once __DIR__ . '/json_db_helper.php';

function score_index_scores_dir(): string {
    return __DIR__ . '/../shared/scores/';
}

function score_index_file(): string {
    return score_index_scores_dir() . 'student_score.json';
}

/**
 * Kiểm tra nhanh (rẻ) xem chỉ mục đã cũ hơn file cá nhân nào chưa.
 * @return bool true nên rebuild
 */
function score_index_is_stale(): bool {
    $scoresDir = score_index_scores_dir();
    $indexFile = score_index_file();
    if (!file_exists($indexFile)) {
        return true;
    }
    $indexMtime = @filemtime($indexFile);
    foreach (glob($scoresDir . '*.json') ?: [] as $file) {
        $name = basename($file);
        if (score_index_is_aux_file($name)) {
            continue;
        }
        if (@filemtime($file) > $indexMtime) {
            return true;
        }
    }
    return false;
}

/**
 * File không phải dữ liệu học sinh (chỉ mục, backup, old, ...).
 */
function score_index_is_aux_file(string $name): bool {
    return $name === 'student_score.json'
        || strpos($name, 'student_score_backup') === 0
        || strpos($name, '.old') !== false
        || strpos($name, '_backup') !== false;
}

/**
 * Rebuild student_score.json từ các file cá nhân.
 *
 * Ghi đè toàn bộ chỉ mục bên trong khóa độc quyền (cùng .lock với
 * saveExamResult) nên không cạnh tranh với luồng học sinh nộp bài.
 * Ghi chú của giáo viên được giữ lại: ưu tiên ghi chú đã có ở chỉ mục cũ
 * (bản ghi cũ chỉ lưu ở đó), nếu không thì lấy từ file cá nhân.
 *
 * @param bool $force Bỏ qua kiểm tra "chỉ mục đã mới" và luôn rebuild
 * @return array ['ok' => bool, 'rebuilt' => bool, 'entries' => int, 'students' => int, 'error' => ?string]
 */
function rebuild_student_score_index(bool $force = false): array {
    $scoresDir = score_index_scores_dir();
    $indexFile = score_index_file();

    if (!is_dir($scoresDir)) {
        return ['ok' => false, 'rebuilt' => false, 'entries' => 0, 'students' => 0, 'error' => "Không tìm thấy thư mục dữ liệu: $scoresDir"];
    }

    if (!$force && !score_index_is_stale()) {
        return ['ok' => true, 'rebuilt' => false, 'entries' => count(get_json_data($indexFile, [])), 'students' => 0, 'error' => null];
    }

    $result = update_json_data($indexFile, function ($current) use ($scoresDir) {
        $current = is_array($current) ? $current : [];

        // Ghi chú giáo viên đã lưu ở chỉ mục cũ phải được giữ qua rebuild
        $oldNotes = [];
        foreach ($current as $entry) {
            $notes = $entry['notes'] ?? '';
            if ($notes === '') {
                continue;
            }
            $key = ($entry['student_id'] ?? '') . '|' . ($entry['exam_id'] ?? '') . '|' . ($entry['subject_id'] ?? '');
            if (!isset($oldNotes[$key])) {
                $oldNotes[$key] = $notes;
            }
        }

        $byKey = [];
        $students = [];

        foreach (glob($scoresDir . '*.json') ?: [] as $file) {
            $name = basename($file);
            if (score_index_is_aux_file($name)) {
                continue;
            }

            $studentCode = pathinfo($name, PATHINFO_FILENAME);
            $records = get_json_data($file, []);
            if (!is_array($records)) {
                continue;
            }
            // Một số file cũ lưu 1 bản ghi dạng object thay vì mảng các bản ghi
            if (isset($records['id']) || isset($records['source_exam_id'])
                || isset($records['exam_id']) || isset($records['student_code'])) {
                $records = [$records];
            }

            // Loại bỏ trùng bản ghi theo id (giữ bản mới nhất)
            $seen = [];
            foreach ($records as $record) {
                if (!is_array($record)) {
                    continue;
                }
                $rid = $record['id'] ?? '';
                if ($rid === '') {
                    $seen[] = $record;
                    continue;
                }
                $seen[$rid] = $record;
            }

            foreach ($seen as $record) {
                $examId = $record['source_exam_id'] ?? ($record['exam_id'] ?? '');
                if ($examId === '') {
                    continue;
                }
                $subjectId = $record['subject_id'] ?? 0;
                $key = $studentCode . '|' . $examId . '|' . $subjectId;

                if (!isset($byKey[$key])) {
                    $byKey[$key] = [
                        'student_id'  => $studentCode,
                        'exam_id'     => $examId,
                        'result_id'   => $record['id'] ?? '',
                        'subject_id'  => $subjectId,
                        'test_name'   => $record['test_name'] ?? '',
                        'attempts'    => 0,
                        'timestamp'   => $record['timestamp'] ?? '',
                        'score'       => $record['score'] ?? 0,
                        'notes'       => '',
                        '_latest_ts'  => $record['timestamp'] ?? '',
                    ];
                } else {
                    // Giữ lại bản ghi "mới nhất" (theo timestamp) cho điểm/result_id
                    $ts = $record['timestamp'] ?? '';
                    if ($ts > $byKey[$key]['_latest_ts']) {
                        $byKey[$key]['result_id']  = $record['id'] ?? $byKey[$key]['result_id'];
                        $byKey[$key]['test_name']   = $record['test_name'] ?? $byKey[$key]['test_name'];
                        $byKey[$key]['timestamp']   = $ts;
                        $byKey[$key]['score']       = $record['score'] ?? $byKey[$key]['score'];
                        $byKey[$key]['_latest_ts']  = $ts;
                    }
                }

                $byKey[$key]['attempts']++;

                // Ghi chú: ưu tiên chỉ mục cũ, nếu không có thì lấy từ file cá nhân
                if (isset($oldNotes[$key]) && $oldNotes[$key] !== '') {
                    $byKey[$key]['notes'] = $oldNotes[$key];
                } elseif ($byKey[$key]['notes'] === '' && isset($record['notes']) && $record['notes'] !== '') {
                    $byKey[$key]['notes'] = $record['notes'];
                }

                $students[$studentCode] = true;
            }
        }

        $entries = [];
        foreach ($byKey as $entry) {
            unset($entry['_latest_ts']);
            $entries[] = $entry;
        }
        usort($entries, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        return $entries;
    }, []);

    if ($result === false) {
        return ['ok' => false, 'rebuilt' => false, 'entries' => 0, 'students' => 0, 'error' => 'Không ghi được chỉ mục student_score.json'];
    }

    $entries = get_json_data($indexFile, []);
    $students = [];
    foreach ($entries as $e) {
        if (isset($e['student_id'])) {
            $students[$e['student_id']] = true;
        }
    }

    return ['ok' => true, 'rebuilt' => true, 'entries' => count($entries), 'students' => count($students), 'error' => null];
}
