<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$configFile = '../system_config.json';

// Load current config
function loadConfig() {
    global $configFile;
    if (file_exists($configFile)) {
        return json_decode(file_get_contents($configFile), true);
    }
    return getDefaultConfig();
}

// Save config
function saveConfig($config) {
    global $configFile;
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Default configuration
function getDefaultConfig() {
    return [
        'semester' => [
            'default' => 'hk2',
            'current' => 'hk1',
            'available' => ['hk1', 'hk2'],
            'labels' => [
                'hk1' => 'Học kì 1',
                'hk2' => 'Học kì 2'
            ]
        ],
        'premium' => [
            'enabled' => true,
            'trial_days' => 7,
            'features' => [
                'unlimited_exams' => true,
                'export_with_answers' => true,
                'auto_matrix' => true,
                'advanced_stats' => true,
                'import_excel' => true,
                'question_bank_unlimited' => true
            ]
        ],
        'security' => [
            'password_min_length' => 6,
            'password_require_uppercase' => false,
            'password_require_numbers' => false,
            'password_require_special' => false,
            'session_timeout' => 3600,
            'max_login_attempts' => 5,
            'lockout_duration' => 900,
            'enable_2fa' => false,
            'ip_whitelist_enabled' => false,
            'ip_whitelist' => []
        ],
        'system' => [
            'school_name' => 'Trường THCS CVD',
            'school_year' => '2025-2026',
            'version' => '2.0',
            'last_updated' => date('Y-m-d'),
            'disable_view_source' => false
        ]
    ];
}

try {
    $config = loadConfig();
    
    // Handle different actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Update semester configuration
        if (isset($_POST['semester']) && isset($_POST['school_year'])) {
            $semester = $_POST['semester'];
            $schoolYear = $_POST['school_year'];
            $schoolName = $_POST['school_name'] ?? $config['system']['school_name'];
            
            if (!in_array($semester, ['hk1', 'hk2'])) {
                throw new Exception('Học kì không hợp lệ');
            }
            
            $config['semester']['current'] = $semester;
            $config['system']['school_year'] = $schoolYear;
            $config['system']['school_name'] = $schoolName;
            $config['system']['last_updated'] = date('Y-m-d');
            
            if (saveConfig($config)) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật cấu hình học kì thành công']);
            } else {
                throw new Exception('Không thể lưu cấu hình');
            }
            exit();
        }
        
        // Update premium configuration
        if (isset($_POST['action']) && $_POST['action'] === 'update_premium_config') {
            $config['premium']['enabled'] = isset($_POST['premium_enabled']);
            $config['premium']['trial_days'] = intval($_POST['trial_days'] ?? 7);
            
            // Update features
            $features = [
                'unlimited_exams',
                'export_with_answers',
                'auto_matrix',
                'advanced_stats',
                'import_excel',
                'question_bank_unlimited'
            ];
            
            foreach ($features as $feature) {
                $config['premium']['features'][$feature] = isset($_POST['feature_' . $feature]);
            }
            
            $config['system']['last_updated'] = date('Y-m-d');
            
            if (saveConfig($config)) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật cấu hình Premium thành công']);
            } else {
                throw new Exception('Không thể lưu cấu hình');
            }
            exit();
        }
        
        // Update security configuration
        if (isset($_POST['action']) && $_POST['action'] === 'update_security_config') {
            // Password policy
            $config['security']['password_min_length'] = max(4, min(20, intval($_POST['password_min_length'] ?? 6)));
            $config['security']['password_require_uppercase'] = isset($_POST['password_require_uppercase']);
            $config['security']['password_require_numbers'] = isset($_POST['password_require_numbers']);
            $config['security']['password_require_special'] = isset($_POST['password_require_special']);
            
            // Session management
            $config['security']['session_timeout'] = max(300, min(86400, intval($_POST['session_timeout'] ?? 3600)));
            
            // Login attempts
            $config['security']['max_login_attempts'] = max(3, min(10, intval($_POST['max_login_attempts'] ?? 5)));
            $config['security']['lockout_duration'] = max(60, min(3600, intval($_POST['lockout_duration'] ?? 900)));
            
            // 2FA
            $config['security']['enable_2fa'] = isset($_POST['enable_2fa']);
            
            // IP Whitelist
            $config['security']['ip_whitelist_enabled'] = isset($_POST['ip_whitelist_enabled']);
            $ipWhitelist = $_POST['ip_whitelist'] ?? '';
            $config['security']['ip_whitelist'] = array_filter(
                array_map('trim', explode("\n", $ipWhitelist)),
                function($ip) { return !empty($ip); }
            );
            
            // System protection
            $config['system']['disable_view_source'] = isset($_POST['disable_view_source']);
            $config['system']['last_updated'] = date('Y-m-d');
            
            if (saveConfig($config)) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật cấu hình bảo mật thành công']);
            } else {
                throw new Exception('Không thể lưu cấu hình');
            }
            exit();
        }
        
        // Unknown action
        throw new Exception('Action không hợp lệ');
    }
    
    // GET request - return current config
    echo json_encode(['success' => true, 'config' => $config]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
