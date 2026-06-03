<?php

declare(strict_types=1);

/**
 * Builds public site JSON from MySQL (same shape as content/site.json for content-bridge.js).
 */
final class SiteContent
{
    public static function build(PDO $pdo): array
    {
        $keys = [
            'public_base', 'site_name', 'tagline',
            'contact_phone_e164', 'contact_phone_display', 'contact_email', 'contact_address',
            'whatsapp_digits', 'whatsapp_prefill',
            'contact_section_title', 'contact_section_lead', 'contact_page_title', 'contact_page_lead',
            'social_instagram', 'social_facebook', 'social_youtube',
            'map_embed_url', 'map_title',
            'seo_description', 'seo_og_image',
            'home_hero_eyebrow', 'home_hero_title_html', 'home_hero_lead',
            'home_about_eyebrow', 'home_about_title', 'home_about_lead_html', 'home_about_image', 'home_about_image_alt', 'home_about_caption',
            'home_gallery_eyebrow', 'home_gallery_title', 'home_gallery_intro',
            'home_projects_eyebrow', 'home_projects_title', 'home_projects_intro',
            'studio_kicker', 'studio_title', 'studio_lead', 'studio_hero_image',
            'studio_philosophy_eyebrow', 'studio_philosophy_title', 'studio_philosophy_lead_1', 'studio_philosophy_lead_2', 'studio_philosophy_image',
            'services_kicker', 'services_title', 'services_lead', 'services_hero_image',
        ];
        $s = settings_get_many($pdo, $keys);

        $stats = [];
        $stmt = $pdo->query('SELECT stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC');
        foreach ($stmt->fetchAll() as $row) {
            $stats[] = ['value' => $row['stat_value'], 'label' => $row['stat_label']];
        }

        $gallery = [];
        $stmt = $pdo->query('SELECT image_path, alt_text, caption FROM gallery_items WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        foreach ($stmt->fetchAll() as $row) {
            $gallery[] = [
                'src' => public_upload_url($row['image_path']),
                'alt' => $row['alt_text'],
                'caption' => $row['caption'] ?? '',
            ];
        }

        $projects = [];
        $stmt = $pdo->query(
            'SELECT slug, title, location, category, summary, hero_image, link_url, home_highlight, home_layout
             FROM projects WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        foreach ($stmt->fetchAll() as $row) {
            $projects[] = [
                'slug' => $row['slug'],
                'title' => $row['title'],
                'location' => $row['location'],
                'category' => $row['category'],
                'summary' => $row['summary'],
                'heroImage' => public_upload_url((string) $row['hero_image']),
                'linkUrl' => $row['link_url'],
                'homeHighlight' => (bool) $row['home_highlight'],
                'homeLayout' => $row['home_layout'] ?? '',
            ];
        }

        $heroSlides = [];
        $stmt = $pdo->query('SELECT image_path, alt_text FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        foreach ($stmt->fetchAll() as $row) {
            $heroSlides[] = [
                'src' => public_upload_url($row['image_path']),
                'alt' => $row['alt_text'],
            ];
        }

        $servicesHome = [];
        $servicesPage = [];
        $stmt = $pdo->query(
            'SELECT number_label, title, short_description, eyebrow, detail_title, detail_lead_1, detail_lead_2, image_path, show_on_home
             FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        foreach ($stmt->fetchAll() as $row) {
            $item = [
                'number' => $row['number_label'],
                'title' => $row['title'],
                'shortDescription' => $row['short_description'],
                'eyebrow' => $row['eyebrow'],
                'detailTitle' => $row['detail_title'],
                'detailLead1' => $row['detail_lead_1'],
                'detailLead2' => $row['detail_lead_2'],
                'image' => public_upload_url((string) ($row['image_path'] ?? '')),
            ];
            $servicesPage[] = $item;
            if ((int) $row['show_on_home'] === 1) {
                $servicesHome[] = $item;
            }
        }

        return [
            'version' => 2,
            'source' => 'mysql',
            'publicBase' => $s['public_base'] ?? 'https://www.archevoinfra.com',
            'siteName' => $s['site_name'] ?? 'Archevo Design',
            'tagline' => $s['tagline'] ?? 'Architecture & Interiors',
            'contact' => [
                'phoneE164' => $s['contact_phone_e164'] ?? '',
                'phoneDisplay' => $s['contact_phone_display'] ?? '',
                'email' => $s['contact_email'] ?? '',
                'addressLine' => $s['contact_address'] ?? '',
                'whatsappDigits' => $s['whatsapp_digits'] ?? '',
                'whatsappPrefill' => $s['whatsapp_prefill'] ?? '',
                'contactSectionTitle' => $s['contact_section_title'] ?? '',
                'contactSectionLead' => $s['contact_section_lead'] ?? '',
                'contactPageTitle' => $s['contact_page_title'] ?? '',
                'contactPageLead' => $s['contact_page_lead'] ?? '',
            ],
            'social' => [
                'instagram' => $s['social_instagram'] ?? '',
                'facebook' => $s['social_facebook'] ?? '',
                'youtube' => $s['social_youtube'] ?? '',
            ],
            'maps' => [
                'embedUrl' => $s['map_embed_url'] ?? '',
                'title' => $s['map_title'] ?? '',
            ],
            'seo' => [
                'organizationDescription' => $s['seo_description'] ?? '',
                'defaultOgImage' => $s['seo_og_image'] ?? '',
            ],
            'home' => [
                'heroEyebrow' => $s['home_hero_eyebrow'] ?? '',
                'heroTitleHtml' => $s['home_hero_title_html'] ?? '',
                'heroLead' => $s['home_hero_lead'] ?? '',
                'stats' => $stats,
                'aboutEyebrow' => $s['home_about_eyebrow'] ?? '',
                'aboutTitle' => $s['home_about_title'] ?? '',
                'aboutLeadHtml' => $s['home_about_lead_html'] ?? '',
                'aboutImage' => public_upload_url($s['home_about_image'] ?? ''),
                'aboutImageAlt' => $s['home_about_image_alt'] ?? '',
                'aboutCaption' => $s['home_about_caption'] ?? '',
                'galleryEyebrow' => $s['home_gallery_eyebrow'] ?? '',
                'galleryTitle' => $s['home_gallery_title'] ?? '',
                'galleryIntro' => $s['home_gallery_intro'] ?? '',
                'projectsEyebrow' => $s['home_projects_eyebrow'] ?? '',
                'projectsTitle' => $s['home_projects_title'] ?? '',
                'projectsIntro' => $s['home_projects_intro'] ?? '',
            ],
            'heroSlides' => $heroSlides,
            'gallery' => $gallery,
            'projects' => $projects,
            'servicesHome' => $servicesHome,
            'pages' => [
                'studio' => [
                    'kicker' => $s['studio_kicker'] ?? '',
                    'title' => $s['studio_title'] ?? '',
                    'lead' => $s['studio_lead'] ?? '',
                    'heroImage' => public_upload_url($s['studio_hero_image'] ?? ''),
                    'philosophyEyebrow' => $s['studio_philosophy_eyebrow'] ?? '',
                    'philosophyTitle' => $s['studio_philosophy_title'] ?? '',
                    'philosophyLead1' => $s['studio_philosophy_lead_1'] ?? '',
                    'philosophyLead2' => $s['studio_philosophy_lead_2'] ?? '',
                    'philosophyImage' => public_upload_url($s['studio_philosophy_image'] ?? ''),
                ],
                'services' => [
                    'kicker' => $s['services_kicker'] ?? '',
                    'title' => $s['services_title'] ?? '',
                    'lead' => $s['services_lead'] ?? '',
                    'heroImage' => public_upload_url($s['services_hero_image'] ?? ''),
                    'items' => $servicesPage,
                ],
            ],
        ];
    }

    public static function exportSiteJson(PDO $pdo): void
    {
        $path = SPANGLE_ROOT . '/content/site.json';
        $payload = self::build($pdo);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }
        file_put_contents($path, $json . "\n");
    }
}
