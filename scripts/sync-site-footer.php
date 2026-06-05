<?php

declare(strict_types=1);

/**
 * Sync shared footer + single CMS modal into static HTML pages.
 * Run: php scripts/sync-site-footer.php
 */

$root = dirname(__DIR__);
$partial = $root . '/includes/partials/site-footer.html';
$footer = file_get_contents($partial);

if ($footer === false || trim($footer) === '') {
    fwrite(STDERR, "Missing footer partial.\n");
    exit(1);
}

$footer = trim($footer);
$skip = [];
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

    if (str_contains($html, '<!-- FOOTER_SYNC -->')) {
        $html = str_replace('<!-- FOOTER_SYNC -->', $footer, $html);
        file_put_contents($file, $html);
        echo "Updated {$name} (placeholder)\n";
        $updated++;
        continue;
    }

    $replaced = preg_replace(
        '/<footer class="site-footer">[\s\S]*?(?=<script src="js\/spangle-env\.js(?:\?v=\d+)?">)/',
        $footer . "\n\n  ",
        $html,
        1,
        $count
    );

    if ($count !== 1 || $replaced === null) {
        fwrite(STDERR, "Skipped {$name}: footer block not found.\n");
        continue;
    }

    if ($replaced !== $html) {
        file_put_contents($file, $replaced);
        $updated++;
        echo "Updated {$name}\n";
    }
}

echo "Done. {$updated} page(s) synced.\n";
