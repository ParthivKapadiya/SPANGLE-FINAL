<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);

    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$token = (string) ($payload['csrf_token'] ?? '');
if (!csrf_verify_from_value($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf']);

    exit;
}

$email = trim((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);

    exit;
}

if (Auth::isLoginLocked()) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'locked']);

    exit;
}

try {
    $pdo = Database::connection($configDb);
    Auth::configureSession();
    Auth::startSession();
    if (!Auth::login($pdo, $email, $password)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'invalid_credentials']);

        exit;
    }
    echo json_encode(['ok' => true, 'redirect' => 'admin/index.php']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'server']);
}
