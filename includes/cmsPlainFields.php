<?php

declare(strict_types=1);

/**
 * Build public-site HTML from plain admin fields (no raw HTML for clients).
 */

function cms_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cms_build_hero_title_html(string $main, string $highlight = ''): string
{
    $main = trim($main);
    $highlight = trim($highlight);
    if ($main === '') {
        return '';
    }
    if ($highlight === '') {
        return cms_escape($main);
    }

    if (function_exists('str_ends_with') && str_ends_with($main, $highlight)) {
        $prefix = rtrim(substr($main, 0, -strlen($highlight)));

        return cms_escape($prefix) . ' <em>' . cms_escape($highlight) . '</em>';
    }

    return cms_escape($main) . ' <em>' . cms_escape($highlight) . '</em>';
}

function cms_parse_hero_title_html(string $html): array
{
    $html = trim($html);
    if ($html === '') {
        return ['main' => '', 'highlight' => ''];
    }
    if (preg_match('/^(.*)<em>(.*?)<\/em>/is', $html, $m)) {
        return [
            'main' => trim(strip_tags($m[1])),
            'highlight' => trim(strip_tags($m[2])),
        ];
    }

    return ['main' => trim(strip_tags($html)), 'highlight' => ''];
}

function cms_build_about_lead_html(string $paragraph1, string $paragraph2 = ''): string
{
    $out = '';
    foreach ([$paragraph1, $paragraph2] as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out .= '<p class="section-lead">' . cms_escape($p) . '</p>';
        }
    }

    return $out;
}

function cms_parse_about_lead_html(string $html): array
{
    $paragraphs = [];
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $m)) {
        foreach ($m[1] as $inner) {
            $paragraphs[] = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
    if (!$paragraphs && trim($html) !== '') {
        $paragraphs[] = trim(strip_tags($html));
    }

    return [
        'paragraph1' => $paragraphs[0] ?? '',
        'paragraph2' => $paragraphs[1] ?? '',
    ];
}

function cms_build_studio_values_html(array $cards): string
{
    $html = '';
    foreach ($cards as $card) {
        $title = trim((string) ($card['title'] ?? ''));
        $text = trim((string) ($card['text'] ?? ''));
        if ($title === '' && $text === '') {
            continue;
        }
        $html .= '<div class="value-card">';
        if ($title !== '') {
            $html .= '<h3>' . cms_escape($title) . '</h3>';
        }
        if ($text !== '') {
            $html .= '<p>' . cms_escape($text) . '</p>';
        }
        $html .= '</div>';
    }

    return $html;
}

function cms_parse_studio_values_html(string $html): array
{
    $cards = [];
    if (preg_match_all('/<div[^>]*class="[^"]*value-card[^"]*"[^>]*>(.*?)<\/div>/is', $html, $blocks)) {
        foreach ($blocks[1] as $block) {
            $title = '';
            $text = '';
            if (preg_match('/<h3[^>]*>(.*?)<\/h3>/is', $block, $t)) {
                $title = trim(strip_tags($t[1]));
            }
            if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $block, $p)) {
                $text = trim(strip_tags($p[1]));
            }
            $cards[] = ['title' => $title, 'text' => $text];
        }
    }
    while (count($cards) < 3) {
        $cards[] = ['title' => '', 'text' => ''];
    }

    return array_slice($cards, 0, 3);
}

function cms_studio_values_html_from_awards(array $awards): string
{
    $cards = [];
    foreach ($awards as $award) {
        $title = trim((string) ($award['title'] ?? ''));
        $text = trim((string) ($award['subtitle'] ?? ($award['text'] ?? '')));
        if ($title !== '' || $text !== '') {
            $cards[] = ['title' => $title, 'text' => $text];
        }
    }

    return cms_build_studio_values_html($cards);
}

function cms_studio_values_html_from_settings(array $settings): string
{
    $cards = [];
    for ($i = 1; $i <= 3; $i++) {
        $title = trim((string) ($settings['studio_value_' . $i . '_title'] ?? ''));
        $text = trim((string) ($settings['studio_value_' . $i . '_text'] ?? ''));
        if ($title !== '' || $text !== '') {
            $cards[] = ['title' => $title, 'text' => $text];
        }
    }
    if ($cards) {
        return cms_build_studio_values_html($cards);
    }

    return trim((string) ($settings['studio_values_html'] ?? ''));
}

function cms_sync_studio_highlight_headings(PDO $pdo, string $eyebrow, string $title): void
{
    setting_set($pdo, 'studio_values_eyebrow', $eyebrow);
    setting_set($pdo, 'studio_values_title', $title);
    setting_set($pdo, 'home_awards_eyebrow', $eyebrow);
    setting_set($pdo, 'home_awards_title', $title);
}

/**
 * @param list<string> $paragraphs
 */
function cms_build_paragraphs_html(array $paragraphs, string $paragraphClass = ''): string
{
    $html = '';
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') {
            continue;
        }
        $classAttr = $paragraphClass !== '' ? ' class="' . cms_escape($paragraphClass) . '"' : '';
        $html .= '<p' . $classAttr . '>' . nl2br(cms_escape($p), false) . '</p>';
    }

    return $html;
}

/**
 * @return list<string>
 */
function cms_parse_paragraphs_html(string $html, int $max = 8): array
{
    $paragraphs = [];
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $m)) {
        foreach ($m[1] as $inner) {
            $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }
    }
    if (!$paragraphs && trim($html) !== '') {
        $paragraphs[] = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return array_slice($paragraphs, 0, $max);
}

function cms_build_footer_blurb_html(string $paragraph1, string $paragraph2 = ''): string
{
    return cms_build_paragraphs_html(array_filter([$paragraph1, $paragraph2]));
}

function cms_parse_footer_blurb_html(string $html): array
{
    $parts = cms_parse_paragraphs_html($html, 2);

    return [
        'paragraph1' => $parts[0] ?? '',
        'paragraph2' => $parts[1] ?? '',
    ];
}

function cms_resolve_hero_title_html(array $settings): string
{
    $main = trim((string) ($settings['home_hero_title_main'] ?? ''));
    $highlight = trim((string) ($settings['home_hero_title_highlight'] ?? ''));
    if ($main !== '') {
        return cms_build_hero_title_html($main, $highlight);
    }

    return trim((string) ($settings['home_hero_title_html'] ?? ''));
}

function cms_resolve_about_lead_html(array $settings): string
{
    $p1 = trim((string) ($settings['home_about_paragraph_1'] ?? ''));
    $p2 = trim((string) ($settings['home_about_paragraph_2'] ?? ''));
    if ($p1 !== '' || $p2 !== '') {
        return cms_build_about_lead_html($p1, $p2);
    }

    return trim((string) ($settings['home_about_lead_html'] ?? ''));
}

function cms_resolve_footer_blurb_html(array $settings): string
{
    $p1 = trim((string) ($settings['footer_blurb_1'] ?? ''));
    $p2 = trim((string) ($settings['footer_blurb_2'] ?? ''));
    if ($p1 !== '' || $p2 !== '') {
        return cms_build_footer_blurb_html($p1, $p2);
    }

    return trim((string) ($settings['footer_blurb_html'] ?? ''));
}

/**
 * @param array<string, string> $post
 */
function cms_post_body_paragraphs(array $post, string $prefix = 'body_paragraph_', int $max = 8): array
{
    $out = [];
    for ($i = 1; $i <= $max; $i++) {
        $p = trim((string) ($post[$prefix . $i] ?? ''));
        if ($p !== '') {
            $out[] = $p;
        }
    }

    return $out;
}

/**
 * @return list<string>
 */
function cms_plain_paragraph_slots(string $html, int $max = 8): array
{
    $parsed = cms_parse_paragraphs_html($html, $max);
    while (count($parsed) < $max) {
        $parsed[] = '';
    }

    return $parsed;
}

function cms_body_is_gallery_markup(string $html): bool
{
    $html = trim($html);
    if ($html === '') {
        return false;
    }
    if (stripos($html, 'project-gallery-grid') === false) {
        return false;
    }

    return !preg_match('/<p\b/i', $html);
}

function cms_humanize_filename_alt(string $path): string
{
    $base = basename(str_replace('%20', ' ', $path));
    $base = preg_replace('/\.[a-z0-9]+$/i', '', $base) ?? $base;
    $base = str_replace(['_', '-'], ' ', $base);
    $base = preg_replace('/\s+/', ' ', trim($base)) ?? '';

    return $base !== '' ? ucfirst($base) . ' — Archevo project' : 'Archevo design project';
}

function cms_fix_hero_slide_alts(PDO $pdo): void
{
    $map = [
        'uploads/ENTRY.jpg' => 'Archevo Design — entry and foyer',
        'uploads/1228_HARESHBHAI_LIVING_5.jpg' => 'Living room interior by Archevo Design',
        'uploads/1159-VISALBHAI RAMPARIYA-5.jpg' => 'Residential interior project',
        'uploads/LIVING 01.jpg' => 'Modern living space',
        'uploads/LIVING%2001.jpg' => 'Modern living space',
        'uploads/1523-HARSHITBHAI-5_ER.jpg.jpeg' => 'Featured residential project',
        'uploads/1228-HARESHBHAI_BED_ROOM-2.jpg' => 'Bedroom interior design',
        'uploads/LIVING_ROOM_2-1.jpg' => 'Living room interior',
        'uploads/054-KANTILAL-3D-6.jpg' => '3D visualization — interior concept',
    ];
    $stmt = $pdo->query('SELECT id, image_path, alt_text FROM hero_slides');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $path = str_replace('\\', '/', (string) $row['image_path']);
        $alt = trim((string) ($row['alt_text'] ?? ''));
        $looksLikeFile = $alt === ''
            || preg_match('/\.(jpe?g|png|webp|gif)$/i', $alt)
            || preg_match('/^[A-Z0-9][A-Z0-9_\-\s\.]{2,}$/i', $alt);
        if (isset($map[$path])) {
            $pdo->prepare('UPDATE hero_slides SET alt_text = ? WHERE id = ?')->execute([$map[$path], (int) $row['id']]);
        } elseif ($looksLikeFile) {
            $pdo->prepare('UPDATE hero_slides SET alt_text = ? WHERE id = ?')
                ->execute([cms_humanize_filename_alt($path), (int) $row['id']]);
        }
    }
}

function cms_hero_headline_setting_keys(): array
{
    return [
        'home_hero_headline_1',
        'home_hero_headline_2',
        'home_hero_headline_3',
        'home_hero_headline_4',
        'home_hero_headline_5',
    ];
}

/** @return list<string> */
function cms_hero_headlines_from_settings(array $settings): array
{
    $lines = [];
    foreach (cms_hero_headline_setting_keys() as $key) {
        $line = trim((string) ($settings[$key] ?? ''));
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines;
}

function cms_home_why_defaults(): array
{
    return [
        'eyebrow' => 'Why Archevo',
        'title' => 'Design intelligence. Build discipline.',
        'intro' => 'A single studio for vision, approvals, construction, and interiors — not a patchwork of contractors.',
        'cards' => [
            1 => [
                'title' => 'Single Point Responsibility',
                'text' => 'One team owns design, coordination, and delivery — so accountability never disappears mid-project.',
                'icon' => 'fa-solid fa-layer-group',
            ],
            2 => [
                'title' => 'Design + Construction',
                'text' => 'Architecture, engineering, and site execution aligned from day one — not translated through layers of vendors.',
                'icon' => 'fa-solid fa-compass-drafting',
            ],
            3 => [
                'title' => 'Transparent Budgeting',
                'text' => 'Scope, materials, and milestones defined early — with clarity before work begins on site.',
                'icon' => 'fa-solid fa-scale-balanced',
            ],
            4 => [
                'title' => 'On-Time Delivery',
                'text' => 'Structured phases, site discipline, and proactive coordination to protect your timeline.',
                'icon' => 'fa-solid fa-clock',
            ],
            5 => [
                'title' => 'Professional Team',
                'text' => 'Directors, designers, and engineers working as one studio — not a revolving door of subcontractors.',
                'icon' => 'fa-solid fa-people-group',
            ],
            6 => [
                'title' => 'Approval Assistance',
                'text' => 'Drawings and submissions prepared for local plan-sanctioning requirements across Gujarat.',
                'icon' => 'fa-solid fa-file-circle-check',
            ],
        ],
    ];
}

/** @return list<array{title: string, text: string, icon: string}> */
function cms_build_why_cards_from_settings(array $s): array
{
    $defaults = cms_home_why_defaults();
    $cards = [];

    foreach ($defaults['cards'] as $i => $fallback) {
        $title = trim((string) ($s['home_why_' . $i . '_title'] ?? ''));
        $text = trim((string) ($s['home_why_' . $i . '_text'] ?? ''));
        $icon = trim((string) ($s['home_why_' . $i . '_icon'] ?? ''));

        if ($title === '') {
            $title = $fallback['title'];
        }
        if ($text === '') {
            $text = $fallback['text'];
        }
        if ($icon === '') {
            $icon = $fallback['icon'];
        }

        if ($title === '' && $text === '') {
            continue;
        }

        $cards[] = [
            'title' => $title,
            'text' => $text,
            'icon' => $icon,
        ];
    }

    return $cards;
}

function cms_seed_home_why_defaults(PDO $pdo): void
{
    $defaults = cms_home_why_defaults();
    foreach (['eyebrow' => 'home_why_eyebrow', 'title' => 'home_why_title', 'intro' => 'home_why_intro'] as $field => $key) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $defaults[$field]);
        }
    }
    foreach ($defaults['cards'] as $i => $card) {
        foreach (['title', 'text', 'icon'] as $field) {
            $key = 'home_why_' . $i . '_' . $field;
            $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
            $exists->execute([$key]);
            if (!$exists->fetch()) {
                setting_set($pdo, $key, $card[$field]);
            }
        }
    }
}

function cms_home_pillar_defaults(): array
{
    return [
        1 => [
            'title' => 'Mission',
            'text' => 'Shape enduring spaces that elevate how people live, work, and gather — with clarity from first sketch to final handover.',
        ],
        2 => [
            'title' => 'Vision',
            'text' => "Be Western India's most trusted integrated architecture and design-build studio for landmark residential and commercial work.",
        ],
        3 => [
            'title' => 'Philosophy',
            'text' => "Quiet luxury, honest materials, and light as architecture — designed for Gujarat's climate and contemporary life.",
        ],
        4 => [
            'title' => 'Execution',
            'text' => 'One accountable team for drawings, approvals, civil work, interiors, and turnkey delivery — fewer gaps, fewer surprises.',
        ],
    ];
}

function cms_seed_home_pillar_defaults(PDO $pdo): void
{
    foreach (cms_home_pillar_defaults() as $i => $pillar) {
        foreach (['title' => 'home_pillar_' . $i . '_title', 'text' => 'home_pillar_' . $i . '_text'] as $field => $key) {
            $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
            $exists->execute([$key]);
            if (!$exists->fetch()) {
                setting_set($pdo, $key, $pillar[$field]);
            }
        }
    }
}

function cms_seed_home_page_defaults(PDO $pdo): void
{
    $defaults = [
        'home_hero_eyebrow' => 'Rajkot · Gujarat · Since 2010',
        'home_hero_title_main' => 'Designing Spaces That Define',
        'home_hero_title_highlight' => 'Generations',
        'home_hero_headline_1' => 'Architecture That Outlives Generations',
        'home_hero_headline_2' => 'Designing Legacies In Concrete And Light',
        'home_hero_headline_3' => 'Where Vision Becomes Landmark',
        'home_hero_headline_4' => 'Spaces Built To Inspire',
        'home_hero_headline_5' => 'Designing Spaces That Define Generations',
        'home_hero_lead' => 'Architecture, Interiors & Design-Build Solutions Crafted For Modern Living.',
        'home_hero_tag_1' => 'Architecture',
        'home_hero_tag_2' => 'Interiors',
        'home_hero_tag_3' => 'Civil Construction',
        'home_hero_tag_4' => 'Turnkey Projects',
        'home_hero_avatar_1' => 'SK',
        'home_hero_avatar_2' => 'RP',
        'home_hero_avatar_3' => 'AM',
        'home_hero_avatar_4' => 'HV',
        'home_hero_avatar_5' => '+',
        'home_hero_social_text' => 'Trusted by 150+ Clients Across Gujarat',
        'home_hero_preview_kicker' => 'Featured Project',
        'home_hero_preview_title' => 'Entry & Foyer',
        'home_hero_preview_meta' => 'Rajkot, Gujarat · Residential',
        'home_hero_preview_url' => 'work.html',
        'home_hero_preview_image' => 'uploads/ENTRY.jpg',
        'home_about_eyebrow' => 'Practice',
        'home_about_title' => 'Architecture with intention',
        'home_capabilities_eyebrow' => 'What we do',
        'home_capabilities_title' => 'Integrated design & build solutions',
        'home_capabilities_intro' => 'At Archevo Design, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from brief to handover.',
        'home_projects_eyebrow' => 'Selected work',
        'home_projects_title' => 'Featured commissions',
        'home_projects_intro' => 'Explore client projects — each album opens a full visual case study.',
        'home_projects_limit' => '8',
        'home_gallery_eyebrow' => 'Gallery',
        'home_gallery_title' => 'Project gallery',
        'home_gallery_intro' => 'A curated library of interiors, architecture, and 3D visualisations from recent Archevo commissions across Gujarat.',
        'home_process_eyebrow' => 'Process',
        'home_process_title' => 'How we move from brief to keys',
        'home_process_intro' => 'Clear phases, shared tools, and sign-off moments — so you always know what happens next.',
        'home_testimonials_eyebrow' => 'Clients',
        'home_testimonials_title' => 'What clients say',
        'home_awards_eyebrow' => 'Studio',
        'home_awards_title' => 'Why clients work with us',
        'home_cta_eyebrow' => 'Next project',
        'home_cta_title' => 'Reserve a studio conversation',
        'home_cta_lead' => 'Share your site, timeline, and ambitions — we respond with a clear path and indicative scope.',
        'home_cta_sub' => 'Architecture · Interiors · Construction · Turnkey Delivery',
        'contact_section_title' => 'Rajkot · Gujarat',
        'contact_section_lead' => 'Call, email, or visit by appointment. Site meetings across Saurashtra and Gujarat are scheduled in advance.',
        'home_gallery_limit' => '12',
    ];
    foreach ($defaults as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}

function cms_sync_plain_home_fields(PDO $pdo): void
{
    $keys = array_merge(
        [
            'home_hero_title_html', 'home_hero_title_main', 'home_hero_title_highlight',
            'home_about_lead_html', 'home_about_paragraph_1', 'home_about_paragraph_2',
            'footer_blurb_html', 'footer_blurb_1', 'footer_blurb_2',
        ],
        cms_hero_headline_setting_keys()
    );
    $s = settings_get_many($pdo, $keys);

    if (trim((string) ($s['home_hero_title_main'] ?? '')) === '' && trim((string) ($s['home_hero_title_html'] ?? '')) !== '') {
        $hero = cms_parse_hero_title_html((string) $s['home_hero_title_html']);
        setting_set($pdo, 'home_hero_title_main', $hero['main']);
        setting_set($pdo, 'home_hero_title_highlight', $hero['highlight']);
    }

    if (trim((string) ($s['home_hero_headline_1'] ?? '')) === '') {
        $main = trim((string) ($s['home_hero_title_main'] ?? ''));
        $highlight = trim((string) ($s['home_hero_title_highlight'] ?? ''));
        if ($main !== '') {
            setting_set($pdo, 'home_hero_headline_1', $highlight !== '' ? $main . ' ' . $highlight : $main);
        } elseif (trim((string) ($s['home_hero_title_html'] ?? '')) !== '') {
            setting_set($pdo, 'home_hero_headline_1', trim(strip_tags((string) $s['home_hero_title_html'])));
        }
    }

    if (trim((string) ($s['home_about_paragraph_1'] ?? '')) === '' && trim((string) ($s['home_about_lead_html'] ?? '')) !== '') {
        $about = cms_parse_about_lead_html((string) $s['home_about_lead_html']);
        setting_set($pdo, 'home_about_paragraph_1', $about['paragraph1']);
        setting_set($pdo, 'home_about_paragraph_2', $about['paragraph2']);
    }

    if (trim((string) ($s['footer_blurb_1'] ?? '')) === '' && trim((string) ($s['footer_blurb_html'] ?? '')) !== '') {
        $footer = cms_parse_footer_blurb_html((string) $s['footer_blurb_html']);
        setting_set($pdo, 'footer_blurb_1', $footer['paragraph1']);
        setting_set($pdo, 'footer_blurb_2', $footer['paragraph2']);
    }

    cms_fix_hero_slide_alts($pdo);
    cms_seed_home_page_defaults($pdo);
    cms_seed_home_pillar_defaults($pdo);
    cms_seed_home_why_defaults($pdo);
}

function cms_services_faq_from_settings(array $s): array
{
    $items = [];
    for ($i = 1; $i <= 6; $i++) {
        $q = trim((string) ($s['services_faq_q' . $i] ?? ''));
        $a = trim((string) ($s['services_faq_a' . $i] ?? ''));
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => $q, 'a' => $a];
        }
    }

    return [
        'eyebrow' => (string) ($s['services_faq_eyebrow'] ?? ''),
        'title' => (string) ($s['services_faq_title'] ?? ''),
        'items' => $items,
    ];
}

function cms_seed_services_page_defaults(PDO $pdo): void
{
    require_once SPANGLE_ROOT . '/includes/cmsServicesSections.php';
    cms_seed_services_section_settings($pdo);

    $defaults = [
        'services_kicker' => 'What we do',
        'services_title' => 'From Vision To Completion.',
        'services_lead' => 'At Archevo Design, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from foundation to finish.',
        'services_hero_image' => 'uploads/054-KANTILAL-3D-6.jpg',
        'services_cta_eyebrow' => 'Next project',
        'services_cta_title' => "Let's Build Something Extraordinary.",
        'services_cta_sub' => 'Architecture · Interiors · Construction · Turnkey Delivery',
        'services_cta_lead' => 'Share your site, scope, and timeline — we will recommend design-only, approval support, or full turnkey delivery.',
        'services_cta_btn_text' => 'Book consultation',
        'services_cta_btn_url' => 'contact.html',
        'services_cta_btn2_text' => 'Get project estimate',
        'services_cta_btn2_url' => 'contact.html',
        'services_faq_eyebrow' => 'Questions',
        'services_faq_title' => 'Before you enquire',
        'services_faq_q1' => 'How much does a typical project cost?',
        'services_faq_a1' => 'Cost depends on site, scope, and finish level. After an initial consultation and site study, we provide a phased estimate aligned to your brief and timeline.',
        'services_faq_q2' => 'How long does the full process take?',
        'services_faq_a2' => 'Timelines vary by project type — approvals, construction, and interiors each have distinct phases. We share a milestone calendar at engagement so you know what happens next.',
        'services_faq_q3' => 'Do you handle plan approvals and sanctions?',
        'services_faq_a3' => 'Yes. We prepare drawings for local plan sanctioning and coordinate with authorities so compliance is handled within the studio — not passed back to you.',
        'services_faq_q4' => 'Can you manage construction on site?',
        'services_faq_a4' => 'Yes. Our civil and project management teams supervise quality, vendors, RFIs, and snag lists through handover — protecting design intent on site.',
        'services_faq_q5' => 'What is included in a turnkey package?',
        'services_faq_a5' => 'Design, approvals, construction, interiors, procurement, and handover under one contract — single point of contact from brief to keys.',
        'services_faq_q6' => 'Do you offer interior design only?',
        'services_faq_a6' => 'Yes. We deliver spatial planning, materials, joinery drawings, FF&E, and execution supervision as a standalone interior engagement or integrated with architecture.',
    ];
    foreach ($defaults as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}

function cms_sync_plain_services_fields(PDO $pdo): void
{
    cms_seed_services_page_defaults($pdo);
}

function cms_process_faq_from_settings(array $s): array
{
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
        $q = trim((string) ($s['process_faq_q' . $i] ?? ''));
        $a = trim((string) ($s['process_faq_a' . $i] ?? ''));
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => $q, 'a' => $a];
        }
    }

    return [
        'eyebrow' => (string) ($s['process_faq_eyebrow'] ?? ''),
        'title' => (string) ($s['process_faq_title'] ?? ''),
        'items' => $items,
    ];
}

function cms_seed_process_page_defaults(PDO $pdo): void
{
    $defaults = [
        'process_kicker' => 'Method',
        'process_title' => 'From Vision To Reality.',
        'process_lead' => 'You always know where we are in the journey — what decisions are due, what we need from you, and what happens next on site.',
        'process_hero_image' => 'uploads/LIVING_ROOM_2-1.jpg',
        'process_split_eyebrow' => 'Engagement',
        'process_split_title' => 'Phased gates, shared tools',
        'process_split_paragraph_1' => 'We structure work into clear phases with sign-off moments. Drawings, 3D studies, and sample boards live in a shared workspace so feedback is captured once — not lost across threads.',
        'process_split_paragraph_2' => 'On site, our project directors hold weekly coordination meetings with contractors and vendors until handover is complete.',
        'process_split_image' => 'uploads/066-UPENDRASINH-3D-3.jpg',
        'process_timeline_eyebrow' => 'Timeline',
        'process_timeline_title' => 'From brief to keys',
        'process_cta_eyebrow' => 'Next step',
        'process_cta_title' => "Let's Build With Confidence.",
        'process_cta_sub' => 'Architecture · Interiors · Construction · Delivered Through A Proven Process',
        'process_cta_text' => 'Request a PDF overview of deliverables and typical timelines for your project type.',
        'process_cta_btn_text' => 'Book consultation',
        'process_cta_btn_url' => 'contact.html',
        'process_cta_btn2_text' => 'Discuss your project',
        'process_cta_btn2_url' => 'contact.html',
        'process_faq_eyebrow' => 'Questions',
        'process_faq_title' => 'About our process',
        'process_faq_q1' => 'How long does a project take?',
        'process_faq_a1' => 'Timelines depend on scope — residential builds typically run 12–18 months turnkey; interiors may complete in 4–8 months. We share a milestone calendar at engagement.',
        'process_faq_q2' => 'When are approvals required?',
        'process_faq_a2' => 'Plan sanctioning follows schematic lock. We prepare drawings and coordinate with local authorities so compliance stays within the studio.',
        'process_faq_q3' => 'How often will I receive updates?',
        'process_faq_a3' => 'Weekly site reports during construction, shared documentation at every phase gate, and director access for key decisions.',
        'process_faq_q4' => 'Can you manage turnkey projects?',
        'process_faq_a4' => 'Yes. Design, approvals, construction, interiors, and handover under one contract — single point of contact throughout.',
        'process_faq_q5' => 'What if changes are needed mid-project?',
        'process_faq_a5' => 'Changes are documented with scope, cost, and timeline impact before work proceeds — no surprises on site.',
    ];
    foreach ($defaults as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}

function cms_sync_plain_process_fields(PDO $pdo): void
{
    $keys = [
        'process_split_lead_html', 'process_split_paragraph_1', 'process_split_paragraph_2',
    ];
    $s = settings_get_many($pdo, $keys);

    if (trim((string) ($s['process_split_paragraph_1'] ?? '')) === '' && trim((string) ($s['process_split_lead_html'] ?? '')) !== '') {
        $split = cms_parse_about_lead_html((string) $s['process_split_lead_html']);
        setting_set($pdo, 'process_split_paragraph_1', $split['paragraph1']);
        setting_set($pdo, 'process_split_paragraph_2', $split['paragraph2']);
    }

    $p1 = trim((string) (settings_get_many($pdo, ['process_split_paragraph_1'])['process_split_paragraph_1'] ?? ''));
    $p2 = trim((string) (settings_get_many($pdo, ['process_split_paragraph_2'])['process_split_paragraph_2'] ?? ''));
    setting_set($pdo, 'process_split_lead_html', cms_build_about_lead_html($p1, $p2));

    cms_seed_process_page_defaults($pdo);
}

function cms_contact_lines_from_setting(string $raw, array $fallback): array
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    return $lines ?: $fallback;
}

function cms_contact_steps_from_settings(array $s): array
{
    $steps = [];
    for ($i = 1; $i <= 4; $i++) {
        $title = trim((string) ($s['contact_step_' . $i . '_title'] ?? ''));
        $text = trim((string) ($s['contact_step_' . $i . '_text'] ?? ''));
        if ($title !== '' && $text !== '') {
            $steps[] = ['title' => $title, 'text' => $text];
        }
    }

    return $steps;
}

function cms_contact_trust_from_settings(array $s): array
{
    $icons = [
        'fa-solid fa-building-columns',
        'fa-solid fa-calendar-check',
        'fa-solid fa-face-smile',
        'fa-solid fa-key',
        'fa-solid fa-file-signature',
        'fa-solid fa-user-check',
    ];
    $items = [];
    for ($i = 1; $i <= 6; $i++) {
        $title = trim((string) ($s['contact_trust_' . $i . '_title'] ?? ''));
        $text = trim((string) ($s['contact_trust_' . $i . '_text'] ?? ''));
        if ($title !== '' && $text !== '') {
            $items[] = ['title' => $title, 'text' => $text, 'icon' => $icons[$i - 1] ?? 'fa-solid fa-circle'];
        }
    }

    return $items;
}

function cms_contact_faq_from_settings(array $s): array
{
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
        $q = trim((string) ($s['contact_faq_q' . $i] ?? ''));
        $a = trim((string) ($s['contact_faq_a' . $i] ?? ''));
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => $q, 'a' => $a];
        }
    }

    return ['items' => $items];
}

function cms_seed_contact_page_defaults(PDO $pdo): void
{
    $defaults = [
        'contact_hero_kicker' => 'Enquiries',
        'contact_hero_title' => "Let's Create Something Extraordinary",
        'contact_hero_lead' => 'Every great project starts with a conversation. Share your vision — we respond within two business days.',
        'contact_hero_image' => 'uploads/ENTRY.jpg',
        'contact_intro_title' => 'What happens next',
        'contact_intro_lead' => 'A clear, calm process from first message to consultation — so you always know where we are.',
        'contact_step_1_title' => 'Submit enquiry',
        'contact_step_1_text' => 'Tell us about your site, scope, and timeline through the form below.',
        'contact_step_2_title' => 'Studio review',
        'contact_step_2_text' => 'Our team reviews your brief and aligns the right director to your project type.',
        'contact_step_3_title' => 'Consultation call',
        'contact_step_3_text' => 'We schedule a call or studio visit to clarify goals, constraints, and budget.',
        'contact_step_4_title' => 'Proposal & roadmap',
        'contact_step_4_text' => 'You receive a phased scope, indicative timeline, and next steps to engage.',
        'contact_project_types' => "Residential\nCommercial\nInterior Design\nConstruction\nTurnkey",
        'contact_budget_ranges' => "₹10L – ₹25L\n₹25L – ₹50L\n₹50L – ₹1Cr\n₹1Cr+",
        'contact_reasons' => "New Home Design\nInterior Design\nConstruction\nTurnkey Solutions",
        'contact_trust_1_title' => '150+ projects',
        'contact_trust_1_text' => 'Delivered across residential, commercial, and turnkey engagements.',
        'contact_trust_2_title' => '16+ years',
        'contact_trust_2_text' => 'Integrated architecture, interiors, and construction experience.',
        'contact_trust_3_title' => '98% satisfaction',
        'contact_trust_3_text' => 'Clients return for phases, referrals, and repeat commissions.',
        'contact_trust_4_title' => 'End-to-end delivery',
        'contact_trust_4_text' => 'Design through handover under one accountable studio.',
        'contact_trust_5_title' => 'Plan approval support',
        'contact_trust_5_text' => 'Drawings and coordination with local authorities.',
        'contact_trust_6_title' => 'Single point responsibility',
        'contact_trust_6_text' => 'One director, one contract, one conversation.',
        'contact_founder_quote' => 'We listen first — then design spaces that perform for daily life, not only for photographs.',
        'contact_visit_parking' => 'Parking available at RK Supreme — Nanamava Circle, Rajkot.',
        'contact_visit_appointment' => 'Studio visits are by appointment, Monday–Friday 10:00–18:30 IST.',
        'contact_wa_lead' => 'Prefer a quick chat? Message us on WhatsApp — we typically reply within a few hours on business days.',
        'contact_cta_title' => "Let's Build Your Vision Together",
        'contact_cta_sub' => 'Architecture · Interiors · Construction · Turnkey Delivery',
        'contact_cta_btn_text' => 'Book consultation',
        'contact_cta_btn_url' => '#cnt-enquiry-form',
        'contact_cta_btn2_text' => 'Start your project',
        'contact_cta_btn2_url' => '#cnt-enquiry-form',
        'contact_faq_q1' => 'How much does architecture cost?',
        'contact_faq_a1' => 'Cost depends on site, scope, and finish level. After an initial consultation, we provide a phased estimate aligned to your brief.',
        'contact_faq_q2' => 'Do you handle turnkey projects?',
        'contact_faq_a2' => 'Yes — design, approvals, construction, interiors, and handover under one contract with a single point of contact.',
        'contact_faq_q3' => 'Can you work outside Rajkot?',
        'contact_faq_a3' => 'We work across Gujarat and India for select commissions. Remote coordination and site visits are structured into the engagement.',
        'contact_faq_q4' => 'How long does a project take?',
        'contact_faq_a4' => 'Timelines vary — interiors may complete in 4–8 months; full turnkey builds often run 12–18 months. We share a milestone calendar at engagement.',
        'contact_faq_q5' => 'Do you assist with approvals?',
        'contact_faq_a5' => 'Yes. We prepare drawings for plan sanctioning and coordinate with local authorities.',
    ];
    foreach ($defaults as $key => $val) {
        $exists = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value != ""');
        $exists->execute([$key]);
        if (!$exists->fetch()) {
            setting_set($pdo, $key, $val);
        }
    }
}

function cms_sync_plain_contact_fields(PDO $pdo): void
{
    cms_seed_contact_page_defaults($pdo);
}
