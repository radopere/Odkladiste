<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$action = $_GET['akce'] ?? null;

// Přepínač akcí

switch ($action) {
case 'presunout':
    $baseDir = realpath(__DIR__ . '/uploads');
    $type = isset($_GET['file']) ? 'file' : (isset($_GET['folder']) ? 'folder' : '');
    $path = $_GET[$type] ?? '';
    $fullPath = realpath($baseDir . '/' . $path);
    $parentDir = dirname($path);

    if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !file_exists($fullPath)) {
        set_notification('Neplatná cesta.', 'danger');
        header("Location: dashboard.php");
        exit;
    }

    function getAllFolders($dir, $basePath = '', $currentPath = '') {
        global $type;
        $folders = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $itemPath = $dir . '/' . $item;
            $relativePath = $basePath ? $basePath . '/' . $item : $item;

            if (is_dir($itemPath)) {
                if ($type === 'folder' && strpos($relativePath, $currentPath) === 0) {
                    continue; // Nezobrazí vlastní podsložky jako cílové
                }

                $folders[] = $relativePath;
                $folders = array_merge($folders, getAllFolders($itemPath, $relativePath, $currentPath));
            }
        }

        return $folders;
    }

    $folders = getAllFolders($baseDir, '', $path);
    array_unshift($folders, ''); // Přidání root složky

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_folder'])) {
        $targetFolder = trim($_POST['target_folder']);
        if ($targetFolder === '__root__') $targetFolder = '';
        $targetPath = $baseDir . ($targetFolder ? '/' . $targetFolder : '');

        if (!file_exists($targetPath) || !is_dir($targetPath)) {
            set_notification('Cílová složka neexistuje.', 'danger');
        } else {
            $fileName = basename($fullPath);
            $newPath = $targetPath . '/' . $fileName;

            // Kontrola: složka nesmí být přesunuta do sebe nebo své podsložky
            if ($type === 'folder') {
                $normalizedTarget = str_replace('\\', '/', str_replace($baseDir . '/', '', $targetPath));
                if (strpos($normalizedTarget, $path) === 0) {
                    set_notification('Nelze přesunout složku do sebe nebo své podsložky.', 'danger');
                    break;
                }
            }

            if (file_exists($newPath)) {
                set_notification('V cílové složce již existuje položka se stejným názvem.', 'danger');
            } else {
                if (rename($fullPath, $newPath)) {
                    if ($type === 'file' && file_exists($fullPath . '.note.json')) {
                        rename($fullPath . '.note.json', $newPath . '.note.json');
                    }

                    set_notification('Položka byla úspěšně přesunuta.', 'success');
                    header("Location: dashboard.php?folder=" . urlencode($targetFolder));
                    exit;
                } else {
                    set_notification('Nepodařilo se přesunout položku.', 'danger');
                }
            }
        }
    }

    require_once __DIR__ . '/includes/header.php';
    $itemName = basename($fullPath);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Přesunout <?= $type === 'file' ? 'soubor' : 'složku' ?></h1>
</div>

<div class="card text-align-center" style="max-width: 450px;">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="item_name" class="form-label">Název položky:</label>
                <input type="text" class="form-control" id="item_name" value="<?= htmlspecialchars($itemName) ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="current_location" class="form-label">Současné umístění:</label>
                <input type="text" class="form-control" id="current_location" value="<?= htmlspecialchars($parentDir ?: 'Root') ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="target_folder" class="form-label">Cílová složka:</label>
                <select class="form-select" id="target_folder" name="target_folder" required>
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?= $folder === '' ? '__root__' : htmlspecialchars($folder) ?>"
                            <?= ($folder === $parentDir || ($folder === '' && $parentDir === '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($folder ?: 'Root') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Přesunout</button>
                <a href="dashboard.php?folder=<?= urlencode($parentDir) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Zpět</a>
            </div>
        </form>
    </div>
</div>

<?php
    break;
        
    case 'poznamka':
        $baseDir = realpath(__DIR__ . '/uploads');
$fileRelPath = $_GET['file'] ?? '';
$filePath = realpath($baseDir . '/' . $fileRelPath);

if (!$filePath || strpos($filePath, $baseDir) !== 0 || is_dir($filePath)) {
    set_notification('Neplatná cesta k souboru.', 'danger');
    header("Location: dashboard.php");
    exit;
}

$notePath = $filePath . '.note.json';
$noteData = ['note' => ''];

if (file_exists($notePath)) {
    $noteData = json_decode(file_get_contents($notePath), true) ?? ['note' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noteData['note'] = $_POST['note'] ?? '';
    
    if (file_put_contents($notePath, json_encode($noteData, JSON_UNESCAPED_UNICODE))) {
        set_notification('Poznámka byla úspěšně uložena.', 'success');
    } else {
        set_notification('Nepodařilo se uložit poznámku.', 'danger');
    }
    
    header("Location: dashboard.php?folder=" . urlencode(dirname($fileRelPath)));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-left mb-4"><h1>Upravit poznámku</h1></div>
<form method="post" action="akce.php?akce=poznamka&file=<?= urlencode($fileRelPath) ?>">
<div class="card text-align-center" style="max-width: 850px;">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <textarea name="note" id="note" class="form-control" placeholder="Text poznámky" rows="5"><?= htmlspecialchars($noteData['note']) ?></textarea>
            </div>

            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Uložit poznámku</button> 
                <a href="dashboard.php?folder=<?= urlencode(dirname($fileRelPath)) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Zpět</a>
            </div>
        </form>
    </div>
</div>
</form>
   <?php break;
        
    case 'stahnout':
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
    break;

        
    case 'smazat':
        $baseDir = realpath(__DIR__ . '/uploads');
$file = $_GET['file'] ?? '';
$folder = $_GET['folder'] ?? '';
$redirectFolder = '';

if ($file) {
    $filePath = realpath($baseDir . '/' . $file);
    $redirectFolder = dirname($file);
    
    // Ochrana proti přístupu mimo uploads
    if (!$filePath || strpos($filePath, $baseDir) !== 0 || !file_exists($filePath)) {
        set_notification('Neplatný soubor.', 'danger');
        header("Location: dashboard.php");
        exit;
    }
    
    // Smazání souboru a jeho poznámky
    $notePath = $filePath . '.note.json';
    if (file_exists($notePath)) {
        unlink($notePath);
    }
    
    if (unlink($filePath)) {
        set_notification('Soubor byl úspěšně smazán.', 'success');
    } else {
        set_notification('Nepodařilo se smazat soubor.', 'danger');
    }
} elseif ($folder) {
    $folderPath = realpath($baseDir . '/' . $folder);
    $redirectFolder = dirname($folder);
    
    // Ochrana proti přístupu mimo uploads
    if (!$folderPath || strpos($folderPath, $baseDir) !== 0 || !is_dir($folderPath)) {
        set_notification('Neplatná složka.', 'danger');
        header("Location: dashboard.php");
        exit;
    }
    
    // Smazání složky
    if (delete_directory($folderPath)) {
        set_notification('Složka byla úspěšně smazána.', 'success');
    } else {
        set_notification('Nepodařilo se smazat složku.', 'danger');
    }
} else {
    set_notification('Nebyl specifikován soubor ani složka k smazání.', 'danger');
}

header("Location: dashboard.php" . ($redirectFolder ? "?folder=" . urlencode($redirectFolder) : ""));
        break;

    case 'prejmenovat':
        $baseDir = realpath(__DIR__ . '/uploads');
$type = isset($_GET['file']) ? 'file' : (isset($_GET['folder']) ? 'folder' : '');
$path = $_GET[$type] ?? '';
$fullPath = realpath($baseDir . '/' . $path);
$parentDir = dirname($path);

// Ochrana proti přístupu mimo uploads
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !file_exists($fullPath)) {
    set_notification('Neplatná cesta.', 'danger');
    header("Location: dashboard.php");
    exit;
}

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $newName = trim($_POST['new_name']);
    $newName = preg_replace('/[^\w\-\. ]/', '', $newName); // Odstranění nebezpečných znaků
    
    if (empty($newName)) {
        set_notification('Název nemůže být prázdný.', 'danger');
    } else {
        $dirPath = dirname($fullPath);
        $newPath = $dirPath . '/' . $newName;
        
        if (file_exists($newPath)) {
            set_notification('Soubor nebo složka s tímto názvem již existuje.', 'danger');
        } else {
            if (rename($fullPath, $newPath)) {
                // Pokud jde o soubor, přejmenujeme i jeho poznámku
                if ($type === 'file' && file_exists($fullPath . '.note.json')) {
                    rename($fullPath . '.note.json', $newPath . '.note.json');
                }
                
                set_notification('Položka byla úspěšně přejmenována.', 'success');
                header("Location: dashboard.php?folder=" . urlencode($parentDir));
                exit;
            } else {
                set_notification('Nepodařilo se přejmenovat položku.', 'danger');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$itemName = basename($fullPath);
?>

<div class="d-flex justify-content-between align-items-left mb-4"><h1>Přejmenovat <?= $type === 'file' ? 'soubor' : 'složku' ?></h1></div>
<form method="post">
<div class="card text-align-center" style="max-width: 450px;">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="current_name" class="form-label">Současný název:</label><input type="text" class="form-control" id="current_name" value="<?= htmlspecialchars($itemName) ?>" disabled>
                <label for="current_name" class="form-label">Nový název:</label><input type="text" class="form-control" id="new_name" name="new_name" value="<?= htmlspecialchars($itemName) ?>" required autofocus>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Přejmenovat</button>
                <a href="dashboard.php?folder=<?= urlencode($parentDir) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Zpět</a>
            </div>
        </form>
    </div>
</div>
</form>

<?php
        break;

    default:
        http_response_code(400);
        echo "Neznámá akce: " . htmlspecialchars($action);
        exit;
}

?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>