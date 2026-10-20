<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class Project
{
    private const PUBLISHED = "p.status = 'published'";

    private const CARD_COLS = "p.id, p.title, p.slug, p.summary, p.study_area, p.cover_image,
                               p.latitude, p.longitude, p.tags, p.published_at, p.created_at,
                               c.name AS category_name, c.slug AS category_slug, c.color AS category_color";

    private const DETAIL_COLS = "p.id, p.category_id, p.author_id, p.title, p.slug, p.summary,
                                 p.description, p.objectives, p.research_questions, p.data_sources,
                                 p.data_year, p.methodology, p.tools_used, p.findings, p.limitations,
                                 p.study_area, p.cover_image, p.latitude, p.longitude, p.zoom,
                                 p.geojson, p.tags, p.status, p.featured, p.views, p.published_at,
                                 p.created_at, p.updated_at,
                                 c.name AS category_name, c.slug AS category_slug, c.color AS category_color";

    public static function featured(int $limit = 3): array
    {
        $limit = max(1, min($limit, 12));
        $sql = "SELECT " . self::CARD_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE " . self::PUBLISHED . " AND p.featured = 1
              ORDER BY p.published_at DESC, p.id DESC
                 LIMIT :lim";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function latestPublished(int $limit = 3): array
    {
        $limit = max(1, min($limit, 12));
        $sql = "SELECT " . self::CARD_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE " . self::PUBLISHED . "
              ORDER BY p.published_at DESC, p.id DESC
                 LIMIT :lim";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function search(array $filters, int $perPage = 9): array
    {
        $category = isset($filters['category']) && $filters['category'] !== '' ? (string) $filters['category'] : null;
        $q        = isset($filters['q']) && trim((string) $filters['q']) !== '' ? trim((string) $filters['q']) : null;
        $sort     = in_array($filters['sort'] ?? 'newest', ['newest', 'oldest', 'title'], true) ? (string) $filters['sort'] : 'newest';
        $page     = max(1, (int) ($filters['page'] ?? 1));

        $where  = [self::PUBLISHED];
        $params = [];
        if ($category !== null) {
            $where[]             = 'c.slug = :category';
            $params[':category'] = $category;
        }
        if ($q !== null) {
            $where[]          = '(MATCH(p.title, p.summary, p.description, p.tags) AGAINST (:q IN NATURAL LANGUAGE MODE)
                                  OR p.study_area LIKE :qlike)';
            $params[':q']     = $q;
            $params[':qlike'] = '%' . self::escapeLike($q) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $orderSql = match ($sort) {
            'oldest' => 'ORDER BY p.published_at ASC, p.id ASC',
            'title'  => 'ORDER BY p.title ASC',
            default  => 'ORDER BY p.published_at DESC, p.id DESC',
        };

        $stmt = Db::pdo()->prepare("SELECT COUNT(*) FROM projects p INNER JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT " . self::CARD_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE {$whereSql}
                 {$orderSql}
                 LIMIT :lim OFFSET :off";
        $stmt = Db::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /** Published-only lookup. Returns null for missing OR unpublished. */
    public static function findPublishedBySlug(string $slug): ?array
    {
        $sql = "SELECT " . self::DETAIL_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE p.slug = :slug AND " . self::PUBLISHED . "
                 LIMIT 1";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Increment view counter. Failure is silent (view count is cosmetic). */
    public static function incrementViews(int $id): void
    {
        try {
            $stmt = Db::pdo()->prepare('UPDATE projects SET views = views + 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('[Project::incrementViews] ' . $e->getMessage());
        }
    }

    /** @return array<int,array{file_path:string,alt_text:?string}> */
    public static function imagesFor(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT file_path, alt_text FROM project_images WHERE project_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':id' => $projectId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array{original_name:string,file_path:string,mime_type:string,size_bytes:int}> */
    public static function filesFor(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT original_name, file_path, mime_type, size_bytes FROM project_files WHERE project_id = :id ORDER BY id ASC'
        );
        $stmt->execute([':id' => $projectId]);
        return $stmt->fetchAll();
    }

    /** Same-category published peers, newest first. */
    public static function related(int $excludeId, int $categoryId, int $limit = 3): array
    {
        $limit = max(1, min($limit, 6));
        $sql = "SELECT " . self::CARD_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE " . self::PUBLISHED . "
                   AND p.id <> :id AND p.category_id = :cat
              ORDER BY p.published_at DESC
                 LIMIT :lim";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':cat', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Fallback related feed: newest published, excluding self. */
    public static function relatedFallback(int $excludeId, int $limit = 3): array
    {
        $limit = max(1, min($limit, 6));
        $sql = "SELECT " . self::CARD_COLS . "
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE " . self::PUBLISHED . " AND p.id <> :id
              ORDER BY p.published_at DESC
                 LIMIT :lim";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }
}