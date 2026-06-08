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
function sanitize_public_base_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return '';
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || preg_match('/localhost|127\.0\.0\.1/', $host)) {
        return '';
    }

    return rtrim($url, '/');
}

function local_public_base(string $stored = ''): string
{
    if (app_is_local()) {
        return site_base_url();
    }

    $stored = sanitize_public_base_url($stored);

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
    return csrf_verify_from_value($_POST['csrf_token'] ?? '');
}

function csrf_verify_from_value(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        Auth::startSession();
    }
    $token = (string) ($token ?? '');
    $session = $_SESSION['csrf_token'] ?? '';

    return is_string($session) && $token !== '' && hash_equals($session, $token);
}

function csrf_json_field(): string
{
    return json_encode(csrf_token(), JSON_UNESCAPED_UNICODE);
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

function home_gallery_limit_min(): int
{
    return 4;
}

function home_gallery_limit_max(): int
{
    return 24;
}

function home_gallery_limit_clamp(int $value): int
{
    return max(home_gallery_limit_min(), min(home_gallery_limit_max(), $value));
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

/**
 * Responsive image bundle for front-end (optional -640w / -1280w variants).
 *
 * @return array{src: string, srcset: string, sizes: string}
 */
function image_responsive_bundle(string $relPath, string $sizes = '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw'): array
{
    $relPath = trim(str_replace('\\', '/', $relPath));
    $src = public_upload_url($relPath);
    if ($relPath === '' || preg_match('#^https?://#i', $relPath)) {
        return ['src' => $src, 'srcset' => '', 'sizes' => $sizes];
    }

    require_once SPANGLE_ROOT . '/includes/ImageOptimizer.php';

    if (!ImageOptimizer::variantsEnabled()) {
        return ['src' => $src, 'srcset' => '', 'sizes' => ''];
    }

    $parts = [];
    $masterAbs = SPANGLE_ROOT . '/' . $relPath;
    if (is_file($masterAbs)) {
        $info = @getimagesize($masterAbs);
        if ($info && $info[0] > 0) {
            $parts[] = $src . ' ' . $info[0] . 'w';
        }
    }

    foreach (ImageOptimizer::VARIANT_WIDTHS as $width) {
        $variantRel = ImageOptimizer::variantRelativePath($relPath, $width);
        if (is_file(SPANGLE_ROOT . '/' . $variantRel)) {
            $parts[] = public_upload_url($variantRel) . ' ' . $width . 'w';
        }
    }

    $parts = array_values(array_unique($parts));
    usort($parts, static function (string $a, string $b): int {
        preg_match('/(\d+)w$/', $a, $ma);
        preg_match('/(\d+)w$/', $b, $mb);

        return ((int) ($ma[1] ?? 0)) <=> ((int) ($mb[1] ?? 0));
    });

    return [
        'src' => $src,
        'srcset' => implode(', ', $parts),
        'sizes' => $sizes,
    ];
}

function normalize_upload_relative_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (preg_match('#^uploads/#i', $path)) {
        return $path;
    }
    $base = basename($path);
    if (preg_match('/^(archevo-logo|archevo-icon)/i', $base)) {
        return 'uploads/branding/' . $base;
    }

    return 'uploads/' . ltrim($path, '/');
}

function public_media_path(string $path): string
{
    $path = normalize_upload_relative_path($path);
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
