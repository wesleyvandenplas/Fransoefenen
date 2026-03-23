<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_role('teacher');
if (is_post()) {
    verify_csrf();
    $forms = ['flashcards', 'multiple_choice', 'typing', 'conjugation'];
    $data = ['class_id' => $user['class_id']];
    foreach ($forms as $form) {
        $data[$form] = isset($_POST['forms'][$form]) ? 1 : 0;
    }
    db_query('INSERT INTO class_workforms (class_id, flashcards, multiple_choice, typing, conjugation)
              VALUES (:class_id, :flashcards, :multiple_choice, :typing, :conjugation)
              ON CONFLICT(class_id) DO UPDATE SET flashcards = excluded.flashcards, multiple_choice = excluded.multiple_choice, typing = excluded.typing, conjugation = excluded.conjugation', $data);
    flash_set('success', 'Werkvormen opgeslagen.');
    redirect('manage_workforms.php');
}
$current = db_query('SELECT * FROM class_workforms WHERE class_id = :class_id', ['class_id' => $user['class_id']])->fetch() ?: ['flashcards' => 1, 'multiple_choice' => 1, 'typing' => 1, 'conjugation' => 1];
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
    <h1>Werkvormen voor jouw klas</h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><input type="checkbox" name="forms[flashcards]" <?= $current['flashcards'] ? 'checked' : '' ?>> Flashcards</label><br>
        <label><input type="checkbox" name="forms[multiple_choice]" <?= $current['multiple_choice'] ? 'checked' : '' ?>> Meerkeuze</label><br>
        <label><input type="checkbox" name="forms[typing]" <?= $current['typing'] ? 'checked' : '' ?>> Zelf typen</label><br>
        <label><input type="checkbox" name="forms[conjugation]" <?= $current['conjugation'] ? 'checked' : '' ?>> Vervoegingen</label><br><br>
        <button type="submit">Opslaan</button>
    </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
