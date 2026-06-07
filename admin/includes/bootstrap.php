<?php

declare(strict_types=1);

define('ADMIN_AUTH_OK', true);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
require_once __DIR__ . '/activity.php';

/** @var array<string, mixed> $configDb */
$configDb = (array) ($GLOBALS['configDb'] ?? []);
/** @var array<string, mixed> $appConfig */
$appConfig = (array) app_config();

Auth::configureSession();
Auth::startSession();

$pdo = Database::connection($configDb);
cms_run_migrations($pdo);
ensure_upload_directories($appConfig);

function admin_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

    return trim($text, '-') ?: 'item';
}

function admin_require_auth(): void
{
    Auth::requireAdmin();
}

function admin_flash_set(string $type, string $message): void
{
    flash_set($type, $message);
}

function admin_tooltip(string $text): string
{
    return '<span class="adm-tip" title="' . e($text) . '" aria-label="' . e($text) . '"><i class="fa-solid fa-circle-info"></i></span>';
}

function admin_brand(): array
{
    static $brand;
    if ($brand === null) {
        $brand = require SPANGLE_ROOT . '/includes/brand.php';
    }

    return $brand;
}

function admin_new_inquiry_count(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function admin_inquiry_status_badge(string $status): string
{
    $label = ucfirst(str_replace('_', ' ', $status));
    $class = 'adm-badge';
    if (in_array($status, ['new', 'contacted', 'in_progress', 'closed'], true)) {
        $class .= ' adm-badge--' . $status;
    }

    return '<span class="' . e($class) . '">' . e($label) . '</span>';
}

function admin_inquiry_source_badge(?string $source): string
{
    $source = trim((string) $source);
    if ($source === '') {
        $source = 'contact';
    }
    $class = 'adm-pill adm-pill--' . preg_replace('/[^a-z0-9_-]/', '', strtolower($source));

    return '<span class="' . e($class) . '">' . e(ucfirst($source)) . '</span>';
}

/** Relative path prefix from the current admin script to /admin/ (e.g. "../" inside /admin/home/). */
function admin_base(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $marker = '/admin/';
    $pos = strrpos($script, $marker);
    if ($pos === false) {
        return '';
    }

    $relative = substr($script, $pos + strlen($marker));
    $dir = str_replace('\\', '/', dirname($relative));
    if ($dir === '.' || $dir === '/') {
        return '';
    }

    $depth = substr_count(trim($dir, '/'), '/') + 1;

    return str_repeat('../', $depth);
}

function admin_href(string $path): string
{
    return admin_base() . ltrim($path, '/');
}

function admin_asset(string $path, int $version = 8): string
{
    $href = admin_href($path);
    $sep = str_contains($href, '?') ? '&' : '?';

    return $href . $sep . 'v=' . $version;
}
