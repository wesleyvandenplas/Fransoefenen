<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function login_user(string $username, string $password): bool {
    $user = db_query('SELECT * FROM users WHERE username = :username AND is_active = 1', ['username' => $username])->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
        'class_id' => $user['class_id'] ? (int)$user['class_id'] : null,
    ];
    return true;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function logout_user(): void {
    unset($_SESSION['user']);
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        redirect('index.php');
    }
    return $user;
}

function require_role(array|string $roles): array {
    $user = require_login();
    $roles = (array)$roles;
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Geen toegang.');
    }
    return $user;
}
