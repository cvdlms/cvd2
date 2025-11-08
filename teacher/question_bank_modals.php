<?php
// question_bank_modals.php - Modals Component
?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deleteModalBody">
                Bạn có chắc chắn muốn xóa câu hỏi này?
                <div id="deleteConfirmInput" style="display:none; margin-top: 10px;">
                    <label for="confirmText" class="form-label">Gõ "OK" để xác nhận:</label>
                    <input type="text" id="confirmText" class="form-control" placeholder="OK">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editQuestionModalLabel">Sửa Câu Hỏi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="editQuestionForm">
                    <input type="hidden" name="action" value="edit_question">
                    <input type="hidden" name="edit_topic_index" id="edit_topic_index">
                    <input type="hidden" name="edit_index" id="edit_index">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_topic" class="form-label">Chủ Đề</label>
                            <select id="edit_topic" name="edit_topic" class="form-select" required>
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
                        <div class="col-12" id="editNewTopicDiv" style="display:none;">
                            <label for="edit_new_topic_name" class="form-label">Tên Chủ Đề Mới</label>
                            <input type="text" id="edit_new_topic_name" name="edit_new_topic_name" class="form-control" placeholder="Ví dụ: Chủ đề 1: Máy tính và cộng đồng">
                        </div>
                        <div class="col-12">
                            <label for="edit_lesson" class="form-label">Bài Học</label>
                            <select id="edit_lesson" name="edit_lesson" class="form-select" required>
                                <option value="">-- Chọn bài học --</option>
                                <option value="new_lesson">+ Tạo bài học mới</option>
                            </select>
                        </div>
                        <div class="col-12" id="editNewLessonDiv" style="display:none;">
                            <label for="edit_new_lesson_name" class="form-label">Tên Bài Học Mới</label>
                            <input type="text" id="edit_new_lesson_name" name="edit_new_lesson_name" class="form-control" placeholder="Ví dụ: Bài 1: Thiết bị vào và thiết bị ra">
                        </div>
                        <div class="col-12">
                            <label for="edit_question_text" class="form-label">Câu Hỏi</label>
                            <textarea id="edit_question_text" name="edit_question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại Câu Hỏi</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit_question_type" id="edit_single_choice" value="single" checked>
                                <label class="form-check-label" for="edit_single_choice">
                                    Trắc nghiệm (1 đáp án đúng)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit_question_type" id="edit_multiple_choice" value="multiple">
                                <label class="form-check-label" for="edit_multiple_choice">
                                    Trắc nghiệm nhiều đáp án
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_question_level" class="form-label">Mức Độ</label>
                            <select id="edit_question_level" name="edit_question_level" class="form-select" required>
                                <option value="NB">Nhận biết</option>
                                <option value="TH">Thông hiểu</option>
                                <option value="VD">Vận dụng</option>
                                <option value="VDC">Vận dụng cao</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Đáp Án</label>
                            <div id="editOptionsContainer">
                                <!-- Options will be populated by JS -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddOptionBtn">+ Thêm đáp án</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success" form="editQuestionForm">💾 Lưu Thay Đổi</button>
            </div>
        </div>
    </div>
</div>

<!-- Excel Import Modal -->
<div class="modal fade" id="excelAddModal" tabindex="-1" aria-labelledby="excelAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excelAddModalLabel">Thêm Câu Hỏi Từ File Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" id="excelImportForm">
                    <input type="hidden" name="action" value="import_excel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="excel_import_grade" class="form-label">Chọn Khối</label>
                            <select id="excel_import_grade" name="excel_import_grade" class="form-select" required>
                                <option value="">-- Chọn khối --</option>
                                <?php foreach ($availableGrades as $g): ?>
                                    <option value="<?php echo $g; ?>"><?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="excel_import_subject_id" class="form-label">Chọn Môn Học</label>
                            <select id="excel_import_subject_id" name="excel_import_subject_id" class="form-select" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($assignedSubjects as $subj): ?>
                                    <option value="<?php echo $subj['id']; ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="excel_file" class="form-label">Chọn File Excel</label>
                            <input type="file" id="excel_file" name="excel_file" class="form-control" accept=".xlsx,.xls" required />
                            <div class="form-text">Chỉ chấp nhận file .xlsx hoặc .xls</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6>📋 Định dạng file Excel mẫu:</h6>
                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="downloadExcelTemplate()">📥 Tải mẫu Excel</button>
                        </div>
                        <table class="table table-sm table-bordered mt-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Cột</th>
                                    <th>Mô tả</th>
                                    <th>Ví dụ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>A</td>
                                    <td>Chủ đề</td>
                                    <td>Chủ đề 1: Máy tính và cộng đồng</td>
                                </tr>
                                <tr>
                                    <td>B</td>
                                    <td>Bài học</td>
                                    <td>Bài 1: Thiết bị vào và thiết bị ra</td>
                                </tr>
                                <tr>
                                    <td>C</td>
                                    <td>Câu hỏi</td>
                                    <td>Câu hỏi trắc nghiệm?</td>
                                </tr>
                                <tr>
                                    <td>D</td>
                                    <td>Đáp án A</td>
                                    <td>Đáp án A</td>
                                </tr>
                                <tr>
                                    <td>E</td>
                                    <td>Đáp án B</td>
                                    <td>Đáp án B</td>
                                </tr>
                                <tr>
                                    <td>F</td>
                                    <td>Đáp án C</td>
                                    <td>Đáp án C</td>
                                </tr>
                                <tr>
                                    <td>G</td>
                                    <td>Đáp án D</td>
                                    <td>Đáp án D</td>
                                </tr>
                                <tr>
                                    <td>H</td>
                                    <td>Đáp án đúng (1=A, 2=B, 3=C, 4=D hoặc 1,3 cho nhiều đáp án)</td>
                                    <td>1 hoặc 1,3</td>
                                </tr>
                                <tr>
                                    <td>I</td>
                                    <td>Loại câu hỏi (single/multiple)</td>
                                    <td>single</td>
                                </tr>
                                <tr>
                                    <td>J</td>
                                    <td>Mức độ (NB/TH/VD/VDC)</td>
                                    <td>NB</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success" form="excelImportForm">📤 Nhập Câu Hỏi</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Thông báo</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Câu hỏi đã được xóa thành công!
        </div>
    </div>
</div>
