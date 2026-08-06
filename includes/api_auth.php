<?php

/**
 * CVD LMS — API authentication helpers.
 * Single source of truth cho xác thực API admin/teacher.
 */

function requireAdminSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('CVD_TEACHER_SESSION');
        session_start();
    }

    if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
        http_response_code(401);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requireTeacherSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('CVD_TEACHER_SESSION');
        session_start();
    }

    if (empty($_SESSION['username']) || $_SESSION['username'] === 'admin') {
        http_response_code(401);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requireStudentSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('CVD_STUDENT_SESSION');
        session_start();
    }

    if (empty($_SESSION['student_code'])) {
        http_response_code(401);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
