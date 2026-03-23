<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
if (current_user()) {
    redirect('dashboard.php');
}
if (is_post()) {
    verify_csrf();
    if (login_user(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
        redirect('dashboard.php');
    }
    flash_set('error', 'Ongeldige login.');
    redirect('index.php');
}
require __DIR__ . '/../templates/header.php';
?>
<div class="login-wrap">
    <div class="card">
        <h1>Inloggen</h1>
        <p class="small">Log in als leerling, leraar of beheerder. Deze versie ondersteunt niveaus, werkvormen, punten en bulkimport.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Gebruikersnaam</label>
            <input type="text" name="username" required>
            <label>Wachtwoord</label>
            <input type="password" name="password" required>
            <button type="submit">Inloggen</button>
        </form>
        <p class="small">Eerste keer? Open dan eerst <strong>setup.php</strong>.</p>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
