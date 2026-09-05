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

<div class="main-content">
    <div class="container py-4 mb-5">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <strong>Thành công!</strong> Đề thi đã được tạo thành công.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <div class="sh-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <h3>Đề Thi Đã Tạo</h3>
                <p>Quản lý các đề thi đã tạo theo khối và môn học</p>
            </div>
            <div class="ms-auto">
                <a href="exam_creation.php?return=my_exams" class="btn btn-primary btn-action-custom">
                    <i class="bi bi-plus-circle"></i> Tạo Đề Mới
                </a>
            </div>
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
            <a href="exam_creation.php?return=my_exams" class="alert-link">Tạo đề thi đầu tiên</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover eduvn-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên Đề Thi</th>
                        <th>Khối</th>
                        <th>Môn Học</th>
                        <th>Số Câu</th>
                        <th>Điểm</th>
                        <th>Ngày Tạo</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examsList as $idx => $exam): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($exam['test_name']); ?></strong>
                                <?php if ($exam['approved'] ?? false): ?>
                                    <span class="badge badge-soft-success ms-2">Đã duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $gradeLabels[$exam['grade']] ?? $exam['grade']; ?></td>
                            <td><?php echo htmlspecialchars($exam['subject_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $exam['total_questions']; ?></td>
                            <td><?php echo $exam['total_points']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($exam['created_at'])); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-soft-info btn-sm view-exam-btn" 
                                            data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                            data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                            data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>">
                                        <i class="bi bi-eye"></i> Xem
                                    </button>
                                    
                                    <?php if ($isPremiumUser): ?>
                                        <button type="button" class="btn btn-soft-danger btn-sm export-pdf-btn"
                                                data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                                data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                                data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>">
                                            <i class="bi bi-file-pdf"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-soft-success btn-sm export-word-btn"
                                                data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                                data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                                data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>">
                                            <i class="bi bi-file-word"></i> Word
                                        </button>
                                        <button type="button" class="btn btn-soft-violet btn-sm export-word-latex-btn"
                                                data-file="<?php echo htmlspecialchars($exam['file']); ?>"
                                                data-grade="<?php echo htmlspecialchars($exam['grade']); ?>"
                                                data-subject-id="<?php echo htmlspecialchars($exam['subject_id']); ?>"
                                                title="Xuất Word với LaTeX nguyên bản (cho MathType)">
                                            <i class="bi bi-filetype-docx"></i> LaTeX
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
    window.MathJax = {
        tex: {
            inlineMath: [['$', '$'], ['\\(', '\\)']],
            displayMath: [['$$', '$$'], ['\\[', '\\]']],
            processEscapes: true,
            packages: {'[+]': ['mhchem']}
        },
        loader: {
            load: ['[tex]/mhchem']
        }
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>

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
    
    // Export to PDF
    document.querySelectorAll('.export-pdf-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const file = this.dataset.file;
            const grade = this.dataset.grade;
            const subjectId = this.getAttribute('data-subject-id');
            
            // Open PDF export page in new window
            const url = `export_exam_pdf.php?file=${encodeURIComponent(file)}&grade=${encodeURIComponent(grade)}&subject_id=${encodeURIComponent(subjectId)}`;
            window.open(url, '_blank', 'width=1000,height=800');
        });
    });
    
    // Export to Word with LaTeX
    document.querySelectorAll('.export-word-latex-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const file = this.dataset.file;
            const grade = this.dataset.grade;
            const subjectId = this.getAttribute('data-subject-id');
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_exam_word_latex.php';
            
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
        // Phân loại câu hỏi theo các phần chuẩn
        const part1 = []; // Trắc nghiệm nhiều lựa chọn
        const part2 = []; // Đúng sai
        const part3 = []; // Tự luận
        const part4 = []; // Thực hành
        
        (exam.questions || []).forEach(q => {
            const t = (q.type || '').toLowerCase();
            if (t === 'true_false_multiple' || t === 'true_false' || t === 'dungsai') {
                part2.push(q);
            } else if (t === 'essay' || t === 'tuluan' || t === 'short_answer') {
                part3.push(q);
            } else if (t === 'practice' || t === 'thuchanh') {
                part4.push(q);
            } else {
                part1.push(q);
            }
        });
        
        const calcPts = (list) => {
            let pts = 0;
            let hasPts = false;
            list.forEach(q => {
                if (q.points !== undefined && q.points !== null && !isNaN(Number(q.points))) {
                    pts += Number(q.points);
                    hasPts = true;
                }
            });
            return hasPts ? Math.round(pts * 100) / 100 : null;
        };

        const pts1 = calcPts(part1);
        const pts2 = calcPts(part2);
        const pts3 = calcPts(part3);
        const pts4 = calcPts(part4);

        let html = `
            <div class="exam-header mb-4 p-3 bg-light rounded border">
                <h3 class="text-center text-primary mb-3">${exam.test_name}</h3>
                <div class="row g-2 text-secondary" style="font-size: 14px;">
                    <div class="col-md-4">
                        <i class="bi bi-calendar-event"></i> <strong>Ngày tạo:</strong> ${new Date(exam.created_at).toLocaleDateString('vi-VN')}
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-card-checklist"></i> <strong>Tổng số câu:</strong> ${exam.total_questions || exam.questions.length} câu
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-trophy"></i> <strong>Tổng điểm:</strong> <span class="badge bg-primary fs-6">${exam.total_points} điểm</span>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-clock"></i> <strong>Thời gian:</strong> ${exam.time_limit} phút
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-pie-chart"></i> <strong>Cấu trúc:</strong> 
                        ${part1.length ? `<span class="badge bg-info text-dark">${part1.length} TNKQ</span> ` : ''}
                        ${part2.length ? `<span class="badge bg-warning text-dark">${part2.length} Đ/S</span> ` : ''}
                        ${part3.length ? `<span class="badge bg-success">${part3.length} TL</span>` : ''}
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-check-circle"></i> <strong>Trạng thái:</strong> ${exam.approved ? '<span class="badge bg-success">Đã duyệt</span>' : '<span class="badge bg-warning text-dark">Chưa duyệt</span>'}
                    </div>
                </div>
            </div>
            <div class="questions-container">
        `;

        let partRomanIndex = 1;
        const romanNumerals = ['I', 'II', 'III', 'IV', 'V'];

        // --- PHẦN I: TRẮC NGHIỆM NHIỀU LỰA CHỌN ---
        if (part1.length > 0) {
            const roman = romanNumerals[partRomanIndex - 1];
            partRomanIndex++;
            const ptsText = pts1 !== null ? `(${pts1} điểm)` : '';
            html += `
                <div class="exam-part mb-4">
                    <div class="alert alert-primary py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <strong class="text-uppercase" style="font-size:15px;">
                            PHẦN ${roman}. TRẮC NGHIỆM NHIỀU PHƯƠNG ÁN LỰA CHỌN ${ptsText}
                        </strong>
                        <span class="badge bg-primary">${part1.length} câu</span>
                    </div>
                    <p class="text-muted fst-italic ms-1 mb-3" style="font-size:13.5px;">
                        Học sinh trả lời từ câu 1 đến câu ${part1.length}, mỗi câu hỏi học sinh chỉ chọn một phương án.
                    </p>
                    <div class="part-questions">
            `;
            part1.forEach((q, idx) => {
                const qNum = idx + 1;
                const ptsBadge = q.points ? `<span class="badge bg-secondary ms-1">${q.points} đ</span>` : '';
                const levelBadge = q.level ? `<span class="badge badge-soft-info">${q.level}</span>` : '';
                html += `
                    <div class="question-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark">Câu ${qNum}:</strong>
                            <div>${levelBadge} ${ptsBadge}</div>
                        </div>
                        <div class="question-text mb-3">${q.question}</div>
                        <div class="options row g-2 ms-1">
                `;
                if (q.options && Array.isArray(q.options)) {
                    q.options.forEach((opt, optIdx) => {
                        const letter = String.fromCharCode(65 + optIdx);
                        const isCorrect = (typeof q.correct === 'number' && q.correct === optIdx) || 
                                          (typeof q.correct === 'string' && q.correct.toUpperCase() === letter) ||
                                          (Array.isArray(q.correct) && q.correct.includes(optIdx));
                        html += `
                            <div class="col-md-6 col-12">
                                <div class="p-2 rounded border ${isCorrect ? 'border-success bg-success bg-opacity-10 text-success fw-bold' : 'bg-light'}">
                                    <span class="badge ${isCorrect ? 'bg-success' : 'bg-secondary'} me-1">${letter}</span> ${opt}
                                    ${isCorrect ? ' <i class="bi bi-check-circle-fill ms-1"></i>' : ''}
                                </div>
                            </div>
                        `;
                    });
                }
                html += `
                        </div>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        // --- PHẦN II: TRẮC NGHIỆM ĐÚNG SAI ---
        if (part2.length > 0) {
            const roman = romanNumerals[partRomanIndex - 1];
            partRomanIndex++;
            const ptsText = pts2 !== null ? `(${pts2} điểm)` : '';
            html += `
                <div class="exam-part mb-4">
                    <div class="alert alert-warning py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <strong class="text-uppercase text-dark" style="font-size:15px;">
                            PHẦN ${roman}. TRẮC NGHIỆM ĐÚNG SAI ${ptsText}
                        </strong>
                        <span class="badge bg-warning text-dark">${part2.length} câu</span>
                    </div>
                    <p class="text-muted fst-italic ms-1 mb-3" style="font-size:13.5px;">
                        Học sinh trả lời từ câu 1 đến câu ${part2.length}. Trong mỗi ý a), b), c), d) ở mỗi câu, học sinh chọn đúng hoặc sai.
                    </p>
                    <div class="part-questions">
            `;
            part2.forEach((q, idx) => {
                const qNum = idx + 1;
                const ptsBadge = q.points ? `<span class="badge bg-secondary ms-1">${q.points} đ</span>` : '';
                const levelBadge = q.level ? `<span class="badge badge-soft-info">${q.level}</span>` : '';
                html += `
                    <div class="question-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark">Câu ${qNum}:</strong>
                            <div>${levelBadge} ${ptsBadge}</div>
                        </div>
                        <div class="question-text mb-3">${q.question}</div>
                        <div class="items-list ms-1">
                `;
                if (q.items && Array.isArray(q.items)) {
                    q.items.forEach((item, itemIdx) => {
                        const lbl = item.label || String.fromCharCode(97 + itemIdx);
                        const isTrue = (item.correct === true || item.correct === 1 || item.correct === 'true' || item.correct === 'dung' || item.correct === 'Đúng');
                        html += `
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded border ${isTrue ? 'bg-success bg-opacity-10 border-success' : 'bg-danger bg-opacity-10 border-danger'}">
                                <div>
                                    <strong class="me-2">${lbl})</strong> ${item.statement || item.text || ''}
                                </div>
                                <span class="badge ${isTrue ? 'bg-success' : 'bg-danger'} px-2 py-1">
                                    ${isTrue ? '<i class="bi bi-check-lg"></i> ĐÚNG' : '<i class="bi bi-x-lg"></i> SAI'}
                                </span>
                            </div>
                        `;
                    });
                }
                html += `
                        </div>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        // --- PHẦN III: TỰ LUẬN ---
        if (part3.length > 0) {
            const roman = romanNumerals[partRomanIndex - 1];
            partRomanIndex++;
            const ptsText = pts3 !== null ? `(${pts3} điểm)` : '';
            html += `
                <div class="exam-part mb-4">
                    <div class="alert alert-success py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <strong class="text-uppercase" style="font-size:15px;">
                            PHẦN ${roman}. TỰ LUẬN ${ptsText}
                        </strong>
                        <span class="badge bg-success">${part3.length} câu</span>
                    </div>
                    <div class="part-questions">
            `;
            part3.forEach((q, idx) => {
                const qNum = idx + 1;
                const ptsBadge = q.points ? `<span class="badge bg-secondary ms-1">${q.points} điểm</span>` : '';
                const levelBadge = q.level ? `<span class="badge badge-soft-info">${q.level}</span>` : '';
                html += `
                    <div class="question-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark">Câu ${qNum} ${ptsBadge}:</strong>
                            <div>${levelBadge}</div>
                        </div>
                        <div class="question-text mb-3">${q.question}</div>
                `;
                if (q.suggested_answer || q.answer) {
                    html += `
                        <div class="p-3 bg-light border-start border-success border-4 rounded mt-2">
                            <strong class="text-success"><i class="bi bi-journal-check"></i> Hướng dẫn / Dàn ý chấm:</strong>
                            <div class="mt-1 text-dark">${q.suggested_answer || q.answer}</div>
                        </div>
                    `;
                }
                html += `</div>`;
            });
            html += `</div></div>`;
        }

        // --- PHẦN IV: THỰC HÀNH (nếu có) ---
        if (part4.length > 0) {
            const roman = romanNumerals[partRomanIndex - 1];
            partRomanIndex++;
            const ptsText = pts4 !== null ? `(${pts4} điểm)` : '';
            html += `
                <div class="exam-part mb-4">
                    <div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <strong class="text-uppercase" style="font-size:15px;">
                            PHẦN ${roman}. THỰC HÀNH ${ptsText}
                        </strong>
                        <span class="badge bg-info text-dark">${part4.length} câu</span>
                    </div>
                    <div class="part-questions">
            `;
            part4.forEach((q, idx) => {
                const qNum = idx + 1;
                const ptsBadge = q.points ? `<span class="badge bg-secondary ms-1">${q.points} điểm</span>` : '';
                html += `
                    <div class="question-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark">Câu ${qNum} ${ptsBadge}:</strong>
                        </div>
                        <div class="question-text mb-2">${q.question}</div>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        // --- BẢNG TỔNG HỢP ĐÁP ÁN ---
        html += `
            <div class="exam-answer-summary mt-4 pt-3 border-top">
                <h5 class="text-primary mb-3"><i class="bi bi-key-fill"></i> Bảng Đáp Án & Biểu Điểm</h5>
                <div class="row g-3">
        `;

        if (part1.length > 0) {
            html += `
                <div class="col-md-6 col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white py-2">
                            <strong>Đáp án Phần I: Trắc nghiệm lựa chọn</strong>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-bordered table-sm mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Câu</th>
                                        <th>Đáp án</th>
                                        <th>Mức độ</th>
                                        <th>Điểm</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            part1.forEach((q, idx) => {
                let ans = '';
                if (typeof q.correct === 'number') {
                    ans = String.fromCharCode(65 + q.correct);
                } else if (typeof q.correct === 'string') {
                    ans = q.correct.toUpperCase();
                } else if (Array.isArray(q.correct)) {
                    ans = q.correct.map(c => typeof c === 'number' ? String.fromCharCode(65 + c) : c).join(', ');
                }
                html += `
                    <tr>
                        <td><strong>${idx + 1}</strong></td>
                        <td><span class="badge bg-success fs-6">${ans || '-'}</span></td>
                        <td><span class="badge bg-light text-dark">${q.level || 'NB'}</span></td>
                        <td>${q.points !== undefined ? q.points : ''}</td>
                    </tr>
                `;
            });
            html += `</tbody></table></div></div></div>`;
        }

        if (part2.length > 0) {
            html += `
                <div class="col-md-6 col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-warning text-dark py-2">
                            <strong>Đáp án Phần II: Đúng / Sai</strong>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-bordered table-sm mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Câu</th>
                                        <th>Ý a</th>
                                        <th>Ý b</th>
                                        <th>Ý c</th>
                                        <th>Ý d</th>
                                        <th>Điểm</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            part2.forEach((q, idx) => {
                const getLabel = (item) => {
                    if (!item) return '-';
                    const isT = (item.correct === true || item.correct === 1 || item.correct === 'true' || item.correct === 'dung' || item.correct === 'Đúng');
                    return isT ? '<span class="text-success fw-bold">Đ</span>' : '<span class="text-danger fw-bold">S</span>';
                };
                const items = q.items || [];
                html += `
                    <tr>
                        <td><strong>${idx + 1}</strong></td>
                        <td>${getLabel(items[0])}</td>
                        <td>${getLabel(items[1])}</td>
                        <td>${getLabel(items[2])}</td>
                        <td>${getLabel(items[3])}</td>
                        <td>${q.points !== undefined ? q.points : ''}</td>
                    </tr>
                `;
            });
            html += `</tbody></table></div></div></div>`;
        }

        html += `
                </div>
            </div>
        `;

        html += '</div>';
        
        document.getElementById('examContent').innerHTML = html;
        
        // Render MathJax after content is loaded
        if (window.MathJax) {
            MathJax.typesetPromise([document.getElementById('examContent')]).catch((err) => console.log('MathJax error:', err));
        }
    }
});
</script>

<?php include '../includes/teacher_footer.php'; ?>
