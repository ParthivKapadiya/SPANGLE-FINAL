<?php

declare(strict_types=1);

/**
 * Admin activity log + storage helpers.
 */

function admin_log_activity(
    PDO $pdo,
    string $action,
    string $entity = '',
    ?int $entityId = null,
    string $detail = ''
): void {
    try {
        $adminId = Auth::adminId();
        $stmt = $pdo->prepare(
            'INSERT INTO admin_activity (admin_id, action, entity, entity_id, detail)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $adminId > 0 ? $adminId : null,
            substr($action, 0, 80),
            substr($entity, 0, 80),
            $entityId,
            substr($detail, 0, 500),
        ]);
    } catch (Throwable $e) {
        // non-fatal
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function admin_recent_activity(PDO $pdo, int $limit = 12): array
{
    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.action, a.entity, a.entity_id, a.detail, a.created_at,
                    COALESCE(u.display_name, u.username, "System") AS admin_name
             FROM admin_activity a
             LEFT JOIN admins u ON u.id = a.admin_id
             ORDER BY a.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function admin_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    if ($bytes < 1073741824) {
        return round($bytes / 1048576, 1) . ' MB';
    }

    return round($bytes / 1073741824, 2) . ' GB';
}

function admin_uploads_usage(array $appConfig): array
{
    $root = rtrim((string) ($appConfig['upload_root'] ?? (SPANGLE_ROOT . '/uploads')), '/');
    $bytes = 0;
    $files = 0;

    if (!is_dir($root)) {
        return ['bytes' => 0, 'files' => 0, 'path' => $root];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $bytes += (int) $file->getSize();
            $files++;
        }
    }

    return ['bytes' => $bytes, 'files' => $files, 'path' => $root];
}

function admin_activity_icon(string $action): string
{
    return match (true) {
        str_contains($action, 'create'), str_contains($action, 'add') => 'fa-plus',
        str_contains($action, 'update'), str_contains($action, 'save'), str_contains($action, 'edit') => 'fa-pen',
        str_contains($action, 'delete'), str_contains($action, 'remove') => 'fa-trash',
        str_contains($action, 'upload') => 'fa-cloud-arrow-up',
        str_contains($action, 'publish') => 'fa-bullhorn',
        default => 'fa-circle-dot',
    };
}
