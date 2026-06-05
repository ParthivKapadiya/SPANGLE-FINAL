<?php

declare(strict_types=1);

/**
 * Delete generated -640w / -1280w copies (optional disk cleanup).
 * Keeps your original uploads. Run: php scripts/remove-image-variants.php
 */

$root = dirname(__DIR__);
$uploads = $root . '/uploads';
$removed = 0;
$bytes = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (!preg_match('/-\d+w\.(jpe?g|png|webp)$/i', $path)) {
        continue;
    }
    $bytes += filesize($path) ?: 0;
    if (@unlink($path)) {
        $removed++;
    }
}

echo "Removed {$removed} variant file(s), freed ~" . round($bytes / 1024 / 1024, 1) . " MB.\n";
echo "Run: php scripts/refresh-site-json-images.php\n";
