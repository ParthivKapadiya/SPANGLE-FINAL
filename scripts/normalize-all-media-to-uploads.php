<?php

declare(strict_types=1);

/**
 * Move stray images into uploads/ and normalize all DB + JSON media paths.
 * Run: php scripts/normalize-all-media-to-uploads.php
 */

$root = dirname(__DIR__);
require_once $root . '/includes/helpers.php';

$imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
$moved = 0;

foreach (scandir($root) ?: [] as $name) {
    if ($name === '.' || $name === '..') {
        continue;
    }
    $abs = $root . '/' . $name;
    if (!is_file($abs)) {
        continue;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $imageExt, true)) {
        continue;
    }
    $destDir = $root . '/uploads/general';
    if (!is_dir($destDir) || !is_writable($destDir)) {
        $destDir = $root . '/uploads';
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }
    $dest = $destDir . '/' . $name;
    if (is_file($dest)) {
        @unlink($abs);
        echo "Removed duplicate root file (already in uploads): {$name}\n";
        $moved++;
        continue;
    }
    if (@rename($abs, $dest) || @copy($abs, $dest)) {
        @unlink($abs);
        echo "Moved {$name} -> uploads/general/\n";
        $moved++;
    }
}

function normalize_media_value(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return normalize_upload_relative_path($path);
}

function fix_json_file(string $file): int
{
    if (!is_file($file)) {
        return 0;
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        return 0;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return 0;
    }

    $changed = 0;
    $walker = static function (&$node) use (&$walker, &$changed): void {
        if (is_array($node)) {
            foreach ($node as $k => &$v) {
                if (is_string($v) && preg_match('/\.(png|jpe?g|webp|gif|avif)$/i', $v)) {
                    $next = normalize_media_value($v);
                    if ($next !== $v) {
                        $v = $next;
                        $changed++;
                    }
                } else {
                    $walker($v);
                }
            }
            unset($v);
        }
    };
    $walker($data);

    if ($changed > 0) {
        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );
        echo "Fixed {$changed} path(s) in " . basename($file) . "\n";
    }

    return $changed;
}

fix_json_file($root . '/content/site.json');
fix_json_file($root . '/site.json');

$dbUpdated = 0;
if (is_file($root . '/config/database.php')) {
    require_once $root . '/includes/bootstrap.php';
    try {
        $pdo = Database::connection(require $root . '/config/database.php');

        $settings = [
            'site_logo', 'site_logo_light', 'site_logo_dark', 'site_favicon',
            'home_about_image', 'seo_og_image', 'studio_hero_image', 'services_hero_image',
        ];
        $stmt = $pdo->prepare(
            'UPDATE site_settings SET setting_value = ? WHERE setting_key = ? AND setting_value != ?'
        );
        foreach ($settings as $key) {
            $row = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
            $row->execute([$key]);
            $val = (string) ($row->fetchColumn() ?: '');
            if ($val === '') {
                continue;
            }
            $next = normalize_media_value($val);
            if ($next !== $val) {
                $stmt->execute([$next, $key, $next]);
                $dbUpdated++;
                echo "DB setting {$key}: {$val} -> {$next}\n";
            }
        }

        foreach (['projects' => 'hero_image', 'hero_slides' => 'image_path', 'gallery_items' => 'image_path'] as $table => $col) {
            $rows = $pdo->query("SELECT id, {$col} FROM {$table} WHERE {$col} IS NOT NULL AND {$col} != ''")->fetchAll();
            $upd = $pdo->prepare("UPDATE {$table} SET {$col} = ? WHERE id = ?");
            foreach ($rows as $r) {
                $val = (string) $r[$col];
                $next = normalize_media_value($val);
                if ($next !== $val) {
                    $upd->execute([$next, $r['id']]);
                    $dbUpdated++;
                }
            }
            echo "Normalized {$table}.{$col}\n";
        }

        if (function_exists('content_sync_site_json')) {
            content_sync_site_json($pdo);
            echo "Regenerated content/site.json from database.\n";
        }
    } catch (Throwable $e) {
        echo 'Database skip: ' . $e->getMessage() . "\n";
    }
}

echo "\nDone. Moved {$moved} root file(s). DB field updates: {$dbUpdated}.\n";
