<?php

declare(strict_types=1);

if (ob_get_level() === 0) {
    ob_start();
}

$root = dirname(__DIR__);

if (!defined('SPANGLE_ROOT')) {
    define('SPANGLE_ROOT', $root);
}

date_default_timezone_set('Asia/Kolkata');

$configApp = require SPANGLE_ROOT . '/config/app.php';
$dbConfigPath = SPANGLE_ROOT . '/config/database.php';

if (!is_file($dbConfigPath)) {
    $dbConfigPath = SPANGLE_ROOT . '/config/database.example.php';
}

$configDb = require $dbConfigPath;
$localDbPath = SPANGLE_ROOT . '/config/database.local.php';
if (is_file($localDbPath)) {
    $configDb = array_merge($configDb, require $localDbPath);
}

$GLOBALS['configApp'] = $configApp;
$GLOBALS['configDb'] = $configDb;

require_once SPANGLE_ROOT . '/includes/helpers.php';

$appEnv = app_env();
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require_once SPANGLE_ROOT . '/includes/Database.php';
require_once SPANGLE_ROOT . '/includes/Auth.php';
require_once SPANGLE_ROOT . '/includes/Upload.php';
require_once SPANGLE_ROOT . '/includes/SiteContent.php';
