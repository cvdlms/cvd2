<?php
// Student session timeout check
if (session_status() == PHP_SESSION_NONE) {
    // Set unique session name for Student to avoid conflict with Teacher
    session_name('CVD_STUDENT_SESSION');
    session_start();
}

// Load security config for session timeout
$sessionTimeout = 3600; // default 1 hour
if (file_exists(__DIR__ . '/../admin/system_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../admin/system_config.json'), true);
    $sessionTimeout = $config['security']['session_timeout'] ?? 3600;
}

// Check if session exists
if (!isset($_SESSION['student_code'])) {
    header('Location: login.php');
    exit;
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
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();
?>
