<?php

/**
 * Copy this file to config.local.php (same folder) and set a strong password.
 * When config.local.php exists, it is used for admin API login instead of
 * content/site.json → adminAccessKey (which is publicly readable).
 */

return [
    'admin_password' => 'change-this-to-a-long-random-secret',
];
