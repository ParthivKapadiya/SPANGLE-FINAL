<?php

declare(strict_types=1);

/**
 * Builds public site JSON from MySQL (same shape as content/site.json for content-bridge.js).
 */
final class SiteContent
{
    public static function build(PDO $pdo): array
    {
        require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
        cms_run_migrations($pdo);

        require_once SPANGLE_ROOT . '/includes/cmsCopyKeys.php';
        require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
        require_once SPANGLE_ROOT . '/includes/cmsNavigation.php';
        require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';

        $keys = [
            'public_base', 'site_name', 'tagline', 'site_logo', 'site_logo_light', 'site_logo_dark', 'site_favicon',
            'brand_name', 'brand_line',
            'footer_blurb_html', 'footer_copyright',
            'contact_phone_e164', 'contact_phone_display', 'contact_email', 'contact_address', 'public_website_url',
            'whatsapp_digits', 'whatsapp_prefill',
            'contact_section_title', 'contact_section_lead', 'contact_page_title', 'contact_page_lead',
            'contact_hero_kicker', 'contact_hero_title', 'contact_hero_lead', 'contact_hero_image', 'contact_hours_html',
            'contact_intro_title', 'contact_intro_lead',
            'contact_step_1_title', 'contact_step_1_text', 'contact_step_2_title', 'contact_step_2_text',
            'contact_step_3_title', 'contact_step_3_text', 'contact_step_4_title', 'contact_step_4_text',
            'contact_project_types', 'contact_budget_ranges', 'contact_reasons',
            'contact_trust_1_title', 'contact_trust_1_text', 'contact_trust_2_title', 'contact_trust_2_text',
            'contact_trust_3_title', 'contact_trust_3_text', 'contact_trust_4_title', 'contact_trust_4_text',
            'contact_trust_5_title', 'contact_trust_5_text', 'contact_trust_6_title', 'contact_trust_6_text',
            'contact_founder_quote', 'contact_visit_parking', 'contact_visit_appointment', 'contact_wa_lead',
            'contact_cta_title', 'contact_cta_sub', 'contact_cta_btn_text', 'contact_cta_btn_url',
            'contact_cta_btn2_text', 'contact_cta_btn2_url',
            'contact_faq_q1', 'contact_faq_a1', 'contact_faq_q2', 'contact_faq_a2',
            'contact_faq_q3', 'contact_faq_a3', 'contact_faq_q4', 'contact_faq_a4',
            'contact_faq_q5', 'contact_faq_a5',
            'social_instagram', 'social_facebook', 'social_youtube', 'social_linkedin',
            'footer_agency_credit', 'analytics_ga_id', 'analytics_gsc_meta', 'site_description',
            'map_embed_url', 'map_title',
            'seo_description', 'seo_og_image',
            'home_hero_eyebrow', 'home_hero_title_html', 'home_hero_title_main', 'home_hero_title_highlight', 'home_hero_lead', 'home_hero_video_url',
            'home_about_eyebrow', 'home_about_title', 'home_about_lead_html', 'home_about_paragraph_1', 'home_about_paragraph_2',
            'home_about_image', 'home_about_image_alt', 'home_about_caption',
            'footer_blurb_1', 'footer_blurb_2',
            'home_gallery_eyebrow', 'home_gallery_title', 'home_gallery_intro',
            'home_projects_eyebrow', 'home_projects_title', 'home_projects_intro', 'home_projects_limit',
            'home_capabilities_eyebrow', 'home_capabilities_title', 'home_capabilities_intro',
            'home_process_eyebrow', 'home_process_title', 'home_process_intro',
            'home_testimonials_eyebrow', 'home_testimonials_title',
            'home_awards_eyebrow', 'home_awards_title',
            'home_team_eyebrow', 'home_team_title',
            'home_journal_eyebrow', 'home_journal_title',
            'home_gallery_limit',
            'home_cta_eyebrow', 'home_cta_title', 'home_cta_lead', 'home_cta_btn_text', 'home_cta_btn_url',
            'studio_kicker', 'studio_title', 'studio_lead', 'studio_hero_image',
            'studio_philosophy_eyebrow', 'studio_philosophy_title', 'studio_philosophy_lead_1', 'studio_philosophy_lead_2', 'studio_philosophy_image',
            'studio_values_eyebrow', 'studio_values_title', 'studio_values_html', 'studio_pullquote',
            'studio_strip_image_1', 'studio_strip_image_2', 'studio_strip_image_3',
            'services_kicker', 'services_title', 'services_lead', 'services_hero_image',
            'services_cta_sub', 'services_cta_btn2_text', 'services_cta_btn2_url',
            'services_faq_eyebrow', 'services_faq_title',
            'services_faq_q1', 'services_faq_a1', 'services_faq_q2', 'services_faq_a2',
            'services_faq_q3', 'services_faq_a3', 'services_faq_q4', 'services_faq_a4',
            'services_faq_q5', 'services_faq_a5', 'services_faq_q6', 'services_faq_a6',
            'work_kicker', 'work_title', 'work_lead', 'work_hero_image',
            'work_featured_eyebrow', 'work_stats_eyebrow', 'work_stats_title',
            'work_categories_eyebrow', 'work_categories_title',
            'work_testimonials_eyebrow', 'work_testimonials_title',
            'work_timeline_eyebrow', 'work_timeline_title', 'work_timeline_intro',
            'work_trust_eyebrow', 'work_trust_title',
            'work_cta_final_eyebrow', 'work_cta_final_title', 'work_cta_final_sub',
            'work_cta_final_btn_text', 'work_cta_final_btn2_text',
            'process_kicker', 'process_title', 'process_lead', 'process_hero_image',
            'process_split_eyebrow', 'process_split_title', 'process_split_lead_html',
            'process_split_paragraph_1', 'process_split_paragraph_2', 'process_split_image',
            'process_timeline_eyebrow', 'process_timeline_title',
            'process_cta_eyebrow', 'process_cta_sub', 'process_cta_btn2_text', 'process_cta_btn2_url',
            'process_faq_eyebrow', 'process_faq_title',
            'process_faq_q1', 'process_faq_a1', 'process_faq_q2', 'process_faq_a2',
            'process_faq_q3', 'process_faq_a3', 'process_faq_q4', 'process_faq_a4',
            'process_faq_q5', 'process_faq_a5',
            'journal_kicker', 'journal_title', 'journal_lead', 'journal_hero_image',
            'journal_stat_readers', 'journal_newsletter_title', 'journal_newsletter_lead',
            'journal_cta_eyebrow', 'journal_cta_title', 'journal_cta_sub',
            'journal_cta_btn2_text', 'journal_cta_btn2_url',
            'journal_faq_q1', 'journal_faq_a1', 'journal_faq_q2', 'journal_faq_a2',
            'journal_faq_q3', 'journal_faq_a3', 'journal_faq_q4', 'journal_faq_a4',
        ];
        $keys = array_merge($keys, array_keys(cms_copy_setting_keys()));
        $s = settings_get_many($pdo, $keys);
        $copy = cms_build_copy_array($s);

        $stats = [];
        $stmt = $pdo->query('SELECT stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC');
        foreach ($stmt->fetchAll() as $row) {
            $stats[] = ['value' => $row['stat_value'], 'label' => $row['stat_label']];
        }

        $homeGalleryLimit = max(4, min(24, (int) ($s['home_gallery_limit'] ?? 12)));
        $homeGallery = [];
        $stmt = $pdo->prepare(
            'SELECT image_path, alt_text, caption FROM gallery_items
             WHERE is_active = 1 AND show_on_home = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT ' . $homeGalleryLimit
        );
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $homeGallery[] = [
                'src' => public_upload_url($row['image_path']),
                'alt' => $row['alt_text'],
                'caption' => $row['caption'] ?? '',
            ];
        }

        $projects = [];
        $stmt = $pdo->query(
            'SELECT slug, title, location, category, project_type, summary, body_html, hero_image, link_url,
                    home_highlight, home_layout, is_featured, sort_order, area_label, completion_year,
                    services_provided
             FROM projects WHERE is_active = 1
             ORDER BY is_featured DESC, sort_order ASC, id ASC'
        );
        foreach ($stmt->fetchAll() as $row) {
            $heroRel = (string) ($row['hero_image'] ?? '');
            $img = image_responsive_bundle($heroRel, '(max-width: 900px) 100vw, 50vw');
            $ptype = ProjectRepository::normalizeType((string) ($row['project_type'] ?? $row['category'] ?? 'residential'));
            $projects[] = [
                'slug' => $row['slug'],
                'title' => $row['title'],
                'location' => $row['location'],
                'category' => $ptype,
                'projectType' => $ptype,
                'summary' => $row['summary'],
                'bodyHtml' => $row['body_html'] ?? '',
                'heroImage' => $img['src'],
                'heroSrcset' => $img['srcset'],
                'heroSizes' => $img['sizes'],
                'linkUrl' => $row['link_url'] ?: ('project.php?slug=' . rawurlencode((string) $row['slug'])),
                'homeHighlight' => (bool) $row['home_highlight'],
                'homeLayout' => $row['home_layout'] ?? '',
                'isFeatured' => (bool) ($row['is_featured'] ?? false),
                'sortOrder' => (int) ($row['sort_order'] ?? 0),
                'area' => (string) ($row['area_label'] ?? ''),
                'year' => isset($row['completion_year']) && $row['completion_year'] !== '' ? (int) $row['completion_year'] : null,
                'servicesProvided' => (string) ($row['services_provided'] ?? ''),
            ];
        }

        $testimonials = [];
        $team = [];
        $awards = [];
        $processSteps = [];
        $journalPosts = [];
        try {
            foreach ($pdo->query('SELECT quote, author_name, author_role FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() as $row) {
                $testimonials[] = ['quote' => $row['quote'], 'authorName' => $row['author_name'], 'authorRole' => $row['author_role'] ?? ''];
            }
            foreach ($pdo->query('SELECT name, role_title, bio, image_path, initials, linkedin_url FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() as $row) {
                $team[] = [
                    'name' => $row['name'],
                    'role' => $row['role_title'],
                    'bio' => $row['bio'] ?? '',
                    'image' => public_upload_url((string) ($row['image_path'] ?? '')),
                    'initials' => $row['initials'] ?? '',
                    'linkedin' => trim((string) ($row['linkedin_url'] ?? '')),
                ];
            }
            foreach ($pdo->query('SELECT icon_class, title, subtitle FROM awards WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() as $row) {
                $awards[] = ['icon' => $row['icon_class'], 'title' => $row['title'], 'subtitle' => $row['subtitle'] ?? ''];
            }
            foreach ($pdo->query('SELECT step_label, title, description, context FROM process_steps WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() as $row) {
                $processSteps[] = [
                    'label' => $row['step_label'],
                    'title' => $row['title'],
                    'description' => $row['description'] ?? '',
                    'context' => $row['context'] ?? 'both',
                ];
            }
            foreach ($pdo->query('SELECT slug, title, excerpt, category, read_minutes, body_html, image_path FROM journal_posts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() as $row) {
                $journalPosts[] = [
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'excerpt' => $row['excerpt'] ?? '',
                    'category' => $row['category'] ?? '',
                    'readMinutes' => $row['read_minutes'] !== null ? (int) $row['read_minutes'] : null,
                    'bodyHtml' => $row['body_html'] ?? '',
                    'image' => public_upload_url((string) ($row['image_path'] ?? '')),
                    'url' => journal_public_url((string) $row['slug']),
                ];
            }
        } catch (Throwable $e) {
            // tables may not exist yet
        }

        $heroSlides = [];
        $stmt = $pdo->query('SELECT image_path, alt_text FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        foreach ($stmt->fetchAll() as $row) {
            $heroSlides[] = [
                'src' => public_upload_url($row['image_path']),
                'alt' => $row['alt_text'],
            ];
        }

        $servicesPage = [];
        $stmt = $pdo->query(
            'SELECT number_label, title, short_description, eyebrow, detail_title, detail_lead_1, detail_lead_2, image_path
             FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        foreach ($stmt->fetchAll() as $row) {
            $servicesPage[] = [
                'number' => $row['number_label'],
                'title' => $row['title'],
                'shortDescription' => $row['short_description'],
                'eyebrow' => $row['eyebrow'],
                'detailTitle' => $row['detail_title'],
                'detailLead1' => $row['detail_lead_1'],
                'detailLead2' => $row['detail_lead_2'],
                'image' => public_upload_url((string) ($row['image_path'] ?? '')),
            ];
        }
        // Same list drives home cards, services.html blocks, and footer links.
        $servicesHome = $servicesPage;

        return [
            'version' => 2,
            'source' => 'mysql',
            'publicBase' => local_public_base(trim((string) ($s['public_base'] ?? ''))),
            'siteName' => $s['site_name'] ?? 'Archevo Design',
            'tagline' => $s['tagline'] ?? 'Architecture & Interiors',
            'branding' => [
                'logo' => public_upload_url($s['site_logo'] ?? 'uploads/branding/archevo-logo.png'),
                'logoLight' => public_upload_url($s['site_logo_light'] ?? 'uploads/branding/archevo-logo-light.png'),
                'logoDark' => public_upload_url($s['site_logo_dark'] ?? 'uploads/branding/archevo-logo-dark.png'),
                'favicon' => public_upload_url($s['site_favicon'] ?? 'uploads/branding/archevo-icon.png'),
                'brandName' => $s['brand_name'] ?? 'Archevo Design',
                'brandLine' => $s['brand_line'] ?? 'Architecture & Interiors',
                'footerBlurbHtml' => cms_resolve_footer_blurb_html($s),
                'footerCopyright' => $s['footer_copyright'] ?? '',
                'footerAgencyCredit' => $s['footer_agency_credit'] ?? '',
            ],
            'navigation' => cms_navigation_from_settings($s),
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
                'websiteUrl' => $s['public_website_url'] ?? '',
            ],
            'social' => [
                'instagram' => $s['social_instagram'] ?? '',
                'facebook' => $s['social_facebook'] ?? '',
                'linkedin' => $s['social_linkedin'] ?? '',
                'youtube' => $s['social_youtube'] ?? '',
            ],
            'analytics' => [
                'gaId' => trim((string) ($s['analytics_ga_id'] ?? '')),
                'gscMeta' => trim((string) ($s['analytics_gsc_meta'] ?? '')),
            ],
            'maps' => [
                'embedUrl' => $s['map_embed_url'] ?? '',
                'title' => $s['map_title'] ?? '',
            ],
            'seo' => [
                'organizationDescription' => $s['seo_description'] ?? '',
                'defaultOgImage' => $s['seo_og_image'] ?? '',
            ],
            'copy' => $copy,
            'legal' => [
                'privacyHtml' => $copy['legal_privacy_html'] ?? '',
                'termsHtml' => $copy['legal_terms_html'] ?? '',
            ],
            'seoPages' => [
                'home' => ['title' => $copy['seo_home_title'] ?? '', 'description' => $copy['seo_home_description'] ?? ''],
                'studio' => ['title' => $copy['seo_studio_title'] ?? '', 'description' => $copy['seo_studio_description'] ?? ''],
                'services' => ['title' => $copy['seo_services_title'] ?? '', 'description' => $copy['seo_services_description'] ?? ''],
                'work' => ['title' => $copy['seo_work_title'] ?? '', 'description' => $copy['seo_work_description'] ?? ''],
                'process' => ['title' => $copy['seo_process_title'] ?? '', 'description' => $copy['seo_process_description'] ?? ''],
                'journal' => ['title' => $copy['seo_journal_title'] ?? '', 'description' => $copy['seo_journal_description'] ?? ''],
                'contact' => ['title' => $copy['seo_contact_title'] ?? '', 'description' => $copy['seo_contact_description'] ?? ''],
                'privacy' => ['title' => $copy['seo_privacy_title'] ?? '', 'description' => $copy['seo_privacy_description'] ?? ''],
                'terms' => ['title' => $copy['seo_terms_title'] ?? '', 'description' => $copy['seo_terms_description'] ?? ''],
                'thanks' => ['title' => $copy['seo_thanks_title'] ?? '', 'description' => $copy['seo_thanks_description'] ?? ''],
            ],
            'home' => [
                'heroEyebrow' => $s['home_hero_eyebrow'] ?? '',
                'heroTitleHtml' => cms_resolve_hero_title_html($s),
                'heroLead' => $s['home_hero_lead'] ?? '',
                'heroVideoUrl' => trim((string) ($s['home_hero_video_url'] ?? '')),
                'stats' => $stats,
                'aboutEyebrow' => $s['home_about_eyebrow'] ?? '',
                'aboutTitle' => $s['home_about_title'] ?? '',
                'aboutLeadHtml' => cms_resolve_about_lead_html($s),
                'aboutImage' => public_upload_url($s['home_about_image'] ?? ''),
                'aboutImageAlt' => $s['home_about_image_alt'] ?? '',
                'aboutCaption' => $s['home_about_caption'] ?? '',
                'galleryEyebrow' => $s['home_gallery_eyebrow'] ?? '',
                'galleryTitle' => $s['home_gallery_title'] ?? '',
                'galleryIntro' => $s['home_gallery_intro'] ?? '',
                'projectsEyebrow' => $s['home_projects_eyebrow'] ?? '',
                'projectsTitle' => $s['home_projects_title'] ?? '',
                'projectsIntro' => $s['home_projects_intro'] ?? '',
                'projectsLimit' => max(4, min(12, (int) ($s['home_projects_limit'] ?? 8))),
                'capabilitiesEyebrow' => $s['home_capabilities_eyebrow'] ?? '',
                'capabilitiesTitle' => $s['home_capabilities_title'] ?? '',
                'capabilitiesIntro' => $s['home_capabilities_intro'] ?? '',
                'processEyebrow' => $s['home_process_eyebrow'] ?? '',
                'processTitle' => $s['home_process_title'] ?? '',
                'processIntro' => $s['home_process_intro'] ?? '',
                'testimonialsEyebrow' => $s['home_testimonials_eyebrow'] ?? '',
                'testimonialsTitle' => $s['home_testimonials_title'] ?? '',
                'awardsEyebrow' => $s['home_awards_eyebrow'] ?? ($s['studio_values_eyebrow'] ?? ''),
                'awardsTitle' => $s['home_awards_title'] ?? ($s['studio_values_title'] ?? ''),
                'teamEyebrow' => $s['home_team_eyebrow'] ?? '',
                'teamTitle' => $s['home_team_title'] ?? '',
                'journalEyebrow' => $s['home_journal_eyebrow'] ?? '',
                'journalTitle' => $s['home_journal_title'] ?? '',
                'ctaEyebrow' => $s['home_cta_eyebrow'] ?? '',
                'ctaTitle' => $s['home_cta_title'] ?? '',
                'ctaLead' => $s['home_cta_lead'] ?? '',
                'ctaBtnText' => $s['home_cta_btn_text'] ?? '',
                'ctaBtnUrl' => $s['home_cta_btn_url'] ?? 'contact.html',
            ],
            'testimonials' => $testimonials,
            'team' => $team,
            'awards' => $awards,
            'processSteps' => $processSteps,
            'journalPosts' => $journalPosts,
            'heroSlides' => $heroSlides,
            'gallery' => $homeGallery,
            'homeGallery' => $homeGallery,
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
                    'valuesEyebrow' => $s['studio_values_eyebrow'] ?? ($s['home_awards_eyebrow'] ?? ''),
                    'valuesTitle' => $s['studio_values_title'] ?? ($s['home_awards_title'] ?? ''),
                    'valuesHtml' => $awards
                        ? cms_studio_values_html_from_awards($awards)
                        : cms_studio_values_html_from_settings($s),
                    'pullquote' => $s['studio_pullquote'] ?? '',
                    'stripImages' => array_values(array_filter([
                        public_upload_url($s['studio_strip_image_1'] ?? ''),
                        public_upload_url($s['studio_strip_image_2'] ?? ''),
                        public_upload_url($s['studio_strip_image_3'] ?? ''),
                    ])),
                ],
                'work' => [
                    'kicker' => $s['work_kicker'] ?? '',
                    'title' => $s['work_title'] ?? '',
                    'lead' => $s['work_lead'] ?? '',
                    'heroImage' => public_upload_url($s['work_hero_image'] ?? ''),
                    'featuredEyebrow' => $s['work_featured_eyebrow'] ?? '',
                    'statsEyebrow' => $s['work_stats_eyebrow'] ?? '',
                    'statsTitle' => $s['work_stats_title'] ?? '',
                    'categoriesEyebrow' => $s['work_categories_eyebrow'] ?? '',
                    'categoriesTitle' => $s['work_categories_title'] ?? '',
                    'testimonialsEyebrow' => $s['work_testimonials_eyebrow'] ?? '',
                    'testimonialsTitle' => $s['work_testimonials_title'] ?? '',
                    'timelineEyebrow' => $s['work_timeline_eyebrow'] ?? '',
                    'timelineTitle' => $s['work_timeline_title'] ?? '',
                    'timelineIntro' => $s['work_timeline_intro'] ?? '',
                    'trustEyebrow' => $s['work_trust_eyebrow'] ?? '',
                    'trustTitle' => $s['work_trust_title'] ?? '',
                    'ctaEyebrow' => $s['work_cta_final_eyebrow'] ?? '',
                    'ctaTitle' => $s['work_cta_final_title'] ?? '',
                    'ctaSub' => $s['work_cta_final_sub'] ?? '',
                ],
                'contact' => [
                    'heroKicker' => $s['contact_hero_kicker'] ?? '',
                    'heroTitle' => $s['contact_hero_title'] ?? '',
                    'heroLead' => $s['contact_hero_lead'] ?? '',
                    'heroImage' => public_upload_url($s['contact_hero_image'] ?? ''),
                    'hoursHtml' => $s['contact_hours_html'] ?? '',
                    'introTitle' => $s['contact_intro_title'] ?? '',
                    'introLead' => $s['contact_intro_lead'] ?? '',
                    'steps' => cms_contact_steps_from_settings($s),
                    'projectTypes' => cms_contact_lines_from_setting(
                        (string) ($s['contact_project_types'] ?? ''),
                        ['Residential', 'Commercial', 'Interior Design', 'Construction', 'Turnkey', 'Renovation']
                    ),
                    'budgetRanges' => cms_contact_lines_from_setting(
                        (string) ($s['contact_budget_ranges'] ?? ''),
                        ['₹10L – ₹25L', '₹25L – ₹50L', '₹50L – ₹1Cr', '₹1Cr+']
                    ),
                    'reasons' => cms_contact_lines_from_setting(
                        (string) ($s['contact_reasons'] ?? ''),
                        ['New Home Design', 'Villa Design', 'Office Design', 'Interior Design', 'Construction', 'Turnkey Solutions']
                    ),
                    'trustPoints' => cms_contact_trust_from_settings($s),
                    'founderQuote' => $s['contact_founder_quote'] ?? '',
                    'visitParking' => $s['contact_visit_parking'] ?? '',
                    'visitAppointment' => $s['contact_visit_appointment'] ?? '',
                    'whatsappLead' => $s['contact_wa_lead'] ?? '',
                    'faq' => cms_contact_faq_from_settings($s),
                    'ctaSecondary' => [
                        'text' => $s['contact_cta_btn2_text'] ?? '',
                        'url' => $s['contact_cta_btn2_url'] ?? '#cnt-enquiry-form',
                    ],
                ],
                'process' => [
                    'kicker' => $s['process_kicker'] ?? '',
                    'title' => $s['process_title'] ?? '',
                    'lead' => $s['process_lead'] ?? '',
                    'heroImage' => public_upload_url($s['process_hero_image'] ?? ''),
                    'splitEyebrow' => $s['process_split_eyebrow'] ?? '',
                    'splitTitle' => $s['process_split_title'] ?? '',
                    'splitLeadHtml' => cms_build_about_lead_html(
                        (string) ($s['process_split_paragraph_1'] ?? ''),
                        (string) ($s['process_split_paragraph_2'] ?? '')
                    ) ?: ($s['process_split_lead_html'] ?? ''),
                    'splitLead1' => $s['process_split_paragraph_1'] ?? '',
                    'splitLead2' => $s['process_split_paragraph_2'] ?? '',
                    'splitImage' => public_upload_url($s['process_split_image'] ?? ''),
                    'timelineEyebrow' => $s['process_timeline_eyebrow'] ?? '',
                    'timelineTitle' => $s['process_timeline_title'] ?? '',
                    'faq' => cms_process_faq_from_settings($s),
                    'ctaSecondary' => [
                        'text' => $s['process_cta_btn2_text'] ?? '',
                        'url' => $s['process_cta_btn2_url'] ?? 'contact.html',
                    ],
                ],
                'journal' => [
                    'kicker' => $s['journal_kicker'] ?? '',
                    'title' => $s['journal_title'] ?? '',
                    'lead' => $s['journal_lead'] ?? '',
                    'heroImage' => public_upload_url($s['journal_hero_image'] ?? ''),
                    'newsletterTitle' => $s['journal_newsletter_title'] ?? '',
                    'newsletterLead' => $s['journal_newsletter_lead'] ?? '',
                    'faq' => cms_journal_faq_from_settings($s),
                    'ctaSecondary' => [
                        'text' => $s['journal_cta_btn2_text'] ?? '',
                        'url' => $s['journal_cta_btn2_url'] ?? 'contact.html',
                    ],
                ],
                'services' => [
                    'kicker' => $s['services_kicker'] ?? '',
                    'title' => $s['services_title'] ?? '',
                    'lead' => $s['services_lead'] ?? '',
                    'heroImage' => public_upload_url($s['services_hero_image'] ?? ''),
                    'items' => $servicesPage,
                    'faq' => cms_services_faq_from_settings($s),
                    'ctaSecondary' => [
                        'text' => $s['services_cta_btn2_text'] ?? '',
                        'url' => $s['services_cta_btn2_url'] ?? 'contact.html',
                    ],
                ],
            ],
        ];
    }

    public static function exportSiteJson(PDO $pdo): bool
    {
        $path = SPANGLE_ROOT . '/content/site.json';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            return false;
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }
        if (is_file($path) && !is_writable($path)) {
            @chmod($path, 0666);
        }
        $payload = self::build($pdo);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        return @file_put_contents($path, $json . "\n") !== false;
    }
}
