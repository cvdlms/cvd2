<?php
// Shared student session bootstrap for root-level api/*.php endpoints.
// Student pages/APIs run under session_name 'CVD_STUDENT_SESSION' — root
// endpoints must use the SAME session or they never see the logged-in student.
if (session_status() === PHP_SESSION_NONE) {
    session_name('CVD_STUDENT_SESSION');
    session_start();
}
