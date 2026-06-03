<?php

declare(strict_types=1);

/**
 * Minimal SMTP client (STARTTLS + AUTH LOGIN) for enquiry notifications.
 */

function smtp_mail_config(): ?array
{
    $path = SPANGLE_ROOT . '/config/mail.local.php';
    if (!is_file($path)) {
        return null;
    }

    $cfg = require $path;
    if (!is_array($cfg) || empty($cfg['enabled'])) {
        return null;
    }

    $host = trim((string) ($cfg['host'] ?? ''));
    $user = trim((string) ($cfg['username'] ?? ''));
    $pass = str_replace(' ', '', (string) ($cfg['password'] ?? ''));
    $from = trim((string) ($cfg['from_email'] ?? $user));

    if ($host === '' || $user === '' || $pass === '' || $from === '') {
        return null;
    }

    return [
        'host' => $host,
        'port' => (int) ($cfg['port'] ?? 587),
        'encryption' => strtolower(trim((string) ($cfg['encryption'] ?? 'tls'))),
        'username' => $user,
        'password' => $pass,
        'from_email' => $from,
        'from_name' => trim((string) ($cfg['from_name'] ?? 'Archevo Design')),
        'timeout' => (int) ($cfg['timeout'] ?? 20),
    ];
}

function smtp_read_line($socket): string
{
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}

function smtp_last_reply(): string
{
    return $GLOBALS['archevo_smtp_last_reply'] ?? '';
}

function smtp_expect($socket, array $codes): bool
{
    $reply = smtp_read_line($socket);
    $GLOBALS['archevo_smtp_last_reply'] = trim($reply);
    $code = (int) substr($reply, 0, 3);

    return in_array($code, $codes, true);
}

function smtp_cmd($socket, string $cmd, array $okCodes): bool
{
    fwrite($socket, $cmd . "\r\n");

    return smtp_expect($socket, $okCodes);
}

function smtp_send_message(array $cfg, string $to, string $subject, string $body, string $replyTo): bool
{
    $host = $cfg['host'];
    $port = $cfg['port'];
    $enc = $cfg['encryption'];
    $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        $remote,
        $errno,
        $errstr,
        $cfg['timeout'],
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
    );

    if (!$socket) {
        error_log("[Archevo SMTP] Connect failed: {$errstr} ({$errno})");

        return false;
    }

    stream_set_timeout($socket, $cfg['timeout']);

    if (!smtp_expect($socket, [220])) {
        fclose($socket);

        return false;
    }

    $ehloHost = 'localhost';
    if (!smtp_cmd($socket, 'EHLO ' . $ehloHost, [250])) {
        fclose($socket);

        return false;
    }

    if ($enc === 'tls') {
        if (!smtp_cmd($socket, 'STARTTLS', [220])) {
            fclose($socket);

            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);

            return false;
        }
        if (!smtp_cmd($socket, 'EHLO ' . $ehloHost, [250])) {
            fclose($socket);

            return false;
        }
    }

    if (!smtp_cmd($socket, 'AUTH LOGIN', [334])) {
        fclose($socket);

        return false;
    }
    if (!smtp_cmd($socket, base64_encode($cfg['username']), [334])) {
        fclose($socket);

        return false;
    }
    if (!smtp_cmd($socket, base64_encode($cfg['password']), [235])) {
        error_log('[Archevo SMTP] AUTH failed: ' . smtp_last_reply());
        fclose($socket);

        return false;
    }

    $from = $cfg['from_email'];
    if (!smtp_cmd($socket, 'MAIL FROM:<' . $from . '>', [250])) {
        fclose($socket);

        return false;
    }
    if (!smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251])) {
        fclose($socket);

        return false;
    }
    if (!smtp_cmd($socket, 'DATA', [354])) {
        fclose($socket);

        return false;
    }

    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromName = $cfg['from_name'];
    $fromHeader = $fromName !== ''
        ? '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>'
        : $from;

    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'To: ' . $to,
        'Subject: ' . $subjectEnc,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
    $payload = str_replace("\n.", "\n..", $payload);
    $payload = str_replace("\n", "\r\n", $payload);

    fwrite($socket, $payload . "\r\n.\r\n");
    if (!smtp_expect($socket, [250])) {
        fclose($socket);

        return false;
    }

    smtp_cmd($socket, 'QUIT', [221]);
    fclose($socket);

    return true;
}
