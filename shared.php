<?php
require_once __DIR__ . '/includes/functions.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('Neplatný odkaz.');
}

$sharePath = __DIR__ . '/shares';
$shareFiles = glob($sharePath . '/*.json');
$shareData = null;

foreach ($shareFiles as $shareFile) {
    $data = json_decode(file_get_contents($shareFile), true);
    
    if ($data['code'] === $code) {
        $shareData = $data;
        break;
    }
}

if (!$shareData) {
    die('Sdílený odkaz neexistuje nebo byl smazán.');
}

// Kontrola platnosti odkazu
if ($shareData['expiry'] > 0 && time() > $shareData['expiry']) {
    die('Platnost sdíleného odkazu vypršela.');
}

$baseDir = realpath(__DIR__ . '/uploads');
$file = $shareData['file'];
$filePath = realpath($baseDir . '/' . $file);

// Ochrana proti přístupu mimo uploads
if (!$filePath || strpos($filePath, $baseDir) !== 0 || !file_exists($filePath) || is_dir($filePath)) {
    die('Soubor neexistuje nebo byl smazán.');
}

$fileName = basename($filePath);
$fileType = mime_content_type($filePath);

// Zjištění, zda jde o obrázek nebo jiný typ souboru
$isImage = strpos($fileType, 'image/') === 0;
$isVideo = strpos($fileType, 'video/') === 0;
$isAudio = strpos($fileType, 'audio/') === 0;
$isPdf = $fileType === 'application/pdf';
$isText = in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['txt', 'md', 'html', 'css', 'js', 'php', 'json', 'xml', 'csv']);

// Načtení obsahu textového souboru
$textContent = '';
if ($isText && filesize($filePath) < 1024 * 1024) { // Omezení na 1MB
    $textContent = file_get_contents($filePath);
}

// Zobrazení souboru nebo nabídnutí ke stažení
if (isset($_GET['download'])) {
    // Nastavení hlaviček pro stažení
    header('Content-Type: ' . $fileType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sdílený soubor: <?= htmlspecialchars($fileName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sdílený soubor: <?= htmlspecialchars($fileName) ?></h5>
            </div>
            <div class="card-body text-center">
                <?php if ($isImage): ?>
                <img src="?code=<?= htmlspecialchars($code) ?>&download=1" class="img-fluid" alt="<?= htmlspecialchars($fileName) ?>">
                <?php elseif ($isVideo): ?>
                <video controls class="w-100">
                    <source src="?code=<?= htmlspecialchars($code) ?>&download=1" type="<?= $fileType ?>">
                    Váš prohlížeč nepodporuje přehrávání videa.
                </video>
                <?php elseif ($isAudio): ?>
                <audio controls class="w-100">
                    <source src="?code=<?= htmlspecialchars($code) ?>&download=1" type="<?= $fileType ?>">
                    Váš prohlížeč nepodporuje přehrávání zvuku.
                </audio>
                <?php elseif ($isPdf): ?>
                <embed src="?code=<?= htmlspecialchars($code) ?>&download=1" type="application/pdf" width="100%" height="600px">
                <?php elseif ($isText): ?>
                <pre class="text-start bg-light p-3" style="max-height: 600px; overflow: auto;"><?= htmlspecialchars($textContent) ?></pre>
                <?php else: ?>
                <div class="p-5 text-center">
                    <i class="bi bi-file-earmark display-1 text-secondary"></i>
                    <p class="mt-3">Náhled není k dispozici pro tento typ souboru.</p>
                    <a href="?code=<?= htmlspecialchars($code) ?>&download=1" class="btn btn-lg btn-success">
                        <i class="bi bi-download"></i> Stáhnout soubor
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted text-center">
                <small>Soubor sdílen pomocí Online odkladiště souborů</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
