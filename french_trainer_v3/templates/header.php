<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
$config = app_config();
$user = current_user();
$flashes = flash_get_all();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-shell">
    <div class="site-header">
        <div>
            <a class="brand" href="<?= $user ? 'dashboard.php' : 'index.php' ?>">🇫🇷 <?= e($config['app_name']) ?></a>
            <div class="subtitle">Studyflow-stijl voor Frans: oefenen, opvolgen en beheren in één platform</div>
        </div>
        <?php if ($user): ?>
            <div class="header-meta">
                <span class="pill"><?= e($user['full_name']) ?> · <?= e(badge_for_role($user['role'])) ?></span>
                <a class="button secondary small-btn" href="logout.php">Uitloggen</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($user): ?>
        <nav class="top-nav">
            <a class="<?= is_active_page(['dashboard.php']) ?>" href="dashboard.php">Dashboard</a>
            <?php if ($user['role'] === 'student'): ?>
                <a class="<?= is_active_page(['exercise.php']) ?>" href="exercise.php?type=vocabulary">Oefenen</a>
                <a class="<?= is_active_page(['student_progress.php']) ?>" href="student_progress.php">Mijn rapport</a>
            <?php elseif ($user['role'] === 'teacher'): ?>
                <a class="<?= is_active_page(['manage_vocabulary.php']) ?>" href="manage_vocabulary.php">Woordenschat</a>
                <a class="<?= is_active_page(['manage_verbs.php']) ?>" href="manage_verbs.php">Werkwoorden</a>
                <a class="<?= is_active_page(['manage_levels.php']) ?>" href="manage_levels.php">Niveaus</a>
                <a class="<?= is_active_page(['manage_workforms.php']) ?>" href="manage_workforms.php">Werkvormen</a>
                <a class="<?= is_active_page(['teacher_reports.php']) ?>" href="teacher_reports.php">Rapportage</a>
            <?php else: ?>
                <a class="<?= is_active_page(['admin_users.php']) ?>" href="admin_users.php">Gebruikers</a>
                <a class="<?= is_active_page(['admin_classes.php']) ?>" href="admin_classes.php">Klassen</a>
                <a class="<?= is_active_page(['admin_import.php']) ?>" href="admin_import.php">CSV import</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
