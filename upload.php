<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$baseDir = realpath(__DIR__ . '/uploads');
$folder = $_GET['folder'] ?? '';
$targetDir = realpath($baseDir . '/' . $folder);

// Bezpečnostní kontrola
if (!$targetDir || strpos($targetDir, $baseDir) !== 0) {
    set_notification('Neplatná cesta.', 'danger');
    header("Location: dashboard.php");
    exit;
}

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
            $errorCount++;
            continue;
        }
        
        $originalName = basename($_FILES['files']['name'][$i]);
        $targetPath = $targetDir . '/' . $originalName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            file_put_contents($targetPath . '.note.json', json_encode(['note' => '']));
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    if ($successCount > 0) {
        $message = "Úspěšně nahráno $successCount " . 
                   ($successCount === 1 ? 'soubor' : ($successCount < 5 ? 'soubory' : 'souborů')) . '.';
        
        if ($errorCount > 0) {
            $message .= " Nepodařilo se nahrát $errorCount " . 
                       ($errorCount === 1 ? 'soubor' : ($errorCount < 5 ? 'soubory' : 'souborů')) . '.';
        }
        
        set_notification($message, 'success');
    } elseif ($errorCount > 0) {
        set_notification("Nepodařilo se nahrát žádné soubory.", 'danger');
    }
    
    header("Location: dashboard.php?folder=" . urlencode($folder));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="mb-4">Nahrát soubory</h1>

<div class="card">
    <div class="card-body">
        <form action="upload.php?folder=<?= urlencode($folder) ?>" method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="mb-4 dropzone" id="dropZone">
                <i class="bi bi-cloud-arrow-up display-4"></i>
                <p class="mb-0">Přetáhněte soubory sem nebo klikněte pro výběr</p>
                <input type="file" id="fileInput" name="files[]" multiple style="display: none;">
                
            </div>
            
    <button type="submit" class="btn btn-primary w-100 mx-auto d-block" id="uploadButton" disabled><i class="bi bi-upload"></i> Nahrát</button>


            
            <div id="fileList" class="mb-3 mt-3"></div>
            
            <div class="progress mb-3" style="display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </form>
        <a href="dashboard.php?folder=<?= urlencode($folder) ?>" class="btn btn-secondary me-auto"><i class="bi bi-arrow-left"></i> Zpět</a>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');
const uploadButton = document.getElementById('uploadButton');
const uploadForm = document.getElementById('uploadForm');
const progressBar = document.querySelector('.progress-bar');
const progress = document.querySelector('.progress');

// Kliknutí na dropzone otevře dialog pro výběr souborů
dropZone.addEventListener('click', () => fileInput.click());

// Drag & Drop události
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('highlight');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('highlight');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('highlight');
    
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        updateFileList();
    }
});

// Aktualizace seznamu vybraných souborů
fileInput.addEventListener('change', updateFileList);

function updateFileList() {
    fileList.innerHTML = '';
    
    if (fileInput.files.length > 0) {
        const list = document.createElement('ul');
        list.className = 'list-group';
        
        for (const file of fileInput.files) {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <i class="bi bi-file-earmark me-2"></i>
                    ${file.name}
                </div>
                <span class="badge bg-secondary">${formatFileSize(file.size)}</span>
            `;
            list.appendChild(item);
        }
        
        fileList.appendChild(list);
        uploadButton.disabled = false;
    } else {
        uploadButton.disabled = true;
    }
}

// Formátování velikosti souboru
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Zobrazení průběhu nahrávání
uploadForm.addEventListener('submit', (e) => {
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Vyberte alespoň jeden soubor k nahrání.');
        return;
    }
    
    // Zobrazení průběhu nahrávání by vyžadovalo AJAX, což je nad rámec této ukázky
    uploadButton.disabled = true;
    uploadButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Nahrávání...';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
