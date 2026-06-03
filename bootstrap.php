<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

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

require_once SPANGLE_ROOT . '/includes/Database.php';
require_once SPANGLE_ROOT . '/includes/helpers.php';
require_once SPANGLE_ROOT . '/includes/Auth.php';
require_once SPANGLE_ROOT . '/includes/Upload.php';
require_once SPANGLE_ROOT . '/includes/SiteContent.php';