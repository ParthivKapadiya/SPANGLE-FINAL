<?php

declare(strict_types=1);

/**
 * Import every portfolio image from uploads/ as its own work-page project (named from filename).
 * Usage: php scripts/sync-all-uploads-to-work.php
 */
require_once dirname(__DIR__) . '/admin/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/syncUploadLibrary.php';

$result = SyncUploadLibrary::syncOneProjectPerImage($pdo, true);

echo 'Work portfolio synced.' . PHP_EOL;
echo 'Projects created: ' . $result['projects'] . PHP_EOL;
echo 'Images linked: ' . $result['images'] . PHP_EOL;
