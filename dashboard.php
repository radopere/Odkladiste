<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$baseDir = realpath(__DIR__ . '/uploads');
$folder = $_GET['folder'] ?? '';
$folder = str_replace('\\', '/', $folder); // Normalizace cesty
$searchTerm = $_GET['search'] ?? '';
$fullPath = realpath($baseDir . '/' . $folder);

// Ochrana proti přístupu mimo uploads
if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
    set_notification('Neplatná cesta.', 'danger');
    header("Location: dashboard.php");
    exit;
}

// Získání obsahu adresáře nebo výsledků vyhledávání
if (!empty($searchTerm)) {
    $searchResults = search_files($searchTerm, $folder);
    $items = [];
    
    foreach ($searchResults as $result) {
        $dirName = dirname($result['path']);
        $relativePath = $dirName === '.' ? '' : $dirName;

        // Přidáme jen výsledky z aktuální složky
        if ($relativePath === $folder) {
            $items[] = [
                'name' => basename($result['path']),
                'is_dir' => false,
                'size' => $result['size'],
                'note' => $result['note'] ?? null,
                'match_type' => $result['match_type'] ?? null
            ];
        }
    }
} else {
    // Původní kód pro získání souborů a složek
    $items = [];
    foreach (scandir($fullPath) as $file) {
        if ($file === '.' || $file === '..' || str_ends_with($file, '.note.json')) continue;
        $filePath = $fullPath . '/' . $file;
        $isDir = is_dir($filePath);
        $note = null;

        if (!$isDir && file_exists("$filePath.note.json")) {
            $noteData = json_decode(file_get_contents("$filePath.note.json"), true);
            $note = $noteData['note'] ?? null;
        }

        $items[] = [
            'name' => $file,
            'is_dir' => $isDir,
            'size' => $isDir ? null : filesize($filePath),
            'note' => $note
        ];
    }

    // Seřazení: nejdřív složky, pak soubory
    usort($items, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}
?>

<!-- Nadpis a vyhledávací formulář na stejné úrovni -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Správce souborů</h1>

    <!-- Vyhledávací formulář -->
    <form method="get" class="d-flex" style="width: 50%;">
        <?php if ($folder): ?>
            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
        <?php endif; ?>
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Hledat soubory... (ve vývoji)" value="<?= htmlspecialchars($searchTerm) ?>" disabled>
            <button class="btn btn-outline-secondary" type="submit" disabled>
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>
</div>

<!-- Všechna tlačítka na jedné řádce -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">

    <a href="upload.php<?= $folder ? '?folder=' . urlencode($folder) : ''; ?>" class="btn btn-primary">
        <i class="bi bi-upload"></i> Nahrát soubor(y)
    </a>

    <a href="create_folder.php<?= $folder ? '?folder=' . urlencode($folder) : ''; ?>" class="btn btn-secondary">
        <i class="bi bi-folder-plus"></i> Nová složka
    </a>

    <?php if ($folder): ?>
        <a href="download_zip.php?folder=<?= urlencode($folder) ?>" class="btn btn-warning">
            <i class="bi bi-file-earmark-zip"></i> Stáhnout složku jako ZIP
        </a>
    <?php endif; ?>

</div>


<div class="mb-2 ps-4 pt-3 p-1 text-uppercase bg-light">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="dashboard.php" class="text-decoration-none">Root</a>
            </li>
            <?php if ($folder): ?>
                <?php
                $folderParts = explode('/', $folder);
                $folderPath = '';
                foreach ($folderParts as $index => $part) {
                    $folderPath .= $part;
                    $breadcrumbItem = htmlspecialchars($part);
                    if ($index === count($folderParts) - 1) {
                        // Aktuální složka bez odkazu
                        echo "<li class=\"breadcrumb-item active\" aria-current=\"page\">$breadcrumbItem</li>";
                    } else {
                        echo "<li class=\"breadcrumb-item\"><a href=\"dashboard.php?folder=" . urlencode($folderPath) . "\">$breadcrumbItem</a></li>";
                    }
                    $folderPath .= '/';
                }
                ?>
            <?php endif; ?>
        </ol>
    </nav>
</div>

<?php
$folderCount = count(array_filter($items, fn($item) => $item['is_dir']));
$fileCount = count(array_filter($items, fn($item) => !$item['is_dir']));
?>
<div class="mb-2 text-muted"><small><i class="bi bi-folder-fill text-warning"></i> Složek: <?= $folderCount; ?> / <i class="bi bi-file-earmark text-secondary"></i> Souborů: <?= $fileCount; ?></small></div>

<!-- Původní tabulka s vylepšeními -->
<table class="table table-bordered table-hover lh-lg">
    <thead class="table-light">
        <tr>
            <th>Název</th>
            <th>Velikost</th>
            <th>Poznámka</th>
            <th class="text-end">Akce</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
        <tr>
            <td colspan="4" class="text-center py-4">
                <?php if ($searchTerm): ?>Nebyly nalezeny žádné soubory odpovídající vašemu vyhledávání.
                <?php else: ?>Složka je prázdná.<?php endif; ?>
            </td>
        </tr>
        <?php else: 
        
        if ($folder): 
        // Určení nadřazené složky
        $parentFolder = dirname($folder);
        // Pokud jsme v rootu, nechceme, aby ".." vedlo k "dashboard.php?folder=."
        $parentFolder = ($parentFolder === '.' || $parentFolder === '\\') ? '' : $parentFolder;
    ?>
    <tr>
        <td colspan="4" class="bg-light">
            <b><i class="bi bi-arrow-return-left text-secondary"></i></b>
            <!-- Odkaz na nadřazenou složku, pokud není v rootu -->
            <a href="dashboard.php<?= $parentFolder ? '?folder=' . urlencode($parentFolder) : ''; ?>">..</a>
        </td>
    </tr>
<?php endif; 
        
        
        foreach ($items as $item): ?>
        <tr <?= isset($item['match_type']) && $item['match_type'] === 'note' ? 'class="table-info"' : '' ?>>        
            <td>
                <?php if ($item['is_dir']): ?>
                    <i class="bi bi-folder-fill text-warning"></i>
                    <a href="?folder=<?= urlencode(trim($folder . '/' . $item['name'], '/')); ?>">
                        <?= htmlspecialchars($item['name']); ?>
                    </a>
                <?php else: ?>
                    <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($item['name']); ?>
                <?php endif; ?>
            </td>
            <td><?= $item['is_dir'] ? '-' : format_bytes($item['size']); ?></td>
            <td><?= $item['note'] ? htmlspecialchars($item['note']) : ''; ?></td>
            <td class="flex gap-6 text-end"  style="max-width: 160px;">
                    <?php if (!$item['is_dir']): ?>
                    <a href="share.php?file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-purple" title="Sdílet"><i class="bi bi-share-fill"></i> </a>
                    <a href="akce.php?akce=poznamka&file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-pink" title="Upravit poznámku"><i class="bi bi-pencil"></i> </a>
                    <a href="akce.php?akce=stahnout&file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-success" title="Stáhnout soubor"><i class="bi bi-download"></i> </a>
                    <a href="akce.php?akce=prejmenovat&file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-orange" title="Přejmenovat soubor"><i class="bi bi-pencil-square"></i> </a>
                    <a href="akce.php?akce=presunout&file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-secondary" title="Přesunout subor"><i class="bi bi-arrows-move"></i> </a>
                    <a href="akce.php?akce=smazat&file=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Opravdu smazat?');" title="Smazat soubor"><i class="bi bi-trash"></i></a>
                
                <!-- akce a správa složek -->
                <?php else: ?>
                    <a href="akce.php?akce=prejmenovat&folder=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-orange" title="Přejmenovat složku"><i class="bi bi-pencil-square"></i></a>
                <?php if ($folder): ?>
                    <a href="akce.php?akce=presunout&folder=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-secondary" title="Přesunout složku"><i class="bi bi-arrows-move"></i></a>
                <?php endif; ?>
                    <a href="akce.php?akce=smazat&folder=<?= urlencode($folder . '/' . $item['name']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Opravdu smazat složku a všechny soubory v ní?');" title="Smazat složku"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<!-- Info o počtu souborů -->
<small>Celkem: <?= count($items); ?> položek</small>

<!-- JavaScript pro ovládání hromadných akcí -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const bulkActionsPanel = document.getElementById('bulkActionsPanel');
        const selectedCountSpan = document.getElementById('selectedCount');
        const bulkDeleteBtn = document.getElementById('bulkDelete');
        const bulkMoveBtn = document.getElementById('bulkMove');
        const bulkDownloadBtn = document.getElementById('bulkDownload');
        
        // Funkce pro aktualizaci počtu vybraných položek
        function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.item-checkbox:checked').length;
    selectedCountSpan.textContent = selectedCount;
    if (selectedCount > 0) {
        bulkActionsPanel.classList.remove('d-none');
    } else {
        bulkActionsPanel.classList.add('d-none');
    }
}

        
        // Výběr všech položek
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
        
        // Aktualizace při změně jednotlivých checkboxů
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        
        // Hromadné mazání
        bulkDeleteBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            
            if (confirm(`Opravdu chcete smazat ${selected.length} vybraných položek?`)) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'bulk_delete.php';
                
                selected.forEach(item => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'items[]';
                    input.value = item;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Hromadný přesun
        bulkMoveBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'bulk_move.php';
            
            selected.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = item;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        });
        
        // Hromadné stažení
        bulkDownloadBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'bulk_download.php';
            
            selected.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = item;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>