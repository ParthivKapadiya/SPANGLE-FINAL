<?php

declare(strict_types=1);

/**
 * Contact page section registry for the admin panel.
 *
 * @return array<int, array{id: string, num: int, label: string, description: string, href: string, icon: string}>
 */
function cms_contact_section_definitions(): array
{
    return [
        ['id' => 'hero', 'num' => 1, 'label' => 'Page header', 'description' => 'Top banner — label, headline, intro, and background image.', 'href' => 'hero.php', 'icon' => 'fa-image'],
        ['id' => 'visit', 'num' => 2, 'label' => 'Studio details', 'description' => 'Hours, parking, and appointment note shown beside the enquiry form.', 'href' => 'visit.php', 'icon' => 'fa-location-dot'],
    ];
}

function cms_contact_section_by_id(string $id): ?array
{
    foreach (cms_contact_section_definitions() as $section) {
        if ($section['id'] === $id) {
            return $section;
        }
    }

    return null;
}

/** @return array<string, string> */
function cms_contact_section_defaults(): array
{
    return [];
}

/** @param array<string, string> $settings */
function cms_fill_contact_section_settings(array $settings): array
{
    foreach (cms_contact_section_defaults() as $key => $default) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $settings[$key] = $default;
        }
    }

    return $settings;
}
