<?php
// Set unique session name for Teacher/Admin
session_name('CVD_TEACHER_SESSION');
session_start();
session_destroy();
header('Location: login.php');
exit;
