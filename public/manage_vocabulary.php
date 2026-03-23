<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_role('teacher');
if (is_post()) {
    verify_csrf();
    db_query('INSERT INTO vocabulary (teacher_id, class_id, level, theme, french_word, dutch_word, created_at) VALUES (:teacher_id, :class_id, :level, :theme, :french_word, :dutch_word, :created_at)', [
        'teacher_id' => $user['id'],
        'class_id' => $user['class_id'],
        'level' => max(1, (int)($_POST['level'] ?? 1)),
        'theme' => trim($_POST['theme'] ?? ''),
        'french_word' => trim($_POST['french_word'] ?? ''),
        'dutch_word' => trim($_POST['dutch_word'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    flash_set('success', 'Woord toegevoegd.');
    redirect('manage_vocabulary.php');
}
$words = db_query('SELECT * FROM vocabulary WHERE teacher_id = :id ORDER BY level, theme, french_word', ['id' => $user['id']])->fetchAll();
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h1>Woordenschat beheren</h1>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Thema</label><input name="theme" required>
            <label>Niveau</label><input type="number" min="1" max="10" name="level" value="1" required>
            <label>Frans woord</label><input name="french_word" required>
            <label>Nederlandse vertaling</label><input name="dutch_word" required>
            <button type="submit">Toevoegen</button>
        </form>
    </div>
    <div class="card">
        <h2>Bestaande woorden</h2>
        <table class="table"><thead><tr><th>Niveau</th><th>Thema</th><th>Frans</th><th>Nederlands</th></tr></thead><tbody>
        <?php foreach ($words as $word): ?><tr><td><?= e((string)$word['level']) ?></td><td><?= e($word['theme']) ?></td><td><?= e($word['french_word']) ?></td><td><?= e($word['dutch_word']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
