<?php
// Set unique session name for Student
session_name('CVD_STUDENT_SESSION');
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the unified login page
header('Location: ../index.php?role=student');
exit;
?>
