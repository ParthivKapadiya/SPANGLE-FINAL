<?php

declare(strict_types=1);

final class Upload
{
    /**
     * @param array<string, mixed> $appConfig
     * @return array{ok: bool, path?: string, error?: string}
     */
    public static function image(array $appConfig, string $folderKey, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file selected.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed. Try a smaller image.'];
        }

        $max = (int) ($appConfig['upload_max_bytes'] ?? 26214400);
        if (($file['size'] ?? 0) > $max) {
            $mb = max(1, (int) round($max / 1048576));
            return ['ok' => false, 'error' => 'Image is too large (max ' . $mb . ' MB).'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $allowed = $appConfig['upload_allowed_mimes'] ?? [];
        if (!in_array($mime, $allowed, true)) {
            return ['ok' => false, 'error' => 'Only JPG, PNG, WEBP or GIF allowed.'];
        }

        $folders = $appConfig['upload_folders'] ?? [];
        $relDir = $folders[$folderKey] ?? $folders['general'] ?? 'uploads/general';
        $absDir = SPANGLE_ROOT . '/' . $relDir;
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['ok' => false, 'error' => 'Could not create upload folder.'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $relPath = $relDir . '/' . $name;
        $absPath = SPANGLE_ROOT . '/' . $relPath;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            return ['ok' => false, 'error' => 'Could not save uploaded file.'];
        }

        return ['ok' => true, 'path' => str_replace('\\', '/', $relPath)];
    }
}
