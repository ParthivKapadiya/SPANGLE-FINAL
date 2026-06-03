<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/storage.php';

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['spangle_admin'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$data = spangle_read_enquiries();
$subs = $data['submissions'];
if (!is_array($subs)) {
    $subs = [];
}

// Newest first for the admin UI
$subs = array_reverse($subs);

echo json_encode(['ok' => true, 'submissions' => $subs], JSON_UNESCAPED_UNICODE);
