<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * @return array{submissions: list<array<string, mixed>>}
 */
function spangle_read_enquiries(): array
{
    $path = spangle_enquiries_path();
    if (!is_file($path)) {
        return ['submissions' => []];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['submissions' => []];
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['submissions']) || !is_array($data['submissions'])) {
        return ['submissions' => []];
    }

    return ['submissions' => $data['submissions']];
}

/**
 * @param array{submissions: list<array<string, mixed>>} $data
 */
function spangle_write_enquiries(array $data): bool
{
    $path = spangle_enquiries_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        @chmod($dir, 0775);
    }

    $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }

    $json = json_encode($data, $flags);
    if ($json === false) {
        return false;
    }

    // Prefer atomic temp + rename (avoids flock issues on some Windows / NFS / shared hosts).
    $tmp = $dir . DIRECTORY_SEPARATOR . '._enq_' . bin2hex(random_bytes(5)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    @chmod($tmp, 0664);

    $ok = @rename($tmp, $path);
    if (!$ok && is_file($path)) {
        @unlink($path);
        $ok = @rename($tmp, $path);
    }
    if (!$ok) {
        @unlink($tmp);
        // Last resort: write in place (no atomicity).
        $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
    }

    if ($ok) {
        @chmod($path, 0664);
    }

    return (bool) $ok;
}

/**
 * @param array<string, mixed> $entry
 */
function spangle_append_enquiry(array $entry): bool
{
    $data = spangle_read_enquiries();
    $data['submissions'][] = $entry;

    return spangle_write_enquiries($data);
}

function spangle_delete_enquiry_by_id(string $id): bool
{
    if ($id === '') {
        return false;
    }
    $data = spangle_read_enquiries();
    $out = [];
    foreach ($data['submissions'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (isset($row['id']) && (string) $row['id'] === $id) {
            continue;
        }
        $out[] = $row;
    }
    $data['submissions'] = $out;

    return spangle_write_enquiries($data);
}
