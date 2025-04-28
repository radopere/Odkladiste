<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$folder = $_GET['folder'] ?? '';
$result = create_zip_archive($folder);

if ($result) {
    $zipPath = $result['path'];
    $zipName = $result['name'];
    
    // Nastavení hlaviček pro stažení
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Odeslání souboru
    readfile($zipPath);
    
    // Smazání dočasného souboru
    unlink($zipPath);
    exit;
} else {
    set_notification('Nepodařilo se vytvořit ZIP archiv.', 'danger');
    header("Location: dashboard.php" . ($folder ? "?folder=" . urlencode($folder) : ""));
    exit;
}
?>
