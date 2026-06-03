<?php

declare(strict_types=1);

/**
 * Public JSON API — same structure as content/site.json for content-bridge.js
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $pdo = Database::connection($configDb);
    $payload = SiteContent::build($pdo);
    $payload['ok'] = true;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'database_unavailable',
        'message' => 'Content API unavailable. Ensure MySQL is running and install.php was completed.',
    ]);
}
