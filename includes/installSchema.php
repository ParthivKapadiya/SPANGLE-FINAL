<?php

declare(strict_types=1);

function install_schema_statements(): array
{
    $schema = file_get_contents(SPANGLE_ROOT . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read database/schema.sql');
    }

    $lines = preg_split('/\r?\n/', $schema) ?: [];
    $buffer = '';
    $statements = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        if (preg_match('/^\s*(CREATE\s+DATABASE|USE)\s+/i', $trim)) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with(rtrim($line), ';')) {
            $sql = trim($buffer);
            $buffer = '';
            if ($sql !== '') {
                $statements[] = $sql;
            }
        }
    }
    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function install_apply_schema(PDO $pdo): void
{
    foreach (install_schema_statements() as $sql) {
        $pdo->exec($sql);
    }
}
