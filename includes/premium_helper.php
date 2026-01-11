<?php
/**
 * Premium Helper Functions
 * Các hàm hỗ trợ quản lý Premium cho hệ thống CVD
 */

// Đường dẫn đến các file dữ liệu
define('PREMIUM_PACKAGES_FILE', __DIR__ . '/../admin/premium_packages.json');
define('PREMIUM_KEYS_FILE', __DIR__ . '/../admin/premium_keys.json');
define('PREMIUM_SUBSCRIPTIONS_FILE', __DIR__ . '/../admin/premium_subscriptions.json');
define('PREMIUM_ORDERS_FILE', __DIR__ . '/../admin/premium_orders.json');
define('SYSTEM_CONFIG_FILE', __DIR__ . '/../admin/system_config.json');

/**
 * Kiểm tra xem giáo viên có Premium không
 * @param string $username Username của giáo viên
 * @return bool
 */
function isPremiumUser($username) {
    $subscription = getActiveSubscription($username);
    return $subscription !== null;
}

/**
 * Lấy thông tin subscription đang active của giáo viên
 * @param string $username Username của giáo viên
 * @return array|null Thông tin subscription hoặc null nếu không có
 */
function getActiveSubscription($username) {
    if (!file_exists(PREMIUM_SUBSCRIPTIONS_FILE)) {
        return null;
    }
    
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $now = time();
    
    foreach ($subscriptions as $sub) {
        if ($sub['username'] === $username && 
            $sub['status'] === 'active' && 
            strtotime($sub['end_date']) > $now) {
            return $sub;
        }
    }
    
    return null;
}

/**
 * Lấy số ngày còn lại của Premium
 * @param string $username Username của giáo viên
 * @return int Số ngày còn lại, -1 nếu không có Premium
 */
function getPremiumDaysRemaining($username) {
    $subscription = getActiveSubscription($username);
    
    if ($subscription === null) {
        return -1;
    }
    
    $endDate = strtotime($subscription['end_date']);
    $now = time();
    $daysRemaining = ceil(($endDate - $now) / (60 * 60 * 24));
    
    return max(0, $daysRemaining);
}

/**
 * Kiểm tra key Premium có hợp lệ không
 * @param string $keyCode Mã key
 * @return array|false Thông tin key hoặc false nếu không hợp lệ
 */
function validatePremiumKey($keyCode) {
    if (!file_exists(PREMIUM_KEYS_FILE)) {
        return false;
    }
    
    $keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];
    
    foreach ($keys as $key) {
        if ($key['key_code'] === $keyCode && $key['status'] === 'unused') {
            return $key;
        }
    }
    
    return false;
}

/**
 * Kích hoạt Premium bằng key
 * @param string $username Username của giáo viên
 * @param string $keyCode Mã key
 * @return array Kết quả ['success' => bool, 'message' => string]
 */
function activatePremiumByKey($username, $keyCode) {
    // Validate key
    $key = validatePremiumKey($keyCode);
    if (!$key) {
        return ['success' => false, 'message' => 'Key không hợp lệ hoặc đã được sử dụng'];
    }
    
    // Lấy thông tin package
    $packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];
    $package = null;
    foreach ($packages as $p) {
        if ($p['package_id'] == $key['package_id']) {
            $package = $p;
            break;
        }
    }
    
    if (!$package) {
        return ['success' => false, 'message' => 'Gói Premium không tồn tại'];
    }
    
    // Tạo subscription mới
    $startDate = date('Y-m-d H:i:s');
    $endDate = date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"));
    
    $subscription = [
        'subscription_id' => uniqid('sub_'),
        'username' => $username,
        'package_id' => $package['package_id'],
        'package_name' => $package['name'],
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => 'active',
        'activated_by' => 'key',
        'key_used' => $keyCode,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Lưu subscription
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $subscriptions[] = $subscription;
    file_put_contents(PREMIUM_SUBSCRIPTIONS_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Cập nhật trạng thái key
    $keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];
    foreach ($keys as &$k) {
        if ($k['key_code'] === $keyCode) {
            $k['status'] = 'used';
            $k['used_by'] = $username;
            $k['used_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents(PREMIUM_KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log activity
    logPremiumActivity($username, 'activate', "Kích hoạt Premium bằng key: $keyCode");
    
    return ['success' => true, 'message' => 'Kích hoạt Premium thành công', 'subscription' => $subscription];
}

/**
 * Tạo đơn đăng ký Premium (chờ admin duyệt)
 * @param array $data Dữ liệu đơn đăng ký
 * @return array Kết quả
 */
function createPremiumOrder($data) {
    $order = [
        'order_id' => uniqid('order_'),
        'username' => $data['username'],
        'fullname' => $data['fullname'],
        'email' => $data['email'],
        'package_id' => $data['package_id'],
        'package_name' => $data['package_name'],
        'price' => $data['price'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'notes' => $data['notes'] ?? ''
    ];
    
    $orders = json_decode(file_get_contents(PREMIUM_ORDERS_FILE), true) ?: [];
    $orders[] = $order;
    file_put_contents(PREMIUM_ORDERS_FILE, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    logPremiumActivity($data['username'], 'order', "Tạo đơn đăng ký Premium: {$data['package_name']}");
    
    return ['success' => true, 'message' => 'Đơn đăng ký đã được gửi, vui lòng chờ admin duyệt', 'order' => $order];
}

/**
 * Duyệt đơn đăng ký Premium (Admin)
 * @param string $orderId ID đơn hàng
 * @param string $status 'approved' hoặc 'rejected'
 * @param string $adminNote Ghi chú của admin
 * @return array Kết quả
 */
function approvePremiumOrder($orderId, $status, $adminNote = '') {
    $orders = json_decode(file_get_contents(PREMIUM_ORDERS_FILE), true) ?: [];
    $orderIndex = null;
    $order = null;
    
    foreach ($orders as $idx => $o) {
        if ($o['order_id'] === $orderId) {
            $orderIndex = $idx;
            $order = $o;
            break;
        }
    }
    
    if (!$order) {
        return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
    }
    
    $orders[$orderIndex]['status'] = $status;
    $orders[$orderIndex]['admin_note'] = $adminNote;
    $orders[$orderIndex]['processed_at'] = date('Y-m-d H:i:s');
    
    // Nếu duyệt, tạo subscription
    if ($status === 'approved') {
        $packages = json_decode(file_get_contents(PREMIUM_PACKAGES_FILE), true) ?: [];
        $package = null;
        foreach ($packages as $p) {
            if ($p['package_id'] == $order['package_id']) {
                $package = $p;
                break;
            }
        }
        
        if ($package) {
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"));
            
            $subscription = [
                'subscription_id' => uniqid('sub_'),
                'username' => $order['username'],
                'package_id' => $package['package_id'],
                'package_name' => $package['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'activated_by' => 'admin_approval',
                'order_id' => $orderId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
            $subscriptions[] = $subscription;
            file_put_contents(PREMIUM_SUBSCRIPTIONS_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    file_put_contents(PREMIUM_ORDERS_FILE, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    logPremiumActivity($order['username'], $status === 'approved' ? 'approved' : 'rejected', "Đơn hàng $orderId: $adminNote");
    
    return ['success' => true, 'message' => $status === 'approved' ? 'Đơn hàng đã được duyệt' : 'Đơn hàng đã bị từ chối'];
}

/**
 * Tạo Premium key (Admin)
 * @param int $packageId ID gói Premium
 * @param int $quantity Số lượng key cần tạo
 * @return array Danh sách key đã tạo
 */
function generatePremiumKeys($packageId, $quantity = 1) {
    $keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];
    $newKeys = [];
    
    for ($i = 0; $i < $quantity; $i++) {
        $keyCode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
        $keyCode = chunk_split($keyCode, 4, '-');
        $keyCode = rtrim($keyCode, '-');
        
        $key = [
            'key_id' => uniqid('key_'),
            'key_code' => $keyCode,
            'package_id' => $packageId,
            'status' => 'unused',
            'created_at' => date('Y-m-d H:i:s'),
            'used_by' => null,
            'used_at' => null
        ];
        
        $keys[] = $key;
        $newKeys[] = $key;
    }
    
    file_put_contents(PREMIUM_KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $newKeys;
}

/**
 * Thu hồi Premium (Admin)
 * @param string $username Username
 * @param string $reason Lý do thu hồi
 * @return array Kết quả
 */
function revokePremium($username, $reason = '') {
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $updated = false;
    
    foreach ($subscriptions as &$sub) {
        if ($sub['username'] === $username && $sub['status'] === 'active') {
            $sub['status'] = 'revoked';
            $sub['revoked_at'] = date('Y-m-d H:i:s');
            $sub['revoked_reason'] = $reason;
            $updated = true;
        }
    }
    
    if ($updated) {
        file_put_contents(PREMIUM_SUBSCRIPTIONS_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        logPremiumActivity($username, 'revoke', "Thu hồi Premium: $reason");
        return ['success' => true, 'message' => 'Đã thu hồi Premium'];
    }
    
    return ['success' => false, 'message' => 'Không tìm thấy Premium đang active'];
}

/**
 * Gia hạn Premium (Admin)
 * @param string $username Username
 * @param int $days Số ngày gia hạn
 * @return array Kết quả
 */
function extendPremium($username, $days) {
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $updated = false;
    
    foreach ($subscriptions as &$sub) {
        if ($sub['username'] === $username && $sub['status'] === 'active') {
            $currentEndDate = strtotime($sub['end_date']);
            $newEndDate = date('Y-m-d H:i:s', strtotime("+$days days", $currentEndDate));
            $sub['end_date'] = $newEndDate;
            $sub['extended_at'] = date('Y-m-d H:i:s');
            $sub['extended_days'] = ($sub['extended_days'] ?? 0) + $days;
            $updated = true;
        }
    }
    
    if ($updated) {
        file_put_contents(PREMIUM_SUBSCRIPTIONS_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        logPremiumActivity($username, 'extend', "Gia hạn Premium: $days ngày");
        return ['success' => true, 'message' => "Đã gia hạn $days ngày"];
    }
    
    return ['success' => false, 'message' => 'Không tìm thấy Premium đang active'];
}

/**
 * Kiểm tra và cập nhật trạng thái Premium hết hạn
 */
function checkExpiredSubscriptions() {
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $now = time();
    $updated = false;
    
    foreach ($subscriptions as &$sub) {
        if ($sub['status'] === 'active' && strtotime($sub['end_date']) < $now) {
            $sub['status'] = 'expired';
            $sub['expired_at'] = date('Y-m-d H:i:s');
            $updated = true;
            logPremiumActivity($sub['username'], 'expired', 'Premium đã hết hạn');
        }
    }
    
    if ($updated) {
        file_put_contents(PREMIUM_SUBSCRIPTIONS_FILE, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

/**
 * Ghi log hoạt động Premium
 * @param string $username Username
 * @param string $action Hành động
 * @param string $details Chi tiết
 */
function logPremiumActivity($username, $action, $details) {
    $logFile = __DIR__ . '/../logs/premium_log.json';
    $logs = [];
    
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }
    
    $logs[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'username' => $username,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Giữ lại 1000 log gần nhất
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Lấy cấu hình hệ thống
 * @return array
 */
function getSystemConfig() {
    if (!file_exists(SYSTEM_CONFIG_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(SYSTEM_CONFIG_FILE), true) ?: [];
}

/**
 * Lấy học kì mặc định của hệ thống
 * @return string
 */
function getDefaultSemester() {
    $config = getSystemConfig();
    return $config['semester']['default'] ?? 'hk2';
}

/**
 * Lấy học kì hiện tại của hệ thống
 * @return string
 */
function getCurrentSemester() {
    $config = getSystemConfig();
    return $config['semester']['current'] ?? 'hk2';
}

/**
 * Cập nhật học kì hiện tại (Admin)
 * @param string $semester 'hk1' hoặc 'hk2'
 * @return bool
 */
function updateCurrentSemester($semester) {
    $config = getSystemConfig();
    $config['semester']['current'] = $semester;
    return file_put_contents(SYSTEM_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Lấy thống kê Premium
 * @return array
 */
function getPremiumStats() {
    checkExpiredSubscriptions();
    
    $subscriptions = json_decode(file_get_contents(PREMIUM_SUBSCRIPTIONS_FILE), true) ?: [];
    $keys = json_decode(file_get_contents(PREMIUM_KEYS_FILE), true) ?: [];
    $orders = json_decode(file_get_contents(PREMIUM_ORDERS_FILE), true) ?: [];
    
    $activeCount = 0;
    $expiringSoon = 0; // Hết hạn trong 7 ngày
    $totalRevenue = 0;
    $now = time();
    
    foreach ($subscriptions as $sub) {
        if ($sub['status'] === 'active') {
            $activeCount++;
            $endDate = strtotime($sub['end_date']);
            $daysRemaining = ceil(($endDate - $now) / (60 * 60 * 24));
            if ($daysRemaining <= 7) {
                $expiringSoon++;
            }
        }
    }
    
    foreach ($orders as $order) {
        if ($order['status'] === 'approved') {
            $totalRevenue += $order['price'];
        }
    }
    
    return [
        'total_active' => $activeCount,
        'expiring_soon' => $expiringSoon,
        'total_revenue' => $totalRevenue,
        'unused_keys' => count(array_filter($keys, fn($k) => $k['status'] === 'unused')),
        'pending_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'pending'))
    ];
}

// Tự động kiểm tra hết hạn khi include file này
checkExpiredSubscriptions();
?>
