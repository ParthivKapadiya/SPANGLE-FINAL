<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

try {
    $pdo = Database::connection($configDb);
    cms_run_migrations($pdo);
    $result = ProjectRepository::list($pdo, $_GET);
    echo json_encode([
        'ok' => true,
        'data' => $result['items'],
        'meta' => $result['meta'],
        'filters' => [
            'types' => ProjectRepository::TYPES,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'unavailable']);
}
