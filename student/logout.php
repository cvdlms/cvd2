<?php
// Set unique session name for Student
session_name('CVD_STUDENT_SESSION');
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirect to the unified login page
header('Location: ../index.php?role=student');
exit;
?>
