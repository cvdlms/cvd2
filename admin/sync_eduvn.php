<?php
session_name('CVD_TEACHER_SESSION');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../index.php?role=admin');
    exit;
}

require_once __DIR__ . '/../includes/eduvn_sync.php';

$users = json_decode(file_get_contents(__DIR__ . '/../admin/user.json'), true);
$fullname = $users[$_SESSION['username']]['fullname'] ?? 'Giáo Viên';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$messageType = 'success';
$result = null;
$preview = null;

$baseUrl = eduvn_sync_base_url();
$hasCurl = function_exists('curl_init');
$apiKeyOk = eduvn_sync_config('sync_api_key', '') !== '';
$configOk = $hasCurl && $apiKeyOk;

if ($action === 'preview') {
    $res = eduvn_sync_fetch_all();
    if ($res['status'] === 200 && isset($res['decoded'])) {
        $d = $res['decoded'];
        $preview = [
            'classes' => count($d['classes'] ?? []),
            'subjects' => count($d['subjects'] ?? []),
            'students' => count($d['students'] ?? []),
            'teachers' => count($d['teachers'] ?? []),
            'teaching_assignments' => count($d['teaching_assignments'] ?? []),
            'departments' => count($d['departments'] ?? []),
        ];
        $message = 'Kết nối EduVN thành công.';
    } else {
        $messageType = 'danger';
        $message = 'Không kết nối được EduVN (HTTP ' . $res['status'] . '): '
            . ($res['error'] !== '' ? $res['error'] : substr((string)$res['raw'], 0, 200));
    }
}

if ($action === 'do_import') {
    $res = eduvn_sync_fetch_all();
    if ($res['status'] === 200 && isset($res['decoded'])) {
        $report = eduvn_sync_import($res['decoded']);
        $result = $report;
        $message = 'Đã nhập dữ liệu từ EduVN thành công.';
    } else {
        $messageType = 'danger';
        $message = 'Không tải được dữ liệu EduVN (HTTP ' . $res['status'] . '): '
            . ($res['error'] !== '' ? $res['error'] : substr((string)$res['raw'], 0, 200));
    }
}

if ($action === 'push_results') {
    $includeDetails = !empty($_POST['include_details']);
    $res = eduvn_sync_push_results($includeDetails);
    $result = $res['decoded'];
    $message = 'Đã đẩy kết quả thi sang EduVN thành công.';
    if ($res['status'] !== 200) {
        $messageType = 'danger';
        $message = 'Đẩy kết quả thi thất bại (HTTP ' . $res['status'] . '): '
            . ($res['error'] !== '' ? $res['error'] : substr((string)$res['raw'], 0, 200));
    }
}

if ($action === 'push_accounts') {
    $res = eduvn_sync_push_accounts();
    $result = $res['decoded'];
    $message = 'Đã đẩy tài khoản & phân lớp sang EduVN thành công.';
    if ($res['status'] !== 200) {
        $messageType = 'danger';
        $message = 'Đẩy tài khoản thất bại (HTTP ' . $res['status'] . '): '
            . ($res['error'] !== '' ? $res['error'] : substr((string)$res['raw'], 0, 200));
    }
}

$resultCount = count(eduvn_sync_collect_results(false));
$accountPreview = eduvn_sync_collect_accounts();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đồng Bộ EduVN - CVD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../styles/main.css" rel="stylesheet">
    <style>
        .sync-card { border: 0; border-radius: 14px; box-shadow: 0 4px 18px rgba(15, 23, 42, .08); }
        .sync-card .card-header { border-radius: 14px 14px 0 0; }
        .sync-stat { background: #f8fafc; border: 1px solid #e9edf3; border-radius: 10px; padding: .9rem 1rem; }
        .sync-stat .num { font-size: 1.35rem; font-weight: 700; color: #2563eb; }
        .sync-stat .lbl { font-size: .78rem; color: #64748b; margin-top: 2px; }
        .sync-ok { color: #15803d; }
        .sync-bad { color: #b91c1c; }
        .sync-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .sync-badge-ok { background: #dcfce7; color: #166534; }
        .sync-badge-no { background: #fee2e2; color: #991b1b; }
        .sync-code { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: .8rem 1rem; font-size: .78rem; white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body class="admin-page">
  <?php $current_page = 'sync_eduvn.php'; include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="card-title mb-0"><i class="bi bi-arrow-repeat me-2"></i>Đồng Bộ Dữ Liệu EduVN</h2>
                        <span class="badge bg-light text-dark">EDUVN EXAMS ↔ EduVN</span>
                    </div>
                    <div class="card-body">
                        <?php if ($message !== ''): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Cấu hình -->
                        <div class="card sync-card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="bi bi-gear me-1"></i>Cấu hình kết nối</h6>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="sync-stat d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="lbl">URL EduVN</div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($baseUrl); ?></div>
                                            </div>
                                            <i class="bi bi-link-45deg fs-4 text-secondary"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sync-stat d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="lbl">PHP cURL</div>
                                                <span class="sync-badge <?php echo $hasCurl ? 'sync-badge-ok' : 'sync-badge-no'; ?>">
                                                    <?php echo $hasCurl ? 'Sẵn sàng' : 'Chưa bật'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sync-stat d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="lbl">Khóa API</div>
                                                <span class="sync-badge <?php echo $apiKeyOk ? 'sync-badge-ok' : 'sync-badge-no'; ?>">
                                                    <?php echo $apiKeyOk ? 'Đã cấu hình' : 'Thiếu'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!$configOk): ?>
                                    <div class="alert alert-warning mt-3 mb-0 py-2 small">
                                        Cần bật tiện ích cURL của PHP và thiết lập <code>eduvn_base_url</code> / <code>sync_api_key</code> trong
                                        <code>includes/sso_config.php</code>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <ul class="nav nav-tabs" id="syncTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="import-tab" data-bs-toggle="tab" data-bs-target="#import-pane" type="button" role="tab" aria-controls="import-pane" aria-selected="true">
                                    <i class="bi bi-download me-1"></i>Nhập từ EduVN
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="push-tab" data-bs-toggle="tab" data-bs-target="#push-pane" type="button" role="tab" aria-controls="push-pane" aria-selected="false">
                                    <i class="bi bi-upload me-1"></i>Đẩy sang EduVN
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-3">
                            <!-- Import -->
                            <div class="tab-pane fade show active" id="import-pane" role="tabpanel" aria-labelledby="import-tab">
                                <div class="card sync-card">
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">
                                            Kéo dữ liệu từ EduVN (lớp, môn, học sinh, giáo viên, phân công giảng dạy) vào EDUVN EXAMS.
                                            Dữ liệu được gộp theo <code>eduvn_id</code> / mã — các bản ghi đã tồn tại sẽ được cập nhật, không tạo trùng.
                                        </p>
                                        <form method="post" class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="submit" name="action" value="preview" class="btn btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>Xem trước dữ liệu EduVN
                                            </button>
                                            <button type="submit" name="action" value="do_import" class="btn btn-primary"
                                                onclick="return confirm('Nhập dữ liệu từ EduVN vào EDUVN EXAMS? Các bản ghi trùng sẽ được cập nhật.');">
                                                <i class="bi bi-download me-1"></i>Nhập dữ liệu
                                            </button>
                                        </form>

                                        <?php if ($preview !== null): ?>
                                            <div class="row g-2">
                                                <?php foreach ($preview as $label => $count): ?>
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <div class="sync-stat text-center">
                                                            <div class="num"><?php echo (int)$count; ?></div>
                                                            <div class="lbl"><?php echo htmlspecialchars($label); ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Push -->
                            <div class="tab-pane fade" id="push-pane" role="tabpanel" aria-labelledby="push-tab">
                                <div class="card sync-card mb-3">
                                    <div class="card-body">
                                        <h6 class="fw-bold"><i class="bi bi-clipboard-data me-1 text-primary"></i>Kết quả thi / bài kiểm tra</h6>
                                        <p class="text-muted small">
                                            Đẩy kết quả kiểm tra chính thức (<code>shared/scores</code>) và kết quả luyện tập
                                            (<code>practice_results</code>) sang <code>student_scores.json</code> của EduVN.
                                        </p>
                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                            <span class="badge bg-primary"><?php echo (int)$resultCount; ?> bản ghi sẵn sàng</span>
                                            <form method="post" class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="include_details" id="includeDetails" value="1">
                                                    <label class="form-check-label small" for="includeDetails">Kèm chi tiết từng câu hỏi</label>
                                                </div>
                                                <button type="submit" name="action" value="push_results" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-upload me-1"></i>Đẩy kết quả thi
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="card sync-card">
                                    <div class="card-body">
                                        <h6 class="fw-bold"><i class="bi bi-people me-1 text-primary"></i>Tài khoản &amp; phân lớp</h6>
                                        <p class="text-muted small">
                                            Đẩy danh sách học sinh (kèm mật khẩu), giáo viên, lớp, môn và phân công giảng dạy sang EduVN.
                                        </p>
                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                            <span class="badge bg-info text-dark"><?php echo (int)count($accountPreview['students']); ?> học sinh</span>
                                            <span class="badge bg-info text-dark"><?php echo (int)count($accountPreview['teachers']); ?> giáo viên</span>
                                            <span class="badge bg-info text-dark"><?php echo (int)count($accountPreview['teacher_assignments']); ?> phân công</span>
                                            <form method="post" class="d-inline">
                                                <button type="submit" name="action" value="push_accounts" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Đẩy toàn bộ tài khoản và phân lớp sang EduVN?');">
                                                    <i class="bi bi-upload me-1"></i>Đẩy tài khoản &amp; phân lớp
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($result !== null): ?>
                            <div class="mt-4">
                                <h6 class="fw-bold">Kết quả thực hiện:</h6>
                                <div class="sync-code"><?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-4">Được tài trợ bởi <a href="https://psmcvn.com/" target="_blank">PSMCVN</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
