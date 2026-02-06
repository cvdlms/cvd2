<?php
// Set unique session name for Teacher/Admin (must match login.php)
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';
include '../includes/premium_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$users = json_decode(file_get_contents('../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

// Load subjects for this teacher
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$teacherSubjects = file_exists($teacherSubjectsFile) ? json_decode(file_get_contents($teacherSubjectsFile), true) : [];
$assignedSubjectIds = $teacherSubjects[$username] ?? [];

// Load all subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$allSubjects = file_exists($subjectsFile) ? json_decode(file_get_contents($subjectsFile), true) : [];

// Filter subjects to only show assigned ones
$subjects = array_filter($allSubjects, function($subject) use ($assignedSubjectIds) {
    return in_array($subject['id'], $assignedSubjectIds);
});

// Load teacher classes
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';
$teacherClasses = file_exists($teacherClassesFile) ? json_decode(file_get_contents($teacherClassesFile), true) : [];
$assignedClassIds = $teacherClasses[$username] ?? [];

// Load all classes to get grade info
$classesFile = __DIR__ . '/../admin/classes.json';
$allClasses = file_exists($classesFile) ? json_decode(file_get_contents($classesFile), true) : [];

// Extract grades from assigned classes
$assignedGrades = [];
foreach ($allClasses as $class) {
    if (in_array($class['id'], $assignedClassIds)) {
        // Extract grade from class code (e.g., "6A1" -> "6")
        if (preg_match('/^(\d+)/', $class['code'], $matches)) {
            $grade = 'khoi' . $matches[1];
            // Only include grades 6-9
            if (in_array($grade, ['khoi6', 'khoi7', 'khoi8', 'khoi9'])) {
                $assignedGrades[$grade] = true;
            }
        }
    }
}
$assignedGrades = array_keys($assignedGrades);
sort($assignedGrades);

// Auto-select first subject if only one assigned
$selectedSubjectId = (count($subjects) === 1) ? reset($subjects)['id'] : '';

// Load system config for security settings
$configFile = __DIR__ . '/../admin/system_config.json';
$systemConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$disableViewSource = $systemConfig['system']['disable_view_source'] ?? true;

$title = 'Bản Mô Tả Mức Độ Đánh Giá - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
    <div class="container my-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="bi bi-clipboard-data"></i> Bản Mô Tả Mức Độ Đánh Giá</h2>
                        <p class="text-muted">Quản lý nội dung kiến thức và mức độ đánh giá theo môn học</p>
                    </div>
                    <a href="teacher.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($subjects)): ?>
        <!-- No Subjects Assigned Warning -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    <strong>Chưa được phân công môn học!</strong> Vui lòng liên hệ admin để được phân công môn học.
                </div>
            </div>
        </div>
        <?php elseif (empty($assignedGrades)): ?>
        <!-- No Classes Assigned Warning -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    <strong>Chưa được phân công lớp dạy!</strong> Vui lòng liên hệ admin để được phân công lớp. 
                    Hệ thống chỉ hiển thị khối của các lớp bạn được phân công dạy.
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Chọn Môn Học</label>
                <select class="form-select" id="subjectFilter">
                    <?php if (count($subjects) > 1): ?>
                        <option value="">-- Chọn môn học --</option>
                    <?php endif; ?>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo ($subject['id'] == $selectedSubjectId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Chọn Khối</label>
                <select class="form-select" id="gradeFilter">
                    <?php if (empty($assignedGrades)): ?>
                        <option value="">-- Chưa có lớp phân công --</option>
                    <?php else: ?>
                        <?php if (count($assignedGrades) > 1): ?>
                            <option value="">-- Chọn khối --</option>
                        <?php endif; ?>
                        <?php 
                        $gradeNames = ['khoi6' => 'Khối 6', 'khoi7' => 'Khối 7', 'khoi8' => 'Khối 8', 'khoi9' => 'Khối 9'];
                        foreach ($assignedGrades as $grade): 
                        ?>
                            <option value="<?php echo $grade; ?>" <?php echo (count($assignedGrades) === 1) ? 'selected' : ''; ?>>
                                <?php echo $gradeNames[$grade] ?? $grade; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary me-2" id="loadAssessmentBtn">
                    <i class="bi bi-search"></i> Tải Bản Đánh Giá
                </button>
                <button class="btn btn-success" id="createNewBtn" disabled>
                    <i class="bi bi-plus-circle"></i> Tạo Mới
                </button>
            </div>
        </div>

        <!-- View Mode: Table Display -->
        <div class="row" id="assessmentViewContainer" style="display: none;">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0">
                            <i class="bi bi-eye"></i> 
                            <span id="viewTitle">Bản Mô Tả Mức Độ Đánh Giá</span>
                        </h5>
                        <div>
                            <button class="btn btn-sm btn-warning" id="editAssessmentBtn">
                                <i class="bi bi-pencil"></i> Chỉnh Sửa
                            </button>
                            <button class="btn btn-sm btn-danger ms-2" id="deleteAssessmentViewBtn">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">STT</th>
                                        <th width="20%">Nội dung kiến thức</th>
                                        <th width="10%">Đơn vị kiến thức</th>
                                        <th width="23%">Nhận biết</th>
                                        <th width="23%">Thông hiểu</th>
                                        <th width="23%">Vận dụng</th>
                                    </tr>
                                </thead>
                                <tbody id="assessmentViewBody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Mode: Modern Form -->
        <div class="row" id="assessmentEditContainer" style="display: none;">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0">
                            <i class="bi bi-pencil-square"></i> 
                            <span id="editTitle">Soạn Bản Mô Tả Mức Độ Đánh Giá</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Action Buttons Top -->
                        <div class="mb-4 d-flex gap-2">
                            <button class="btn btn-primary" id="saveAssessmentBtn">
                                <i class="bi bi-save"></i> Lưu Bản Đánh Giá
                            </button>
                            <button class="btn btn-outline-secondary" id="cancelEditBtn">
                                <i class="bi bi-x-circle"></i> Hủy
                            </button>
                        </div>

                        <!-- Items Container -->
                        <div id="itemsContainer">
                            <!-- Form items will be added here -->
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-5" style="display: none;">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Chưa có nội dung nào. Nhấn "Thêm Nội Dung Mới" bên dưới để bắt đầu.</p>
                        </div>

                        <!-- Add Button Bottom -->
                        <div class="mt-4 d-flex justify-content-center">
                            <button class="btn btn-success btn-lg" id="addItemBtn">
                                <i class="bi bi-plus-circle"></i> Thêm Nội Dung Mới
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Form -->
        <div class="row" id="assessmentFormContainer" style="display: none;">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> 
                            <span id="formTitle">Bản Mô Tả Mức Độ Đánh Giá</span>
                        </h5>
                    </div>
                    <div class="card-body" style="display: none;">
                        <!-- Hidden - kept for backward compatibility -->
                        <div class="mb-3">
                            <button class="btn btn-sm btn-success" id="addRowBtn">
                                <i class="bi bi-plus-circle"></i> Thêm Dòng
                            </button>
                            <button class="btn btn-sm btn-primary ms-2" id="saveAssessmentBtn2">
                                <i class="bi bi-save"></i> Lưu Bản Đánh Giá
                            </button>
                            <button class="btn btn-sm btn-danger ms-2" id="deleteAssessmentBtn" style="display: none;">
                                <i class="bi bi-trash"></i> Xóa Bản Đánh Giá
                            </button>
                        </div>

                        <!-- Assessment Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="assessmentTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">STT</th>
                                        <th width="20%">Nội dung kiến thức</th>
                                        <th width="10%">Đơn vị kiến thức</th>
                                        <th width="23%">Nhận biết</th>
                                        <th width="23%">Thông hiểu</th>
                                        <th width="23%">Vận dụng</th>
                                        <th width="60">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="assessmentTableBody">
                                    <!-- Rows will be added here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="row mt-4" id="infoAlert">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Hướng dẫn:</strong> Chọn môn học và khối, sau đó nhấn "Tải Bản Đánh Giá" để xem hoặc nhấn "Tạo Mới" để tạo bản mới.
                    <hr>
                    <small>
                        <strong>Môn học được phân công:</strong> 
                        <?php 
                        $subjectNames = array_map(function($s) { return $s['name']; }, $subjects);
                        echo htmlspecialchars(implode(', ', $subjectNames)); 
                        ?>
                        <br>
                        <strong>Khối được phân công:</strong> 
                        <?php 
                        if (empty($assignedGrades)) {
                            echo '<span class="text-warning">Chưa có lớp được phân công</span>';
                        } else {
                            $gradeNames = ['khoi6' => 'Khối 6', 'khoi7' => 'Khối 7', 'khoi8' => 'Khối 8', 'khoi9' => 'Khối 9'];
                            $gradeLabels = array_map(function($g) use ($gradeNames) { return $gradeNames[$g] ?? $g; }, $assignedGrades);
                            echo htmlspecialchars(implode(', ', $gradeLabels)); 
                        }
                        ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.delete-row-btn {
    cursor: pointer;
    color: #dc3545;
    transition: color 0.2s;
}
.delete-row-btn:hover {
    color: #a71d2a;
}

/* Form Item Card */
.form-item-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    background: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.form-item-card:hover {
    border-color: #667eea;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.12);
}

.form-item-card .card-number {
    position: absolute;
    top: -12px;
    left: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.form-item-card .delete-item-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    color: #dc3545;
    font-size: 1.3rem;
    cursor: pointer;
    opacity: 0.6;
    transition: all 0.2s;
    padding: 4px 8px;
}

.form-item-card .delete-item-btn:hover {
    opacity: 1;
    transform: scale(1.1);
}

.form-item-card .form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-item-card .form-control,
.form-item-card .form-select {
    border: 1.5px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-item-card .form-control:focus,
.form-item-card .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-item-card textarea.form-control {
    min-height: 100px;
    line-height: 1.6;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.level-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    margin-top: 12px;
}

.level-section .badge {
    font-size: 0.85rem;
    padding: 6px 12px;
    margin-bottom: 10px;
}

/* View mode table styling */
#assessmentViewBody td {
    vertical-align: top;
    padding: 12px;
    line-height: 1.6;
}

#assessmentViewBody td pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
    font-family: inherit;
    background: none;
    border: none;
    padding: 0;
}

#assessmentTable input[type="text"] {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 6px 10px;
    width: 100%;
    font-size: 0.9rem;
}

#assessmentTable textarea {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 8px 12px;
    width: 100%;
    font-size: 0.9rem;
    resize: vertical;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    line-height: 1.5;
}

#assessmentTable .knowledge-content {
    min-height: 80px;
}

#assessmentTable .level-nhan-biet,
#assessmentTable .level-thong-hieu,
#assessmentTable .level-van-dung {
    min-height: 120px;
}
</style>

<?php include '../includes/teacher_footer.php'; ?>

<script>
let currentAssessmentId = null;
let currentSubject = null;
let currentGrade = null;
let currentMode = null; // 'view' or 'edit'

$(document).ready(function() {
    // Check initial state and enable button if both filters have values
    function checkFiltersAndEnableButton() {
        const subject = $('#subjectFilter').val();
        const grade = $('#gradeFilter').val();
        
        if (subject && grade) {
            $('#createNewBtn').prop('disabled', false);
        } else {
            $('#createNewBtn').prop('disabled', true);
        }
    }
    
    // Check on page load
    checkFiltersAndEnableButton();
    
    // Enable create button when both filters are selected
    $('#subjectFilter, #gradeFilter').on('change', function() {
        checkFiltersAndEnableButton();
    });

    // Load assessment (VIEW mode)
    $('#loadAssessmentBtn').on('click', function() {
        const subject = $('#subjectFilter').val();
        const grade = $('#gradeFilter').val();
        
        if (!subject || !grade) {
            alert('Vui lòng chọn môn học và khối!');
            return;
        }
        
        loadAssessment(subject, grade);
    });

    // Create new assessment (EDIT mode)
    $('#createNewBtn').on('click', function() {
        const subject = $('#subjectFilter').val();
        const grade = $('#gradeFilter').val();
        
        if (!subject || !grade) {
            alert('Vui lòng chọn môn học và khối!');
            return;
        }
        
        showEditMode(subject, grade);
    });

    // Add item in edit mode
    $('#addItemBtn').on('click', function() {
        addFormItem();
    });

    // Save assessment
    $('#saveAssessmentBtn').on('click', function() {
        saveAssessment();
    });

    // Cancel edit
    $('#cancelEditBtn').on('click', function() {
        if (confirm('Hủy chỉnh sửa? Dữ liệu chưa lưu sẽ mất.')) {
            hideAllContainers();
            $('#infoAlert').show();
        }
    });

    // Edit assessment from view mode
    $('#editAssessmentBtn').on('click', function() {
        showEditMode(currentSubject, currentGrade, true);
    });

    // Delete from view mode
    $('#deleteAssessmentViewBtn').on('click', function() {
        if (confirm('Bạn có chắc muốn xóa bản đánh giá này?')) {
            deleteAssessment();
        }
    });
});

function hideAllContainers() {
    $('#assessmentViewContainer').hide();
    $('#assessmentEditContainer').hide();
    $('#assessmentFormContainer').hide();
}

function showEditMode(subject, grade, loadExisting = false) {
    currentSubject = subject;
    currentGrade = grade;
    currentMode = 'edit';
    
    const subjectName = $('#subjectFilter option:selected').text();
    const gradeName = $('#gradeFilter option:selected').text();
    
    $('#editTitle').text(`${subjectName} - ${gradeName}`);
    $('#itemsContainer').empty();
    
    hideAllContainers();
    $('#infoAlert').hide();
    
    if (loadExisting && currentAssessmentId) {
        // Load existing data into edit mode
        $.ajax({
            url: 'api/manage_knowledge_assessment.php',
            method: 'GET',
            data: { 
                action: 'load',
                subject_id: subject,
                grade: grade
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.items && response.data.items.length > 0) {
                    response.data.items.forEach(item => {
                        addFormItem(item);
                    });
                } else {
                    addFormItem();
                }
                $('#assessmentEditContainer').slideDown();
                checkEmptyState();
            }
        });
    } else {
        // New assessment
        currentAssessmentId = null;
        addFormItem();
        $('#assessmentEditContainer').slideDown();
        checkEmptyState();
    }
}

function addFormItem(data = null) {
    const itemCount = $('#itemsContainer .form-item-card').length + 1;
    
    const content = data ? data.content : '';
    const unit = data ? data.unit : '';
    const nhanBiet = data ? (data.nhan_biet || '') : '';
    const thongHieu = data ? (data.thong_hieu || '') : '';
    const vanDung = data ? (data.van_dung || '') : '';
    
    const itemHtml = `
        <div class="form-item-card">
            <div class="card-number">${itemCount}</div>
            <button type="button" class="delete-item-btn" title="Xóa nội dung này">
                <i class="bi bi-x-circle-fill"></i>
            </button>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-book"></i> Nội dung kiến thức <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control item-content" placeholder="VD: Thông tin số trong thời đại kỹ thuật số" value="${content}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-journal-bookmark"></i> Đơn vị kiến thức <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control item-unit" rows="2" placeholder="VD: Bài 1, 2" required>${unit}</textarea>
                </div>
            </div>
            
            <div class="level-section">
                <span class="badge bg-primary"><i class="bi bi-1-circle"></i> Nhận biết</span>
                <textarea class="form-control item-nhan-biet" rows="3" placeholder="– Nêu được...\n– Chỉ ra được...\n– Liệt kê được...">${nhanBiet}</textarea>
            </div>
            
            <div class="level-section">
                <span class="badge bg-success"><i class="bi bi-2-circle"></i> Thông hiểu</span>
                <textarea class="form-control item-thong-hieu" rows="3" placeholder="– Trình bày được...\n– Giải thích được...\n– So sánh được...">${thongHieu}</textarea>
            </div>
            
            <div class="level-section">
                <span class="badge bg-warning text-dark"><i class="bi bi-3-circle"></i> Vận dụng</span>
                <textarea class="form-control item-van-dung" rows="3" placeholder="– Sử dụng được...\n– Áp dụng được...\n– Thực hiện được...">${vanDung}</textarea>
            </div>
        </div>
    `;
    
    const $item = $(itemHtml);
    
    // Attach delete handler
    $item.find('.delete-item-btn').on('click', function() {
        if (confirm('Bạn có chắc muốn xóa nội dung này?')) {
            $item.remove();
            updateItemNumbers();
            checkEmptyState();
        }
    });
    
    $('#itemsContainer').append($item);
    updateItemNumbers();
    checkEmptyState();
}

function updateItemNumbers() {
    $('#itemsContainer .form-item-card').each(function(index) {
        $(this).find('.card-number').text(index + 1);
    });
}

function checkEmptyState() {
    if ($('#itemsContainer .form-item-card').length === 0) {
        $('#emptyState').show();
    } else {
        $('#emptyState').hide();
    }
}

function loadAssessment(subject, grade) {
    $.ajax({
        url: 'api/manage_knowledge_assessment.php',
        method: 'GET',
        data: { 
            action: 'load',
            subject_id: subject,
            grade: grade
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentSubject = subject;
                currentGrade = grade;
                currentAssessmentId = response.data.id;
                currentMode = 'view';
                
                showViewMode(response.data);
            } else {
                alert('Không tìm thấy bản đánh giá. Vui lòng tạo mới.');
            }
        },
        error: function() {
            alert('Có lỗi khi tải dữ liệu!');
        }
    });
}

function showViewMode(data) {
    const subjectName = $('#subjectFilter option:selected').text();
    const gradeName = $('#gradeFilter option:selected').text();
    
    $('#viewTitle').text(`${subjectName} - ${gradeName}`);
    $('#assessmentViewBody').empty();
    
    if (data.items && data.items.length > 0) {
        data.items.forEach((item, index) => {
            const row = `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td><pre>${escapeHtml(item.content)}</pre></td>
                    <td><pre>${escapeHtml(item.unit)}</pre></td>
                    <td><pre>${escapeHtml(item.nhan_biet || '')}</pre></td>
                    <td><pre>${escapeHtml(item.thong_hieu || '')}</pre></td>
                    <td><pre>${escapeHtml(item.van_dung || '')}</pre></td>
                </tr>
            `;
            $('#assessmentViewBody').append(row);
        });
    }
    
    hideAllContainers();
    $('#infoAlert').hide();
    $('#assessmentViewContainer').slideDown();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function saveAssessment() {
    if (!currentSubject || !currentGrade) {
        alert('Thiếu thông tin môn học hoặc khối!');
        return;
    }
    
    const items = [];
    let hasError = false;
    
    $('#itemsContainer .form-item-card').each(function(index) {
        const content = $(this).find('.item-content').val().trim();
        const unit = $(this).find('.item-unit').val().trim();
        const nhanBiet = $(this).find('.item-nhan-biet').val().trim();
        const thongHieu = $(this).find('.item-thong-hieu').val().trim();
        const vanDung = $(this).find('.item-van-dung').val().trim();
        
        if (!content) {
            alert(`Mục ${index + 1}: Vui lòng nhập nội dung kiến thức!`);
            hasError = true;
            return false;
        }
        
        if (!unit) {
            alert(`Mục ${index + 1}: Vui lòng nhập đơn vị kiến thức!`);
            hasError = true;
            return false;
        }
        
        if (!nhanBiet && !thongHieu && !vanDung) {
            alert(`Mục ${index + 1}: Vui lòng nhập ít nhất một mức độ đánh giá!`);
            hasError = true;
            return false;
        }
        
        items.push({
            order: index + 1,
            content: content,
            unit: unit,
            nhan_biet: nhanBiet,
            thong_hieu: thongHieu,
            van_dung: vanDung
        });
    });
    
    if (hasError) return;
    
    if (items.length === 0) {
        alert('Vui lòng thêm ít nhất một nội dung!');
        return;
    }
    
    const data = {
        action: 'save',
        subject_id: currentSubject,
        grade: currentGrade,
        items: items
    };
    
    if (currentAssessmentId) {
        data.id = currentAssessmentId;
    }
    
    $.ajax({
        url: 'api/manage_knowledge_assessment.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✅ Lưu bản đánh giá thành công!');
                currentAssessmentId = response.id;
                // Reload in view mode
                loadAssessment(currentSubject, currentGrade);
            } else {
                alert('❌ Lỗi: ' + (response.message || 'Không thể lưu dữ liệu'));
            }
        },
        error: function() {
            alert('❌ Có lỗi khi lưu dữ liệu!');
        }
    });
}

function deleteAssessment() {
    if (!currentAssessmentId) {
        alert('Không có bản đánh giá nào để xóa!');
        return;
    }
    
    $.ajax({
        url: 'api/manage_knowledge_assessment.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'delete',
            id: currentAssessmentId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✅ Xóa bản đánh giá thành công!');
                hideAllContainers();
                $('#infoAlert').show();
                currentAssessmentId = null;
                currentSubject = null;
                currentGrade = null;
            } else {
                alert('❌ Lỗi: ' + (response.message || 'Không thể xóa'));
            }
        },
        error: function() {
            alert('❌ Có lỗi khi xóa dữ liệu!');
        }
    });
}
</script>
