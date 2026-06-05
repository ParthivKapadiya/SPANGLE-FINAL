<?php

declare(strict_types=1);

/**
 * Upload diagnostics for InfinityFree (missing /uploads 404s).
 * Open: https://your-domain/check-uploads.php
 * Delete this file after fixing uploads.
 */
header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$uploadsDir = $root . '/uploads';

echo "=== Archevo uploads check ===\n\n";
echo 'Time: ' . gmdate('Y-m-d H:i:s') . " UTC\n";
echo 'Script folder (__DIR__): ' . $root . "\n";
echo 'DOCUMENT_ROOT: ' . ($_SERVER['DOCUMENT_ROOT'] ?? '(unknown)') . "\n";
echo 'HTTP Host: ' . ($_SERVER['HTTP_HOST'] ?? '') . "\n\n";

echo "uploads/ folder exists: " . (is_dir($uploadsDir) ? "YES\n" : "NO — create htdocs/uploads/\n");

if (!is_dir($uploadsDir)) {
    exit(0);
}

$all = scandir($uploadsDir) ?: [];
$fileNames = array_values(array_filter($all, static function (string $name): bool {
    return $name !== '.' && $name !== '..' && !is_dir(__DIR__ . '/uploads/' . $name);
}));
$imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
$images = array_values(array_filter($fileNames, static function (string $name) use ($imageExt): bool {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    return in_array($ext, $imageExt, true);
}));

echo 'Total files in uploads/: ' . count($fileNames) . "\n";
echo 'Image files (jpg/png/…): ' . count($images) . "\n";
echo "Expected on Mac after optimize: ~426 images\n\n";

$mustExist = [
    'ENTRY.jpg',
    'ENTRY-640w.jpg',
    '054-KANTILAL-3D-6.jpg',
    '054-KANTILAL-3D-6-640w.jpg',
];

echo "Required sample files:\n";
foreach ($mustExist as $name) {
    $path = $uploadsDir . '/' . $name;
    if (is_file($path)) {
        echo "  OK   $name (" . filesize($path) . " bytes)\n";
    } else {
        echo "  MISS $name\n";
    }
}

echo "\nFirst 15 images (alphabetical):\n";
sort($images);
foreach (array_slice($images, 0, 15) as $name) {
    echo '  ' . $name . "\n";
}

if (is_dir($uploadsDir . '/uploads')) {
    echo "\nWARNING: Found htdocs/uploads/uploads/ (nested folder).\n";
    echo "Files must be in htdocs/uploads/NAME.jpg not htdocs/uploads/uploads/NAME.jpg\n";
}

echo "\nIf MISS lines appear but you uploaded the folder, the files are on the wrong FTP path\n";
echo "or the upload did not finish (use FTP, wait for 100% queue, refresh File Manager).\n";
echo "Delete check-uploads.php when done.\n";
