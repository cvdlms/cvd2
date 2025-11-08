// question_bank.js - JavaScript for question_bank.php

document.addEventListener('DOMContentLoaded', function() {
    if (typeof MathJax !== 'undefined' && MathJax.typeset) {
        MathJax.typeset();
    }

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

            // Validate at least one correct answer is selected
            const correctAnswers = formData.getAll('correct[]');
            if (correctAnswers.length === 0) {
                alert('Vui lòng chọn ít nhất một đáp án đúng!');
                return;
            }

            // Validate question type and correct answers
            const questionType = data.question_type;
            if (questionType === 'single' && correctAnswers.length > 1) {
                alert('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                return;
            }
            if (questionType === 'multiple' && correctAnswers.length < 2) {
                alert('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                return;
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
                    alert('Câu hỏi đã được thêm thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra khi thêm câu hỏi!');
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
        if (e.target.classList.contains('delete-question')) {
            e.stopPropagation();
            const topicIndex = e.target.getAttribute('data-topic-index');
            const index = e.target.getAttribute('data-index');
            currentDeleteData = { topicIndex, index };

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    });

    // Handle confirm delete
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentDeleteData) {
                const { topicIndex, index } = currentDeleteData;
                // Send delete request
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_question',
                        topic_index: topicIndex,
                        index: index
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Close modal
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                        deleteModal.hide();
                        // Show success toast
                        const toast = new bootstrap.Toast(document.getElementById('successToast'));
                        document.getElementById('toastMessage').textContent = 'Câu hỏi đã được xóa thành công!';
                        toast.show();
                        // Reload after a short delay
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Lỗi: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('Có lỗi xảy ra khi xóa câu hỏi!');
                    console.error(error);
                });
            }
        });
    }

    // Handle delete all questions
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa TẤT CẢ câu hỏi? Hành động này không thể hoàn tác!')) {
                // Send delete all request
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_all_questions'
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Tất cả câu hỏi đã được xóa thành công!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('Có lỗi xảy ra khi xóa tất cả câu hỏi!');
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
                const button = this; // capture the button
                // Fallback copy function for compatibility
                function copyToClipboard(text) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        // Change button text temporarily to indicate success
                        const originalText = button.textContent;
                        button.textContent = '✅ Đã sao chép!';
                        setTimeout(() => {
                            button.textContent = originalText;
                        }, 2000);
                    } catch (err) {
                        alert('Không thể sao chép. Vui lòng sao chép thủ công.');
                        console.error('Copy failed:', err);
                    }
                    document.body.removeChild(textArea);
                }
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(jsonText).then(() => {
                        // Change button text temporarily to indicate success
                        const originalText = button.textContent;
                        button.textContent = '✅ Đã sao chép!';
                        setTimeout(() => {
                            button.textContent = originalText;
                        }, 2000);
                    }).catch(() => {
                        // Fallback to old method
                        copyToClipboard(jsonText);
                    });
                } else {
                    // Fallback to old method
                    copyToClipboard(jsonText);
                }
            }
        });
    }

    // Handle edit question
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-question')) {
            const topicIndex = e.target.getAttribute('data-topic-index');
            const index = e.target.getAttribute('data-index');
            const flatIndex = e.target.getAttribute('data-flat-index');

            // Hide the view modal
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('questionModal' + flatIndex));
            if (viewModal) viewModal.hide();

            // Get question data from questionsData
            const questionsData = window.questionsData || [];
            const topicData = questionsData[topicIndex];
            const q = topicData.questions[index];

            // Populate edit form
            document.getElementById('edit_topic_index').value = topicIndex;
            document.getElementById('edit_index').value = index;
            document.getElementById('edit_topic').value = topicData.topic;
            document.getElementById('edit_question_text').value = q.question;
            if (q.type === 'single') {
                document.getElementById('edit_single_choice').checked = true;
            } else {
                document.getElementById('edit_multiple_choice').checked = true;
            }
            document.getElementById('edit_question_level').value = q.level;

            // Populate options
            const optionsContainer = document.getElementById('editOptionsContainer');
            optionsContainer.innerHTML = '';
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const correctIndices = Array.isArray(q.correct) ? q.correct : [q.correct];
            q.options.forEach((opt, idx) => {
                const letter = letters[idx % 26];
                const isCorrect = correctIndices.includes(idx);
                const optionDiv = document.createElement('div');
                optionDiv.className = 'input-group mb-2';
                optionDiv.innerHTML = `
                    <span class="input-group-text">${letter}</span>
                    <input type="text" name="edit_options[]" class="form-control" placeholder="Đáp án ${letter}" value="${opt}" required>
                    <input type="checkbox" name="edit_correct[]" value="${idx}" class="form-check-input ms-2" title="Đáp án đúng" ${isCorrect ? 'checked' : ''}>
                    ${idx >= 4 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-option">X</button>' : ''}
                `;
                optionsContainer.appendChild(optionDiv);
            });

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

            // Validate at least one correct answer is selected
            const correctAnswers = formData.getAll('edit_correct[]');
            if (correctAnswers.length === 0) {
                alert('Vui lòng chọn ít nhất một đáp án đúng!');
                return;
            }

            // Validate question type and correct answers
            const questionType = data.edit_question_type;
            if (questionType === 'single' && correctAnswers.length > 1) {
                alert('Câu hỏi trắc nghiệm chỉ được chọn một đáp án đúng!');
                return;
            }
            if (questionType === 'multiple' && correctAnswers.length < 2) {
                alert('Câu hỏi trắc nghiệm nhiều đáp án phải chọn ít nhất hai đáp án đúng!');
                return;
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
                    alert('Câu hỏi đã được cập nhật thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra khi cập nhật câu hỏi!');
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
