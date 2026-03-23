<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
$config = app_config();
$dbPath = $config['db_path'];
$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
db()->exec($sql);

$existing = db_query('SELECT COUNT(*) AS total FROM users')->fetch()['total'] ?? 0;
if ((int)$existing === 0) {
    db_query('INSERT INTO classes (name, school_year, created_at) VALUES (:name, :school_year, :created_at)', [
        'name' => '6A',
        'school_year' => current_school_year(),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $classId = (int)db()->lastInsertId();
    db_query('INSERT INTO class_workforms (class_id) VALUES (:class_id)', ['class_id' => $classId]);

    $users = [
        ['admin', 'Beheerder Demo', 'admin', 'admin123', null],
        ['teacher', 'Leraar Demo', 'leraar1', 'Welkom123!', $classId],
        ['student', 'Leerling Demo', 'leerling1', 'Welkom123!', $classId],
    ];
    foreach ($users as [$role, $name, $username, $password, $classIdValue]) {
        db_query('INSERT INTO users (role, full_name, username, password_hash, class_id, created_at) VALUES (:role, :full_name, :username, :password_hash, :class_id, :created_at)', [
            'role' => $role,
            'full_name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'class_id' => $classIdValue,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($role === 'student') {
            db_query('INSERT INTO student_levels (student_id, level, updated_at) VALUES (:student_id, 1, :updated_at)', [
                'student_id' => (int)db()->lastInsertId(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    db_query('INSERT INTO vocabulary (teacher_id, class_id, level, theme, french_word, dutch_word, created_at) VALUES
        (2, :class_id, 1, "Klas", "la chaise", "de stoel", :created_at),
        (2, :class_id, 1, "Klas", "la table", "de tafel", :created_at),
        (2, :class_id, 2, "Eten", "le pain", "het brood", :created_at),
        (2, :class_id, 2, "Eten", "le fromage", "de kaas", :created_at)', [
        'class_id' => $classId,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    db_query('INSERT INTO verbs (teacher_id, class_id, level, infinitive, translation, group_name, created_at) VALUES
        (2, :class_id, 1, "être", "zijn", "onregelmatig", :created_at),
        (2, :class_id, 1, "avoir", "hebben", "onregelmatig", :created_at),
        (2, :class_id, 2, "aimer", "houden van", "-er", :created_at)', [
        'class_id' => $classId,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $verbIds = db_query('SELECT id, infinitive FROM verbs')->fetchAll();
    $conjugations = [
        'être' => ['je' => 'suis', 'tu' => 'es', 'il/elle' => 'est', 'nous' => 'sommes', 'vous' => 'êtes', 'ils/elles' => 'sont'],
        'avoir' => ['j\'' => 'ai', 'tu' => 'as', 'il/elle' => 'a', 'nous' => 'avons', 'vous' => 'avez', 'ils/elles' => 'ont'],
        'aimer' => ['j\'' => 'aime', 'tu' => 'aimes', 'il/elle' => 'aime', 'nous' => 'aimons', 'vous' => 'aimez', 'ils/elles' => 'aiment'],
    ];
    foreach ($verbIds as $verb) {
        foreach ($conjugations[$verb['infinitive']] as $pronoun => $form) {
            db_query('INSERT INTO conjugations (verb_id, tense, pronoun, conjugated_form) VALUES (:verb_id, "présent", :pronoun, :form)', [
                'verb_id' => $verb['id'],
                'pronoun' => $pronoun,
                'form' => $form,
            ]);
        }
    }
}
?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>Setup voltooid</title><link rel="stylesheet" href="../assets/style.css"></head><body><main class="container"><div class="card"><h1>Setup voltooid</h1><p>Databasebestand: <strong><?= e($dbPath) ?></strong></p><p>Demo-accounts:</p><ul><li>admin / admin123</li><li>leraar1 / Welkom123!</li><li>leerling1 / Welkom123!</li></ul><p><a class="button" href="index.php">Ga naar login</a></p></div></main></body></html>
