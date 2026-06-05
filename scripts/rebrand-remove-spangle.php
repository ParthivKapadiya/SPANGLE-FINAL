<?php

declare(strict_types=1);

/**
 * Remove SPANGLE branding from public site content (DB + JSON + HTML).
 * Run: php scripts/rebrand-remove-spangle.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);

$brand = 'SPANGLE Architecture & Interior Design Studio';
$company = 'SPANGLE Architecture & Interior Design Studio';
$siteUrl = 'https://spangle.page.gd';

$settings = [
    'site_name' => $brand,
    'seo_organization_description' => "{$brand} — integrated architecture, civil construction, interior design, and turnkey project management in Rajkot, Gujarat.",
    'legal_privacy_html' => '<p class="section-lead">This policy describes how ' . $company . ' collects and uses information when you visit our website or submit an enquiry.</p><p>We collect information you provide in contact forms (name, email, phone, message). We use it only to respond to your enquiry and manage our client relationship. We do not sell your data to third parties.</p><p>For questions about this policy, contact us via the details on our contact page.</p>',
    'legal_terms_html' => '<p class="section-lead">By using this website you agree to these terms. Content, images, and text are owned by ' . $company . ' unless otherwise credited.</p><p>Information on this site is for general guidance and does not constitute a contract until agreed in writing. We reserve the right to update these terms at any time.</p>',
    'seo_home_title' => "{$brand} | Architecture & Interior Design · Rajkot",
    'seo_home_description' => "{$brand} — integrated design, civil construction, interiors, and turnkey delivery across Gujarat.",
    'seo_studio_title' => "Studio | {$brand}",
    'seo_studio_description' => "Meet {$brand} — philosophy, team, and integrated design & build in Rajkot, Gujarat.",
    'seo_services_title' => "Services | {$brand}",
    'seo_services_description' => "Plan approvals, interiors, civil construction, and turnkey projects from {$brand}.",
    'seo_work_title' => "Work | {$brand}",
    'seo_work_description' => "Selected residential, commercial, and retail projects by {$brand}.",
    'seo_process_title' => "Process | {$brand}",
    'seo_process_description' => "How {$brand} moves from brief to handover — clear phases and accountability.",
    'seo_journal_title' => "Journal | {$brand}",
    'seo_journal_description' => "Ideas on materiality, process, and the culture of space from {$brand}.",
    'seo_contact_title' => "Contact | {$brand}",
    'seo_contact_description' => "Contact {$brand} in Rajkot for architecture and interior design enquiries.",
    'seo_privacy_title' => "Privacy | {$brand}",
    'seo_privacy_description' => "Privacy policy for {$company} website.",
    'seo_terms_title' => "Terms | {$brand}",
    'seo_terms_description' => "Terms of use for the {$brand} website.",
    'seo_thanks_title' => "Thank you | {$brand}",
    'seo_thanks_description' => "Thank you for contacting {$brand}.",
    'home_gallery_intro' => 'A curated library of interiors, architecture, and 3D visualisations from recent Archevo commissions across Gujarat.',
    'home_about_lead_html' => '<p class="section-lead">' . $company . ' is a full-service studio for architecture, interiors, and delivery. We work from first sketch through site administration — one accountable team, aligned incentives, and drawings that contractors can actually build.</p><p class="section-lead">Our work favours honest materials, generous daylight, and envelopes tuned for Gujarat’s climate. The result feels calm, durable, and unmistakably yours.</p>',
];

foreach ($settings as $key => $val) {
    setting_set($pdo, $key, $val);
}

$pdo->exec("UPDATE testimonials SET quote = REPLACE(quote, 'SPANGLE', 'SPANGLE Architecture & Interior Design Studio') WHERE quote LIKE '%SPANGLE%'");

content_sync_site_json($pdo);

$root = dirname(__DIR__);
$replacements = [
    'https://www.spangle.studio' => $siteUrl,
    'http://www.spangle.studio' => $siteUrl,
    'SPANGLE | Architecture &amp; Interior Design Studio' => "{$brand} | Architecture &amp; Interior Design · Rajkot",
    'SPANGLE | Architecture & Interior Design Studio' => "{$brand} | Architecture & Interior Design · Rajkot",
    'SPANGLE Design' => $brand,
    'SPANGLE Journal' => "{$brand} Journal",
    'SPANGLE Studio' => $brand,
    'SPANGLE Architecture &amp; Interiors' => $company,
    'SPANGLE Architecture & Interiors' => $company,
    'SPANGLE Admin' => "{$brand} Admin",
    'SPANGLEADMIN' => 'ARCHEVOADMIN',
    'the SPANGLE studio' => $brand,
    'from SPANGLE' => "from {$brand}",
    'from SPANGLE studio' => "from {$brand}",
    'by SPANGLE' => "by {$brand}",
    'by SPANGLE Design' => "by {$brand}",
    'by SPANGLE.' => "by {$brand}.",
    'Meet SPANGLE' => "Meet {$brand}",
    'How SPANGLE' => "How {$brand}",
    'Contact SPANGLE' => "Contact {$brand}",
    'At SPANGLE' => "At {$brand}",
    'owned by SPANGLE' => "owned by {$company}",
    'SPANGLE is not' => "{$company} is not",
    'SPANGLE is a' => "{$brand} is a",
    'SPANGLE is ' => "{$brand} is ",
    'SPANGLE moved' => "{$brand} moved",
    'SPANGLE stayed' => 'SPANGLE Architecture & Interior Design Studio stayed',
    'SPANGLE transformed' => 'SPANGLE Architecture & Interior Design Studio transformed',
    'SPANGLE project' => 'SPANGLE Architecture & Interior Design Studio project',
    'public SPANGLE site' => "public {$brand} site",
    'SPANGLE on ' => "{$brand} on ",
    '· SPANGLE' => "· {$brand}",
    '| SPANGLE' => "| {$brand}",
    'SPANGLE.' => "{$brand}.",
    'SPANGLE,' => "{$brand},",
    'SPANGLE ' => "{$brand} ",
    'SPANGLE"' => "{$brand}\"",
    'SPANGLE)' => "{$brand})",
];

$skipInPath = ['/admin/vendor/', '/node_modules/', '/.git/'];
$htmlCount = 0;
$jsonCount = 0;

foreach (array_merge(
    glob($root . '/*.html') ?: [],
    glob($root . '/content/*.json') ?: [],
    [$root . '/site.json', $root . '/sitemap.xml']
) as $file) {
    $rel = str_replace($root, '', $file);
    foreach ($skipInPath as $skip) {
        if (str_contains($rel, $skip)) {
            continue 2;
        }
    }
    $text = file_get_contents($file);
    if ($text === false) {
        continue;
    }
    $orig = $text;
    foreach ($replacements as $from => $to) {
        $text = str_replace($from, $to, $text);
    }
    if ($text !== $orig) {
        file_put_contents($file, $text);
        if (str_ends_with($file, '.html')) {
            $htmlCount++;
        } else {
            $jsonCount++;
        }
        echo "Updated {$rel}\n";
    }
}

echo "DB settings + testimonials updated. site.json synced.\n";
echo "HTML files touched: {$htmlCount}, JSON/XML: {$jsonCount}\n";
