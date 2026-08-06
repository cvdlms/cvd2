<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $title ?? 'EDUVN EXAMS'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <?php if (file_exists(__DIR__ . '/../assets/vendor/bootstrap/css/bootstrap.min.css')): ?>
        <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/vendor/bootstrap-icons/bootstrap-icons.css')): ?>
        <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/vendor/datatables/dataTables.bootstrap5.min.css')): ?>
        <link href="../assets/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
        <link href="../assets/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <?php else: ?>
        <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
        <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    <?php endif; ?>
    <link href="../styles/main.css" rel="stylesheet" />
    <link href="../styles/theme-eduvn.css" rel="stylesheet" />
    <?php if (file_exists(__DIR__ . '/../assets/vendor/jquery/jquery.min.js')): ?>
        <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <?php else: ?>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/vendor/bootstrap/js/bootstrap.bundle.min.js')): ?>
        <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <?php else: ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
    <?php if (file_exists(__DIR__ . '/../assets/vendor/datatables/jquery.dataTables.min.js')): ?>
        <script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
        <script src="../assets/vendor/datatables/dataTables.responsive.min.js"></script>
        <script src="../assets/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <?php else: ?>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <?php endif; ?>
</head>
<body class="teacher-page">
<?php include 'teacher_navbar.php'; ?>
