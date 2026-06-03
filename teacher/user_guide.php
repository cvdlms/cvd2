<?php
// Trang này không cần đăng nhập - Public access
$title = 'Hướng Dẫn Sử Dụng - CVD LMS';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            margin-bottom: 2rem;
        }
        .guide-nav-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .quick-link-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .quick-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-0"><i class="bi bi-book me-2"></i>Hướng Dẫn Sử Dụng - CVD LMS</h2>
                <p class="text-white-50 mb-0 mt-2">Tài liệu hướng dẫn đầy đủ các chức năng của hệ thống</p>
            </div>
            <a href="../index.html" class="btn btn-light btn-lg">
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
                        <a href="#exams" class="quick-link-btn"><i class="bi bi-file-earmark-text"></i> Quản Lý Đề Thi</a>
                        <a href="#assignments" class="quick-link-btn"><i class="bi bi-journal-check"></i> Bài Tập</a>
                        <a href="#grading" class="quick-link-btn"><i class="bi bi-clipboard-check"></i> Chấm Điểm</a>
                        <a href="#scores" class="quick-link-btn"><i class="bi bi-graph-up"></i> Quản Lý Điểm</a>
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
                                <p class="mb-0 small text-muted">Truy cập và đăng nhập vào tài khoản giáo viên</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseLogin" class="accordion-collapse collapse show" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Các Bước Đăng Nhập</h5>
                                <ol class="guide-steps">
                                    <li>
                                        <strong>Truy cập trang đăng nhập:</strong>
                                        <p>Mở trình duyệt và truy cập trang đăng nhập của hệ thống (URL do quản trị viên cung cấp)</p>
                                    </li>
                                    <li>
                                        <strong>Nhập thông tin đăng nhập:</strong>
                                        <ul>
                                            <li>Tên đăng nhập: Tài khoản giáo viên của bạn</li>
                                            <li>Mật khẩu: Mật khẩu đã được cấp</li>
                                        </ul>
                                    </li>
                                    <li>
                                        <strong>Nhấn nút "Đăng Nhập"</strong>
                                        <p>Hệ thống sẽ chuyển hướng đến trang chủ giáo viên</p>
                                    </li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Lưu ý:</strong> Nếu quên mật khẩu, vui lòng liên hệ quản trị viên để được hỗ trợ.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quản Lý Đề Thi -->
                <div class="accordion-item guide-accordion-item" id="exams">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExams">
                            <i class="bi bi-file-earmark-text me-3 fs-5"></i>
                            <div>
                                <strong>2. Quản Lý Đề Thi</strong>
                                <p class="mb-0 small text-muted">Tạo, chỉnh sửa và quản lý đề thi trắc nghiệm</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseExams" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Tạo Đề Thi Mới</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Quản Lý Đề Thi</strong></li>
                                    <li>Nhấn nút <span class="badge bg-success">+ Tạo Đề Thi Mới</span></li>
                                    <li>Điền thông tin:
                                        <ul>
                                            <li><strong>Môn học:</strong> Chọn môn từ danh sách</li>
                                            <li><strong>Tiêu đề:</strong> Tên đề thi (VD: "Kiểm tra 15 phút - Chương 1")</li>
                                            <li><strong>Mô tả:</strong> Nội dung, yêu cầu của đề thi</li>
                                            <li><strong>Thời gian:</strong> Số phút làm bài</li>
                                            <li><strong>Số câu hỏi:</strong> Tổng số câu hỏi trong đề</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-primary">Lưu</span> để tạo đề thi</li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Thêm Câu Hỏi</h5>
                                <ol class="guide-steps">
                                    <li>Trong danh sách đề thi, nhấn nút <span class="badge bg-info">Quản Lý Câu Hỏi</span></li>
                                    <li>Nhấn <span class="badge bg-success">+ Thêm Câu Hỏi</span></li>
                                    <li>Nhập nội dung câu hỏi và 4 đáp án (A, B, C, D)</li>
                                    <li>Chọn đáp án đúng</li>
                                    <li>Nhấn <span class="badge bg-primary">Lưu Câu Hỏi</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Import Câu Hỏi từ Excel</h5>
                                <ol class="guide-steps">
                                    <li>Chuẩn bị file Excel theo mẫu có sẵn</li>
                                    <li>Trong trang quản lý câu hỏi, nhấn <span class="badge bg-warning">Import Excel</span></li>
                                    <li>Chọn file Excel và upload</li>
                                    <li>Hệ thống sẽ tự động import các câu hỏi</li>
                                </ol>

                                <div class="alert alert-success">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Mẹo:</strong> Sử dụng chức năng import Excel để thêm nhiều câu hỏi nhanh chóng.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quản Lý Bài Tập -->
                <div class="accordion-item guide-accordion-item" id="assignments">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAssignments">
                            <i class="bi bi-journal-check me-3 fs-5"></i>
                            <div>
                                <strong>3. Quản Lý Bài Tập</strong>
                                <p class="mb-0 small text-muted">Giao bài tập và theo dõi tiến độ nộp bài</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseAssignments" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Tạo Bài Tập Mới</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Quản Lý Bài Tập</strong></li>
                                    <li>Nhấn nút <span class="badge bg-success">+ Tạo Bài Tập Mới</span></li>
                                    <li>Điền thông tin:
                                        <ul>
                                            <li><strong>Tiêu đề:</strong> Tên bài tập</li>
                                            <li><strong>Môn học:</strong> Chọn môn</li>
                                            <li><strong>Lớp học:</strong> Chọn một hoặc nhiều lớp (giữ Ctrl để chọn nhiều)</li>
                                            <li><strong>Mô tả/Yêu cầu:</strong> Nội dung chi tiết bài tập</li>
                                            <li><strong>Hạn nộp:</strong> Ngày giờ deadline</li>
                                            <li><strong>Điểm tối đa:</strong> Thang điểm</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-primary">Tạo Bài Tập</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Chấm Bài Nộp</h5>
                                <ol class="guide-steps">
                                    <li>Trong danh sách bài tập, nhấn vào số bài nộp</li>
                                    <li>Xem danh sách học sinh đã nộp</li>
                                    <li>Nhấn <span class="badge bg-info">Xem</span> để xem chi tiết bài làm</li>
                                    <li>Nhập điểm và nhận xét</li>
                                    <li>Nhấn <span class="badge bg-success">Lưu Điểm</span></li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Lưu ý:</strong> Học sinh có thể nộp file Word, Excel, PDF và hình ảnh.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chấm Điểm Bài Thi -->
                <div class="accordion-item guide-accordion-item" id="grading">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGrading">
                            <i class="bi bi-clipboard-check me-3 fs-5"></i>
                            <div>
                                <strong>4. Chấm Điểm & Xem Kết Quả Thi</strong>
                                <p class="mb-0 small text-muted">Xem chi tiết bài thi của học sinh</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseGrading" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Xem Kết Quả Bài Thi</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Quản Lý Đề Thi</strong></li>
                                    <li>Chọn đề thi cần xem kết quả</li>
                                    <li>Nhấn <span class="badge bg-info">Xem Kết Quả</span></li>
                                    <li>Xem danh sách học sinh đã làm bài với:
                                        <ul>
                                            <li>Điểm số</li>
                                            <li>Thời gian làm bài</li>
                                            <li>Số câu đúng/sai</li>
                                        </ul>
                                    </li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Xem Chi Tiết Bài Làm</h5>
                                <ol class="guide-steps">
                                    <li>Trong danh sách kết quả, nhấn vào tên học sinh</li>
                                    <li>Xem từng câu trả lời:
                                        <ul>
                                            <li>Câu hỏi</li>
                                            <li>Đáp án học sinh chọn</li>
                                            <li>Đáp án đúng</li>
                                            <li>Đúng/Sai</li>
                                        </ul>
                                    </li>
                                </ol>

                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Tự động:</strong> Hệ thống tự động chấm điểm bài trắc nghiệm ngay khi học sinh nộp bài.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quản Lý Điểm -->
                <div class="accordion-item guide-accordion-item" id="scores">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseScores">
                            <i class="bi bi-graph-up me-3 fs-5"></i>
                            <div>
                                <strong>5. Quản Lý Điểm</strong>
                                <p class="mb-0 small text-muted">Nhập và quản lý điểm số học sinh</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseScores" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Nhập Điểm Thủ Công</h5>
                                <ol class="guide-steps">
                                    <li>Vào menu <strong>Quản Lý Điểm</strong></li>
                                    <li>Chọn môn học và lớp</li>
                                    <li>Chọn loại điểm (Miệng, 15 phút, 1 tiết, Giữa kỳ, Cuối kỳ)</li>
                                    <li>Nhập điểm cho từng học sinh</li>
                                    <li>Nhấn <span class="badge bg-primary">Lưu Điểm</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Xem Bảng Điểm</h5>
                                <ol class="guide-steps">
                                    <li>Chọn môn học và lớp cần xem</li>
                                    <li>Hệ thống hiển thị bảng điểm đầy đủ với:
                                        <ul>
                                            <li>Tất cả cột điểm</li>
                                            <li>Điểm trung bình</li>
                                            <li>Xếp loại</li>
                                        </ul>
                                    </li>
                                    <li>Có thể xuất Excel hoặc in bảng điểm</li>
                                </ol>

                                <div class="alert alert-info">
                                    <i class="bi bi-calculator me-2"></i>
                                    <strong>Tự động:</strong> Điểm trung bình và xếp loại được tính tự động theo quy định.
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
                                <strong>6. Cài Đặt & Tài Khoản</strong>
                                <p class="mb-0 small text-muted">Quản lý thông tin cá nhân và đổi mật khẩu</p>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseSettings" class="accordion-collapse collapse" data-bs-parent="#guideAccordion">
                        <div class="accordion-body">
                            <div class="guide-content">
                                <h5 class="text-primary mb-3">Đổi Mật Khẩu</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn vào tên tài khoản ở góc trên bên phải</li>
                                    <li>Chọn <strong>Đổi Mật Khẩu</strong></li>
                                    <li>Nhập:
                                        <ul>
                                            <li>Mật khẩu hiện tại</li>
                                            <li>Mật khẩu mới</li>
                                            <li>Xác nhận mật khẩu mới</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn <span class="badge bg-primary">Đổi Mật Khẩu</span></li>
                                </ol>

                                <h5 class="text-primary mb-3 mt-4">Đăng Xuất</h5>
                                <ol class="guide-steps">
                                    <li>Nhấn vào tên tài khoản</li>
                                    <li>Chọn <strong>Đăng Xuất</strong></li>
                                </ol>

                                <div class="alert alert-warning">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <strong>Bảo mật:</strong> Nên đổi mật khẩu định kỳ và không chia sẻ mật khẩu cho người khác.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card faq-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-question-circle me-2"></i>Câu Hỏi Thường Gặp (FAQ)</h5>
                </div>
                <div class="card-body">
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Làm thế nào để trở thành giáo viên Premium?</h6>
                        <p>Liên hệ với quản trị viên để được nâng cấp tài khoản lên Premium và sử dụng đầy đủ các tính năng.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Tôi có thể giao bài tập cho nhiều lớp cùng lúc không?</h6>
                        <p>Có, khi tạo bài tập, bạn có thể chọn nhiều lớp để giao cùng một bài tập.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Học sinh có thể xem lại bài thi của mình không?</h6>
                        <p>Có, học sinh có thể xem lại kết quả, điểm số và chi tiết các câu trả lời sau khi làm bài.</p>
                    </div>
                    <div class="faq-item">
                        <h6><i class="bi bi-chevron-right text-primary me-2"></i>Làm sao để liên hệ hỗ trợ kỹ thuật?</h6>
                        <p>Liên hệ quản trị viên hệ thống hoặc gửi email đến bộ phận hỗ trợ.</p>
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

.faq-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.faq-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    padding: 1rem 1.5rem;
}

.faq-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-item h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

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

    <!-- Footer -->
    <footer class="text-center text-white py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 CVD Learning Management System</p>
            <p class="mb-0 small opacity-75">
                <a href="../index.html" class="text-white text-decoration-none">Trang chủ</a> | 
                <a href="../login.php" class="text-white text-decoration-none">Đăng nhập</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
