<?php
foreach (['CVD_TEACHER_SESSION', 'CVD_STUDENT_SESSION'] as $sessName) {
    if (isset($_COOKIE[$sessName])) {
        session_name($sessName);
        session_start();
        $_SESSION = array();
        setcookie($sessName, '', time() - 3600, '/');
        session_destroy();
    }
}
header('Location: index.php');
exit;
