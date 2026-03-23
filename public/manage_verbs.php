<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_role('teacher');
if (is_post()) {
    verify_csrf();
    db_query('INSERT INTO verbs (teacher_id, class_id, level, infinitive, translation, group_name, created_at) VALUES (:teacher_id, :class_id, :level, :infinitive, :translation, :group_name, :created_at)', [
        'teacher_id' => $user['id'],
        'class_id' => $user['class_id'],
        'level' => max(1, (int)($_POST['level'] ?? 1)),
        'infinitive' => trim($_POST['infinitive'] ?? ''),
        'translation' => trim($_POST['translation'] ?? ''),
        'group_name' => trim($_POST['group_name'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $verbId = (int)db()->lastInsertId();
    foreach (['je','tu','il/elle','nous','vous','ils/elles'] as $pronoun) {
        $field = str_replace(['/','\'',' '], ['_','',''], $pronoun);
        $value = trim($_POST['form_' . $field] ?? '');
        if ($value !== '') {
            db_query('INSERT INTO conjugations (verb_id, tense, pronoun, conjugated_form) VALUES (:verb_id, :tense, :pronoun, :form)', [
                'verb_id' => $verbId,
                'tense' => trim($_POST['tense'] ?? 'présent'),
                'pronoun' => $pronoun,
                'form' => $value,
            ]);
        }
    }
    flash_set('success', 'Werkwoord toegevoegd.');
    redirect('manage_verbs.php');
}
$verbs = db_query('SELECT * FROM verbs WHERE teacher_id = :id ORDER BY level, infinitive', ['id' => $user['id']])->fetchAll();
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h1>Werkwoorden beheren</h1>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Infinitief</label><input name="infinitive" required>
            <label>Vertaling</label><input name="translation" required>
            <label>Groep</label><input name="group_name" placeholder="bv. -er of onregelmatig">
            <label>Niveau</label><input type="number" min="1" max="10" name="level" value="1" required>
            <label>Tijd</label><input name="tense" value="présent" required>
            <label>je</label><input name="form_je">
            <label>tu</label><input name="form_tu">
            <label>il/elle</label><input name="form_il_elle">
            <label>nous</label><input name="form_nous">
            <label>vous</label><input name="form_vous">
            <label>ils/elles</label><input name="form_ils_elles">
            <button type="submit">Toevoegen</button>
        </form>
    </div>
    <div class="card">
        <h2>Bestaande werkwoorden</h2>
        <table class="table"><thead><tr><th>Niveau</th><th>Infinitief</th><th>Vertaling</th><th>Groep</th></tr></thead><tbody>
        <?php foreach ($verbs as $verb): ?><tr><td><?= e((string)$verb['level']) ?></td><td><?= e($verb['infinitive']) ?></td><td><?= e($verb['translation']) ?></td><td><?= e($verb['group_name']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
