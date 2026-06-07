<?php

declare(strict_types=1);

require_once SPANGLE_ROOT . '/includes/cmsHomeSections.php';

function home_admin_require_section(string $sectionId): array
{
    $section = cms_home_section_by_id($sectionId);
    if ($section === null) {
        http_response_code(404);
        exit('Home section not found.');
    }

    return $section;
}

function home_admin_page_vars(array $section): array
{
    return [
        'pageTitle' => $section['num'] . '. ' . $section['label'],
        'pageDescription' => $section['description'],
        'activeNav' => 'home',
        'homeSection' => $section,
    ];
}

function home_admin_save_text_fields(PDO $pdo, array $keys): void
{
    foreach ($keys as $key) {
        if (!isset($_POST[$key])) {
            continue;
        }
        setting_set($pdo, $key, trim((string) $_POST[$key]));
    }
}

function home_admin_sync_and_redirect(string $sectionHref, string $message): void
{
    global $pdo;
    content_sync_site_json($pdo);
    admin_flash_set('success', $message);
    redirect($sectionHref);
}

function home_admin_ensure_stats(PDO $pdo): array
{
    $stats = $pdo->query('SELECT id, stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
    if (count($stats) >= 4) {
        return $stats;
    }
    $defaults = [
        ['150+', 'Projects Delivered'],
        ['16+', 'Years Experience'],
        ['2M+', 'Sq Ft Designed'],
        ['98%', 'Client Satisfaction'],
    ];
    $ins = $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, sort_order) VALUES (?, ?, ?)');
    foreach ($defaults as $i => $d) {
        if ($i >= count($stats)) {
            $ins->execute([$d[0], $d[1], $i]);
        }
    }

    return $pdo->query('SELECT id, stat_value, stat_label FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function home_admin_save_stats(PDO $pdo): void
{
    if (!isset($_POST['stats']) || !is_array($_POST['stats'])) {
        return;
    }
    foreach ($_POST['stats'] as $statId => $pair) {
        $sid = (int) $statId;
        if ($sid <= 0) {
            continue;
        }
        $pdo->prepare('UPDATE home_stats SET stat_value = ?, stat_label = ? WHERE id = ?')
            ->execute([
                trim((string) ($pair['value'] ?? '')),
                trim((string) ($pair['label'] ?? '')),
                $sid,
            ]);
    }
}

function home_admin_render_back(): void
{
    echo '<p class="adm-hint adm-card" style="margin-bottom:1rem;">';
    echo '<a href="' . e(admin_href('home/index.php')) . '" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-left"></i> All home sections</a> ';
    echo '<a href="' . e(admin_href('../index.html')) . '" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> Preview home page</a>';
    echo '</p>';
}

function home_admin_render_field(string $key, string $label, array $s, string $type = 'text'): void
{
    echo '<div class="adm-field">';
    echo '<label for="' . e($key) . '">' . e($label) . '</label>';
    if ($type === 'textarea') {
        echo '<textarea name="' . e($key) . '" id="' . e($key) . '" rows="3">' . e($s[$key] ?? '') . '</textarea>';
    } elseif ($type === 'number') {
        echo '<input type="number" name="' . e($key) . '" id="' . e($key) . '" value="' . e((string) ($s[$key] ?? '')) . '" />';
    } else {
        echo '<input type="text" name="' . e($key) . '" id="' . e($key) . '" value="' . e($s[$key] ?? '') . '" />';
    }
    echo '</div>';
}

function home_admin_render_link_row(string $textKey, string $urlKey, array $s, string $textLabel = 'Link text', string $urlLabel = 'Link URL'): void
{
    echo '<div class="adm-field adm-field-row">';
    echo '<div><label for="' . e($textKey) . '">' . e($textLabel) . '</label>';
    echo '<input type="text" name="' . e($textKey) . '" id="' . e($textKey) . '" value="' . e($s[$textKey] ?? '') . '" /></div>';
    echo '<div><label for="' . e($urlKey) . '">' . e($urlLabel) . '</label>';
    echo '<input type="text" name="' . e($urlKey) . '" id="' . e($urlKey) . '" value="' . e($s[$urlKey] ?? '') . '" /></div>';
    echo '</div>';
}

function home_admin_render_save(string $label = 'Save section'): void
{
    echo '<div class="adm-card adm-glass"><div class="adm-actions">';
    echo '<button type="submit" class="adm-btn adm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ' . e($label) . '</button>';
    echo '</div></div>';
}

function home_admin_card_link(string $href, string $label, string $hint = ''): void
{
    echo '<p class="adm-hint">';
    echo '<a href="' . e(admin_href($href)) . '" class="adm-btn adm-btn-sm adm-btn-ghost">' . e($label) . '</a>';
    if ($hint !== '') {
        echo ' — ' . e($hint);
    }
    echo '</p>';
}
