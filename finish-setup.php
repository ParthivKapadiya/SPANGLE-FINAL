<?php

declare(strict_types=1);

/**
 * One-time InfinityFree setup. Upload to site root, open in browser, DELETE when done.
 */
$configPath = __DIR__ . '/config/database.php';
$lockPath = __DIR__ . '/config/.installed';

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Finish setup</title>';
echo '<style>body{font-family:system-ui;max-width:640px;margin:2rem auto;padding:0 1rem}.ok{color:#080}.err{color:#b00}code{background:#f4f4f4;padding:2px 6px}</style></head><body>';
echo '<h1>Finish database setup</h1>';

if (!is_file($configPath)) {
    echo '<p class="err">Missing <code>config/database.php</code>. Create it on the server (not database.example.php).</p></body></html>';
    exit;
}

$config = require $configPath;
$localPath = __DIR__ . '/config/database.local.php';
if (is_file($localPath)) {
    echo '<p class="err"><strong>Delete <code>config/database.local.php</code></strong> on the server — it may override your database name.</p>';
    $config = array_merge($config, require $localPath);
}

$dbName = (string) ($config['database'] ?? '');
echo '<p>Reading <code>config/database.php</code>:<br>database = <strong>' . htmlspecialchars($dbName) . '</strong></p>';

if ($dbName === 'spangle_studio' || $dbName === '') {
    echo '<p class="err">Wrong database name. On InfinityFree use <code>if0_42093866_archevoinfra</code>, not <code>spangle_studio</code>.</p></body></html>';
    exit;
}

if (is_file($lockPath) && !isset($_GET['force'])) {
    echo '<p class="ok">Already installed. <a href="index.html">Open site</a> · <a href="admin/login.php">Admin</a></p>';
    echo '<p>Re-run anyway: <a href="finish-setup.php?force=1">finish-setup.php?force=1</a></p></body></html>';
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
    echo '<p class="ok">MySQL connected.</p>';

    if (!defined('SPANGLE_ROOT')) {
        define('SPANGLE_ROOT', __DIR__);
    }
    require_once __DIR__ . '/includes/installSchema.php';
    foreach (install_schema_statements() as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                continue;
            }
            throw $e;
        }
    }
    echo '<p class="ok">Tables created (or already exist).</p>';

    $GLOBALS['configDb'] = $config;
    $GLOBALS['installSeedPdo'] = $pdo;

    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/includes/cmsMigrate.php';
    cms_run_migrations($pdo);
    echo '<p class="ok">CMS migrations done.</p>';

    require __DIR__ . '/database/seed.php';
    echo '<p class="ok">Seed data loaded.</p>';
    unset($GLOBALS['installSeedPdo']);

    if (!is_dir(__DIR__ . '/config')) {
        mkdir(__DIR__ . '/config', 0775, true);
    }
    file_put_contents($lockPath, date('c') . "\n");

    echo '<p class="ok"><strong>Setup complete.</strong></p>';
    echo '<ul><li>Admin: <a href="admin/login.php">admin/login.php</a> — user <code>admin</code> / <code>admin123</code></li>';
    echo '<li>Test API: <a href="api/site-data.js.php">api/site-data.js.php</a></li></ul>';
    echo '<p class="err"><strong>Delete this file now:</strong> <code>finish-setup.php</code> and <code>install.php</code></p>';
} catch (Throwable $e) {
    echo '<p class="err">' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</body></html>';
