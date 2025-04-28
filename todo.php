<?php

require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    set_notification('Nemáte oprávnění pro přístup k nastavení.', 'danger');
    header("Location: dashboard.php");
    exit;
}


// Cesta k souboru s úkoly

$todoFile = __DIR__ . '/includes/todo/todo_' . md5($_SESSION['user'] ?? '') . '.json';



$todoDir = dirname($todoFile);



// Vytvoření adresáře, pokud neexistuje

if (!file_exists($todoDir)) {

    mkdir($todoDir, 0755, true);

}



// Načtení úkolů

$todos = [];

if (file_exists($todoFile)) {

    $todos = json_decode(file_get_contents($todoFile), true) ?: [];

}



// Zpracování akcí

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    

    if ($action === 'add' && !empty($_POST['task'])) {

        // Přidání nového úkolu

        $todos[] = [

            'id' => uniqid(),

            'text' => $_POST['task'],

            'completed' => false,

            'created' => time()

        ];

        set_notification('Úkol byl přidán.', 'success');

    } 

    elseif ($action === 'toggle' && !empty($_POST['id'])) {

        // Označení úkolu jako dokončeného/nedokončeného

        foreach ($todos as &$todo) {

            if ($todo['id'] === $_POST['id']) {

                $todo['completed'] = !$todo['completed'];

                break;

            }

        }

    } 

    elseif ($action === 'delete' && !empty($_POST['id'])) {

        // Smazání úkolu

        foreach ($todos as $key => $todo) {

            if ($todo['id'] === $_POST['id']) {

                unset($todos[$key]);

                break;

            }

        }

        $todos = array_values($todos); // Reindexace pole

        set_notification('Úkol byl smazán.', 'success');

    }

    

    // Uložení úkolů

    file_put_contents($todoFile, json_encode($todos));

    

    // Přesměrování pro zabránění opakovanému odeslání formuláře

    header("Location: todo.php");

    exit;

}



require_once __DIR__ . '/includes/header.php';

?>



<div class="d-flex justify-content-between align-items-left mb-4">

    <h1>Moje úkoly</h1>

</div>



<div class="row">

    <form method="post" class="d-flex p-2">

                                    <input type="hidden" name="action" value="add">

                                    <input type="text" name="task" class="form-control me-2" placeholder="Co potřebujete udělat?" required autofocus>

                                    <button type="submit" class="btn btn-primary">Přidat</button>

                                </form>

        <!-- Tabulka s úkoly -->

                <table class="table table-bordered table-hover lh-lg">

                    <thead class="table-light">

                        <tr>

                            <th>Úkol</th>

                            <th>Vytvořeno</th>

                            <th class="text-end">Akce</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($todos)): ?>

                        <tr>

                            <td colspan="3" class="text-center text-muted">Žádné úkoly k zobrazení. Přidejte nový úkol výše.</td>

                        </tr>

                        <?php else: ?>

                        <?php foreach ($todos as $todo): ?>

                        <tr>

                            <td>

                                <form method="post" class="d-inline">

                                    <input type="hidden" name="action" value="toggle">

                                    <input type="hidden" name="id" value="<?= htmlspecialchars($todo['id']) ?>">

                                    <button type="submit" class="btn btn-sm <?= $todo['completed'] ? 'btn-success' : 'btn-outline-secondary' ?> me-2">

                                        <i class="bi <?= $todo['completed'] ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>

                                    </button>

                                </form>

                                <span class="<?= $todo['completed'] ? 'text-decoration-line-through text-muted' : '' ?>">

                                    <?= htmlspecialchars($todo['text']) ?>

                                </span>

                            </td>

                            <td><small class="text-muted"><?= date('d.m.Y H:i', $todo['created']) ?></small></td>

                            <td class="text-end">

                                <form method="post" class="d-inline">

                                    <input type="hidden" name="action" value="delete">

                                    <input type="hidden" name="id" value="<?= htmlspecialchars($todo['id']) ?>">

                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Opravdu smazat tento úkol?');">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

        </div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>