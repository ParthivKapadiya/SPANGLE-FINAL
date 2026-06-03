<?php

declare(strict_types=1);

/**
 * Copy to database.local.php (gitignored) to override XAMPP MySQL credentials
 * without editing database.php.
 *
 * XAMPP on macOS: password is usually empty.
 * XAMPP on Windows: password is often "root".
 */
return [
    // 'host' => '127.0.0.1',
    // 'port' => '3306',
    // 'database' => 'spangle_studio',
    // 'username' => 'root',
    'password' => '',
];
