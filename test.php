<?php

declare(strict_types=1);

/**
 * Local diagnostics only — remove or block on production hosting.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!app_is_local()) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
echo "PHP OK\n";
echo 'Environment: ' . app_env() . "\n";
echo 'Base URL: ' . site_base_url() . "\n";

try {
    $pdo = Database::connection($configDb);
    $pdo->query('SELECT 1');
    echo "Database: connected\n";
} catch (Throwable $e) {
    echo "Database: error\n";
}
