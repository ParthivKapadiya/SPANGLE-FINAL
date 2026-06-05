<?php

declare(strict_types=1);

/**
 * Push SPANGLE hero copy, stats, and branding into MySQL + site.json.
 * Run: php scripts/sync-spangle-home-branding.php
 */

$root = dirname(__DIR__);
require_once $root . '/includes/bootstrap.php';
require_once $root . '/includes/cmsPlainFields.php';

$pdo = Database::connection($configDb);
$brand = require $root . '/includes/brand.php';

$archevoLogo = 'uploads/branding/archevo-logo.png';
$archevoLogoLight = 'uploads/branding/archevo-logo-light.png';
$archevoLogoDark = 'uploads/branding/archevo-logo-dark.png';
$archevoFavicon = 'uploads/branding/archevo-icon.png';

$settings = [
    'site_name' => $brand['name'],
    'brand_name' => $brand['short'],
    'brand_line' => 'Architecture & Interiors',
    'tagline' => 'Architecture & Interiors',
    'public_base' => '',
    'site_logo' => $archevoLogo,
    'site_logo_light' => $archevoLogoLight,
    'site_logo_dark' => $archevoLogoDark,
    'site_favicon' => $archevoFavicon,
    'footer_blurb_1' => $brand['name'] . ' — end-to-end architecture, civil construction, interiors, and turnkey project management from Rajkot, Gujarat.',
    'footer_copyright' => '© 2026 ' . $brand['name'],
    'home_hero_eyebrow' => 'Rajkot · Gujarat · Since 2010',
    'home_hero_title_main' => 'Designing Spaces That Define',
    'home_hero_title_highlight' => 'Generations',
    'home_hero_lead' => 'Architecture, Interiors & Design-Build Solutions Crafted For Modern Living.',
    'home_hero_title_html' => cms_build_hero_title_html('Designing Spaces That Define', 'Generations'),
    'whatsapp_prefill' => $brand['whatsapp_prefill'],
];

require_once $root . '/includes/cmsCopyKeys.php';
$copyKeys = [
    'home_hero_btn_primary_text' => 'Book Consultation',
    'home_hero_btn_secondary_text' => 'Explore Projects',
    'home_hero_btn_secondary_url' => 'work.html',
];
foreach ($copyKeys as $key => $value) {
    setting_set($pdo, $key, $value);
}

setting_set($pdo, 'footer_blurb_html', cms_build_footer_blurb_html($settings['footer_blurb_1'], ''));

foreach ($settings as $key => $value) {
    setting_set($pdo, $key, $value);
}

$stats = [
    ['150+', 'Projects completed'],
    ['16', 'Years experience'],
    ['2M+', 'Sq ft delivered'],
    ['98%', 'Client satisfaction'],
];

$rows = $pdo->query('SELECT id FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
foreach ($stats as $i => $pair) {
    if (!isset($rows[$i])) {
        $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, sort_order) VALUES (?,?,?)')
            ->execute([$pair[0], $pair[1], $i]);
        continue;
    }
    $pdo->prepare('UPDATE home_stats SET stat_value = ?, stat_label = ?, sort_order = ? WHERE id = ?')
        ->execute([$pair[0], $pair[1], $i, (int) $rows[$i]]);
}

content_sync_site_json($pdo);

echo "Archevo hero, stats, and branding synced to database and content/site.json.\n";
