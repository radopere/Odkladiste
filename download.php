<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$baseDir = realpath(__DIR__ . '/uploads');
$file = $_GET['file'] ?? '';
$filePath = realpath($baseDir . '/' . $file);

// Ochrana proti přístupu mimo uploads
if (!$filePath || strpos($filePath, $baseDir) !== 0 || !file_exists($filePath)) {
    die('Neplatný soubor.');
}

$fileName = basename($filePath);

header('Content-Type: ' . mime_content_type($filePath));
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>