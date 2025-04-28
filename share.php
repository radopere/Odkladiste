<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}
// nastaveni casoveho pasma
date_default_timezone_set('Europe/Prague');


$baseDir = realpath(__DIR__ . '/uploads');
$file = $_GET['file'] ?? '';
$filePath = realpath($baseDir . '/' . $file);
$parentDir = dirname($file);

// Ochrana proti přístupu mimo uploads
if (!$filePath || strpos($filePath, $baseDir) !== 0 || !file_exists($filePath) || is_dir($filePath)) {
    set_notification('Neplatný soubor.', 'danger');
    header("Location: dashboard.php");
    exit;
}

// Generování nebo získání sdíleného odkazu
$shareCode = '';
$shareExpiry = '';
$sharePath = __DIR__ . '/shares';

if (!file_exists($sharePath)) {
    mkdir($sharePath, 0755, true);
}

$shareFile = $sharePath . '/' . md5($file) . '.json';

if (file_exists($shareFile)) {
    $shareData = json_decode(file_get_contents($shareFile), true);
    $shareCode = $shareData['code'];
    $shareExpiry = $shareData['expiry'] ? date('d.m.Y H:i', $shareData['expiry']) : 'Nikdy';
}

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Vytvoření nového sdíleného odkazu
        $expiryDays = (float)($_POST['expiry_days'] ?? 0);
        $expiryTime = 0;

        if ($expiryDays > 0) {
            $expiryTime = time() + (int)($expiryDays * 86400); // Převod dnů na sekundy
        }
        
        $shareCode = bin2hex(random_bytes(16));
        $shareData = [
            'code' => $shareCode,
            'file' => $file,
            'expiry' => $expiryTime,
            'created' => time()
        ];
        
        file_put_contents($shareFile, json_encode($shareData));
        
        $shareExpiry = $expiryTime ? date('d.m.Y H:i', $expiryTime) : 'Nikdy';
        set_notification('Sdílený odkaz byl úspěšně vytvořen.', 'success');
    } elseif ($action === 'delete') {
        // Smazání sdíleného odkazu
        if (file_exists($shareFile)) {
            unlink($shareFile);
            $shareCode = '';
            $shareExpiry = '';
            set_notification('Sdílený odkaz byl úspěšně smazán.', 'success');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$fileName = basename($filePath);
?>

<div class="d-flex justify-content-between align-items-left mb-4"><h1>Sdílení souboru</h1></div>
<table class="table table-bordered">
<thead class="table-light"><th><?= htmlspecialchars(basename($filePath)) ?></th></thead>
    
    
    <tr><td>
        
    <?php if ($shareCode): ?>
        <div class="p-3">
            <h5>Sdílený odkaz</h5>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="xy123" value="<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/shared.php?code=' . $shareCode) ?>" readonly>
                <button class="btn btn-outline-purple" type="button" onclick="copyShareLink()"><i class="bi bi-clipboard"></i> Kopírovat</button>
            </div>
            <p>Platnost odkazu: <b><?= htmlspecialchars($shareExpiry) ?></b></p>
            
            <form method="post" class="mt-3">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Opravdu chcete smazat sdílený odkaz?');">
                    <i class="bi bi-trash"></i> Smazat sdílený odkaz
                </button>
            </form>
        </div>
        <?php else: ?>
        <form method="post">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-3">
                <label for="expiry_days" class="form-label">Platnost odkazu</label>
                <select class="form-select" id="expiry_days" name="expiry_days">
                    <option value="0">Bez omezení</option>
                    <option value="0.0007">1 minuta</option>
                    <option value="1">1 den</option>
                    <option value="7">7 dní</option>
                    <option value="30">30 dní</option>
                    <option value="90">90 dní</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-purple"><i class="bi bi-share-fill"></i> Vytvořit sdílený odkaz</button>
            <a href="dashboard.php?folder=<?= urlencode($parentDir) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Zpět</a>
        </form>
        <?php endif; ?>    
    
    </td></tr>
</table>

<script>
function copyShareLink() {
    const shareLink = document.getElementById('xy123');
    shareLink.select();
    document.execCommand('copy');
    alert('Odkaz byl zkopírován do schránky.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>