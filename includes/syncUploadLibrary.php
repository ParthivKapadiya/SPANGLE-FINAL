<?php

declare(strict_types=1);

/**
 * Import images from uploads/ (root + subfolders) into gallery, projects, and heroes.
 */
final class SyncUploadLibrary
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * @return array{images: list<array{rel:string,abs:string,size:int,name:string}>, groups: array<string, list<array>>}
     */
    public static function scan(): array
    {
        $root = SPANGLE_ROOT . '/uploads';
        $images = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, self::IMAGE_EXT, true)) {
                continue;
            }
            $abs = str_replace('\\', '/', $file->getPathname());
            $rel = 'uploads/' . ltrim(str_replace(str_replace('\\', '/', $root), '', $abs), '/');
            if (self::shouldSkipProjectImage($rel, $file->getFilename())) {
                continue;
            }
            $images[] = [
                'rel' => $rel,
                'abs' => $abs,
                'size' => (int) $file->getSize(),
                'name' => $file->getFilename(),
            ];
        }

        usort($images, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        $groups = [];
        foreach ($images as $img) {
            $key = self::projectKeyFromFilename($img['name']);
            $groups[$key][] = $img;
        }

        uasort($groups, static fn ($a, $b) => count($b) <=> count($a));

        return ['images' => $images, 'groups' => $groups];
    }

    public static function projectKeyFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^(\d{3,4})[-_](.+?)(?:[-_]\d+)?$/i', $base, $m)) {
            $name = trim(preg_replace('/[-_]\d+$/', '', $m[2]));

            return $m[1] . '-' . $name;
        }

        if (preg_match('/^(\d{3,4})[-_](.+)$/i', $base, $m)) {
            return $m[1] . '-' . trim($m[2]);
        }

        if (preg_match('/^(\d{3,4})[-_]([A-Z]+)/i', $base, $m)) {
            return $m[1] . '-' . $m[2];
        }

        return 'collection-' . self::slugify($base);
    }

    public static function titleFromKey(string $key): string
    {
        if (str_starts_with($key, 'collection-')) {
            return ucwords(str_replace('-', ' ', substr($key, 11)));
        }

        if (preg_match('/^\d{3,4}-(.+)$/i', $key, $m)) {
            return ucwords(strtolower($m[1]));
        }

        return ucwords(str_replace('-', ' ', $key));
    }

    public static function titleFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $text = preg_replace('/[-_]+/', ' ', $base) ?? $base;
        $text = preg_replace('/\s+/', ' ', trim($text));

        return ucwords(strtolower($text));
    }

    /**
     * Create one work-page project per uploaded image (title from filename).
     *
     * @return array{projects: int, images: int, gallery: int}
     */
    public static function syncOneProjectPerImage(PDO $pdo, bool $replacePortfolio = true): array
    {
        require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
        require_once SPANGLE_ROOT . '/includes/cms/ProjectRepository.php';
        cms_run_migrations($pdo);

        $scan = self::scan();
        $images = $scan['images'];
        if (!$images) {
            return ['projects' => 0, 'images' => 0, 'gallery' => 0];
        }

        if ($replacePortfolio) {
            $pdo->exec(
                "DELETE pi FROM project_images pi
                 INNER JOIN projects p ON p.id = pi.project_id
                 WHERE p.slug NOT LIKE 'retail-%' AND p.hero_image NOT LIKE 'http%'"
            );
            $pdo->exec("DELETE FROM projects WHERE slug NOT LIKE 'retail-%' AND hero_image NOT LIKE 'http%'");
        }

        $projectStmt = $pdo->prepare(
            'INSERT INTO projects (slug, title, location, category, project_type, summary, body_html, hero_image, link_url,
             home_highlight, home_layout, sort_order, is_active, is_featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $imageStmt = $pdo->prepare(
            'INSERT INTO project_images (project_id, image_path, caption, sort_order) VALUES (?, ?, ?, 0)'
        );

        $sort = 0;
        $highlightCount = 0;
        foreach ($images as $img) {
            $title = self::titleFromFilename($img['name']);
            $slugBase = self::slugify(pathinfo($img['name'], PATHINFO_FILENAME));
            $slug = $slugBase;
            $n = 2;
            while (self::slugExists($pdo, $slug)) {
                $slug = $slugBase . '-' . $n;
                $n++;
            }

            $category = self::guessCategory(self::projectKeyFromFilename($img['name']), $img['name']);
            $projectType = ProjectRepository::normalizeType($category === 'retail' ? 'commercial' : $category);
            $link = 'project.php?slug=' . rawurlencode($slug);
            $highlight = $highlightCount < 8 ? 1 : 0;
            $layout = $highlightCount === 0 ? 'lg' : ($highlightCount === 3 ? 'wide' : '');
            if ($highlight) {
                $highlightCount++;
            }

            $body = self::buildProjectBodyHtml([$img]);
            $projectStmt->execute([
                $slug,
                $title,
                'Gujarat, India',
                $projectType,
                $projectType,
                'Interior & architecture visual — ' . $title . '.',
                $body,
                $img['rel'],
                $link,
                $highlight,
                $layout,
                $sort++,
                $highlight ? 1 : 0,
            ]);

            $projectId = (int) $pdo->lastInsertId();
            $imageStmt->execute([$projectId, $img['rel'], $title]);
        }

        setting_set($pdo, 'home_projects_title', 'Featured commissions');
        setting_set($pdo, 'home_projects_intro', 'Explore client projects — each album opens a full visual case study.');
        content_sync_site_json($pdo);

        return [
            'projects' => count($images),
            'images' => count($images),
            'gallery' => count($images),
        ];
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

        return trim($text, '-') ?: 'project';
    }

    /** Skip logos, admin test uploads, and other non-portfolio assets. */
    public static function shouldSkipProjectImage(string $rel, string $filename): bool
    {
        $name = strtolower($filename);
        $path = strtolower(str_replace('\\', '/', $rel));

        if (preg_match('/^archevo design_0[12]\.(png|jpe?g|webp)$/i', $name)) {
            return true;
        }
        if (preg_match('/^spangle/i', $name)) {
            return true;
        }
        if (preg_match('/^\d{8}_\d{6}_[a-f0-9]+\.(png|jpe?g|webp)$/i', $name)) {
            return true;
        }
        if (str_contains($path, '/uploads/hero/') || str_contains($path, '/uploads/general/')) {
            return true;
        }
        if (str_contains($path, '/uploads/retail/')) {
            return true;
        }

        return false;
    }

    public static function guessCategory(string $key, string $filename): string
    {
        $hay = strtoupper($key . ' ' . $filename);
        if (preg_match('/\b(RETAIL|SHOP|SHOWROOM|STORE|BOUTIQUE)\b/', $hay)) {
            return 'retail';
        }
        if (preg_match('/\b(OFFICE|COMMERCIAL|WORKPLACE|CORPORATE|CLINIC|HOSPITAL|RESTAURANT|CAFE|HOTEL)\b/', $hay)) {
            return 'commercial';
        }
        if (preg_match('/\b(JADEJA|3D)\b/', $hay)) {
            return 'commercial';
        }

        return 'residential';
    }

    /**
     * @return array{gallery: int, projects: int, heroes: int, settings: int}
     */
    public static function sync(PDO $pdo, bool $replaceExisting = true): array
    {
        require_once SPANGLE_ROOT . '/includes/cmsMigrate.php';
        cms_run_migrations($pdo);

        $scan = self::scan();
        $images = $scan['images'];
        $groups = $scan['groups'];

        if (!$images) {
            return ['gallery' => 0, 'projects' => 0, 'heroes' => 0, 'settings' => 0];
        }

        if ($replaceExisting) {
            $pdo->exec('DELETE FROM gallery_items');
            $pdo->exec("DELETE FROM projects WHERE hero_image NOT LIKE 'http%' AND slug NOT LIKE 'retail-%'");
            $pdo->exec('DELETE FROM hero_slides');
        }

        $homeLimit = 12;
        $homePicks = self::pickHomeGalleryImages($groups, $homeLimit);
        $galleryStmt = $pdo->prepare(
            'INSERT INTO gallery_items (image_path, alt_text, caption, sort_order, is_active, show_on_home) VALUES (?, ?, ?, ?, 1, 1)'
        );
        $sort = 0;
        foreach ($homePicks as $img) {
            $caption = self::titleFromKey(self::projectKeyFromFilename($img['name']));
            $galleryStmt->execute([$img['rel'], $caption, $caption, $sort++]);
        }
        setting_set($pdo, 'home_gallery_limit', (string) $homeLimit);

        $projectStmt = $pdo->prepare(
            'INSERT INTO projects (slug, title, location, category, summary, body_html, hero_image, link_url, home_highlight, home_layout, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );

        $projectSort = 0;
        $highlightCount = 0;
        foreach ($groups as $key => $groupImages) {
            $title = self::titleFromKey($key);
            $slug = self::slugify($key);
            $slugBase = $slug;
            $n = 2;
            while (self::slugExists($pdo, $slug)) {
                $slug = $slugBase . '-' . $n;
                $n++;
            }

            $hero = $groupImages[0]['rel'];
            $category = self::guessCategory($key, $groupImages[0]['name']);
            $summary = count($groupImages) . ' visual' . (count($groupImages) === 1 ? '' : 's') . ' from this commission.';
            $body = self::buildProjectBodyHtml($groupImages);
            $link = 'project.php?slug=' . rawurlencode($slug);
            $highlight = $highlightCount < 8 ? 1 : 0;
            $layout = $highlightCount === 0 ? 'lg' : ($highlightCount === 3 ? 'wide' : '');
            if ($highlight) {
                $highlightCount++;
            }

            $projectStmt->execute([
                $slug,
                $title,
                'Gujarat, India',
                $category,
                $summary,
                $body,
                $hero,
                $link,
                $highlight,
                $layout,
                $projectSort++,
            ]);
        }

        $heroStmt = $pdo->prepare(
            'INSERT INTO hero_slides (image_path, alt_text, sort_order, is_active) VALUES (?, ?, ?, 1)'
        );
        $heroCandidates = $images;
        usort($heroCandidates, static fn ($a, $b) => $b['size'] <=> $a['size']);
        $heroPick = array_slice($heroCandidates, 0, min(8, count($heroCandidates)));
        $heroSort = 0;
        foreach ($heroPick as $img) {
            $alt = self::titleFromKey(self::projectKeyFromFilename($img['name']));
            $heroStmt->execute([$img['rel'], $alt, $heroSort++]);
        }

        $settings = 0;
        $settings += self::setSetting($pdo, 'home_about_image', $heroPick[0]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'work_hero_image', $heroPick[1]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'studio_hero_image', $heroPick[2]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'services_hero_image', $heroPick[3]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'journal_hero_image', $heroPick[4]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'contact_hero_image', $heroPick[5]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'process_hero_image', $heroPick[6]['rel'] ?? $images[0]['rel']) ? 1 : 0;
        $settings += self::setSetting($pdo, 'seo_og_image', $heroPick[0]['rel'] ?? '') ? 1 : 0;
        self::setSetting($pdo, 'home_gallery_title', 'Project gallery');
        self::setSetting($pdo, 'home_gallery_intro', 'A curated library of interiors, architecture, and 3D visualisations from recent Archevo commissions across Gujarat.');
        self::setSetting($pdo, 'home_projects_title', 'Featured commissions');
        self::setSetting($pdo, 'home_projects_intro', 'Explore client projects — each album opens a full visual case study.');

        $pdo->exec('DELETE FROM home_stats');
        $stats = [
            [strtoupper((string) count($groups)), 'Client commissions'],
            [(string) count($images), 'Project visuals'],
            ['16+', 'Years in practice'],
            ['3', 'Design disciplines'],
        ];
        $statStmt = $pdo->prepare('INSERT INTO home_stats (stat_value, stat_label, sort_order) VALUES (?, ?, ?)');
        foreach ($stats as $i => $row) {
            $statStmt->execute([$row[0], $row[1], $i]);
        }

        content_sync_site_json($pdo);

        return [
            'gallery' => count($homePicks),
            'projects' => count($groups),
            'heroes' => count($heroPick),
            'settings' => $settings,
            'total_images' => count($images),
        ];
    }

    /**
     * One strong image per project group for the home page gallery only.
     *
     * @param array<string, list<array{rel:string,abs:string,size:int,name:string}>> $groups
     * @return list<array{rel:string,abs:string,size:int,name:string}>
     */
    public static function pickHomeGalleryImages(array $groups, int $limit = 12): array
    {
        $picks = [];
        foreach ($groups as $groupImages) {
            if (count($picks) >= $limit) {
                break;
            }
            if (!$groupImages) {
                continue;
            }
            $sorted = $groupImages;
            usort($sorted, static fn ($a, $b) => $b['size'] <=> $a['size']);
            $picks[] = $sorted[0];
        }

        return $picks;
    }

    private static function slugExists(PDO $pdo, string $slug): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM projects WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);

        return (bool) $stmt->fetch();
    }

    /**
     * @param list<array{rel:string,name:string}> $images
     */
    private static function buildProjectBodyHtml(array $images): string
    {
        $figures = array_map(static function ($img) {
            $alt = htmlspecialchars(self::titleFromKey(self::projectKeyFromFilename($img['name'])), ENT_QUOTES, 'UTF-8');
            $src = htmlspecialchars(public_media_path($img['rel']), ENT_QUOTES, 'UTF-8');

            return '<figure class="project-photo"><img src="' . $src . '" alt="' . $alt . '" loading="lazy" decoding="async" /></figure>';
        }, $images);

        return '<div class="project-gallery-grid">' . implode('', $figures) . '</div>';
    }

    private static function setSetting(PDO $pdo, string $key, string $value): bool
    {
        if ($value === '') {
            return false;
        }
        setting_set($pdo, $key, $value);

        return true;
    }
}
