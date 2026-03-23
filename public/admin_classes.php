<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
$user = require_role('admin');
if (is_post()) {
    verify_csrf();
    db_query('INSERT INTO classes (name, school_year, created_at) VALUES (:name, :school_year, :created_at)', [
        'name' => trim($_POST['name'] ?? ''),
        'school_year' => trim($_POST['school_year'] ?? current_school_year()),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $classId = (int)db()->lastInsertId();
    db_query('INSERT INTO class_workforms (class_id) VALUES (:class_id)', ['class_id' => $classId]);
    flash_set('success', 'Klas aangemaakt.');
    redirect('admin_classes.php');
}
$classes = db_query('SELECT * FROM classes ORDER BY school_year DESC, name ASC')->fetchAll();
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h1>Klassen beheren</h1>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Klasnaam</label><input name="name" placeholder="bv. 5A" required>
            <label>Schooljaar</label><input name="school_year" value="<?= e(current_school_year()) ?>" required>
            <button type="submit">Klas toevoegen</button>
        </form>
    </div>
    <div class="card"><h2>Bestaande klassen</h2><table class="table"><thead><tr><th>Naam</th><th>Schooljaar</th></tr></thead><tbody><?php foreach ($classes as $class): ?><tr><td><?= e($class['name']) ?></td><td><?= e($class['school_year']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
