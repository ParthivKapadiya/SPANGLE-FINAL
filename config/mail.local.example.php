<?php

declare(strict_types=1);

/**
 * Example SMTP config — copy to mail.local.php and add real credentials.
 *
 *   cp config/mail.local.example.php config/mail.local.php
 *
 * Put your real email and App Password in mail.local.php only (not in this file).
 * Gmail / Google Workspace: smtp.gmail.com, port 587, encryption tls.
 */
return [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'pkapadiya257@rku.ac.in',
    'password' => 'zwiq lfyu gikn tbkm',
    'from_email' => 'pkapadiya257@rku.ac.in',
    'from_name' => 'SPANGLE Architecture & Interior Design Studio Website',
];
