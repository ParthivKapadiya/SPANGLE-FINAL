<?php

declare(strict_types=1);

/**
 * Replace stock imagery with client uploads across all pages (DB + site.json).
 * Run: php scripts/apply-client-site-images.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);

$pageHeroes = [
    'studio_hero_image' => 'uploads/1212-ARVINDBHAI PARMAR_FRONT-2.jpg',
    'studio_philosophy_image' => 'uploads/1228_HARESHBHAI_LIVING_3.jpg',
    'work_hero_image' => 'uploads/1523-HARSHITBHAI-5_ER.jpg.jpeg',
    'services_hero_image' => 'uploads/054-KANTILAL-3D-6.jpg',
    'process_hero_image' => 'uploads/LIVING_ROOM_2-1.jpg',
    'process_split_image' => 'uploads/066-UPENDRASINH-3D-3.jpg',
    'journal_hero_image' => 'uploads/1228_HARESHBHAI_LIVING_4.jpg',
    'contact_hero_image' => 'uploads/ENTRY.jpg',
    'seo_og_image' => 'uploads/ENTRY.jpg',
    'home_about_image' => 'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'studio_strip_image_1' => 'uploads/1213-SANJAYSINH JADEJA_PLOT-62-1.jpg',
    'studio_strip_image_2' => 'uploads/1228_HARESHBHAI_LIVING_4.jpg',
    'studio_strip_image_3' => 'uploads/058-PRAKASHBHAI TANK-3D-2.jpg',
];

foreach ($pageHeroes as $key => $path) {
    setting_set($pdo, $key, $path);
}

$journalMap = [
    'journal-materiality' => 'uploads/054-KANTILAL-3D-5.jpg',
    'journal-sustainable' => 'uploads/LIVING 02.jpg',
    'journal-quiet-luxury' => 'uploads/1228_HARESHBHAI_LIVING_3.jpg',
    'journal-workplaces' => 'uploads/1071-SANJAYSINH JADEJA_4.jpg',
];

$journalStmt = $pdo->prepare('UPDATE journal_posts SET image_path = ? WHERE slug = ?');
foreach ($journalMap as $slug => $path) {
    $journalStmt->execute([$path, $slug]);
}

$serviceImages = [
    'Plan sanctioning & approvals' => 'uploads/054-KANTILAL-3D-6.jpg',
    'Interior design & execution' => 'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'Civil construction' => 'uploads/1212-ARVINDBHAI PARMAR_FRONT-2.jpg',
    'Turnkey project management' => 'uploads/LIVING_ROOM_2-1.jpg',
    'Consulting civil engineering' => 'uploads/058-PRAKASHBHAI TANK-3D-2.jpg',
    'Building plan & NA layouts' => 'uploads/1213-SANJAYSINH JADEJA_PLOT-62-1.jpg',
    '3D design & visualization' => 'uploads/066-UPENDRASINH-3D-3.jpg',
];

$svcStmt = $pdo->prepare('UPDATE services SET image_path = ? WHERE title = ?');
foreach ($serviceImages as $title => $path) {
    $svcStmt->execute([$path, $title]);
}

$heroImages = [
    'uploads/ENTRY.jpg',
    'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'uploads/1159-VISALBHAI RAMPARIYA-5.jpg',
    'uploads/LIVING 01.jpg',
    'uploads/1523-HARSHITBHAI-5_ER.jpg.jpeg',
    'uploads/1228-HARESHBHAI_BED_ROOM-2.jpg',
    'uploads/LIVING_ROOM_2-1.jpg',
    'uploads/054-KANTILAL-3D-6.jpg',
];

$pdo->exec('DELETE FROM hero_slides');
$heroStmt = $pdo->prepare(
    'INSERT INTO hero_slides (image_path, alt_text, sort_order, is_active) VALUES (?, ?, ?, 1)'
);
foreach ($heroImages as $i => $path) {
    $name = pathinfo($path, PATHINFO_FILENAME);
    $alt = ucwords(str_replace(['_', '-'], ' ', $name));
    $heroStmt->execute([$path, $alt, $i]);
}

content_sync_site_json($pdo);

echo "All page images updated to client uploads.\n";
