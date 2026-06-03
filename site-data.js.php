<?php

declare(strict_types=1);

/**
 * Embeds live MySQL content for the public site (used by content-bridge.js).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $pdo = Database::connection($configDb);
    $payload = SiteContent::build($pdo);
    echo 'window.__SPANGLE_SITE__ = ' . json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) . ';';
} catch (Throwable $e) {
    http_response_code(503);
    echo 'window.__SPANGLE_SITE__ = null;';
}
