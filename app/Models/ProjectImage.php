<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class ProjectImage
{
    public static function add(int $projectId, string $filePath, ?string $altText, int $sortOrder = 0): void
    {
        $stmt = Db::pdo()->prepare(
            'INSERT INTO project_images (project_id, file_path, alt_text, sort_order)
             VALUES (:p, :f, :a, :s)'
        );
        $stmt->bindValue(':p', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':f', $filePath, PDO::PARAM_STR);
        $stmt->bindValue(':a', $altText,  $altText === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':s', $sortOrder, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $imageId): ?string
    {
        $stmt = Db::pdo()->prepare('SELECT file_path FROM project_images WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $imageId]);
        $path = $stmt->fetchColumn();
        if ($path === false) return null;

        $stmt = Db::pdo()->prepare('DELETE FROM project_images WHERE id = :id');
        $stmt->execute([':id' => $imageId]);
        return (string) $path;
    }

    /** @return array<int,array{id:int,file_path:string,alt_text:?string,sort_order:int}> */
    public static function forProject(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT id, file_path, alt_text, sort_order FROM project_images
              WHERE project_id = :p ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':p' => $projectId]);
        return $stmt->fetchAll();
    }
}