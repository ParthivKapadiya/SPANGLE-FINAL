<?php

declare(strict_types=1);

/**
 * Example SMTP config — copy to mail.local.php and add real credentials.
 *
 *   cp config/mail.local.example.php config/mail.local.php
 *
 * Used for: enquiry form notifications + admin password reset emails.
 * Put real credentials in mail.local.php only (never commit that file).
 * Gmail / Google Workspace: smtp.gmail.com, port 587, encryption tls.
 */
return [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Archevo Design Website',
];
