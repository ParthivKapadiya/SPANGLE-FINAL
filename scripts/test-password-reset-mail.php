<?php

declare(strict_types=1);

/**
 * Test admin password-reset email delivery.
 * Run: php scripts/test-password-reset-mail.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);
require_once SPANGLE_ROOT . '/includes/passwordResetMail.php';
require_once SPANGLE_ROOT . '/includes/smtpMail.php';

$admin = $pdo->query('SELECT id, email, display_name, username FROM admins ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    echo "No admin user in database.\n";
    exit(1);
}

$smtp = smtp_mail_config();
echo 'Admin: ' . $admin['username'] . ' <' . ($admin['email'] ?: 'no email') . ">\n";
echo 'SMTP: ' . ($smtp ? $smtp['host'] . ':' . $smtp['port'] . ' as ' . $smtp['username'] : 'NOT SET') . "\n\n";

if (trim((string) ($admin['email'] ?? '')) === '') {
    echo "Admin has no email — set one in My profile first.\n";
    exit(1);
}

$token = bin2hex(random_bytes(16));
$link = password_reset_build_url($pdo, $token);
$ok = password_reset_send_email($pdo, $admin, $link);

if ($ok) {
    echo "SUCCESS — check inbox: {$admin['email']}\n";
    exit(0);
}

echo "FAILED — see data/password-reset-mail.log or PHP error log.\n";
echo "Setup: cp config/mail.local.example.php config/mail.local.php\n";
exit(1);
