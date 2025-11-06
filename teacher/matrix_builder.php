<?php
session_start();
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

// Load user data for fullname
$users = json_decode(file_get_contents('../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

$title = 'Xây Dựng Ma Trận Đề Kiểm Tra - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4 text-center">Xây Dựng Ma Trận Đề Kiểm Tra Tin Học THCS</h1>
                <p class="lead text-center mb-5">Nhập thông tin chủ đề, đơn vị kiến thức, số tiết và mức độ đánh giá để tạo ma trận đề kiểm tra.</p>
            </div>
        </div>
        <form id="matrixForm" method="POST" action="matrix_generate.php">
            <div id="topicsContainer">
                <!-- Topics will be added here -->
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-success" id="addTopicBtn">Thêm Chủ Đề</button>
                    <button type="submit" class="btn btn-primary">Tạo Ma Trận</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let topicCount = 0;

    document.getElementById('addTopicBtn').addEventListener('click', function() {
        topicCount++;
        const topicHtml = `
            <div class="card mb-4 topic-card" data-topic-id="${topicCount}">
                <div class="card-header">
                    <h5>Chủ Đề ${topicCount}</h5>
                    <button type="button" class="btn btn-danger btn-sm remove-topic">Xóa Chủ Đề</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tên Chủ Đề</label>
                        <input type="text" class="form-control" name="topics[${topicCount}][name]" required>
                    </div>
                    <div class="units-container">
                        <!-- Units will be added here -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm add-unit" data-topic="${topicCount}">Thêm Đơn Vị Kiến Thức</button>
                </div>
            </div>
        `;
        document.getElementById('topicsContainer').insertAdjacentHTML('beforeend', topicHtml);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-unit')) {
            const topicId = e.target.getAttribute('data-topic');
            const unitsContainer = e.target.previousElementSibling;
            const unitCount = unitsContainer.children.length + 1;
            const unitHtml = `
                <div class="card mb-3 unit-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Đơn Vị Kiến Thức</label>
                                <input type="text" class="form-control" name="topics[${topicId}][units][${unitCount}][name]" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Số Tiết</label>
                                <input type="number" class="form-control" name="topics[${topicId}][units][${unitCount}][periods]" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mức Độ</label>
                                <div class="levels-container">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="topics[${topicId}][units][${unitCount}][levels][]" value="Biết" checked>
                                        <label class="form-check-label">Biết</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="topics[${topicId}][units][${unitCount}][levels][]" value="Hiểu" checked>
                                        <label class="form-check-label">Hiểu</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="topics[${topicId}][units][${unitCount}][levels][]" value="VD" checked>
                                        <label class="form-check-label">VD</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm remove-unit">Xóa Đơn Vị</button>
                    </div>
                </div>
            `;
            unitsContainer.insertAdjacentHTML('beforeend', unitHtml);
        }

        if (e.target.classList.contains('remove-topic')) {
            e.target.closest('.topic-card').remove();
        }

        if (e.target.classList.contains('remove-unit')) {
            e.target.closest('.unit-card').remove();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
