<?php

declare(strict_types=1);

final class ProjectRepository
{
    public const PER_PAGE = 12;

    public const TYPES = [
        'residential',
        'commercial',
        'interior',
        'architecture',
        'renovation',
        'hospitality',
        'office',
        'villa',
        'mixed-use',
    ];

    public static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === 'retail') {
            return 'commercial';
        }

        return in_array($type, self::TYPES, true) ? $type : 'residential';
    }

    /**
     * @return array{items: list<array>, meta: array}
     */
    public static function list(PDO $pdo, array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min(48, (int) ($query['per_page'] ?? self::PER_PAGE)));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) ($query['search'] ?? ''));
        $category = self::normalizeType((string) ($query['category'] ?? ''));
        $type = trim((string) ($query['type'] ?? ''));
        if ($type !== '') {
            $type = self::normalizeType($type);
        }
        $sort = (string) ($query['sort'] ?? 'latest');
        $activeOnly = !isset($query['active']) || (string) $query['active'] !== '0';

        $where = [];
        $params = [];

        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }
        if ($search !== '') {
            $where[] = '(title LIKE ? OR location LIKE ? OR summary LIKE ? OR slug LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($category !== '' && $category !== 'all') {
            $where[] = '(project_type = ? OR category = ?)';
            $params[] = $category;
            $params[] = $category === 'commercial' ? 'commercial' : $category;
        }
        if ($type !== '') {
            $where[] = 'project_type = ?';
            $params[] = $type;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $order = match ($sort) {
            'oldest' => 'COALESCE(created_at, id) ASC, id ASC',
            'featured' => 'is_featured DESC, COALESCE(created_at, id) DESC, id DESC',
            default => 'COALESCE(created_at, id) DESC, id DESC',
        };

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM projects $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id, slug, title, location, category, project_type, summary, hero_image,
                       area_label, completion_year, is_featured, home_highlight, link_url, created_at
                FROM projects $whereSql
                ORDER BY $order
                LIMIT $perPage OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map([self::class, 'mapListItem'], $rows);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    public static function findBySlug(PDO $pdo, string $slug, bool $activeOnly = true): ?array
    {
        $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower(trim($slug)));
        if ($slug === '') {
            return null;
        }
        $sql = 'SELECT * FROM projects WHERE slug = ?';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::mapDetail($pdo, $row) : null;
    }

    /**
     * @return list<array>
     */
    public static function galleryForProject(PDO $pdo, int $projectId): array
    {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, image_path, caption FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC'
            );
            $stmt->execute([$projectId]);
            $out = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'src' => public_upload_url((string) $row['image_path']),
                    'caption' => (string) ($row['caption'] ?? ''),
                ];
            }

            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array>
     */
    public static function related(PDO $pdo, int $projectId, string $projectType, int $limit = 4): array
    {
        $type = self::normalizeType($projectType);
        $stmt = $pdo->prepare(
            'SELECT id, slug, title, location, project_type, category, summary, hero_image
             FROM projects
             WHERE is_active = 1 AND id != ? AND (project_type = ? OR category = ?)
             ORDER BY is_featured DESC, created_at DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$projectId, $type, $type === 'commercial' ? 'commercial' : $type]);

        return array_map([self::class, 'mapListItem'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private static function mapListItem(array $row): array
    {
        $slug = (string) $row['slug'];
        $type = self::normalizeType((string) ($row['project_type'] ?? $row['category'] ?? 'residential'));
        $year = $row['completion_year'] ?? null;

        return [
            'id' => (int) ($row['id'] ?? 0),
            'slug' => $slug,
            'title' => (string) $row['title'],
            'location' => (string) ($row['location'] ?? ''),
            'category' => $type,
            'projectType' => $type,
            'summary' => (string) ($row['summary'] ?? ''),
            'heroImage' => public_upload_url((string) ($row['hero_image'] ?? '')),
            'area' => (string) ($row['area_label'] ?? ''),
            'year' => $year !== null && $year !== '' ? (int) $year : null,
            'isFeatured' => (bool) ($row['is_featured'] ?? false),
            'linkUrl' => 'project.php?slug=' . rawurlencode($slug),
        ];
    }

    private static function mapDetail(PDO $pdo, array $row): array
    {
        $id = (int) $row['id'];
        $type = self::normalizeType((string) ($row['project_type'] ?? $row['category'] ?? 'residential'));
        $gallery = self::galleryForProject($pdo, $id);
        $hero = public_upload_url((string) ($row['hero_image'] ?? ''));
        if (!$gallery && $hero !== '') {
            $gallery = [['src' => $hero, 'caption' => '']];
        }
        $year = $row['completion_year'] ?? null;

        return [
            'id' => $id,
            'slug' => (string) $row['slug'],
            'title' => (string) $row['title'],
            'location' => (string) ($row['location'] ?? ''),
            'area' => (string) ($row['area_label'] ?? ''),
            'year' => $year !== null && $year !== '' ? (int) $year : null,
            'category' => $type,
            'projectType' => $type,
            'summary' => (string) ($row['summary'] ?? ''),
            'bodyHtml' => (string) ($row['body_html'] ?? ''),
            'servicesProvided' => (string) ($row['services_provided'] ?? ''),
            'clientTestimonial' => (string) ($row['client_testimonial'] ?? ''),
            'heroImage' => $hero,
            'gallery' => $gallery,
            'seoTitle' => (string) ($row['seo_title'] ?? ''),
            'seoDescription' => (string) ($row['seo_description'] ?? ''),
            'isFeatured' => (bool) ($row['is_featured'] ?? false),
            'related' => self::related($pdo, $id, $type),
        ];
    }
}
