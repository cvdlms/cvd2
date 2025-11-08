<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

$title = 'Nhận Xét Excel - CVD';
include '../includes/teacher_header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title mb-0">📊 Nhận Xét Từ File Excel</h2>
                </div>
                <div class="card-body">
                    <p class="mb-4">Tải lên file Excel (.xlsx) chứa danh sách học sinh với điểm trung bình ở cột L. Hệ thống sẽ tự động thêm nhận xét vào cột M dựa trên điểm số và cho phép tải về file hoàn chỉnh.</p>

                    <form id="uploadForm">
                        <input type="hidden" name="comment_rules" id="commentRulesInput">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Chọn file Excel (.xlsx):</label>
                            <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx" required>
                            <div class="form-text">File phải có nhiều sheet, mỗi sheet là một lớp, cột L chứa điểm trung bình.</div>
                        </div>
                        <button type="submit" class="btn btn-success" id="processBtn">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Xử Lý và Tải Về
                        </button>
                    </form>

                    <!-- Loading Modal -->
                    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <h5>Đang xử lý file Excel...</h5>
                                    <p class="text-muted">Vui lòng đợi trong giây lát. Quá trình này có thể mất vài phút tùy thuộc vào kích thước file. Hãy đợi đến khi tập tin tải xuống thành công!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comment Rules Modal -->
                    <div class="modal fade" id="commentRulesModal" tabindex="-1" aria-labelledby="commentRulesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="commentRulesModalLabel">Cấu Hình Nhận Xét</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3">Chỉnh sửa các mốc điểm và nhận xét tương ứng:</p>
                                    <div id="rulesContainer">
                                        <!-- Rules will be loaded here -->
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" id="addRuleBtn">
                                        <i class="bi bi-plus-circle"></i> Thêm Mốc Điểm
                                    </button>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                    <button type="button" class="btn btn-primary" id="saveRulesBtn">
                                        <i class="bi bi-check-circle"></i> Lưu Thay Đổi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#commentRulesModal">
                        <i class="bi bi-gear"></i> Cấu Hình Nhận Xét
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const processBtn = document.getElementById('processBtn');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

    // Show loading modal when form is submitted
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Get current rules from localStorage and set to hidden input
        const rules = JSON.parse(localStorage.getItem('commentRules')) || [
            { min: 0, max: 3.4, comment: 'Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn.' },
            { min: 3.5, max: 4.9, comment: 'Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn.' },
            { min: 5.0, max: 6.4, comment: 'Có cố gắng, cần phát huy thêm.' },
            { min: 6.5, max: 7.9, comment: 'Siêng học, cần phát huy thêm.' },
            { min: 8.0, max: 10.0, comment: 'Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh.' }
        ];

        // Show loading modal
        processBtn.disabled = true;
        processBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';
        loadingModal.show();

        // Prepare form data
        const formData = new FormData(uploadForm);
        formData.set('comment_rules', JSON.stringify(rules));

        // Send AJAX request
        fetch('process_excel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success - trigger download
                const link = document.createElement('a');
                link.href = data.downloadUrl;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Show success message
                alert('File đã được xử lý thành công! Dung lượng: ' + data.fileSize);
            } else {
                // Error
                alert('Lỗi xử lý file: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi kết nối: ' + error.message);
        })
        .finally(() => {
            // Always hide modal and reset button
            loadingModal.hide();
            processBtn.disabled = false;
            processBtn.innerHTML = '<i class="bi bi-file-earmark-spreadsheet"></i> Xử Lý và Tải Về';
        });
    });

    // Load comment rules
    loadCommentRules();

    // Add rule button
    document.getElementById('addRuleBtn').addEventListener('click', function() {
        addRule();
    });

    // Save rules button
    document.getElementById('saveRulesBtn').addEventListener('click', function() {
        saveCommentRules();
    });
});

function loadCommentRules() {
    // Load rules from localStorage or use defaults
    let rules = JSON.parse(localStorage.getItem('commentRules')) || [
        { min: 0, max: 3.4, comment: 'Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn.' },
        { min: 3.5, max: 4.9, comment: 'Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn.' },
        { min: 5.0, max: 6.4, comment: 'Có cố gắng, cần phát huy thêm.' },
        { min: 6.5, max: 7.9, comment: 'Siêng học, cần phát huy thêm.' },
        { min: 8.0, max: 10.0, comment: 'Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh.' }
    ];

    const container = document.getElementById('rulesContainer');
    container.innerHTML = '';

    rules.forEach((rule, index) => {
        const ruleDiv = document.createElement('div');
        ruleDiv.className = 'rule-item mb-3 p-3 border rounded';
        ruleDiv.innerHTML = `
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Điểm từ:</label>
                    <input type="number" class="form-control min-score" value="${rule.min}" step="0.1" min="0" max="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Đến:</label>
                    <input type="number" class="form-control max-score" value="${rule.max}" step="0.1" min="0" max="10">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Nhận xét:</label>
                    <input type="text" class="form-control comment-text" value="${rule.comment}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-rule" onclick="removeRule(this)">
                        Xóa
                    </button>
                </div>
            </div>
        `;
        container.appendChild(ruleDiv);
    });
}

function addRule() {
    const container = document.getElementById('rulesContainer');
    const ruleDiv = document.createElement('div');
    ruleDiv.className = 'rule-item mb-3 p-3 border rounded';
    ruleDiv.innerHTML = `
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Điểm từ:</label>
                <input type="number" class="form-control min-score" value="0" step="0.1" min="0" max="10">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến:</label>
                <input type="number" class="form-control max-score" value="10" step="0.1" min="0" max="10">
            </div>
            <div class="col-md-7">
                <label class="form-label">Nhận xét:</label>
                <input type="text" class="form-control comment-text" value="Nhận xét mới">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-rule" onclick="removeRule(this)">
                    Xóa
                </button>
            </div>
        </div>
    `;
    container.appendChild(ruleDiv);
}

function removeRule(button) {
    button.closest('.rule-item').remove();
}

function saveCommentRules() {
    const rules = [];
    const ruleItems = document.querySelectorAll('.rule-item');

    ruleItems.forEach(item => {
        const min = parseFloat(item.querySelector('.min-score').value);
        const max = parseFloat(item.querySelector('.max-score').value);
        const comment = item.querySelector('.comment-text').value.trim();

        if (!isNaN(min) && !isNaN(max) && comment) {
            rules.push({ min, max, comment });
        }
    });

    // Sort rules by min score
    rules.sort((a, b) => a.min - b.min);

    // Save to localStorage
    localStorage.setItem('commentRules', JSON.stringify(rules));

    // Show success message
    alert('Đã lưu cấu hình nhận xét thành công!');

    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('commentRulesModal')).hide();
}
</script>

<?php include '../includes/footer.php'; ?>
