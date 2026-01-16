<?php
// Set session timeout BEFORE any session operation
if (session_status() == PHP_SESSION_NONE) {
    // Set unique session name for Teacher/Admin to avoid conflict with Student
    session_name('CVD_TEACHER_SESSION');
    
    // Load security config for session timeout
    $sessionTimeout = 7200; // default 2 hours
    if (file_exists(__DIR__ . '/../admin/system_config.json')) {
        $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
        $sessionTimeout = $config['security']['session_timeout'] ?? 7200;
    }
    
    // Set session timeout and cookie lifetime BEFORE session_start()
    ini_set('session.gc_maxlifetime', $sessionTimeout);
    ini_set('session.cookie_lifetime', $sessionTimeout);
    // Ensure session files are not deleted prematurely
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    session_start();
} else {
    // Session already started, load config for timeout check only
    $sessionTimeout = 7200;
    if (file_exists(__DIR__ . '/../admin/system_config.json')) {
        $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
        $sessionTimeout = $config['security']['session_timeout'] ?? 7200;
    }
}

// Check if session exists
if (!isset($_SESSION['username'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
        exit;
    } else {
        header('Location: ../login.php');
        exit;
    }
}

// Check session timeout
if (isset($_SESSION['LAST_ACTIVITY'])) {
    $inactive = time() - $_SESSION['LAST_ACTIVITY'];
    if ($inactive > $sessionTimeout) {
        // Session expired
        session_unset();
        session_destroy();
        // Start new session to clear old data
        session_start();
        session_regenerate_id(true);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc đã hết hạn do không hoạt động. Vui lòng đăng nhập lại.', 'expired' => true]);
            exit;
        } else {
            header('Location: ../login.php?timeout=1');
            exit;
        }
    }
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();
?>
