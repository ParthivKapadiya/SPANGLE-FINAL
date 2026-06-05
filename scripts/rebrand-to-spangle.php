<?php

declare(strict_types=1);

/**
 * One-time text rebrand: Archevo → SPANGLE (run from project root via CLI).
 *
 *   php scripts/rebrand-to-spangle.php
 *
 * Logo file paths under uploads/branding/ are left unchanged until new assets are uploaded.
 */

$root = dirname(__DIR__);
$brand = require $root . '/includes/brand.php';
$name = $brand['name'];
$short = $brand['short'];

$replacements = [
    'Archevo Infra Edge Pvt Ltd' => $name,
    'ARCHEVO DESIGN' => strtoupper($short),
    'Archevo Design' => $name,
    'archevodesign6@gmail.com' => 'hello@spangle.studio',
    'https://www.archevoinfra.com' => rtrim($brand['default_url'], '/'),
    'www.archevoinfra.com' => parse_url($brand['default_url'], PHP_URL_HOST) ?: 'spangle.page.gd',
    'Hello Archevo Design — I would like to discuss a project.' => $brand['whatsapp_prefill'],
    'Hello Archevo Design' => $brand['whatsapp_prefill'],
    'admin@archevo.local' => 'admin@spangle.local',
    '[Archevo]' => '[SPANGLE]',
];

$skipDirs = ['.git', 'node_modules', 'vendor', 'uploads'];
$extensions = ['html', 'php', 'js', 'json', 'css', 'md', 'sql'];

$changed = 0;
$files = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    foreach ($skipDirs as $skip) {
        if (str_starts_with($rel, $skip . '/') || $rel === $skip) {
            continue 2;
        }
    }
    if ($rel === 'scripts/rebrand-to-spangle.php') {
        continue;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions, true)) {
        continue;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }
    $original = $content;
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed++;
        echo "Updated: $rel\n";
    }
    $files++;
}

echo "\nScanned $files files, updated $changed.\n";
