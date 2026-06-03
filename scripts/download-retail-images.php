<?php

declare(strict_types=1);

/**
 * Download retail showcase hero images into uploads/ and point projects at local files.
 * Run: php scripts/download-retail-images.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);

$retailDir = SPANGLE_ROOT . '/uploads/retail';
if (!is_dir($retailDir)) {
    mkdir($retailDir, 0755, true);
}

$images = [
    'retail-jewellery-showcase' => [
        'file' => 'retail-jewellery-showroom.jpg',
        'url' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1400&q=85',
    ],
    'retail-fashion-boutique' => [
        'file' => 'retail-fashion-boutique.jpg',
        'url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1400&q=85',
    ],
    'retail-furniture-showroom' => [
        'file' => 'retail-furniture-showroom.jpg',
        'url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=1400&q=85',
    ],
    'retail-cafe-lounge' => [
        'file' => 'retail-cafe-lounge.jpg',
        'url' => 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1400&q=85',
    ],
    'retail-electronics-store' => [
        'file' => 'retail-electronics-store.jpg',
        'url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=85',
    ],
    'retail-cosmetics-counter' => [
        'file' => 'retail-cosmetics-counter.jpg',
        'url' => 'https://images.unsplash.com/photo-1600585152915-d208bec867a1?auto=format&fit=crop&w=1400&q=85',
    ],
];

function download_image(string $url, string $dest): bool
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 60,
            'header' => "User-Agent: ArchevoDesign/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 1024) {
        return false;
    }

    return file_put_contents($dest, $data) !== false;
}

$update = $pdo->prepare('UPDATE projects SET hero_image = ? WHERE slug = ?');
$downloaded = 0;

foreach ($images as $slug => $meta) {
    $abs = $retailDir . '/' . $meta['file'];
    $rel = 'uploads/retail/' . $meta['file'];

    if (!download_image($meta['url'], $abs)) {
        echo "FAILED: $slug\n";
        continue;
    }

    $update->execute([$rel, $slug]);
    echo "OK: $rel (" . round(filesize($abs) / 1024) . " KB)\n";
    $downloaded++;
}

if ($downloaded === count($images)) {
    content_sync_site_json($pdo);
}

echo "Downloaded $downloaded / " . count($images) . " retail images to uploads/retail/\n";
