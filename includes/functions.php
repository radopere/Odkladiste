<?php
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}


// Vyhledávání souborů podle názvu nebo poznámky
function search_files($searchTerm, $directory = '', $searchEverywhere = false) {
    $baseDir = realpath(__DIR__ . '/../uploads');
    $searchDir = $directory ? realpath($baseDir . '/' . $directory) : $baseDir;
    
    if (!$searchDir || strpos($searchDir, $baseDir) !== 0) {
        return [];
    }
    
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        // Přeskočit složky a poznámkové soubory
        if ($file->isDir() || str_ends_with($file->getFilename(), '.note.json')) {
            continue;
        }
        
        $filePath = $file->getPathname();
        $relativePath = substr($filePath, strlen($baseDir) + 1);
        $fileName = $file->getFilename();
        $fileDir = dirname($relativePath);
        
        // Pokud nehledáme všude, kontrolujeme, zda je soubor v aktuální složce
        if (!$searchEverywhere) {
            // Pokud je fileDir ".", znamená to, že soubor je v kořenovém adresáři
            $currentDir = $fileDir === '.' ? '' : $fileDir;
            if ($currentDir !== $directory) {
                continue;
            }
        }
        
        // Kontrola názvu souboru
        if (stripos($fileName, $searchTerm) !== false) {
            $results[] = [
                'name' => $fileName,
                'path' => $relativePath,
                'directory' => $fileDir === '.' ? '' : $fileDir,
                'is_dir' => false,
                'size' => $file->getSize(),
                'match_type' => 'name'
            ];
            continue;
        }
        
        // Kontrola poznámky
        $notePath = $filePath . '.note.json';
        if (file_exists($notePath)) {
            $noteData = json_decode(file_get_contents($notePath), true);
            $note = $noteData['note'] ?? '';
            
            if (stripos($note, $searchTerm) !== false) {
                $results[] = [
                    'name' => $fileName,
                    'path' => $relativePath,
                    'directory' => $fileDir === '.' ? '' : $fileDir,
                    'is_dir' => false,
                    'size' => $file->getSize(),
                    'note' => $note,
                    'match_type' => 'note'
                ];
            }
        }
    }
    
    return $results;
}

// Systém notifikací
function set_notification($message, $type = 'success') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_notification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

// Získání všech souborů a složek v adresáři
function get_directory_contents($path = '') {
    $baseDir = realpath(__DIR__ . '/../uploads');
    $fullPath = $path ? realpath($baseDir . '/' . $path) : $baseDir;
    
    // Ochrana proti přístupu mimo uploads
    if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
        return [];
    }
    
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
            'modified' => filemtime($filePath),
            'note' => $note
        ];
    }
    
    // Seřazení: nejdřív složky, pak soubory
    usort($items, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $items;
}

// Rekurzivní mazání složky
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

// Vytvoření ZIP archivu složky
function create_zip_archive($folder) {
    $baseDir = realpath(__DIR__ . '/../uploads');
    $targetDir = realpath($baseDir . '/' . $folder);
    
    if (!$targetDir || strpos($targetDir, $baseDir) !== 0 || !is_dir($targetDir)) {
        return false;
    }
    
    $zipName = basename($folder) ?: 'archiv';
    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        return false;
    }
    
    $baseLength = strlen($targetDir) + 1;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetDir));
    
    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        
        $filePath = $file->getPathname();
        
        // Přeskakujeme poznámky
        if (str_ends_with($filePath, '.note.json')) continue;
        
        $localPath = substr($filePath, $baseLength);
        $zip->addFile($filePath, $localPath);
    }
    
    $zip->close();
    
    return [
        'path' => $zipFile,
        'name' => $zipName . '.zip'
    ];
}

function convert_to_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    
    return $val;
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

// Funkce pro zjištění, zda je soubor sdílený
function is_file_shared($filePath) {
    $sharePath = __DIR__ . '/shares';
    
    if (!file_exists($sharePath)) {
        return false;
    }
    
    $shareFiles = glob($sharePath . '/*.json');
    
    foreach ($shareFiles as $shareFile) {
        $data = json_decode(file_get_contents($shareFile), true);
        
        if ($data['file'] === $filePath) {
            // Kontrola expirace
            if ($data['expiry'] > 0 && time() > $data['expiry']) {
                continue; // Sdílení vypršelo
            }
            return true;
        }
    }
    
    return false;
}
