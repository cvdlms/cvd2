<?php
// question_bank_form.php - Add Question Form Component
?>
<div class="collapse mb-4" id="addQuestionForm">
    <div class="card card-body">
        <h5 class="card-title">Thêm Câu Hỏi Mới</h5>
        <form method="post" id="addQuestionFormData">
            <input type="hidden" name="action" value="add_question">
            <input type="hidden" name="items_json" id="itemsJsonInput">
            <div class="row g-3">
                <div class="col-12">
                    <label for="topic" class="form-label">Chủ Đề</label>
                    <select id="topic" name="topic" class="form-select" required>
                        <option value="">-- Chọn chủ đề --</option>
                        <?php
                        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
                        if (file_exists($questionsFile)) {
                            $data = json_decode(file_get_contents($questionsFile), true);
                            if (is_array($data)) {
                                $topics = [];
                                foreach ($data as $item) {
                                    $topics[$item['topic']] = true;
                                }
                                foreach (array_keys($topics) as $topic) {
                                    echo "<option value=\"$topic\">$topic</option>";
                                }
                            }
                        }
                        ?>
                        <option value="new_topic">+ Tạo chủ đề mới</option>
                    </select>
                </div>
                <div class="col-12" id="newTopicDiv" style="display:none;">
                    <label for="new_topic_name" class="form-label">Tên Chủ Đề Mới</label>
                    <input type="text" id="new_topic_name" name="new_topic_name" class="form-control" placeholder="Ví dụ: Chủ đề 1: Máy tính và cộng đồng">
                </div>
                <div class="col-12">
                    <label for="lesson" class="form-label">Bài Học</label>
                    <select id="lesson" name="lesson" class="form-select" required>
                        <option value="">-- Chọn bài học --</option>
                        <option value="new_lesson">+ Tạo bài học mới</option>
                    </select>
                </div>
                <div class="col-12" id="newLessonDiv" style="display:none;">
                    <label for="new_lesson_name" class="form-label">Tên Bài Học Mới</label>
                    <input type="text" id="new_lesson_name" name="new_lesson_name" class="form-control" placeholder="Ví dụ: Bài 1: Thiết bị vào và thiết bị ra">
                </div>

                <!-- Question Type Selector -->
                <div class="col-12">
                    <label class="form-label mb-2">Loại Câu Hỏi</label>
                    <div class="qb-type-grid">
                        <label class="qb-type-card is-selected" data-type="single">
                            <input type="radio" class="qb-type-radio" name="question_type" value="single" checked>
                            <i class="bi bi-ui-radios"></i>
                            <span class="qb-type-name">Trắc nghiệm</span>
                            <span class="qb-type-desc">1 đáp án đúng</span>
                        </label>
                        <label class="qb-type-card" data-type="multiple">
                            <input type="radio" class="qb-type-radio" name="question_type" value="multiple">
                            <i class="bi bi-ui-checks-grid"></i>
                            <span class="qb-type-name">Nhiều đáp án</span>
                            <span class="qb-type-desc">2+ đáp án đúng</span>
                        </label>
                        <label class="qb-type-card qb-type-card--tf" data-type="true_false_multiple">
                            <input type="radio" class="qb-type-radio" name="question_type" value="true_false_multiple">
                            <i class="bi bi-check2-square"></i>
                            <span class="qb-type-name">Đúng / Sai</span>
                            <span class="qb-type-desc">Câu dẫn + 4 ý a-d</span>
                        </label>
                        <label class="qb-type-card qb-type-card--essay" data-type="essay">
                            <input type="radio" class="qb-type-radio" name="question_type" value="essay">
                            <i class="bi bi-pencil-square"></i>
                            <span class="qb-type-name">Tự luận</span>
                            <span class="qb-type-desc">Chấm tay theo điểm</span>
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="question_text" class="form-label" id="questionTextLabel">Câu Hỏi</label>
                    <textarea id="question_text" name="question_text" class="form-control" rows="3" required></textarea>
                    <div class="form-text d-none" id="tfHintText"><i class="bi bi-info-circle me-1"></i>Nhập câu dẫn/tình huống dài 2–3 dòng, sau đó khai báo các ý phát biểu a, b, c, d bên dưới.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Hình Ảnh Minh Họa (không bắt buộc)</label>
                    <input type="hidden" name="question_image" id="question_image">
                    <div class="input-group">
                        <input type="file" id="question_image_upload" class="form-control" accept="image/*">
                        <button type="button" class="btn btn-outline-secondary" id="question_image_remove" style="display:none;">
                            <i class="bi bi-trash"></i> Gỡ ảnh
                        </button>
                    </div>
                    <div id="question_image_preview" style="display:none; margin-top:10px;">
                        <img src="" alt="Hình minh họa câu hỏi" style="max-width:220px; max-height:160px; border:1px solid var(--border-soft); border-radius:8px; padding:4px; background:#fff;">
                    </div>
                    <div class="form-text">Chèn 1 hình minh họa cho câu hỏi. Hỗ trợ JPG, PNG, GIF, WebP (tối đa 5MB).</div>
                </div>
                <div class="col-md-6">
                    <label for="question_level" class="form-label">Mức Độ Nhận Thức</label>
                    <select id="question_level" name="question_level" class="form-select" required>
                        <option value="NB">Nhận biết</option>
                        <option value="TH">Thông hiểu</option>
                        <option value="VD">Vận dụng</option>
                        <option value="VDC">Vận dụng cao</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-text mb-2" id="levelHintText">Mức độ áp dụng cho loại câu hỏi đang chọn.</div>
                </div>

                <!-- MCQ Options Section -->
                <div class="col-12 qb-section" id="mcqSection">
                    <div class="qb-section-box qb-section-mcq">
                        <div class="qb-section-title"><i class="bi bi-list-check me-1"></i>Danh Sách Đáp Án</div>
                        <div id="optionsContainer">
                            <div class="input-group mb-2">
                                <span class="input-group-text">A</span>
                                <input type="text" name="options[]" class="form-control mcq-option-input" placeholder="Đáp án A" required>
                                <input type="checkbox" name="correct[]" value="0" class="form-check-input ms-2 mcq-correct-input" title="Đáp án đúng">
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">B</span>
                                <input type="text" name="options[]" class="form-control mcq-option-input" placeholder="Đáp án B" required>
                                <input type="checkbox" name="correct[]" value="1" class="form-check-input ms-2 mcq-correct-input" title="Đáp án đúng">
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">C</span>
                                <input type="text" name="options[]" class="form-control mcq-option-input" placeholder="Đáp án C" required>
                                <input type="checkbox" name="correct[]" value="2" class="form-check-input ms-2 mcq-correct-input" title="Đáp án đúng">
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">D</span>
                                <input type="text" name="options[]" class="form-control mcq-option-input" placeholder="Đáp án D" required>
                                <input type="checkbox" name="correct[]" value="3" class="form-check-input ms-2 mcq-correct-input" title="Đáp án đúng">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addOptionBtn">+ Thêm đáp án</button>
                    </div>
                </div>

                <!-- True/False Items Section -->
                <div class="col-12 qb-section" id="tfSection" style="display:none;">
                    <div class="qb-section-box qb-section-tf">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="qb-section-title mb-0"><i class="bi bi-check2-square me-1"></i>Các Ý Phát Biểu Đúng/Sai</div>
                            <span class="badge badge-soft-violet">Mỗi ý chọn Đúng hoặc Sai</span>
                        </div>
                        <p class="form-text mt-0">Học sinh đánh giá từng phát biểu là Đúng hay Sai dựa trên câu dẫn ở trên.</p>
                        <div id="tfItemsContainer"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addTfItemBtn"><i class="bi bi-plus-lg me-1"></i>Thêm ý</button>
                    </div>
                </div>

                <!-- Essay Section -->
                <div class="col-12 qb-section" id="essaySection" style="display:none;">
                    <div class="qb-section-box qb-section-essay">
                        <div class="qb-section-title"><i class="bi bi-pencil-square me-1"></i>Thiết Lập Cho Câu Tự Luận</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="essay_points" class="form-label">Điểm Tối Đa <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.25" min="0.25" max="20" id="essay_points" name="essay_points" class="form-control" value="2.0" required>
                                    <span class="input-group-text">điểm</span>
                                </div>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <div class="form-text mb-2"><i class="bi bi-person-fill-gear me-1"></i>Câu tự luận do giáo viên chấm tay sau khi học sinh nộp bài.</div>
                            </div>
                            <div class="col-12">
                                <label for="essay_suggested_answer" class="form-label">Đáp Án Gợi Ý / Dàn Ý Chấm (không bắt buộc)</label>
                                <textarea id="essay_suggested_answer" name="essay_suggested_answer" class="form-control" rows="4" placeholder="Ví dụ: 1) Nêu khái niệm... (0.5đ) — 2) Phân tích tác động... (1.0đ) — 3) Kết luận... (0.5đ)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">➕ Thêm Câu Hỏi</button>
            </div>
        </form>
    </div>
</div>
