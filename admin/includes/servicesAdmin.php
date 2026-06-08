<?php

declare(strict_types=1);

require_once SPANGLE_ROOT . '/includes/cmsServicesSections.php';
require_once __DIR__ . '/homeAdmin.php';

function services_admin_require_section(string $sectionId): array
{
    $section = cms_services_section_by_id($sectionId);
    if ($section === null) {
        http_response_code(404);
        exit('Services section not found.');
    }

    return $section;
}

function services_admin_page_vars(array $section): array
{
    return [
        'pageTitle' => $section['num'] . '. ' . $section['label'],
        'pageDescription' => $section['description'],
        'activeNav' => 'services-page',
        'servicesSection' => $section,
    ];
}

function services_admin_sync_and_redirect(string $sectionHref, string $message): void
{
    global $pdo;

    require_once SPANGLE_ROOT . '/includes/cmsPlainFields.php';
    cms_sync_plain_services_fields($pdo);
    content_sync_site_json($pdo);
    admin_flash_set('success', $message);
    redirect($sectionHref);
}

function services_admin_render_back(): void
{
    echo '<p class="adm-hint adm-card" style="margin-bottom:1rem;">';
    echo '<a href="' . e(admin_href('services/index.php')) . '" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-left"></i> All services sections</a> ';
    echo '<a href="' . e(admin_href('../services.html')) . '" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview services page</a>';
    echo '</p>';
}

function services_admin_load_settings(PDO $pdo, array $keys): array
{
    return cms_fill_services_section_settings(settings_get_many($pdo, $keys));
}

function services_admin_save_settings(PDO $pdo, array $keys): void
{
    home_admin_save_text_fields($pdo, $keys);
}

/** @return array<int, array<string, mixed>> */
function services_admin_load_blocks(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id, number_label, title, short_description, show_on_home, is_active, sort_order
         FROM services ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
}

/** @return array<int, array<string, mixed>> */
function services_admin_load_process_steps(PDO $pdo): array
{
    $rows = $pdo->query(
        'SELECT id, step_label, title, description, context, sort_order, is_active
         FROM process_steps ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    return array_values(array_filter($rows, static function (array $row): bool {
        if (empty($row['is_active'])) {
            return false;
        }
        $context = (string) ($row['context'] ?? 'both');

        return $context === 'both' || $context === 'page';
    }));
}

function services_admin_save_image_setting(PDO $pdo, array $appConfig, string $settingKey, string $fileField): void
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
