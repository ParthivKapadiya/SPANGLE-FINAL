<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['configApp'] ?? [];
    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function app_env(): string
{
    $configured = (string) (app_config('env') ?? 'auto');
    if ($configured !== 'auto') {
        return $configured;
    }
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '' || preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host)) {
        return 'local';
    }

    return 'production';
}

function app_is_local(): bool
{
    return app_env() === 'local';
}

function app_is_production(): bool
{
    return app_env() === 'production';
}

/**
 * On localhost, always use the current request base URL for uploads and asset paths.
 * On production, use the stored public_base when set.
 */
function local_public_base(string $stored = ''): string
{
    if (app_is_local()) {
        return site_base_url();
    }

    $stored = trim($stored);

    return $stored !== '' ? $stored : site_base_url();
}

/**
 * Cookie path for admin PHP sessions (must match project URL path, e.g. /SPANGLE_FINAL/).
 */
function admin_base_path(): string
{
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $root = rtrim(str_replace('\\', '/', SPANGLE_ROOT), '/');
    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        $rel = substr($root, strlen($docRoot));
        if ($rel === false || $rel === '') {
            return '/';
        }

        return rtrim($rel, '/') . '/';
    }

    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(/.*)/admin/#', $script, $m)) {
        return $m[1] . '/';
    }

    return '/';
}

function site_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $root = rtrim(str_replace('\\', '/', SPANGLE_ROOT), '/');
    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        $path = substr($root, strlen($docRoot));
        if ($path === false || $path === '') {
            $path = '';
        }

        return rtrim($scheme . '://' . $host . $path, '/');
    }

    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = str_replace('\\', '/', dirname($script));
    if (preg_match('#/(api|admin)$#', $dir)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }

    return rtrim($scheme . '://' . $host . $dir, '/');
}

/** Public URL for a journal article (static .html — never journal-post.php). */
function journal_public_url(string $slug): string
{
    $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($slug)));
    if ($slug === '') {
        return 'journal.html';
    }

    return $slug . '.html';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
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

function ensure_upload_directories(?array $appConfig = null): void
{
    $appConfig = $appConfig ?? (array) app_config();
    $mode = app_is_local() ? 0777 : 0775;
    $folders = $appConfig['upload_folders'] ?? [];

    $root = SPANGLE_ROOT . '/uploads';
    if (!is_dir($root)) {
        @mkdir($root, $mode, true);
    }
    if (app_is_local() && is_dir($root) && !is_writable($root)) {
        @chmod($root, 0777);
        clearstatcache(true, $root);
    }

    foreach ($folders as $relDir) {
        $absDir = SPANGLE_ROOT . '/' . ltrim((string) $relDir, '/');
        if (!is_dir($absDir)) {
            @mkdir($absDir, $mode, true);
        }
        if (!is_dir($absDir)) {
            continue;
        }
        if (!is_writable($absDir)) {
            @chmod($absDir, $mode);
            clearstatcache(true, $absDir);
        }
        $index = $absDir . '/index.html';
        if (!is_file($index) && is_writable($absDir)) {
            @file_put_contents($index, '');
        }
    }
}

function public_upload_url(string $path): string
{
    return public_media_path($path);
}

function public_media_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $segments = explode('/', $path);

    return implode('/', array_map(static function ($segment) {
        return rawurlencode(rawurldecode($segment));
    }, $segments));
}
