<?php
// Set unique session name for Teacher/Admin
session_name('CVD_TEACHER_SESSION');
session_start();

include_once '../includes/session_check.php';
include_once '../includes/premium_helper.php';

$title = 'Hướng Dẫn Chức Năng Hệ Thống - EDUVN EXAMS';
include '../includes/teacher_header.php';
?>

<style>
/* Modern Styling for User Guide Page */
.guide-hero {
    background: var(--grad-accent, linear-gradient(135deg, #6366F1 0%, #4F46E5 100%));
    border-radius: var(--radius-lg, 20px);
    padding: 2.5rem 2rem;
    color: #ffffff;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md, 0 10px 25px rgba(79, 70, 229, 0.2));
    position: relative;
    overflow: hidden;
}

.guide-hero::after {
    content: '';
    position: absolute;
    right: -40px;
    bottom: -50px;
    width: 260px;
    height: 260px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    pointer-events: none;
}

.guide-search-wrapper {
    position: relative;
    max-width: 480px;
}

.guide-search-wrapper input {
    padding-left: 2.75rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    backdrop-filter: blur(10px);
    height: 46px;
    transition: all 0.25 ease;
}

.guide-search-wrapper input::placeholder {
    color: rgba(255, 255, 255, 0.75);
}

.guide-search-wrapper input:focus {
    background: rgba(255, 255, 255, 0.25);
    border-color: #ffffff;
    color: #ffffff;
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.2);
}

.guide-search-wrapper i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
}

.filter-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 2rem;
}

.filter-btn {
    border: 1px solid var(--border, #E4E7F0);
    border-radius: 20px;
    padding: 0.5rem 1.25rem;
    color: var(--muted-strong, #475569);
    font-weight: 500;
    background: #ffffff;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--accent, #4F46E5);
    color: #ffffff;
    border-color: var(--accent, #4F46E5);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.feature-card {
    border: 1px solid var(--border, #E4E7F0);
    border-radius: var(--radius-lg, 16px);
    background: #ffffff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    box-shadow: var(--shadow-xs, 0 1px 3px rgba(0, 0, 0, 0.05));
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md, 0 12px 24px -10px rgba(15, 23, 42, 0.12));
    border-color: var(--accent-mist, #E5E3FB);
}

.feature-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.icon-indigo { background: var(--accent-light, #EEEDFD); color: var(--accent, #4F46E5); }
.icon-success { background: var(--success-light, #D9F5EC); color: var(--success, #10B981); }
.icon-warning { background: var(--warning-light, #FDEED2); color: var(--warning, #F59E0B); }
.icon-info { background: var(--info-light, #DDF1FC); color: var(--info, #0EA5E9); }
.icon-violet { background: var(--violet-light, #EDE5FD); color: var(--violet, #8B5CF6); }

.feature-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
}

.badge-indigo { background: var(--accent-light, #EEEDFD); color: var(--accent, #4F46E5); }
.badge-success { background: var(--success-light, #D9F5EC); color: var(--success, #10B981); }
.badge-warning { background: var(--warning-light, #FDEED2); color: var(--warning, #F59E0B); }
.badge-info { background: var(--info-light, #DDF1FC); color: var(--info, #0EA5E9); }
.badge-violet { background: var(--violet-light, #EDE5FD); color: var(--violet, #8B5CF6); }

.feature-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ink, #1E293B);
    margin-bottom: 0.5rem;
}

.feature-purpose {
    color: var(--muted-strong, #475569);
    font-size: 0.925rem;
    line-height: 1.6;
    flex-grow: 1;
    margin-bottom: 1.25rem;
}

.feature-highlights {
    background: #F8FAFC;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.25rem;
    border: 1px dashed var(--border, #E4E7F0);
}

.feature-highlights-title {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted, #64748B);
    margin-bottom: 0.4rem;
}

.feature-highlights ul {
    margin: 0;
    padding-left: 1.2rem;
    font-size: 0.85rem;
    color: var(--ink, #1E293B);
}

.feature-highlights li {
    margin-bottom: 0.25rem;
}

.feature-highlights li:last-child {
    margin-bottom: 0;
}

.feature-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid var(--border-soft, #EEF0F7);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.btn-open-feature {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--accent, #4F46E5);
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-open-feature:hover {
    color: var(--accent-dark, #3730A3);
    transform: translateX(3px);
}
</style>

<div class="container-fluid px-4 py-3">
    <!-- Hero Header -->
    <div class="guide-hero">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-3 mb-lg-0">
                <h2 class="fw-bold mb-2"><i class="bi bi-compass me-2"></i>Hướng Dẫn Chức Năng Hệ Thống</h2>
                <p class="mb-0 text-white-50 fs-6">
                    Tổng hợp mục đích và ý nghĩa ứng dụng của tất cả các chức năng trên cổng Giáo Viên EDUVN EXAMS.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="guide-search-wrapper ms-lg-auto">
                    <i class="bi bi-search"></i>
                    <input type="text" id="guideSearchInput" class="form-control" placeholder="Tìm kiếm chức năng..." autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="filter-nav">
        <button type="button" class="filter-btn active" data-filter="all">
            <i class="bi bi-grid-fill"></i> Tất Cả Chức Năng
        </button>
        <button type="button" class="filter-btn" data-filter="overview">
            <i class="bi bi-speedometer2"></i> Tổng Quan & Học Sinh
        </button>
        <button type="button" class="filter-btn" data-filter="exams">
            <i class="bi bi-file-earmark-text"></i> Ngân Hàng & Đề Kiểm Tra
        </button>
        <button type="button" class="filter-btn" data-filter="teaching">
            <i class="bi bi-easel"></i> Dạy Học & Bài Tập
        </button>
        <button type="button" class="filter-btn" data-filter="tools">
            <i class="bi bi-tools"></i> Tiện Ích & Hỗ Trợ
        </button>
    </div>

    <!-- Feature Grid -->
    <div class="row g-4" id="featureGrid">

        <!-- 1. Bảng Điều Khiển -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="overview">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-indigo">
                        <i class="bi bi-grid-1x2-fill"></i>
                    </div>
                    <span class="feature-badge badge-indigo">Tổng quan</span>
                </div>
                <h3 class="feature-title">Bảng Điều Khiển</h3>
                <p class="feature-purpose">
                    Cung cấp góc nhìn tổng quan và theo dõi thời gian thực về tình hình học tập, số lượng học sinh, đề thi, bài tập và các hoạt động vừa diễn ra trên hệ thống.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Thống kê các chỉ số hoạt động quan trọng</li>
                        <li>Theo dõi nhật ký hoạt động của học sinh</li>
                        <li>Phím tắt truy cập nhanh các chức năng thường dùng</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Trang chủ giáo viên</span>
                    <a href="teacher.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 2. Quản Lý Học Sinh -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="overview">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-info">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span class="feature-badge badge-info">Học sinh</span>
                </div>
                <h3 class="feature-title">Quản Lý Học Sinh</h3>
                <p class="feature-purpose">
                    Quản lý danh sách học sinh theo từng lớp học, theo dõi danh sách thành viên, cập nhật thông tin cá nhân và tài khoản truy cập của học sinh.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Phân loại và quản lý học sinh theo lớp giảng dạy</li>
                        <li>Tra cứu nhanh thông tin và tài khoản học sinh</li>
                        <li>Theo dõi trạng thái tham gia lớp học</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Lớp học & Học sinh</span>
                    <a href="manage_students.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 3. Kết Quả Học Tập -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="overview">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-success">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="feature-badge badge-success">Báo cáo & Điểm</span>
                </div>
                <h3 class="feature-title">Kết Quả Học Tập</h3>
                <p class="feature-purpose">
                    Tổng hợp, thống kê và phân tích kết quả học tập của học sinh qua các kỳ kiểm tra; hỗ trợ đánh giá năng lực và xuất báo cáo bảng điểm tổng hợp.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Bảng điểm tổng hợp theo từng lớp và môn học</li>
                        <li>Phân tích phổ điểm và tỷ lệ đạt yêu cầu</li>
                        <li>Xuất báo cáo dữ liệu bảng điểm học sinh</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Thống kê điểm số</span>
                    <a href="manage_result.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 4. Ngân Hàng Câu Hỏi -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="exams">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-violet">
                        <i class="bi bi-collection-fill"></i>
                    </div>
                    <span class="feature-badge badge-violet">Ngân hàng đề</span>
                </div>
                <h3 class="feature-title">Ngân Hàng Câu Hỏi</h3>
                <p class="feature-purpose">
                    Lưu trữ và quản lý tập trung toàn bộ câu hỏi trắc nghiệm và tự luận; phân loại theo môn học, bài học, khối lớp và mức độ tư duy nhận thức.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Phân loại 4 mức độ tư duy (Nhận biết, Thông hiểu, Vận dụng, Vận dụng cao)</li>
                        <li>Nhập hàng loạt câu hỏi từ file Word / Excel có sẵn</li>
                        <li>Lưu trữ kho câu hỏi dùng chung lâu dài</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Kho dữ liệu câu hỏi</span>
                    <a href="question_bank.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 5. Quản Lý Đề Thi -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="exams">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-indigo">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <span class="feature-badge badge-indigo">Đề kiểm tra</span>
                </div>
                <h3 class="feature-title">Quản Lý Đề Thi</h3>
                <p class="feature-purpose">
                    Quản lý danh sách các đề kiểm tra đã tạo; cấu hình tham số phòng thi như thời gian làm bài, mật khẩu, thời gian mở/đóng đề và chế độ xem lại bài làm.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Tùy chỉnh linh hoạt cài đặt thời gian và bảo mật thi</li>
                        <li>Trộn câu hỏi và đáp án tự động chống gian lận</li>
                        <li>Theo dõi trạng thái đề thi (Đang mở / Đã đóng)</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Danh sách đề thi</span>
                    <a href="my_exams.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 6. Tạo Đề Kiểm Tra -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="exams">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-warning">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <span class="feature-badge badge-warning">Soạn đề</span>
                </div>
                <h3 class="feature-title">Tạo Đề Kiểm Tra</h3>
                <p class="feature-purpose">
                    Công cụ khởi tạo đề kiểm tra trực tuyến nhanh chóng bằng cách rút câu hỏi tự động từ ngân hàng theo ma trận đặc tả hoặc tải trực tiếp từ file Word/Excel.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Sinh đề thi ngẫu nhiên theo ma trận cấu trúc</li>
                        <li>Tạo bài kiểm tra trực tiếp từ tài liệu Word/Excel</li>
                        <li>Xem trước và xem lại ma trận câu hỏi trước khi phát đề</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Khởi tạo đề thi mới</span>
                    <a href="exam_creation.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 7. Bản Mô Tả Mức Độ Đánh Giá -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="exams">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-info">
                        <i class="bi bi-clipboard-data-fill"></i>
                    </div>
                    <span class="feature-badge badge-info">Chuẩn đánh giá</span>
                </div>
                <h3 class="feature-title">Bản Mô Tả Mức Độ Đánh Giá</h3>
                <p class="feature-purpose">
                    Định nghĩa và quản lý khung chuẩn kiến thức, kỹ năng và các mức độ nhận thức theo từng chủ đề bài học nhằm định hướng xây dựng đề thi bám sát chương trình.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Chuẩn hóa yêu cầu cần đạt theo từng chương bài</li>
                        <li>Căn cứ rõ ràng cho việc thiết lập ma trận đề thi</li>
                        <li>Đảm bảo tính chính xác trong đánh giá năng lực học sinh</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Khung đặc tả kiến thức</span>
                    <a href="knowledge_assessment.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 8. Ma Trận Đặc Tả -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="exams">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-violet">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <span class="feature-badge badge-violet">Ma trận đề</span>
                </div>
                <h3 class="feature-title">Ma Trận Đặc Tả</h3>
                <p class="feature-purpose">
                    Xây dựng ma trận đề thi chuyên nghiệp theo tỷ lệ phần trăm số câu hỏi và trọng số điểm cho từng mức độ tư duy trước khi sinh đề.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Cân bằng tỷ lệ mức độ nhận thức trong đề kiểm tra</li>
                        <li>Quy định chi tiết số câu hỏi trắc nghiệm / tự luận</li>
                        <li>Đảm bảo đề thi đạt chuẩn quy định của bộ môn</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Cấu trúc ma trận</span>
                    <a href="matrix_builder.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 9. Slide Bài Giảng -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="teaching">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-success">
                        <i class="bi bi-easel-fill"></i>
                    </div>
                    <span class="feature-badge badge-success">Bài giảng</span>
                </div>
                <h3 class="feature-title">Slide Bài Giảng</h3>
                <p class="feature-purpose">
                    Biên soạn, quản lý và trình chiếu các bài giảng điện tử trực tuyến; hỗ trợ nhập và chuyển đổi từ file PowerPoint (PPTX) sang slide tương tác sinh động.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Trình chiếu trực tiếp bài giảng mượt mà trên web</li>
                        <li>Chuyển đổi bài giảng từ PowerPoint sẵn có</li>
                        <li>Tương tác giảng dạy hiện đại tại lớp học</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Bài giảng tương tác</span>
                    <a href="slides.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 10. Quản Lý Bài Tập -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="teaching">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-indigo">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <span class="feature-badge badge-indigo">Bài tập</span>
                </div>
                <h3 class="feature-title">Quản Lý Bài Tập</h3>
                <p class="feature-purpose">
                    Giao bài tập về nhà hoặc bài tập tự luyện cho các lớp học, quy định thời hạn nộp bài, tiếp nhận bài làm (file Word, PDF, Ảnh) và thực hiện chấm điểm, trả nhận xét.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Giao bài tập tự luận / thực hành cho nhiều lớp</li>
                        <li>Tiếp nhận tệp đính kèm bài làm đa dạng định dạng</li>
                        <li>Chấm điểm và phản hồi nhận xét trực tiếp cho học sinh</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Bài tập & Chấm điểm</span>
                    <a href="manage_assignments.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 11. Kế Hoạch Bài Dạy -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="teaching">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-info">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <span class="feature-badge badge-info">Giáo án</span>
                </div>
                <h3 class="feature-title">Kế Hoạch Bài Dạy</h3>
                <p class="feature-purpose">
                    Lưu trữ và quản lý hệ thống giáo án, kế hoạch bài dạy theo đúng khung chuẩn quy định của ngành giáo dục; hỗ trợ xem trước và xuất file Word/PDF.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Quản lý giáo án tập trung theo từng khối lớp và môn học</li>
                        <li>Xem trước nội dung chi tiết kế hoạch tiết học</li>
                        <li>Tải về và xuất file tài liệu phục vụ công tác chuyên môn</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Kế hoạch giảng dạy</span>
                    <a href="lesson_plans.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 12. Vòng Quay May Mắn -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="tools">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-warning">
                        <i class="bi bi-disc-fill"></i>
                    </div>
                    <span class="feature-badge badge-warning">Tương tác</span>
                </div>
                <h3 class="feature-title">Vòng Quay May Mắn</h3>
                <p class="feature-purpose">
                    Công cụ tương tác tại lớp học giúp lựa chọn ngẫu nhiên học sinh trả lời câu hỏi hoặc tham gia hoạt động, tạo không khí học tập sinh động và công bằng.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Gọi tên học sinh ngẫu nhiên theo danh sách lớp</li>
                        <li>Hiệu ứng quay thưởng trực quan hào hứng</li>
                        <li>Tăng tính tương tác sôi nổi trong giờ học</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Công cụ lớp học</span>
                    <a href="lucky_wheel.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 13. Điều Khiển Từ Xa -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="tools">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-violet">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <span class="feature-badge badge-violet">Trình chiếu</span>
                </div>
                <h3 class="feature-title">Điều Khiển Từ Xa</h3>
                <p class="feature-purpose">
                    Cho phép giáo viên kết nối điện thoại thông minh hoặc thiết bị di động làm tay điều khiển từ xa để chuyển trang slide bài giảng mà không cần đứng cạnh máy tính.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Kết nối nhanh chóng qua mã QR Code</li>
                        <li>Chuyển trang slide và điều khiển giảng dạy linh hoạt</li>
                        <li>Giúp giáo viên di chuyển tự do trong không gian lớp học</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Remote bài giảng</span>
                    <a href="remote_control.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 14. Nhận Xét VnEdu -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="tools">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-success">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                    </div>
                    <span class="feature-badge badge-success">Tiện ích sổ sách</span>
                </div>
                <h3 class="feature-title">Nhận Xét VnEdu</h3>
                <p class="feature-purpose">
                    Tự động hóa quá trình sinh lời nhận xét học sinh định kỳ và xuất file dữ liệu nhận xét tương thích chuẩn để đồng bộ nhanh chóng lên hệ thống VnEdu.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Kho mẫu lời nhận xét phong phú theo từng học lực</li>
                        <li>Tự động Ghép tên học sinh và kết quả học tập</li>
                        <li>Xuất file Excel đúng định dạng mẫu của phần mềm VnEdu</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Đồng bộ sổ điểm</span>
                    <a href="excel_comments.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 15. Hệ Thống Thông Báo -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="tools">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-info">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <span class="feature-badge badge-info">Tin tức</span>
                </div>
                <h3 class="feature-title">Hệ Thống Thông Báo</h3>
                <p class="feature-purpose">
                    Cập nhật tức thời các thông báo hệ thống như học sinh hoàn thành nộp bài tập, kết quả bài thi mới, cập nhật từ ban quản trị và các sự kiện quan trọng.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Nhận thông tin cảnh báo và nhắc nhở thời gian thực</li>
                        <li>Theo dõi tiến độ nộp bài của học sinh lập tức</li>
                        <li>Lưu trữ lịch sử thông báo hệ thống đầy đủ</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Nhật ký tin nhắn</span>
                    <a href="notifications.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 16. Nâng Cấp Premium -->
        <div class="col-12 col-md-6 col-xl-4 feature-item" data-category="tools">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon icon-indigo">
                        <i class="bi bi-stars"></i>
                    </div>
                    <span class="feature-badge badge-indigo">Gói dịch vụ</span>
                </div>
                <h3 class="feature-title">Nâng Cấp Premium</h3>
                <p class="feature-purpose">
                    Quản lý thời hạn tài khoản và kích hoạt các đặc quyền Premium cao cấp nhằm mở khóa toàn bộ tính năng và giới hạn lưu trữ trên hệ thống.
                </p>
                <div class="feature-highlights">
                    <div class="feature-highlights-title">Giá trị sử dụng chính</div>
                    <ul>
                        <li>Mở khóa trọn bộ công cụ nâng cao (Remote, Slide AI, Import)</li>
                        <li>Tăng hạn mức lưu trữ kho câu hỏi và bài giảng</li>
                        <li>Được ưu tiên hỗ trợ kỹ thuật và cập nhật phiên bản mới</li>
                    </ul>
                </div>
                <div class="feature-footer">
                    <span class="small text-muted">Đặc quyền tài khoản</span>
                    <a href="premium_activation.php" class="btn-open-feature">Mở chức năng <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

    </div>

    <!-- Empty State Message when no search matches -->
    <div id="noResultsMessage" class="text-center py-5 d-none">
        <i class="bi bi-search text-muted fs-1 mb-3 d-block"></i>
        <h5 class="fw-bold text-secondary">Không tìm thấy chức năng phù hợp</h5>
        <p class="text-muted small">Vui lòng thử từ khóa tìm kiếm khác hoặc đổi danh mục lọc.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('guideSearchInput');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const featureItems = document.querySelectorAll('.feature-item');
    const noResultsMsg = document.getElementById('noResultsMessage');

    let currentCategory = 'all';
    let currentQuery = '';

    function filterFeatures() {
        let visibleCount = 0;

        featureItems.forEach(item => {
            const category = item.getAttribute('data-category');
            const title = item.querySelector('.feature-title').textContent.toLowerCase();
            const purpose = item.querySelector('.feature-purpose').textContent.toLowerCase();
            const highlights = item.querySelector('.feature-highlights').textContent.toLowerCase();

            const matchesCategory = (currentCategory === 'all' || category === currentCategory);
            const matchesQuery = !currentQuery || 
                title.includes(currentQuery) || 
                purpose.includes(currentQuery) || 
                highlights.includes(currentQuery);

            if (matchesCategory && matchesQuery) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });

        if (visibleCount === 0) {
            noResultsMsg.classList.remove('d-none');
        } else {
            noResultsMsg.classList.add('d-none');
        }
    }

    // Filter by Tab click
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-filter');
            filterFeatures();
        });
    });

    // Search input typing
    searchInput.addEventListener('input', function() {
        currentQuery = this.value.trim().toLowerCase();
        filterFeatures();
    });
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
