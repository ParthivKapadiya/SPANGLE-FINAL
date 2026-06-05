<?php

declare(strict_types=1);

/**
 * Resize images in uploads/ and generate 640w / 1280w variants for srcset.
 * Run: php scripts/optimize-upload-images.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/Database.php';
require_once SPANGLE_ROOT . '/includes/ImageOptimizer.php';

/** @var array<string, mixed> $configDb */
$configDb = (array) ($GLOBALS['configDb'] ?? []);
$pdo = Database::connection($configDb);

$dir = SPANGLE_ROOT . '/uploads';
echo "Optimizing images in {$dir}…\n";
$stats = ImageOptimizer::optimizeDirectory($dir, true);
echo 'Processed: ' . $stats['processed'] . "\n";
echo 'Skipped: ' . $stats['skipped'] . "\n";
echo 'Errors: ' . $stats['errors'] . "\n";

if (isset($pdo) && $pdo instanceof PDO) {
    content_sync_site_json($pdo);
    echo "Site JSON refreshed.\n";
}
