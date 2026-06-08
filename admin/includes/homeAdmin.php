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

/** @return array<string, string> */
function home_admin_impact_defaults(): array
{
    return [
        'home_impact_eyebrow' => 'Impact',
        'home_impact_title' => 'Built at scale. Trusted at home.',
    ];
}

function home_admin_fill_impact_settings(array $settings): array
{
    foreach (home_admin_impact_defaults() as $key => $default) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $settings[$key] = $default;
        }
    }

    return $settings;
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

function home_admin_ensure_stats(PDO $pdo, int $minimum = 4): array
{
    cms_add_column_if_missing($pdo, 'home_stats', 'stat_icon', 'VARCHAR(80) NULL');

    $stats = $pdo->query('SELECT id, stat_value, stat_label, stat_icon FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
    $defaults = [
        ['150+', 'Projects Delivered', 'fa-solid fa-chart-line'],
        ['16+', 'Years Experience', 'fa-solid fa-chart-line'],
        ['2M+', 'Sq Ft Designed', 'fa-solid fa-chart-line'],
        ['98%', 'Client Satisfaction', 'fa-solid fa-chart-line'],
        ['', 'Government Approval Support', 'fa-solid fa-landmark'],
        ['', 'Turnkey Execution', 'fa-solid fa-key'],
    ];
    $target = max(4, min(6, $minimum));
    if (count($stats) >= $target) {
        foreach ($stats as $i => $row) {
            if (trim((string) ($row['stat_icon'] ?? '')) !== '') {
                continue;
            }
            $icon = ($i >= 4 || trim((string) ($row['stat_value'] ?? '')) === '')
                ? ($defaults[$i][2] ?? 'fa-solid fa-award')
                : 'fa-solid fa-chart-line';
            $pdo->prepare('UPDATE home_stats SET stat_icon = ? WHERE id = ?')->execute([$icon, (int) $row['id']]);
            $stats[$i]['stat_icon'] = $icon;
        }

        return $stats;
    }

    $ins = $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, stat_icon, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($defaults as $i => $d) {
        if ($i >= count($stats) && $i < $target) {
            $ins->execute([$d[0], $d[1], $d[2], $i]);
        }
    }

    return $pdo->query('SELECT id, stat_value, stat_label, stat_icon FROM home_stats ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function home_admin_hero_stats(PDO $pdo): array
{
    return array_slice(home_admin_ensure_stats($pdo, 4), 0, 4);
}

function home_admin_trust_strip_stats(PDO $pdo): array
{
    return home_admin_ensure_stats($pdo, 6);
}

function home_admin_is_trust_credential(int $index): bool
{
    return $index >= 4;
}

function home_admin_save_stats(PDO $pdo): void
{
    if (!isset($_POST['stats']) || !is_array($_POST['stats'])) {
        return;
    }
    $sortById = [];
    foreach ($pdo->query('SELECT id, sort_order FROM home_stats')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sortById[(int) $row['id']] = (int) $row['sort_order'];
    }
    foreach ($_POST['stats'] as $statId => $pair) {
        $sid = (int) $statId;
        if ($sid <= 0) {
            continue;
        }
        $value = trim((string) ($pair['value'] ?? ''));
        if (home_admin_is_trust_credential($sortById[$sid] ?? 99)) {
            $value = '';
        }
        $pdo->prepare('UPDATE home_stats SET stat_value = ?, stat_label = ?, stat_icon = ? WHERE id = ?')
            ->execute([
                $value,
                trim((string) ($pair['label'] ?? '')),
                trim((string) ($pair['icon'] ?? '')),
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

function home_admin_render_save(string $label = 'Save section', bool $sticky = false): void
{
    $class = 'adm-card adm-glass' . ($sticky ? ' adm-save-bar' : '');
    echo '<div class="' . e($class) . '"><div class="adm-actions">';
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
