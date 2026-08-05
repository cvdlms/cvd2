<?php require_once __DIR__ . '/student_gender.php';
$stdGender = getStudentGender($_SESSION['student_code'] ?? '');
$stdBodyTheme = $stdGender === 'Nữ' ? ' theme-nu' : ($stdGender === 'Nam' ? ' theme-nam' : ''); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $title ?? 'CVD'; ?></title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="//cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link rel="preconnect" href="//fonts.googleapis.com" />
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin />
    <link href="//fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="../styles/theme-eduvn-student.css" rel="stylesheet" />
    <link href="../styles/main.css" rel="stylesheet" />
</head>
<body class="student-page<?php echo $stdBodyTheme; ?>">
<?php include 'student_navbar.php'; ?>
