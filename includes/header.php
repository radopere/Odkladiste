<?php
require_once __DIR__ . '/auth.php';
check_session_timeout();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Online odkladiště</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="includes/styl.css" rel="stylesheet">
    <link rel="icon" href="includes/favicon.ico" type="image/x-icon">

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Odkladiště</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house-door-fill"></i> Index</a></li>
                <li class="nav-item"><a class="nav-link" href="shared_files.php"><i class="bi bi-share-fill"></i> Sdílené soubory</a></li>
                <li class="nav-item"><a class="nav-link" href="todo.php"><i class="bi bi-card-checklist"></i> To-do list</a></li>
                <?php if (is_admin()): ?><li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Nastavení</a></li><?php endif; ?>
            </ul>
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Odhlásit se</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php
    // Zobrazení notifikace
    $notification = get_notification();
    if ($notification): 
    ?>
    <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show mb-4" role="alert">
        <?php echo $notification['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['timeout'])): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        Vaše relace vypršela z důvodu neaktivity. Přihlaste se prosím znovu.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
