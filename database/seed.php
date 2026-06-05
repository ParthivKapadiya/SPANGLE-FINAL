<?php

declare(strict_types=1);

/**
 * Seed database from content/site.json and create default admin.
 * Run via install.php or: php database/seed.php
 */

if (isset($GLOBALS['installSeedPdo']) && $GLOBALS['installSeedPdo'] instanceof PDO) {
    $pdo = $GLOBALS['installSeedPdo'];
} else {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
    Database::reset();
    $pdo = Database::connection($GLOBALS['configDb'] ?? $configDb);
}

$jsonPath = SPANGLE_ROOT . '/content/site.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, "Missing content/site.json\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid site.json\n");
    exit(1);
}

$c = $data['contact'] ?? [];
$home = $data['home'] ?? [];
$social = $data['social'] ?? [];
$maps = $data['maps'] ?? [];
$seo = $data['seo'] ?? [];

$settings = [
    'public_base' => $data['publicBase'] ?? '',
    'site_name' => $data['siteName'] ?? 'Archevo Design',
    'tagline' => $data['tagline'] ?? '',
    'contact_phone_e164' => $c['phoneE164'] ?? '',
    'contact_phone_display' => $c['phoneDisplay'] ?? '',
    'contact_email' => $c['email'] ?? '',
    'contact_address' => $c['addressLine'] ?? '',
    'whatsapp_digits' => $c['whatsappDigits'] ?? '',
    'whatsapp_prefill' => $c['whatsappPrefill'] ?? '',
    'contact_section_title' => $c['contactSectionTitle'] ?? '',
    'contact_section_lead' => $c['contactSectionLead'] ?? '',
    'contact_page_title' => $c['contactPageTitle'] ?? '',
    'contact_page_lead' => $c['contactPageLead'] ?? '',
    'social_instagram' => $social['instagram'] ?? '',
    'social_facebook' => $social['facebook'] ?? '',
    'social_youtube' => $social['youtube'] ?? '',
    'map_embed_url' => $maps['embedUrl'] ?? '',
    'map_title' => $maps['title'] ?? '',
    'seo_description' => $seo['organizationDescription'] ?? '',
    'seo_og_image' => $seo['defaultOgImage'] ?? '',
    'home_hero_eyebrow' => $home['heroEyebrow'] ?? '',
    'home_hero_title_html' => $home['heroTitleHtml'] ?? '',
    'home_hero_lead' => $home['heroLead'] ?? '',
    'home_about_eyebrow' => $home['aboutEyebrow'] ?? '',
    'home_about_title' => $home['aboutTitle'] ?? '',
    'home_about_lead_html' => $home['aboutLeadHtml'] ?? '',
    'home_about_image' => 'uploads/1228_HARESHBHAI_LIVING_5.jpg',
    'home_about_image_alt' => 'Layered interior with natural light',
    'home_about_caption' => 'Studio atmosphere · Rajkot',
    'home_gallery_eyebrow' => $home['galleryEyebrow'] ?? '',
    'home_gallery_title' => $home['galleryTitle'] ?? '',
    'home_gallery_intro' => $home['galleryIntro'] ?? '',
    'home_projects_eyebrow' => $home['projectsEyebrow'] ?? '',
    'home_projects_title' => $home['projectsTitle'] ?? '',
    'home_projects_intro' => $home['projectsIntro'] ?? '',
    'studio_kicker' => 'Rajkot · Gujarat',
    'studio_title' => 'A practice built on patience and precision',
    'studio_lead' => 'We are architects and interior designers who believe the best spaces feel obvious in hindsight.',
    'studio_hero_image' => 'uploads/1212-ARVINDBHAI PARMAR_FRONT-2.jpg',
    'studio_philosophy_eyebrow' => 'Philosophy',
    'studio_philosophy_title' => 'Quiet confidence',
    'studio_philosophy_lead_1' => 'Archevo Design offers end-to-end design and build from Rajkot, Gujarat.',
    'studio_philosophy_lead_2' => 'We do not chase novelty for its own sake.',
    'studio_philosophy_image' => 'uploads/1228_HARESHBHAI_LIVING_3.jpg',
    'services_kicker' => 'What we offer',
    'services_title' => 'Design that travels from sketch to site',
    'services_lead' => 'Integrated architecture, interiors, and delivery — one studio, one contract.',
    'services_hero_image' => 'uploads/054-KANTILAL-3D-6.jpg',
];

foreach ($settings as $k => $v) {
    setting_set($pdo, $k, (string) $v);
}

$pdo->exec('DELETE FROM home_stats');
$sort = 0;
foreach ($home['stats'] ?? [] as $stat) {
    $stmt = $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, sort_order) VALUES (?, ?, ?)');
    $stmt->execute([$stat['value'] ?? '', $stat['label'] ?? '', $sort++]);
}

$pdo->exec('DELETE FROM hero_slides');
$heroImages = [
    ['uploads/ENTRY.jpg', 'Archevo Design entry'],
    ['uploads/1228_HARESHBHAI_LIVING_5.jpg', 'Living room interior'],
    ['uploads/1159-VISALBHAI RAMPARIYA-5.jpg', 'Residential interior'],
    ['uploads/LIVING 01.jpg', 'Modern living space'],
];
$sort = 0;
foreach ($heroImages as $h) {
    $stmt = $pdo->prepare('INSERT INTO hero_slides (image_path, alt_text, sort_order) VALUES (?, ?, ?)');
    $stmt->execute([$h[0], $h[1], $sort++]);
}

$pdo->exec('DELETE FROM gallery_items');
$sort = 0;
foreach ($data['gallery'] ?? [] as $g) {
    $stmt = $pdo->prepare('INSERT INTO gallery_items (image_path, alt_text, caption, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$g['src'] ?? '', $g['alt'] ?? '', $g['caption'] ?? '', $sort++]);
}

$pdo->exec('DELETE FROM projects');
$sort = 0;
foreach ($data['projects'] ?? [] as $p) {
    $stmt = $pdo->prepare(
        'INSERT INTO projects (slug, title, location, category, summary, hero_image, link_url, home_highlight, home_layout, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $p['slug'] ?? 'project-' . $sort,
        $p['title'] ?? '',
        $p['location'] ?? '',
        $p['category'] ?? 'residential',
        $p['summary'] ?? '',
        $p['heroImage'] ?? '',
        $p['linkUrl'] ?? 'work.html',
        !empty($p['homeHighlight']) ? 1 : 0,
        $p['homeLayout'] ?? '',
        $sort++,
    ]);
}

$pdo->exec('DELETE FROM services');
$defaultServices = [
    ['01', 'Architecture', 'Massing, structure-aware planning, façades, and services integration.', '01 — Architecture', 'Shell & strategy', 'Site analysis, zoning compliance, volumetric studies.', 'We optimise orientation and shading.', 'uploads/054-KANTILAL-3D-6.jpg'],
    ['02', 'Interiors', 'Spatial flow, bespoke joinery, lighting design, and material specification.', '02 — Interiors', 'Atmosphere & detail', 'Spatial planning, lighting layers, bespoke joinery.', 'Every elevation is coordinated with services.', 'uploads/1228_HARESHBHAI_LIVING_5.jpg'],
    ['03', 'Delivery', 'Site rhythm, vendor coordination, and quality control through handover.', '03 — Delivery', 'Execution you can trust', 'BOQs, vendor shortlists, site visits, snag lists.', 'Optional turnkey packages available.', 'uploads/LIVING_ROOM_2-1.jpg'],
];
$sort = 0;
foreach ($defaultServices as $svc) {
    $stmt = $pdo->prepare(
        'INSERT INTO services (number_label, title, short_description, eyebrow, detail_title, detail_lead_1, detail_lead_2, image_path, show_on_home, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
    );
    $stmt->execute([$svc[0], $svc[1], $svc[2], $svc[3], $svc[4], $svc[5], $svc[6], $svc[7], $sort++]);
}

// Default admin — change password after first login
$username = 'admin';
$email = 'admin@spangle.local';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if (!$stmt->fetch()) {
    $ins = $pdo->prepare('INSERT INTO admins (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)');
    $ins->execute([$username, $email, $hash, 'Studio Admin']);
} else {
    $pdo->prepare('UPDATE admins SET email = ? WHERE username = ? AND (email IS NULL OR email = "")')
        ->execute([$email, $username]);
}

echo "Seed complete.\n";
echo "Admin login: admin (or admin@spangle.local) / password admin123 (change immediately)\n";
