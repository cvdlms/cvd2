<?php
include '../includes/session_check.php';
include '../includes/common_functions.php';

$username = $_SESSION['username'];
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? 'Giáo Viên';

$config = [];
if (is_file(__DIR__ . '/../admin/system_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
}
$defaultSemester = $config['semester']['current'] ?? 'hk1';

$title = 'Trò Chơi Ai Là Triệu Phú - EDUVN EXAMS';
include '../includes/teacher_header.php';
?>
<style>
    .game-hero {
        background: linear-gradient(135deg, #0b1435 0%, #141d4d 55%, #1a2560 100%);
        border: 1px solid rgba(242, 201, 76, .35);
        border-radius: 1.25rem;
        padding: 2rem;
        color: #eef1ff;
        box-shadow: 0 14px 40px rgba(0, 0, 0, .25);
    }
    .game-hero .hero-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #f2c94c;
    }
    .game-hero small { color: #c3c9ee; }
    .level-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid #33418f;
        border-radius: 999px;
        padding: .25rem .7rem;
        font-size: .8rem;
        font-weight: 600;
        background: #0e1538;
        color: #aab2d8;
    }
    .level-chip .dot { width: 9px; height: 9px; border-radius: 50%; }
    .dot.nb { background: #27ae60; }
    .dot.th { background: #2d9cdb; }
    .dot.vd { background: #f2c94c; }
    .subj-toggle {
        border: 1px solid #33418f;
        border-radius: .75rem;
        padding: .6rem .8rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem .6rem;
        cursor: pointer;
        background: #121b46;
        color: #eef1ff;
        transition: border-color .15s ease, background .15s ease;
        user-select: none;
        height: 100%;
    }
    .subj-toggle:hover { border-color: #2d9cdb; }
    .subj-toggle.selected { border-color: #f2c94c; background: #1e2a6b; }
    .subj-toggle input { accent-color: #f2c94c; width: 16px; height: 16px; flex-shrink: 0; }
    .subj-toggle .subj-name { flex: 1 1 auto; min-width: 0; word-break: break-word; }
    .availability-badge { font-size: .72rem; color: #aab2d8; flex-basis: 100%; text-align: left; white-space: normal; word-break: break-word; }
    .count-input {
        border: 1px solid #33418f;
        border-radius: .6rem;
        background: #0e1538;
        color: #eef1ff;
        text-align: center;
        font-weight: 700;
        padding: .4rem .25rem;
        width: 68px;
    }
    .avail-card { background: #101a44; border: 1px solid #33418f; border-radius: .9rem; }
    .avail-item { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .4rem .6rem; border-bottom: 1px dashed #26306b; font-size: .85rem; }
    .avail-item:last-child { border-bottom: 0; }
    .ok-badge { color: #27ae60; font-weight: 700; }
    .bad-badge { color: #e6484f; font-weight: 700; }
</style>

<div class="container-fluid px-4 py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="teacher.php"><i class="bi bi-grid-1x2-fill me-1"></i>Bảng Điều Khiển</a></li>
            <li class="breadcrumb-item active" aria-current="page">Trò Chơi Ai Là Triệu Phú</li>
        </ol>
    </nav>

    <div class="game-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="hero-title"><i class="bi bi-trophy-fill me-2"></i>Ai Là Triệu Phú — Sinh Hoạt Dưới Cờ</div>
                <small class="d-block mt-1">Cấu hình trò chơi ngay từ <b>Ngân hàng câu hỏi</b>: câu hỏi được lấy ra theo mức độ khó tương ứng với thang điểm &amp; giải thưởng của game show.</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="level-chip"><span class="dot nb"></span>Mức 1 · Dễ (NB)</span>
                <span class="level-chip"><span class="dot th"></span>Mức 2 · Vừa (TH)</span>
                <span class="level-chip"><span class="dot vd"></span>Mức 3 · Khó (VD)</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Setup form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2 text-primary"></i>Cấu hình bộ câu hỏi</h5>
                </div>
                <div class="card-body px-3 pb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Khối lớp</label>
                            <select class="form-select" id="gsGrade"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Học kỳ</label>
                            <select class="form-select" id="gsSemester"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Tổng số câu (tự động tính)</label>
                            <input type="text" class="form-control bg-light fw-bold" id="gsTotal" value="15" readonly>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold small text-muted">Chọn môn học (bạn có thể chọn nhiều môn để trộn chung một lượt chơi)</label>
                        <div class="row g-2" id="gsSubjects"></div>
                        <div class="mt-2" id="gsSubjectsMsg" class="small text-muted"></div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold small text-muted">Số câu hỏi cho mỗi mức độ</label>
                        <div class="row g-2 text-center">
                            <div class="col-6 col-md-4">
                                <div class="avail-card p-2">
                                    <div class="small text-muted mb-1">Mức 1 · Dễ <span class="dot nb d-inline-block"></span></div>
                                    <input type="number" class="count-input" id="gsNB" value="5" min="0" max="30">
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="avail-card p-2">
                                    <div class="small text-muted mb-1">Mức 2 · Vừa <span class="dot th d-inline-block"></span></div>
                                    <input type="number" class="count-input" id="gsTH" value="4" min="0" max="30">
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="avail-card p-2">
                                    <div class="small text-muted mb-1">Mức 3 · Khó <span class="dot vd d-inline-block"></span></div>
                                    <input type="number" class="count-input" id="gsVD" value="4" min="0" max="30">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-success btn-lg fw-bold" onclick="gsOpenGame()">
                            <i class="bi bi-play-fill me-1"></i>Mở Trò Chơi (màn hình chiếu)
                        </button>
                        <button class="btn btn-outline-secondary" onclick="gsLoadMeta(false)">
                            <i class="bi bi-arrow-repeat me-1"></i>Cập nhật số câu sẵn có
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Availability / info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Số câu sẵn có trong ngân hàng</h6>
                </div>
                <div class="card-body px-3 pt-2" id="gsAvail">
                    <div class="text-muted small py-2">Đang tải dữ liệu…</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle-fill me-2 text-info"></i>Cơ chế thang điểm</h6>
                </div>
                <div class="card-body px-3 pt-2 small text-muted">
                    <p class="mb-2">Câu hỏi được xếp theo thứ tự độ khó tăng dần, tương ứng với càng chơi lâu điểm giải thưởng càng cao:</p>
                    <ul class="mb-2 ps-3">
                        <li>Mức 1 (NB) — các câu đầu, thang điểm thấp</li>
                        <li>Mức 2 (TH) — câu giữa, củng cố</li>
                        <li>Mức 3 (VD) — các câu cuối, đỉnh cao</li>
                    </ul>
                    <p class="mb-1">Mốc an toàn tự đặt ở cuối mỗi cụm mức độ: trả lời sai vẫn giữ được phần thưởng tại mốc gần nhất.</p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-lightbulb-fill me-1 text-warning"></i>
                        Chỉ các câu <b>trắc nghiệm 1 đáp án</b> và <b>Đúng/Sai</b> được đưa vào trò chơi. Nếu một mức không đủ câu, trò chơi tự bù từ mức dễ hơn gần nhất và hiển thị cảnh báo.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const GS = {
        meta: null,
        grade: 'khoi7',
        semester: '<?php echo $defaultSemester; ?>',
        subjects: [],
        counts: { NB: 5, TH: 4, VD: 4 }
    };

    const $ = (id) => document.getElementById(id);

    function readUrlParams() {
        const p = new URLSearchParams(location.search);
        GS.grade = p.get('grade') || GS.grade;
        GS.semester = p.get('semester') || GS.semester;
        const subs = p.get('subjects');
        if (subs) GS.subjects = subs.split(',').map(Number).filter(n => n > 0);
        GS.counts.NB = clampInt(p.get('nb'), GS.counts.NB);
        GS.counts.TH = clampInt(p.get('th'), GS.counts.TH);
        GS.counts.VD = clampInt(p.get('vd'), GS.counts.VD);
    }
    function clampInt(v, d) {
        const n = parseInt(v, 10);
        return Number.isFinite(n) ? Math.max(0, Math.min(30, n)) : d;
    }

    function updateTotal() {
        const t = GS.counts.NB + GS.counts.TH + GS.counts.VD;
        $('gsTotal').value = t;
    }

    function setCountInputs() {
        $('gsNB').value = GS.counts.NB;
        $('gsTH').value = GS.counts.TH;
        $('gsVD').value = GS.counts.VD;
    }

    function renderSubjects() {
        const box = $('gsSubjects');
        box.innerHTML = '';
        GS.subjects.sort((a, b) => a - b);
        const subjMeta = new Map((GS.meta ? GS.meta.subjects : []).map(s => [s.id, s]));
        GS.meta.subjects.forEach((s, i) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-xl-3';
            const hasQ = s.total > 0;
            col.innerHTML = `
                <label class="subj-toggle ${GS.subjects.includes(s.id) ? 'selected' : ''} ${hasQ ? '' : 'opacity-50'}" style="cursor:${hasQ ? 'pointer' : 'not-allowed'}">
                    <input type="checkbox" value="${s.id}" ${GS.subjects.includes(s.id) ? 'checked' : ''} ${hasQ ? '' : 'disabled'}>
                    <span class="subj-name">${esc(s.name)}</span>
                    <span class="availability-badge">${s.total ? 'NB ' + s.levels.NB + ' · TH ' + s.levels.TH + ' · VD ' + s.levels.VD + ' · ' + s.total + ' câu' : 'chưa có câu hỏi'}</span>
                </label>`;
            const inp = col.querySelector('input');
            inp.addEventListener('change', () => {
                if (inp.checked) { GS.subjects.push(s.id); }
                else { GS.subjects = GS.subjects.filter(x => x !== s.id); }
                GS.subjects.sort((a, b) => a - b);
                col.querySelector('.subj-toggle').classList.toggle('selected', inp.checked);
                renderAvailability();
            });
            box.appendChild(col);
        });
        $('gsSubjectsMsg').textContent = GS.subjects.length
            ? 'Đang chọn: ' + GS.subjects.map(id => subjMeta.get(id)?.name).filter(Boolean).join(', ')
            : '';
    }

    function summarizeSelected() {
        const sum = { NB: 0, TH: 0, VD: 0 };
        const meta = new Map((GS.meta ? GS.meta.subjects : []).map(s => [s.id, s]));
        GS.subjects.forEach(id => {
            const s = meta.get(id);
            if (s) {
                sum.NB += s.levels.NB;
                sum.TH += s.levels.TH;
                sum.VD += s.levels.VD;
            }
        });
        return sum;
    }

    const LEVEL_DEFS = [
        { key: 'NB', label: 'Mức 1 · Dễ (NB)' },
        { key: 'TH', label: 'Mức 2 · Vừa (TH)' },
        { key: 'VD', label: 'Mức 3 · Khó (VD)' }
    ];

    function renderAvailability() {
        const box = $('gsAvail');
        box.innerHTML = '';
        if (!GS.meta) { box.innerHTML = '<div class="text-muted small py-2">Chưa có dữ liệu.</div>'; return; }
        const sum = summarizeSelected();
        if (!GS.subjects.length) {
            box.innerHTML = '<div class="text-muted small py-2">Vui lòng chọn ít nhất một môn học ở trên.</div>';
            return;
        }
        const rows = LEVEL_DEFS.map(def => {
            const req = GS.counts[def.key], have = sum[def.key];
            const ok = req <= have;
            return `<div class="avail-item">
                <span>${def.label}</span>
                <span class="${ok ? 'ok-badge' : 'bad-badge'}">cần ${req} / có ${have} ${ok ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-exclamation-triangle-fill"></i>'}</span>
            </div>`;
        }).join('');
        box.innerHTML = rows;
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function buildGameUrl() {
        const p = new URLSearchParams();
        p.set('grade', GS.grade);
        p.set('semester', GS.semester);
        if (GS.subjects.length) p.set('subjects', GS.subjects.join(','));
        p.set('nb', GS.counts.NB);
        p.set('th', GS.counts.TH);
        p.set('vd', GS.counts.VD);
        return '../html/ai-la-trieu-phu.html?' + p.toString();
    }

    window.gsLoadMeta = function () {
        const p = new URLSearchParams({ action: 'meta', grade: GS.grade, semester: GS.semester });
        fetch('../api/game_questions.php?' + p.toString())
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message || 'Không tải được dữ liệu.');
                GS.meta = d;
                renderSubjects();
                renderAvailability();
            })
            .catch(e => {
                $('gsAvail').innerHTML = '<div class="text-danger small py-2">' + esc(e.message || 'Không kết nối được máy chủ.') + '</div>';
            });
    };

    window.gsOpenGame = function () {
        if (!GS.subjects.length) {
            alert('Vui lòng chọn ít nhất một môn học.');
            return;
        }
        updateTotal();
        window.open(buildGameUrl(), '_blank', 'noopener');
    };

    document.addEventListener('DOMContentLoaded', function () {
        readUrlParams();
        setCountInputs();
        updateTotal();

        // grade / semester selects + load meta first time
        const gSel = $('gsGrade'), sSel = $('gsSemester');
        const grades = ['khoi6', 'khoi7', 'khoi8', 'khoi9'];
        const gradeLabels = { khoi6: 'Khối 6', khoi7: 'Khối 7', khoi8: 'Khối 8', khoi9: 'Khối 9' };
        grades.forEach(g => {
            const o = document.createElement('option');
            o.value = g; o.textContent = gradeLabels[g]; gSel.add(o);
        });
        gSel.value = GS.grade;
        ['hk1', 'hk2'].forEach(h => {
            const o = document.createElement('option');
            o.value = h; o.textContent = h === 'hk1' ? 'Học kì 1' : 'Học kì 2'; sSel.add(o);
        });
        sSel.value = GS.semester;

        gSel.addEventListener('change', () => { GS.grade = gSel.value; gsLoadMeta(); });
        sSel.addEventListener('change', () => { GS.semester = sSel.value; gsLoadMeta(); });

        ['gsNB', 'gsTH', 'gsVD'].forEach((id, i) => {
            $(id).addEventListener('input', () => {
                GS.counts[['NB', 'TH', 'VD'][i]] = clampInt($(id).value, 0);
                updateTotal();
                renderAvailability();
            });
        });

        gsLoadMeta();
    });
})();
</script>

<?php include '../includes/teacher_footer.php'; ?>