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
        'uploads/ENTRY.jpg' => 'SPANGLE Architecture & Interior Design Studio — entry and foyer',
        'uploads/1228_HARESHBHAI_LIVING_5.jpg' => 'Living room interior by SPANGLE Architecture & Interior Design Studio',
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

function cms_seed_home_page_defaults(PDO $pdo): void
{
    $defaults = [
        'home_hero_eyebrow' => 'Rajkot · Gujarat · Since 2016',
        'home_hero_title_main' => 'Spaces shaped for',
        'home_hero_title_highlight' => 'how you live',
        'home_hero_lead' => 'Architecture and interior design for discerning homes, workplaces, and retail — conceived with rigour, finished with quiet luxury.',
        'home_about_eyebrow' => 'Practice',
        'home_about_title' => 'Architecture with intention',
        'home_capabilities_eyebrow' => 'What we do',
        'home_capabilities_title' => 'Integrated design & build solutions',
        'home_capabilities_intro' => 'At SPANGLE Architecture & Interior Design Studio, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from brief to handover.',
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
        'home_team_eyebrow' => 'People',
        'home_team_title' => 'Leadership',
        'home_journal_eyebrow' => 'Journal',
        'home_journal_title' => 'Latest insights',
        'home_cta_eyebrow' => 'Next project',
        'home_cta_title' => 'Reserve a studio conversation',
        'home_cta_lead' => 'Share your site, timeline, and ambitions — we respond with a clear path and indicative scope.',
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
    $keys = [
        'home_hero_title_html', 'home_hero_title_main', 'home_hero_title_highlight',
        'home_about_lead_html', 'home_about_paragraph_1', 'home_about_paragraph_2',
        'footer_blurb_html', 'footer_blurb_1', 'footer_blurb_2',
    ];
    $s = settings_get_many($pdo, $keys);

    if (trim((string) ($s['home_hero_title_main'] ?? '')) === '' && trim((string) ($s['home_hero_title_html'] ?? '')) !== '') {
        $hero = cms_parse_hero_title_html((string) $s['home_hero_title_html']);
        setting_set($pdo, 'home_hero_title_main', $hero['main']);
        setting_set($pdo, 'home_hero_title_highlight', $hero['highlight']);
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
}

function cms_seed_services_page_defaults(PDO $pdo): void
{
    $defaults = [
        'services_kicker' => 'What we do',
        'services_title' => 'Integrated design & build solutions',
        'services_lead' => 'At SPANGLE Architecture & Interior Design Studio, we blend structural expertise with aesthetic precision — functional, compliant, and beautifully designed spaces from foundation to finish.',
        'services_hero_image' => 'uploads/054-KANTILAL-3D-6.jpg',
        'services_cta_eyebrow' => 'Brief us',
        'services_cta_title' => 'Tell us about your site',
        'services_cta_lead' => 'Share your site, scope, and timeline — we will recommend design-only, approval support, or full turnkey delivery.',
        'services_cta_btn_text' => 'Book a call',
        'services_cta_btn_url' => 'contact.html',
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

function cms_seed_process_page_defaults(PDO $pdo): void
{
    $defaults = [
        'process_kicker' => 'Method',
        'process_title' => 'Clarity at every milestone',
        'process_lead' => 'You always know where we are in the journey — what decisions are due, what we need from you, and what happens next on site.',
        'process_hero_image' => 'uploads/LIVING_ROOM_2-1.jpg',
        'process_split_eyebrow' => 'Engagement',
        'process_split_title' => 'Phased gates, shared tools',
        'process_split_paragraph_1' => 'We structure work into clear phases with sign-off moments. Drawings, 3D studies, and sample boards live in a shared workspace so feedback is captured once — not lost across threads.',
        'process_split_paragraph_2' => 'On site, our project directors hold weekly coordination meetings with contractors and vendors until handover is complete.',
        'process_split_image' => 'uploads/066-UPENDRASINH-3D-3.jpg',
        'process_timeline_eyebrow' => 'Timeline',
        'process_timeline_title' => 'From brief to keys',
        'process_cta_text' => 'Request a PDF overview of deliverables and typical timelines for your project type.',
        'process_cta_btn_text' => 'Contact the studio',
        'process_cta_btn_url' => 'contact.html',
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
