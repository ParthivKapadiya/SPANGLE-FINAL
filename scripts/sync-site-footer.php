<?php

declare(strict_types=1);

/**
 * Replace inline footers in static HTML pages with the shared partial.
 * Run: php scripts/sync-site-footer.php
 */

$root = dirname(__DIR__);
$partial = $root . '/includes/partials/site-footer.html';
$footer = file_get_contents($partial);

if ($footer === false || trim($footer) === '') {
    fwrite(STDERR, "Missing footer partial.\n");
    exit(1);
}

$skip = ['admin.html'];
$updated = 0;

foreach (glob($root . '/*.html') as $file) {
    $name = basename($file);
    if (in_array($name, $skip, true)) {
        continue;
    }

    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }

    $replaced = preg_replace(
        '/<footer class="site-footer">[\s\S]*?<\/footer>/',
        trim($footer),
        $html,
        1,
        $count
    );

    if ($count !== 1 || $replaced === null) {
        fwrite(STDERR, "Skipped {$name}: footer not found or multiple matches.\n");
        continue;
    }

    if ($replaced !== $html) {
        file_put_contents($file, $replaced);
        $updated++;
        echo "Updated {$name}\n";
    }
}

echo "Done. {$updated} page(s) synced.\n";
