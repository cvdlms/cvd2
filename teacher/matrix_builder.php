<?php
// ============================================================
// Xây Dựng Ma Trận Đề Kiểm Tra
// Port từ chức năng "Ma trận đề kiểm tra" của EduVN
// (eduvn/public/tools/matran) sang CVD LMS
// ============================================================

session_name('CVD_TEACHER_SESSION');
session_start();

include '../includes/session_check.php';
include '../includes/premium_helper.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

$username = $_SESSION['username'];
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$username]['fullname'] ?? $username;

$title = 'Xây Dựng Ma Trận - CVD';
include '../includes/teacher_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ── Tool color vars ─────────────────────────────────────── */
:root{
  --mtn-navy:#1A3A5C; --mtn-navy-lt:#2c5282; --mtn-blue-lt:#D0E4FF;
  --mtn-purple:#6B21A8; --mtn-purple-lt:#F3E8FF;
  --mtn-border:#CBD5E1; --mtn-muted:#64748B;
  --mtn-radius:10px; --mtn-shadow:0 2px 8px rgba(0,0,0,.08);
}

/* ── Two-column layout ───────────────────────────────────── */
.mtn-layout{display:flex;gap:16px;align-items:flex-start}
.mtn-panel{width:300px;flex-shrink:0;background:#fff;border:1px solid var(--mtn-border);border-radius:var(--mtn-radius);overflow:hidden;position:sticky;top:70px;max-height:calc(100vh - 90px);overflow-y:auto}
.mtn-main{flex:1;min-width:0}
.mtn-section{border-bottom:1px solid var(--mtn-border);padding:14px}
.mtn-section:last-child{border-bottom:none}
.mtn-section h2{font-size:11px;font-weight:700;color:var(--mtn-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
@media(max-width:900px){.mtn-layout{flex-direction:column}.mtn-panel{width:100%;position:static;max-height:none}}

/* ── Form controls ───────────────────────────────────────── */
.mf{margin-bottom:10px}
.mf label{display:block;font-size:12px;font-weight:600;color:var(--mtn-muted);margin-bottom:4px}
.mf input,.mf select,.mf textarea{width:100%;padding:7px 10px;border:1.5px solid var(--mtn-border);border-radius:7px;font-size:13px;color:#1E293B;background:#fff;transition:border-color .15s;font-family:inherit}
.mf input:focus,.mf select:focus{outline:none;border-color:var(--mtn-navy)}
.mf input[type=number]{text-align:center}
.mf-row{display:flex;gap:8px}.mf-row .mf{flex:1}

/* ── Tabs ─────────────────────────────────────────────────── */
.mtn-tabs{display:flex;border-bottom:2px solid var(--mtn-border);background:#fff;border-radius:var(--mtn-radius) var(--mtn-radius) 0 0;position:sticky;top:0;z-index:10}
.mtn-tab-btn{padding:10px 18px;font-size:13px;font-weight:600;color:var(--mtn-muted);border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
.mtn-tab-btn.active{color:var(--mtn-navy);border-bottom-color:var(--mtn-navy)}
.mtn-tab{display:none;padding:16px}.mtn-tab.active{display:block}

/* ── Card ─────────────────────────────────────────────────── */
.mtn-card{background:#fff;border-radius:var(--mtn-radius);border:1px solid var(--mtn-border);padding:16px;margin-bottom:14px;box-shadow:var(--mtn-shadow)}
.mtn-card-title{font-size:13px;font-weight:700;color:var(--mtn-navy);margin-bottom:12px;display:flex;align-items:center;gap:6px}

/* ── Buttons ─────────────────────────────────────────────── */
.mtn-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;font-family:inherit}
.mtn-btn-navy{background:var(--mtn-navy);color:#fff}.mtn-btn-navy:hover{background:var(--mtn-navy-lt)}
.mtn-btn-gen{background:#059669;color:#fff;font-size:14px;padding:10px 22px}.mtn-btn-gen:hover{background:#047857}
.mtn-btn-word{background:#1D6F42;color:#fff}.mtn-btn-word:hover{background:#155e35}
.mtn-btn-sm{padding:6px 12px;font-size:12px}
.mtn-btn-ol{background:#fff;border:1.5px solid var(--mtn-border);color:#1E293B}.mtn-btn-ol:hover{border-color:var(--mtn-navy);color:var(--mtn-navy)}
.mtn-btn-danger{background:#FEE2E2;color:#DC2626;border:1.5px solid #FECACA}
.mtn-btn-upload{background:var(--mtn-purple-lt);color:var(--mtn-purple);border:1.5px dashed var(--mtn-purple)}

/* ── Unit table ──────────────────────────────────────────── */
.unit-table{width:100%;border-collapse:collapse;font-size:12px}
.unit-table th{background:#F8FAFC;color:var(--mtn-muted);font-size:11px;font-weight:700;padding:7px 8px;text-align:center;border-bottom:2px solid var(--mtn-border)}
.unit-table td{padding:6px 8px;border-bottom:1px solid #F1F5F9;vertical-align:middle;text-align:center}
.unit-table tr:hover td{background:#F8FAFC}
.unit-table td:nth-child(2),.unit-table td:nth-child(3){text-align:left}
.unit-table td:nth-child(3){font-size:11px;color:var(--mtn-muted)}
.del-btn{background:none;border:none;cursor:pointer;color:#94A3B8;font-size:16px;padding:2px 6px;border-radius:5px}
.del-btn:hover{color:#DC2626;background:#FEE2E2}
.tiet-input{width:52px;text-align:center;padding:3px 6px;border:1.5px solid var(--mtn-border);border-radius:5px;font-size:12px}
.tiet-input:focus{outline:none;border-color:var(--mtn-navy)}

/* ── Level badges ────────────────────────────────────────── */
.badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-right:3px}
.badge-nb{background:#DBEAFE;color:#1D4ED8}
.badge-th{background:#D1FAE5;color:#065F46}
.badge-vd{background:#FEF3C7;color:#92400E}

/* ── Preview tables ──────────────────────────────────────── */
.out-table-wrap{overflow-x:auto;margin-top:12px}
.out-table{width:100%;border-collapse:collapse;font-size:11px;font-family:'Times New Roman',serif}
.out-table th,.out-table td{border:1px solid #333;padding:4px 5px;text-align:center;vertical-align:middle}
.out-table .hdr-main{background:#1A3A5C;color:#fff;font-weight:700}
.out-table .hdr-sub{background:#D0E4FF;font-weight:700}
.out-table .row-head{text-align:left;background:#F5F5F5;font-weight:600}
.out-table .total-row td{background:#E8F5E9;font-weight:700}
.out-table .pts-row td,.out-table .pct-row td{background:#FFF8E1;font-weight:700}

/* ── Upload zone ─────────────────────────────────────────── */
.drop-zone{border:2px dashed var(--mtn-purple);border-radius:var(--mtn-radius);padding:24px;text-align:center;cursor:pointer;transition:all .2s;background:var(--mtn-purple-lt)}
.drop-zone:hover,.drop-zone.drag-over{border-color:#5B21B6;background:#EDE9FE}
.drop-zone input[type=file]{display:none}

/* ── Subject chips ───────────────────────────────────────── */
.subj-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.subj-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#EFF6FF;color:var(--mtn-navy);border:1px solid #BFDBFE}
.subj-chip .rm{cursor:pointer;color:#94A3B8;font-size:14px}.subj-chip .rm:hover{color:#DC2626}

/* ── Toast ───────────────────────────────────────────────── */
#mtn-toast{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:6px}
.toast-item{padding:10px 16px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,.15);animation:mtnIn .2s ease}
.toast-item.success{background:#D1FAE5;color:#065F46}
.toast-item.err{background:#FEE2E2;color:#991B1B}
.toast-item.warn{background:#FEF3C7;color:#92400E}
.toast-item.info{background:#DBEAFE;color:#1D4ED8}
@keyframes mtnIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* ── Misc ────────────────────────────────────────────────── */
.sum-display{font-size:12px;padding:8px 10px;border-radius:6px;margin-top:6px;font-weight:500}
.sum-ok{background:#D1FAE5;color:#065F46}.sum-err{background:#FEE2E2;color:#991B1B}
.pct-total{font-size:12px;font-weight:700;margin-top:4px}
.pct-ok{color:#059669}.pct-err{color:#DC2626}
.spinner{display:none;width:18px;height:18px;border:2.5px solid #fff;border-top-color:transparent;border-radius:50%;animation:mtnSpin .7s linear infinite}
@keyframes mtnSpin{to{transform:rotate(360deg)}}
.empty-state{text-align:center;padding:32px;color:var(--mtn-muted)}
.empty-state .icon{font-size:36px;margin-bottom:8px}
.unit-levels-info{font-size:12px;margin-top:6px;padding:6px 10px;background:#F0F9FF;border-radius:6px;border-left:3px solid #38BDF8}
.export-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px}

/* ── Override table ──────────────────────────────────────── */
.ov-card{background:var(--mtn-purple-lt);border:2px solid var(--mtn-purple);border-radius:var(--mtn-radius);padding:16px;margin-bottom:16px}
.ov-card-title{font-size:12px;font-weight:700;color:var(--mtn-purple);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;display:flex;align-items:center;gap:6px}
.ov-card-sub{font-size:11px;color:#7C3AED;margin-bottom:12px;line-height:1.5}
.ov-table{border-collapse:collapse;width:100%;font-size:12px;table-layout:auto}
.ov-table th{background:var(--mtn-purple);color:#fff;padding:7px 10px;text-align:center;font-weight:700;white-space:nowrap}
.ov-table th:first-child{text-align:left}
.ov-table td{border:1px solid #C4B5FD;padding:5px 8px;text-align:center;vertical-align:middle}
.ov-table td:first-child{text-align:left;font-weight:500;font-size:11px;min-width:120px;max-width:180px;word-break:break-word}
.ov-table tr:nth-child(even) td{background:rgba(139,92,246,.05)}
.ov-input{width:46px;text-align:center;padding:3px 4px;border:1.5px solid #A78BFA;border-radius:4px;font-size:12px;font-family:inherit;color:#1E293B}
.ov-input:focus{outline:none;border-color:var(--mtn-purple);background:#EDE9FE}
.ov-input.err{border-color:#DC2626;background:#FEE2E2}
.ov-sum-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;align-items:center}
.ov-badge{font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;background:#EDE9FE;color:var(--mtn-purple)}
.ov-badge.ok{background:#D1FAE5;color:#065F46}.ov-badge.err{background:#FEE2E2;color:#DC2626}
.btn-recalc{background:var(--mtn-purple);color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s}
.btn-recalc:hover{background:#5B21B6}
.btn-reset-ov{background:#fff;color:var(--mtn-purple);border:1.5px solid var(--mtn-purple);border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
</style>

<div class="main-content">
    <div class="container py-4 mb-5" style="max-width:1680px;">
        <!-- Header -->
        <div class="section-header mb-4">
            <div class="sh-icon"><i class="bi bi-diagram-3"></i></div>
            <div class="flex-grow-1">
                <h3 class="mb-0">Xây Dựng Ma Trận</h3>
                <p class="mb-0">Tạo ma trận và bảng đặc tả theo chuẩn Bộ GD&ĐT, xuất file Word</p>
            </div>
            <a href="teacher.php" class="btn btn-soft-slate btn-action-custom">
                <i class="bi bi-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>

        <div class="mtn-layout">

            <!-- ── PANEL CẤU HÌNH (trái) ─────────────────────────────── -->
            <div class="mtn-panel">

                <div class="mtn-section">
                    <h2>📝 Thông tin đề</h2>
                    <div class="mf"><label>Tên trường</label><input type="text" id="meta_school" value="Trường THCS ..." placeholder="Nhập tên trường"></div>
                    <div class="mf-row">
                        <div class="mf"><label>Năm học</label><input type="text" id="meta_year" value="2024-2025"></div>
                        <div class="mf"><label>Thời gian (phút)</label><input type="number" id="meta_duration" value="45" min="15" max="180"></div>
                    </div>
                    <div class="mf-row">
                        <div class="mf"><label>Loại kiểm tra</label>
                            <select id="meta_type"><option>Kiểm tra giữa kỳ</option><option>Kiểm tra cuối kỳ</option><option>Kiểm tra 1 tiết</option></select>
                        </div>
                        <div class="mf"><label>Học kỳ</label>
                            <select id="meta_semester"><option value="HK1">Học kỳ 1</option><option value="HK2" selected>Học kỳ 2</option></select>
                        </div>
                    </div>
                </div>

                <div class="mtn-section">
                    <h2>⚙️ Cấu trúc đề</h2>
                    <div class="mf"><label>TNKQ Nhiều lựa chọn</label>
                        <select id="cfg_tnkq_num" onchange="cfgChanged()">
                            <option value="8" selected>8 câu × 0.50đ = 4đ</option>
                            <option value="16">16 câu × 0.25đ = 4đ</option>
                        </select>
                    </div>
                    <div class="mf"><label>TNKQ Đúng/Sai <small style="font-weight:400">(cố định)</small></label>
                        <input type="text" value="2 câu × 4 ý × 0.25đ = 2đ" disabled style="color:var(--mtn-muted);background:#F8FAFC">
                    </div>
                    <div class="mf-row">
                        <div class="mf"><label>Điểm Tự luận</label><input type="number" id="cfg_tl_pts" value="4" min="1" max="7" step="0.5" onchange="cfgChanged()"></div>
                        <div class="mf"><label>Số câu TL</label>
                            <select id="cfg_tl_num" onchange="cfgChanged()"><option value="3">3 câu</option><option value="4" selected>4 câu</option></select>
                        </div>
                    </div>
                    <div id="cfg_sum" class="sum-display sum-ok">TNKQ: 4đ | DS: 2đ | TL: 4đ = 10đ ✔</div>
                </div>

                <div class="mtn-section">
                    <h2>📊 Tỉ lệ nhận thức</h2>
                    <div class="mf-row">
                        <div class="mf"><label><span class="badge badge-nb">Nhận biết</span></label><input type="number" id="pct_nb" value="35" min="0" max="100" onchange="pctChanged()"></div>
                        <div class="mf"><label><span class="badge badge-th">Thông hiểu</span></label><input type="number" id="pct_th" value="35" min="0" max="100" onchange="pctChanged()"></div>
                        <div class="mf"><label><span class="badge badge-vd">Vận dụng</span></label><input type="number" id="pct_vd" value="30" min="0" max="100" onchange="pctChanged()"></div>
                    </div>
                    <div id="pct_check" class="pct-total pct-ok">✔ Tổng: 100%</div>
                </div>

            </div><!-- /mtn-panel -->

            <!-- ── MAIN (phải) ──────────────────────────────────────────── -->
            <div class="mtn-main">

                <div class="mtn-tabs">
                    <button class="mtn-tab-btn active" onclick="switchTab('input',this)">📖 Đơn vị kiến thức</button>
                    <button class="mtn-tab-btn" onclick="switchTab('subjects',this)">📚 Môn học</button>
                    <button class="mtn-tab-btn" onclick="switchTab('output',this)">📊 Kết quả</button>
                </div>

                <!-- TAB 1: Nhập đơn vị -->
                <div id="tab-input" class="mtn-tab active">
                    <div class="mtn-card">
                        <div class="mtn-card-title">🔍 Chọn môn & thêm đơn vị kiến thức</div>
                        <div class="mf-row" style="flex-wrap:wrap;gap:10px;align-items:flex-end">
                            <div class="mf" style="min-width:140px"><label>Môn học</label><select id="sel_subject" onchange="onSubjectChange()"></select></div>
                            <div class="mf" style="min-width:90px"><label>Khối lớp</label>
                                <select id="sel_grade" onchange="populateUnits()">
                                    <option value="6">Lớp 6</option><option value="7">Lớp 7</option><option value="8" selected>Lớp 8</option><option value="9">Lớp 9</option>
                                </select>
                            </div>
                            <div class="mf" style="flex:2;min-width:220px"><label>Đơn vị kiến thức</label><select id="sel_unit" onchange="showUnitLevels()" style="width:100%"></select></div>
                            <div class="mf" style="min-width:70px"><label>Số tiết</label><input type="number" id="sel_tiet" value="2" min="1" max="50" style="width:66px"></div>
                            <div><button class="mtn-btn mtn-btn-navy" onclick="addUnit()" style="margin-bottom:10px">＋ Thêm</button></div>
                        </div>
                        <div id="unit_levels" class="unit-levels-info" style="display:none"></div>
                    </div>

                    <div class="mtn-card">
                        <div class="mtn-card-title">📋 Danh sách đơn vị kiến thức
                            <span id="tiet_total" style="font-size:11px;font-weight:400;color:var(--mtn-muted);margin-left:8px"></span>
                        </div>
                        <div id="unit_empty" class="empty-state"><div class="icon">📭</div><div>Chưa có đơn vị nào. Hãy chọn và nhấn <b>＋ Thêm</b></div></div>
                        <div class="out-table-wrap" id="unit_table_wrap" style="display:none">
                            <table class="unit-table">
                                <thead><tr><th style="width:28px">#</th><th style="text-align:left">Chủ đề</th><th style="text-align:left">Đơn vị kiến thức</th><th>Tiết</th><th>Tỉ trọng</th><th>Mức độ</th><th></th></tr></thead>
                                <tbody id="unit_tbody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px">
                        <button class="mtn-btn mtn-btn-gen" onclick="doGenerate()">
                            <span>🔄 Tạo Ma trận & Đặc tả</span>
                            <span class="spinner" id="gen_spinner"></span>
                        </button>
                        <span style="font-size:12px;color:var(--mtn-muted)">Cần ít nhất 2 đơn vị kiến thức</span>
                    </div>

                    <!-- Bảng tinh chỉnh số câu -->
                    <div class="ov-card" id="ov_inline" style="display:none">
                        <div class="ov-card-title">✏️ Tinh chỉnh số câu / ý theo mức độ</div>
                        <div class="ov-card-sub">Tỉ lệ % thực tế có thể lệch do làm tròn. Chỉnh trực tiếp vào bảng rồi nhấn <b>Tính lại & xem kết quả</b>.</div>
                        <div style="overflow-x:auto">
                            <table class="ov-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2" style="text-align:left;min-width:120px">Đơn vị</th>
                                        <th colspan="3" style="background:#7C3AED">TNKQ lựa chọn</th>
                                        <th colspan="3" style="background:#6D28D9">Đúng/Sai (ý)</th>
                                        <th colspan="3" style="background:#5B21B6">Tự luận</th>
                                        <th rowspan="2" style="background:#4C1D95;min-width:46px;font-size:10px">∑TN</th>
                                        <th rowspan="2" style="background:#4C1D95;min-width:46px;font-size:10px">∑DS</th>
                                        <th rowspan="2" style="background:#4C1D95;min-width:46px;font-size:10px">∑TL</th>
                                        <th rowspan="2" style="background:#3730A3;min-width:56px;font-size:10px">∑ Điểm</th>
                                    </tr>
                                    <tr>
                                        <th style="background:#7C3AED;font-size:10px">B</th><th style="background:#7C3AED;font-size:10px">H</th><th style="background:#7C3AED;font-size:10px">VD</th>
                                        <th style="background:#6D28D9;font-size:10px">B</th><th style="background:#6D28D9;font-size:10px">H</th><th style="background:#6D28D9;font-size:10px">VD</th>
                                        <th style="background:#5B21B6;font-size:10px">B</th><th style="background:#5B21B6;font-size:10px">H</th><th style="background:#5B21B6;font-size:10px">VD</th>
                                    </tr>
                                </thead>
                                <tbody id="ov_tbody"></tbody>
                            </table>
                        </div>
                        <div class="ov-sum-row" id="ov_sums"></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
                            <button class="btn-recalc" onclick="applyOverride()">🔄 Tính lại & xem kết quả</button>
                            <button class="btn-reset-ov" onclick="resetOverride()">↺ Khôi phục tự động</button>
                            <button class="mtn-btn mtn-btn-word mtn-btn-sm" onclick="exportWord('all')" style="margin-left:8px">📄 Xuất Word ngay</button>
                        </div>
                    </div>
                </div><!-- /tab-input -->

                <!-- TAB 2: Môn học -->
                <div id="tab-subjects" class="mtn-tab">
                    <div class="mtn-card">
                        <div class="mtn-card-title">📚 Môn học đã tải</div>
                        <div id="subj_chips" class="subj-chips"></div>
                        <p style="font-size:12px;color:var(--mtn-muted)">Môn <b>Tin học</b> được tích hợp sẵn, không thể xóa. Upload file JSON để thêm môn khác.</p>
                    </div>
                    <div class="mtn-card">
                        <div class="mtn-card-title">⬆️ Upload bản đặc tả môn học (JSON)</div>
                        <div class="drop-zone" id="drop_zone" onclick="document.getElementById('json_file').click()" ondragover="event.preventDefault();this.classList.add('drag-over')" ondragleave="this.classList.remove('drag-over')" ondrop="handleDrop(event)">
                            <input type="file" id="json_file" accept=".json" onchange="handleFileUpload(this.files[0])">
                            <div style="font-size:32px;margin-bottom:6px">📂</div>
                            <div><b>Kéo thả file JSON</b> hoặc nhấn để chọn</div>
                            <div style="font-size:11px;margin-top:4px;color:var(--mtn-muted)">Tối đa 2MB · Định dạng JSON chuẩn Bộ GD&ĐT</div>
                        </div>
                        <div id="upload_status" style="margin-top:10px;font-size:13px"></div>
                    </div>
                    <div class="mtn-card" style="position:relative">
                        <div class="mtn-card-title">📋 Cấu trúc JSON yêu cầu</div>
                        <button onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent);this.textContent='✓ Đã copy';setTimeout(()=>this.textContent='Copy',1500)" style="position:absolute;top:12px;right:12px;background:#F1F5F9;border:1px solid var(--mtn-border);color:var(--mtn-muted);padding:4px 10px;border-radius:6px;cursor:pointer;font-size:11px;transition:all .15s">Copy</button>
                        <pre id="jsonTemplate" style="background:#0F172A;color:#94A3B8;padding:14px;border-radius:8px;font-size:11.5px;overflow-x:auto;line-height:1.7">{
  <span style="color:#7DD3FC">"tên_tài_liệu"</span>: <span style="color:#86EFAC">"Tên môn học"</span>,
  <span style="color:#7DD3FC">"các_khối"</span>: {
    <span style="color:#7DD3FC">"6"</span>: [
      {
        <span style="color:#7DD3FC">"Đơn vị kiến thức"</span>: <span style="color:#86EFAC">"1. Tên chủ đề"</span>,
        <span style="color:#7DD3FC">"Nội dung kiến thức"</span>: <span style="color:#86EFAC">"Nội dung cụ thể"</span>,
        <span style="color:#7DD3FC">"Mức độ đánh giá"</span>: {
          <span style="color:#7DD3FC">"Nhận biết"</span>: [<span style="color:#86EFAC">"Yêu cầu 1"</span>, <span style="color:#86EFAC">"Yêu cầu 2"</span>],
          <span style="color:#7DD3FC">"Thông hiểu"</span>: [<span style="color:#86EFAC">"Yêu cầu 1"</span>],
          <span style="color:#7DD3FC">"Vận dụng"</span>: [<span style="color:#86EFAC">"Yêu cầu 1"</span>]
        }
      }
    ],
    <span style="color:#7DD3FC">"7"</span>: [ ... ],
    <span style="color:#7DD3FC">"8"</span>: [ ... ],
    <span style="color:#7DD3FC">"9"</span>: [ ... ]
  }
}</pre>
                        <div style="margin-top:8px;font-size:11px;color:var(--mtn-muted)">
                            <b>Lưu ý:</b> Mỗi "Yêu cầu" là 1 câu mô tả học sinh cần đạt (vd: "Nhận biết được tập hợp số tự nhiên").
                            Các cấp bậc không bắt buộc đều có, nếu không có bạn có thể bỏ qua key đó.
                        </div>
                    </div>
                </div><!-- /tab-subjects -->

                <!-- TAB 3: Kết quả -->
                <div id="tab-output" class="mtn-tab">
                    <div id="output_empty" class="empty-state" style="padding:48px">
                        <div class="icon">📊</div>
                        <div>Chưa có kết quả. Chuyển sang tab <b>Đơn vị kiến thức</b> và nhấn <b>Tạo Ma trận</b>.</div>
                    </div>
                    <div id="output_content" style="display:none">
                        <div class="export-bar">
                            <b style="font-size:13px">Xuất Word:</b>
                            <button class="mtn-btn mtn-btn-word" onclick="exportWord('all')">📄 Ma trận + Đặc tả</button>
                            <button class="mtn-btn mtn-btn-word mtn-btn-sm" onclick="exportWord('matran')">Ma trận</button>
                            <button class="mtn-btn mtn-btn-word mtn-btn-sm" onclick="exportWord('dacta')">Đặc tả</button>
                            <button class="mtn-btn mtn-btn-ol mtn-btn-sm" onclick="window.print()">🖨️ In</button>
                        </div>
                        <div class="mtn-card">
                            <div class="mtn-card-title">MA TRẬN ĐỀ KIỂM TRA</div>
                            <div id="preview_subtitle" style="font-size:12px;color:var(--mtn-muted);margin-bottom:10px"></div>
                            <div class="out-table-wrap" id="preview_matran"></div>
                        </div>
                        <div class="mtn-card" style="margin-top:16px">
                            <div class="mtn-card-title">BẢN ĐẶC TẢ ĐỀ KIỂM TRA</div>
                            <div class="out-table-wrap" id="preview_dacta"></div>
                        </div>
                    </div>
                </div><!-- /tab-output -->

            </div><!-- /mtn-main -->
        </div><!-- /mtn-layout -->
    </div>
</div>

<div id="mtn-toast"></div>

<script>
// ── STATE ──────────────────────────────────────────────────────────────────
let units      = [];
let subjects   = [];
let lastResult = null;
const API = 'api/matran/';

// ── INIT ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => { loadSubjects(); cfgChanged(); pctChanged(); });

// ── SUBJECTS ───────────────────────────────────────────────────────────────
async function loadSubjects() {
  const res = await fetch(API + 'list_subjects.php'); const data = await res.json();
  if (!data.ok) { toast('Lỗi tải danh sách môn: ' + data.error, 'err'); return; }
  subjects = data.subjects; renderSubjectChips(); renderSubjectSelector();
}
function renderSubjectChips() {
  document.getElementById('subj_chips').innerHTML = subjects.map(s =>
    `<span class="subj-chip">📖 ${s.name}<span class="rm" onclick="removeSubject('${s.slug}',event)">×</span></span>`
  ).join('');
}
function renderSubjectSelector() {
  const sel = document.getElementById('sel_subject'); const cur = sel.value;
  sel.innerHTML = subjects.map(s => `<option value="${s.slug}">${s.name}</option>`).join('');
  if (cur && subjects.find(s => s.slug === cur)) sel.value = cur;
  onSubjectChange();
}
async function removeSubject(slug, e) {
  e.stopPropagation();
  const s = subjects.find(x => x.slug === slug);
  if (!s || !confirm(`Xóa môn "${s.name}"?`)) return;
  const res = await fetch(`${API}delete_subject.php?slug=${encodeURIComponent(slug)}`);
  const data = await res.json();
  if (!data.ok) { toast(data.error, 'err'); return; }
  subjects = subjects.filter(x => x.slug !== slug);
  renderSubjectChips(); renderSubjectSelector(); toast(`Đã xóa môn ${s.name}`, 'warn');
}
function handleDrop(e) {
  e.preventDefault(); document.getElementById('drop_zone').classList.remove('drag-over');
  const file = e.dataTransfer.files[0]; if (file) handleFileUpload(file);
}
async function handleFileUpload(file) {
  if (!file) return;
  if (!file.name.endsWith('.json')) { toast('Chỉ hỗ trợ file .json', 'err'); return; }
  const fd = new FormData(); fd.append('subject_json', file);
  const statusEl = document.getElementById('upload_status');
  statusEl.innerHTML = '<span style="color:#3B82F6">⏳ Đang upload...</span>';
  const res = await fetch(API + 'upload_subject.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (!data.ok) { statusEl.innerHTML = `<span style="color:#DC2626">✘ ${data.error}</span>`; toast(data.error, 'err'); return; }
  statusEl.innerHTML = `<span style="color:#059669;font-weight:600">✔ ${data.msg}</span>`;
  toast('✔ ' + data.msg, 'success'); await loadSubjects();
}

// ── UNIT SELECTION ─────────────────────────────────────────────────────────
function onSubjectChange() {
  const slug = document.getElementById('sel_subject').value;
  const s = subjects.find(x => x.slug === slug);
  const gradeEl = document.getElementById('sel_grade'); const curGrade = gradeEl.value;
  if (s) {
    Array.from(gradeEl.options).forEach(o => { o.disabled = !s.grades.includes(o.value); });
    if (s.grades.length && !s.grades.includes(curGrade)) gradeEl.value = s.grades[0];
  }
  populateUnits();
}
async function populateUnits() {
  const slug = document.getElementById('sel_subject').value;
  const grade = document.getElementById('sel_grade').value;
  const sel = document.getElementById('sel_unit');
  sel.innerHTML = '<option>Đang tải...</option>';
  let items = [];
  const res = await fetch(`${API}get_units.php?slug=${encodeURIComponent(slug)}&grade=${grade}`);
  const data = await res.json();
  if (!data.ok) { sel.innerHTML = '<option>Lỗi tải dữ liệu</option>'; toast(data.error,'err'); return; }
  items = data.units || [];
  sel.innerHTML = items.length
    ? items.map((item, i) => `<option value="${i}">${item['Đơn vị kiến thức']} (${item['Nội dung kiến thức']})</option>`).join('')
    : '<option value="">— Không có dữ liệu cho khối này —</option>';
  window._currentItems = items; showUnitLevels();
}
function showUnitLevels() {
  const idx = parseInt(document.getElementById('sel_unit').value);
  const items = window._currentItems || []; const item = items[idx];
  const el = document.getElementById('unit_levels');
  if (!item) { el.style.display = 'none'; return; }
  const lvls = item['Mức độ đánh giá'] || {};
  const tags = [];
  if (lvls['Nhận biết']) tags.push('<span class="badge badge-nb">Nhận biết</span>');
  if (lvls['Thông hiểu']) tags.push('<span class="badge badge-th">Thông hiểu</span>');
  if (lvls['Vận dụng'] || lvls['Vận dụng cao']) tags.push('<span class="badge badge-vd">Vận dụng</span>');
  let warn = '';
  if (!lvls['Nhận biết'] && !lvls['Thông hiểu']) warn = ' &nbsp;<span style="color:#C2410C;font-weight:600;font-size:11px">⚠ Sẽ hạ mức Vận dụng</span>';
  el.style.display = 'block';
  el.innerHTML = '<b>Mức độ:</b> ' + tags.join(' ') + warn;
}
function addUnit() {
  const idx = parseInt(document.getElementById('sel_unit').value);
  const tiet = parseInt(document.getElementById('sel_tiet').value) || 1;
  const grade = document.getElementById('sel_grade').value;
  const slug = document.getElementById('sel_subject').value;
  const items = window._currentItems || []; const item = items[idx];
  if (!item) { toast('Chọn đơn vị kiến thức trước.', 'warn'); return; }
  units.push({ nd: item['Nội dung kiến thức'], dv: item['Đơn vị kiến thức'], tiet, muc: item['Mức độ đánh giá'] || {}, grade, subjSlug: slug });
  renderUnits(); toast(`✔ Đã thêm: ${item['Đơn vị kiến thức']}`, 'success');
}
function removeUnit(i) { units.splice(i, 1); renderUnits(); }
function renderUnits() {
  const tbody = document.getElementById('unit_tbody');
  const empty = document.getElementById('unit_empty');
  const wrap  = document.getElementById('unit_table_wrap');
  const total = units.reduce((s,u) => s + u.tiet, 0);
  document.getElementById('tiet_total').textContent = total > 0 ? `Tổng: ${total} tiết` : '';
  if (!units.length) { empty.style.display=''; wrap.style.display='none'; return; }
  empty.style.display='none'; wrap.style.display='';
  tbody.innerHTML = units.map((u,i) => {
    const pct = total > 0 ? (u.tiet/total*100).toFixed(1) : '—';
    const lvls = Object.keys(u.muc||{}).map(k => {
      if(k==='Nhận biết') return '<span class="badge badge-nb">Biết</span>';
      if(k==='Thông hiểu') return '<span class="badge badge-th">Hiểu</span>';
      if(k==='Vận dụng'||k==='Vận dụng cao') return '<span class="badge badge-vd">VD</span>'; return '';
    }).filter((v,idx,arr)=>v&&arr.indexOf(v)===idx).join('');
    return `<tr><td>${i+1}</td><td style="text-align:left">${escHtml(u.nd)}</td><td style="text-align:left">${escHtml(u.dv)}</td>
    <td><input class="tiet-input" type="number" value="${u.tiet}" min="1" max="50" onchange="units[${i}].tiet=parseInt(this.value)||1;renderUnits()"></td>
    <td><b>${pct}%</b></td><td>${lvls}</td>
    <td><button class="del-btn" onclick="removeUnit(${i})" title="Xóa">✕</button></td></tr>`;
  }).join('');
}

// ── GENERATE ───────────────────────────────────────────────────────────────
async function doGenerate() {
  if (units.length < 2) { toast('Cần ít nhất 2 đơn vị kiến thức.', 'warn'); return; }
  const spinner = document.getElementById('gen_spinner'); spinner.style.display = 'block';
  const fd = buildFormData('preview');
  const res = await fetch(API + 'generate.php', { method: 'POST', body: fd });
  const data = await res.json(); spinner.style.display = 'none';
  if (!data.ok) { toast(data.error, 'err'); return; }
  lastResult = data; renderPreview(data.unitData, data.ctx, data.meta); renderOverrideTable(data.unitData);
  if (data.warnings?.length) setTimeout(() => data.warnings.forEach(w => toast('⚠ '+w,'warn')), 300);
  toast('✔ Đã tạo ma trận! Xem bảng tinh chỉnh bên dưới.', 'success');
}
async function exportWord(type) {
  if (!lastResult) { toast('Chưa có dữ liệu. Hãy tạo ma trận trước.', 'warn'); return; }
  toast('⏳ Đang tạo file Word...', 'info');
  const fd = buildFormData('export_' + (type === 'all' ? 'all' : type), true);
  const form = document.createElement('form'); form.method='POST'; form.action=API+'generate.php'; form.style.display='none';
  for (const [k,v] of fd.entries()) { const inp=document.createElement('input'); inp.type='hidden'; inp.name=k; inp.value=v; form.appendChild(inp); }
  document.body.appendChild(form); form.submit(); document.body.removeChild(form);
  setTimeout(() => toast('✔ File Word đang tải xuống!', 'success'), 800);
}
function buildFormData(action, includeOverride=false) {
  const fd = new FormData();
  fd.append('action', action); fd.append('units', JSON.stringify(units));
  fd.append('tnkq_num', document.getElementById('cfg_tnkq_num').value);
  fd.append('pct_nb', document.getElementById('pct_nb').value);
  fd.append('pct_th', document.getElementById('pct_th').value);
  fd.append('pct_vd', document.getElementById('pct_vd').value);
  fd.append('tl_pts', document.getElementById('cfg_tl_pts').value);
  fd.append('tl_num', document.getElementById('cfg_tl_num').value);
  fd.append('school', document.getElementById('meta_school').value);
  fd.append('subject', subjects.find(s => s.slug === document.getElementById('sel_subject').value)?.name || 'Môn học');
  fd.append('grade', units[0]?.grade || document.getElementById('sel_grade').value);
  fd.append('semester', document.getElementById('meta_semester').value);
  fd.append('year', document.getElementById('meta_year').value);
  fd.append('duration', document.getElementById('meta_duration').value);
  fd.append('examType', document.getElementById('meta_type').value);
  if (includeOverride) { const ovBody=document.getElementById('ov_tbody'); if(ovBody?.querySelector('input')) fd.append('overrides', JSON.stringify(readOverrides())); }
  return fd;
}

// ── PREVIEW RENDER ─────────────────────────────────────────────────────────
function renderPreview(unitData, ctx, meta) {
  document.getElementById('output_empty').style.display='none';
  document.getElementById('output_content').style.display='';
  const { tnkq_per_q, ds_pt_per_item, pts_ds, pts_tl, tl_num, pct_nb, pct_th, pct_vd, pts_tnkq, total_pts, tl_per_q } = ctx;
  document.getElementById('preview_subtitle').textContent =
    `${meta.school}  |  Môn: ${meta.subject}  |  Lớp ${meta.grade}  |  ${meta.examType} ${meta.semester} – ${meta.year}  |  ${meta.duration} phút`;
  let tot = {tnb:0,tth:0,tvd:0,dnb:0,dth:0,dvd:0,lnb:0,lth:0,lvd:0};
  let rows = unitData.map(u => {
    const nb=u.u_tnkq_nb*tnkq_per_q+u.u_ds_nb*ds_pt_per_item+u.u_tl_nb*tl_per_q;
    const th=u.u_tnkq_th*tnkq_per_q+u.u_ds_th*ds_pt_per_item+u.u_tl_th*tl_per_q;
    const vd=u.u_tnkq_vd*tnkq_per_q+u.u_ds_vd*ds_pt_per_item+u.u_tl_vd*tl_per_q;
    tot.tnb+=u.u_tnkq_nb;tot.tth+=u.u_tnkq_th;tot.tvd+=u.u_tnkq_vd;
    tot.dnb+=u.u_ds_nb;tot.dth+=u.u_ds_th;tot.dvd+=u.u_ds_vd;
    tot.lnb+=u.u_tl_nb;tot.lth+=u.u_tl_th;tot.lvd+=u.u_tl_vd;
    return `<tr>
      <td class="row-head">${escHtml(u.nd)}<br><small style="font-weight:400">${escHtml(u.dv)} (${u.tiet} tiết)</small></td>
      <td>${fn(u.tnkq_nb_nums)}</td><td>${fn(u.tnkq_th_nums)}</td><td>${fn(u.tnkq_vd_nums)}</td>
      <td>${fn(u.ds_nb_nums)}</td><td>${fn(u.ds_th_nums)}</td><td>${fn(u.ds_vd_nums)}</td>
      <td>${fn(u.tl_nb_nums)}</td><td>${fn(u.tl_th_nums)}</td><td>${fn(u.tl_vd_nums)}</td>
      <td><b>${fp(nb)}đ</b></td><td><b>${fp(th)}đ</b></td><td><b>${fp(vd)}đ</b></td>
      <td><b>${fp(nb+th+vd)}đ</b></td><td><b>${Math.round(u.ratio*100)}%</b></td>
    </tr>`;
  }).join('');
  const nb_s=tot.tnb*tnkq_per_q+tot.dnb*ds_pt_per_item+tot.lnb*tl_per_q;
  const th_s=tot.tth*tnkq_per_q+tot.dth*ds_pt_per_item+tot.lth*tl_per_q;
  const vd_s=tot.tvd*tnkq_per_q+tot.dvd*ds_pt_per_item+tot.lvd*tl_per_q;
  const grand=nb_s+th_s+vd_s;
  const rNb=Math.round(nb_s/grand*100),rTh=Math.round(th_s/grand*100),rVd=100-rNb-rTh;
  const pTNKQ=Math.round((tot.tnb+tot.tth+tot.tvd)*tnkq_per_q/grand*100);
  const pDS=Math.round((tot.dnb+tot.dth+tot.dvd)*ds_pt_per_item/grand*100);
  const pTL=100-pTNKQ-pDS;
  document.getElementById('preview_matran').innerHTML = `
  <table class="out-table">
    <thead>
      <tr><th class="hdr-main" rowspan="2">Chủ đề / Đơn vị (tiết)</th>
        <th class="hdr-main" colspan="3">TNKQ nhiều lựa chọn (${tnkq_per_q}đ/câu)</th>
        <th class="hdr-main" colspan="3">TNKQ Đúng/Sai (0.25đ/ý)</th>
        <th class="hdr-main" colspan="3">Tự luận</th>
        <th class="hdr-main" colspan="3">Tổng điểm theo mức</th>
        <th class="hdr-main" rowspan="2">Tổng điểm</th><th class="hdr-main" rowspan="2">Tỉ lệ %</th></tr>
      <tr><th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th>
          <th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th>
          <th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th>
          <th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th></tr>
    </thead>
    <tbody>${rows}
      <tr class="total-row"><td class="row-head">Tổng số câu/ý</td>
        <td>${tot.tnb}c</td><td>${tot.tth}c</td><td>${tot.tvd}c</td>
        <td>${tot.dnb}ý</td><td>${tot.dth}ý</td><td>${tot.dvd}ý</td>
        <td>${tot.lnb}c</td><td>${tot.lth}c</td><td>${tot.lvd}c</td>
        <td>${fp(nb_s)}đ</td><td>${fp(th_s)}đ</td><td>${fp(vd_s)}đ</td>
        <td>${fp(grand)}đ</td><td></td></tr>
      <tr class="pts-row"><td class="row-head">Tổng điểm</td>
        <td colspan="3">${fp((tot.tnb+tot.tth+tot.tvd)*tnkq_per_q)}đ</td>
        <td colspan="3">${fp((tot.dnb+tot.dth+tot.dvd)*ds_pt_per_item)}đ</td>
        <td colspan="3">${fp((tot.lnb+tot.lth+tot.lvd)*tl_per_q)}đ</td>
        <td>${fp(nb_s)}đ</td><td>${fp(th_s)}đ</td><td>${fp(vd_s)}đ</td>
        <td>${fp(grand)}đ</td><td></td></tr>
      <tr class="pct-row"><td class="row-head">Tỉ lệ %</td>
        <td colspan="3">${pTNKQ}%</td><td colspan="3">${pDS}%</td><td colspan="3">${pTL}%</td>
        <td>${rNb}%</td><td>${rTh}%</td><td>${rVd}%</td><td>100%</td><td></td></tr>
    </tbody></table>`;
  const dtRows = unitData.map(u => {
    const nb=reqText(u.muc,'NB'),th=reqText(u.muc,'TH'),vd=reqText(u.muc,'VD');
    const parts=[];
    if(nb.length) parts.push('<b>Nhận biết:</b><br>'+nb.map(r=>'– '+escHtml(r)).join('<br>'));
    if(th.length) parts.push('<b>Thông hiểu:</b><br>'+th.map(r=>'– '+escHtml(r)).join('<br>'));
    if(vd.length) parts.push('<b>Vận dụng:</b><br>'+vd.map(r=>'– '+escHtml(r)).join('<br>'));
    return `<tr><td style="text-align:left">${escHtml(u.nd)}</td><td style="text-align:left">${escHtml(u.dv)}</td>
    <td style="text-align:left;font-size:10px;line-height:1.5">${parts.join('<br>')}</td>
    <td>${u.u_tnkq_nb||''}</td><td>${u.u_tnkq_th||''}</td><td>${u.u_tnkq_vd||''}</td>
    <td>${u.u_ds_nb||''}</td><td>${u.u_ds_th||''}</td><td>${u.u_ds_vd||''}</td>
    <td>${u.u_tl_nb||''}</td><td>${u.u_tl_th||''}</td><td>${u.u_tl_vd||''}</td></tr>`;
  }).join('');
  document.getElementById('preview_dacta').innerHTML = `
  <table class="out-table">
    <thead>
      <tr><th class="hdr-main" rowspan="2">Chương/Chủ đề</th><th class="hdr-main" rowspan="2">Đơn vị kiến thức</th><th class="hdr-main" rowspan="2">Yêu cầu cần đạt</th>
        <th class="hdr-main" colspan="3">TNKQ nhiều lựa chọn</th><th class="hdr-main" colspan="3">TNKQ Đúng/Sai</th><th class="hdr-main" colspan="3">Tự luận</th></tr>
      <tr><th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th>
          <th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th>
          <th class="hdr-sub">Biết</th><th class="hdr-sub">Hiểu</th><th class="hdr-sub">VD</th></tr>
    </thead><tbody>${dtRows}</tbody></table>`;
  renderOverrideTable(unitData);
}

// ── OVERRIDE TABLE ─────────────────────────────────────────────────────────
function renderOverrideTable(unitData) {
  const tbody = document.getElementById('ov_tbody'); if(!tbody) return;
  document.getElementById('ov_inline').style.display='';
  tbody.innerHTML = unitData.map((u,i) => {
    const inp=(field,val,max)=>`<input class="ov-input" type="number" min="0" max="${max}" value="${val}" data-idx="${i}" data-field="${field}" oninput="ovChanged()">`;
    const totTnkq=u.u_tnkq_nb+u.u_tnkq_th+u.u_tnkq_vd, totDs=u.u_ds_nb+u.u_ds_th+u.u_ds_vd, totTl=u.u_tl_nb+u.u_tl_th+u.u_tl_vd;
    const ctx=lastResult?.ctx||{}; const pts=calcRowPts(u,ctx);
    return `<tr data-idx="${i}">
      <td title="${escHtml(u.dv)}">${escHtml(u.dv)}</td>
      <td>${inp('u_tnkq_nb',u.u_tnkq_nb,20)}</td><td>${inp('u_tnkq_th',u.u_tnkq_th,20)}</td><td>${inp('u_tnkq_vd',u.u_tnkq_vd,20)}</td>
      <td>${inp('u_ds_nb',u.u_ds_nb,8)}</td><td>${inp('u_ds_th',u.u_ds_th,8)}</td><td>${inp('u_ds_vd',u.u_ds_vd,8)}</td>
      <td>${inp('u_tl_nb',u.u_tl_nb,10)}</td><td>${inp('u_tl_th',u.u_tl_th,10)}</td><td>${inp('u_tl_vd',u.u_tl_vd,10)}</td>
      <td class="ov-tot-tnkq"><b>${totTnkq}</b></td><td class="ov-tot-ds"><b>${totDs}</b></td><td class="ov-tot-tl"><b>${totTl}</b></td>
      <td class="ov-tot-pts"><b>${fp(pts)}đ</b></td></tr>`;
  }).join('');
  ovChanged();
}
function calcRowPts(u,ctx){ const{tnkq_per_q=0.5,ds_pt_per_item=0.25,tl_per_q=1}=ctx; return(u.u_tnkq_nb+u.u_tnkq_th+u.u_tnkq_vd)*tnkq_per_q+(u.u_ds_nb+u.u_ds_th+u.u_ds_vd)*ds_pt_per_item+(u.u_tl_nb+u.u_tl_th+u.u_tl_vd)*tl_per_q; }
function readOverrides(){ const ov={}; document.querySelectorAll('#ov_tbody input.ov-input').forEach(inp=>{ const i=+inp.dataset.idx,f=inp.dataset.field; if(!ov[i])ov[i]={}; ov[i][f]=Math.max(0,parseInt(inp.value)||0); }); return ov; }
function ovChanged(){
  if(!lastResult) return; const ctx=lastResult.ctx; const{tnkq_per_q,ds_pt_per_item,tl_per_q}=ctx;
  let gTnkq=0,gDs=0,gTl=0,gNb=0,gTh=0,gVd=0,gPts=0;
  document.querySelectorAll('#ov_tbody tr[data-idx]').forEach(row=>{
    const g=f=>Math.max(0,parseInt(row.querySelector(`[data-field="${f}"]`)?.value)||0);
    const tnb=g('u_tnkq_nb'),tth=g('u_tnkq_th'),tvd=g('u_tnkq_vd');
    const dnb=g('u_ds_nb'),dth=g('u_ds_th'),dvd=g('u_ds_vd');
    const lnb=g('u_tl_nb'),lth=g('u_tl_th'),lvd=g('u_tl_vd');
    const tTnkq=tnb+tth+tvd,tDs=dnb+dth+dvd,tTl=lnb+lth+lvd;
    const rPts=tTnkq*tnkq_per_q+tDs*ds_pt_per_item+tTl*tl_per_q;
    row.querySelector('.ov-tot-tnkq').innerHTML=`<b>${tTnkq}</b>`;
    row.querySelector('.ov-tot-ds').innerHTML=`<b>${tDs}</b>`;
    row.querySelector('.ov-tot-tl').innerHTML=`<b>${tTl}</b>`;
    row.querySelector('.ov-tot-pts').innerHTML=`<b>${fp(rPts)}đ</b>`;
    gTnkq+=tTnkq;gDs+=tDs;gTl+=tTl;gPts+=rPts;
    gNb+=(tnb*tnkq_per_q+dnb*ds_pt_per_item+lnb*tl_per_q);
    gTh+=(tth*tnkq_per_q+dth*ds_pt_per_item+lth*tl_per_q);
    gVd+=(tvd*tnkq_per_q+dvd*ds_pt_per_item+lvd*tl_per_q);
  });
  const tnkqT=+document.getElementById('cfg_tnkq_num').value,tlT=+document.getElementById('cfg_tl_num').value;
  const badge=(lbl,val,ok)=>`<span class="ov-badge ${ok?'ok':'err'}">${lbl}: <b>${val}</b>${ok?' ✔':' ✘'}</span>`;
  const pNb=gPts>0?Math.round(gNb/gPts*100):0,pTh=gPts>0?Math.round(gTh/gPts*100):0,pVd=100-pNb-pTh;
  document.getElementById('ov_sums').innerHTML =
    badge('TNKQ',gTnkq+'câu',gTnkq===tnkqT)+badge('DS',gDs+'ý',gDs===8)+badge('TL',gTl+'câu',gTl===tlT)+
    badge('Tổng',fp(gPts)+'đ',Math.abs(gPts-10)<.01)+
    `<span class="ov-badge" style="background:#EDE9FE;color:#5B21B6">Biết ${pNb}% · Hiểu ${pTh}% · VD ${pVd}%</span>`;
}
async function applyOverride(){
  if(!lastResult) return;
  const fd=buildFormData('preview',true); const spinner=document.getElementById('gen_spinner'); if(spinner)spinner.style.display='block';
  const res=await fetch(API+'generate.php',{method:'POST',body:fd}); const data=await res.json();
  if(spinner)spinner.style.display='none';
  if(!data.ok){toast(data.error,'err');return;}
  lastResult=data; renderPreview(data.unitData,data.ctx,data.meta); ovChanged();
  switchTab('output',document.querySelectorAll('.mtn-tab-btn')[2]); toast('✔ Đã tính lại!','success');
}
async function resetOverride(){
  if(!lastResult){toast('Hãy tạo ma trận trước.','warn');return;}
  const fd=buildFormData('preview',false); const res=await fetch(API+'generate.php',{method:'POST',body:fd}); const data=await res.json();
  if(!data.ok){toast(data.error,'err');return;}
  lastResult=data; renderOverrideTable(data.unitData); renderPreview(data.unitData,data.ctx,data.meta);
  toast('↺ Đã khôi phục phân bổ tự động.','info');
}

// ── CONFIG VALIDATION ──────────────────────────────────────────────────────
function cfgChanged(){
  const tnkq=+document.getElementById('cfg_tnkq_num').value,ppq=tnkq===8?0.5:0.25,pTNKQ=+(tnkq*ppq).toFixed(2),pTL=+document.getElementById('cfg_tl_pts').value,tot=pTNKQ+2+pTL,ok=Math.abs(tot-10)<.01;
  const el=document.getElementById('cfg_sum'); el.textContent=`TNKQ: ${pTNKQ}đ | DS: 2đ | TL: ${pTL}đ = ${tot}đ ${ok?'✔':'✘'}`; el.className='sum-display '+(ok?'sum-ok':'sum-err');
}
function pctChanged(){
  const nb=+document.getElementById('pct_nb').value,th=+document.getElementById('pct_th').value,vd=+document.getElementById('pct_vd').value,tot=nb+th+vd;
  const el=document.getElementById('pct_check'); el.textContent=`${tot===100?'✔':'✘'} Tổng: ${tot}%`; el.className='pct-total '+(tot===100?'pct-ok':'pct-err');
}

// ── TAB SWITCHING ──────────────────────────────────────────────────────────
function switchTab(name, btn){
  document.querySelectorAll('.mtn-tab').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.mtn-tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active'); if(btn)btn.classList.add('active');
}

// ── TOAST ──────────────────────────────────────────────────────────────────
function toast(msg, type='info'){
  const t=document.getElementById('mtn-toast'); const item=document.createElement('div');
  item.className='toast-item '+type; item.textContent=msg; t.appendChild(item);
  setTimeout(()=>{item.style.opacity='0';item.style.transition='opacity .3s';setTimeout(()=>item.remove(),300);},3200);
}

// ── UTILS ──────────────────────────────────────────────────────────────────
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function fn(arr){return(arr||[]).length?arr.join(', '):'—';}
function fp(n){const v=Math.round(n*100)/100;return Number.isInteger(v)?v.toFixed(1):v.toFixed(2);}
function reqText(muc,lvl){const map={NB:['Nhận biết'],TH:['Thông hiểu'],VD:['Vận dụng','Vận dụng cao']};let out=[];(map[lvl]||[]).forEach(k=>{if(muc[k])out=out.concat(muc[k]);});return out;}
</script>

<?php include '../includes/teacher_footer.php'; ?>
