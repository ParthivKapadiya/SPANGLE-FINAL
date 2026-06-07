<?php

declare(strict_types=1);

/**
 * Homepage section registry for the admin panel.
 *
 * @return array<int, array{id: string, num: int, label: string, description: string, href: string, icon: string}>
 */
function cms_home_section_definitions(): array
{
    return [
        ['id' => 'hero', 'num' => 1, 'label' => 'Hero', 'description' => 'Top banner — images, rotating headlines, tags, trust row, stats, and featured project card.', 'href' => 'hero.php', 'icon' => 'fa-image'],
        ['id' => 'trust-strip', 'num' => 2, 'label' => 'Trust strip', 'description' => 'Scrolling credentials bar below the hero.', 'href' => 'trust-strip.php', 'icon' => 'fa-shield-halved'],
        ['id' => 'about', 'num' => 3, 'label' => 'About', 'description' => 'Practice story, photo, and Mission / Vision / Philosophy / Execution pillars.', 'href' => 'about.php', 'icon' => 'fa-building-columns'],
        ['id' => 'why-archevo', 'num' => 4, 'label' => 'Why Archevo', 'description' => 'Six cards explaining why clients choose the studio.', 'href' => 'why-archevo.php', 'icon' => 'fa-star'],
        ['id' => 'services', 'num' => 5, 'label' => 'Services', 'description' => 'Section heading and service cards on the home page.', 'href' => 'services.php', 'icon' => 'fa-briefcase'],
        ['id' => 'projects', 'num' => 6, 'label' => 'Projects', 'description' => 'Featured project tiles and section intro.', 'href' => 'projects.php', 'icon' => 'fa-folder-open'],
        ['id' => 'gallery', 'num' => 7, 'label' => 'Gallery', 'description' => 'Visual studies grid — heading and photos.', 'href' => 'gallery.php', 'icon' => 'fa-camera'],
        ['id' => 'process', 'num' => 8, 'label' => 'Process', 'description' => 'How you move from brief to keys — steps on home.', 'href' => 'process.php', 'icon' => 'fa-diagram-project'],
        ['id' => 'impact', 'num' => 9, 'label' => 'Impact', 'description' => 'Dark stats band — “Built at scale. Trusted at home.”', 'href' => 'impact.php', 'icon' => 'fa-chart-line'],
        ['id' => 'testimonials', 'num' => 10, 'label' => 'Testimonials', 'description' => 'Client quotes marquee.', 'href' => 'testimonials.php', 'icon' => 'fa-quote-left'],
        ['id' => 'highlights', 'num' => 11, 'label' => 'Studio highlights', 'description' => 'Why clients work with us — trust points.', 'href' => 'highlights.php', 'icon' => 'fa-award'],
        ['id' => 'cta', 'num' => 12, 'label' => 'Call to action', 'description' => 'Bottom invitation — “Let’s build something extraordinary.”', 'href' => 'cta.php', 'icon' => 'fa-bullhorn'],
        ['id' => 'contact', 'num' => 13, 'label' => 'Contact', 'description' => 'Contact details and enquiry form on the home page.', 'href' => 'contact.php', 'icon' => 'fa-envelope'],
        ['id' => 'map', 'num' => 14, 'label' => 'Map', 'description' => 'Google Map embed at the bottom of the home page.', 'href' => 'map.php', 'icon' => 'fa-map-location-dot'],
    ];
}

function cms_home_section_by_id(string $id): ?array
{
    foreach (cms_home_section_definitions() as $section) {
        if ($section['id'] === $id) {
            return $section;
        }
    }

    return null;
}
