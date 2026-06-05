<?php

declare(strict_types=1);

/**
 * Email notification when a visitor submits a contact/enquiry form.
 */

function enquiry_notify_recipients(PDO $pdo): array
{
    $raw = trim(setting_get($pdo, 'enquiry_notify_email', ''));
    if ($raw === '') {
        $raw = trim(setting_get($pdo, 'contact_email', 'hello@spangle.studio'));
    }

    $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $out = [];
    foreach ($parts as $addr) {
        if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $out[] = $addr;
        }
    }

    return array_values(array_unique($out));
}

function enquiry_mail_from_address(PDO $pdo): string
{
    $smtp = smtp_mail_config();
    if ($smtp) {
        return $smtp['from_email'];
    }

    $from = trim(setting_get($pdo, 'enquiry_mail_from', ''));
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return $from;
    }

    $contact = trim(setting_get($pdo, 'contact_email', ''));
    if ($contact !== '' && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        return $contact;
    }

    return 'noreply@localhost';
}

function enquiry_build_message(PDO $pdo, array $enquiry): array
{
    $siteName = setting_get($pdo, 'site_name', 'SPANGLE Architecture & Interior Design Studio');
    $name = (string) ($enquiry['name'] ?? '');
    $email = (string) ($enquiry['email'] ?? '');
    $phone = (string) ($enquiry['phone'] ?? '');
    $projectType = (string) ($enquiry['project_type'] ?? '');
    $message = (string) ($enquiry['message'] ?? '');
    $formSource = (string) ($enquiry['form_source'] ?? 'contact');
    $submittedAt = (string) ($enquiry['submitted_at'] ?? date('Y-m-d H:i:s'));

    $subject = sprintf('[%s] New website enquiry from %s', $siteName, $name !== '' ? $name : 'Visitor');

    $lines = [
        'A new enquiry was submitted on the website.',
        '',
        'Name: ' . $name,
        'Email: ' . $email,
        'Phone: ' . ($phone !== '' ? $phone : '—'),
    ];
    if ($projectType !== '') {
        $lines[] = 'Project type: ' . $projectType;
    }
    $lines[] = 'Form: ' . $formSource;
    $lines[] = 'Submitted: ' . $submittedAt;
    $lines[] = '';
    $lines[] = 'Message:';
    $lines[] = $message;
    $lines[] = '';
    $lines[] = '—';
    $lines[] = 'Reply directly to the sender using Reply in your mail app.';
    $lines[] = 'A copy is also saved in the website admin under Contact messages.';

    return [
        'subject' => $subject,
        'body' => implode("\n", $lines),
        'reply_to' => $email,
        'site_name' => $siteName,
    ];
}

function enquiry_send_via_php_mail(string $to, string $subject, string $body, string $fromEmail, string $siteName, string $replyTo): bool
{
    $fromHeader = sprintf('%s <%s>', $siteName, $fromEmail);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromHeader,
        'Reply-To: ' . $replyTo,
    ];

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function enquiry_log_notification_failure(array $recipients, string $subject, string $body, string $reason): void
{
    $dir = SPANGLE_ROOT . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $logPath = $dir . '/enquiry-mail.log';
    $entry = str_repeat('-', 72) . "\n"
        . date('Y-m-d H:i:s') . " — EMAIL NOT SENT ({$reason})\n"
        . 'To: ' . implode(', ', $recipients) . "\n"
        . 'Subject: ' . $subject . "\n\n"
        . $body . "\n\n";

    @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
}

function enquiry_send_notification(PDO $pdo, array $enquiry): bool
{
    require_once SPANGLE_ROOT . '/includes/smtpMail.php';

    $recipients = enquiry_notify_recipients($pdo);
    if (!$recipients) {
        return false;
    }

    $built = enquiry_build_message($pdo, $enquiry);
    $subject = $built['subject'];
    $body = $built['body'];
    $replyTo = $built['reply_to'];
    $siteName = $built['site_name'];
    $from = enquiry_mail_from_address($pdo);

    $smtp = smtp_mail_config();
    $anySent = false;
    $lastError = 'mail() failed';

    foreach ($recipients as $to) {
        if ($smtp && smtp_send_message($smtp, $to, $subject, $body, $replyTo)) {
            $anySent = true;
            continue;
        }

        if ($smtp) {
            $reply = function_exists('smtp_last_reply') ? trim(smtp_last_reply()) : '';
            $lastError = $reply !== ''
                ? 'SMTP failed: ' . $reply
                : 'SMTP failed — check config/mail.local.php (Gmail App Password for this account)';
        }

        if (enquiry_send_via_php_mail($to, $subject, $body, $from, $siteName, $replyTo)) {
            $anySent = true;
            continue;
        }
    }

    if ($anySent) {
        return true;
    }

    enquiry_log_notification_failure($recipients, $subject, $body, $lastError);
    error_log('[SPANGLE] Enquiry email not sent. Log: data/enquiry-mail.log — ' . $lastError);

    return false;
}
