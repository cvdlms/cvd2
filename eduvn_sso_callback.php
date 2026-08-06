<?php
/**
 * Trình xử lý SSO Callback nhận token đăng nhập từ hệ thống EduVN
 */

// Tải cấu hình SSO
$ssoConfigFile = __DIR__ . '/includes/sso_config.php';
$ssoConfig = file_exists($ssoConfigFile) ? require $ssoConfigFile : [
    'sso_secret' => 'e8d086336d389cbba8c0dc6244ddb9529205589ab0fc9cf6023476a34a2ad3ae',
    'token_ttl'  => 300,
];

$token = $_GET['token'] ?? '';
$error = '';

if (empty($token)) {
    $error = 'Thiếu thông tin xác thực token từ EduVN.';
} else {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        $error = 'Mã token SSO không đúng định dạng.';
    } else {
        list($payloadB64, $signature) = $parts;
        $payloadJson = base64_decode($payloadB64, true);

        if ($payloadJson === false) {
            $error = 'Không thể giải mã dữ liệu token SSO.';
        } else {
            $secret = $ssoConfig['sso_secret'];
            $expectedSignature = hash_hmac('sha256', $payloadJson, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                $error = 'Chữ ký số SSO không hợp lệ (Signature mismatch).';
            } else {
                $payload = json_decode($payloadJson, true);
                if (!is_array($payload) || empty($payload['username']) && empty($payload['email'])) {
                    $error = 'Dữ liệu token SSO không chứa thông tin người dùng.';
                } else {
                    $issuedAt = $payload['issued_at'] ?? 0;
                    $ttl = $ssoConfig['token_ttl'] ?? 300;
                    if (time() - $issuedAt > $ttl) {
                        $error = 'Mã token SSO đã hết hạn. Vui lòng thử lại từ hệ thống EduVN.';
                    } else {
                        // Token hợp lệ! Tiến hành xử lý thông tin người dùng
                        $email       = trim((string)($payload['email'] ?? ''));
                        $username    = trim((string)($payload['username'] ?? ''));
                        $fullName    = trim((string)($payload['full_name'] ?? ''));
                        $role        = trim((string)($payload['role'] ?? 'teacher'));
                        $systemRole  = trim((string)($payload['system_role'] ?? ''));
                        $externalId  = trim((string)($payload['external_user_id'] ?? ''));

                        if (empty($username) && !empty($email)) {
                            $username = explode('@', $email)[0];
                        }
                        // Loại bỏ ký tự đặc biệt trong username
                        $username = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $username);

                        // Xác định vai trò trong CVDLMS
                        if ($systemRole === 'system_owner' || $role === 'admin' || $role === 'principal') {
                            $cvdRole = 'admin';
                        } else {
                            $cvdRole = 'teacher';
                        }

                        // Đọc danh sách user trong CVDLMS
                        $userJsonFile = __DIR__ . '/admin/user.json';
                        $users = file_exists($userJsonFile) ? json_decode(file_get_contents($userJsonFile), true) : [];
                        if (!is_array($users)) {
                            $users = [];
                        }

                        // Tìm user theo username hoặc email trong admin/user.json
                        $targetUserKey = null;
                        $targetUn = strtolower($username);
                        $targetEm = strtolower($email);

                        if ($targetUn !== '' && isset($users[$targetUn])) {
                            $targetUserKey = $targetUn;
                        } else {
                            foreach ($users as $key => $u) {
                                $uKey   = strtolower($key);
                                $uEmail = strtolower($u['email'] ?? '');
                                $uName  = strtolower($u['username'] ?? '');

                                if (!empty($targetEm) && $uEmail === $targetEm) {
                                    $targetUserKey = $key;
                                    break;
                                }
                                if (!empty($targetUn) && ($uName === $targetUn || $uKey === $targetUn)) {
                                    $targetUserKey = $key;
                                    break;
                                }
                                if ($uKey !== '' && ($targetUn !== '' && str_contains($targetUn, $uKey) || $targetEm !== '' && str_contains($targetEm, $uKey))) {
                                    $targetUserKey = $key;
                                    break;
                                }
                            }
                        }

                        // Tài khoản quản trị (admin/principal/system_owner) từ EduVN
                        // luôn được đăng nhập vào tài khoản 'admin' của CVDLMS vì toàn bộ
                        // trang quản trị yêu cầu username chính xác là 'admin'.
                        if ($cvdRole === 'admin') {
                            $targetUserKey = 'admin';
                        }

                        // Nếu người dùng chưa tồn tại trong CVDLMS, tự động tạo mới
                        if (!$targetUserKey) {
                            $targetUserKey = $username;
                            $users[$targetUserKey] = [
                                'fullname' => $fullName ?: $username,
                                'username' => $username,
                                'password' => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                                'email'    => $email,
                                'dob'      => '',
                                'eduvn_id' => $externalId,
                                'created_via_eduvn' => true,
                            ];
                            file_put_contents($userJsonFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        }

                        // Cấp phiên đăng nhập cho CVDLMS
                        if (session_status() === PHP_SESSION_NONE) {
                            session_name('CVD_TEACHER_SESSION');
                            session_start();
                        }

                        session_regenerate_id(true);
                        $_SESSION['username'] = $targetUserKey;
                        $_SESSION['role']     = $cvdRole;
                        $_SESSION['LAST_ACTIVITY'] = time();
                        $_SESSION['eduvn_sso'] = true;

                        // Chuyển hướng tới trang chính tương ứng (sử dụng đường dẫn tuyệt đối từ root)
                        $redirectUrl = ($cvdRole === 'admin') ? '/cvdlms/admin/dashboard.php' : '/cvdlms/teacher/teacher.php';
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EDUVN EXAMS — Đăng nhập liên kết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 16px;
            padding: 40px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
        }
        .icon-box {
            width: 70px;
            height: 70px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box">
            <i class="bi bi-shield-x"></i>
        </div>
        <h4 class="mb-3 font-weight-bold text-dark">Xác thực EduVN Không Thành Công</h4>
        <div class="alert alert-danger mb-4" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="index.php" class="btn btn-primary btn-lg w-100 rounded-3">
            <i class="bi bi-arrow-left"></i> Quay lại trang Đăng nhập
        </a>
    </div>
</body>
</html>
