<?php

declare(strict_types=1);

require_once SPANGLE_ROOT . '/includes/cmsStudioSections.php';
require_once __DIR__ . '/homeAdmin.php';

function studio_admin_require_section(string $sectionId): array
{
    $section = cms_studio_section_by_id($sectionId);
    if ($section === null) {
        http_response_code(404);
        exit('Studio section not found.');
    }

    return $section;
}

function studio_admin_page_vars(array $section): array
{
    return [
        'pageTitle' => $section['num'] . '. ' . $section['label'],
        'pageDescription' => $section['description'],
        'activeNav' => 'studio',
        'studioSection' => $section,
    ];
}

function studio_admin_sync_and_redirect(string $sectionHref, string $message): void
{
    global $pdo;
    content_sync_site_json($pdo);
    admin_flash_set('success', $message);
    redirect($sectionHref);
}

function studio_admin_render_back(): void
{
    echo '<p class="adm-hint adm-card" style="margin-bottom:1rem;">';
    echo '<a href="' . e(admin_href('studio/index.php')) . '" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-left"></i> All studio sections</a> ';
    echo '<a href="' . e(admin_href('../studio.html')) . '" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview studio page</a>';
    echo '</p>';
}

function studio_admin_load_settings(PDO $pdo, array $keys): array
{
    return cms_fill_studio_settings(settings_get_many($pdo, $keys));
}

function studio_admin_save_settings(PDO $pdo, array $keys): void
{
    home_admin_save_text_fields($pdo, $keys);
}

function studio_admin_save_image_setting(PDO $pdo, array $appConfig, string $settingKey, string $fileField): void
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
