<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_role('teacher');
if (is_post()) {
    verify_csrf();
    foreach ($_POST['level'] ?? [] as $studentId => $level) {
        $level = max(1, min(10, (int)$level));
        db_query('INSERT INTO student_levels (student_id, level, updated_at) VALUES (:student_id, :level, :updated_at)
                  ON CONFLICT(student_id) DO UPDATE SET level = excluded.level, updated_at = excluded.updated_at', [
            'student_id' => (int)$studentId,
            'level' => $level,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    flash_set('success', 'Niveaus opgeslagen.');
    redirect('manage_levels.php');
}
$students = db_query('SELECT u.id, u.full_name, u.username, COALESCE(sl.level,1) AS level FROM users u LEFT JOIN student_levels sl ON sl.student_id = u.id WHERE u.role = "student" AND u.class_id = :class_id ORDER BY u.full_name', ['class_id' => $user['class_id']])->fetchAll();
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
    <h1>Niveaus per leerling</h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <table class="table"><thead><tr><th>Leerling</th><th>Gebruikersnaam</th><th>Niveau</th></tr></thead><tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?= e($student['full_name']) ?></td>
                <td><?= e($student['username']) ?></td>
                <td><input type="number" min="1" max="10" name="level[<?= (int)$student['id'] ?>]" value="<?= e((string)$student['level']) ?>"></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
        <button type="submit">Opslaan</button>
    </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
