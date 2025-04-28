<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Získání seznamu všech sdílených souborů
$sharePath = __DIR__ . '/shares';
$sharedFiles = [];

if (file_exists($sharePath)) {
    $shareFiles = glob($sharePath . '/*.json');
    
    foreach ($shareFiles as $shareFile) {
        $data = json_decode(file_get_contents($shareFile), true);
        
        // Přeskočit expirované sdílení
        if ($data['expiry'] > 0 && time() > $data['expiry']) {
            continue;
        }
        
        $filePath = $data['file'];
        $fullPath = realpath(__DIR__ . '/uploads/' . $filePath);
        
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            $sharedFiles[] = [
                'name' => basename($filePath),
                'path' => $filePath,
                'directory' => dirname($filePath),
                'size' => filesize($fullPath),
                'code' => $data['code'],
                'expiry' => $data['expiry'],
                'created' => $data['created']
            ];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Sdílené soubory</h1>
</div>


        <?php if (empty($sharedFiles)): ?>
        <p class="text-center text-muted">Nemáte žádné sdílené soubory.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Soubor</th>
                        <th>Umístění</th>
                        <th>Odkaz</th>
                        <th>Platnost do</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sharedFiles as $file): ?>
                    <tr>
                        <td>
                            <i class="bi bi-file-earmark-share"></i>
                            <?= htmlspecialchars($file['name']) ?>
                        </td>
                        <td>
                            <a href="dashboard.php?folder=<?= urlencode($file['directory']) ?>">
                                <?= htmlspecialchars($file['directory'] ?: 'Root') ?>
                            </a>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/shared.php?code=' . $file['code']) ?>" readonly>
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <?= $file['expiry'] ? date('d.m.Y H:i', $file['expiry']) : 'Bez omezení' ?>
                        </td>
                        
                        <td>
                            <a href="#" class="btn btn-sm btn-outline-success" onclick="showEmailForm('<?= htmlspecialchars($file['name']) ?>', '<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/shared.php?code=' . $file['code']) ?>')" data-bs-toggle="modal" data-bs-target="#emailModal">
                                <i class="bi bi-envelope"></i> Odeslat emailem
                            </a>
                            <form method="post" action="share.php?file=<?= urlencode($file['path']) ?>" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Opravdu chcete zrušit sdílení?');">
                                    <i class="bi bi-x-circle"></i> Zrušit sdílení
                                </button>
                            </form>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

<script>
function copyToClipboard(element) {
    element.select();
    document.execCommand('copy');
    alert('Odkaz byl zkopírován do schránky.');
}
</script>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Odeslat odkaz emailem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="includes/share_email_send.php">
                <div class="modal-body">
                    <input type="hidden" name="share_link" id="shareLink">
                    <input type="hidden" name="file_name" id="fileName">
                    
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Email příjemce</label>
                        <input type="email" class="form-control" id="recipient" name="recipient" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Předmět</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="Sdílený soubor z online odkladiště">
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Zpráva</label>
                        <textarea class="form-control" id="message" name="message" rows="7">Ahoj, posílám odkaz na sdílený soubor z mého online odkladiště:

[ODKAZ]
                            
S pozdravem</textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Odeslat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyToClipboard(element) {
    element.select();
    document.execCommand('copy');
    alert('Odkaz byl zkopírován do schránky.');
}

function showEmailForm(fileName, shareLink) {
    document.getElementById('fileName').value = fileName;
    document.getElementById('shareLink').value = shareLink;
    document.getElementById('message').value = document.getElementById('message').value.replace('[ODKAZ]', shareLink);
}
</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
