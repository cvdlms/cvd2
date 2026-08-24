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
                    <input type="hidden" name="edit_items_json" id="editItemsJsonInput">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_topic" class="form-label">Chủ Đề</label>
                            <select id="edit_topic" name="edit_topic" class="form-select" required>
                                <option value="">-- Chọn chủ đề --</option>
                                <?php
                                if ($selectedGrade && $selectedSubjectId && $selectedSemester) {
                                    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
                                    if (file_exists($questionsFile)) {
                                        $data = json_decode(file_get_contents($questionsFile), true);
                                        if (is_array($data)) {
                                            $topics = [];
                                            foreach ($data as $item) {
                                                $topics[$item['topic']] = true;
                                            }
                                            foreach (array_keys($topics) as $topic) {
                                                echo "<option value=\"" . htmlspecialchars($topic) . "\">" . htmlspecialchars($topic) . "</option>";
                                            }
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
                            <label for="edit_question_text" class="form-label" id="editQuestionTextLabel">Câu Hỏi</label>
                            <textarea id="edit_question_text" name="edit_question_text" class="form-control" rows="3" required></textarea>
                            <div class="form-text d-none" id="editTfHintText"><i class="bi bi-info-circle me-1"></i>Nhập câu dẫn/tình huống dài 2–3 dòng, các ý phát biểu a, b, c, d khai báo bên dưới.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hình Ảnh Minh Họa (không bắt buộc)</label>
                            <input type="hidden" name="edit_question_image" id="edit_question_image">
                            <div class="input-group">
                                <input type="file" id="edit_question_image_upload" class="form-control" accept="image/*">
                                <button type="button" class="btn btn-outline-secondary" id="edit_question_image_remove" style="display:none;">
                                    <i class="bi bi-trash"></i> Gỡ ảnh
                                </button>
                            </div>
                            <div id="edit_question_image_preview" style="display:none; margin-top:10px;">
                                <img src="" alt="Hình minh họa câu hỏi" style="max-width:220px; max-height:160px; border:1px solid var(--border-soft); border-radius:8px; padding:4px; background:#fff;">
                            </div>
                            <div class="form-text">Chèn 1 hình minh họa cho câu hỏi. Hỗ trợ JPG, PNG, GIF, WebP (tối đa 5MB).</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-2">Loại Câu Hỏi</label>
                            <div class="qb-type-grid">
                                <label class="qb-type-card is-selected" data-type="single">
                                    <input type="radio" class="qb-type-radio" name="edit_question_type" id="edit_single_choice" value="single" checked>
                                    <i class="bi bi-ui-radios"></i>
                                    <span class="qb-type-name">Trắc nghiệm</span>
                                    <span class="qb-type-desc">1 đáp án đúng</span>
                                </label>
                                <label class="qb-type-card" data-type="multiple">
                                    <input type="radio" class="qb-type-radio" name="edit_question_type" id="edit_multiple_choice" value="multiple">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span class="qb-type-name">Nhiều đáp án</span>
                                    <span class="qb-type-desc">2+ đáp án đúng</span>
                                </label>
                                <label class="qb-type-card qb-type-card--tf" data-type="true_false_multiple">
                                    <input type="radio" class="qb-type-radio" name="edit_question_type" id="edit_type_tf" value="true_false_multiple">
                                    <i class="bi bi-check2-square"></i>
                                    <span class="qb-type-name">Đúng / Sai</span>
                                    <span class="qb-type-desc">Câu dẫn + 4 ý a-d</span>
                                </label>
                                <label class="qb-type-card qb-type-card--essay" data-type="essay">
                                    <input type="radio" class="qb-type-radio" name="edit_question_type" id="edit_type_essay" value="essay">
                                    <i class="bi bi-pencil-square"></i>
                                    <span class="qb-type-name">Tự luận</span>
                                    <span class="qb-type-desc">Chấm tay theo điểm</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_question_level" class="form-label">Mức Độ Nhận Thức</label>
                            <select id="edit_question_level" name="edit_question_level" class="form-select" required>
                                <option value="NB">Nhận biết</option>
                                <option value="TH">Thông hiểu</option>
                                <option value="VD">Vận dụng</option>
                                <option value="VDC">Vận dụng cao</option>
                            </select>
                        </div>
                        <!-- MCQ Options Section -->
                        <div class="col-12" id="editMcqSection">
                            <div class="qb-section-box qb-section-mcq">
                                <div class="qb-section-title"><i class="bi bi-list-check me-1"></i>Danh Sách Đáp Án</div>
                                <div id="editOptionsContainer">
                                    <!-- Options will be populated by JS -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddOptionBtn">+ Thêm đáp án</button>
                            </div>
                        </div>
                        <!-- True/False Items Section -->
                        <div class="col-12" id="editTfSection" style="display:none;">
                            <div class="qb-section-box qb-section-tf">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <div class="qb-section-title mb-0"><i class="bi bi-check2-square me-1"></i>Các Ý Phát Biểu Đúng/Sai</div>
                                    <span class="badge badge-soft-violet">Mỗi ý chọn Đúng hoặc Sai</span>
                                </div>
                                <p class="form-text mt-0">Học sinh đánh giá từng phát biểu là Đúng hay Sai dựa trên câu dẫn ở trên.</p>
                                <div id="editTfItemsContainer"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddTfItemBtn"><i class="bi bi-plus-lg me-1"></i>Thêm ý</button>
                            </div>
                        </div>
                        <!-- Essay Section -->
                        <div class="col-12" id="editEssaySection" style="display:none;">
                            <div class="qb-section-box qb-section-essay">
                                <div class="qb-section-title"><i class="bi bi-pencil-square me-1"></i>Thiết Lập Cho Câu Tự Luận</div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="edit_essay_points" class="form-label">Điểm Tối Đa <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.25" min="0.25" max="20" id="edit_essay_points" name="edit_essay_points" class="form-control" value="2.0">
                                            <span class="input-group-text">điểm</span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="edit_essay_suggested_answer" class="form-label">Đáp Án Gợi Ý / Dàn Ý Chấm (không bắt buộc)</label>
                                        <textarea id="edit_essay_suggested_answer" name="edit_essay_suggested_answer" class="form-control" rows="4" placeholder="Ví dụ: 1) Nêu khái niệm... (0.5đ) — 2) Phân tích tác động... (1.0đ) — 3) Kết luận... (0.5đ)"></textarea>
                                    </div>
                                </div>
                            </div>
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
                <h5 class="modal-title" id="excelAddModalLabel">Thêm Câu Hỏi Từ File Excel 
                <?php 
                include_once '../includes/premium_helper.php';
                $isPremiumUser = isPremiumUser($_SESSION['username']);
                if (!$isPremiumUser): 
                ?>
                    <span class="badge bg-warning text-dark">⭐ Premium</span>
                <?php endif; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!$isPremiumUser): ?>
                    <div class="alert alert-warning">
                        <h6>🔒 Chức năng Premium</h6>
                        <p>Import câu hỏi từ Excel là tính năng dành cho tài khoản Premium.</p>
                        <a href="premium_activation.php" class="btn btn-sm btn-warning">⭐ Nâng cấp Premium ngay</a>
                    </div>
                <?php else: ?>
                <form method="post" enctype="multipart/form-data" id="excelImportForm">
                    <input type="hidden" name="action" value="import_excel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="excel_import_grade" class="form-label">Chọn Khối</label>
                            <select id="excel_import_grade" name="excel_import_grade" class="form-select" required>
                                <option value="">-- Chọn khối --</option>
                                <?php foreach ($availableGrades as $g): ?>
                                    <option value="<?php echo $g; ?>"><?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="excel_import_subject_id" class="form-label">Chọn Môn Học</label>
                            <select id="excel_import_subject_id" name="excel_import_subject_id" class="form-select" required>
                                <option value="">-- Chọn môn học --</option>
                                <?php foreach ($assignedSubjects as $subj): ?>
                                    <option value="<?php echo $subj['id']; ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="excel_import_semester" class="form-label">Chọn Học Kì</label>
                            <select id="excel_import_semester" name="excel_import_semester" class="form-select" required>
                                <option value="">-- Chọn học kì --</option>
                                <option value="hk1">Học kì 1</option>
                                <option value="hk2">Học kì 2</option>
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
                            <div class="table-responsive">
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
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <?php if ($isPremiumUser): ?>
                <button type="submit" class="btn btn-success" form="excelImportForm">📤 Nhập Câu Hỏi</button>
                <?php endif; ?>
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
