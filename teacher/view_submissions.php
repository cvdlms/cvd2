<?php
include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$assignmentId = $_GET['id'] ?? '';
if (empty($assignmentId)) {
    header('Location: manage_assignments.php');
    exit;
}

// Load assignment
$assignmentsFile = __DIR__ . '/../data/assignments.json';
$assignments = json_decode(file_get_contents($assignmentsFile), true) ?: [];
$assignment = null;
foreach ($assignments as $a) {
    if ($a['id'] === $assignmentId && $a['teacher_username'] === $_SESSION['username']) {
        $assignment = $a;
        break;
    }
}

if (!$assignment) {
    header('Location: manage_assignments.php');
    exit;
}

// Load subjects
$subjectsFile = __DIR__ . '/../admin/subjects.json';
$subjectsData = json_decode(file_get_contents($subjectsFile), true) ?: [];
$subjects = [];
foreach ($subjectsData as $subject) {
    $subjects[$subject['id']] = $subject['name'];
}

$title = 'Xem Bài Nộp - CVD';
include '../includes/teacher_header.php';
?>

<div class="main-content">
<div class="container my-5">
    <div class="section-header flex-wrap eduvn-reveal">
        <div class="sh-icon">
            <i class="bi bi-file-earmark-text"></i>
        </div>
        <div>
            <h3 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h3>
            <p class="mb-1">
                <i class="bi bi-book me-1"></i><?php echo $subjects[$assignment['subject_id']] ?? $assignment['subject_id']; ?>
                <span class="mx-2">|</span>
                <i class="bi bi-people me-1"></i><?php 
                    $classNames = $assignment['class_names'] ?? [$assignment['class_name'] ?? ''];
                    if (is_string($classNames)) $classNames = [$classNames];
                    echo htmlspecialchars(implode(', ', $classNames)); 
                ?>
            </p>
            <p class="mb-0">
                <i class="bi bi-calendar-event me-1"></i>Hạn nộp: <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?>
                <span class="mx-2">|</span>
                <i class="bi bi-star me-1"></i>Điểm tối đa: <?php echo $assignment['max_score']; ?>
            </p>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-success btn-action-custom" onclick="exportAssignmentScores()">
                <i class="bi bi-file-earmark-excel me-2"></i>Xuất Excel
            </button>
            <a href="manage_assignments.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-2"></i>Quay Lại
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Mô Tả Bài Tập
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($assignment['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-collection me-2 text-primary"></i>Danh Sách Bài Nộp
                </div>
                <div class="card-body">
                    <table id="submissionsTable" class="table table-striped table-hover eduvn-table">
                        <thead>
                            <tr>
                                <th>Học Sinh</th>
                                <th>Lớp</th>
                                <th>Thời Gian Nộp</th>
                                <th>Trạng Thái</th>
                                <th>Điểm</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="submissionsBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- View Submission Modal -->
<div class="modal fade eduvn-modal" id="viewSubmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content submission-modal-content">
            <div class="modal-header submission-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="sub-avatar" id="viewStudentAvatar">--</div>
                    <div>
                        <h5 class="modal-title sub-title mb-0" id="viewStudentName">Học sinh</h5>
                        <div class="sub-meta-line">
                            <span id="viewStatus"></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="meta-tile">
                            <div class="meta-tile-ico ico-indigo"><i class="bi bi-person-vcard"></i></div>
                            <div class="meta-tile-txt">
                                <span class="meta-tile-label">Mã học sinh</span>
                                <span class="meta-tile-value mono" id="viewStudentCode">---</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="meta-tile">
                            <div class="meta-tile-ico ico-violet"><i class="bi bi-mortarboard"></i></div>
                            <div class="meta-tile-txt">
                                <span class="meta-tile-label">Lớp</span>
                                <span class="meta-tile-value" id="viewStudentClass">---</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="meta-tile">
                            <div class="meta-tile-ico ico-teal"><i class="bi bi-clock-history"></i></div>
                            <div class="meta-tile-txt">
                                <span class="meta-tile-label">Thời gian nộp</span>
                                <span class="meta-tile-value" id="viewSubmittedAt">---</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="meta-tile">
                            <div class="meta-tile-ico ico-gold"><i class="bi bi-people"></i></div>
                            <div class="meta-tile-txt">
                                <span class="meta-tile-label">Thành viên nhóm</span>
                                <span class="meta-tile-value" id="viewGroupMembers">Không có</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-panel reveal-step">
                    <div class="content-panel-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="cp-ico"><i class="bi bi-journal-text"></i></div>
                            <span>Nội dung bài làm</span>
                        </div>
                        <span class="cp-hint" id="viewContentCount"></span>
                    </div>
                    <div class="content-panel-body" id="viewContent">(Không có nội dung)</div>
                </div>

                <div class="content-panel reveal-step" id="documentsSection" style="display: none;">
                    <div class="content-panel-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="cp-ico ico-teal"><i class="bi bi-paperclip"></i></div>
                            <span>File bài tập đính kèm</span>
                        </div>
                        <span class="cp-hint" id="viewDocumentsCount"></span>
                    </div>
                    <div id="viewDocuments" class="doc-list"></div>
                </div>

                <div class="content-panel reveal-step">
                    <div class="content-panel-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="cp-ico ico-violet"><i class="bi bi-images"></i></div>
                            <span>Hình ảnh đính kèm</span>
                        </div>
                        <span class="cp-hint" id="viewImagesCount"></span>
                    </div>
                    <div id="viewImages" class="img-grid"></div>
                </div>

                <div class="grading-panel reveal-step">
                    <div class="grading-panel-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="cp-ico ico-gold"><i class="bi bi-pencil-square"></i></div>
                            <span>Chấm điểm</span>
                        </div>
                        <span class="grading-hint">Điểm tối đa: <b><?php echo $assignment['max_score']; ?></b></span>
                    </div>
                    <input type="hidden" id="gradeSubmissionId">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="gradeScore">Điểm <span class="text-danger">*</span></label>
                            <div class="score-input-wrap">
                                <input type="number" class="form-control score-input" id="gradeScore" min="0" max="<?php echo $assignment['max_score']; ?>" step="0.5" placeholder="--">
                                <span class="score-max">/ <?php echo $assignment['max_score']; ?></span>
                            </div>
                            <div class="score-chips">
                                <button type="button" class="score-chip" onclick="setScore('<?php echo $assignment['max_score']; ?>')">Tối đa</button>
                                <button type="button" class="score-chip" onclick="setScoreHalf()">Một nửa</button>
                                <button type="button" class="score-chip chip-danger" onclick="setScore(0)">0 điểm</button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="gradeFeedback">Nhận xét</label>
                            <textarea class="form-control feedback-input" id="gradeFeedback" rows="3" placeholder="Nhập nhận xét cho học sinh..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer submission-modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>Đóng
                </button>
                <button type="button" class="btn btn-gradient-primary btn-save-grade" onclick="saveGrade()">
                    <i class="bi bi-check2-circle me-2"></i>Lưu điểm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white"><i class="bi bi-image me-2"></i>Xem hình ảnh bài nộp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagePreviewModalImg" src="" alt="Hình ảnh bài nộp" class="img-fluid rounded" style="max-height: 75vh;">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" onclick="openPreviewImageInNewTab()">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Mở tab mới
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.btn-gradient-primary {
    background: var(--grad-accent);
    border: none;
    color: #fff;
    border-radius: 11px;
    font-weight: 600;
}

.btn-gradient-primary:hover {
    box-shadow: var(--shadow-accent);
    color: #fff;
    transform: translateY(-1px);
}

.badge-graded {
    background: var(--grad-accent);
    color: #fff;
    border-radius: 8px;
    padding: 5px 10px;
    font-weight: 600;
    font-size: .72rem;
}

.badge-submitted {
    background: var(--grad-success);
    color: #fff;
    border-radius: 8px;
    padding: 5px 10px;
    font-weight: 600;
    font-size: .72rem;
}

/* ---------- Submission modal (EDUVN EXAMS) ---------- */
.eduvn-modal .modal-content {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 32px 64px -24px rgba(15, 23, 42, .32);
    animation: subModalIn .28s cubic-bezier(.2, .8, .2, 1);
}

@keyframes subModalIn {
    from { opacity: 0; transform: translateY(16px) scale(.985); }
    to   { opacity: 1; transform: none; }
}

.submission-modal-header {
    position: relative;
    background: var(--grad-accent);
    border: none;
    padding: 22px 26px;
    color: #fff;
}

.submission-modal-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(120% 160% at 100% 0, rgba(255, 255, 255, .18) 0%, transparent 46%);
    pointer-events: none;
}

.submission-modal-header .btn-close {
    z-index: 1;
    filter: invert(1) grayscale(100%) brightness(200%);
    opacity: .85;
}

.submission-modal-header .btn-close:hover {
    opacity: 1;
}

.sub-avatar {
    width: 52px;
    height: 52px;
    flex: none;
    border-radius: 16px;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .35);
    color: #fff;
    display: grid;
    place-items: center;
    font-family: var(--display);
    font-weight: 800;
    font-size: 1.15rem;
    box-shadow: 0 8px 20px -8px rgba(20, 23, 51, .5);
    backdrop-filter: blur(4px);
}

.sub-title {
    font-family: var(--display);
    font-weight: 800;
    font-size: 1.05rem;
    color: #fff;
}

.sub-meta-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 3px;
}

.submission-modal-body {
    padding: 24px 26px;
}

/* Meta tiles */
.meta-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--surface);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius);
    padding: 13px 15px;
    box-shadow: var(--shadow-xs);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.meta-tile:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
    border-color: var(--accent-mist);
}

.meta-tile-ico {
    width: 40px;
    height: 40px;
    flex: none;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.05rem;
}

.ico-indigo { background: var(--accent-light); color: var(--accent); }
.ico-violet { background: var(--violet-light); color: var(--violet); }
.ico-teal   { background: var(--success-light); color: #059669; }
.ico-gold   { background: var(--warning-light); color: #B45309; }

.meta-tile-txt {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.meta-tile-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--muted);
}

.meta-tile-value {
    font-size: .85rem;
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.meta-tile-value.mono {
    font-family: var(--mono);
    font-size: .78rem;
}

/* Content panels */
.content-panel {
    background: var(--surface);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius);
    box-shadow: var(--shadow-xs);
    margin-bottom: 18px;
    overflow: hidden;
}

.content-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border-soft);
    background: #FAFBFF;
    font-family: var(--display);
    font-weight: 700;
    font-size: .85rem;
    color: var(--ink);
}

.cp-ico {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    background: var(--accent-light);
    color: var(--accent);
    display: grid;
    place-items: center;
    font-size: .95rem;
}

.cp-ico.ico-teal  { background: var(--success-light); color: #059669; }
.cp-ico.ico-violet { background: var(--violet-light); color: var(--violet); }
.cp-ico.ico-gold  { background: var(--warning-light); color: #B45309; }

.cp-hint {
    font-family: var(--mono);
    font-size: .7rem;
    color: var(--muted);
}

.content-panel-body {
    padding: 18px;
    white-space: pre-wrap;
    font-size: .875rem;
    line-height: 1.75;
    color: var(--ink);
    max-height: 320px;
    overflow: auto;
    background: linear-gradient(180deg, #FFFFFF 0%, #FCFDFF 100%);
}

/* Documents */
.doc-list {
    padding: 10px;
}

.doc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid var(--border-soft);
    border-left: 4px solid var(--accent);
    border-radius: 12px;
    margin-bottom: 8px;
    background: var(--surface);
    transition: all .18s ease;
}

.doc-item:last-child {
    margin-bottom: 0;
}

.doc-item:hover {
    background: #FAFBFF;
    transform: translateX(4px);
    border-left-color: var(--accent-dark);
    box-shadow: var(--shadow-xs);
}

.doc-icon {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 11px;
    display: grid;
    place-items: center;
    font-size: 1.15rem;
}

.doc-icon.word  { background: var(--info-light); color: #0369A1; }
.doc-icon.excel { background: var(--success-light); color: #047857; }
.doc-icon.pdf   { background: var(--danger-light); color: #B91C1C; }
.doc-icon.ppt   { background: var(--warning-light); color: #B45309; }
.doc-icon.zip   { background: var(--violet-light); color: #6D28D9; }
.doc-icon.other { background: #EEF0F7; color: var(--muted-strong); }

.doc-name {
    font-size: .85rem;
    font-weight: 600;
    color: var(--ink);
    word-break: break-word;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 380px;
}

.doc-size {
    font-size: .72rem;
    color: var(--muted);
}

.doc-badge {
    font-family: var(--mono);
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .04em;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
}

.doc-badge.badge-word  { background: var(--info-light); color: #0369A1; }
.doc-badge.badge-excel { background: var(--success-light); color: #047857; }
.doc-badge.badge-pdf   { background: var(--danger-light); color: #B91C1C; }
.doc-badge.badge-ppt   { background: var(--warning-light); color: #B45309; }
.doc-badge.badge-zip   { background: var(--violet-light); color: #6D28D9; }
.doc-badge.badge-other { background: #EEF0F7; color: var(--muted-strong); }

.btn-download-doc {
    border-radius: 9px;
    color: var(--muted-strong);
    border-color: var(--border);
    transition: all .18s ease;
}

.btn-download-doc:hover {
    color: var(--accent);
    border-color: var(--accent);
    background: var(--accent-light);
}

/* Images */
.img-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    padding: 14px;
}

.img-item {
    position: relative;
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid var(--border-soft);
    background: #fff;
    box-shadow: var(--shadow-xs);
    transition: transform .2s ease, box-shadow .2s ease;
}

.img-item:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: var(--shadow-md);
}

.img-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.img-item .img-overlay {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgba(20, 23, 51, .45);
    color: #fff;
    font-size: 1.4rem;
    opacity: 0;
    transition: opacity .2s ease;
    backdrop-filter: blur(2px);
}

.img-item:hover .img-overlay {
    opacity: 1;
}

.img-empty {
    grid-column: 1 / -1;
    padding: 24px;
    text-align: center;
    color: var(--muted);
    font-size: .85rem;
}

/* Grading panel */
.grading-panel {
    border: 1px dashed var(--accent-mist);
    border-radius: var(--radius);
    background: linear-gradient(180deg, #FBFCFF, #F7F8FE);
    padding: 18px 20px;
}

.grading-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    font-family: var(--display);
    font-weight: 700;
    font-size: .9rem;
    color: var(--ink);
}

.grading-hint {
    font-size: .75rem;
    color: var(--muted);
}

.grading-hint b {
    color: var(--accent);
    font-family: var(--mono);
    font-size: .85rem;
}

.score-input-wrap {
    position: relative;
}

.score-input {
    padding-right: 62px;
    font-family: var(--mono);
    font-weight: 700;
    font-size: 1.05rem;
    height: 46px;
    border-radius: var(--radius-sm);
    border-color: var(--border);
}

.score-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
}

.score-max {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-family: var(--mono);
    font-size: .8rem;
}

.score-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.score-chip {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted-strong);
    font-size: .72rem;
    font-weight: 600;
    padding: 5px 11px;
    border-radius: 8px;
    transition: all .15s ease;
}

.score-chip:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-light);
    transform: translateY(-1px);
}

.score-chip.chip-danger:hover {
    border-color: var(--danger);
    color: var(--danger);
    background: var(--danger-light);
}

.feedback-input {
    border-radius: var(--radius-sm);
    border-color: var(--border);
}

.feedback-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
}

.submission-modal-footer {
    background: var(--surface);
    border-top: 1px solid var(--border-soft);
    padding: 16px 26px;
}

.btn-save-grade {
    padding: 10px 22px;
    border-radius: 12px;
    box-shadow: var(--shadow-accent);
}

.btn-save-grade:hover {
    box-shadow: 0 16px 30px -12px rgba(79, 70, 229, .55);
}

.reveal-step {
    animation: revealUp .4s cubic-bezier(.2, .8, .2, 1) both;
}

.submission-modal-body > .reveal-step:nth-of-type(2) { animation-delay: .06s; }
.submission-modal-body > .reveal-step:nth-of-type(3) { animation-delay: .11s; }
.submission-modal-body > .reveal-step:nth-of-type(4) { animation-delay: .16s; }
.submission-modal-body > .reveal-step:nth-of-type(5) { animation-delay: .21s; }

@keyframes revealUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: none; }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
const assignmentId = '<?php echo $assignmentId; ?>';
let submissionsTable;
let currentPreviewImageUrl = '';

$(document).ready(function() {
    loadSubmissions();
});

function loadSubmissions() {
    fetch(`api/get_submissions.php?assignment_id=${assignmentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('submissionsBody');
                tbody.innerHTML = '';
                
                result.submissions.forEach(submission => {
                    let statusBadge;
                    if (submission.score !== null && submission.score !== undefined) {
                        statusBadge = '<span class="badge badge-graded">Đã chấm</span>';
                    } else {
                        statusBadge = '<span class="badge badge-submitted">Chưa chấm</span>';
                    }
                    
                    const scoreDisplay = submission.score !== null ? 
                        `<strong class="text-primary">${submission.score}/${<?php echo $assignment['max_score']; ?>}</strong>` : 
                        '<span class="text-muted">---</span>';
                    
                    const row = `
                        <tr>
                            <td>${submission.student_name}</td>
                            <td>${submission.student_class}</td>
                            <td>${formatDateTime(submission.submitted_at)}</td>
                            <td>${statusBadge}</td>
                            <td>${scoreDisplay}</td>
                            <td>
                                <button class="btn btn-sm btn-gradient-primary" onclick="viewSubmission('${submission.id}')">
                                    <i class="bi bi-eye me-1"></i>Xem & Chấm
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                if (submissionsTable) {
                    submissionsTable.destroy();
                }
                
                submissionsTable = $('#submissionsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 50,
                    order: [[2, 'desc']]
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function viewSubmission(submissionId) {
    fetch(`api/get_submissions.php?id=${submissionId}`)
        .then(response => response.json())
        .then(result => {
            console.log('Submission result:', result);
            if (result.success && result.submission) {
                const sub = result.submission;
                
                document.getElementById('viewStudentName').textContent = sub.student_name || 'Học sinh';
                document.getElementById('viewStudentAvatar').textContent = getInitials(sub.student_name);
                document.getElementById('viewStudentCode').textContent = sub.student_code || '---';
                document.getElementById('viewStudentClass').textContent = sub.student_class || '---';
                document.getElementById('viewGroupMembers').textContent = Array.isArray(sub.group_members) && sub.group_members.length > 0 ? sub.group_members.join(', ') : 'Không có';
                document.getElementById('viewSubmittedAt').textContent = formatDateTime(sub.submitted_at);
                
                const statusBadge = sub.score !== null && sub.score !== undefined ? 
                    '<span class="badge badge-graded">Đã chấm</span>' : 
                    '<span class="badge badge-submitted">Chưa chấm</span>';
                document.getElementById('viewStatus').innerHTML = statusBadge;
                
                const content = sub.content || '';
                document.getElementById('viewContent').textContent = content || '(Không có nội dung)';
                document.getElementById('viewContentCount').textContent = content ? countWords(content) + ' từ' : '';
                
                // Display documents
                const documentsContainer = document.getElementById('viewDocuments');
                const documentsSection = document.getElementById('documentsSection');
                documentsContainer.innerHTML = '';
                if (sub.documents && sub.documents.length > 0) {
                    documentsSection.style.display = 'block';
                    document.getElementById('viewDocumentsCount').textContent = sub.documents.length + ' file';
                    sub.documents.forEach(doc => {
                        const fileExt = (doc.extension || (doc.path.split('.').pop() || 'file')).toLowerCase();
                        let typeClass = 'other';
                        let iconClass = 'bi-file-earmark';
                        if (fileExt === 'doc' || fileExt === 'docx') {
                            typeClass = 'word';
                            iconClass = 'bi-file-earmark-word';
                        } else if (fileExt === 'xls' || fileExt === 'xlsx') {
                            typeClass = 'excel';
                            iconClass = 'bi-file-earmark-excel';
                        } else if (fileExt === 'pdf') {
                            typeClass = 'pdf';
                            iconClass = 'bi-file-earmark-pdf';
                        } else if (fileExt === 'ppt' || fileExt === 'pptx') {
                            typeClass = 'ppt';
                            iconClass = 'bi-file-earmark-ppt';
                        } else if (fileExt === 'zip' || fileExt === 'rar') {
                            typeClass = 'zip';
                            iconClass = 'bi-file-earmark-zip';
                        }
                        
                        const fileSize = doc.size ? (doc.size / 1024).toFixed(2) + ' KB' : 'N/A';
                        const fileName = doc.filename || doc.path.split('/').pop();
                        
                        const item = document.createElement('div');
                        item.className = 'doc-item';
                        item.innerHTML = `
                            <div class="d-flex align-items-center gap-3" style="min-width:0;">
                                <div class="doc-icon ${typeClass}"><i class="bi ${iconClass}"></i></div>
                                <div style="min-width:0;">
                                    <div class="doc-name" title="${escapeHtml(fileName)}">${escapeHtml(fileName)}</div>
                                    <div class="doc-size">${fileSize}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="doc-badge badge-${typeClass}">${fileExt.toUpperCase()}</span>
                                <a href="api/download_file.php?file=${encodeURIComponent(doc.path)}" class="btn btn-sm btn-light btn-download-doc" title="Tải xuống" target="_blank">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        `;
                        documentsContainer.appendChild(item);
                    });
                } else {
                    documentsSection.style.display = 'none';
                    document.getElementById('viewDocumentsCount').textContent = '';
                }
                
                // Display images
                const imagesContainer = document.getElementById('viewImages');
                imagesContainer.innerHTML = '';
                if (sub.images && sub.images.length > 0) {
                    document.getElementById('viewImagesCount').textContent = sub.images.length + ' ảnh';
                    sub.images.forEach(imagePath => {
                        const imageUrl = 'api/download_file.php?file=' + encodeURIComponent(imagePath);
                        const item = document.createElement('div');
                        item.className = 'img-item';
                        item.title = 'Bấm để xem lớn';
                        item.onclick = () => openImagePreview(imageUrl);
                        item.innerHTML = `
                            <img src="${imageUrl}" alt="Hình ảnh bài nộp" loading="lazy">
                            <div class="img-overlay"><i class="bi bi-zoom-in"></i></div>
                        `;
                        imagesContainer.appendChild(item);
                    });
                } else {
                    imagesContainer.innerHTML = '<div class="img-empty"><i class="bi bi-image me-1"></i> Không có hình ảnh đính kèm</div>';
                    document.getElementById('viewImagesCount').textContent = '';
                }
                
                // Set grading fields
                document.getElementById('gradeSubmissionId').value = sub.id;
                document.getElementById('gradeScore').value = sub.score || '';
                document.getElementById('gradeFeedback').value = sub.feedback || '';
                
                new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
            } else {
                console.error('Error:', result.message);
                showToast('Không thể tải bài nộp: ' + (result.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Có lỗi xảy ra khi tải bài nộp', 'error');
        });
}

function saveGrade() {
    const submissionId = document.getElementById('gradeSubmissionId').value;
    const score = parseFloat(document.getElementById('gradeScore').value);
    const feedback = document.getElementById('gradeFeedback').value;
    
    if (isNaN(score) || score < 0 || score > <?php echo $assignment['max_score']; ?>) {
        showToast('Vui lòng nhập điểm hợp lệ (0 - <?php echo $assignment['max_score']; ?>)!', 'warning');
        return;
    }
    
    const saveBtn = document.querySelector('#viewSubmissionModal .btn-save-grade');
    const restoreBtn = () => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Lưu điểm';
        }
    };
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
    }
    
    fetch('api/grade_submission.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            submission_id: submissionId,
            score: score,
            feedback: feedback
        })
    })
    .then(response => response.json())
    .then(async result => {
        if (result.success) {
            showToast('Đã lưu điểm thành công!', 'success');
            
            // Update modal status immediately
            document.getElementById('viewStatus').innerHTML = '<span class="badge badge-graded">Đã chấm</span>';
            
            await loadSubmissions();
            restoreBtn();
            bootstrap.Modal.getInstance(document.getElementById('viewSubmissionModal')).hide();
        } else {
            restoreBtn();
            showToast('Lỗi: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        restoreBtn();
        showToast('Có lỗi xảy ra khi lưu điểm', 'error');
    });
}

function openImagePreview(imageUrl) {
    currentPreviewImageUrl = imageUrl;
    document.getElementById('imagePreviewModalImg').src = imageUrl;
    new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
}

function openPreviewImageInNewTab() {
    if (currentPreviewImageUrl) {
        window.open(currentPreviewImageUrl, '_blank');
    }
}

async function exportAssignmentScores() {
    try {
        const response = await fetch(`api/export_assignment_scores.php?assignment_id=${encodeURIComponent(assignmentId)}`);
        const result = await response.json();

        if (!result.success) {
            showToast('Lỗi: ' + result.message, 'error');
            return;
        }

        const wb = XLSX.utils.book_new();
        const groupedByClass = result.rows.reduce((acc, row) => {
            const className = row.class_name || 'Khong ro lop';
            if (!acc[className]) {
                acc[className] = [];
            }
            acc[className].push(row);
            return acc;
        }, {});

        Object.keys(groupedByClass).forEach(className => {
            const rows = [
                ['STT', 'Mã học sinh', 'Họ và tên', 'Lớp', 'Điểm', 'Trạng thái', 'Nguồn điểm', 'Người nộp', 'Thời gian nộp', 'Thành viên nhóm', 'Nhận xét']
            ];

            groupedByClass[className].forEach((row, index) => {
                rows.push([
                    index + 1,
                    row.student_code || '',
                    row.student_name || '',
                    row.class_name || '',
                    row.score ?? '',
                    row.status || '',
                    row.score_source || '',
                    row.submitted_by || '',
                    row.submitted_at ? formatDateTime(row.submitted_at) : '',
                    row.group_members || '',
                    row.feedback || ''
                ]);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            const sheetName = String(className).replace(/[\\\/:*?"<>|]/g, '_').substring(0, 31) || 'Lop';
            XLSX.utils.book_append_sheet(wb, ws, sheetName);
        });

        const title = (result.assignment?.title || 'BaiTap').replace(/[\\\/:*?"<>|]/g, '_');
        XLSX.writeFile(wb, `Diem_${title}.xlsx`);
    } catch (error) {
        console.error('Export error:', error);
        showToast('Có lỗi xảy ra khi xuất Excel', 'error');
    }
}

function getInitials(name) {
    if (!name) return '--';
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '--';
    return parts.map(p => p.charAt(0)).slice(0, 2).join('').toUpperCase();
}

function countWords(text) {
    const trimmed = String(text).trim();
    return trimmed ? trimmed.split(/\s+/).filter(Boolean).length : 0;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function setScore(value) {
    document.getElementById('gradeScore').value = value;
}

function setScoreHalf() {
    const max = <?php echo $assignment['max_score']; ?>;
    const half = (max / 2).toFixed(1);
    document.getElementById('gradeScore').value = half.endsWith('.0') ? half.slice(0, -2) : half;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}
</script>

<script src="../includes/toast-notifications.js"></script>

<?php include '../includes/teacher_footer.php'; ?>
