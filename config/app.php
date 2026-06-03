<?php

declare(strict_types=1);

/**
 * Application paths and upload rules.
 *
 * env: "auto" detects local vs production from HTTP_HOST.
 * Set env to "production" on live hosting to force production-safe behavior.
 */
return [
    'site_name' => 'Archevo Design',
    'timezone' => 'Asia/Kolkata',
    'env' => 'auto',
    'admin_session_timeout' => 7200,
    'login_max_attempts' => 5,
    'login_lockout_seconds' => 900,
    'upload_max_bytes' => 5 * 1024 * 1024,
    'upload_allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
    'upload_folders' => [
        'hero' => 'uploads/hero',
        'about' => 'uploads/about',
        'gallery' => 'uploads/gallery',
        'projects' => 'uploads/projects',
        'services' => 'uploads/services',
        'studio' => 'uploads/studio',
        'general' => 'uploads/general',
        'team' => 'uploads/team',
    ],
];
