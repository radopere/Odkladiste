<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$baseDir = realpath(__DIR__ . '/uploads');
$folder = $_GET['folder'] ?? '';
$currentPath = realpath($baseDir . '/' . $folder);

if (!$currentPath || strpos($currentPath, $baseDir) !== 0) {
    die("Neplatná cesta.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_name'])) {
    $newFolderName = trim($_POST['folder_name']);
    if ($newFolderName !== '') {
        $newFolderPath = $currentPath . '/' . basename($newFolderName);
        if (!file_exists($newFolderPath)) {
            mkdir($newFolderPath, 0775, true);
        }
    }

    header("Location: dashboard.php?folder=" . urlencode($folder));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="mb-4">Vytvořit složku</h1>

<form method="post" action="create_folder.php?folder=<?= urlencode($folder) ?>" class="p-4 bg-light">
    <div class="mb-3">
        <label for="folder_name" class="form-label">Název nové složky:</label>
        <input type="text" name="folder_name" id="folder_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-folder-plus"></i> Vytvořit</button>
        <a href="dashboard.php?folder=<?= urlencode($folder) ?>" class="btn btn-secondary">Zpět</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
