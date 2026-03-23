<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get_all(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        exit('Ongeldige sessie. Vernieuw de pagina.');
    }
}

function badge_for_role(string $role): string {
    return match ($role) {
        'admin' => 'Beheerder',
        'teacher' => 'Leraar',
        default => 'Leerling',
    };
}

function current_school_year(): string {
    $year = (int)date('Y');
    $month = (int)date('n');
    return $month >= 9 ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
}

function current_path(): string {
    return basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
}

function is_active_page(string|array $pages): string {
    return in_array(current_path(), (array)$pages, true) ? 'is-active' : '';
}

function accuracy_percentage(int $correct, int $total): int {
    return $total > 0 ? (int)round(($correct / $total) * 100) : 0;
}

function level_label(int $level): string {
    return 'Niveau ' . max(1, $level);
}
