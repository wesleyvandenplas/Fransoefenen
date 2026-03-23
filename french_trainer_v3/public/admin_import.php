<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
$user = require_role('admin');
$report = [];
if (is_post() && isset($_FILES['csv'])) {
    verify_csrf();
    $type = $_POST['import_type'] ?? 'users';
    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    if ($handle) {
        $header = fgetcsv($handle, 0, ';') ?: [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $data = array_combine($header, $row);
            if (!$data) { continue; }
            if ($type === 'classes') {
                db_query('INSERT OR IGNORE INTO classes (name, school_year, created_at) VALUES (:name, :school_year, :created_at)', [
                    'name' => trim($data['name'] ?? ''),
                    'school_year' => trim($data['school_year'] ?? current_school_year()),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $class = db_query('SELECT id FROM classes WHERE name = :name', ['name' => trim($data['name'] ?? '')])->fetch();
                if ($class) {
                    db_query('INSERT OR IGNORE INTO class_workforms (class_id) VALUES (:class_id)', ['class_id' => $class['id']]);
                }
                $report[] = 'Klas verwerkt: ' . ($data['name'] ?? '');
            } else {
                $classId = null;
                if (!empty($data['class_name'])) {
                    $class = db_query('SELECT id FROM classes WHERE name = :name', ['name' => trim($data['class_name'])])->fetch();
                    if ($class) { $classId = (int)$class['id']; }
                }
                db_query('INSERT OR IGNORE INTO users (role, full_name, username, password_hash, class_id, created_at) VALUES (:role, :full_name, :username, :password_hash, :class_id, :created_at)', [
                    'role' => trim($data['role'] ?? 'student'),
                    'full_name' => trim($data['full_name'] ?? ''),
                    'username' => trim($data['username'] ?? ''),
                    'password_hash' => password_hash(trim($data['password'] ?? 'Welkom123!'), PASSWORD_DEFAULT),
                    'class_id' => $classId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $newUser = db_query('SELECT id, role FROM users WHERE username = :username', ['username' => trim($data['username'] ?? '')])->fetch();
                if ($newUser && $newUser['role'] === 'student') {
                    db_query('INSERT OR IGNORE INTO student_levels (student_id, level, updated_at) VALUES (:student_id, :level, :updated_at)', [
                        'student_id' => (int)$newUser['id'],
                        'level' => max(1, (int)($data['level'] ?? 1)),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $report[] = 'Gebruiker verwerkt: ' . ($data['username'] ?? '');
            }
        }
        fclose($handle);
        flash_set('success', 'CSV import verwerkt.');
    }
}
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h1>CSV bulkimport</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Type import</label>
            <select name="import_type">
                <option value="users">Gebruikers</option>
                <option value="classes">Klassen</option>
            </select>
            <label>CSV-bestand (puntkomma gescheiden)</label>
            <input type="file" name="csv" accept=".csv" required>
            <button type="submit">Importeer CSV</button>
        </form>
        <h3>Voorbeeld gebruikers CSV</h3>
        <pre class="csv">role;full_name;username;password;class_name;level
student;Marie Dupont;marie.d;Welkom123!;6A;2
teacher;Meester Jan;jan.v;Welkom123!;6A;1</pre>
        <h3>Voorbeeld klassen CSV</h3>
        <pre class="csv">name;school_year
6A;2025-2026
6B;2025-2026</pre>
    </div>
    <div class="card">
        <h2>Importverslag</h2>
        <?php if (!$report): ?><p class="small">Nog geen import uitgevoerd in deze sessie.</p><?php endif; ?>
        <?php foreach ($report as $line): ?><div class="tag"><?= e($line) ?></div><?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
