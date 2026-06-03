<?php

declare(strict_types=1);

/**
 * Test enquiry email delivery.
 * Run: php scripts/test-enquiry-mail.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection($GLOBALS['configDb']);
require_once SPANGLE_ROOT . '/includes/enquiryMail.php';
require_once SPANGLE_ROOT . '/includes/smtpMail.php';

$recipients = enquiry_notify_recipients($pdo);
$smtp = smtp_mail_config();

echo "Notify: " . implode(', ', $recipients) . "\n";
echo 'SMTP config: ' . ($smtp ? $smtp['host'] . ':' . $smtp['port'] . ' as ' . $smtp['username'] : 'NOT SET (copy config/mail.local.example.php → mail.local.php)') . "\n\n";

$ok = enquiry_send_notification($pdo, [
    'name' => 'Mail test',
    'email' => 'visitor@example.com',
    'phone' => '+91 99999 99999',
    'project_type' => 'Test',
    'message' => 'If you receive this, enquiry email is working.',
    'form_source' => 'cli-test',
    'submitted_at' => date('Y-m-d H:i:s'),
]);

if ($ok) {
    echo "SUCCESS — check the inbox for notify address(es).\n";
    exit(0);
}

$log = SPANGLE_ROOT . '/data/enquiry-mail.log';
echo "FAILED — PHP mail() does not work on XAMPP without SMTP.\n";
if (is_file($log)) {
    echo "Fallback log written: data/enquiry-mail.log\n";
}
echo "\nSetup SMTP:\n";
echo "  cp config/mail.local.example.php config/mail.local.php\n";
echo "  Edit mail.local.php with Gmail App Password, then run this script again.\n";
exit(1);
