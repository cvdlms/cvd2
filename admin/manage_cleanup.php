<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Làm Sạch Dữ Liệu - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="../styles/main.css" rel="stylesheet">
    <style>
        .stats-card {
            border-left: 4px solid;
        }
        .stats-card.exams {
            border-color: #0d6efd;
        }
        .stats-card.results {
            border-color: #198754;
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 8px;
            background-color: #fff5f5;
        }
        /* Fix DataTables width issues */
        #examsTable, #resultsTable {
            width: 100% !important;
            table-layout: fixed !important;
        }
        .dataTables_wrapper {
            width: 100% !important;
        }
        .dataTables_scroll {
            width: 100% !important;
        }
        .dataTables_scrollHead,
        .dataTables_scrollBody {
            width: 100% !important;
        }
        .dataTables_scrollHeadInner,
        .dataTables_scrollBody table {
            width: 100% !important;
        }
        table.dataTable thead th,
        table.dataTable tbody td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Specific column widths for exams table */
        #examsTable th:nth-child(1),
        #examsTable td:nth-child(1) { width: 40px !important; }
        #examsTable th:nth-child(2),
        #examsTable td:nth-child(2) { width: 25% !important; }
        #examsTable th:nth-child(3),
        #examsTable td:nth-child(3) { width: 15% !important; }
        #examsTable th:nth-child(4),
        #examsTable td:nth-child(4) { width: 10% !important; }
        #examsTable th:nth-child(5),
        #examsTable td:nth-child(5) { width: 10% !important; }
        #examsTable th:nth-child(6),
        #examsTable td:nth-child(6) { width: 15% !important; }
        #examsTable th:nth-child(7),
        #examsTable td:nth-child(7) { width: 15% !important; }
        /* Specific column widths for results table */
        #resultsTable th:nth-child(1),
        #resultsTable td:nth-child(1) { width: 40px !important; }
        #resultsTable th:nth-child(2),
        #resultsTable td:nth-child(2) { width: 12% !important; }
        #resultsTable th:nth-child(3),
        #resultsTable td:nth-child(3) { width: 20% !important; }
        #resultsTable th:nth-child(4),
        #resultsTable td:nth-child(4) { width: 25% !important; }
        #resultsTable th:nth-child(5),
        #resultsTable td:nth-child(5) { width: 10% !important; }
        #resultsTable th:nth-child(6),
        #resultsTable td:nth-child(6) { width: 18% !important; }
        #resultsTable th:nth-child(7),
        #resultsTable td:nth-child(7) { width: 10% !important; }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'manage_cleanup.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="card-title mb-0">🧹 Làm Sạch Dữ Liệu Hệ Thống</h2>
                        <p class="mb-0 small">Quản lý và xóa bài kiểm tra cũ, kết quả học sinh để tối ưu hóa dữ liệu</p>
                    </div>
                    <div class="card-body">
                        <!-- Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card stats-card exams">
                                    <div class="card-body">
                                        <h5 class="card-title">📝 Bài Kiểm Tra</h5>
                                        <h2 class="mb-0" id="totalExams">0</h2>
                                        <small class="text-muted">Tổng số bài thi trong hệ thống</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card stats-card results">
                                    <div class="card-body">
                                        <h5 class="card-title">📊 Kết Quả Thi</h5>
                                        <h2 class="mb-0" id="totalResults">0</h2>
                                        <small class="text-muted">Tổng số kết quả đã lưu</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs mb-4" id="cleanupTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams" type="button">
                                    📝 Bài Kiểm Tra
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button">
                                    📊 Kết Quả Thi
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="cleanupTabContent">
                            <!-- Exams Tab -->
                            <div class="tab-pane fade show active" id="exams" role="tabpanel">
                                <div class="mb-3">
                                    <label for="gradeFilter" class="form-label">Lọc theo khối:</label>
                                    <select class="form-select" id="gradeFilter" style="max-width: 200px;">
                                        <option value="">Tất cả khối</option>
                                    </select>
                                </div>
                                <table id="examsTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAllExams"></th>
                                            <th>Tên Bài Thi</th>
                                            <th>Môn Học</th>
                                            <th>Khối</th>
                                            <th>Số Câu</th>
                                            <th>Ngày Tạo</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                                <div class="danger-zone p-3 mt-3">
                                    <h5 class="text-danger">⚠️ Vùng Nguy Hiểm</h5>
                                    <p>Xóa các bài kiểm tra đã chọn. <strong>Hành động này không thể hoàn tác!</strong></p>
                                    <button class="btn btn-danger" id="deleteSelectedExams" disabled>
                                        🗑️ Xóa Bài Thi Đã Chọn (<span id="selectedExamsCount">0</span>)
                                    </button>
                                </div>
                            </div>

                            <!-- Results Tab -->
                            <div class="tab-pane fade" id="results" role="tabpanel">
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="studentFilter" class="form-label">Lọc theo học sinh:</label>
                                            <select class="form-select" id="studentFilter">
                                                <option value="">Tất cả học sinh</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="examFilter" class="form-label">Lọc theo bài thi:</label>
                                            <select class="form-select" id="examFilter">
                                                <option value="">Tất cả bài thi</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <table id="resultsTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAllResults"></th>
                                            <th>Mã HS</th>
                                            <th>Tên HS</th>
                                            <th>Bài Thi</th>
                                            <th>Điểm</th>
                                            <th>Ngày Thi</th>
                                            <th>Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                                <div class="danger-zone p-3 mt-3">
                                    <h5 class="text-danger">⚠️ Vùng Nguy Hiểm</h5>
                                    <p>Xóa các kết quả thi đã chọn. <strong>Hành động này không thể hoàn tác!</strong></p>
                                    <button class="btn btn-danger" id="deleteSelectedResults" disabled>
                                        🗑️ Xóa Kết Quả Đã Chọn (<span id="selectedResultsCount">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let examsData = [];
        let resultsData = [];
        let subjects = {};
        
        // Load all data
        async function loadData() {
            try {
                // Load subjects
                const subjectsRes = await fetch('../admin/subjects.json');
                const subjectsArray = await subjectsRes.json();
                subjectsArray.forEach(s => {
                    subjects[s.id] = s.name;
                });

                // Load exams
                const examsRes = await fetch('api/get_all_exams.php');
                examsData = await examsRes.json();
                $('#totalExams').text(examsData.length);

                // Load results
                const resultsRes = await fetch('api/get_all_results.php');
                resultsData = await resultsRes.json();
                $('#totalResults').text(resultsData.length);

                // Populate filters
                populateFilters();
                
                // Initialize tables
                initExamsTable();
                initResultsTable();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        function populateFilters() {
            // Grade filter
            const grades = [...new Set(examsData.map(e => e.grade))].sort();
            grades.forEach(grade => {
                $('#gradeFilter').append(`<option value="${grade}">${grade}</option>`);
            });

            // Student filter
            const students = [...new Set(resultsData.map(r => ({
                code: r.student_id,
                name: r.student_name
            })))];
            students.sort((a, b) => a.code.localeCompare(b.code));
            students.forEach(student => {
                $('#studentFilter').append(`<option value="${student.code}">${student.code} - ${student.name}</option>`);
            });

            // Exam filter for results
            const examNames = [...new Set(resultsData.map(r => r.exam_name))].sort();
            examNames.forEach(name => {
                $('#examFilter').append(`<option value="${name}">${name}</option>`);
            });
        }

        function initExamsTable() {
            const table = $('#examsTable').DataTable({
                data: examsData,
                columns: [
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="exam-checkbox" data-path="${row.file_path}">`;
                        },
                        orderable: false,
                        width: '40px'
                    },
                    { data: 'test_name', width: '25%' },
                    { 
                        data: 'subject_id',
                        render: function(data) {
                            return subjects[data] || 'N/A';
                        },
                        width: '15%'
                    },
                    { data: 'grade', width: '10%' },
                    { data: 'question_count', width: '10%' },
                    { 
                        data: 'created_date',
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString('vi-VN') : 'N/A';
                        },
                        width: '15%'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-danger delete-exam" data-path="${row.file_path}">🗑️ Xóa</button>`;
                        },
                        orderable: false,
                        width: '15%'
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                scrollX: false,
                autoWidth: false,
                columnDefs: [
                    { targets: '_all', className: 'text-nowrap' }
                ]
            });

            // Handle grade filter
            $('#gradeFilter').on('change', function() {
                table.column(3).search(this.value).draw();
            });
        }

        function initResultsTable() {
            const table = $('#resultsTable').DataTable({
                data: resultsData,
                columns: [
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="result-checkbox" data-id="${row.id}" data-student="${row.student_id}">`;
                        },
                        orderable: false,
                        width: '40px'
                    },
                    { data: 'student_id', width: '12%' },
                    { data: 'student_name', width: '20%' },
                    { data: 'exam_name', width: '25%' },
                    { 
                        data: 'score',
                        render: function(data) {
                            return data !== null ? data.toFixed(2) : 'N/A';
                        },
                        width: '10%'
                    },
                    { 
                        data: 'submitted_at',
                        render: function(data) {
                            return data ? new Date(data).toLocaleString('vi-VN') : 'N/A';
                        },
                        width: '18%'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-danger delete-result" data-id="${row.id}" data-student="${row.student_id}">🗑️ Xóa</button>`;
                        },
                        orderable: false,
                        width: '10%'
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                scrollX: false,
                autoWidth: false,
                columnDefs: [
                    { targets: '_all', className: 'text-nowrap' }
                ]
            });

            // Handle filters
            $('#studentFilter').on('change', function() {
                table.column(1).search(this.value).draw();
            });

            $('#examFilter').on('change', function() {
                table.column(3).search(this.value).draw();
            });
        }

        // Select all exams
        $('#selectAllExams').on('change', function() {
            $('.exam-checkbox').prop('checked', this.checked);
            updateSelectedCount('exams');
        });

        // Select all results
        $('#selectAllResults').on('change', function() {
            $('.result-checkbox').prop('checked', this.checked);
            updateSelectedCount('results');
        });

        // Update count when individual checkbox changes
        $(document).on('change', '.exam-checkbox', function() {
            updateSelectedCount('exams');
        });

        $(document).on('change', '.result-checkbox', function() {
            updateSelectedCount('results');
        });

        function updateSelectedCount(type) {
            if (type === 'exams') {
                const count = $('.exam-checkbox:checked').length;
                $('#selectedExamsCount').text(count);
                $('#deleteSelectedExams').prop('disabled', count === 0);
            } else {
                const count = $('.result-checkbox:checked').length;
                $('#selectedResultsCount').text(count);
                $('#deleteSelectedResults').prop('disabled', count === 0);
            }
        }

        // Delete single exam
        $(document).on('click', '.delete-exam', function() {
            const path = $(this).data('path');
            if (confirm('Bạn có chắc muốn xóa bài thi này?')) {
                deleteExams([path]);
            }
        });

        // Delete selected exams
        $('#deleteSelectedExams').on('click', function() {
            const paths = $('.exam-checkbox:checked').map(function() {
                return $(this).data('path');
            }).get();

            if (paths.length === 0) return;

            if (confirm(`Bạn có chắc muốn xóa ${paths.length} bài thi đã chọn? Hành động này không thể hoàn tác!`)) {
                deleteExams(paths);
            }
        });

        async function deleteExams(paths) {
            try {
                const response = await fetch('api/delete_exams.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paths })
                });

                const result = await response.json();
                if (result.success) {
                    alert(`Đã xóa thành công ${result.deleted} bài thi!`);
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + result.message);
                }
            } catch (error) {
                alert('Lỗi khi xóa bài thi: ' + error.message);
            }
        }

        // Delete single result
        $(document).on('click', '.delete-result', function() {
            const id = $(this).data('id');
            const studentId = $(this).data('student');
            if (confirm('Bạn có chắc muốn xóa kết quả này?')) {
                deleteResults([{ id, student_id: studentId }]);
            }
        });

        // Delete selected results
        $('#deleteSelectedResults').on('click', function() {
            const results = $('.result-checkbox:checked').map(function() {
                return {
                    id: $(this).data('id'),
                    student_id: $(this).data('student')
                };
            }).get();

            if (results.length === 0) return;

            if (confirm(`Bạn có chắc muốn xóa ${results.length} kết quả đã chọn? Hành động này không thể hoàn tác!`)) {
                deleteResults(results);
            }
        });

        async function deleteResults(results) {
            try {
                const response = await fetch('api/delete_results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ results })
                });

                const result = await response.json();
                if (result.success) {
                    alert(`Đã xóa thành công ${result.deleted} kết quả!`);
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + result.message);
                }
            } catch (error) {
                alert('Lỗi khi xóa kết quả: ' + error.message);
            }
        }

        // Initialize on page load
        $(document).ready(function() {
            loadData();
        });
    </script>
</body>
</html>
