<?php

declare(strict_types=1);

function cms_seed_defaults(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $testimonials = [
        ['“Our living and bedroom layouts were planned practically. The team was on site when it mattered and handover was smooth.”', 'Hareshbhai', 'Residence · Rajkot'],
        ['“3D views and drawings helped us decide finishes before work started. Plan-related queries were handled without us chasing offices.”', 'Kantilalbhai', 'Residence · Rajkot'],
        ['“Plot layout and front elevation matched what we discussed. One studio for design and coordination through construction.”', 'Sanjaysinh Jadeja', 'Plot & residence · Rajkot'],
        ['“Interior detailing and civil work moved in step. We always knew who to call and what was happening next.”', 'Arvind Parmar', 'Residence · Rajkot'],
    ];
    $stmt = $pdo->prepare('INSERT INTO testimonials (quote, author_name, author_role, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($testimonials as $i => $t) {
        $stmt->execute([$t[0], $t[1], $t[2], $i]);
    }

    $team = [
        ['Jay Rathod', 'Director', 'Leads project direction, client relationships, and overall delivery for Archevo Infra Edge across Gujarat.', 'JR'],
        ['Kishan Tank', 'Director', 'Oversees design, interiors, and site coordination — from drawings and 3D through execution.', 'KT'],
        ['Jignesh Sekhva', 'Engineer', 'Technical drawings, structural coordination, and on-site engineering support for civil and approval work.', 'JS'],
    ];
    $stmt = $pdo->prepare('INSERT INTO team_members (name, role_title, bio, initials, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($team as $i => $m) {
        $stmt->execute([$m[0], $m[1], $m[2], $m[3], $i]);
    }

    $steps = [
        ['I', 'Discovery', 'Site, aspirations, and feasibility — aligned before pencil hits paper.', 'both'],
        ['II', 'Design', 'Schematic through tender-ready drawings, models, and sample boards.', 'both'],
        ['III', 'Delivery', 'Site administration, RFIs, and vendor coordination until handover.', 'both'],
        ['IV', 'Close-out', 'Styling, documentation, and photography — space ready to live in.', 'both'],
    ];
    $stmt = $pdo->prepare('INSERT INTO process_steps (step_label, title, description, context, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($steps as $i => $s) {
        $stmt->execute([$s[0], $s[1], $s[2], $s[3], $i]);
    }

    $awards = [
        ['fas fa-map-location-dot', 'Rajkot & Saurashtra', 'On-ground experience across Gujarat — not a distant design-only studio.'],
        ['fas fa-file-lines', 'Approvals support', 'Drawings and submissions prepared for local plan-sanctioning requirements.'],
        ['fas fa-handshake', 'Referral-led work', 'A large share of new projects come from past clients and word of mouth.'],
        ['fas fa-house-chimney', 'Turnkey under one roof', 'Design, civil, and interiors coordinated by one accountable team.'],
    ];
    $stmt = $pdo->prepare('INSERT INTO awards (icon_class, title, subtitle, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($awards as $i => $a) {
        $stmt->execute([$a[0], $a[1], $a[2], $i]);
    }

    $journal = [
        ['journal-materiality', 'Materiality in Indian light', 'How finishes behave under harsh sun and monsoon.', 'uploads/054-KANTILAL-3D-5.jpg'],
        ['journal-sustainable', 'Sustainable architecture in 2026', 'Passive cooling, honest materials, and long-life envelopes.', 'uploads/LIVING 02.jpg'],
        ['journal-quiet-luxury', 'Quiet luxury interiors', 'Restraint, texture, and light as the real ornament.', 'uploads/1228_HARESHBHAI_LIVING_3.jpg'],
        ['journal-workplaces', 'Workplaces that perform', 'Acoustics, focus zones, and hospitality moments for teams.', 'uploads/1071-SANJAYSINH JADEJA_4.jpg'],
    ];
    $stmt = $pdo->prepare('INSERT INTO journal_posts (slug, title, excerpt, image_path, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($journal as $i => $j) {
        $stmt->execute([$j[0], $j[1], $j[2], $j[3], $i]);
    }

    $defaults = [
        'site_logo' => 'uploads/branding/archevo-logo.png',
        'site_logo_light' => 'uploads/branding/archevo-logo-light.png',
        'site_logo_dark' => 'uploads/branding/archevo-logo-dark.png',
        'brand_name' => 'SPANGLE',
        'brand_line' => 'Integrated Design & Build',
        'nav_studio_label' => 'Studio',
        'nav_studio_href' => 'studio.html',
        'nav_services_label' => 'Services',
        'nav_services_href' => 'services.html',
        'nav_work_label' => 'Work',
        'nav_work_href' => 'work.html',
        'nav_process_label' => 'Process',
        'nav_process_href' => 'process.html',
        'nav_journal_label' => 'Journal',
        'nav_journal_href' => 'journal.html',
        'nav_contact_label' => 'Enquire',
        'nav_contact_href' => 'contact.html',
        'footer_blurb_html' => '<p>SPANGLE Architecture & Interior Design Studio — end-to-end architecture, civil construction, interiors, and turnkey project management from Rajkot, Gujarat.</p>',
        'footer_copyright' => '© 2026 SPANGLE Architecture & Interior Design Studio · SPANGLE Architecture & Interior Design Studio',
        'home_capabilities_eyebrow' => 'Capabilities',
        'home_capabilities_title' => 'What we design',
        'home_capabilities_intro' => 'Integrated commissions — from shell strategy to the last layer of light and texture.',
        'home_process_eyebrow' => 'Process',
        'home_process_title' => 'How we move from brief to keys',
        'home_process_intro' => 'Clear phases, shared tools, and sign-off moments — so you always know what happens next.',
        'home_testimonials_eyebrow' => 'Clients',
        'home_testimonials_title' => 'What clients say',
        'home_awards_eyebrow' => 'Studio',
        'home_awards_title' => 'Why clients work with us',
        'home_team_eyebrow' => 'People',
        'home_team_title' => 'Leadership',
        'home_journal_eyebrow' => 'Journal',
        'home_journal_title' => 'Latest insights',
        'home_cta_eyebrow' => 'Next project',
        'home_cta_title' => 'Reserve a studio conversation',
        'home_cta_lead' => 'Share your site, timeline, and ambitions — we respond with a clear path and indicative scope.',
        'home_cta_btn_text' => 'Plan your space',
        'home_cta_btn_url' => 'contact.html',
        'work_kicker' => 'Portfolio',
        'work_title' => 'Selected work',
        'work_lead' => 'Residential, commercial, and retail — conceived as one coherent story from street to detail.',
        'work_hero_image' => 'uploads/1523-HARSHITBHAI-5_ER.jpg.jpeg',
        'contact_hero_kicker' => 'Enquiries',
        'contact_hero_title' => 'Let\'s talk about your project',
        'contact_hero_lead' => 'Tell us about your site, timeline, and ambitions. We respond within two business days with next steps.',
        'contact_hero_image' => 'uploads/ENTRY.jpg',
        'contact_hours_html' => '<p><strong>Studio hours</strong><br>Mon–Fri · 10:00–18:00<br>Saturday · By appointment</p>',
        'process_kicker' => 'Method',
        'process_title' => 'Clarity at every milestone',
        'process_lead' => 'You always know where we are in the journey — what decisions are due, what we need from you, and what happens next on site.',
        'process_hero_image' => 'uploads/LIVING_ROOM_2-1.jpg',
        'process_split_eyebrow' => 'Engagement',
        'process_split_title' => 'Phased gates, shared tools',
        'process_split_lead_html' => '<p class="section-lead">We structure work into clear phases with sign-off moments. Drawings, 3D studies, and sample boards live in a shared workspace so feedback is captured once — not lost across threads.</p><p class="section-lead">On site, our project directors hold weekly coordination meetings with contractors and vendors until handover is complete.</p>',
        'process_split_paragraph_1' => 'We structure work into clear phases with sign-off moments. Drawings, 3D studies, and sample boards live in a shared workspace so feedback is captured once — not lost across threads.',
        'process_split_paragraph_2' => 'On site, our project directors hold weekly coordination meetings with contractors and vendors until handover is complete.',
        'process_split_image' => 'uploads/066-UPENDRASINH-3D-3.jpg',
        'process_timeline_eyebrow' => 'Timeline',
        'process_timeline_title' => 'From brief to keys',
        'journal_kicker' => 'Journal',
        'journal_title' => 'Ideas from the studio',
        'journal_lead' => 'Notes on materiality, process, and the culture of space — for clients and collaborators.',
        'journal_hero_image' => 'uploads/1228_HARESHBHAI_LIVING_4.jpg',
        'studio_values_eyebrow' => 'In the studio',
        'studio_values_title' => 'Three disciplines, one table',
        'studio_values_html' => '<div class="value-card"><h3>Architecture</h3><p>Envelope, structure, and light — the bones of every commission.</p></div><div class="value-card"><h3>Interiors</h3><p>Flow, joinery, and atmosphere — where daily life meets craft.</p></div><div class="value-card"><h3>Delivery</h3><p>Site rhythm and quality control — design intent through handover.</p></div>',
        'studio_pullquote' => 'We design for how spaces feel at 7am and at dusk — not only how they photograph.',
    ];

    foreach ($defaults as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ?');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}

function cms_seed_copy_settings(PDO $pdo): void
{
    require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';
    foreach (cms_copy_defaults() as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ?');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}
