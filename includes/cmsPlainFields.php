<?php

declare(strict_types=1);

/**
 * Build public-site HTML from plain admin fields (no raw HTML for clients).
 */

function cms_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cms_build_hero_title_html(string $main, string $highlight = ''): string
{
    $main = trim($main);
    $highlight = trim($highlight);
    if ($main === '') {
        return '';
    }
    if ($highlight === '') {
        return cms_escape($main);
    }

    return cms_escape($main) . ' <em>' . cms_escape($highlight) . '</em>';
}

function cms_parse_hero_title_html(string $html): array
{
    $html = trim($html);
    if ($html === '') {
        return ['main' => '', 'highlight' => ''];
    }
    if (preg_match('/^(.*)<em>(.*?)<\/em>/is', $html, $m)) {
        return [
            'main' => trim(strip_tags($m[1])),
            'highlight' => trim(strip_tags($m[2])),
        ];
    }

    return ['main' => trim(strip_tags($html)), 'highlight' => ''];
}

function cms_build_about_lead_html(string $paragraph1, string $paragraph2 = ''): string
{
    $out = '';
    foreach ([$paragraph1, $paragraph2] as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out .= '<p class="section-lead">' . cms_escape($p) . '</p>';
        }
    }

    return $out;
}

function cms_parse_about_lead_html(string $html): array
{
    $paragraphs = [];
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $m)) {
        foreach ($m[1] as $inner) {
            $paragraphs[] = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
    if (!$paragraphs && trim($html) !== '') {
        $paragraphs[] = trim(strip_tags($html));
    }

    return [
        'paragraph1' => $paragraphs[0] ?? '',
        'paragraph2' => $paragraphs[1] ?? '',
    ];
}

function cms_build_studio_values_html(array $cards): string
{
    $html = '';
    foreach ($cards as $card) {
        $title = trim((string) ($card['title'] ?? ''));
        $text = trim((string) ($card['text'] ?? ''));
        if ($title === '' && $text === '') {
            continue;
        }
        $html .= '<div class="value-card">';
        if ($title !== '') {
            $html .= '<h3>' . cms_escape($title) . '</h3>';
        }
        if ($text !== '') {
            $html .= '<p>' . cms_escape($text) . '</p>';
        }
        $html .= '</div>';
    }

    return $html;
}

function cms_parse_studio_values_html(string $html): array
{
    $cards = [];
    if (preg_match_all('/<div[^>]*class="[^"]*value-card[^"]*"[^>]*>(.*?)<\/div>/is', $html, $blocks)) {
        foreach ($blocks[1] as $block) {
            $title = '';
            $text = '';
            if (preg_match('/<h3[^>]*>(.*?)<\/h3>/is', $block, $t)) {
                $title = trim(strip_tags($t[1]));
            }
            if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $block, $p)) {
                $text = trim(strip_tags($p[1]));
            }
            $cards[] = ['title' => $title, 'text' => $text];
        }
    }
    while (count($cards) < 3) {
        $cards[] = ['title' => '', 'text' => ''];
    }

    return array_slice($cards, 0, 3);
}

function cms_studio_values_html_from_settings(array $settings): string
{
    $cards = [];
    for ($i = 1; $i <= 3; $i++) {
        $title = trim((string) ($settings['studio_value_' . $i . '_title'] ?? ''));
        $text = trim((string) ($settings['studio_value_' . $i . '_text'] ?? ''));
        if ($title !== '' || $text !== '') {
            $cards[] = ['title' => $title, 'text' => $text];
        }
    }
    if ($cards) {
        return cms_build_studio_values_html($cards);
    }

    return trim((string) ($settings['studio_values_html'] ?? ''));
}
