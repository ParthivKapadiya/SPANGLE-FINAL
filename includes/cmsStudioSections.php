<?php

declare(strict_types=1);

/**
 * Studio page section registry for the admin panel.
 *
 * @return array<int, array{id: string, num: int, label: string, description: string, href: string, icon: string}>
 */
function cms_studio_section_definitions(): array
{
    return [
        ['id' => 'hero', 'num' => 1, 'label' => 'Hero', 'description' => 'Top banner — label, headline, intro, background image, and credential stats.', 'href' => 'hero.php', 'icon' => 'fa-image'],
        ['id' => 'story', 'num' => 2, 'label' => 'Our story', 'description' => 'Practice timeline — section heading, photo, and milestone cards.', 'href' => 'story.php', 'icon' => 'fa-book-open'],
        ['id' => 'philosophy', 'num' => 3, 'label' => 'Philosophy', 'description' => 'Design vision block — heading, image, six pillar cards, and pull quote.', 'href' => 'philosophy.php', 'icon' => 'fa-compass'],
        ['id' => 'why', 'num' => 4, 'label' => 'Why Archevo', 'description' => 'Studio page heading — six cards are shared with Home → Why Archevo.', 'href' => 'why.php', 'icon' => 'fa-star'],
        ['id' => 'values', 'num' => 5, 'label' => 'Our values', 'description' => 'Value cards and highlight blocks (also used on the home page).', 'href' => 'values.php', 'icon' => 'fa-gem'],
        ['id' => 'process', 'num' => 6, 'label' => 'Process preview', 'description' => 'How you work — heading and steps pulled from Process.', 'href' => 'process.php', 'icon' => 'fa-diagram-project'],
        ['id' => 'culture', 'num' => 7, 'label' => 'Studio culture', 'description' => 'Behind-the-scenes photo grid with captions.', 'href' => 'culture.php', 'icon' => 'fa-camera'],
        ['id' => 'impact', 'num' => 8, 'label' => 'Project impact', 'description' => 'Dark stats band — heading and hero statistics.', 'href' => 'impact.php', 'icon' => 'fa-chart-line'],
        ['id' => 'testimonials', 'num' => 9, 'label' => 'Client trust', 'description' => 'Testimonials heading and client quotes.', 'href' => 'testimonials.php', 'icon' => 'fa-quote-left'],
        ['id' => 'compare', 'num' => 10, 'label' => 'The difference', 'description' => 'Archevo vs traditional contractors — headings and bullet lists.', 'href' => 'compare.php', 'icon' => 'fa-scale-balanced'],
        ['id' => 'cta', 'num' => 11, 'label' => 'Call to action', 'description' => 'Bottom invitation — headline, text, and buttons.', 'href' => 'cta.php', 'icon' => 'fa-bullhorn'],
    ];
}

function cms_studio_section_by_id(string $id): ?array
{
    foreach (cms_studio_section_definitions() as $section) {
        if ($section['id'] === $id) {
            return $section;
        }
    }

    return null;
}

/** @return array<string, string> */
function cms_studio_section_defaults(): array
{
    return [
        'studio_kicker' => 'Rajkot · Gujarat',
        'studio_title' => 'We Design More Than Buildings.',
        'studio_lead' => 'Integrated design and build — civil engineering, plan approvals, interiors, and turnkey project management from Rajkot, Gujarat.',
        'studio_story_eyebrow' => 'Our story',
        'studio_story_title' => 'From foundation to future',
        'studio_story_intro' => 'A Rajkot-born studio built on patience, precision, and the belief that great architecture must survive Tuesday mornings — not only opening photographs.',
        'studio_story_image' => 'uploads/1228_HARESHBHAI_LIVING_3.jpg',
        'studio_timeline_1_year' => '2010 · Foundation',
        'studio_timeline_1_title' => 'Roots in Rajkot',
        'studio_timeline_1_text' => 'Archevo Design began as a practice committed to integrated architecture and civil delivery — one team accountable from drawing board to handover.',
        'studio_timeline_2_year' => 'Growth',
        'studio_timeline_2_title' => 'Design + build under one roof',
        'studio_timeline_2_text' => 'Archevo Design offers end-to-end services across civil engineering, plan sanctioning, interior design, and turnkey project management — one accountable team for homes, workplaces, and developments in Gujarat.',
        'studio_timeline_3_year' => 'Milestone',
        'studio_timeline_3_title' => '150+ commissions delivered',
        'studio_timeline_3_text' => 'Residential, commercial, and retail projects across Saurashtra — each shaped by climate logic, honest materials, and build discipline.',
        'studio_timeline_4_year' => 'Vision',
        'studio_timeline_4_title' => 'Quiet luxury, lasting spaces',
        'studio_timeline_4_text' => 'We favour envelopes tuned for Gujarat, generous daylight, and interiors composed with the same rigour as structure.',
        'studio_timeline_5_year' => 'Forward',
        'studio_timeline_5_title' => 'Western India\'s trusted studio',
        'studio_timeline_5_text' => 'We execute with integrity, intelligence, and innovation — coordinating authorities, contractors, and vendors so you have a single point of contact from drawing board to keys.',
        'studio_philosophy_eyebrow' => 'Philosophy',
        'studio_philosophy_title' => 'From design vision to built reality',
        'studio_why_eyebrow' => 'Why Archevo Design',
        'studio_why_title' => 'Not just another architecture firm',
        'studio_why_intro' => 'Integrated design, civil expertise, and turnkey delivery — one studio, one contract, one point of accountability.',
        'studio_values_eyebrow' => 'Our values',
        'studio_values_title' => 'What we stand for',
        'studio_pullquote' => 'From design vision to built reality — we execute with integrity, intelligence, and innovation.',
        'studio_process_eyebrow' => 'How we work',
        'studio_process_title' => 'From discovery to handover',
        'studio_process_intro' => 'Clear phases, shared tools, and sign-off moments — so you always know what happens next.',
        'studio_culture_eyebrow' => 'Studio culture',
        'studio_culture_title' => 'Behind the work',
        'studio_culture_intro' => 'Site visits, design reviews, material selection, and the quiet discipline that turns drawings into built reality.',
        'studio_culture_caption_1' => 'Site visits',
        'studio_culture_caption_2' => 'Design reviews',
        'studio_culture_caption_3' => 'Material selection',
        'studio_culture_caption_4' => '',
        'studio_culture_caption_5' => '',
        'studio_culture_caption_6' => '',
        'studio_impact_eyebrow' => 'Project impact',
        'studio_impact_title' => 'Built with purpose',
        'studio_testimonials_eyebrow' => 'Clients',
        'studio_testimonials_title' => 'What clients say',
        'studio_trust_badge_1_value' => '4.9',
        'studio_trust_badge_1_label' => 'Client satisfaction',
        'studio_trust_badge_1_icon' => 'fa-solid fa-star',
        'studio_trust_badge_2_value' => '85%',
        'studio_trust_badge_2_label' => 'Referral-led projects',
        'studio_trust_badge_2_icon' => 'fa-solid fa-handshake',
        'studio_trust_badge_3_value' => '150+',
        'studio_trust_badge_3_label' => 'Successful deliveries',
        'studio_trust_badge_3_icon' => 'fa-solid fa-award',
        'studio_compare_eyebrow' => 'The difference',
        'studio_compare_title' => 'Archevo Design vs traditional contractors',
        'studio_compare_us_title' => 'Archevo Design',
        'studio_compare_them_title' => 'Traditional approach',
        'studio_compare_us_1' => 'Single studio for design, approvals, build & interiors',
        'studio_compare_us_2' => 'Director-led accountability through handover',
        'studio_compare_us_3' => 'Drawings prepared for local plan sanctioning',
        'studio_compare_us_4' => 'Transparent phases with shared documentation',
        'studio_compare_us_5' => '3D visualization before work begins on site',
        'studio_compare_them_1' => 'Separate architect, contractor & interior vendors',
        'studio_compare_them_2' => 'Accountability gaps between design and site',
        'studio_compare_them_3' => 'Approval paperwork handled by the client',
        'studio_compare_them_4' => 'Scope changes without clear sign-off',
        'studio_compare_them_5' => 'Decisions made on site without prior study',
        'studio_cta_eyebrow' => 'Next project',
        'studio_cta_title' => 'Let\'s Create Something Meaningful Together',
        'studio_cta_sub' => 'Architecture · Interiors · Construction · Delivered With Purpose',
        'studio_cta_text' => 'See how we translate intent into drawings, models, and built work.',
        'studio_cta_btn_text' => 'Book a consultation',
        'studio_cta_btn_url' => 'contact.html',
        'studio_cta_btn2_text' => 'View our work',
        'studio_cta_btn2_url' => 'work.html',
    ];
}

/** @return array<int, array{title: string, text: string, icon: string}> */
function cms_studio_philosophy_pillar_defaults(): array
{
    return [
        1 => [
            'title' => 'Design thinking',
            'text' => 'Every commission begins with clarity — brief, site, budget, and the life that will unfold inside the space.',
            'icon' => 'fa-solid fa-brain',
        ],
        2 => [
            'title' => 'Human-centred',
            'text' => 'Layouts, light, and circulation composed for how people actually live, work, and gather — not for catalogue spreads.',
            'icon' => 'fa-solid fa-users',
        ],
        3 => [
            'title' => 'Functionality',
            'text' => 'Structure-first thinking so the shell supports daily life — compliant, buildable, and efficient on site.',
            'icon' => 'fa-solid fa-cubes',
        ],
        4 => [
            'title' => 'Beauty',
            'text' => 'Atmosphere, proportion, and tactile sequences — composed like a slow conversation between material and light.',
            'icon' => 'fa-solid fa-gem',
        ],
        5 => [
            'title' => 'Sustainability',
            'text' => 'Climate-responsive envelopes, durable finishes, and decisions that reduce waste across the project lifecycle.',
            'icon' => 'fa-solid fa-leaf',
        ],
        6 => [
            'title' => 'Construction intelligence',
            'text' => 'Drawings contractors can build, site rhythm directors can hold, and clients can trust through handover.',
            'icon' => 'fa-solid fa-helmet-safety',
        ],
    ];
}

function cms_fill_studio_settings(array $settings): array
{
    foreach (cms_studio_section_defaults() as $key => $default) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $settings[$key] = $default;
        }
    }

    foreach (cms_studio_philosophy_pillar_defaults() as $i => $pillar) {
        foreach (['title', 'text', 'icon'] as $field) {
            $key = 'studio_pillar_' . $i . '_' . $field;
            if (trim((string) ($settings[$key] ?? '')) === '') {
                $settings[$key] = $pillar[$field];
            }
        }
    }

    return $settings;
}

/**
 * @param array<string, string> $s
 * @param list<array> $awards
 * @return array<string, mixed>
 */
function cms_build_studio_page_payload(array $s, array $awards): array
{
    require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
    $s = cms_fill_studio_settings($s);
    $timeline = [];
    for ($i = 1; $i <= 5; $i++) {
        $timeline[] = [
            'year' => (string) ($s['studio_timeline_' . $i . '_year'] ?? ''),
            'title' => (string) ($s['studio_timeline_' . $i . '_title'] ?? ''),
            'text' => (string) ($s['studio_timeline_' . $i . '_text'] ?? ''),
        ];
    }

    $pillars = [];
    foreach (cms_studio_philosophy_pillar_defaults() as $i => $defaults) {
        $pillars[] = [
            'title' => (string) ($s['studio_pillar_' . $i . '_title'] ?? $defaults['title']),
            'text' => (string) ($s['studio_pillar_' . $i . '_text'] ?? $defaults['text']),
            'icon' => (string) ($s['studio_pillar_' . $i . '_icon'] ?? $defaults['icon']),
        ];
    }

    return [
        'kicker' => $s['studio_kicker'] ?? '',
        'title' => $s['studio_title'] ?? '',
        'lead' => $s['studio_lead'] ?? '',
        'heroImage' => public_upload_url($s['studio_hero_image'] ?? ''),
        'storyEyebrow' => $s['studio_story_eyebrow'] ?? '',
        'storyTitle' => $s['studio_story_title'] ?? '',
        'storyIntro' => $s['studio_story_intro'] ?? '',
        'storyImage' => public_upload_url($s['studio_story_image'] ?? ''),
        'timeline' => $timeline,
        'philosophyEyebrow' => $s['studio_philosophy_eyebrow'] ?? '',
        'philosophyTitle' => $s['studio_philosophy_title'] ?? '',
        'philosophyLead1' => $timeline[1]['text'] ?? ($s['studio_philosophy_lead_1'] ?? ''),
        'philosophyLead2' => $timeline[4]['text'] ?? ($s['studio_philosophy_lead_2'] ?? ''),
        'philosophyImage' => public_upload_url($s['studio_philosophy_image'] ?? ''),
        'pillars' => $pillars,
        'whyEyebrow' => $s['studio_why_eyebrow'] ?? '',
        'whyTitle' => $s['studio_why_title'] ?? '',
        'whyIntro' => $s['studio_why_intro'] ?? '',
        'whyCards' => cms_build_why_cards_from_settings($s),
        'valuesEyebrow' => $s['studio_values_eyebrow'] ?? ($s['home_awards_eyebrow'] ?? ''),
        'valuesTitle' => $s['studio_values_title'] ?? ($s['home_awards_title'] ?? ''),
        'valuesHtml' => $awards
            ? cms_studio_values_html_from_awards($awards)
            : cms_studio_values_html_from_settings($s),
        'pullquote' => $s['studio_pullquote'] ?? '',
        'processEyebrow' => $s['studio_process_eyebrow'] ?? '',
        'processTitle' => $s['studio_process_title'] ?? '',
        'processIntro' => $s['studio_process_intro'] ?? '',
        'cultureEyebrow' => $s['studio_culture_eyebrow'] ?? '',
        'cultureTitle' => $s['studio_culture_title'] ?? '',
        'cultureIntro' => $s['studio_culture_intro'] ?? '',
        'stripCaptions' => array_values(array_filter(array_map(
            static function (int $i) use ($s): ?string {
                $image = trim((string) ($s['studio_strip_image_' . $i] ?? ''));
                if ($image === '') {
                    return null;
                }

                return (string) ($s['studio_culture_caption_' . $i] ?? '');
            },
            range(1, 6)
        ))),
        'stripImages' => array_values(array_filter(array_map(
            static fn (int $i): string => public_upload_url($s['studio_strip_image_' . $i] ?? ''),
            range(1, 6)
        ))),
        'impactEyebrow' => $s['studio_impact_eyebrow'] ?? '',
        'impactTitle' => $s['studio_impact_title'] ?? '',
        'testimonialsEyebrow' => $s['studio_testimonials_eyebrow'] ?? '',
        'testimonialsTitle' => $s['studio_testimonials_title'] ?? '',
        'trustBadges' => array_values(array_map(
            static function (int $i) use ($s): array {
                return [
                    'value' => (string) ($s['studio_trust_badge_' . $i . '_value'] ?? ''),
                    'label' => (string) ($s['studio_trust_badge_' . $i . '_label'] ?? ''),
                    'icon' => (string) ($s['studio_trust_badge_' . $i . '_icon'] ?? ''),
                ];
            },
            range(1, 3)
        )),
        'compareEyebrow' => $s['studio_compare_eyebrow'] ?? '',
        'compareTitle' => $s['studio_compare_title'] ?? '',
        'compareUsTitle' => $s['studio_compare_us_title'] ?? '',
        'compareThemTitle' => $s['studio_compare_them_title'] ?? '',
        'compareUsItems' => array_values(array_filter(array_map(
            static fn (int $i): string => trim((string) ($s['studio_compare_us_' . $i] ?? '')),
            range(1, 5)
        ))),
        'compareThemItems' => array_values(array_filter(array_map(
            static fn (int $i): string => trim((string) ($s['studio_compare_them_' . $i] ?? '')),
            range(1, 5)
        ))),
        'ctaEyebrow' => $s['studio_cta_eyebrow'] ?? '',
        'ctaTitle' => $s['studio_cta_title'] ?? '',
        'ctaSub' => $s['studio_cta_sub'] ?? '',
        'ctaText' => $s['studio_cta_text'] ?? '',
        'ctaBtnText' => $s['studio_cta_btn_text'] ?? '',
        'ctaBtnUrl' => $s['studio_cta_btn_url'] ?? '',
        'ctaBtn2Text' => $s['studio_cta_btn2_text'] ?? '',
        'ctaBtn2Url' => $s['studio_cta_btn2_url'] ?? '',
    ];
}

function cms_sync_studio_story_timeline_settings(PDO $pdo): void
{
    $pairs = [
        ['studio_timeline_2_text', 'studio_philosophy_lead_1'],
        ['studio_timeline_5_text', 'studio_philosophy_lead_2'],
    ];

    foreach ($pairs as [$timelineKey, $legacyKey]) {
        $values = settings_get_many($pdo, [$timelineKey, $legacyKey]);
        $timeline = trim((string) ($values[$timelineKey] ?? ''));
        $legacy = trim((string) ($values[$legacyKey] ?? ''));

        if ($timeline === '' && $legacy !== '') {
            setting_set($pdo, $timelineKey, $legacy);
            continue;
        }

        if ($timeline !== '' && $timeline !== $legacy) {
            setting_set($pdo, $legacyKey, $timeline);
        }
    }
}

function cms_seed_studio_section_settings(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (cms_studio_section_defaults() as $key => $default) {
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || trim((string) ($row['setting_value'] ?? '')) === '') {
                setting_set($pdo, $key, $default);
            }
        } catch (Throwable $e) {
        }
    }

    foreach (cms_studio_philosophy_pillar_defaults() as $i => $pillar) {
        foreach (['title', 'text', 'icon'] as $field) {
            $key = 'studio_pillar_' . $i . '_' . $field;
            try {
                $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
                $stmt->execute([$key]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || trim((string) ($row['setting_value'] ?? '')) === '') {
                    setting_set($pdo, $key, $pillar[$field]);
                }
            } catch (Throwable $e) {
            }
        }
    }
}
