<?php

declare(strict_types=1);

/**
 * Admin password reset email (uses config/mail.local.php SMTP when enabled).
 */

function password_reset_build_url(PDO $pdo, string $token): string
{
    $base = local_public_base(setting_get($pdo, 'public_base', ''));

    return rtrim($base, '/') . '/admin/reset-password.php?token=' . urlencode($token);
}

function password_reset_smtp_available(): bool
{
    require_once SPANGLE_ROOT . '/includes/smtpMail.php';

    return smtp_mail_config() !== null;
}

function password_reset_build_message(PDO $pdo, array $admin, string $resetLink): array
{
    $siteName = trim(setting_get($pdo, 'site_name', ''));
    if ($siteName === '') {
        $brand = require SPANGLE_ROOT . '/includes/brand.php';
        $siteName = (string) ($brand['name'] ?? 'Archevo Design');
    }
    $displayName = trim((string) ($admin['display_name'] ?? 'Admin'));
    $subject = sprintf('[%s] Reset your admin password', $siteName);

    $lines = [
        'Hello ' . ($displayName !== '' ? $displayName : 'Admin') . ',',
        '',
        'We received a request to reset the password for your admin account.',
        '',
        'Open this link to choose a new password (valid for 1 hour, one-time use):',
        $resetLink,
        '',
        'If you did not request this, you can ignore this email. Your password will not change.',
        '',
        '— ' . $siteName,
    ];

    return [
        'subject' => $subject,
        'body' => implode("\n", $lines),
        'site_name' => $siteName,
    ];
}

function password_reset_log_failure(string $to, string $subject, string $body, string $reason): void
{
    $dir = SPANGLE_ROOT . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $entry = str_repeat('-', 72) . "\n"
        . date('Y-m-d H:i:s') . " — PASSWORD RESET EMAIL NOT SENT ({$reason})\n"
        . 'To: ' . $to . "\n"
        . 'Subject: ' . $subject . "\n\n"
        . $body . "\n\n";

    @file_put_contents($dir . '/password-reset-mail.log', $entry, FILE_APPEND | LOCK_EX);
}

function password_reset_send_email(PDO $pdo, array $admin, string $resetLink): bool
{
    require_once SPANGLE_ROOT . '/includes/smtpMail.php';
    require_once SPANGLE_ROOT . '/includes/enquiryMail.php';

    $to = trim((string) ($admin['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $built = password_reset_build_message($pdo, $admin, $resetLink);
    $subject = $built['subject'];
    $body = $built['body'];
    $from = enquiry_mail_from_address($pdo);

    $smtp = smtp_mail_config();
    if ($smtp && smtp_send_message($smtp, $to, $subject, $body, $from)) {
        return true;
    }

    $reply = function_exists('smtp_last_reply') ? trim(smtp_last_reply()) : '';
    $reason = $reply !== ''
        ? 'SMTP failed: ' . $reply
        : ($smtp ? 'SMTP failed' : 'SMTP not configured');

    if (enquiry_send_via_php_mail($to, $subject, $body, $from, $built['site_name'], $from)) {
        return true;
    }

    password_reset_log_failure($to, $subject, $body, $reason);
    error_log('[SPANGLE] Password reset email not sent. Log: data/password-reset-mail.log — ' . $reason);

    return false;
}

function password_reset_email_sent_message(): string
{
    return 'If that account exists, we sent a reset link to the registered email address. Check your inbox and spam folder. The link expires in 1 hour.';
}

function password_reset_should_show_link_on_screen(bool $emailSent, bool $smtpConfigured): bool
{
    if ($emailSent) {
        return false;
    }

    return app_is_local() || !$smtpConfigured;
}
