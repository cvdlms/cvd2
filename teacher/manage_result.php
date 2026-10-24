﻿﻿<?php
include '../includes/session_check.php'; // Ensure logged in

// Check if teacher (not admin)
if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
    header('Location: ../index.php?role=teacher');
    exit;
}

// Phương án (a): rebuild chỉ mục điểm từ file cá nhân mỗi khi mở trang quản lý
require_once __DIR__ . '/../includes/score_index.php';
rebuild_student_score_index();

// Load user data for fullname
$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$username = $_SESSION['username'];
$fullname = $users[$username]['fullname'] ?? $username;

// Load teacher's assigned classes
$teacherClasses = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_classes.json'), true);
$assignedClasses = $teacherClasses[$username] ?? [];

// Load teacher's assigned subjects
$teacherSubjects = json_decode(file_get_contents(__DIR__ . '/../admin/teacher_subjects.json'), true);
$assignedSubjects = $teacherSubjects[$username] ?? [];

// Số bài tự luận đang chờ chấm của giáo viên (liên kết sang trang chấm)
require_once __DIR__ . '/../includes/exam_helper.php';
$pendingEssayCount = exam_pending_essay_count($assignedSubjects);

// Load subjects for display names
$subjects = json_decode(file_get_contents(__DIR__ . '/../admin/subjects.json'), true) ?? [];
$subjectNames = [];
foreach ($subjects as $subject) {
    $subjectNames[$subject['id']] = $subject['name'];
}

$title = 'Xem Học Sinh - CVD';
include '../includes/teacher_header.php';
?>
    <style>
        #studentsTable th {
            white-space: nowrap;
        }
    </style>

    <div class="main-content">
    <div class="container py-4 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="section-header">
                    <div class="sh-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3>Kết quả kiểm tra</h3>
                        <p>Tra cứu điểm kiểm tra của học sinh theo lớp và loại kiểm tra</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-action-custom" id="exportTemplateBtn" title="Xu&#7845;t theo m&#7851;u VnEdu" data-bs-toggle="modal" data-bs-target="#exportTemplateModal">
                            <i class="bi bi-file-earmark-excel"></i> Xu&#7845;t m&#7851;u VnEdu
                        </button>
                        <button type="button" class="btn btn-outline-success btn-action-custom" id="exportBtn" title="Xu&#7845;t Excel th&#244;ng th&#432;&#7901;ng">
                            <i class="bi bi-table"></i> Xu&#7845;t nhanh
                        </button>
                    </div>
                </div>
                <?php if ($pendingEssayCount['bai'] > 0): ?>
                    <a href="grade_essays.php" class="d-flex align-items-center gap-2 text-decoration-none mb-3"
                       style="background:#FFF1F6;border:1px solid #FFC9DC;border-radius:12px;padding:10px 16px;color:#C2255C">
                        <i class="bi bi-pencil-fill fs-5"></i>
                        <span class="flex-grow-1 fw-semibold">
                            Có <?php echo $pendingEssayCount['bai']; ?> bài / <?php echo $pendingEssayCount['cau']; ?> câu Tự luận đang chờ bạn chấm điểm
                        </span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                <?php endif; ?>
                <div class="eduvn-card eduvn-reveal">
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="classFilter" class="form-label">Lọc theo lớp:</label>
                                    <select class="form-select" id="classFilter">
                                        <option value="">Tất cả lớp</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex align-items-center justify-content-between">
                                        <span>Lọc theo loại kiểm tra:</span>
                                        <span class="badge bg-secondary" id="selectedTestTypeBadge" style="display:none!important;font-size:0.7rem"></span>
                                    </label>
                                    <!-- Dropdown multi-select wrapper -->
                                    <div class="dropdown" id="testTypeDropdown">
                                        <button class="form-select text-start d-flex align-items-center justify-content-between"
                                                type="button" id="testTypeDropdownBtn"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                                aria-expanded="false"
                                                style="background-image:none;cursor:pointer">
                                            <span id="testTypeDropdownLabel">Tất cả loại kiểm tra</span>
                                            <i class="bi bi-chevron-down ms-2" style="font-size:0.8rem"></i>
                                        </button>
                                        <div class="dropdown-menu p-2 w-100" id="testTypeMenu" style="max-height:260px;overflow-y:auto;min-width:100%">
                                            <div class="d-flex gap-2 mb-2">
                                                <button class="btn btn-sm btn-outline-primary flex-fill" id="testTypeSelectAll">Chọn tất cả</button>
                                                <button class="btn btn-sm btn-outline-secondary flex-fill" id="testTypeDeselectAll">Bỏ chọn</button>
                                            </div>
                                            <div id="testTypeCheckList"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="searchInput" class="form-label">Tìm kiếm:</label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm theo mã HS hoặc tên...">
                                </div>
                            </div>
                        </div>

                        <table id="studentsTable" class="table table-striped table-bordered eduvn-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã học sinh</th>
                                    <th>Họ và tên</th>
                                    <th>Lớp</th>
                                    <th>Điểm</th>
                                    <th>Kiểm tra</th>
                                    <th>Ngày</th>
                                    <th>Ghi chú</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Modal -->
        <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historyModalLabel">📊 Lịch sử điểm kiểm tra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input to store student code -->
                        <input type="hidden" id="modalStudentCodeInput">



                        <!-- Table Section -->
                        <div id="modalTableSection" style="display: none;">
                            <table id="modalHistoryTable" class="table table-striped table-bordered eduvn-table" width="100%">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên bài kiểm tra</th>
                                        <th>Lần làm</th>
                                        <th>Điểm</th>
                                        <th>Thời gian nộp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <!-- No Data Message -->
                        <div id="modalNoDataMessage" class="alert alert-info" style="display: none;">
                            Không tìm thấy dữ liệu lịch sử cho học sinh này.
                        </div>

                        <!-- Loading Spinner -->
                        <div id="modalLoadingSpinner" class="text-center" style="display: none;">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p>Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Export Template Modal -->
        <div class="modal fade" id="exportTemplateModal" tabindex="-1" aria-labelledby="exportTemplateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportTemplateModalLabel">
                            <i class="bi bi-file-earmark-excel text-success"></i> Xu&#7845;t b&#7843;ng &#273;i&#7875;m theo m&#7851;u VnEdu
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">L&#7899;p <span class="text-danger">*</span></label>
                                <select class="form-select" id="tmplClassSelect">
                                    <option value="">-- Ch&#7885;n l&#7899;p --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">&#272;i&#7873;n &#273;i&#7875;m v&#224;o c&#7897;t <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3 flex-wrap mt-1" id="txColumnPicker">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="txCol" id="txCol1" value="tx1" checked>
                                        <label class="form-check-label fw-bold" for="txCol1">TX1</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="txCol" id="txCol2" value="tx2">
                                        <label class="form-check-label fw-bold" for="txCol2">TX2</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="txCol" id="txCol3" value="tx3">
                                        <label class="form-check-label fw-bold" for="txCol3">TX3</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="txCol" id="txCol4" value="tx4">
                                        <label class="form-check-label fw-bold" for="txCol4">TX4</label>
                                    </div>
                                </div>
                                <div class="form-text">&#272;i&#7875;m cao nh&#7845;t t&#7915; c&#225;c b&#224;i ki&#7875;m tra &#273;&#227; ch&#7885;n s&#7869; &#273;&#432;&#7907;c &#273;i&#7873;n v&#224;o c&#7897;t n&#224;y.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">M&#244;n h&#7885;c</label>
                                <input type="text" class="form-control" id="tmplMon" placeholder="VD: To&#225;n">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">H&#7885;c k&#7923;</label>
                                <select class="form-select" id="tmplHocKy">
                                    <option value="I">H&#7885;c k&#7923; I</option>
                                    <option value="II">H&#7885;c k&#7923; II</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">N&#259;m h&#7885;c</label>
                                <input type="text" class="form-control" id="tmplNamHoc" placeholder="VD: 2025-2026">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">&#272;&#417;n v&#7883; ch&#7911; qu&#7843;n</label>
                                <input type="text" class="form-control" id="tmplDonVi" placeholder="VD: S&#7903; GD&amp;&#272;T TP.HCM">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">T&#234;n tr&#432;&#7901;ng</label>
                                <input type="text" class="form-control" id="tmplTruong" placeholder="VD: Tr&#432;&#7901;ng THPT CVD">
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-light rounded">
                            <strong>&#128203; B&#224;i ki&#7875;m tra s&#7917; d&#7909;ng:</strong>
                            <span id="tmplTestTypeLabel" class="ms-2 text-muted">T&#7845;t c&#7843; lo&#7841;i ki&#7875;m tra</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">H&#7911;y</button>
                        <button type="button" class="btn btn-success" id="tmplExportBtn">
                            <i class="bi bi-download"></i> T&#7843;i xu&#7889;ng Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>
    </div>




    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        const subjectNames = <?php echo json_encode($subjectNames); ?>;
    </script>

    <script>
        let studentsTable;
        let classesData = [];
        let scoresData = [];
        const teacherSubjects = <?php echo json_encode($assignedSubjects); ?>;

        // ── Multi-select test type state ──────────────────────────────────────
        // selectedTestTypes: Set of selected test_name strings. Empty set = "all"
        let selectedTestTypes = new Set();
        let availableTestTypes = []; // ordered list populated after loading scores

        function scoreBelongsToTeacher(score) {
            return teacherSubjects.includes(parseInt(score.subject_id)) || score.subject_id === null || score.subject_id === "";
        }

        // Returns array of selected test-type strings (empty = all)
        function getSelectedTestTypes() {
            return [...selectedTestTypes];
        }

        // Kept for backward compat (export button uses it too)
        function getSelectedTestType() {
            return getSelectedTestTypes().join(',');
        }

        /**
         * Lấy điểm CAO NHẤT của học sinh trong các bài kiểm tra đã chọn.
         * Nếu chọn nhiều bài -> gom điểm từ tất cả, rồi lấy record cao nhất.
         */
        function getBestScoreForStudent(studentCode) {
            const types = getSelectedTestTypes();
            const studentScores = scoresData.filter(score => {
                const sameStudent = String(score.student_id) === String(studentCode);
                const sameSubject = scoreBelongsToTeacher(score);
                const matchType = types.length === 0 || types.includes(score.test_name || '');
                return sameStudent && sameSubject && matchType;
            });

            if (studentScores.length === 0) return null;
            // Lấy điểm cao nhất (so sánh số, fallback 0)
            return studentScores.reduce((best, current) => {
                const bScore = parseFloat(best.score) || 0;
                const cScore = parseFloat(current.score) || 0;
                return cScore > bScore ? current : best;
            });
        }

        // ── Populate multi-select checklist ───────────────────────────────────
        function populateTestTypeFilter(students = []) {
            const studentCodes = new Set(students.map(s => String(s.code)));
            availableTestTypes = [...new Set(scoresData
                .filter(score => scoreBelongsToTeacher(score) && studentCodes.has(String(score.student_id)))
                .map(score => score.test_name || '')
                .filter(Boolean)
            )].sort((a, b) => a.localeCompare(b, 'vi'));

            const checkList = document.getElementById('testTypeCheckList');
            checkList.innerHTML = '';

            availableTestTypes.forEach(testName => {
                const checked = selectedTestTypes.size === 0 ? false : selectedTestTypes.has(testName);
                const id = 'ttc_' + testName.replace(/\W/g, '_');
                const item = document.createElement('div');
                item.className = 'form-check';
                item.innerHTML = `
                    <input class="form-check-input test-type-cb" type="checkbox" value="${testName}" id="${id}" ${checked ? 'checked' : ''}>
                    <label class="form-check-label" for="${id}">${testName}</label>`;
                checkList.appendChild(item);
            });

            updateTestTypeDropdownLabel();
        }

        function updateTestTypeDropdownLabel() {
            const label = document.getElementById('testTypeDropdownLabel');
            const count = selectedTestTypes.size;
            if (count === 0) {
                label.textContent = 'Tất cả loại kiểm tra';
            } else if (count === 1) {
                label.textContent = [...selectedTestTypes][0];
            } else {
                label.textContent = `Đã chọn ${count} loại kiểm tra`;
            }
        }

        async function loadScores() {
            try {
                const scoresResponse = await fetch('../shared/scores/student_score.json?v=' + Date.now());
                if (scoresResponse.ok) {
                    scoresData = await scoresResponse.json();
                } else {
                    console.warn('Student scores file not found, using empty array');
                    scoresData = [];
                }
            } catch (scoreError) {
                console.warn('Error loading student scores:', scoreError);
                scoresData = [];
            }
        }

        // Load classes for dropdowns
        async function loadClasses() {
            try {
                const response = await fetch('api/get_classes.php');
                const result = await response.json();

                if (result.success) {
                    classesData = result.data;

                    // Populate class filters
                    const classFilter = document.getElementById('classFilter');

                    classFilter.innerHTML = '<option value="">Tất cả lớp</option>';

                    classesData.forEach(classItem => {
                        classFilter.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        // Load students table
        async function loadStudents(classFilter = '') {
            try {
                const url = classFilter ? `api/get_students.php?class_id=${classFilter}` : 'api/get_students.php';
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    await loadScores();
                    populateTestTypeFilter(result.data);

                    if (studentsTable) {
                        studentsTable.destroy();
                    }

                    // Custom sort for Vietnamese names (by last name)
                    $.fn.dataTable.ext.type.order['vietnamese-name-asc'] = function (a, b) {
                        const getLastName = (name) => {
                            const words = name.trim().split(' ');
                            return words[words.length - 1].toLowerCase();
                        };
                        const aLast = getLastName(a);
                        const bLast = getLastName(b);
                        return aLast.localeCompare(bLast, 'vi');
                    };

                    // Transform data: lay diem CAO NHAT trong cac bai da chon
                    const tableData = result.data.map((student) => {
                        const bestScore = getBestScoreForStudent(student.code);

                        return {
                            stt: student.stt ?? '-',
                            code: student.code,
                            name: student.name,
                            class_name: student.class_name,
                            score: bestScore ? bestScore.score : '-',
                            max_score: bestScore ? bestScore.max_score : null,
                            test_name: bestScore ? bestScore.test_name : '-',
                            timestamp: bestScore ? new Date(bestScore.timestamp).toLocaleDateString('vi-VN') : '-',
                            notes: bestScore ? (bestScore.notes || '') : '',
                            exam_id: bestScore ? bestScore.exam_id : null
                        };
                    });

                    studentsTable = $('#studentsTable').DataTable({
                        data: tableData,
                        columns: [
                            { data: 'stt' },
                            { data: 'code' },
                            { data: 'name', type: 'vietnamese-name' },
                            { data: 'class_name' },
                            {
                                data: 'score',
                                render: function(data, type, row) {
                                    if (data === '-' || data === null || data === undefined) return '-';
                                    const m = Number(row.max_score) || 10;
                                    return type === 'display' ? `${data}/${m}` : data;
                                }
                            },
                            { data: 'test_name' },
                            { data: 'timestamp' },
                            { 
                                data: 'notes',
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        const maxLength = 50;
                                        const notes = data || '';
                                        if (notes.length > maxLength) {
                                            return '<span title="' + notes.replace(/"/g, '&quot;') + '">' + notes.substring(0, maxLength) + '...</span>';
                                        }
                                        return notes;
                                    }
                                    return data || '';
                                }
                            },
                            {
                                data: null,
                                render: function(data, type, row) {
                                    if (row.score !== '-') {
                                        return `<button type="button" class="btn btn-sm btn-info detail-btn" data-student-code="${row.code}" title="Chi tiết">📊</button>`;
                                    } else {
                                        return '';
                                    }
                                },
                                orderable: false
                            }
                        ],
                        columnDefs: [
                            {
                                targets: [0, 4, 6, 8], // STT, Điểm, Ngày, Hành động
                                className: 'text-center',
                                createdCell: function(td) {
                                    td.style.whiteSpace = 'nowrap';
                                }
                            },
                            {
                                targets: 8, // Cột Hành động
                                width: '80px'
                            }
                        ],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 50,
                        order: [[3, 'asc'], [0, 'asc']]  // Lớp (cột 3), rồi STT trong lớp (cột 0)
                    });
                } else {
                    alert('Không thể tải danh sách học sinh: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading students:', error);
                alert('Lỗi kết nối: ' + error.message);
            }
        }

        // History Modal Functions
        let modalHistoryTable;
        let modalAllData = [];

        // Open History Modal - Make it globally accessible
        window.openHistoryModal = function(studentCode) {
            document.getElementById('modalStudentCodeInput').value = studentCode;
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
            loadModalHistory(studentCode);
        };

        // Load History in Modal
        async function loadModalHistory(studentCode) {
            document.getElementById('modalLoadingSpinner').style.display = 'block';
            document.getElementById('modalTableSection').style.display = 'none';
            document.getElementById('modalNoDataMessage').style.display = 'none';

            try {
                const response = await fetch(`api/get_student_history.php?student_code=${encodeURIComponent(studentCode)}`);
                const result = await response.json();

                if (result.success) {
                    modalAllData = result.data;
                    displayModalData(modalAllData);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading modal history:', error);
                alert('Lỗi kết nối: ' + error.message);
            } finally {
                document.getElementById('modalLoadingSpinner').style.display = 'none';
            }
        }

        // Display Modal Data
        function displayModalData(data) {
            // Filter data to only include teacher's subjects
            const teacherSubjects = <?php echo json_encode($assignedSubjects); ?>;
            const filteredData = data.filter(item => {
                return teacherSubjects.includes(parseInt(item.subject_id));
            });

            if (filteredData.length === 0) {
                document.getElementById('modalNoDataMessage').style.display = 'block';
                document.getElementById('modalTableSection').style.display = 'none';
                return;
            }

            document.getElementById('modalNoDataMessage').style.display = 'none';
            document.getElementById('modalTableSection').style.display = 'block';

            // Initialize or update table
            if (modalHistoryTable) {
                modalHistoryTable.destroy();
            }

            const tableData = filteredData.map((exam, index) => ({
                stt: index + 1,
                test_name: exam.test_name,
                attempt: exam.attempt,
                score: exam.score,
                max_score: exam.max_score,
                timestamp: new Date(exam.timestamp).toLocaleString('vi-VN')
            }));

            modalHistoryTable = $('#modalHistoryTable').DataTable({
                data: tableData,
                columns: [
                    { data: 'stt' },
                    { data: 'test_name' },
                    { data: 'attempt' },
                    {
                        data: 'score',
                        render: function(data, type, row) {
                            if (data === null || data === undefined) return '-';
                            const m = Number(row.max_score) || 10;
                            return type === 'display' ? `${data}/${m}` : data;
                        }
                    },
                    { data: 'timestamp' }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                pageLength: 10,
                order: [[4, 'desc']]  // Sort by timestamp descending (column 4)
            });
        }



        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Event delegation for detail buttons (works for dynamically created buttons)
            $(document).on('click', '.detail-btn', function(e) {
                e.preventDefault();
                const studentCode = $(this).data('student-code');
                if (studentCode) {
                    window.openHistoryModal(studentCode);
                }
            });

            // Class filter
            document.getElementById('classFilter').addEventListener('change', function() {
                loadStudents(this.value);
            });

            // Multi-select: checkbox change
            document.getElementById('testTypeCheckList').addEventListener('change', function(e) {
                if (e.target.classList.contains('test-type-cb')) {
                    if (e.target.checked) {
                        selectedTestTypes.add(e.target.value);
                    } else {
                        selectedTestTypes.delete(e.target.value);
                    }
                    updateTestTypeDropdownLabel();
                    loadStudents(document.getElementById('classFilter').value);
                }
            });

            // Select all
            document.getElementById('testTypeSelectAll').addEventListener('click', function(e) {
                e.stopPropagation();
                availableTestTypes.forEach(t => selectedTestTypes.add(t));
                document.querySelectorAll('.test-type-cb').forEach(cb => cb.checked = true);
                updateTestTypeDropdownLabel();
                loadStudents(document.getElementById('classFilter').value);
            });
            // Deselect all
            document.getElementById('testTypeDeselectAll').addEventListener('click', function(e) {
                e.stopPropagation();
                selectedTestTypes.clear();
                document.querySelectorAll('.test-type-cb').forEach(cb => cb.checked = false);
                updateTestTypeDropdownLabel();
                loadStudents(document.getElementById('classFilter').value);
            });

            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                if (studentsTable) {
                    studentsTable.search(this.value).draw();
                }
            });

            // Export to Excel with sheets by class
            document.getElementById('exportBtn').addEventListener('click', async function() {
                try {
                    const selectedClassId = document.getElementById('classFilter').value;
                    // selectedTestType replaced by multi-select
                    const response = await fetch(selectedClassId ? `api/get_students.php?class_id=${selectedClassId}` : 'api/get_students.php');
                    const result = await response.json();

                    if (result.success && result.data.length > 0) {
                        await loadScores();

                        // Group students by class
                        const groupedByClass = result.data.reduce((acc, student) => {
                            const className = student.class_name;
                            if (!acc[className]) {
                                acc[className] = [];
                            }
                            acc[className].push(student);
                            return acc;
                        }, {});

                        const wb = XLSX.utils.book_new();

                        // Create a sheet for each class
                        Object.keys(groupedByClass).forEach(className => {
                            const students = groupedByClass[className];
                            const wsData = [['STT', 'Mã học sinh', 'Họ và tên', 'Lớp', 'Điểm', 'Thang điểm', 'Kiểm tra', 'Ngày', 'Ghi chú']];

                            students.forEach((student) => {
                                const bestScore = getBestScoreForStudent(student.code);

                                wsData.push([
                                    student.stt ?? '',
                                    student.code || '',
                                    student.name || '',
                                    student.class_name || '',
                                    bestScore ? bestScore.score : '', // Score
                                    bestScore ? (Number(bestScore.max_score) || 10) : '', // Thang điểm của đề
                                    bestScore ? bestScore.test_name : '', // Test name
                                    bestScore ? new Date(bestScore.timestamp).toLocaleDateString('vi-VN') : '',  // Date
                                    bestScore ? (bestScore.notes || '') : ''  // Notes
                                ]);
                            });

                            const ws = XLSX.utils.aoa_to_sheet(wsData);
                            XLSX.utils.book_append_sheet(wb, ws, className);
                        });

                        const types = getSelectedTestTypes(); const suffix = types.length > 0 ? '_' + types.join('-').replace(/[\/\\\\:*?"<>|]/g, '_').substring(0, 60) : '';
                        XLSX.writeFile(wb, `DanhSachHocSinh${suffix}.xlsx`);
                    } else {
                        alert('Không có dữ liệu để xuất.');
                    }
                } catch (error) {
                    console.error('Error exporting:', error);
                    alert('Lỗi khi xuất dữ liệu: ' + error.message);
                }
            });


            // ── Template Export Modal ────────────────────────────────────────
            // Populate class dropdown in modal from classesData
            document.getElementById('exportTemplateBtn').addEventListener('click', function() {
                // Sync class list
                const tmplSel = document.getElementById('tmplClassSelect');
                const curClass = document.getElementById('classFilter').value;
                tmplSel.innerHTML = '<option value="">-- Chọn lớp --</option>';
                classesData.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if (String(c.id) === String(curClass)) opt.selected = true;
                    tmplSel.appendChild(opt);
                });

                // Show current test type selection
                const types = getSelectedTestTypes();
                const label = document.getElementById('tmplTestTypeLabel');
                if (types.length === 0) {
                    label.textContent = 'Tất cả loại kiểm tra';
                    label.className = 'ms-2 text-muted';
                } else {
                    label.textContent = types.join(', ');
                    label.className = 'ms-2 text-success fw-semibold';
                }

                // Auto-fill năm học
                const now = new Date();
                const yr = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1;
                if (!document.getElementById('tmplNamHoc').value) {
                    document.getElementById('tmplNamHoc').value = yr + '-' + (yr + 1);
                }
            });

            // Export template button inside modal
            document.getElementById('tmplExportBtn').addEventListener('click', async function() {
                const classId = document.getElementById('tmplClassSelect').value;
                if (!classId) {
                    alert('Vui lòng chọn lớp!');
                    return;
                }
                const txCol   = document.querySelector('input[name="txCol"]:checked')?.value || 'tx1';
                const mon     = document.getElementById('tmplMon').value.trim();
                const hocKy   = document.getElementById('tmplHocKy').value;
                const namHoc  = document.getElementById('tmplNamHoc').value.trim();
                const donVi   = document.getElementById('tmplDonVi').value.trim();
                const truong  = document.getElementById('tmplTruong').value.trim();
                const types   = getSelectedTestTypes();

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang tạo...';

                try {
                    const resp = await fetch('api/export_template_excel.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            class_id: classId,
                            tx_column: txCol,
                            test_types: types,
                            mon, hoc_ky: hocKy, nam_hoc: namHoc,
                            don_vi: donVi, truong
                        })
                    });

                    if (!resp.ok) {
                        const err = await resp.json().catch(() => ({ message: resp.statusText }));
                        throw new Error(err.message || 'Lỗi server');
                    }

                    // Trigger download
                    const blob = await resp.blob();
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    const cd   = resp.headers.get('Content-Disposition') || '';
                    const fnMatch = cd.match(/filename="([^"]+)"/);
                    a.download = fnMatch ? fnMatch[1] : 'BangDiem.xlsx';
                    a.href = url;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);

                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('exportTemplateModal'))?.hide();
                } catch (e) {
                    alert('Lỗi khi xuất: ' + e.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-download"></i> Tải xuống Excel';
                }
            });
            // Load initial data
            loadClasses().then(() => {
                loadStudents();
            });
        });
    </script>
<?php include '../includes/footer.php'; ?>
