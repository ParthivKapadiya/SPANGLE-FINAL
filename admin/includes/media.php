<?php

declare(strict_types=1);

function media_register(PDO $pdo, string $filePath, string $fileName, ?string $mime = null, ?int $size = null): void
{
    $filePath = str_replace('\\', '/', trim($filePath));
    if ($filePath === '') {
        return;
    }
    $exists = $pdo->prepare('SELECT id FROM media_assets WHERE file_path = ? LIMIT 1');
    $exists->execute([$filePath]);
    if ($exists->fetch()) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO media_assets (file_path, file_name, mime_type, file_size) VALUES (?, ?, ?, ?)'
    )->execute([$filePath, $fileName, $mime, $size]);
}

function media_delete_file(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT file_path FROM media_assets WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    $path = SPANGLE_ROOT . '/' . ltrim((string) $row['file_path'], '/');
    if (is_file($path)) {
        @unlink($path);
    }
    $pdo->prepare('DELETE FROM media_assets WHERE id = ?')->execute([$id]);

    return true;
}
