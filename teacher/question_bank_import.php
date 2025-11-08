<?php
// question_bank_import.php - Import Questions Component
?>
<!-- Import Questions Section -->
<div class="mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">📤 Nhập Câu Hỏi Từ File JSON</h3>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Nhập từ file JSON hoặc thêm thủ công từ Excel</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#excelAddModal">Thêm từ Excel</button>
            </div>
            <?php
            $importMessage = '';
            $importError = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_questions') {
                $grade = $_POST['import_grade'] ?? '';
                $subjectId = (int)($_POST['import_subject_id'] ?? 0);

                if (!in_array($grade, $availableGrades)) {
                    $importError = 'Khối không hợp lệ.';
                } elseif (!in_array($subjectId, $assignedSubjectIds)) {
                    $importError = 'Môn học không hợp lệ hoặc không được phép.';
                } elseif (!isset($_FILES['questions_file']) || $_FILES['questions_file']['error'] !== UPLOAD_ERR_OK) {
                    $importError = 'Vui lòng chọn file JSON hợp lệ để tải lên.';
                } else {
                    $questionsDir = __DIR__ . '/questions/' . $grade . '/';
                    if (!is_dir($questionsDir)) {
                        mkdir($questionsDir, 0755, true);
                    }
                    $fileContent = file_get_contents($_FILES['questions_file']['tmp_name']);
                    $data = json_decode($fileContent, true);
                    if ($data === null) {
                        $importError = 'File JSON không hợp lệ.';
                    } else {
                        if (!is_array($data)) {
                            $importError = 'File JSON phải là mảng các chủ đề/bài học.';
                        } else {
                            $allValid = true;
                            $normalizedData = [];
                            foreach ($data as $topicItem) {
                                if (!isset($topicItem['topic'], $topicItem['lesson'], $topicItem['questions']) || !is_array($topicItem['questions'])) {
                                    $allValid = false;
                                    break;
                                }
                                $valid = true;
                                foreach ($topicItem['questions'] as &$q) {
                                    if (!isset($q['question'], $q['options'], $q['correct'], $q['type'], $q['level'])) {
                                        $valid = false;
                                        break;
                                    }
                                    if ($q['type'] === 'single') {
                                        if (is_array($q['correct']) && count($q['correct']) === 1) {
                                            $q['correct'] = $q['correct'][0];
                                        } elseif (!is_int($q['correct'])) {
                                            $valid = false;
                                            break;
                                        }
                                    } elseif ($q['type'] === 'multiple' && !is_array($q['correct'])) {
                                        $valid = false;
                                        break;
                                    }
                                }
                                unset($q);
                                if (!$valid) {
                                    $allValid = false;
                                    break;
                                }
                                $normalizedData[] = $topicItem;
                            }
                            if (!$allValid) {
                                $importError = 'Định dạng câu hỏi không hợp lệ.';
                            } else {
                                $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                                $existing = [];
                                if (file_exists($subjectQuestionsFile)) {
                                    $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                                }
                                // Merge imported data into existing, avoiding duplicates
                                foreach ($normalizedData as $newTopicItem) {
                                    $topic = $newTopicItem['topic'];
                                    $lesson = $newTopicItem['lesson'];
                                    $newQuestions = $newTopicItem['questions'];
                                    $merged = false;
                                    foreach ($existing as &$existingTopic) {
                                        if ($existingTopic['topic'] === $topic && $existingTopic['lesson'] === $lesson) {
                                            // Merge questions, avoiding duplicates based on question text
                                            foreach ($newQuestions as $newQ) {
                                                $duplicate = false;
                                                foreach ($existingTopic['questions'] as $existingQ) {
                                                    if ($existingQ['question'] === $newQ['question']) {
                                                        $duplicate = true;
                                                        break;
                                                    }
                                                }
                                                if (!$duplicate) {
                                                    $existingTopic['questions'][] = $newQ;
                                                }
                                            }
                                            $merged = true;
                                            break;
                                        }
                                    }
                                    unset($existingTopic);
                                    if (!$merged) {
                                        $existing[] = $newTopicItem;
                                    }
                                }
                                if (file_put_contents($subjectQuestionsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                                    $importMessage = 'Câu hỏi đã được nhập thành công cho môn học.';
                                } else {
                                    $importError = 'Lỗi khi lưu câu hỏi.';
                                }
                            }
                        }
                    }
                }
            }

            // Handle POST request for importing from Excel
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_excel') {
                $grade = $_POST['excel_import_grade'] ?? '';
                $subjectId = (int)($_POST['excel_import_subject_id'] ?? 0);

                if (!in_array($grade, $availableGrades)) {
                    $importError = 'Khối không hợp lệ.';
                } elseif (!in_array($subjectId, $assignedSubjectIds)) {
                    $importError = 'Môn học không hợp lệ hoặc không được phép.';
                } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                    $importError = 'Vui lòng chọn file Excel hợp lệ để tải lên.';
                } else {
                    // Require PhpSpreadsheet (assuming it's installed via Composer)
                    require_once '../vendor/autoload.php';

                    try {
                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $rows = $worksheet->toArray();

                        // Skip header row
                        array_shift($rows);

                        $questionsDir = __DIR__ . '/questions/' . $grade . '/';
                        if (!is_dir($questionsDir)) {
                            mkdir($questionsDir, 0755, true);
                        }

                        $subjectQuestionsFile = $questionsDir . 'subject_' . $subjectId . '.json';
                        $existing = [];
                        if (file_exists($subjectQuestionsFile)) {
                            $existing = json_decode(file_get_contents($subjectQuestionsFile), true) ?: [];
                        }

                        foreach ($rows as $row) {
                            // Expected columns: Topic, Lesson, Question, Option A, Option B, Option C, Option D, Correct (e.g., 1 or 1,3), Type (single/multiple), Level (NB/TH/VD/VDC)
                            if (count($row) < 10) continue; // Skip invalid rows

                            $topic = trim($row[0]);
                            $lesson = trim($row[1]);
                            $question = trim($row[2]);
                            $options = [trim($row[3]), trim($row[4]), trim($row[5]), trim($row[6])];
                            $correctStr = trim($row[7]);
                            $type = trim($row[8]);
                            $level = trim($row[9]);

                            // Validate type and level
                            if (!in_array($type, ['single', 'multiple']) || !in_array($level, ['NB', 'TH', 'VD', 'VDC'])) {
                                continue; // Skip invalid questions
                            }

                            // Parse correct answers
                            if ($type === 'single') {
                                $correct = is_numeric($correctStr) ? (int)$correctStr - 1 : 0; // 1-based to 0-based
                            } else {
                                $correctParts = array_map('intval', explode(',', $correctStr));
                                $correct = array_map(function($c) { return $c - 1; }, $correctParts);
                            }

                            // Find or create topic/lesson
                            $topicIndex = null;
                            foreach ($existing as $idx => $item) {
                                if ($item['topic'] === $topic && $item['lesson'] === $lesson) {
                                    $topicIndex = $idx;
                                    break;
                                }
                            }
                            if ($topicIndex === null) {
                                $existing[] = [
                                    'topic' => $topic,
                                    'lesson' => $lesson,
                                    'questions' => []
                                ];
                                $topicIndex = count($existing) - 1;
                            }

                            // Add question, avoiding duplicates
                            $duplicate = false;
                            foreach ($existing[$topicIndex]['questions'] as $existingQ) {
                                if ($existingQ['question'] === $question) {
                                    $duplicate = true;
                                    break;
                                }
                            }
                            if (!$duplicate) {
                                $existing[$topicIndex]['questions'][] = [
                                    'question' => $question,
                                    'options' => $options,
                                    'correct' => $correct,
                                    'type' => $type,
                                    'level' => $level
                                ];
                            }
                        }

                        if (file_put_contents($subjectQuestionsFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                            $importMessage = 'Câu hỏi đã được nhập từ Excel thành công.';
                        } else {
                            $importError = 'Lỗi khi lưu câu hỏi.';
                        }
                    } catch (Exception $e) {
                        $importError = 'Lỗi xử lý file Excel: ' . $e->getMessage();
                    }
                }
            }
            ?>

            <?php if ($importError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($importError); ?></div>
            <?php endif; ?>
            <?php if ($importMessage): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($importMessage); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_questions">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="import_grade" class="form-label">Chọn Khối</label>
                        <select id="import_grade" name="import_grade" class="form-select" required>
                            <option value="">-- Chọn khối --</option>
                            <?php foreach ($availableGrades as $g): ?>
                                <option value="<?php echo $g; ?>"><?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="import_subject_id" class="form-label">Chọn Môn Học</label>
                        <select id="import_subject_id" name="import_subject_id" class="form-select" required>
                            <option value="">-- Chọn môn học --</option>
                            <?php foreach ($assignedSubjects as $subj): ?>
                                <option value="<?php echo $subj['id']; ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="questions_file" class="form-label">Chọn File JSON</label>
                        <input type="file" id="questions_file" name="questions_file" class="form-control" accept=".json" required />
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">📤 Nhập Câu Hỏi</button>
                </div>
            </form>

            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>📋 Định dạng file JSON mẫu:</h5>
                    <button class="btn btn-sm" id="copyJsonBtn">📋 Sao chép</button>
                </div>
                <pre class="bg-light p-3 rounded"><code id="jsonSample">[
  {
    "topic": "Chủ đề 1",
    "lesson": "Bài 1",
    "questions": [
      {
        "question": "Câu hỏi 1?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 0,
        "type": "single",
        "level": "NB"
      },
      {
        "question": "Câu hỏi nhiều đáp án?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": [0, 2],
        "type": "multiple",
        "level": "TH"
      }
    ]
  },
  {
    "topic": "Chủ đề 2",
    "lesson": "Bài 2",
    "questions": [...]
  }
]</code></pre>
            </div>
        </div>
    </div>
</div>
