<?php

declare(strict_types=1);

/**
 * Rebuild content/site.json so images use originals only (no srcset variants).
 * Run after setting image_responsive_variants => false in config/app.php:
 *   php scripts/refresh-site-json-images.php
 */

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';

if (!is_file($root . '/config/database.php')) {
    fwrite(STDERR, "Missing config/database.php — run install.php first.\n");
    exit(1);
}

try {
    $pdo = Database::connection(require $root . '/config/database.php');
    content_sync_site_json($pdo);
    echo "Regenerated content/site.json (originals only, no variant srcset).\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
