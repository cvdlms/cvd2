<?php
session_name('CVD_STUDENT_SESSION');
session_start();
if (!isset($_SESSION['student_code'])) {
    header('Location: ../index.php?role=student');
    exit;
}

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];
$studentClass = $_SESSION['student_class'] ?? '';
$studentClassCode = $_SESSION['student_class_code'] ?? '';

$title = 'Kết Quả Thi - EduVN';
include '../includes/student_header.php';
?>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)'],],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                packages: {'[+]': ['mhchem']}
            },
            loader: {
                load: ['[tex]/mhchem']
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js"></script>

    <div class="std-content">
    <div class="container mt-4">
        <header class="std-masthead">
            <div class="std-page-head" style="margin-bottom:0;">
                <div class="ph-title">
                    <div class="ph-ic" style="background:var(--grad-violet);"><i class="bi bi-bar-chart-fill"></i></div>
                    <div>
                        <h1>Kết Quả Học Tập</h1>
                        <div class="ph-sub"><?php echo date('d/m/Y'); ?> · Lịch sử các bài thi và thống kê điểm số</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="std-stats mb-4">
            <div class="std-stat c-violet">
                <div class="s-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                <div>
                    <div class="s-num" id="totalExams">-</div>
                    <div class="s-label">Tổng bài thi</div>
                </div>
            </div>
            <div class="std-stat c-teal">
                <div class="s-icon"><i class="bi bi-percent"></i></div>
                <div>
                    <div class="s-num" id="averageScore">-</div>
                    <div class="s-label">Điểm trung bình</div>
                </div>
            </div>
            <div class="std-stat c-amber">
                <div class="s-icon"><i class="bi bi-trophy-fill"></i></div>
                <div>
                    <div class="s-num" id="highestScore">-</div>
                    <div class="s-label">Điểm cao nhất</div>
                </div>
            </div>
            <div class="std-stat c-coral">
                <div class="s-icon"><i class="bi bi-patch-check-fill"></i></div>
                <div>
                    <div class="s-num" id="passRate">-</div>
                    <div class="s-label">Tỷ lệ đỗ</div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card std-card">
            <div class="card-header">
                <h5 class="mb-0">📊 Lịch Sử Bài Thi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table id="resultsTable" class="table std-table">
                    <thead>
                        <tr>
                            <th>Loại Thi</th>
                            <th>Lần Thi</th>
                            <th>Điểm</th>
                            <th>Xếp Loại</th>
                            <th>Thời Gian</th>
                            <th>Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Detail Modal -->
    <div class="modal fade" id="examDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Bài Thi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="examDetailContent">
                        <!-- Exam details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn std-btn std-violet btn-sm" onclick="printExamDetail()">In Chi Tiết</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let resultsTable;
        let allResults = [];

        // Load student results
        async function loadResults() {
            try {
                const response = await fetch('api/get_student_results.php');
                const data = await response.json();

                if (data.success) {
                    allResults = data.results;
                    displayStatistics();
                    displayResultsTable();
                } else {
                    document.querySelector('#resultsTable tbody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-muted">Chưa có kết quả thi nào.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading results:', error);
                alert('Lỗi tải kết quả: ' + error.message);
            }
        }

        // Display statistics
        function displayStatistics() {
            const totalExams = allResults.length;

            if (totalExams === 0) {
                document.getElementById('totalExams').textContent = '0';
                document.getElementById('averageScore').textContent = '-';
                document.getElementById('highestScore').textContent = '-';
                document.getElementById('passRate').textContent = '-';
                return;
            }

            // Calculate statistics
            let totalScore = 0;
            let highestScore = 0;
            let passedExams = 0;

            allResults.forEach(result => {
                if (result.score !== null) {
                    totalScore += result.score;
                    if (result.score > highestScore) highestScore = result.score;
                    if (result.score >= 5.0) passedExams++;
                }
            });

            const averageScore = (totalScore / totalExams).toFixed(1);
            const passRate = ((passedExams / totalExams) * 100).toFixed(1) + '%';

            document.getElementById('totalExams').textContent = totalExams;
            document.getElementById('averageScore').textContent = averageScore;
            document.getElementById('highestScore').textContent = highestScore.toFixed(1);
            document.getElementById('passRate').textContent = passRate;
        }

        // Display results table
        function displayResultsTable() {
            if (resultsTable) {
                resultsTable.destroy();
            }

            resultsTable = $('#resultsTable').DataTable({
                data: allResults,
                columns: [
                    {
                        data: null,
                        render: function(data) {
                            return data.test_name || data.exam_type;
                        }
                    },
                    { data: 'attempt' },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="text-muted">Chưa hoàn thành</span>';
                            return `<strong>${data}</strong>`;
                        }
                    },
                    {
                        data: 'score',
                        render: function(data) {
                            if (data === null) return '<span class="badge bg-secondary">Chưa hoàn thành</span>';

                            let grade = 'F';
                            let badgeClass = 'grade-low';

                            if (data >= 9.0) { grade = 'A+'; badgeClass = 'grade-good'; }
                            else if (data >= 8.5) { grade = 'A'; badgeClass = 'grade-good'; }
                            else if (data >= 8.0) { grade = 'B+'; badgeClass = 'grade-good'; }
                            else if (data >= 7.0) { grade = 'B'; badgeClass = 'grade-mid'; }
                            else if (data >= 6.5) { grade = 'C+'; badgeClass = 'grade-mid'; }
                            else if (data >= 6.0) { grade = 'C'; badgeClass = 'grade-mid'; }
                            else if (data >= 5.5) { grade = 'D+'; badgeClass = 'grade-mid'; }
                            else if (data >= 5.0) { grade = 'D'; badgeClass = 'grade-mid'; }

                            return `<span class="badge grade-badge ${badgeClass}">${grade}</span>`;
                        }
                    },
                    {
                        data: 'timestamp',
                        render: function(data) {
                            return new Date(data).toLocaleString('vi-VN');
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            if (!data.completed) {
                                return '<span class="text-muted">Chưa hoàn thành</span>';
                            }
                            return `<button class="btn std-btn std-ghost btn-sm" onclick="viewExamDetail('${data.id}')">👁️ Xem</button>`;
                        },
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
                },
                responsive: true,
                order: [[4, 'desc']], // Sort by timestamp descending
                pageLength: 10
            });
        }

        // View exam detail
        async function viewExamDetail(examId) {
            try {
                const response = await fetch(`api/get_exam_result.php?exam_id=${examId}`);
                const data = await response.json();

                if (data.success) {
                    const result = data.result;
                    const modal = new bootstrap.Modal(document.getElementById('examDetailModal'));
                    const content = document.getElementById('examDetailContent');

                    content.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Loại thi:</strong> ${result.test_name || result.exam_type}
                            </div>
                            <div class="col-md-6">
                                <strong>Lần thi:</strong> ${result.attempt}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Điểm số:</strong> <span class="h4 text-primary">${result.score}/10</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Số câu đúng:</strong> ${result.correct_answers}/${result.total_questions}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Thời gian:</strong> ${new Date(result.timestamp).toLocaleString('vi-VN')}
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong> <span class="badge bg-success">Hoàn thành</span>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Chi Tiết Bài Làm</h5>
                        <div class="accordion" id="questionsAccordion">
                            ${result.question_results.map((q, index) => `
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ${q.is_correct ? '' : 'bg-danger text-white'}" type="button" data-bs-toggle="collapse" data-bs-target="#question${index}">
                                            Câu ${index + 1}: ${q.is_correct ? '✅ Đúng' : '❌ Sai'}
                                        </button>
                                    </h2>
                                    <div id="question${index}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Câu hỏi:</strong> ${q.question}</p>
                                            <p><strong>Đáp án đúng:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.correct_answer)
                                                    : q.correct_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>
                                            ${q.user_answer !== null ? `<p><strong>Đáp án của bạn:</strong> ${
                                                q.type === 'single'
                                                    ? String.fromCharCode(65 + q.user_answer)
                                                    : q.user_answer.map(i => String.fromCharCode(65 + i)).join(', ')
                                            }</p>` : '<p><strong>Đáp án của bạn:</strong> <em>Chưa trả lời</em></p>'}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    modal.show();
                } else {
                    alert('Không thể tải chi tiết bài thi');
                }
            } catch (error) {
                console.error('Error loading exam detail:', error);
                alert('Lỗi tải chi tiết bài thi: ' + error.message);
            }
        }

        // Print exam detail
        function printExamDetail() {
            const content = document.getElementById('examDetailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chi Tiết Bài Thi</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .accordion-item { margin-bottom: 10px; border: 1px solid #ddd; }
                        .accordion-button { background: #f8f9fa; border: none; padding: 10px; width: 100%; text-align: left; }
                        .accordion-body { padding: 10px; }
                        .badge { padding: 2px 6px; border-radius: 3px; }
                        .bg-success { background: #28a745; color: white; }
                        .text-primary { color: #007bff; }
                        .h4 { font-size: 1.5rem; font-weight: bold; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Load results on page load
        document.addEventListener('DOMContentLoaded', loadResults);

        // Render MathJax after modal content is loaded
        document.getElementById('examDetailModal').addEventListener('shown.bs.modal', function() {
            setTimeout(function() {
                if (window.MathJax && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(function(err) {
                        console.log('MathJax error:', err);
                    });
                }
            }, 100);
        });
    </script>

    </div><!-- /.std-content -->
<?php include '../includes/student_footer.php'; ?>
