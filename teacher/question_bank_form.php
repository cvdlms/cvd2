<?php
// question_bank_form.php - Add Question Form Component
?>
<div class="collapse mb-4" id="addQuestionForm">
    <div class="card card-body">
        <h5 class="card-title">Thêm Câu Hỏi Mới</h5>
        <form method="post" id="addQuestionFormData">
            <input type="hidden" name="action" value="add_question">
            <div class="row g-3">
                <div class="col-12">
                    <label for="topic" class="form-label">Chủ Đề</label>
                    <select id="topic" name="topic" class="form-select" required>
                        <option value="">-- Chọn chủ đề --</option>
                        <?php
                        $questionsFile = __DIR__ . "/questions/{$selectedGrade}/subject_{$selectedSubjectId}.json";
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
                <div class="col-12">
                    <label for="question_text" class="form-label">Câu Hỏi</label>
                    <textarea id="question_text" name="question_text" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Loại Câu Hỏi</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="question_type" id="single_choice" value="single" checked>
                        <label class="form-check-label" for="single_choice">
                            Trắc nghiệm (1 đáp án đúng)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="question_type" id="multiple_choice" value="multiple">
                        <label class="form-check-label" for="multiple_choice">
                            Trắc nghiệm nhiều đáp án
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="question_level" class="form-label">Mức Độ</label>
                    <select id="question_level" name="question_level" class="form-select" required>
                        <option value="NB">Nhận biết</option>
                        <option value="TH">Thông hiểu</option>
                        <option value="VD">Vận dụng</option>
                        <option value="VDC">Vận dụng cao</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Đáp Án</label>
                    <div id="optionsContainer">
                        <div class="input-group mb-2">
                            <span class="input-group-text">A</span>
                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án A" required>
                            <input type="checkbox" name="correct[]" value="0" class="form-check-input ms-2" title="Đáp án đúng">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">B</span>
                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án B" required>
                            <input type="checkbox" name="correct[]" value="1" class="form-check-input ms-2" title="Đáp án đúng">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">C</span>
                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án C" required>
                            <input type="checkbox" name="correct[]" value="2" class="form-check-input ms-2" title="Đáp án đúng">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">D</span>
                            <input type="text" name="options[]" class="form-control" placeholder="Đáp án D" required>
                            <input type="checkbox" name="correct[]" value="3" class="form-check-input ms-2" title="Đáp án đúng">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addOptionBtn">+ Thêm đáp án</button>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">➕ Thêm Câu Hỏi</button>
            </div>
        </form>
    </div>
</div>
