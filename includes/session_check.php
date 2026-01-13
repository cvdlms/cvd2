<?php
// Set session timeout BEFORE any session operation
if (session_status() == PHP_SESSION_NONE) {
    // Load security config for session timeout
    $sessionTimeout = 3600; // default 1 hour
    if (file_exists(__DIR__ . '/../admin/system_config.json')) {
        $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
        $sessionTimeout = $config['security']['session_timeout'] ?? 3600;
    }
    
    // Set session timeout BEFORE session_start()
    ini_set('session.gc_maxlifetime', $sessionTimeout);
    session_start();
} else {
    // Session already started, load config for timeout check only
    $sessionTimeout = 3600;
    if (file_exists(__DIR__ . '/../admin/system_config.json')) {
        $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
        $sessionTimeout = $config['security']['session_timeout'] ?? 3600;
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
