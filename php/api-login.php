<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

$raw = file_get_contents('php://input');
$body = is_string($raw) ? json_decode($raw, true) : null;
$password = '';
if (is_array($body) && isset($body['password']) && is_string($body['password'])) {
    $password = $body['password'];
}

$expected = spangle_admin_password();
if ($expected === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'not_configured']);
    exit;
}

if (!hash_equals($expected, $password)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

$_SESSION['spangle_admin'] = true;
echo json_encode(['ok' => true]);
