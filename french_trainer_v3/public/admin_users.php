<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_role('admin');
if (is_post()) {
    verify_csrf();
    db_query('INSERT INTO users (role, full_name, username, password_hash, class_id, created_at) VALUES (:role, :full_name, :username, :password_hash, :class_id, :created_at)', [
        'role' => $_POST['role'],
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password_hash' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
        'class_id' => $_POST['class_id'] !== '' ? (int)$_POST['class_id'] : null,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $userId = (int)db()->lastInsertId();
    if (($_POST['role'] ?? '') === 'student') {
        db_query('INSERT INTO student_levels (student_id, level, updated_at) VALUES (:student_id, 1, :updated_at)', [
            'student_id' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    flash_set('success', 'Gebruiker aangemaakt.');
    redirect('admin_users.php');
}
$classes = db_query('SELECT * FROM classes ORDER BY name')->fetchAll();
$users = db_query('SELECT u.*, c.name AS class_name FROM users u LEFT JOIN classes c ON c.id = u.class_id ORDER BY u.role, u.full_name')->fetchAll();
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h1>Gebruikers beheren</h1>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Rol</label>
            <select name="role"><option value="student">Leerling</option><option value="teacher">Leraar</option><option value="admin">Beheerder</option></select>
            <label>Volledige naam</label><input name="full_name" required>
            <label>Gebruikersnaam</label><input name="username" required>
            <label>Wachtwoord</label><input type="text" name="password" required>
            <label>Klas</label>
            <select name="class_id"><option value="">Geen klas</option><?php foreach ($classes as $class): ?><option value="<?= (int)$class['id'] ?>"><?= e($class['name']) ?></option><?php endforeach; ?></select>
            <button type="submit">Gebruiker toevoegen</button>
        </form>
    </div>
    <div class="card"><h2>Bestaande gebruikers</h2><table class="table"><thead><tr><th>Naam</th><th>Rol</th><th>Gebruikersnaam</th><th>Klas</th></tr></thead><tbody><?php foreach ($users as $row): ?><tr><td><?= e($row['full_name']) ?></td><td><?= e($row['role']) ?></td><td><?= e($row['username']) ?></td><td><?= e($row['class_name']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
