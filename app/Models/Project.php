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

    // ============================================================
    // PUBLIC READ
    // ============================================================

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

    public static function incrementViews(int $id): void
    {
        try {
            $stmt = Db::pdo()->prepare('UPDATE projects SET views = views + 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('[Project::incrementViews] ' . $e->getMessage());
        }
    }

    public static function imagesFor(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT file_path, alt_text FROM project_images WHERE project_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':id' => $projectId]);
        return $stmt->fetchAll();
    }

    public static function filesFor(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT original_name, file_path, mime_type, size_bytes FROM project_files WHERE project_id = :id ORDER BY id ASC'
        );
        $stmt->execute([':id' => $projectId]);
        return $stmt->fetchAll();
    }

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

    // ============================================================
    // ADMIN — LIST
    // ============================================================

    /**
     * @param array{q?:?string, status?:?string, category?:?string, page?:int} $filters
     * @return array{rows:array<int,array<string,mixed>>, total:int}
     */
    public static function adminList(array $filters, int $perPage = 20): array
    {
        $q        = isset($filters['q'])        && trim((string) $filters['q']) !== '' ? trim((string) $filters['q']) : null;
        $status   = isset($filters['status'])   && in_array($filters['status'], ['draft','published'], true) ? (string) $filters['status'] : null;
        $category = isset($filters['category']) && $filters['category'] !== '' ? (string) $filters['category'] : null;
        $page     = max(1, (int) ($filters['page'] ?? 1));

        $where  = ['1=1'];
        $params = [];
        if ($q !== null) {
            $where[]          = '(p.title LIKE :qlike OR p.slug LIKE :qlike OR p.study_area LIKE :qlike)';
            $params[':qlike'] = '%' . self::escapeLike($q) . '%';
        }
        if ($status !== null) {
            $where[]           = 'p.status = :status';
            $params[':status'] = $status;
        }
        if ($category !== null) {
            $where[]             = 'c.slug = :category';
            $params[':category'] = $category;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = Db::pdo()->prepare("SELECT COUNT(*) FROM projects p INNER JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.id, p.title, p.slug, p.status, p.featured, p.cover_image,
                       p.study_area, p.published_at, p.updated_at, p.created_at,
                       c.name AS category_name, c.slug AS category_slug, c.color AS category_color
                  FROM projects p
            INNER JOIN categories c ON c.id = p.category_id
                 WHERE {$whereSql}
              ORDER BY p.updated_at DESC, p.id DESC
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

    /** Admin edit lookup — returns draft too, by id. */
    public static function adminFind(int $id): ?array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT * FROM projects WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    // ============================================================
    // ADMIN — WRITE
    // ============================================================

    /**
     * @param array<string,mixed> $data
     * @return int  new project id
     */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO projects
                    (category_id, author_id, title, slug, summary, description,
                     objectives, research_questions, data_sources, data_year,
                     methodology, tools_used, findings, limitations,
                     study_area, cover_image, latitude, longitude, zoom, tags,
                     status, featured, published_at, created_at, updated_at)
                VALUES
                    (:category_id, :author_id, :title, :slug, :summary, :description,
                     :objectives, :research_questions, :data_sources, :data_year,
                     :methodology, :tools_used, :findings, :limitations,
                     :study_area, :cover_image, :latitude, :longitude, :zoom, :tags,
                     :status, :featured, :published_at, UTC_TIMESTAMP(), UTC_TIMESTAMP())';
        $stmt = Db::pdo()->prepare($sql);

        $stmt->bindValue(':category_id',        (int)  $data['category_id'],       PDO::PARAM_INT);
        $stmt->bindValue(':author_id',          (int)  $data['author_id'],         PDO::PARAM_INT);
        $stmt->bindValue(':title',              (string) $data['title'],           PDO::PARAM_STR);
        $stmt->bindValue(':slug',               (string) $data['slug'],            PDO::PARAM_STR);
        $stmt->bindValue(':summary',            (string) $data['summary'],         PDO::PARAM_STR);
        $stmt->bindValue(':description',        (string) ($data['description'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':objectives',         self::nullable($data['objectives'] ?? null),         $data['objectives'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':research_questions', self::nullable($data['research_questions'] ?? null), $data['research_questions'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':data_sources',       self::nullable($data['data_sources'] ?? null),       $data['data_sources'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':data_year',          self::nullable($data['data_year'] ?? null),          $data['data_year'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':methodology',        self::nullable($data['methodology'] ?? null),        $data['methodology'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':tools_used',         self::nullable($data['tools_used'] ?? null),         $data['tools_used'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':findings',           self::nullable($data['findings'] ?? null),           $data['findings'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':limitations',        self::nullable($data['limitations'] ?? null),        $data['limitations'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':study_area',         self::nullable($data['study_area'] ?? null),         $data['study_area'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':cover_image',        self::nullable($data['cover_image'] ?? null),        $data['cover_image'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':latitude',           $data['latitude']  === null ? null : (string) $data['latitude'],  $data['latitude']  === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':longitude',          $data['longitude'] === null ? null : (string) $data['longitude'], $data['longitude'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':zoom',               (int) ($data['zoom'] ?? 8),  PDO::PARAM_INT);
        $stmt->bindValue(':tags',               self::nullable($data['tags'] ?? null),  $data['tags'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status',             (string) $data['status'],             PDO::PARAM_STR);
        $stmt->bindValue(':featured',           (int) $data['featured'],              PDO::PARAM_INT);
        $stmt->bindValue(':published_at',       $data['published_at'] ?? null,        $data['published_at'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) Db::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE projects SET
                    category_id = :category_id,
                    title       = :title,
                    slug        = :slug,
                    summary     = :summary,
                    description = :description,
                    objectives  = :objectives,
                    research_questions = :research_questions,
                    data_sources = :data_sources,
                    data_year    = :data_year,
                    methodology  = :methodology,
                    tools_used   = :tools_used,
                    findings     = :findings,
                    limitations  = :limitations,
                    study_area   = :study_area,
                    cover_image  = :cover_image,
                    latitude     = :latitude,
                    longitude    = :longitude,
                    zoom         = :zoom,
                    tags         = :tags,
                    status       = :status,
                    featured     = :featured,
                    published_at = :published_at,
                    updated_at   = UTC_TIMESTAMP()
                WHERE id = :id';
        $stmt = Db::pdo()->prepare($sql);

        $stmt->bindValue(':category_id',        (int) $data['category_id'],  PDO::PARAM_INT);
        $stmt->bindValue(':title',              (string) $data['title'],     PDO::PARAM_STR);
        $stmt->bindValue(':slug',               (string) $data['slug'],      PDO::PARAM_STR);
        $stmt->bindValue(':summary',            (string) $data['summary'],   PDO::PARAM_STR);
        $stmt->bindValue(':description',        (string) ($data['description'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':objectives',         self::nullable($data['objectives'] ?? null),         $data['objectives'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':research_questions', self::nullable($data['research_questions'] ?? null), $data['research_questions'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':data_sources',       self::nullable($data['data_sources'] ?? null),       $data['data_sources'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':data_year',          self::nullable($data['data_year'] ?? null),          $data['data_year'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':methodology',        self::nullable($data['methodology'] ?? null),        $data['methodology'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':tools_used',         self::nullable($data['tools_used'] ?? null),         $data['tools_used'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':findings',           self::nullable($data['findings'] ?? null),           $data['findings'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':limitations',        self::nullable($data['limitations'] ?? null),        $data['limitations'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':study_area',         self::nullable($data['study_area'] ?? null),         $data['study_area'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':cover_image',        self::nullable($data['cover_image'] ?? null),        $data['cover_image'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':latitude',           $data['latitude']  === null ? null : (string) $data['latitude'],  $data['latitude']  === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':longitude',          $data['longitude'] === null ? null : (string) $data['longitude'], $data['longitude'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':zoom',               (int) ($data['zoom'] ?? 8),   PDO::PARAM_INT);
        $stmt->bindValue(':tags',               self::nullable($data['tags'] ?? null),               $data['tags'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status',             (string) $data['status'],    PDO::PARAM_STR);
        $stmt->bindValue(':featured',           (int) $data['featured'],     PDO::PARAM_INT);
        $stmt->bindValue(':published_at',       $data['published_at'] ?? null, $data['published_at'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id',                 $id,                        PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Delete a project and return its stored file paths so the controller
     * can remove the files from disk after the DB transaction commits.
     *
     * @return array{cover_image:?string, images:string[], files:string[]}
     */
    public static function delete(int $id): array
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT cover_image FROM projects WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $cover = $stmt->fetchColumn();
            $cover = ($cover === false) ? null : (string) $cover;

            $stmt = $pdo->prepare('SELECT file_path FROM project_images WHERE project_id = :id');
            $stmt->execute([':id' => $id]);
            $images = array_map(fn($r) => (string) $r['file_path'], $stmt->fetchAll());

            $stmt = $pdo->prepare('SELECT file_path FROM project_files WHERE project_id = :id');
            $stmt->execute([':id' => $id]);
            $files = array_map(fn($r) => (string) $r['file_path'], $stmt->fetchAll());

            // project_images and project_files ON DELETE CASCADE handles children.
            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $pdo->commit();
            return ['cover_image' => $cover, 'images' => $images, 'files' => $files];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['draft', 'published'], true)) {
            throw new \InvalidArgumentException('Invalid status.');
        }
        $publishedAt = $status === 'published' ? 'COALESCE(published_at, UTC_TIMESTAMP())' : 'NULL';
        $stmt = Db::pdo()->prepare(
            "UPDATE projects SET status = :s, published_at = {$publishedAt}, updated_at = UTC_TIMESTAMP() WHERE id = :id"
        );
        $stmt->execute([':s' => $status, ':id' => $id]);
    }

    public static function setFeatured(int $id, bool $featured): void
    {
        $stmt = Db::pdo()->prepare('UPDATE projects SET featured = :f, updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $stmt->bindValue(':f', $featured ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private static function nullable($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }

    private static function escapeLike(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }
}