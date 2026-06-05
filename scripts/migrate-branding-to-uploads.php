<?php

declare(strict_types=1);

/**
 * Point all site logo/favicon references at uploads/branding/.
 * Run once: php scripts/migrate-branding-to-uploads.php
 */

$root = dirname(__DIR__);

$replacements = [
    'uploads/branding/' => 'uploads/branding/',
    'https://spangle.page.gd/archevo-logo.png' => 'uploads/branding/archevo-logo.png',
    'https://spangle.page.gd/archevo-logo-light.png' => 'uploads/branding/archevo-logo-light.png',
];

$bareLogos = [
    'archevo-logo-light.png' => 'uploads/branding/archevo-logo-light.png',
    'archevo-logo-dark.png' => 'uploads/branding/archevo-logo-dark.png',
    'archevo-logo.png' => 'uploads/branding/archevo-logo.png',
    'archevo-icon.png' => 'uploads/branding/archevo-icon.png',
];

$globPatterns = [
    $root . '/*.html',
    $root . '/**/*.html',
    $root . '/includes/**/*.html',
    $root . '/includes/**/*.php',
    $root . '/js/*.js',
    $root . '/content/*.json',
    $root . '/site.json',
    $root . '/database/*.php',
];

$files = [];
foreach ($globPatterns as $pattern) {
    foreach (glob($pattern) ?: [] as $file) {
        if (is_file($file)) {
            $files[$file] = true;
        }
    }
}

$updated = 0;
foreach (array_keys($files) as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $next = $content;
    foreach ($replacements as $from => $to) {
        $next = str_replace($from, $to, $next);
    }
    foreach ($bareLogos as $from => $to) {
        $next = preg_replace(
            '/(?<!uploads\\/branding\\/)(?<!uploads\\/branding\\/)(?<![\\/\\w])' . preg_quote($from, '/') . '/',
            $to,
            $next
        ) ?? $next;
    }
    if ($next !== $content) {
        file_put_contents($file, $next);
        echo 'Updated ' . str_replace($root . '/', '', $file) . "\n";
        $updated++;
    }
}

if (is_file($root . '/config/database.php')) {
    require_once $root . '/includes/bootstrap.php';
    try {
        $pdo = Database::connection(require $root . '/config/database.php');
        $map = [
            'site_logo' => 'uploads/branding/archevo-logo.png',
            'site_logo_light' => 'uploads/branding/archevo-logo-light.png',
            'site_logo_dark' => 'uploads/branding/archevo-logo-dark.png',
            'site_favicon' => 'uploads/branding/archevo-logo-light.png',
        ];
        $stmt = $pdo->prepare(
            'UPDATE site_settings SET setting_value = ? WHERE setting_key = ?'
        );
        foreach ($map as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        if (function_exists('content_sync_site_json')) {
            content_sync_site_json($pdo);
            echo "Synced content/site.json from database.\n";
        }
    } catch (Throwable $e) {
        echo 'DB skip: ' . $e->getMessage() . "\n";
    }
}

echo "Done. {$updated} file(s) updated.\n";
