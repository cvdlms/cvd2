<?php
require_once 'session_check.php';
require_once __DIR__ . '/../includes/student_premium_helper.php';

$studentCode = $_SESSION['student_code'];
$studentName = $_SESSION['student_name'];

// Check premium status
$premiumStatus = getStudentPremiumStatus($studentCode);

// Redirect if not premium
if (!$premiumStatus['is_premium']) {
    header('Location: premium.php');
    exit;
}
$title = 'Thống Kê Nâng Cao - EduVN Premium';
include '../includes/student_header.php';
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 12px;
        }
        .subject-progress {
            margin-bottom: 15px;
        }
        .subject-progress .progress {
            border-radius: 99px;
            background: var(--border);
        }
        .progress-bar-gradient {
            border-radius: 99px;
            background: var(--grad-violet);
        }
    </style>

    <div class="std-content">
        <div class="std-masthead">
            <div class="std-page-head" style="margin-bottom:0;">
                <div class="ph-title">
                    <div class="ph-ic" style="background:var(--grad-teal);"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <h1>Thống Kê Chi Tiết</h1>
                        <div class="ph-sub"><?php echo date('d/m/Y'); ?> · Phân tích chi tiết kết quả học tập</div>
                    </div>
                </div>
            </div>
        </div>

    <div class="container mt-4 mb-5">
        <!-- Premium Header -->
        <div class="std-hero mb-4">
            <div class="hero-blob"></div>
            <div class="hero-blob blob2"></div>
            <h2><i class="bi bi-graph-up-arrow me-2"></i> Thống Kê Nâng Cao</h2>
            <p>Phân tích chi tiết kết quả học tập của bạn</p>
            <div class="hero-cta">
                <?php echo getPremiumBadgeHTML($studentCode); ?>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="std-stats mb-4">
            <div class="std-stat c-violet">
                <div class="s-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="s-num" id="totalExams">0</div>
                    <div class="s-label">Tổng số bài thi</div>
                </div>
            </div>
            <div class="std-stat c-teal">
                <div class="s-icon"><i class="bi bi-percent"></i></div>
                <div>
                    <div class="s-num" id="avgScore">0%</div>
                    <div class="s-label">Điểm trung bình</div>
                </div>
            </div>
            <div class="std-stat c-amber">
                <div class="s-icon"><i class="bi bi-trophy"></i></div>
                <div>
                    <div class="s-num" id="bestScore">0%</div>
                    <div class="s-label">Điểm cao nhất</div>
                </div>
            </div>
            <div class="std-stat c-coral">
                <div class="s-icon"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <div class="s-num" id="totalPractice">0</div>
                    <div class="s-label">Lượt luyện tập</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card std-card">
                    <div class="card-body">
                        <div class="std-section-head" style="margin-top: 0;">
                            <h2><i class="bi bi-graph-up me-2"></i>Xu Hướng Điểm Số</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card std-card">
                    <div class="card-body">
                        <div class="std-section-head" style="margin-top: 0;">
                            <h2><i class="bi bi-pie-chart me-2"></i>Phân Bổ Điểm Số</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card std-card">
                    <div class="card-body">
                        <div class="std-section-head" style="margin-top: 0;">
                            <h2><i class="bi bi-bar-chart me-2"></i>Kết Quả Theo Môn Học</h2>
                        </div>
                        <div class="chart-container" style="height: 400px;">
                            <canvas id="subjectChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Progress -->
        <div class="row">
            <div class="col-md-12">
                <div class="card std-card">
                    <div class="card-body">
                        <div class="std-section-head" style="margin-top: 0;">
                            <h2><i class="bi bi-speedometer2 me-2"></i>Tiến Độ Theo Môn</h2>
                        </div>
                        <div id="subjectProgress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const studentCode = '<?php echo $studentCode; ?>';
        let allResults = [];
        let practiceHistory = [];

        // Load data
        async function loadData() {
            try {
                // Load exam results
                const resultsResponse = await fetch(`../api/get_student_results.php?student_code=${studentCode}`);
                const resultsData = await resultsResponse.json();
                allResults = resultsData.results || [];

                // Load practice history
                const practiceResponse = await fetch(`../api/get_practice_history.php?student_code=${studentCode}`);
                const practiceData = await practiceResponse.json();
                practiceHistory = practiceData.history || [];

                displayStatistics();
                renderCharts();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        function displayStatistics() {
            // Total exams
            document.getElementById('totalExams').textContent = allResults.length;

            // Average score
            if (allResults.length > 0) {
                const totalScore = allResults.reduce((sum, r) => sum + (parseFloat(r.score) || 0), 0);
                const avgScore = (totalScore / allResults.length).toFixed(1);
                document.getElementById('avgScore').textContent = avgScore + '%';

                // Best score
                const bestScore = Math.max(...allResults.map(r => parseFloat(r.score) || 0));
                document.getElementById('bestScore').textContent = bestScore.toFixed(1) + '%';
            }

            // Total practice
            document.getElementById('totalPractice').textContent = practiceHistory.length;
        }

        function renderCharts() {
            renderTrendChart();
            renderDistributionChart();
            renderSubjectChart();
            renderSubjectProgress();
        }

        function renderTrendChart() {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            // Sort by timestamp
            const sorted = [...allResults].sort((a, b) => a.timestamp - b.timestamp);
            const labels = sorted.map((r, i) => `Lần ${i + 1}`);
            const scores = sorted.map(r => parseFloat(r.score) || 0);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Điểm số',
                        data: scores,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        function renderDistributionChart() {
            const ctx = document.getElementById('distributionChart').getContext('2d');
            
            // Group scores into ranges
            const ranges = { '0-20%': 0, '21-40%': 0, '41-60%': 0, '61-80%': 0, '81-100%': 0 };
            allResults.forEach(r => {
                const score = parseFloat(r.score) || 0;
                if (score <= 20) ranges['0-20%']++;
                else if (score <= 40) ranges['21-40%']++;
                else if (score <= 60) ranges['41-60%']++;
                else if (score <= 80) ranges['61-80%']++;
                else ranges['81-100%']++;
            });

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(ranges),
                    datasets: [{
                        data: Object.values(ranges),
                        backgroundColor: [
                            '#eb3349',
                            '#f79d65',
                            '#ffd89b',
                            '#38ef7d',
                            '#11998e'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        async function renderSubjectChart() {
            const ctx = document.getElementById('subjectChart').getContext('2d');
            
            // Load subjects
            const subjectsResponse = await fetch('../api/get_subjects.php');
            const subjectsData = await subjectsResponse.json();
            const subjects = {};
            subjectsData.subjects.forEach(s => {
                subjects[s.id] = s.name;
            });

            // Group by subject
            const subjectScores = {};
            allResults.forEach(r => {
                const subjectId = r.subject_id || 'unknown';
                if (!subjectScores[subjectId]) {
                    subjectScores[subjectId] = [];
                }
                subjectScores[subjectId].push(parseFloat(r.score) || 0);
            });

            // Calculate averages
            const labels = [];
            const avgScores = [];
            Object.entries(subjectScores).forEach(([id, scores]) => {
                labels.push(subjects[id] || 'Không xác định');
                avgScores.push((scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1));
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Điểm trung bình',
                        data: avgScores,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: '#667eea',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        async function renderSubjectProgress() {
            const container = document.getElementById('subjectProgress');
            
            // Load subjects
            const subjectsResponse = await fetch('../api/get_subjects.php');
            const subjectsData = await subjectsResponse.json();
            const subjects = {};
            subjectsData.subjects.forEach(s => {
                subjects[s.id] = s.name;
            });

            // Group by subject
            const subjectStats = {};
            allResults.forEach(r => {
                const subjectId = r.subject_id || 'unknown';
                if (!subjectStats[subjectId]) {
                    subjectStats[subjectId] = { count: 0, totalScore: 0 };
                }
                subjectStats[subjectId].count++;
                subjectStats[subjectId].totalScore += parseFloat(r.score) || 0;
            });

            // Render progress bars
            let html = '';
            Object.entries(subjectStats).forEach(([id, stats]) => {
                const avgScore = (stats.totalScore / stats.count).toFixed(1);
                const subjectName = subjects[id] || 'Không xác định';
                
                html += `
                    <div class="subject-progress">
                        <div class="d-flex justify-content-between mb-1">
                            <span><strong>${subjectName}</strong></span>
                            <span>${avgScore}% (${stats.count} bài thi)</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-gradient" 
                                 role="progressbar" 
                                 style="width: ${avgScore}%"
                                 aria-valuenow="${avgScore}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                ${avgScore}%
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html || '<p class="text-muted">Chưa có dữ liệu</p>';
        }

        // Load data on page load
        loadData();
    </script>

    </div><!-- /.container -->
    </div><!-- /.std-content -->
<?php include '../includes/student_footer.php'; ?>