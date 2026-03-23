<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/stats.php';
$user = require_role('teacher');
$classId = (int)$user['class_id'];
$summary = get_teacher_class_summary($classId);
$students = get_teacher_student_report($classId);
$themeReport = get_teacher_theme_report($classId);
$riskList = get_teacher_student_risk_list($classId);
$recentAttempts = db_query(
    'SELECT ea.attempted_at, ea.question, ea.workform, ea.is_correct, u.full_name
     FROM exercise_attempts ea
     INNER JOIN users u ON u.id = ea.student_id
     WHERE u.class_id = :class_id
     ORDER BY ea.attempted_at DESC
     LIMIT 20',
    ['class_id' => $classId]
)->fetchAll();
$accuracy = accuracy_percentage($summary['correct_attempts'], $summary['total_attempts']);
require __DIR__ . '/../templates/header.php';
?>
<section class="hero">
    <div class="card spotlight">
        <span class="eyebrow">Rapportage</span>
        <h1>Klasrapport Frans</h1>
        <p>Gebruik dit overzicht om snel te zien welke leerlingen actief zijn, welke thema's moeilijk lopen en wie extra ondersteuning nodig heeft.</p>
    </div>
    <div class="card stat-card-grid">
        <div class="mini-stat"><span>Leerlingen</span><strong><?= $summary['students'] ?></strong></div>
        <div class="mini-stat"><span>Pogingen</span><strong><?= $summary['total_attempts'] ?></strong></div>
        <div class="mini-stat"><span>Juist %</span><strong><?= $accuracy ?>%</strong></div>
        <div class="mini-stat"><span>Gem. streak</span><strong><?= $summary['avg_streak'] ?></strong></div>
    </div>
</section>

<section class="grid grid-2 section-gap">
    <div class="card soft-panel">
        <div class="section-head"><h2>Themarapport</h2><span class="small">Waar de klas op botst</span></div>
        <?php if ($themeReport): ?>
            <div class="theme-breakdown">
                <?php foreach ($themeReport as $row): $rowAccuracy = accuracy_percentage((int)$row['correct'], (int)$row['attempts']); ?>
                    <div class="theme-row">
                        <div>
                            <strong><?= e($row['label']) ?></strong>
                            <div class="small"><?= (int)$row['attempts'] ?> pogingen · <?= (int)$row['active_students'] ?> actieve leerlingen</div>
                        </div>
                        <div class="theme-metrics">
                            <span><?= $rowAccuracy ?>%</span>
                            <div class="progress"><span style="width: <?= $rowAccuracy ?>%"></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small">Nog geen themagegevens beschikbaar.</p>
        <?php endif; ?>
    </div>
    <div class="card soft-panel">
        <div class="section-head"><h2>Extra ondersteuning nodig</h2><span class="small">Laagste accuratesse</span></div>
        <?php if ($riskList): ?>
            <div class="support-list">
                <?php foreach ($riskList as $student): $studentAccuracy = accuracy_percentage((int)$student['correct'], (int)$student['attempts']); ?>
                    <div class="support-item">
                        <div>
                            <strong><?= e($student['full_name']) ?></strong>
                            <div class="small"><?= (int)$student['attempts'] ?> pogingen · laatste activiteit: <?= e($student['last_attempt'] ?: '-') ?></div>
                        </div>
                        <div class="support-badge"><?= $studentAccuracy ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small">Nog onvoldoende gegevens om leerlingen uit te lichten.</p>
        <?php endif; ?>
    </div>
</section>

<section class="card section-gap">
    <div class="section-head"><h2>Leerlingrapport</h2><span class="small">Sterk voor opvolging tijdens MDO of oudercontact</span></div>
    <table class="table"><thead><tr><th>Leerling</th><th>Niveau</th><th>Punten</th><th>Streak</th><th>Pogingen</th><th>Juist</th><th>Laatste activiteit</th></tr></thead><tbody>
        <?php foreach ($students as $student): $studentAccuracy = accuracy_percentage((int)($student['correct'] ?? 0), (int)($student['attempts'] ?? 0)); ?>
            <tr>
                <td><?= e($student['full_name']) ?></td>
                <td><?= e(level_label((int)$student['level'])) ?></td>
                <td><?= e((string)$student['points']) ?></td>
                <td><?= e((string)$student['streak']) ?></td>
                <td><?= e((string)$student['attempts']) ?></td>
                <td><?= $studentAccuracy ?>%</td>
                <td><?= e($student['last_attempt'] ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table>
</section>

<section class="card section-gap">
    <div class="section-head"><h2>Recente activiteit</h2><span class="small">Laatste 20 oefenmomenten</span></div>
    <table class="table"><thead><tr><th>Tijd</th><th>Leerling</th><th>Vraag</th><th>Werkvorm</th><th>Resultaat</th></tr></thead><tbody>
        <?php foreach ($recentAttempts as $attempt): ?>
            <tr>
                <td><?= e($attempt['attempted_at']) ?></td>
                <td><?= e($attempt['full_name']) ?></td>
                <td><?= e($attempt['question']) ?></td>
                <td><?= e($attempt['workform']) ?></td>
                <td class="<?= $attempt['is_correct'] ? 'result-good' : 'result-bad' ?>"><?= $attempt['is_correct'] ? 'Juist' : 'Fout' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
