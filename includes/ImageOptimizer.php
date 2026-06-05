<?php

declare(strict_types=1);

/**
 * Resize uploads and generate width variants for responsive srcset.
 */
final class ImageOptimizer
{
    public const VARIANT_WIDTHS = [640, 1280, 1920];

    public const MAX_MASTER_WIDTH = 1920;

    public const JPEG_QUALITY = 84;

    public static function isSupported(string $absPath): bool
    {
        if (!is_file($absPath) || !function_exists('imagecreatetruecolor')) {
            return false;
        }
        $info = @getimagesize($absPath);

        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }

    public static function variantsEnabled(): bool
    {
        return (bool) app_config('image_responsive_variants', false);
    }

    public static function processUploadedFile(string $absPath): void
    {
        if (!self::isSupported($absPath)) {
            return;
        }

        self::resizeToMaxWidth($absPath, self::MAX_MASTER_WIDTH);
        if (self::variantsEnabled()) {
            self::generateVariants($absPath);
        }
    }

    /**
     * @return array{processed: int, skipped: int, errors: int}
     */
    public static function optimizeDirectory(string $absDir, bool $recursive = true): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'errors' => 0];
        if (!is_dir($absDir)) {
            return $stats;
        }

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absDir, FilesystemIterator::SKIP_DOTS))
            : new IteratorIterator(new DirectoryIterator($absDir));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (preg_match('/-\d+w\.(jpe?g|png|webp)$/i', $path)) {
                continue;
            }
            if (!self::isSupported($path)) {
                $stats['skipped']++;

                continue;
            }
            try {
                self::resizeToMaxWidth($path, self::MAX_MASTER_WIDTH);
                if (self::variantsEnabled()) {
                    self::generateVariants($path);
                }
                $stats['processed']++;
            } catch (Throwable $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    public static function variantRelativePath(string $relPath, int $width): string
    {
        $relPath = str_replace('\\', '/', $relPath);
        $dot = strrpos($relPath, '.');
        if ($dot === false) {
            return $relPath . '-' . $width . 'w';
        }

        return substr($relPath, 0, $dot) . '-' . $width . 'w' . substr($relPath, $dot);
    }

    public static function resizeToMaxWidth(string $absPath, int $maxWidth): void
    {
        $image = self::loadImage($absPath);
        if ($image === null) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            return;
        }

        if ($width > $maxWidth) {
            $newH = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newH);
            self::preserveAlpha($resized, $absPath);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newH, $width, $height);
            $image = $resized;
        }

        self::saveImage($image, $absPath);
    }

    public static function generateVariants(string $absPath): void
    {
        $info = @getimagesize($absPath);
        if ($info === false) {
            return;
        }
        $masterW = $info[0];
        $rel = self::relativeFromAbsolute($absPath);
        if ($rel === '') {
            return;
        }

        foreach (self::VARIANT_WIDTHS as $targetW) {
            if ($targetW >= $masterW) {
                continue;
            }
            $variantRel = self::variantRelativePath($rel, $targetW);
            $variantAbs = SPANGLE_ROOT . '/' . $variantRel;
            $dir = dirname($variantAbs);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $image = self::loadImage($absPath);
            if ($image === null) {
                continue;
            }
            $width = imagesx($image);
            $height = imagesy($image);
            $newH = (int) round($height * ($targetW / $width));
            $resized = imagecreatetruecolor($targetW, $newH);
            self::preserveAlpha($resized, $absPath);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetW, $newH, $width, $height);
            self::saveImage($resized, $variantAbs);
        }
    }

    private static function relativeFromAbsolute(string $absPath): string
    {
        $root = rtrim(str_replace('\\', '/', SPANGLE_ROOT), '/');
        $abs = str_replace('\\', '/', $absPath);
        if (!str_starts_with($abs, $root . '/')) {
            return '';
        }

        return ltrim(substr($abs, strlen($root) + 1), '/');
    }

    private static function preserveAlpha(GdImage $canvas, string $sourcePath): void
    {
        $info = @getimagesize($sourcePath);
        if ($info && $info[2] === IMAGETYPE_PNG) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        }
    }

    private static function loadImage(string $absPath): ?GdImage
    {
        $info = @getimagesize($absPath);
        if ($info === false) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absPath) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($absPath) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($absPath) ?: null) : null,
            default => null,
        };
    }

    private static function saveImage(GdImage $image, string $absPath): void
    {
        $info = @getimagesize($absPath);
        $type = $info[2] ?? IMAGETYPE_JPEG;

        if ($type === IMAGETYPE_PNG) {
            imagepng($image, $absPath, 8);

            return;
        }
        if ($type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            imagewebp($image, $absPath, self::JPEG_QUALITY);

            return;
        }

        imagejpeg($image, $absPath, self::JPEG_QUALITY);
    }
}
