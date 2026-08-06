<?php
// Legacy student login page — all authentication now happens on the unified page /cvdlms/ (index.php).
$query = $_GET;
$query['role'] = 'student';
if (isset($query['timeout'])) {
    $query['timeout'] = '1';
}
header('Location: ../index.php?' . http_build_query($query));
exit;
