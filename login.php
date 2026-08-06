<?php
// Legacy login page — all authentication now happens on the unified page /cvdlms/ (index.php).
$query = $_GET;
if (isset($query['timeout'])) {
    $query['timeout'] = '1';
}
$qs = $query ? '?' . http_build_query($query) : '';
header('Location: index.php' . $qs);
exit;
