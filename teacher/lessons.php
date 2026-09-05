<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('CVD_TEACHER_SESSION');
    session_start();
}

include_once __DIR__ . '/../includes/session_check.php';
include_once __DIR__ . '/../includes/common_functions.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$username = $_SESSION['username'];

// Load users data
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true) ?: [];
$fullname = $users[$username]['fullname'] ?? $username;

// Load assigned subjects and classes
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$teacherSubjects = file_exists($teacherSubjectsFile) ? (json_decode(file_get_contents($teacherSubjectsFile), true) ?: []) : [];
$allSubjects = file_exists($subjectsFile) ? (json_decode(file_get_contents($subjectsFile), true) ?: []) : [];
$teacherClasses = file_exists($teacherClassesFile) ? (json_decode(file_get_contents($teacherClassesFile), true) ?: []) : [];
$allClasses = file_exists($classesFile) ? (json_decode(file_get_contents($classesFile), true) ?: []) : [];

$assignedSubjectIds = $teacherSubjects[$username] ?? [];
$assignedClassIds = $teacherClasses[$username] ?? [];

$assignedSubjects = array_values(array_filter($allSubjects, function($s) use ($assignedSubjectIds) {
    return empty($assignedSubjectIds) || in_array($s['id'], $assignedSubjectIds);
}));

// System config for default semester
$configFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($configFile) ? (json_decode(file_get_contents($configFile), true) ?: []) : [];
$defaultSemester = $systemConfig['semester']['current'] ?? 'hk1';

$title = 'Quản Lý Bài Học Giảng Dạy - EDUVN EXAMS';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.lesson-card {
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    background: #fff;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.lesson-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.12);
    border-color: rgba(37, 99, 235, 0.3);
}
.lesson-card-header {
    padding: 18px 20px 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.lesson-card-body {
    padding: 10px 20px 20px 20px;
    flex-grow: 1;
}
.lesson-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    line-height: 1.4;
}
.lesson-card-desc {
    font-size: 0.88rem;
    color: #64748b;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.lesson-card-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
}
.lesson-card-footer {
    padding: 12px 20px 18px 20px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}
.badge-grade {
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 600;
    border-radius: 6px;
    padding: 4px 10px;
}
.badge-subject {
    background: #ecfdf5;
    color: #065f46;
    font-weight: 600;
    border-radius: 6px;
    padding: 4px 10px;
}
.badge-semester {
    background: #fef3c7;
    color: #92400e;
    font-weight: 600;
    border-radius: 6px;
    padding: 4px 10px;
}
.badge-count {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    border-radius: 6px;
    padding: 4px 10px;
}
.btn-present-tv {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff !important;
    font-weight: 700;
    border-radius: 10px;
    padding: 9px 16px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-present-tv:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
}
.q-bank-item {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
    transition: background 0.15s;
    background: #fff;
}
.q-bank-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.q-bank-item.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}
.selected-q-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
    background: #fff;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.selected-q-num {
    background: #3b82f6;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.level-badge-nb { background: #dcfce7; color: #166534; font-weight: bold; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
.level-badge-th { background: #e0f2fe; color: #075985; font-weight: bold; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
.level-badge-vd { background: #fef9c3; color: #854d0e; font-weight: bold; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
.level-badge-vdc { background: #fee2e2; color: #991b1b; font-weight: bold; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
</style>

<div class="main-content">
    <div class="container py-4 mb-5">

        <!-- Header Section -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 eduvn-reveal">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="teacher.php">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bài Học (Chiếu TV)</li>
                    </ol>
                </nav>
                <h2 class="fw-800 text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-tv text-primary"></i>
                    Quản Lý Bài Học Giảng Dạy
                </h2>
                <p class="text-muted mb-0">Tạo danh sách câu hỏi chọn lọc từ Ngân hàng câu hỏi để trình chiếu slide giảng dạy trên TV phòng học.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-lg rounded-3 shadow-sm px-4 fw-bold d-inline-flex align-items-center gap-2" id="btnCreateLesson">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <span>Tạo Bài Học Mới</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 eduvn-reveal">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light" id="searchLessonInput" placeholder="Tìm kiếm bài học theo tiêu đề...">
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <select class="form-select bg-light" id="filterGrade">
                            <option value="">-- Tất cả khối lớp --</option>
                            <option value="khoi6">Khối 6</option>
                            <option value="khoi7">Khối 7</option>
                            <option value="khoi8">Khối 8</option>
                            <option value="khoi9">Khối 9</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-6">
                        <select class="form-select bg-light" id="filterSubject">
                            <option value="">-- Tất cả môn học --</option>
                            <?php foreach ($assignedSubjects as $subj): ?>
                                <option value="<?php echo $subj['id']; ?>"><?php echo htmlspecialchars($subj['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lessons Container -->
        <div id="lessonsLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="text-muted mt-2">Đang tải danh sách bài học...</p>
        </div>

        <div id="lessonsGrid" class="row g-4 row-cols-1 row-cols-md-2 row-cols-xl-3 d-none">
            <!-- Rendered by JS -->
        </div>

        <div id="lessonsEmpty" class="card border-0 shadow-sm rounded-4 p-5 text-center d-none">
            <div class="mb-3">
                <i class="bi bi-tv text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
            </div>
            <h4 class="fw-bold text-dark">Chưa có bài học nào</h4>
            <p class="text-muted mx-auto" style="max-width: 500px;">Hãy tạo bài học đầu tiên bằng cách chọn câu hỏi từ Ngân hàng câu hỏi để chiếu lên TV hỗ trợ giảng dạy trên lớp.</p>
            <div class="mt-3">
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-bold" onclick="document.getElementById('btnCreateLesson').click();">
                    <i class="bi bi-plus-lg me-1"></i> Tạo bài học ngay
                </button>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: Tạo / Sửa Bài Học -->
<div class="modal fade" id="lessonModal" tabindex="-1" aria-labelledby="lessonModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="lessonModalLabel">
                    <i class="bi bi-journal-text text-primary"></i>
                    <span id="lessonModalTitleText">Tạo Bài Học Giảng Dạy Mới</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="lessonForm">
                    <input type="hidden" id="lessonId" value="">

                    <!-- Step 1: Thông tin chung -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tiêu đề bài học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="lessonTitle" placeholder="Ví dụ: Bài 1: Thông tin và thu nhận thông tin" required>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label fw-bold">Khối lớp <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="lessonGrade" required>
                                <option value="khoi6">Khối 6</option>
                                <option value="khoi7">Khối 7</option>
                                <option value="khoi8">Khối 8</option>
                                <option value="khoi9">Khối 9</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label fw-bold">Học kỳ <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="lessonSemester" required>
                                <option value="hk1" <?php echo $defaultSemester === 'hk1' ? 'selected' : ''; ?>>Học kì 1</option>
                                <option value="hk2" <?php echo $defaultSemester === 'hk2' ? 'selected' : ''; ?>>Học kì 2</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Môn học <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="lessonSubject" required>
                                <?php foreach ($assignedSubjects as $subj): ?>
                                    <option value="<?php echo $subj['id']; ?>" data-name="<?php echo htmlspecialchars($subj['name']); ?>">
                                        <?php echo htmlspecialchars($subj['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô tả / Ghi chú cho bài dạy (tùy chọn)</label>
                            <textarea class="form-control" id="lessonDescription" rows="2" placeholder="Nhập mục tiêu, dặn dò hoặc ghi chú bài giảng..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Step 2: Danh sách câu hỏi trong bài -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-0 fs-5 d-flex align-items-center gap-2">
                                <i class="bi bi-collection-fill text-warning"></i>
                                Danh Sách Câu Hỏi Trong Bài Học
                                <span class="badge bg-primary rounded-pill ms-2" id="selectedCountBadge">0 câu</span>
                            </h6>
                            <small class="text-muted">Các câu hỏi này sẽ được chiếu lần lượt trên TV theo thứ tự bên dưới.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-warning fw-bold d-inline-flex align-items-center gap-2 shadow-sm" id="btnOpenBankModal">
                                <i class="bi bi-bank2"></i>
                                <span>Chọn Câu Hỏi Từ Ngân Hàng</span>
                            </button>
                        </div>
                    </div>

                    <!-- Selected Questions Container -->
                    <div id="selectedQuestionsEmpty" class="border border-dashed rounded-4 p-5 text-center bg-light">
                        <i class="bi bi-plus-circle text-primary fs-1 d-block mb-2"></i>
                        <h6 class="fw-bold text-dark">Chưa có câu hỏi nào trong bài học</h6>
                        <p class="text-muted small mb-3">Bấm vào nút <strong>"Chọn Câu Hỏi Từ Ngân Hàng"</strong> ở trên để đưa câu hỏi vào bài giảng.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-3 fw-bold" onclick="document.getElementById('btnOpenBankModal').click();">
                            <i class="bi bi-search me-1"></i> Mở Ngân Hàng Câu Hỏi
                        </button>
                    </div>

                    <div id="selectedQuestionsList" class="d-none">
                        <!-- Rendered by JS -->
                    </div>

                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 fw-bold" id="btnSaveLesson">
                    <i class="bi bi-check-circle me-1"></i> Lưu Bài Học
                </button>
                <button type="button" class="btn btn-success rounded-3 px-4 fw-bold" id="btnSaveAndPresent">
                    <i class="bi bi-tv-fill me-1"></i> Lưu & Chiếu TV Ngay
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Chọn câu hỏi từ Ngân Hàng Câu Hỏi -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning-subtle border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="bankModalLabel">
                    <i class="bi bi-bank2 text-warning"></i>
                    <span>Ngân Hàng Câu Hỏi: <span id="bankHeaderScope" class="text-primary"></span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Filter bar in Bank Modal -->
                <div class="row g-2 mb-3 bg-light p-3 rounded-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="bankSearchInput" placeholder="Tìm từ khóa trong câu hỏi...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="bankTopicFilter">
                            <option value="">-- Tất cả chủ đề / bài học --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="bankLevelFilter">
                            <option value="">-- Tất cả mức độ --</option>
                            <option value="NB">Nhận biết (NB)</option>
                            <option value="TH">Thông hiểu (TH)</option>
                            <option value="VD">Vận dụng (VD)</option>
                            <option value="VDC">Vận dụng cao (VDC)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkAllVisibleBankQuestions">
                        <label class="form-check-label fw-bold" for="checkAllVisibleBankQuestions">
                            Chọn tất cả câu đang hiển thị
                        </label>
                    </div>
                    <div class="small text-muted">
                        Đang hiển thị <span id="bankVisibleCount" class="fw-bold text-dark">0</span> câu hỏi
                    </div>
                </div>

                <!-- Bank Questions List -->
                <div id="bankLoading" class="text-center py-4">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="text-muted mt-2">Đang tải câu hỏi từ ngân hàng...</p>
                </div>

                <div id="bankQuestionsContainer" class="d-none" style="max-height: 520px; overflow-y: auto;">
                    <!-- Rendered by JS -->
                </div>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <div class="me-auto">
                    <span class="text-muted">Đã chọn: <strong id="bankCheckedCount" class="text-primary">0</strong> câu</span>
                </div>
                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary rounded-3 fw-bold px-4" id="btnAddCheckedToLesson">
                    <i class="bi bi-plus-circle me-1"></i> Đưa Vào Bài Học
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allLessons = [];
    let currentSelectedQuestions = []; // array of question objects in currently opened lesson modal
    let bankQuestionsList = []; // flat list of loaded bank questions

    const lessonsLoading = document.getElementById('lessonsLoading');
    const lessonsGrid = document.getElementById('lessonsGrid');
    const lessonsEmpty = document.getElementById('lessonsEmpty');
    const searchLessonInput = document.getElementById('searchLessonInput');
    const filterGrade = document.getElementById('filterGrade');
    const filterSubject = document.getElementById('filterSubject');

    const lessonModal = new bootstrap.Modal(document.getElementById('lessonModal'));
    const bankModal = new bootstrap.Modal(document.getElementById('bankModal'));

    // Load lessons
    function loadLessons() {
        lessonsLoading.classList.remove('d-none');
        lessonsGrid.classList.add('d-none');
        lessonsEmpty.classList.add('d-none');

        fetch('api/manage_lesson.php?action=get_lessons')
            .then(res => res.json())
            .then(data => {
                lessonsLoading.classList.add('d-none');
                if (data.success && Array.isArray(data.lessons)) {
                    allLessons = data.lessons;
                    renderLessonsList();
                } else {
                    allLessons = [];
                    renderLessonsList();
                }
            })
            .catch(err => {
                console.error(err);
                lessonsLoading.classList.add('d-none');
                alert('Có lỗi khi tải danh sách bài học.');
            });
    }

    // Render lessons grid
    function renderLessonsList() {
        const query = (searchLessonInput.value || '').toLowerCase().trim();
        const selGrade = filterGrade.value;
        const selSubj = filterSubject.value;

        const filtered = allLessons.filter(les => {
            const matchesQuery = !query || (les.title && les.title.toLowerCase().includes(query)) || (les.description && les.description.toLowerCase().includes(query));
            const matchesGrade = !selGrade || les.grade === selGrade;
            const matchesSubj = !selSubj || String(les.subject_id) === String(selSubj);
            return matchesQuery && matchesGrade && matchesSubj;
        });

        if (filtered.length === 0) {
            lessonsGrid.classList.add('d-none');
            lessonsEmpty.classList.remove('d-none');
            return;
        }

        lessonsEmpty.classList.add('d-none');
        lessonsGrid.classList.remove('d-none');
        lessonsGrid.innerHTML = '';

        const gradeNames = {
            'khoi6': 'Khối 6',
            'khoi7': 'Khối 7',
            'khoi8': 'Khối 8',
            'khoi9': 'Khối 9'
        };

        const semNames = {
            'hk1': 'Học kì 1',
            'hk2': 'Học kì 2'
        };

        filtered.forEach(les => {
            const qCount = Array.isArray(les.questions) ? les.questions.length : 0;
            const gradeLabel = gradeNames[les.grade] || les.grade;
            const semLabel = semNames[les.semester] || les.semester;
            const updatedTime = les.updated_at ? les.updated_at.substring(0, 16) : '';

            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="lesson-card">
                    <div class="lesson-card-header">
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge-grade">${gradeLabel}</span>
                            <span class="badge-semester">${semLabel}</span>
                            <span class="badge-subject">${escapeHtml(les.subject_name || 'Môn học')}</span>
                        </div>
                        <span class="badge-count"><i class="bi bi-question-circle me-1"></i>${qCount} câu</span>
                    </div>
                    <div class="lesson-card-body">
                        <h5 class="lesson-card-title">${escapeHtml(les.title)}</h5>
                        <p class="lesson-card-desc">${escapeHtml(les.description || 'Không có mô tả chi tiết.')}</p>
                        <div class="text-muted small">
                            <i class="bi bi-clock-history me-1"></i>Cập nhật: ${updatedTime}
                        </div>
                    </div>
                    <div class="lesson-card-footer">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <a href="lesson_present.php?id=${les.id}" target="_blank" class="btn btn-present-tv w-100" title="Chiếu toàn màn hình lên TV lớp học">
                                <i class="bi bi-tv-fill fs-5"></i>
                                <span>CHIẾU LÊN TV</span>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <button type="button" class="btn btn-light btn-sm flex-fill text-primary fw-semibold btn-edit-lesson" data-id="${les.id}">
                                <i class="bi bi-pencil-square me-1"></i> Sửa
                            </button>
                            <button type="button" class="btn btn-light btn-sm flex-fill text-dark fw-semibold btn-dup-lesson" data-id="${les.id}">
                                <i class="bi bi-copy me-1"></i> Nhân bản
                            </button>
                            <button type="button" class="btn btn-light btn-sm text-danger fw-semibold btn-del-lesson" data-id="${les.id}" title="Xóa bài học">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            lessonsGrid.appendChild(col);
        });

        // Attach action handlers
        document.querySelectorAll('.btn-edit-lesson').forEach(btn => {
            btn.addEventListener('click', function() {
                openEditLessonModal(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-dup-lesson').forEach(btn => {
            btn.addEventListener('click', function() {
                duplicateLesson(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-del-lesson').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteLesson(this.getAttribute('data-id'));
            });
        });
    }

    // Filter events
    searchLessonInput.addEventListener('input', renderLessonsList);
    filterGrade.addEventListener('change', renderLessonsList);
    filterSubject.addEventListener('change', renderLessonsList);

    // Open Create Modal
    document.getElementById('btnCreateLesson').addEventListener('click', function() {
        document.getElementById('lessonForm').reset();
        document.getElementById('lessonId').value = '';
        document.getElementById('lessonModalTitleText').textContent = 'Tạo Bài Học Giảng Dạy Mới';
        currentSelectedQuestions = [];
        renderSelectedQuestions();
        lessonModal.show();
    });

    // Open Edit Modal
    function openEditLessonModal(id) {
        const found = allLessons.find(l => l.id === id);
        if (!found) return;

        document.getElementById('lessonId').value = found.id;
        document.getElementById('lessonTitle').value = found.title || '';
        document.getElementById('lessonGrade').value = found.grade || 'khoi6';
        document.getElementById('lessonSemester').value = found.semester || 'hk1';
        document.getElementById('lessonSubject').value = found.subject_id || 1;
        document.getElementById('lessonDescription').value = found.description || '';
        document.getElementById('lessonModalTitleText').textContent = 'Chỉnh Sửa Bài Học: ' + found.title;

        currentSelectedQuestions = Array.isArray(found.questions) ? JSON.parse(JSON.stringify(found.questions)) : [];
        renderSelectedQuestions();
        lessonModal.show();
    }

    // Duplicate Lesson
    function duplicateLesson(id) {
        if (!confirm('Bạn có chắc muốn nhân bản bài học này không?')) return;
        fetch('api/manage_lesson.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'duplicate_lesson', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadLessons();
            } else {
                alert(data.message || 'Lỗi nhân bản bài học.');
            }
        });
    }

    // Delete Lesson
    function deleteLesson(id) {
        if (!confirm('Bạn có chắc chắn muốn xóa bài học này? Thao tác này không thể hoàn tác.')) return;
        fetch('api/manage_lesson.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'delete_lesson', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadLessons();
            } else {
                alert(data.message || 'Lỗi xóa bài học.');
            }
        });
    }

    // Render Selected Questions in Lesson Modal
    function renderSelectedQuestions() {
        const emptyEl = document.getElementById('selectedQuestionsEmpty');
        const listEl = document.getElementById('selectedQuestionsList');
        const countBadge = document.getElementById('selectedCountBadge');

        countBadge.textContent = currentSelectedQuestions.length + ' câu';

        if (currentSelectedQuestions.length === 0) {
            emptyEl.classList.remove('d-none');
            listEl.classList.add('d-none');
            listEl.innerHTML = '';
            return;
        }

        emptyEl.classList.add('d-none');
        listEl.classList.remove('d-none');
        listEl.innerHTML = '';

        currentSelectedQuestions.forEach((q, idx) => {
            const card = document.createElement('div');
            card.className = 'selected-q-card';
            
            let levelClass = 'level-badge-nb';
            let levelText = 'Nhận biết';
            if (q.level === 'TH') { levelClass = 'level-badge-th'; levelText = 'Thông hiểu'; }
            else if (q.level === 'VD') { levelClass = 'level-badge-vd'; levelText = 'Vận dụng'; }
            else if (q.level === 'VDC') { levelClass = 'level-badge-vdc'; levelText = 'Vận dụng cao'; }

            let typeText = 'Trắc nghiệm';
            if (q.type === 'true_false_multiple') typeText = 'Đúng/Sai';
            else if (q.type === 'essay') typeText = 'Tự luận';
            else if (q.type === 'short_answer') typeText = 'Trả lời ngắn';

            card.innerHTML = `
                <div class="selected-q-num">${idx + 1}</div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="${levelClass}">${levelText}</span>
                        <span class="badge bg-light text-secondary border">${typeText}</span>
                        ${q.topic ? `<small class="text-muted"><i class="bi bi-tag me-1"></i>${escapeHtml(q.topic)}</small>` : ''}
                    </div>
                    <div class="fw-semibold text-dark mb-1" style="font-size: 0.95rem;">
                        ${q.question || ''}
                    </div>
                    ${renderOptionsPreview(q)}
                </div>
                <div class="d-flex flex-column gap-1 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2 btn-move-up" data-index="${idx}" ${idx === 0 ? 'disabled' : ''} title="Di chuyển lên">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2 btn-move-down" data-index="${idx}" ${idx === currentSelectedQuestions.length - 1 ? 'disabled' : ''} title="Di chuyển xuống">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 btn-remove-q" data-index="${idx}" title="Xóa câu hỏi khỏi bài học">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
            listEl.appendChild(card);
        });

        // Event handlers for move & remove
        listEl.querySelectorAll('.btn-move-up').forEach(b => {
            b.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                if (idx > 0) {
                    const temp = currentSelectedQuestions[idx - 1];
                    currentSelectedQuestions[idx - 1] = currentSelectedQuestions[idx];
                    currentSelectedQuestions[idx] = temp;
                    renderSelectedQuestions();
                }
            });
        });

        listEl.querySelectorAll('.btn-move-down').forEach(b => {
            b.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                if (idx < currentSelectedQuestions.length - 1) {
                    const temp = currentSelectedQuestions[idx + 1];
                    currentSelectedQuestions[idx + 1] = currentSelectedQuestions[idx];
                    currentSelectedQuestions[idx] = temp;
                    renderSelectedQuestions();
                }
            });
        });

        listEl.querySelectorAll('.btn-remove-q').forEach(b => {
            b.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                currentSelectedQuestions.splice(idx, 1);
                renderSelectedQuestions();
            });
        });

        // Trigger MathJax if available
        if (window.MathJax && window.MathJax.typesetPromise) {
            window.MathJax.typesetPromise([listEl]).catch(err => console.error(err));
        }
    }

    function renderOptionsPreview(q) {
        if (q.type === 'single' && Array.isArray(q.options) && q.options.length > 0) {
            const letters = ['A', 'B', 'C', 'D', 'E'];
            const correctIdx = q.correct;
            return `
                <div class="row g-1 text-muted small mt-1">
                    ${q.options.map((opt, i) => `
                        <div class="col-md-6 ${i === correctIdx ? 'text-success fw-bold' : ''}">
                            <span class="badge ${i === correctIdx ? 'bg-success text-white' : 'bg-light text-secondary border'} me-1">${letters[i]}</span>
                            ${escapeHtml(opt)} ${i === correctIdx ? '✓' : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }
        if (q.type === 'true_false_multiple' && Array.isArray(q.items)) {
            return `
                <div class="small text-muted mt-1">
                    ${q.items.map(it => `
                        <span class="badge ${it.correct ? 'bg-success' : 'bg-danger'} me-1">${it.label}: ${it.correct ? 'Đúng' : 'Sai'}</span>
                    `).join(' ')}
                </div>
            `;
        }
        return '';
    }

    // Save Lesson Function
    function saveLessonData(callback) {
        const title = document.getElementById('lessonTitle').value.trim();
        if (!title) {
            alert('Vui lòng nhập tiêu đề bài học.');
            document.getElementById('lessonTitle').focus();
            return;
        }

        const id = document.getElementById('lessonId').value;
        const grade = document.getElementById('lessonGrade').value;
        const semester = document.getElementById('lessonSemester').value;
        const subjSelect = document.getElementById('lessonSubject');
        const subjectId = subjSelect.value;
        const subjectName = subjSelect.options[subjSelect.selectedIndex]?.getAttribute('data-name') || 'Môn học';
        const description = document.getElementById('lessonDescription').value.trim();

        const payload = {
            action: 'save_lesson',
            id: id,
            title: title,
            grade: grade,
            semester: semester,
            subject_id: subjectId,
            subject_name: subjectName,
            description: description,
            questions: currentSelectedQuestions
        };

        fetch('api/manage_lesson.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadLessons();
                if (callback) callback(data.id);
                else {
                    lessonModal.hide();
                    alert('Đã lưu bài học thành công!');
                }
            } else {
                alert(data.message || 'Lỗi lưu bài học.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi lưu bài học.');
        });
    }

    document.getElementById('btnSaveLesson').addEventListener('click', function() {
        saveLessonData();
    });

    document.getElementById('btnSaveAndPresent').addEventListener('click', function() {
        saveLessonData(function(lessonId) {
            lessonModal.hide();
            window.open('lesson_present.php?id=' + lessonId, '_blank');
        });
    });

    // OPEN QUESTION BANK MODAL
    document.getElementById('btnOpenBankModal').addEventListener('click', function() {
        const grade = document.getElementById('lessonGrade').value;
        const semester = document.getElementById('lessonSemester').value;
        const subjSelect = document.getElementById('lessonSubject');
        const subjectId = subjSelect.value;
        const subjectName = subjSelect.options[subjSelect.selectedIndex]?.getAttribute('data-name') || 'Môn học';

        const gradeNames = {'khoi6': 'Khối 6', 'khoi7': 'Khối 7', 'khoi8': 'Khối 8', 'khoi9': 'Khối 9'};
        const semNames = {'hk1': 'Học kì 1', 'hk2': 'Học kì 2'};

        document.getElementById('bankHeaderScope').textContent = `${subjectName} - ${gradeNames[grade]} (${semNames[semester]})`;

        document.getElementById('bankLoading').classList.remove('d-none');
        document.getElementById('bankQuestionsContainer').classList.add('d-none');
        document.getElementById('bankSearchInput').value = '';
        document.getElementById('bankTopicFilter').innerHTML = '<option value="">-- Tất cả chủ đề / bài học --</option>';
        document.getElementById('bankLevelFilter').value = '';
        document.getElementById('checkAllVisibleBankQuestions').checked = false;

        bankModal.show();

        fetch(`api/manage_lesson.php?action=get_bank_questions&grade=${grade}&semester=${semester}&subject_id=${subjectId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('bankLoading').classList.add('d-none');
                document.getElementById('bankQuestionsContainer').classList.remove('d-none');

                bankQuestionsList = [];
                const topicFilterSelect = document.getElementById('bankTopicFilter');
                const seenTopics = new Set();

                if (data.success && Array.isArray(data.topics)) {
                    data.topics.forEach(t => {
                        const topName = t.topic || 'Chủ đề chung';
                        if (!seenTopics.has(topName)) {
                            seenTopics.add(topName);
                            const opt = document.createElement('option');
                            opt.value = topName;
                            opt.textContent = topName;
                            topicFilterSelect.appendChild(opt);
                        }

                        if (Array.isArray(t.questions)) {
                            t.questions.forEach(q => {
                                bankQuestionsList.push(q);
                            });
                        }
                    });
                }

                renderBankQuestionsList();
            })
            .catch(err => {
                console.error(err);
                document.getElementById('bankLoading').classList.add('d-none');
                alert('Không thể tải câu hỏi từ ngân hàng.');
            });
    });

    // Render Bank Questions with Filters
    function renderBankQuestionsList() {
        const container = document.getElementById('bankQuestionsContainer');
        container.innerHTML = '';

        const searchKeyword = (document.getElementById('bankSearchInput').value || '').toLowerCase().trim();
        const selectedTopic = document.getElementById('bankTopicFilter').value;
        const selectedLevel = document.getElementById('bankLevelFilter').value;

        // Existing question texts already in lesson
        const existingTexts = new Set(currentSelectedQuestions.map(q => (q.question || '').trim()));

        let visibleCount = 0;

        bankQuestionsList.forEach((q, idx) => {
            const matchesSearch = !searchKeyword || (q.question && q.question.toLowerCase().includes(searchKeyword));
            const matchesTopic = !selectedTopic || q.topic === selectedTopic;
            const matchesLevel = !selectedLevel || q.level === selectedLevel;

            if (!matchesSearch || !matchesTopic || !matchesLevel) return;

            visibleCount++;
            const isAlreadyAdded = existingTexts.has((q.question || '').trim());

            let levelClass = 'level-badge-nb';
            let levelText = 'Nhận biết';
            if (q.level === 'TH') { levelClass = 'level-badge-th'; levelText = 'Thông hiểu'; }
            else if (q.level === 'VD') { levelClass = 'level-badge-vd'; levelText = 'Vận dụng'; }
            else if (q.level === 'VDC') { levelClass = 'level-badge-vdc'; levelText = 'Vận dụng cao'; }

            const itemDiv = document.createElement('div');
            itemDiv.className = 'q-bank-item ' + (isAlreadyAdded ? 'bg-light opacity-75' : '');
            itemDiv.innerHTML = `
                <div class="d-flex align-items-start gap-3">
                    <div class="pt-1">
                        <input type="checkbox" class="form-check-input bank-q-checkbox fs-5" data-index="${idx}" ${isAlreadyAdded ? 'disabled title="Đã có trong bài học"' : ''}>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="${levelClass}">${levelText}</span>
                            ${q.topic ? `<span class="badge bg-secondary-subtle text-secondary">${escapeHtml(q.topic)}</span>` : ''}
                            ${q.lesson ? `<span class="badge bg-light text-dark border">${escapeHtml(q.lesson)}</span>` : ''}
                            ${isAlreadyAdded ? '<span class="badge bg-success-subtle text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Đã có trong bài</span>' : ''}
                        </div>
                        <div class="fw-bold text-dark mb-1">
                            ${q.question || ''}
                        </div>
                        ${renderOptionsPreview(q)}
                    </div>
                </div>
            `;
            container.appendChild(itemDiv);
        });

        document.getElementById('bankVisibleCount').textContent = visibleCount;
        updateBankCheckedCount();

        // Checkbox events
        container.querySelectorAll('.bank-q-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBankCheckedCount);
        });

        // MathJax
        if (window.MathJax && window.MathJax.typesetPromise) {
            window.MathJax.typesetPromise([container]).catch(err => console.error(err));
        }
    }

    function updateBankCheckedCount() {
        const checked = document.querySelectorAll('.bank-q-checkbox:checked').length;
        document.getElementById('bankCheckedCount').textContent = checked;
    }

    // Filter events in Bank Modal
    document.getElementById('bankSearchInput').addEventListener('input', renderBankQuestionsList);
    document.getElementById('bankTopicFilter').addEventListener('change', renderBankQuestionsList);
    document.getElementById('bankLevelFilter').addEventListener('change', renderBankQuestionsList);

    // Check all visible
    document.getElementById('checkAllVisibleBankQuestions').addEventListener('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.bank-q-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = checked;
        });
        updateBankCheckedCount();
    });

    // Add Checked Bank Questions to Current Lesson
    document.getElementById('btnAddCheckedToLesson').addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.bank-q-checkbox:checked');
        if (checkedBoxes.length === 0) {
            alert('Vui lòng tích chọn ít nhất 1 câu hỏi để đưa vào bài học.');
            return;
        }

        let addedCount = 0;
        checkedBoxes.forEach(cb => {
            const idx = parseInt(cb.getAttribute('data-index'), 10);
            const q = bankQuestionsList[idx];
            if (q) {
                // clone question
                const clone = JSON.parse(JSON.stringify(q));
                currentSelectedQuestions.push(clone);
                addedCount++;
            }
        });

        bankModal.hide();
        renderSelectedQuestions();
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // Initial load
    loadLessons();
});
</script>

<!-- MathJax CDN for preview formulas -->
<script>
    window.MathJax = {
        tex: {
            inlineMath: [['$', '$'], ['\\(', '\\)']],
            displayMath: [['$$', '$$'], ['\\[', '\\]']],
            processEscapes: true
        }
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>

<?php include '../includes/teacher_footer.php'; ?>
