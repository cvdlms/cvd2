<?php
error_reporting(0);
ini_set('display_errors', 0);
include '../includes/session_check.php';
include '../includes/common_functions.php';
include '../includes/premium_helper.php';

$title = 'Đề Thi Đã Tạo - CVD';
include '../includes/teacher_header.php';

$username = $_SESSION['username'];
$isPremiumUser = isPremiumUser($username);

// Get teacher's assigned subjects and classes
$teacherSubjectsFile = __DIR__ . '/../admin/teacher_subjects.json';
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$teacherClassesFile = __DIR__ . '/../admin/teacher_classes.json';
$classesFile = __DIR__ . '/../admin/classes.json';

$teacherSubjects = json_decode(file_get_contents($teacherSubjectsFile), true) ?: [];
$subjects = json_decode(file_get_contents($subjectsFile), true) ?: [];
$teacherClasses = json_decode(file_get_contents($teacherClassesFile), true) ?: [];
$classes = json_decode(file_get_contents($classesFile), true) ?: [];

$assignedSubjectIds = $teacherSubjects[$username] ?? [];
$assignedClassIds = $teacherClasses[$username] ?? [];

$assignedSubjects = array_filter($subjects, function($subj) use ($assignedSubjectIds) {
    return in_array($subj['id'], $assignedSubjectIds);
});

// Map grades to class prefixes
$gradeToPrefix = [
    'khoi6' => '6',
    'khoi7' => '7',
    'khoi8' => '8',
    'khoi9' => '9',
];

// Get assigned grades
$assignedGrades = [];
foreach ($assignedClassIds as $classId) {
    foreach ($classes as $class) {
        if ($class['id'] == $classId) {
            $prefix = substr($class['code'], 0, 1);
            $grade = array_search($prefix, $gradeToPrefix);
            if ($grade && !in_array($grade, $assignedGrades)) {
                $assignedGrades[] = $grade;
            }
            break;
        }
    }
}

$gradeLabels = [
    'khoi6' => 'Khối 6',
    'khoi7' => 'Khối 7',
    'khoi8' => 'Khối 8',
    'khoi9' => 'Khối 9',
];

// Load all exams
$examsList = [];
$examsDir = __DIR__ . '/exams';

foreach ($assignedGrades as $grade) {
    foreach ($assignedSubjectIds as $subjectId) {
        $gradeSubjectDir = $examsDir . "/{$grade}/subject_{$subjectId}";
        if (is_dir($gradeSubjectDir)) {
            $files = glob($gradeSubjectDir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['teacher']) && $data['teacher'] === $username) {
                    $data['file'] = basename($file);
                    $data['grade'] = $grade;
                    $data['subject_id'] = $subjectId;
                    
                    // Get subject name
                    foreach ($subjects as $subj) {
                        if ($subj['id'] == $subjectId) {
                            $data['subject_name'] = $subj['name'];
                            break;
                        }
                    }
                    
                    $examsList[] = $data;
                }
            }
        }
    }
}

// Sort by created date (newest first)
usort($examsList, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Đề Thi Đã Tạo</h2>
        <a href="exam_creation.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tạo Đề Mới
        </a>
    </div>

    <?php if (!$isPremiumUser): ?>
        <div class="alert alert-warning">
            <i class="bi bi-star"></i> <strong>Lưu ý:</strong> Chức năng xuất file Word chỉ dành cho tài khoản Premium. 
            <a href="premium_activation.php" class="alert-link">Nâng cấp ngay</a>
        </div>
    <?php endif; ?>

    <?php if (empty($examsList)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Bạn chưa tạo đề thi nào. 
            <a href="exam_creation.php" class="alert-link">Tạo đề thi đầu tiên</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Tên Đề Thi</th>
                        <th style="width: 10%;">Khối</th>
                        <th style="width: 15%;">Môn Học</th>
                        <th style="width: 10%;">Số Câu</th>
                        <th style="width: 10%;">Điểm</th>
                        <th style="width: 10%;">Ngày Tạo</th>
                        <th style="width: 15%;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examsList as $idx => $exam): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($exam['test_name']); ?></strong>
                                <?php if ($exam['approved'] ?? false): ?>
                                    <span class="badge bg-success ms-2">Đã duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $gradeLabels[$exam['grade']] ?? $exam['grade']; ?></td>
                            <td><?php echo htmlspecialchars($exam['subject_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $exam['total_questions']; ?></td>
                            <td><?php echo $exam['total_points']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($exam['created_at'])); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-info btn-sm view-exam-btn" 
                                            data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                            data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                            data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>">
                                        <i class="bi bi-eye"></i> Xem
                                    </button>
                                    
                                    <?php if ($isPremiumUser): ?>
                                        <button type="button" class="btn btn-success btn-sm export-word-btn"
                                                data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                                data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                                data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>">
                                            <i class="bi bi-file-word"></i> Xuất Word
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>
                                            <i class="bi bi-lock"></i> Premium
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- View Exam Modal -->
<div class="modal fade" id="viewExamModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi Tiết Đề Thi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="examContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View exam details
    document.querySelectorAll('.view-exam-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const file = this.dataset.file;
            const grade = this.dataset.grade;
            const subjectId = this.getAttribute('data-subject-id');
            
            const modal = new bootstrap.Modal(document.getElementById('viewExamModal'));
            const content = document.getElementById('examContent');
            
            content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            modal.show();
            
            // Load exam data via AJAX
            fetch(`api/get_exam_details.php?file=${file}&grade=${grade}&subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayExamContent(data.exam);
                    } else {
                        content.innerHTML = '<div class="alert alert-danger">Lỗi: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">Lỗi khi tải đề thi</div>';
                });
        });
    });
    
    // Export to Word
    document.querySelectorAll('.export-word-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const file = this.dataset.file;
            const grade = this.dataset.grade;
            const subjectId = this.getAttribute('data-subject-id');
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_exam_word.php';
            
            const fileInput = document.createElement('input');
            fileInput.type = 'hidden';
            fileInput.name = 'file';
            fileInput.value = file;
            form.appendChild(fileInput);
            
            const gradeInput = document.createElement('input');
            gradeInput.type = 'hidden';
            gradeInput.name = 'grade';
            gradeInput.value = grade;
            form.appendChild(gradeInput);
            
            const subjectInput = document.createElement('input');
            subjectInput.type = 'hidden';
            subjectInput.name = 'subject_id';
            subjectInput.value = subjectId;
            form.appendChild(subjectInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    });
    
    function displayExamContent(exam) {
        let html = `
            <div class="exam-header mb-4">
                <h3 class="text-center">${exam.test_name}</h3>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Ngày tạo:</strong> ${new Date(exam.created_at).toLocaleDateString('vi-VN')}
                    </div>
                    <div class="col-md-4">
                        <strong>Số câu hỏi:</strong> ${exam.total_questions}
                    </div>
                    <div class="col-md-4">
                        <strong>Tổng điểm:</strong> ${exam.total_points}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <strong>Thời gian:</strong> ${exam.time_limit} phút
                    </div>
                    <div class="col-md-4">
                        <strong>Điểm mỗi câu:</strong> ${exam.points_per_question}
                    </div>
                    <div class="col-md-4">
                        <strong>Trạng thái:</strong> ${exam.approved ? '<span class="badge bg-success">Đã duyệt</span>' : '<span class="badge bg-warning">Chưa duyệt</span>'}
                    </div>
                </div>
            </div>
            
            <div class="questions-list">
                <h4 class="mb-3">Danh Sách Câu Hỏi</h4>
        `;
        
        exam.questions.forEach((q, idx) => {
            html += `
                <div class="question-item mb-4 p-3 border rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Câu ${idx + 1}:</strong>
                        <span class="badge bg-info">${q.level}</span>
                    </div>
                    <p>${q.question}</p>
                    <div class="options ms-3">
            `;
            
            if (q.options && Array.isArray(q.options)) {
                q.options.forEach((opt, optIdx) => {
                    const letter = String.fromCharCode(65 + optIdx);
                    const isCorrect = (typeof q.correct === 'number' && q.correct === optIdx) || 
                                    (typeof q.correct === 'string' && q.correct.toUpperCase() === letter);
                    html += `<div class="${isCorrect ? 'text-success fw-bold' : ''}">${letter}. ${opt}</div>`;
                });
            }
            
            html += `
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        document.getElementById('examContent').innerHTML = html;
    }
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
