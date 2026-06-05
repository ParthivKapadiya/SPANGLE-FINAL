<?php

declare(strict_types=1);

/**
 * Normalize CSS/JS cache-busting query strings across static HTML.
 * Run: php scripts/sync-asset-versions.php
 */

$root = dirname(__DIR__);
$version = '8';
$updated = 0;

foreach (glob($root . '/*.html') as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }

    $next = $html;
    $next = preg_replace('/href="style\.css(\?v=\d+)?"/', 'href="style.css?v=' . $version . '"', $next) ?? $next;
    $next = preg_replace('/href="css\/([^"]+\.css)(\?v=\d+)?"/', 'href="css/$1?v=' . $version . '"', $next) ?? $next;
    $next = preg_replace(
        '/src="(js\/[^"]+\.js|site\.js)(\?v=\d+)?"/',
        'src="$1?v=' . $version . '"',
        $next
    ) ?? $next;

    if ($next !== $html) {
        file_put_contents($file, $next);
        $updated++;
        echo 'Updated ' . basename($file) . "\n";
    }
}

echo "Done. {$updated} file(s) versioned (v={$version}).\n";
