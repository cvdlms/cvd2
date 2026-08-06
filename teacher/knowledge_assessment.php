<?php
// Set unique session name for Teacher/Admin (must match index.php)
session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';
include '../includes/premium_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
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
    <div class="container py-4 mb-5">
        <!-- Header -->
        <div class="section-header mb-4">
            <div class="sh-icon"><i class="bi bi-clipboard-data"></i></div>
            <div class="flex-grow-1">
                <h3 class="mb-0">Bản Mô Tả Mức Độ Đánh Giá</h3>
                <p class="mb-0">Quản lý nội dung kiến thức và mức độ đánh giá theo môn học</p>
            </div>
            <a href="teacher.php" class="btn btn-soft-slate btn-action-custom">
                <i class="bi bi-arrow-left"></i> Quay lại Dashboard
            </a>
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
        <div class="filter-bar mb-4">
            <div class="flex-grow-1" style="min-width: 220px;">
                <label class="form-label fw-bold d-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-journal-bookmark text-primary"></i> Môn Học
                </label>
                <select class="form-select w-100" id="subjectFilter">
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
            <div class="flex-grow-1" style="min-width: 220px;">
                <label class="form-label fw-bold d-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-easel text-primary"></i> Khối Lớp
                </label>
                <select class="form-select w-100" id="gradeFilter">
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
            <div class="d-flex gap-2 align-items-end">
                <button class="btn btn-primary btn-action-custom" id="loadAssessmentBtn">
                    <i class="bi bi-search"></i> Tải Bản Đánh Giá
                </button>
                <button class="btn btn-success btn-action-custom" id="createNewBtn" disabled>
                    <i class="bi bi-plus-circle"></i> Tạo Mới
                </button>
                <div class="btn-group">
                    <button class="btn btn-info btn-action-custom text-white" id="importJsonBtn" disabled>
                        <i class="bi bi-upload"></i> Nhập JSON
                    </button>
                    <button class="btn btn-outline-info btn-action-custom" id="copyTemplateBtn" title="Copy mẫu JSON">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <input type="file" id="jsonFileInput" accept=".json" style="display: none;">
            </div>
        </div>

        <!-- View Mode: Table Display -->
        <div class="row" id="assessmentViewContainer" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-eye"></i> 
                            <span id="viewTitle">Bản Mô Tả Mức Độ Đánh Giá</span>
                            <span class="badge badge-soft-slate ms-1" id="viewSummary" style="display: none;"></span>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-soft-warning" id="editAssessmentBtn">
                                <i class="bi bi-pencil"></i> Chỉnh Sửa
                            </button>
                            <button class="btn btn-sm btn-soft-danger" id="deleteAssessmentViewBtn">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover eduvn-table">
                                <thead>
                                    <tr>
                                        <th width="50">STT</th>
                                        <th width="20%" class="knowledge-th">Nội dung kiến thức</th>
                                        <th width="10%">Đơn vị kiến thức</th>
                                        <th width="23%" class="th-nb"><i class="bi bi-1-circle me-1"></i>Nhận biết</th>
                                        <th width="23%" class="th-th"><i class="bi bi-2-circle me-1"></i>Thông hiểu</th>
                                        <th width="23%" class="th-vd"><i class="bi bi-3-circle me-1"></i>Vận dụng</th>
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
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square"></i> 
                            <span id="editTitle">Soạn Bản Mô Tả Mức Độ Đánh Giá</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Items Container -->
                        <div id="itemsContainer">
                            <!-- Form items will be added here -->
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="empty-state empty-state-wrap py-5" style="display: none;">
                            <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                            <h6>Chưa có nội dung nào</h6>
                            <p>Nhấn "Thêm Nội Dung Mới" bên dưới để bắt đầu soạn bản mô tả.</p>
                        </div>

                        <!-- Add Button Bottom -->
                        <div class="mt-4 d-flex justify-content-center">
                            <button class="btn btn-success btn-lg btn-action-custom px-4" id="addItemBtn">
                                <i class="bi bi-plus-circle"></i> Thêm Nội Dung Mới
                            </button>
                        </div>

                        <!-- Sticky Action Bar -->
                        <div class="edit-actions-bar" id="editActionsBar">
                            <button class="btn btn-primary btn-action-custom px-4" id="saveAssessmentBtn">
                                <i class="bi bi-save"></i> Lưu Bản Đánh Giá
                            </button>
                            <button class="btn btn-soft-slate btn-action-custom" id="cancelEditBtn">
                                <i class="bi bi-x-circle"></i> Hủy
                            </button>
                            <span class="edit-actions-hint d-none d-md-inline-flex">
                                <i class="bi bi-info-circle"></i> Cần ít nhất 1 nội dung kiến thức &amp; 1 đơn vị kiến thức mỗi nội dung
                            </span>
                        </div>
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
/* ---------- Form Item Card ---------- */
.form-item-card {
    position: relative;
    border: 1.5px solid var(--border-soft);
    border-radius: var(--radius);
    padding: 28px 24px 24px;
    margin-bottom: 22px;
    background: var(--surface);
    box-shadow: var(--shadow-xs);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.form-item-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: var(--radius) var(--radius) 0 0;
    background: var(--grad-accent);
}

.form-item-card:hover {
    border-color: var(--accent);
    box-shadow: var(--shadow-sm);
}

.form-item-card .card-number {
    position: absolute;
    top: -14px;
    left: 22px;
    background: var(--grad-accent);
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.9rem;
    box-shadow: var(--shadow-accent);
}

.form-item-card .delete-item-btn {
    position: absolute;
    top: 18px;
    right: 18px;
    background: var(--danger-light);
    color: var(--danger);
    border: none;
    border-radius: 10px;
    width: 34px;
    height: 34px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.form-item-card .delete-item-btn:hover {
    background: var(--danger);
    color: #fff;
    transform: scale(1.08);
}

.form-item-card .form-label {
    font-weight: 600;
    color: var(--muted-strong);
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-item-card .form-control,
.form-item-card .form-select {
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-item-card .form-control:focus,
.form-item-card .form-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.form-item-card textarea.form-control {
    min-height: 100px;
    line-height: 1.6;
}

.form-item-card.flash-err {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.unit-card.flash-err {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

/* ---------- Unit card ---------- */
.unit-card {
    background: var(--surface);
    border: 1.5px solid var(--border-soft);
    border-radius: var(--radius);
    padding: 18px 18px 8px;
    margin-bottom: 16px;
    position: relative;
    transition: all 0.3s ease;
}

.unit-card:hover {
    border-color: var(--accent);
    box-shadow: var(--shadow-sm);
}

.unit-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.unit-number {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    background: var(--grad-accent);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    box-shadow: var(--shadow-accent);
}

.unit-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.delete-unit-btn {
    margin-left: auto;
    background: var(--danger-light);
    color: var(--danger);
    border: none;
    border-radius: 10px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.delete-unit-btn:hover {
    background: var(--danger);
    color: #fff;
    transform: scale(1.05);
}

.levels-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--muted-strong);
    margin: 16px 0 4px;
    padding-top: 14px;
    border-top: 1px dashed var(--border);
}

.units-container {
    margin-top: 18px;
}

.add-unit-btn {
    margin-top: 4px;
    width: 100%;
    border-style: dashed !important;
}

/* ---------- Level sections ---------- */
.level-section {
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-top: 10px;
}

.level-section .badge {
    font-size: 0.75rem;
    padding: 5px 12px;
    margin-bottom: 4px;
}

.level-section .form-control {
    margin-top: 10px;
    border-color: rgba(255, 255, 255, 0.9);
}

.ls-nb { background: var(--info-light); border-left: 4px solid var(--info); }
.ls-th { background: var(--success-light); border-left: 4px solid var(--success); }
.ls-vd { background: var(--warning-light); border-left: 4px solid var(--warning); }

/* ---------- Empty state wrapper ---------- */
.empty-state-wrap {
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    background: #FAFBFF;
    margin-top: 6px;
}

/* ---------- Sticky action bar ---------- */
.edit-actions-bar {
    position: sticky;
    bottom: 12px;
    z-index: 30;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-top: 24px;
    padding: 14px 18px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
}

.edit-actions-hint {
    color: var(--muted);
    font-size: 0.8rem;
    align-items: center;
    gap: 6px;
}

/* ---------- View mode table ---------- */
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

#assessmentViewBody .view-content-cell {
    background: #F4F5FD;
    font-weight: 600;
    color: var(--accent-dark);
}

#assessmentViewBody .view-level-nb { background: #F7FBFF; }
#assessmentViewBody .view-level-th { background: #F6FDF9; }
#assessmentViewBody .view-level-vd { background: #FFFBF0; }

.knowledge-th { background: var(--accent-light) !important; color: var(--accent-dark) !important; }
.th-nb { background: var(--info-light) !important; color: #0369A1 !important; }
.th-th { background: var(--success-light) !important; color: #047857 !important; }
.th-vd { background: var(--warning-light) !important; color: #92400E !important; }
</style>

<?php include '../includes/teacher_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentAssessmentId = null;
let currentSubject = null;
let currentGrade = null;
let currentMode = null; // 'view' or 'edit'

$(document).ready(function() {
    function checkFiltersAndEnableButton() {
        const subject = $('#subjectFilter').val();
        const grade = $('#gradeFilter').val();
        const ready = Boolean(subject && grade);
        
        $('#createNewBtn').prop('disabled', !ready);
        $('#loadAssessmentBtn').prop('disabled', !ready);
        $('#importJsonBtn').prop('disabled', !ready);
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
            swalToast('warning', 'Vui lòng chọn môn học và khối!');
            return;
        }
        
        loadAssessment(subject, grade);
    });

    // Create new assessment (EDIT mode)
    $('#createNewBtn').on('click', function() {
        const subject = $('#subjectFilter').val();
        const grade = $('#gradeFilter').val();
        
        if (!subject || !grade) {
            swalToast('warning', 'Vui lòng chọn môn học và khối!');
            return;
        }
        
        showEditMode(subject, grade);
    });

    // Import JSON handler
    $('#importJsonBtn').on('click', function() {
        $('#jsonFileInput').click();
    });

    $('#jsonFileInput').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const data = JSON.parse(event.target.result);
                let items = [];
                
                // Handle both {items: [...]} and [...] structures
                if (data && data.items && Array.isArray(data.items)) {
                    items = data.items;
                } else if (Array.isArray(data)) {
                    items = data;
                } else {
                    swalToast('error', 'Cấu trúc file JSON không hợp lệ!');
                    $('#jsonFileInput').val('');
                    return;
                }
                
                // Basic validation
                if (items.length === 0) {
                    swalToast('warning', 'File JSON không có nội dung nào!');
                    $('#jsonFileInput').val('');
                    return;
                }
                
                // Show in edit mode
                importToEditMode(items);
            } catch (error) {
                swalToast('error', 'Lỗi khi đọc file JSON!');
            }
            $('#jsonFileInput').val('');
        };
        reader.readAsText(file);
    });

    // Copy JSON template
    $('#copyTemplateBtn').on('click', function() {
        const template = [
            {
                "content": "Nội dung kiến thức 1 (VD: Thông tin số)",
                "units": [
                    {
                        "unit_name": "Bài 1",
                        "nhan_biet": "– Nêu được...\n– Nhận biết được...",
                        "thong_hieu": "– Giải thích được...",
                        "van_dung": "– Thực hiện được..."
                    }
                ]
            },
            {
                "content": "Nội dung kiến thức 2",
                "units": [
                    {
                        "unit_name": "Bài 2",
                        "nhan_biet": "– Liệt kê được...",
                        "thong_hieu": "",
                        "van_dung": "– Áp dụng được..."
                    }
                ]
            }
        ];
        
        const jsonStr = JSON.stringify(template, null, 2);
        
        navigator.clipboard.writeText(jsonStr).then(function() {
            swalToast('success', 'Đã copy mẫu JSON vào clipboard!');
        }).catch(function() {
            // Fallback for older browsers
            const $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(jsonStr).select();
            document.execCommand("copy");
            $temp.remove();
            swalToast('success', 'Đã copy mẫu JSON vào clipboard!');
        });
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
        swalConfirm(
            'Hủy chỉnh sửa?',
            'Dữ liệu chưa lưu sẽ bị mất.',
            'warning',
            '#64748B'
        ).then(confirmed => {
            if (confirmed) {
                hideAllContainers();
                $('#infoAlert').show();
            }
        });
    });

    // Edit assessment from view mode
    $('#editAssessmentBtn').on('click', function() {
        showEditMode(currentSubject, currentGrade, true);
    });

    // Delete from view mode
    $('#deleteAssessmentViewBtn').on('click', function() {
        swalConfirm(
            'Xóa bản đánh giá?',
            'Hành động này không thể hoàn tác.',
            'warning'
        ).then(confirmed => {
            if (confirmed) {
                deleteAssessment();
            }
        });
    });
});

function hideAllContainers() {
    $('#assessmentViewContainer').hide();
    $('#assessmentEditContainer').hide();
}

function swalToast(icon, title) {
    if (typeof Swal === 'undefined') { alert(title); return; }
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true
    });
}

function swalConfirm(title, text, icon, confirmColor) {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon || 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy',
        confirmButtonColor: confirmColor || '#EF4444',
        reverseButtons: true
    }).then(result => result.isConfirmed);
}

function setBtnLoading($btn, loading) {
    const $icon = $btn.find('i');
    if (loading) {
        $btn.prop('disabled', true);
        $icon.attr('data-orig-class', $icon.attr('class')).removeAttr('class').addClass('spinner-border spinner-border-sm');
    } else {
        $btn.prop('disabled', false);
        const orig = $icon.attr('data-orig-class') || 'bi bi-search';
        $icon.removeAttr('class').addClass(orig);
    }
}

function flashCard($card, message) {
    $card.addClass('flash-err');
    $card[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => $card.removeClass('flash-err'), 2000);
    swalToast('error', message);
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

function importToEditMode(items) {
    const subject = $('#subjectFilter').val();
    const grade = $('#gradeFilter').val();
    
    currentSubject = subject;
    currentGrade = grade;
    currentMode = 'edit';
    
    const subjectName = $('#subjectFilter option:selected').text();
    const gradeName = $('#gradeFilter option:selected').text();
    
    $('#editTitle').text(`${subjectName} - ${gradeName} (Đã nhập từ file)`);
    $('#itemsContainer').empty();
    
    hideAllContainers();
    $('#infoAlert').hide();
    
    // Fetch existing ID if any, then populate items
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
            if (response.success && response.data) {
                currentAssessmentId = response.data.id;
            } else {
                currentAssessmentId = null;
            }
            
            items.forEach(item => {
                addFormItem(item);
            });
            $('#assessmentEditContainer').slideDown();
            checkEmptyState();
            swalToast('success', 'Nhập dữ liệu thành công. Vui lòng kiểm tra và lưu lại!');
        },
        error: function() {
            // Still allow importing even if error fetching existing
            currentAssessmentId = null;
            items.forEach(item => {
                addFormItem(item);
            });
            $('#assessmentEditContainer').slideDown();
            checkEmptyState();
            swalToast('success', 'Nhập dữ liệu thành công. Vui lòng kiểm tra và lưu lại!');
        }
    });
}

function addFormItem(data = null) {
    const itemCount = $('#itemsContainer .form-item-card').length + 1;
    
    const content = data ? data.content : '';
    const units = data && data.units ? data.units : [];
    
    const itemHtml = `
        <div class="form-item-card" data-content-id="${itemCount}">
            <div class="card-number">${itemCount}</div>
            <button type="button" class="delete-item-btn" title="Xóa nội dung kiến thức này">
                <i class="bi bi-x-circle-fill"></i>
            </button>
            
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-book text-primary"></i> Nội dung kiến thức <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control item-content" placeholder="VD: Thông tin số trong thời đại kỹ thuật số" value="${content}" required>
            </div>
            
            <div class="levels-title">
                <i class="bi bi-journal-bookmark text-primary"></i> Các đơn vị kiến thức
            </div>
            
            <div class="units-container">
                <!-- Units will be added here -->
            </div>
            
            <button type="button" class="btn btn-soft-primary btn-action-custom add-unit-btn">
                <i class="bi bi-plus-circle"></i> Thêm Đơn vị Kiến Thức
            </button>
        </div>
    `;
    
    const $item = $(itemHtml);
    
    // Attach delete content handler
    $item.find('.delete-item-btn').on('click', function() {
        swalConfirm(
            'Xóa nội dung kiến thức?',
            'Toàn bộ các đơn vị kiến thức bên trong cũng sẽ bị xóa.',
            'warning'
        ).then(confirmed => {
            if (confirmed) {
                $item.remove();
                updateItemNumbers();
                checkEmptyState();
            }
        });
    });
    
    // Attach add unit handler
    $item.find('.add-unit-btn').on('click', function() {
        addUnitItem($item.find('.units-container'));
    });
    
    $('#itemsContainer').append($item);
    
    // Load existing units or add one empty unit
    if (units.length > 0) {
        units.forEach(unitData => {
            addUnitItem($item.find('.units-container'), unitData);
        });
    } else {
        addUnitItem($item.find('.units-container'));
    }
    
    updateItemNumbers();
    checkEmptyState();
}

function addUnitItem($container, data = null) {
    const unitCount = $container.find('.unit-card').length + 1;
    
    const unitName = data ? data.unit_name : '';
    const nhanBiet = data ? (data.nhan_biet || '') : '';
    const thongHieu = data ? (data.thong_hieu || '') : '';
    const vanDung = data ? (data.van_dung || '') : '';
    
    const unitHtml = `
        <div class="unit-card">
            <div class="unit-header">
                <span class="unit-number">${unitCount}</span>
                <span class="unit-title"><i class="bi bi-journal-text text-primary"></i> Tên đơn vị kiến thức <span class="text-danger">*</span></span>
                <button type="button" class="delete-unit-btn" title="Xóa đơn vị này">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            
            <textarea class="form-control unit-name" rows="2" placeholder="VD: Bài 1, 2" required>${unitName}</textarea>
            
            <div class="levels-title">
                <i class="bi bi-bar-chart-steps text-primary"></i> Mức độ đánh giá
            </div>
            
            <div class="level-section ls-nb">
                <span class="badge badge-soft-info"><i class="bi bi-1-circle"></i> Nhận biết</span>
                <textarea class="form-control unit-nhan-biet" rows="3" placeholder="– Nêu được...\n– Chỉ ra được...\n– Liệt kê được...">${nhanBiet}</textarea>
            </div>
            
            <div class="level-section ls-th">
                <span class="badge badge-soft-success"><i class="bi bi-2-circle"></i> Thông hiểu</span>
                <textarea class="form-control unit-thong-hieu" rows="3" placeholder="– Trình bày được...\n– Giải thích được...\n– So sánh được...">${thongHieu}</textarea>
            </div>
            
            <div class="level-section ls-vd">
                <span class="badge badge-soft-warning"><i class="bi bi-3-circle"></i> Vận dụng</span>
                <textarea class="form-control unit-van-dung" rows="3" placeholder="– Sử dụng được...\n– Áp dụng được...\n– Thực hiện được...">${vanDung}</textarea>
            </div>
        </div>
    `;
    
    const $unit = $(unitHtml);
    
    // Attach delete unit handler
    $unit.find('.delete-unit-btn').on('click', function() {
        swalConfirm(
            'Xóa đơn vị kiến thức?',
            'Đơn vị kiến thức này sẽ bị xóa khỏi danh sách.',
            'warning'
        ).then(confirmed => {
            if (confirmed) {
                $unit.remove();
                updateUnitNumbers($container);
            }
        });
    });
    
    $container.append($unit);
    updateUnitNumbers($container);
}

function updateUnitNumbers($container) {
    $container.find('.unit-card').each(function(index) {
        $(this).find('.unit-number').text(index + 1);
    });
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
    const $btn = $('#loadAssessmentBtn');
    setBtnLoading($btn, true);
    
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
            setBtnLoading($btn, false);
            if (response.success) {
                currentSubject = subject;
                currentGrade = grade;
                currentAssessmentId = response.data.id;
                currentMode = 'view';
                
                showViewMode(response.data);
            } else {
                swalToast('warning', 'Không tìm thấy bản đánh giá. Vui lòng tạo mới.');
            }
        },
        error: function() {
            setBtnLoading($btn, false);
            swalToast('error', 'Có lỗi khi tải dữ liệu!');
        }
    });
}

function showViewMode(data) {
    const subjectName = $('#subjectFilter option:selected').text();
    const gradeName = $('#gradeFilter option:selected').text();
    
    $('#viewTitle').text(`${subjectName} - ${gradeName}`);
    $('#assessmentViewBody').empty();
    
    let totalUnits = 0;
    
    if (data.items && data.items.length > 0) {
        data.items.forEach((item, index) => {
            // Each content item may have multiple units
            if (item.units && item.units.length > 0) {
                totalUnits += item.units.length;
                item.units.forEach((unit, unitIndex) => {
                    const row = `
                        <tr>
                            ${unitIndex === 0 ? `<td class="text-center fw-bold" rowspan="${item.units.length}">${index + 1}</td>` : ''}
                            ${unitIndex === 0 ? `<td class="view-content-cell" rowspan="${item.units.length}"><pre>${escapeHtml(item.content)}</pre></td>` : ''}
                            <td><pre>${escapeHtml(unit.unit_name)}</pre></td>
                            <td class="view-level-nb"><pre>${escapeHtml(unit.nhan_biet || '')}</pre></td>
                            <td class="view-level-th"><pre>${escapeHtml(unit.thong_hieu || '')}</pre></td>
                            <td class="view-level-vd"><pre>${escapeHtml(unit.van_dung || '')}</pre></td>
                        </tr>
                    `;
                    $('#assessmentViewBody').append(row);
                });
            }
        });
    }
    
    if ($('#assessmentViewBody tr').length === 0) {
        $('#assessmentViewBody').append(`
            <tr>
                <td colspan="6">
                    <div class="empty-state py-5">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <h6>Chưa có nội dung</h6>
                        <p>Bản đánh giá này chưa có nội dung kiến thức nào.</p>
                    </div>
                </td>
            </tr>
        `);
    }
    
    const contentCount = data.items ? data.items.length : 0;
    $('#viewSummary').text(`${contentCount} nội dung · ${totalUnits} đơn vị kiến thức`).show();
    
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
        swalToast('error', 'Thiếu thông tin môn học hoặc khối!');
        return;
    }
    
    const items = [];
    let hasError = false;
    
    $('#itemsContainer .form-item-card').each(function(index) {
        const $contentCard = $(this);
        const content = $contentCard.find('.item-content').val().trim();
        
        if (!content) {
            flashCard($contentCard, `Nội dung ${index + 1}: Vui lòng nhập nội dung kiến thức!`);
            hasError = true;
            return false;
        }
        
        const units = [];
        $contentCard.find('.units-container .unit-card').each(function(unitIndex) {
            const $unitCard = $(this);
            const unitName = $unitCard.find('.unit-name').val().trim();
            const nhanBiet = $unitCard.find('.unit-nhan-biet').val().trim();
            const thongHieu = $unitCard.find('.unit-thong-hieu').val().trim();
            const vanDung = $unitCard.find('.unit-van-dung').val().trim();
            
            if (!unitName) {
                flashCard($unitCard, `Nội dung ${index + 1}, Đơn vị ${unitIndex + 1}: Vui lòng nhập tên đơn vị kiến thức!`);
                hasError = true;
                return false;
            }
            
            if (!nhanBiet && !thongHieu && !vanDung) {
                flashCard($unitCard, `Nội dung ${index + 1}, Đơn vị ${unitIndex + 1}: Vui lòng nhập ít nhất một mức độ đánh giá!`);
                hasError = true;
                return false;
            }
            
            units.push({
                unit_name: unitName,
                nhan_biet: nhanBiet,
                thong_hieu: thongHieu,
                van_dung: vanDung
            });
        });
        
        if (hasError) return false;
        
        if (units.length === 0) {
            flashCard($contentCard, `Nội dung ${index + 1}: Vui lòng thêm ít nhất một đơn vị kiến thức!`);
            hasError = true;
            return false;
        }
        
        items.push({
            order: index + 1,
            content: content,
            units: units
        });
    });
    
    if (hasError) return;
    
    if (items.length === 0) {
        swalToast('warning', 'Vui lòng thêm ít nhất một nội dung kiến thức!');
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
    
    const $btn = $('#saveAssessmentBtn');
    setBtnLoading($btn, true);
    
    $.ajax({
        url: 'api/manage_knowledge_assessment.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            setBtnLoading($btn, false);
            if (response.success) {
                currentAssessmentId = response.id;
                swalToast('success', 'Lưu bản đánh giá thành công!');
                // Reload in view mode
                loadAssessment(currentSubject, currentGrade);
            } else {
                swalToast('error', 'Lỗi: ' + (response.message || 'Không thể lưu dữ liệu'));
            }
        },
        error: function() {
            setBtnLoading($btn, false);
            swalToast('error', 'Có lỗi khi lưu dữ liệu!');
        }
    });
}

function deleteAssessment() {
    if (!currentAssessmentId) {
        swalToast('warning', 'Không có bản đánh giá nào để xóa!');
        return;
    }
    
    const $btn = $('#deleteAssessmentViewBtn');
    setBtnLoading($btn, true);
    
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
            setBtnLoading($btn, false);
            if (response.success) {
                swalToast('success', 'Xóa bản đánh giá thành công!');
                hideAllContainers();
                $('#infoAlert').show();
                currentAssessmentId = null;
                currentSubject = null;
                currentGrade = null;
            } else {
                swalToast('error', 'Lỗi: ' + (response.message || 'Không thể xóa'));
            }
        },
        error: function() {
            setBtnLoading($btn, false);
            swalToast('error', 'Có lỗi khi xóa dữ liệu!');
        }
    });
}
</script>
