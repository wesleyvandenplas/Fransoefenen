<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/stats.php';
$user = require_role('student');
$stats = get_student_stats($user['id']);
$accuracy = accuracy_percentage($stats['correct'], $stats['total']);
$themes = get_student_theme_breakdown($user['id']);
$mistakes = get_student_recent_mistakes($user['id']);
$weekly = get_student_weekly_activity($user['id']);
require __DIR__ . '/../templates/header.php';
?>
<section class="hero hero-student">
    <div class="card spotlight">
        <span class="eyebrow">Mijn rapport</span>
        <h1>Zo evolueer jij in het Frans</h1>
        <p>Hier zie je welke thema's goed lopen, waar je nog kan groeien en welke fouten je best nog eens herhaalt.</p>
    </div>
    <div class="card stat-card-grid">
        <div class="mini-stat"><span>Punten</span><strong><?= $stats['points'] ?></strong></div>
        <div class="mini-stat"><span>Streak</span><strong><?= $stats['streak'] ?></strong></div>
        <div class="mini-stat"><span>Totaal</span><strong><?= $stats['total'] ?></strong></div>
        <div class="mini-stat"><span>Juist %</span><strong><?= $accuracy ?>%</strong></div>
    </div>
</section>

<section class="grid grid-2 section-gap">
    <div class="card soft-panel">
        <div class="section-head"><h2>Thema's</h2><span class="small">Sterktes en groeikansen</span></div>
        <?php if ($themes): ?>
            <div class="theme-breakdown">
                <?php foreach ($themes as $theme): $themeAccuracy = accuracy_percentage((int)$theme['correct'], (int)$theme['attempts']); ?>
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
            <p class="small">Nog geen themagegevens beschikbaar.</p>
        <?php endif; ?>
    </div>
    <div class="card soft-panel">
        <div class="section-head"><h2>Activiteit per dag</h2><span class="small">Laatste 7 oefendagen</span></div>
        <?php if ($weekly): ?>
            <div class="activity-list">
                <?php foreach ($weekly as $day): $dayAccuracy = accuracy_percentage((int)$day['correct'], (int)$day['total']); ?>
                    <div class="activity-item">
                        <div>
                            <strong><?= e($day['day']) ?></strong>
                            <div class="small"><?= (int)$day['total'] ?> pogingen</div>
                        </div>
                        <div class="theme-metrics">
                            <span><?= $dayAccuracy ?>%</span>
                            <div class="progress"><span style="width: <?= $dayAccuracy ?>%"></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small">Nog geen activiteit gevonden.</p>
        <?php endif; ?>
    </div>
</section>

<section class="card section-gap">
    <div class="section-head"><h2>Fouten om te herhalen</h2><span class="small">Gebruik dit als remediëring</span></div>
    <?php if ($mistakes): ?>
        <table class="table">
            <thead><tr><th>Tijd</th><th>Vraag</th><th>Jouw antwoord</th><th>Correct antwoord</th><th>Werkvorm</th></tr></thead>
            <tbody>
            <?php foreach ($mistakes as $row): ?>
                <tr>
                    <td><?= e($row['attempted_at']) ?></td>
                    <td><?= e($row['question']) ?></td>
                    <td class="result-bad"><?= e($row['user_answer']) ?></td>
                    <td class="result-good"><?= e($row['correct_answer']) ?></td>
                    <td><?= e($row['workform']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="small">Geen recente fouten. Mooi zo.</p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
