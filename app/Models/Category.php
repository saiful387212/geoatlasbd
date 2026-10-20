<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class Category
{
    public static function allWithProjectCounts(): array
    {
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order,
                       COUNT(p.id) AS project_count
                  FROM categories c
             LEFT JOIN projects p
                    ON p.category_id = c.id
                   AND p.status = 'published'
              GROUP BY c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order
              ORDER BY c.sort_order ASC, c.name ASC";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Admin list — includes draft project counts. */
    public static function allWithTotals(): array
    {
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order,
                       COUNT(p.id) AS total_projects,
                       SUM(CASE WHEN p.status='published' THEN 1 ELSE 0 END) AS published_projects
                  FROM categories c
             LEFT JOIN projects p ON p.category_id = c.id
              GROUP BY c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order
              ORDER BY c.sort_order ASC, c.name ASC";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(array $data): int
    {
        $stmt = Db::pdo()->prepare(
            'INSERT INTO categories (name, slug, description, color, icon, sort_order, created_at, updated_at)
             VALUES (:name, :slug, :description, :color, :icon, :sort_order, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $stmt->bindValue(':name',        (string) $data['name'],        PDO::PARAM_STR);
        $stmt->bindValue(':slug',        (string) $data['slug'],        PDO::PARAM_STR);
        $stmt->bindValue(':description', self::nullable($data['description'] ?? null), $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':color',       self::nullable($data['color'] ?? null),       $data['color'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':icon',        self::nullable($data['icon'] ?? null),        $data['icon'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':sort_order',  (int) ($data['sort_order'] ?? 0), PDO::PARAM_INT);
        $stmt->execute();
        return (int) Db::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Db::pdo()->prepare(
            'UPDATE categories SET
                name = :name, slug = :slug, description = :description,
                color = :color, icon = :icon, sort_order = :sort_order,
                updated_at = UTC_TIMESTAMP()
              WHERE id = :id'
        );
        $stmt->bindValue(':name',        (string) $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':slug',        (string) $data['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':description', self::nullable($data['description'] ?? null), $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':color',       self::nullable($data['color'] ?? null),       $data['color'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':icon',        self::nullable($data['icon'] ?? null),        $data['icon'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':sort_order',  (int) ($data['sort_order'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':id',          $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** Returns false if any project (any status) references this category. */
    public static function delete(int $id): bool
    {
        $stmt = Db::pdo()->prepare('SELECT COUNT(*) FROM projects WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = Db::pdo()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return true;
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    private static function nullable($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }
}