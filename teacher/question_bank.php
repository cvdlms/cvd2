<?php
include '../includes/session_check.php';
include '../includes/common_functions.php';

$username = $_SESSION['username'];
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

// Map grades to class prefixes
$gradeToPrefix = [
    'khoi6' => '6',
    'khoi7' => '7',
    'khoi8' => '8',
    'khoi9' => '9',
];

// Get assigned grades for the teacher
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

$assignedSubjects = array_filter($subjects, function($subj) use ($assignedSubjectIds) {
    return in_array($subj['id'], $assignedSubjectIds);
});

$grades = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
$gradeLabels = [
    'khoi6' => 'Khối 6',
    'khoi7' => 'Khối 7',
    'khoi8' => 'Khối 8',
    'khoi9' => 'Khối 9',
];

// Filter grades to only show assigned ones
$availableGrades = array_values(array_intersect($grades, $assignedGrades));

$selectedGrade = (isset($_GET['grade']) && $_GET['grade'] !== '') ? $_GET['grade'] : ($availableGrades[0] ?? '');
$selectedSubjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($assignedSubjectIds ? $assignedSubjectIds[0] : 0);

// Load system config to get current semester
$systemConfig = [];
$configFile = __DIR__ . '/../admin/system_config.json';
if (file_exists($configFile)) {
    $systemConfig = json_decode(file_get_contents($configFile), true);
}
$defaultSemester = $systemConfig['semester']['current'] ?? 'hk2';

// If semester is not in URL, use the default from config
$selectedSemester = $_GET['semester'] ?? $defaultSemester;

if ($selectedSubjectId && !in_array($selectedSubjectId, $assignedSubjectIds)) {
    die('Môn học không hợp lệ hoặc không được phép.');
}

if ($selectedGrade && !in_array($selectedGrade, $grades)) {
    die('Khối không hợp lệ.');
}

$semesters = ['hk1', 'hk2'];
$semesterLabels = [
    'hk1' => 'Học kì 1',
    'hk2' => 'Học kì 2',
];

if ($selectedSemester && !in_array($selectedSemester, $semesters)) {
    die('Học kì không hợp lệ.');
}

$questions = [];
$questionsData = [];
if ($selectedGrade && $selectedSubjectId && $selectedSemester) {
    $questionsFile = __DIR__ . "/questions/{$selectedGrade}/{$selectedSemester}/subject_{$selectedSubjectId}.json";
    if (file_exists($questionsFile)) {
        $questionsData = json_decode(file_get_contents($questionsFile), true) ?: [];
        if (is_array($questionsData)) {
            foreach ($questionsData as $topicIndex => $topicData) {
                $topic = $topicData['topic'] ?? '';
                $lesson = $topicData['lesson'] ?? '';
                $lessonQuestions = $topicData['questions'] ?? [];
                foreach ($lessonQuestions as $idx => $q) {
                    $questions[] = [
                        'data' => $q,
                        'topic' => $topic,
                        'lesson' => $lesson,
                        'topicIndex' => $topicIndex,
                        'index' => $idx
                    ];
                }
            }
        }
    }
}

$importMessage = '';
$importError = '';

// Check for import messages from session
if (isset($_SESSION['import_message'])) {
    $importMessage = $_SESSION['import_message'];
    unset($_SESSION['import_message']);
}
if (isset($_SESSION['import_error'])) {
    $importError = $_SESSION['import_error'];
    unset($_SESSION['import_error']);
}

// ---------- Question type helpers ----------
if (!function_exists('qb_type_meta')) {
    function qb_type_meta($type) {
        switch ($type) {
            case 'multiple':
                return ['label' => 'Nhiều đáp án', 'full' => 'Trắc nghiệm nhiều đáp án', 'icon' => 'bi-ui-checks-grid', 'badge' => 'badge-soft-info'];
            case 'true_false_multiple':
                return ['label' => 'Đúng / Sai', 'full' => 'Đúng/Sai nhiều ý (a–d)', 'icon' => 'bi-check2-square', 'badge' => 'badge-soft-violet'];
            case 'essay':
                return ['label' => 'Tự luận', 'full' => 'Tự luận (chấm tay)', 'icon' => 'bi-pencil-square', 'badge' => 'badge-soft-warning'];
            default:
                return ['label' => 'Trắc nghiệm', 'full' => 'Trắc nghiệm 1 đáp án', 'icon' => 'bi-ui-radios', 'badge' => 'badge-soft-slate'];
        }
    }
}

if (!function_exists('qb_level_label')) {
    function qb_level_label($level, $type) {
        if ($type === 'true_false_multiple') {
            // Câu Đúng/Sai dùng thang Biết / Hiểu / Vận dụng
            $map = ['NB' => 'Biết', 'TH' => 'Hiểu', 'VD' => 'Vận dụng'];
            return $map[$level] ?? $level;
        }
        $map = ['NB' => 'Nhận biết', 'TH' => 'Thông hiểu', 'VD' => 'Vận dụng', 'VDC' => 'Vận dụng cao'];
        return $map[$level] ?? $level;
    }
}

if (!function_exists('qb_render_correct_cell')) {
    function qb_render_correct_cell($q) {
        $type = $q['type'] ?? 'single';
        if ($type === 'true_false_multiple') {
            $html = '';
            foreach (($q['items'] ?? []) as $it) {
                $yes = !empty($it['correct']);
                $letter = strtoupper((string)($it['label'] ?? '?'));
                $html .= '<span class="tf-ans-chip ' . ($yes ? 'tf-ans-true' : 'tf-ans-false') . '" title="Ý ' . htmlspecialchars($letter) . ': ' . ($yes ? 'Đúng' : 'Sai') . '">'
                    . htmlspecialchars($letter) . ' <i class="bi bi-' . ($yes ? 'check-lg' : 'x-lg') . '"></i></span>';
            }
            return $html !== '' ? $html : '<span class="text-muted small">—</span>';
        }
        if ($type === 'essay') {
            $pts = isset($q['points']) ? rtrim(rtrim(number_format((float)$q['points'], 2, '.', ''), '0'), '.') : '';
            return '<span class="badge badge-soft-warning"><i class="bi bi-pencil me-1"></i>Chấm tay</span>'
                . ($pts !== '' ? ' <strong class="ms-1">' . htmlspecialchars($pts) . 'đ</strong>' : '');
        }
        return renderCorrect($q['correct'] ?? null, $q['options'] ?? []);
    }
}

include 'question_bank_handlers.php';

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

$title = 'Quản Lý Ngân Hàng Câu Hỏi - EDUVN EXAMS';
include '../includes/teacher_header.php';
?>

<style>
    /* Accordions */
    .qb-accordion .accordion-item {
        border: 1px solid var(--border-soft);
        border-radius: var(--radius) !important;
        margin-bottom: 14px;
        overflow: hidden;
        box-shadow: var(--shadow-xs);
    }
    .qb-accordion .accordion-button {
        padding: 18px 24px;
        font-weight: 700;
        font-size: 1rem;
        color: var(--ink);
        background: var(--surface);
        box-shadow: none;
    }
    .qb-accordion .accordion-button:not(.collapsed) {
        background: var(--accent-light);
        color: var(--accent);
    }
    .qb-accordion .accordion-button::after {
        background-size: 1.25rem;
    }

    /* ---------- Type selector cards ---------- */
    .qb-type-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    @media (min-width: 768px) {
        .qb-type-grid { grid-template-columns: repeat(4, 1fr); }
    }
    .qb-type-card {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        padding: 14px 8px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        cursor: pointer;
        text-align: center;
        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    .qb-type-card:hover { border-color: var(--accent-mist); transform: translateY(-1px); }
    .qb-type-card i { font-size: 1.35rem; color: var(--muted); transition: color .18s ease; }
    .qb-type-name { font-weight: 700; font-size: .84rem; color: var(--ink); }
    .qb-type-desc { font-size: .7rem; color: var(--muted); }
    .qb-type-card input { position: absolute; opacity: 0; pointer-events: none; }
    .qb-type-card.is-selected {
        border-color: var(--accent);
        background: var(--accent-light);
        box-shadow: 0 8px 18px -10px rgba(79, 70, 229, .5);
    }
    .qb-type-card.is-selected i,
    .qb-type-card.is-selected .qb-type-desc { color: var(--accent-dark); }
    .qb-type-card--tf.is-selected {
        border-color: var(--violet);
        background: var(--violet-light);
        box-shadow: 0 8px 18px -10px rgba(139, 92, 246, .5);
    }
    .qb-type-card--tf.is-selected i,
    .qb-type-card--tf.is-selected .qb-type-desc { color: #6D28D9; }
    .qb-type-card--essay.is-selected {
        border-color: var(--gold);
        background: var(--warning-light);
        box-shadow: 0 8px 18px -10px rgba(245, 158, 11, .5);
    }
    .qb-type-card--essay.is-selected i,
    .qb-type-card--essay.is-selected .qb-type-desc { color: #92400E; }

    /* ---------- Section boxes (MCQ / DS / Tu luan) ---------- */
    .qb-section-box {
        border: 1.5px dashed var(--border);
        border-radius: var(--radius-sm);
        padding: 16px;
        animation: qbSectionIn .25s ease;
    }
    @keyframes qbSectionIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .qb-section-mcq { background: #FBFBFE; }
    .qb-section-tf { background: linear-gradient(180deg, #FCFBFF, #F8F6FF); border-color: #DDD6FE; }
    .qb-section-essay { background: linear-gradient(180deg, #FFFDF7, #FFF9EC); border-color: #FDE68A; }
    .qb-section-title { font-weight: 700; font-size: .88rem; color: var(--ink); margin-bottom: 10px; }

    /* ---------- True/False item rows ---------- */
    .tf-item-row {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 8px 12px;
        margin-bottom: 8px;
        animation: qbSectionIn .2s ease;
    }
    .tf-item-row:focus-within { border-color: var(--violet); box-shadow: 0 0 0 .2rem rgba(139, 92, 246, .1); }
    .tf-item-letter {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        background: var(--grad-violet);
        color: #fff;
        font-family: var(--display);
        font-size: .78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tf-item-row .tf-statement-input { flex: 1 1 auto; min-width: 0; }
    .tf-toggle-group {
        display: inline-flex;
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--surface);
    }
    .tf-toggle-btn {
        border: none;
        background: var(--surface);
        color: var(--muted-strong);
        font-size: .76rem;
        font-weight: 700;
        padding: 7px 14px;
        line-height: 1.2;
        transition: background .15s ease, color .15s ease;
    }
    .tf-toggle-btn + .tf-toggle-btn { border-left: 1px solid var(--border); }
    .tf-toggle-btn.tf-yes.is-active { background: var(--success); color: #fff; }
    .tf-toggle-btn.tf-no.is-active { background: var(--danger); color: #fff; }
    .tf-remove-btn {
        border: none;
        background: transparent;
        color: var(--muted);
        border-radius: 8px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .15s ease, color .15s ease;
    }
    .tf-remove-btn:hover { background: var(--danger-light); color: var(--danger); }

    /* ---------- Answer chips in list (a✓ b✗ c✓ d✗) ---------- */
    .tf-ans-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-family: var(--mono);
        font-size: .72rem;
        font-weight: 600;
        border-radius: 8px;
        padding: 2px 7px;
        margin-right: 3px;
        margin-bottom: 2px;
    }
    .tf-ans-true { background: var(--success-light); color: #047857; }
    .tf-ans-false { background: var(--danger-light); color: #B91C1C; }
</style>

<div class="main-content">
    <div class="container py-4 mb-5">
        
        <!-- Section Header -->
        <div class="section-header justify-content-between align-items-center flex-wrap gap-3 mb-4 eduvn-reveal">
            <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1 1 auto;">
                <div class="sh-icon flex-shrink-0">
                    <i class="bi bi-collection-fill"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 class="mb-0">Ngân Hàng Câu Hỏi</h3>
                    <p class="mb-0 text-muted">Quản lý, tìm kiếm và phân loại câu hỏi theo Khối lớp, Môn học và Học kỳ.</p>
                </div>
            </div>
            
            <?php if ($selectedGrade && $selectedSubjectId && $selectedSemester): ?>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                    <button class="btn btn-primary btn-action-custom text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#addQuestionForm">
                        <i class="bi bi-plus-lg me-1"></i>Thêm Câu Hỏi
                    </button>
                    <button class="btn btn-outline-danger btn-action-custom text-nowrap" id="deleteAllBtn" type="button">
                        <i class="bi bi-trash me-1"></i>Xóa Tất Cả
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter Card -->
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-primary"></i>
                <span>Phạm Vi Tìm Kiếm & Lọc Câu Hỏi</span>
            </h5>
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="grade" class="form-label fw-bold">KHỐI LỚP</label>
                    <select id="grade" name="grade" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Chọn khối lớp --</option>
                        <?php foreach ($availableGrades as $g): ?>
                            <option value="<?php echo $g; ?>" <?php if ($g === $selectedGrade) echo 'selected'; ?>>
                                <?php echo $gradeLabels[$g] ?? ucfirst($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="subject_id" class="form-label fw-bold">MÔN HỌC</label>
                    <select id="subject_id" name="subject_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Chọn môn học --</option>
                        <?php foreach ($assignedSubjects as $subj): ?>
                            <option value="<?php echo $subj['id']; ?>" <?php if ($subj['id'] == $selectedSubjectId) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($subj['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="semester" class="form-label fw-bold">HỌC KÌ</label>
                    <select id="semester" name="semester" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Chọn học kì --</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem; ?>" <?php if ($sem === $selectedSemester) echo 'selected'; ?>>
                                <?php echo $semesterLabels[$sem]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($importError): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Lỗi!</strong> <?php echo htmlspecialchars($importError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($importMessage): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <strong><i class="bi bi-check-circle-fill me-1"></i> Thành công!</strong> <?php echo htmlspecialchars($importMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($selectedGrade && $selectedSubjectId && $selectedSemester): ?>
            <?php include 'question_bank_form.php'; ?>
        <?php endif; ?>

        <!-- Questions Accordion Section -->
        <?php if ($selectedGrade && $selectedSubjectId && $selectedSemester): ?>
            <div class="accordion qb-accordion" id="topicsAccordion">
                <?php
                $groupedTopics = [];
                if (is_array($questionsData)) {
                    foreach ($questionsData as $topicIndex => $topicData) {
                        $topicName = trim($topicData['topic'] ?? 'Chủ đề không xác định');
                        $lessonName = trim($topicData['lesson'] ?? 'Bài học không xác định');
                        $lessonQuestions = $topicData['questions'] ?? [];

                        if (!isset($groupedTopics[$topicName])) {
                            $groupedTopics[$topicName] = [
                                'topic' => $topicName,
                                'lessons' => [],
                                'total_questions' => 0
                            ];
                        }

                        if (!isset($groupedTopics[$topicName]['lessons'][$lessonName])) {
                            $groupedTopics[$topicName]['lessons'][$lessonName] = [];
                        }

                        foreach ($lessonQuestions as $idx => $q) {
                            $groupedTopics[$topicName]['lessons'][$lessonName][] = [
                                'data' => $q,
                                'topicIndex' => $topicIndex,
                                'index' => $idx
                            ];
                            $groupedTopics[$topicName]['total_questions']++;
                        }
                    }
                }

                $topicCounter = 0;
                $globalIndex = 0;
                foreach ($groupedTopics as $topicName => $groupInfo):
                    $topicCounter++;
                    $totalQuestionsInTopic = $groupInfo['total_questions'];
                    $lessons = $groupInfo['lessons'];
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $topicCounter; ?>">
                            <button class="accordion-button <?php echo $topicCounter > 1 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $topicCounter; ?>" aria-expanded="<?php echo $topicCounter === 1 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $topicCounter; ?>">
                                <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>
                                <span><?php echo htmlspecialchars($topicName); ?></span>
                                <span class="badge badge-soft ms-3"><?php echo $totalQuestionsInTopic; ?> câu hỏi</span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $topicCounter; ?>" class="accordion-collapse collapse <?php echo $topicCounter === 1 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $topicCounter; ?>" data-bs-parent="#topicsAccordion">
                            <div class="accordion-body p-3">
                                <?php foreach ($lessons as $lesson => $lessonQuestions): ?>
                                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                                        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-book-half text-info me-2"></i><?php echo htmlspecialchars($lesson); ?></h6>
                                            <span class="badge badge-soft-slate"><?php echo count($lessonQuestions); ?> câu</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-qb eduvn-table table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 50px;">#</th>
                                                            <th>Nội dung câu hỏi</th>
                                                            <th>Đáp án đúng</th>
                                                            <th style="width: 140px;">Loại câu</th>
                                                            <th style="width: 130px;">Mức độ</th>
                                                            <th style="width: 100px;" class="text-end">Thao tác</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($lessonQuestions as $item): ?>
                                                            <?php
                                                            $q = $item['data'];
                                                            $topicIndex = $item['topicIndex'];
                                                            $flatIndex = $globalIndex++;
                                                            $qImage = trim($q['image'] ?? '');
                                                            ?>
                                                            <tr onclick="if (!event.target.closest('.delete-question')) { const modal = new bootstrap.Modal(document.getElementById('questionModal<?php echo $flatIndex; ?>')); modal.show(); }" style="cursor:pointer;">
                                                                <td class="fw-bold text-muted"><?php echo $flatIndex + 1; ?></td>
                                                                <td class="fw-semibold text-dark">
                                                                    <?php echo strip_tags($q['question'], '<img>'); ?>
                                                                    <?php if ($qImage): ?>
                                                                        <span class="d-block mt-2">
                                                                            <img src="<?php echo htmlspecialchars($qImage); ?>" alt="Hình minh họa" class="rounded" style="max-width:70px; max-height:50px; object-fit:cover; border:1px solid var(--border-soft);">
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo qb_render_correct_cell($q); ?></td>
                                                                <td>
                                                                    <?php $typeMeta = qb_type_meta($q['type'] ?? 'single'); ?>
                                                                    <span class="badge <?php echo $typeMeta['badge']; ?>">
                                                                        <i class="bi <?php echo $typeMeta['icon']; ?> me-1"></i><?php echo $typeMeta['label']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $level = $q['level'] ?? 'NB';
                                                                    $levelMap = [
                                                                        'NB' => ['label' => 'Nhận biết', 'class' => 'level-nb'],
                                                                        'TH' => ['label' => 'Thông hiểu', 'class' => 'level-th'],
                                                                        'VD' => ['label' => 'Vận dụng', 'class' => 'level-vd'],
                                                                        'VDC' => ['label' => 'Vận dụng cao', 'class' => 'level-vdc'],
                                                                    ];
                                                                    $lvlData = $levelMap[$level] ?? ['label' => $level, 'class' => 'level-nb'];
                                                                    $lvlData['label'] = qb_level_label($level, $q['type'] ?? 'single');
                                                                    ?>
                                                                    <span class="level-chip <?php echo $lvlData['class']; ?>"><?php echo $lvlData['label']; ?></span>
                                                                </td>
                                                                <td class="text-end">
                                                                    <button class="btn btn-outline-danger btn-sm rounded-circle p-2 delete-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" title="Xóa câu hỏi">
                                                                        <i class="bi bi-trash-fill"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>

                                                            <!-- Question Detail Modal -->
                                                            <div class="modal fade" id="questionModal<?php echo $flatIndex; ?>" tabindex="-1" aria-labelledby="questionModalLabel<?php echo $flatIndex; ?>" aria-hidden="true">
                                                              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                                <div class="modal-content">
                                                                  <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold" id="questionModalLabel<?php echo $flatIndex; ?>"><i class="bi bi-patch-question-fill me-2 text-warning"></i>Chi tiết câu hỏi #<?php echo $flatIndex + 1; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                  </div>
                                                                  <div class="modal-body">
                                                                    <div class="mb-3 p-3 bg-light rounded-3 border">
                                                                        <h6 class="text-secondary small fw-bold text-uppercase mb-1">CÂU HỎI</h6>
                                                                        <div class="fs-6 fw-bold text-dark"><?php echo strip_tags($q['question'], '<img>'); ?></div>
                                                                        <?php if ($qImage): ?>
                                                                            <img src="<?php echo htmlspecialchars($qImage); ?>" alt="Hình minh họa" class="mt-3 rounded" style="max-width:100%; max-height:320px;">
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-6">
                                                                            <div class="p-2 border rounded text-center">
                                                                                <small class="text-muted d-block">LOẠI CÂU HỎI</small>
                                                                                <strong class="text-dark"><?php echo htmlspecialchars(qb_type_meta($q['type'] ?? 'single')['full']); ?></strong>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="p-2 border rounded text-center">
                                                                                <small class="text-muted d-block">MỨC ĐỘ</small>
                                                                                <strong class="text-dark"><?php echo $lvlData['label']; ?></strong>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <?php if (($q['type'] ?? 'single') === 'true_false_multiple'): ?>
                                                                        <h6 class="fw-bold mb-2 text-secondary small text-uppercase">CÁC Ý PHÁT BIỂU ĐÚNG/SAI:</h6>
                                                                        <div class="list-group">
                                                                            <?php foreach (($q['items'] ?? []) as $it):
                                                                                $isTrue = !empty($it['correct']);
                                                                            ?>
                                                                                <div class="list-group-item d-flex align-items-center justify-content-between gap-2">
                                                                                    <div class="d-flex align-items-center gap-2">
                                                                                        <span class="tf-item-letter"><?php echo htmlspecialchars((string)($it['label'] ?? '?')); ?></span>
                                                                                        <span><?php echo htmlspecialchars((string)($it['statement'] ?? '')); ?></span>
                                                                                    </div>
                                                                                    <span class="tf-ans-chip <?php echo $isTrue ? 'tf-ans-true' : 'tf-ans-false'; ?> flex-shrink-0">
                                                                                        <i class="bi bi-<?php echo $isTrue ? 'check-lg' : 'x-lg'; ?>"></i><?php echo $isTrue ? 'Đúng' : 'Sai'; ?>
                                                                                    </span>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php elseif (($q['type'] ?? 'single') === 'essay'): ?>
                                                                        <div class="row g-3">
                                                                            <div class="col-md-4">
                                                                                <div class="p-2 border rounded text-center bg-light">
                                                                                    <small class="text-muted d-block">ĐIỂM TỐI ĐA</small>
                                                                                    <strong class="fs-5 text-dark"><?php echo isset($q['points']) ? rtrim(rtrim(number_format((float)$q['points'], 2, '.', ''), '0'), '.') : '—'; ?> đ</strong>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <small class="text-muted d-block fw-bold text-uppercase mb-1">ĐÁP ÁN GỢI Ý / DÀN Ý CHẤM:</small>
                                                                                <?php if (!empty($q['suggested_answer'])): ?>
                                                                                    <div class="p-3 border rounded bg-light" style="white-space: pre-wrap;"><?php echo htmlspecialchars($q['suggested_answer']); ?></div>
                                                                                <?php else: ?>
                                                                                    <em class="text-muted">Chưa có đáp án gợi ý cho câu này.</em>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <h6 class="fw-bold mb-2 text-secondary small text-uppercase">DANH SÁCH LỰA CHỌN:</h6>
                                                                    <div class="list-group">
                                                                        <?php
                                                                        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                                                                        $correctIndices = is_array($q['correct']) ? $q['correct'] : [$q['correct']];
                                                                        foreach ($q['options'] as $idx => $opt):
                                                                            $isCorrect = in_array($idx, $correctIndices);
                                                                        ?>
                                                                            <div class="list-group-item d-flex align-items-center justify-content-between <?php echo $isCorrect ? 'list-group-item-success fw-bold' : ''; ?>">
                                                                                <div>
                                                                                    <span class="badge bg-secondary me-2"><?php echo $letters[$idx] ?? $idx; ?></span>
                                                                                    <span><?php echo htmlspecialchars($opt); ?></span>
                                                                                </div>
                                                                                <?php if ($isCorrect): ?>
                                                                                    <span class="badge badge-soft-success"><i class="bi bi-check-circle-fill me-1"></i> ĐÚNG</span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                  </div>
                                                                  <div class="modal-footer">
                                                                    <button type="button" class="btn btn-warning px-4 fw-bold edit-question" data-topic-index="<?php echo $topicIndex; ?>" data-index="<?php echo $item['index']; ?>" data-flat-index="<?php echo $flatIndex; ?>" title="Sửa câu hỏi">
                                                                        <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
                                                                  </div>
                                                                </div>
                                                              </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($questionsData)): ?>
                <div class="alert alert-info p-4 text-center">
                    <i class="bi bi-info-circle-fill fs-3 d-block mb-2 text-info"></i>
                    Chưa có câu hỏi nào trong phạm vi môn học và học kì đã chọn.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info rounded-3 shadow-sm p-4 text-center">
                <i class="bi bi-hand-index-thumb-fill fs-3 d-block mb-2 text-info"></i>
                Vui lòng chọn Khối lớp, Môn học và Học kì để xem danh sách câu hỏi.
            </div>
        <?php endif; ?>

        <!-- Import Questions Section -->
        <div class="mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Nhập Câu Hỏi Từ File JSON / Excel</h5>
                    <button class="btn btn-light text-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#excelAddModal">Thêm từ Excel</button>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_questions">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="import_grade" class="form-label fw-bold">KHỐI LỚP</label>
                                <select id="import_grade" name="import_grade" class="form-select" required>
                                    <option value="">-- Chọn khối --</option>
                                    <?php foreach ($availableGrades as $g): ?>
                                        <option value="<?php echo $g; ?>"><?php echo htmlspecialchars($gradeLabels[$g] ?? ucfirst($g)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="import_subject_id" class="form-label fw-bold">MÔN HỌC</label>
                                <select id="import_subject_id" name="import_subject_id" class="form-select" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php foreach ($assignedSubjects as $subj): ?>
                                        <option value="<?php echo $subj['id']; ?>" <?php if ($subj['id'] == $selectedSubjectId) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($subj['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="import_semester" class="form-label fw-bold">HỌC KÌ</label>
                                <select id="import_semester" name="import_semester" class="form-select" required>
                                    <option value="">-- Chọn học kì --</option>
                                    <option value="hk1" <?php if ($defaultSemester === 'hk1') echo 'selected'; ?>>Học kì 1</option>
                                    <option value="hk2" <?php if ($defaultSemester === 'hk2') echo 'selected'; ?>>Học kì 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="questions_file" class="form-label fw-bold">CHỌN FILE JSON</label>
                                <input type="file" id="questions_file" name="questions_file" class="form-control" accept=".json" required />
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold btn-action-custom">
                                <i class="bi bi-cloud-upload-fill"></i> Tải Lên & Nhập Dữ Liệu
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 p-3 bg-light border rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-code-slash text-primary me-2"></i>Định dạng file JSON mẫu:</h6>
                            <button class="btn btn-sm btn-outline-secondary" id="copyJsonBtn"><i class="bi bi-clipboard me-1"></i> Sao chép</button>
                        </div>
                        <pre class="bg-dark text-success p-3 rounded-3 mb-0" style="max-height: 250px; font-size: 0.85rem; overflow-x: auto; white-space: pre;"><code id="jsonSample">[
  {
    "topic": "Chủ đề 1",
    "lesson": "Bài 1",
    "questions": [
      {
        "question": "Câu hỏi trắc nghiệm?",
        "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
        "correct": 0,
        "type": "single",
        "level": "NB",
        "image": ""
      },
      {
        "question": "(Câu dẫn/tình huống 2-3 dòng...)",
        "type": "true_false_multiple",
        "level": "TH",
        "image": "",
        "items": [
          { "label": "a", "statement": "Phát biểu ý a...", "correct": true },
          { "label": "b", "statement": "Phát biểu ý b...", "correct": false },
          { "label": "c", "statement": "Phát biểu ý c...", "correct": false },
          { "label": "d", "statement": "Phát biểu ý d...", "correct": true }
        ]
      },
      {
        "question": "Câu hỏi tự luận?",
        "type": "essay",
        "level": "VD",
        "suggested_answer": "Dàn ý chấm: 1) ... (1đ) 2) ... (1đ)",
        "image": ""
      }
    ]
  }
]</code></pre>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'question_bank_modals.php'; ?>

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
    window.questionsData = <?php echo json_encode($questionsData); ?>;
</script>
<script src="../includes/toast-notifications.js?v=<?php echo filemtime(__DIR__ . '/../includes/toast-notifications.js'); ?>"></script>
<script src="question_bank.js?v=<?php echo filemtime(__DIR__ . '/question_bank.js'); ?>"></script>

<?php include '../includes/teacher_footer.php'; ?>
