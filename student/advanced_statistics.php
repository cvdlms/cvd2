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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Nâng Cao - CVD Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .premium-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 30px;
        }
        .trend-up {
            color: #38ef7d;
        }
        .trend-down {
            color: #f45c43;
        }
        .subject-progress {
            margin-bottom: 15px;
        }
        .progress-bar-gradient {
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Premium Header -->
        <div class="card premium-gradient mb-4">
            <div class="card-body text-center py-4">
                <h2><i class="bi bi-graph-up-arrow me-2"></i>Thống Kê Nâng Cao</h2>
                <p class="mb-0">Phân tích chi tiết kết quả học tập của bạn</p>
                <?php echo getPremiumBadgeHTML($studentCode); ?>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text display-4 text-primary"></i>
                        <h3 id="totalExams" class="mt-2">0</h3>
                        <p class="text-muted mb-0">Tổng số bài thi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-percent display-4 text-success"></i>
                        <h3 id="avgScore" class="mt-2">0%</h3>
                        <p class="text-muted mb-0">Điểm trung bình</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-trophy display-4 text-warning"></i>
                        <h3 id="bestScore" class="mt-2">0%</h3>
                        <p class="text-muted mb-0">Điểm cao nhất</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-pencil-square display-4 text-info"></i>
                        <h3 id="totalPractice" class="mt-2">0</h3>
                        <p class="text-muted mb-0">Lượt luyện tập</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-up me-2"></i>Xu Hướng Điểm Số</h5>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-pie-chart me-2"></i>Phân Bổ Điểm Số</h5>
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
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-bar-chart me-2"></i>Kết Quả Theo Môn Học</h5>
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
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-speedometer2 me-2"></i>Tiến Độ Theo Môn</h5>
                        <div id="subjectProgress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
</body>
</html>