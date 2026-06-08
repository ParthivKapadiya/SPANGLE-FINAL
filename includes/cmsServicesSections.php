<?php

declare(strict_types=1);

/**
 * Services page section registry for the admin panel.
 *
 * @return array<int, array{id: string, num: int, label: string, description: string, href: string, icon: string}>
 */
function cms_services_section_definitions(): array
{
    return [
        ['id' => 'hero', 'num' => 1, 'label' => 'Hero', 'description' => 'Top banner — label, headline, intro, background image, and credential stats.', 'href' => 'hero.php', 'icon' => 'fa-image'],
        ['id' => 'ecosystem', 'num' => 2, 'label' => 'Service ecosystem', 'description' => 'Section heading, service cards grid, and flow diagram.', 'href' => 'ecosystem.php', 'icon' => 'fa-circle-nodes'],
        ['id' => 'blocks', 'num' => 3, 'label' => 'Service blocks', 'description' => 'Detail sections — title, copy, and image for each offering.', 'href' => 'blocks.php', 'icon' => 'fa-list-check'],
        ['id' => 'compare', 'num' => 4, 'label' => 'The difference', 'description' => 'Why clients choose us — heading and shared compare bullets.', 'href' => 'compare.php', 'icon' => 'fa-scale-balanced'],
        ['id' => 'process', 'num' => 5, 'label' => 'Process preview', 'description' => 'How you work — heading, eight process steps, and link.', 'href' => 'process.php', 'icon' => 'fa-diagram-project'],
        ['id' => 'impact', 'num' => 6, 'label' => 'Results & impact', 'description' => 'Dark stats band — heading, background image, and hero statistics.', 'href' => 'impact.php', 'icon' => 'fa-chart-line'],
        ['id' => 'cases', 'num' => 7, 'label' => 'Case studies', 'description' => 'Featured projects — section heading and portfolio link.', 'href' => 'cases.php', 'icon' => 'fa-folder-open'],
        ['id' => 'testimonials', 'num' => 8, 'label' => 'Client trust', 'description' => 'Testimonials heading and client quotes.', 'href' => 'testimonials.php', 'icon' => 'fa-quote-left'],
        ['id' => 'faq', 'num' => 9, 'label' => 'FAQ', 'description' => 'Questions accordion — six question and answer pairs.', 'href' => 'faq.php', 'icon' => 'fa-circle-question'],
        ['id' => 'cta', 'num' => 10, 'label' => 'Call to action', 'description' => 'Bottom invitation — headline, disciplines line, text, and buttons.', 'href' => 'cta.php', 'icon' => 'fa-bullhorn'],
    ];
}

function cms_services_section_by_id(string $id): ?array
{
    foreach (cms_services_section_definitions() as $section) {
        if ($section['id'] === $id) {
            return $section;
        }
    }

    return null;
}

/** @return array<string, string> */
function cms_services_section_defaults(): array
{
    return [
        'services_ecosystem_eyebrow' => 'Integrated delivery',
        'services_ecosystem_title' => 'One studio. Every solution.',
        'services_ecosystem_intro' => 'Architecture, interiors, civil construction, approvals, and turnkey project management — connected under one accountable team from Rajkot, Gujarat.',
        'services_compare_eyebrow' => 'The difference',
        'services_compare_title' => 'Why clients choose us',
        'services_compare_intro' => 'Integrated design, civil expertise, and turnkey delivery — one studio, one contract, one point of accountability.',
        'services_process_eyebrow' => 'How we work',
        'services_process_title' => 'From consultation to handover',
        'services_process_intro' => 'Clear phases, shared tools, and sign-off moments — so you always know what happens next.',
        'services_impact_eyebrow' => 'Results',
        'services_impact_title' => 'Built with purpose',
        'services_cases_eyebrow' => 'Selected work',
        'services_cases_title' => 'Projects that prove the process',
        'services_cases_intro' => 'Real commissions across residential, commercial, and turnkey delivery — each shaped by discipline, clarity, and craft.',
        'services_testimonials_eyebrow' => 'Clients',
        'services_testimonials_title' => 'What clients say',
        'services_detail_link_text' => 'View related work',
        'services_detail_link_url' => 'work.html',
        'services_cases_link_text' => 'View all work',
        'services_cases_link_url' => 'work.html',
        'services_process_link_text' => 'Full process',
        'services_process_link_url' => 'process.html',
    ];
}

/** @param array<string, string> $settings */
function cms_fill_services_section_settings(array $settings): array
{
    foreach (cms_services_section_defaults() as $key => $default) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $settings[$key] = $default;
        }
    }

    return $settings;
}

/**
 * Compare bullets are shared with the studio page (studio_compare_* keys).
 *
 * @param array<string, string> $s
 * @return array<string, mixed>
 */
function cms_build_services_compare_payload(array $s): array
{
    $s = cms_fill_services_section_settings($s);

    return [
        'eyebrow' => $s['services_compare_eyebrow'] ?? '',
        'title' => $s['services_compare_title'] ?? '',
        'intro' => $s['services_compare_intro'] ?? '',
        'usTitle' => $s['studio_compare_us_title'] ?? 'Archevo Design',
        'themTitle' => $s['studio_compare_them_title'] ?? 'Traditional approach',
        'usItems' => array_values(array_filter(array_map(
            static fn (int $i): string => trim((string) ($s['studio_compare_us_' . $i] ?? '')),
            range(1, 5)
        ))),
        'themItems' => array_values(array_filter(array_map(
            static fn (int $i): string => trim((string) ($s['studio_compare_them_' . $i] ?? '')),
            range(1, 5)
        ))),
    ];
}

/**
 * @param array<string, string> $s
 * @return array<string, mixed>
 */
function cms_build_services_page_sections(array $s): array
{
    $s = cms_fill_services_section_settings($s);

    return [
        'ecosystemEyebrow' => $s['services_ecosystem_eyebrow'] ?? '',
        'ecosystemTitle' => $s['services_ecosystem_title'] ?? '',
        'ecosystemIntro' => $s['services_ecosystem_intro'] ?? '',
        'compare' => cms_build_services_compare_payload($s),
        'processEyebrow' => $s['services_process_eyebrow'] ?? '',
        'processTitle' => $s['services_process_title'] ?? '',
        'processIntro' => $s['services_process_intro'] ?? '',
        'impactEyebrow' => $s['services_impact_eyebrow'] ?? '',
        'impactTitle' => $s['services_impact_title'] ?? '',
        'casesEyebrow' => $s['services_cases_eyebrow'] ?? '',
        'casesTitle' => $s['services_cases_title'] ?? '',
        'casesIntro' => $s['services_cases_intro'] ?? '',
        'testimonialsEyebrow' => $s['services_testimonials_eyebrow'] ?? '',
        'testimonialsTitle' => $s['services_testimonials_title'] ?? '',
        'ctaSub' => $s['services_cta_sub'] ?? '',
        'detailLinkText' => $s['services_detail_link_text'] ?? '',
        'detailLinkUrl' => $s['services_detail_link_url'] ?? 'work.html',
        'casesLinkText' => $s['services_cases_link_text'] ?? '',
        'casesLinkUrl' => $s['services_cases_link_url'] ?? 'work.html',
        'processLinkText' => $s['services_process_link_text'] ?? '',
        'processLinkUrl' => $s['services_process_link_url'] ?? 'process.html',
        'impactImage' => public_upload_url($s['services_impact_image'] ?? ''),
    ];
}

function cms_seed_services_section_settings(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (cms_services_section_defaults() as $key => $default) {
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
}
