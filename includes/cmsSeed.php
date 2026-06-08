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
        ['Jay P. Rathood', 'Director', 'Leads project direction, client relationships, and overall delivery for Archevo Infra Edge across Gujarat.', 'JR'],
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
        ['V', 'Approvals', 'Plan sanctioning, authority coordination, and compliance documentation.', 'page'],
        ['VI', 'Construction', 'Site administration, quality checks, and vendor coordination on site.', 'page'],
        ['VII', 'Interior execution', 'Joinery, finishes, FF&E, and styling aligned with the design intent.', 'page'],
        ['VIII', 'Handover', 'Snag resolution, documentation, and keys — space ready to occupy.', 'page'],
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
        'nav_contact_label' => 'Contact',
        'nav_contact_href' => 'contact.html',
        'nav_enquire_label' => 'Enquire',
        'nav_enquire_href' => 'contact.html',
        'footer_blurb_html' => '<p>Archevo Design — end-to-end architecture, civil construction, interiors, and turnkey project management from Rajkot, Gujarat.</p>',
        'footer_copyright' => '© 2026 Archevo Design · Archevo Infra Edge Pvt Ltd',
        'home_capabilities_eyebrow' => 'Capabilities',
        'home_capabilities_title' => 'What we design',
        'home_capabilities_intro' => 'Integrated commissions — from shell strategy to the last layer of light and texture.',
        'home_process_eyebrow' => 'Process',
        'home_process_title' => 'How we move from brief to keys',
        'home_process_intro' => 'Clear phases, shared tools, and sign-off moments — so you always know what happens next.',
        'home_impact_eyebrow' => 'Impact',
        'home_impact_title' => 'Built at scale. Trusted at home.',
        'home_testimonials_eyebrow' => 'Clients',
        'home_testimonials_title' => 'What clients say',
        'home_awards_eyebrow' => 'Studio',
        'home_awards_title' => 'Why clients work with us',
        'home_cta_eyebrow' => 'Next project',
        'home_cta_title' => 'Reserve a studio conversation',
        'home_cta_lead' => 'Share your site, timeline, and ambitions — we respond with a clear path and indicative scope.',
        'home_cta_sub' => 'Architecture · Interiors · Construction · Turnkey Delivery',
        'home_cta_btn_text' => 'Plan your space',
        'home_cta_btn_url' => 'contact.html',
        'work_kicker' => 'Portfolio',
        'work_title' => "Built To Last.\nDesigned To Inspire.",
        'work_lead' => 'A curated selection of commissions — each shaped by context, brief, and craft across Western India.',
        'work_hero_image' => 'uploads/1523-HARSHITBHAI-5_ER.jpg.jpeg',
        'work_featured_eyebrow' => 'Featured commission',
        'work_stats_eyebrow' => 'Experience',
        'work_stats_title' => 'Built on measurable impact',
        'work_categories_eyebrow' => 'Disciplines',
        'work_categories_title' => 'Explore by category',
        'work_testimonials_eyebrow' => 'Clients',
        'work_testimonials_title' => 'Trusted for execution & design',
        'work_timeline_eyebrow' => 'Growth',
        'work_timeline_title' => 'A decade of delivery',
        'work_timeline_intro' => 'Projects completed year by year — evidence of sustained practice and expanding scope.',
        'work_trust_eyebrow' => 'Why Archevo',
        'work_trust_title' => 'Why clients choose Archevo',
        'work_cta_final_eyebrow' => 'Next project',
        'work_cta_final_title' => 'Ready To Build Something Exceptional?',
        'work_cta_final_sub' => "Architecture.\nInteriors.\nConstruction.\nTurnkey Delivery.",
        'work_cta_final_btn_text' => 'Start Your Project',
        'work_cta_final_btn2_text' => 'Book Consultation',
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
