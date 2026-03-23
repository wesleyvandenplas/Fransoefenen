<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/stats.php';
$user = require_login();
require __DIR__ . '/../templates/header.php';

if ($user['role'] === 'student') {
    $stats = get_student_stats($user['id']);
    $level = get_student_level($user['id']);
    $workforms = get_available_workforms_for_class($user['class_id']);
    $recent = db_query('SELECT * FROM exercise_attempts WHERE student_id = :id ORDER BY attempted_at DESC LIMIT 8', ['id' => $user['id']])->fetchAll();
    $weekly = get_student_weekly_activity($user['id']);
    $leaderboard = $user['class_id'] ? get_class_leaderboard($user['class_id']) : [];
    $themes = get_student_theme_breakdown($user['id']);
    $accuracy = accuracy_percentage($stats['correct'], $stats['total']);
    ?>
    <section class="hero hero-student">
        <div class="card spotlight">
            <span class="eyebrow">Leerlingendashboard</span>
            <h1>Welkom terug, <?= e($user['full_name']) ?></h1>
            <p>Je werkt momenteel op <strong><?= e(level_label($level)) ?></strong>. Kies een oefenvorm, bouw punten op en volg je groei zoals in een moderne Studyflow-omgeving.</p>
            <div class="action-row">
                <a class="button" href="exercise.php?type=vocabulary">Start woordenschat</a>
                <a class="button secondary" href="exercise.php?type=verbs">Start werkwoorden</a>
                <a class="button secondary" href="student_progress.php">Bekijk mijn rapport</a>
            </div>
            <div class="tag-row">
                <?php foreach ($workforms as $form): ?>
                    <span class="tag"><?= e($form) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card stat-card-grid">
            <div class="mini-stat"><span>Punten</span><strong><?= $stats['points'] ?></strong></div>
            <div class="mini-stat"><span>Streak</span><strong><?= $stats['streak'] ?></strong></div>
            <div class="mini-stat"><span>Oefeningen</span><strong><?= $stats['total'] ?></strong></div>
            <div class="mini-stat"><span>Juist</span><strong><?= $accuracy ?>%</strong></div>
        </div>
    </section>

    <section class="grid grid-2 section-gap">
        <div class="card soft-panel">
            <div class="section-head"><h2>Jouw activiteit</h2><span class="small">Laatste 7 oefendagen</span></div>
            <?php if ($weekly): ?>
                <div class="activity-list">
                    <?php foreach ($weekly as $day): $dayAccuracy = accuracy_percentage((int)$day['correct'], (int)$day['total']); ?>
                        <div class="activity-item">
                            <div>
                                <strong><?= e($day['day']) ?></strong>
                                <div class="small"><?= (int)$day['total'] ?> pogingen · <?= $dayAccuracy ?>% juist</div>
                            </div>
                            <div class="progress"><span style="width: <?= $dayAccuracy ?>%"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="small">Nog geen oefenactiviteit geregistreerd.</p>
            <?php endif; ?>
        </div>
        <div class="card soft-panel">
            <div class="section-head"><h2>Klasranking</h2><span class="small">Top 5 op punten</span></div>
            <?php if ($leaderboard): ?>
                <ol class="leaderboard">
                    <?php foreach ($leaderboard as $row): ?>
                        <li>
                            <span><?= e($row['full_name']) ?></span>
                            <strong><?= (int)$row['points'] ?> pt</strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="small">Geen klasranking beschikbaar.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid grid-2 section-gap">
        <div class="card soft-panel">
            <div class="section-head"><h2>Beste thema's</h2><span class="small">Gebaseerd op jouw oefenhistoriek</span></div>
            <?php if ($themes): ?>
                <div class="theme-breakdown">
                    <?php foreach (array_slice($themes, 0, 5) as $theme): $themeAccuracy = accuracy_percentage((int)$theme['correct'], (int)$theme['attempts']); ?>
                        <div class="theme-row">
                            <div>
                                <strong><?= e($theme['label']) ?></strong>
                                <div class="small"><?= (int)$theme['attempts'] ?> pogingen</div>
                            </div>
                            <div class="theme-metrics">
                                <span><?= $themeAccuracy ?>%</span>
                                <div class="progress"><span style="width: <?= $themeAccuracy ?>%"></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="small">Nog geen thema-overzicht beschikbaar.</p>
            <?php endif; ?>
        </div>
        <div class="card soft-panel">
            <div class="section-head"><h2>Snelle acties</h2><span class="small">Werk vlot verder</span></div>
            <div class="action-stack">
                <a class="button" href="exercise.php?type=vocabulary&mode=flashcards">Herhaal met flashcards</a>
                <a class="button secondary" href="exercise.php?type=vocabulary&mode=multiple_choice">Doe meerkeuze</a>
                <a class="button secondary" href="exercise.php?type=verbs&mode=conjugation">Oefen vervoegingen</a>
                <a class="button secondary" href="student_progress.php">Open mijn rapport</a>
            </div>
        </div>
    </section>

    <section class="card section-gap">
        <div class="section-head"><h2>Laatste oefeningen</h2><span class="small">Laatste 8 pogingen</span></div>
        <table class="table"><thead><tr><th>Tijd</th><th>Vraag</th><th>Antwoord</th><th>Resultaat</th></tr></thead><tbody>
        <?php foreach ($recent as $row): ?>
            <tr>
                <td><?= e($row['attempted_at']) ?></td>
                <td><?= e($row['question']) ?></td>
                <td><?= e($row['user_answer']) ?></td>
                <td class="<?= $row['is_correct'] ? 'result-good' : 'result-bad' ?>"><?= $row['is_correct'] ? 'Juist' : 'Fout' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </section>
    <?php
} elseif ($user['role'] === 'teacher') {
    $counts = [
        'words' => db_query('SELECT COUNT(*) AS total FROM vocabulary WHERE teacher_id = :id', ['id' => $user['id']])->fetch()['total'] ?? 0,
        'verbs' => db_query('SELECT COUNT(*) AS total FROM verbs WHERE teacher_id = :id', ['id' => $user['id']])->fetch()['total'] ?? 0,
        'themes' => db_query('SELECT COUNT(DISTINCT theme) AS total FROM vocabulary WHERE teacher_id = :id', ['id' => $user['id']])->fetch()['total'] ?? 0,
    ];
    $summary = get_teacher_class_summary((int)$user['class_id']);
    $students = get_teacher_student_report((int)$user['class_id']);
    $themeReport = get_teacher_theme_report((int)$user['class_id']);
    $accuracy = accuracy_percentage($summary['correct_attempts'], $summary['total_attempts']);
    ?>
    <section class="hero">
        <div class="card spotlight">
            <span class="eyebrow">Leraarsdashboard</span>
            <h1>Beheer je klas slim</h1>
            <p>Voeg woordenschat en werkwoorden toe, differentieer per leerling en volg resultaten op in een compacte omgeving die aanvoelt zoals jullie Studyflow-aanpak.</p>
            <div class="menu-grid compact-grid">
                <div class="menu-card"><h3>Woordenschat</h3><p>Nieuwe woorden toevoegen per thema en niveau.</p><a class="button" href="manage_vocabulary.php">Open</a></div>
                <div class="menu-card"><h3>Werkwoorden</h3><p>Werkwoorden en vervoegingen beheren.</p><a class="button" href="manage_verbs.php">Open</a></div>
                <div class="menu-card"><h3>Niveaus</h3><p>Differentiëren per leerling.</p><a class="button" href="manage_levels.php">Open</a></div>
                <div class="menu-card"><h3>Rapportage</h3><p>Bekijk accuratesse en activiteit.</p><a class="button" href="teacher_reports.php">Open</a></div>
            </div>
        </div>
        <div class="card stat-card-grid">
            <div class="mini-stat"><span>Woorden</span><strong><?= (int)$counts['words'] ?></strong></div>
            <div class="mini-stat"><span>Werkwoorden</span><strong><?= (int)$counts['verbs'] ?></strong></div>
            <div class="mini-stat"><span>Thema's</span><strong><?= (int)$counts['themes'] ?></strong></div>
            <div class="mini-stat"><span>Leerlingen</span><strong><?= $summary['students'] ?></strong></div>
            <div class="mini-stat"><span>Gem. punten</span><strong><?= $summary['avg_points'] ?></strong></div>
            <div class="mini-stat"><span>Accuratesse</span><strong><?= $accuracy ?>%</strong></div>
        </div>
    </section>
    <section class="grid grid-2 section-gap">
        <div class="card soft-panel">
            <div class="section-head"><h2>Klasoverzicht</h2><span class="small">Snelle opvolging tijdens de week</span></div>
            <table class="table"><thead><tr><th>Leerling</th><th>Niveau</th><th>Punten</th><th>Pogingen</th><th>Juist %</th></tr></thead><tbody>
            <?php foreach (array_slice($students, 0, 8) as $student): $studentAccuracy = accuracy_percentage((int)($student['correct'] ?? 0), (int)($student['attempts'] ?? 0)); ?>
                <tr>
                    <td><?= e($student['full_name']) ?></td>
                    <td><?= e((string)$student['level']) ?></td>
                    <td><?= e((string)$student['points']) ?></td>
                    <td><?= e((string)$student['attempts']) ?></td>
                    <td><?= $studentAccuracy ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <div class="card soft-panel">
            <div class="section-head"><h2>Moeilijke thema's</h2><span class="small">Goed voor remediëring</span></div>
            <?php if ($themeReport): ?>
                <div class="theme-breakdown">
                    <?php foreach (array_slice($themeReport, 0, 5) as $row): $rowAccuracy = accuracy_percentage((int)$row['correct'], (int)$row['attempts']); ?>
                        <div class="theme-row">
                            <div>
                                <strong><?= e($row['label']) ?></strong>
                                <div class="small"><?= (int)$row['attempts'] ?> pogingen</div>
                            </div>
                            <div class="theme-metrics">
                                <span><?= $rowAccuracy ?>%</span>
                                <div class="progress"><span style="width: <?= $rowAccuracy ?>%"></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="small">Nog geen themadata beschikbaar.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php
} else {
    $counts = [
        'classes' => db_query('SELECT COUNT(*) AS total FROM classes')->fetch()['total'] ?? 0,
        'teachers' => db_query('SELECT COUNT(*) AS total FROM users WHERE role = "teacher"')->fetch()['total'] ?? 0,
        'students' => db_query('SELECT COUNT(*) AS total FROM users WHERE role = "student"')->fetch()['total'] ?? 0,
    ];
    $latestUsers = db_query('SELECT full_name, username, role, created_at FROM users ORDER BY id DESC LIMIT 8')->fetchAll();
    ?>
    <section class="hero">
        <div class="card spotlight">
            <span class="eyebrow">Beheerdersdashboard</span>
            <h1>Centraal beheer voor de hele school</h1>
            <p>Maak klassen en accounts aan, importeer gebruikers via CSV en hou zicht op de structuur van het platform.</p>
            <div class="action-row">
                <a class="button" href="admin_users.php">Gebruikers beheren</a>
                <a class="button secondary" href="admin_import.php">CSV import starten</a>
            </div>
        </div>
        <div class="card stat-card-grid">
            <div class="mini-stat"><span>Klassen</span><strong><?= (int)$counts['classes'] ?></strong></div>
            <div class="mini-stat"><span>Leraren</span><strong><?= (int)$counts['teachers'] ?></strong></div>
            <div class="mini-stat"><span>Leerlingen</span><strong><?= (int)$counts['students'] ?></strong></div>
        </div>
    </section>

    <section class="grid grid-2 section-gap">
        <div class="card soft-panel">
            <div class="section-head"><h2>Beheermodules</h2><span class="small">Sneltoetsen</span></div>
            <div class="menu-grid compact-grid">
                <div class="menu-card admin-card"><h3>Gebruikers</h3><p>Maak leerlingen, leraren en admins aan.</p><a class="button" href="admin_users.php">Open</a></div>
                <div class="menu-card admin-card"><h3>Klassen</h3><p>Beheer klasstructuur en schooljaar.</p><a class="button" href="admin_classes.php">Open</a></div>
                <div class="menu-card admin-card"><h3>CSV import</h3><p>Voeg meerdere accounts tegelijk toe.</p><a class="button" href="admin_import.php">Open</a></div>
            </div>
        </div>
        <div class="card soft-panel">
            <div class="section-head"><h2>Laatst aangemaakte gebruikers</h2><span class="small">Laatste 8</span></div>
            <table class="table"><thead><tr><th>Naam</th><th>Gebruikersnaam</th><th>Rol</th><th>Aangemaakt</th></tr></thead><tbody>
            <?php foreach ($latestUsers as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><?= e(badge_for_role($row['role'])) ?></td>
                    <td><?= e($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </section>
    <?php
}
require __DIR__ . '/../templates/footer.php';
