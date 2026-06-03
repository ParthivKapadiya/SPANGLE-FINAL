<?php

declare(strict_types=1);

/**
 * Main site header navigation (labels + page links).
 *
 * @return array<string, array{label:string, href:string, setting_label:string, setting_href:string}>
 */
function cms_nav_item_definitions(): array
{
    return [
        'studio' => [
            'label' => 'Studio',
            'href' => 'studio.html',
            'setting_label' => 'nav_studio_label',
            'setting_href' => 'nav_studio_href',
        ],
        'services' => [
            'label' => 'Services',
            'href' => 'services.html',
            'setting_label' => 'nav_services_label',
            'setting_href' => 'nav_services_href',
        ],
        'work' => [
            'label' => 'Work',
            'href' => 'work.html',
            'setting_label' => 'nav_work_label',
            'setting_href' => 'nav_work_href',
        ],
        'process' => [
            'label' => 'Process',
            'href' => 'process.html',
            'setting_label' => 'nav_process_label',
            'setting_href' => 'nav_process_href',
        ],
        'journal' => [
            'label' => 'Journal',
            'href' => 'journal.html',
            'setting_label' => 'nav_journal_label',
            'setting_href' => 'nav_journal_href',
        ],
        'contact' => [
            'label' => 'Enquire',
            'href' => 'contact.html',
            'setting_label' => 'nav_contact_label',
            'setting_href' => 'nav_contact_href',
        ],
    ];
}

/**
 * @param array<string, string> $settings
 *
 * @return array<string, array{label: string, href: string}>
 */
function cms_navigation_from_settings(array $settings): array
{
    $out = [];
    foreach (cms_nav_item_definitions() as $id => $def) {
        $label = trim((string) ($settings[$def['setting_label']] ?? ''));
        $href = trim((string) ($settings[$def['setting_href']] ?? ''));
        $out[$id] = [
            'label' => $label !== '' ? $label : $def['label'],
            'href' => $href !== '' ? $href : $def['href'],
        ];
    }

    return $out;
}
