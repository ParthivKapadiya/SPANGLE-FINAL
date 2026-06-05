<?php

declare(strict_types=1);

/** Clear heroSrcset / heroSizes in exported JSON (originals only). */

$root = dirname(__DIR__);
$keys = ['heroSrcset', 'heroSizes'];

foreach (['content/site.json', 'site.json'] as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) {
        continue;
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        continue;
    }
    $n = 0;
    $walk = static function (&$node) use (&$walk, $keys, &$n): void {
        if (!is_array($node)) {
            return;
        }
        foreach ($node as $k => &$v) {
            if (in_array($k, $keys, true) && is_string($v) && $v !== '') {
                $v = '';
                $n++;
            } else {
                $walk($v);
            }
        }
    };
    $walk($data);
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    echo "{$rel}: cleared {$n} srcset field(s).\n";
}
