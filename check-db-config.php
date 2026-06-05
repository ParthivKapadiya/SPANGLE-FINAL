<?php

declare(strict_types=1);

/**
 * One-time check: upload to site root, open in browser, then DELETE.
 */
header('Content-Type: text/plain; charset=UTF-8');

$root = __DIR__;
$configPath = $root . '/config/database.php';

echo "SPANGLE DB config check\n\n";

if (!is_file($configPath)) {
    echo "MISSING: config/database.php\n";
    exit;
}

$config = require $configPath;
$localPath = $root . '/config/database.local.php';
if (is_file($localPath)) {
    echo "WARNING: config/database.local.php exists and may override database.php\n";
    $config = array_merge($config, require $localPath);
}

echo 'host: ' . ($config['host'] ?? '') . "\n";
echo 'database: ' . ($config['database'] ?? '') . "\n";
echo 'username: ' . ($config['username'] ?? '') . "\n";

if (($config['database'] ?? '') === 'spangle_studio') {
    echo "\nERROR: database is still spangle_studio (XAMPP). Change to if0_42093866_archevoinfra on the SERVER.\n";
    exit;
}

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? '3306',
            $config['database']
        ),
        $config['username'],
        $config['password'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->query('SELECT 1');
    echo "\nOK: Connected to MySQL successfully.\n";
} catch (Throwable $e) {
    echo "\nFAIL: " . $e->getMessage() . "\n";
}
