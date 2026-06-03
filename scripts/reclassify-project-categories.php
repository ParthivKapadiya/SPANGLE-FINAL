<?php

declare(strict_types=1);

/**
 * Re-tag projects as residential / commercial / retail from slug + title.
 * Run: php scripts/reclassify-project-categories.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/syncUploadLibrary.php';

$pdo = Database::connection($GLOBALS['configDb']);

$stmt = $pdo->prepare('UPDATE projects SET category = ? WHERE id = ?');
$counts = ['residential' => 0, 'commercial' => 0, 'retail' => 0];

foreach ($pdo->query('SELECT id, slug, title, hero_image FROM projects') as $row) {
    $hero = basename((string) ($row['hero_image'] ?? ''));
    $category = SyncUploadLibrary::guessCategory((string) $row['slug'], (string) $row['title'] . ' ' . $hero);
    $stmt->execute([$category, (int) $row['id']]);
    $counts[$category] = ($counts[$category] ?? 0) + 1;
}

content_sync_site_json($pdo);

echo 'Categories updated: residential=' . $counts['residential']
    . ', commercial=' . $counts['commercial']
    . ', retail=' . $counts['retail'] . PHP_EOL;
