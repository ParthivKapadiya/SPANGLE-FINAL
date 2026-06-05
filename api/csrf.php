<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

Auth::configureSession();
Auth::startSession();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => true, 'token' => csrf_token()]);
