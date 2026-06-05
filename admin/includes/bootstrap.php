<?php

declare(strict_types=1);

define('ADMIN_AUTH_OK', true);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';

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
