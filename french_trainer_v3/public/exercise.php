<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/stats.php';
$user = require_role('student');
$type = ($_GET['type'] ?? 'vocabulary') === 'verbs' ? 'verbs' : 'vocabulary';
$allowedForms = get_available_workforms_for_class($user['class_id']);
$defaultMode = $type === 'verbs' && in_array('conjugation', $allowedForms, true) ? 'conjugation' : ($allowedForms[0] ?? 'typing');
$mode = $_GET['mode'] ?? $defaultMode;
if (!in_array($mode, $allowedForms, true)) {
    $mode = $defaultMode;
}
$level = get_student_level($user['id']);
$result = null;
$item = null;
$question = '';
$prompt = '';
$correct = '';
$themeFilter = trim((string)($_GET['theme'] ?? ''));
$groupFilter = trim((string)($_GET['group'] ?? ''));
$themeOptions = get_available_vocabulary_themes($user['class_id']);
$groupOptions = get_available_verb_groups($user['class_id']);

function pick_random(array $items): ?array { return $items ? $items[array_rand($items)] : null; }

if ($type === 'vocabulary') {
    $params = ['level' => $level, 'class_id' => $user['class_id']];
    $sql = 'SELECT * FROM vocabulary WHERE level <= :level AND (class_id IS NULL OR class_id = :class_id)';
    if ($themeFilter !== '') {
        $sql .= ' AND theme = :theme';
        $params['theme'] = $themeFilter;
    }
    $sql .= ' ORDER BY id';
    $items = db_query($sql, $params)->fetchAll();
    $item = pick_random($items);
    if (!$item) {
        flash_set('info', 'Nog geen woordenschat beschikbaar voor deze selectie.');
        redirect('exercise.php?type=vocabulary');
    }

    $question = $item['french_word'];
    $correct = $item['dutch_word'];
    $prompt = 'Wat betekent dit Franse woord in het Nederlands?';

    if (is_post()) {
        verify_csrf();
        $userAnswerRaw = trim((string)($_POST['answer'] ?? ''));
        $userAnswer = mb_strtolower($userAnswerRaw);
        $isCorrect = trim(mb_strtolower($correct)) === $userAnswer;
        record_attempt($user['id'], 'vocabulary', 'vocabulary', (int)$item['id'], $mode, $question, $correct, $userAnswerRaw, $isCorrect);
        $result = ['is_correct' => $isCorrect, 'correct_answer' => $correct];
    }
} else {
    $params = ['level' => $level, 'class_id' => $user['class_id']];
    $sql = 'SELECT v.*, c.pronoun, c.conjugated_form, c.tense
            FROM verbs v
            LEFT JOIN conjugations c ON c.verb_id = v.id
            WHERE v.level <= :level AND (v.class_id IS NULL OR v.class_id = :class_id)';
    if ($groupFilter !== '') {
        $sql .= ' AND COALESCE(v.group_name, "Algemeen") = :group_name';
        $params['group_name'] = $groupFilter;
    }
    $sql .= ' ORDER BY v.id';
    $items = db_query($sql, $params)->fetchAll();
    $item = pick_random($items);
    if (!$item) {
        flash_set('info', 'Nog geen werkwoorden beschikbaar voor deze selectie.');
        redirect('exercise.php?type=verbs');
    }

    if ($mode === 'conjugation') {
        $question = trim(($item['pronoun'] ?: 'je') . ' ' . $item['infinitive'] . ' (' . ($item['tense'] ?: 'présent') . ')');
        $correct = $item['conjugated_form'] ?: $item['translation'];
        $prompt = 'Vervoeg dit werkwoord.';
    } else {
        $question = $item['infinitive'];
        $correct = $item['translation'];
        $prompt = 'Geef de Nederlandse vertaling van dit werkwoord.';
    }

    if (is_post()) {
        verify_csrf();
        $userAnswerRaw = trim((string)($_POST['answer'] ?? ''));
        $userAnswer = mb_strtolower($userAnswerRaw);
        $isCorrect = trim(mb_strtolower($correct)) === $userAnswer;
        record_attempt($user['id'], 'verbs', 'verb', (int)$item['id'], $mode, $question, $correct, $userAnswerRaw, $isCorrect);
        $result = ['is_correct' => $isCorrect, 'correct_answer' => $correct];
    }
}

$options = [];
if ($mode === 'multiple_choice' && $item) {
    if ($type === 'vocabulary') {
        $others = db_query('SELECT dutch_word FROM vocabulary WHERE id != :id ORDER BY RANDOM() LIMIT 3', ['id' => $item['id']])->fetchAll();
        $options = array_merge([$correct], array_map(fn($r) => $r['dutch_word'], $others));
    } else {
        $others = db_query('SELECT translation FROM verbs WHERE id != :id ORDER BY RANDOM() LIMIT 3', ['id' => $item['id']])->fetchAll();
        $options = array_merge([$correct], array_map(fn($r) => $r['translation'], $others));
    }
    $options = array_values(array_unique($options));
    shuffle($options);
}

$stats = get_student_stats($user['id']);
$accuracy = accuracy_percentage($stats['correct'], $stats['total']);
require __DIR__ . '/../templates/header.php';
?>
<div class="grid grid-exercise">
    <section class="card practice-card elevated-card">
        <div class="section-head stack-mobile">
            <div>
                <span class="eyebrow"><?= $type === 'vocabulary' ? 'Woordenschat' : 'Werkwoorden' ?></span>
                <h1><?= $type === 'vocabulary' ? 'Franse woordenschat oefenen' : 'Franse werkwoorden oefenen' ?></h1>
                <p class="small"><?= e(level_label($level)) ?> · werkvorm: <?= e($mode) ?></p>
            </div>
            <div class="tag-row compact">
                <?php foreach ($allowedForms as $form): ?>
                    <a class="tag tag-link <?= $form === $mode ? 'tag-current' : '' ?>" href="exercise.php?type=<?= e($type) ?>&mode=<?= e($form) ?><?= $themeFilter !== '' ? '&theme=' . urlencode($themeFilter) : '' ?><?= $groupFilter !== '' ? '&group=' . urlencode($groupFilter) : '' ?>"><?= e($form) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="get" class="filter-bar">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <input type="hidden" name="mode" value="<?= e($mode) ?>">
            <?php if ($type === 'vocabulary'): ?>
                <div>
                    <label for="theme">Thema</label>
                    <select name="theme" id="theme">
                        <option value="">Alle thema's</option>
                        <?php foreach ($themeOptions as $row): ?>
                            <option value="<?= e($row['theme']) ?>" <?= $themeFilter === $row['theme'] ? 'selected' : '' ?>><?= e($row['theme']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div>
                    <label for="group">Werkwoordgroep</label>
                    <select name="group" id="group">
                        <option value="">Alle groepen</option>
                        <?php foreach ($groupOptions as $row): $group = $row['group_name']; ?>
                            <option value="<?= e($group) ?>" <?= $groupFilter === $group ? 'selected' : '' ?>><?= e($group) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="filter-actions">
                <button type="submit">Toepassen</button>
            </div>
        </form>

        <?php if ($result): ?>
            <div class="flash <?= $result['is_correct'] ? 'success' : 'error' ?>">
                <?= $result['is_correct'] ? 'Goed gedaan!' : 'Nog niet juist.' ?> Correct antwoord: <strong><?= e($result['correct_answer']) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($item): ?>
            <div class="question-box question-box-studyflow">
                <div class="pill"><?= e($prompt) ?></div>
                <div class="meta-chips">
                    <?php if (!empty($item['theme'])): ?><span class="tag">Thema: <?= e($item['theme']) ?></span><?php endif; ?>
                    <?php if (!empty($item['group_name'])): ?><span class="tag">Groep: <?= e($item['group_name']) ?></span><?php endif; ?>
                    <span class="tag">Niveau <?= (int)$item['level'] ?></span>
                </div>
                <div class="question-text question-text-large"><?= e($question) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'flashcards'): ?>
            <div class="flashcard-panel">
                <p class="small">Gebruik deze als snelle herhaling.</p>
                <div class="answer-preview"><?= e($correct) ?></div>
                <div class="action-row">
                    <a class="button" href="exercise.php?type=<?= e($type) ?>&mode=<?= e($mode) ?><?= $themeFilter !== '' ? '&theme=' . urlencode($themeFilter) : '' ?><?= $groupFilter !== '' ? '&group=' . urlencode($groupFilter) : '' ?>">Volgende kaart</a>
                    <a class="button secondary" href="dashboard.php">Terug naar dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <form method="post" class="practice-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($mode === 'multiple_choice'): ?>
                    <div class="option-list">
                        <?php foreach ($options as $option): ?>
                            <label class="choice-card"><input type="radio" name="answer" value="<?= e($option) ?>" required> <span><?= e($option) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <label>Jouw antwoord</label>
                    <input type="text" name="answer" autocomplete="off" required placeholder="Typ hier je antwoord">
                <?php endif; ?>
                <div class="action-row">
                    <button type="submit">Controleren</button>
                    <a class="button secondary" href="exercise.php?type=<?= e($type) ?>&mode=<?= e($mode) ?><?= $themeFilter !== '' ? '&theme=' . urlencode($themeFilter) : '' ?><?= $groupFilter !== '' ? '&group=' . urlencode($groupFilter) : '' ?>">Overslaan</a>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <aside class="card sidebar-card elevated-card soft-panel">
        <h2>Jouw voortgang</h2>
        <div class="mini-stat-list">
            <div class="mini-stat alt"><span>Punten</span><strong><?= $stats['points'] ?></strong></div>
            <div class="mini-stat alt"><span>Streak</span><strong><?= $stats['streak'] ?></strong></div>
            <div class="mini-stat alt"><span>Accuratesse</span><strong><?= $accuracy ?>%</strong></div>
            <div class="mini-stat alt"><span>Totaal</span><strong><?= $stats['total'] ?></strong></div>
        </div>
        <div class="tip-box">
            <strong>Tip</strong>
            <p>Laat leerlingen eerst flashcards gebruiken, daarna meerkeuze en pas dan typen of vervoegen. Zo bouw je moeilijkheid rustig op.</p>
        </div>
        <div class="action-stack">
            <a class="button" href="exercise.php?type=vocabulary">Woordenschat</a>
            <a class="button secondary" href="exercise.php?type=verbs">Werkwoorden</a>
            <a class="button secondary" href="student_progress.php">Mijn rapport</a>
            <a class="button secondary" href="dashboard.php">Dashboard</a>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
