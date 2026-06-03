<?php

declare(strict_types=1);

/**
 * Optional override: copy config.local.example.php to config.local.php
 * and set admin_password to a strong secret (recommended for production).
 */
function spangle_admin_password(): string
{
    $local = __DIR__ . '/config.local.php';
    if (is_file($local)) {
        $cfg = require $local;
        if (is_array($cfg) && !empty($cfg['admin_password']) && is_string($cfg['admin_password'])) {
            return $cfg['admin_password'];
        }
    }

    $sitePath = dirname(__DIR__) . '/content/site.json';
    if (is_file($sitePath)) {
        $raw = file_get_contents($sitePath);
        if ($raw !== false) {
            $j = json_decode($raw, true);
            if (is_array($j) && !empty($j['adminAccessKey']) && is_string($j['adminAccessKey'])) {
                return $j['adminAccessKey'];
            }
        }
    }

    return '';
}

function spangle_enquiries_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'enquiries.json';
}
