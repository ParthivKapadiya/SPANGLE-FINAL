<?php

declare(strict_types=1);

/**
 * One-time: apply Archevo client info from business card & services flyer.
 * Run: php scripts/apply-archevo-client-info.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);
$brand = require dirname(__DIR__) . '/includes/brand.php';

$settings = [
    'site_name' => $brand['name'],
    'brand_name' => $brand['short'],
    'brand_line' => 'Integrated Design & Build',
    'tagline' => 'Integrated Design & Build Solutions',
    'footer_blurb_html' => '<p>Archevo Design — end-to-end architecture, civil construction, interiors, and turnkey project management from Rajkot, Gujarat.</p>',
    'footer_copyright' => '© 2026 Archevo Design · Archevo Infra Edge Pvt Ltd',
    'contact_phone_e164' => '+916359351513',
    'contact_phone_display' => '+91 63593 51513',
    'contact_email' => 'archevodesign6@gmail.com',
    'contact_address' => '1113, RK Supreme, Nanamava Circle, Opp. Twin Star, 150 Ft. Ring Rd, Rajkot, Gujarat',
    'whatsapp_digits' => '916359351513',
    'whatsapp_prefill' => $brand['whatsapp_prefill'],
    'contact_section_title' => 'Rajkot · Gujarat',
    'contact_section_lead' => 'Call, email, or visit by appointment. Site meetings across Saurashtra and Gujarat are scheduled in advance.',
    'contact_page_title' => 'Archevo Design · Rajkot',
    'contact_page_lead' => 'Reach Jay Rathod and the studio team for new projects, plan approvals, interiors, or turnkey delivery.',
    'home_capabilities_eyebrow' => 'What we do',
    'home_capabilities_title' => 'Integrated design & build solutions',
    'home_capabilities_intro' => 'At Archevo Design, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from brief to handover.',
    'services_kicker' => 'What we do',
    'services_title' => 'Integrated design & build solutions',
    'services_lead' => 'At Archevo Design, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from brief to handover.',
    'services_cta_lead' => 'Share your site, scope, and timeline — we will recommend design-only, approval support, or full turnkey delivery.',
    'studio_philosophy_title' => 'From design vision to built reality',
    'studio_philosophy_lead_1' => 'Archevo Design offers end-to-end services across civil engineering, plan sanctioning, interior design, and turnkey project management — one accountable team for homes, workplaces, and developments in Gujarat.',
    'studio_philosophy_lead_2' => 'We execute with integrity, intelligence, and innovation — coordinating authorities, contractors, and vendors so you have a single point of contact from drawing board to keys.',
    'studio_pullquote' => 'From design vision to built reality — we execute with integrity, intelligence, and innovation.',
    'studio_kicker' => 'Rajkot · Gujarat',
    'studio_title' => 'Archevo Design',
    'studio_lead' => 'Integrated design and build — civil engineering, plan approvals, interiors, and turnkey project management from Rajkot.',
    'studio_values_html' => '<div class="value-card"><h3>Approvals &amp; planning</h3><p>Drawings, municipal liaison, and regulatory compliance under local development rules.</p></div><div class="value-card"><h3>Interiors &amp; execution</h3><p>Space planning, 3D visualization, modular fit-outs, and turnkey interior delivery.</p></div><div class="value-card"><h3>Build &amp; management</h3><p>Civil construction, procurement, labour management, and handover — one studio, one contract.</p></div>',
    'seo_organization_description' => 'Archevo Design — integrated architecture, civil construction, interior design, and turnkey project management in Rajkot, Gujarat.',
    'public_website_url' => $brand['default_url'],
    'social_instagram' => 'https://www.instagram.com/archevo._.design/',
    'social_facebook' => 'https://www.facebook.com/share/1D1tnk6xVr/',
    'map_embed_url' => 'https://www.google.com/maps?q=1113%2C+RK+Supreme%2C+Nanamava+Circle%2C+Opp.+Twin+Star%2C+150+Ft.+Ring+Rd%2C+Rajkot%2C+Gujarat&output=embed',
    'map_title' => 'Archevo Design · RK Supreme, Nanamava Circle, Rajkot',
    'seo_home_title' => 'Archevo Design | Architecture & Interior Design · Rajkot',
    'seo_home_description' => 'Archevo Design — integrated design, civil construction, interiors, and turnkey delivery across Gujarat.',
    'seo_studio_title' => 'Studio | Archevo Design',
    'seo_work_title' => 'Work | Archevo Design',
    'seo_journal_title' => 'Journal | Archevo Design',
    'seo_contact_title' => 'Contact | Archevo Design',
    'legal_privacy_html' => '<p class="section-lead">Last updated: January 2026. This policy describes how Archevo Design and Archevo Infra Edge Pvt Ltd (“we”) handle information when you use this website or contact us.</p><h2>Information we collect</h2><p>When you submit an enquiry through our website contact form, we collect the details you provide (such as your name, email address, phone number if given, project type if given, and message). Each submission is stored securely on our server and a notification is emailed to our studio team so we can respond. If you contact us directly by email or phone, we receive the information you choose to share.</p><h2>How we use it</h2><p>We use contact information only to respond to enquiries, prepare proposals, and manage projects. We do not sell personal data.</p><h2>Retention</h2><p>Enquiry records are kept for as long as needed to fulfil our contract and legal obligations, then deleted or anonymised according to our internal policy.</p><h2>Your rights</h2><p>Depending on applicable law, you may request access, correction, or deletion of your personal data. Contact <a href="mailto:archevodesign6@gmail.com">archevodesign6@gmail.com</a>.</p><h2>Changes</h2><p>We may update this page periodically. Continued use of the site after changes constitutes acceptance of the updated policy.</p>',
    'enquiry_notify_email' => 'archevodesign6@gmail.com',
    'seo_description' => 'Archevo Design is a premium architecture and interior design studio in Gujarat — residential, commercial, and bespoke spaces crafted with precision.',
    'legal_terms_html' => '<p class="section-lead">By using this website you agree to these terms. Content, images, and text are owned by Archevo Design unless otherwise credited.</p><p>Information on this site is for general guidance and does not constitute a contract until agreed in writing. We reserve the right to update these terms at any time.</p>',
];

foreach ($settings as $key => $val) {
    setting_set($pdo, $key, $val);
}

$pdo->exec('DELETE FROM services');

$services = [
    [
        '01', 'Plan sanctioning & approvals', 'Architectural drawings, authority liaison, and regulatory compliance.',
        '01 — Approvals', 'Plan sanctioning & authority approvals',
        'Architectural drawing preparation and documentation aligned to your plot and programme.',
        'Liaison with municipal and town-planning authorities; compliance with local development control rules.',
        'uploads/054-KANTILAL-3D-6.jpg', 1, 1,
    ],
    [
        '02', 'Interior design & execution', 'Space planning, 3D visuals, materials, and turnkey interior delivery.',
        '02 — Interiors', 'Interior design & execution',
        'Space planning, 3D visualization, material selection, lighting, false ceiling, and wall finishes.',
        'Modular furniture, joinery, and turnkey interior project delivery — documented for site.',
        'uploads/1228_HARESHBHAI_LIVING_5.jpg', 1, 1,
    ],
    [
        '03', 'Civil construction', 'Residential and commercial structure execution across Gujarat.',
        '03 — Construction', 'Civil construction',
        'Residential and commercial structure execution with disciplined site supervision.',
        'RCC work, brick masonry, plastering, waterproofing, and coordinated MEP interfaces.',
        'uploads/1212-ARVINDBHAI PARMAR_FRONT-2.jpg', 1, 1,
    ],
    [
        '04', 'Turnkey project management', 'From design brief to handover — one point of contact.',
        '04 — Turnkey', 'Turnkey project management',
        'From design brief to handover: procurement, labour management, and vendor coordination.',
        'A single accountable team for design, approvals, build, and interior completion.',
        'uploads/LIVING_ROOM_2-1.jpg', 1, 1,
    ],
    [
        '05', 'Consulting civil engineering', 'Structural advice, site feasibility, and buildability reviews.',
        '05 — Engineering', 'Consulting civil engineer',
        'Structural and civil engineering consultation for new builds and renovations.',
        'Feasibility studies, BOQs, and coordination with architects and contractors.',
        'uploads/058-PRAKASHBHAI TANK-3D-2.jpg', 1, 1,
    ],
    [
        '06', 'Building plan & NA layouts', 'Plan approval support, layouts, and land survey.',
        '06 — Planning', 'Building plan approval & NA layouts',
        'Building plan approval pathways and documentation for residential and commercial plots.',
        'NA layouts, land survey, and plot planning support across Gujarat.',
        'uploads/1213-SANJAYSINH JADEJA_PLOT-62-1.jpg', 1, 1,
    ],
    [
        '07', '3D design & visualization', 'Concept renders and presentation models for client sign-off.',
        '07 — Visualization', '3D designing & visualization',
        'Photorealistic 3D renders and walkthroughs for architecture and interior schemes.',
        'Material and lighting studies before work starts on site — fewer surprises at execution.',
        'uploads/066-UPENDRASINH-3D-3.jpg', 1, 1,
    ],
];

$pdo->exec('UPDATE services SET show_on_home = 1 WHERE is_active = 1');

$stmt = $pdo->prepare(
    'INSERT INTO services (number_label, title, short_description, eyebrow, detail_title,
     detail_lead_1, detail_lead_2, image_path, show_on_home, sort_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

foreach ($services as $i => $s) {
    $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $i, $s[9]]);
}

content_sync_site_json($pdo);

echo "Archevo client info applied. site.json synced.\n";
