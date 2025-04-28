<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    set_notification('Nemáte oprávnění pro přístup k nastavení.', 'danger');
    header("Location: dashboard.php");
    exit;
}

// Získání statistik
$baseDir = realpath(__DIR__ . '/uploads');
$totalSize = 0;
$totalFiles = 0;
$totalFolders = 0;
$totalCapacity = 2 * 1024 * 1024 * 1024; // 2 GB v bajtech

// Definice cesty k zálohám
$backupDir = __DIR__ . '/backups';

// Vytvoření adresáře pro zálohy, pokud neexistuje
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Počítání složek - opraveno
function countFolders($dir) {
    $count = 0;
    $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
    
    foreach ($items as $item) {
        if ($item->isDir()) {
            $count++; // Počítáme tuto složku
            $count += countFolders($item->getPathname()); // Rekurzivně počítáme podsložky
        }
    }
    
    return $count;
}

// Funkce pro vyčištění dočasných souborů starších než 7 dní
function clean_temp_files($dir, $days = 7) {
    $now = time();
    $count = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            if ($now - $file->getMTime() > $days * 86400) {
                @unlink($file->getRealPath());
                if (!file_exists($file->getRealPath())) {
                    $count++;
                }
            }
        }
    }
    return $count;
}

// Funkce pro vytvoření zálohy
function create_backup($sourceDir, $backupDir) {
    $zipName = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zipPath = $backupDir . '/' . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    return $zipName;
}

// Funkce pro kontrolu integrity
function check_integrity($dir) {
    $errors = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (!$file->isReadable()) {
            $errors[] = $file->getRealPath();
        }
    }

    return $errors;
}

// Funkce pro výpis záloh
function list_backups($backupDir) {
    $backups = array_filter(glob($backupDir . '/*.zip'), 'is_file');
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return $backups;
}

// Funkce pro mazání starých záloh
function delete_old_backups($backupDir, $days = 30) {
    $now = time();
    $count = 0;

    foreach (glob($backupDir . '/*.zip') as $file) {
        if (is_file($file) && $now - filemtime($file) > $days * 86400) {
            if (unlink($file)) {
                $count++;
            }
        }
    }

    return $count;
}

// Funkce pro testování zápisu do adresáře
function test_write_permission($dir) {
    $testFile = $dir . '/write_test_' . time() . '.tmp';
    $result = false;
    
    // Pokus o zápis
    if ($handle = @fopen($testFile, 'w')) {
        fwrite($handle, 'Test zápisu');
        fclose($handle);
        
        // Pokus o čtení
        if (file_exists($testFile) && is_readable($testFile)) {
            $content = file_get_contents($testFile);
            $result = ($content === 'Test zápisu');
        }
        
        // Smazání testovacího souboru
        @unlink($testFile);
    }
    
    return $result;
}

// Funkce pro získání statistik záloh
function get_backup_stats($dir) {
    $stats = [
        'count' => 0,
        'total_size' => 0,
        'newest' => null,
        'oldest' => null
    ];
    
    if (!is_dir($dir)) {
        return $stats;
    }
    
    $files = glob($dir . '/*.zip');
    $stats['count'] = count($files);
    
    if ($stats['count'] > 0) {
        $newest_time = 0;
        $oldest_time = PHP_INT_MAX;
        $newest_file = '';
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            $mtime = filemtime($file);
            
            if ($mtime > $newest_time) {
                $newest_time = $mtime;
                $newest_file = $file;
                $stats['newest'] = [
                    'name' => basename($file),
                    'time' => $mtime,
                    'size' => filesize($file),
                    'path' => $file
                ];
            }
            
            if ($mtime < $oldest_time) {
                $oldest_time = $mtime;
                $stats['oldest'] = [
                    'name' => basename($file),
                    'time' => $mtime
                ];
            }
        }
    }
    
    return $stats;
}

// Funkce pro získání statistik typů souborů
function get_file_type_stats($dir) {
    // Definice kategorií souborů podle přípon
    $file_categories = [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'odt'],
        'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'code' => ['php', 'js', 'html', 'css', 'json', 'xml', 'py', 'java', 'c', 'cpp']
    ];
    
    // Inicializace počítadel pro každou kategorii
    $file_type_counts = [
        'images' => 0,
        'documents' => 0,
        'videos' => 0,
        'audio' => 0,
        'archives' => 0,
        'code' => 0,
        'others' => 0
    ];
    
    // Vytvoření mapování přípon na kategorie pro rychlejší vyhledávání
    $ext_to_category = [];
    foreach ($file_categories as $category => $extensions) {
        foreach ($extensions as $ext) {
            $ext_to_category[$ext] = $category;
        }
    }
    
    // Procházení všech souborů v adresáři rekurzivně
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        // Přeskočit složky a poznámkové soubory
        if ($file->isDir() || str_ends_with($file->getFilename(), '.note.json')) {
            continue;
        }
        
        // Získání přípony souboru
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        
        // Přiřazení souboru do kategorie podle přípony
        if (isset($ext_to_category[$ext])) {
            $category = $ext_to_category[$ext];
            $file_type_counts[$category]++;
        } else {
            $file_type_counts['others']++;
        }
    }
    
    return $file_type_counts;
}

// Zpracování akcí
$action = $_GET['action'] ?? '';
$message = '';

switch ($action) {
    case 'clean_temp':
        $count = clean_temp_files(sys_get_temp_dir());
        $message = "Vyčištěno $count dočasných souborů.";
        break;
        
    case 'backup':
        $zipName = create_backup($baseDir, $backupDir);
        if ($zipName) {
            $message = "Záloha byla vytvořena: $zipName";
        } else {
            $message = "Nepodařilo se vytvořit zálohu.";
        }
        break;
        
    case 'check_integrity':
        $errors = check_integrity($baseDir);
        if (empty($errors)) {
            $message = "Kontrola integrity proběhla v pořádku.";
        } else {
            $message = "Nalezeny problémy s přístupem k těmto souborům/složkám:<br>" . implode('<br>', $errors);
        }
        break;
        
    case 'delete_old_backups':
        $count = delete_old_backups($backupDir, 30);
        $message = "Smazáno $count záloh starších než 30 dní.";
        break;
        
    case 'delete_backup':
        $file = $_GET['file'] ?? '';
        $filePath = $backupDir . '/' . basename($file);

        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
            if (unlink($filePath)) {
                $message = "Záloha $file byla úspěšně smazána.";
            } else {
                $message = "Nepodařilo se smazat zálohu $file.";
            }
        } else {
            $message = "Neplatný soubor.";
        }
        break;
        
    case 'download_latest':
        $stats = get_backup_stats($backupDir);
        if ($stats['newest']) {
            $file = $stats['newest']['path'];
            $fileName = $stats['newest']['name'];
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        } else {
            $message = "Žádná záloha není k dispozici ke stažení.";
        }
        break;
        
    case 'test_write':
        $uploads_result = test_write_permission($baseDir);
        $backups_result = test_write_permission($backupDir);
        
        $message = "Test zápisu do adresáře uploads: " . ($uploads_result ? "OK" : "CHYBA") . "<br>";
        $message .= "Test zápisu do adresáře backups: " . ($backups_result ? "OK" : "CHYBA");
        break;
}

// Počítání souborů a velikosti
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isDir() && !str_ends_with($file->getFilename(), '.note.json')) {
        $totalFiles++;
        $totalSize += $file->getSize();
    }
}

// Počítání složek pomocí nové funkce
$totalFolders = countFolders($baseDir);

// Výpočet procent a volného místa - přesunuto sem
$freeSpace = $totalCapacity - $totalSize;
$usedPercent = round(($totalSize / $totalCapacity) * 100, 1);

// Získání statistik záloh pro zobrazení
$backupStats = get_backup_stats($backupDir);

// Získání statistik typů souborů
$fileTypeStats = get_file_type_stats($baseDir);

require_once __DIR__ . '/includes/header.php';

// Zobrazení zprávy, pokud existuje
if ($message) {
    echo '<div class="alert alert-info" role="alert">' . $message . '</div>';
}
?>

<h1 class="mb-4">Nastavení systému</h1>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Statistiky úložiště</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Celkový počet souborů:</th>
                        <td><?= $totalFiles ?></td>
                    </tr>
                    <tr>
                        <th>Celkový počet složek:</th>
                        <td><?= $totalFolders ?></td>
                    </tr>
                    <tr>
                        <th>Maximální velikost nahrávání:</th>
                        <td><?= format_bytes(min(
                            convert_to_bytes(ini_get('upload_max_filesize')),
                            convert_to_bytes(ini_get('post_max_size'))
                        )) ?></td>
                    </tr>
                    <tr>
                        <th>Celková velikost:</th>
                        <td><?= format_bytes($totalSize) ?> (<?= $usedPercent ?> %)</td>
                    </tr>
                    <tr>
                        <th>Celková kapacita úložiště:</th>
                        <td><?= format_bytes($totalCapacity) ?></td>
                    </tr>
                    <tr>
                        <th>Volné místo:</th>
                        <td><?= format_bytes($freeSpace) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Systémové informace</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>PHP verze:</th>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <th>Časová zóna:</th>
                        <td><?= date_default_timezone_get() ?></td>
                    </tr>
                    <tr>
                        <th>Operační systém:</th>
                        <td><?= php_uname() ?></td>
                    </tr>
                    <tr>
                        <th>ZIP rozšíření:</th>
                        <td><?= extension_loaded('zip') ? 'Povoleno' : 'Nepovoleno' ?></td>
                    </tr>
                    <tr>
                        <th>Nahrávání souborů:</th>
                        <td><?= ini_get('file_uploads') ? 'Povoleno' : 'Nepovoleno' ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Správa uživatelů</h5>
    </div>
    <div class="card-body">
        <p class="alert alert-info">
            Pro změnu uživatelů a hesel upravte soubor <code>includes/config.php</code>.
        </p>
        <table class="table">
            <thead>
                <tr>
                    <th>Heslo</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($USERS as $password => $role): ?>
                <tr>
                    <td><?= htmlspecialchars($password) ?></td>
                    <td><span class="badge bg-<?= $role === 'admin' ? 'danger' : 'primary' ?>"><?= htmlspecialchars($role) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Údržba systému</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <a href="?action=clean_temp" class="btn btn-warning" onclick="return confirm('Opravdu chcete vyčistit dočasné soubory?');">
                <i class="bi bi-broom"></i> Vyčistit dočasné soubory
            </a>
            <a href="?action=backup" class="btn btn-info">
                <i class="bi bi-cloud-arrow-up"></i> Vytvořit zálohu
            </a>
            <a href="?action=check_integrity" class="btn btn-primary">
                <i class="bi bi-shield-check"></i> Kontrola integrity
            </a>
            <a href="?action=delete_old_backups" class="btn btn-danger" onclick="return confirm('Opravdu chcete smazat zálohy starší než 30 dní?');">
                <i class="bi bi-trash"></i> Smazat staré zálohy
            </a>
            <a href="?action=test_write" class="btn btn-secondary">
                <i class="bi bi-pencil-square"></i> Test zápisu
            </a>
        </div>

        <?php if ($action === 'backup' || $action === '' || $action === 'delete_backup' || $action === 'delete_old_backups'): ?>
            <h5 class="mt-4">Přehled záloh</h5>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Název</th>
                        <th>Velikost</th>
                        <th>Datum vytvoření</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $backups = list_backups($backupDir);
                    foreach ($backups as $backup):
                        $filename = basename($backup);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($filename) ?></td>
                        <td><?= number_format(filesize($backup) / 1024 / 1024, 2) ?> MB</td>
                        <td><?= date('d.m.Y H:i:s', filemtime($backup)) ?></td>
                        <td>
                            <a href="backups/<?= urlencode($filename) ?>" class="btn btn-sm btn-success" download>
                                <i class="bi bi-download"></i> Stáhnout
                            </a>
                            <a href="?action=delete_backup&file=<?= urlencode($filename) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Opravdu chcete tuto zálohu smazat?');">
                                <i class="bi bi-trash"></i> Smazat
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Žádné zálohy nebyly nalezeny</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Využití úložiště</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="storageChart" width="400" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="fileTypesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graf využití úložiště
    const storageCtx = document.getElementById('storageChart').getContext('2d');
    const storageChart = new Chart(storageCtx, {
        type: 'doughnut',
        data: {
            labels: ['Využito', 'Volné místo'],
            datasets: [{
                data: [<?= $totalSize ?>, <?= $totalCapacity - $totalSize ?>],
                backgroundColor: ['#0d6efd', '#e9ecef']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Využití úložiště'
                }
            }
        }
    });
    
    // Graf typů souborů
    const fileTypesCtx = document.getElementById('fileTypesChart').getContext('2d');
    const fileTypesChart = new Chart(fileTypesCtx, {
        type: 'bar',
        data: {
            labels: ['Obrázky', 'Dokumenty', 'Video', 'Audio', 'Archivy', 'Kód', 'Ostatní'],
            datasets: [{
                label: 'Počet souborů podle typu',
                data: [
                    <?= $fileTypeStats['images'] ?>,
                    <?= $fileTypeStats['documents'] ?>,
                    <?= $fileTypeStats['videos'] ?>,
                    <?= $fileTypeStats['audio'] ?>,
                    <?= $fileTypeStats['archives'] ?>,
                    <?= $fileTypeStats['code'] ?>,
                    <?= $fileTypeStats['others'] ?>
                ],
                backgroundColor: [
                    '#4BC0C0', // Obrázky - tyrkysová
                    '#36A2EB', // Dokumenty - modrá
                    '#FF6384', // Video - růžová
                    '#FFCD56', // Audio - žlutá
                    '#FF9F40', // Archivy - oranžová
                    '#9966FF', // Kód - fialová
                    '#C9CBCF'  // Ostatní - šedá
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Typy souborů'
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw + ' souborů';
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
