<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '" />';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';

    return is_string($token) && is_string($session) && $token !== '' && hash_equals($session, $token);
}

function setting_get(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return $row && $row['setting_value'] !== null ? (string) $row['setting_value'] : $default;
}

function setting_set(PDO $pdo, string $key, ?string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
    content_sync_site_json($pdo);
}

function content_sync_site_json(PDO $pdo): void
{
    require_once SPANGLE_ROOT . '/includes/SiteContent.php';
    SiteContent::exportSiteJson($pdo);
}

function settings_get_many(PDO $pdo, array $keys): array
{
    if (!$keys) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
    $stmt->execute(array_values($keys));
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
    }

    return $out;
}

function public_upload_url(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return $path;
}
