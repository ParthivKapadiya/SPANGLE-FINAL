<?php

declare(strict_types=1);

$base = 'http://localhost/SPANGLE_FINAL';
$pages = [
    'index.html', 'studio.html', 'services.html', 'work.html', 'process.html', 'contact.html',
    'privacy.html', 'terms.html', 'thanks.html', 'developer.html', 'credits.html',
    'project-courtyard-villa.html', 'sitemap.php', 'robots.txt',
];

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => true,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [$code, is_string($raw) ? substr($raw, $headerSize) : ''];
}

function headStatus(string $url): int
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code;
}

$allImages = [];
$brokenPages = [];
$brokenImages = [];
$internalLinks = [];

foreach ($pages as $page) {
    [$code, $html] = fetch("$base/$page");
    if ($code >= 400 || $html === '') {
        $brokenPages[] = "$code $page";
        continue;
    }

    preg_match_all('/(?:src|poster)=["\']([^"\']+)["\']/i', $html, $srcMatches);
    preg_match_all('/url\([\'"]?([^\'"\)]+)[\'"]?\)/i', $html, $bgMatches);
    preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $hrefMatches);

    foreach (array_merge($srcMatches[1] ?? [], $bgMatches[1] ?? []) as $u) {
        $u = trim(html_entity_decode($u));
        if ($u === '' || str_starts_with($u, 'data:') || str_starts_with($u, '#')) {
            continue;
        }
        if (str_starts_with($u, 'http') && !str_contains($u, 'localhost')) {
            continue;
        }
        $allImages[$u][] = $page;
    }

    foreach ($hrefMatches[1] ?? [] as $href) {
        $href = trim(html_entity_decode($href));
        if ($href === '' || $href[0] === '#' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            continue;
        }
        if (str_starts_with($href, 'http') && !str_contains($href, 'localhost')) {
            continue;
        }
        $internalLinks[$href][] = $page;
    }
}

[$jsonCode, $jsonRaw] = fetch("$base/api/public-content.php");
$data = json_decode($jsonRaw, true) ?: [];

$walk = function ($v, string $path) use (&$walk, &$allImages): void {
    if (is_string($v)) {
        if (preg_match('/^(uploads\/|\.\/uploads\/)/', $v) || preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $v)) {
            $allImages[$v][] = "cms:$path";
        }

        return;
    }
    if (is_array($v)) {
        foreach ($v as $k => $item) {
            $walk($item, "$path.$k");
        }
    }
};
$walk($data, 'cms');

$checked = [];
foreach ($allImages as $path => $sources) {
    $norm = str_replace('%20', ' ', $path);
    if (isset($checked[$norm])) {
        continue;
    }
    $checked[$norm] = true;

    if (str_starts_with($norm, 'http')) {
        $url = $norm;
    } else {
        $url = $base . '/' . ltrim($norm, '/');
    }
    $url = str_replace(' ', '%20', $url);
    $imgCode = headStatus($url);
    if ($imgCode >= 400) {
        $brokenImages[] = [
            'code' => $imgCode,
            'path' => $norm,
            'sources' => array_values(array_unique($sources)),
        ];
    }
}

$brokenLinkFails = [];
$testedLinks = [];
foreach ($internalLinks as $href => $sources) {
    if (isset($testedLinks[$href])) {
        continue;
    }
    $testedLinks[$href] = true;
    if (str_starts_with($href, 'http')) {
        continue;
    }
    $url = $base . '/' . ltrim($href, '/');
    [$lc] = fetch($url);
    if ($lc >= 400) {
        $brokenLinkFails[] = ['code' => $lc, 'href' => $href, 'from' => array_values(array_unique($sources))];
    }
}

$slug = $data['projects'][0]['slug'] ?? '';
if ($slug !== '') {
    [$pc] = fetch("$base/project.php?slug=" . rawurlencode($slug));
    if ($pc >= 400) {
        $brokenPages[] = "$pc project.php?slug=$slug";
    }
}

// Sample more project images from CMS
$projectImageFails = 0;
$projectImageChecked = 0;
foreach (array_slice($data['projects'] ?? [], 0, 30) as $p) {
    $img = $p['heroImage'] ?? '';
    if ($img === '') {
        continue;
    }
    $projectImageChecked++;
    $url = str_starts_with($img, 'http') ? $img : $base . '/' . ltrim($img, '/');
    if (headStatus($url) >= 400) {
        $projectImageFails++;
        if ($projectImageFails <= 10) {
            $brokenImages[] = ['code' => 404, 'path' => $img, 'sources' => ['project:' . ($p['slug'] ?? '?')]];
        }
    }
}

echo "=== PAGE ERRORS ===\n";
echo $brokenPages === [] ? "None\n" : implode("\n", $brokenPages) . "\n";

echo "\n=== BROKEN IMAGES (" . count($brokenImages) . ") ===\n";
foreach (array_slice($brokenImages, 0, 50) as $bi) {
    echo $bi['code'] . ' ' . $bi['path'] . ' (from: ' . implode(', ', array_slice($bi['sources'], 0, 2)) . ")\n";
}
if (count($brokenImages) > 50) {
    echo '... and ' . (count($brokenImages) - 50) . " more\n";
}

echo "\n=== BROKEN INTERNAL LINKS (" . count($brokenLinkFails) . ") ===\n";
foreach ($brokenLinkFails as $bl) {
    echo $bl['code'] . ' ' . $bl['href'] . ' (from: ' . implode(', ', array_slice($bl['from'], 0, 2)) . ")\n";
}

echo "\n=== CMS DATA ISSUES ===\n";
echo 'brandName: ' . ($data['branding']['brandName'] ?? '?') . "\n";
echo 'brandLine: ' . ($data['branding']['brandLine'] ?? '?') . "\n";
echo 'projects: ' . count($data['projects'] ?? []) . "\n";
if (!empty($data['copy']['home_link_journal_url'])) {
    echo 'stale journal link in copy: ' . $data['copy']['home_link_journal_url'] . "\n";
}
if (!empty($data['journalPosts'])) {
    echo 'stale journalPosts in API: ' . count($data['journalPosts']) . " entries\n";
}

// Gallery + hero slides + all project heroes
$galleryBad = 0;
$galleryTotal = 0;
foreach (array_merge($data['gallery'] ?? [], $data['homeGallery'] ?? []) as $g) {
    $src = $g['src'] ?? '';
    if ($src === '') {
        continue;
    }
    $galleryTotal++;
    $url = str_starts_with($src, 'http') ? $src : $base . '/' . str_replace(' ', '%20', ltrim($src, '/'));
    if (headStatus($url) >= 400) {
        $galleryBad++;
    }
}

$heroBad = 0;
$heroTotal = 0;
foreach ($data['heroSlides'] ?? [] as $h) {
    $src = $h['src'] ?? '';
    if ($src === '') {
        continue;
    }
    $heroTotal++;
    $url = str_starts_with($src, 'http') ? $src : $base . '/' . str_replace(' ', '%20', ltrim($src, '/'));
    if (headStatus($url) >= 400) {
        $heroBad++;
    }
}

$allProjectBad = 0;
foreach ($data['projects'] ?? [] as $p) {
    $img = $p['heroImage'] ?? '';
    if ($img === '') {
        $allProjectBad++;
        continue;
    }
    $url = str_starts_with($img, 'http') ? $img : $base . '/' . str_replace(' ', '%20', ltrim($img, '/'));
    if (headStatus($url) >= 400) {
        $allProjectBad++;
    }
}

$assetBad = [];
$html = file_get_contents("$base/index.html") ?: '';
preg_match_all('/(?:href|src)=["\']([^"\']+\.(?:css|js|woff2))["\']/i', $html, $assetMatches);
foreach (array_unique($assetMatches[1] ?? []) as $asset) {
    if (str_starts_with($asset, 'http')) {
        continue;
    }
    if (headStatus($base . '/' . ltrim($asset, '/')) >= 400) {
        $assetBad[] = $asset;
    }
}

echo "\n=== MEDIA SUMMARY ===\n";
echo "Gallery broken: $galleryBad / $galleryTotal\n";
echo "Hero slides broken: $heroBad / $heroTotal\n";
echo 'All project heroes broken/missing: ' . $allProjectBad . ' / ' . count($data['projects'] ?? []) . "\n";
echo 'Index CSS/JS/font assets broken: ' . count($assetBad) . "\n";
foreach ($assetBad as $a) {
    echo "  $a\n";
}

echo "\n=== SUMMARY ===\n";
echo 'Pages checked: ' . count($pages) . "\n";
echo 'Unique images checked: ' . count($checked) . "\n";
echo 'Project hero images sampled: ' . $projectImageChecked . "\n";
