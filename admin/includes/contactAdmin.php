<?php

declare(strict_types=1);

require_once SPANGLE_ROOT . '/includes/cmsContactSections.php';
require_once __DIR__ . '/homeAdmin.php';

function contact_admin_require_section(string $sectionId): array
{
    $section = cms_contact_section_by_id($sectionId);
    if ($section === null) {
        http_response_code(404);
        exit('Contact section not found.');
    }

    return $section;
}

function contact_admin_page_vars(array $section): array
{
    return [
        'pageTitle' => $section['num'] . '. ' . $section['label'],
        'pageDescription' => $section['description'],
        'activeNav' => 'contact-page',
        'contactSection' => $section,
    ];
}

function contact_admin_sync_and_redirect(string $sectionHref, string $message): void
{
    global $pdo;

    require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
    cms_sync_plain_contact_fields($pdo);
    content_sync_site_json($pdo);
    admin_flash_set('success', $message);
    redirect($sectionHref);
}

function contact_admin_render_back(): void
{
    echo '<p class="adm-hint adm-card" style="margin-bottom:1rem;">';
    echo '<a href="' . e(admin_href('contact/index.php')) . '" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-left"></i> All contact sections</a> ';
    echo '<a href="' . e(admin_href('../contact.html')) . '" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview contact page</a>';
    echo '</p>';
}

function contact_admin_load_settings(PDO $pdo, array $keys): array
{
    return cms_fill_contact_section_settings(settings_get_many($pdo, $keys));
}

function contact_admin_save_settings(PDO $pdo, array $keys): void
{
    home_admin_save_text_fields($pdo, $keys);
}

function contact_admin_save_image_setting(PDO $pdo, array $appConfig, string $settingKey, string $fileField): void
{
    if (empty($_FILES[$fileField]['name'])) {
        return;
    }

    $up = Upload::image($appConfig, 'general', $_FILES[$fileField]);
    if ($up['ok']) {
        setting_set($pdo, $settingKey, $up['path']);
        media_register($pdo, $up['path'], basename($up['path']));
    }
}
