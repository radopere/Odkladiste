<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (login($password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Neplatné heslo.";
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 " style="min-width: 400px;">
    <h4 class="mb-3 text-center"><i class="bi bi-person-fill-lock"></i> Přihlášení</h4>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Zadejte heslo" required>
        </div>
        <button type="submit" class="btn btn-primary">Přihlásit se <i class="bi bi-arrow-bar-right"></i></button>
    </form>
</div>

</body>
</html>
