<?php

declare(strict_types=1);

/**
 * Add retail portfolio entries with online architectural / interior imagery.
 * Run: php scripts/seed-retail-showcase.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);

function retail_gallery_html(array $urls, string $alt): string
{
    $figures = array_map(static function (string $url) use ($alt) {
        $src = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $altEsc = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

        return '<figure class="project-photo"><img src="' . $src . '" alt="' . $altEsc . '" loading="lazy" decoding="async" /></figure>';
    }, $urls);

    return '<div class="project-gallery-grid">' . implode('', $figures) . '</div>';
}

$retailProjects = [
    [
        'slug' => 'retail-jewellery-showcase',
        'title' => 'Jewellery showroom',
        'location' => 'Rajkot, Gujarat',
        'summary' => 'Luxury retail interior with display lighting and bespoke joinery.',
        'hero_image' => 'uploads/retail/retail-jewellery-showroom.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1600607687644-c7171b42498f?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600047509358-9dc75507daeb?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 130,
    ],
    [
        'slug' => 'retail-fashion-boutique',
        'title' => 'Fashion boutique',
        'location' => 'Ahmedabad, Gujarat',
        'summary' => 'Boutique fit-out — merchandising walls, trial rooms, and brand-led atmosphere.',
        'hero_image' => 'uploads/retail/retail-fashion-boutique.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 131,
    ],
    [
        'slug' => 'retail-furniture-showroom',
        'title' => 'Furniture showroom',
        'location' => 'Surat, Gujarat',
        'summary' => 'Open-plan showroom zoning with feature displays and customer circulation.',
        'hero_image' => 'uploads/retail/retail-furniture-showroom.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 132,
    ],
    [
        'slug' => 'retail-cafe-lounge',
        'title' => 'Café & retail lounge',
        'location' => 'Vadodara, Gujarat',
        'summary' => 'Hybrid café-retail concept — hospitality finishes with product display.',
        'hero_image' => 'uploads/retail/retail-cafe-lounge.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 133,
    ],
    [
        'slug' => 'retail-electronics-store',
        'title' => 'Electronics retail',
        'location' => 'Rajkot, Gujarat',
        'summary' => 'Clean-lined retail shell with modular fixtures and accent lighting.',
        'hero_image' => 'uploads/retail/retail-electronics-store.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1600607688969-a5bfcd646154?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 134,
    ],
    [
        'slug' => 'retail-cosmetics-counter',
        'title' => 'Cosmetics counter',
        'location' => 'Gandhinagar, Gujarat',
        'summary' => 'Premium beauty retail — illuminated counters and material-rich surfaces.',
        'hero_image' => 'uploads/retail/retail-cosmetics-counter.jpg',
        'gallery' => [
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
        ],
        'sort_order' => 135,
    ],
];

$deleteSlugs = array_column($retailProjects, 'slug');
$placeholders = implode(',', array_fill(0, count($deleteSlugs), '?'));
$pdo->prepare("DELETE FROM projects WHERE slug IN ($placeholders)")->execute($deleteSlugs);

$insert = $pdo->prepare(
    'INSERT INTO projects (slug, title, location, category, summary, body_html, hero_image, link_url, home_highlight, home_layout, sort_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)'
);

foreach ($retailProjects as $p) {
    $body = retail_gallery_html($p['gallery'], $p['title']);
    $link = 'project.php?slug=' . rawurlencode($p['slug']);
    $insert->execute([
        $p['slug'],
        $p['title'],
        $p['location'],
        'retail',
        $p['summary'],
        $body,
        $p['hero_image'],
        $link,
        '',
        $p['sort_order'],
    ]);
}

content_sync_site_json($pdo);

echo 'Added ' . count($retailProjects) . ' retail showcase projects with gallery pages.' . PHP_EOL;
