<?php

declare(strict_types=1);

/**
 * Export MySQL database to SQL dump (pure PHP — works without mysqldump on shared hosting).
 */

function database_export_quote_identifier(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function database_export_sql_value(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return $pdo->quote((string) $value);
}

/** @return array<int, string> */
function database_export_table_names(PDO $pdo): array
{
    $stmt = $pdo->query('SHOW TABLES');
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = (string) $row[0];
    }

    sort($tables);

    return $tables;
}

/** @return array{database: string, tables: int, rows: int, size_bytes: int} */
function database_export_stats(PDO $pdo, array $dbConfig): array
{
    $database = (string) ($dbConfig['database'] ?? '');
    $tables = database_export_table_names($pdo);
    $rows = 0;
    $sizeBytes = 0;

    foreach ($tables as $table) {
        $q = database_export_quote_identifier($table);
        $rows += (int) $pdo->query("SELECT COUNT(*) FROM {$q}")->fetchColumn();
    }

    if ($database !== '') {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(data_length + index_length), 0)
             FROM information_schema.TABLES
             WHERE table_schema = ?'
        );
        $stmt->execute([$database]);
        $sizeBytes = (int) $stmt->fetchColumn();
    }

    return [
        'database' => $database,
        'tables' => count($tables),
        'rows' => $rows,
        'size_bytes' => $sizeBytes,
    ];
}

function database_export_filename(string $database = ''): string
{
    $slug = preg_replace('/[^a-z0-9_-]+/i', '-', $database) ?: 'archevo';
    $slug = trim(strtolower($slug), '-');

    return $slug . '-backup-' . date('Y-m-d-His') . '.sql';
}

function database_export_sql(PDO $pdo): string
{
    $pdo->exec('SET NAMES utf8mb4');

    $lines = [
        '-- Archevo CMS database backup',
        '-- Generated: ' . gmdate('Y-m-d H:i:s') . ' UTC',
        'SET FOREIGN_KEY_CHECKS=0;',
        'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
        'SET time_zone = "+00:00";',
        'START TRANSACTION;',
    ];

    foreach (database_export_table_names($pdo) as $table) {
        $q = database_export_quote_identifier($table);
        $createRow = $pdo->query("SHOW CREATE TABLE {$q}")->fetch(PDO::FETCH_NUM);
        if (!$createRow) {
            continue;
        }

        $lines[] = '';
        $lines[] = "DROP TABLE IF EXISTS {$q};";
        $lines[] = (string) $createRow[1] . ';';

        $stmt = $pdo->query("SELECT * FROM {$q}");
        $batch = [];
        $columns = null;
        $colList = '';

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($columns === null) {
                $columns = array_keys($row);
                $colList = implode(', ', array_map(
                    static fn (string $c): string => database_export_quote_identifier($c),
                    $columns
                ));
            }

            $vals = [];
            foreach ($columns as $col) {
                $vals[] = database_export_sql_value($pdo, $row[$col]);
            }
            $batch[] = '(' . implode(', ', $vals) . ')';

            if (count($batch) >= 80) {
                $lines[] = "INSERT INTO {$q} ({$colList}) VALUES\n" . implode(",\n", $batch) . ';';
                $batch = [];
            }
        }

        if ($batch !== [] && $columns !== null) {
            $lines[] = "INSERT INTO {$q} ({$colList}) VALUES\n" . implode(",\n", $batch) . ';';
        }
    }

    $lines[] = '';
    $lines[] = 'COMMIT;';
    $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

    return implode("\n", $lines) . "\n";
}

function database_export_clear_output_buffers(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function database_export_temp_zip_path(): string
{
    $candidates = [
        SPANGLE_ROOT . '/data',
        SPANGLE_ROOT . '/uploads/.backup-tmp',
    ];

    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return rtrim($dir, '/') . '/.backup-' . bin2hex(random_bytes(8)) . '.zip';
        }
    }

    return rtrim(sys_get_temp_dir(), '/') . '/archevo-backup-' . bin2hex(random_bytes(8)) . '.zip';
}

function database_export_send_download(PDO $pdo, array $dbConfig): void
{
    $sql = database_export_sql($pdo);
    $filename = database_export_filename((string) ($dbConfig['database'] ?? 'archevo'));

    database_export_clear_output_buffers();

    header('Content-Type: application/sql; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($sql));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $sql;
}

function database_export_uploads_root(array $appConfig): string
{
    $root = rtrim((string) ($appConfig['upload_root'] ?? (SPANGLE_ROOT . '/uploads')), '/');

    return $root;
}

/** @return array{files: int, bytes: int, path: string} */
function database_export_uploads_stats(array $appConfig): array
{
    $root = database_export_uploads_root($appConfig);
    $files = 0;
    $bytes = 0;

    if (!is_dir($root)) {
        return ['files' => 0, 'bytes' => 0, 'path' => $root];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files++;
            $bytes += (int) $file->getSize();
        }
    }

    return ['files' => $files, 'bytes' => $bytes, 'path' => $root];
}

function database_export_full_zip_filename(string $database = ''): string
{
    $slug = preg_replace('/[^a-z0-9_-]+/i', '-', $database) ?: 'archevo';
    $slug = trim(strtolower($slug), '-');

    return $slug . '-full-backup-' . date('Y-m-d-His') . '.zip';
}

function database_export_readme_text(string $sqlFilename): string
{
    return <<<TXT
Archevo CMS — full site backup
Generated: {$sqlFilename}

CONTENTS
--------
database/   MySQL dump (.sql) — import in phpMyAdmin or your host MySQL tool
uploads/    All website images and media files

RESTORE
-------
1. Import the .sql file from database/ into your MySQL database.
2. Extract uploads/ into your website root (merge with existing uploads folder).
3. Ensure config/database.php points to the restored database.

Keep this archive private — it contains admin credentials and all site data.
TXT;
}

function database_export_zip_available(): bool
{
    return class_exists('ZipArchive');
}

/**
 * @return true on success
 */
function database_export_send_full_zip(PDO $pdo, array $dbConfig, array $appConfig): bool
{
    if (!database_export_zip_available()) {
        return false;
    }

    @set_time_limit(600);
    @ini_set('memory_limit', '512M');

    $database = (string) ($dbConfig['database'] ?? 'archevo');
    $sqlFilename = database_export_filename($database);
    $zipFilename = database_export_full_zip_filename($database);
    $sql = database_export_sql($pdo);
    $uploadRoot = database_export_uploads_root($appConfig);
    $tmp = database_export_temp_zip_path();

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);

        return false;
    }

    $zip->addFromString('database/' . $sqlFilename, $sql);
    $zip->addFromString('README.txt', database_export_readme_text($sqlFilename));

    if (is_dir($uploadRoot)) {
        $baseLen = strlen(rtrim(str_replace('\\', '/', $uploadRoot), '/')) + 1;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $abs = str_replace('\\', '/', $file->getPathname());
            $rel = 'uploads/' . substr($abs, $baseLen);
            $zip->addFile($file->getPathname(), $rel);
        }
    }

    if (!$zip->close()) {
        @unlink($tmp);

        return false;
    }

    if (!is_file($tmp) || filesize($tmp) === 0) {
        @unlink($tmp);

        return false;
    }

    database_export_clear_output_buffers();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . (string) filesize($tmp));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($tmp);
    @unlink($tmp);

    return true;
}
