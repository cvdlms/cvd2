function notifySuccess(msg) {
    if (typeof showSuccessToast === 'function') showSuccessToast(msg);
    else if (typeof showToast === 'function') showToast(msg, 'success');
    else alert(msg);
}
function notifyError(msg) {
    if (typeof showErrorToast === 'function') showErrorToast(msg);
    else if (typeof showToast === 'function') showToast(msg, 'error');
    else alert(msg);
}
function notifyWarning(msg) {
    if (typeof showWarningToast === 'function') showWarningToast(msg);
    else if (typeof showToast === 'function') showToast(msg, 'warning');
    else alert(msg);
}

function initQuestionImageUpload(fileInput, hiddenInput, previewWrap, removeBtn) {
    if (!fileInput || !hiddenInput) return;

    function showPreview(url) {
        if (!url) {
            if (previewWrap) previewWrap.style.display = 'none';
            if (removeBtn) removeBtn.style.display = 'none';
            return;
        }
        if (previewWrap) {
            const img = previewWrap.querySelector('img');
            if (img) img.src = url;
            previewWrap.style.display = 'block';
        }
        if (removeBtn) removeBtn.style.display = 'inline-block';
    }

    fileInput.addEventListener('change', function() {
        const file = this.files && this.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('image', file);
        showPreview(URL.createObjectURL(file));
        fetch('api/upload_question_image.php', {
            method: 'POST',
            body: fd
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.url) {
                hiddenInput.value = result.url;
                showPreview(result.url);
                notifySuccess('Đã tải ảnh lên thành công!');
            } else {
                hiddenInput.value = '';
                showPreview('');
                fileInput.value = '';
                notifyError('Lỗi tải ảnh: ' + (result.message || 'Không xác định'));
            }
        })
        .catch(() => {
            hiddenInput.value = '';
            showPreview('');
            fileInput.value = '';
            notifyError('Lỗi kết nối khi tải ảnh lên.');
        });
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            hiddenInput.value = '';
            fileInput.value = '';
            showPreview('');
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initQuestionImageUpload(
        document.getElementById('question_image_upload'),
        document.getElementById('question_image'),
        document.getElementById('question_image_preview'),
        document.getElementById('question_image_remove')
    );
    initQuestionImageUpload(
        document.getElementById('edit_question_image_upload'),
        document.getElementById('edit_question_image'),
        document.getElementById('edit_question_image_preview'),
        document.getElementById('edit_question_image_remove')
    );

    if (typeof MathJax !== 'undefined' && MathJax.typeset) {
        MathJax.typeset();
    }

    /* ================= Question type switcher (MCQ / DS / Tu luan) ================= */
    const QB_LEVEL_LABELS_STD = { NB: 'Nhận biết', TH: 'Thông hiểu', VD: 'Vận dụng', VDC: 'Vận dụng cao' };
    const QB_LEVEL_LABELS_TF = { NB: 'Biết', TH: 'Hiểu', VD: 'Vận dụng' };
    const TFM_LETTERS = ['a', 'b', 'c', 'd', 'e', 'f'];
    const TFM_MAX_ITEMS = 6;
    const TFM_MIN_ITEMS = 2;

    function qbGetType(prefix) {
        const checked = document.querySelector('input[name="' + prefix + 'question_type"]:checked');
        return checked ? checked.value : 'single';
    }

    function qbSyncTypeUI(prefix) {
        const isEdit = prefix === 'edit_';
        const type = qbGetType(prefix);

        const mcqSec = document.getElementById(isEdit ? 'editMcqSection' : 'mcqSection');
        const tfSec = document.getElementById(isEdit ? 'editTfSection' : 'tfSection');
        const essaySec = document.getElementById(isEdit ? 'editEssaySection' : 'essaySection');

        if (mcqSec) mcqSec.style.display = (type === 'single' || type === 'multiple') ? '' : 'none';
        if (tfSec) tfSec.style.display = type === 'true_false_multiple' ? '' : 'none';
        if (essaySec) essaySec.style.display = type === 'essay' ? '' : 'none';

        // Hidden sections must not carry "required" inputs, otherwise submit is blocked
        if (mcqSec) {
            const isMcq = (type === 'single' || type === 'multiple');
            mcqSec.querySelectorAll('.mcq-option-input').forEach(inp => { inp.required = isMcq; });
        }
        const pointsInput = document.getElementById(isEdit ? 'edit_essay_points' : 'essay_points');
        if (pointsInput) pointsInput.required = (type === 'essay');

        // Question label / placeholder / hint
        const qLabel = document.getElementById(isEdit ? 'editQuestionTextLabel' : 'questionTextLabel');
        const qText = document.getElementById(prefix + 'question_text');
        const tfHint = document.getElementById(isEdit ? 'editTfHintText' : 'tfHintText');
        if (qLabel) qLabel.textContent = type === 'true_false_multiple' ? 'Câu Dẫn / Tình Huống' : 'Câu Hỏi';
        if (tfHint) tfHint.classList.toggle('d-none', type !== 'true_false_multiple');
        if (qText) {
            qText.placeholder = type === 'true_false_multiple'
                ? 'Ví dụ: Trong một lớp học, giáo viên nhập điểm kiểm tra của học sinh vào bảng tính và sử dụng các hàm để tính điểm trung bình, điểm cao nhất và tổng số học sinh. Sau đó giáo viên thay đổi một vài điểm số trong bảng...'
                : '';
        }

        // Level labels swap for DS type
        const levelSelect = document.getElementById(prefix === 'edit_' ? 'edit_question_level' : 'question_level');
        if (levelSelect) {
            const map = type === 'true_false_multiple' ? QB_LEVEL_LABELS_TF : QB_LEVEL_LABELS_STD;
            Array.from(levelSelect.options).forEach(opt => {
                if (QB_LEVEL_LABELS_STD[opt.value]) opt.textContent = map[opt.value];
                const hideOpt = (type === 'true_false_multiple' && opt.value === 'VDC');
                opt.disabled = hideOpt;
                opt.hidden = hideOpt;
            });
            if (type === 'true_false_multiple' && levelSelect.value === 'VDC') levelSelect.value = 'TH';
        }

        // Card selected state
        document.querySelectorAll('input[name="' + prefix + 'question_type"]').forEach(r => {
            const card = r.closest('.qb-type-card');
            if (card) card.classList.toggle('is-selected', r.checked);
        });
    }

    function qbRenumberTfRows(container) {
        const rows = container.querySelectorAll('.tf-item-row');
        rows.forEach((row, i) => {
            const letter = TFM_LETTERS[i] || String(i + 1);
            row.querySelector('.tf-item-letter').textContent = letter;
            row.querySelector('.tf-statement-input').placeholder = 'Phát biểu ý ' + letter + '...';
            row.querySelector('.tf-remove-btn').style.visibility = rows.length > TFM_MIN_ITEMS ? 'visible' : 'hidden';
        });
    }

    function qbCreateTfItemRow(statement, correct) {
        const row = document.createElement('div');
        row.className = 'tf-item-row';
        row.innerHTML =
            '<span class="tf-item-letter">a</span>' +
            '<input type="text" class="form-control tf-statement-input" maxlength="500">' +
            '<div class="tf-toggle-group">' +
            '<button type="button" class="tf-toggle-btn tf-yes" data-value="1"><i class="bi bi-check-lg me-1"></i>Đúng</button>' +
            '<button type="button" class="tf-toggle-btn tf-no" data-value="0"><i class="bi bi-x-lg me-1"></i>Sai</button>' +
            '</div>' +
            '<button type="button" class="tf-remove-btn" title="Xóa ý này"><i class="bi bi-x-lg"></i></button>';

        row.querySelectorAll('.tf-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                row.dataset.answer = this.dataset.value;
                row.querySelectorAll('.tf-toggle-btn').forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');
            });
        });
        row.querySelector('.tf-remove-btn').addEventListener('click', function() {
            if (row.parentElement.children.length <= TFM_MIN_ITEMS) {
                notifyWarning('Cần ít nhất 2 ý phát biểu!');
                return;
            }
            const container = row.parentElement;
            row.remove();
            qbRenumberTfRows(container);
        });

        if (typeof statement === 'string') row.querySelector('.tf-statement-input').value = statement;
        if (correct === true || correct === false) {
            row.dataset.answer = correct ? '1' : '0';
            row.querySelector(correct ? '.tf-yes' : '.tf-no').classList.add('is-active');
        } else {
            // Default to Đúng so every row always has a chosen answer
            row.querySelector('.tf-yes').classList.add('is-active');
            row.dataset.answer = '1';
        }
        return row;
    }

    function qbGetTfContainer(prefix) {
        return document.getElementById(prefix === 'edit_' ? 'editTfItemsContainer' : 'tfItemsContainer');
    }

    function qbFillTfContainer(prefix, items) {
        const container = qbGetTfContainer(prefix);
        container.innerHTML = '';
        (items && items.length ? items : []).forEach(it => {
            container.appendChild(qbCreateTfItemRow(it.statement || '', it.correct));
        });
        qbRenumberTfRows(container);
    }

    function qbAddEmptyTfItem(prefix) {
        const container = qbGetTfContainer(prefix);
        if (container.children.length >= TFM_MAX_ITEMS) {
            notifyWarning('Tối đa 6 ý phát biểu!');
            return;
        }
        container.appendChild(qbCreateTfItemRow('', null));
        qbRenumberTfRows(container);
    }

    function qbCollectTfItems(prefix) {
        const container = qbGetTfContainer(prefix);
        const items = [];
        for (const row of container.querySelectorAll('.tf-item-row')) {
            const statement = row.querySelector('.tf-statement-input').value.trim();
            if (!statement) return { error: 'Vui lòng nhập nội dung cho tất cả các ý phát biểu!' };
            items.push({ statement: statement, correct: row.dataset.answer === '1' });
        }
        if (items.length < TFM_MIN_ITEMS) return { error: 'Cần ít nhất 2 ý phát biểu Đúng/Sai!' };
        return { items: items };
    }

    function qbInitTypeSwitcher(prefix) {
        const radios = document.querySelectorAll('input[name="' + prefix + 'question_type"]');
        if (!radios.length) return false;
        radios.forEach(radio => radio.addEventListener('change', () => qbSyncTypeUI(prefix)));
        qbSyncTypeUI(prefix);
        return true;
    }

    /* ================= Essay sub-questions (các ý nhỏ a, b, c...) ================= */
    const SUBQ_LETTERS = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
    const SUBQ_MAX = 10;

    function qbGetSubqContainer(prefix) {
        return document.getElementById(prefix === 'edit_' ? 'editSubQuestionsContainer' : 'subQuestionsContainer');
    }

    function qbPointsInput(prefix) {
        return document.getElementById(prefix === 'edit_' ? 'edit_essay_points' : 'essay_points');
    }

    function qbUpdateSubqTotal(prefix) {
        const container = qbGetSubqContainer(prefix);
        if (!container) return;
        let sum = 0;
        container.querySelectorAll('.subq-points-input').forEach(inp => {
            const v = parseFloat(inp.value);
            if (!isNaN(v) && v > 0) sum += v;
        });
        sum = Math.round(sum * 100) / 100;
        const totalEl = document.getElementById(prefix === 'edit_' ? 'editSubqPointsTotal' : 'subqPointsTotal');
        if (totalEl) totalEl.textContent = sum;
        const ptsInput = qbPointsInput(prefix);
        const hint = container.closest('.qb-section-box, .row, .col-12');
        const maxPts = ptsInput ? parseFloat(ptsInput.value) : NaN;
        if (totalEl) {
            const over = !isNaN(maxPts) && maxPts > 0 && sum > maxPts + 0.001;
            totalEl.style.color = over ? '#dc3545' : '';
        }
    }

    function qbRenumberSubqRows(container) {
        const rows = container.querySelectorAll('.subq-row');
        rows.forEach((row, i) => {
            const letter = SUBQ_LETTERS[i] || String(i + 1);
            row.querySelector('.subq-letter').textContent = letter;
            row.querySelector('.subq-text-input').placeholder = 'Nội dung ý ' + letter + '...';
            row.querySelector('.subq-remove-btn').style.visibility = rows.length > 1 ? 'visible' : 'hidden';
        });
        qbUpdateSubqTotal(container.dataset.prefix || (container.id === 'editSubQuestionsContainer' ? 'edit_' : ''));
    }

    function qbCreateSubqRow(text, points) {
        const row = document.createElement('div');
        row.className = 'subq-row';
        row.innerHTML =
            '<span class="subq-letter">a</span>' +
            '<input type="text" class="form-control subq-text-input" maxlength="600">' +
            '<div class="input-group input-group-sm subq-points-group">' +
            '<input type="number" class="form-control subq-points-input" step="0.25" min="0" max="20" placeholder="đ">' +
            '<span class="input-group-text">đ</span>' +
            '</div>' +
            '<button type="button" class="subq-remove-btn" title="Xóa ý này"><i class="bi bi-x-lg"></i></button>';

        if (typeof text === 'string') row.querySelector('.subq-text-input').value = text;
        if (points !== undefined && points !== null && points !== '') row.querySelector('.subq-points-input').value = points;

        row.querySelector('.subq-points-input').addEventListener('input', function() {
            qbUpdateSubqTotal(row.closest('.subq-container').dataset.prefix || '');
        });
        row.querySelector('.subq-remove-btn').addEventListener('click', function() {
            const container = row.parentElement;
            row.remove();
            qbRenumberSubqRows(container);
        });
        return row;
    }

    function qbFillSubqContainer(prefix, subs) {
        const container = qbGetSubqContainer(prefix);
        if (!container) return;
        container.dataset.prefix = prefix;
        container.innerHTML = '';
        (subs && subs.length ? subs : []).forEach(sub => {
            container.appendChild(qbCreateSubqRow(sub.text || '', sub.points));
        });
        qbRenumberSubqRows(container);
    }

    function qbAddEmptySubq(prefix) {
        const container = qbGetSubqContainer(prefix);
        if (!container) return;
        container.dataset.prefix = prefix;
        if (container.querySelectorAll('.subq-row').length >= SUBQ_MAX) {
            notifyWarning('Tối đa 10 ý nhỏ!');
            return;
        }
        container.appendChild(qbCreateSubqRow('', ''));
        qbRenumberSubqRows(container);
    }

    function qbCollectSubq(prefix) {
        const container = qbGetSubqContainer(prefix);
        if (!container) return { subs: [] };
        const subs = [];
        for (const row of container.querySelectorAll('.subq-row')) {
            const text = row.querySelector('.subq-text-input').value.trim();
            const ptsRaw = row.querySelector('.subq-points-input').value;
            if (!text) return { error: 'Vui lòng nhập nội dung cho tất cả các ý nhỏ (hoặc xóa ý trống)!' };
            const sub = { text: text };
            const pts = parseFloat(ptsRaw);
            if (!isNaN(pts) && pts > 0) sub.points = Math.round(pts * 100) / 100;
            subs.push(sub);
        }
        const ptsInput = qbPointsInput(prefix);
        const maxPts = ptsInput ? parseFloat(ptsInput.value) : NaN;
        const sum = subs.reduce((a, s) => a + (s.points || 0), 0);
        if (!isNaN(maxPts) && maxPts > 0 && sum > maxPts + 0.001) {
            return { error: 'Tổng điểm các ý (' + Math.round(sum * 100) / 100 + ') vượt quá điểm tối đa của câu (' + maxPts + ')!' };
        }
        return { subs: subs, hasPoints: subs.some(s => s.points) };
    }

    function qbInitSubq(prefix) {
        const addBtn = document.querySelector('.subq-add-btn[data-prefix="' + prefix + '"]');
        if (addBtn) addBtn.addEventListener('click', () => qbAddEmptySubq(prefix));
        const ptsInput = qbPointsInput(prefix);
        if (ptsInput) ptsInput.addEventListener('input', () => qbUpdateSubqTotal(prefix));
    }

    // Wire up both forms (add form + edit modal)
    qbInitTypeSwitcher('');
    qbInitTypeSwitcher('edit_');
    qbInitSubq('');
    qbInitSubq('edit_');

    // Default 4 empty DS rows in the add form
    const addTfContainer = document.getElementById('tfItemsContainer');
    if (addTfContainer) {
        for (let i = 0; i < 4; i++) addTfContainer.appendChild(qbCreateTfItemRow('', null));
        qbRenumberTfRows(addTfContainer);
    }
    const addTfItemBtn = document.getElementById('addTfItemBtn');
    if (addTfItemBtn) addTfItemBtn.addEventListener('click', () => qbAddEmptyTfItem(''));
    const editAddTfItemBtn = document.getElementById('editAddTfItemBtn');
    if (editAddTfItemBtn) editAddTfItemBtn.addEventListener('click', () => qbAddEmptyTfItem('edit_'));


    // Handle topic selection
    const topicSelect = document.getElementById('topic');
    if (topicSelect) {
        topicSelect.addEventListener('change', function() {
            const newTopicDiv = document.getElementById('newTopicDiv');
            const lessonSelect = document.getElementById('lesson');
            if (this.value === 'new_topic') {
                if (newTopicDiv) newTopicDiv.style.display = 'block';
                const newTopicName = document.getElementById('new_topic_name');
                if (newTopicName) newTopicName.required = true;
                if (lessonSelect) lessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
            } else {
                if (newTopicDiv) newTopicDiv.style.display = 'none';
                const newTopicName = document.getElementById('new_topic_name');
                if (newTopicName) newTopicName.required = false;
                // Populate lessons for selected topic
                populateLessons(this.value);
            }
        });
    }

    // Handle lesson selection
    const lessonSelect = document.getElementById('lesson');
    if (lessonSelect) {
        lessonSelect.addEventListener('change', function() {
            const newLessonDiv = document.getElementById('newLessonDiv');
            if (this.value === 'new_lesson') {
                if (newLessonDiv) newLessonDiv.style.display = 'block';
                const newLessonName = document.getElementById('new_lesson_name');
                if (newLessonName) newLessonName.required = true;
            } else {
                if (newLessonDiv) newLessonDiv.style.display = 'none';
                const newLessonName = document.getElementById('new_lesson_name');
                if (newLessonName) newLessonName.required = false;
            }
        });
    }

    function populateLessons(selectedTopic) {
        const lessonSelect = document.getElementById('lesson');
        if (lessonSelect) {
            lessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
            const questionsData = window.questionsData || [];
            const lessons = [];
            questionsData.forEach(item => {
                if (item.topic === selectedTopic) {
                    lessons.push(item.lesson);
                }
            });
            lessons.forEach(lesson => {
                const option = document.createElement('option');
                option.value = lesson;
                option.textContent = lesson;
                lessonSelect.appendChild(option);
            });
        }
    }

    // Handle adding more options
    const addOptionBtn = document.getElementById('addOptionBtn');
    if (addOptionBtn) {
        let optionIndex = 4; // Start from E
        addOptionBtn.addEventListener('click', function() {
            const container = document.getElementById('optionsContainer');
            if (container) {
                const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const letter = letters[optionIndex % 26];

                const optionDiv = document.createElement('div');
                optionDiv.className = 'input-group mb-2';
                optionDiv.innerHTML = `
                    <span class="input-group-text">${letter}</span>
                    <input type="text" name="options[]" class="form-control" placeholder="Đáp án ${letter}" required>
                    <input type="checkbox" name="correct[]" value="${optionIndex}" class="form-check-input ms-2" title="Đáp án đúng">
                    <button type="button" class="btn btn-sm btn-danger remove-option">X</button>
                `;
                container.appendChild(optionDiv);
                optionIndex++;
            }
        });
    }

    // Handle removing options
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            e.target.closest('.input-group').remove();
        }
    });

    // Handle form submission
    const addQuestionForm = document.getElementById('addQuestionFormData');
    if (addQuestionForm) {
        addQuestionForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const questionType = data.question_type;

            if (questionType === 'true_false_multiple') {
                const res = qbCollectTfItems('');
                if (res.error) { notifyWarning(res.error); return; }
                formData.set('items_json', JSON.stringify(res.items));
            } else if (questionType === 'essay') {
                const pts = parseFloat(data.essay_points);
                if (!pts || pts <= 0) {
                    notifyWarning('Vui lòng nhập điểm tối đa hợp lệ cho câu tự luận!');
                    return;
                }
                const subRes = qbCollectSubq('');
                if (subRes.error) { notifyWarning(subRes.error); return; }
                if (subRes.subs.length) {
                    formData.set('sub_questions_json', JSON.stringify(subRes.subs));
                } else {
                    formData.delete('sub_questions_json');
                }
            } else {
                // MCQ: validate at least one correct answer is selected
                const correctAnswers = formData.getAll('correct[]');
                if (correctAnswers.length === 0) {
                    notifyWarning('Vui lòng chọn ít nhất một đáp án đúng!');
                    return;
                }
                if (questionType === 'single' && correctAnswers.length > 1) {
                    notifyWarning('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                    return;
                }
                if (questionType === 'multiple' && correctAnswers.length < 2) {
                    notifyWarning('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                    return;
                }
            }

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Đang lưu...';
            submitBtn.disabled = true;

            // Send data
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    notifySuccess('Câu hỏi đã được thêm thành công!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notifyError('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                notifyError('Có lỗi xảy ra khi thêm câu hỏi!');
                console.error(error);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Handle delete question
    let currentDeleteData = null;
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-question');
        if (btn) {
            e.stopPropagation();
            const topicIndex = btn.getAttribute('data-topic-index');
            const index = btn.getAttribute('data-index');
            currentDeleteData = { type: 'single', topicIndex, index };

            const deleteBody = document.getElementById('deleteModalBody');
            if (deleteBody) {
                deleteBody.innerHTML = 'Bạn có chắc chắn muốn xóa câu hỏi này?';
            }
            const deleteTitle = document.getElementById('deleteModalLabel');
            if (deleteTitle) {
                deleteTitle.textContent = 'Xác nhận xóa câu hỏi';
            }

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    });

    // Handle delete all questions button
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            currentDeleteData = { type: 'all' };

            const deleteBody = document.getElementById('deleteModalBody');
            if (deleteBody) {
                deleteBody.innerHTML = '<div class="alert alert-danger mb-0 text-start"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa <strong>TẤT CẢ</strong> câu hỏi trong môn học này? Hành động này không thể hoàn tác!</div>';
            }
            const deleteTitle = document.getElementById('deleteModalLabel');
            if (deleteTitle) {
                deleteTitle.textContent = 'Xóa tất cả câu hỏi';
            }

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    }

    // Handle confirm delete in modal
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (!currentDeleteData) return;

            const modalEl = document.getElementById('deleteModal');
            const deleteModalInstance = bootstrap.Modal.getInstance(modalEl);

            if (currentDeleteData.type === 'single') {
                const { topicIndex, index } = currentDeleteData;
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_question',
                        topic_index: topicIndex,
                        index: index
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (deleteModalInstance) deleteModalInstance.hide();
                    if (result.success) {
                        notifySuccess('Câu hỏi đã được xóa thành công!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        notifyError('Lỗi: ' + result.message);
                    }
                })
                .catch(error => {
                    if (deleteModalInstance) deleteModalInstance.hide();
                    notifyError('Có lỗi xảy ra khi xóa câu hỏi!');
                    console.error(error);
                });
            } else if (currentDeleteData.type === 'all') {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_all_questions'
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (deleteModalInstance) deleteModalInstance.hide();
                    if (result.success) {
                        notifySuccess('Tất cả câu hỏi đã được xóa thành công!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        notifyError('Lỗi: ' + result.message);
                    }
                })
                .catch(error => {
                    if (deleteModalInstance) deleteModalInstance.hide();
                    notifyError('Có lỗi xảy ra khi xóa tất cả câu hỏi!');
                    console.error(error);
                });
            }
        });
    }

    // Handle copy JSON sample
    const copyBtn = document.getElementById('copyJsonBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const jsonSample = document.getElementById('jsonSample');
            if (jsonSample) {
                const jsonText = jsonSample.textContent;
                const button = this;
                function copyToClipboardFallback(text) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        notifySuccess('✅ Đã sao chép vào clipboard!');
                    } catch (err) {
                        notifyError('Không thể sao chép. Vui lòng sao chép thủ công.');
                        console.error('Copy failed:', err);
                    }
                    document.body.removeChild(textArea);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(jsonText).then(() => {
                        notifySuccess('✅ Đã sao chép mẫu JSON!');
                    }).catch(() => {
                        copyToClipboardFallback(jsonText);
                    });
                } else {
                    copyToClipboardFallback(jsonText);
                }
            }
        });
    }

    // Handle edit question
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-question');
        if (btn) {
            const topicIndex = btn.getAttribute('data-topic-index');
            const index = btn.getAttribute('data-index');
            const flatIndex = btn.getAttribute('data-flat-index');

            // Hide the view modal
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('questionModal' + flatIndex));
            if (viewModal) viewModal.hide();

            // Get question data from questionsData
            const questionsData = window.questionsData || [];
            const topicData = questionsData[topicIndex];
            if (!topicData || !topicData.questions || !topicData.questions[index]) return;
            const q = topicData.questions[index];

            // Populate edit form
            document.getElementById('edit_topic_index').value = topicIndex;
            document.getElementById('edit_index').value = index;
            document.getElementById('edit_topic').value = topicData.topic;
            document.getElementById('edit_question_text').value = q.question;

            // Populate existing image (if any)
            const editHidden = document.getElementById('edit_question_image');
            const editFileInput = document.getElementById('edit_question_image_upload');
            const editPreviewWrap = document.getElementById('edit_question_image_preview');
            const editRemoveBtn = document.getElementById('edit_question_image_remove');
            const existingImage = q.image || '';
            if (editHidden) editHidden.value = existingImage;
            if (editFileInput) editFileInput.value = '';
            if (editPreviewWrap) {
                const editImg = editPreviewWrap.querySelector('img');
                if (editImg) {
                    if (existingImage) editImg.src = existingImage;
                    editPreviewWrap.style.display = existingImage ? 'block' : 'none';
                }
            }
            if (editRemoveBtn) editRemoveBtn.style.display = existingImage ? 'inline-block' : 'none';

            // Select question type & sync sections
            const editTypeValue = ['single', 'multiple', 'true_false_multiple', 'essay'].includes(q.type) ? q.type : 'single';
            const editTypeRadio = document.querySelector('input[name="edit_question_type"][value="' + editTypeValue + '"]');
            if (editTypeRadio) editTypeRadio.checked = true;
            qbSyncTypeUI('edit_');
            document.getElementById('edit_question_level').value = QB_LEVEL_LABELS_STD[q.level] ? q.level : 'NB';

            // Reset dynamic sections
            document.getElementById('editTfItemsContainer').innerHTML = '';
            qbFillSubqContainer('edit_', []);
            const editPointsInput = document.getElementById('edit_essay_points');
            const editSuggestedInput = document.getElementById('edit_essay_suggested_answer');

            if (editTypeValue === 'true_false_multiple') {
                qbFillTfContainer('edit_', q.items || []);
            } else if (editTypeValue === 'essay') {
                if (editPointsInput) editPointsInput.value = (q.points !== undefined && q.points !== null) ? q.points : '2.0';
                if (editSuggestedInput) editSuggestedInput.value = q.suggested_answer || '';
                qbFillSubqContainer('edit_', q.sub_questions || []);
            } else {
                // Populate MCQ options
                const optionsContainer = document.getElementById('editOptionsContainer');
                optionsContainer.innerHTML = '';
                const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const correctIndices = Array.isArray(q.correct) ? q.correct : [q.correct];
                (q.options || []).forEach((opt, idx) => {
                    const letter = letters[idx % 26];
                    const isCorrect = correctIndices.includes(idx);
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'input-group mb-2';
                    optionDiv.innerHTML = `
                        <span class="input-group-text">${letter}</span>
                        <input type="text" name="edit_options[]" class="form-control mcq-option-input" placeholder="Đáp án ${letter}" value="${opt}" required>
                        <input type="checkbox" name="edit_correct[]" value="${idx}" class="form-check-input ms-2" title="Đáp án đúng" ${isCorrect ? 'checked' : ''}>
                        ${idx >= 4 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-option">X</button>' : ''}
                    `;
                    optionsContainer.appendChild(optionDiv);
                });
            }

            // Populate lessons for the selected topic
            populateEditLessons(topicData.topic);

            // Set lesson after populating options
            document.getElementById('edit_lesson').value = topicData.lesson;

            // Show edit modal
            const editModal = new bootstrap.Modal(document.getElementById('editQuestionModal'));
            editModal.show();
        }
    });

    // Handle edit topic selection
    const editTopicSelect = document.getElementById('edit_topic');
    if (editTopicSelect) {
        editTopicSelect.addEventListener('change', function() {
            const editNewTopicDiv = document.getElementById('editNewTopicDiv');
            const editLessonSelect = document.getElementById('edit_lesson');
            if (this.value === 'new_topic') {
                if (editNewTopicDiv) editNewTopicDiv.style.display = 'block';
                const editNewTopicName = document.getElementById('edit_new_topic_name');
                if (editNewTopicName) editNewTopicName.required = true;
                if (editLessonSelect) editLessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
            } else {
                if (editNewTopicDiv) editNewTopicDiv.style.display = 'none';
                const editNewTopicName = document.getElementById('edit_new_topic_name');
                if (editNewTopicName) editNewTopicName.required = false;
                // Populate lessons for selected topic
                populateEditLessons(this.value);
            }
        });
    }

    // Handle edit lesson selection
    const editLessonSelect = document.getElementById('edit_lesson');
    if (editLessonSelect) {
        editLessonSelect.addEventListener('change', function() {
            const editNewLessonDiv = document.getElementById('editNewLessonDiv');
            if (this.value === 'new_lesson') {
                if (editNewLessonDiv) editNewLessonDiv.style.display = 'block';
                const editNewLessonName = document.getElementById('edit_new_lesson_name');
                if (editNewLessonName) editNewLessonName.required = true;
            } else {
                if (editNewLessonDiv) editNewLessonDiv.style.display = 'none';
                const editNewLessonName = document.getElementById('edit_new_lesson_name');
                if (editNewLessonName) editNewLessonName.required = false;
            }
        });
    }

    function populateEditLessons(selectedTopic) {
        const editLessonSelect = document.getElementById('edit_lesson');
        if (editLessonSelect) {
            editLessonSelect.innerHTML = '<option value="">-- Chọn bài học --</option><option value="new_lesson">+ Tạo bài học mới</option>';
            const questionsData = window.questionsData || [];
            const lessons = [];
            questionsData.forEach(item => {
                if (item.topic === selectedTopic) {
                    lessons.push(item.lesson);
                }
            });
            lessons.forEach(lesson => {
                const option = document.createElement('option');
                option.value = lesson;
                option.textContent = lesson;
                editLessonSelect.appendChild(option);
            });
        }
    }

    // Handle adding more options in edit modal
    const editAddOptionBtn = document.getElementById('editAddOptionBtn');
    if (editAddOptionBtn) {
        let editOptionIndex = 4; // Start from E
        editAddOptionBtn.addEventListener('click', function() {
            const container = document.getElementById('editOptionsContainer');
            if (container) {
                const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const letter = letters[editOptionIndex % 26];

                const optionDiv = document.createElement('div');
                optionDiv.className = 'input-group mb-2';
                optionDiv.innerHTML = `
                    <span class="input-group-text">${letter}</span>
                    <input type="text" name="edit_options[]" class="form-control" placeholder="Đáp án ${letter}" required>
                    <input type="checkbox" name="edit_correct[]" value="${editOptionIndex}" class="form-check-input ms-2" title="Đáp án đúng">
                    <button type="button" class="btn btn-sm btn-danger remove-edit-option">X</button>
                `;
                container.appendChild(optionDiv);
                editOptionIndex++;
            }
        });
    }

    // Handle removing options in edit modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-edit-option')) {
            e.target.closest('.input-group').remove();
        }
    });

    // Handle edit form submission
    const editQuestionForm = document.getElementById('editQuestionForm');
    if (editQuestionForm) {
        editQuestionForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const questionType = data.edit_question_type;

            if (questionType === 'true_false_multiple') {
                const res = qbCollectTfItems('edit_');
                if (res.error) { notifyWarning(res.error); return; }
                formData.set('edit_items_json', JSON.stringify(res.items));
            } else if (questionType === 'essay') {
                const pts = parseFloat(data.edit_essay_points);
                if (!pts || pts <= 0) {
                    notifyWarning('Vui lòng nhập điểm tối đa hợp lệ cho câu tự luận!');
                    return;
                }
                const subRes = qbCollectSubq('edit_');
                if (subRes.error) { notifyWarning(subRes.error); return; }
                if (subRes.subs.length) {
                    formData.set('edit_sub_questions_json', JSON.stringify(subRes.subs));
                } else {
                    formData.delete('edit_sub_questions_json');
                }
            } else {
                // MCQ: validate at least one correct answer is selected
                const correctAnswers = formData.getAll('edit_correct[]');
                if (correctAnswers.length === 0) {
                    notifyWarning('Vui lòng chọn ít nhất một đáp án đúng!');
                    return;
                }
                if (questionType === 'single' && correctAnswers.length > 1) {
                    notifyWarning('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                    return;
                }
                if (questionType === 'multiple' && correctAnswers.length < 2) {
                    notifyWarning('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                    return;
                }
            }

            // Show loading
            const submitBtn = document.querySelector('button[form="editQuestionForm"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Đang lưu...';
            submitBtn.disabled = true;

            // Send data
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    notifySuccess('Câu hỏi đã được cập nhật thành công!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notifyError('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                notifyError('Có lỗi xảy ra khi cập nhật câu hỏi!');
                console.error(error);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Function to download Excel template
function downloadExcelTemplate() {
    window.location.href = '?action=download_excel_template';
}
