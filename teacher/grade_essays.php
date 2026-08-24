<?php
include '../includes/session_check.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$username = $_SESSION['username'];
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

$teacherSubjects = [];
$subjectsMap = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_subjects.json'), true) ?: [];
$teacherSubjects = $subjectsMap[$username] ?? [];

require_once __DIR__ . '/../includes/exam_helper.php';
$pendingList = exam_scan_pending_essays($teacherSubjects);

$totalQuestionsPending = 0;
foreach ($pendingList as $item) $totalQuestionsPending += (int)$item['essay_count'];

$title = 'Chấm Tự Luận - CVD';
include '../includes/teacher_header.php';
?>
    <style>
        .grading-card{border:1px solid #E8ECF4;border-radius:14px;background:#fff;transition:box-shadow .2s}
        .grading-card:hover{box-shadow:0 6px 18px rgba(30,41,72,.08)}
        .grading-card.urgent{border-left:4px solid #F4568C}
        .essay-chip{display:inline-flex;align-items:center;gap:5px;background:#FFF1F6;color:#C2255C;border-radius:999px;padding:3px 10px;font-size:.78rem;font-weight:600}
        .grade-essay-block{border:1px solid #E8ECF4;border-radius:12px;padding:16px;margin-bottom:16px;background:#FAFBFE}
        .grade-essay-block .q-text{font-weight:600;margin-bottom:8px}
        .student-answer-box{background:#fff;border:1px dashed #CBD2E0;border-radius:10px;padding:12px 14px;white-space:pre-wrap;max-height:260px;overflow:auto;font-size:.93rem;line-height:1.55}
        .suggested-toggle{font-size:.83rem;color:#2563EB;cursor:pointer;background:none;border:none;padding:0}
        .suggested-box{background:#EEF4FF;border-radius:10px;padding:10px 14px;font-size:.88rem;margin-top:8px;white-space:pre-wrap;display:none}
        .score-input{width:110px}
        .score-max{color:#64748B;font-size:.9rem}
        .empty-state{text-align:center;padding:60px 20px;color:#64748B}
        .empty-state i{font-size:3rem;color:#C7CEDD;margin-bottom:12px;display:block}
        .storage-badge{font-size:.72rem;border-radius:999px;padding:2px 9px;font-weight:600}
        .storage-badge.practice{background:#EDE9FE;color:#6D28D9}
        .storage-badge.score{background:#DCFCE7;color:#15803D}
        #saveGradesBtn .spinner-border{width:1em;height:1em;margin-right:6px}
        .toast-save{position:fixed;top:20px;right:20px;z-index:20000;min-width:280px}
    </style>

    <div class="main-content">
    <div class="container py-4 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="section-header">
                    <div class="sh-icon" style="background:#FFF1F6;color:#C2255C">
                        <i class="bi bi-pencil-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3>Chấm Tự Luận</h3>
                        <p>Danh sách bài làm có câu Tự luận đang chờ bạn chấm điểm</p>
                    </div>
                    <?php if ($totalQuestionsPending > 0): ?>
                        <span class="badge rounded-pill bg-danger fs-6 px-3 py-2 align-self-center">
                            <?php echo count($pendingList); ?> bài / <?php echo $totalQuestionsPending; ?> câu
                        </span>
                    <?php endif; ?>
                </div>

                <div class="eduvn-card eduvn-reveal">
                    <div class="card-body">
                        <div id="pendingList" class="row g-3">
                            <?php if (empty($pendingList)): ?>
                                <div class="empty-state col-12">
                                    <i class="bi bi-check2-circle"></i>
                                    <h5>Không có bài nào chờ chấm</h5>
                                    <p class="mb-0">Tất cả các bài Tự luận đã được chấm xong. Tuyệt vời!</p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($pendingList as $idx => $item): ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="grading-card p-3 h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="storage-badge <?php echo $item['storage'] === 'practice' ? 'practice' : 'score'; ?>">
                                                <?php echo $item['storage'] === 'practice' ? 'Luyện tập' : 'Kiểm tra'; ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(date('d/m H:i', strtotime($item['timestamp']) ?: time())); ?>
                                            </small>
                                        </div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['student_name'] ?: $item['student_code']); ?></h6>
                                        <div class="text-muted small mb-2">
                                            Mã HS: <?php echo htmlspecialchars($item['student_code']); ?>
                                            · Lớp: <?php echo htmlspecialchars($item['class_name'] ?: '—'); ?>
                                        </div>
                                        <div class="small text-truncate mb-2" title="<?php echo htmlspecialchars($item['test_name']); ?>">
                                            📄 <?php echo htmlspecialchars($item['test_name']); ?>
                                        </div>
                                        <div class="mb-3">
                                            <span class="essay-chip"><i class="bi bi-pencil-square"></i> <?php echo (int)$item['essay_count']; ?> câu tự luận</span>
                                            <span class="ms-2 small text-muted">Điểm tạm: <?php echo $item['auto_score']; ?>/10</span>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm mt-auto start-grading"
                                                data-idx="<?php echo $idx; ?>">
                                            <i class="bi bi-pencil-fill me-1"></i> Chấm ngay
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Grading Modal -->
    <div class="modal fade" id="gradingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✍️ Chấm bài: <span id="modalStudentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer justify-content-between">
                    <div class="small text-muted">
                        Điểm dự kiến sau chấm: <strong id="previewScore"><?php echo '—'; ?></strong>/10
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="saveGradesBtn">
                            <i class="bi bi-save me-1"></i> Lưu điểm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-success toast-save d-none" id="saveToast" role="alert"></div>

    <script>
        const PENDING = <?php echo json_encode($pendingList, JSON_UNESCAPED_UNICODE); ?>;
        let currentIdx = null;
        let gradingModal = null;

        document.addEventListener('DOMContentLoaded', function () {
            gradingModal = new bootstrap.Modal(document.getElementById('gradingModal'));
            document.querySelectorAll('.start-grading').forEach(btn => {
                btn.addEventListener('click', () => openGrading(parseInt(btn.dataset.idx, 10)));
            });
            document.getElementById('saveGradesBtn').addEventListener('click', saveGrades);
            document.getElementById('modalBody').addEventListener('input', updatePreview);
        });

        function openGrading(idx) {
            currentIdx = idx;
            const item = PENDING[idx];
            if (!item) return;
            document.getElementById('modalStudentName').textContent =
                (item.student_name || item.student_code) + ' — ' + item.test_name;

            const html = item.essays.map((es, i) => `
                <div class="grade-essay-block" data-qindex="${es.question_index}" data-points="${es.points}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-dark">Câu ${es.question_index + 1}</span>
                        <span class="essay-chip"><i class="bi bi-star"></i> ${es.points} điểm</span>
                    </div>
                    <div class="q-text">${escHtml(es.question)}</div>
                    ${es.image ? `<img src="${escAttr(es.image)}" alt="Hình câu hỏi" style="max-width:100%;max-height:220px;border-radius:8px;margin-bottom:8px">` : ''}
                    <div class="small fw-semibold text-secondary mb-1"><i class="bi bi-person-lines-fill me-1"></i>Bài làm của học sinh:</div>
                    <div class="student-answer-box">${es.answer ? escHtml(es.answer) : '<em class="text-muted">(Không viết bài làm)</em>'}</div>
                    ${es.suggested ? `
                        <button type="button" class="suggested-toggle mt-2" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='block'?'none':'block'">
                            <i class="bi bi-lightbulb me-1"></i>Xem gợi ý đáp án
                        </button>
                        <div class="suggested-box"><strong>Gợi ý:</strong> ${escHtml(es.suggested)}</div>` : ''}
                    <div class="row g-2 mt-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small mb-0">Cho điểm:</label>
                            <input type="number" class="form-control form-control-sm score-input"
                                   min="0" max="${es.points}" step="0.25" value="${es.points}" data-role="awarded">
                            <div class="score-max">/ ${es.points} điểm</div>
                        </div>
                        <div class="col">
                            <label class="form-label small mb-0">Nhận xét:</label>
                            <input type="text" class="form-control form-control-sm" maxlength="500" placeholder="Gửi lời nhận xét cho học sinh (không bắt buộc)" data-role="comment">
                        </div>
                    </div>
                </div>`).join('');

            document.getElementById('modalBody').innerHTML = html;
            updatePreview();
            gradingModal.show();
        }

        function updatePreview() {
            if (currentIdx === null) return;
            const item = PENDING[currentIdx];
            let earnedAuto = 0;
            // Tái lập đơn vị phần tự động từ dữ liệu đề không gửi kèm modal → dùng công thức ngược:
            // auto_score hiện tại = earned_auto / gradable * 10 với gradable = total - essay_count
            const gradable = Math.max(1, item.total_questions - item.essay_count);
            earnedAuto = (item.auto_score / 10) * gradable;
            let essayEarned = 0;
            document.querySelectorAll('#modalBody .grade-essay-block').forEach(block => {
                const max = parseFloat(block.dataset.points) || 0;
                const val = parseFloat(block.querySelector('[data-role="awarded"]').value) || 0;
                if (max > 0) essayEarned += Math.min(Math.max(val, 0), max) / max;
            });
            const total = item.total_questions || gradable + item.essay_count;
            const score = Math.min(10, ((earnedAuto + essayEarned) / total) * 10);
            document.getElementById('previewScore').textContent = (Math.round(score * 10) / 10).toFixed(1);
        }

        function saveGrades() {
            if (currentIdx === null) return;
            const item = PENDING[currentIdx];
            const grades = [];
            document.querySelectorAll('#modalBody .grade-essay-block').forEach(block => {
                grades.push({
                    question_index: parseInt(block.dataset.qindex, 10),
                    awarded: parseFloat(block.querySelector('[data-role="awarded"]').value) || 0,
                    comment: block.querySelector('[data-role="comment"]').value.trim()
                });
            });

            const btn = document.getElementById('saveGradesBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';

            fetch('api/save_essay_grades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    storage: item.storage,
                    result_id: item.result_id,
                    student_code: item.student_code,
                    grades: grades
                })
            })
            .then(r => r.json())
            .then(res => {
                showToast(res.success ? 'success' : 'danger',
                    res.message + (res.success ? ` Điểm mới: ${res.new_score}/10.` : ''));
                if (res.success) setTimeout(() => location.reload(), 1200);
            })
            .catch(() => showToast('danger', 'Lỗi kết nối. Vui lòng thử lại.'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Lưu điểm';
            });
        }

        function showToast(type, msg) {
            const toast = document.getElementById('saveToast');
            toast.className = 'alert alert-' + type + ' toast-save';
            toast.innerHTML = escHtml(msg);
            setTimeout(() => toast.classList.add('d-none'), 4000);
        }

        function escHtml(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function escAttr(s) { return escHtml(s); }
    </script>
<?php include '../includes/footer.php'; ?>