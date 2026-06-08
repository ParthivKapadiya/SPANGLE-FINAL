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
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file selected.'];
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage($uploadError)];
        }

        $max = (int) ($appConfig['upload_max_bytes'] ?? 5242880);
        if (($file['size'] ?? 0) > $max) {
            return ['ok' => false, 'error' => 'Image is too large (max ' . self::formatBytes($max) . ').'];
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
        $mode = function_exists('app_is_local') && app_is_local() ? 0777 : 0775;
        if (!is_dir($absDir) && !@mkdir($absDir, $mode, true) && !is_dir($absDir)) {
            return ['ok' => false, 'error' => 'Could not create upload folder.'];
        }
        if (!is_writable($absDir)) {
            @chmod($absDir, $mode);
            clearstatcache(true, $absDir);
        }
        if (!is_writable($absDir)) {
            return [
                'ok' => false,
                'error' => 'Upload folder is not writable. Run: chmod -R 777 ' . dirname($absDir),
            ];
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

        require_once SPANGLE_ROOT . '/includes/ImageOptimizer.php';
        ImageOptimizer::processUploadedFile($absPath);

        return ['ok' => true, 'path' => str_replace('\\', '/', $relPath)];
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Image exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Use a smaller file or compress the image.',
            UPLOAD_ERR_FORM_SIZE => 'Image exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing. Contact your host.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
            default => 'Upload failed. Try a smaller image.',
        };
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
