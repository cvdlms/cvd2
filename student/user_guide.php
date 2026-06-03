<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
}

$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';

$title = 'Hướng Dẫn Sử Dụng - Học Sinh - CVD';
include '../includes/student_header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-0"><i class="bi bi-book me-2"></i>Hướng Dẫn Sử Dụng - Học Sinh</h2>
                <p class="text-white-50 mb-0 mt-2">Tài liệu hướng dẫn đầy đủ các chức năng dành cho học sinh</p>
            </div>
            <a href="dashboard.php" class="btn btn-light btn-lg">
                <i class="bi bi-house-door me-2"></i>Trang Chủ
            </a>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card guide-nav-card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-compass me-2"></i>Điều Hướng Nhanh</h5>
                    <div class="quick-links">
                        <a href="#login" class="quick-link-btn"><i class="bi bi-box-arrow-in-right"></i> Đăng Nhập</a>
                        <a href="#exams" class="quick-link-btn"><i class="bi bi-pencil-square"></i> Làm Bài Thi</a>
                        <a href="#assignments" class="quick-link-btn"><i class="bi bi-journal-check"></i> Nộp Bài Tập</a>
                        <a href="#scores" class="quick-link-btn"><i class="bi bi-graph-up"></i> Xem Điểm</a>
                        <a href="#results" class="quick-link-btn"><i class="bi bi-clipboard-data"></i> Kết Quả</a>
                        <a href="#settings" class="quick-link-btn"><i class="bi bi-gear"></i> Cài Đặt</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guide Content -->
    <div class="row">
        <div class="col-12">
            <div class="accordion" id="guideAccordion">
                
                <!-- Đăng Nhập -->
                <div class="accordion-item guide-accordion-item" id="login">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogin">
                            <i class="bi bi-box-arrow-in-right me-3 fs-5"></i>
                            <div>
                                <strong>1. Đăng Nhập Hệ Thống</strong>
                                <p class="mb-0 small text-muted">Truy cập và đăng nhập vào tài khoản học sinh</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseLogin" class="accordion-collapse collapse show" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Các Bước Đăng Nhập</h5>
                                <ol class="guide-steps">
                                    <li>
                                        <strong>Truy cập trang đăng nhập học sinh:</strong>
                                        <p>Mở trình duyệt và truy cập trang đăng nhập học sinh (URL do giáo viên/quản trị viên cung cấp)</p>
                                    </li>
                                    <li>
                                        <strong>Nhập thông tin:</strong>
                                        <ul>
                                            <li>Mã học sinh: Mã số được cấp (VD: HS001)</li>
                                            <li>Mật khẩu: Mật khẩu cá nhân</li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong>Nhấn nút "Đăng Nhập"</strong>
                                        <p>Hệ thống sẽ chuyển đến trang Dashboard</p>
                                    </li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Lưu ý:</strong> Nếu quên mật khẩu, liên hệ giáo viên chủ nhiệm để được hỗ trợ.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Làm Bài Thi -->
                <div class="accordion-item guide-accordion-item" id="exams">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExams">
                            <i class="bi bi-pencil-square me-3 fs-5"></i>
                            <div>
                                <strong>2. Làm Bài Thi Trắc Nghiệm</strong>
                                <p class="mb-0 small text-muted">Tham gia và hoàn thành bài kiểm tra</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseExams" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Bắt Đầu Làm Bài</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Làm Bài Thi</strong></li>
                                    <li>Chọn môn học và đề thi cần làm</li>
                                    <li>Đọc kỹ:
                                        <ul>
                                            <li>Số câu hỏi</li>
                                            <li>Thời gian làm bài</li>
                                            <li>Yêu cầu và lưu ý</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-success">Bắt Đầu Làm Bài</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Trong Quá Trình Làm Bài</h5>
                                <ol class="guide-steps">
                                    <li>Đọc kỹ đề và chọn đáp án (A, B, C, hoặc D)</li>
                                    <li>Theo dõi thời gian đếm ngược ở góc trên</li>
                                    <li>Sử dụng nút điều hướng:
                                        <ul>
                                            <li><span class="badge bg-secondary">Câu Trước</span> - Quay lại câu trước</li>
                                            <li><span class="badge bg-primary">Câu Tiếp</span> - Sang câu kế tiếp</li>
                                        </ul>
                                    </li>
                                    <li>Kiểm tra lại các câu chưa trả lời (có dấu cảnh báo)</li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Nộp Bài</h5>
                                <ol class="guide-steps">
                                    <li>Sau khi hoàn thành, nhấn <span class="badge bg-danger">Nộp Bài</span></li>
                                    <li>Xác nhận nộp bài trong popup</li>
                                    <li>Xem kết quả ngay lập tức</li>
                                </ol>

                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Quan trọng:</strong> Hết thời gian bài thi sẽ tự động nộp. Không thể làm lại sau khi đã nộp.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nộp Bài Tập -->
                <div class="accordion-item guide-accordion-item" id="assignments">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAssignments">
                            <i class="bi bi-journal-check me-3 fs-5"></i>
                            <div>
                                <strong>3. Nộp Bài Tập</strong>
                                <p class="mb-0 small text-muted">Làm và nộp bài tập được giao</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseAssignments" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Xem Bài Tập Được Giao</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Bài Tập</strong></li>
                                    <li>Xem danh sách bài tập:
                                        <ul>
                                            <li>Tiêu đề</li>
                                            <li>Môn học</li>
                                            <li>Hạn nộp</li>
                                            <li>Trạng thái (Chưa nộp/Đã nộp)</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-info">Xem</span> để đọc yêu cầu chi tiết</li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Nộp Bài Làm</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn <span class="badge bg-success">Nộp Bài</span></li>
                                    <li>Nhập nội dung bài làm vào ô text</li>
                                    <li>Upload file (nếu cần):
                                        <ul>
                                            <li>File Word, Excel, PDF, PowerPoint</li>
                                            <li>Kích thước tối đa: 10MB</li>
                                        </ul>
                                    </li>
                                    <li>Thêm hình ảnh (nếu cần):
                                        <ul>
                                            <li>Chọn từ máy tính hoặc chụp ảnh trực tiếp</li>
                                            <li>Kích thước tối đa: 5MB/ảnh</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-success">Nộp Bài</span> để hoàn tất</li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Lưu ý:</strong> Chỉ được nộp 1 lần và không thể sửa sau khi đã nộp. Kiểm tra kỹ trước khi nộp!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Xem Điểm -->
                <div class="accordion-item guide-accordion-item" id="scores">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseScores">
                            <i class="bi bi-graph-up me-3 fs-5"></i>
                            <div>
                                <strong>4. Xem Điểm Số</strong>
                                <p class="mb-0 small text-muted">Tra cứu điểm các môn học</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseScores" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Tra Cứu Điểm</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Xem Điểm</strong></li>
                                    <li>Chọn môn học cần xem</li>
                                    <li>Hệ thống hiển thị:
                                        <ul>
                                            <li>Điểm miệng</li>
                                            <li>Điểm 15 phút</li>
                                            <li>Điểm 1 tiết</li>
                                            <li>Điểm giữa kỳ</li>
                                            <li>Điểm cuối kỳ</li>
                                            <li>Điểm trung bình môn</li>
                                        </ul>
                                    </li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Xem Bảng Điểm Tổng Hợp</h5>
                                <ol class="guide-steps">
                                    <li>Chọn <strong>Tất Cả Môn</strong></li>
                                    <li>Xem điểm của tất cả các môn học</li>
                                    <li>Xem điểm trung bình chung</li>
                                </ol>

                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Cập nhật:</strong> Điểm được cập nhật ngay sau khi giáo viên nhập hoặc chấm bài.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Xem Kết Quả -->
                <div class="accordion-item guide-accordion-item" id="results">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResults">
                            <i class="bi bi-clipboard-data me-3 fs-5"></i>
                            <div>
                                <strong>5. Xem Kết Quả Bài Thi</strong>
                                <p class="mb-0 small text-muted">Xem lại chi tiết bài thi đã làm</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseResults" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Xem Lịch Sử Bài Thi</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Kết Quả Thi</strong></li>
                                    <li>Chọn môn học</li>
                                    <li>Xem danh sách các bài thi đã làm với:
                                        <ul>
                                            <li>Tên đề thi</li>
                                            <li>Ngày làm bài</li>
                                            <li>Điểm số</li>
                                            <li>Số câu đúng/tổng số câu</li>
                                        </ul>
                                    </li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Xem Chi Tiết Bài Làm</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn <span class="badge bg-info">Xem Chi Tiết</span></li>
                                    <li>Xem từng câu hỏi với:
                                        <ul>
                                            <li>Đề bài</li>
                                            <li>Đáp án đã chọn</li>
                                            <li>Đáp án đúng</li>
                                            <li>Đúng/Sai</li>
                                        </ul>
                                    </li>
                                    <li>Học từ những câu sai để cải thiện</li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Mẹo:</strong> Xem lại các câu sai để rút kinh nghiệm cho lần thi sau.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cài Đặt -->
                <div class="accordion-item guide-accordion-item" id="settings">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings">
                            <i class="bi bi-gear me-3 fs-5"></i>
                            <div>
                                <strong>6. Cài Đặt Tài Khoản</strong>
                                <p class="mb-0 small text-muted">Quản lý thông tin cá nhân</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseSettings" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Đổi Mật Khẩu</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn vào tên của bạn ở góc trên</li>
                                    <li>Chọn <strong>Đổi Mật Khẩu</strong></li>
                                    <li>Nhập:
                                        <ul>
                                            <li>Mật khẩu hiện tại</li>
                                            <li>Mật khẩu mới</li>
                                            <li>Xác nhận mật khẩu mới</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-primary">Cập Nhật</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Đăng Xuất</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn vào tên tài khoản</li>
                                    <li>Chọn <strong>Đăng Xuất</strong></li>
                                </ol>

                                <div class="alert alert-warning">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <strong>Bảo mật:</strong> Không chia sẻ mật khẩu cho bạn bè. Đổi mật khẩu ngay nếu nghi ngờ bị lộ.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card tips-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Mẹo Học Tập Hiệu Quả</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="tip-item">
                                <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Quản Lý Thời Gian</h6>
                                <p>Lập kế hoạch học tập, dành thời gian đều cho các môn. Hoàn thành bài tập trước deadline.</p>
                            </div>
                            <div class="tip-item">
                                <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Luyện Tập Thường Xuyên</h6>
                                <p>Làm bài thi thử nhiều lần để quen với format và cải thiện tốc độ.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="tip-item">
                                <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Xem Lại Sai Lầm</h6>
                                <p>Sau mỗi bài thi, xem lại các câu sai để rút kinh nghiệm.</p>
                            </div>
                            <div class="tip-item">
                                <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Hỏi Khi Chưa Hiểu</h6>
                                <p>Đừng ngại hỏi giáo viên hoặc bạn bè khi gặp khó khăn.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="row mt-4 mb-5">
        <div class="col-12">
            <div class="card faq-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-question-circle me-2"></i>Câu Hỏi Thường Gặp</h5>
                </div>
                <div class="card-body">
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Tôi có thể làm lại bài thi không?</h6>
                        <p>Mỗi đề thi chỉ được làm 1 lần duy nhất. Hãy chuẩn bị kỹ trước khi bắt đầu.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Nếu mất kết nối Internet khi đang làm bài thì sao?</h6>
                        <p>Hệ thống tự động lưu tiến độ. Kết nối lại và tiếp tục làm bài trong thời gian còn lại.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Làm sao để trở thành học sinh Premium?</h6>
                        <p>Liên hệ với giáo viên hoặc quản trị viên để được nâng cấp tài khoản.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Tôi có thể xem điểm của bạn khác không?</h6>
                        <p>Không, bạn chỉ có thể xem điểm của chính mình để bảo vệ quyền riêng tư.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.guide-nav-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.quick-links {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.quick-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid #e9ecef;
    border-radius: 10px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.quick-link-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.guide-accordion-item {
    border: none;
    margin-bottom: 15px;
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.guide-accordion-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.guide-accordion-item .accordion-button {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: none;
    padding: 1.5rem;
    font-size: 1.1rem;
}

.guide-accordion-item .accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: none;
}

.guide-accordion-item .accordion-button:focus {
    box-shadow: none;
}

.guide-accordion-item .accordion-button::after {
    filter: brightness(0) invert(0.5);
}

.guide-accordion-item .accordion-button:not(.collapsed)::after {
    filter: brightness(0) invert(1);
}

.guide-content {
    padding: 1rem 0;
}

.guide-steps {
    margin-left: 0;
    padding-left: 1.5rem;
}

.guide-steps li {
    margin-bottom: 1rem;
    line-height: 1.8;
}

.guide-steps li strong {
    color: #667eea;
}

.guide-steps ul {
    margin-top: 0.5rem;
}

code {
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 4px;
    color: #d63384;
    font-size: 0.9em;
}

.tips-card,
.faq-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.tips-card .card-header,
.faq-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    padding: 1rem 1.5rem;
}

.tip-item,
.faq-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.tip-item:last-child,
.faq-item:last-child {
    border-bottom: none;
}

.tip-item h6,
.faq-item h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.tip-item p,
.faq-item p {
    color: #6c757d;
    margin-bottom: 0;
    padding-left: 1.8rem;
}

.alert {
    border-radius: 10px;
    border: none;
}

.badge {
    padding: 4px 10px;
    font-weight: 500;
}
</style>

<?php include '../includes/student_footer.php'; ?>
