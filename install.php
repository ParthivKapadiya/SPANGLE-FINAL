<?php

declare(strict_types=1);

/**
 * One-time installer for XAMPP / PHP shared hosting.
 * Delete this file after successful setup on production.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$messages = [];
$errors = [];

$configPath = SPANGLE_ROOT . '/config/database.php';
$lockPath = SPANGLE_ROOT . '/config/.installed';

function install_config_dir(): string
{
    return SPANGLE_ROOT . '/config';
}

/** On XAMPP, Apache runs as "daemon" — ensure config/ and uploads/ are writable. */
function install_prepare_permissions(): void
{
    if (!app_is_local()) {
        return;
    }

    $paths = [
        install_config_dir(),
        SPANGLE_ROOT . '/uploads',
    ];
    foreach ($paths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        if (is_dir($path) && !is_writable($path)) {
            @chmod($path, 0777);
            clearstatcache(true, $path);
        }
    }

    $dbFile = install_config_dir() . '/database.php';
    if (is_file($dbFile) && !is_writable($dbFile)) {
        @chmod($dbFile, 0666);
        clearstatcache(true, $dbFile);
    }
}

function install_ensure_config_writable(): void
{
    $dir = install_config_dir();
    $path = $dir . '/database.php';

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Could not create config/ directory.');
    }

    if (!is_writable($dir)) {
        @chmod($dir, app_is_local() ? 0777 : 0775);
        clearstatcache(true, $dir);
    }
    if (is_file($path) && !is_writable($path)) {
        @chmod($path, app_is_local() ? 0666 : 0644);
        clearstatcache(true, $path);
    }

    if (is_writable($dir)) {
        return;
    }

    $cmd = 'chmod 777 ' . escapeshellarg($dir);
    throw new RuntimeException(
        'Cannot write config/database.php — the config/ folder is not writable by Apache (XAMPP runs as user "daemon").'
        . ' Run this in Terminal, then click Install again:'
        . ' ' . $cmd
    );
}

function install_write_config(string $path, string $contents): void
{
    install_ensure_config_writable();

    $dir = dirname($path);
    $tmp = $dir . '/.database.tmp.' . getmypid();
    if (file_put_contents($tmp, $contents) === false) {
        throw new RuntimeException('Failed to write temporary config file.');
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Failed to write config/database.php (permission denied).');
        }
    }
    @chmod($path, app_is_local() ? 0666 : 0640);
}

function install_mark_complete(): void
{
    global $lockPath;
    install_ensure_config_writable();
    if (file_put_contents($lockPath, date('c') . "\n") === false) {
        throw new RuntimeException('Could not write config/.installed');
    }
    @chmod($lockPath, app_is_local() ? 0666 : 0640);
}

/**
 * Database + config already exist but .installed lock is missing — finish without rewriting config.
 */
function install_finish_existing(): bool
{
    global $configPath, $lockPath, $messages;

    if (!is_file($configPath) || is_file($lockPath)) {
        return false;
    }

    try {
        $existing = require $configPath;
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $existing['host'],
                $existing['port'] ?? '3306',
                $existing['database']
            ),
            $existing['username'],
            $existing['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->query('SELECT 1 FROM admins LIMIT 1');
        require SPANGLE_ROOT . '/database/seed.php';
        ensure_upload_directories();
        install_mark_complete();
        $messages[] = 'Setup completed using your existing <code>config/database.php</code>.';
        $messages[] = 'Database is ready. Admin panel will be added separately.';

        return true;
    } catch (Throwable $e) {
        global $errors;
        $errors[] = app_is_local()
            ? ('Could not complete setup: ' . $e->getMessage())
            : 'Could not complete setup. Check database.php credentials and MySQL.';

        return false;
    }
}

install_prepare_permissions();
$configWritable = is_dir(install_config_dir()) && is_writable(install_config_dir());

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['finish'])) {
    install_finish_existing();
}

$alreadyInstalled = is_file($lockPath) && is_file($configPath);
if ($alreadyInstalled && !$messages) {
    try {
        $existing = require $configPath;
        $pdoCheck = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $existing['host'],
                $existing['port'] ?? '3306',
                $existing['database']
            ),
            $existing['username'],
            $existing['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdoCheck->query('SELECT 1 FROM admins LIMIT 1');
        $messages[] = 'Already installed. Open the public site at <a href="index.html">index.html</a>.';
        $messages[] = 'Delete <code>install.php</code> on production when you no longer need it.';
    } catch (Throwable $e) {
        $alreadyInstalled = false;
    }
}

if (app_is_production() && $alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors[] = 'Installer is locked on production. Delete config/.installed only if you must reinstall.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$messages && !$errors) {
    try {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? 'spangle_studio');
        $user = trim($_POST['db_user'] ?? 'root');
        $pass = (string) ($_POST['db_pass'] ?? '');

        $pdoRoot = new PDO(
            "mysql:host=$host;port=$port;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdoRoot->exec("USE `$name`");

        $schema = file_get_contents(SPANGLE_ROOT . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Could not read schema.sql');
        }
        $parts = preg_split('/;\s*\n/', $schema);
        foreach ($parts as $sql) {
            $sql = trim($sql);
            if ($sql === '' || stripos($sql, 'CREATE DATABASE') === 0 || stripos($sql, 'USE ') === 0) {
                continue;
            }
            $pdoRoot->exec($sql);
        }

        $configPhp = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n"
            . "    'host' => " . var_export($host, true) . ",\n"
            . "    'port' => " . var_export($port, true) . ",\n"
            . "    'database' => " . var_export($name, true) . ",\n"
            . "    'username' => " . var_export($user, true) . ",\n"
            . "    'password' => " . var_export($pass, true) . ",\n"
            . "    'charset' => 'utf8mb4',\n];\n";
        install_write_config($configPath, $configPhp);
        install_mark_complete();

        $configDb = require SPANGLE_ROOT . '/config/database.php';
        require SPANGLE_ROOT . '/database/seed.php';
        ensure_upload_directories();

        $messages[] = 'Database installed and seeded successfully.';
        if (app_is_local()) {
            $messages[] = 'Default DB admin user: <strong>admin</strong> / <strong>admin123</strong> (for the new admin panel when built).';
        }
        $messages[] = 'Delete <code>install.php</code> when done.';
    } catch (Throwable $e) {
        $errors[] = app_is_local() ? $e->getMessage() : 'Installation failed. Check database credentials and folder permissions.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Archevo Design — Install</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 520px; margin: 3rem auto; padding: 0 1rem; }
    label { display: block; margin: 0.75rem 0 0.25rem; font-size: 0.9rem; }
    input { width: 100%; padding: 0.5rem; box-sizing: border-box; }
    button { margin-top: 1.25rem; padding: 0.65rem 1.25rem; cursor: pointer; }
    .ok { color: #0a6; } .err { color: #b00; }
  </style>
</head>
<body>
  <h1>Archevo Design installer</h1>
  <p>Creates MySQL database, tables, seed data, and <code>config/database.php</code>.</p>
  <?php foreach ($errors as $err): ?>
    <p class="err"><?= htmlspecialchars($err) ?></p>
  <?php endforeach; ?>
  <?php foreach ($messages as $msg): ?>
    <p class="ok"><?= $msg ?></p>
  <?php endforeach; ?>
  <?php if (!$messages && !$alreadyInstalled): ?>
  <?php if (!$configWritable): ?>
    <p class="err"><strong>config/ is not writable by Apache.</strong> Run in Terminal:<br />
      <code style="display:block;margin:0.5rem 0;padding:0.5rem;background:#f5f5f5;">chmod 777 <?= htmlspecialchars(install_config_dir()) ?></code>
      Then reload this page and install again.</p>
  <?php endif; ?>
  <?php if (is_file($configPath) && !is_file($lockPath)): ?>
    <p class="err">Found <code>config/database.php</code> but install is incomplete.</p>
    <p><a href="install.php?finish=1" style="display:inline-block;padding:0.5rem 1rem;background:#0a6;color:#fff;text-decoration:none;border-radius:4px;">Complete setup now</a> (uses existing DB config, no rewrite)</p>
    <p style="font-size:0.85rem;color:#666;">Or submit the form below to update credentials and re-run install.</p>
  <?php endif; ?>
  <form method="post">
    <label>MySQL host</label>
    <input name="db_host" value="<?= htmlspecialchars(app_is_local() ? '127.0.0.1' : 'localhost') ?>" required />
    <label>Port</label>
    <input name="db_port" value="3306" required />
    <label>Database name</label>
    <input name="db_name" value="spangle_studio" required />
    <label>Username</label>
    <input name="db_user" value="root" required />
    <label>Password</label>
    <input name="db_pass" type="password" value="" autocomplete="off" placeholder="<?= app_is_local() ? 'empty on Mac, or root on Windows' : '' ?>" />
    <button type="submit">Install now</button>
  </form>
  <p style="font-size:0.85rem;color:#666;">XAMPP: user <strong>root</strong>, password usually <strong>empty</strong> (Mac) or <strong>root</strong> (Windows). Start MySQL first. Override anytime with <code>config/database.local.php</code>.</p>
  <?php elseif ($alreadyInstalled && $messages): ?>
  <p style="font-size:0.85rem;color:#666;">To reinstall: delete <code>config/.installed</code> and <code>config/database.php</code>, then reload this page.</p>
  <?php endif; ?>
</body>
</html>
