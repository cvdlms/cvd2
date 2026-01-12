<?php
header('Content-Type: application/json');
include '../includes/session_check.php';
include '../includes/premium_helper.php';

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_keys':
            $packageId = (int)($_POST['package_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($quantity < 1 || $quantity > 100) {
                throw new Exception('Số lượng phải từ 1-100');
            }
            
            $keys = generatePremiumKeys($packageId, $quantity);
            echo json_encode(['success' => true, 'message' => "Đã tạo $quantity key", 'keys' => $keys]);
            break;
            
        case 'revoke_key':
            $keyId = $_POST['key_id'] ?? '';
            $keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];
            
            foreach ($keys as &$key) {
                if ($key['key_id'] === $keyId && $key['status'] === 'unused') {
                    $key['status'] = 'revoked';
                    $key['revoked_at'] = date('Y-m-d H:i:s');
                    file_put_contents(PREMIUM_KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo json_encode(['success' => true, 'message' => 'Đã thu hồi key']);
                    exit;
                }
            }
            throw new Exception('Key không tồn tại hoặc đã được sử dụng');
            
        case 'approve_order':
            $orderId = $_POST['order_id'] ?? '';
            $adminNote = $_POST['admin_note'] ?? '';
            $result = approvePremiumOrder($orderId, 'approved', $adminNote);
            echo json_encode($result);
            break;
            
        case 'reject_order':
            $orderId = $_POST['order_id'] ?? '';
            $adminNote = $_POST['admin_note'] ?? 'Admin từ chối';
            $result = approvePremiumOrder($orderId, 'rejected', $adminNote);
            echo json_encode($result);
            break;
            
        case 'extend':
            $username = $_POST['username'] ?? '';
            $days = (int)($_POST['days'] ?? 0);
            $result = extendPremium($username, $days);
            echo json_encode($result);
            break;
            
        case 'revoke':
            $username = $_POST['username'] ?? '';
            $reason = $_POST['reason'] ?? '';
            $result = revokePremium($username, $reason);
            echo json_encode($result);
            break;
            
        case 'update_semester':
            $semester = $_POST['current_semester'] ?? '';
            if (!in_array($semester, ['hk1', 'hk2'])) {
                throw new Exception('Học kì không hợp lệ');
            }
            
            $success = updateCurrentSemester($semester);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật học kì hệ thống']);
            } else {
                throw new Exception('Không thể cập nhật học kì');
            }
            break;
        
        case 'update_premium_config':
            $configFile = __DIR__ . '/system_config.json';
            $config = json_decode(file_get_contents($configFile), true) ?: [];
            
            $premiumEnabled = isset($_POST['premium_enabled']) ? true : false;
            $config['premium']['enabled'] = $premiumEnabled;
            
            $success = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật cấu hình Premium']);
            } else {
                throw new Exception('Không thể lưu cấu hình');
            }
            break;
        
        case 'update_security':
            $configFile = __DIR__ . '/system_config.json';
            $config = json_decode(file_get_contents($configFile), true) ?: [];
            
            $disableViewSource = isset($_POST['disable_view_source']) ? true : false;
            $config['system']['disable_view_source'] = $disableViewSource;
            
            $success = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật cấu hình bảo mật']);
            } else {
                throw new Exception('Không thể lưu cấu hình');
            }
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
