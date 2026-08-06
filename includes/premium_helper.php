<?php
/**
 * Premium Helper Functions
 * Các hàm hỗ trợ quản lý Premium cho hệ thống CVD
 */

require_once __DIR__ . '/json_db_helper.php';

// Đường dẫn đến các file dữ liệu
if (!defined('PREMIUM_PACKAGES_FILE')) define('PREMIUM_PACKAGES_FILE', __DIR__ . '/../admin/premium_packages.json');
if (!defined('PREMIUM_KEYS_FILE')) define('PREMIUM_KEYS_FILE', __DIR__ . '/../admin/premium_keys.json');
if (!defined('PREMIUM_SUBSCRIPTIONS_FILE')) define('PREMIUM_SUBSCRIPTIONS_FILE', __DIR__ . '/../admin/premium_subscriptions.json');
if (!defined('PREMIUM_ORDERS_FILE')) define('PREMIUM_ORDERS_FILE', __DIR__ . '/../admin/premium_orders.json');
if (!defined('SYSTEM_CONFIG_FILE')) define('SYSTEM_CONFIG_FILE', __DIR__ . '/../admin/system_config.json');

/**
 * Kiểm tra xem giáo viên có Premium không
 */
if (!function_exists('isPremiumUser')) {
function isPremiumUser($username) {
    // If Premium system is disabled, everyone is Premium (free for all)
    $config = getSystemConfig();
    if (!($config['premium']['enabled'] ?? true)) {
        return true;
    }
    
    $subscription = getActiveSubscription($username);
    return $subscription !== null;
}
}

/**
 * Lấy thông tin subscription đang active của giáo viên
 */
if (!function_exists('getActiveSubscription')) {
function getActiveSubscription($username) {
    if (!file_exists(PREMIUM_SUBSCRIPTIONS_FILE)) {
        return null;
    }
    
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
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
}

/**
 * Lấy số ngày còn lại của Premium
 */
if (!function_exists('getPremiumDaysRemaining')) {
function getPremiumDaysRemaining($username) {
    $subscription = getActiveSubscription($username);
    if (!$subscription) {
        return -1;
    }
    
    $endDate = strtotime($subscription['end_date']);
    $now = time();
    $days = ceil(($endDate - $now) / (60 * 60 * 24));
    
    return max(0, (int)$days);
}
}

/**
 * Lấy thông tin gói Premium theo ID
 */
if (!function_exists('getPremiumPackageInfo')) {
function getPremiumPackageInfo($packageId) {
    if (!file_exists(PREMIUM_PACKAGES_FILE)) {
        return null;
    }
    
    $packages = get_json_data(PREMIUM_PACKAGES_FILE, []);
    foreach ($packages as $pkg) {
        if ($pkg['package_id'] == $packageId) {
            return $pkg;
        }
    }
    
    return null;
}
}

/**
 * Kích hoạt Premium bằng key
 */
if (!function_exists('activatePremiumWithKey')) {
function activatePremiumWithKey($username, $keyCode) {
    $keyCode = trim(strtoupper($keyCode));
    
    if (!file_exists(PREMIUM_KEYS_FILE)) {
        return ['success' => false, 'message' => 'Hệ thống key chưa được khởi tạo'];
    }
    
    $keys = get_json_data(PREMIUM_KEYS_FILE, []);
    $targetKey = null;
    
    foreach ($keys as $k) {
        if ($k['key_code'] === $keyCode && $k['status'] === 'unused') {
            $targetKey = $k;
            break;
        }
    }
    
    if (!$targetKey) {
        return ['success' => false, 'message' => 'Mã kích hoạt không đúng hoặc đã được sử dụng'];
    }
    
    $package = getPremiumPackageInfo($targetKey['package_id']);
    if (!$package) {
        return ['success' => false, 'message' => 'Gói Premium không hợp lệ'];
    }
    
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
        'key_code' => $keyCode,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Lưu subscription
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
    $subscriptions[] = $subscription;
    save_json_data(PREMIUM_SUBSCRIPTIONS_FILE, $subscriptions);
    
    // Cập nhật trạng thái key
    foreach ($keys as &$k) {
        if ($k['key_code'] === $keyCode) {
            $k['status'] = 'used';
            $k['used_by'] = $username;
            $k['used_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    save_json_data(PREMIUM_KEYS_FILE, $keys);
    
    // Log activity
    logPremiumActivity($username, 'activate', "Kích hoạt Premium bằng key: $keyCode");
    
    return ['success' => true, 'message' => 'Kích hoạt Premium thành công', 'subscription' => $subscription];
}
}

/**
 * Tạo đơn đăng ký Premium (chờ admin duyệt)
 */
if (!function_exists('createPremiumOrder')) {
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
    
    $orders = get_json_data(PREMIUM_ORDERS_FILE, []);
    $orders[] = $order;
    save_json_data(PREMIUM_ORDERS_FILE, $orders);
    
    logPremiumActivity($data['username'], 'order', "Tạo đơn đăng ký Premium: {$data['package_name']}");
    
    return ['success' => true, 'message' => 'Đơn đăng ký đã được gửi, vui lòng chờ admin duyệt', 'order' => $order];
}
}

/**
 * Duyệt đơn đăng ký Premium (Admin)
 */
if (!function_exists('approvePremiumOrder')) {
function approvePremiumOrder($orderId, $status, $adminNote = '') {
    $orders = get_json_data(PREMIUM_ORDERS_FILE, []);
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
        $packages = get_json_data(PREMIUM_PACKAGES_FILE, []);
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
            
            $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
            $subscriptions[] = $subscription;
            save_json_data(PREMIUM_SUBSCRIPTIONS_FILE, $subscriptions);
        }
    }
    
    save_json_data(PREMIUM_ORDERS_FILE, $orders);
    logPremiumActivity($order['username'], $status === 'approved' ? 'approved' : 'rejected', "Đơn hàng $orderId: $adminNote");
    
    return ['success' => true, 'message' => $status === 'approved' ? 'Đơn hàng đã được duyệt' : 'Đơn hàng đã bị từ chối'];
}
}

/**
 * Tạo Premium key (Admin)
 */
if (!function_exists('generatePremiumKeys')) {
function generatePremiumKeys($packageId, $quantity = 1) {
    $keys = get_json_data(PREMIUM_KEYS_FILE, []);
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
    
    save_json_data(PREMIUM_KEYS_FILE, $keys);
    return $newKeys;
}
}

/**
 * Thu hồi Premium (Admin)
 */
if (!function_exists('revokePremium')) {
function revokePremium($username, $reason = '') {
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
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
        save_json_data(PREMIUM_SUBSCRIPTIONS_FILE, $subscriptions);
        logPremiumActivity($username, 'revoke', "Thu hồi Premium: $reason");
        return ['success' => true, 'message' => 'Đã thu hồi Premium'];
    }
    
    return ['success' => false, 'message' => 'Không tìm thấy Premium đang active'];
}
}

/**
 * Gia hạn Premium (Admin)
 */
if (!function_exists('extendPremium')) {
function extendPremium($username, $days) {
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
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
        save_json_data(PREMIUM_SUBSCRIPTIONS_FILE, $subscriptions);
        logPremiumActivity($username, 'extend', "Gia hạn Premium: $days ngày");
        return ['success' => true, 'message' => "Đã gia hạn $days ngày"];
    }
    
    return ['success' => false, 'message' => 'Không tìm thấy Premium đang active'];
}
}

/**
 * Kiểm tra và cập nhật trạng thái Premium hết hạn
 */
if (!function_exists('checkExpiredSubscriptions')) {
function checkExpiredSubscriptions() {
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
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
        save_json_data(PREMIUM_SUBSCRIPTIONS_FILE, $subscriptions);
    }
}
}

/**
 * Ghi log hoạt động Premium
 */
if (!function_exists('logPremiumActivity')) {
function logPremiumActivity($username, $action, $details) {
    $logFile = __DIR__ . '/../logs/premium_log.json';
    $logs = get_json_data($logFile, []);
    
    $logs[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'username' => $username,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    save_json_data($logFile, $logs);
}
}

/**
 * Lấy cấu hình hệ thống
 */
if (!function_exists('getSystemConfig')) {
function getSystemConfig() {
    if (!file_exists(SYSTEM_CONFIG_FILE)) {
        return [];
    }
    return get_json_data(SYSTEM_CONFIG_FILE, []);
}
}

/**
 * Lấy học kì mặc định của hệ thống
 */
if (!function_exists('getDefaultSemester')) {
function getDefaultSemester() {
    $config = getSystemConfig();
    return $config['semester']['default'] ?? 'hk2';
}
}

/**
 * Lấy học kì hiện tại của hệ thống
 */
if (!function_exists('getCurrentSemester')) {
function getCurrentSemester() {
    $config = getSystemConfig();
    return $config['semester']['current'] ?? 'hk2';
}
}

/**
 * Cập nhật học kì hiện tại (Admin)
 */
if (!function_exists('updateCurrentSemester')) {
function updateCurrentSemester($semester) {
    $config = getSystemConfig();
    $config['semester']['current'] = $semester;
    return save_json_data(SYSTEM_CONFIG_FILE, $config);
}
}

/**
 * Lấy thống kê Premium
 */
if (!function_exists('getPremiumStats')) {
function getPremiumStats() {
    checkExpiredSubscriptions();
    
    $subscriptions = get_json_data(PREMIUM_SUBSCRIPTIONS_FILE, []);
    $keys = get_json_data(PREMIUM_KEYS_FILE, []);
    $orders = get_json_data(PREMIUM_ORDERS_FILE, []);
    
    $activeCount = 0;
    $expiringSoon = 0;
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
}

// Tự động kiểm tra hết hạn khi include file này
checkExpiredSubscriptions();
?>
